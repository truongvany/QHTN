<?php
require_once 'config.php';

// Khởi tạo giỏ hàng
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// ── 1. AJAX: Xóa nhanh (trả JSON) ──
if (isset($_GET['ajax_remove'])) {
    $rid = (int)$_GET['ajax_remove'];
    foreach ($_SESSION['cart'] as $k => $item) {
        if ($item['id'] == $rid) { unset($_SESSION['cart'][$k]); break; }
    }
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    echo json_encode(['ok' => true, 'count' => array_sum(array_column($_SESSION['cart'], 'quantity'))]);
    exit;
}

// ── 2. Xóa thường (GET) ──
if (isset($_GET['remove_id'])) {
    $rid = (int)$_GET['remove_id'];
    foreach ($_SESSION['cart'] as $k => $item) {
        if ($item['id'] == $rid) { unset($_SESSION['cart'][$k]); break; }
    }
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    header('Location: cart.php');
    exit;
}

// ── 3. Cập nhật số lượng (POST) ──
if (isset($_POST['update_cart'])) {
    $quantities = $_POST['qty'] ?? [];
    $days_map   = $_POST['days'] ?? [];
    foreach ($_SESSION['cart'] as $k => $item) {
        $pid = $item['id'];
        if (isset($quantities[$pid])) {
            $newQty = max(1, (int)$quantities[$pid]);
            $_SESSION['cart'][$k]['quantity'] = $newQty;
        }
        if (isset($days_map[$pid])) {
            $newDays = max(1, (int)$days_map[$pid]);
            $_SESSION['cart'][$k]['duration_days'] = $newDays;
        }
        // Remove if qty=0
        if (isset($quantities[$pid]) && (int)$quantities[$pid] <= 0) {
            unset($_SESSION['cart'][$k]);
        }
    }
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    header('Location: cart.php');
    exit;
}

// ── 4. Tính tổng ──
$total = 0;
$totalItems = 0;
foreach ($_SESSION['cart'] as $item) {
    $days = max(1, (int)($item['duration_days'] ?? 1));
    $price = (int)($item['price'] ?? 0);
    $qty = max(1, (int)($item['quantity'] ?? 1));
    $total += $price * $qty * $days;
    $totalItems += $qty;
}

$pageTitle = 'Giỏ Hàng | MinQuin';
require_once 'header.php';
?>

