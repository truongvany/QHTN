<?php
require_once 'config.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Get posted data
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$username = isset($_POST['username']) ? trim($_POST['username']) : '';

// Validate input
if (empty($email) || empty($username)) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng điền đầy đủ thông tin']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Email không hợp lệ']);
    exit;
}

// Check email uniqueness (excluding current user)
$stmt = $conn->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
$stmt->execute([$email, $userId]);
if ($stmt->fetchColumn()) {
    echo json_encode(['status' => 'error', 'message' => 'Email này đã được sử dụng']);
    exit;
}

// Check username uniqueness (excluding current user)
$stmt = $conn->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
$stmt->execute([$username, $userId]);
if ($stmt->fetchColumn()) {
    echo json_encode(['status' => 'error', 'message' => 'Tên người dùng này đã được sử dụng']);
    exit;
}

// Update user
$stmt = $conn->prepare('UPDATE users SET email = ?, phone = ?, username = ? WHERE id = ?');
if ($stmt->execute([$email, $phone, $username, $userId])) {
    // Update session to reflect new username
    $_SESSION['username'] = $username;
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Hồ sơ cập nhật thành công',
        'username' => $username,
        'email' => $email,
        'phone' => $phone
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi cập nhật dữ liệu']);
}
