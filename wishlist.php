<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = (int)$_SESSION['user_id'];

// ── Auto-create wishlists table if not exists ──
$conn->exec("
    CREATE TABLE IF NOT EXISTS wishlists (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_user_product (user_id, product_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// ── User info ──
$stmtUser = $conn->prepare('SELECT username, email, phone, avatar FROM users WHERE id = ? LIMIT 1');
$stmtUser->execute([$userId]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

// ── Filter & Search ──
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$sortOptions = ['newest' => 'w.created_at DESC', 'oldest' => 'w.created_at ASC', 'price_asc' => 'p.price ASC', 'price_desc' => 'p.price DESC'];
$sort = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sortOptions) ? $_GET['sort'] : 'newest';
$sortSql = $sortOptions[$sort];

// ── Pagination ──
$perPage = 12;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

// ── Category filter ──
$categoryId = isset($_GET['cat']) && (int)$_GET['cat'] > 0 ? (int)$_GET['cat'] : 0;

// ── Count query ──
$countSql = "SELECT COUNT(*) FROM wishlists w
             JOIN products p ON w.product_id = p.id
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE w.user_id = ?";
$countParams = [$userId];
if ($search) { $countSql .= " AND p.name LIKE ?"; $countParams[] = "%$search%"; }
if ($categoryId) { $countSql .= " AND p.category_id = ?"; $countParams[] = $categoryId; }
$stmtCount = $conn->prepare($countSql);
$stmtCount->execute($countParams);
$totalItems = (int)$stmtCount->fetchColumn();
$totalPages = max(1, (int)ceil($totalItems / $perPage));

// ── Fetch wishlisted products ──
$dataSql = "SELECT w.id AS wishlist_id, w.created_at AS added_at,
                   p.id AS product_id, p.name, p.price, p.image, p.short_note,
                   c.name AS category_name
            FROM wishlists w
            JOIN products p ON w.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE w.user_id = ?";
$dataParams = [$userId];
if ($search) { $dataSql .= " AND p.name LIKE ?"; $dataParams[] = "%$search%"; }
if ($categoryId) { $dataSql .= " AND p.category_id = ?"; $dataParams[] = $categoryId; }
$dataSql .= " ORDER BY $sortSql LIMIT $perPage OFFSET $offset";
$stmtData = $conn->prepare($dataSql);
$stmtData->execute($dataParams);
$wishlistItems = $stmtData->fetchAll(PDO::FETCH_ASSOC);

// ── Total count (all, no filter) ──
$stmtTotal = $conn->prepare("SELECT COUNT(*) FROM wishlists WHERE user_id = ?");
$stmtTotal->execute([$userId]);
$totalAll = (int)$stmtTotal->fetchColumn();

// ── Price range stats ──
$stmtStats = $conn->prepare("SELECT MIN(p.price) AS min_price, MAX(p.price) AS max_price, AVG(p.price) AS avg_price
    FROM wishlists w JOIN products p ON w.product_id = p.id WHERE w.user_id = ?");
$stmtStats->execute([$userId]);
$priceStats = $stmtStats->fetch(PDO::FETCH_ASSOC);

// ── Categories in wishlist ──
$stmtCats = $conn->prepare("SELECT c.id, c.name, COUNT(*) AS cnt
    FROM wishlists w JOIN products p ON w.product_id = p.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE w.user_id = ? GROUP BY c.id, c.name ORDER BY cnt DESC");
$stmtCats->execute([$userId]);
$wishlistCats = $stmtCats->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Sản Phẩm Yêu Thích | QHTN';
include 'header.php';
?>

<style>
/* ============================================================
   WISHLIST PAGE — QHTN CORPORATE EDITION
   Đồng bộ với orders.php · No border-radius · Pink-Burgundy
============================================================ */
.wl-page { background: #f8f5f6; min-height: 80vh; font-family: 'Montserrat', sans-serif; }

/* ── BANNER ── */
.wl-banner {
    background:
        linear-gradient(105deg, rgba(47,28,38,0.95) 0%, rgba(90,33,56,0.88) 55%, rgba(139,48,87,0.75) 100%),
        url('img/avatars/hero.webp') center 30% / cover no-repeat;
    padding: 36px 5%;
}
.wl-banner-inner { max-width: 1280px; margin: 0 auto; }
.wl-banner-kicker { font-size: 10px; font-weight: 700; letter-spacing: 4px; text-transform: uppercase; color: rgba(255,255,255,0.4); margin-bottom: 6px; }
.wl-banner-title { font-size: 28px; font-weight: 900; color: #fff; text-transform: uppercase; letter-spacing: -0.5px; }

/* ── LAYOUT ── */
.wl-main { max-width: 1280px; margin: 0 auto; padding: 32px 5%; display: grid; grid-template-columns: 260px 1fr; gap: 24px; align-items: start; }

/* ── SIDEBAR ── */
.wl-sidebar-card { background: #fff; border: 1px solid #ecdde4; }
.wl-user-mini { padding: 22px 20px; border-bottom: 1px solid #ecdde4; display: flex; align-items: center; gap: 14px; }
.wl-user-avatar { width: 48px; height: 48px; flex-shrink: 0; object-fit: cover; object-position: top; border: 2px solid #f7c8d9; }
.wl-user-name { font-size: 13px; font-weight: 800; color: #2f1c26; text-transform: uppercase; line-height: 1.2; }
.wl-user-email { font-size: 11px; color: #bbb; margin-top: 2px; }
.wl-nav { padding: 6px 0; }
.wl-nav a { display: flex; align-items: center; gap: 11px; padding: 12px 20px; font-size: 12.5px; font-weight: 600; color: #666; text-decoration: none; border-left: 3px solid transparent; transition: all 0.2s; }
.wl-nav a:hover { color: var(--accent-pink); background: #fff8fb; border-left-color: #f7c8d9; }
.wl-nav a.active { color: var(--accent-pink); background: #fff0f5; border-left-color: var(--accent-pink); }
.wl-nav a i { width: 16px; text-align: center; font-size: 13px; color: var(--accent-pink); }
.wl-nav-sep { height: 1px; background: #ecdde4; margin: 4px 0; }
.wl-nav a.wl-logout { color: #e84a5f; }
.wl-nav a.wl-logout i { color: #e84a5f; }
.wl-nav a.wl-logout:hover { background: #fff5f5; border-left-color: #e84a5f; }

/* Sidebar Stats Box */
.wl-sidebar-stats { padding: 18px 20px; border-bottom: 1px solid #ecdde4; }
.wl-sidebar-stats-title { font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; color: #bbb; margin-bottom: 14px; }
.wl-sidebar-stat-row { display: flex; justify-content: space-between; align-items: center; padding: 7px 0; border-bottom: 1px dotted #f5eff2; }
.wl-sidebar-stat-row:last-child { border-bottom: none; }
.wl-sidebar-stat-label { font-size: 11px; color: #888; }
.wl-sidebar-stat-value { font-size: 13px; font-weight: 800; color: var(--accent-pink); }

/* Sidebar Category Filter */
.wl-cat-filter { padding: 18px 20px; }
.wl-cat-filter-title { font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; color: #bbb; margin-bottom: 12px; }
.wl-cat-item { display: flex; align-items: center; justify-content: space-between; padding: 8px 10px; font-size: 12px; font-weight: 600; color: #666; text-decoration: none; transition: all 0.18s; border-left: 2px solid transparent; margin-bottom: 2px; }
.wl-cat-item:hover { color: var(--accent-pink); background: #fff8fb; border-left-color: #f7c8d9; }
.wl-cat-item.active { color: var(--accent-pink); background: #fff0f5; border-left-color: var(--accent-pink); }
.wl-cat-badge { background: #f5eff2; color: #888; font-size: 10px; font-weight: 700; padding: 2px 7px; min-width: 22px; text-align: center; }
.wl-cat-item.active .wl-cat-badge { background: var(--accent-pink); color: #fff; }

/* ── CONTENT ── */
.wl-content { display: flex; flex-direction: column; gap: 20px; }

/* Stats Strip */
.wl-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0; border: 1px solid #ecdde4; background: #fff; }
.wl-stat { padding: 18px 14px; border-right: 1px solid #ecdde4; text-align: center; }
.wl-stat:last-child { border-right: none; }
.wl-stat-icon { font-size: 18px; color: #f7c8d9; margin-bottom: 8px; }
.wl-stat-num { font-size: 22px; font-weight: 900; color: #2f1c26; line-height: 1; margin-bottom: 4px; }
.wl-stat-label { font-size: 10px; color: #aaa; font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase; }

/* Filter/Search Bar */
.wl-filter-bar { background: #fff; border: 1px solid #ecdde4; padding: 16px 20px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.wl-search-wrap { display: flex; align-items: center; flex: 1; min-width: 200px; border: 1.5px solid #ecdde4; }
.wl-search-wrap input { flex: 1; padding: 10px 14px; font-size: 13px; font-family: 'Montserrat', sans-serif; color: #2f1c26; border: none; outline: none; }
.wl-search-wrap button { padding: 10px 16px; background: #2f1c26; color: #fff; border: none; cursor: pointer; font-size: 13px; transition: background 0.2s; }
.wl-search-wrap button:hover { background: var(--accent-pink); }
.wl-sort-select { padding: 10px 14px; font-size: 12px; font-family: 'Montserrat', sans-serif; font-weight: 600; color: #2f1c26; border: 1.5px solid #ecdde4; background: #fff; outline: none; cursor: pointer; }
.wl-sort-select:focus { border-color: var(--accent-pink); }
.wl-clear-btn { padding: 10px 18px; font-size: 10px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; border: 1.5px solid #ffd0d5; color: #e84a5f; background: transparent; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.2s; white-space: nowrap; }
.wl-clear-btn:hover { background: #e84a5f; color: #fff; border-color: #e84a5f; }

/* Meta Row */
.wl-meta { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
.wl-meta-count { font-size: 12px; color: #999; font-weight: 600; }
.wl-meta-count strong { color: #2f1c26; }
.wl-meta-right { font-size: 12px; color: #bbb; display: flex; align-items: center; gap: 6px; }

/* Product Grid */
.wl-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }

/* Product Card */
.wl-card { background: #fff; border: 1px solid #ecdde4; position: relative; transition: box-shadow 0.25s, transform 0.2s; overflow: hidden; }
.wl-card:hover { box-shadow: 0 8px 28px rgba(233,90,138,0.13); transform: translateY(-2px); }

.wl-card-img-wrap { position: relative; overflow: hidden; aspect-ratio: 3/4; background: #f5eff2; }
.wl-card-img { width: 100%; height: 100%; object-fit: cover; object-position: top; transition: transform 0.4s ease; display: block; }
.wl-card:hover .wl-card-img { transform: scale(1.04); }
.wl-card-cat { position: absolute; top: 10px; left: 10px; background: rgba(47,28,38,0.82); color: #fff; font-size: 8px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; padding: 4px 8px; backdrop-filter: blur(4px); }
.wl-card-date { position: absolute; bottom: 10px; left: 10px; background: rgba(255,255,255,0.92); color: #aaa; font-size: 9px; font-weight: 600; padding: 3px 8px; }
.wl-card-remove { position: absolute; top: 10px; right: 10px; width: 30px; height: 30px; background: rgba(255,255,255,0.92); border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #e84a5f; font-size: 14px; transition: all 0.2s; backdrop-filter: blur(4px); }
.wl-card-remove:hover { background: #e84a5f; color: #fff; }

.wl-card-body { padding: 14px 16px; }
.wl-card-name { font-size: 12.5px; font-weight: 700; color: #2f1c26; margin-bottom: 6px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.wl-card-note { font-size: 10.5px; color: #bbb; margin-bottom: 10px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.wl-card-foot { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding-top: 10px; border-top: 1px solid #f5eff2; }
.wl-card-price { font-size: 14px; font-weight: 900; color: var(--accent-pink); }
.wl-card-price small { font-size: 10px; color: #bbb; font-weight: 500; margin-left: 2px; }
.wl-card-btn { padding: 7px 14px; font-size: 9px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; background: #2f1c26; color: #fff; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; transition: background 0.2s; white-space: nowrap; }
.wl-card-btn:hover { background: var(--accent-pink); }

/* Empty State */
.wl-empty { padding: 72px 20px; text-align: center; background: #fff; border: 1px solid #ecdde4; }
.wl-empty i { font-size: 52px; color: #f7c8d9; margin-bottom: 16px; display: block; }
.wl-empty h3 { font-size: 16px; font-weight: 800; color: #2f1c26; text-transform: uppercase; margin-bottom: 8px; }
.wl-empty p { font-size: 13px; color: #bbb; margin-bottom: 24px; }
.wl-empty a { display: inline-block; padding: 14px 36px; background: var(--accent-pink); color: #fff; font-size: 11px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; text-decoration: none; transition: background 0.2s; }
.wl-empty a:hover { background: var(--hover-pink); }

/* Pagination */
.wl-pagination { display: flex; justify-content: center; align-items: center; gap: 4px; flex-wrap: wrap; }
.wl-page-btn { display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 36px; padding: 0 10px; font-size: 12px; font-weight: 700; color: #888; background: #fff; border: 1.5px solid #ecdde4; text-decoration: none; transition: all 0.2s; }
.wl-page-btn:hover { border-color: var(--accent-pink); color: var(--accent-pink); }
.wl-page-btn.active { background: var(--accent-pink); border-color: var(--accent-pink); color: #fff; }
.wl-page-btn.disabled { opacity: 0.4; pointer-events: none; }

/* Confirm Modal */
.wl-modal { display: none; position: fixed; inset: 0; z-index: 9999; align-items: center; justify-content: center; background: rgba(47,28,38,0.7); backdrop-filter: blur(4px); }
.wl-modal.open { display: flex; }
.wl-modal-box { background: #fff; max-width: 400px; width: 90%; }
.wl-modal-head { padding: 20px 24px; background: #2f1c26; display: flex; align-items: center; justify-content: space-between; }
.wl-modal-head span { font-size: 12px; font-weight: 800; color: #fff; letter-spacing: 2px; text-transform: uppercase; }
.wl-modal-close { background: none; border: none; color: rgba(255,255,255,0.5); font-size: 22px; cursor: pointer; padding: 0; line-height: 1; transition: color 0.2s; }
.wl-modal-close:hover { color: #fff; }
.wl-modal-body { padding: 24px; font-size: 14px; color: #555; line-height: 1.7; }
.wl-modal-body strong { color: #2f1c26; display: block; margin-bottom: 8px; font-size: 16px; }
.wl-modal-foot { padding: 0 24px 24px; display: flex; gap: 10px; justify-content: flex-end; }
.wl-modal-cancel { padding: 11px 24px; background: transparent; border: 1.5px solid #ecdde4; color: #888; font-size: 11px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; cursor: pointer; transition: all 0.2s; }
.wl-modal-cancel:hover { border-color: #2f1c26; color: #2f1c26; }
.wl-modal-confirm { padding: 11px 28px; background: #e84a5f; color: #fff; border: none; font-size: 11px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; cursor: pointer; transition: background 0.2s; }
.wl-modal-confirm:hover { background: #c0392b; }

/* Toast */
.wl-toast { position: fixed; bottom: 28px; right: 28px; z-index: 10000; padding: 14px 24px; background: #2f1c26; color: #fff; font-size: 13px; font-weight: 600; box-shadow: 0 8px 32px rgba(0,0,0,0.25); transform: translateY(20px); opacity: 0; transition: all 0.35s; pointer-events: none; }
.wl-toast.show { transform: translateY(0); opacity: 1; }
.wl-toast.success { border-left: 4px solid #3fb27f; }
.wl-toast.error   { border-left: 4px solid #e84a5f; }
.wl-toast.info    { border-left: 4px solid var(--accent-pink); }

/* Responsive */
@media (max-width: 1024px) { .wl-main { grid-template-columns: 1fr; } }
@media (max-width: 900px) { .wl-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 520px) { .wl-grid { grid-template-columns: 1fr; } .wl-stats { grid-template-columns: repeat(3, 1fr); } }
</style>

<div class="wl-page">

<!-- ── BANNER ── -->
<div class="wl-banner">
    <div class="wl-banner-inner">
        <div class="wl-banner-kicker">Tài khoản của bạn</div>
        <div class="wl-banner-title">Sản phẩm yêu thích</div>
    </div>
</div>

<!-- ── MAIN ── -->
<div class="wl-main">

    <!-- ── SIDEBAR ── -->
    <aside>
        <div class="wl-sidebar-card">
            <!-- User Mini -->
            <div class="wl-user-mini">
                <?php
                $avatarSrc = !empty($user['avatar']) && file_exists($user['avatar'])
                    ? $user['avatar'] : 'img/avatars/hero.webp';
                ?>
                <img class="wl-user-avatar" src="<?= htmlspecialchars($avatarSrc) ?>" alt="Avatar">
                <div>
                    <div class="wl-user-name"><?= htmlspecialchars($user['username'] ?? 'Thành viên') ?></div>
                    <div class="wl-user-email"><?= htmlspecialchars($user['email'] ?? '') ?></div>
                </div>
            </div>

            <!-- Nav -->
            <nav class="wl-nav">
                <a href="profile.php"><i class="fa-solid fa-circle-user"></i> Hồ sơ cá nhân</a>
                <a href="orders.php"><i class="fa-solid fa-receipt"></i> Đơn hàng của tôi</a>
                <a href="wishlist.php" class="active"><i class="fa-solid fa-heart"></i> Sản phẩm yêu thích</a>
                <a href="membership.php"><i class="fa-solid fa-crown"></i> Quyền lợi thành viên</a>
                <div class="wl-nav-sep"></div>
                <a href="logout.php" class="wl-logout"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a>
            </nav>

            <!-- Stats -->
            <?php if ($totalAll > 0): ?>
            <div class="wl-sidebar-stats">
                <div class="wl-sidebar-stats-title">Thống kê yêu thích</div>
                <div class="wl-sidebar-stat-row">
                    <span class="wl-sidebar-stat-label">Tổng sản phẩm</span>
                    <span class="wl-sidebar-stat-value"><?= $totalAll ?></span>
                </div>
                <?php if ($priceStats['min_price']): ?>
                <div class="wl-sidebar-stat-row">
                    <span class="wl-sidebar-stat-label">Giá thấp nhất</span>
                    <span class="wl-sidebar-stat-value"><?= number_format($priceStats['min_price']) ?>đ</span>
                </div>
                <div class="wl-sidebar-stat-row">
                    <span class="wl-sidebar-stat-label">Giá cao nhất</span>
                    <span class="wl-sidebar-stat-value"><?= number_format($priceStats['max_price']) ?>đ</span>
                </div>
                <div class="wl-sidebar-stat-row">
                    <span class="wl-sidebar-stat-label">Giá trung bình</span>
                    <span class="wl-sidebar-stat-value"><?= number_format((int)$priceStats['avg_price']) ?>đ</span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Category Filter -->
            <?php if (!empty($wishlistCats)): ?>
            <div class="wl-cat-filter">
                <div class="wl-cat-filter-title">Lọc theo danh mục</div>
                <a href="wishlist.php<?= $search ? '?q='.urlencode($search) : '' ?>"
                   class="wl-cat-item <?= $categoryId === 0 ? 'active' : '' ?>">
                    <span>Tất cả</span>
                    <span class="wl-cat-badge"><?= $totalAll ?></span>
                </a>
                <?php foreach ($wishlistCats as $cat): ?>
                <a href="wishlist.php?cat=<?= $cat['id'] ?><?= $search ? '&q='.urlencode($search) : '' ?>"
                   class="wl-cat-item <?= $categoryId === (int)$cat['id'] ? 'active' : '' ?>">
                    <span><?= htmlspecialchars($cat['name'] ?? 'Khác') ?></span>
                    <span class="wl-cat-badge"><?= $cat['cnt'] ?></span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </aside>

    <!-- ── CONTENT ── -->
    <div class="wl-content">

        <!-- Stats Strip -->
        <div class="wl-stats">
            <div class="wl-stat">
                <div class="wl-stat-icon"><i class="fa-solid fa-heart"></i></div>
                <div class="wl-stat-num"><?= $totalAll ?></div>
                <div class="wl-stat-label">Đã lưu</div>
            </div>
            <div class="wl-stat">
                <div class="wl-stat-icon"><i class="fa-solid fa-tags"></i></div>
                <div class="wl-stat-num"><?= count($wishlistCats) ?></div>
                <div class="wl-stat-label">Danh mục</div>
            </div>
            <div class="wl-stat">
                <div class="wl-stat-icon"><i class="fa-solid fa-circle-dollar-to-slot"></i></div>
                <div class="wl-stat-num"><?= $priceStats['min_price'] ? number_format((int)$priceStats['min_price']).'đ' : '—' ?></div>
                <div class="wl-stat-label">Giá thấp nhất/ngày</div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="wl-filter-bar">
            <form method="GET" action="wishlist.php" style="display:flex;gap:8px;flex:1;min-width:200px;align-items:center">
                <?php if ($categoryId): ?><input type="hidden" name="cat" value="<?= $categoryId ?>"><?php endif; ?>
                <div class="wl-search-wrap" style="flex:1">
                    <input type="text" name="q" placeholder="Tìm tên sản phẩm..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                </div>
            </form>
            <select class="wl-sort-select" id="sortSelect" onchange="applySort(this.value)">
                <option value="newest" <?= $sort==='newest' ? 'selected' : '' ?>>Mới thêm nhất</option>
                <option value="oldest" <?= $sort==='oldest' ? 'selected' : '' ?>>Thêm sớm nhất</option>
                <option value="price_asc" <?= $sort==='price_asc' ? 'selected' : '' ?>>Giá tăng dần</option>
                <option value="price_desc" <?= $sort==='price_desc' ? 'selected' : '' ?>>Giá giảm dần</option>
            </select>
            <?php if ($totalAll > 0): ?>
            <button class="wl-clear-btn" onclick="openClearModal()">
                <i class="fa-solid fa-trash-can"></i> Xóa tất cả
            </button>
            <?php endif; ?>
        </div>

        <!-- Meta Row -->
        <div class="wl-meta">
            <div class="wl-meta-count">
                Hiển thị <strong><?= count($wishlistItems) ?></strong> / <strong><?= $totalItems ?></strong> sản phẩm<?= $search ? ' cho "' . htmlspecialchars($search) . '"' : '' ?>
            </div>
            <div class="wl-meta-right">
                <i class="fa-solid fa-heart" style="color:var(--accent-pink);font-size:10px"></i>
                Cập nhật lần cuối: <?= !empty($wishlistItems) ? date('d/m/Y', strtotime($wishlistItems[0]['added_at'])) : '—' ?>
            </div>
        </div>

        <!-- Product Grid -->
        <?php if (!empty($wishlistItems)): ?>
        <div class="wl-grid" id="wlGrid">
            <?php foreach ($wishlistItems as $item):
                $imgSrc = !empty($item['image']) ? htmlspecialchars($item['image']) : 'img/default.jpg';
                $addedDate = date('d/m/Y', strtotime($item['added_at']));
            ?>
            <div class="wl-card" id="wl-item-<?= $item['product_id'] ?>">
                <div class="wl-card-img-wrap">
                    <a href="product_detail.php?id=<?= $item['product_id'] ?>">
                        <img class="wl-card-img"
                             src="<?= $imgSrc ?>"
                             alt="<?= htmlspecialchars($item['name']) ?>"
                             onerror="this.src='img/default.jpg'">
                    </a>
                    <?php if ($item['category_name']): ?>
                    <span class="wl-card-cat"><?= htmlspecialchars($item['category_name']) ?></span>
                    <?php endif; ?>
                    <span class="wl-card-date"><i class="fa-regular fa-calendar" style="margin-right:4px"></i><?= $addedDate ?></span>
                    <button class="wl-card-remove" onclick="removeItem(<?= $item['product_id'] ?>, <?= $item['wishlist_id'] ?>)" title="Xóa khỏi yêu thích">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="wl-card-body">
                    <div class="wl-card-name"><?= htmlspecialchars($item['name']) ?></div>
                    <?php if (!empty($item['short_note'])): ?>
                    <div class="wl-card-note"><?= htmlspecialchars($item['short_note']) ?></div>
                    <?php endif; ?>
                    <div class="wl-card-foot">
                        <div class="wl-card-price">
                            <?= number_format($item['price']) ?>đ<small>/ngày</small>
                        </div>
                        <a href="product_detail.php?id=<?= $item['product_id'] ?>" class="wl-card-btn">
                            <i class="fa-solid fa-bag-shopping"></i> Thuê ngay
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php else: ?>
        <div class="wl-empty">
            <i class="fa-regular fa-heart"></i>
            <h3><?= $search ? 'Không tìm thấy sản phẩm' : 'Danh sách yêu thích trống' ?></h3>
            <p><?= $search ? 'Thử từ khóa khác hoặc xóa bộ lọc.' : 'Hãy thêm những sản phẩm bạn thích vào đây để dễ dàng thuê sau.' ?></p>
            <a href="<?= $search ? 'wishlist.php' : 'ao_dai.php' ?>">
                <?= $search ? 'Xem tất cả yêu thích' : 'Khám phá bộ sưu tập' ?>
            </a>
        </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="wl-pagination">
            <a href="?cat=<?= $categoryId ?>&q=<?= urlencode($search) ?>&sort=<?= $sort ?>&page=<?= max(1,$page-1) ?>"
               class="wl-page-btn <?= $page<=1?'disabled':'' ?>">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
            <?php for ($p = max(1,$page-2); $p <= min($totalPages,$page+2); $p++): ?>
            <a href="?cat=<?= $categoryId ?>&q=<?= urlencode($search) ?>&sort=<?= $sort ?>&page=<?= $p ?>"
               class="wl-page-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a>
            <?php endfor; ?>
            <a href="?cat=<?= $categoryId ?>&q=<?= urlencode($search) ?>&sort=<?= $sort ?>&page=<?= min($totalPages,$page+1) ?>"
               class="wl-page-btn <?= $page>=$totalPages?'disabled':'' ?>">
                <i class="fa-solid fa-chevron-right"></i>
            </a>
        </div>
        <?php endif; ?>

    </div><!-- .wl-content -->
</div><!-- .wl-main -->
</div><!-- .wl-page -->

<!-- Clear All Modal -->
<div class="wl-modal" id="clearModal">
    <div class="wl-modal-box">
        <div class="wl-modal-head">
            <span><i class="fa-solid fa-trash-can"></i>&ensp;Xóa tất cả yêu thích</span>
            <button class="wl-modal-close" onclick="closeClearModal()">&times;</button>
        </div>
        <div class="wl-modal-body">
            <strong>Xác nhận xóa tất cả?</strong>
            Tất cả <?= $totalAll ?> sản phẩm yêu thích sẽ bị xóa khỏi danh sách. Thao tác này không thể hoàn tác.
        </div>
        <div class="wl-modal-foot">
            <button class="wl-modal-cancel" onclick="closeClearModal()">Không, giữ lại</button>
            <button class="wl-modal-confirm" id="clearConfirmBtn">Xóa tất cả</button>
        </div>
    </div>
</div>

<div class="wl-toast" id="wlToast"></div>

<script>
(function () {
    function toast(msg, type = 'success') {
        const el = document.getElementById('wlToast');
        el.textContent = msg;
        el.className = 'wl-toast ' + type + ' show';
        setTimeout(() => el.classList.remove('show'), 3200);
    }

    // ── Sort ──
    window.applySort = function(val) {
        const url = new URL(window.location.href);
        url.searchParams.set('sort', val);
        url.searchParams.set('page', '1');
        window.location = url.toString();
    };

    // ── Remove single item ──
    window.removeItem = function(productId, wishlistId) {
        fetch('wishlist_ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=remove&product_id=' + productId
        })
        .then(r => r.json())
        .then(d => {
            if (d.status === 'success') {
                const card = document.getElementById('wl-item-' + productId);
                if (card) {
                    card.style.transition = 'all 0.35s ease';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.9)';
                    setTimeout(() => {
                        card.remove();
                        updateHeartState(productId, false);
                        // If grid empty, reload to show empty state
                        const grid = document.getElementById('wlGrid');
                        if (grid && grid.children.length === 0) {
                            setTimeout(() => location.reload(), 300);
                        }
                    }, 350);
                }
                toast('Đã xóa khỏi danh sách yêu thích.', 'info');
            } else {
                toast(d.message || 'Không thể xóa sản phẩm này.', 'error');
            }
        })
        .catch(() => toast('Lỗi kết nối, vui lòng thử lại.', 'error'));
    };

    // ── Clear All Modal ──
    window.openClearModal = function() {
        document.getElementById('clearModal').classList.add('open');
    };
    window.closeClearModal = function() {
        document.getElementById('clearModal').classList.remove('open');
    };

    const clearBtn = document.getElementById('clearConfirmBtn');
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            const btn = this;
            btn.disabled = true;
            btn.textContent = 'Đang xóa...';
            fetch('wishlist_ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=clear'
            })
            .then(r => r.json())
            .then(d => {
                if (d.status === 'success') {
                    closeClearModal();
                    toast('Đã xóa tất cả sản phẩm yêu thích.', 'info');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toast(d.message || 'Không thể xóa.', 'error');
                    btn.disabled = false;
                    btn.textContent = 'Xóa tất cả';
                }
            })
            .catch(() => {
                toast('Lỗi kết nối.', 'error');
                btn.disabled = false;
                btn.textContent = 'Xóa tất cả';
            });
        });
    }

    // ── Close modal on backdrop click ──
    document.getElementById('clearModal').addEventListener('click', function(e) {
        if (e.target === this) closeClearModal();
    });

    // ── Sync heart icons on page if product cards exist ──
    function updateHeartState(productId, isInWishlist) {
        // Update any heart buttons on the page (from product listings)
        const heartBtns = document.querySelectorAll(
            '[data-product-id="' + productId + '"] .heart-btn, ' +
            '.heart-btn[data-id="' + productId + '"]'
        );
        heartBtns.forEach(btn => {
            const icon = btn.querySelector('i');
            if (icon) {
                icon.className = isInWishlist ? 'fa-solid fa-heart' : 'fa-regular fa-heart';
            }
            btn.classList.toggle('active', isInWishlist);
            btn.dataset.wishlisted = isInWishlist ? '1' : '0';
        });

        // Also dispatch a custom event so other scripts can sync
        window.dispatchEvent(new CustomEvent('wishlistChanged', {
            detail: { productId, inWishlist: isInWishlist }
        }));
    }
})();
</script>

<?php include 'footer.php'; ?>
