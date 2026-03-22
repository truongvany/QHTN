<?php
require_once 'config.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Check if file was uploaded
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'Không có tệp hoặc lỗi tải lên']);
    exit;
}

$file = $_FILES['avatar'];

// Validate file type
$allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$fileMime = mime_content_type($file['tmp_name']);
if (!in_array($fileMime, $allowedMimes)) {
    echo json_encode(['status' => 'error', 'message' => 'Chỉ chấp nhận ảnh (JPG, PNG, GIF, WebP)']);
    exit;
}

// Validate file size (5MB max)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['status' => 'error', 'message' => 'Kích thước tệp không được vượt quá 5MB']);
    exit;
}

// Create directory if not exists
$uploadDir = 'img/avatars';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Get current avatar to delete old file
$stmt = $conn->prepare('SELECT avatar FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && $user['avatar'] !== 'default.jpg' && file_exists($user['avatar'])) {
    unlink($user['avatar']);
}

// Generate filename
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$ext = strtolower(preg_replace('/[^a-z0-9]/', '', $ext));
$newFilename = $uploadDir . '/' . $userId . '_' . time() . '.' . $ext;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $newFilename)) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi khi lưu tệp']);
    exit;
}

// Update database
$stmt = $conn->prepare('UPDATE users SET avatar = ? WHERE id = ?');
if ($stmt->execute([$newFilename, $userId])) {
    echo json_encode([
        'status' => 'success',
        'avatar_url' => $newFilename,
        'message' => 'Ảnh đại diện cập nhật thành công'
    ]);
} else {
    // Delete file if database update failed
    unlink($newFilename);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi cập nhật cơ sở dữ liệu']);
}
