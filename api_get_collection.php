<?php
// API endpoint trả về HTML cho khu vực "Bộ Sưu Tập Mới" dựa trên category_id
require_once 'config.php';

if (!isset($_GET['category_id'])) {
    exit('Invalid Request');
}

$category_id = (int)$_GET['category_id'];

// Lấy 9 sản phẩm thuộc danh mục này (1 cái lớn, 8 cái nhỏ)
$stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = :cat_id ORDER BY id DESC LIMIT 20");
$stmt->execute(['cat_id' => $category_id]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($products)) {
    echo '<div style="grid-column: 1 / -1; padding: 60px; text-align: center; color: #888; font-size: 15px; background: #fafafa; border-radius: 8px;">Hiện tại chưa có sản phẩm nào cho danh mục này.</div>';
    exit;
}

// Sản phẩm đầu tiên làm banner lớn
$big_product = $products[0];

// Các sản phẩm còn lại làm thẻ nhỏ
$small_products = array_slice($products, 1, 8);
?>

<!-- Big Card (Hình ảnh banner đứng) -->
<div class="collection-big-card">
    <img src="img/<?php echo basename($big_product['image']); ?>" alt="<?php echo htmlspecialchars($big_product['name']); ?>">
</div>

<!-- Sản phẩm nhỏ -->
<?php foreach ($small_products as $product): ?>
    <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="cute-product-card">
        <img class="cute-product-img" src="img/<?php echo basename($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
        <div class="cute-product-info">
            <div class="cute-product-name"><?php echo htmlspecialchars($product['name']); ?></div>
            <div class="cute-product-price"><?php echo number_format($product['price']); ?>đ</div>
        </div>
    </a>
<?php endforeach; ?>
