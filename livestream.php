<?php
require_once 'config.php';

// ── Auto-create tables ──
$conn->exec("
    CREATE TABLE IF NOT EXISTS livestream_sessions (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        title      VARCHAR(255) NOT NULL,
        description TEXT,
        host       VARCHAR(100) DEFAULT 'MinQuin Team',
        status     ENUM('upcoming','live','ended') DEFAULT 'upcoming',
        platform   ENUM('youtube','facebook','tiktok') DEFAULT 'youtube',
        stream_url VARCHAR(512),
        embed_id   VARCHAR(128),
        thumbnail  VARCHAR(255),
        scheduled_at DATETIME,
        ended_at   DATETIME,
        viewers    INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$conn->exec("
    CREATE TABLE IF NOT EXISTS livestream_reminders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        session_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_reminder (user_id, session_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$conn->exec("
    CREATE TABLE IF NOT EXISTS livestream_featured_products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_id INT NOT NULL,
        product_id INT NOT NULL,
        sort_order INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// ── Seed sample data if empty ──
$stmtChk = $conn->query("SELECT COUNT(*) FROM livestream_sessions");
if ((int)$stmtChk->fetchColumn() === 0) {
    $conn->exec("
        INSERT INTO livestream_sessions (title, description, host, status, platform, embed_id, thumbnail, scheduled_at, viewers) VALUES
        ('✨ Lookbook Hè 2026 — Váy Maxi & Đồ Đi Biển',
         'Khám phá bộ sưu tập mùa hè với những mẫu máy maxi, set đi biển cực trendy. Chị em sẽ được thấy cách phối đồ thực tế, review chất liệu và mức giá thuê hấp dẫn.',
         'Minh Quyên', 'live', 'youtube', '5aU6EXel1Cs', NULL,
         DATE_ADD(NOW(), INTERVAL -1 HOUR), 1247),

        ('🎀 Áo Dài Cách Tân 2026 — Phong Cách & Tôn Dáng',
         'Tư vấn chọn áo dài phù hợp từng vóc dáng. So sánh các mẫu áo dài cách tân hot nhất, hướng dẫn phối phụ kiện để có set đồ hoàn hảo.',
         'Thảo Ngân', 'upcoming', 'youtube', NULL, NULL,
         DATE_ADD(NOW(), INTERVAL 2 DAY), 0),

        ('👑 Top 20 Váy Dự Tiệc Hot Nhất Tháng 3',
         'Review 20 mẫu váy thiết kế được yêu thích nhất, so sánh chi tiết chất liệu, form dáng và giá thuê. Tặng mã giảm 20% cho người xem live.',
         'Diệu Huyền', 'upcoming', 'facebook', NULL, NULL,
         DATE_ADD(NOW(), INTERVAL 5 DAY), 0),

        ('💼 Bí Kíp Chọn Set Quần Áo Công Sở Sang Chảnh',
         'Hướng dẫn chọn set đồ công sở thanh lịch nhưng vẫn trẻ trung. Phối đồ đi làm mà không nhàm chán, tips chọn màu sắc hợp xu hướng 2026.',
         'Phương Trinh', 'ended', 'youtube', 'dQw4w9WgXcQ', NULL,
         DATE_ADD(NOW(), INTERVAL -7 DAY), 3821),

        ('👠 Workshop Phối Giày & Phụ Kiện Cùng Đồ Thuê',
         'Giải đáp thắc mắc: nên chọn giày gì, túi gì để phối cùng từng loại trang phục. Bật mí list phụ kiện must-have khi thuê đồ ở MinQuin.',
         'Minh Quyên', 'ended', 'tiktok', NULL, NULL,
         DATE_ADD(NOW(), INTERVAL -14 DAY), 2156),

        ('🌸 Xu Hướng Trang Phục Dự Đám Cưới 2026',
         'Mùa cưới đến rồi! Review những mẫu váy, áo dài phù hợp dự đám cưới. Làm sao để nổi bật mà không lấn át cô dâu — bí kíp từ chuyên gia thời trang MinQuin.',
         'Thảo Ngân', 'ended', 'youtube', 'dQw4w9WgXcQ', NULL,
         DATE_ADD(NOW(), INTERVAL -21 DAY), 5432)
    ");

    // Seed featured products (link to first 4 products for first session)
    $conn->exec("
        INSERT IGNORE INTO livestream_featured_products (session_id, product_id, sort_order)
        SELECT 1, id, ROW_NUMBER() OVER (ORDER BY id) FROM products LIMIT 4
    ");
    $conn->exec("
        INSERT IGNORE INTO livestream_featured_products (session_id, product_id, sort_order)
        SELECT 2, id, ROW_NUMBER() OVER (ORDER BY id) FROM products WHERE category_id = 1 LIMIT 4
    ");
}

// ── Fetch sessions ──
$filterStatus = isset($_GET['tab']) && in_array($_GET['tab'], ['live','upcoming','ended']) ? $_GET['tab'] : 'all';

$sqlSessions = "SELECT * FROM livestream_sessions";
$params = [];
if ($filterStatus !== 'all') { $sqlSessions .= " WHERE status = ?"; $params[] = $filterStatus; }
$sqlSessions .= " ORDER BY FIELD(status,'live','upcoming','ended'), scheduled_at DESC";
$stmtSessions = $conn->prepare($sqlSessions);
$stmtSessions->execute($params);
$sessions = $stmtSessions->fetchAll(PDO::FETCH_ASSOC);

// ── Active live session ──
$stmtLive = $conn->prepare("SELECT * FROM livestream_sessions WHERE status='live' ORDER BY scheduled_at DESC LIMIT 1");
$stmtLive->execute();
$liveSession = $stmtLive->fetch(PDO::FETCH_ASSOC);

// ── Stats ──
$stmtStats = $conn->query("SELECT status, COUNT(*) cnt, SUM(viewers) total_viewers FROM livestream_sessions GROUP BY status");
$rawStats = $stmtStats->fetchAll(PDO::FETCH_ASSOC);
$statsMap = ['live'=>0,'upcoming'=>0,'ended'=>0];
$totalViewers = 0;
foreach ($rawStats as $r) { if (isset($statsMap[$r['status']])) $statsMap[$r['status']] = (int)$r['cnt']; $totalViewers += (int)$r['total_viewers']; }

// ── User reminder set ──
$myReminders = [];
if (isset($_SESSION['user_id'])) {
    $stmtRem = $conn->prepare("SELECT session_id FROM livestream_reminders WHERE user_id = ?");
    $stmtRem->execute([$_SESSION['user_id']]);
    foreach ($stmtRem->fetchAll() as $r) $myReminders[] = (int)$r['session_id'];
}

// ── Featured products for live session ──
$featuredProducts = [];
if ($liveSession) {
    $stmtFeat = $conn->prepare("
        SELECT p.id, p.name, p.price, p.image, p.short_note
        FROM livestream_featured_products fp
        JOIN products p ON fp.product_id = p.id
        WHERE fp.session_id = ?
        ORDER BY fp.sort_order LIMIT 6
    ");
    $stmtFeat->execute([$liveSession['id']]);
    $featuredProducts = $stmtFeat->fetchAll(PDO::FETCH_ASSOC);
}

$pageTitle = 'MinQuin Live — Thời Trang Trực Tiếp';
include 'header.php';

$platformMeta = [
    'youtube'  => ['icon' => 'fa-brands fa-youtube',  'color' => '#ff0000', 'label' => 'YouTube'],
    'facebook' => ['icon' => 'fa-brands fa-facebook', 'color' => '#1877f2', 'label' => 'Facebook'],
    'tiktok'   => ['icon' => 'fa-brands fa-tiktok',   'color' => '#010101', 'label' => 'TikTok'],
];
?>

<style>
/* ============================================================
   LIVESTREAM PAGE — MinQuin CORPORATE EDITION
   Dark-accented · Pink-Burgundy design system
============================================================ */
:root {
    --ls-dark: #0f0811;
    --ls-dark2: #1a0e1d;
    --ls-pink: #e95a8a;
    --ls-pink-light: #f7c8d9;
    --ls-gold: #f0b429;
}
.ls-page { background: #f8f5f6; min-height: 80vh; font-family: 'Montserrat', sans-serif; }

/* ── HERO BANNER ── */
.ls-hero {
    background: linear-gradient(135deg, #0f0811 0%, #2f1c26 50%, #5a2138 100%);
    padding: 0;
    position: relative;
    overflow: hidden;
    min-height: 280px;
    display: flex;
    flex-direction: column;
}
.ls-hero-noise {
    position: absolute; inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
    opacity: 0.4; pointer-events: none;
}
.ls-hero-glow {
    position: absolute; top: -80px; right: 5%;
    width: 400px; height: 400px;
    background: radial-gradient(circle, rgba(233,90,138,0.25) 0%, transparent 70%);
    pointer-events: none;
}

.ls-hero-inner {
    max-width: 1280px; margin: 0 auto; padding: 40px 5%;
    position: relative; z-index: 2;
    display: grid; grid-template-columns: 1fr auto; gap: 24px; align-items: center;
    width: 100%;
}
.ls-hero-kicker {
    display: inline-flex; align-items: center; gap: 8px;
    font-size: 9px; font-weight: 800; letter-spacing: 4px;
    text-transform: uppercase; color: rgba(255,255,255,0.4); margin-bottom: 10px;
}
.ls-hero-kicker .live-pulse {
    width: 8px; height: 8px; background: #ff3b3b; border-radius: 50%;
    animation: lsPulse 1.5s infinite;
    box-shadow: 0 0 0 0 rgba(255,59,59,0.7);
}
@keyframes lsPulse {
    0%   { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255,59,59,0.7); }
    70%  { transform: scale(1.0);  box-shadow: 0 0 0 8px rgba(255,59,59,0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255,59,59,0); }
}
.ls-hero-title {
    font-size: 36px; font-weight: 900; color: #fff;
    text-transform: uppercase; letter-spacing: -1px; line-height: 1.1;
    margin-bottom: 10px;
}
.ls-hero-title span { color: var(--ls-pink); }
.ls-hero-sub { font-size: 13px; color: rgba(255,255,255,0.45); line-height: 1.7; max-width: 500px; }

/* Hero Stats */
.ls-hero-stats { display: flex; flex-direction: column; gap: 8px; flex-shrink: 0; }
.ls-hero-stat {
    background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08);
    padding: 14px 20px; text-align: center; min-width: 110px;
    backdrop-filter: blur(4px);
}
.ls-hero-stat-num { font-size: 24px; font-weight: 900; color: #fff; line-height: 1; }
.ls-hero-stat-num span { font-size: 12px; color: var(--ls-pink); }
.ls-hero-stat-label { font-size: 9px; color: rgba(255,255,255,0.35); font-weight: 700; letter-spacing: 1px; text-transform: uppercase; margin-top: 4px; }

/* Status tabs */
.ls-tabs-wrap { background: rgba(0,0,0,0.3); border-top: 1px solid rgba(255,255,255,0.06); position: relative; z-index: 2; }
.ls-tabs { max-width: 1280px; margin: 0 auto; padding: 0 5%; display: flex; align-items: center; gap: 0; }
.ls-tab {
    padding: 14px 22px; font-size: 11px; font-weight: 700; letter-spacing: 1.5px;
    text-transform: uppercase; color: rgba(255,255,255,0.4); text-decoration: none;
    border-bottom: 3px solid transparent; transition: all 0.2s; display: flex; align-items: center; gap: 7px;
}
.ls-tab:hover { color: rgba(255,255,255,0.8); }
.ls-tab.active { color: #fff; border-bottom-color: var(--ls-pink); }
.ls-tab-badge {
    background: rgba(255,255,255,0.12); color: rgba(255,255,255,0.6);
    font-size: 9px; font-weight: 800; padding: 2px 7px; min-width: 20px; text-align: center;
}
.ls-tab.active .ls-tab-badge { background: var(--ls-pink); color: #fff; }
.ls-tab-live-dot { width: 7px; height: 7px; background: #ff3b3b; border-radius: 50%; animation: lsPulse 1.5s infinite; }

/* ── MAIN LAYOUT ── */
.ls-main { max-width: 1280px; margin: 0 auto; padding: 32px 5%; }

/* ── LIVE NOW SECTION ── */
.ls-live-section { margin-bottom: 32px; }
.ls-section-header { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.ls-section-title { font-size: 13px; font-weight: 900; color: #2f1c26; text-transform: uppercase; letter-spacing: 2px; display: flex; align-items: center; gap: 8px; }
.ls-section-title i { color: var(--ls-pink); }
.ls-section-line { flex: 1; height: 1px; background: #ecdde4; min-width: 20px; }

/* Live Player Box */
.ls-player-box {
    display: grid; grid-template-columns: 1fr 320px; gap: 0;
    border: 1px solid #ecdde4; background: #fff;
    min-height: 360px;
    box-shadow: 0 8px 40px rgba(233,90,138,0.1);
}
.ls-player-left { background: var(--ls-dark); position: relative; overflow: hidden; min-height: 360px; }
.ls-player-embed { width: 100%; height: 100%; min-height: 360px; display: block; border: none; }
.ls-player-overlay { /* shown when no embed */ }

.ls-player-no-embed {
    position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 16px;
    background: linear-gradient(135deg, #0f0811, #2f1c26);
}
.ls-player-no-embed .pulse-icon { font-size: 64px; color: rgba(233,90,138,0.3); animation: iconPulse 2s infinite; }
@keyframes iconPulse { 0%,100%{opacity:0.4;transform:scale(1)} 50%{opacity:0.8;transform:scale(1.05)} }
.ls-player-no-embed p { color: rgba(255,255,255,0.5); font-size: 13px; }

/* Live badge on player */
.ls-live-badge-player {
    position: absolute; top: 16px; left: 16px; z-index: 3;
    background: #ff3b3b; color: #fff; font-size: 9px; font-weight: 800;
    letter-spacing: 2px; text-transform: uppercase; padding: 5px 12px;
    display: flex; align-items: center; gap: 6px;
    box-shadow: 0 2px 12px rgba(255,59,59,0.5);
}
.ls-live-badge-player::before {
    content: ''; width: 6px; height: 6px; background: #fff; border-radius: 50%; animation: lsPulse 1.5s infinite;
}

/* Live Info Panel */
.ls-player-info { padding: 24px; display: flex; flex-direction: column; gap: 14px; border-left: 1px solid #ecdde4; overflow-y: auto; }
.ls-player-info-title { font-size: 14px; font-weight: 800; color: #2f1c26; line-height: 1.4; }
.ls-player-info-host { display: flex; align-items: center; gap: 8px; font-size: 11.5px; color: #888; }
.ls-player-info-host i { color: var(--ls-pink); font-size: 13px; }
.ls-player-info-desc { font-size: 12px; color: #aaa; line-height: 1.7; }
.ls-player-info-views { display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; color: #e84a5f; }
.ls-player-info-sep { height: 1px; background: #f5eff2; }
.ls-player-info-products-title { font-size: 9px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; color: #bbb; }
.ls-feat-products { display: flex; flex-direction: column; gap: 8px; }
.ls-feat-product { display: flex; align-items: center; gap: 10px; text-decoration: none; padding: 6px; transition: background 0.15s; }
.ls-feat-product:hover { background: #fff8fb; }
.ls-feat-product img { width: 36px; height: 42px; object-fit: cover; object-position: top; flex-shrink: 0; background: #f5eff2; }
.ls-feat-product-name { font-size: 11px; font-weight: 700; color: #2f1c26; line-height: 1.3; flex: 1; }
.ls-feat-product-price { font-size: 11px; font-weight: 800; color: var(--ls-pink); flex-shrink: 0; }

/* ── SESSION GRID ── */
.ls-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }

/* Session Card */
.ls-card { background: #fff; border: 1px solid #ecdde4; transition: box-shadow 0.25s, transform 0.2s; position: relative; overflow: hidden; }
.ls-card:hover { box-shadow: 0 8px 28px rgba(233,90,138,0.12); transform: translateY(-2px); }

.ls-card-thumb { position: relative; aspect-ratio: 16/9; background: linear-gradient(135deg, #1a0e1d, #2f1c26); overflow: hidden; }
.ls-card-thumb-img { width: 100%; height: 100%; object-fit: cover; opacity: 0.85; transition: opacity 0.3s; }
.ls-card:hover .ls-card-thumb-img { opacity: 1; }
.ls-card-thumb-default { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; }
.ls-card-thumb-default i { font-size: 40px; color: rgba(233,90,138,0.25); }
.ls-card-status-badge {
    position: absolute; top: 10px; left: 10px; z-index: 2;
    font-size: 8px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase;
    padding: 4px 10px; display: flex; align-items: center; gap: 5px;
}
.ls-card-status-badge.badge-live { background: #ff3b3b; color: #fff; }
.ls-card-status-badge.badge-upcoming { background: rgba(240,180,41,0.92); color: #fff; }
.ls-card-status-badge.badge-ended { background: rgba(47,28,38,0.75); color: rgba(255,255,255,0.6); }
.ls-card-status-badge .dot { width: 5px; height: 5px; background: currentColor; border-radius: 50%; animation: lsPulse 1.5s infinite; }
.badge-ended .dot { animation: none; opacity: 0.5; }

.ls-card-platform {
    position: absolute; top: 10px; right: 10px; z-index: 2;
    width: 26px; height: 26px; display: flex; align-items: center; justify-content: center;
    background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);
    font-size: 12px; color: #fff;
}
.ls-card-viewers {
    position: absolute; bottom: 10px; left: 10px; z-index: 2;
    background: rgba(0,0,0,0.6); color: rgba(255,255,255,0.8);
    font-size: 9px; font-weight: 700; padding: 3px 8px; display: flex; align-items: center; gap: 4px; backdrop-filter: blur(4px);
}
.ls-card-viewers.hidden { display: none; }
.ls-play-overlay {
    position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
    background: rgba(233,90,138,0.0); transition: background 0.25s; z-index: 1;
}
.ls-card:hover .ls-play-overlay { background: rgba(233,90,138,0.12); }
.ls-play-btn {
    width: 44px; height: 44px; background: rgba(255,255,255,0.9);
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; color: #2f1c26; opacity: 0; transition: opacity 0.25s;
}
.ls-card:hover .ls-play-btn { opacity: 1; }
.ls-card-body { padding: 16px; }
.ls-card-title { font-size: 13px; font-weight: 800; color: #2f1c26; line-height: 1.4; margin-bottom: 6px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.ls-card-host { font-size: 11px; color: #aaa; display: flex; align-items: center; gap: 5px; margin-bottom: 8px; }
.ls-card-host i { color: var(--ls-pink); }
.ls-card-date { font-size: 10.5px; color: #bbb; display: flex; align-items: center; gap: 5px; }
.ls-card-date i { color: var(--ls-pink); font-size: 10px; }
.ls-card-foot { padding: 12px 16px; border-top: 1px solid #f5eff2; display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.ls-btn {
    padding: 8px 16px; font-size: 9px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase;
    border: none; cursor: pointer; text-decoration: none;
    display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;
    font-family: 'Montserrat', sans-serif;
}
.ls-btn.primary { background: var(--ls-pink); color: #fff; }
.ls-btn.primary:hover { background: #d54f7a; }
.ls-btn.dark { background: #2f1c26; color: #fff; }
.ls-btn.dark:hover { background: var(--ls-pink); }
.ls-btn.ghost { background: transparent; border: 1.5px solid #ecdde4; color: #888; }
.ls-btn.ghost:hover { border-color: var(--ls-pink); color: var(--ls-pink); }
.ls-btn.ghost.reminded { border-color: var(--ls-gold); color: var(--ls-gold); }
.ls-btn.ghost.reminded i { }
.ls-btn.gold { background: rgba(240,180,41,0.12); border: 1.5px solid var(--ls-gold); color: var(--ls-gold); }
.ls-btn.gold:hover { background: var(--ls-gold); color: #fff; }

/* Empty State */
.ls-empty { padding: 64px 20px; text-align: center; background: #fff; border: 1px solid #ecdde4; }
.ls-empty i { font-size: 48px; color: #f7c8d9; margin-bottom: 16px; display: block; }
.ls-empty h3 { font-size: 15px; font-weight: 800; color: #2f1c26; text-transform: uppercase; margin-bottom: 8px; }
.ls-empty p { font-size: 13px; color: #bbb; }

/* Watch Modal */
.ls-watch-modal { display: none; position: fixed; inset: 0; z-index: 9999; align-items: center; justify-content: center; background: rgba(0,0,0,0.88); backdrop-filter: blur(8px); }
.ls-watch-modal.open { display: flex; }
.ls-watch-box { background: #0f0811; width: 90%; max-width: 900px; border: 1px solid rgba(255,255,255,0.08); }
.ls-watch-head { padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.06); }
.ls-watch-head-title { font-size: 12px; font-weight: 800; color: #fff; letter-spacing: 1px; max-width: 80%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ls-watch-close { background: none; border: none; color: rgba(255,255,255,0.4); font-size: 26px; cursor: pointer; line-height: 1; transition: color 0.2s; }
.ls-watch-close:hover { color: #fff; }
.ls-watch-player { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; }
.ls-watch-player iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none; }

/* Toast */
.ls-toast { position: fixed; bottom: 28px; right: 28px; z-index: 10000; padding: 14px 24px; background: #2f1c26; color: #fff; font-size: 13px; font-weight: 600; box-shadow: 0 8px 32px rgba(0,0,0,0.25); transform: translateY(20px); opacity: 0; transition: all 0.35s; pointer-events: none; }
.ls-toast.show { transform: translateY(0); opacity: 1; }
.ls-toast.success { border-left: 4px solid #3fb27f; }
.ls-toast.info    { border-left: 4px solid var(--ls-pink); }
.ls-toast.error   { border-left: 4px solid #e84a5f; }

/* Responsive */
@media (max-width: 1024px) { .ls-player-box { grid-template-columns: 1fr; } .ls-player-info { border-left: none; border-top: 1px solid #ecdde4; } }
@media (max-width: 900px)  { .ls-grid { grid-template-columns: repeat(2, 1fr); } .ls-hero-inner { grid-template-columns: 1fr; } .ls-hero-stats { flex-direction: row; } }
@media (max-width: 600px)  { .ls-grid { grid-template-columns: 1fr; } .ls-hero-title { font-size: 26px; } }
</style>

<div class="ls-page">

<!-- ── HERO ── -->
<div class="ls-hero">
    <div class="ls-hero-noise"></div>
    <div class="ls-hero-glow"></div>
    <div class="ls-hero-inner">
        <div>
            <div class="ls-hero-kicker">
                <?php if ($statsMap['live'] > 0): ?>
                    <span class="live-pulse"></span> Đang phát sóng trực tiếp
                <?php else: ?>
                    <i class="fa-solid fa-video" style="font-size:9px;color:rgba(255,255,255,0.4)"></i>
                    Kênh thời trang MinQuin
                <?php endif; ?>
            </div>
            <div class="ls-hero-title">MinQuin <span>LIVE</span></div>
            <div class="ls-hero-sub">Xem trực tiếp những bộ sưu tập thời trang mới nhất, tips phối đồ từ chuyên gia, và nhận ưu đãi độc quyền chỉ dành cho người xem live.</div>
        </div>
        <div class="ls-hero-stats" style="flex-direction:row;gap:8px">
            <div class="ls-hero-stat">
                <div class="ls-hero-stat-num"><?= $statsMap['live'] ?><span> LIVE</span></div>
                <div class="ls-hero-stat-label">Đang phát</div>
            </div>
            <div class="ls-hero-stat">
                <div class="ls-hero-stat-num"><?= $statsMap['upcoming'] ?></div>
                <div class="ls-hero-stat-label">Sắp tới</div>
            </div>
            <div class="ls-hero-stat">
                <div class="ls-hero-stat-num"><?= number_format($totalViewers) ?></div>
                <div class="ls-hero-stat-label">Lượt xem</div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="ls-tabs-wrap">
        <div class="ls-tabs">
            <a href="livestream.php" class="ls-tab <?= $filterStatus==='all' ? 'active' : '' ?>">
                Tất cả <span class="ls-tab-badge"><?= array_sum($statsMap) ?></span>
            </a>
            <a href="livestream.php?tab=live" class="ls-tab <?= $filterStatus==='live' ? 'active' : '' ?>">
                <span class="ls-tab-live-dot"></span> Đang Live
                <span class="ls-tab-badge"><?= $statsMap['live'] ?></span>
            </a>
            <a href="livestream.php?tab=upcoming" class="ls-tab <?= $filterStatus==='upcoming' ? 'active' : '' ?>">
                <i class="fa-solid fa-clock" style="font-size:10px"></i> Sắp diễn ra
                <span class="ls-tab-badge"><?= $statsMap['upcoming'] ?></span>
            </a>
            <a href="livestream.php?tab=ended" class="ls-tab <?= $filterStatus==='ended' ? 'active' : '' ?>">
                <i class="fa-solid fa-film" style="font-size:10px"></i> Đã phát
                <span class="ls-tab-badge"><?= $statsMap['ended'] ?></span>
            </a>
        </div>
    </div>
</div>

<!-- ── MAIN CONTENT ── -->
<div class="ls-main">

    <!-- LIVE NOW SECTION -->
    <?php if ($liveSession && ($filterStatus === 'all' || $filterStatus === 'live')): ?>
    <div class="ls-live-section">
        <div class="ls-section-header">
            <div class="ls-section-title">
                <i class="fa-solid fa-circle" style="color:#ff3b3b;animation:lsPulse 1.5s infinite;font-size:10px"></i>
                Đang phát sóng trực tiếp
            </div>
            <div class="ls-section-line"></div>
        </div>

        <div class="ls-player-box">
            <!-- Video Player -->
            <div class="ls-player-left">
                <span class="ls-live-badge-player">🔴 LIVE</span>
                <?php if (!empty($liveSession['embed_id']) && $liveSession['platform'] === 'youtube'): ?>
                    <iframe class="ls-player-embed"
                            src="https://www.youtube.com/embed/<?= htmlspecialchars($liveSession['embed_id']) ?>?autoplay=0&controls=1&rel=0"
                            title="<?= htmlspecialchars($liveSession['title']) ?>"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen></iframe>
                <?php else: ?>
                    <div class="ls-player-no-embed">
                        <i class="fa-solid fa-video pulse-icon"></i>
                        <p>Đang chuẩn bị phát sóng…</p>
                        <button class="ls-btn primary" onclick="openWatch(<?= $liveSession['id'] ?>, '<?= htmlspecialchars(addslashes($liveSession['title'])) ?>', '<?= $liveSession['embed_id'] ?>', '<?= $liveSession['platform'] ?>')">
                            <i class="fa-solid fa-play"></i> Xem ngay
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Info Panel -->
            <div class="ls-player-info">
                <div class="ls-player-info-title"><?= htmlspecialchars($liveSession['title']) ?></div>
                <div class="ls-player-info-host">
                    <i class="fa-solid fa-circle-user"></i>
                    <?= htmlspecialchars($liveSession['host']) ?>
                </div>
                <?php if ($liveSession['viewers'] > 0): ?>
                <div class="ls-player-info-views">
                    <i class="fa-solid fa-eye"></i>
                    <?= number_format($liveSession['viewers']) ?> người đang xem
                </div>
                <?php endif; ?>
                <div class="ls-player-info-sep"></div>
                <div class="ls-player-info-desc"><?= nl2br(htmlspecialchars($liveSession['description'])) ?></div>

                <?php if (!empty($featuredProducts)): ?>
                <div class="ls-player-info-sep"></div>
                <div class="ls-player-info-products-title">
                    <i class="fa-solid fa-tag" style="color:var(--ls-pink)"></i>
                    Sản phẩm trong phiên live
                </div>
                <div class="ls-feat-products">
                    <?php foreach ($featuredProducts as $fp): ?>
                    <a href="product_detail.php?id=<?= $fp['id'] ?>" class="ls-feat-product">
                        <img src="<?= htmlspecialchars($fp['image'] ?? 'img/default.jpg') ?>"
                             alt="<?= htmlspecialchars($fp['name']) ?>"
                             onerror="this.src='img/default.jpg'">
                        <div class="ls-feat-product-name"><?= htmlspecialchars($fp['name']) ?></div>
                        <div class="ls-feat-product-price"><?= number_format($fp['price']) ?>đ</div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <a href="<?= htmlspecialchars($liveSession['stream_url'] ?? '#') ?>"
                   target="_blank"
                   class="ls-btn primary"
                   onclick="openWatch(<?= $liveSession['id'] ?>, '<?= htmlspecialchars(addslashes($liveSession['title'])) ?>', '<?= $liveSession['embed_id'] ?>', '<?= $liveSession['platform'] ?>'); return false;">
                    <i class="fa-solid fa-play"></i> Xem fullscreen
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- SESSION GRID -->
    <?php
    $gridSessions = array_filter($sessions, function($s) use ($filterStatus, $liveSession) {
        if ($filterStatus !== 'all') return true;
        if ($liveSession && $s['id'] === $liveSession['id']) return false;
        return true;
    });
    ?>

    <?php if ($filterStatus !== 'all' || !empty($gridSessions)): ?>
    <div class="ls-section-header">
        <div class="ls-section-title">
            <i class="fa-solid fa-film"></i>
            <?php
            if ($filterStatus === 'live') echo 'Phiên đang phát';
            elseif ($filterStatus === 'upcoming') echo 'Lịch phát sóng';
            elseif ($filterStatus === 'ended') echo 'Đã phát — Xem lại';
            else echo 'Tất cả phiên phát sóng';
            ?>
        </div>
        <div class="ls-section-line"></div>
    </div>
    <?php endif; ?>

    <?php if (!empty($gridSessions)): ?>
    <div class="ls-grid">
        <?php foreach ($gridSessions as $sess):
            $pm = $platformMeta[$sess['platform']] ?? $platformMeta['youtube'];
            $isLive = $sess['status'] === 'live';
            $isUpcoming = $sess['status'] === 'upcoming';
            $isEnded = $sess['status'] === 'ended';
            $reminded = in_array((int)$sess['id'], $myReminders);
            $scheduledFmt = $sess['scheduled_at'] ? date('d/m/Y · H:i', strtotime($sess['scheduled_at'])) : '';
        ?>
        <div class="ls-card" id="sess-<?= $sess['id'] ?>">
            <!-- Thumbnail -->
            <div class="ls-card-thumb">
                <?php if ($sess['thumbnail']): ?>
                    <img class="ls-card-thumb-img" src="<?= htmlspecialchars($sess['thumbnail']) ?>" alt="">
                <?php else: ?>
                    <div class="ls-card-thumb-default">
                        <i class="fa-solid fa-<?= $isLive ? 'circle-dot' : ($isUpcoming ? 'clock' : 'film') ?>"></i>
                    </div>
                <?php endif; ?>

                <!-- Status Badge -->
                <span class="ls-card-status-badge badge-<?= $sess['status'] ?>">
                    <span class="dot"></span>
                    <?= $isLive ? 'Live' : ($isUpcoming ? 'Sắp tới' : 'Replay') ?>
                </span>

                <!-- Platform -->
                <span class="ls-card-platform" style="color:<?= $pm['color'] ?>">
                    <i class="<?= $pm['icon'] ?>"></i>
                </span>

                <!-- Viewers -->
                <?php if ($sess['viewers'] > 0): ?>
                <span class="ls-card-viewers">
                    <i class="fa-solid fa-eye"></i> <?= number_format($sess['viewers']) ?>
                </span>
                <?php endif; ?>

                <!-- Play overlay -->
                <?php if ($isLive || $isEnded): ?>
                <div class="ls-play-overlay">
                    <div class="ls-play-btn"><i class="fa-solid fa-play"></i></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Body -->
            <div class="ls-card-body">
                <div class="ls-card-title"><?= htmlspecialchars($sess['title']) ?></div>
                <div class="ls-card-host"><i class="fa-solid fa-circle-user"></i><?= htmlspecialchars($sess['host']) ?></div>
                <div class="ls-card-date">
                    <i class="fa-regular fa-calendar"></i>
                    <?= $scheduledFmt ?>
                </div>
            </div>

            <!-- Footer -->
            <div class="ls-card-foot">
                <span style="font-size:10px;color:#bbb;display:flex;align-items:center;gap:4px">
                    <i class="<?= $pm['icon'] ?>" style="color:<?= $pm['color'] ?>"></i>
                    <?= $pm['label'] ?>
                </span>
                <div style="display:flex;gap:6px;align-items:center">
                    <?php if ($isUpcoming && isset($_SESSION['user_id'])): ?>
                        <button id="rem-btn-<?= $sess['id'] ?>"
                                class="ls-btn ghost <?= $reminded ? 'reminded' : '' ?>"
                                onclick="toggleReminder(<?= $sess['id'] ?>)">
                            <i class="fa-<?= $reminded ? 'solid' : 'regular' ?> fa-bell"></i>
                            <?= $reminded ? 'Đã nhắc' : 'Nhắc tôi' ?>
                        </button>
                    <?php elseif ($isUpcoming): ?>
                        <a href="login.php" class="ls-btn ghost">
                            <i class="fa-regular fa-bell"></i> Nhắc tôi
                        </a>
                    <?php endif; ?>

                    <?php if ($isLive || $isEnded): ?>
                        <button class="ls-btn <?= $isLive ? 'primary' : 'dark' ?>"
                                onclick="openWatch(<?= $sess['id'] ?>, '<?= htmlspecialchars(addslashes($sess['title'])) ?>', '<?= $sess['embed_id'] ?>', '<?= $sess['platform'] ?>')">
                            <i class="fa-solid fa-<?= $isLive ? 'circle-dot' : 'play' ?>"></i>
                            <?= $isLive ? 'Xem Live' : 'Xem lại' ?>
                        </button>
                    <?php else: ?>
                        <span class="ls-btn gold" style="cursor:default">
                            <i class="fa-solid fa-hourglass-half"></i> Sắp phát
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php elseif (empty($sessions)): ?>
    <div class="ls-empty">
        <i class="fa-solid fa-video"></i>
        <h3>Chưa có phiên phát sóng nào</h3>
        <p>Theo dõi MinQuin trên mạng xã hội để nhận thông báo về các phiên live sắp tới.</p>
    </div>
    <?php endif; ?>

</div><!-- .ls-main -->
</div><!-- .ls-page -->

<!-- Watch Modal -->
<div class="ls-watch-modal" id="watchModal">
    <div class="ls-watch-box">
        <div class="ls-watch-head">
            <span class="ls-watch-head-title" id="watchTitle">Đang tải…</span>
            <button class="ls-watch-close" onclick="closeWatch()">&times;</button>
        </div>
        <div class="ls-watch-player" id="watchPlayer"></div>
    </div>
</div>

<div class="ls-toast" id="lsToast"></div>

<script>
(function () {
    function toast(msg, type = 'info') {
        const el = document.getElementById('lsToast');
        el.textContent = msg;
        el.className = 'ls-toast ' + type + ' show';
        setTimeout(() => el.classList.remove('show'), 3200);
    }

    // ── Watch Modal ──
    window.openWatch = function (id, title, embedId, platform) {
        document.getElementById('watchTitle').textContent = title;
        const player = document.getElementById('watchPlayer');
        let src = '';
        if (platform === 'youtube' && embedId) {
            src = `https://www.youtube.com/embed/${embedId}?autoplay=1&rel=0`;
            player.innerHTML = `<iframe src="${src}" title="${title}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
        } else if (platform === 'facebook' && embedId) {
            src = `https://www.facebook.com/video/embed?video_id=${embedId}`;
            player.innerHTML = `<iframe src="${src}" title="${title}" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share" allowfullscreen></iframe>`;
        } else {
            player.innerHTML = `<div style="padding:48px;text-align:center;color:rgba(255,255,255,0.4);font-size:13px"><i class="fa-solid fa-video-slash" style="font-size:40px;display:block;margin-bottom:12px"></i>Liên kết phát sóng chưa sẵn sàng.<br>Vui lòng quay lại sau.</div>`;
        }
        document.getElementById('watchModal').classList.add('open');
    };

    window.closeWatch = function () {
        document.getElementById('watchModal').classList.remove('open');
        document.getElementById('watchPlayer').innerHTML = '';
    };

    document.getElementById('watchModal').addEventListener('click', function (e) {
        if (e.target === this) closeWatch();
    });

    // ESC to close
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeWatch(); });

    // ── Reminder Toggle ──
    window.toggleReminder = function (sessionId) {
        <?php if (!isset($_SESSION['user_id'])): ?>
        window.location = 'login.php';
        return;
        <?php endif; ?>

        fetch('livestream_ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=toggle_reminder&session_id=' + sessionId
        })
        .then(r => r.json())
        .then(d => {
            const btn = document.getElementById('rem-btn-' + sessionId);
            if (!btn) return;
            if (d.status === 'added') {
                btn.innerHTML = '<i class="fa-solid fa-bell"></i> Đã nhắc';
                btn.classList.add('reminded');
                toast('✅ Đã đặt nhắc nhở cho phiên live!', 'success');
            } else if (d.status === 'removed') {
                btn.innerHTML = '<i class="fa-regular fa-bell"></i> Nhắc tôi';
                btn.classList.remove('reminded');
                toast('Đã hủy nhắc nhở.', 'info');
            } else {
                toast(d.message || 'Có lỗi xảy ra.', 'error');
            }
        })
        .catch(() => toast('Lỗi kết nối.', 'error'));
    };
})();
</script>

<?php include 'footer.php'; ?>
