<?php
require_once 'config.php';
$pageTitle = "Thành Viên & Quyền Lợi | QHTN";
$user = null;
$userTier = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Tính tổng chi tiêu trong năm
    $yearStmt = $pdo->prepare("
        SELECT COALESCE(SUM(o.total_price), 0) as total_spent
        FROM orders o
        WHERE o.user_id = ? AND o.status IN ('completed','confirmed','ongoing')
        AND YEAR(o.created_at) = YEAR(NOW())
    ");
    $yearStmt->execute([$_SESSION['user_id']]);
    $spent = $yearStmt->fetchColumn();

    if ($spent >= 10000000) $userTier = 'diamond';
    elseif ($spent >= 3000000) $userTier = 'gold';
    else $userTier = 'silver';
}
include 'header.php';
?>

<style>
/* ============================================================
   MEMBERSHIP PAGE — QHTN CORPORATE PINK EDITION
   No border-radius, enterprise feel, ultra-clean layout
============================================================ */

/* ── PAGE WRAPPER ── */
.mem-page {
    background: #fff;
    color: var(--text-color);
    font-family: 'Montserrat', sans-serif;
}

/* ── HERO BANNER ── */
.mem-hero {
    background:
        linear-gradient(105deg, rgba(47,28,38,0.88) 0%, rgba(90,33,56,0.82) 50%, rgba(139,48,87,0.78) 100%),
        url('img/avatars/hero.webp') center center / cover no-repeat;
    padding: 72px 5%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 48px;
    position: relative;
    overflow: hidden;
}
.mem-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 80% 50%, rgba(233,90,138,0.22) 0%, transparent 55%),
        repeating-linear-gradient(
            45deg,
            transparent,
            transparent 60px,
            rgba(255,255,255,0.015) 60px,
            rgba(255,255,255,0.015) 61px
        );
    pointer-events: none;
}
.mem-hero-text {
    max-width: 580px;
    position: relative;
    z-index: 1;
}
.mem-hero-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    letter-spacing: 4px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    color: #f48fb1;
    margin-bottom: 18px;
}
.mem-hero-eyebrow span {
    display: inline-block;
    width: 28px;
    height: 1px;
    background: #f48fb1;
}
.mem-hero h1 {
    font-size: 48px;
    font-weight: 900;
    color: #fff;
    line-height: 1.08;
    letter-spacing: -1px;
    margin-bottom: 18px;
    text-transform: uppercase;
}
.mem-hero h1 em {
    font-style: normal;
    color: #f48fb1;
}
.mem-hero p {
    font-size: 15px;
    color: rgba(255,255,255,0.68);
    line-height: 1.8;
    max-width: 460px;
    margin-bottom: 32px;
}
.mem-hero-cta {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 14px 36px;
    background: var(--accent-pink);
    color: #fff;
    font-weight: 800;
    font-size: 13px;
    letter-spacing: 1px;
    text-transform: uppercase;
    border: none;
    cursor: pointer;
    transition: background 0.25s, transform 0.25s;
    text-decoration: none;
}
.mem-hero-cta:hover {
    background: var(--hover-pink);
    transform: translateY(-2px);
}
.mem-hero-stats {
    display: flex;
    gap: 36px;
    flex-shrink: 0;
    position: relative;
    z-index: 1;
}
.mem-stat {
    text-align: center;
}
.mem-stat-num {
    font-size: 38px;
    font-weight: 900;
    color: #fff;
    line-height: 1;
    margin-bottom: 6px;
}
.mem-stat-num span {
    color: #f48fb1;
}
.mem-stat-label {
    font-size: 11px;
    color: rgba(255,255,255,0.5);
    letter-spacing: 2px;
    text-transform: uppercase;
    font-weight: 600;
}
.mem-stat-divider {
    width: 1px;
    background: rgba(255,255,255,0.12);
    align-self: stretch;
}

/* ── CURRENT TIER BANNER (Logged in) ── */
.mem-tier-bar {
    background: linear-gradient(90deg, #fff0f5 0%, #fff 100%);
    border-top: 3px solid var(--accent-pink);
    border-bottom: 1px solid #f7c8d9;
    padding: 20px 5%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    flex-wrap: wrap;
}
.mem-tier-bar-info {
    display: flex;
    align-items: center;
    gap: 16px;
}
.mem-tier-icon {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    background: #fff;
    border: 1.5px solid #f7c8d9;
}
.mem-tier-bar-text strong {
    display: block;
    font-size: 15px;
    font-weight: 800;
    color: #2f1c26;
    margin-bottom: 2px;
}
.mem-tier-bar-text span {
    font-size: 13px;
    color: #888;
}
.mem-progress-wrap {
    flex: 1;
    min-width: 200px;
    max-width: 360px;
}
.mem-progress-label {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    color: #888;
    font-weight: 600;
    margin-bottom: 6px;
    letter-spacing: 0.5px;
}
.mem-progress-track {
    height: 6px;
    background: #f0e0e8;
    position: relative;
    overflow: hidden;
}
.mem-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #f48fb1, var(--accent-pink));
    transition: width 0.8s ease;
}

