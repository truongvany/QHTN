<?php
require_once 'config.php';

// ── 1. KIỂM TRA ĐĂNG NHẬP ──
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// ── 2. KIỂM TRA GIỎ HÀNG ──
if (empty($_SESSION['cart']) && !isset($_SESSION['last_order_id'])) {
    header('Location: index.php');
    exit();
}

$showSuccess = false;
$error       = '';
$total       = 0;

// Tính tổng tiền hiển thị
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $days   = max(1, (int)($item['duration_days'] ?? 1));
        $total += $item['price'] * $item['quantity'] * $days;
    }
}

// ── Helper: kiểm tra đặt chỗ (guard chống redeclare) ──
if (!function_exists('bookedQuantityForVariant')) {
    function bookedQuantityForVariant(PDO $conn, int $variantId, string $startDate, string $endDate): int {
        $sql = "SELECT COALESCE(SUM(od.quantity),0) FROM order_details od
                JOIN orders o ON od.order_id = o.id
                WHERE od.variant_id = ?
                  AND od.rental_start IS NOT NULL AND od.rental_end IS NOT NULL
                  AND o.status NOT IN ('cancelled','returned')
                  AND NOT (od.rental_end < ? OR od.rental_start > ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$variantId, $startDate, $endDate]);
        return (int)$stmt->fetchColumn();
    }
}

if (!function_exists('hasProductConflictNoVariant')) {
    function hasProductConflictNoVariant(PDO $conn, int $productId, string $startDate, string $endDate): bool {
        $sql = "SELECT 1 FROM order_details od
                JOIN orders o ON od.order_id = o.id
                WHERE od.product_id = ? AND od.variant_id IS NULL
                  AND od.rental_start IS NOT NULL AND od.rental_end IS NOT NULL
                  AND o.status NOT IN ('cancelled','returned')
                  AND NOT (od.rental_end < ? OR od.rental_start > ?)
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$productId, $startDate, $endDate]);
        return (bool)$stmt->fetchColumn();
    }
}

// ── 3. XỬ LÝ ĐẶT HÀNG ──
if (isset($_POST['checkout']) && !empty($_SESSION['cart'])) {
    $user_id  = $_SESSION['user_id'];
    $fullname = trim($_POST['fullname'] ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $address  = trim($_POST['address']  ?? '');
    $note     = trim($_POST['note']     ?? '');
    $payment  = $_POST['payment_method'] ?? 'cod';

    $full_note_for_db = "Người nhận: $fullname | SĐT: $phone | ĐC: $address | Ghi chú: $note";

    $cart_ids = array_column($_SESSION['cart'], 'id');
    if (empty($cart_ids)) { header('Location: index.php'); exit; }

    $ids_ph   = implode(',', array_fill(0, count($cart_ids), '?'));
    $stmt     = $conn->prepare("SELECT * FROM products WHERE id IN ($ids_ph)");
    $stmt->execute($cart_ids);
    $product_map = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $p) $product_map[$p['id']] = $p;

    $variantIds  = array_filter(array_column($_SESSION['cart'], 'variant_id'));
    $variant_map = [];
    if (!empty($variantIds)) {
        $vph    = implode(',', array_fill(0, count($variantIds), '?'));
        $vstmt  = $conn->prepare("SELECT * FROM product_variants WHERE id IN ($vph)");
        $vstmt->execute(array_values($variantIds));
        foreach ($vstmt->fetchAll(PDO::FETCH_ASSOC) as $v) $variant_map[$v['id']] = $v;
    }

    $real_total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $pid = $item['id'];
        if (isset($product_map[$pid])) {
            $days      = max(1, (int)($item['duration_days'] ?? 1));
            $priceUse  = $product_map[$pid]['price'];
            $vid       = $item['variant_id'] ?? null;
            if ($vid && isset($variant_map[$vid]) && $variant_map[$vid]['price_override'] !== null && $variant_map[$vid]['price_override'] !== '') {
                $priceUse = (int)$variant_map[$vid]['price_override'];
            }
            $real_total += $priceUse * $item['quantity'] * $days;
        }
    }

    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_price, status, payment_method, note, created_at) VALUES (?, ?, 'pending', ?, ?, NOW())");
        $stmt->execute([$user_id, $real_total, $payment, $full_note_for_db]);
        $order_id = $conn->lastInsertId();

        $stmt_detail = $conn->prepare("INSERT INTO order_details (order_id, product_id, variant_id, quantity, price, rental_start, rental_end, duration_days) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($_SESSION['cart'] as $item) {
            $pid = $item['id'];
            if (!isset($product_map[$pid])) continue;
            $p_db      = $product_map[$pid];
            $variantId = $item['variant_id'] ?? null;
            $variantRow= $variantId && isset($variant_map[$variantId]) ? $variant_map[$variantId] : null;
            $priceUse  = $p_db['price'];
            if ($variantRow && $variantRow['price_override'] !== null && $variantRow['price_override'] !== '') {
                $priceUse = (int)$variantRow['price_override'];
            }
            $days      = max(1, (int)($item['duration_days'] ?? 1));
            $startDate = $item['rental_start'] ?? null;
            $endDate   = $item['rental_end']   ?? null;

            if ($startDate && $endDate) {
                if ($variantRow) {
                    $booked = bookedQuantityForVariant($conn, $variantRow['id'], $startDate, $endDate);
                    if (($booked + $item['quantity']) > (int)$variantRow['stock']) {
                        throw new Exception('Biến thể đã hết hàng trong thời gian này.');
                    }
                } else {
                    if (hasProductConflictNoVariant($conn, $pid, $startDate, $endDate)) {
                        throw new Exception('Sản phẩm đã được đặt trong khoảng thời gian bạn chọn.');
                    }
                }
            }

            $stmt_detail->execute([$order_id, $pid, $variantId, $item['quantity'], $priceUse, $startDate, $endDate, $days]);

            if ($variantRow) {
                $upd = $conn->prepare("UPDATE product_variants SET stock = stock - ? WHERE id = ? AND stock >= ?");
                $upd->execute([$item['quantity'], $variantRow['id'], $item['quantity']]);
                if ($upd->rowCount() === 0) throw new Exception('Kho không đủ cho biến thể được chọn.');
            }
        }
        $conn->commit();

        $_SESSION['last_order_id'] = $order_id;
        $_SESSION['last_order_info'] = [
            'fullname' => $fullname,
            'phone'    => $phone,
            'address'  => $address,
            'total'    => $real_total,
            'payment'  => $payment,
            'items'    => $_SESSION['cart'],
        ];
        unset($_SESSION['cart']);
        header('Location: checkout.php?success=1');
        exit;

    } catch (Exception $e) {
        $conn->rollBack();
        $error = $e->getMessage();
    }
}

