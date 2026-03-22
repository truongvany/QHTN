<?php
// Kiểm tra xem session đã bật chưa, nếu chưa mới bật
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$dbname = 'qhtn_fashion';
$username = 'root';
$password = ''; // Mật khẩu trống (mặc định XAMPP)

try {
    // Đổi utf8 thành utf8mb4 để hỗ trợ Emoji và Tiếng Việt tốt nhất
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Cấu hình báo lỗi và chế độ fetch mặc định
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Provide legacy `$pdo` variable for admin pages expecting `$pdo`
    $pdo = $conn;
    
} catch(PDOException $e) {
    // Ở môi trường thật, nên ghi log thay vì in lỗi ra màn hình để tránh lộ đường dẫn file
    die("Lỗi kết nối cơ sở dữ liệu. Vui lòng thử lại sau."); 
    // die("Lỗi: " . $e->getMessage()); // Chỉ mở dòng này khi đang sửa lỗi (Debug)
}
?>