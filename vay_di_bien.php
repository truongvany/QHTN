<?php 
require_once 'config.php'; 
include 'header.php'; 

$category_id = 6; // Váy đi biển

$size = isset($_GET['size']) ? trim($_GET['size']) : '';
$color = isset($_GET['color']) ? trim($_GET['color']) : '';
$min_price = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (int)$_GET['min_price'] : '';
$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (int)$_GET['max_price'] : '';
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';
$startDateTime = $start_date ? $start_date . ' 00:00:00' : null;
$endDateTime = $end_date ? $end_date . ' 23:59:59' : null;

$optStmt = $conn->prepare("SELECT DISTINCT pv.size, pv.color FROM product_variants pv JOIN products p ON p.id = pv.product_id WHERE p.category_id = ? AND pv.stock > 0");
$optStmt->execute([$category_id]);
$sizes = [];
$colors = [];
while ($opt = $optStmt->fetch(PDO::FETCH_ASSOC)) {
    if (!empty($opt['size']) && !in_array($opt['size'], $sizes)) {
        $sizes[] = $opt['size'];
    }
    if (!empty($opt['color']) && !in_array($opt['color'], $colors)) {
        $colors[] = $opt['color'];
    }
}

$sql = "SELECT p.* FROM products p JOIN product_variants pv ON pv.product_id = p.id WHERE p.category_id = ? AND pv.stock > 0";
$params = [$category_id];

if ($size !== '') {
    $sql .= " AND pv.size = ?";
    $params[] = $size;
}
if ($color !== '') {
    $sql .= " AND pv.color = ?";
    $params[] = $color;
}
if ($min_price !== '') {
    $sql .= " AND p.price >= ?";
    $params[] = $min_price;
}
if ($max_price !== '') {
    $sql .= " AND p.price <= ?";
    $params[] = $max_price;
}
if ($startDateTime && $endDateTime) {
    $sql .= " AND NOT EXISTS (SELECT 1 FROM order_details od JOIN orders o ON o.id = od.order_id WHERE od.variant_id = pv.id AND o.status IN ('pending','confirmed','ongoing') AND NOT (od.rental_end < ? OR od.rental_start > ?))";
    $params[] = $startDateTime;
    $params[] = $endDateTime;
}

$sql .= " GROUP BY p.id";

// Đếm tổng số lượng cho phân trang
$countSql = "SELECT COUNT(*) FROM ($sql) AS count_query";
$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);
$total_items = $countStmt->fetchColumn();

// Xử lý biến trang
$limit = 15;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$total_pages = ceil($total_items / $limit);
if ($total_pages > 0 && $page > $total_pages) $page = $total_pages;

$offset = ($page - 1) * $limit;

