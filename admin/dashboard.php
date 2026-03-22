<?php
require_once __DIR__ . '/layout.php';

$totalProducts = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$totalOrders = (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$totalRevenue = (float)$pdo->query('SELECT COALESCE(SUM(total_price),0) FROM orders')->fetchColumn();
$totalUsers = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

// Get revenue for current month
$thisMonthRevenue = (float)$pdo->query("
    SELECT COALESCE(SUM(total_price), 0) FROM orders 
    WHERE MONTH(created_at) = MONTH(NOW()) 
    AND YEAR(created_at) = YEAR(NOW())
")->fetchColumn();

// Get rentals due in next 3 days
$dueSoonRentals = $pdo->query("
    SELECT 
        od.id, o.id as order_id, o.created_at,
        u.username AS customer_name, u.email, p.name AS product_name,
        od.rental_end, 
        DATEDIFF(od.rental_end, NOW()) as days_left
    FROM order_details od
    LEFT JOIN orders o ON od.order_id = o.id
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN products p ON od.product_id = p.id
    WHERE od.rental_end IS NOT NULL 
        AND od.status != 'returned'
        AND DATEDIFF(od.rental_end, NOW()) BETWEEN 0 AND 3
    ORDER BY od.rental_end ASC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$latestOrders = $pdo->query('SELECT o.*, u.username AS customer_name, u.email, u.phone FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
$topCategories = $pdo->query('SELECT c.name, COUNT(p.id) AS total_products FROM categories c LEFT JOIN products p ON p.category_id = c.id GROUP BY c.id ORDER BY total_products DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);

admin_header('Tổng quan', 'dashboard');
?>

<style>
    .dashboard-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    
    .stat-card {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
    
    .stat-label {
        font-size: 12px;
        color: #999;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }
    
    .stat-value {
        font-size: 28px;
        font-weight: 700;
        color: #333;
    }
    
    .stat-card.accent .stat-value {
        color: #e95a8a;
    }
    
    .stat-card.accent {
        background: linear-gradient(135deg, #ffd6e6 0%, #f5e9f1 100%);
        border-color: #f48fb1;
    }
    
    .stat-trend {
        font-size: 11px;
        color: #999;
        margin-top: 8px;
    }
    
    .dashboard-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-top: 24px;
    }
    
    .widget-card {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
    }
    
    .widget-title {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 2px solid #ffd6e6;
    }
    
    .widget-title h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 700;
    }
    
    .widget-title a {
        font-size: 11px;
        font-weight: 600;
        color: #e95a8a;
        text-decoration: none;
    }
    
    .widget-title a:hover {
        text-decoration: underline;
    }
    
    .rental-item {
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .rental-item:last-child {
        border-bottom: none;
    }
    
    .rental-info {
        flex: 1;
    }
    
    .rental-product {
        font-weight: 600;
        font-size: 13px;
        color: #333;
    }
    
    .rental-customer {
        font-size: 11px;
        color: #999;
        margin-top: 2px;
    }
    
    .rental-days {
        font-weight: 700;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        margin-left: 12px;
        white-space: nowrap;
    }
    
    .days-critical {
        background: #dc3545;
        color: white;
    }
    
    .days-warning {
        background: #ffc107;
        color: #333;
    }
    
    .days-healthy {
        background: #28a745;
        color: white;
    }
    
    .empty-state {
        text-align: center;
        color: #999;
        padding: 32px 16px;
        font-size: 13px;
    }
    
    .dashboard-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    
    .dashboard-table th {
        background: #f9f9f9;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        font-size: 11px;
        color: #666;
        border-bottom: 2px solid #e0e0e0;
    }
    
    .dashboard-table td {
        padding: 12px;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .dashboard-table tr:hover {
        background: #fafafa;
    }
    
    @media (max-width: 1024px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
        
        .dashboard-stats {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 768px) {
        .dashboard-stats {
            grid-template-columns: 1fr;
        }
    }
</style>

<!-- Main Stats Cards -->
<div class="dashboard-stats">
    <div class="stat-card">
        <div class="stat-label">Tổng sản phẩm</div>
        <div class="stat-value"><?php echo $totalProducts; ?></div>
        <div class="stat-trend">Trong hệ thống</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Tổng đơn hàng</div>
        <div class="stat-value"><?php echo $totalOrders; ?></div>
        <div class="stat-trend">Mọi thời gian</div>
    </div>
    <div class="stat-card accent">
        <div class="stat-label">Doanh thu tháng này</div>
        <div class="stat-value"><?php echo number_format($thisMonthRevenue, 0, ',', '.'); ?></div>
        <div class="stat-trend">Tính bằng đ</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Người dùng hoạt động</div>
        <div class="stat-value"><?php echo $totalUsers; ?></div>
        <div class="stat-trend">Đã đăng ký</div>
    </div>
</div>

<!-- Main Content Grid -->
<div class="dashboard-grid">
    <!-- Rentals Due Soon -->
    <div class="widget-card">
        <div class="widget-title">
            <h3>📋 Sắp hết hạn thuê</h3>
            <a href="rentals.php">Xem tất cả →</a>
        </div>
        <?php if (!empty($dueSoonRentals)): ?>
            <div>
                <?php foreach ($dueSoonRentals as $rental): ?>
                    <div class="rental-item">
                        <div class="rental-info">
                            <div class="rental-product"><?php echo htmlspecialchars($rental['product_name']); ?></div>
                            <div class="rental-customer">
                                <?php echo htmlspecialchars($rental['customer_name']); ?> 
                                <i class="fa-solid fa-circle" style="font-size: 3px; margin: 0 4px; vertical-align: middle;"></i>
                                <?php echo htmlspecialchars($rental['email']); ?>
                            </div>
                        </div>
                        <?php
                        $daysLeft = (int)$rental['days_left'];
                        if ($daysLeft < 0) {
                            $daysClass = 'days-critical';
                            $daysText = '⚠️ Quá hạn';
                        } elseif ($daysLeft < 1) {
                            $daysClass = 'days-critical';
                            $daysText = '🔴 Hôm nay';
                        } else {
                            $daysClass = 'days-warning';
                            $daysText = '🟠 ' . $daysLeft . ' ngày';
                        }
                        ?>
                        <span class="rental-days <?php echo $daysClass; ?>"><?php echo $daysText; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">✓ Không có đơn hàng sắp hết hạn</div>
        <?php endif; ?>
    </div>
    
    <!-- Revenue This Month & Quick Actions -->
    <div class="widget-card">
        <div class="widget-title">
            <h3>💰 Thống kê doanh thu</h3>
            <a href="reports.php">Chi tiết →</a>
        </div>
        <div style="text-align: center; padding: 16px 0;">
            <div class="stat-label">Tháng này</div>
            <div style="font-size: 32px; font-weight: 700; color: #e95a8a; margin: 12px 0;">
                <?php echo number_format($thisMonthRevenue, 0, ',', '.'); ?> đ
            </div>
            <div class="stat-label">So với tất cả thời gian</div>
            <div style="font-size: 18px; font-weight: 600; color: #333; margin-top: 12px;">
                <?php echo number_format($totalRevenue, 0, ',', '.'); ?> đ
            </div>
        </div>
        <hr style="margin: 16px 0; border: none; border-top: 1px solid #f0f0f0;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 16px;">
            <a href="rentals.php" class="btn ghost" style="text-align: center; font-size: 12px;">📅 Quản lý thuê</a>
            <a href="inventory.php" class="btn ghost" style="text-align: center; font-size: 12px;">📦 Kho hàng</a>
        </div>
    </div>
</div>

<!-- Latest Orders & Top Categories -->
<div class="dashboard-grid" style="margin-top: 24px;">
    <div class="widget-card">
        <div class="widget-title">
            <h3>🛒 Đơn hàng mới</h3>
            <a href="orders.php">Xem tất cả →</a>
        </div>
        <table class="dashboard-table">
            <tr>
                <th>ID</th>
                <th>Khách</th>
                <th>Ngày</th>
                <th>Tổng</th>
            </tr>
            <?php foreach ($latestOrders as $order): ?>
                <tr style="cursor: pointer;" onclick="window.location.href='order_detail.php?id=<?php echo $order['id']; ?>'">
                    <td><strong>#<?php echo $order['id']; ?></strong></td>
                    <td><?php echo htmlspecialchars($order['customer_name'] ?: 'Khách'); ?></td>
                    <td><?php echo date('d/m', strtotime($order['created_at'])); ?></td>
                    <td><?php echo number_format($order['total_price'], 0, ',', '.'); ?> đ</td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div class="widget-card">
        <div class="widget-title">
            <h3>📂 Top danh mục</h3>
            <a href="products.php">Quản lý →</a>
        </div>
        <table class="dashboard-table">
            <tr>
                <th>Danh mục</th>
                <th>Sản phẩm</th>
            </tr>
            <?php foreach ($topCategories as $cat): ?>
                <tr>
                    <td><?php echo htmlspecialchars($cat['name']); ?></td>
                    <td><strong><?php echo $cat['total_products']; ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<?php
admin_footer();
