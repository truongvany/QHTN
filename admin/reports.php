<?php
require_once __DIR__ . '/layout.php';

$period = isset($_GET['period']) ? trim($_GET['period']) : 'monthly';
$startDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? trim($_GET['end_date']) : date('Y-m-d');

// Validate period
if (!in_array($period, ['daily', 'monthly', 'yearly'])) {
    $period = 'monthly';
}

// Build date group query based on period
$dateFormat = match($period) {
    'daily' => '%Y-%m-%d',
    'monthly' => '%Y-%m',
    'yearly' => '%Y'
};

$sql = "
    SELECT 
        DATE_FORMAT(o.created_at, ?) as period,
        COUNT(o.id) as order_count,
        SUM(o.total_price) as revenue,
        MAX(o.total_price) as max_order,
        MIN(o.total_price) as min_order
    FROM orders o
    WHERE o.status != 'cancelled'
        AND DATE(o.created_at) >= ?
        AND DATE(o.created_at) <= ?
    GROUP BY DATE_FORMAT(o.created_at, ?)
    ORDER BY period ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$dateFormat, $startDate, $endDate, $dateFormat]);
$reportData = $stmt->fetchAll();

// Calculate totals
$totalRevenue = 0;
$totalOrders = 0;
$avgOrder = 0;

foreach ($reportData as $row) {
    $totalRevenue += (float)$row['revenue'];
    $totalOrders += (int)$row['order_count'];
}

if ($totalOrders > 0) {
    $avgOrder = $totalRevenue / $totalOrders;
}

// Get top products by revenue
$topProductsSql = "
    SELECT 
        p.id,
        p.name,
        SUM(od.quantity) as total_quantity,
        SUM(od.quantity * od.price) as product_revenue,
        COUNT(DISTINCT od.order_id) as order_count
    FROM order_details od
    LEFT JOIN products p ON od.product_id = p.id
    LEFT JOIN orders o ON od.order_id = o.id
    WHERE o.status != 'cancelled'
        AND DATE(o.created_at) >= ?
        AND DATE(o.created_at) <= ?
    GROUP BY p.id, p.name
    ORDER BY product_revenue DESC
    LIMIT 10
";

$topStmt = $pdo->prepare($topProductsSql);
$topStmt->execute([$startDate, $endDate]);
$topProducts = $topStmt->fetchAll();

admin_header('Báo cáo Doanh thu', 'reports');
?>

