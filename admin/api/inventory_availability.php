<?php
/**
 * Admin API: Get inventory availability for a product variant
 * GET: product_id, variant_id (optional), start_date, end_date
 */
require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

// Check admin access
if (!isset($_SESSION['user_id']) || (isset($_SESSION['role']) && $_SESSION['role'] !== 'admin')) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$variantId = isset($_GET['variant_id']) ? (int)$_GET['variant_id'] : 0;
$startDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$endDate = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

if ($productId <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid product ID']);
    exit;
}

try {
    // If variant not specified, get first available
    if ($variantId <= 0) {
        $stmt = $pdo->prepare('SELECT id, stock FROM product_variants WHERE product_id = ? LIMIT 1');
        $stmt->execute([$productId]);
        $variant = $stmt->fetch();
        if (!$variant) {
            echo json_encode([
                'status' => 'error',
                'message' => 'No variants found',
                'variants' => []
            ]);
            exit;
        }
        $variantId = $variant['id'];
        $totalStock = $variant['stock'];
    } else {
        // Verify variant belongs to product
        $stmt = $pdo->prepare('SELECT stock FROM product_variants WHERE id = ? AND product_id = ?');
        $stmt->execute([$variantId, $productId]);
        $variant = $stmt->fetch();
        if (!$variant) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Variant not found']);
            exit;
        }
        $totalStock = $variant['stock'];
    }
    
    // Get all variants for this product
    $stmt = $pdo->prepare('SELECT id, size, color, stock FROM product_variants WHERE product_id = ? ORDER BY size, color');
    $stmt->execute([$productId]);
    $variants = $stmt->fetchAll();
    
    // Get booked dates for this variant
    $sql = 'SELECT rental_start, rental_end, quantity FROM order_details 
            WHERE variant_id = ? 
            AND status IN ("in-use", "collected", "in-transit")
            AND rental_start IS NOT NULL 
            AND rental_end IS NOT NULL';
    $params = [$variantId];
    
    if ($startDate && $endDate) {
        $sql .= ' AND NOT (rental_end < ? OR rental_start > ?)';
        $params[] = $startDate;
        $params[] = $endDate;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
    
    // Build availability calendar data
    $bookedDates = [];
    $totalBooked = 0;
    
    foreach ($bookings as $booking) {
        $start = new DateTime($booking['rental_start']);
        $end = new DateTime($booking['rental_end']);
        
        $current = clone $start;
        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');
            if (!isset($bookedDates[$dateStr])) {
                $bookedDates[$dateStr] = 0;
            }
            $bookedDates[$dateStr] += $booking['quantity'];
            $current->modify('+1 day');
        }
        
        $totalBooked = max($totalBooked, $booking['quantity']);
    }
    
    echo json_encode([
        'status' => 'success',
        'product_id' => $productId,
        'variant_id' => $variantId,
        'total_stock' => $totalStock,
        'booked_dates' => $bookedDates,
        'variants' => $variants,
        'summary' => [
            'available' => $totalStock,
            'booked' => $totalBooked
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
