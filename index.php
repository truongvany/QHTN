<?php
// 1. Kết nối CSDL
require_once 'config.php';
$is_homepage = true; // Added this

// 2. Lấy 8 sản phẩm ÁO DÀI (category_id = 1)
$stmt_ad = $conn->prepare("SELECT * FROM products WHERE category_id = 1 ORDER BY id DESC LIMIT 8");
$stmt_ad->execute();
$aodai_products = $stmt_ad->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

<div id="toast-box">
    <i class="fas fa-check-circle"></i>
    <span id="toast-msg">Đã thêm vào giỏ hàng</span>
</div>

<style>
    /* CSS RIÊNG CHO TRANG CHỦ */

    /* 1. Banner */
    .slideshow-container {
        position: relative;
        max-width: 100%;
        margin: auto;
        overflow: hidden;
    }

    .mySlides {
        display: none;
        width: 100%;
    }

    .mySlides img {
        width: 100%;
        height: auto;
        object-fit: cover;
    }

    .fade {
        animation-name: fade;
        animation-duration: 1.5s;
    }

    @keyframes fade {
        from {
            opacity: .4
        }

        to {
            opacity: 1
        }
    }

    /* 2. Policy */
    .policy-section {
        display: flex;
        justify-content: space-around;
        padding: 40px 20px;
        background: #fff;
        border-bottom: 1px solid #eee;
        flex-wrap: wrap;
        gap: 20px;
    }

    .policy-item {
        text-align: center;
        flex: 1;
        min-width: 200px;
    }

    .policy-item i {
        font-size: 35px;
        color: var(--accent-pink, #ff4757);
        margin-bottom: 15px;
    }

    .policy-item h4 {
        font-size: 16px;
        margin-bottom: 5px;
        color: #333;
        font-weight: 700;
    }

    .policy-item p {
        font-size: 13px;
        color: #777;
        margin: 0;
    }

    /* 3. Section Header */
    .section-header {
        text-align: center;
        margin: 50px 0 30px;
    }

    .section-header h2 {
        font-size: 28px;
        color: #333;
        text-transform: uppercase;
        display: inline-block;
        position: relative;
        padding-bottom: 10px;
        font-weight: 700;
    }

    .section-header h2::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 3px;
        background: var(--accent-pink, #ff4757);
    }

    /* 4. Grid Sản phẩm */
    .products {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 30px;
    }

    .product-card {
        background: #fff;
        border: 1px solid #eee;
        border-radius: 10px;
        overflow: hidden;
        transition: 0.3s;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
    }

    .product-card:hover {
        transform: translateY(-5px);
        border-color: var(--primary-pink, #ff6b81);
    }

    .product-img-wrapper {
        position: relative;
        padding-top: 100%;
        overflow: hidden;
    }

    .product-img-wrapper img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: 0.5s;
    }

    .product-card:hover .product-img-wrapper img {
        transform: scale(1.1);
    }

    .product-info {
        padding: 15px;
        text-align: center;
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .product-name {
        font-size: 16px;
        font-weight: 600;
        color: #333;
        margin-bottom: 5px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .product-price {
        color: var(--accent-pink, #ff4757);
        font-weight: 700;
        font-size: 16px;
        margin-bottom: 15px;
    }

    /* 5. Nút Ajax */
    .btn-add-ajax {
        background: #333;
        color: #fff;
        border: none;
        padding: 10px;
        border-radius: 25px;
        cursor: pointer;
        font-weight: 600;
        transition: 0.3s;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-add-ajax:hover {
        background: var(--accent-pink, #ff4757);
    }

    /* 6. Toast */
    #toast-box {
        position: fixed;
        bottom: 30px;
        left: 50%;
        transform: translateX(-50%);
        background-color: rgba(0, 0, 0, 0.8);
        color: #fff;
        padding: 12px 30px;
        border-radius: 50px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        display: flex;
        align-items: center;
        gap: 10px;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        z-index: 9999;
    }

    #toast-box.show {
        bottom: 50px;
        opacity: 1;
        visibility: visible;
    }

    #toast-box i {
        color: #2ecc71;
        font-size: 18px;
    }

    /* 7. View More */
    .view-more {
        display: block;
        width: fit-content;
        margin: 40px auto;
        padding: 12px 40px;
        border: 2px solid #333;
        color: #333;
        font-weight: 600;
        border-radius: 50px;
        text-decoration: none;
        transition: 0.3s;
    }

    .view-more:hover {
        background: #333;
        color: #fff;
        border-color: #333;
    }
</style>

<div class="slideshow-container">
    <div class="mySlides fade"><img src="img/1.jpg" alt="Banner 1"></div>
    <div class="mySlides fade"><img src="img/2.jpg" alt="Banner 2"></div>
    <div class="mySlides fade"><img src="img/3.jpg" alt="Banner 3"></div>
    <div class="mySlides fade"><img src="img/4.jpg" alt="Banner 4"></div>
    <div class="mySlides fade"><img src="img/5.jpg" alt="Banner 5"></div>
</div>

<div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">

    <?php if (count($aodai_products) > 0): ?>
        <section class="featured-products">
            <div class="section-header">
                <h2>Bộ Sưu Tập Áo Dài</h2>
            </div>

            <div class="products">
                <?php foreach ($aodai_products as $p):
                    // Xử lý ảnh
                    $filename = basename($p['image']);
                    $final_img_src = 'img/' . $filename;
                    if (empty($filename) || $filename == 'default.jpg')
                        $final_img_src = 'img/default.jpg';
                    ?>
                    <div class="product-card">
                        <div class="product-img-wrapper">
                            <img src="<?= htmlspecialchars($final_img_src) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                        </div>
                        <div class="product-info">
                            <h3 class="product-name"><?= htmlspecialchars($p['name']) ?></h3>
                            <div class="product-price"><?= number_format($p['price']) ?> VNĐ / ngày</div>
                            <button class="btn-add-ajax" onclick="addToCartAjax(<?= $p['id'] ?>)">
                                <i class="fas fa-cart-plus"></i> Thuê Ngay
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <a href="ao_dai.php" class="view-more">Xem thêm Áo Dài</a>
        </section>
    <?php endif; ?>

</div>

<script>
    function addToCartAjax(productId) {
        const toastBox = document.getElementById('toast-box');
        const formData = new FormData();
        formData.append('id', productId);

        fetch('add_to_cart_ajax.php', {
            method: 'POST', body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const cartCountEl = document.querySelector('.cart-count');
                    if (cartCountEl) {
                        cartCountEl.innerText = data.total_count;
                    } else {
                        location.reload(); // Reload nếu chưa có số để hiện
                    }
                    document.getElementById('toast-msg').innerText = data.message;
                    toastBox.classList.add('show');
                    setTimeout(() => { toastBox.classList.remove('show'); }, 2500);
                } else if (data.status === 'login_required') {
                    alert(data.message);
                    window.location.href = 'login.php';
                } else {
                    alert('Lỗi: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi kết nối server!');
            });
    }
</script>

<?php include 'footer.php'; ?>

<script>
    // 1. SLIDER LOGIC
    let slideIndex = 0;
    showSlides();

    function showSlides() {
        let i;
        let slides = document.getElementsByClassName("mySlides");
        /* Check if slides exist to avoid errors if HTML is changed temporarily */
        if (slides.length === 0) return;

        for (i = 0; i < slides.length; i++) {
            slides[i].style.display = "none";
        }
        slideIndex++;
        if (slideIndex > slides.length) { slideIndex = 1 }
        slides[slideIndex - 1].style.display = "block";
        setTimeout(showSlides, 3000);
    }
</script>