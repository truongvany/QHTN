<?php
require_once 'config.php';
// Đặt tiêu đề trang riêng
$pageTitle = "Về Chúng Tôi & Chính Sách Thành Viên";
include 'header.php';
?>

<style>
    /* 1. Phần Giới thiệu (Lấy cảm hứng từ ảnh bạn gửi) */
    .about-section {
        padding: 80px 0;
        text-align: center;
        max-width: 1000px;
        margin: 0 auto;
    }
    
    .small-title {
        font-size: 14px;
        letter-spacing: 3px;
        text-transform: uppercase;
        color: #888;
        margin-bottom: 10px;
        display: block;
    }

    .main-title {
        font-size: 36px;
        font-weight: 800;
        color: #333;
        text-transform: uppercase;
        margin-bottom: 50px;
        position: relative;
        display: inline-block;
    }
    
    /* Gạch chân hồng dưới tiêu đề */
    .main-title::after {
        content: '';
        display: block;
        width: 60px;
        height: 4px;
        background: var(--accent-pink, #e91e63);
        margin: 15px auto 0;
        border-radius: 2px;
    }

    .about-content {
        display: grid;
        grid-template-columns: 1fr 1fr; /* Chia 2 cột như trong ảnh */
        gap: 50px;
        text-align: justify;
        line-height: 1.8;
        color: #555;
        font-size: 15px;
    }

    /* 2. Phần Các Hạng Thành Viên */
    .membership-levels {
        background-color: #fff5f7; /* Nền hồng rất nhạt */
        padding: 80px 0;
        margin-top: 50px;
    }

    .level-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        max-width: 1200px;
        margin: 40px auto 0;
        padding: 0 20px;
    }

    .level-card {
        background: #fff;
        border-radius: 15px;
        padding: 40px 30px;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        transition: 0.3s;
        border: 2px solid transparent;
        position: relative;
        overflow: hidden;
    }

    .level-card:hover {
        transform: translateY(-10px);
        border-color: var(--primary-pink, #ffc0cb);
        box-shadow: 0 15px 40px rgba(233, 30, 99, 0.15);
    }

    .level-icon {
        font-size: 50px;
        margin-bottom: 20px;
        color: #ddd; /* Màu mặc định */
    }

    .level-name {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 15px;
        text-transform: uppercase;
    }

    .level-condition {
        font-size: 13px;
        color: #888;
        margin-bottom: 25px;
        font-style: italic;
    }

    .benefit-list {
        list-style: none;
        padding: 0;
        margin-bottom: 30px;
    }

    .benefit-list li {
        margin-bottom: 12px;
        color: #555;
        font-size: 14px;
    }
    .benefit-list li i {
        color: var(--accent-pink);
        margin-right: 8px;
    }

    /* Màu sắc riêng cho từng thẻ */
    .card-silver .level-icon { color: #bdc3c7; }
    .card-gold .level-icon { color: #f1c40f; }
    .card-diamond .level-icon { color: #3498db; }
    
    .card-diamond {
        border-color: var(--accent-pink); /* Thẻ VIP nổi bật hơn */
    }
    .card-diamond::before {
        content: 'BEST CHOICE';
        position: absolute;
        top: 20px; right: -30px;
        background: var(--accent-pink);
        color: #fff;
        font-size: 10px;
        font-weight: bold;
        padding: 5px 30px;
        transform: rotate(45deg);
    }

    .btn-register-now {
        display: inline-block;
        padding: 10px 30px;
        background: #333;
        color: #fff;
        border-radius: 50px;
        font-weight: 600;
        font-size: 14px;
    }
    .btn-register-now:hover {
        background: var(--accent-pink);
        color: #fff;
    }

    /* Responsive cho mobile */
    @media (max-width: 768px) {
        .about-content { grid-template-columns: 1fr; gap: 20px; }
        .main-title { font-size: 28px; }
    }
</style>

<div class="container">
    <div class="about-section">
        <span class="small-title">Về Chúng Tôi</span>
        <h1 class="main-title">QHTN FASHION</h1>

        <div class="about-content">
            <div>
                <p style="margin-bottom: 15px;">
                    Chào mừng đến với <strong>QHTN</strong> - thương hiệu cho thuê đồ thiết kế chính hãng hàng đầu tại Việt Nam. Chúng tôi tự hào cung cấp dịch vụ cho thuê trang phục đi tiệc và sự kiện chất lượng cao, đảm bảo 100% đồ chính hãng.
                </p>
                <p>
                    Với nhiều năm kinh nghiệm trong ngành thời trang, QHTN đã xây dựng được một bộ sưu tập đồ thiết kế đa dạng và phong phú, từ những bộ váy dài sang trọng cho đến những trang phục cá tính và hiện đại.
                </p>
            </div>

            <div>
                <p style="margin-bottom: 15px;">
                    Chúng tôi luôn cập nhật xu hướng mới nhất và mang lại cho khách hàng những trải nghiệm thời trang tuyệt vời. Với QHTN, bạn sẽ không chỉ được trải nghiệm thời trang chất lượng cao mà còn được hưởng những dịch vụ chuyên nghiệp và tận tâm.
                </p>
                <p>
                    Hãy để chúng tôi giúp bạn tỏa sáng trong những bữa tiệc và sự kiện quan trọng của cuộc đời!
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>