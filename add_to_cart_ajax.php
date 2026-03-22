<?php
// File: add_to_cart_ajax.php

// 1. Load cấu hình
require_once 'config.php'; 

// Cấu hình header trả về JSON
header('Content-Type: application/json');

// ====================================================
// PHẦN 1: KIỂM TRA ĐĂNG NHẬP
// ====================================================
if (!isset($_SESSION['user_id']) && !isset($_SESSION['username'])) { 
    echo json_encode([
        'status' => 'login_required', 
        'message' => 'Bạn vui lòng đăng nhập để thuê đồ nhé!'
    ]);
    exit;
}

if (!function_exists('bookedQuantityForVariant')) {
    function bookedQuantityForVariant(PDO $conn, int $variantId, string $startDate, string $endDate): int {
        $sql = "SELECT COALESCE(SUM(od.quantity),0) AS qty FROM order_details od
                        JOIN orders o ON od.order_id = o.id
                        WHERE od.variant_id = ?
                            AND od.rental_start IS NOT NULL AND od.rental_end IS NOT NULL
                            AND o.status NOT IN ('cancelled','returned')
                            AND NOT (od.rental_end < ? OR od.rental_start > ?)
                        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$variantId, $startDate, $endDate]);
        return (int)$stmt->fetchColumn();
    }
}

if (!function_exists('hasProductConflictNoVariant')) {
    function hasProductConflictNoVariant(PDO $conn, int $productId, string $startDate, string $endDate): bool {
        $sql = "SELECT 1 FROM order_details od
                        JOIN orders o ON od.order_id = o.id
                        WHERE od.product_id = ?
                            AND od.variant_id IS NULL
                            AND od.rental_start IS NOT NULL AND od.rental_end IS NOT NULL
                            AND o.status NOT IN ('cancelled','returned')
                            AND NOT (od.rental_end < ? OR od.rental_start > ?)
                        LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$productId, $startDate, $endDate]);
        return (bool)$stmt->fetchColumn();
    }
}

// ====================================================
// PHẦN 2: XỬ LÝ
// ====================================================

