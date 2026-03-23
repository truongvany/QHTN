<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];

// ── User info (full columns)
$stmtUser = $conn->prepare('SELECT id, username, email, phone, avatar, created_at FROM users WHERE id = ? LIMIT 1');
$stmtUser->execute([$userId]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

// ── Avatar fallback
$avatarPath = !empty($user['avatar']) && file_exists($user['avatar'])
    ? $user['avatar']
    : 'img/avatars/hero.webp';

// ── Membership tier — tổng chi tiêu trong năm hiện tại
$yearStmt = $conn->prepare("
    SELECT COALESCE(SUM(o.total_price), 0) AS total_spent
    FROM orders o
    WHERE o.user_id = ? AND o.status IN ('completed','confirmed','ongoing')
      AND YEAR(o.created_at) = YEAR(NOW())
");
$yearStmt->execute([$userId]);
$yearSpent = (float)$yearStmt->fetchColumn();

if ($yearSpent >= 10000000)      { $tier = 'diamond'; $tierLabel = 'Kim Cương VIP'; $tierColor = '#b8860b'; $tierNext = null; $tierNextAmt = 0; }
elseif ($yearSpent >= 3000000)   { $tier = 'gold';    $tierLabel = 'Thành Viên Vàng'; $tierColor = '#c9a227'; $tierNext = 'Kim Cương'; $tierNextAmt = 10000000 - $yearSpent; }
else                             { $tier = 'silver';  $tierLabel = 'Thành Viên Bạc'; $tierColor = '#888'; $tierNext = 'Vàng';      $tierNextAmt = 3000000 - $yearSpent; }

$tierProgress = $tier === 'silver'
    ? min(100, round($yearSpent / 3000000 * 100))
    : ($tier === 'gold'
        ? min(100, round(($yearSpent - 3000000) / 7000000 * 100))
        : 100);

// ── Stats
$stmtTotal = $conn->prepare('SELECT COUNT(DISTINCT id) FROM orders WHERE user_id = ?');
$stmtTotal->execute([$userId]);
$totalOrders = (int)$stmtTotal->fetchColumn();

$stmtItems = $conn->prepare('SELECT COALESCE(SUM(od.quantity),0) FROM order_details od JOIN orders o ON od.order_id=o.id WHERE o.user_id = ?');
$stmtItems->execute([$userId]);
$totalItems = (int)$stmtItems->fetchColumn();

$stmtSpent = $conn->prepare("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE user_id = ? AND status IN ('completed','confirmed','ongoing')");
$stmtSpent->execute([$userId]);
$totalSpent = (float)$stmtSpent->fetchColumn();

$stmtActive = $conn->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status IN ('ongoing','confirmed','pending')");
$stmtActive->execute([$userId]);
$activeOrders = (int)$stmtActive->fetchColumn();

// ── Recent orders (grouped by order)
$sql = "SELECT o.id, o.total_price, o.status, o.created_at,
               od.rental_start, od.rental_end, od.duration_days,
               p.name AS product_name,
               od.quantity, od.price, p.image
        FROM orders o
        LEFT JOIN order_details od ON o.id = od.order_id
        LEFT JOIN products p ON od.product_id = p.id
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC, od.id DESC
        LIMIT 20";
$stmtOrders = $conn->prepare($sql);
$stmtOrders->execute([$userId]);
$allRows = $stmtOrders->fetchAll(PDO::FETCH_ASSOC);

// Group rows by order id
$groupedOrders = [];
foreach ($allRows as $row) {
    $oid = $row['id'];
    if (!isset($groupedOrders[$oid])) {
        $groupedOrders[$oid] = [
            'id'         => $row['id'],
            'total_price'=> $row['total_price'],
            'status'     => $row['status'],
            'created_at' => $row['created_at'],
            'items'      => []
        ];
    }
    if (!empty($row['product_name'])) {
        $groupedOrders[$oid]['items'][] = [
            'product_name' => $row['product_name'],
            'image'        => $row['image'],
            'quantity'     => $row['quantity'],
            'price'        => $row['price'],
            'rental_start' => $row['rental_start'],
            'rental_end'   => $row['rental_end'],
            'duration_days'=> $row['duration_days'],
        ];
    }
}

// Status map
$statusMap = [
    'pending'   => ['label' => 'Chờ xác nhận', 'color' => '#f0b429'],
    'confirmed' => ['label' => 'Đã xác nhận',  'color' => '#3fb27f'],
    'ongoing'   => ['label' => 'Đang thuê',    'color' => '#4c9fff'],
    'completed' => ['label' => 'Hoàn thành',   'color' => '#888'],
    'cancelled' => ['label' => 'Đã hủy',       'color' => '#e84a5f'],
];

$pageTitle = 'Hồ sơ của tôi | QHTN';
include 'header.php';
?>

<style>
/* ============================================================
   PROFILE PAGE — QHTN CORPORATE EDITION
   No border-radius · Enterprise tone · Pink-Burgundy palette
============================================================ */
.pf-page {
    background: #f8f5f6;
    min-height: 80vh;
    font-family: 'Montserrat', sans-serif;
}

/* ── TOP BANNER ── */
.pf-banner {
    background:
        linear-gradient(105deg, rgba(47,28,38,0.95) 0%, rgba(90,33,56,0.88) 55%, rgba(139,48,87,0.80) 100%),
        url('img/avatars/hero.webp') center 30% / cover no-repeat;
    padding: 48px 5% 0;
    position: relative;
    overflow: hidden;
}
.pf-banner::before {
    content: '';
    position: absolute; inset: 0;
    background: repeating-linear-gradient(
        45deg, transparent, transparent 60px,
        rgba(255,255,255,0.012) 60px, rgba(255,255,255,0.012) 61px
    );
}
.pf-banner-inner {
    max-width: 1280px; margin: 0 auto;
    position: relative; z-index: 1;
    display: flex; align-items: flex-end; gap: 36px;
    padding-bottom: 0;
}
.pf-avatar-wrap {
    position: relative; flex-shrink: 0;
    width: 118px; height: 118px;
    margin-bottom: -38px;
    top: -10px;
}
.pf-avatar-wrap img {
    width: 118px; height: 118px;
    object-fit: cover; object-position: center;
    border: 4px solid #fff; box-shadow: 0 6px 24px rgba(0,0,0,0.28);
    cursor: pointer; transition: filter 0.25s;
    display: block;
}
.pf-avatar-wrap:hover img { filter: brightness(0.7); }
.pf-avatar-cam {
    position: absolute; inset: 0; display: flex;
    align-items: center; justify-content: center;
    color: #fff; font-size: 20px; opacity: 0;
    transition: opacity 0.25s; pointer-events: none;
}
.pf-avatar-wrap:hover .pf-avatar-cam { opacity: 1; }
.pf-avatar-wrap input[type=file] { display: none; }

.pf-banner-text { padding-bottom: 36px; }
.pf-banner-kicker {
    font-size: 10px; font-weight: 700; letter-spacing: 3px;
    color: rgba(255,255,255,0.45); text-transform: uppercase; margin-bottom: 4px;
}
.pf-banner-name {
    font-size: 26px; font-weight: 900; color: #fff;
    letter-spacing: -0.5px; text-transform: uppercase; margin-bottom: 4px;
}
.pf-banner-email { font-size: 13px; color: rgba(255,255,255,0.5); }

.pf-banner-tier {
    margin-left: auto; margin-bottom: 36px; flex-shrink: 0;
    display: flex; align-items: center; gap: 10px;
    padding: 10px 20px;
    background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15);
}
.pf-tier-dot {
    width: 10px; height: 10px;
    flex-shrink: 0;
}
.pf-tier-label {
    font-size: 11px; font-weight: 700; letter-spacing: 2px;
    text-transform: uppercase; color: #fff;
}

/* ── MAIN LAYOUT ── */
.pf-main {
    max-width: 1280px; margin: 0 auto;
    padding: 40px 5%;
    display: grid; grid-template-columns: 280px 1fr;
    gap: 28px; align-items: start;
}

/* ── SIDEBAR ── */
.pf-sidebar {}
.pf-sidebar-card {
    background: #fff; border: 1px solid #ecdde4;
}
.pf-user-card {
    padding: 28px 24px; border-bottom: 1px solid #ecdde4;
    text-align: center;
}
.pf-user-card-name {
    font-size: 15px; font-weight: 800; color: #2f1c26;
    text-transform: uppercase; letter-spacing: -0.2px; margin-bottom: 4px;
}
.pf-user-card-email { font-size: 12px; color: #999; margin-bottom: 12px; }
.pf-user-card-joined {
    font-size: 11px; color: #bbb; letter-spacing: 0.5px;
}
.pf-tier-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; margin-bottom: 10px;
    font-size: 11px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;
}
.pf-tier-badge i { font-size: 10px; }

/* Progress */
.pf-progress-wrap { padding: 18px 24px; border-bottom: 1px solid #ecdde4; }
.pf-progress-label {
    display: flex; justify-content: space-between; align-items: baseline;
    margin-bottom: 8px;
}
.pf-progress-label span:first-child { font-size: 11px; color: #888; font-weight: 600; }
.pf-progress-label span:last-child  { font-size: 11px; color: var(--accent-pink); font-weight: 700; }
.pf-progress-bar {
    height: 4px; background: #f5e0e8; width: 100%; position: relative; overflow: hidden;
}
.pf-progress-fill {
    height: 100%; background: var(--accent-pink);
    transition: width 0.6s ease;
}
.pf-progress-note { font-size: 11px; color: #bbb; margin-top: 6px; line-height: 1.5; }

/* Nav */
.pf-nav { padding: 8px 0; }
.pf-nav a {
    display: flex; align-items: center; gap: 12px;
    padding: 13px 24px;
    font-size: 12.5px; font-weight: 600; color: #666;
    text-decoration: none; border-left: 3px solid transparent;
    transition: all 0.2s;
}
.pf-nav a:hover { color: var(--accent-pink); background: #fff8fb; border-left-color: #f7c8d9; }
.pf-nav a.active { color: var(--accent-pink); background: #fff0f5; border-left-color: var(--accent-pink); }
.pf-nav a i { width: 16px; text-align: center; font-size: 13px; color: var(--accent-pink); }
.pf-nav-sep { height: 1px; background: #ecdde4; margin: 6px 0; }
.pf-nav a.pf-logout { color: #e84a5f; }
.pf-nav a.pf-logout:hover { background: #fff5f5; border-left-color: #e84a5f; }
.pf-nav a.pf-logout i { color: #e84a5f; }

/* ── CONTENT RIGHT ── */
.pf-content { display: flex; flex-direction: column; gap: 24px; }

/* Stats strip */
.pf-stats {
    display: grid; grid-template-columns: repeat(4,1fr);
    gap: 0; border: 1px solid #ecdde4; background: #fff;
}
.pf-stat {
    padding: 24px 20px; border-right: 1px solid #ecdde4;
    text-align: center;
}
.pf-stat:last-child { border-right: none; }
.pf-stat-icon {
    width: 36px; height: 36px; margin: 0 auto 12px;
    background: #fff0f5; display: flex; align-items: center; justify-content: center;
    font-size: 14px; color: var(--accent-pink);
}
.pf-stat-num {
    font-size: 26px; font-weight: 900; color: #2f1c26;
    line-height: 1; margin-bottom: 4px; letter-spacing: -0.5px;
}
.pf-stat-num sub { font-size: 13px; font-weight: 700; color: var(--accent-pink); }
.pf-stat-label { font-size: 11px; color: #999; font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase; }

/* ── PROFILE INFO CARD ── */
.pf-card {
    background: #fff; border: 1px solid #ecdde4;
}
.pf-card-header {
    padding: 20px 28px; border-bottom: 1px solid #f0e0e8;
    display: flex; align-items: center; justify-content: space-between;
}
.pf-card-title {
    font-size: 13px; font-weight: 800; text-transform: uppercase;
    letter-spacing: 1.5px; color: #2f1c26;
    display: flex; align-items: center; gap: 10px;
}
.pf-card-title i { color: var(--accent-pink); font-size: 12px; }
.pf-btn-edit {
    padding: 8px 22px; background: #2f1c26; color: #fff;
    font-size: 11px; font-weight: 700; letter-spacing: 1.5px;
    text-transform: uppercase; border: none; cursor: pointer;
    transition: background 0.2s;
}
.pf-btn-edit:hover { background: var(--accent-pink); }

.pf-info-grid {
    padding: 24px 28px;
    display: grid; grid-template-columns: 1fr 1fr; gap: 0;
}
.pf-info-item {
    padding: 16px 0; border-bottom: 1px solid #f5eff2;
    padding-right: 28px;
}
.pf-info-item:nth-child(even) { padding-right: 0; padding-left: 28px; border-left: 1px solid #f5eff2; }
.pf-info-label {
    font-size: 10px; font-weight: 700; letter-spacing: 2px;
    text-transform: uppercase; color: var(--accent-pink); margin-bottom: 5px;
}
.pf-info-val {
    font-size: 14px; font-weight: 600; color: #2f1c26;
}
.pf-info-val.muted { color: #bbb; font-weight: 400; font-style: italic; }

/* ── MEMBERSHIP TIER CARD ── */
.pf-tier-card { background: #fff; border: 1px solid #ecdde4; }
.pf-tier-content { padding: 24px 28px; }
.pf-tier-row {
    display: flex; align-items: center; gap: 24px;
}
.pf-tier-icon-big {
    flex-shrink: 0; width: 64px; height: 64px;
    display: flex; align-items: center; justify-content: center;
    font-size: 28px;
}
.pf-tier-info { flex: 1; }
.pf-tier-tier-label {
    font-size: 10px; font-weight: 700; letter-spacing: 3px;
    text-transform: uppercase; color: #999; margin-bottom: 4px;
}
.pf-tier-tier-name {
    font-size: 20px; font-weight: 900; color: #2f1c26;
    text-transform: uppercase; letter-spacing: -0.3px; margin-bottom: 8px;
}
.pf-tier-spent {
    font-size: 13px; color: #888;
}
.pf-tier-spent strong { color: var(--accent-pink); }
.pf-tier-bar-wrap {
    height: 6px; background: #f0e0e8; position: relative; overflow: hidden;
    margin-top: 14px;
}
.pf-tier-bar-fill { height: 100%; background: var(--accent-pink); }
.pf-tier-next {
    font-size: 11px; color: #bbb; margin-top: 6px;
}
.pf-tier-next strong { color: #2f1c26; font-weight: 700; }
.pf-tier-perks {
    display: grid; grid-template-columns: repeat(3,1fr);
    gap: 0; border-top: 1px solid #f0e0e8; margin-top: 20px;
}
.pf-tier-perk {
    padding: 14px 18px; text-align: center;
    border-right: 1px solid #f0e0e8; font-size: 12px; color: #888;
}
.pf-tier-perk:last-child { border-right: none; }
.pf-tier-perk i { color: var(--accent-pink); display: block; margin-bottom: 5px; font-size: 14px; }
.pf-tier-perk strong { display: block; font-size: 13px; color: #2f1c26; font-weight: 700; margin-bottom: 2px; }

/* ── ORDER TABLE ── */
.pf-orders-card {}
.pf-orders-list { padding: 0 28px 24px; }
.pf-order-item {
    border: 1px solid #f0e0e8; margin-bottom: 12px;
    transition: box-shadow 0.2s;
}
.pf-order-item:last-child { margin-bottom: 0; }
.pf-order-item:hover { box-shadow: 0 4px 16px rgba(233,90,138,0.1); }
.pf-order-head {
    background: #fff8fb; padding: 12px 18px;
    display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
    border-bottom: 1px solid #f0e0e8;
}
.pf-order-id {
    font-size: 12px; font-weight: 800; color: #2f1c26;
    letter-spacing: 0.5px;
}
.pf-order-date { font-size: 11px; color: #bbb; margin-left: auto; }
.pf-order-status {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; font-size: 10px; font-weight: 700;
    letter-spacing: 1px; text-transform: uppercase;
}
.pf-order-total {
    font-size: 13px; font-weight: 800; color: var(--accent-pink);
}
.pf-order-items { padding: 12px 18px; display: flex; flex-direction: column; gap: 10px; }
.pf-order-product {
    display: flex; align-items: center; gap: 14px;
}
.pf-order-product-img {
    width: 52px; height: 60px; flex-shrink: 0;
    object-fit: cover; background: #f5eff2;
}
.pf-order-product-info {}
.pf-order-product-name {
    font-size: 13px; font-weight: 700; color: #2f1c26; margin-bottom: 3px;
}
.pf-order-product-meta {
    font-size: 11px; color: #aaa; line-height: 1.5;
}
.pf-order-product-meta strong { color: var(--accent-pink); }

/* Empty state */
.pf-empty {
    padding: 56px 28px; text-align: center;
}
.pf-empty i { font-size: 40px; color: #e5c8d4; margin-bottom: 16px; display: block; }
.pf-empty p { font-size: 14px; color: #bbb; margin-bottom: 18px; }
.pf-empty a {
    display: inline-block; padding: 12px 32px;
    background: var(--accent-pink); color: #fff;
    font-size: 12px; font-weight: 700; letter-spacing: 1.5px;
    text-transform: uppercase; text-decoration: none;
    transition: background 0.2s;
}
.pf-empty a:hover { background: var(--hover-pink); }

/* ── MODAL ── */
.pf-modal {
    display: none; position: fixed; inset: 0; z-index: 9999;
    align-items: center; justify-content: center;
    background: rgba(47,28,38,0.7); backdrop-filter: blur(4px);
}
.pf-modal.open { display: flex; }
.pf-modal-box {
    background: #fff; width: 100%; max-width: 520px;
    position: relative; box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}
.pf-modal-header {
    padding: 22px 28px; background: #2f1c26;
    display: flex; align-items: center; justify-content: space-between;
}
.pf-modal-title { font-size: 13px; font-weight: 800; color: #fff; letter-spacing: 2px; text-transform: uppercase; }
.pf-modal-close {
    background: none; border: none; color: rgba(255,255,255,0.5);
    font-size: 22px; cursor: pointer; padding: 0; line-height: 1;
    transition: color 0.2s;
}
.pf-modal-close:hover { color: #fff; }
.pf-modal-body { padding: 28px; }
.pf-modal-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.pf-field { margin-bottom: 18px; }
.pf-field:last-child { margin-bottom: 0; }
.pf-field label {
    display: block; font-size: 10px; font-weight: 700; letter-spacing: 2px;
    text-transform: uppercase; color: #888; margin-bottom: 7px;
}
.pf-field input {
    width: 100%; padding: 13px 16px;
    border: 1.5px solid #ecdde4; background: #fff;
    font-size: 13px; font-family: 'Montserrat', sans-serif; color: #2f1c26;
    outline: none; transition: border-color 0.2s; box-sizing: border-box;
}
.pf-field input:focus { border-color: var(--accent-pink); }
.pf-modal-footer {
    padding: 0 28px 28px;
    display: flex; gap: 10px; justify-content: flex-end;
}
.pf-modal-cancel {
    padding: 13px 28px; background: transparent;
    border: 1.5px solid #ecdde4; color: #888;
    font-size: 11px; font-weight: 700; letter-spacing: 1px;
    text-transform: uppercase; cursor: pointer; transition: all 0.2s;
}
.pf-modal-cancel:hover { border-color: #2f1c26; color: #2f1c26; }
.pf-modal-submit {
    padding: 13px 36px; background: var(--accent-pink); color: #fff;
    border: none; font-size: 11px; font-weight: 700; letter-spacing: 1px;
    text-transform: uppercase; cursor: pointer; transition: background 0.2s;
}
.pf-modal-submit:hover { background: var(--hover-pink); }
.pf-msg {
    margin: 0 28px 14px; padding: 12px 16px;
    font-size: 12.5px; font-weight: 600;
    border-left: 3px solid;
}
.pf-msg.success { background: #f0fff6; color: #1e7a42; border-color: #3fb27f; }
.pf-msg.error   { background: #fff5f5; color: #c0392b; border-color: #e84a5f; }
.pf-msg { display: none; }
.pf-msg.show { display: block; }

/* Alert toast */
.pf-toast {
    position: fixed; bottom: 28px; right: 28px; z-index: 10000;
    padding: 14px 24px; background: #2f1c26; color: #fff;
    font-size: 13px; font-weight: 600; letter-spacing: 0.5px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.25);
    transform: translateY(20px); opacity: 0;
    transition: all 0.35s ease; pointer-events: none;
}
.pf-toast.show { transform: translateY(0); opacity: 1; }
.pf-toast.success { border-left: 4px solid #3fb27f; }
.pf-toast.error   { border-left: 4px solid #e84a5f; }

/* Responsive */
@media (max-width: 1100px) {
    .pf-main { grid-template-columns: 1fr; }
    .pf-stats { grid-template-columns: repeat(2,1fr); }
    .pf-stat:nth-child(2) { border-right: none; }
    .pf-stat:nth-child(3) { border-top: 1px solid #ecdde4; }
    .pf-tier-perks { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 640px) {
    .pf-info-grid { grid-template-columns: 1fr; }
    .pf-info-item:nth-child(even) { padding-left: 0; border-left: none; }
    .pf-stats { grid-template-columns: 1fr 1fr; }
    .pf-modal-row { grid-template-columns: 1fr; }
    .pf-tier-perks { grid-template-columns: 1fr; }
    .pf-tier-perk { border-right: none; border-bottom: 1px solid #f0e0e8; }
    .pf-banner-tier { display: none; }
}
</style>

<div class="pf-page">

<!-- ── TOP BANNER ── -->
<div class="pf-banner">
    <div class="pf-banner-inner">
        <div class="pf-avatar-wrap" id="avatarWrap">
            <img id="pfAvatar"
                 src="<?= htmlspecialchars($avatarPath) ?>"
                 alt="Avatar">
            <div class="pf-avatar-cam"><i class="fa-solid fa-camera"></i></div>
            <input type="file" id="avatarInput" accept="image/jpeg,image/png,image/gif,image/webp">
        </div>
        <div class="pf-banner-text">
            <div class="pf-banner-kicker">Tài khoản của bạn</div>
            <div class="pf-banner-name" id="bannerName"><?= htmlspecialchars($user['username'] ?? 'Thành viên') ?></div>
            <div class="pf-banner-email"><?= htmlspecialchars($user['email'] ?? '') ?></div>
        </div>
        <div class="pf-banner-tier">
            <div class="pf-tier-dot" style="background:<?= $tierColor ?>"></div>
            <div class="pf-tier-label"><?= $tierLabel ?></div>
        </div>
    </div>
</div>

<!-- ── MAIN ── -->
<div class="pf-main">

    <!-- ── SIDEBAR ── -->
    <aside class="pf-sidebar">
        <div class="pf-sidebar-card">
            <div class="pf-user-card">
                <div class="pf-tier-badge" style="background:<?= $tier==='diamond'?'#fff8e1':($tier==='gold'?'#fffae5':'#f5f5f5') ?>; color:<?= $tierColor ?>; border-left: 3px solid <?= $tierColor ?>">
                    <i class="fa-solid <?= $tier==='diamond'?'fa-gem':($tier==='gold'?'fa-crown':'fa-shield-halved') ?>"></i>
                    <?= $tierLabel ?>
                </div>
                <div class="pf-user-card-name"><?= htmlspecialchars($user['username'] ?? 'Thành viên') ?></div>
                <div class="pf-user-card-email"><?= htmlspecialchars($user['email'] ?? '') ?></div>
                <div class="pf-user-card-joined">Thành viên từ <?= date('m/Y', strtotime($user['created_at'])) ?></div>
            </div>

            <?php if ($tier !== 'diamond' && $tierNextAmt > 0): ?>
            <div class="pf-progress-wrap">
                <div class="pf-progress-label">
                    <span>Tiến độ lên <?= $tierNext ?></span>
                    <span><?= $tierProgress ?>%</span>
                </div>
                <div class="pf-progress-bar">
                    <div class="pf-progress-fill" style="width:<?= $tierProgress ?>%"></div>
                </div>
                <div class="pf-progress-note">
                    Cần thêm <strong><?= number_format($tierNextAmt) ?>đ</strong> để lên <?= $tierNext ?>
                </div>
            </div>
            <?php endif; ?>

            <nav class="pf-nav">
                <a href="profile.php" class="active">
                    <i class="fa-solid fa-circle-user"></i> Hồ sơ cá nhân
                </a>
                <a href="orders.php">
                    <i class="fa-solid fa-receipt"></i> Đơn hàng của tôi
                </a>
                <a href="wishlist.php">
                    <i class="fa-solid fa-heart"></i> Sản phẩm yêu thích
                </a>
                <a href="membership.php">
                    <i class="fa-solid fa-crown"></i> Quyền lợi thành viên
                </a>
                <div class="pf-nav-sep"></div>
                <a href="logout.php" class="pf-logout">
                    <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
                </a>
            </nav>
        </div>
    </aside>

    <!-- ── CONTENT ── -->
    <div class="pf-content">

        <!-- Stats -->
        <div class="pf-stats">
            <div class="pf-stat">
                <div class="pf-stat-icon"><i class="fa-solid fa-receipt"></i></div>
                <div class="pf-stat-num"><?= number_format($totalOrders) ?></div>
                <div class="pf-stat-label">Tổng đơn hàng</div>
            </div>
            <div class="pf-stat">
                <div class="pf-stat-icon"><i class="fa-solid fa-shirt"></i></div>
                <div class="pf-stat-num"><?= number_format($totalItems) ?></div>
                <div class="pf-stat-label">Lượt thuê</div>
            </div>
            <div class="pf-stat">
                <div class="pf-stat-icon"><i class="fa-solid fa-fire"></i></div>
                <div class="pf-stat-num"><?= number_format($activeOrders) ?></div>
                <div class="pf-stat-label">Đang thuê</div>
            </div>
            <div class="pf-stat">
                <div class="pf-stat-icon"><i class="fa-solid fa-wallet"></i></div>
                <div class="pf-stat-num" style="font-size:18px"><?= number_format($totalSpent/1000) ?><sub>K</sub></div>
                <div class="pf-stat-label">Tổng chi tiêu</div>
            </div>
        </div>

        <!-- Profile Info -->
        <div class="pf-card">
            <div class="pf-card-header">
                <div class="pf-card-title">
                    <i class="fa-solid fa-id-card"></i>
                    Thông Tin Cá Nhân
                </div>
                <button class="pf-btn-edit" id="openEditModal">
                    <i class="fa-solid fa-pen"></i> Chỉnh sửa
                </button>
            </div>
            <div class="pf-info-grid">
                <div class="pf-info-item">
                    <div class="pf-info-label">Tên người dùng</div>
                    <div class="pf-info-val" id="infoUsername"><?= htmlspecialchars($user['username'] ?? '-') ?></div>
                </div>
                <div class="pf-info-item">
                    <div class="pf-info-label">Email</div>
                    <div class="pf-info-val" id="infoEmail"><?= htmlspecialchars($user['email'] ?? '-') ?></div>
                </div>
                <div class="pf-info-item">
                    <div class="pf-info-label">Số điện thoại</div>
                    <div class="pf-info-val <?= empty($user['phone']) ? 'muted' : '' ?>" id="infoPhone">
                        <?= htmlspecialchars($user['phone'] ?? 'Chưa cập nhật') ?>
                    </div>
                </div>
                <div class="pf-info-item">
                    <div class="pf-info-label">Ngày tham gia</div>
                    <div class="pf-info-val"><?= date('d/m/Y', strtotime($user['created_at'])) ?></div>
                </div>
            </div>
        </div>

        <!-- Membership Tier -->
        <div class="pf-card pf-tier-card">
            <div class="pf-card-header">
                <div class="pf-card-title">
                    <i class="fa-solid fa-crown"></i>
                    Hạng Thành Viên
                </div>
                <a href="membership.php" style="font-size:11px;font-weight:700;color:var(--accent-pink);text-decoration:none;letter-spacing:1px;text-transform:uppercase">
                    Chi tiết →
                </a>
            </div>
            <div class="pf-tier-content">
                <div class="pf-tier-row">
                    <div class="pf-tier-icon-big" style="background:<?= $tier==='diamond'?'#fff8e1':($tier==='gold'?'#fffae5':'#f5f5f5') ?>">
                        <?php if ($tier === 'diamond'): ?>
                            <i class="fa-solid fa-gem" style="color:#b8860b"></i>
                        <?php elseif ($tier === 'gold'): ?>
                            <i class="fa-solid fa-crown" style="color:#c9a227"></i>
                        <?php else: ?>
                            <i class="fa-solid fa-shield-halved" style="color:#888"></i>
                        <?php endif; ?>
                    </div>
                    <div class="pf-tier-info">
                        <div class="pf-tier-tier-label">Hạng hiện tại</div>
                        <div class="pf-tier-tier-name" style="color:<?= $tierColor ?>"><?= $tierLabel ?></div>
                        <div class="pf-tier-spent">
                            Chi tiêu năm <?= date('Y') ?>: <strong><?= number_format($yearSpent) ?>đ</strong>
                        </div>
                        <?php if ($tier !== 'diamond'): ?>
                        <div class="pf-tier-bar-wrap">
                            <div class="pf-tier-bar-fill" style="width:<?= $tierProgress ?>%"></div>
                        </div>
                        <div class="pf-tier-next">
                            Cần thêm <strong><?= number_format($tierNextAmt) ?>đ</strong> để lên hạng <strong><?= $tierNext ?></strong>
                        </div>
                        <?php else: ?>
                        <div class="pf-tier-next" style="color:var(--accent-pink);font-weight:700">
                            ★ Bạn đang ở hạng cao nhất — Kim Cương VIP
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="pf-tier-perks">
                    <?php if ($tier === 'silver'): ?>
                        <div class="pf-tier-perk"><i class="fa-solid fa-check"></i><strong>Tích điểm</strong>mỗi đơn hàng</div>
                        <div class="pf-tier-perk"><i class="fa-solid fa-headset"></i><strong>HT 24/7</strong>qua chat</div>
                        <div class="pf-tier-perk"><i class="fa-solid fa-bolt"></i><strong>Flash sale</strong>ưu tiên</div>
                    <?php elseif ($tier === 'gold'): ?>
                        <div class="pf-tier-perk"><i class="fa-solid fa-percent"></i><strong>Giảm 5%</strong>mọi đơn thuê</div>
                        <div class="pf-tier-perk"><i class="fa-solid fa-truck"></i><strong>Freeship</strong>2 chiều</div>
                        <div class="pf-tier-perk"><i class="fa-solid fa-gift"></i><strong>Quà sinh nhật</strong>đặc biệt</div>
                    <?php else: ?>
                        <div class="pf-tier-perk"><i class="fa-solid fa-percent"></i><strong>Giảm 10%</strong>mọi đơn thuê</div>
                        <div class="pf-tier-perk"><i class="fa-solid fa-house"></i><strong>Thử đồ</strong>tại nhà miễn phí</div>
                        <div class="pf-tier-perk"><i class="fa-solid fa-user-tie"></i><strong>Stylist</strong>riêng theo yêu cầu</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="pf-card pf-orders-card">
            <div class="pf-card-header">
                <div class="pf-card-title">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    Đơn Hàng Gần Đây
                </div>
                <a href="orders.php" style="font-size:11px;font-weight:700;color:var(--accent-pink);text-decoration:none;letter-spacing:1px;text-transform:uppercase">
                    Xem tất cả →
                </a>
            </div>
            <div class="pf-orders-list">
                <?php if (!empty($groupedOrders)): ?>
                    <?php foreach (array_slice($groupedOrders, 0, 5) as $order):
                        $st = $order['status'] ?? 'pending';
                        $stInfo = $statusMap[$st] ?? ['label' => ucfirst($st), 'color' => '#888'];
                    ?>
                    <div class="pf-order-item">
                        <div class="pf-order-head">
                            <span class="pf-order-id">ĐƠN #<?= $order['id'] ?></span>
                            <span class="pf-order-status" style="background:<?= $stInfo['color'] ?>20; color:<?= $stInfo['color'] ?>; border-left: 3px solid <?= $stInfo['color'] ?>">
                                <?= $stInfo['label'] ?>
                            </span>
                            <span class="pf-order-total"><?= number_format($order['total_price']) ?>đ</span>
                            <span class="pf-order-date"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></span>
                        </div>
                        <div class="pf-order-items">
                            <?php foreach ($order['items'] as $item): ?>
                            <div class="pf-order-product">
                                <?php
                                    $imgSrc = !empty($item['image']) ? 'img/' . basename($item['image']) : 'img/default.jpg';
                                ?>
                                <img class="pf-order-product-img" src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
                                <div class="pf-order-product-info">
                                    <div class="pf-order-product-name"><?= htmlspecialchars($item['product_name']) ?></div>
                                    <div class="pf-order-product-meta">
                                        SL: <strong><?= (int)$item['quantity'] ?></strong>
                                        · <?= htmlspecialchars($item['rental_start'] ?? '') ?> → <?= htmlspecialchars($item['rental_end'] ?? '') ?>
                                        · <strong><?= number_format((int)$item['price'] * max(1,(int)$item['quantity']) * max(1,(int)$item['duration_days'])) ?>đ</strong>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="pf-empty">
                        <i class="fa-solid fa-bag-shopping"></i>
                        <p>Bạn chưa có đơn hàng nào</p>
                        <a href="ao_dai.php">Khám phá ngay</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- .pf-content -->
</div><!-- .pf-main -->
</div><!-- .pf-page -->

<!-- ── EDIT MODAL ── -->
<div class="pf-modal" id="editModal">
    <div class="pf-modal-box">
        <div class="pf-modal-header">
            <span class="pf-modal-title"><i class="fa-solid fa-pen"></i>&ensp;Chỉnh sửa hồ sơ</span>
            <button class="pf-modal-close" id="closeModal">&times;</button>
        </div>
        <div id="modalMsg" class="pf-msg"></div>
        <form id="editForm" class="pf-modal-body">
            <div class="pf-modal-row">
                <div class="pf-field">
                    <label for="fUsername">Tên người dùng *</label>
                    <input type="text" id="fUsername" name="username"
                           value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
                </div>
                <div class="pf-field">
                    <label for="fPhone">Số điện thoại</label>
                    <input type="tel" id="fPhone" name="phone"
                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                           placeholder="0912 345 678">
                </div>
            </div>
            <div class="pf-field">
                <label for="fEmail">Email *</label>
                <input type="email" id="fEmail" name="email"
                       value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
            </div>
        </form>
        <div class="pf-modal-footer">
            <button class="pf-modal-cancel" id="cancelModal">Hủy</button>
            <button class="pf-modal-submit" id="submitEdit">
                <i class="fa-solid fa-floppy-disk"></i>&ensp;Lưu thay đổi
            </button>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="pf-toast" id="pfToast"></div>

<script>
(function () {
    const $ = id => document.getElementById(id);

    /* toast */
    function toast(msg, type = 'success') {
        const el = $('pfToast');
        el.textContent = msg;
        el.className = 'pf-toast ' + type + ' show';
        setTimeout(() => el.classList.remove('show'), 3500);
    }

    /* avatar upload */
    $('avatarWrap').addEventListener('click', () => $('avatarInput').click());
    $('avatarInput').addEventListener('change', function () {
        if (!this.files || !this.files[0]) return;
        const fd = new FormData();
        fd.append('avatar', this.files[0]);
        fetch('avatar_upload.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.status === 'success') {
                    $('pfAvatar').src = d.avatar_url + '?t=' + Date.now();
                    toast('Cập nhật ảnh đại diện thành công!');
                } else {
                    toast(d.message || 'Lỗi tải ảnh', 'error');
                }
            })
            .catch(() => toast('Có lỗi xảy ra', 'error'));
    });

    /* modal open/close */
    function openModal() { $('editModal').classList.add('open'); }
    function closeModal() {
        $('editModal').classList.remove('open');
        const msg = $('modalMsg');
        msg.className = 'pf-msg';
        msg.textContent = '';
    }
    $('openEditModal').addEventListener('click', openModal);
    $('closeModal').addEventListener('click', closeModal);
    $('cancelModal').addEventListener('click', closeModal);
    $('editModal').addEventListener('click', e => { if (e.target === $('editModal')) closeModal(); });

    /* submit edit */
    $('submitEdit').addEventListener('click', function () {
        const form = $('editForm');
        if (!form.reportValidity()) return;

        const fd = new FormData(form);
        const btn = this;
        btn.disabled = true;
        btn.textContent = 'Đang lưu...';

        fetch('edit_profile_ajax.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                const msg = $('modalMsg');
                if (d.status === 'success') {
                    msg.className = 'pf-msg success show';
                    msg.textContent = '✓ ' + d.message;
                    // Update page display
                    if ($('infoUsername')) $('infoUsername').textContent = d.username;
                    if ($('infoEmail'))    $('infoEmail').textContent    = d.email;
                    if ($('infoPhone'))    { $('infoPhone').textContent  = d.phone || 'Chưa cập nhật'; $('infoPhone').className = d.phone ? 'pf-info-val' : 'pf-info-val muted'; }
                    if ($('bannerName'))   $('bannerName').textContent   = d.username;
                    setTimeout(() => { closeModal(); toast('Hồ sơ đã được cập nhật!'); }, 900);
                } else {
                    msg.className = 'pf-msg error show';
                    msg.textContent = '✕ ' + d.message;
                }
            })
            .catch(() => {
                const msg = $('modalMsg');
                msg.className = 'pf-msg error show';
                msg.textContent = '✕ Có lỗi xảy ra, vui lòng thử lại.';
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i>&ensp;Lưu thay đổi';
            });
    });
})();
</script>

<?php include 'footer.php'; ?>