// Thêm sắp xếp và limit offset
$sql .= " ORDER BY p.id DESC LIMIT $limit OFFSET $offset";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container" style="padding: 40px 5%; min-height: 60vh;">
    <div class="section-heading">
        <h2 class="section-title"><i class="fa-solid fa-umbrella-beach"></i> Bộ Sưu Tập Váy Đi Biển</h2>
    </div>

    <div class="catalog-layout">
        <div class="filter-card">
            <div class="filter-header">
                <div class="filter-eyebrow">Bộ lọc</div>
                <div class="filter-title">Tìm mẫu phù hợp</div>
            </div>
            <form class="filter-form auto-filter-form" method="get">
                <div class="filter-section">
                    <h4>Kích cỡ</h4>
                    <div class="pill-options">
                        <label class="pill-btn <?= $size === '' ? 'active' : ''; ?>">
                            <input type="radio" name="size" value="" <?= $size === '' ? 'checked' : ''; ?>>
                            <span class="pill-content">Tất cả</span>
                        </label>
                        <?php foreach ($sizes as $s): ?>
                            <label class="pill-btn <?= $size === $s ? 'active' : ''; ?>">
                                <input type="radio" name="size" value="<?= htmlspecialchars($s); ?>" <?= $size === $s ? 'checked' : ''; ?>>
                                <span class="pill-content"><?= htmlspecialchars($s); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="filter-section">
                    <h4>Màu sắc</h4>
                    <?php
                        $colorSwatches = [
                            'Đỏ' => '#d32f2f',
                            'Hồng' => '#ff7fbf',
                            'Đen' => '#2f2f2f',
                            'Be' => '#e3c9b3',
                            'Trắng' => '#ffffff'
                        ];
                    ?>
                    <div class="pill-options">
                        <label class="pill-btn <?= $color === '' ? 'active' : ''; ?> color-chip">
                            <input type="radio" name="color" value="" <?= $color === '' ? 'checked' : ''; ?>>
                            <span class="pill-content"><span class="color-swatch" style="background: linear-gradient(135deg, #eea5bf 0%, #f2dde9 100%); border: 1px solid #f0c0d5;"></span><span>Tất cả</span></span>
                        </label>
                        <?php foreach ($colors as $c): ?>
                            <?php $swatch = isset($colorSwatches[$c]) ? $colorSwatches[$c] : '#f0c0d5'; ?>
                            <label class="pill-btn <?= $color === $c ? 'active' : ''; ?> color-chip">
                                <input type="radio" name="color" value="<?= htmlspecialchars($c); ?>" <?= $color === $c ? 'checked' : ''; ?>>
                                <span class="pill-content"><span class="color-swatch" style="background: <?= $swatch; ?>; border: 1px solid rgba(0,0,0,0.16);"></span><span><?= htmlspecialchars($c); ?></span></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="filter-section">
                    <h4>Khoảng giá</h4>
                    <div class="price-range">
                        <div class="range-label">
                            <span class="range-value" id="price_range_label"><?= number_format($min_price ?: 0); ?>đ - <?= number_format($max_price ?: 2000000); ?>đ</span>
                        </div>
                        <div class="range-track">
                            <input type="range" name="min_price" id="min_price" min="0" max="2000000" step="50000" value="<?= htmlspecialchars($min_price ?: 0); ?>">
                            <input type="range" name="max_price" id="max_price" min="0" max="2000000" step="50000" value="<?= htmlspecialchars($max_price ?: 2000000); ?>">
                        </div>
                    </div>
                </div>

            </form>
        </div>

        <div>
            <div class="product-grid">
                <?php
                if(count($products) > 0):
                    foreach ($products as $row):
                        $db_img = $row['image'];
                        $filename = basename($db_img);
                        $img_path = 'img/' . $filename;
                        
                        if(empty($filename) || $filename == 'default.jpg') {
                            $img_path = 'img/default.jpg';
                        }
                ?>
                    <div class="product-card">
                        <a class="product-img-wrapper" href="product_detail.php?id=<?php echo $row['id']; ?>">
                            <img src="<?php echo htmlspecialchars($img_path); ?>" 
                                 alt="<?php echo htmlspecialchars($row['name']); ?>">
                        </a>
                        
                        <div class="product-info">
                            <a href="product_detail.php?id=<?php echo $row['id']; ?>">
                                <h3 class="product-name"><?php echo htmlspecialchars($row['name']); ?></h3>
                            </a>
                            <div class="product-price"><?php echo number_format($row['price']); ?> VNĐ / ngày</div>
                            
                            <div class="card-actions">
                                <a href="product_detail.php?id=<?php echo $row['id']; ?>" class="btn-pill primary">
                                    <i class="fa-solid fa-eye"></i> Xem chi tiết
                                </a>
                            </div>
                        </div>
                    </div>
                <?php 
                    endforeach; 
                else: 
                ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 50px; color: #777;">
                        <i class="fa-regular fa-folder-open" style="font-size: 60px; margin-bottom: 20px; color: #ddd;"></i>
                        <p>Hiện chưa có mẫu váy đi biển nào được cập nhật.</p>
                        <a href="index.php" style="color: var(--accent-pink, #ff4757); text-decoration: underline;">Quay về trang chủ</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- ====== PHÂN TRANG ====== -->
            <?php if (isset($total_pages) && $total_pages > 1): ?>
                <div class="pagination-premium">
                    <?php 
                    $queryParams = $_GET;
                    
                    $prevPage = max(1, $page - 1);
                    $queryParams['page'] = $prevPage;
                    $prevUrl = '?' . http_build_query($queryParams);
                    
                    $nextPage = min($total_pages, $page + 1);
                    $queryParams['page'] = $nextPage;
                    $nextUrl = '?' . http_build_query($queryParams);
                    ?>
                    
                    <a href="<?= htmlspecialchars($prevUrl) ?>" class="page-link <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <i class="fa-solid fa-chevron-left"></i> Pre
                    </a>
                    
                    <div class="page-numbers">
                        <?php 
                        // Hiển thị tối đa 5 số trang ở giữa
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        // Chỉnh lề lệch nếu ở đầu / cuối để vẫn giữ đủ 5 nút
                        if ($end_page - $start_page < 4) {
                            if ($start_page == 1) {
                                $end_page = min($total_pages, 5);
                            } else if ($end_page == $total_pages) {
                                $start_page = max(1, $total_pages - 4);
                            }
                        }
                        
                        if ($start_page > 1): ?>
                            <a href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['page' => 1]))) ?>" class="page-num" title="Trang đầu">1</a>
                            <?php if ($start_page > 2): ?>
                                <span class="page-dots">...</span>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $start_page; $i <= $end_page; $i++): 
                            $queryParams['page'] = $i;
                            $pageUrl = '?' . http_build_query($queryParams);
                        ?>
                            <a href="<?= htmlspecialchars($pageUrl) ?>" class="page-num <?= ($i == $page) ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <span class="page-dots">...</span>
                            <?php endif; ?>
                            <a href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['page' => $total_pages]))) ?>" class="page-num" title="Trang cuối"><?= $total_pages ?></a>
                        <?php endif; ?>
                    </div>
                    
                    <a href="<?= htmlspecialchars($nextUrl) ?>" class="page-link <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                        Next <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </div>
            <?php endif; ?>
            <!-- ====== HẾT PHÂN TRANG ====== -->

        </div>
    </div>
