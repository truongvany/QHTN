<?php 
require_once 'config.php';

// Khởi tạo giỏ hàng
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// ====================================================
// LOGIC PHP: GIỮ NGUYÊN 100%
// ====================================================

// 1. Xóa sản phẩm
if (isset($_GET['remove_id'])) {
    $remove_id = intval($_GET['remove_id']);
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['id'] == $remove_id) {
            unset($_SESSION['cart'][$key]);
            break; 
        }
    }
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    header("Location: cart.php");
    exit;
}

// 2. Cập nhật số lượng
if (isset($_POST['update_cart'])) {
    $quantities = $_POST['qty']; 
    foreach ($_SESSION['cart'] as $key => $item) {
        $pid = $item['id'];
        if (isset($quantities[$pid])) {
            $newQty = intval($quantities[$pid]);
            if ($newQty > 0) $_SESSION['cart'][$key]['quantity'] = $newQty;
            else unset($_SESSION['cart'][$key]);
        }
    }
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    header("Location: cart.php");
    exit;
}

require_once 'header.php'; 
$total = 0;
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
    
    .cart-page { max-width: 1100px; margin: 40px auto; padding: 0 20px; min-height: 60vh; }

    /* Stepper (Giống checkout) */
    .stepper { display: flex; justify-content: center; margin-bottom: 40px; gap: 40px; }
    .step { display: flex; align-items: center; gap: 10px; color: #b2bec3; font-weight: 600; font-size: 14px; text-transform: uppercase; }
    .step.active { color: var(--main-color); }
    .step-num { width: 28px; height: 28px; border-radius: 50%; border: 2px solid #b2bec3; display: flex; align-items: center; justify-content: center; font-size: 12px; }
    .step.active .step-num { border-color: var(--main-color); background: var(--main-color); color: #fff; }

    /* Empty State */
    .empty-cart-box { text-align: center; padding: 80px 20px; background: #fff; border-radius: 20px; box-shadow: var(--shadow-soft); }
    .empty-cart-icon { font-size: 80px; color: #dfe6e9; margin-bottom: 20px; }
    .btn-shopping { display: inline-block; padding: 12px 30px; background: var(--text-dark); color: #fff; border-radius: 50px; text-decoration: none; font-weight: 600; transition: 0.3s; margin-top: 20px; }
    .btn-shopping:hover { background: var(--main-color); transform: translateY(-2px); }

    /* Layout 2 cột */
    .cart-layout { display: grid; grid-template-columns: 2.2fr 1fr; gap: 30px; align-items: start; }

    /* Cart Item Styling */
    .cart-items-wrapper { background: transparent; }
    .cart-header-text { font-size: 24px; font-weight: 800; color: var(--text-dark); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    
    .cart-item-row { 
        background: #fff; 
        border-radius: 16px; 
        padding: 20px; 
        margin-bottom: 15px; 
        display: flex; 
        align-items: center; 
        gap: 20px; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.03); 
        transition: transform 0.2s;
        position: relative;
    }
    .cart-item-row:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.06); }

    .c-img { width: 90px; height: 90px; border-radius: 12px; object-fit: cover; background: #f0f0f0; flex-shrink: 0; }
    .c-info { flex: 1; }
    .c-name { font-size: 16px; font-weight: 700; color: var(--text-dark); margin-bottom: 5px; text-decoration: none; display: block; }
    .c-price { color: #636e72; font-size: 14px; }
    
    .c-qty-group { display: flex; flex-direction: column; align-items: center; gap: 5px; }
    .qty-modern { 
        width: 60px; padding: 8px; text-align: center; 
        border: 2px solid #dfe6e9; border-radius: 8px; 
        font-weight: 700; color: var(--text-dark); outline: none; 
        transition: 0.2s;
    }
    .qty-modern:focus { border-color: var(--main-color); }
    
    .c-subtotal { width: 120px; text-align: right; font-weight: 700; color: var(--main-color); font-size: 16px; }
    
    .btn-delete { 
        color: #b2bec3; width: 30px; height: 30px; 
        display: flex; align-items: center; justify-content: center; 
        border-radius: 50%; transition: 0.2s; cursor: pointer;
    }
    .btn-delete:hover { background: #ffe6e6; color: #ff4757; }

    /* Action Bar (Update Btn) */
    .cart-actions { margin-top: 20px; text-align: right; }
    .btn-update-cart { 
        background: transparent; border: 2px solid #dfe6e9; 
        color: #636e72; padding: 10px 20px; border-radius: 8px; 
        font-weight: 600; cursor: pointer; transition: 0.2s; 
    }
    .btn-update-cart:hover { border-color: var(--text-dark); color: var(--text-dark); }

    /* Summary Box (Right Column) */
    .summary-card { 
        background: #fff; padding: 30px; border-radius: 20px; 
        box-shadow: var(--shadow-soft); position: sticky; top: 30px; 
    }
    .s-title { font-size: 18px; font-weight: 800; color: var(--text-dark); margin-bottom: 20px; border-bottom: 2px dashed #dfe6e9; padding-bottom: 15px; }
    .s-row { display: flex; justify-content: space-between; margin-bottom: 15px; color: #636e72; font-size: 15px; }
    .s-total { display: flex; justify-content: space-between; margin-top: 20px; padding-top: 20px; border-top: 2px solid #f0f2f5; font-size: 22px; font-weight: 800; color: var(--text-dark); }
    .s-total span:last-child { color: var(--main-color); }

    .btn-go-checkout { 
        display: block; width: 100%; padding: 18px; 
        background: var(--main-color); color: #fff; text-align: center; 
        border-radius: 12px; font-weight: 700; text-transform: uppercase; 
        margin-top: 25px; text-decoration: none; transition: 0.3s; border: none; 
        box-shadow: 0 10px 20px rgba(255, 71, 87, 0.3);
    }
    .btn-go-checkout:hover { transform: translateY(-3px); box-shadow: 0 15px 25px rgba(255, 71, 87, 0.4); }
    
    .back-link { display: block; text-align: center; margin-top: 15px; font-size: 14px; color: #b2bec3; text-decoration: none; }
    .back-link:hover { color: var(--main-color); }

    /* Responsive */
    @media (max-width: 768px) {
        .cart-layout { grid-template-columns: 1fr; }
        .cart-item-row { flex-wrap: wrap; }
        .c-qty-group { margin-left: auto; }
        .stepper { display: none; }
    }
</style>

<div class="cart-page">

    <?php if(empty($_SESSION['cart'])): ?>
        <div class="empty-cart-box">
            <i class="fas fa-shopping-basket empty-cart-icon"></i>
            <h2 style="color: var(--text-dark);">Giỏ hàng trống trơn!</h2>
            <p style="color: #636e72;">Có vẻ như bạn chưa chọn được món đồ nào ưng ý.</p>
            <a href="index.php" class="btn-shopping">
                <i class="fas fa-arrow-left"></i> Quay lại cửa hàng
            </a>
        </div>
    
    <?php else: ?>
        
        <div class="stepper">
            <div class="step active">
                <div class="step-num">1</div>
                <span>Giỏ hàng</span>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <span>Thanh toán</span>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <span>Hoàn tất</span>
            </div>
        </div>

        <div class="cart-layout">
            
            <form method="POST" action="cart.php" class="cart-items-wrapper">
                <div class="cart-header-text">
                    Giỏ hàng của bạn <span style="font-size: 16px; color: #b2bec3; font-weight: 600;">(<?= count($_SESSION['cart']) ?> món)</span>
                </div>

                <?php foreach($_SESSION['cart'] as $item): 
                    $id = $item['id'];
                        $days = isset($item['duration_days']) ? max(1, intval($item['duration_days'])) : 1;
                        $price = isset($item['price']) ? intval($item['price']) : 0;
                        $qty = isset($item['quantity']) ? intval($item['quantity']) : 1;
                    $name = isset($item['name']) ? $item['name'] : 'Sản phẩm';
                        $subtotal = $price * $qty * $days;
                    $total += $subtotal;

                    // Xử lý ảnh (Logic cũ)
                    $raw_img = isset($item['image']) ? $item['image'] : '';
                    $filename = basename($raw_img); 
                    $img_show = (empty($filename) || $filename == 'default.jpg') ? 'img/default.jpg' : 'img/' . $filename;
                ?>
                <div class="cart-item-row">
                    <img src="<?= htmlspecialchars($img_show) ?>" class="c-img" alt="<?= htmlspecialchars($name) ?>">
                    
                    <div class="c-info">
                        <div class="c-name"><?= htmlspecialchars($name) ?></div>
                        <?php if (!empty($item['variant_size']) || !empty($item['variant_color'])): ?>
                            <div class="c-price" style="color:#ff4757; font-weight:700;">Biến thể: <?= htmlspecialchars(trim(($item['variant_size'] ?? '') . ' ' . ($item['variant_color'] ?? ''))); ?></div>
                        <?php endif; ?>
                        <div class="c-price">Đơn giá: <?= number_format($price) ?>đ / ngày</div>
                        <div class="c-price" style="margin-top:4px;">Ngày thuê: <?= htmlspecialchars($item['rental_start'] ?? '-') ?> → <?= htmlspecialchars($item['rental_end'] ?? '-') ?> (<?= $days ?> ngày)</div>
                    </div>

                    <div class="c-qty-group">
                        <label style="font-size: 11px; text-transform: uppercase; color: #b2bec3; font-weight: 700;">Số lượng</label>
                        <input type="number" name="qty[<?= $id ?>]" value="<?= $qty ?>" min="1" class="qty-modern">
                    </div>

                    <div class="c-subtotal">
                        <?= number_format($subtotal) ?>đ
                    </div>

                    <a href="cart.php?remove_id=<?= $id ?>" class="btn-delete" title="Xóa sản phẩm" onclick="return confirm('Bạn chắc chắn muốn xóa khỏi giỏ hàng?')">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
                <?php endforeach; ?>
                
                <div class="cart-actions">
                    <button type="submit" name="update_cart" class="btn-update-cart">
                        <i class="fas fa-sync-alt"></i> Cập nhật giỏ hàng
                    </button>
                </div>
            </form>

            <div class="cart-summary">
                <div class="summary-card">
                    <div class="s-title">Thông tin thanh toán</div>
                    
                    <div class="s-row">
                        <span>Tạm tính</span>
                        <span><?= number_format($total) ?>đ</span>
                    </div>
                    <div class="s-row">
                        <span>Giảm giá</span>
                        <span style="color: #00b894;">0đ</span>
                    </div>
                    
                    <div class="s-total">
                        <span>Tổng cộng</span>
                        <span><?= number_format($total) ?>đ</span>
                    </div>

                    <a href="checkout.php" class="btn-go-checkout">
                        TIẾN HÀNH ĐẶT THUÊ
                    </a>
                    
                    <a href="index.php" class="back-link">
                        Tiếp tục xem thêm đồ
                    </a>
                </div>
            </div>

        </div>

    <?php endif; ?>
</div>