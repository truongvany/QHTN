<?php
require_once 'config.php';

$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($productId <= 0) {
    header('Location: index.php');
    exit();
}

$stmt = $conn->prepare("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ? LIMIT 1");
$stmt->execute([$productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: index.php');
    exit();
}

// Fallback image
$filename = basename($product['image'] ?? '');
$imgPath = !empty($filename) ? 'img/' . $filename : 'img/default.jpg';

// Variants
$variantStmt = $conn->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY size, color, id");
$variantStmt->execute([$productId]);
$variants = $variantStmt->fetchAll(PDO::FETCH_ASSOC);

// Related products same category
$related = [];
if (!empty($product['category_id'])) {
    $relStmt = $conn->prepare("SELECT id, name, price, image FROM products WHERE category_id = ? AND id <> ? ORDER BY id DESC LIMIT 4");
    $relStmt->execute([$product['category_id'], $productId]);
    $related = $relStmt->fetchAll(PDO::FETCH_ASSOC);
}

$categoryToCollection = [
    'Áo Dài' => 'ao_dai.php',
    'Giày' => 'giay.php',
    'Phụ kiện' => 'phu_kien.php',
    'Váy Thiết Kế' => 'vay_thiet_ke.php',
    'Váy Đi Biển' => 'vay_di_bien.php',
    'Set Quần Áo' => 'set_quan_ao.php'
];
$collectionLink = isset($categoryToCollection[$product['category_name'] ?? ''])
    ? $categoryToCollection[$product['category_name']]
    : 'index.php';

$sizeOptions = [];
$colorOptions = [];
$variantPayload = [];
foreach ($variants as $variant) {
    $size = trim((string)($variant['size'] ?? ''));
    $color = trim((string)($variant['color'] ?? ''));
    $priceUse = ($variant['price_override'] !== null && $variant['price_override'] !== '')
        ? (int)$variant['price_override']
        : (int)$product['price'];

    if ($size !== '' && !in_array($size, $sizeOptions, true)) {
        $sizeOptions[] = $size;
    }
    if ($color !== '' && !in_array($color, $colorOptions, true)) {
        $colorOptions[] = $color;
    }

    $variantPayload[] = [
        'id' => (int)$variant['id'],
        'size' => $size,
        'color' => $color,
        'stock' => (int)$variant['stock'],
        'price' => $priceUse
    ];
}

$colorMap = [
    'Đỏ' => '#d63333',
    'Hồng' => '#ea77ad',
    'Soft Pink' => '#eab7cb',
    'Rose' => '#cc637f',
    'Cream' => '#eadfc8',
    'Be' => '#dec8aa',
    'Đen' => '#2f2f2f',
    'Trắng' => '#f5f5f5'
];

$pageTitle = $product['name'] . ' | MinQuin';
include 'header.php';
?>

<style>
    :root {
        --pd-bg: #fff6fa;
        --pd-accent: #be4f71;
        --pd-accent-strong: #a43f60;
        --pd-border: #dfbfd0;
        --pd-text: #32232d;
        --pd-soft: #f8e8ef;
    }
    .pd-hero {
        padding: 48px 5%;
        background:
            radial-gradient(circle at 6% 8%, rgba(240, 194, 214, 0.65) 0, transparent 40%),
            radial-gradient(circle at 92% 12%, rgba(253, 229, 239, 0.95) 0, transparent 42%),
            #f9f0f5;
    }
    .pd-container { max-width: 1240px; margin: 0 auto; }
    .pd-main { display: grid; grid-template-columns: minmax(300px, 440px) 1fr; gap: 34px; align-items: start; }
    .pd-image-wrap {
        background: #fff;
        border-radius: 16px;
        border: 1px solid #e4d0db;
        padding: 10px;
        box-shadow: 0 20px 38px rgba(84, 47, 67, 0.13);
        position: sticky;
        top: 90px;
    }
    .pd-image-wrap img {
        width: 100%;
        height: clamp(460px, 68vh, 760px);
        object-fit: cover;
        border-radius: 12px;
    }
    .pd-panel {
        background: rgba(255,255,255,0.72);
        border: 1px solid #ead4df;
        border-radius: 18px;
        padding: 28px;
        backdrop-filter: blur(2px);
    }
    .pd-meta {
        margin: 0;
        text-transform: uppercase;
        font-size: 14px;
        letter-spacing: 2px;
        color: #674755;
    }
    .pd-title {
        margin: 8px 0 10px;
        font-size: clamp(24px, 3.5vw, 40px);
        line-height: 1.08;
        font-family: "Playfair Display", "Cormorant Garamond", serif;
        color: #2b1c24;
        font-weight: 700;
    }
    .pd-price {
        color: #b57182;
        font-size: clamp(22px, 3vw, 34px);
        font-weight: 700;
        letter-spacing: 0.8px;
        margin: 0 0 16px;
        font-family: "Cormorant Garamond", serif;
    }
    .pd-choice-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 22px;
        margin-bottom: 14px;
    }
    .pd-choice-title {
        font-size: 17px;
        font-weight: 800;
        color: var(--pd-text);
        margin-bottom: 10px;
    }
    .pd-size-list,
    .pd-color-list {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }
    .pd-size-item,
    .pd-color-item {
        position: relative;
        cursor: pointer;
    }
    .pd-size-item input,
    .pd-color-item input {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
    }
    .pd-size-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 58px;
        height: 44px;
        border-radius: 12px;
        border: 1px solid var(--pd-border);
        background: #fff;
        font-size: 18px;
        font-weight: 700;
        color: #32252c;
        transition: 0.2s ease;
    }
    .pd-size-item:has(input:checked) .pd-size-pill {
        background: #eec7d6;
        border-color: #c87998;
    }
    .pd-color-chip {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }
    .pd-color-dot {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        border: 2px solid #ca93a7;
        box-shadow: inset 0 0 0 2px rgba(255,255,255,0.8);
        transition: 0.2s ease;
    }
    .pd-color-name {
        font-size: 14px;
        font-weight: 600;
        color: #4f3e47;
    }
    .pd-color-item:has(input:checked) .pd-color-dot {
        transform: translateY(-1px);
        box-shadow: inset 0 0 0 2px rgba(255,255,255,0.8), 0 0 0 4px rgba(190, 79, 113, 0.26);
    }
    .pd-color-item:has(input:checked) .pd-color-name {
        color: #d02d54;
    }
    .pd-date-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        margin-top: 4px;
    }
    .pd-date-field label {
        display: block;
        font-size: 16px;
        font-weight: 800;
        color: #24171e;
        margin-bottom: 6px;
    }
    .pd-date-field input {
        width: 100%;
        padding: 12px 14px;
        border: 1px solid #dcc5d1;
        border-radius: 12px;
        font-size: 17px;
        color: #2d2028;
        background: #fff;
    }
    .pd-summary {
        display: grid;
        grid-template-columns: 1fr auto;
        align-items: center;
        margin-top: 14px;
        padding: 14px 16px;
        border-radius: 12px;
        background: #f2e4ea;
        font-size: 20px;
        color: #2e2028;
        font-weight: 700;
    }
    .pd-summary strong { font-size: 37px; }
    .pd-actions {
        display: grid;
        grid-template-columns: minmax(240px, 1fr) minmax(220px, 320px);
        gap: 14px;
        margin-top: 16px;
    }
    .pd-btn {
        height: 56px;
        border-radius: 999px;
        font-size: 18px;
        font-weight: 800;
        border: none;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .pd-btn:hover { transform: translateY(-1px); }
    .pd-btn-primary {
        background: linear-gradient(150deg, #dd8ca8 0%, #ab415f 100%);
        color: #fff;
        box-shadow: 0 10px 20px rgba(154, 58, 89, 0.32);
    }
    .pd-btn-primary:disabled {
        background: #c4b7bd;
        box-shadow: none;
        cursor: not-allowed;
        transform: none;
    }
    .pd-btn-outline {
        background: #fff;
        color: #9f5b72;
        border: 2px solid #b8788f;
        text-align: center;
        line-height: 52px;
    }
    .pd-hint {
        min-height: 20px;
        margin: 8px 0 0;
        font-size: 14px;
        color: #865f6f;
        font-weight: 600;
    }
    .pd-feature-grid {
        margin-top: 18px;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }
    .pd-feature {
        border: 1px solid #dab5c4;
        border-radius: 14px;
        padding: 14px;
        background: #fff;
    }
    .pd-feature i {
        font-size: 20px;
        color: #8f4d63;
        margin-bottom: 8px;
    }
    .pd-feature h3 {
        font-size: 17px;
        margin: 0 0 8px;
        color: #25161d;
    }
    .pd-feature p {
        margin: 0;
        color: #43333b;
        line-height: 1.5;
        font-size: 15px;
    }
    .pd-section {
        padding: 44px 5% 66px;
        background: #fff9fc;
    }
    .pd-section h2 {
        margin: 0 0 18px;
        font-size: 30px;
        color: #291a22;
    }
    .pd-related {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
    }
    .pd-card {
        background: #fff;
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid #ecd6e0;
        box-shadow: 0 10px 24px rgba(66, 39, 52, 0.08);
    }
    .pd-card img { width: 100%; height: 220px; object-fit: cover; }
    .pd-card-body { padding: 14px; }
    .pd-card h4 { margin: 0 0 6px; font-size: 17px; }
    .pd-card .price { color: var(--pd-accent-strong); font-weight: 700; margin-bottom: 10px; }
    .pd-card button {
        width: 100%;
        border: none;
        border-radius: 10px;
        height: 40px;
        background: linear-gradient(140deg, #d583a0, #a74464);
        color: #fff;
        font-weight: 700;
        cursor: pointer;
    }

    @media (max-width: 1024px) {
        .pd-main { grid-template-columns: 1fr; }
        .pd-image-wrap { position: static; }
        .pd-feature-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 760px) {
        .pd-choice-grid,
        .pd-date-grid,
        .pd-actions { grid-template-columns: 1fr; }
        .pd-title { font-size: 42px; }
        .pd-price { font-size: 34px; }
        .pd-summary { font-size: 18px; }
        .pd-summary strong { font-size: 28px; }
    }
</style>

<div class="pd-hero">
    <div class="pd-container pd-main">
        <div class="pd-image-wrap">
            <img src="<?= htmlspecialchars($imgPath) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
        </div>
        <div class="pd-panel">
            <p class="pd-meta"><?= htmlspecialchars($product['category_name'] ?? 'Bộ sưu tập') ?></p>
            <h1 class="pd-title"><?= htmlspecialchars($product['name']) ?></h1>
            <div class="pd-price" id="pd-price" data-base-price="<?= (int)$product['price']; ?>"><?= number_format($product['price']) ?> VNĐ / ngày</div>
            <?php if (count($variantPayload) > 0): ?>
                <div class="pd-choice-grid">
                    <div>
                        <div class="pd-choice-title">Chọn size:</div>
                        <div class="pd-size-list" id="pd-size-list">
                            <?php foreach ($sizeOptions as $size): ?>
                                <label class="pd-size-item">
                                    <input type="radio" name="pd_size" value="<?= htmlspecialchars($size); ?>">
                                    <span class="pd-size-pill"><?= htmlspecialchars($size); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div>
                        <div class="pd-choice-title">Chọn màu:</div>
                        <div class="pd-color-list" id="pd-color-list">
                            <?php foreach ($colorOptions as $color):
                                $swatch = isset($colorMap[$color]) ? $colorMap[$color] : '#e4c7d4';
                            ?>
                                <label class="pd-color-item">
                                    <input type="radio" name="pd_color" value="<?= htmlspecialchars($color); ?>">
                                    <span class="pd-color-chip">
                                        <span class="pd-color-dot" style="background: <?= htmlspecialchars($swatch); ?>;"></span>
                                        <span class="pd-color-name"><?= htmlspecialchars($color); ?></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="pd-hint" id="variant_hint">Chọn size và màu để kiểm tra tồn kho trước khi thêm vào giỏ.</div>
            <?php else: ?>
                <div class="pd-hint" id="variant_hint">Sản phẩm không có biến thể, có thể thêm trực tiếp vào giỏ.</div>
            <?php endif; ?>

            <div class="pd-date-grid">
                <div class="pd-date-field">
                    <label for="rental_start">Ngày nhận</label>
                    <input type="date" id="rental_start" />
                </div>
                <div class="pd-date-field">
                    <label for="rental_end">Ngày trả</label>
                    <input type="date" id="rental_end" />
                </div>
            </div>

            <div class="pd-summary">
                <div>Thời gian: <strong id="rental_days">0 ngày</strong></div>
                <div>Tổng: <strong id="rental_total">0 VND</strong></div>
            </div>

            <div class="pd-actions">
                <button class="pd-btn pd-btn-primary" id="add_to_cart_btn" onclick="addToCartAjax(<?= $product['id'] ?>)">Thêm vào giỏ hàng</button>
                <a class="pd-btn pd-btn-outline" href="<?= htmlspecialchars($collectionLink); ?>">Xem bộ sưu tập</a>
            </div>

            <div class="pd-feature-grid">
                <div class="pd-feature">
                    <i class="fa-regular fa-file-invoice-dollar"></i>
                    <h3>Phí thuê & Cọc</h3>
                    <p>Phí thuê: <span id="pd-fee"><?= number_format($product['price']) ?></span>đ / ngày<br>Cọc: <?= $product['deposit'] !== null ? number_format((int)$product['deposit']) . 'đ' : 'Liên hệ'; ?></p>
                </div>
                <div class="pd-feature">
                    <i class="fa-regular fa-badge-check"></i>
                    <h3>Tình trạng</h3>
                    <p><?= $product['condition_note'] ? htmlspecialchars($product['condition_note']) : 'Đã vệ sinh, sẵn sàng cho thuê'; ?><br>Liên hệ để đặt lịch thử.</p>
                </div>
                <div class="pd-feature">
                    <i class="fa-solid fa-shirt"></i>
                    <h3>Chất liệu & Bảo quản</h3>
                    <p><?= htmlspecialchars($product['material'] ?: 'Đang cập nhật chất liệu'); ?><br><?= htmlspecialchars($product['care'] ?: 'Làm sạch theo hướng dẫn của cửa hàng'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="pd-section">
    <div class="pd-container">
        <h2>Sản phẩm liên quan</h2>
        <?php if (count($related) > 0): ?>
            <div class="pd-related">
                <?php foreach ($related as $rel): 
                    $relFile = basename($rel['image'] ?? '');
                    $relImg = !empty($relFile) ? 'img/' . $relFile : 'img/default.jpg';
                ?>
                    <div class="pd-card">
                        <img src="<?= htmlspecialchars($relImg) ?>" alt="<?= htmlspecialchars($rel['name']) ?>">
                        <div class="pd-card-body">
                            <h4><?= htmlspecialchars($rel['name']) ?></h4>
                            <div class="price"><?= number_format($rel['price']) ?>đ / ngày</div>
                            <a href="product_detail.php?id=<?= $rel['id'] ?>">
                                <button>Xem chi tiết</button>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color:#666;">Chưa có sản phẩm liên quan.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    const variantData = <?= json_encode($variantPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const priceBase = <?= (int)$product['price']; ?>;
    let pricePerDay = priceBase;
    let currentVariant = null;
    const hasSizeOptions = document.querySelectorAll('input[name="pd_size"]').length > 0;
    const hasColorOptions = document.querySelectorAll('input[name="pd_color"]').length > 0;

    const startInput = document.getElementById('rental_start');
    const endInput = document.getElementById('rental_end');
    const priceEl = document.getElementById('pd-price');
    const feeEl = document.getElementById('pd-fee');
    const totalEl = document.getElementById('rental_total');
    const daysEl = document.getElementById('rental_days');
    const hintEl = document.getElementById('variant_hint');
    const addBtn = document.getElementById('add_to_cart_btn');

    function formatMoney(value) {
        return new Intl.NumberFormat('vi-VN').format(value);
    }

    function getSelectedValue(name) {
        const checked = document.querySelector('input[name="' + name + '"]:checked');
        return checked ? checked.value : '';
    }

    function calcRental() {
        const start = startInput.value ? new Date(startInput.value) : null;
        const end = endInput.value ? new Date(endInput.value) : null;
        let days = 0;

        if (start && end && end >= start) {
            const diff = (end - start) / (1000 * 60 * 60 * 24) + 1;
            days = Math.max(1, Math.round(diff));
        }

        daysEl.textContent = days + ' ngày';
        totalEl.textContent = days > 0 ? formatMoney(days * pricePerDay) + ' VND' : '0 VND';
        return days;
    }

    function syncVariantState() {
        if (!variantData.length) {
            currentVariant = null;
            pricePerDay = priceBase;
            priceEl.textContent = formatMoney(pricePerDay) + ' VNĐ / ngày';
            feeEl.textContent = formatMoney(pricePerDay);
            calcRental();
            return;
        }

        const size = getSelectedValue('pd_size');
        const color = getSelectedValue('pd_color');

        if ((hasSizeOptions && !size) || (hasColorOptions && !color)) {
            currentVariant = null;
            if (hasSizeOptions && !size && hasColorOptions && !color) {
                hintEl.textContent = 'Chọn đủ size và màu để thêm vào giỏ.';
            } else if (hasSizeOptions && !size) {
                hintEl.textContent = 'Vui lòng chọn size để tiếp tục.';
            } else {
                hintEl.textContent = 'Vui lòng chọn màu để tiếp tục.';
            }
            pricePerDay = priceBase;
            priceEl.textContent = formatMoney(pricePerDay) + ' VNĐ / ngày';
            feeEl.textContent = formatMoney(pricePerDay);
            calcRental();
            return;
        }

        const matched = variantData.find(function(item) {
            if (hasSizeOptions && item.size !== size) return false;
            if (hasColorOptions && item.color !== color) return false;
            return Number(item.stock) > 0;
        });

        if (!matched || Number(matched.stock) <= 0) {
            currentVariant = null;
            hintEl.textContent = 'Biến thể đã chọn hiện hết hàng, vui lòng chọn tổ hợp khác.';
            pricePerDay = priceBase;
            priceEl.textContent = formatMoney(pricePerDay) + ' VNĐ / ngày';
            feeEl.textContent = formatMoney(pricePerDay);
            calcRental();
            return;
        }

        currentVariant = matched;
        pricePerDay = Number(matched.price) || priceBase;
        hintEl.textContent = 'Đã chọn ' + size + ' / ' + color + ' · Còn ' + matched.stock + ' sản phẩm';
        priceEl.textContent = formatMoney(pricePerDay) + ' VNĐ / ngày';
        feeEl.textContent = formatMoney(pricePerDay);
        calcRental();
    }

    function ensureDefaults() {
        const today = new Date();
        const tomorrow = new Date(Date.now() + 24 * 3600 * 1000);
        const isoToday = today.toISOString().slice(0, 10);
        const isoTomorrow = tomorrow.toISOString().slice(0, 10);

        if (!startInput.value) startInput.value = isoToday;
        if (!endInput.value) endInput.value = isoTomorrow;
    }

    function addToCartAjax(productId) {
        const days = calcRental();
        const startVal = startInput.value;
        const endVal = endInput.value;

        if (!startVal || !endVal) {
            alert('Vui lòng chọn ngày nhận và ngày trả');
            return;
        }
        if (days <= 0) {
            alert('Ngày trả phải sau hoặc bằng ngày nhận');
            return;
        }

        if (variantData.length && !currentVariant) {
            alert('Vui lòng chọn size và màu hợp lệ trước khi thêm vào giỏ');
            return;
        }

        const formData = new FormData();
        formData.append('id', productId);
        formData.append('rental_start', startVal);
        formData.append('rental_end', endVal);
        formData.append('duration_days', days);

        if (currentVariant) {
            formData.append('variant_id', currentVariant.id);
            formData.append('variant_size', currentVariant.size || '');
            formData.append('variant_color', currentVariant.color || '');
        }

        fetch('add_to_cart_ajax.php', { method: 'POST', body: formData })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.status === 'success') {
                    alert('Đã thêm vào giỏ hàng');
                } else if (data.status === 'login_required') {
                    alert(data.message);
                    window.location.href = 'login.php';
                } else {
                    alert('Lỗi: ' + data.message);
                }
            })
            .catch(function(err) {
                console.error(err);
                alert('Có lỗi xảy ra');
            });
    }

    startInput.addEventListener('change', calcRental);
    endInput.addEventListener('change', calcRental);
    document.querySelectorAll('input[name="pd_size"], input[name="pd_color"]').forEach(function(el) {
        el.addEventListener('change', syncVariantState);
    });

    ensureDefaults();
    calcRental();
    syncVariantState();
</script>

<?php include 'footer.php'; ?>
