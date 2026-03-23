<?php
require_once __DIR__ . '/init.php';

function admin_header(string $pageTitle = 'Admin', string $active = ''): void {
    $flash = get_flash();
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($pageTitle); ?></title>
        <link rel="stylesheet" href="admin.css?v=1">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    </head>
    <body>
    <div class="topbar">
        <div class="brand">QHTN Admin</div>
        <div class="top-actions">
            <a class="pill" href="../index.php" target="_blank"><i class="fa-solid fa-globe"></i> Xem trang</a>
            <a class="pill" href="../logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Đăng xuất</a>
        </div>
    </div>
    <div class="shell">
        <nav class="sidebar">
            <a class="nav-link <?php echo $active === 'dashboard' ? 'active' : ''; ?>" href="dashboard.php"><i class="fa-solid fa-chart-pie"></i> Tổng quan</a>
            <a class="nav-link <?php echo $active === 'products' ? 'active' : ''; ?>" href="products.php"><i class="fa-solid fa-shirt"></i> Sản phẩm</a>
            <a class="nav-link <?php echo $active === 'categories' ? 'active' : ''; ?>" href="categories.php"><i class="fa-solid fa-layer-group"></i> Danh mục</a>
            <a class="nav-link <?php echo $active === 'orders' ? 'active' : ''; ?>" href="orders.php"><i class="fa-solid fa-receipt"></i> Đơn hàng</a>
            <a class="nav-link <?php echo $active === 'rentals' ? 'active' : ''; ?>" href="rentals.php"><i class="fa-solid fa-calendar-days"></i> Quản lý Thuê</a>
        </nav>
        <main class="content">
            <div class="page-header">
                <div>
                    <p class="eyebrow">Bảng điều khiển</p>
                    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
                </div>
            </div>
            <?php if ($flash): ?>
                <div class="alert <?php echo htmlspecialchars($flash['type']); ?>">
                    <?php echo htmlspecialchars($flash['msg']); ?>
                </div>
            <?php endif; ?>
    <?php
}

function admin_footer(): void {
    ?>
        </main>
    </div>
    </body>
    </html>
    <?php
}
