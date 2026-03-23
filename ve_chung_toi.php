<?php
require_once 'config.php';
$pageTitle = "Về QHTN | Fashion & Studio";
include 'header.php';
?>

<style>
/* ============================================================
   VỀ CHÚNG TÔI — QHTN CORPORATE EDITION
   Sharp edges, enterprise tone, pink-burgundy palette
============================================================ */
.ab-page { background: #fff; font-family: 'Montserrat', sans-serif; color: var(--text-color); }

/* ─── HERO ─── */
.ab-hero {
    position: relative;
    min-height: 80vh;
    display: flex;
    align-items: flex-end;
    background:
        linear-gradient(to right, rgba(47,28,38,0.96) 32%, rgba(47,28,38,0.65) 60%, rgba(47,28,38,0.15) 100%),
        url('https://as2.ftcdn.net/v2/jpg/06/75/20/35/1000_F_675203597_CozdpomUpN56eZX1iePmXDNODiRTkcdO.jpg') center 25% / cover no-repeat;
    overflow: hidden;
}
.ab-hero::before {
    content: '';
    position: absolute; inset: 0;
    background: repeating-linear-gradient(
        45deg, transparent, transparent 80px,
        rgba(255,255,255,0.012) 80px, rgba(255,255,255,0.012) 81px
    );
}
.ab-hero-inner {
    position: relative; z-index: 1;
    max-width: 1280px; margin: 0 auto;
    padding: 0 5% 80px;
    width: 100%;
}
.ab-hero-kicker {
    display: inline-flex; align-items: center; gap: 10px;
    font-size: 11px; font-weight: 700; letter-spacing: 4px;
    text-transform: uppercase; color: #f48fb1; margin-bottom: 20px;
}
.ab-hero-kicker span { width: 28px; height: 1px; background: #f48fb1; display: inline-block; }
.ab-hero h1 {
    font-size: clamp(40px,5vw,68px); font-weight: 900;
    color: #fff; line-height: 1.02; letter-spacing: -2px;
    text-transform: uppercase; margin-bottom: 22px;
    max-width: 680px;
}
.ab-hero h1 em { font-style: normal; color: #f48fb1; }
.ab-hero-desc {
    font-size: 15px; color: rgba(255,255,255,0.62);
    line-height: 1.85; max-width: 500px; margin-bottom: 40px;
}
.ab-hero-btns { display: flex; gap: 14px; flex-wrap: wrap; }
.ab-btn-pink {
    padding: 16px 42px; background: var(--accent-pink); color: #fff;
    font-size: 12px; font-weight: 800; letter-spacing: 2px;
    text-transform: uppercase; text-decoration: none;
    transition: background 0.22s, transform 0.22s;
}
.ab-btn-pink:hover { background: var(--hover-pink); transform: translateY(-2px); }
.ab-btn-ghost {
    padding: 16px 42px; background: transparent;
    border: 1.5px solid rgba(255,255,255,0.22); color: rgba(255,255,255,0.72);
    font-size: 12px; font-weight: 800; letter-spacing: 2px;
    text-transform: uppercase; text-decoration: none;
    transition: all 0.22s;
}
.ab-btn-ghost:hover { border-color: rgba(255,255,255,0.55); color: #fff; }

/* Scroll indicator */
.ab-hero-scroll {
    position: absolute; bottom: 32px; right: 5%;
    display: flex; flex-direction: column; align-items: center; gap: 6px;
    z-index: 1;
}
.ab-hero-scroll span {
    writing-mode: vertical-rl; font-size: 10px; letter-spacing: 3px;
    text-transform: uppercase; color: rgba(255,255,255,0.35); font-weight: 600;
}
.ab-hero-scroll-line {
    width: 1px; height: 48px;
    background: linear-gradient(to bottom, rgba(255,255,255,0.5), transparent);
}

/* ─── METRICS STRIP ─── */
.ab-metrics {
    background: #2f1c26; border-top: 3px solid var(--accent-pink);
}
.ab-metrics-inner {
    max-width: 1280px; margin: 0 auto;
    display: grid; grid-template-columns: repeat(4,1fr);
}
.ab-metric {
    padding: 40px 32px; text-align: center;
    border-right: 1px solid rgba(255,255,255,0.07);
}
.ab-metric:last-child { border-right: none; }
.ab-metric-num {
    font-size: 38px; font-weight: 900; color: #fff;
    line-height: 1; margin-bottom: 8px; letter-spacing: -1px;
}
.ab-metric-num span { color: #f48fb1; }
.ab-metric-label {
    font-size: 11px; font-weight: 700; color: rgba(255,255,255,0.45);
    letter-spacing: 2px; text-transform: uppercase; margin-bottom: 4px;
}
.ab-metric-sub { font-size: 12px; color: rgba(255,255,255,0.28); line-height: 1.5; }

/* ─── SECTION SHELL ─── */
.ab-section { max-width: 1280px; margin: 0 auto; padding: 80px 5%; }
.ab-section-sm { max-width: 1280px; margin: 0 auto; padding: 56px 5%; }
.ab-eyebrow {
    font-size: 11px; font-weight: 700; letter-spacing: 4px;
    text-transform: uppercase; color: var(--accent-pink);
    display: flex; align-items: center; gap: 10px; margin-bottom: 10px;
}
.ab-eyebrow::before {
    content: ''; display: inline-block;
    width: 24px; height: 2px; background: var(--accent-pink);
}
.ab-heading {
    font-size: clamp(28px,3vw,38px); font-weight: 900;
    color: #2f1c26; line-height: 1.1; letter-spacing: -0.5px;
    text-transform: uppercase; margin-bottom: 12px;
}
.ab-subtext { font-size: 14px; color: #888; line-height: 1.85; max-width: 600px; }

/* ─── STORY ─── */
.ab-story-strip {
    background: #fff8fb;
    border-top: 1px solid #f7c8d9; border-bottom: 1px solid #f7c8d9;
}
.ab-story-grid {
    max-width: 1280px; margin: 0 auto; padding: 80px 5%;
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 0; align-items: stretch;
}
.ab-story-img {
    position: relative; overflow: hidden;
}
.ab-story-img img {
    width: 100%; height: 100%; min-height: 420px;
    object-fit: cover; display: block;
    filter: brightness(0.92) saturate(1.05);
}
.ab-story-img::after {
    content: '';
    position: absolute; inset:0;
    background: linear-gradient(135deg, rgba(233,90,138,0.08) 0%, transparent 60%);
}
.ab-story-text {
    padding: 64px 56px;
    display: flex; flex-direction: column; justify-content: center;
    background: #fff;
}
.ab-story-text p {
    font-size: 14px; color: #666; line-height: 1.9;
    margin-bottom: 18px;
}
.ab-story-text p:last-child { margin-bottom: 0; }
.ab-story-pull {
    font-size: 20px; font-weight: 800; color: #2f1c26;
    line-height: 1.4; letter-spacing: -0.3px;
    border-left: 4px solid var(--accent-pink);
    padding-left: 20px; margin: 28px 0;
    font-style: italic;
}

/* ─── CORE VALUES ─── */
.ab-values-grid {
    display: grid; grid-template-columns: repeat(4,1fr);
    gap: 0; border: 1.5px solid #f7c8d9; margin-top: 48px;
}
.ab-value-card {
    padding: 40px 32px; border-right: 1.5px solid #f7c8d9;
    transition: background 0.22s;
}
.ab-value-card:last-child { border-right: none; }
.ab-value-card:hover { background: #fff5f8; }
.ab-value-icon {
    width: 48px; height: 48px;
    background: #2f1c26; color: #f48fb1;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; margin-bottom: 24px;
}
.ab-value-title {
    font-size: 13px; font-weight: 900; text-transform: uppercase;
    letter-spacing: 1px; color: #2f1c26; margin-bottom: 12px;
}
.ab-value-en {
    font-size: 10px; font-weight: 700; letter-spacing: 2px;
    color: var(--accent-pink); text-transform: uppercase; margin-bottom: 8px;
}
.ab-value-desc { font-size: 13px; color: #777; line-height: 1.75; }

/* ─── TIMELINE ─── */
.ab-timeline-bg {
    background: #2f1c26;
    border-top: 1px solid rgba(255,255,255,0.06);
    border-bottom: 1px solid rgba(255,255,255,0.06);
}
.ab-timeline-inner {
    max-width: 1280px; margin: 0 auto; padding: 80px 5%;
}
.ab-timeline {
    margin-top: 52px;
    display: grid; grid-template-columns: repeat(5,1fr);
    gap: 0; border: 1px solid rgba(255,255,255,0.08);
    position: relative;
}
.ab-timeline::before {
    content: '';
    position: absolute; left: 0; right: 0;
    top: 48px; height: 2px;
    background: linear-gradient(90deg, var(--accent-pink), transparent);
    z-index: 0;
}
.ab-tl-item {
    padding: 40px 28px; border-right: 1px solid rgba(255,255,255,0.06);
    position: relative; z-index: 1;
    transition: background 0.22s;
}
.ab-tl-item:last-child { border-right: none; }
.ab-tl-item:hover { background: rgba(255,255,255,0.04); }
.ab-tl-dot {
    width: 14px; height: 14px; background: var(--accent-pink);
    margin-bottom: 18px; position: relative;
}
.ab-tl-dot::after {
    content: '';
    position: absolute; top: -4px; left: -4px;
    width: 22px; height: 22px;
    border: 1px solid rgba(233,90,138,0.3);
}
.ab-tl-year {
    font-size: 28px; font-weight: 900; color: #fff;
    letter-spacing: -1px; margin-bottom: 8px; line-height: 1;
}
.ab-tl-year span { font-size: 14px; color: #f48fb1; font-weight: 700; letter-spacing: 0; }
.ab-tl-title {
    font-size: 12px; font-weight: 800; text-transform: uppercase;
    letter-spacing: 1px; color: rgba(255,255,255,0.85); margin-bottom: 10px;
}
.ab-tl-desc { font-size: 12.5px; color: rgba(255,255,255,0.38); line-height: 1.7; }

/* ─── GALLERY ─── */
.ab-gallery {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    grid-template-rows: auto auto;
    gap: 3px; margin-top: 48px;
}
.ab-gallery-item { overflow: hidden; position: relative; }
.ab-gallery-item:first-child { grid-row: 1 / 3; }
.ab-gallery-item img {
    width: 100%; height: 100%; min-height: 240px;
    object-fit: cover; display: block;
    transition: transform 0.45s ease, filter 0.45s ease;
    filter: brightness(0.95) saturate(1.05);
}
.ab-gallery-item:hover img { transform: scale(1.04); filter: brightness(1) saturate(1.1); }
.ab-gallery-caption {
    position: absolute; bottom: 0; left: 0; right: 0;
    padding: 12px 16px;
    background: linear-gradient(transparent, rgba(47,28,38,0.82));
    font-size: 11px; font-weight: 600; color: rgba(255,255,255,0.75);
    letter-spacing: 1px; text-transform: uppercase;
    opacity: 0; transform: translateY(6px);
    transition: all 0.3s ease;
}
.ab-gallery-item:hover .ab-gallery-caption { opacity: 1; transform: translateY(0); }

/* ─── TEAM ─── */
.ab-team-grid {
    display: grid; grid-template-columns: repeat(3,1fr);
    gap: 0; border: 1.5px solid #f7c8d9; margin-top: 48px;
}
.ab-team-card {
    border-right: 1.5px solid #f7c8d9;
    display: flex; flex-direction: column;
}
.ab-team-card:last-child { border-right: none; }
.ab-team-img-wrap {
    position: relative; overflow: hidden; aspect-ratio: 3/4;
}
.ab-team-img-wrap img {
    width: 100%; height: 100%; object-fit: cover; object-position: top;
    transition: transform 0.45s ease;
}
.ab-team-card:hover .ab-team-img-wrap img { transform: scale(1.04); }
.ab-team-info { padding: 28px 28px 32px; }
.ab-team-role {
    font-size: 10px; font-weight: 700; letter-spacing: 3px;
    text-transform: uppercase; color: var(--accent-pink); margin-bottom: 6px;
}
.ab-team-name {
    font-size: 20px; font-weight: 900; color: #2f1c26;
    letter-spacing: -0.3px; text-transform: uppercase; margin-bottom: 12px;
}
.ab-team-bio { font-size: 13px; color: #888; line-height: 1.75; }
.ab-team-tag {
    display: inline-flex; align-items: center; gap: 6px;
    margin-top: 16px; padding: 6px 14px;
    background: #fff0f5; border-left: 2px solid var(--accent-pink);
    font-size: 11px; color: #888; font-weight: 600;
}

/* ─── CONTACT SECTION ─── */
.ab-contact-bg {
    border-top: 1px solid #f7c8d9;
    border-bottom: 1px solid #f7c8d9;
    background: #fff8fb;
}
.ab-contact-inner {
    max-width: 1280px; margin: 0 auto; padding: 80px 5%;
    display: grid; grid-template-columns: 1fr 1fr; gap: 72px; align-items: start;
}
.ab-contact-info {}
.ab-contact-rows { margin-top: 36px; display: flex; flex-direction: column; gap: 0; }
.ab-contact-row {
    display: flex; align-items: flex-start; gap: 20px;
    padding: 22px 0; border-bottom: 1px solid #f0dce4;
}
.ab-contact-row:first-child { border-top: 1px solid #f0dce4; }
.ab-contact-icon {
    flex-shrink: 0; width: 40px; height: 40px;
    background: #2f1c26; color: #f48fb1;
    display: flex; align-items: center; justify-content: center; font-size: 15px;
}
.ab-contact-label {
    font-size: 10px; font-weight: 700; letter-spacing: 2px;
    text-transform: uppercase; color: var(--accent-pink); margin-bottom: 4px;
}
.ab-contact-val { font-size: 14px; font-weight: 600; color: #2f1c26; line-height: 1.5; }
.ab-contact-val small { font-weight: 500; color: #888; font-size: 12px; display: block; }

.ab-contact-form {}
.ab-form-label {
    font-size: 10px; font-weight: 700; letter-spacing: 2px;
    text-transform: uppercase; color: #888; display: block; margin-bottom: 8px;
}
.ab-form-input, .ab-form-textarea {
    width: 100%; padding: 14px 16px;
    border: 1.5px solid #f0d0dc; background: #fff;
    font-size: 13px; font-family: 'Montserrat', sans-serif;
    color: #2f1c26; outline: none;
    transition: border-color 0.2s;
    margin-bottom: 16px; display: block;
}
.ab-form-input:focus, .ab-form-textarea:focus { border-color: var(--accent-pink); }
.ab-form-textarea { min-height: 120px; resize: vertical; }
.ab-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.ab-form-submit {
    width: 100%; padding: 16px;
    background: var(--accent-pink); color: #fff;
    font-size: 12px; font-weight: 800; letter-spacing: 2px;
    text-transform: uppercase; border: none; cursor: pointer;
    transition: background 0.22s, transform 0.22s;
}
.ab-form-submit:hover { background: var(--hover-pink); transform: translateY(-1px); }
.ab-form-note { font-size: 11px; color: #bbb; margin-top: 10px; text-align: center; }

/* ─── CTA BOTTOM ─── */
.ab-cta {
    background: linear-gradient(105deg, #2f1c26 0%, #5a2138 50%, #8b3057 100%);
    padding: 88px 5%; text-align: center; position: relative; overflow: hidden;
}
.ab-cta::before {
    content: '';
    position: absolute; inset: 0;
    background: radial-gradient(circle at 50% 130%, rgba(233,90,138,0.28) 0%, transparent 60%);
}
.ab-cta h2 {
    font-size: clamp(30px,4vw,48px); font-weight: 900; color: #fff;
    letter-spacing: -1px; text-transform: uppercase; margin-bottom: 14px;
    position: relative;
}
.ab-cta p { font-size: 15px; color: rgba(255,255,255,0.5); margin-bottom: 40px; position: relative; }
.ab-cta-btns { display: flex; justify-content: center; gap: 16px; flex-wrap: wrap; position: relative; }
.ab-cta-btns a {
    padding: 18px 52px; font-size: 12px; font-weight: 800;
    letter-spacing: 2px; text-transform: uppercase;
    text-decoration: none; transition: all 0.22s; display: inline-block;
}
.ab-btn-white { background: #fff; color: #2f1c26; }
.ab-btn-white:hover { background: #ffe8f0; }
.ab-btn-border { border: 1.5px solid rgba(255,255,255,0.2); color: rgba(255,255,255,0.65); }
.ab-btn-border:hover { border-color: rgba(255,255,255,0.5); color: #fff; }

/* ─── RESPONSIVE ─── */
@media (max-width: 1100px) {
    .ab-metrics-inner { grid-template-columns: repeat(2,1fr); }
    .ab-metric:nth-child(2) { border-right: none; }
    .ab-metric:nth-child(3) { border-top: 1px solid rgba(255,255,255,0.07); }
    .ab-values-grid { grid-template-columns: repeat(2,1fr); }
    .ab-value-card:nth-child(even) { border-right: none; }
    .ab-value-card:nth-child(n+3) { border-top: 1.5px solid #f7c8d9; }
    .ab-timeline { grid-template-columns: 1fr; }
    .ab-tl-item { border-right: none; border-bottom: 1px solid rgba(255,255,255,0.06); }
    .ab-story-grid { grid-template-columns: 1fr; }
    .ab-story-img img { min-height: 300px; }
    .ab-story-text { padding: 48px 36px; }
    .ab-gallery { grid-template-columns: 1fr 1fr; }
    .ab-gallery-item:first-child { grid-row: auto; }
    .ab-contact-inner { grid-template-columns: 1fr; gap: 48px; }
}
@media (max-width: 768px) {
    .ab-hero { min-height: 70vh; }
    .ab-hero h1 { font-size: 34px; }
    .ab-metrics-inner { grid-template-columns: repeat(2,1fr); }
    .ab-values-grid { grid-template-columns: 1fr; }
    .ab-value-card { border-right: none; border-bottom: 1.5px solid #f7c8d9; }
    .ab-team-grid { grid-template-columns: 1fr; }
    .ab-team-card { border-right: none; border-bottom: 1.5px solid #f7c8d9; }
    .ab-gallery { grid-template-columns: 1fr; }
    .ab-form-row { grid-template-columns: 1fr; }
}
</style>

<div class="ab-page">

<!-- ===== HERO ===== -->
<section class="ab-hero">
    <div class="ab-hero-inner">
        <div class="ab-hero-kicker"><span></span>Thương hiệu thời trang Việt</div>
        <h1>Chúng tôi là<br><em>QHTN</em><br>Fashion &amp; Studio</h1>
        <p class="ab-hero-desc">Từ một studio nhỏ với ước mơ đưa thời trang cao cấp đến gần hơn với mọi cô gái — QHTN đã trở thành điểm đến tin cậy cho những trang phục thiết kế được tuyển chọn thủ công.</p>
        <div class="ab-hero-btns">
            <a href="ao_dai.php" class="ab-btn-pink">Khám phá bộ sưu tập</a>
            <a href="#story" class="ab-btn-ghost">Đọc câu chuyện</a>
        </div>
    </div>
    <div class="ab-hero-scroll">
        <span>Scroll</span>
        <div class="ab-hero-scroll-line"></div>
    </div>
</section>

<!-- ===== METRICS ===== -->
<div class="ab-metrics">
    <div class="ab-metrics-inner">
        <div class="ab-metric">
            <div class="ab-metric-num">10<span>K+</span></div>
            <div class="ab-metric-label">Khách hài lòng</div>
            <div class="ab-metric-sub">Từ dạ hội, lễ đính hôn đến showbiz</div>
        </div>
        <div class="ab-metric">
            <div class="ab-metric-num">350<span>+</span></div>
            <div class="ab-metric-label">Mẫu thiết kế</div>
            <div class="ab-metric-sub">Đa dạng phom dáng & bảng màu</div>
        </div>
        <div class="ab-metric">
            <div class="ab-metric-num">5<span>+ năm</span></div>
            <div class="ab-metric-label">Kinh nghiệm</div>
            <div class="ab-metric-sub">Stylist dày dạn sự kiện cao cấp</div>
        </div>
        <div class="ab-metric">
            <div class="ab-metric-num">100<span>%</span></div>
            <div class="ab-metric-label">Chính hãng</div>
            <div class="ab-metric-sub">Kiểm định nguồn gốc, chuẩn boutique</div>
        </div>
    </div>
</div>

<!-- ===== STORY ===== -->
<div class="ab-story-strip" id="story">
    <div class="ab-story-grid">
        <div class="ab-story-img">
            <img src="assets/pictures/IMG_5006.webp" alt="Đội ngũ QHTN">
        </div>
        <div class="ab-story-text">
            <div class="ab-eyebrow">Our Story</div>
            <h2 class="ab-heading">Hành trình<br>tạo nên QHTN</h2>
            <blockquote class="ab-story-pull">
                "Sứ mệnh của chúng tôi là giúp bạn tỏa sáng đúng chất riêng — mỗi thiết kế, một câu chuyện."
            </blockquote>
            <p>Năm 2016, một nhóm stylist và nhà thiết kế trẻ lập nên QHTN để lấp khoảng trống giữa thời trang haute couture và nhu cầu thuê linh hoạt. Chúng tôi muốn mọi khách hàng có thể khoác lên mình các tác phẩm thiết kế mà không bị rào cản sở hữu.</p>
            <p>Mỗi bộ trang phục được lựa chọn, bảo quản và chỉnh sửa thủ công, kèm tư vấn styling cá nhân hóa. Quy trình bảo dưỡng chuẩn boutique, 100% chính hãng, giao nhanh nội thành và hỗ trợ thử đồ theo lịch hẹn.</p>
        </div>
    </div>
</div>

<!-- ===== CORE VALUES ===== -->
<div class="ab-section">
    <div class="ab-eyebrow">Core Values</div>
    <h2 class="ab-heading">Giá trị cốt lõi</h2>
    <p class="ab-subtext">Bốn trụ cột xây dựng nên mọi trải nghiệm tại QHTN — từ kho lưu trữ đến khoảnh khắc bạn bước ra sự kiện.</p>
    <div class="ab-values-grid">
        <div class="ab-value-card">
            <div class="ab-value-icon"><i class="fa-solid fa-gem"></i></div>
            <div class="ab-value-en">Premium Quality</div>
            <div class="ab-value-title">Chất lượng tuyệt đối</div>
            <p class="ab-value-desc">Chỉ nhận đồ thiết kế chính hãng, kiểm tra thủ công từng đường may, lưu kho chuẩn nhiệt-ẩm để giữ phom hoàn hảo suốt vòng đời sản phẩm.</p>
        </div>
        <div class="ab-value-card">
            <div class="ab-value-icon"><i class="fa-solid fa-layer-group"></i></div>
            <div class="ab-value-en">Diverse Collection</div>
            <div class="ab-value-title">Bộ sưu tập đa dạng</div>
            <p class="ab-value-desc">Trải rộng từ dạ hội, red-carpet, lễ cưới đến cocktail. Cập nhật liên tục theo mùa và khuynh hướng runway quốc tế.</p>
        </div>
        <div class="ab-value-card">
            <div class="ab-value-icon"><i class="fa-solid fa-user-tie"></i></div>
            <div class="ab-value-en">Personal Styling</div>
            <div class="ab-value-title">Styling cá nhân hóa</div>
            <p class="ab-value-desc">Tư vấn 1-1, gợi ý phụ kiện phù hợp, thử đồ theo lịch hẹn, điều chỉnh vừa vặn trước khi giao — đảm bảo bạn hoàn hảo mọi góc nhìn.</p>
        </div>
        <div class="ab-value-card">
            <div class="ab-value-icon"><i class="fa-solid fa-leaf"></i></div>
            <div class="ab-value-en">Care & Sustainability</div>
            <div class="ab-value-title">Bền vững &amp; trân trọng</div>
            <p class="ab-value-desc">Giặt hấp chuẩn boutique, tái sử dụng trang phục cao cấp để giảm lãng phí thời trang và kéo dài vòng đời từng thiết kế.</p>
        </div>
    </div>
</div>

<!-- ===== TIMELINE ===== -->
<div class="ab-timeline-bg">
    <div class="ab-timeline-inner">
        <div class="ab-eyebrow" style="color:#f48fb1;">
            <span style="background:#f48fb1"></span>Milestones
        </div>
        <h2 class="ab-heading" style="color:#fff;">Dấu mốc<br>hình thành &amp; phát triển</h2>
        <div class="ab-timeline">
            <div class="ab-tl-item">
                <div class="ab-tl-dot"></div>
                <div class="ab-tl-year">2016</div>
                <div class="ab-tl-title">Khởi lập QHTN Studio</div>
                <p class="ab-tl-desc">Nhóm stylist trẻ mở studio đầu tiên tại Hà Nội, tập trung dịch vụ thuê đầm dạ hội và trang phục sự kiện.</p>
            </div>
            <div class="ab-tl-item">
                <div class="ab-tl-dot"></div>
                <div class="ab-tl-year">2018</div>
                <div class="ab-tl-title">Mở rộng bộ sưu tập Couture</div>
                <p class="ab-tl-desc">Hợp tác các nhà mốt trong nước, bổ sung dòng thiết kế red-carpet và bridal capsule cao cấp.</p>
            </div>
            <div class="ab-tl-item">
                <div class="ab-tl-dot"></div>
                <div class="ab-tl-year">2020</div>
                <div class="ab-tl-title">Styling cá nhân hóa</div>
                <p class="ab-tl-desc">Ra mắt dịch vụ tư vấn 1-1, fitting theo lịch hẹn, giao nhận tận nơi trong ngày tại nội thành.</p>
            </div>
            <div class="ab-tl-item">
                <div class="ab-tl-dot"></div>
                <div class="ab-tl-year">2023</div>
                <div class="ab-tl-title">10,000+ khách hàng</div>
                <p class="ab-tl-desc">Đạt mốc 10k khách hài lòng, mở rộng kho lưu trữ và phòng thử tiêu chuẩn boutique chuyên nghiệp.</p>
            </div>
            <div class="ab-tl-item">
                <div class="ab-tl-dot"></div>
                <div class="ab-tl-year">2025<span> →</span></div>
                <div class="ab-tl-title">Nền tảng Omnichannel</div>
                <p class="ab-tl-desc">Đặt lịch, chọn mẫu, giữ size trực tuyến. Quy trình bảo dưỡng nâng cấp với thiết bị boutique thế hệ mới.</p>
            </div>
        </div>
    </div>
</div>

<!-- ===== GALLERY ===== -->
<div class="ab-section">
    <div class="ab-eyebrow">Gallery</div>
    <h2 class="ab-heading">Khoảnh khắc<br>tỏa sáng</h2>
    <div class="ab-gallery">
        <div class="ab-gallery-item">
            <img src="assets/pictures/about/4.jpg" alt="Lookbook váy dạ hội hồng ánh kim">
            <div class="ab-gallery-caption">Bridal Collection</div>
        </div>
        <div class="ab-gallery-item">
            <img src="assets/pictures/about/5.jpg" alt="Chi tiết corset lấp lánh">
            <div class="ab-gallery-caption">Corset Detail</div>
        </div>
        <div class="ab-gallery-item">
            <img src="assets/pictures/about/6.jpg" alt="Đầm couture dài phối cape">
            <div class="ab-gallery-caption">Couture Cape</div>
        </div>
        <div class="ab-gallery-item">
            <img src="assets/pictures/about/7.jpg" alt="Runway phong cách black-tie">
            <div class="ab-gallery-caption">Black-Tie Runway</div>
        </div>
        <div class="ab-gallery-item">
            <img src="assets/pictures/about/8.jpg" alt="Chi tiết eo thắt nơ satin">
            <div class="ab-gallery-caption">Satin Bow Detail</div>
        </div>
        <div class="ab-gallery-item">
            <img src="assets/pictures/about/9.jpg" alt="Đầm ren đính đá pastel">
            <div class="ab-gallery-caption">Lace & Crystal</div>
        </div>
    </div>
</div>

<!-- ===== TEAM ===== -->
<div class="ab-story-strip">
    <div class="ab-section-sm">
        <div class="ab-eyebrow">Our Team</div>
        <h2 class="ab-heading">Đội ngũ chuyên gia<br>phía sau QHTN</h2>
        <p class="ab-subtext">Những người đứng sau từng lần fitting, bảo dưỡng và tuyển chọn mẫu — để mỗi khách hàng có trải nghiệm hoàn hảo nhất.</p>
        <div class="ab-team-grid">
            <div class="ab-team-card">
                <div class="ab-team-img-wrap">
                    <img src="assets/pictures/about/1.jpg" alt="Lead Stylist QHTN">
                </div>
                <div class="ab-team-info">
                    <div class="ab-team-role">Lead Stylist</div>
                    <div class="ab-team-name">Lan Anh</div>
                    <p class="ab-team-bio">10 năm styling sự kiện, thấu hiểu phom dáng và bảng màu tôn da, luôn tối ưu look cho từng dáng người một cách chính xác và tinh tế nhất.</p>
                    <div class="ab-team-tag"><i class="fa-solid fa-star" style="color:var(--accent-pink);font-size:10px"></i> 10+ năm kinh nghiệm</div>
                </div>
            </div>
            <div class="ab-team-card">
                <div class="ab-team-img-wrap">
                    <img src="assets/pictures/about/1 copy.jpg" alt="Head of Curation QHTN">
                </div>
                <div class="ab-team-info">
                    <div class="ab-team-role">Head of Curation</div>
                    <div class="ab-team-name">Minh Châu</div>
                    <p class="ab-team-bio">Chọn lọc thiết kế, làm việc cùng nhà mốt để có phiên bản giới hạn, giữ chất lượng và tính độc bản của từng bộ sưu tập trong kho QHTN.</p>
                    <div class="ab-team-tag"><i class="fa-solid fa-gem" style="color:var(--accent-pink);font-size:10px"></i> Buyer chuyên nghiệp</div>
                </div>
            </div>
            <div class="ab-team-card">
                <div class="ab-team-img-wrap">
                    <img src="assets/pictures/about/3.jpg" alt="Tailoring Specialist QHTN">
                </div>
                <div class="ab-team-info">
                    <div class="ab-team-role">Tailoring Specialist</div>
                    <div class="ab-team-name">Thùy Dương</div>
                    <p class="ab-team-bio">Phụ trách fitting, chỉnh sửa dáng và bảo dưỡng chất liệu, đảm bảo trang phục sẵn sàng trước sự kiện trong tình trạng hoàn hảo nhất.</p>
                    <div class="ab-team-tag"><i class="fa-solid fa-scissors" style="color:var(--accent-pink);font-size:10px"></i> Chuyên gia chỉnh sửa</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== CONTACT ===== -->
<div class="ab-contact-bg" id="lien-he">
    <div class="ab-contact-inner">
        <div class="ab-contact-info">
            <div class="ab-eyebrow">Liên hệ</div>
            <h2 class="ab-heading">Gặp gỡ<br>&amp; tư vấn</h2>
            <p class="ab-subtext" style="margin-bottom:0">Đến trực tiếp showroom, gọi điện hoặc để lại tin nhắn — đội ngũ QHTN phản hồi trong vòng 24 giờ làm việc.</p>
            <div class="ab-contact-rows">
                <div class="ab-contact-row">
                    <div class="ab-contact-icon"><i class="fa-solid fa-location-dot"></i></div>
                    <div>
                        <div class="ab-contact-label">Showroom</div>
                        <div class="ab-contact-val">123 Đường Thời Trang, Quận 1<br><small>TP. Hồ Chí Minh — Mở cửa 8h–20h hàng ngày</small></div>
                    </div>
                </div>
                <div class="ab-contact-row">
                    <div class="ab-contact-icon"><i class="fa-solid fa-phone"></i></div>
                    <div>
                        <div class="ab-contact-label">Hotline</div>
                        <div class="ab-contact-val">0xxx-xxx-xxx<br><small>Thứ 2 → Chủ nhật · 8:00 – 21:00</small></div>
                    </div>
                </div>
                <div class="ab-contact-row">
                    <div class="ab-contact-icon"><i class="fa-solid fa-envelope"></i></div>
                    <div>
                        <div class="ab-contact-label">Email</div>
                        <div class="ab-contact-val">qhtn.fashion@gmail.com<br><small>Phản hồi trong vòng 24 giờ làm việc</small></div>
                    </div>
                </div>
                <div class="ab-contact-row">
                    <div class="ab-contact-icon"><i class="fa-brands fa-facebook"></i></div>
                    <div>
                        <div class="ab-contact-label">Mạng xã hội</div>
                        <div class="ab-contact-val">QHTN Fashion Studio<br><small>Facebook · Instagram · TikTok</small></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="ab-contact-form">
            <div class="ab-eyebrow">Gửi tin nhắn</div>
            <h3 class="ab-heading" style="font-size:24px;margin-bottom:28px;">Để lại<br>thông tin</h3>
            <form method="POST" action="ve_chung_toi.php#lien-he" onsubmit="return handleContactForm(event)">
                <div class="ab-form-row">
                    <div>
                        <label class="ab-form-label">Họ và Tên *</label>
                        <input class="ab-form-input" type="text" name="name" placeholder="Nguyễn Thị A" required>
                    </div>
                    <div>
                        <label class="ab-form-label">Số điện thoại</label>
                        <input class="ab-form-input" type="tel" name="phone" placeholder="0912 345 678">
                    </div>
                </div>
                <label class="ab-form-label">Email *</label>
                <input class="ab-form-input" type="email" name="email" placeholder="email@example.com" required>
                <label class="ab-form-label">Nội dung / Yêu cầu *</label>
                <textarea class="ab-form-textarea" name="message" placeholder="Tôi muốn tư vấn về trang phục dự tiệc, thuê đầm dạ hội..." required></textarea>
                <button type="submit" class="ab-form-submit">
                    <i class="fa-solid fa-paper-plane"></i>&ensp;Gửi yêu cầu tư vấn
                </button>
                <p class="ab-form-note">Thông tin của bạn được bảo mật tuyệt đối. Chúng tôi không spam.</p>
            </form>
        </div>
    </div>
</div>

<!-- ===== CTA BOTTOM ===== -->
<section class="ab-cta">
    <h2>Sẵn sàng tỏa sáng<br>trong thiết kế chuẩn boutique?</h2>
    <p>Đặt lịch thử đồ hoặc chọn ngay look yêu thích cho dịp đặc biệt của bạn.</p>
    <div class="ab-cta-btns">
        <a href="ao_dai.php" class="ab-btn-white">
            <i class="fa-solid fa-shirt"></i>&ensp;Xem bộ sưu tập
        </a>
        <a href="membership.php" class="ab-btn-border">
            <i class="fa-solid fa-crown"></i>&ensp;Tham gia thành viên
        </a>
    </div>
</section>

</div><!-- .ab-page -->

<script>
function handleContactForm(e) {
    e.preventDefault();
    const btn = e.target.querySelector('.ab-form-submit');
    btn.textContent = 'Đã gửi! Chúng tôi sẽ liên hệ sớm.';
    btn.style.background = '#2f1c26';
    btn.disabled = true;
    setTimeout(() => {
        btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i>&ensp;Gửi yêu cầu tư vấn';
        btn.style.background = '';
        btn.disabled = false;
        e.target.reset();
    }, 4000);
    return false;
}
</script>

<?php include 'footer.php'; ?>