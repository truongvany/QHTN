<?php
/**
 * Admin API: Update order or item status with optional admin notes
 * POST: order_id, status, admin_notes (optional), item_id (optional)
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
$newStatus = isset($_POST['status']) ? trim($_POST['status']) : '';
$adminNotes = isset($_POST['admin_notes']) ? trim($_POST['admin_notes']) : '';
$itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;

// Validate
if ($orderId <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid order ID']);
    exit;
}

$validStatuses = ['pending', 'confirmed', 'ongoing', 'returned', 'cancelled'];
if (!in_array($newStatus, $validStatuses)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid status']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Verify order exists
    $stmt = $pdo->prepare('SELECT id FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);
    if (!$stmt->fetch()) {
        throw new Exception('Order not found');
    }
    
    // Update order status and admin notes
    $sql = 'UPDATE orders SET status = ?';
    $params = [$newStatus];
    
    if ($adminNotes !== '') {
        $sql .= ', admin_notes = ?';
        $params[] = $adminNotes;
    }
    
    $sql .= ' WHERE id = ?';
    $params[] = $orderId;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // If item_id provided, update item status also
    if ($itemId > 0) {
        $itemStatuses = ['pending', 'collected', 'in-transit', 'in-use', 'returned'];
        // Map order status to item status if applicable
        $itemStatus = $newStatus === 'ongoing' ? 'in-use' : $newStatus;
        
        if (in_array($itemStatus, $itemStatuses)) {
            $stmt = $pdo->prepare('UPDATE order_details SET status = ? WHERE id = ? AND order_id = ?');
            $stmt->execute([$itemStatus, $itemId, $orderId]);
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Order updated successfully'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