<style>
    .report-filters {
        display: flex;
        gap: 12px;
        margin-bottom: 24px;
        align-items: flex-end;
        flex-wrap: wrap;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
    }
    
    .filter-group label {
        font-size: 12px;
        font-weight: 600;
        color: #666;
        margin-bottom: 4px;
    }
    
    .filter-group .input,
    .filter-group select {
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 32px;
    }
    
    .summary-card {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
    }
    
    .summary-card .label {
        font-size: 12px;
        color: #999;
        font-weight: 600;
        margin-bottom: 8px;
    }
    
    .summary-card .value {
        font-size: 28px;
        font-weight: 700;
        color: #333;
    }
    
    .summary-card.accent .value {
        color: #e95a8a;
    }
    
    .chart-container {
        position: relative;
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 24px;
        height: 300px;
    }
    
    .table-container {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        overflow: hidden;
    }
    
    .table-title {
        padding: 16px 20px;
        border-bottom: 1px solid #e0e0e0;
        font-weight: 700;
        font-size: 14px;
    }
    
    .report-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .report-table th {
        background: #f9f9f9;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        font-size: 12px;
        border-bottom: 1px solid #e0e0e0;
        color: #666;
    }
    
    .report-table td {
        padding: 12px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 13px;
    }
    
    .report-table tr:hover {
        background: #f9f9f9;
    }
    
    .export-btn {
        background: #28a745;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
        font-size: 13px;
        display: inline-flex;
        gap: 6px;
        align-items: center;
    }
    
    .export-btn:hover {
        background: #218838;
    }
    
    @media (max-width: 768px) {
        .report-filters {
            flex-direction: column;
        }
        
        .filter-group {
            width: 100%;
        }
        
        .summary-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="card">
    <form method="GET" class="report-filters">
        <div class="filter-group">
            <label>Từ ngày</label>
            <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>" class="input">
        </div>
        <div class="filter-group">
            <label>Đến ngày</label>
            <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>" class="input">
        </div>
        <div class="filter-group">
            <label>Kiểu báo cáo</label>
            <select name="period" class="input">
                <option value="daily" <?php echo $period === 'daily' ? 'selected' : ''; ?>>Hàng ngày</option>
                <option value="monthly" <?php echo $period === 'monthly' ? 'selected' : ''; ?>>Hàng tháng</option>
                <option value="yearly" <?php echo $period === 'yearly' ? 'selected' : ''; ?>>Hàng năm</option>
            </select>
        </div>
        <button type="submit" class="btn primary">🔄 Cập nhật</button>
        <button type="button" class="export-btn" onclick="exportCSV()">📥 Xuất CSV</button>
    </form>
</div>

<!-- Summary Cards -->
<div class="summary-grid">
    <div class="summary-card accent">
        <div class="label">Tổng doanh thu</div>
        <div class="value"><?php echo number_format($totalRevenue, 0, ',', '.'); ?> đ</div>
    </div>
    <div class="summary-card">
        <div class="label">Số đơn hàng</div>
        <div class="value"><?php echo number_format($totalOrders, 0, ',', '.'); ?></div>
    </div>
    <div class="summary-card">
        <div class="label">Trung bình/ đơn</div>
        <div class="value"><?php echo number_format($avgOrder, 0, ',', '.'); ?> đ</div>
    </div>
    <div class="summary-card">
        <div class="label">Thời kỳ</div>
        <div class="value" style="font-size: 14px;">
            <?php echo date('d/m/Y', strtotime($startDate)); ?> -<br>
            <?php echo date('d/m/Y', strtotime($endDate)); ?>
        </div>
    </div>
</div>

<!-- Chart -->
<div class="chart-container">
    <canvas id="revenueChart"></canvas>
</div>

<!-- Period Breakdown Table -->
<div class="table-container">
    <div class="table-title">Doanh thu theo <?php echo $period === 'daily' ? 'ngày' : ($period === 'monthly' ? 'tháng' : 'năm'); ?></div>
    <table class="report-table">
        <tr>
            <th><?php echo $period === 'daily' ? 'Ngày' : ($period === 'monthly' ? 'Tháng' : 'Năm'); ?></th>
            <th style="text-align: right;">Đơn hàng</th>
            <th style="text-align: right;">Doanh thu</th>
            <th style="text-align: right;">Trung bình</th>
            <th style="text-align: right;">Tối thiểu</th>
            <th style="text-align: right;">Tối đa</th>
        </tr>
        <?php 
        $periodLabels = [];
        $periodRevenue = [];
        
        foreach ($reportData as $row): 
            $avgForPeriod = $row['order_count'] > 0 ? (float)$row['revenue'] / (int)$row['order_count'] : 0;
            $periodLabels[] = $row['period'];
            $periodRevenue[] = (float)$row['revenue'];
            
            $displayPeriod = $row['period'];
            if ($period === 'daily') {
                $displayPeriod = date('d/m/Y', strtotime($displayPeriod));
            } elseif ($period === 'monthly') {
                $displayPeriod = date('m/Y', strtotime($displayPeriod . '-01'));
            }
        ?>
            <tr>
                <td><?php echo htmlspecialchars($displayPeriod); ?></td>
                <td style="text-align: right;"><?php echo number_format($row['order_count'], 0, ',', '.'); ?></td>
                <td style="text-align: right;"><?php echo number_format($row['revenue'], 0, ',', '.'); ?> đ</td>
                <td style="text-align: right;"><?php echo number_format($avgForPeriod, 0, ',', '.'); ?> đ</td>
                <td style="text-align: right;"><?php echo number_format($row['min_order'], 0, ',', '.'); ?> đ</td>
                <td style="text-align: right;"><?php echo number_format($row['max_order'], 0, ',', '.'); ?> đ</td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<!-- Top Products Table -->
<div class="table-container" style="margin-top: 24px;">
    <div class="table-title">Top 10 sản phẩm theo doanh thu</div>
    <table class="report-table">
        <tr>
            <th>Sản phẩm</th>
            <th style="text-align: right;">Số lượng</th>
            <th style="text-align: right;">Doanh thu</th>
            <th style="text-align: right;">Đơn hàng</th>
        </tr>
        <?php foreach ($topProducts as $product): ?>
            <tr>
                <td><?php echo htmlspecialchars($product['name'] ?? 'Unknown'); ?></td>
                <td style="text-align: right;"><?php echo number_format($product['total_quantity'], 0, ',', '.'); ?></td>
                <td style="text-align: right;"><?php echo number_format($product['product_revenue'], 0, ',', '.'); ?> đ</td>
                <td style="text-align: right;"><?php echo number_format($product['order_count'], 0, ',', '.'); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>
// Chart initialization
const ctx = document.getElementById('revenueChart').getContext('2d');
const periodLabels = <?php echo json_encode($periodLabels); ?>;
const periodRevenue = <?php echo json_encode($periodRevenue); ?>;

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: periodLabels,
        datasets: [{
            label: 'Doanh thu (đ)',
            data: periodRevenue,
            backgroundColor: '#e95a8a',
            borderColor: '#c2185b',
            borderWidth: 1,
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString('vi-VN') + ' đ';
                    }
                }
            }
        }
    }
});

// CSV export function
function exportCSV() {
    const startDate = document.querySelector('input[name="start_date"]').value;
    const endDate = document.querySelector('input[name="end_date"]').value;
    const period = document.querySelector('select[name="period"]').value;
    
    let csv = 'Báo cáo doanh thu\n';
    csv += 'Từ: ' + startDate + ', Đến: ' + endDate + '\n';
    csv += 'Kiểu báo cáo: ' + period + '\n\n';
    
    csv += 'Tóm tắt\n';
    csv += 'Tổng doanh thu,' + <?php echo $totalRevenue; ?> + '\n';
    csv += 'Số đơn hàng,' + <?php echo $totalOrders; ?> + '\n';
    csv += 'Trung bình/đơn,' + <?php echo $avgOrder; ?> + '\n\n';
    
    csv += 'Chi tiết theo ' + period + '\n';
    csv += 'Kỳ,Đơn hàng,Doanh thu,Trung bình,Tối thiểu,Tối đa\n';
    
    const rows = document.querySelectorAll('.report-table tbody tr');
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        const values = [];
        cells.forEach(cell => {
            values.push('"' + cell.textContent.trim().replace(/"/g, '""') + '"');
        });
        csv += values.join(',') + '\n';
    });
    
    // Download CSV
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'revenue_report_' + new Date().toISOString().split('T')[0] + '.csv';
    link.click();
}
</script>

<?php
admin_footer();
