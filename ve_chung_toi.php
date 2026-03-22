<?php
require_once 'config.php';
$pageTitle = "Về Chúng Tôi | QHTN Fashion";
include 'header.php';
?>

<style>
    :root {
        --hero-overlay: linear-gradient(120deg, rgba(233, 30, 99, 0.55), rgba(0, 0, 0, 0.35));
        --card-bg: rgba(255, 255, 255, 0.82);
        --shadow-soft: 0 15px 45px rgba(0, 0, 0, 0.12);
        --radius-lg: 18px;
    }

    .about-wrapper {
        background: #faf7f8;
        color: #333;
    }

    /* Hero */
    .about-hero {
        position: relative;
        min-height: 68vh;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: #fff;
        overflow: hidden;
        background: var(--hero-overlay), url('img/about-hero.jpg') center/cover no-repeat;
    }

    .about-hero::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(to bottom, rgba(0, 0, 0, 0.15), rgba(0, 0, 0, 0.55));
    }

    .hero-content {
        position: relative;
        max-width: 900px;
        padding: 40px 24px;
        z-index: 1;
    }

    .hero-kicker {
        letter-spacing: 6px;
        text-transform: uppercase;
        font-size: 13px;
        margin-bottom: 14px;
        color: #ffe5ee;
    }

    .hero-title {
        font-size: clamp(34px, 4vw, 52px);
        font-weight: 800;
        letter-spacing: 2px;
        margin-bottom: 18px;
    }

    .hero-subtitle {
        font-size: 17px;
        max-width: 760px;
        margin: 0 auto 26px;
        line-height: 1.7;
        color: #f7f2f4;
    }

    .hero-cta {
        display: inline-flex;
        gap: 12px;
        align-items: center;
        justify-content: center;
        padding: 12px 28px;
        border-radius: 999px;
        background: #fff;
        color: #d81b60;
        font-weight: 700;
        border: none;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.16);
        transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        cursor: pointer;
    }

    .hero-cta:hover {
        transform: translateY(-2px);
        box-shadow: 0 18px 40px rgba(0, 0, 0, 0.2);
        background: #ffe6ef;
    }

    /* Section shell */
    .section {
        padding: 80px 5%;
    }

    .section-narrow {
        max-width: 1200px;
        margin: 0 auto;
    }

    .section-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .section-kicker {
        font-size: 13px;
        letter-spacing: 5px;
        text-transform: uppercase;
        color: #c77;
        margin-bottom: 10px;
    }

    .section-title {
        font-size: clamp(26px, 3vw, 36px);
        font-weight: 800;
        color: #222;
        letter-spacing: 1px;
    }

    .section-desc {
        color: #666;
        max-width: 820px;
        margin: 12px auto 0;
        line-height: 1.7;
    }

    /* Story */
    .story-grid {
        display: grid;
        grid-template-columns: 1.05fr 0.95fr;
        gap: 48px;
        align-items: center;
    }

    .story-text {
        background: var(--card-bg);
        border-radius: var(--radius-lg);
        padding: 32px;
        box-shadow: var(--shadow-soft);
        backdrop-filter: blur(6px);
        line-height: 1.8;
        color: #444;
    }

    .story-text h3 {
        font-size: 20px;
        margin-bottom: 14px;
        color: #c2185b;
        letter-spacing: 0.5px;
    }

    .story-text p { margin-bottom: 14px; }

    .story-portrait {
        position: relative;
    }

    .story-portrait img {
        width: 100%;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-soft);
        object-fit: cover;
    }

    /* Stats */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 18px;
    }

    .stat-card {
        background: #fff;
        border-radius: 14px;
        padding: 22px 20px;
        text-align: center;
        box-shadow: 0 10px 28px rgba(0, 0, 0, 0.08);
        border: 1px solid #f4d7df;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .stat-card:hover { transform: translateY(-4px); box-shadow: 0 14px 38px rgba(0, 0, 0, 0.12); }

    .stat-number {
        font-size: 30px;
        font-weight: 800;
        color: #c2185b;
        margin-bottom: 6px;
    }

    .stat-label { color: #666; font-weight: 600; }

    /* Values */
    .value-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 18px;
    }

    .value-card {
        background: var(--card-bg);
        border-radius: var(--radius-lg);
        padding: 22px 20px;
        box-shadow: var(--shadow-soft);
        backdrop-filter: blur(6px);
        border: 1px solid #f4d7df;
    }

    .value-card h4 {
        margin-bottom: 10px;
        font-size: 18px;
        color: #c2185b;
    }

    .value-card p { color: #555; line-height: 1.6; }

    /* Timeline */
    .timeline-wrapper {
        position: relative;
        padding: 6px 0 6px 22px;
        max-width: 1100px;
        margin: 0 auto;
    }

    .timeline-line {
        position: absolute;
        left: 12px;
        top: 0;
        bottom: 0;
        width: 3px;
        background: linear-gradient(180deg, #f9d7e3 0%, #f1b4d0 100%);
        border-radius: 6px;
    }

    .timeline {
        display: grid;
        gap: 16px;
    }

    .timeline-card {
        position: relative;
        background: #fff;
        border-radius: 14px;
        padding: 16px 16px 14px 20px;
        border: 1px solid #f4d7df;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .timeline-card:hover {
        transform: translateX(4px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
    }

    .timeline-dot {
        position: absolute;
        left: -14px;
        top: 18px;
        width: 16px;
        height: 16px;
        background: #fff;
        border: 4px solid #c2185b;
        border-radius: 50%;
        box-shadow: 0 2px 10px rgba(0,0,0,0.15);
    }

    .timeline-year {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 999px;
        background: #ffe6ef;
        color: #c2185b;
        font-weight: 700;
        margin-bottom: 10px;
        letter-spacing: 0.5px;
    }

    .timeline-title { font-weight: 700; margin-bottom: 6px; color: #222; }
    .timeline-desc { color: #555; line-height: 1.6; }

    @media (max-width: 640px) {
        .timeline-wrapper { padding-left: 18px; }
        .timeline-line { left: 8px; }
        .timeline-card { padding-left: 18px; }
        .timeline-dot { left: -10px; }
    }

    /* Gallery */
    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 14px;
    }

    .gallery-grid img {
        width: 100%;
        height: 240px;
        object-fit: cover;
        border-radius: 14px;
        box-shadow: 0 10px 28px rgba(0, 0, 0, 0.12);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .gallery-grid img:hover { transform: translateY(-3px); box-shadow: 0 16px 38px rgba(0, 0, 0, 0.18); }

    /* Team */
    .team-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 18px;
    }

    .team-card {
        background: #fff;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: var(--shadow-soft);
        border: 1px solid #f4d7df;
        display: flex;
        flex-direction: column;
    }

    .team-card img { width: 100%; height: 260px; object-fit: cover; }

    .team-info { padding: 16px 18px 18px; }
    .team-name { font-weight: 700; font-size: 18px; color: #222; }
    .team-role { color: #c2185b; font-weight: 600; margin-top: 4px; }
    .team-bio { color: #555; line-height: 1.6; margin-top: 8px; }

    /* CTA */
    .cta {
        padding: 70px 5%;
        background: linear-gradient(120deg, #f9d7e3 0%, #f4c3d8 50%, #f1b4d0 100%);
        text-align: center;
        color: #402b35;
    }

    .cta h3 { font-size: clamp(24px, 3vw, 32px); font-weight: 800; margin-bottom: 10px; }
    .cta p { color: #4a3b44; margin-bottom: 18px; }

    .cta .hero-cta { background: #fff; color: #c2185b; }

    /* Responsive */
    @media (max-width: 1024px) {
        .story-grid { grid-template-columns: 1fr; }
        .about-hero { min-height: 58vh; }
    }

    @media (max-width: 768px) {
        .section { padding: 64px 5%; }
        .hero-title { letter-spacing: 1px; }
        .hero-kicker { letter-spacing: 3px; }
    }

    @media (max-width: 520px) {
        .hero-cta { width: 100%; }
        .gallery-grid img { height: 200px; }
    }
</style>

<div class="about-wrapper">
    <section class="about-hero">
        <div class="hero-content">
            <div class="hero-kicker">QHTN FASHION</div>
            <h1 class="hero-title">Về Chúng Tôi</h1>
            <p class="hero-subtitle">Từ một studio nhỏ với ước mơ đưa thời trang cao cấp đến gần hơn với mọi cô gái, QHTN đã trở thành điểm đến cho những trang phục thiết kế chính hãng, được tuyển chọn thủ công và phục vụ bằng sự tận tâm.</p>
            <a class="hero-cta" href="set_quan_ao.php">Khám phá bộ sưu tập</a>
        </div>
    </section>

    <section class="section">
        <div class="section-narrow">
            <div class="section-header">
                <div class="section-kicker">Our Story</div>
                <h2 class="section-title">Hành trình tạo nên QHTN</h2>
                <p class="section-desc">Chúng tôi bắt đầu từ niềm tin rằng mọi khoảnh khắc đặc biệt đều xứng đáng với những thiết kế tinh xảo. QHTN tuyển chọn trang phục từ các nhà mốt và nghệ nhân, đảm bảo tính nguyên bản, kiểm soát chất lượng và trải nghiệm phục vụ riêng biệt.</p>
            </div>

            <div class="story-grid">
                <div class="story-text">
                    <h3>Khởi nguồn & tầm nhìn</h3>
                    <p>2016, một nhóm stylist và nhà thiết kế trẻ lập nên QHTN để lấp khoảng trống giữa thời trang haute couture và nhu cầu thuê linh hoạt. Chúng tôi muốn mọi khách hàng có thể khoác lên mình các tác phẩm thiết kế mà không bị rào cản sở hữu.</p>
                    <p>Sứ mệnh của QHTN là “giúp bạn tỏa sáng đúng chất riêng”. Mỗi bộ trang phục được lựa chọn, bảo quản và chỉnh sửa thủ công, kèm tư vấn styling cá nhân hóa.</p>

                    <h3>Cam kết & dịch vụ</h3>
                    <p>100% chính hãng, quy trình bảo dưỡng chuẩn boutique, giao nhanh tại nội thành và hỗ trợ thử đồ theo lịch hẹn. Đội ngũ stylist giàu kinh nghiệm luôn đồng hành từ khâu chọn mẫu đến fitting.</p>
                </div>

                <div class="story-portrait">
                    <img src="img/about-portrait.jpg" alt="Stylist QHTN đang chuẩn bị trang phục cho khách"> 
                </div>
            </div>
        </div>
    </section>

    <section class="section" style="padding-top: 30px;">
        <div class="section-narrow stats-grid">
            <div class="stat-card">
                <div class="stat-number">100%+</div>
                <div class="stat-label">Đồ chính hãng</div>
                <div style="color:#777; font-size:13px; margin-top:6px;">Kiểm định nguồn gốc, bảo dưỡng chuẩn boutique</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">5+ năm</div>
                <div class="stat-label">Chuyên về mướn couture</div>
                <div style="color:#777; font-size:13px; margin-top:6px;">Stylist dày dạn kinh nghiệm sự kiện</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">10,000+</div>
                <div class="stat-label">Khách hài lòng</div>
                <div style="color:#777; font-size:13px; margin-top:6px;">Từ dạ hội, lễ đính hôn đến showbiz</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">350+</div>
                <div class="stat-label">Mẫu thiết kế</div>
                <div style="color:#777; font-size:13px; margin-top:6px;">Đa dạng phom dáng & bảng màu</div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="section-narrow">
            <div class="section-header">
                <div class="section-kicker">Core Values</div>
                <h2 class="section-title">Giá trị cốt lõi</h2>
            </div>
            <div class="value-grid">
                <div class="value-card">
                    <h4>Premium Quality</h4>
                    <p>Chỉ nhận đồ thiết kế chính hãng, kiểm tra thủ công từng đường may, lưu kho chuẩn nhiệt-ẩm để giữ phom hoàn hảo.</p>
                </div>
                <div class="value-card">
                    <h4>Diverse Collection</h4>
                    <p>Bộ sưu tập trải rộng từ dạ hội, red-carpet, lễ cưới đến cocktail. Cập nhật theo mùa và khuynh hướng runway.</p>
                </div>
                <div class="value-card">
                    <h4>Personal Styling</h4>
                    <p>Tư vấn 1-1, gợi ý phụ kiện, thử đồ theo lịch hẹn, điều chỉnh vừa vặn trước khi giao.</p>
                </div>
                <div class="value-card">
                    <h4>Care & Sustainability</h4>
                    <p>Giặt hấp chuẩn boutique, tái sử dụng trang phục cao cấp để giảm lãng phí và kéo dài vòng đời thiết kế.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section" style="padding-top: 10px;">
        <div class="section-narrow">
            <div class="section-header" style="margin-bottom: 26px;">
                <div class="section-kicker">Timeline</div>
                <h2 class="section-title">Dấu mốc hình thành & phát triển</h2>
            </div>

            <div class="timeline-wrapper">
                <div class="timeline-line"></div>
                <div class="timeline">
                    <div class="timeline-card">
                        <span class="timeline-dot"></span>
                        <div class="timeline-year">2016</div>
                        <div class="timeline-title">Khởi lập QHTN Studio</div>
                        <div class="timeline-desc">Nhóm stylist trẻ mở studio đầu tiên tại Hà Nội, tập trung dịch vụ thuê đầm dạ hội.</div>
                    </div>
                    <div class="timeline-card">
                        <span class="timeline-dot"></span>
                        <div class="timeline-year">2018</div>
                        <div class="timeline-title">Mở rộng bộ sưu tập couture</div>
                        <div class="timeline-desc">Hợp tác các nhà mốt trong nước, bổ sung dòng thiết kế red-carpet và bridal capsule.</div>
                    </div>
                    <div class="timeline-card">
                        <span class="timeline-dot"></span>
                        <div class="timeline-year">2020</div>
                        <div class="timeline-title">Styling cá nhân hóa</div>
                        <div class="timeline-desc">Ra mắt dịch vụ tư vấn 1-1, fitting theo lịch hẹn, giao nhận tận nơi trong ngày.</div>
                    </div>
                    <div class="timeline-card">
                        <span class="timeline-dot"></span>
                        <div class="timeline-year">2023</div>
                        <div class="timeline-title">10,000+ khách hàng</div>
                        <div class="timeline-desc">Đạt mốc 10k khách hài lòng, mở rộng kho lưu trữ và phòng thử tiêu chuẩn boutique.</div>
                    </div>
                    <div class="timeline-card">
                        <span class="timeline-dot"></span>
                        <div class="timeline-year">2025</div>
                        <div class="timeline-title">Nâng cấp trải nghiệm omnichannel</div>
                        <div class="timeline-desc">Đặt lịch, chọn mẫu và giữ size trực tuyến; quy trình bảo dưỡng nâng cấp với thiết bị mới.</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section" style="padding-top: 10px;">
        <div class="section-narrow">
            <div class="section-header">
                <div class="section-kicker">Gallery</div>
                <h2 class="section-title">Khoảnh khắc tỏa sáng</h2>
                <p class="section-desc">Một vài lookbook và hình ảnh runway truyền cảm hứng cho bộ sưu tập của QHTN.</p>
            </div>

            <div class="gallery-grid">
                <img src="assets/pictures/about/4.jpg" alt="Lookbook váy dạ hội hồng ánh kim">
                <img src="assets/pictures/about/5.jpg" alt="Chi tiết corset và chất liệu lấp lánh">
                <img src="assets/pictures/about/6.jpg" alt="Đầm couture dài phối tay cape">
                <img src="assets/pictures/about/7.jpg" alt="Runway phong cách black-tie">
                <img src="assets/pictures/about/8.jpg" alt="Chi tiết eo thắt nơ trên vải satin">
                <img src="assets/pictures/about/9.jpg" alt="Đầm ren đính đá tông pastel">
            </div>
        </div>
    </section>

    <section class="section" style="padding-top: 10px;">
        <div class="section-narrow">
            <div class="section-header">
                <div class="section-kicker">Team</div>
                <h2 class="section-title">Stylist & cố vấn</h2>
                <p class="section-desc">Những người đứng sau từng lần fitting, bảo dưỡng và chọn mẫu cho bạn.</p>
            </div>

            <div class="team-grid">
                <div class="team-card">
                    <img src="assets/pictures/about/1.jpg" alt="Lead stylist QHTN">
                    <div class="team-info">
                        <div class="team-name">Lan Anh</div>
                        <div class="team-role">Lead Stylist</div>
                        <div class="team-bio">10 năm styling sự kiện, hiểu phom dáng và bảng màu tôn da, luôn tối ưu look cho từng dáng người.</div>
                    </div>
                </div>
                <div class="team-card">
                    <img src="assets/pictures/about/1 copy.jpg" alt="Head of Curation QHTN">
                    <div class="team-info">
                        <div class="team-name">Minh Châu</div>
                        <div class="team-role">Head of Curation</div>
                        <div class="team-bio">Chọn lọc thiết kế, làm việc cùng nhà mốt để có phiên bản giới hạn, giữ chất lượng và độ độc bản.</div>
                    </div>
                </div>
                <div class="team-card">
                    <img src="assets/pictures/about/3.jpg" alt="Tailoring specialist QHTN">
                    <div class="team-info">
                        <div class="team-name">Thùy Dương</div>
                        <div class="team-role">Tailoring Specialist</div>
                        <div class="team-bio">Phụ trách fitting, chỉnh sửa dáng và bảo dưỡng chất liệu, đảm bảo trang phục sẵn sàng ngay trước sự kiện.</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="cta">
        <h3>Sẵn sàng tỏa sáng trong thiết kế chuẩn boutique?</h3>
        <p>Đặt lịch thử đồ hoặc chọn ngay look yêu thích.</p>
        <a class="hero-cta" href="set_quan_ao.php">Chọn trang phục ngay</a>
    </section>
</div>

<?php include 'footer.php'; ?>