<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];

// ── User info (for sidebar)
$stmtUser = $conn->prepare('SELECT username, email, phone, avatar, created_at FROM users WHERE id = ? LIMIT 1');
$stmtUser->execute([$userId]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

// ── Status filter
$allowedStatuses = ['all', 'pending', 'confirmed', 'ongoing', 'completed', 'cancelled'];
$filterStatus = isset($_GET['status']) && in_array($_GET['status'], $allowedStatuses)
    ? $_GET['status'] : 'all';

// ── Search
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

// ── Pagination
$perPage = 8;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// ── Count query
$countSql = "SELECT COUNT(DISTINCT o.id) FROM orders o
             LEFT JOIN order_details od ON o.id = od.order_id
             LEFT JOIN products p ON od.product_id = p.id
             WHERE o.user_id = ?";
$countParams = [$userId];
if ($filterStatus !== 'all') { $countSql .= " AND o.status = ?"; $countParams[] = $filterStatus; }
if ($search)                 { $countSql .= " AND (p.name LIKE ? OR o.id LIKE ?)"; $countParams[] = "%$search%"; $countParams[] = "%$search%"; }
$stmtCount = $conn->prepare($countSql);
$stmtCount->execute($countParams);
$totalOrders = (int)$stmtCount->fetchColumn();
$totalPages  = max(1, (int)ceil($totalOrders / $perPage));

// ── Fetch all rows for this page's orders
$dataSql = "SELECT o.id, o.total_price, o.status, o.created_at,
                   od.rental_start, od.rental_end, od.duration_days,
                   p.name AS product_name,
                   od.quantity, od.price, p.image
            FROM orders o
            LEFT JOIN order_details od ON o.id = od.order_id
            LEFT JOIN products p ON od.product_id = p.id
            WHERE o.user_id = ?";
$dataParams = [$userId];
if ($filterStatus !== 'all') { $dataSql .= " AND o.status = ?"; $dataParams[] = $filterStatus; }
if ($search)                 { $dataSql .= " AND (p.name LIKE ? OR o.id LIKE ?)"; $dataParams[] = "%$search%"; $dataParams[] = "%$search%"; }
$dataSql .= " ORDER BY o.created_at DESC, od.id ASC LIMIT $perPage OFFSET $offset";
$stmtData = $conn->prepare($dataSql);
$stmtData->execute($dataParams);
$allRows = $stmtData->fetchAll(PDO::FETCH_ASSOC);

// Group rows by order id (preserving page-order)
$groupedOrders = [];
foreach ($allRows as $row) {
    $oid = $row['id'];
    if (!isset($groupedOrders[$oid])) {
        $groupedOrders[$oid] = [
            'id'          => $row['id'],
            'total_price' => $row['total_price'],
            'status'      => $row['status'],
            'created_at'  => $row['created_at'],
            'items'       => []
        ];
    }
    if (!empty($row['product_name'])) {
        $groupedOrders[$oid]['items'][] = $row;
    }
}

// ── Stats per status (all time)
$stmtStats = $conn->prepare("
    SELECT status, COUNT(*) AS cnt
    FROM orders WHERE user_id = ?
    GROUP BY status
");
$stmtStats->execute([$userId]);
$rawStats = $stmtStats->fetchAll(PDO::FETCH_ASSOC);
$statsMap = ['pending'=>0,'confirmed'=>0,'ongoing'=>0,'completed'=>0,'cancelled'=>0];
foreach ($rawStats as $r) { if (isset($statsMap[$r['status']])) $statsMap[$r['status']] = (int)$r['cnt']; }
$totalAll = array_sum($statsMap);

// Total spend
$stmtSpend = $conn->prepare("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE user_id = ? AND status IN ('completed','confirmed','ongoing')");
$stmtSpend->execute([$userId]);
$totalSpend = (float)$stmtSpend->fetchColumn();

// Status metadata
$statusMeta = [
    'pending'   => ['label' => 'Chờ xác nhận', 'icon' => 'fa-clock',          'color' => '#f0b429', 'bg' => '#fffbf0'],
    'confirmed' => ['label' => 'Đã xác nhận',  'icon' => 'fa-circle-check',   'color' => '#3fb27f', 'bg' => '#f0fff6'],
    'ongoing'   => ['label' => 'Đang thuê',    'icon' => 'fa-shirt',           'color' => '#4c9fff', 'bg' => '#f0f8ff'],
    'completed' => ['label' => 'Hoàn thành',   'icon' => 'fa-check-double',   'color' => '#888',    'bg' => '#f8f8f8'],
    'cancelled' => ['label' => 'Đã hủy',       'icon' => 'fa-ban',            'color' => '#e84a5f', 'bg' => '#fff5f5'],
];

$pageTitle = 'Đơn Hàng Của Tôi | MinQuin';
include 'header.php';

// Build query string helper
function buildQs(array $overrides = []): string {
    $params = array_merge(['status' => $_GET['status'] ?? 'all', 'q' => $_GET['q'] ?? '', 'page' => 1], $overrides);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== 'all' && $v !== 1 || $v === ($overrides['status'] ?? null) || $v === ($overrides['page'] ?? null));
    return http_build_query(array_filter($params, fn($v) => $v !== '' && !($v === 'all') && !($v === 1), ARRAY_FILTER_USE_BOTH) + array_filter($overrides, fn($v) => $v !== ''));
}
?>

<style>
/* ============================================================
   ORDERS PAGE — MinQuin CORPORATE EDITION
   Đồng bộ với profile.php · No border-radius · Pink-Burgundy
============================================================ */
.ord-page { background: #f8f5f6; min-height: 80vh; font-family: 'Montserrat', sans-serif; }

/* ── TOP BANNER (compact) ── */
.ord-banner {
    background:
        linear-gradient(105deg, rgba(47,28,38,0.95) 0%, rgba(90,33,56,0.88) 55%, rgba(139,48,87,0.75) 100%),
        url('img/avatars/hero.webp') center 30% / cover no-repeat;
    padding: 36px 5%;
}
.ord-banner-inner { max-width: 1280px; margin: 0 auto; }
.ord-banner-kicker { font-size: 10px; font-weight: 700; letter-spacing: 4px; text-transform: uppercase; color: rgba(255,255,255,0.4); margin-bottom: 6px; }
.ord-banner-title  { font-size: 28px; font-weight: 900; color: #fff; text-transform: uppercase; letter-spacing: -0.5px; }

/* ── MAIN LAYOUT ── */
.ord-main { max-width: 1280px; margin: 0 auto; padding: 32px 5%; display: grid; grid-template-columns: 260px 1fr; gap: 24px; align-items: start; }

/* ── SIDEBAR ── */
.ord-sidebar-card { background: #fff; border: 1px solid #ecdde4; }
.ord-user-mini { padding: 22px 20px; border-bottom: 1px solid #ecdde4; display: flex; align-items: center; gap: 14px; }
.ord-user-avatar {
    width: 48px; height: 48px; flex-shrink: 0;
    object-fit: cover; object-position: top;
    border: 2px solid #f7c8d9;
}
.ord-user-name  { font-size: 13px; font-weight: 800; color: #2f1c26; text-transform: uppercase; line-height: 1.2; }
.ord-user-email { font-size: 11px; color: #bbb; margin-top: 2px; }
.ord-nav { padding: 6px 0; }
.ord-nav a { display: flex; align-items: center; gap: 11px; padding: 12px 20px; font-size: 12.5px; font-weight: 600; color: #666; text-decoration: none; border-left: 3px solid transparent; transition: all 0.2s; }
.ord-nav a:hover  { color: var(--accent-pink); background: #fff8fb; border-left-color: #f7c8d9; }
.ord-nav a.active { color: var(--accent-pink); background: #fff0f5; border-left-color: var(--accent-pink); }
.ord-nav a i      { width: 16px; text-align: center; font-size: 13px; color: var(--accent-pink); }
.ord-nav-sep      { height: 1px; background: #ecdde4; margin: 4px 0; }
.ord-nav a.ord-logout { color: #e84a5f; }
.ord-nav a.ord-logout i { color: #e84a5f; }
.ord-nav a.ord-logout:hover { background: #fff5f5; border-left-color: #e84a5f; }

/* ── CONTENT ── */
.ord-content { display: flex; flex-direction: column; gap: 20px; }

/* Stats strip */
.ord-stats { display: grid; grid-template-columns: repeat(5,1fr); gap: 0; border: 1px solid #ecdde4; background: #fff; }
.ord-stat { padding: 18px 14px; border-right: 1px solid #ecdde4; text-align: center; cursor: pointer; text-decoration: none; transition: background 0.18s; display: block; }
.ord-stat:last-child { border-right: none; }
.ord-stat:hover { background: #fff8fb; }
.ord-stat.active { background: #fff0f5; border-bottom: 3px solid var(--accent-pink); }
.ord-stat-num  { font-size: 22px; font-weight: 900; color: #2f1c26; line-height: 1; margin-bottom: 4px; }
.ord-stat-num sub { font-size: 11px; font-weight: 700; }
.ord-stat-label { font-size: 10px; color: #aaa; font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase; }

/* Filter bar */
.ord-filter-bar { background: #fff; border: 1px solid #ecdde4; padding: 16px 20px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.ord-search-wrap { display: flex; align-items: center; gap: 0; flex: 1; min-width: 200px; border: 1.5px solid #ecdde4; }
.ord-search-wrap input { flex: 1; padding: 10px 14px; font-size: 13px; font-family: 'Montserrat', sans-serif; color: #2f1c26; border: none; outline: none; }
.ord-search-wrap button { padding: 10px 16px; background: #2f1c26; color: #fff; border: none; cursor: pointer; font-size: 13px; transition: background 0.2s; }
.ord-search-wrap button:hover { background: var(--accent-pink); }
.ord-filter-tabs { display: flex; gap: 0; border: 1.5px solid #ecdde4; overflow: hidden; }
.ord-filter-tab { padding: 9px 16px; font-size: 11px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: #888; text-decoration: none; border-right: 1px solid #ecdde4; transition: all 0.18s; white-space: nowrap; }
.ord-filter-tab:last-child { border-right: none; }
.ord-filter-tab:hover  { background: #fff8fb; color: var(--accent-pink); }
.ord-filter-tab.active { background: var(--accent-pink); color: #fff; }

/* Meta row */
.ord-meta { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
.ord-meta-count { font-size: 12px; color: #999; font-weight: 600; }
.ord-meta-count strong { color: #2f1c26; }
.ord-meta-spend { font-size: 12px; color: #999; }
.ord-meta-spend strong { color: var(--accent-pink); font-weight: 800; }

/* Order card */
.ord-card { background: #fff; border: 1px solid #ecdde4; transition: box-shadow 0.2s; }
.ord-card:hover { box-shadow: 0 4px 20px rgba(233,90,138,0.1); }

.ord-card-head {
    padding: 14px 20px; border-bottom: 1px solid #f5eff2;
    background: #fff8fb;
    display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
}
.ord-card-id   { font-size: 12px; font-weight: 900; color: #2f1c26; letter-spacing: 0.5px; }
.ord-card-date { font-size: 11px; color: #bbb; margin-left: auto; }
.ord-card-status {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 12px; font-size: 10px; font-weight: 700;
    letter-spacing: 1px; text-transform: uppercase;
}
.ord-card-status i { font-size: 9px; }
.ord-card-total { font-size: 14px; font-weight: 900; color: var(--accent-pink); }

.ord-card-items { padding: 14px 20px; display: flex; flex-direction: column; gap: 12px; }
.ord-card-product { display: flex; align-items: center; gap: 14px; }
.ord-card-product-img { width: 56px; height: 64px; flex-shrink: 0; object-fit: cover; object-position: top; background: #f5eff2; }
.ord-card-product-info { flex: 1; }
.ord-card-product-name { font-size: 13px; font-weight: 700; color: #2f1c26; margin-bottom: 4px; }
.ord-card-product-meta { font-size: 11.5px; color: #aaa; line-height: 1.6; }
.ord-card-product-meta strong { color: var(--accent-pink); font-weight: 700; }
.ord-card-product-price { flex-shrink: 0; font-size: 13px; font-weight: 800; color: #2f1c26; text-align: right; }
.ord-card-product-price small { display: block; font-size: 10px; color: #bbb; font-weight: 400; }

.ord-card-foot {
    padding: 12px 20px; border-top: 1px solid #f5eff2;
    display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;
}
.ord-card-rental { font-size: 12px; color: #888; display: flex; align-items: center; gap: 8px; }
.ord-card-rental i { color: var(--accent-pink); }
.ord-card-actions { display: flex; gap: 8px; }
.ord-action-btn {
    padding: 8px 18px; font-size: 10px; font-weight: 700; letter-spacing: 1.5px;
    text-transform: uppercase; border: none; cursor: pointer; text-decoration: none;
    display: inline-flex; align-items: center; gap: 6px;
    transition: all 0.2s;
}
.ord-action-btn.primary { background: var(--accent-pink); color: #fff; }
.ord-action-btn.primary:hover { background: var(--hover-pink); }
.ord-action-btn.ghost { background: transparent; border: 1.5px solid #ecdde4; color: #888; }
.ord-action-btn.ghost:hover { border-color: var(--accent-pink); color: var(--accent-pink); }
.ord-action-btn.danger { background: transparent; border: 1.5px solid #ffd0d5; color: #e84a5f; }
.ord-action-btn.danger:hover { background: #e84a5f; color: #fff; }

/* Empty state */
.ord-empty { padding: 72px 20px; text-align: center; background: #fff; border: 1px solid #ecdde4; }
.ord-empty i { font-size: 48px; color: #e5c8d4; margin-bottom: 16px; display: block; }
.ord-empty h3 { font-size: 16px; font-weight: 800; color: #2f1c26; text-transform: uppercase; margin-bottom: 8px; }
.ord-empty p  { font-size: 13px; color: #bbb; margin-bottom: 24px; }
.ord-empty a  {
    display: inline-block; padding: 14px 36px;
    background: var(--accent-pink); color: #fff;
    font-size: 11px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase;
    text-decoration: none; transition: background 0.2s;
}
.ord-empty a:hover { background: var(--hover-pink); }

/* Pagination */
.ord-pagination { display: flex; justify-content: center; align-items: center; gap: 4px; flex-wrap: wrap; }
.ord-page-btn {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 36px; height: 36px; padding: 0 10px;
    font-size: 12px; font-weight: 700; color: #888;
    background: #fff; border: 1.5px solid #ecdde4;
    text-decoration: none; transition: all 0.2s;
}
.ord-page-btn:hover  { border-color: var(--accent-pink); color: var(--accent-pink); }
.ord-page-btn.active { background: var(--accent-pink); border-color: var(--accent-pink); color: #fff; }
.ord-page-btn.disabled { opacity: 0.4; pointer-events: none; }

/* Cancel modal */
.ord-modal { display: none; position: fixed; inset: 0; z-index: 9999; align-items: center; justify-content: center; background: rgba(47,28,38,0.7); backdrop-filter: blur(4px); }
.ord-modal.open { display: flex; }
.ord-modal-box { background: #fff; max-width: 420px; width: 90%; }
.ord-modal-head { padding: 20px 24px; background: #2f1c26; display: flex; align-items: center; justify-content: space-between; }
.ord-modal-head span { font-size: 12px; font-weight: 800; color: #fff; letter-spacing: 2px; text-transform: uppercase; }
.ord-modal-close { background: none; border: none; color: rgba(255,255,255,0.5); font-size: 22px; cursor: pointer; padding: 0; line-height: 1; transition: color 0.2s; }
.ord-modal-close:hover { color: #fff; }
.ord-modal-body { padding: 24px; font-size: 14px; color: #555; line-height: 1.7; }
.ord-modal-body strong { color: #2f1c26; display: block; margin-bottom: 8px; font-size: 16px; }
.ord-modal-foot { padding: 0 24px 24px; display: flex; gap: 10px; justify-content: flex-end; }
.ord-modal-cancel { padding: 11px 24px; background: transparent; border: 1.5px solid #ecdde4; color: #888; font-size: 11px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; cursor: pointer; transition: all 0.2s; }
.ord-modal-cancel:hover { border-color: #2f1c26; color: #2f1c26; }
.ord-modal-confirm { padding: 11px 28px; background: #e84a5f; color: #fff; border: none; font-size: 11px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; cursor: pointer; transition: background 0.2s; }
.ord-modal-confirm:hover { background: #c0392b; }

/* Toast */
.ord-toast { position: fixed; bottom: 28px; right: 28px; z-index: 10000; padding: 14px 24px; background: #2f1c26; color: #fff; font-size: 13px; font-weight: 600; box-shadow: 0 8px 32px rgba(0,0,0,0.25); transform: translateY(20px); opacity: 0; transition: all 0.35s; pointer-events: none; }
.ord-toast.show { transform: translateY(0); opacity: 1; }
.ord-toast.success { border-left: 4px solid #3fb27f; }
.ord-toast.error   { border-left: 4px solid #e84a5f; }

/* Responsive */
@media (max-width: 1024px) { .ord-main { grid-template-columns: 1fr; } }
@media (max-width: 768px)  { .ord-stats { grid-template-columns: repeat(3,1fr); } .ord-stat:nth-child(3) { border-right:none; } .ord-stat:nth-child(n+4) { border-top: 1px solid #ecdde4; } }
@media (max-width: 520px)  { .ord-stats { grid-template-columns: repeat(2,1fr); } .ord-card-head { gap: 8px; } }
</style>

<div class="ord-page">

<!-- ── BANNER ── -->
<div class="ord-banner">
    <div class="ord-banner-inner">
        <div class="ord-banner-kicker">Tài khoản của bạn</div>
        <div class="ord-banner-title">Đơn hàng của tôi</div>
    </div>
</div>

<!-- ── MAIN ── -->
<div class="ord-main">

    <!-- ── SIDEBAR ── -->
    <aside>
        <div class="ord-sidebar-card">
            <div class="ord-user-mini">
                <?php
                $avatarSrc = !empty($user['avatar']) && file_exists($user['avatar'])
                    ? $user['avatar'] : 'img/avatars/hero.webp';
                ?>
                <img class="ord-user-avatar" src="<?= htmlspecialchars($avatarSrc) ?>" alt="Avatar">
                <div>
                    <div class="ord-user-name"><?= htmlspecialchars($user['username'] ?? 'Thành viên') ?></div>
                    <div class="ord-user-email"><?= htmlspecialchars($user['email'] ?? '') ?></div>
                </div>
            </div>
            <nav class="ord-nav">
                <a href="profile.php"><i class="fa-solid fa-circle-user"></i> Hồ sơ cá nhân</a>
                <a href="orders.php" class="active"><i class="fa-solid fa-receipt"></i> Đơn hàng của tôi</a>
                <a href="wishlist.php"><i class="fa-solid fa-heart"></i> Sản phẩm yêu thích</a>
                <a href="membership.php"><i class="fa-solid fa-crown"></i> Quyền lợi thành viên</a>
                <div class="ord-nav-sep"></div>
                <a href="logout.php" class="ord-logout"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a>
            </nav>
        </div>
    </aside>

    <!-- ── CONTENT ── -->
    <div class="ord-content">

        <!-- Stats strip -->
        <div class="ord-stats">
            <a href="orders.php" class="ord-stat <?= $filterStatus==='all' ? 'active' : '' ?>">
                <div class="ord-stat-num"><?= $totalAll ?></div>
                <div class="ord-stat-label">Tất cả</div>
            </a>
            <a href="orders.php?status=pending" class="ord-stat <?= $filterStatus==='pending' ? 'active' : '' ?>">
                <div class="ord-stat-num" style="color:#f0b429"><?= $statsMap['pending'] ?></div>
                <div class="ord-stat-label">Chờ xác nhận</div>
            </a>
            <a href="orders.php?status=ongoing" class="ord-stat <?= $filterStatus==='ongoing' ? 'active' : '' ?>">
                <div class="ord-stat-num" style="color:#4c9fff"><?= $statsMap['ongoing'] ?></div>
                <div class="ord-stat-label">Đang thuê</div>
            </a>
            <a href="orders.php?status=completed" class="ord-stat <?= $filterStatus==='completed' ? 'active' : '' ?>">
                <div class="ord-stat-num" style="color:#3fb27f"><?= $statsMap['completed'] ?></div>
                <div class="ord-stat-label">Hoàn thành</div>
            </a>
            <a href="orders.php?status=cancelled" class="ord-stat <?= $filterStatus==='cancelled' ? 'active' : '' ?>">
                <div class="ord-stat-num" style="color:#e84a5f"><?= $statsMap['cancelled'] ?></div>
                <div class="ord-stat-label">Đã hủy</div>
            </a>
        </div>

        <!-- Filter bar -->
        <div class="ord-filter-bar">
            <form method="GET" action="orders.php" style="display:flex;gap:8px;flex:1;flex-wrap:wrap;align-items:center">
                <input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>">
                <div class="ord-search-wrap" style="flex:1;min-width:200px">
                    <input type="text" name="q" placeholder="Tìm tên sản phẩm hoặc mã đơn..."
                           value="<?= htmlspecialchars($search) ?>">
                    <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                </div>
            </form>
            <div class="ord-filter-tabs">
                <a href="orders.php?status=all<?= $search ? '&q='.urlencode($search) : '' ?>"  class="ord-filter-tab <?= $filterStatus==='all'       ? 'active' : '' ?>">Tất cả</a>
                <a href="orders.php?status=pending<?= $search ? '&q='.urlencode($search) : '' ?>"   class="ord-filter-tab <?= $filterStatus==='pending'   ? 'active' : '' ?>">Chờ xác nhận</a>
                <a href="orders.php?status=confirmed<?= $search ? '&q='.urlencode($search) : '' ?>" class="ord-filter-tab <?= $filterStatus==='confirmed' ? 'active' : '' ?>">Đã xác nhận</a>
                <a href="orders.php?status=ongoing<?= $search ? '&q='.urlencode($search) : '' ?>"   class="ord-filter-tab <?= $filterStatus==='ongoing'   ? 'active' : '' ?>">Đang thuê</a>
                <a href="orders.php?status=completed<?= $search ? '&q='.urlencode($search) : '' ?>" class="ord-filter-tab <?= $filterStatus==='completed' ? 'active' : '' ?>">Hoàn thành</a>
                <a href="orders.php?status=cancelled<?= $search ? '&q='.urlencode($search) : '' ?>" class="ord-filter-tab <?= $filterStatus==='cancelled' ? 'active' : '' ?>">Đã hủy</a>
            </div>
        </div>

        <!-- Meta -->
        <div class="ord-meta">
            <div class="ord-meta-count">
                Tìm thấy <strong><?= $totalOrders ?></strong> đơn hàng<?= $search ? ' cho "' . htmlspecialchars($search) . '"' : '' ?>
            </div>
            <div class="ord-meta-spend">
                Tổng chi tiêu hợp lệ: <strong><?= number_format($totalSpend) ?>đ</strong>
            </div>
        </div>

        <!-- Order Cards -->
        <?php if (!empty($groupedOrders)): ?>
            <?php foreach ($groupedOrders as $order):
                $st     = $order['status'] ?? 'pending';
                $stInfo = $statusMeta[$st] ?? ['label' => ucfirst($st), 'icon' => 'fa-circle', 'color' => '#888', 'bg' => '#f8f8f8'];
                $rentalStart = $order['items'][0]['rental_start'] ?? null;
                $rentalEnd   = $order['items'][0]['rental_end']   ?? null;
                $durDays     = $order['items'][0]['duration_days'] ?? 1;
            ?>
            <div class="ord-card" id="order-<?= $order['id'] ?>">
                <!-- Head -->
                <div class="ord-card-head">
                    <span class="ord-card-id">ĐƠN #<?= $order['id'] ?></span>
                    <span class="ord-card-status" style="background:<?= $stInfo['bg'] ?>; color:<?= $stInfo['color'] ?>; border-left: 3px solid <?= $stInfo['color'] ?>">
                        <i class="fa-solid <?= $stInfo['icon'] ?>"></i>
                        <?= $stInfo['label'] ?>
                    </span>
                    <span class="ord-card-total"><?= number_format($order['total_price']) ?>đ</span>
                    <span class="ord-card-date">
                        <i class="fa-regular fa-clock" style="color:#ddd"></i>
                        <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                    </span>
                </div>

                <!-- Items -->
                <div class="ord-card-items">
                    <?php foreach ($order['items'] as $item):
                        $imgSrc   = !empty($item['image']) ? 'img/' . basename($item['image']) : 'img/default.jpg';
                        $qty      = max(1, (int)$item['quantity']);
                        $days     = max(1, (int)($item['duration_days'] ?? 1));
                        $lineAmt  = (int)$item['price'] * $qty * $days;
                    ?>
                    <div class="ord-card-product">
                        <img class="ord-card-product-img"
                             src="<?= htmlspecialchars($imgSrc) ?>"
                             alt="<?= htmlspecialchars($item['product_name']) ?>"
                             onerror="this.src='img/default.jpg'">
                        <div class="ord-card-product-info">
                            <div class="ord-card-product-name"><?= htmlspecialchars($item['product_name']) ?></div>
                            <div class="ord-card-product-meta">
                                Số lượng: <strong><?= $qty ?></strong>
                                · <?= $days ?> ngày thuê
                                · <?= number_format((int)$item['price']) ?>đ/ngày
                            </div>
                        </div>
                        <div class="ord-card-product-price">
                            <?= number_format($lineAmt) ?>đ
                            <small><?= $qty ?> × <?= $days ?> ngày</small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Footer -->
                <div class="ord-card-foot">
                    <div class="ord-card-rental">
                        <?php if ($rentalStart && $rentalEnd): ?>
                            <i class="fa-solid fa-calendar-days"></i>
                            <?= htmlspecialchars($rentalStart) ?> → <?= htmlspecialchars($rentalEnd) ?>
                            (<?= max(1,(int)$durDays) ?> ngày)
                        <?php endif; ?>
                    </div>
                    <div class="ord-card-actions">
                        <?php if ($st === 'pending'): ?>
                            <button class="ord-action-btn danger"
                                    onclick="openCancelModal(<?= $order['id'] ?>)">
                                <i class="fa-solid fa-ban"></i> Hủy đơn
                            </button>
                        <?php endif; ?>
                        <?php if ($st === 'completed'): ?>
                            <a href="ao_dai.php" class="ord-action-btn ghost">
                                <i class="fa-solid fa-rotate-left"></i> Thuê lại
                            </a>
                        <?php endif; ?>
                        <a href="javascript:void(0)"
                           onclick="toggleDetail(<?= $order['id'] ?>)"
                           class="ord-action-btn primary">
                            <i class="fa-solid fa-eye" id="eyeIcon<?= $order['id'] ?>"></i>
                            Chi tiết
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

        <?php else: ?>
            <div class="ord-empty">
                <i class="fa-solid fa-bag-shopping"></i>
                <h3><?= $search ? 'Không tìm thấy đơn hàng' : 'Chưa có đơn hàng nào' ?></h3>
                <p><?= $search ? 'Thử từ khóa khác hoặc xóa bộ lọc.' : 'Bắt đầu thuê ngay bộ sưu tập trang phục cao cấp của MinQuin.' ?></p>
                <a href="<?= $search ? 'orders.php' : 'ao_dai.php' ?>">
                    <?= $search ? 'Xem tất cả đơn hàng' : 'Khám phá bộ sưu tập' ?>
                </a>
            </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="ord-pagination">
            <a href="?status=<?= urlencode($filterStatus) ?>&q=<?= urlencode($search) ?>&page=<?= max(1,$page-1) ?>"
               class="ord-page-btn <?= $page<=1?'disabled':'' ?>">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
            <?php for ($p = max(1,$page-2); $p <= min($totalPages,$page+2); $p++): ?>
            <a href="?status=<?= urlencode($filterStatus) ?>&q=<?= urlencode($search) ?>&page=<?= $p ?>"
               class="ord-page-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a>
            <?php endfor; ?>
            <a href="?status=<?= urlencode($filterStatus) ?>&q=<?= urlencode($search) ?>&page=<?= min($totalPages,$page+1) ?>"
               class="ord-page-btn <?= $page>=$totalPages?'disabled':'' ?>">
                <i class="fa-solid fa-chevron-right"></i>
            </a>
        </div>
        <?php endif; ?>

    </div><!-- .ord-content -->
</div><!-- .ord-main -->
</div><!-- .ord-page -->

<!-- Cancel Modal -->
<div class="ord-modal" id="cancelModal">
    <div class="ord-modal-box">
        <div class="ord-modal-head">
            <span><i class="fa-solid fa-ban"></i>&ensp;Hủy đơn hàng</span>
            <button class="ord-modal-close" onclick="closeCancelModal()">&times;</button>
        </div>
        <div class="ord-modal-body">
            <strong id="cancelModalTitle">Xác nhận hủy đơn?</strong>
            Thao tác này không thể hoàn tác. Đơn hàng sẽ chuyển sang trạng thái "Đã hủy" và không thể khôi phục.
        </div>
        <div class="ord-modal-foot">
            <button class="ord-modal-cancel" onclick="closeCancelModal()">Không, giữ lại</button>
            <button class="ord-modal-confirm" id="cancelConfirmBtn">Xác nhận hủy</button>
        </div>
    </div>
</div>

<div class="ord-toast" id="ordToast"></div>

<script>
(function () {
    let cancelOrderId = null;

    function toast(msg, type = 'success') {
        const el = document.getElementById('ordToast');
        el.textContent = msg;
        el.className = 'ord-toast ' + type + ' show';
        setTimeout(() => el.classList.remove('show'), 3500);
    }

    window.openCancelModal = function (orderId) {
        cancelOrderId = orderId;
        document.getElementById('cancelModalTitle').textContent = 'Hủy đơn #' + orderId + '?';
        document.getElementById('cancelModal').classList.add('open');
    };
    window.closeCancelModal = function () {
        document.getElementById('cancelModal').classList.remove('open');
        cancelOrderId = null;
    };

    document.getElementById('cancelConfirmBtn').addEventListener('click', function () {
        if (!cancelOrderId) return;
        const btn = this;
        btn.disabled = true;
        btn.textContent = 'Đang hủy...';

        fetch('cancel_order_ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'order_id=' + cancelOrderId
        })
        .then(r => r.json())
        .then(d => {
            if (d.status === 'success') {
                closeCancelModal();
                toast('Đã hủy đơn #' + cancelOrderId + ' thành công.');
                setTimeout(() => location.reload(), 1200);
            } else {
                toast(d.message || 'Không thể hủy đơn này.', 'error');
                btn.disabled = false;
                btn.textContent = 'Xác nhận hủy';
            }
        })
        .catch(() => {
            toast('Lỗi kết nối, vui lòng thử lại.', 'error');
            btn.disabled = false;
            btn.textContent = 'Xác nhận hủy';
        });
    });

    // Close modal on backdrop click
    document.getElementById('cancelModal').addEventListener('click', function (e) {
        if (e.target === this) closeCancelModal();
    });

    // Toggle detail — just smooth scroll to card
    window.toggleDetail = function (orderId) {
        const card = document.getElementById('order-' + orderId);
        if (card) card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    };
})();
</script>

<?php include 'footer.php'; ?>
