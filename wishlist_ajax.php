<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Chưa đăng nhập.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// ── Create wishlists table if not exists (auto-migration) ──
$conn->exec("
    CREATE TABLE IF NOT EXISTS wishlists (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_user_product (user_id, product_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

if ($action === 'remove') {
    $productId = (int)($_POST['product_id'] ?? 0);
    if ($productId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Sản phẩm không hợp lệ.']);
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM wishlists WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$userId, $productId]);
    echo json_encode(['status' => 'success', 'message' => 'Đã xóa khỏi danh sách yêu thích.']);

} elseif ($action === 'toggle') {
    $productId = (int)($_POST['product_id'] ?? 0);
    if ($productId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Sản phẩm không hợp lệ.']);
        exit;
    }
    // Check if already in wishlist
    $stmtCheck = $conn->prepare("SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?");
    $stmtCheck->execute([$userId, $productId]);
    if ($stmtCheck->fetch()) {
        // Remove
        $conn->prepare("DELETE FROM wishlists WHERE user_id = ? AND product_id = ?")->execute([$userId, $productId]);
        echo json_encode(['status' => 'removed', 'message' => 'Đã xóa khỏi yêu thích.']);
    } else {
        // Add
        $conn->prepare("INSERT IGNORE INTO wishlists (user_id, product_id) VALUES (?, ?)")->execute([$userId, $productId]);
        echo json_encode(['status' => 'added', 'message' => 'Đã thêm vào yêu thích.']);
    }

} elseif ($action === 'clear') {
    $conn->prepare("DELETE FROM wishlists WHERE user_id = ?")->execute([$userId]);
    echo json_encode(['status' => 'success', 'message' => 'Đã xóa tất cả sản phẩm yêu thích.']);

} else {
    echo json_encode(['status' => 'error', 'message' => 'Hành động không hợp lệ.']);
}