</div>
<style>
.pagination-premium {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 12px;
    margin-top: 50px;
    margin-bottom: 20px;
    font-family: inherit;
}
.pagination-premium a {
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}
.page-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 24px;
    background: #fff;
    border: 1px solid #ffb6c1;
    color: #ff4757;
    border-radius: 9999px;
    font-weight: 600;
    font-size: 14px;
    box-shadow: 0 4px 10px rgba(255, 182, 193, 0.15);
}
.page-link i {
    font-size: 12px;
}
.page-link:hover:not(.disabled) {
    background: #ff4757;
    border-color: #ff4757;
    color: #fff;
    box-shadow: 0 6px 15px rgba(255, 71, 87, 0.3);
    transform: translateY(-2px);
}
.page-link.disabled {
    opacity: 0.4;
    pointer-events: none;
    border-color: #eee;
    color: #aaa;
    background: #fafafa;
    box-shadow: none;
}
.page-numbers {
    display: flex;
    gap: 6px;
    align-items: center;
}
.page-num {
    width: 40px;
    height: 40px;
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 50%;
    color: #555;
    font-weight: 600;
    font-size: 14px;
    border: 1px solid transparent;
}
.page-num:hover:not(.active) {
    background: #fff0f5;
    color: #ff4757;
}
.page-num.active {
    background: #ff4757;
    color: #fff;
    box-shadow: 0 4px 12px rgba(255, 71, 87, 0.3);
    pointer-events: none;
}
.page-dots {
    color: #ffb6c1;
    font-weight: bold;
    letter-spacing: 2px;
    padding: 0 5px;
}
</style>


<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.filter-form .pill-btn input').forEach(function(input) {
        if (input.checked) {
            const btn = input.closest('.pill-btn');
            if (btn) btn.classList.add('active');
        }
        input.addEventListener('change', function() {
            const name = input.name;
            document.querySelectorAll('.filter-form input[name="' + name + '"]').forEach(function(i) {
                const b = i.closest('.pill-btn');
                if (b) b.classList.remove('active');
            });
            const btn = input.closest('.pill-btn');
            if (btn) btn.classList.add('active');
        });
    });
});
</script>

<?php include 'footer.php'; ?>