if(isset($_POST['id'])) {
    
    $id = intval($_POST['id']);
    $variantId = isset($_POST['variant_id']) ? intval($_POST['variant_id']) : null;
    $variantSize = $_POST['variant_size'] ?? '';
    $variantColor = $_POST['variant_color'] ?? '';

    // Cho phép nhận số lượng từ client gửi lên (nếu có), mặc định là 1
    $qty = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    if ($qty < 1) $qty = 1;

    $rentalStart = $_POST['rental_start'] ?? null;
    $rentalEnd = $_POST['rental_end'] ?? null;
    $durationDays = isset($_POST['duration_days']) ? intval($_POST['duration_days']) : null;
    if (!$rentalStart || !$rentalEnd) {
        echo json_encode(['status' => 'error', 'message' => 'Vui lòng chọn ngày nhận và ngày trả.']);
        exit;
    }
    $startTs = strtotime($rentalStart);
    $endTs = strtotime($rentalEnd);
    if (!$startTs || !$endTs || $endTs < $startTs) {
        echo json_encode(['status' => 'error', 'message' => 'Khoảng thời gian không hợp lệ.']);
        exit;
    }
    if ($durationDays === null || $durationDays <= 0) {
        $durationDays = max(1, (int)round(($endTs - $startTs) / 86400) + 1);
    }

    // A. BẢO MẬT: Lấy thông tin sản phẩm
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$product) {
        echo json_encode(['status' => 'error', 'message' => 'Sản phẩm không tồn tại!']);
        exit;
    }

    // Biến thể (nếu có)
    $variant = null;
    $priceUse = (int)$product['price'];
    $stockAvailable = null; // null nghĩa là không giới hạn (chưa khai báo stock)
    if ($variantId) {
        $vstmt = $conn->prepare("SELECT * FROM product_variants WHERE id = ? AND product_id = ?");
        $vstmt->execute([$variantId, $id]);
        $variant = $vstmt->fetch(PDO::FETCH_ASSOC);
        if (!$variant) {
            echo json_encode(['status' => 'error', 'message' => 'Biến thể không tồn tại.']);
            exit;
        }
        $priceUse = ($variant['price_override'] !== null && $variant['price_override'] !== '') ? (int)$variant['price_override'] : (int)$product['price'];
        $stockAvailable = (int)$variant['stock'];
    }

    // --- XỬ LÝ ẢNH (QUAN TRỌNG) ---
    $db_img = $product['image'];
    $filename = basename($db_img);
    $final_img = 'img/' . $filename;
    if (empty($filename) || $filename == 'default.jpg') {
        $final_img = 'img/default.jpg';
    }

    $startDate = date('Y-m-d', $startTs);
    $endDate = date('Y-m-d', $endTs);

    // B. KIỂM TRA TRÙNG LỊCH & TỒN KHO THEO BIẾN THỂ
    if ($variant) {
        $booked = bookedQuantityForVariant($conn, $variant['id'], $startDate, $endDate);
        $inCartSamePeriod = 0;
        if (isset($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                if (($item['variant_id'] ?? null) == $variant['id'] && ($item['rental_start'] ?? null) === $startDate && ($item['rental_end'] ?? null) === $endDate) {
                    $inCartSamePeriod += (int)$item['quantity'];
                }
            }
        }
        if ($stockAvailable !== null && ($booked + $inCartSamePeriod + $qty) > $stockAvailable) {
            echo json_encode(['status' => 'error', 'message' => 'Biến thể này đã hết hàng cho khoảng thời gian chọn.']);
            exit;
        }
    } else {
        // Không có biến thể: vẫn kiểm tra xung đột đặt chồng
        if (hasProductConflictNoVariant($conn, $product['id'], $startDate, $endDate)) {
            echo json_encode(['status' => 'error', 'message' => 'Thời gian này đã có người đặt. Vui lòng chọn ngày khác.']);
            exit;
        }
    }

    // C. KHỞI TẠO GIỎ HÀNG
    if(!isset($_SESSION['cart'])){
        $_SESSION['cart'] = [];
    }

    // D. KIỂM TRA TỒN TẠI (Gộp nếu cùng ngày và cùng biến thể)
    $found = false;
    foreach($_SESSION['cart'] as $key => $item) {
        $sameProduct = $item['id'] == $product['id'];
        $sameVariant = ($item['variant_id'] ?? null) == ($variant['id'] ?? null);
        $sameDate = ($item['rental_start'] ?? null) === $startDate && ($item['rental_end'] ?? null) === $endDate;
        if($sameProduct && $sameVariant && $sameDate) {
            $_SESSION['cart'][$key]['quantity'] += $qty;
            $found = true;
            break;
        }
    }

    // E. THÊM MỚI (Lưu đường dẫn ảnh đã xử lý)
    if(!$found) {
        $_SESSION['cart'][] = [
            'id'            => $product['id'],
            'variant_id'    => $variant['id'] ?? null,
            'variant_size'  => $variantSize ?: ($variant['size'] ?? ''),
            'variant_color' => $variantColor ?: ($variant['color'] ?? ''),
            'name'          => $product['name'],
            'price'         => $priceUse,
            'image'         => $final_img,
            'quantity'      => $qty,
            'rental_start'  => $startDate,
            'rental_end'    => $endDate,
            'duration_days' => $durationDays
        ];
    }

    // F. TÍNH TỔNG
    $totalCount = 0;
    foreach($_SESSION['cart'] as $item) {
        $totalCount += $item['quantity'];
    }

    // G. TRẢ VỀ
    echo json_encode([
        'status'      => 'success',
        'message'     => 'Đã thêm ' . $product['name'] . ' vào giỏ!',
        'total_count' => $totalCount
    ]);

} else {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi dữ liệu.']);
}
?>