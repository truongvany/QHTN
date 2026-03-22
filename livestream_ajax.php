<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Chưa đăng nhập.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'toggle_reminder') {
    $sessionId = (int)($_POST['session_id'] ?? 0);
    if ($sessionId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Phiên không hợp lệ.']);
        exit;
    }

    // Check table exists
    try {
        $stmtCheck = $conn->prepare("SELECT id FROM livestream_reminders WHERE user_id = ? AND session_id = ?");
        $stmtCheck->execute([$userId, $sessionId]);
        if ($stmtCheck->fetch()) {
            $conn->prepare("DELETE FROM livestream_reminders WHERE user_id = ? AND session_id = ?")->execute([$userId, $sessionId]);
            echo json_encode(['status' => 'removed', 'message' => 'Đã hủy nhắc nhở.']);
        } else {
            $conn->prepare("INSERT IGNORE INTO livestream_reminders (user_id, session_id) VALUES (?, ?)")->execute([$userId, $sessionId]);
            echo json_encode(['status' => 'added', 'message' => 'Đã đặt nhắc nhở.']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống.']);
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Hành động không hợp lệ.']);
}
