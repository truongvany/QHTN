<?php
require_once __DIR__ . '/../init.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Order ID required']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT admin_notes FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Order not found']);
        exit;
    }
    
    echo json_encode([
        'status' => 'success',
        'notes' => $order['admin_notes'] ?? ''
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