<style>
/* ============================================================
   CART PAGE — MinQuin CORPORATE EDITION
   No border-radius · Pink-Burgundy · Đồng bộ orders/profile
============================================================ */
.ct-page { background: #f8f5f6; min-height: 80vh; font-family: 'Montserrat', sans-serif; }

/* ── BANNER ── */
.ct-banner {
    background:
        linear-gradient(105deg, rgba(47,28,38,0.95) 0%, rgba(90,33,56,0.88) 55%, rgba(139,48,87,0.75) 100%),
        url('img/avatars/hero.webp') center 30% / cover no-repeat;
    padding: 36px 5%;
}
.ct-banner-inner { max-width: 1280px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; }
.ct-banner-kicker { font-size: 10px; font-weight: 700; letter-spacing: 4px; text-transform: uppercase; color: rgba(255,255,255,0.4); margin-bottom: 6px; }
.ct-banner-title { font-size: 28px; font-weight: 900; color: #fff; text-transform: uppercase; letter-spacing: -0.5px; }

/* Stepper */
.ct-stepper { display: flex; align-items: center; gap: 0; }
.ct-step { display: flex; align-items: center; gap: 8px; padding: 0 20px; }
.ct-step:first-child { padding-left: 0; }
.ct-step-num {
    width: 28px; height: 28px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 800; color: rgba(255,255,255,0.35);
    border: 2px solid rgba(255,255,255,0.2);
}
.ct-step.active .ct-step-num { background: var(--accent-pink); border-color: var(--accent-pink); color: #fff; }
.ct-step.done .ct-step-num { background: rgba(63,178,127,0.8); border-color: transparent; color: #fff; }
.ct-step-label { font-size: 10px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: rgba(255,255,255,0.3); }
.ct-step.active .ct-step-label { color: #fff; }
.ct-step.done .ct-step-label { color: rgba(255,255,255,0.5); }
.ct-step-arrow { font-size: 10px; color: rgba(255,255,255,0.15); flex-shrink: 0; }

/* ── MAIN ── */
.ct-main { max-width: 1280px; margin: 0 auto; padding: 32px 5%; display: grid; grid-template-columns: 1fr 320px; gap: 24px; align-items: start; }

/* ── CART ITEMS PANEL ── */
.ct-items-panel { display: flex; flex-direction: column; gap: 0; }

/* Panel header */
.ct-panel-head {
    background: #fff; border: 1px solid #ecdde4; border-bottom: none;
    padding: 16px 20px; display: flex; align-items: center; justify-content: space-between;
}
.ct-panel-title { font-size: 12px; font-weight: 900; color: #2f1c26; text-transform: uppercase; letter-spacing: 2px; display: flex; align-items: center; gap: 8px; }
.ct-panel-title i { color: var(--accent-pink); }
.ct-panel-count { font-size: 12px; color: #bbb; font-weight: 600; }

/* Table-like column headers */
.ct-col-labels {
    background: #fff8fb; border: 1px solid #ecdde4; border-bottom: none;
    display: grid; grid-template-columns: 56px 1fr 110px 90px 100px 40px;
    gap: 0; padding: 10px 20px; align-items: center;
}
.ct-col-label { font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: #bbb; text-align: center; }
.ct-col-label:nth-child(2) { text-align: left; }

/* Item row */
.ct-item {
    background: #fff; border: 1px solid #ecdde4; border-bottom: none;
    display: grid; grid-template-columns: 56px 1fr 110px 90px 100px 40px;
    gap: 0; padding: 0; align-items: center;
    transition: background 0.15s;
}
.ct-item:hover { background: #fff8fb; }
.ct-item.removing { opacity: 0; transform: scale(0.98); transition: all 0.3s ease; }
.ct-item-border { border-bottom: 1px solid #ecdde4; }/* last item gets border */

.ct-item-img-wrap { padding: 14px 10px 14px 20px; }
.ct-item-img { width: 36px; height: 44px; object-fit: cover; object-position: top; background: #f5eff2; display: block; }

.ct-item-info { padding: 14px 12px; }
.ct-item-name { font-size: 12.5px; font-weight: 700; color: #2f1c26; line-height: 1.4; margin-bottom: 4px; }
.ct-item-variant { font-size: 10px; color: var(--accent-pink); font-weight: 700; margin-bottom: 3px; }
.ct-item-price-day { font-size: 10.5px; color: #bbb; }
.ct-item-rental { font-size: 10px; color: #aaa; margin-top: 3px; display: flex; align-items: center; gap: 4px; }
.ct-item-rental i { color: var(--accent-pink); font-size: 9px; }

/* Qty control */
.ct-qty-cell { padding: 14px 8px; display: flex; align-items: center; justify-content: center; }
.ct-qty-wrap { display: flex; align-items: center; border: 1.5px solid #ecdde4; }
.ct-qty-btn { width: 26px; height: 26px; border: none; background: transparent; cursor: pointer; font-size: 13px; color: #888; display: flex; align-items: center; justify-content: center; transition: all 0.15s; font-family: 'Montserrat', sans-serif; }
.ct-qty-btn:hover { background: #fff0f5; color: var(--accent-pink); }
.ct-qty-input { width: 36px; height: 26px; border: none; border-left: 1.5px solid #ecdde4; border-right: 1.5px solid #ecdde4; text-align: center; font-size: 12px; font-weight: 700; color: #2f1c26; outline: none; font-family: 'Montserrat', sans-serif; background: #fff; }

/* Days control */
.ct-days-cell { padding: 14px 8px; display: flex; flex-direction: column; align-items: center; gap: 4px; }
.ct-days-input { width: 46px; height: 28px; border: 1.5px solid #ecdde4; text-align: center; font-size: 12px; font-weight: 700; color: #2f1c26; outline: none; font-family: 'Montserrat', sans-serif; background: #fff; transition: border-color 0.15s; }
.ct-days-input:focus { border-color: var(--accent-pink); }
.ct-days-label { font-size: 9px; color: #ccc; font-weight: 600; }

/* Subtotal */
.ct-subtotal-cell { padding: 14px 8px; text-align: center; font-size: 13px; font-weight: 800; color: var(--accent-pink); }

/* Remove btn */
.ct-remove-cell { padding: 14px 14px 14px 8px; display: flex; align-items: center; justify-content: center; }
.ct-remove-btn { width: 28px; height: 28px; border: none; background: transparent; cursor: pointer; color: #ddd; font-size: 13px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
.ct-remove-btn:hover { background: #fff0f0; color: #e84a5f; }

/* Panel footer */
.ct-panel-foot {
    background: #fff; border: 1px solid #ecdde4;
    padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
}
.ct-continue-link { font-size: 11px; font-weight: 600; color: #bbb; text-decoration: none; display: flex; align-items: center; gap: 6px; transition: color 0.2s; }
.ct-continue-link:hover { color: var(--accent-pink); }
.ct-update-btn {
    padding: 10px 20px; font-size: 10px; font-weight: 700; letter-spacing: 1.5px;
    text-transform: uppercase; border: 1.5px solid #ecdde4; color: #888; background: transparent;
    cursor: pointer; display: flex; align-items: center; gap: 6px;
    transition: all 0.2s; font-family: 'Montserrat', sans-serif;
}
.ct-update-btn:hover { border-color: #2f1c26; color: #2f1c26; }

/* ── SUMMARY PANEL ── */
.ct-summary { display: flex; flex-direction: column; gap: 0; position: sticky; top: 24px; }
.ct-summary-head { background: #2f1c26; padding: 16px 20px; }
.ct-summary-head-title { font-size: 11px; font-weight: 900; color: #fff; text-transform: uppercase; letter-spacing: 2px; }
.ct-summary-body { background: #fff; border: 1px solid #ecdde4; border-top: none; padding: 20px; display: flex; flex-direction: column; gap: 0; }

.ct-summary-row { display: flex; justify-content: space-between; align-items: center; padding: 11px 0; border-bottom: 1px dotted #f5eff2; }
.ct-summary-row:last-of-type { border-bottom: none; }
.ct-summary-label { font-size: 12px; color: #888; font-weight: 600; }
.ct-summary-value { font-size: 12px; font-weight: 700; color: #2f1c26; }
.ct-summary-value.highlight { color: var(--accent-pink); font-size: 13px; }
.ct-summary-value.green { color: #3fb27f; }

.ct-summary-total { padding: 16px 0 8px; border-top: 2px solid #ecdde4; margin-top: 8px; display: flex; justify-content: space-between; align-items: center; }
.ct-summary-total-label { font-size: 13px; font-weight: 800; color: #2f1c26; text-transform: uppercase; letter-spacing: 0.5px; }
.ct-summary-total-value { font-size: 22px; font-weight: 900; color: var(--accent-pink); }

.ct-checkout-btn {
    display: block; width: 100%; padding: 16px;
    background: var(--accent-pink); color: #fff; text-align: center;
    font-size: 11px; font-weight: 800; letter-spacing: 2.5px; text-transform: uppercase;
    text-decoration: none; border: none; cursor: pointer;
    transition: background 0.2s; margin-top: 16px; font-family: 'Montserrat', sans-serif;
}
.ct-checkout-btn:hover { background: var(--hover-pink, #d54f7a); }
.ct-checkout-btn i { margin-right: 6px; }

.ct-note { background: #fff8fb; border: 1px solid #ecdde4; border-top: none; padding: 14px 20px; }
.ct-note-text { font-size: 10px; color: #bbb; line-height: 1.7; display: flex; gap: 8px; }
.ct-note-text i { color: var(--accent-pink); flex-shrink: 0; margin-top: 2px; }

/* ── EMPTY STATE ── */
.ct-empty { background: #fff; border: 1px solid #ecdde4; padding: 80px 20px; text-align: center; }
.ct-empty-icon { font-size: 56px; color: #f0dde6; margin-bottom: 20px; }
.ct-empty h2 { font-size: 18px; font-weight: 900; color: #2f1c26; text-transform: uppercase; letter-spacing: -0.5px; margin-bottom: 8px; }
.ct-empty p { font-size: 13px; color: #bbb; margin-bottom: 28px; }
.ct-empty-btn {
    display: inline-block; padding: 15px 40px;
    background: var(--accent-pink); color: #fff;
    font-size: 11px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase;
    text-decoration: none; transition: background 0.2s;
}
.ct-empty-btn:hover { background: var(--hover-pink, #d54f7a); }

/* Toast */
.ct-toast {
    position: fixed; bottom: 28px; right: 28px; z-index: 10000;
    padding: 13px 22px; background: #2f1c26; color: #fff;
    font-size: 13px; font-weight: 600;
    box-shadow: 0 8px 32px rgba(0,0,0,0.25);
    transform: translateY(20px); opacity: 0; transition: all 0.35s; pointer-events: none;
}
.ct-toast.show { transform: translateY(0); opacity: 1; }
.ct-toast.success { border-left: 4px solid #3fb27f; }
.ct-toast.info    { border-left: 4px solid var(--accent-pink); }

/* Responsive */
@media (max-width: 1100px) { .ct-main { grid-template-columns: 1fr; } .ct-summary { position: static; } }
@media (max-width: 768px) {
    .ct-col-labels { display: none; }
    .ct-item { grid-template-columns: 48px 1fr auto auto; grid-template-rows: auto auto; gap: 0; }
    .ct-item-img-wrap { grid-row: span 2; }
    .ct-item-info { grid-column: 2 / 4; }
    .ct-qty-cell { padding: 8px; }
    .ct-days-cell { padding: 8px; }
    .ct-subtotal-cell { grid-column: 2; padding: 8px 8px 14px; text-align: left; }
    .ct-remove-cell { grid-row: 1; grid-column: 4; }
    .ct-stepper { display: none; }
}
</style>

<div class="ct-page">

<!-- ── BANNER ── -->
<div class="ct-banner">
    <div class="ct-banner-inner">
        <div>
            <div class="ct-banner-kicker">Đặt thuê trang phục</div>
            <div class="ct-banner-title">Giỏ hàng của tôi</div>
        </div>
        <!-- Stepper -->
        <div class="ct-stepper">
            <div class="ct-step active">
                <div class="ct-step-num"><i class="fa-solid fa-bag-shopping" style="font-size:11px"></i></div>
                <div class="ct-step-label">Giỏ hàng</div>
            </div>
            <div class="ct-step-arrow"><i class="fa-solid fa-chevron-right"></i></div>
            <div class="ct-step">
                <div class="ct-step-num">2</div>
                <div class="ct-step-label">Thanh toán</div>
            </div>
            <div class="ct-step-arrow"><i class="fa-solid fa-chevron-right"></i></div>
            <div class="ct-step">
                <div class="ct-step-num">3</div>
                <div class="ct-step-label">Hoàn tất</div>
            </div>
        </div>
    </div>
</div>

<!-- ── MAIN ── -->
<div class="ct-main">

<?php if (empty($_SESSION['cart'])): ?>
    <div class="ct-empty" style="grid-column: 1 / -1;">
        <div class="ct-empty-icon"><i class="fa-solid fa-bag-shopping"></i></div>
        <h2>Giỏ hàng trống</h2>
        <p>Bạn chưa chọn được trang phục nào. Hãy khám phá bộ sưu tập của MinQuin!</p>
        <a href="ao_dai.php" class="ct-empty-btn">
            <i class="fa-solid fa-sparkles" style="margin-right:6px"></i> Khám phá bộ sưu tập
        </a>
    </div>

<?php else: ?>

    <!-- ── ITEMS PANEL ── -->
    <form method="POST" action="cart.php" id="cartForm">
        <div class="ct-items-panel">
            <!-- Head -->
            <div class="ct-panel-head">
                <div class="ct-panel-title">
                    <i class="fa-solid fa-bag-shopping"></i>
                    Sản phẩm trong giỏ
                </div>
                <div class="ct-panel-count"><?= $totalItems ?> sản phẩm · <?= count($_SESSION['cart']) ?> mặt hàng</div>
            </div>

            <!-- Column Labels -->
            <div class="ct-col-labels">
                <div class="ct-col-label"></div>
                <div class="ct-col-label" style="text-align:left">Sản phẩm</div>
                <div class="ct-col-label">Số lượng</div>
                <div class="ct-col-label">Số ngày</div>
                <div class="ct-col-label">Thành tiền</div>
                <div class="ct-col-label"></div>
            </div>

            <!-- Items -->
            <?php
            $cartLen = count($_SESSION['cart']);
            foreach ($_SESSION['cart'] as $idx => $item):
                $id       = (int)($item['id'] ?? 0);
                $days     = max(1, (int)($item['duration_days'] ?? 1));
                $price    = (int)($item['price'] ?? 0);
                $qty      = max(1, (int)($item['quantity'] ?? 1));
                $name     = $item['name'] ?? 'Sản phẩm';
                $subtotal = $price * $qty * $days;
                $raw_img  = $item['image'] ?? '';
                $img_show = !empty($raw_img) ? 'img/' . basename($raw_img) : 'img/default.jpg';
                $isLast   = ($idx === $cartLen - 1);
            ?>
            <div class="ct-item <?= !$isLast ? 'ct-item-border' : '' ?>" id="cart-item-<?= $id ?>">
                <!-- Image -->
                <div class="ct-item-img-wrap">
                    <img src="<?= htmlspecialchars($img_show) ?>"
                         class="ct-item-img"
                         alt="<?= htmlspecialchars($name) ?>"
                         onerror="this.src='img/default.jpg'">
                </div>

                <!-- Info -->
                <div class="ct-item-info">
                    <div class="ct-item-name"><?= htmlspecialchars($name) ?></div>
                    <?php if (!empty($item['variant_size']) || !empty($item['variant_color'])): ?>
                    <div class="ct-item-variant">
                        <i class="fa-solid fa-circle-dot" style="font-size:8px"></i>
                        <?= htmlspecialchars(trim(($item['variant_size'] ?? '') . ' · ' . ($item['variant_color'] ?? ''))) ?>
                    </div>
                    <?php endif; ?>
                    <div class="ct-item-price-day"><?= number_format($price) ?>đ / ngày</div>
                    <?php if (!empty($item['rental_start'])): ?>
                    <div class="ct-item-rental">
                        <i class="fa-regular fa-calendar"></i>
                        <?= htmlspecialchars($item['rental_start']) ?> → <?= htmlspecialchars($item['rental_end'] ?? '') ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Qty -->
                <div class="ct-qty-cell">
                    <div class="ct-qty-wrap">
                        <button type="button" class="ct-qty-btn" onclick="adjustQty(<?= $id ?>, -1)">−</button>
                        <input type="number" class="ct-qty-input" id="qty-<?= $id ?>"
                               name="qty[<?= $id ?>]" value="<?= $qty ?>" min="1" max="10"
                               onchange="recalcRow(<?= $id ?>, <?= $price ?>)">
                        <button type="button" class="ct-qty-btn" onclick="adjustQty(<?= $id ?>, 1)">+</button>
                    </div>
                </div>

                <!-- Days -->
                <div class="ct-days-cell">
                    <input type="number" class="ct-days-input" id="days-<?= $id ?>"
                           name="days[<?= $id ?>]" value="<?= $days ?>" min="1" max="30"
                           onchange="recalcRow(<?= $id ?>, <?= $price ?>)">
                    <span class="ct-days-label">ngày</span>
                </div>

                <!-- Subtotal -->
                <div class="ct-subtotal-cell" id="sub-<?= $id ?>">
                    <?= number_format($subtotal) ?>đ
                </div>

                <!-- Remove -->
                <div class="ct-remove-cell">
                    <button type="button" class="ct-remove-btn"
                            title="Xóa sản phẩm"
                            onclick="removeItem(<?= $id ?>)">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Panel Footer -->
            <div class="ct-panel-foot">
                <a href="ao_dai.php" class="ct-continue-link">
                    <i class="fa-solid fa-arrow-left"></i> Tiếp tục chọn đồ
                </a>
                <button type="submit" name="update_cart" class="ct-update-btn">
                    <i class="fa-solid fa-rotate"></i> Cập nhật giỏ hàng
                </button>
            </div>
        </div>
    </form>

    <!-- ── SUMMARY PANEL ── -->
    <div class="ct-summary">
        <div class="ct-summary-head">
            <div class="ct-summary-head-title">
                <i class="fa-solid fa-receipt" style="margin-right:8px;opacity:0.6"></i>
                Tóm tắt đơn hàng
            </div>
        </div>
        <div class="ct-summary-body">
            <div class="ct-summary-row">
                <span class="ct-summary-label">Số mặt hàng</span>
                <span class="ct-summary-value" id="sum-items"><?= count($_SESSION['cart']) ?></span>
            </div>
            <div class="ct-summary-row">
                <span class="ct-summary-label">Tổng số lượng</span>
                <span class="ct-summary-value" id="sum-qty"><?= $totalItems ?></span>
            </div>
            <div class="ct-summary-row">
                <span class="ct-summary-label">Tạm tính</span>
                <span class="ct-summary-value highlight" id="sum-subtotal"><?= number_format($total) ?>đ</span>
            </div>
            <div class="ct-summary-row">
                <span class="ct-summary-label">Giảm giá</span>
                <span class="ct-summary-value green">0đ</span>
            </div>
            <div class="ct-summary-total">
                <span class="ct-summary-total-label">Tổng cộng</span>
                <span class="ct-summary-total-value" id="sum-total"><?= number_format($total) ?>đ</span>
            </div>

            <a href="checkout.php" class="ct-checkout-btn">
                <i class="fa-solid fa-lock"></i> Tiến hành đặt thuê
            </a>
        </div>
        <div class="ct-note">
            <div class="ct-note-text">
                <i class="fa-solid fa-circle-info"></i>
                <span>Giá đã bao gồm phí vệ sinh. Đặt cọc sẽ được hoàn trả sau khi trả đồ đúng hạn và nguyên vẹn.</span>
            </div>
        </div>
    </div>

<?php endif; ?>

</div><!-- .ct-main -->
</div><!-- .ct-page -->

<div class="ct-toast" id="ctToast"></div>

<script>
(function() {
    function toast(msg, type = 'info') {
        const el = document.getElementById('ctToast');
        el.textContent = msg;
        el.className = 'ct-toast ' + type + ' show';
        clearTimeout(el._t);
        el._t = setTimeout(() => el.classList.remove('show'), 3000);
    }

    // ── Update Logic ──
    const inputs = document.querySelectorAll('.ct-qty-input, .ct-days-input');
    inputs.forEach(i => i.addEventListener('change', () => {
        // Debounce update to server
        clearTimeout(window.saveTimer);
        window.saveTimer = setTimeout(() => {
            const form = document.getElementById('cartForm');
            // Inject hidden update trigger
            if (!form.querySelector('input[name="update_cart"]')) {
                const h = document.createElement('input');
                h.type = 'hidden'; h.name = 'update_cart'; h.value = '1';
                form.appendChild(h);
            }
            form.submit();
        }, 800);
    }));

    window.adjustQty = function(id, delta) {
        const inp = document.getElementById('qty-' + id);
        if (!inp) return;
        let v = parseInt(inp.value) + delta;
        v = Math.max(1, Math.min(10, v));
        inp.value = v;
        const price = getPrice(id);
        recalcRow(id, price);

        // Auto save
        clearTimeout(window.saveTimer);
        window.saveTimer = setTimeout(() => {
            const form = document.getElementById('cartForm');
            if (!form.querySelector('input[name="update_cart"]')) {
                const h = document.createElement('input');
                h.type = 'hidden'; h.name = 'update_cart'; h.value = '1';
                form.appendChild(h);
            }
            form.submit();
        }, 800);
    };

    function getPrice(id) {
        // Read from data attribute injected per-item
        const row = document.getElementById('cart-item-' + id);
        return row ? parseInt(row.dataset.price || 0) : 0;
    }

    window.recalcRow = function(id, price) {
        const qty = parseInt(document.getElementById('qty-' + id)?.value || 1);
        const days = parseInt(document.getElementById('days-' + id)?.value || 1);
        const sub = price * qty * days;
        const subEl = document.getElementById('sub-' + id);
        if (subEl) subEl.textContent = sub.toLocaleString('vi-VN') + 'đ';
        updateTotal();
    };

    function updateTotal() {
        let total = 0;
        document.querySelectorAll('[id^="sub-"]').forEach(el => {
            total += parseInt(el.textContent.replace(/\D/g,'')) || 0;
        });
        const fmt = total.toLocaleString('vi-VN') + 'đ';
        const s = document.getElementById('sum-subtotal');
        const t = document.getElementById('sum-total');
        if (s) s.textContent = fmt;
        if (t) t.textContent = fmt;
    }

    // ── AJAX Remove ──
    window.removeItem = function(id) {
        const row = document.getElementById('cart-item-' + id);
        if (!row) return;
        row.classList.add('removing');
        setTimeout(() => {
            fetch('cart.php?ajax_remove=' + id)
            .then(r => r.json())
            .then(d => {
                if (d.ok) {
                    row.remove();
                    updateTotal();
                    // Update count badge in header
                    const badge = document.getElementById('cart-count');
                    if (badge) { badge.textContent = d.count; if (d.count <= 0) badge.remove(); }
                    toast('Đã xóa sản phẩm khỏi giỏ hàng.', 'info');
                    // If cart empty, reload to show empty state
                    if (!document.querySelector('[id^="cart-item-"]')) {
                        setTimeout(() => location.reload(), 500);
                    }
                }
            })
            .catch(() => { row.classList.remove('removing'); location.href = 'cart.php?remove_id=' + id; });
        }, 300);
    };

    // ── Store price in data attr for each row ──
    <?php foreach ($_SESSION['cart'] as $item): ?>
    (function() {
        const row = document.getElementById('cart-item-<?= (int)$item['id'] ?>');
        if (row) row.dataset.price = '<?= (int)$item['price'] ?>';
    })();
    <?php endforeach; ?>
})();
</script>

<?php include 'footer.php'; ?>