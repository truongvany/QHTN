<?php 
require_once 'config.php'; 

// ====================================================
// LOGIC PHP: GIỮ NGUYÊN KHÔNG THAY ĐỔI
// ====================================================

// 1. KIỂM TRA ĐĂNG NHẬP
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. KIỂM TRA GIỎ HÀNG
if (empty($_SESSION['cart']) && !isset($_SESSION['last_order_id'])) {
    header("Location: index.php");
    exit();
}

$showSuccess = false;
$error = "";
$total = 0;

// Tính tổng tiền hiển thị (theo số ngày thuê)
if(!empty($_SESSION['cart'])) {
    foreach($_SESSION['cart'] as $item) {
        $days = isset($item['duration_days']) ? max(1, intval($item['duration_days'])) : 1;
        $total += $item['price'] * $item['quantity'] * $days;
    }
}

function bookedQuantityForVariant(PDO $conn, int $variantId, string $startDate, string $endDate): int {
        $sql = "SELECT COALESCE(SUM(od.quantity),0) FROM order_details od
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

// XỬ LÝ ĐẶT HÀNG
if (isset($_POST['checkout']) && !empty($_SESSION['cart'])) {
    
    $user_id  = $_SESSION['user_id'];
    $fullname = $_POST['fullname']; 
    $phone    = $_POST['phone'];
    $address  = $_POST['address'];
    $note     = $_POST['note'];
    
    // Gộp thông tin vào cột note
    $full_note_for_db = "Người nhận: $fullname | SĐT: $phone | ĐC: $address | Note: $note";

    // A. BẢO MẬT: TÍNH LẠI TỔNG TIỀN TỪ DATABASE
    $cart_ids = array_column($_SESSION['cart'], 'id');
    
    if(empty($cart_ids)) {
        header("Location: index.php");
        exit;
    }

    $ids_placeholder = implode(',', array_fill(0, count($cart_ids), '?'));
    $stmt = $conn->prepare("SELECT * FROM products WHERE id IN ($ids_placeholder)");
    $stmt->execute($cart_ids);
    $db_products = $stmt->fetchAll(PDO::FETCH_ASSOC); 
    
    $product_map = [];
    foreach($db_products as $p) {
        $product_map[$p['id']] = $p;
    }

    // Lấy danh sách variant cần thiết
    $variantIds = [];
    foreach ($_SESSION['cart'] as $item) {
        if (!empty($item['variant_id'])) {
            $variantIds[] = (int)$item['variant_id'];
        }
    }
    $variant_map = [];
    if (!empty($variantIds)) {
        $place = implode(',', array_fill(0, count($variantIds), '?'));
        $vstmt = $conn->prepare("SELECT * FROM product_variants WHERE id IN ($place)");
        $vstmt->execute($variantIds);
        $variants = $vstmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($variants as $v) { $variant_map[$v['id']] = $v; }
    }

    $real_total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $pid = $item['id'];
        $days = isset($item['duration_days']) ? max(1, intval($item['duration_days'])) : 1;
        if(isset($product_map[$pid])) {
            $priceUse = $product_map[$pid]['price'];
            if (!empty($item['variant_id']) && isset($variant_map[$item['variant_id']]) && $variant_map[$item['variant_id']]['price_override'] !== null && $variant_map[$item['variant_id']]['price_override'] !== '') {
                $priceUse = (int)$variant_map[$item['variant_id']]['price_override'];
            }
            $real_total += $priceUse * $item['quantity'] * $days;
        }
    }

    // B. GHI VÀO DATABASE
    try {
        $conn->beginTransaction();

        // 1. INSERT VÀO BẢNG ORDERS
        $sql_order = "INSERT INTO orders (user_id, total_price, status, note, created_at) 
                      VALUES (?, ?, 'pending', ?, NOW())";
        
        $stmt = $conn->prepare($sql_order);
        $stmt->execute([$user_id, $real_total, $full_note_for_db]);
        $order_id = $conn->lastInsertId();

        // 2. INSERT VÀO BẢNG ORDER_DETAILS
        $sql_detail = "INSERT INTO order_details (order_id, product_id, variant_id, quantity, price, rental_start, rental_end, duration_days) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_detail = $conn->prepare($sql_detail);
        foreach ($_SESSION['cart'] as $item) {
            $pid = $item['id'];
            if(isset($product_map[$pid])) {
                $p_db = $product_map[$pid];
                $variantId = $item['variant_id'] ?? null;
                $variantRow = $variantId && isset($variant_map[$variantId]) ? $variant_map[$variantId] : null;
                $priceUse = $p_db['price'];
                if ($variantRow && $variantRow['price_override'] !== null && $variantRow['price_override'] !== '') {
                    $priceUse = (int)$variantRow['price_override'];
                }
                $days = isset($item['duration_days']) ? max(1, intval($item['duration_days'])) : 1;
                $startDate = isset($item['rental_start']) ? $item['rental_start'] : null;
                $endDate = isset($item['rental_end']) ? $item['rental_end'] : null;

                if ($startDate && $endDate) {
                    if ($variantRow) {
                        $booked = bookedQuantityForVariant($conn, $variantRow['id'], $startDate, $endDate);
                        $stockAvail = (int)$variantRow['stock'];
                        if (($booked + $item['quantity']) > $stockAvail) {
                            throw new Exception('Biến thể đã hết hàng trong thời gian này.');
                        }
                    } else {
                        if (hasProductConflictNoVariant($conn, $pid, $startDate, $endDate)) {
                            throw new Exception('Sản phẩm đã được đặt trong khoảng thời gian bạn chọn.');
                        }
                    }
                }

                $stmt_detail->execute([
                    $order_id, 
                    $pid,
                    $variantId,
                    $item['quantity'], 
                    $priceUse,
                    $startDate,
                    $endDate,
                    $days
                ]);

                // Trừ kho biến thể nếu có
                if ($variantRow) {
                    $upd = $conn->prepare("UPDATE product_variants SET stock = stock - ? WHERE id = ? AND stock >= ?");
                    $upd->execute([$item['quantity'], $variantRow['id'], $item['quantity']]);
                    if ($upd->rowCount() === 0) {
                        throw new Exception('Kho không đủ cho biến thể được chọn.');
                    }
                }
            }
        }

        $conn->commit();

        $_SESSION['last_order_id'] = $order_id;
        $_SESSION['last_order_info'] = [
            'fullname' => $fullname,
            'phone'    => $phone,
            'address'  => $address,
            'total'    => $real_total,
            'items'    => $_SESSION['cart']
        ];

        unset($_SESSION['cart']);
        header("Location: checkout.php?success=1");
        exit;

    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Lỗi hệ thống: " . $e->getMessage();
    }
}

