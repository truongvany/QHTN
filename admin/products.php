<?php
require_once __DIR__ . '/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $name = trim($_POST['name'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $material = trim($_POST['material'] ?? '');
    $care = trim($_POST['care'] ?? '');
    $conditionNote = trim($_POST['condition_note'] ?? '');
    $deposit = $_POST['deposit'] !== '' ? (int)$_POST['deposit'] : null;
    $shortNote = trim($_POST['short_note'] ?? '');

    if ($name === '' || $categoryId <= 0 || $price <= 0) {
        set_flash('error', 'Vui lòng nhập đầy đủ thông tin sản phẩm.');
        header('Location: products.php');
        exit();
    }

    $imageName = null;
    if (!empty($_FILES['image']['name'])) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imageName = uniqid('prod_') . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../img/' . $imageName);
    }

    if ($id > 0) {
        $sql = 'UPDATE products SET name = ?, category_id = ?, price = ?, description = ?, material = ?, care = ?, condition_note = ?, deposit = ?, short_note = ?' . ($imageName ? ', image = ?' : '') . ' WHERE id = ?';
        $params = [$name, $categoryId, $price, $description, $material, $care, $conditionNote, $deposit, $shortNote];
        if ($imageName) { $params[] = $imageName; }
        $params[] = $id;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        set_flash('success', 'Đã cập nhật sản phẩm.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO products (name, category_id, price, description, material, care, condition_note, deposit, short_note, image) VALUES (?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$name, $categoryId, $price, $description, $material, $care, $conditionNote, $deposit, $shortNote, $imageName]);
        set_flash('success', 'Đã thêm sản phẩm.');
    }

    header('Location: products.php');
    exit();
}

if (isset($_GET['delete_product'])) {
    $deleteId = (int)$_GET['delete_product'];
    $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$deleteId]);
    set_flash('success', 'Đã xóa sản phẩm.');
    header('Location: products.php');
    exit();
}

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$products = $pdo->query('SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC')->fetchAll(PDO::FETCH_ASSOC);

admin_header('Sản phẩm', 'products');
?>
<div class="grid grid-2">
    <div class="card" id="form-card" style="display: none;">
        <h3 id="form-title">Thêm sản phẩm</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="product_id" id="product_id">
            <input class="input" type="text" name="name" id="name" placeholder="Tên sản phẩm" required>
            <select name="category_id" id="category_id" required>
                <option value="">Chọn danh mục</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <input class="input" type="number" name="price" id="price" placeholder="Giá" min="0" required>
            <textarea class="input" name="description" id="description" placeholder="Mô tả"></textarea>
            <input class="input" type="text" name="material" id="material" placeholder="Chất liệu">
            <input class="input" type="text" name="care" id="care" placeholder="Bảo quản">
            <input class="input" type="text" name="condition_note" id="condition_note" placeholder="Tình trạng">
            <input class="input" type="number" name="deposit" id="deposit" placeholder="Tiền cọc" min="0">
            <input class="input" type="text" name="short_note" id="short_note" placeholder="Ghi chú nhanh (hiển thị ngoài trang sản phẩm)">
            <input class="input" type="file" name="image">
            <div class="actions">
                <button type="submit" name="save_product"><i class="fa-solid fa-save"></i> Lưu</button>
                <button type="button" class="btn" style="background: #f3f4f6; color: #4b5563;" onclick="resetForm();">Làm mới</button>
                <button type="button" class="btn" style="background: #fee2e2; color: #b91c1c;" onclick="toggleForm();">Đóng</button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="section-title">
            <h3>Danh sách sản phẩm</h3>
            <div style="display: flex; gap: 10px; align-items: center;">
                <span class="badge"><i class="fa-solid fa-database"></i> <?php echo count($products); ?></span>
                <button class="btn" onclick="toggleForm()" title="Thêm sản phẩm"><i class="fa-solid fa-plus"></i> Thêm mới</button>
            </div>
        </div>
        <table class="table">
            <tr><th>ID</th><th>Tên</th><th>Danh mục</th><th>Giá</th><th>Tiền cọc</th><th>Hình</th><th></th></tr>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?php echo $product['id']; ?></td>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                    <td><?php echo number_format($product['price'], 0, ',', '.'); ?> đ</td>
                    <td><?php echo $product['deposit'] !== null ? number_format($product['deposit'], 0, ',', '.') . ' đ' : '—'; ?></td>
                    <td><img class="img-thumb" src="../img/<?php echo htmlspecialchars(basename($product['image'] ?: 'default.jpg')); ?>" alt=""></td>
                    <td class="actions">
                        <a class="btn ghost" href="#" onclick='editProduct(<?php echo json_encode($product); ?>); return false;'><i class="fa-solid fa-pen"></i></a>
                        <a class="btn ghost" style="border-color: rgba(248,113,113,0.5); color: #fecdd3;" href="?delete_product=<?php echo $product['id']; ?>" onclick="return confirm('Xóa sản phẩm này?')"><i class="fa-solid fa-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<script>
function toggleForm() {
    const formCard = document.getElementById('form-card');
    if (formCard.style.display === 'none' || formCard.style.display === '') {
        formCard.style.display = 'block';
        resetForm();
    } else {
        formCard.style.display = 'none';
        resetForm();
    }
}

function editProduct(product) {
    document.getElementById('form-card').style.display = 'block';
    document.getElementById('form-title').innerText = 'Sửa sản phẩm';
    document.getElementById('product_id').value = product.id;
    document.getElementById('name').value = product.name;
    document.getElementById('category_id').value = product.category_id;
    document.getElementById('price').value = product.price;
    document.getElementById('description').value = product.description || '';
    document.getElementById('material').value = product.material || '';
    document.getElementById('care').value = product.care || '';
    document.getElementById('condition_note').value = product.condition_note || '';
    document.getElementById('deposit').value = product.deposit ?? '';
    document.getElementById('short_note').value = product.short_note || '';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resetForm() {
    document.getElementById('form-title').innerText = 'Thêm sản phẩm';
    document.getElementById('product_id').value = '';
    document.getElementById('name').value = '';
    document.getElementById('category_id').value = '';
    document.getElementById('price').value = '';
    document.getElementById('description').value = '';
    document.getElementById('material').value = '';
    document.getElementById('care').value = '';
    document.getElementById('condition_note').value = '';
    document.getElementById('deposit').value = '';
    document.getElementById('short_note').value = '';
}
</script>
<?php
admin_footer();