/* ── SECTION WRAPPER ── */
.mem-section {
    max-width: 1280px;
    margin: 0 auto;
    padding: 72px 5%;
}
.mem-section-sm {
    max-width: 1280px;
    margin: 0 auto;
    padding: 48px 5%;
}
.mem-eyebrow {
    font-size: 11px;
    letter-spacing: 4px;
    text-transform: uppercase;
    color: var(--accent-pink);
    font-weight: 700;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.mem-eyebrow::before {
    content: '';
    display: inline-block;
    width: 24px;
    height: 2px;
    background: var(--accent-pink);
}
.mem-heading {
    font-size: 34px;
    font-weight: 900;
    color: #2f1c26;
    line-height: 1.1;
    letter-spacing: -0.5px;
    margin-bottom: 12px;
    text-transform: uppercase;
}
.mem-subtext {
    font-size: 14px;
    color: #888;
    max-width: 540px;
    line-height: 1.8;
}

/* ── TIER CARDS ── */
.mem-tier-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0;
    border: 1.5px solid #f7c8d9;
    margin-top: 48px;
}
.mem-tier-card {
    border-right: 1.5px solid #f7c8d9;
    padding: 48px 36px;
    position: relative;
    transition: background 0.25s;
    background: #fff;
}
.mem-tier-card:last-child {
    border-right: none;
}
.mem-tier-card.featured {
    background: linear-gradient(160deg, #2f1c26 0%, #5a2138 100%);
}
.mem-tier-card.featured::before {
    content: 'PHỔ BIẾN NHẤT';
    position: absolute;
    top: 0; left: 0; right: 0;
    background: var(--accent-pink);
    color: #fff;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 3px;
    text-align: center;
    padding: 6px 0;
}
.mem-tier-label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 5px 12px;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 3px;
    text-transform: uppercase;
    margin-bottom: 28px;
    margin-top: 0;
}
.mem-tier-card.featured .mem-tier-label {
    margin-top: 20px;
}
.label-silver { background: #f5f5f5; color: #888; }
.label-gold { background: #fff8e6; color: #b8860b; }
.label-diamond { background: rgba(233,90,138,0.12); color: var(--accent-pink); }

.mem-tier-name {
    font-size: 26px;
    font-weight: 900;
    color: #2f1c26;
    text-transform: uppercase;
    letter-spacing: -0.5px;
    margin-bottom: 4px;
}
.mem-tier-card.featured .mem-tier-name { color: #fff; }

.mem-tier-cond {
    font-size: 12px;
    color: #aaa;
    margin-bottom: 32px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
}
.mem-tier-card.featured .mem-tier-cond { color: rgba(255,255,255,0.5); }
.mem-tier-cond i { font-size: 10px; color: var(--accent-pink); }

.mem-tier-price {
    margin-bottom: 32px;
    padding-bottom: 28px;
    border-bottom: 1px solid #f0e0e8;
}
.mem-tier-card.featured .mem-tier-price { border-color: rgba(255,255,255,0.1); }
.mem-tier-price-main {
    font-size: 15px;
    font-weight: 700;
    color: var(--accent-pink);
    margin-bottom: 2px;
}
.mem-tier-price-sub {
    font-size: 12px;
    color: #bbb;
    font-weight: 500;
}

.mem-tier-benefits {
    list-style: none;
    margin: 0 0 36px;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 13px;
}
.mem-tier-benefits li {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 13.5px;
    color: #555;
    line-height: 1.5;
    font-weight: 500;
}
.mem-tier-card.featured .mem-tier-benefits li { color: rgba(255,255,255,0.75); }
.mem-tier-benefits li i {
    flex-shrink: 0;
    width: 18px;
    height: 18px;
    background: #fff5f8;
    color: var(--accent-pink);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 9px;
    margin-top: 1px;
}
.mem-tier-card.featured .mem-tier-benefits li i {
    background: rgba(233,90,138,0.18);
    color: #f48fb1;
}
.mem-tier-benefits li span strong {
    color: #2f1c26;
    font-weight: 700;
}
.mem-tier-card.featured .mem-tier-benefits li span strong { color: #fff; }

.mem-tier-btn {
    display: block;
    width: 100%;
    padding: 14px;
    text-align: center;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 2px;
    text-transform: uppercase;
    text-decoration: none;
    transition: all 0.25s;
    cursor: pointer;
    border: none;
}
.btn-tier-outline {
    background: transparent;
    border: 1.5px solid #f0d0dc;
    color: #888;
}
.btn-tier-outline:hover {
    border-color: var(--accent-pink);
    color: var(--accent-pink);
    background: #fff5f8;
}
.btn-tier-primary {
    background: var(--accent-pink);
    color: #fff;
}
.btn-tier-primary:hover {
    background: var(--hover-pink);
    transform: translateY(-1px);
}
.btn-tier-ghost {
    background: rgba(255,255,255,0.1);
    color: rgba(255,255,255,0.7);
    border: 1.5px solid rgba(255,255,255,0.18);
}
.btn-tier-ghost:hover {
    background: rgba(255,255,255,0.2);
    color: #fff;
}

/* ── HOW IT WORKS ── */
.mem-how-bg {
    background: #fff6fa;
    border-top: 1px solid #f7c8d9;
    border-bottom: 1px solid #f7c8d9;
}
.mem-steps {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0;
    margin-top: 52px;
    counter-reset: step;
}
.mem-step {
    padding: 36px 28px;
    border-right: 1px solid #f0dce4;
    position: relative;
}
.mem-step:last-child { border-right: none; }
.mem-step-num {
    width: 42px;
    height: 42px;
    background: var(--accent-pink);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    font-weight: 900;
    margin-bottom: 20px;
    letter-spacing: -0.5px;
}
.mem-step-title {
    font-size: 15px;
    font-weight: 800;
    color: #2f1c26;
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.mem-step-desc {
    font-size: 13px;
    color: #888;
    line-height: 1.75;
}

/* ── COMPARISON TABLE ── */
.mem-table-wrap {
    overflow-x: auto;
    margin-top: 48px;
}
.mem-compare {
    width: 100%;
    border-collapse: collapse;
    font-size: 13.5px;
}
.mem-compare th, .mem-compare td {
    padding: 16px 24px;
    text-align: left;
    border-bottom: 1px solid #f7e8ee;
}
.mem-compare thead th {
    background: #2f1c26;
    color: #fff;
    font-weight: 700;
    font-size: 11px;
    letter-spacing: 2px;
    text-transform: uppercase;
    padding: 18px 24px;
}
.mem-compare thead th:first-child { background: #221118; }
.mem-compare thead .th-gold { background: #4a3010; color: #f1c40f; }
.mem-compare thead .th-diamond { background: var(--accent-pink); }
.mem-compare tbody tr:nth-child(even) { background: #fff8fb; }
.mem-compare tbody td:first-child {
    font-weight: 700;
    color: #2f1c26;
    font-size: 13px;
}
.mem-compare .check-yes { color: var(--accent-pink); font-size: 14px; }
.mem-compare .check-no { color: #ddd; font-size: 14px; }
.mem-compare .check-val {
    font-weight: 800;
    color: var(--accent-pink);
}

/* ── FAQ ── */
.mem-faq {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0;
    margin-top: 48px;
    border: 1.5px solid #f7c8d9;
}
.mem-faq-item {
    padding: 28px 32px;
    border-right: 1.5px solid #f7c8d9;
    border-bottom: 1.5px solid #f7c8d9;
}
.mem-faq-item:nth-child(even) { border-right: none; }
.mem-faq-item:nth-last-child(-n+2) { border-bottom: none; }
.mem-faq-q {
    font-size: 14px;
    font-weight: 800;
    color: #2f1c26;
    margin-bottom: 10px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}
.mem-faq-q i {
    color: var(--accent-pink);
    font-size: 12px;
    margin-top: 2px;
    flex-shrink: 0;
}
.mem-faq-a {
    font-size: 13px;
    color: #888;
    line-height: 1.8;
    padding-left: 22px;
}

/* ── CTA BOTTOM ── */
.mem-cta-bottom {
    background: linear-gradient(105deg, #2f1c26 0%, #5a2138 60%, #8b3057 100%);
    padding: 80px 5%;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.mem-cta-bottom::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 50% 120%, rgba(233,90,138,0.3) 0%, transparent 60%);
}
.mem-cta-bottom h2 {
    font-size: 40px;
    font-weight: 900;
    color: #fff;
    letter-spacing: -1px;
    text-transform: uppercase;
    margin-bottom: 14px;
    position: relative;
}
.mem-cta-bottom p {
    font-size: 15px;
    color: rgba(255,255,255,0.55);
    margin-bottom: 36px;
    position: relative;
}
.mem-cta-btns {
    display: flex;
    justify-content: center;
    gap: 16px;
    flex-wrap: wrap;
    position: relative;
}
.mem-cta-btns a {
    padding: 16px 48px;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 2px;
    text-transform: uppercase;
    text-decoration: none;
    transition: all 0.25s;
    display: inline-block;
}
.cta-primary-btn {
    background: var(--accent-pink);
    color: #fff;
}
.cta-primary-btn:hover { background: var(--hover-pink); }
.cta-ghost-btn {
    background: transparent;
    color: rgba(255,255,255,0.7);
    border: 1.5px solid rgba(255,255,255,0.2);
}
.cta-ghost-btn:hover {
    border-color: rgba(255,255,255,0.5);
    color: #fff;
}

/* ── RESPONSIVE ── */
@media (max-width: 1100px) {
    .mem-tier-grid { grid-template-columns: 1fr; }
    .mem-tier-card { border-right: none; border-bottom: 1.5px solid #f7c8d9; }
    .mem-tier-card:last-child { border-bottom: none; }
    .mem-steps { grid-template-columns: repeat(2, 1fr); }
    .mem-step:nth-child(2) { border-right: none; }
    .mem-step:nth-child(3) { border-right: 1px solid #f0dce4; }
    .mem-compare th, .mem-compare td { padding: 13px 16px; font-size: 12.5px; }
}
@media (max-width: 768px) {
    .mem-hero { flex-direction: column; padding: 48px 5%; }
    .mem-hero h1 { font-size: 32px; }
    .mem-hero-stats { gap: 20px; }
    .mem-stat-num { font-size: 28px; }
    .mem-steps { grid-template-columns: 1fr; }
    .mem-step { border-right: none; border-bottom: 1px solid #f0dce4; }
    .mem-step:last-child { border-bottom: none; }
    .mem-faq { grid-template-columns: 1fr; }
    .mem-faq-item { border-right: none; }
    .mem-faq-item:nth-last-child(-n+2) { border-bottom: 1.5px solid #f7c8d9; }
    .mem-faq-item:last-child { border-bottom: none; }
    .mem-heading { font-size: 26px; }
    .mem-cta-bottom h2 { font-size: 28px; }
}
</style>

<div class="mem-page">

    <!-- ===== HERO ===== -->
    <section class="mem-hero">
        <div class="mem-hero-text">
            <div class="mem-hero-eyebrow">
                <span></span>Chương trình khách hàng thân thiết
            </div>
            <h1>Thành viên<br><em>QHTN</em> Club</h1>
            <p>Tích lũy chi tiêu, nâng hạng thành viên và nhận hàng loạt đặc quyền độc quyền — từ giảm giá trực tiếp đến dịch vụ ưu tiên cao cấp.</p>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="register.php" class="mem-hero-cta">
                    <i class="fa-solid fa-arrow-right"></i> Tham gia miễn phí ngay
                </a>
            <?php elseif ($userTier !== 'diamond'): ?>
                <a href="ao_dai.php" class="mem-hero-cta">
                    <i class="fa-solid fa-bag-shopping"></i> Mua sắm để nâng hạng
                </a>
            <?php else: ?>
                <span style="color: #f48fb1; font-size: 13px; font-weight: 700; letter-spacing: 1px;">
                    <i class="fa-solid fa-gem"></i>&ensp;Bạn đang ở hạng cao nhất — Kim Cương
                </span>
            <?php endif; ?>
        </div>
        <div class="mem-hero-stats">
            <div class="mem-stat">
                <div class="mem-stat-num">3<span>+</span></div>
                <div class="mem-stat-label">Hạng thành viên</div>
            </div>
            <div class="mem-stat-divider"></div>
            <div class="mem-stat">
                <div class="mem-stat-num">10<span>%</span></div>
                <div class="mem-stat-label">Giảm tối đa</div>
            </div>
            <div class="mem-stat-divider"></div>
            <div class="mem-stat">
                <div class="mem-stat-num">24<span>/7</span></div>
                <div class="mem-stat-label">Hỗ trợ VIP</div>
            </div>
        </div>
    </section>

    <!-- ===== TIER STATUS BAR (If logged in) ===== -->
    <?php if ($user && $userTier): ?>
    <?php
        $tierLabels = [
            'silver'  => ['Thành Viên Bạc', 'fa-medal', '#aaa', 0, 3000000],
            'gold'    => ['Thành Viên Vàng', 'fa-crown', '#f1c40f', 3000000, 10000000],
            'diamond' => ['Kim Cương', 'fa-gem', '#e95a8a', 10000000, 10000000],
        ];
        [$tierName, $tierIcon, $tierColor, $tierMin, $tierMax] = $tierLabels[$userTier];
        $progress = $tierMax > 0 ? min(100, ($spent - $tierMin) / ($tierMax - $tierMin) * 100) : 100;
        if ($userTier === 'diamond') $progress = 100;
    ?>
    <div class="mem-tier-bar">
        <div class="mem-tier-bar-info">
            <div class="mem-tier-icon">
                <i class="fa-solid <?= $tierIcon ?>" style="color: <?= $tierColor ?>"></i>
            </div>
            <div class="mem-tier-bar-text">
                <strong>Xin chào, <?= htmlspecialchars($user['name'] ?? $user['username']) ?></strong>
                <span>Hạng hiện tại: <strong style="color: <?= $tierColor ?>"><?= $tierName ?></strong> &nbsp;·&nbsp; Đã chi: <strong><?= number_format($spent) ?>đ</strong></span>
            </div>
        </div>
        <?php if ($userTier !== 'diamond'): ?>
        <div class="mem-progress-wrap">
            <div class="mem-progress-label">
                <span>Tiến trình nâng hạng</span>
                <span><?= number_format($tierMax - $spent) ?>đ còn thiếu</span>
            </div>
            <div class="mem-progress-track">
                <div class="mem-progress-fill" style="width: <?= number_format($progress, 1) ?>%"></div>
            </div>
        </div>
        <?php else: ?>
        <span style="font-size: 12px; color: var(--accent-pink); font-weight: 700; letter-spacing: 1px; text-transform: uppercase;">
            <i class="fa-solid fa-check-double"></i>&ensp;Hạng cao nhất
        </span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ===== TIER CARDS ===== -->
    <div class="mem-section">
        <div class="mem-eyebrow">Hạng Thành Viên</div>
        <h2 class="mem-heading">Chọn hạng<br>phù hợp với bạn</h2>
        <p class="mem-subtext">Tất cả thành viên đều được tích điểm. Hạng cao hơn sẽ mở ra nhiều đặc quyền hơn.</p>

        <div class="mem-tier-grid">
            <!-- SILVER -->
            <div class="mem-tier-card">
                <span class="mem-tier-label label-silver">
                    <i class="fa-solid fa-medal"></i> Bạc
                </span>
                <div class="mem-tier-name">Thành Viên Bạc</div>
                <div class="mem-tier-cond">
                    <i class="fa-solid fa-circle"></i> Đăng ký miễn phí — không cần điều kiện
                </div>
                <div class="mem-tier-price">
                    <div class="mem-tier-price-main">0đ / Miễn phí</div>
                    <div class="mem-tier-price-sub">Chi tiêu dưới 3.000.000đ / năm</div>
                </div>
                <ul class="mem-tier-benefits">
                    <li><i class="fa-solid fa-check"></i><span>Tích điểm đổi quà mỗi đơn hàng</span></li>
                    <li><i class="fa-solid fa-check"></i><span>Hỗ trợ tư vấn 24/7 qua chat</span></li>
                    <li><i class="fa-solid fa-check"></i><span>Tham gia flash sale & event độc quyền</span></li>
                    <li><i class="fa-solid fa-check"></i><span>Truy cập lịch sử đơn hàng đầy đủ</span></li>
                </ul>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="register.php" class="mem-tier-btn btn-tier-outline">Đăng ký ngay</a>
                <?php else: ?>
                    <?php if ($userTier === 'silver'): ?>
                    <span class="mem-tier-btn btn-tier-outline" style="cursor: default;">
                        <i class="fa-solid fa-user-check"></i>&ensp;Hạng hiện tại của bạn
                    </span>
                    <?php else: ?>
                    <span class="mem-tier-btn" style="background: #f5f5f5; color: #bbb; cursor: default; font-size:12px; font-weight:700; letter-spacing:1px; text-transform:uppercase; padding:14px; display:block; text-align:center;">Đã vượt hạng này</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- GOLD (featured) -->
            <div class="mem-tier-card featured">
                <span class="mem-tier-label label-diamond">
                    <i class="fa-solid fa-crown"></i> Vàng
                </span>
                <div class="mem-tier-name" style="color:#fff;">Thành Viên Vàng</div>
                <div class="mem-tier-cond">
                    <i class="fa-solid fa-circle"></i> Chi tiêu từ 3.000.000đ / năm
                </div>
                <div class="mem-tier-price" style="border-color: rgba(255,255,255,0.1);">
                    <div class="mem-tier-price-main" style="color: #f1c40f;">Tự động nâng hạng</div>
                    <div class="mem-tier-price-sub" style="color: rgba(255,255,255,0.35);">Khi đạt điều kiện chi tiêu</div>
                </div>
                <ul class="mem-tier-benefits">
                    <li><i class="fa-solid fa-check"></i><span><strong>Giảm 5%</strong> trực tiếp mọi đơn thuê</span></li>
                    <li><i class="fa-solid fa-check"></i><span>Freeship 2 chiều (giao & nhận)</span></li>
                    <li><i class="fa-solid fa-check"></i><span>Được giữ trang phục thêm <strong>1 ngày</strong></span></li>
                    <li><i class="fa-solid fa-check"></i><span>Quà tặng sinh nhật đặc biệt</span></li>
                    <li><i class="fa-solid fa-check"></i><span>Ưu tiên đặt lịch thử đồ</span></li>
                    <li><i class="fa-solid fa-check"></i><span>Tích điểm nhân đôi vào cuối tuần</span></li>
                </ul>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="register.php" class="mem-tier-btn btn-tier-primary">Đăng ký & tích lũy</a>
                <?php elseif ($userTier === 'gold'): ?>
                    <span class="mem-tier-btn btn-tier-ghost" style="cursor: default;">
                        <i class="fa-solid fa-user-check"></i>&ensp;Hạng hiện tại của bạn
                    </span>
                <?php elseif ($userTier === 'silver'): ?>
                    <a href="ao_dai.php" class="mem-tier-btn btn-tier-primary">Mua sắm để nâng hạng</a>
                <?php else: ?>
                    <span class="mem-tier-btn btn-tier-ghost" style="cursor: default;">Đã vượt hạng này</span>
                <?php endif; ?>
            </div>

            <!-- DIAMOND -->
            <div class="mem-tier-card">
                <span class="mem-tier-label label-diamond">
                    <i class="fa-regular fa-gem"></i> Kim Cương
                </span>
                <div class="mem-tier-name">Kim Cương VIP</div>
                <div class="mem-tier-cond">
                    <i class="fa-solid fa-circle"></i> Chi tiêu từ 10.000.000đ / năm
                </div>
                <div class="mem-tier-price">
                    <div class="mem-tier-price-main">Đặc quyền tối thượng</div>
                    <div class="mem-tier-price-sub">Hạng cao nhất — phục vụ ưu tiên tuyệt đối</div>
                </div>
                <ul class="mem-tier-benefits">
                    <li><i class="fa-solid fa-check"></i><span><strong>Giảm 10%</strong> trực tiếp mọi đơn thuê</span></li>
                    <li><i class="fa-solid fa-check"></i><span>Ưu tiên thử đồ tại nhà (miễn phí)</span></li>
                    <li><i class="fa-solid fa-check"></i><span>Được giữ trang phục thêm <strong>2 ngày</strong></span></li>
                    <li><i class="fa-solid fa-check"></i><span>Đặc quyền Pre-order mẫu mới nhất</span></li>
                    <li><i class="fa-solid fa-check"></i><span>Stylist tư vấn riêng theo yêu cầu</span></li>
                    <li><i class="fa-solid fa-check"></i><span>Freeship ưu tiên + giao hỏa tốc miễn phí</span></li>
                </ul>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="register.php" class="mem-tier-btn btn-tier-primary">Bắt đầu hành trình</a>
                <?php elseif ($userTier === 'diamond'): ?>
                    <span class="mem-tier-btn btn-tier-primary" style="cursor: default;">
                        <i class="fa-solid fa-gem"></i>&ensp;Hạng hiện tại của bạn
                    </span>
                <?php else: ?>
                    <a href="ao_dai.php" class="mem-tier-btn btn-tier-outline">Mua sắm để đạt hạng</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ===== HOW IT WORKS ===== -->
    <div class="mem-how-bg">
        <div class="mem-section-sm">
            <div class="mem-eyebrow">Cơ chế vận hành</div>
            <h2 class="mem-heading">Cách hoạt động</h2>
            <div class="mem-steps">
                <div class="mem-step">
                    <div class="mem-step-num">01</div>
                    <div class="mem-step-title">Đăng ký tài khoản</div>
                    <p class="mem-step-desc">Tạo tài khoản miễn phí tại QHTN. Bạn ngay lập tức trở thành thành viên Bạc và bắt đầu tích điểm.</p>
                </div>
                <div class="mem-step">
                    <div class="mem-step-num">02</div>
                    <div class="mem-step-title">Mua sắm & tích lũy</div>
                    <p class="mem-step-desc">Mỗi đơn hàng hoàn thành trong năm đều được tính vào tổng chi tiêu để xét nâng hạng thành viên.</p>
                </div>
                <div class="mem-step">
                    <div class="mem-step-num">03</div>
                    <div class="mem-step-title">Tự động nâng hạng</div>
                    <p class="mem-step-desc">Hệ thống tự động ghi nhận và nâng hạng ngay khi bạn đạt điều kiện chi tiêu — không cần thao tác thủ công.</p>
                </div>
                <div class="mem-step">
                    <div class="mem-step-num">04</div>
                    <div class="mem-step-title">Tận hưởng đặc quyền</div>
                    <p class="mem-step-desc">Giảm giá tự động áp dụng ở mọi đơn hàng, cùng hàng loạt ưu đãi độc quyền theo hạng thành viên.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== COMPARISON TABLE ===== -->
    <div class="mem-section">
        <div class="mem-eyebrow">So Sánh Quyền Lợi</div>
        <h2 class="mem-heading">Bảng so sánh<br>chi tiết các hạng</h2>

        <div class="mem-table-wrap">
            <table class="mem-compare">
                <thead>
                    <tr>
                        <th style="width: 34%;">Quyền lợi</th>
                        <th><i class="fa-solid fa-medal" style="color:#aaa"></i>&ensp;Bạc</th>
                        <th class="th-gold"><i class="fa-solid fa-crown"></i>&ensp;Vàng</th>
                        <th class="th-diamond"><i class="fa-regular fa-gem"></i>&ensp;Kim Cương</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Điều kiện chi tiêu / năm</td>
                        <td>Miễn phí</td>
                        <td class="check-val">≥ 3.000.000đ</td>
                        <td class="check-val">≥ 10.000.000đ</td>
                    </tr>
                    <tr>
                        <td>Giảm giá trực tiếp</td>
                        <td><i class="fa-solid fa-minus check-no"></i></td>
                        <td class="check-val">5%</td>
                        <td class="check-val">10%</td>
                    </tr>
                    <tr>
                        <td>Tích điểm đổi quà</td>
                        <td><i class="fa-solid fa-check check-yes"></i></td>
                        <td><i class="fa-solid fa-check check-yes"></i></td>
                        <td><i class="fa-solid fa-check check-yes"></i></td>
                    </tr>
                    <tr>
                        <td>Điểm nhân đôi cuối tuần</td>
                        <td><i class="fa-solid fa-minus check-no"></i></td>
                        <td><i class="fa-solid fa-check check-yes"></i></td>
                        <td><i class="fa-solid fa-check check-yes"></i></td>
                    </tr>
                    <tr>
                        <td>Freeship 2 chiều</td>
                        <td><i class="fa-solid fa-minus check-no"></i></td>
                        <td><i class="fa-solid fa-check check-yes"></i></td>
                        <td><i class="fa-solid fa-check check-yes"></i></td>
                    </tr>
                    <tr>
                        <td>Giao hỏa tốc miễn phí</td>
                        <td><i class="fa-solid fa-minus check-no"></i></td>
                        <td><i class="fa-solid fa-minus check-no"></i></td>
                        <td><i class="fa-solid fa-check check-yes"></i></td>
                    </tr>
                    <tr>
                        <td>Giữ đồ thêm</td>
                        <td><i class="fa-solid fa-minus check-no"></i></td>
                        <td class="check-val">+1 ngày</td>
                        <td class="check-val">+2 ngày</td>
                    </tr>
                    <tr>
                        <td>Thử đồ tại nhà</td>
                        <td><i class="fa-solid fa-minus check-no"></i></td>
                        <td><i class="fa-solid fa-minus check-no"></i></td>
                        <td><i class="fa-solid fa-check check-yes"></i></td>
                    </tr>
                    <tr>
                        <td>Pre-order mẫu mới</td>
                        <td><i class="fa-solid fa-minus check-no"></i></td>
                        <td><i class="fa-solid fa-minus check-no"></i></td>
                        <td><i class="fa-solid fa-check check-yes"></i></td>
                    </tr>
                    <tr>
                        <td>Stylist tư vấn riêng</td>
                        <td><i class="fa-solid fa-minus check-no"></i></td>
                        <td><i class="fa-solid fa-minus check-no"></i></td>
                        <td><i class="fa-solid fa-check check-yes"></i></td>
                    </tr>
                    <tr>
                        <td>Quà tặng sinh nhật</td>
                        <td><i class="fa-solid fa-minus check-no"></i></td>
                        <td><i class="fa-solid fa-check check-yes"></i></td>
                        <td><i class="fa-solid fa-check check-yes"></i></td>
                    </tr>
                    <tr>
                        <td>Hỗ trợ tư vấn</td>
                        <td>24/7 Chat</td>
                        <td class="check-val">Ưu tiên</td>
                        <td class="check-val">Hotline riêng</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ===== FAQ ===== -->
    <div class="mem-how-bg">
        <div class="mem-section-sm">
            <div class="mem-eyebrow">Câu Hỏi Thường Gặp</div>
            <h2 class="mem-heading">FAQ</h2>
            <div class="mem-faq">
                <div class="mem-faq-item">
                    <div class="mem-faq-q"><i class="fa-solid fa-circle-dot"></i> Hạng thành viên được tính như thế nào?</div>
                    <p class="mem-faq-a">Hạng được xét dựa trên tổng giá trị các đơn hàng đã hoàn thành (status: completed, confirmed, ongoing) trong năm dương lịch hiện tại.</p>
                </div>
                <div class="mem-faq-item">
                    <div class="mem-faq-q"><i class="fa-solid fa-circle-dot"></i> Hạng có bị hạ vào năm sau không?</div>
                    <p class="mem-faq-a">Hạng của bạn được giữ đến cuối năm. Sang năm mới, hạng sẽ được tính lại dựa theo chi tiêu tích lũy trong năm mới đó.</p>
                </div>
                <div class="mem-faq-item">
                    <div class="mem-faq-q"><i class="fa-solid fa-circle-dot"></i> Giảm giá được áp dụng ở đâu?</div>
                    <p class="mem-faq-a">Chiết khấu áp dụng tự động vào tất cả đơn thuê trang phục. Không áp dụng với tiền cọc và phí dịch vụ phát sinh khác.</p>
                </div>
                <div class="mem-faq-item">
                    <div class="mem-faq-q"><i class="fa-solid fa-circle-dot"></i> Làm sao biết mình đủ điều kiện nâng hạng?</div>
                    <p class="mem-faq-a">Đăng nhập và truy cập trang này — thanh tiến trình sẽ hiển thị số tiền cần thêm. Hệ thống tự động nâng hạng ngay khi đạt mốc.</p>
                </div>
                <div class="mem-faq-item">
                    <div class="mem-faq-q"><i class="fa-solid fa-circle-dot"></i> Tích điểm có thời hạn không?</div>
                    <p class="mem-faq-a">Điểm tích lũy có hiệu lực trong vòng 12 tháng kể từ ngày phát sinh. Điểm hết hạn sẽ tự động xóa khỏi tài khoản.</p>
                </div>
                <div class="mem-faq-item">
                    <div class="mem-faq-q"><i class="fa-solid fa-circle-dot"></i> Dịch vụ thử đồ tại nhà (Kim Cương) hoạt động thế nào?</div>
                    <p class="mem-faq-a">Thành viên Kim Cương có thể đặt lịch stylish mang trang phục đến nhà để thử — tối đa 3 bộ/lần, hoàn toàn miễn phí phí dịch vụ.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== CTA BOTTOM ===== -->
    <section class="mem-cta-bottom">
        <h2>Bắt đầu hành trình<br>thành viên hôm nay</h2>
        <p>Đăng ký miễn phí — tích lũy ngay từ đơn hàng đầu tiên</p>
        <div class="mem-cta-btns">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="register.php" class="cta-primary-btn">Tạo tài khoản miễn phí</a>
                <a href="login.php" class="cta-ghost-btn">Đã có tài khoản</a>
            <?php else: ?>
                <a href="ao_dai.php" class="cta-primary-btn">Khám phá bộ sưu tập</a>
                <a href="profile.php" class="cta-ghost-btn">Xem hồ sơ thành viên</a>
            <?php endif; ?>
        </div>
    </section>

</div>

<?php include 'footer.php'; ?>