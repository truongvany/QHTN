<?php
/**
 * Admin API: Mark rental item as returned and restore stock
 * POST: order_id, item_id
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

// Check admin access
if (!isset($_SESSION['user_id']) || (isset($_SESSION['role']) && $_SESSION['role'] !== 'admin')) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;

if ($orderId <= 0 || $itemId <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid order or item ID']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Get item details
    $stmt = $pdo->prepare('SELECT * FROM order_details WHERE id = ? AND order_id = ?');
    $stmt->execute([$itemId, $orderId]);
    $item = $stmt->fetch();
    
    if (!$item) {
        throw new Exception('Item not found');
    }
    
    // Update item status to returned
    $stmt = $pdo->prepare('UPDATE order_details SET status = ? WHERE id = ?');
    $stmt->execute(['returned', $itemId]);
    
    // Restore stock if variant exists
    if ($item['variant_id']) {
        $stmt = $pdo->prepare('UPDATE product_variants SET stock = stock + ? WHERE id = ?');
        $stmt->execute([$item['quantity'], $item['variant_id']]);
    }
    
    // Check if all items in order are returned
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM order_details WHERE order_id = ? AND status != ?');
    $stmt->execute([$orderId, 'returned']);
    $unreturned = (int)$stmt->fetchColumn();
    
    // If all items returned, update order status
    if ($unreturned === 0) {
        $stmt = $pdo->prepare('UPDATE orders SET status = ?, returned_at = NOW() WHERE id = ?');
        $stmt->execute(['returned', $orderId]);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Item marked as returned and stock restored',
        'all_returned' => $unreturned === 0
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
