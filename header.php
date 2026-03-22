<?php
// Kiểm tra session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

// Logic đếm giỏ hàng
$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += isset($item['quantity']) ? (int)$item['quantity'] : 0;
    }
}
$current_page = basename($_SERVER['PHP_SELF']);

// Kiểm tra xem có phải trang chủ không (biến này cần được đặt bên index.php trước khi include header)
$isHome = isset($is_homepage) && $is_homepage === true;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QHTN - Thuê Đồ Thời Trang</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>"> 
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        /* ===== HEADER STYLING ===== */
        .sticky-wrapper {
            background: linear-gradient(135deg, rgba(233, 90, 138, 0.05) 0%, rgba(255, 214, 230, 0.5) 100%);
            border-bottom: 2px solid rgba(233, 90, 138, 0.15);
            backdrop-filter: blur(10px);
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 5%;
            max-width: 1400px;
            margin: 0 auto;
            min-height: 48px;
        }

        /* Logo */
        .logo {
            display: flex;
            align-items: center;
            flex-shrink: 0;
        }

        .logo img {
            width: 40px;
            height: 40px;
            object-fit: contain;
            display: block;
            transition: transform 0.3s ease;
        }

        .logo a:hover img {
            transform: scale(1.05);
        }

        /* Search Container */
        .search-container {
            flex: 1;
            display: flex;
            justify-content: center;
            margin: 0 16px;
        }

        .search-box {
            width: 100%;
            max-width: 380px;
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.95);
            border: 1.5px solid rgba(233, 90, 138, 0.3);
            border-radius: 50px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(233, 90, 138, 0.06);
        }

        .search-box:focus-within {
            border-color: var(--accent-pink);
            box-shadow: 0 5px 15px rgba(233, 90, 138, 0.12);
        }

        .search-box input {
            flex: 1;
            border: none;
            padding: 8px 14px;
            outline: none;
            font-size: 12px;
            background: transparent;
            color: var(--text-color);
            font-family: 'Montserrat', sans-serif;
        }

        .search-box input::placeholder {
            color: #bbb;
        }

        .search-box button {
            background: var(--accent-pink);
            color: white;
            border: none;
            padding: 0 13px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 13px;
            height: 100%;
        }

        .search-box button:hover {
            background: #d54f7b;
        }

        /* Header Right */
        .header-right {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-shrink: 0;
        }

        /* Cart Icon */
        .cart-icon-header a {
            position: relative;
            font-size: 18px;
            color: var(--accent-pink);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .cart-icon-header a:hover {
            transform: scale(1.1);
            color: #d54f7b;
        }

        .cart-count {
            position: absolute;
            top: -6px;
            right: -8px;
            background: var(--accent-pink);
            color: white;
            font-size: 8px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        /* Auth Links */
        .auth-links {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .profile-icon {
            color: var(--accent-pink);
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .profile-icon:hover {
            color: #d54f7b;
            transform: scale(1.1);
        }

        .user-dropdown {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .user-dropdown a {
            font-size: 11px;
            color: var(--accent-pink);
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .user-dropdown a:hover {
            color: #d54f7b;
        }

        .badge-admin {
            background: var(--white);
            color: #2f1c26;
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border: 1px solid var(--accent-pink);
        }

        .badge-admin:hover {
            background: #f7d5e2;
        }

        .btn-login {
            background: var(--accent-pink);
            color: white;
            padding: 7px 14px;
            border: none;
            border-radius: 50px;
            font-weight: 700;
            font-size: 11px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
        }

        .btn-login:hover {
            background: #d54f7b;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(233, 90, 138, 0.2);
        }

        /* ===== MAIN MENU ===== */
        .main-menu {
            background: linear-gradient(90deg, rgba(255, 214, 230, 0.3) 0%, rgba(245, 233, 241, 0.5) 100%);
            border-top: 1px solid rgba(233, 90, 138, 0.1);
            padding: 0 5%;
        }

        .main-menu > ul {
            display: flex;
            justify-content: center;
            gap: 24px;
            padding: 9px 0;
            margin: 0;
            max-width: 1400px;
            margin: 0 auto;
        }

        .main-menu > ul > li {
            position: relative;
        }

        .menu-link {
            font-weight: 700;
            font-size: 11px;
            color: #2f1c26 !important;
            text-transform: uppercase;
            padding: 6px 0;
            display: block;
            position: relative;
            transition: all 0.3s ease;
            letter-spacing: 0.4px;
        }

        .menu-link:hover,
        .menu-link.active {
            color: var(--accent-pink) !important;
        }

        .menu-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--accent-pink);
            transition: width 0.3s ease;
        }

        .menu-link:hover::after,
        .menu-link.active::after {
            width: 100%;
        }

        /* Dropdown */
        .dropdown-container {
            position: relative;
        }

        .dropdown-content {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            min-width: 170px;
            list-style: none;
            padding: 5px 0;
            border-radius: 8px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.09);
            border: 1px solid rgba(233, 90, 138, 0.15);
            display: none;
            flex-direction: column;
            gap: 0;
            z-index: 1000;
        }

        .dropdown-container:hover .dropdown-content {
            display: flex;
        }

        .dropdown-content a {
            padding: 9px 14px;
            color: var(--text-color);
            font-weight: 600;
            font-size: 11px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .dropdown-content a:hover {
            background: var(--primary-pink);
            color: var(--accent-pink);
            padding-left: 18px;
        }

        .dropdown-content i {
            font-size: 11px;
            color: var(--accent-pink);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            header {
                padding: 6px 3%;
                min-height: 46px;
            }

            .search-container {
                margin: 0 12px;
            }

            .search-box {
                max-width: 320px;
            }

            .header-right {
                gap: 12px;
            }

            .main-menu > ul {
                gap: 20px;
                padding: 8px 0;
            }

            .menu-link {
                font-size: 10px;
                padding: 5px 0;
            }
        }

        @media (max-width: 768px) {
            header {
                flex-wrap: wrap;
                padding: 6px 3%;
                min-height: auto;
                gap: 6px;
            }

            .logo img {
                width: 36px;
                height: 36px;
            }

            .search-container {
                order: 3;
                width: 100%;
                margin: 6px 0 0 0;
            }

            .search-box {
                max-width: 100%;
                border-radius: 8px;
            }

            .main-menu > ul {
                gap: 12px;
                padding: 7px 0;
                overflow-x: auto;
            }

            .menu-link {
                font-size: 9px;
                white-space: nowrap;
                padding: 4px 0;
            }

            .header-right {
                gap: 8px;
            }
        }

        @media (max-width: 480px) {
            .search-box input {
                padding: 7px 10px;
                font-size: 11px;
            }

            .header-right {
                gap: 6px;
            }

            .main-menu > ul {
                gap: 8px;
            }

            .menu-link {
                font-size: 8px;
            }

            .cart-icon-header a {
                font-size: 16px;
            }

            .profile-icon {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>

<div class="sticky-wrapper <?= $isHome ? 'home-mode' : '' ?>">
    <header>
        <div class="logo" style="display:flex; align-items:center;">
            <a href="index.php" style="display:flex; align-items:center; gap:16px;">
                <img src="assets/logo.png" alt="QHTN" style="width: 96px; height: 96px; object-fit:contain; display:block;" />
    
            </a>
        </div>
        
        <div class="search-container">
            <form method="GET" action="search.php" class="search-box">
                <input type="text" name="keyword" placeholder="Bạn cần tìm gì?" required value="<?php echo isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : ''; ?>">
                <button type="submit"><i class="fa fa-search"></i></button>
            </form>
        </div>

        <div class="header-right">
            <div class="cart-icon-header">
                <a href="cart.php" title="Giỏ hàng">
                    <i class="fa-solid fa-bag-shopping"></i>
                    <?php if($cartCount > 0): ?>
                        <span class="cart-count" id="cart-count"><?= $cartCount ?></span>
                    <?php endif; ?>
                </a>
            </div>
            
            <div class="auth-links">
                <?php if (isset($_SESSION['username'])): ?>
                    <a href="profile.php" title="Trang cá nhân" class="profile-icon" style="margin-right: 14px; <?= $isHome ? 'color:#fff' : 'color:#322'; ?>; font-size: 32px; display:flex; align-items:center;">
                        <i class="fa-solid fa-circle-user" style="font-size: 28px;"></i>
                    </a>
                    <div class="user-dropdown">
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <a class="badge-admin" href="admin/admin.php" title="Trang quản trị"><i class="fa-solid fa-user-shield"></i> Admin</a>
                        <?php endif; ?>
                        
                        <a href="logout.php" title="Đăng xuất" style="margin-left: 10px; color: #e74c3c;">
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn-login" style="<?= $isHome ? 'background:#fff; border:none;' : '' ?>"><i class="fa-regular fa-user"></i> Đăng nhập</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <nav class="main-menu">
        <ul>
            <li><a href="index.php" class="menu-link <?= ($current_page == 'index.php') ? 'active' : '' ?>">Trang Chủ</a></li>
            <li><a href="ve_chung_toi.php" class="menu-link">Về QHTN</a></li>
            
            <li class="dropdown-container">
                <a href="javascript:void(0)" class="menu-link" id="product-toggle">
                    Sản Phẩm <i class="fa-solid fa-chevron-down" style="font-size: 10px; margin-left: 5px;"></i>
                </a>
                <ul class="dropdown-content">
                    <li><a href="ao_dai.php"><i class="fa-solid fa-vest-patches"></i> Áo Dài</a></li>
                    <li><a href="vay_thiet_ke.php"><i class="fa-solid fa-person-dress"></i> Váy Thiết Kế</a></li>
                    <li><a href="set_quan_ao.php"><i class="fa-solid fa-layer-group"></i> Set Quần Áo</a></li>
                    <li><a href="vay_di_bien.php"><i class="fa-solid fa-umbrella-beach"></i> Váy Đi Biển</a></li>
                    <li><a href="giay.php"><i class="fa-solid fa-shoe-prints"></i> Giày</a></li>
                    <li><a href="phu_kien.php"><i class="fa-solid fa-gem"></i> Phụ Kiện</a></li>
                </ul>
            </li>

            <li><a href="chinh_sach.php" class="menu-link">Chính Sách</a></li>
            <li><a href="membership.php" class="menu-link">Menmbership</a></li>
            <li>
    <a href="javascript:void(0)" class="menu-link btn-livestream" id="open-livestream">
        <span class="live-dot"></span> Live stream
    </a>
</li>
        </ul>
    </nav>
</div>

<script>
$(document).ready(function() {
    // 1. Toggle Menu Sản Phẩm
    $('#product-toggle').click(function(e) {
        e.stopPropagation();
        $(this).next('.dropdown-content').toggleClass('show');
    });

    // 2. Click ra ngoài để đóng menu
    $(document).click(function(e) {
        if (!$(e.target).closest('.dropdown-container').length) {
            $('.dropdown-content').removeClass('show');
        }
    });
});
</script>

<div id="livestream-modal" class="modal-overlay">
    <div class="modal-content-video">
        <span class="close-modal-video">&times;</span>
        
        <div class="video-wrapper">
            <iframe width="100%" height="400" 
                    src="https://www.youtube.com/embed/live_stream_id?autoplay=1" 
                    title="Live Stream" frameborder="0" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                    allowfullscreen>
            </iframe>
            
            </div>
    </div>
</div>

<style>
    /* 1. Nút Live Stream trên Menu */
    .btn-livestream {
        display: flex; 
        align-items: center; 
        gap: 8px;
        color: var(--accent-pink) !important; /* Chữ màu hồng đỏ */
        font-weight: 700 !important;
    }

    /* Dấu chấm đỏ nhấp nháy */
    .live-dot {
        width: 8px; height: 8px;
        background-color: red;
        border-radius: 50%;
        display: inline-block;
        box-shadow: 0 0 0 0 rgba(255, 0, 0, 0.7);
        animation: pulse-red 1.5s infinite;
    }

    @keyframes pulse-red {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 0, 0, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(255, 0, 0, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 0, 0, 0); }
    }

    /* 2. Modal Popup (Nền tối) */
    .modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.85); /* Nền đen mờ */
        z-index: 99999;
        display: none; /* Mặc định ẩn */
        justify-content: center; align-items: center;
        opacity: 0; transition: opacity 0.3s ease;
    }
    .modal-overlay.show { display: flex; opacity: 1; }

    /* Nội dung Popup */
    .modal-content-video {
        position: relative;
        width: 90%; max-width: 800px;
        background: #000;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        transform: scale(0.8); transition: transform 0.3s ease;
    }
    .modal-overlay.show .modal-content-video { transform: scale(1); }

    /* Nút đóng X */
    .close-modal-video {
        position: absolute; top: -10px; right: 10px;
        font-size: 40px; color: #fff; cursor: pointer; z-index: 10;
        transition: 0.2s;
    }
    .close-modal-video:hover { color: var(--accent-pink); }

    /* Khung Video Responsive */
    .video-wrapper {
        position: relative; padding-bottom: 56.25%; /* Tỷ lệ 16:9 */
        height: 0; overflow: hidden;
        background: #000;
    }
    .video-wrapper iframe {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
    }
</style>

<script>
$(document).ready(function() {
    // 1. Mở Popup khi click vào nút Live stream
    $('#open-livestream').click(function(e) {
        e.preventDefault();
        $('#livestream-modal').addClass('show');
    });

    // 2. Đóng Popup khi click nút X
    $('.close-modal-video').click(function() {
        $('#livestream-modal').removeClass('show');
        // Dừng video khi đóng (bằng cách reset src)
        var $iframe = $('#livestream-modal iframe');
        var src = $iframe.attr('src');
        $iframe.attr('src', '');
        $iframe.attr('src', src);
    });

    // 3. Đóng Popup khi click ra vùng đen bên ngoài
    $('#livestream-modal').click(function(e) {
        if ($(e.target).is('#livestream-modal')) {
            $('.close-modal-video').click();
        }
    });
});
</script>