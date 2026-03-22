<?php
/**
 * Admin API: Get revenue report data by period
 * GET: period (daily/monthly/yearly), start_date, end_date
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

// Check admin access
if (!isset($_SESSION['user_id']) || (isset($_SESSION['role']) && $_SESSION['role'] !== 'admin')) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$period = isset($_GET['period']) ? trim($_GET['period']) : 'daily';
$startDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$endDate = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

// Validate period
if (!in_array($period, ['daily', 'monthly', 'yearly'])) {
    $period = 'daily';
}

// If no dates provided, default to current month
if (!$startDate || !$endDate) {
    switch ($period) {
        case 'monthly':
            $startDate = date('Y-01-01');
            $endDate = date('Y-m-d');
            break;
        case 'yearly':
            $startDate = date('Y-01-01');
            $endDate = date('Y-m-d');
            break;
        default: // daily
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-d');
    }
}

try {
    // Determine date format for grouping
    $dateFormat = match ($period) {
        'monthly' => '%Y-%m',
        'yearly' => '%Y',
        default => '%Y-%m-%d'  // daily
    };
    
    $sql = "SELECT 
        DATE_FORMAT(o.created_at, '$dateFormat') AS period,
        COUNT(DISTINCT o.id) AS order_count,
        SUM(o.total_price) AS total_revenue,
        AVG(o.total_price) AS avg_order_value
        FROM orders o
        WHERE o.status NOT IN ('cancelled')
        AND DATE(o.created_at) BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(o.created_at, '$dateFormat')
        ORDER BY o.created_at ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$startDate, $endDate]);
    $data = $stmt->fetchAll();
    
    // Get overall summary
    $sumSql = "SELECT 
        COUNT(DISTINCT id) AS total_orders,
        SUM(total_price) AS total_revenue,
        AVG(total_price) AS avg_order_value,
        MIN(total_price) AS min_order,
        MAX(total_price) AS max_order
        FROM orders
        WHERE status NOT IN ('cancelled')
        AND DATE(created_at) BETWEEN ? AND ?";
    
    $stmt = $pdo->prepare($sumSql);
    $stmt->execute([$startDate, $endDate]);
    $summary = $stmt->fetch();
    
    // Get top products
    $topSql = "SELECT 
        p.id,
        p.name,
        COUNT(od.id) AS rental_count,
        SUM(od.quantity) AS total_quantity,
        SUM(od.price * od.quantity) AS revenue
        FROM products p
        LEFT JOIN order_details od ON p.id = od.product_id
        LEFT JOIN orders o ON od.order_id = o.id
        WHERE o.status NOT IN ('cancelled')
        AND DATE(o.created_at) BETWEEN ? AND ?
        GROUP BY p.id
        ORDER BY revenue DESC
        LIMIT 10";
    
    $stmt = $pdo->prepare($topSql);
    $stmt->execute([$startDate, $endDate]);
    $topProducts = $stmt->fetchAll();
    
    echo json_encode([
        'status' => 'success',
        'period' => $period,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'summary' => [
            'total_orders' => (int)($summary['total_orders'] ?? 0),
            'total_revenue' => (float)($summary['total_revenue'] ?? 0),
            'avg_order_value' => (float)($summary['avg_order_value'] ?? 0),
            'min_order' => (float)($summary['min_order'] ?? 0),
            'max_order' => (float)($summary['max_order'] ?? 0)
        ],
        'data' => $data,
        'top_products' => $topProducts
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
