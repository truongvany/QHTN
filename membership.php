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

<div class="membership-levels">
    <div class="container">
        <div style="text-align: center;">
            <span class="small-title">Quyền Lợi Độc Quyền</span>
            <h2 class="main-title" style="margin-bottom: 10px;">HẠNG THÀNH VIÊN</h2>
            <p style="color: #666;">Tích lũy chi tiêu để nhận được nhiều ưu đãi hấp dẫn hơn</p>
        </div>

        <div class="level-grid">
            <div class="level-card card-silver">
                <i class="fa-solid fa-medal level-icon"></i>
                <h3 class="level-name">Thành Viên Bạc</h3>
                
                <ul class="benefit-list">
                    <li><i class="fa-solid fa-check"></i> Tích điểm đổi quà</li>
                    <li><i class="fa-solid fa-check"></i> Hỗ trợ tư vấn 24/7</li>
                    <li><i class="fa-solid fa-check"></i> Tham gia các event sale</li>
                </ul>
                
                <?php if(!isset($_SESSION['username'])): ?>
                    <a href="register.php" class="btn-register-now">Đăng Ký Ngay</a>
                <?php else: ?>
                    <span style="color: #888; font-size: 13px;">Bạn đã là thành viên</span>
                <?php endif; ?>
            </div>

            <div class="level-card card-gold">
                <i class="fa-solid fa-crown level-icon"></i>
                <h3 class="level-name">Thành Viên Vàng</h3>
                <p class="level-condition">Chi tiêu > 3.000.000đ / năm</p>
                
                <ul class="benefit-list">
                    <li><i class="fa-solid fa-check"></i> <strong>Giảm 5%</strong> mọi đơn thuê</li>
                    <li><i class="fa-solid fa-check"></i> Freeship 2 chiều</li>
                    <li><i class="fa-solid fa-check"></i> Được giữ đồ thêm 1 ngày</li>
                    <li><i class="fa-solid fa-check"></i> Quà tặng sinh nhật</li>
                </ul>
            </div>

            <div class="level-card card-diamond">
                <i class="fa-regular fa-gem level-icon"></i>
                <h3 class="level-name">Kim Cương</h3>
                <p class="level-condition">Chi tiêu > 10.000.000đ / năm</p>
                
                <ul class="benefit-list">
                    <li><i class="fa-solid fa-check"></i> <strong>Giảm 10%</strong> mọi đơn thuê</li>
                    <li><i class="fa-solid fa-check"></i> Ưu tiên thử đồ tại nhà</li>
                    <li><i class="fa-solid fa-check"></i> Được giữ đồ thêm 2 ngày</li>
                    <li><i class="fa-solid fa-check"></i> Đặc quyền Pre-order mẫu mới</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>