if (isset($_GET['success']) && isset($_SESSION['last_order_id'])) {
    $showSuccess = true;
}

// ── User info pre-fill ──
$prefillName  = '';
$prefillPhone = '';
if (isset($_SESSION['user_id'])) {
    $stmtU = $conn->prepare('SELECT username, phone FROM users WHERE id = ? LIMIT 1');
    $stmtU->execute([$_SESSION['user_id']]);
    $uRow = $stmtU->fetch(PDO::FETCH_ASSOC);
    $prefillName  = $uRow['username'] ?? '';
    $prefillPhone = $uRow['phone']    ?? '';
}

$pageTitle = $showSuccess ? 'Đặt Hàng Thành Công | QHTN' : 'Thanh Toán | QHTN';
require_once 'header.php';
?>

<style>
/* ============================================================
   CHECKOUT PAGE — QHTN CORPORATE EDITION
   No border-radius · Pink-Burgundy · Đồng bộ cart/orders
============================================================ */
.ck-page { background: #f8f5f6; min-height: 80vh; font-family: 'Montserrat', sans-serif; }

/* ── BANNER ── */
.ck-banner {
    background: linear-gradient(105deg, rgba(47,28,38,0.95) 0%, rgba(90,33,56,0.88) 55%, rgba(139,48,87,0.75) 100%),
                url('img/avatars/hero.webp') center 30% / cover no-repeat;
    padding: 36px 5%;
}
.ck-banner-inner { max-width: 1280px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; }
.ck-banner-kicker { font-size: 10px; font-weight: 700; letter-spacing: 4px; text-transform: uppercase; color: rgba(255,255,255,0.4); margin-bottom: 6px; }
.ck-banner-title  { font-size: 28px; font-weight: 900; color: #fff; text-transform: uppercase; letter-spacing: -0.5px; }

/* Stepper (same as cart) */
.ck-stepper { display: flex; align-items: center; gap: 0; }
.ck-step { display: flex; align-items: center; gap: 8px; padding: 0 20px; }
.ck-step:first-child { padding-left: 0; }
.ck-step-num { width: 28px; height: 28px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 800; color: rgba(255,255,255,0.35); border: 2px solid rgba(255,255,255,0.2); }
.ck-step.done  .ck-step-num  { background: rgba(63,178,127,0.9); border-color: transparent; color: #fff; }
.ck-step.active .ck-step-num { background: var(--accent-pink); border-color: var(--accent-pink); color: #fff; }
.ck-step.compl  .ck-step-num { background: rgba(63,178,127,0.9); border-color: transparent; color: #fff; }
.ck-step-label { font-size: 10px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: rgba(255,255,255,0.3); }
.ck-step.done  .ck-step-label  { color: rgba(255,255,255,0.5); }
.ck-step.active .ck-step-label { color: #fff; }
.ck-step.compl  .ck-step-label { color: #fff; }
.ck-step-arrow { font-size: 10px; color: rgba(255,255,255,0.15); flex-shrink: 0; }

/* ── MAIN LAYOUT ── */
.ck-main { max-width: 1280px; margin: 0 auto; padding: 32px 5%; }

/* ── CHECKOUT FORM LAYOUT ── */
.ck-layout { display: grid; grid-template-columns: 1fr 360px; gap: 24px; align-items: start; }

/* ── FORM PANEL ── */
.ck-form-panel { display: flex; flex-direction: column; gap: 0; }
.ck-section { background: #fff; border: 1px solid #ecdde4; border-bottom: none; }
.ck-section:last-child { border-bottom: 1px solid #ecdde4; }
.ck-section-head { padding: 16px 24px; border-bottom: 1px solid #f5eff2; background: #fff8fb; display: flex; align-items: center; gap: 10px; }
.ck-section-head-icon { width: 28px; height: 28px; background: var(--accent-pink); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 11px; flex-shrink: 0; }
.ck-section-title { font-size: 11px; font-weight: 900; color: #2f1c26; text-transform: uppercase; letter-spacing: 2px; }
.ck-section-body { padding: 24px; display: flex; flex-direction: column; gap: 16px; }

/* Error Banner */
.ck-error { padding: 14px 20px; background: #fff5f5; border-left: 4px solid #e84a5f; margin-bottom: 0; display: flex; align-items: center; gap: 10px; font-size: 13px; color: #e84a5f; font-weight: 600; }

/* Field Styles */
.ck-field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.ck-field { display: flex; flex-direction: column; gap: 6px; }
.ck-field-label { font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: #bbb; }
.ck-field-label .req { color: #e84a5f; }
.ck-input {
    padding: 12px 14px; font-size: 13px; font-family: 'Montserrat', sans-serif;
    color: #2f1c26; background: #fdf9fb;
    border: 1.5px solid #ecdde4; outline: none;
    transition: border-color 0.2s, background 0.2s;
    width: 100%; box-sizing: border-box;
}
.ck-input:focus { border-color: var(--accent-pink); background: #fff; }
.ck-input::placeholder { color: #ccc; font-size: 12px; }
textarea.ck-input { resize: vertical; min-height: 90px; }

/* Payment Methods */
.ck-payment-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
.ck-pay-opt { position: relative; }
.ck-pay-opt input[type=radio] { position: absolute; opacity: 0; }
.ck-pay-label {
    display: flex; flex-direction: column; align-items: center; gap: 8px;
    padding: 14px 10px; border: 1.5px solid #ecdde4; cursor: pointer;
    text-align: center; transition: all 0.18s; background: #fdf9fb;
}
.ck-pay-label:hover { border-color: var(--accent-pink); background: #fff8fb; }
.ck-pay-opt input:checked + .ck-pay-label { border-color: var(--accent-pink); background: #fff0f5; }
.ck-pay-icon { font-size: 20px; }
.ck-pay-name { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #2f1c26; }
.ck-pay-opt input:checked + .ck-pay-label .ck-pay-icon { color: var(--accent-pink); }
.ck-pay-check { position: absolute; top: 8px; right: 8px; width: 14px; height: 14px; background: var(--accent-pink); color: #fff; font-size: 8px; display: none; align-items: center; justify-content: center; }
.ck-pay-opt input:checked ~ .ck-pay-check { display: flex; }

/* ── ORDER RECEIPT PANEL ── */
.ck-receipt { position: sticky; top: 24px; display: flex; flex-direction: column; }
.ck-receipt-head { background: #2f1c26; padding: 16px 20px; }
.ck-receipt-title { font-size: 11px; font-weight: 900; color: #fff; text-transform: uppercase; letter-spacing: 2px; }
.ck-receipt-body { background: #fff; border: 1px solid #ecdde4; border-top: none; }

/* Cart items in receipt */
.ck-receipt-items { padding: 16px 20px; display: flex; flex-direction: column; gap: 12px; max-height: 320px; overflow-y: auto; border-bottom: 1px solid #f5eff2; }
.ck-receipt-items::-webkit-scrollbar { width: 3px; }
.ck-receipt-items::-webkit-scrollbar-thumb { background: #ecdde4; }
.ck-receipt-item { display: flex; align-items: center; gap: 12px; }
.ck-receipt-item-img { width: 42px; height: 50px; object-fit: cover; object-position: top; background: #f5eff2; flex-shrink: 0; }
.ck-receipt-item-info { flex: 1; }
.ck-receipt-item-name { font-size: 12px; font-weight: 700; color: #2f1c26; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.ck-receipt-item-meta { font-size: 10px; color: #bbb; margin-top: 2px; }
.ck-receipt-item-price { font-size: 12px; font-weight: 800; color: var(--accent-pink); flex-shrink: 0; text-align: right; }

/* Pricing rows */
.ck-receipt-pricing { padding: 16px 20px; display: flex; flex-direction: column; gap: 0; }
.ck-price-row { display: flex; justify-content: space-between; align-items: center; padding: 9px 0; border-bottom: 1px dotted #f5eff2; }
.ck-price-row:last-of-type { border-bottom: none; }
.ck-price-label { font-size: 12px; color: #888; }
.ck-price-value { font-size: 12px; font-weight: 700; color: #2f1c26; }
.ck-price-value.free  { color: #3fb27f; }
.ck-price-total { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-top: 2px solid #ecdde4; }
.ck-price-total-label { font-size: 13px; font-weight: 800; color: #2f1c26; text-transform: uppercase; }
.ck-price-total-value { font-size: 22px; font-weight: 900; color: var(--accent-pink); }

/* Submit */
.ck-submit-wrap { padding: 16px 20px 20px; border-top: 1px solid #f5eff2; }
.ck-submit-btn {
    display: block; width: 100%; padding: 16px;
    background: var(--accent-pink); color: #fff; text-align: center;
    font-size: 11px; font-weight: 800; letter-spacing: 2.5px; text-transform: uppercase;
    border: none; cursor: pointer; transition: background 0.2s; font-family: 'Montserrat', sans-serif;
}
.ck-submit-btn:hover { background: var(--hover-pink, #d54f7a); }
.ck-back-link { display: block; text-align: center; margin-top: 12px; font-size: 11px; color: #bbb; text-decoration: none; transition: color 0.2s; }
.ck-back-link:hover { color: var(--accent-pink); }

/* ── SUCCESS PAGE ── */
.ck-success-wrap { max-width: 660px; margin: 0 auto; }
.ck-success-top { background: #2f1c26; padding: 32px 40px; text-align: center; }
.ck-success-icon-ring { width: 72px; height: 72px; background: rgba(63,178,127,0.2); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }
.ck-success-icon-ring i { font-size: 34px; color: #3fb27f; }
.ck-success-top h2 { font-size: 22px; font-weight: 900; color: #fff; text-transform: uppercase; letter-spacing: -0.5px; margin-bottom: 6px; }
.ck-success-top p { font-size: 13px; color: rgba(255,255,255,0.45); }
.ck-success-order-id { display: inline-block; margin-top: 12px; padding: 6px 18px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12); font-size: 12px; font-weight: 800; color: rgba(255,255,255,0.7); letter-spacing: 2px; }

.ck-success-body { background: #fff; border: 1px solid #ecdde4; border-top: none; }

.ck-bill-section { padding: 20px 24px; border-bottom: 1px solid #f5eff2; }
.ck-bill-section-title { font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; color: #bbb; margin-bottom: 14px; }
.ck-bill-row { display: flex; justify-content: space-between; align-items: center; padding: 9px 0; border-bottom: 1px dotted #f5eff2; }
.ck-bill-row:last-child { border-bottom: none; }
.ck-bill-label { font-size: 12px; color: #888; }
.ck-bill-value { font-size: 13px; font-weight: 700; color: #2f1c26; }
.ck-bill-value.pink { color: var(--accent-pink); font-size: 16px; }
.ck-bill-value.green { color: #3fb27f; }

/* Success items */
.ck-success-items { padding: 20px 24px; border-bottom: 1px solid #f5eff2; display: flex; flex-direction: column; gap: 10px; }
.ck-success-item { display: flex; align-items: center; gap: 12px; }
.ck-success-item-img { width: 38px; height: 46px; object-fit: cover; object-position: top; background: #f5eff2; flex-shrink: 0; }
.ck-success-item-name { flex: 1; font-size: 12px; font-weight: 700; color: #2f1c26; }
.ck-success-item-price { font-size: 12px; font-weight: 800; color: var(--accent-pink); }

.ck-success-actions { padding: 20px 24px; display: flex; gap: 10px; flex-wrap: wrap; }
.ck-success-btn {
    flex: 1; padding: 14px; text-align: center;
    font-size: 10px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase;
    text-decoration: none; transition: all 0.2s;
}
.ck-success-btn.primary { background: var(--accent-pink); color: #fff; border: none; }
.ck-success-btn.primary:hover { background: var(--hover-pink, #d54f7a); }
.ck-success-btn.ghost { background: transparent; border: 1.5px solid #ecdde4; color: #888; }
.ck-success-btn.ghost:hover { border-color: var(--accent-pink); color: var(--accent-pink); }

/* Timeline */
.ck-timeline { padding: 20px 24px; }
.ck-timeline-title { font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; color: #bbb; margin-bottom: 16px; }
.ck-timeline-steps { display: flex; gap: 0; }
.ck-tl-step { flex: 1; text-align: center; position: relative; }
.ck-tl-step::after { content: ''; position: absolute; top: 13px; left: 50%; width: 100%; height: 2px; background: #ecdde4; z-index: 0; }
.ck-tl-step:last-child::after { display: none; }
.ck-tl-dot { width: 28px; height: 28px; margin: 0 auto 8px; display: flex; align-items: center; justify-content: center; font-size: 12px; position: relative; z-index: 1; }
.ck-tl-dot.done  { background: var(--accent-pink); color: #fff; }
.ck-tl-dot.pending { background: #fff; border: 2px solid #ecdde4; color: #ccc; }
.ck-tl-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #bbb; }
.ck-tl-step.done .ck-tl-label { color: var(--accent-pink); }

/* Responsive */
@media (max-width: 1024px) { .ck-layout { grid-template-columns: 1fr; } .ck-receipt { position: static; } }
@media (max-width: 640px) { .ck-field-row { grid-template-columns: 1fr; } .ck-payment-grid { grid-template-columns: 1fr; } .ck-stepper { display: none; } }
</style>

<div class="ck-page">

<!-- ── BANNER ── -->
<div class="ck-banner">
    <div class="ck-banner-inner">
        <div>
            <div class="ck-banner-kicker">Đặt thuê trang phục QHTN</div>
            <div class="ck-banner-title"><?= $showSuccess ? 'Đặt hàng thành công' : 'Thanh toán' ?></div>
        </div>
        <div class="ck-stepper">
            <div class="ck-step done">
                <div class="ck-step-num"><i class="fa-solid fa-check" style="font-size:10px"></i></div>
                <div class="ck-step-label">Giỏ hàng</div>
            </div>
            <div class="ck-step-arrow"><i class="fa-solid fa-chevron-right"></i></div>
            <div class="ck-step <?= $showSuccess ? 'done' : 'active' ?>">
                <div class="ck-step-num"><?= $showSuccess ? '<i class="fa-solid fa-check" style="font-size:10px"></i>' : '2' ?></div>
                <div class="ck-step-label">Thanh toán</div>
            </div>
            <div class="ck-step-arrow"><i class="fa-solid fa-chevron-right"></i></div>
            <div class="ck-step <?= $showSuccess ? 'compl active' : '' ?>">
                <div class="ck-step-num"><?= $showSuccess ? '<i class="fa-solid fa-check" style="font-size:10px"></i>' : '3' ?></div>
                <div class="ck-step-label">Hoàn tất</div>
            </div>
        </div>
    </div>
</div>

<?php if ($error && !$showSuccess): ?>
<div style="background:#e84a5f;color:#fff;padding:14px 5%;font-size:13px;font-weight:700;display:flex;align-items:center;gap:10px;">
    <i class="fa-solid fa-triangle-exclamation" style="font-size:18px"></i>
    <span><?= htmlspecialchars($error) ?></span>
</div>
<?php endif; ?>

<div class="ck-main">

<?php if ($showSuccess):
    $lastOrder = $_SESSION['last_order_info'] ?? [];
    $paymentLabels = ['cod' => 'Thanh toán khi nhận hàng (COD)', 'bank' => 'Chuyển khoản ngân hàng', 'momo' => 'Ví MoMo'];
    $payLabel = $paymentLabels[$lastOrder['payment'] ?? 'cod'] ?? 'COD';
?>

<!-- ══════════════════════════════════════
     SUCCESS PAGE
══════════════════════════════════════ -->
<div class="ck-success-wrap">
    <!-- Top -->
    <div class="ck-success-top">
        <div class="ck-success-icon-ring">
            <i class="fa-solid fa-check"></i>
        </div>
        <h2>Đặt hàng thành công!</h2>
        <p>Cảm ơn bạn đã tin tưởng QHTN. Chúng tôi sẽ liên hệ sớm để xác nhận đơn hàng.</p>
        <div class="ck-success-order-id">ĐƠN #<?= (int)$_SESSION['last_order_id'] ?></div>
    </div>

    <div class="ck-success-body">
        <!-- Timeline -->
        <div class="ck-timeline">
            <div class="ck-timeline-title">Trạng thái đơn hàng</div>
            <div class="ck-timeline-steps">
                <div class="ck-tl-step done">
                    <div class="ck-tl-dot done"><i class="fa-solid fa-check" style="font-size:10px"></i></div>
                    <div class="ck-tl-label">Đặt hàng</div>
                </div>
                <div class="ck-tl-step">
                    <div class="ck-tl-dot pending"><i class="fa-solid fa-circle-check" style="font-size:11px"></i></div>
                    <div class="ck-tl-label">Xác nhận</div>
                </div>
                <div class="ck-tl-step">
                    <div class="ck-tl-dot pending"><i class="fa-solid fa-shirt" style="font-size:11px"></i></div>
                    <div class="ck-tl-label">Đang thuê</div>
                </div>
                <div class="ck-tl-step">
                    <div class="ck-tl-dot pending"><i class="fa-solid fa-box" style="font-size:11px"></i></div>
                    <div class="ck-tl-label">Hoàn trả</div>
                </div>
            </div>
        </div>

        <!-- Customer info -->
        <div class="ck-bill-section">
            <div class="ck-bill-section-title"><i class="fa-solid fa-user" style="color:var(--accent-pink)"></i> Thông tin nhận hàng</div>
            <div class="ck-bill-row">
                <span class="ck-bill-label">Họ và tên</span>
                <span class="ck-bill-value"><?= htmlspecialchars($lastOrder['fullname'] ?? '') ?></span>
            </div>
            <div class="ck-bill-row">
                <span class="ck-bill-label">Số điện thoại</span>
                <span class="ck-bill-value"><?= htmlspecialchars($lastOrder['phone'] ?? '') ?></span>
            </div>
            <?php if (!empty($lastOrder['address'])): ?>
            <div class="ck-bill-row">
                <span class="ck-bill-label">Địa chỉ</span>
                <span class="ck-bill-value" style="max-width:60%;text-align:right"><?= htmlspecialchars($lastOrder['address']) ?></span>
            </div>
            <?php endif; ?>
            <div class="ck-bill-row">
                <span class="ck-bill-label">Hình thức thanh toán</span>
                <span class="ck-bill-value green"><?= htmlspecialchars($payLabel) ?></span>
            </div>
            <div class="ck-bill-row">
                <span class="ck-bill-label">Tổng thanh toán</span>
                <span class="ck-bill-value pink"><?= number_format($lastOrder['total'] ?? 0) ?>đ</span>
            </div>
        </div>

        <!-- Items ordered -->
        <?php if (!empty($lastOrder['items'])): ?>
        <div class="ck-success-items">
            <div class="ck-bill-section-title" style="margin-bottom:12px"><i class="fa-solid fa-bag-shopping" style="color:var(--accent-pink)"></i> Sản phẩm đã đặt</div>
            <?php foreach ($lastOrder['items'] as $item):
                $raw_img  = $item['image'] ?? '';
                $img_show = !empty($raw_img) ? 'img/' . basename($raw_img) : 'img/default.jpg';
                $days     = max(1, (int)($item['duration_days'] ?? 1));
                $subtotal = (int)$item['price'] * (int)$item['quantity'] * $days;
            ?>
            <div class="ck-success-item">
                <img src="<?= htmlspecialchars($img_show) ?>" class="ck-success-item-img"
                     alt="<?= htmlspecialchars($item['name'] ?? '') ?>"
                     onerror="this.src='img/default.jpg'">
                <div class="ck-success-item-name">
                    <?= htmlspecialchars($item['name'] ?? '') ?>
                    <div style="font-size:10px;color:#bbb;font-weight:500;margin-top:2px">
                        <?= $item['quantity'] ?> × <?= $days ?> ngày
                    </div>
                </div>
                <div class="ck-success-item-price"><?= number_format($subtotal) ?>đ</div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Action buttons -->
        <div class="ck-success-actions">
            <a href="orders.php" class="ck-success-btn primary">
                <i class="fa-solid fa-receipt" style="margin-right:6px"></i> Xem đơn hàng
            </a>
            <a href="ao_dai.php" class="ck-success-btn ghost">
                <i class="fa-solid fa-sparkles" style="margin-right:6px"></i> Tiếp tục thuê đồ
            </a>
        </div>
    </div>
</div>

<?php else: ?>

<!-- ══════════════════════════════════════
     CHECKOUT FORM
══════════════════════════════════════ -->
<form method="POST" class="ck-layout" id="ckForm">

    <!-- ── LEFT: FORM ── -->
    <div class="ck-form-panel">

        <!-- Error -->
        <?php if ($error): ?>
        <div class="ck-error">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <!-- Section 1: Thông tin nhận hàng -->
        <div class="ck-section">
            <div class="ck-section-head">
                <div class="ck-section-head-icon"><i class="fa-solid fa-user"></i></div>
                <div class="ck-section-title">Thông tin nhận hàng</div>
            </div>
            <div class="ck-section-body">
                <div class="ck-field-row">
                    <div class="ck-field">
                        <label class="ck-field-label">Họ và tên <span class="req">*</span></label>
                        <input type="text" name="fullname" class="ck-input" required
                               value="<?= htmlspecialchars($_POST['fullname'] ?? $prefillName) ?>"
                               placeholder="Nguyễn Thị Hoa">
                    </div>
                    <div class="ck-field">
                        <label class="ck-field-label">Số điện thoại <span class="req">*</span></label>
                        <input type="tel" name="phone" class="ck-input" required
                               value="<?= htmlspecialchars($_POST['phone'] ?? $prefillPhone) ?>"
                               placeholder="09x xxx xxxx">
                    </div>
                </div>
                <div class="ck-field">
                    <label class="ck-field-label">Địa chỉ nhận đồ <span class="req">*</span></label>
                    <input type="text" name="address" class="ck-input" required
                           value="<?= htmlspecialchars($_POST['address'] ?? '') ?>"
                           placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành phố">
                </div>
                <div class="ck-field">
                    <label class="ck-field-label">Ghi chú đơn hàng</label>
                    <textarea name="note" class="ck-input" placeholder="Ví dụ: Giao giờ hành chính, gọi trước khi đến..."><?= htmlspecialchars($_POST['note'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Section 2: Hình thức thanh toán -->
        <div class="ck-section">
            <div class="ck-section-head">
                <div class="ck-section-head-icon"><i class="fa-solid fa-credit-card"></i></div>
                <div class="ck-section-title">Hình thức thanh toán</div>
            </div>
            <div class="ck-section-body">
                <div class="ck-payment-grid">
                    <div class="ck-pay-opt">
                        <input type="radio" name="payment_method" id="pay_cod" value="cod"
                               <?= ($_POST['payment_method'] ?? 'cod') === 'cod' ? 'checked' : '' ?>>
                        <label class="ck-pay-label" for="pay_cod">
                            <span class="ck-pay-icon">💵</span>
                            <span class="ck-pay-name">COD</span>
                            <small style="font-size:9px;color:#bbb">Tiền mặt</small>
                        </label>
                        <span class="ck-pay-check"><i class="fa-solid fa-check" style="font-size:7px"></i></span>
                    </div>
                    <div class="ck-pay-opt">
                        <input type="radio" name="payment_method" id="pay_bank" value="bank"
                               <?= ($_POST['payment_method'] ?? '') === 'bank' ? 'checked' : '' ?>>
                        <label class="ck-pay-label" for="pay_bank">
                            <span class="ck-pay-icon">🏦</span>
                            <span class="ck-pay-name">Ngân hàng</span>
                            <small style="font-size:9px;color:#bbb">Chuyển khoản</small>
                        </label>
                        <span class="ck-pay-check"><i class="fa-solid fa-check" style="font-size:7px"></i></span>
                    </div>
                    <div class="ck-pay-opt">
                        <input type="radio" name="payment_method" id="pay_momo" value="momo"
                               <?= ($_POST['payment_method'] ?? '') === 'momo' ? 'checked' : '' ?>>
                        <label class="ck-pay-label" for="pay_momo">
                            <span class="ck-pay-icon">💳</span>
                            <span class="ck-pay-name">MoMo</span>
                            <small style="font-size:9px;color:#bbb">Ví điện tử</small>
                        </label>
                        <span class="ck-pay-check"><i class="fa-solid fa-check" style="font-size:7px"></i></span>
                    </div>
                </div>

                <!-- Bank info (shown when bank is selected) -->
                <div id="bankInfo" style="display:none; background:#fff8fb; border:1.5px solid #ecdde4; padding:16px; font-size:12px; color:#555; line-height:1.8;">
                    <strong style="color:#2f1c26;display:block;margin-bottom:8px;font-size:11px;text-transform:uppercase;letter-spacing:1px">
                        <i class="fa-solid fa-building-columns" style="color:var(--accent-pink)"></i> Thông tin chuyển khoản
                    </strong>
                    Ngân hàng: <strong>Vietcombank</strong><br>
                    Số tài khoản: <strong>1234 5678 9012</strong><br>
                    Tên TK: <strong>QHTN FASHION</strong><br>
                    Nội dung: <strong>QHTN + Tên + SĐT</strong>
                </div>
                <!-- MoMo info -->
                <div id="momoInfo" style="display:none; background:#fff8fb; border:1.5px solid #ecdde4; padding:16px; font-size:12px; color:#555; line-height:1.8;">
                    <strong style="color:#2f1c26;display:block;margin-bottom:8px;font-size:11px;text-transform:uppercase;letter-spacing:1px">
                        <i class="fa-solid fa-mobile-screen" style="color:var(--accent-pink)"></i> Ví MoMo
                    </strong>
                    Số điện thoại MoMo: <strong>0986 772 017</strong><br>
                    Tên: <strong>QHTN Fashion</strong><br>
                    Nội dung: <strong>QHTN + Tên + SĐT</strong>
                </div>
            </div>
        </div>

        <!-- Section 3: Ghi chú dịch vụ -->
        <div class="ck-section">
            <div class="ck-section-head">
                <div class="ck-section-head-icon"><i class="fa-solid fa-shield-halved"></i></div>
                <div class="ck-section-title">Cam kết dịch vụ</div>
            </div>
            <div class="ck-section-body" style="flex-direction:row;flex-wrap:wrap;gap:12px">
                <?php foreach ([
                    ['fa-vihara','Trang phục đã vệ sinh và được kiểm tra trước khi giao'],
                    ['fa-rotate-left','Đặt cọc được hoàn trả sau khi trả đồ nguyên vẹn'],
                    ['fa-headset','Hỗ trợ 24/7 · Giao nhận tận nơi trong TP. Đà Nẵng'],
                ] as [$icon, $text]): ?>
                <div style="display:flex;align-items:flex-start;gap:10px;font-size:12px;color:#888;flex:1;min-width:200px">
                    <i class="fa-solid <?= $icon ?>" style="color:var(--accent-pink);margin-top:2px;flex-shrink:0"></i>
                    <span><?= $text ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div><!-- .ck-form-panel -->

    <!-- ── RIGHT: RECEIPT ── -->
    <div class="ck-receipt">
        <div class="ck-receipt-head">
            <div class="ck-receipt-title">
                <i class="fa-solid fa-receipt" style="margin-right:8px;opacity:0.6"></i>
                Đơn hàng (<?= count($_SESSION['cart']) ?> sản phẩm)
            </div>
        </div>
        <div class="ck-receipt-body">
            <!-- Items -->
            <div class="ck-receipt-items">
                <?php foreach ($_SESSION['cart'] as $item):
                    $raw_img  = $item['image'] ?? '';
                    $img_show = !empty($raw_img) ? 'img/' . basename($raw_img) : 'img/default.jpg';
                    $days     = max(1, (int)($item['duration_days'] ?? 1));
                    $subtotal = (int)$item['price'] * (int)$item['quantity'] * $days;
                ?>
                <div class="ck-receipt-item">
                    <img src="<?= htmlspecialchars($img_show) ?>"
                         class="ck-receipt-item-img"
                         alt="<?= htmlspecialchars($item['name'] ?? '') ?>"
                         onerror="this.src='img/default.jpg'">
                    <div class="ck-receipt-item-info">
                        <div class="ck-receipt-item-name"><?= htmlspecialchars($item['name'] ?? '') ?></div>
                        <div class="ck-receipt-item-meta">
                            <?= (int)$item['quantity'] ?> cái · <?= $days ?> ngày
                            <?php if (!empty($item['variant_size'])): ?>
                            · <?= htmlspecialchars($item['variant_size']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="ck-receipt-item-price"><?= number_format($subtotal) ?>đ</div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pricing -->
            <div class="ck-receipt-pricing">
                <div class="ck-price-row">
                    <span class="ck-price-label">Tạm tính</span>
                    <span class="ck-price-value"><?= number_format($total) ?>đ</span>
                </div>
                <div class="ck-price-row">
                    <span class="ck-price-label">Phí vận chuyển</span>
                    <span class="ck-price-value free">Miễn phí</span>
                </div>
                <div class="ck-price-row">
                    <span class="ck-price-label">Giảm giá</span>
                    <span class="ck-price-value free">0đ</span>
                </div>
            </div>

            <!-- Total -->
            <div class="ck-price-total">
                <span class="ck-price-total-label">Tổng cộng</span>
                <span class="ck-price-total-value"><?= number_format($total) ?>đ</span>
            </div>

            <!-- Submit -->
            <div class="ck-submit-wrap">
                <button type="submit" name="checkout" class="ck-submit-btn" id="submitBtn">
                    <i class="fa-solid fa-lock" style="margin-right:6px"></i>
                    Xác nhận đặt thuê
                </button>
                <a href="cart.php" class="ck-back-link">
                    <i class="fa-solid fa-arrow-left" style="margin-right:4px"></i>
                    Quay lại giỏ hàng
                </a>
            </div>
        </div>
    </div><!-- .ck-receipt -->

</form>

<?php endif; ?>

</div><!-- .ck-main -->
</div><!-- .ck-page -->

<script>
(function() {
    // ── Payment method toggle ──
    const radios = document.querySelectorAll('input[name="payment_method"]');
    const bankInfo = document.getElementById('bankInfo');
    const momoInfo = document.getElementById('momoInfo');

    function updatePayInfo() {
        const val = document.querySelector('input[name="payment_method"]:checked')?.value;
        if (bankInfo) bankInfo.style.display = val === 'bank' ? 'block' : 'none';
        if (momoInfo) momoInfo.style.display = val === 'momo' ? 'block' : 'none';
    }

    radios.forEach(r => r.addEventListener('change', updatePayInfo));
    updatePayInfo();

    // ── Submit loading state ──
    const form = document.getElementById('ckForm');
    const btn  = document.getElementById('submitBtn');
    if (form && btn) {
        form.addEventListener('submit', function (e) {
            // Validate required fields first
            const req = form.querySelectorAll('[required]');
            let valid = true;
            req.forEach(function(el) {
                if (!el.value.trim()) {
                    el.style.borderColor = '#e84a5f';
                    valid = false;
                } else {
                    el.style.borderColor = '';
                }
            });
            if (!valid) {
                e.preventDefault();
                const firstInvalid = form.querySelector('[required]:not([value=""]):not(:valid), [required][value=""]');
                if (firstInvalid) firstInvalid.focus();
                return false;
            }
            // Delay disable so form data is captured by browser first
            setTimeout(function() {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin" style="margin-right:6px"></i> Đang xử lý...';
            }, 50);
        });
    }
})();
</script>

<?php include 'footer.php'; ?>