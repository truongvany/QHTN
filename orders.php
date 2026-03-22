<?php
require_once 'config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$pageTitle = 'Đơn hàng | QHTN';
include 'header.php';
?>

<div class="profile-container">
    <aside class="profile-sidebar">
        <nav class="sidebar-menu">
            <a href="profile.php" class="menu-item">
                <i class="fa-solid fa-circle-user"></i>
                <span>Thông tin cá nhân</span>
            </a>
            <a href="orders.php" class="menu-item active">
                <i class="fa-solid fa-receipt"></i>
                <span>Đơn hàng</span>
            </a>
            <a href="addresses.php" class="menu-item">
                <i class="fa-solid fa-location-dot"></i>
                <span>Địa chỉ</span>
            </a>
            <a href="wishlist.php" class="menu-item">
                <i class="fa-solid fa-heart"></i>
                <span>Yêu thích</span>
            </a>
            <a href="settings.php" class="menu-item">
                <i class="fa-solid fa-gear"></i>
                <span>Cài đặt</span>
            </a>
            <a href="logout.php" class="menu-item logout">
                <i class="fa-solid fa-sign-out-alt"></i>
                <span>Đăng xuất</span>
            </a>
        </nav>
    </aside>

    <main class="profile-content">
        <div class="section">
            <h2 class="section-title">Đơn hàng của tôi</h2>
            <div class="empty-state">
                <i class="fa-solid fa-inbox"></i>
                <p>Chức năng này đang được phát triển</p>
            </div>
        </div>
    </main>
</div>

<?php include 'footer.php'; ?>
