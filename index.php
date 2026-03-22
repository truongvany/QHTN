<?php
// 1. Kết nối CSDL & Setup
require_once 'config.php';
$is_homepage = true;

// 2. Fetch dữ liệu
// Lấy tất cả danh mục
$stmt = $pdo->query("SELECT * FROM categories ORDER BY id ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy sản phẩm áo dài (category_id = 1)
$stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = 1 ORDER BY id DESC LIMIT 20");
$stmt->execute();
$aodai_products = $stmt->fetchAll();

// Lấy sản phẩm nổi bật (featured) - lấy 8 sản phẩm ngẫu nhiên
$stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = 1 ORDER BY RAND() LIMIT 8");
$stmt->execute();
$featured_products = $stmt->fetchAll();

// Lấy sản phẩm mới nhất (trending)
$stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = 1 ORDER BY id DESC LIMIT 16");
$stmt->execute();
$trending_products = $stmt->fetchAll();

include 'header.php';
?>


<style>
    /* ===== HERO SECTION ===== */
    .hero-banner {
        position: relative;
        height: 60vh;
        background: linear-gradient(135deg, #fff0f7 0%, #ffd6e6 50%, #fff5f0 100%);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 60px 5%;
        overflow: hidden;
        margin-bottom: 80px;
    }

    .hero-content {
        flex: 1;
        z-index: 2;
        max-width: 500px;
    }

    .hero-content h1 {
        font-size: 48px;
        font-weight: 800;
        color: var(--text-color);
        margin-bottom: 16px;
        line-height: 1.2;
        letter-spacing: -1px;
    }

    .hero-content p {
        font-size: 16px;
        color: #666;
        margin-bottom: 28px;
        line-height: 1.8;
    }

    .hero-cta {
        display: inline-block;
        padding: 14px 40px;
        background: var(--accent-pink);
        color: white;
        border-radius: 50px;
        font-weight: 600;
        box-shadow: 0 8px 25px rgba(233, 90, 138, 0.3);
        transition: all 0.3s ease;
    }

    .hero-cta:hover {
        background: #d54f7b;
        transform: translateY(-2px);
        box-shadow: 0 12px 35px rgba(233, 90, 138, 0.4);
    }

    .hero-image-row {
        flex: 1;
        position: relative;
        height: 100%;
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: stretch;
        z-index: 1;
        gap: 12px;
    }

    .hero-thumb-horizontal {
        flex: 1;
        overflow: hidden;
        border-radius: 16px;
        box-shadow: 0 15px 40px rgba(0,0,0,0.09);
        position: relative;
        background: white;
        border: 1px solid rgba(233, 90, 138, 0.2);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .hero-thumb-horizontal:hover {
        transform: translateY(-6px);
        box-shadow: 0 22px 48px rgba(0,0,0,0.16);
    }

    .hero-thumb-horizontal img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .hero-thumb-label {
        position: absolute;
        bottom: 10px;
        left: 10px;
        right: 10px;
        background: rgba(255,255,255,0.85);
        color: #2f1c26;
        font-size: 12px;
        font-weight: 700;
        padding: 6px 10px;
        border-radius: 999px;
        text-align: center;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    @media (max-width: 1024px) {
        .hero-image-row {
            flex-direction: column;
            height: auto;
        }

        .hero-thumb-horizontal {
            min-height: 180px;
        }
    }

    .hero-badge {
        display: inline-block;
        background: rgba(233, 90, 138, 0.1);
        color: var(--accent-pink);
        padding: 8px 16px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 12px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* ===== CATEGORIES SECTION ===== */
    .categories-section {
        text-align: center;
        margin-bottom: 80px;
    }

    .categories-title {
        font-size: 18px;
        font-weight: 700;
        color: var(--text-color);
        margin-bottom: 32px;
    }

    .categories-grid {
        display: flex;
        justify-content: center;
        gap: 16px;
        flex-wrap: wrap;
        max-width: 1000px;
        margin: 0 auto;
    }

    .category-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px 24px;
        background: white;
        border: 2px solid #f0f0f0;
        border-radius: 50px;
        font-size: 14px;
        font-weight: 600;
        color: var(--text-color);
        cursor: pointer;
        transition: all 0.3s ease;
        min-width: 100px;
    }

    .category-chip:hover,
    .category-chip.active {
        background: var(--primary-pink);
        border-color: var(--accent-pink);
        color: var(--accent-pink);
    }

    .category-chip i {
        font-size: 16px;
    }

    /* ===== PRODUCTS SECTION HEADER ===== */
    .section-header-premium {
        text-align: center;
        margin: 80px 0 50px;
        position: relative;
    }

    .section-header-premium h2 {
        font-size: 42px;
        font-weight: 800;
        color: var(--text-color);
        margin-bottom: 16px;
        letter-spacing: -1px;
    }

    .section-header-premium p {
        font-size: 14px;
        color: #999;
        font-weight: 500;
        letter-spacing: 2px;
        text-transform: uppercase;
        margin-bottom: 32px;
    }

    .section-accent {
        width: 60px;
        height: 4px;
        background: linear-gradient(90deg, transparent, var(--accent-pink), transparent);
        margin: 0 auto 20px;
    }

    /* ===== PRODUCT GRID ===== */
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 32px;
        margin-bottom: 80px;
        max-width: 1400px;
        margin-left: auto;
        margin-right: auto;
    }

    .product-card-premium {
        group;
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .product-card-premium:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 35px rgba(233, 90, 138, 0.15);
    }

    .product-img-container {
        position: relative;
        overflow: hidden;
        aspect-ratio: 3/4;
        background: #f9f9f9;
    }

    .product-img-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.4s ease;
    }

    .product-card-premium:hover .product-img-container img {
        transform: scale(1.08);
    }

    .product-badge {
        position: absolute;
        top: 16px;
        right: 16px;
        background: var(--accent-pink);
        color: white;
        padding: 6px 12px;
        border-radius: 50px;
        font-size: 11px;
        font-weight: 700;
        z-index: 2;
    }

    .product-content {
        padding: 24px;
    }

    .product-category {
        font-size: 11px;
        color: var(--accent-pink);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 8px;
    }

    .product-name {
        font-size: 16px;
        font-weight: 700;
        color: var(--text-color);
        margin-bottom: 12px;
        line-height: 1.4;
        min-height: 40px;
    }

    .product-price {
        font-size: 18px;
        font-weight: 700;
        color: var(--accent-pink);
        margin-bottom: 16px;
    }

    .product-actions {
        display: flex;
        gap: 12px;
    }

    .btn-view {
        flex: 1;
        padding: 12px 16px;
        background: var(--accent-pink);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s;
        text-align: center;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-view:hover {
        background: #d54f7b;
        transform: translateY(-2px);
    }

    .btn-cart {
        width: 45px;
        height: 45px;
        background: var(--primary-pink);
        color: var(--accent-pink);
        border: 2px solid var(--accent-pink);
        border-radius: 50%;
        cursor: pointer;
        font-size: 18px;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .btn-cart:hover {
        background: var(--accent-pink);
        color: white;
    }

    /* ===== FEATURED SECTION ===== */
    .featured-section {
        background: linear-gradient(135deg, #fff5f0 0%, #fff6fa 50%, #f5e9f1 100%);
        padding: 80px 5%;
        margin-bottom: 80px;
        border-radius: 24px;
    }

    .featured-section .section-header-premium {
        margin-top: 0;
        margin-bottom: 50px;
    }

    .featured-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 28px;
        max-width: 1400px;
        margin: 0 auto;
    }

    .featured-card {
        position: relative;
        border-radius: 16px;
        overflow: hidden;
        aspect-ratio: 1;
        cursor: pointer;
    }

    .featured-card img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.4s ease;
    }

    .featured-card:hover img {
        transform: scale(1.1);
    }

    .featured-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 24px;
        background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.7) 100%);
        color: white;
        transform: translateY(20px);
        transition: transform 0.3s ease;
    }

    .featured-card:hover .featured-overlay {
        transform: translateY(0);
    }

    .featured-name {
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .featured-price {
        font-size: 14px;
        color: #fff;
        opacity: 0.9;
    }

    /* ===== TRENDING SECTION ===== */
    .trending-section {
        margin-bottom: 80px;
        max-width: 1400px;
        margin-left: auto;
        margin-right: auto;
    }

    .trending-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 24px;
    }

    .trending-card {
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        aspect-ratio: 3/4;
        cursor: pointer;
    }

    .trending-card img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .trending-card:hover img {
        transform: scale(1.05);
    }

    .trending-label {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 16px;
        background: rgba(255,255,255,0.95);
        text-align: center;
    }

    .trending-name {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-color);
    }

    /* ===== VIEW MORE BUTTON ===== */
    .view-all-btn {
        display: block;
        width: fit-content;
        margin: 60px auto;
        padding: 16px 50px;
        border: 2px solid var(--accent-pink);
        color: var(--accent-pink);
        background: white;
        border-radius: 50px;
        font-weight: 700;
        font-size: 15px;
        text-decoration: none;
        transition: all 0.3s;
        cursor: pointer;
    }

    .view-all-btn:hover {
        background: var(--accent-pink);
        color: white;
        transform: scale(1.05);
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 1024px) {
        .hero-banner {
            height: auto;
            flex-direction: column;
            padding: 40px 5%;
        }

        .hero-image {
            margin-top: 40px;
        }

        .products-grid {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .hero-content h1 {
            font-size: 36px;
        }
    }

    @media (max-width: 768px) {
        .hero-content h1 {
            font-size: 28px;
        }

        .hero-content p {
            font-size: 14px;
        }

        .products-grid,
        .featured-grid,
        .trending-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .section-header-premium h2 {
            font-size: 28px;
        }

        .categories-grid {
            justify-content: center;
        }

        .category-chip {
            padding: 10px 16px;
            font-size: 12px;
        }
    }

    @media (max-width: 480px) {
        .hero-banner {
            padding: 20px 3%;
        }

        .products-grid,
        .featured-grid,
        .trending-grid {
            grid-template-columns: 1fr;
        }

        .hero-content h1 {
            font-size: 22px;
        }

        .section-header-premium h2 {
            font-size: 20px;
        }
    }
</style>


<!-- ===== HERO SECTION ===== -->
<section class="hero-banner">
    <div class="hero-content">
        <div class="hero-badge">🎀 Mới 2026</div>
        <h1>Bộ Sưu Tập Áo Dài 2026</h1>
        <p>Khám phá những thiết kế áo dài sang trọng, kết hợp nét truyền thống và hiện đại. Hoàn hảo cho mọi dịp đặc biệt.</p>
        <a href="ao_dai.php" class="hero-cta">Khám phá bộ sưu tập</a>
    </div>
    <div class="hero-image-row">
        <?php if (!empty($aodai_products)): ?>
            <?php foreach (array_slice($aodai_products, 0, 3) as $thumb): ?>
                <div class="hero-thumb-horizontal">
                    <img src="img/<?php echo basename($thumb['image']); ?>" alt="<?php echo htmlspecialchars($thumb['name']); ?>">
                    <div class="hero-thumb-label"><?php echo htmlspecialchars($thumb['name']); ?></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="hero-thumb-horizontal">
                <img src="img/default.jpg" alt="Featured Collection">
                <div class="hero-thumb-label">Bộ sưu tập nổi bật</div>
            </div>
        <?php endif; ?>
    </div>
</section>

<div class="container" style="max-width: 1400px; margin: 0 auto; padding: 0 5%;">
    <!-- ===== CATEGORIES SECTION ===== -->
    <section class="categories-section">
        <div class="categories-title">Danh Mục Sản Phẩm</div>
        <div class="categories-grid">
            <a href="index.php" class="category-chip active">
                <i class="fas fa-th"></i> Tất cả
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="<?php 
                    // Map category to appropriate page
                    $cat_map = [
                        '1' => 'ao_dai.php',
                        '2' => 'vay_di_bien.php',
                        '3' => 'vay_thiet_ke.php',
                        '4' => 'set_quan_ao.php',
                        '5' => 'giay.php',
                        '6' => 'phu_kien.php'
                    ];
                    echo isset($cat_map[$cat['id']]) ? $cat_map[$cat['id']] : 'index.php';
                ?>" class="category-chip">
                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($cat['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ===== MAIN PRODUCTS SECTION ===== -->
    <?php if (!empty($aodai_products)): ?>
        <section class="main-products">
            <div class="section-header-premium">
                <div class="section-accent"></div>
                <h2>Bộ Sưu Tập Áo Dài</h2>
                <p>Những thiết kế độc đáo cho bạn</p>
            </div>
            
            <div class="products-grid">
                <?php foreach (array_slice($aodai_products, 0, 12) as $product): ?>
                    <div class="product-card-premium">
                        <div class="product-img-container">
                            <img src="img/<?php echo basename($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <span class="product-badge">Cho thuê</span>
                        </div>
                        <div class="product-content">
                            <div class="product-category">Áo Dài</div>
                            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <div class="product-price"><?php echo number_format($product['price']); ?> đ/ngày</div>
                            <div class="product-actions">
                                <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="btn-view">
                                    <i class="fas fa-eye"></i> Xem chi tiết
                                </a>
                                <button class="btn-cart" onclick="addToCart(<?php echo $product['id']; ?>)" title="Thêm vào giỏ">
                                    <i class="fas fa-heart"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <a href="ao_dai.php" class="view-all-btn">Xem tất cả bộ sưu tập →</a>
        </section>
    <?php endif; ?>

    <!-- ===== FEATURED SECTION ===== -->
    <?php if (!empty($featured_products)): ?>
        <section class="featured-section">
            <div class="section-header-premium">
                <div class="section-accent"></div>
                <h2>Bộ Sưu Chọn Lựa Chik</h2>
                <p>Những item yêu thích nhất của chúng tôi</p>
            </div>
            
            <div class="featured-grid">
                <?php foreach ($featured_products as $product): ?>
                    <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="featured-card">
                        <img src="img/<?php echo basename($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <div class="featured-overlay">
                            <div class="featured-name"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div class="featured-price"><?php echo number_format($product['price']); ?> đ/ngày</div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- ===== TRENDING/LATEST SECTION ===== -->
    <?php if (!empty($trending_products)): ?>
        <section class="trending-section">
            <div class="section-header-premium">
                <div class="section-accent"></div>
                <h2>Các Hướng Hot Nhất</h2>
                <p>Sản phẩm mới nhất và phổ biến</p>
            </div>
            
            <div class="trending-grid">
                <?php foreach ($trending_products as $product): ?>
                    <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="trending-card">
                        <img src="img/<?php echo basename($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <div class="trending-label">
                            <div class="trending-name"><?php echo htmlspecialchars($product['name']); ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

</div>

<?php include 'footer.php'; ?>

<script>
// HERO IMAGE SLIDESHOW
let currentHeroIndex = 0;
const aodaiImages = [
    <?php 
    // Tạo array của tất cả ảnh áo dài
    foreach ($aodai_products as $p) {
        echo "'" . basename($p['image']) . "',";
    }
    ?>
];

function rotateHeroImage() {
    const heroImg = document.querySelector('.hero-image img');
    if (aodaiImages.length > 0 && heroImg) {
        currentHeroIndex = (currentHeroIndex + 1) % aodaiImages.length;
        heroImg.style.opacity = '0';
        setTimeout(() => {
            heroImg.src = 'img/' + aodaiImages[currentHeroIndex];
            heroImg.style.opacity = '1';
        }, 300);
    }
}

// Change hero image every 5 seconds
setInterval(rotateHeroImage, 5000);

// Category chip active state
document.addEventListener('DOMContentLoaded', function() {
    const chips = document.querySelectorAll('.category-chip');
    chips.forEach(chip => {
        chip.addEventListener('click', function(e) {
            if (this.href === window.location.href) {
                e.preventDefault();
                chips.forEach(c => c.classList.remove('active'));
                this.classList.add('active');
            }
        });
    });
});

// Add to cart/wishlist function
function addToCart(productId) {
    // Integration with existing cart system
    alert('Thêm vào giỏ hàng ID: ' + productId);
}
</script>