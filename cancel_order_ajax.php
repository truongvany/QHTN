<?php
// cancel_order_ajax.php — Hủy đơn hàng (chỉ đơn "pending")
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$userId  = (int)$_SESSION['user_id'];
$orderId = (int)($_POST['order_id'] ?? 0);

if ($orderId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Mã đơn không hợp lệ']);
    exit;
}

// Verify order belongs to user and is cancellable
$stmt = $conn->prepare("SELECT id, status FROM orders WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy đơn hàng']);
    exit;
}

if (!in_array($order['status'], ['pending', 'confirmed'])) {
    echo json_encode(['status' => 'error', 'message' => 'Chỉ có thể hủy đơn đang chờ xác nhận hoặc đã xác nhận']);
    exit;
}

// Perform cancel
$stmtUpdate = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ? AND user_id = ?");
if ($stmtUpdate->execute([$orderId, $userId])) {
    echo json_encode(['status' => 'success', 'message' => 'Đơn hàng đã được hủy thành công']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Không thể hủy đơn, vui lòng thử lại']);
}
