<?php 
require_once 'config.php';

// 1. LẤY TỪ KHÓA TÌM KIẾM
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$lowerKeyword = mb_strtolower($keyword, 'UTF-8');

// 2. SMART REDIRECT (Chuyển hướng thông minh)
// Danh sách từ khóa đặc biệt sẽ chuyển hướng sang trang chuyên biệt (nếu có)
// Nếu bạn muốn dùng search.php làm trang hiển thị chính cho mọi thứ thì có thể bỏ qua phần này.
$redirect_map = [
    'áo dài'       => 'search.php?keyword=áo+dài', // Ví dụ: Giữ nguyên ở trang search
    'váy đi biển'  => 'search.php?keyword=biển',
    'giày'         => 'search.php?keyword=giày',
    'phụ kiện'     => 'search.php?keyword=phụ+kiện',
    'set quần áo'  => 'search.php?keyword=set',
    'váy thiết kế' => 'search.php?keyword=váy',
];

// Nếu từ khóa khớp chính xác key trong map nhưng URL hiện tại chưa đúng đích -> chuyển hướng
// (Logic này tùy chọn, bạn có thể xóa nếu thấy phiền)

// 3. TÌM KIẾM TRONG DATABASE
$results = [];
if ($keyword !== '') {
    // Search in name and description
    $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE :keyword OR description LIKE :keyword ORDER BY id DESC");
    $stmt->execute(['keyword' => "%$keyword%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include 'header.php';
?>

<div class="container" style="max-width: 1400px; margin: 40px auto; padding: 0 5%;">
    <style>
        .search-header {
            margin-bottom: 30px;
            text-align: center;
        }
        .highlight-kw {
            color: var(--accent-pink, #e95a8a);
        }
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
        }
        .product-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
        .product-img-wrapper {
            aspect-ratio: 3/4;
            overflow: hidden;
        }
        .product-img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .product-info {
            padding: 20px;
        }
        .product-name {
            font-size: 16px;
            margin-bottom: 10px;
            min-height: 48px;
        }
        .product-price {
            color: var(--accent-pink, #e95a8a);
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 15px;
        }
        .btn-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            width: 100%;
        }
        .btn-pill.primary {
            background: var(--accent-pink, #e95a8a);
            color: white;
        }
    </style>

    <div class="search-header">
            <h2>Kết quả cho: <span class="highlight-kw">"<?php echo htmlspecialchars($keyword); ?>"</span></h2>
            <p>Tìm thấy <b><?php echo count($results); ?></b> sản phẩm phù hợp.</p>
        </div>

        <div class="product-grid">
            <?php foreach ($results as $row): 
                // --- XỬ LÝ ẢNH CHUẨN ---
                $db_img = $row['image'];
                $filename = basename($db_img); // Lấy tên file gốc, bỏ đường dẫn cũ nếu có
                $final_img_src = 'img/' . $filename; // Luôn trỏ về thư mục img/
                
                // Nếu không có ảnh hoặc tên là default -> dùng ảnh mặc định
                if(empty($filename) || $filename == 'default.jpg') {
                    $final_img_src = 'img/default.jpg';
                }
            ?>
            <div class="product-card">
                <div class="product-img-wrapper">
                    <img src="<?php echo htmlspecialchars($final_img_src); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                </div>
                
                <div class="product-info">
                    <h3 class="product-name"><?php echo htmlspecialchars($row['name']); ?></h3>
                    <p class="product-price"><?php echo number_format($row['price']); ?> VNĐ / ngày</p>
                    
                    <div class="card-actions">
                        <a class="btn-pill primary" href="product_detail.php?id=<?php echo $row['id']; ?>">
                            <i class="fa-solid fa-eye"></i> Xem chi tiết
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
</div>

<?php include 'footer.php'; ?>
