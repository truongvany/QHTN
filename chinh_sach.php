<?php
require_once 'config.php';
$pageTitle = "Chính Sách & Quy Định | QHTN";
include 'header.php';
?>

<style>
/* ============================================================
   POLICY PAGE — QHTN CORPORATE EDITION
   No border-radius, enterprise feel, structured layout
============================================================ */

.pol-page { background: #fff; font-family: 'Montserrat', sans-serif; color: var(--text-color); }

/* ── HERO ── */
.pol-hero {
    background:
        linear-gradient(105deg, rgba(47,28,38,0.91) 0%, rgba(90,33,56,0.85) 55%, rgba(139,48,87,0.80) 100%),
        url('https://as2.ftcdn.net/v2/jpg/06/64/47/11/1000_F_664471105_wSK5c9cjh4VPDY6ftbZQoAv9xzm0pqlE.jpg') center 30% / cover no-repeat;
    padding: 72px 5%;
    position: relative;
    overflow: hidden;
}
.pol-hero::before {
    content: '';
    position: absolute; inset: 0;
    background: repeating-linear-gradient(
        45deg, transparent, transparent 60px,
        rgba(255,255,255,0.013) 60px, rgba(255,255,255,0.013) 61px
    );
    pointer-events: none;
}
.pol-hero-inner {
    max-width: 1280px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 48px;
    position: relative;
    z-index: 1;
}
.pol-hero-text { max-width: 600px; }
.pol-hero-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    letter-spacing: 4px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    color: #f48fb1;
    margin-bottom: 18px;
}
.pol-hero-eyebrow span { display: inline-block; width: 28px; height: 1px; background: #f48fb1; }
.pol-hero h1 {
    font-size: 46px;
    font-weight: 900;
    color: #fff;
    line-height: 1.08;
    letter-spacing: -1px;
    text-transform: uppercase;
    margin-bottom: 16px;
}
.pol-hero h1 em { font-style: normal; color: #f48fb1; }
.pol-hero-desc {
    font-size: 14px;
    color: rgba(255,255,255,0.62);
    line-height: 1.85;
    max-width: 460px;
}
.pol-hero-stats {
    display: flex;
    gap: 0;
    flex-shrink: 0;
    border: 1px solid rgba(255,255,255,0.12);
}
.pol-stat {
    padding: 28px 36px;
    text-align: center;
    border-right: 1px solid rgba(255,255,255,0.12);
}
.pol-stat:last-child { border-right: none; }
.pol-stat-num {
    font-size: 34px;
    font-weight: 900;
    color: #fff;
    line-height: 1;
    margin-bottom: 6px;
}
.pol-stat-num span { color: #f48fb1; }
.pol-stat-label {
    font-size: 10px;
    color: rgba(255,255,255,0.45);
    letter-spacing: 2px;
    text-transform: uppercase;
    font-weight: 600;
}

/* ── QUICK NAV STRIP ── */
.pol-nav-strip {
    background: #2f1c26;
    border-bottom: 2px solid var(--accent-pink);
    padding: 0 5%;
    overflow-x: auto;
    white-space: nowrap;
}
.pol-nav-inner {
    max-width: 1280px;
    margin: 0 auto;
    display: flex;
    gap: 0;
}
.pol-nav-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 16px 22px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: rgba(255,255,255,0.5);
    text-decoration: none;
    border-right: 1px solid rgba(255,255,255,0.07);
    transition: all 0.22s;
    flex-shrink: 0;
}
.pol-nav-link:hover {
    color: #fff;
    background: rgba(255,255,255,0.06);
}
.pol-nav-link i { font-size: 12px; color: var(--accent-pink); }

/* ── LAYOUT MAIN ── */
.pol-main {
    max-width: 1280px;
    margin: 0 auto;
    padding: 72px 5%;
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 56px;
    align-items: start;
}
/* Sidebar TOC */
.pol-toc {
    position: sticky;
    top: 90px;
}
.pol-toc-title {
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--accent-pink);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.pol-toc-title::before {
    content: '';
    display: inline-block;
    width: 20px; height: 2px;
    background: var(--accent-pink);
}
.pol-toc-list { list-style: none; margin: 0; padding: 0; border-left: 2px solid #f7c8d9; }
.pol-toc-list li a {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 11px 16px;
    font-size: 12.5px;
    font-weight: 600;
    color: #888;
    text-decoration: none;
    border-left: 2px solid transparent;
    margin-left: -2px;
    transition: all 0.2s;
    line-height: 1.4;
}
.pol-toc-list li a:hover {
    color: var(--accent-pink);
    border-left-color: var(--accent-pink);
    background: #fff5f8;
    padding-left: 20px;
}
.pol-toc-list li a i { font-size: 11px; color: var(--accent-pink); margin-top: 1px; flex-shrink: 0; }
.pol-toc-update {
    margin-top: 24px;
    padding: 16px;
    background: #fff8fb;
    border-left: 3px solid var(--accent-pink);
    font-size: 12px;
    color: #888;
    line-height: 1.6;
}
.pol-toc-update strong { color: #2f1c26; display: block; margin-bottom: 4px; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; }

/* ── POLICY SECTIONS ── */
.pol-sections { display: flex; flex-direction: column; gap: 0; }

.pol-section {
    padding: 48px 0;
    border-bottom: 1px solid #f5e0e8;
}
.pol-section:last-child { border-bottom: none; padding-bottom: 0; }

.pol-section-header {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 28px;
}
.pol-section-num {
    flex-shrink: 0;
    width: 48px; height: 48px;
    background: var(--accent-pink);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 900;
    letter-spacing: -0.5px;
}
.pol-section-title-wrap {}
.pol-section-sub {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--accent-pink);
    margin-bottom: 4px;
}
.pol-section-title {
    font-size: 22px;
    font-weight: 900;
    color: #2f1c26;
    text-transform: uppercase;
    letter-spacing: -0.3px;
}

.pol-section-body { padding-left: 68px; }
.pol-section-lead {
    font-size: 14px;
    color: #666;
    line-height: 1.85;
    margin-bottom: 24px;
    border-left: 3px solid #f7c8d9;
    padding-left: 16px;
}

/* Rules Grid */
.pol-rules {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0;
    border: 1.5px solid #f7c8d9;
    margin-bottom: 20px;
}
.pol-rule-item {
    padding: 20px 24px;
    border-right: 1.5px solid #f7c8d9;
    border-bottom: 1.5px solid #f7c8d9;
    display: flex;
    gap: 14px;
    align-items: flex-start;
}
.pol-rule-item:nth-child(even) { border-right: none; }
.pol-rules.cols-1 { grid-template-columns: 1fr; }
.pol-rules.cols-1 .pol-rule-item { border-right: none; }

/* Remove bottom border from last row */
.pol-rules.cols-2 .pol-rule-item:nth-last-child(-n+2) { border-bottom: none; }
.pol-rules.cols-1 .pol-rule-item:last-child { border-bottom: none; }

.pol-rule-icon {
    flex-shrink: 0;
    width: 32px; height: 32px;
    background: #fff5f8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    color: var(--accent-pink);
    margin-top: 1px;
}
.pol-rule-text {}
.pol-rule-title {
    font-size: 13px;
    font-weight: 700;
    color: #2f1c26;
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.pol-rule-desc {
    font-size: 12.5px;
    color: #777;
    line-height: 1.7;
}
.pol-rule-desc strong { color: var(--accent-pink); font-weight: 700; }

/* ── HIGHLIGHT BOX ── */
.pol-highlight {
    background: linear-gradient(135deg, #fff5f8 0%, #fff0f5 100%);
    border-left: 4px solid var(--accent-pink);
    padding: 18px 22px;
    margin-top: 16px;
    display: flex;
    gap: 14px;
    align-items: flex-start;
}
.pol-highlight i { color: var(--accent-pink); font-size: 16px; margin-top: 2px; flex-shrink: 0; }
.pol-highlight-text {
    font-size: 13px;
    color: #555;
    line-height: 1.7;
}
.pol-highlight-text strong { color: #2f1c26; }

/* ── COMMITMENT STRIP ── */
.pol-commit-bg {
    background: #2f1c26;
    padding: 0;
    border-top: 3px solid var(--accent-pink);
}
.pol-commit-inner {
    max-width: 1280px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0;
}
.pol-commit-item {
    padding: 40px 32px;
    border-right: 1px solid rgba(255,255,255,0.07);
    text-align: center;
}
.pol-commit-item:last-child { border-right: none; }
.pol-commit-icon {
    font-size: 26px;
    color: #f48fb1;
    margin-bottom: 14px;
}
.pol-commit-title {
    font-size: 13px;
    font-weight: 800;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}
.pol-commit-desc {
    font-size: 12px;
    color: rgba(255,255,255,0.45);
    line-height: 1.7;
}

/* ── CTA BOTTOM ── */
.pol-cta {
    background: linear-gradient(135deg, #fff0f5 0%, #fff5f8 100%);
    border-top: 1px solid #f7c8d9;
    padding: 64px 5%;
    text-align: center;
}
.pol-cta h2 {
    font-size: 32px;
    font-weight: 900;
    color: #2f1c26;
    text-transform: uppercase;
    letter-spacing: -0.5px;
    margin-bottom: 12px;
}
.pol-cta p {
    font-size: 14px;
    color: #888;
    margin-bottom: 32px;
    max-width: 480px;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.7;
}
.pol-cta-links {
    display: flex;
    justify-content: center;
    gap: 14px;
    flex-wrap: wrap;
}
.pol-cta-links a {
    padding: 14px 40px;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 2px;
    text-transform: uppercase;
    text-decoration: none;
    transition: all 0.22s;
    display: inline-block;
}
.pol-btn-pink { background: var(--accent-pink); color: #fff; }
.pol-btn-pink:hover { background: var(--hover-pink); transform: translateY(-1px); }
.pol-btn-outline { background: transparent; border: 1.5px solid #f0d0dc; color: #888; }
.pol-btn-outline:hover { border-color: var(--accent-pink); color: var(--accent-pink); }

/* ── RESPONSIVE ── */
@media (max-width: 1100px) {
    .pol-main { grid-template-columns: 1fr; gap: 0; }
    .pol-toc { display: none; }
    .pol-section-body { padding-left: 0; }
    .pol-commit-inner { grid-template-columns: repeat(2, 1fr); }
    .pol-commit-item { border-bottom: 1px solid rgba(255,255,255,0.07); }
    .pol-commit-item:nth-child(even) { border-right: none; }
    .pol-hero-stats { display: none; }
}
@media (max-width: 768px) {
    .pol-hero h1 { font-size: 30px; }
    .pol-rules { grid-template-columns: 1fr; }
    .pol-rule-item { border-right: none; }
    .pol-rules.cols-2 .pol-rule-item:nth-last-child(-n+2) { border-bottom: 1.5px solid #f7c8d9; }
    .pol-rules.cols-2 .pol-rule-item:last-child { border-bottom: none; }
    .pol-commit-inner { grid-template-columns: 1fr; }
    .pol-commit-item { border-right: none; }
}
</style>

<div class="pol-page">

<!-- ===== HERO ===== -->
<section class="pol-hero">
    <div class="pol-hero-inner">
        <div class="pol-hero-text">
            <div class="pol-hero-eyebrow">
                <span></span>Quy định & cam kết doanh nghiệp
            </div>
            <h1>Chính Sách<br><em>QHTN</em></h1>
            <p class="pol-hero-desc">Toàn bộ quy định, điều khoản và cam kết của chúng tôi — được xây dựng minh bạch, rõ ràng để bảo vệ quyền lợi tối đa cho khách hàng.</p>
        </div>
        <div class="pol-hero-stats">
            <div class="pol-stat">
                <div class="pol-stat-num">6<span>+</span></div>
                <div class="pol-stat-label">Nhóm chính sách</div>
            </div>
            <div class="pol-stat">
                <div class="pol-stat-num">100<span>%</span></div>
                <div class="pol-stat-label">Bảo mật dữ liệu</div>
            </div>
            <div class="pol-stat">
                <div class="pol-stat-num">24<span>/7</span></div>
                <div class="pol-stat-label">Hỗ trợ khiếu nại</div>
            </div>
        </div>
    </div>
</section>

<!-- ===== QUICK NAV ===== -->
<nav class="pol-nav-strip">
    <div class="pol-nav-inner">
        <a href="#thue-do" class="pol-nav-link"><i class="fa-solid fa-shirt"></i> Thuê Đồ</a>
        <a href="#doi-tra" class="pol-nav-link"><i class="fa-solid fa-rotate"></i> Đổi / Trả</a>
        <a href="#thanh-toan" class="pol-nav-link"><i class="fa-solid fa-wallet"></i> Thanh Toán</a>
        <a href="#giao-van" class="pol-nav-link"><i class="fa-solid fa-truck-fast"></i> Giao Vận</a>
        <a href="#dong-kiem" class="pol-nav-link"><i class="fa-solid fa-magnifying-glass"></i> Đồng Kiểm</a>
        <a href="#bao-mat" class="pol-nav-link"><i class="fa-solid fa-user-shield"></i> Bảo Mật</a>
    </div>
</nav>

<!-- ===== MAIN CONTENT ===== -->
<div class="pol-main">

    <!-- Sidebar TOC -->
    <aside class="pol-toc">
        <div class="pol-toc-title">Mục lục</div>
        <ul class="pol-toc-list">
            <li><a href="#thue-do"><i class="fa-solid fa-shirt"></i> Chính sách Thuê Đồ</a></li>
            <li><a href="#doi-tra"><i class="fa-solid fa-rotate"></i> Chính sách Đổi / Trả</a></li>
            <li><a href="#thanh-toan"><i class="fa-solid fa-wallet"></i> Phương thức Thanh toán</a></li>
            <li><a href="#giao-van"><i class="fa-solid fa-truck-fast"></i> Chính sách Giao vận</a></li>
            <li><a href="#dong-kiem"><i class="fa-solid fa-magnifying-glass"></i> Chính sách Đồng kiểm</a></li>
            <li><a href="#bao-mat"><i class="fa-solid fa-user-shield"></i> Bảo mật thông tin</a></li>
        </ul>
        <div class="pol-toc-update">
            <strong>Cập nhật lần cuối</strong>
            Tháng 3 năm 2026 · Phiên bản 2.1<br>Có hiệu lực từ ngày 01/01/2026
        </div>
    </aside>

    <!-- Policy Sections -->
    <div class="pol-sections">

        <!-- 1. THUÊ ĐỒ -->
        <div class="pol-section" id="thue-do">
            <div class="pol-section-header">
                <div class="pol-section-num">01</div>
                <div class="pol-section-title-wrap">
                    <div class="pol-section-sub">Rental Policy</div>
                    <div class="pol-section-title">Chính Sách Thuê Đồ</div>
                </div>
            </div>
            <div class="pol-section-body">
                <p class="pol-section-lead">
                    Để đảm bảo quyền lợi cho cả hai bên và dịch vụ thuê trang phục diễn ra thuận lợi, khách hàng vui lòng đọc kỹ và tuân thủ các quy định sau đây.
                </p>
                <div class="pol-rules cols-2">
                    <div class="pol-rule-item">
                        <div class="pol-rule-icon"><i class="fa-solid fa-clock"></i></div>
                        <div class="pol-rule-text">
                            <div class="pol-rule-title">Thời gian thuê</div>
                            <div class="pol-rule-desc">Thời gian thuê được tính từ lúc khách nhận đồ đến khi trả lại. Mỗi ngày trễ hạn tính phí <strong>10% giá trị sản phẩm/ngày</strong>.</div>
                        </div>
                    </div>
                    <div class="pol-rule-item">
                        <div class="pol-rule-icon"><i class="fa-solid fa-lock"></i></div>
                        <div class="pol-rule-text">
                            <div class="pol-rule-title">Đặt cọc bắt buộc</div>
                            <div class="pol-rule-desc">Khách hàng phải đặt cọc (tiền mặt hoặc CCCD) khi nhận đồ. Hoàn trả <strong>100%</strong> khi trả đồ còn nguyên vẹn.</div>
                        </div>
                    </div>
                    <div class="pol-rule-item">
                        <div class="pol-rule-icon"><i class="fa-solid fa-scissors"></i></div>
                        <div class="pol-rule-text">
                            <div class="pol-rule-title">Không cắt chỉnh</div>
                            <div class="pol-rule-desc">Tuyệt đối không tự ý cắt, chỉnh, nhuộm hoặc sửa trang phục. Vi phạm sẽ chịu toàn bộ chi phí phục hồi.</div>
                        </div>
                    </div>
                    <div class="pol-rule-item">
                        <div class="pol-rule-icon"><i class="fa-solid fa-hand-holding-heart"></i></div>
                        <div class="pol-rule-text">
                            <div class="pol-rule-title">Bảo quản đúng cách</div>
                            <div class="pol-rule-desc">Không giặt máy, không phơi nắng trực tiếp. Trang phục rách, bẩn nặng sẽ bị trừ đặt cọc theo mức thiệt hại thực tế.</div>
                        </div>
                    </div>
                    <div class="pol-rule-item">
                        <div class="pol-rule-icon"><i class="fa-solid fa-calendar-check"></i></div>
                        <div class="pol-rule-text">
                            <div class="pol-rule-title">Đặt lịch trước</div>
                            <div class="pol-rule-desc">Để đảm bảo sản phẩm sẵn sàng, vui lòng đặt trước ít nhất <strong>24h</strong> cho đơn thuê thông thường.</div>
                        </div>
                    </div>
                    <div class="pol-rule-item">
                        <div class="pol-rule-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                        <div class="pol-rule-text">
                            <div class="pol-rule-title">Trách nhiệm mất mát</div>
                            <div class="pol-rule-desc">Trường hợp mất trang phục, khách hàng chịu trách nhiệm bồi thường <strong>100% giá trị</strong> sản phẩm theo giá niêm yết.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. ĐỔI / TRẢ -->
        <div class="pol-section" id="doi-tra">
            <div class="pol-section-header">
                <div class="pol-section-num">02</div>
                <div class="pol-section-title-wrap">
                    <div class="pol-section-sub">Return & Exchange Policy</div>
                    <div class="pol-section-title">Chính Sách Đổi / Trả</div>
                </div>
            </div>
            <div class="pol-section-body">
                <p class="pol-section-lead">
                    QHTN hỗ trợ đổi trả linh hoạt nhằm đảm bảo khách hàng luôn có được trang phục vừa ý nhất cho mọi dịp quan trọng.
                </p>
                <div class="pol-rules cols-2">
                    <div class="pol-rule-item">
                        <div class="pol-rule-icon"><i class="fa-solid fa-check-double"></i></div>
                        <div class="pol-rule-text">
                            <div class="pol-rule-title">Đổi size / mẫu</div>
                            <div class="pol-rule-desc">Miễn phí trong vòng <strong>24h</strong> sau khi nhận hàng, điều kiện sản phẩm chưa qua sử dụng và còn nguyên nhãn.</div>
                        </div>
                    </div>
                    <div class="pol-rule-item">
                        <div class="pol-rule-icon"><i class="fa-solid fa-bug"></i></div>
                        <div class="pol-rule-text">
                            <div class="pol-rule-title">Lỗi nhà cung cấp</div>
                            <div class="pol-rule-desc">Sản phẩm rách, bẩn, hỏng khóa do lỗi cửa hàng được đổi mới <strong>ngay lập tức</strong>, không mất thêm phí vận chuyển.</div>
                        </div>
                    </div>
                    <div class="pol-rule-item">
                        <div class="pol-rule-icon"><i class="fa-solid fa-ban"></i></div>
                        <div class="pol-rule-text">
                            <div class="pol-rule-title">Không hỗ trợ trả</div>
                            <div class="pol-rule-desc">Không chấp nhận trả hàng với lý do "không thích", đã qua sử dụng, hoặc quá thời hạn 24h kể từ khi nhận.</div>
                        </div>
                    </div>
                    <div class="pol-rule-item">
                        <div class="pol-rule-icon"><i class="fa-solid fa-money-bill-transfer"></i></div>
                        <div class="pol-rule-text">
                            <div class="pol-rule-title">Hoàn tiền</div>
                            <div class="pol-rule-desc">Trường hợp hoàn tiền hợp lệ: xử lý trong <strong>3–5 ngày làm việc</strong> qua phương thức thanh toán ban đầu.</div>
                        </div>
                    </div>
                </div>
                <div class="pol-highlight">
                    <i class="fa-solid fa-circle-info"></i>
                    <div class="pol-highlight-text">
                        <strong>Lưu ý quan trọng:</strong> Vui lòng quay video clip khi mở hàng để làm bằng chứng nếu sản phẩm có lỗi từ cửa hàng. Khiếu nại không có bằng chứng video có thể không được giải quyết.
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. THANH TOÁN -->
        <div class="pol-section" id="thanh-toan">
            <div class="pol-section-header">
                <div class="pol-section-num">03</div>
                <div class="pol-section-title-wrap">
                    <div class="pol-section-sub">Payment Policy</div>
                    <div class="pol-section-title">Phương Thức Thanh Toán</div>
                </div>
            </div>
            <div class="pol-section-body">
                <p class="pol-section-lead">
                    Chúng tôi hỗ trợ đa dạng phương thức thanh toán, linh hoạt và tiện lợi cho mọi khách hàng — từ thanh toán trực tiếp đến chuyển khoản và ví điện tử.
                </p>
                <div class="pol-rules cols-2">
                    <div class="pol-rule-item">
                        <div class="pol-rule-icon"><i class="fa-solid fa-money-bills"></i></div>
                        <div class="pol-rule-text">
                            <div class="pol-rule-title">Tiền mặt (COD)</div>
                            <div class="pol-rule-desc">Thanh toán trực tiếp tại cửa hàng hoặc khi nhận hàng. Áp dụng cho cả đặt cọc và thanh toán toàn bộ.</div>
                        </div>
                    </div>
                    <div class="pol-rule-item">
                        <div class="pol-rule-icon"><i class="fa-solid fa-building-columns"></i></div>
                        <div class="pol-rule-text">
                            <div class="pol-rule-title">Chuyển khoản ngân hàng</div>
                            <div class="pol-rule-desc">Hỗ trợ tất cả ngân hàng nội địa: Vietcombank, Techcombank, VietinBank, BIDV, MB Bank,…</div>
                        </div>
                    </div>
                    <div class="pol-rule-item">
                        <div class="pol-rule-icon"><i class="fa-solid fa-mobile-screen"></i></div>
                        <div class="pol-rule-text">
                            <div class="pol-rule-title">Ví điện tử</div>
                            <div class="pol-rule-desc">Chấp nhận <strong>MoMo</strong>, <strong>ZaloPay</strong>, <strong>VNPay</strong>. Quét mã QR trực tiếp tại thanh toán online.</div>
                        </div>
                    </div>
                    <div class="pol-rule-item">
                        <div class="pol-rule-icon"><i class="fa-solid fa-pen-to-square"></i></div>
                        <div class="pol-rule-text">
                            <div class="pol-rule-title">Nội dung chuyển khoản</div>
                            <div class="pol-rule-desc">Ghi rõ: <strong>Tên Khách Hàng + Số điện thoại + Mã đơn hàng</strong> để xác nhận nhanh chóng.</div>
                        </div>
                    </div>
                </div>
                <div class="pol-highlight">
                    <i class="fa-solid fa-qrcode"></i>
                    <div class="pol-highlight-text">
                        Trang thanh toán của chúng tôi hiển thị mã QR trực tiếp với đầy đủ thông tin. Sau khi chuyển khoản, đơn hàng được xác nhận tự động trong vòng <strong>5–15 phút</strong>.
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. GIAO VẬN -->
        <div class="pol-section" id="giao-van">
            <div class="pol-section-header">
                <div class="pol-section-num">04</div>
                <div class="pol-section-title-wrap">
                    <div class="pol-section-sub">Shipping Policy</div>
                    <div class="pol-section-title">Chính Sách Giao Vận</div>
                </div>
            </div>
            <div class="pol-section-body">
                <p class="pol-section-lead">
                    QHTN hợp tác với các đơn vị vận chuyển uy tín, đảm bảo trang phục đến tay khách hàng an toàn, đúng hẹn và trong tình trạng tốt nhất.
                </p>
                <div class="pol-rules cols-2">
                    <div class="pol-rule-item">
                        <div class="pol-rule-icon"><i class="fa-solid fa-bolt"></i></div>
                        <div class="pol-rule-text">
                            <div class="pol-rule-title">Nội thành — Hỏa tốc</div>
                            <div class="pol-rule-desc">Giao trong <strong>2–4 tiếng</strong>. Miễn phí ship cho đơn hàng trên <strong>500.000đ</strong>. Đặt trước 17h giao trong ngày.</div>
                        </div>
                    </div>
                    <div class="pol-rule-item">
                        <div class="pol-rule-icon"><i class="fa-solid fa-road"></i></div>
                        <div class="pol-rule-text">
                            <div class="pol-rule-title">Ngoại thành / Tỉnh</div>
                            <div class="pol-rule-desc">Giao từ <strong>2–4 ngày làm việc</strong> qua các đối tác: GHN, GHTK, VNPost. Theo dõi đơn hàng realtime.</div>
                        </div>
                    </div>
                    <div class="pol-rule-item">
                        <div class="pol-rule-icon"><i class="fa-solid fa-arrow-right-arrow-left"></i></div>
                        <div class="pol-rule-text">
                            <div class="pol-rule-title">Freeship thành viên</div>
                            <div class="pol-rule-desc">Thành viên Vàng & Kim Cương được miễn phí ship <strong>2 chiều</strong> (giao đồ và nhận lại) cho tất cả đơn hàng.</div>
                        </div>
                    </div>
                    <div class="pol-rule-item">
                        <div class="pol-rule-icon"><i class="fa-solid fa-receipt"></i></div>
                        <div class="pol-rule-text">
                            <div class="pol-rule-title">Phí ship đổi trả</div>
                            <div class="pol-rule-desc">Khách chịu phí ship <strong>2 chiều</strong> nếu đổi/trả không do lỗi của cửa hàng. Lỗi cửa hàng: QHTN chịu 100% phí.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. ĐỒNG KIỂM -->
        <div class="pol-section" id="dong-kiem">
            <div class="pol-section-header">
                <div class="pol-section-num">05</div>
                <div class="pol-section-title-wrap">
                    <div class="pol-section-sub">Inspection Policy</div>
                    <div class="pol-section-title">Chính Sách Đồng Kiểm</div>
                </div>
            </div>
            <div class="pol-section-body">
                <p class="pol-section-lead">
                    Quyền kiểm tra hàng trước khi thanh toán là quyền lợi chính đáng của khách hàng. Chúng tôi khuyến khích và hỗ trợ tối đa quy trình đồng kiểm này.
                </p>
                <div class="pol-rules cols-1">
                    <div class="pol-rule-item">
                        <div class="pol-rule-icon"><i class="fa-solid fa-box-open"></i></div>
                        <div class="pol-rule-text">
                            <div class="pol-rule-title">Mở kiện hàng trước khi thanh toán</div>
                            <div class="pol-rule-desc">Khách hàng <strong>ĐƯỢC PHÉP</strong> mở gói và kiểm tra số lượng, mẫu mã, tình trạng đóng gói trước khi thanh toán cho shipper. Đây là quyền lợi được chúng tôi khuyến khích.</div>
                        </div>
                    </div>
                    <div class="pol-rule-item">
                        <div class="pol-rule-icon"><i class="fa-solid fa-video"></i></div>
                        <div class="pol-rule-text">
                            <div class="pol-rule-title">Quay video khi mở hàng</div>
                            <div class="pol-rule-desc">Vui lòng quay video clip liên tục từ khi bắt đầu mở gói đến khi kiểm tra xong. Video là bằng chứng hợp lệ duy nhất khi khiếu nại tình trạng hàng hóa.</div>
                        </div>
                    </div>
                    <div class="pol-rule-item">
                        <div class="pol-rule-icon"><i class="fa-solid fa-eye"></i></div>
                        <div class="pol-rule-text">
                            <div class="pol-rule-title">Các điểm cần kiểm tra</div>
                            <div class="pol-rule-desc">Kiểm tra kỹ: <strong>tình trạng vải</strong> (không rách, không bẩn), <strong>đường chỉ</strong> (không bung), <strong>khóa kéo / cúc</strong> (hoạt động tốt), <strong>phụ kiện kèm theo</strong> (dây đai, nơ…).</div>
                        </div>
                    </div>
                    <div class="pol-rule-item">
                        <div class="pol-rule-icon"><i class="fa-solid fa-signature"></i></div>
                        <div class="pol-rule-text">
                            <div class="pol-rule-title">Xác nhận khi nhận hàng</div>
                            <div class="pol-rule-desc">Việc ký nhận hoặc xác nhận đơn hàng đồng nghĩa với việc khách hàng đã kiểm tra và chấp thuận tình trạng sản phẩm tại thời điểm nhận.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. BẢO MẬT -->
        <div class="pol-section" id="bao-mat">
            <div class="pol-section-header">
                <div class="pol-section-num">06</div>
                <div class="pol-section-title-wrap">
                    <div class="pol-section-sub">Privacy & Security Policy</div>
                    <div class="pol-section-title">Bảo Mật Thông Tin</div>
                </div>
            </div>
            <div class="pol-section-body">
                <p class="pol-section-lead">
                    QHTN cam kết bảo vệ tuyệt đối thông tin cá nhân của khách hàng theo quy định của pháp luật Việt Nam về bảo vệ dữ liệu cá nhân.
                </p>
                <div class="pol-rules cols-2">
                    <div class="pol-rule-item">
                        <div class="pol-rule-icon"><i class="fa-solid fa-database"></i></div>
                        <div class="pol-rule-text">
                            <div class="pol-rule-title">Dữ liệu được thu thập</div>
                            <div class="pol-rule-desc">Họ tên, SĐT, địa chỉ giao hàng, CCCD (nếu đặt cọc) — chỉ dùng cho mục đích thuê đồ và giao vận.</div>
                        </div>
                    </div>
                    <div class="pol-rule-item">
                        <div class="pol-rule-icon"><i class="fa-solid fa-share-nodes"></i></div>
                        <div class="pol-rule-text">
                            <div class="pol-rule-title">Không chia sẻ bên thứ 3</div>
                            <div class="pol-rule-desc">Tuyệt đối không cung cấp thông tin khách hàng cho bên thứ ba vì mục đích thương mại hay quảng cáo.</div>
                        </div>
                    </div>
                    <div class="pol-rule-item">
                        <div class="pol-rule-icon"><i class="fa-solid fa-key"></i></div>
                        <div class="pol-rule-text">
                            <div class="pol-rule-title">Mã hóa mật khẩu</div>
                            <div class="pol-rule-desc">Mật khẩu người dùng được mã hóa <strong>bcrypt</strong> — nhân viên QHTN không thể xem mật khẩu của bạn.</div>
                        </div>
                    </div>
                    <div class="pol-rule-item">
                        <div class="pol-rule-icon"><i class="fa-solid fa-shield-halved"></i></div>
                        <div class="pol-rule-text">
                            <div class="pol-rule-title">Quyền của khách hàng</div>
                            <div class="pol-rule-desc">Khách hàng có quyền yêu cầu xem, chỉnh sửa hoặc xóa dữ liệu cá nhân bất kỳ lúc nào qua kênh hỗ trợ.</div>
                        </div>
                    </div>
                </div>
                <div class="pol-highlight">
                    <i class="fa-solid fa-shield-halved"></i>
                    <div class="pol-highlight-text">
                        Để biết thêm thông tin hoặc yêu cầu xóa dữ liệu, liên hệ: <strong>qhtn.fashion@gmail.com</strong> hoặc hotline <strong>0xxx-xxx-xxx</strong>. Chúng tôi phản hồi trong vòng 24 giờ làm việc.
                    </div>
                </div>
            </div>
        </div>

    </div><!-- .pol-sections -->
</div><!-- .pol-main -->

<!-- ===== COMMITMENT STRIP ===== -->
<div class="pol-commit-bg">
    <div class="pol-commit-inner">
        <div class="pol-commit-item">
            <div class="pol-commit-icon"><i class="fa-solid fa-handshake"></i></div>
            <div class="pol-commit-title">Cam Kết Minh Bạch</div>
            <div class="pol-commit-desc">Mọi chính sách được công khai rõ ràng, không điều khoản ẩn hay phí phát sinh bất ngờ.</div>
        </div>
        <div class="pol-commit-item">
            <div class="pol-commit-icon"><i class="fa-solid fa-headset"></i></div>
            <div class="pol-commit-title">Hỗ Trợ 24/7</div>
            <div class="pol-commit-desc">Đội ngũ hỗ trợ khách hàng sẵn sàng giải quyết khiếu nại và thắc mắc bất kỳ lúc nào.</div>
        </div>
        <div class="pol-commit-item">
            <div class="pol-commit-icon"><i class="fa-solid fa-rotate-left"></i></div>
            <div class="pol-commit-title">Đổi Trả Nhanh</div>
            <div class="pol-commit-desc">Xử lý đổi trả và hoàn tiền nhanh chóng, tối đa 5 ngày làm việc kể từ khi nhận yêu cầu.</div>
        </div>
        <div class="pol-commit-item">
            <div class="pol-commit-icon"><i class="fa-solid fa-lock"></i></div>
            <div class="pol-commit-title">Bảo Mật Tuyệt Đối</div>
            <div class="pol-commit-desc">Dữ liệu cá nhân được mã hóa và bảo vệ theo tiêu chuẩn bảo mật quốc tế hiện hành.</div>
        </div>
    </div>
</div>

<!-- ===== CTA BOTTOM ===== -->
<section class="pol-cta">
    <h2>Còn thắc mắc về chính sách?</h2>
    <p>Đội ngũ QHTN luôn sẵn sàng giải đáp mọi câu hỏi của bạn. Liên hệ ngay để được hỗ trợ trực tiếp.</p>
    <div class="pol-cta-links">
        <a href="ve_chung_toi.php#lien-he" class="pol-btn-pink">
            <i class="fa-solid fa-message"></i>&ensp;Liên hệ hỗ trợ
        </a>
        <a href="membership.php" class="pol-btn-outline">
            <i class="fa-solid fa-crown"></i>&ensp;Xem quyền lợi thành viên
        </a>
    </div>
</section>

</div><!-- .pol-page -->

<?php include 'footer.php'; ?>