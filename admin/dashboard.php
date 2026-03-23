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

// Rentals due soon
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

// Latest orders
$latestOrders = $pdo->query('SELECT o.*, u.username AS customer_name FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);

// Chart Data (Last 7 days)
$chartSql = "
    SELECT 
        DATE_FORMAT(o.created_at, '%d/%m') as period,
        SUM(o.total_price) as revenue
    FROM orders o
    WHERE o.status != 'cancelled'
        AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(o.created_at)
    ORDER BY DATE(o.created_at) ASC
";
$chartData = $pdo->query($chartSql)->fetchAll(PDO::FETCH_ASSOC);

$dates = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('d/m', strtotime("-$i days"));
    $dates[$date] = 0;
}
foreach ($chartData as $row) {
    if (isset($dates[$row['period']])) {
        $dates[$row['period']] = (float)$row['revenue'];
    }
}
$periodLabels = array_keys($dates);
$periodRevenue = array_values($dates);

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
        border: 1px solid #eef2ff;
        border-radius: 12px;
        padding: 24px;
        text-align: left;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
    }
    
    .stat-label {
        font-size: 13px;
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }
    
    .stat-value {
        font-size: 28px;
        font-weight: 800;
        color: #0f172a;
    }
    
    .stat-card.accent {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: #fff;
    }
    .stat-card.accent .stat-label { color: #e0e7ff; }
    .stat-card.accent .stat-value { color: #fff; }
    
    .dashboard-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-top: 24px;
    }
    
    .widget-card {
        background: white;
        border: 1px solid #eef2ff;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    
    .widget-title {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .widget-title h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
    }
    
    .widget-title a {
        font-size: 13px;
        font-weight: 600;
        color: #6366f1;
        text-decoration: none;
    }
    
    .rental-item {
        padding: 12px 16px;
        border-radius: 8px;
        background: #f8fafc;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
        border: 1px solid #f1f5f9;
        transition: background 0.2s;
    }
    
    .rental-item:hover { background: #f1f5f9; }
    
    .rental-info { flex: 1; }
    
    .rental-product {
        font-weight: 600;
        font-size: 14px;
        color: #0f172a;
    }
    
    .rental-customer {
        font-size: 12px;
        color: #64748b;
        margin-top: 4px;
    }
    
    .rental-days {
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 12px;
    }
    
    .days-critical { background: #fee2e2; color: #b91c1c; }
    .days-warning { background: #fef3c7; color: #b45309; }
    
    .dashboard-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }
    
    .dashboard-table th {
        padding: 12px;
        text-align: left;
        font-weight: 600;
        color: #64748b;
        border-bottom: 2px solid #f1f5f9;
    }
    
    .dashboard-table td {
        padding: 12px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
    }
    
    .dashboard-table tr:hover td {
        background: #f8fafc;
    }
    
    .chart-box {
        background: white;
        border: 1px solid #eef2ff;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        height: 300px;
        margin-bottom: 24px;
    }
    
    @media (max-width: 1024px) {
        .dashboard-grid { grid-template-columns: 1fr; }
    }
</style>

<!-- Main Stats -->
<div class="dashboard-stats">
    <div class="stat-card accent">
        <div class="stat-label">Doanh thu tháng này</div>
        <div class="stat-value"><?php echo number_format($thisMonthRevenue, 0, ',', '.'); ?> đ</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Tổng đơn hàng</div>
        <div class="stat-value"><?php echo $totalOrders; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Sản phẩm hiện có</div>
        <div class="stat-value"><?php echo $totalProducts; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Người dùng</div>
        <div class="stat-value"><?php echo $totalUsers; ?></div>
    </div>
</div>

<!-- Revenue Chart -->
<div class="chart-box">
    <div style="font-size: 16px; font-weight: 700; color: #1e293b; margin-bottom: 16px;">Biểu đồ doanh thu 7 ngày qua</div>
    <div style="height: 220px;">
        <canvas id="revenueChart"></canvas>
    </div>
</div>

<!-- Main Content Grid -->
<div class="dashboard-grid">
    <!-- Latest Orders -->
    <div class="widget-card">
        <div class="widget-title">
            <h3><i class="fa-solid fa-cart-shopping" style="color:#6366f1; margin-right:8px;"></i>Đơn hàng mới</h3>
            <a href="orders.php">Tất cả →</a>
        </div>
        <table class="dashboard-table">
            <tr>
                <th>Mã</th>
                <th>Khách hàng</th>
                <th>Ngày</th>
                <th style="text-align: right;">Tổng</th>
            </tr>
            <?php foreach ($latestOrders as $order): ?>
                <tr style="cursor: pointer;" onclick="window.location.href='order_detail.php?id=<?php echo $order['id']; ?>'">
                    <td><strong>#<?php echo $order['id']; ?></strong></td>
                    <td><?php echo htmlspecialchars($order['customer_name'] ?: 'Khách'); ?></td>
                    <td><?php echo date('d/m', strtotime($order['created_at'])); ?></td>
                    <td style="text-align: right; font-weight: 600; color: #4f46e5;"><?php echo number_format($order['total_price'], 0, ',', '.'); ?> đ</td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- Rentals Due Soon -->
    <div class="widget-card">
        <div class="widget-title">
            <h3><i class="fa-solid fa-clock" style="color:#f59e0b; margin-right:8px;"></i>Sắp đến hạn trả</h3>
            <a href="rentals.php">Quản lý →</a>
        </div>
        <?php if (!empty($dueSoonRentals)): ?>
            <div>
                <?php foreach ($dueSoonRentals as $rental): ?>
                    <div class="rental-item" style="cursor: pointer;" onclick="window.location.href='order_detail.php?id=<?php echo $rental['order_id']; ?>'">
                        <div class="rental-info">
                            <div class="rental-product"><?php echo htmlspecialchars($rental['product_name']); ?></div>
                            <div class="rental-customer">
                                <?php echo htmlspecialchars($rental['customer_name']); ?> • Nhận: <?php echo date('d/m', strtotime($rental['rental_end'])); ?>
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
                            $daysText = '🟠 ' . $daysLeft . ' ngày nữa';
                        }
                        ?>
                        <span class="rental-days <?php echo $daysClass; ?>"><?php echo $daysText; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; color: #94a3b8; padding: 24px; font-size: 14px;">Khoảng thời gian này không có đơn thuê sắp hạn.</div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>
const ctx = document.getElementById('revenueChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($periodLabels); ?>,
        datasets: [{
            label: 'Doanh thu',
            data: <?php echo json_encode($periodRevenue); ?>,
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            borderWidth: 2,
            tension: 0.3,
            fill: true,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#6366f1',
            pointBorderWidth: 2,
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: '#f1f5f9',
                    drawBorder: false
                },
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString('vi-VN') + ' đ';
                    },
                    font: { size: 11 },
                    color: '#64748b'
                }
            },
            x: {
                grid: { display: false, drawBorder: false },
                ticks: { font: { size: 11 }, color: '#64748b' }
            }
        }
    }
});
</script>

<?php
admin_footer();