if (isset($_GET['success']) && isset($_SESSION['last_order_id'])) {
    $showSuccess = true;
}

require_once 'header.php'; 
?>

<style>
    :root { 
        --main-color: #ff4757; 
        --bg-body: #f4f6f8;
        --input-bg: #f0f2f5;
        --text-dark: #2d3436;
        --shadow-soft: 0 10px 40px rgba(0,0,0,0.08);
    }
    
    body { background-color: var(--bg-body); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    
    /* Layout chung */
    .checkout-page { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
    
    /* Stepper (Thanh tiến trình) */
    .stepper { display: flex; justify-content: center; margin-bottom: 40px; gap: 40px; }
    .step { display: flex; align-items: center; gap: 10px; color: #b2bec3; font-weight: 600; font-size: 14px; text-transform: uppercase; }
    .step.active { color: var(--main-color); }
    .step-num { width: 28px; height: 28px; border-radius: 50%; border: 2px solid #b2bec3; display: flex; align-items: center; justify-content: center; font-size: 12px; }
    .step.active .step-num { border-color: var(--main-color); background: var(--main-color); color: #fff; }

    /* Layout 2 cột mới */
    .checkout-layout { display: grid; grid-template-columns: 1.4fr 1fr; gap: 40px; align-items: start; }
    
    /* Style Form bên trái */
    .form-header h2 { margin: 0 0 10px; font-size: 24px; color: var(--text-dark); }
    .form-header p { margin: 0 0 30px; color: #636e72; font-size: 14px; }
    
    .field-group { margin-bottom: 20px; }
    .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    
    .field-label { display: block; font-size: 13px; font-weight: 700; color: #636e72; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
    
    /* Input kiểu mới: Filled Style */
    .input-modern { 
        width: 100%; padding: 16px; 
        background: var(--input-bg); 
        border: 2px solid transparent; 
        border-radius: 12px; 
        font-size: 15px; 
        color: var(--text-dark); 
        transition: all 0.2s ease;
    }
    .input-modern:focus { background: #fff; border-color: var(--main-color); box-shadow: 0 5px 15px rgba(255, 71, 87, 0.1); outline: none; }
    textarea.input-modern { resize: vertical; min-height: 100px; }

    /* Style khối bên phải (Hóa đơn) */
    .order-receipt { 
        background: #fff; 
        padding: 30px; 
        border-radius: 20px; 
        box-shadow: var(--shadow-soft); 
        position: sticky; 
        top: 30px; 
    }
    .receipt-header { border-bottom: 2px dashed #dfe6e9; padding-bottom: 20px; margin-bottom: 20px; }
    .receipt-title { font-size: 18px; font-weight: 800; color: var(--text-dark); }
    
    .cart-list { max-height: 350px; overflow-y: auto; padding-right: 5px; }
    .cart-item { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; }
    .item-img { width: 64px; height: 64px; border-radius: 12px; object-fit: cover; background: #f0f0f0; }
    .item-details { flex: 1; }
    .item-name { font-weight: 600; font-size: 14px; color: var(--text-dark); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;}
    .item-meta { font-size: 12px; color: #636e72; margin-top: 4px; }
    .item-price { font-weight: 700; color: var(--main-color); font-size: 14px; }

    .price-summary { border-top: 2px dashed #dfe6e9; padding-top: 20px; margin-top: 10px; }
    .summary-row { display: flex; justify-content: space-between; font-size: 15px; color: #636e72; margin-bottom: 10px; }
    .total-row { display: flex; justify-content: space-between; font-size: 22px; font-weight: 800; color: var(--text-dark); margin-top: 20px; }
    .total-row span:last-child { color: var(--main-color); }

    .btn-confirm { 
        width: 100%; 
        padding: 18px; 
        background: var(--main-color); 
        color: #fff; 
        border: none; 
        border-radius: 12px; 
        font-size: 16px; 
        font-weight: 700; 
        cursor: pointer; 
        margin-top: 25px; 
        box-shadow: 0 10px 20px rgba(255, 71, 87, 0.3);
        transition: transform 0.2s;
    }
    .btn-confirm:hover { transform: translateY(-3px); box-shadow: 0 15px 25px rgba(255, 71, 87, 0.4); }
    .back-link { display: block; text-align: center; margin-top: 15px; color: #b2bec3; font-size: 14px; text-decoration: none; }
    .back-link:hover { color: var(--main-color); }

    /* Success Page Style */
    .success-card { 
        background: #fff; max-width: 500px; margin: 0 auto; padding: 50px 30px; text-align: center; border-radius: 20px; box-shadow: var(--shadow-soft); 
    }
    .success-icon { 
        width: 80px; height: 80px; background: #e0ffe4; color: #00b894; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; margin: 0 auto 20px; 
    }
    .bill-info { background: #f9f9f9; padding: 20px; border-radius: 12px; text-align: left; margin: 30px 0; font-size: 14px; color: #555; }
    .bill-line { display: flex; justify-content: space-between; margin-bottom: 8px; border-bottom: 1px dashed #eee; padding-bottom: 8px;}

    /* Responsive */
    @media (max-width: 900px) {
        .checkout-layout { grid-template-columns: 1fr; }
        .order-receipt { position: static; margin-bottom: 30px; order: -1; } /* Đẩy giỏ hàng lên trên mobile */
        .stepper { display: none; } /* Ẩn stepper trên mobile cho gọn */
    }
</style>

<div class="checkout-page">

    <?php if ($showSuccess): $lastOrder = $_SESSION['last_order_info']; ?>
        <div class="success-card">
            <div class="success-icon"><i class="fas fa-check"></i></div>
            <h2 style="color: #2d3436; margin-bottom: 10px;">Đặt hàng thành công!</h2>
            <p style="color: #b2bec3;">Cảm ơn bạn, đơn hàng #<?= $_SESSION['last_order_id'] ?> đang được xử lý.</p>

            <div class="bill-info">
                <div class="bill-line">
                    <span>Khách hàng:</span> 
                    <strong><?= htmlspecialchars($lastOrder['fullname']) ?></strong>
                </div>
                <div class="bill-line">
                    <span>Số điện thoại:</span> 
                    <strong><?= htmlspecialchars($lastOrder['phone']) ?></strong>
                </div>
                <div class="bill-line" style="border:none;">
                    <span>Tổng thanh toán:</span> 
                    <strong style="color: var(--main-color); font-size: 16px;"><?= number_format($lastOrder['total']) ?>đ</strong>
                </div>
            </div>

            <a href="index.php" class="btn-confirm" style="text-decoration: none; display: inline-block;">
                Tiếp tục mua sắm
            </a>
        </div>
    
    <?php else: ?>

        <div class="stepper">
            <div class="step">
                <div class="step-num"><i class="fas fa-check"></i></div>
                <span>Giỏ hàng</span>
            </div>
            <div class="step active">
                <div class="step-num">2</div>
                <span>Thanh toán</span>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <span>Hoàn tất</span>
            </div>
        </div>

        <form method="POST" class="checkout-layout">
            
            <div class="col-form">
                <div class="form-header">
                    <h2>Thông tin giao hàng</h2>
                    <p>Vui lòng điền chính xác để chúng tôi giao hàng sớm nhất.</p>
                </div>

                <?php if($error): ?>
                    <div style="background: #ff7675; color: white; padding: 15px; border-radius: 8px; margin-bottom: 25px;">
                        <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
                    </div>
                <?php endif; ?>

                <div class="field-row">
                    <div class="field-group">
                        <label class="field-label">Họ và tên</label>
                        <input type="text" name="fullname" class="input-modern" required 
                               value="<?= isset($_SESSION['username']) ? $_SESSION['username'] : '' ?>" 
                               placeholder="Ví dụ: Nguyễn Văn A">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Số điện thoại (*)</label>
                        <input type="tel" name="phone" class="input-modern" required placeholder="09xxx...">
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label">Địa chỉ nhận hàng</label>
                    <input type="text" name="address" class="input-modern" required placeholder="Số nhà, đường, phường/xã, quận/huyện...">
                </div>

                <div class="field-group">
                    <label class="field-label">Ghi chú đơn hàng</label>
                    <textarea name="note" class="input-modern" placeholder="Ví dụ: Giao hàng giờ hành chính, gọi trước khi giao..."></textarea>
                </div>
            </div>

            <div class="col-receipt">
                <div class="order-receipt">
                    <div class="receipt-header">
                        <div class="receipt-title">Đơn hàng của bạn</div>
                        <div style="font-size: 13px; color: #b2bec3;"><?= count($_SESSION['cart']) ?> sản phẩm</div>
                    </div>

                    <div class="cart-list">
                        <?php foreach($_SESSION['cart'] as $item): 
                            $raw_img = isset($item['image']) ? $item['image'] : '';
                            $filename = basename($raw_img); 
                            $img_show = (empty($filename) || $filename == 'default.jpg') ? 'img/default.jpg' : 'img/' . $filename;
                        ?>
                        <div class="cart-item">
                            <img src="<?= htmlspecialchars($img_show) ?>" class="item-img">
                            <div class="item-details">
                                <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                                <div class="item-meta">SL: <?= $item['quantity'] ?></div>
                            </div>
                            <div class="item-price"><?= number_format($item['price'] * $item['quantity']) ?>đ</div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="price-summary">
                        <div class="summary-row">
                            <span>Tạm tính</span>
                            <span><?= number_format($total) ?>đ</span>
                        </div>
                        <div class="summary-row">
                            <span>Phí vận chuyển</span>
                            <span style="color: #00b894;">Miễn phí</span>
                        </div>
                        <div class="total-row">
                            <span>Tổng cộng</span>
                            <span><?= number_format($total) ?>đ</span>
                        </div>
                    </div>

                    <button type="submit" name="checkout" class="btn-confirm">
                        THANH TOÁN NGAY
                    </button>
                    
                    <a href="cart.php" class="back-link">Quay lại giỏ hàng</a>
                </div>
            </div>

        </form>

    <?php endif; ?>
</div>