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
        $productId = $id;
        set_flash('success', 'Đã cập nhật sản phẩm.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO products (name, category_id, price, description, material, care, condition_note, deposit, short_note, image) VALUES (?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$name, $categoryId, $price, $description, $material, $care, $conditionNote, $deposit, $shortNote, $imageName]);
        $productId = $pdo->lastInsertId();
        set_flash('success', 'Đã thêm sản phẩm.');
    }

    // Xử lý Variants (Biến thể: Size, Màu, Stock)
    if (isset($_POST['variants']) && is_array($_POST['variants'])) {
        foreach ($_POST['variants'] as $v) {
            $vid = isset($v['id']) ? (int)$v['id'] : 0;
            $vSize = trim($v['size']);
            $vColor = trim($v['color']);
            $vStock = (int)$v['stock'];
            $vDelete = isset($v['delete']) && $v['delete'] == 1;

            if ($vDelete && $vid > 0) {
                // Xóa biến thể
                $pdo->prepare("DELETE FROM product_variants WHERE id = ?")->execute([$vid]);
            } elseif (!$vDelete && $vSize !== '' && $vStock >= 0) {
                if ($vid > 0) {
                    // Update
                    $stmt = $pdo->prepare("UPDATE product_variants SET size = ?, color = ?, stock = ? WHERE id = ? AND product_id = ?");
                    $stmt->execute([$vSize, $vColor, $vStock, $vid, $productId]);
                } else {
                    // Insert
                    $stmt = $pdo->prepare("INSERT INTO product_variants (product_id, size, color, stock) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$productId, $vSize, $vColor, $vStock]);
                }
            }
        }
    }

    header('Location: products.php');
    exit();
}

if (isset($_GET['delete_product'])) {
    $deleteId = (int)$_GET['delete_product'];
    // Xóa variants trước (nếu FK cascade không hoạt động)
    $pdo->prepare('DELETE FROM product_variants WHERE product_id = ?')->execute([$deleteId]);
    $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$deleteId]);
    set_flash('success', 'Đã xóa sản phẩm.');
    header('Location: products.php');
    exit();
}

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$products = $pdo->query('SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC')->fetchAll(PDO::FETCH_ASSOC);

// Load variants cho mỗi sản phẩm để sửa
foreach ($products as &$prod) {
    $stmtV = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY id ASC");
    $stmtV->execute([$prod['id']]);
    $prod['variants'] = $stmtV->fetchAll(PDO::FETCH_ASSOC);
    
    // Tính tổng stock hiển thị
    $totalStock = 0;
    foreach ($prod['variants'] as $pv) $totalStock += $pv['stock'];
    $prod['total_stock'] = $totalStock;
}
unset($prod);

admin_header('Sản phẩm', 'products');
?>
<div class="grid grid-2">
    <div class="card" id="form-card" style="display: none;">
        <h3 id="form-title">Thêm sản phẩm</h3>
        <style>
            .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 12px; }
            .form-label { font-size: 13px; font-weight: 600; color: #374151; }
            .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        </style>
        <form method="POST" enctype="multipart/form-data" style="display: block;">
            <input type="hidden" name="product_id" id="product_id">
            
            <div class="form-group">
                <label class="form-label">Tên sản phẩm <span style="color:red">*</span></label>
                <input class="input" type="text" name="name" id="name" placeholder="Ví dụ: Váy Maxi Tầng Hai Dây" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Danh mục <span style="color:red">*</span></label>
                    <select class="input" name="category_id" id="category_id" required>
                        <option value="">-- Chọn danh mục --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Giá thuê (VNĐ) <span style="color:red">*</span></label>
                    <input class="input" type="number" name="price" id="price" placeholder="0" min="0" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Mô tả sản phẩm</label>
                <textarea class="input" name="description" id="description" rows="3" placeholder="Mô tả chi tiết về kiểu dáng, màu sắc..."></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Chất liệu</label>
                    <input class="input" type="text" name="material" id="material" placeholder="Ví dụ: Voan, Lụa...">
                </div>
                <div class="form-group">
                    <label class="form-label">Hướng dẫn bảo quản</label>
                    <input class="input" type="text" name="care" id="care" placeholder="Ví dụ: Giặt tay, Giặt khô...">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Tình trạng hiện tại</label>
                <input class="input" type="text" name="condition_note" id="condition_note" placeholder="Ví dụ: Đã vệ sinh, sẵn sàng thuê">
            </div>

            <!-- PHẦN QUẢN LÝ KHO/BIẾN THỂ -->
            <div class="form-group" style="background: #f8fafc; padding: 12px; border: 1px dashed #cbd5e1; border-radius: 8px;">
                <label class="form-label" style="display:flex; justify-content:space-between; align-items:center;">
                    <span>Kho & Biến thể (Size/Màu)</span>
                    <button type="button" class="btn ghost" style="padding: 4px 8px; font-size: 11px;" onclick="addVariantRow()">+ Thêm</button>
                </label>
                <div id="variants-list">
                    <!-- Javascript sẽ render rows ở đây -->
                </div>
                <small style="color: #64748b; font-size: 11px;">* Nếu không nhập Size, hệ thống sẽ tự hiểu là Freesize.</small>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Tiền cọc (VNĐ)</label>
                    <input class="input" type="number" name="deposit" id="deposit" placeholder="0 (để trống nếu không cọc)" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Ghi chú ngắn</label>
                    <input class="input" type="text" name="short_note" id="short_note" placeholder="Hiển thị ở trang danh sách (VD: Hai dây)">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Hình ảnh sản phẩm</label>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <input class="input" type="file" name="image" id="image_input" style="flex: 1;">
                    <img id="preview_img" src="" style="height: 40px; display: none; border-radius: 4px; border: 1px solid #ddd;">
                </div>
            </div>

            <div class="actions" style="margin-top: 10px;">
                <button type="submit" name="save_product"><i class="fa-solid fa-save"></i> Lưu Sản Phẩm</button>
                <button type="button" class="btn" style="background: #f3f4f6; color: #4b5563;" onclick="resetForm();">Làm mới</button>
                <button type="button" class="btn" style="background: #fee2e2; color: #b91c1c;" onclick="toggleForm();">Đóng</button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="section-title">
            <h3>Danh sách sản phẩm</h3>
            <div style="display: flex; gap: 10px; align-items: center;">
                <span class="badge"><i class="fa-solid fa-cube"></i> <?php echo count($products); ?></span>
                <button class="btn" onclick="toggleForm()" title="Thêm sản phẩm"><i class="fa-solid fa-plus"></i> Thêm mới</button>
            </div>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th width="40">ID</th>
                    <th>Tên sản phẩm</th>
                    <th>Danh mục</th>
                    <th width="100">Kho</th>
                    <th width="100">Giá thuê</th>
                    <th width="80">Hình</th>
                    <th width="100">Hành động</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?php echo $product['id']; ?></td>
                    <td>
                        <div style="font-weight: 600;"><?php echo htmlspecialchars($product['name']); ?></div>
                        <div style="font-size: 11px; color: #64748b;"><?php echo htmlspecialchars($product['short_note'] ?? ''); ?></div>
                    </td>
                    <td><span class="badge" style="background:#f1f5f9; color:#475569; font-weight:500; font-size:11px;"><?php echo htmlspecialchars($product['category_name']); ?></span></td>
                    <td>
                        <div style="font-weight: 700; color: <?php echo ($product['total_stock'] > 0 ? '#059669' : '#dc2626'); ?>">
                            <?php echo $product['total_stock']; ?>
                        </div>
                        <?php if(!empty($product['variants'])): ?>
                        <div style="font-size: 10px; color: #94a3b8; margin-top: 2px;">
                            <?php 
                                $vText = [];
                                foreach(array_slice($product['variants'], 0, 3) as $v) $vText[] = $v['size'] . ($v['color'] ? '-'.$v['color'] : '') . ': ' . $v['stock'];
                                echo implode(', ', $vText) . (count($product['variants']) > 3 ? '...' : '');
                            ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo number_format($product['price'], 0, ',', '.'); ?> đ</td>
                    <td><img class="img-thumb" src="../img/<?php echo htmlspecialchars(basename($product['image'] ?: 'default.jpg')); ?>" style="width: 40px; height: 40px; border-radius: 6px; object-fit: cover;" alt=""></td>
                    <td class="actions">
                        <a class="btn ghost" href="#" onclick='editProduct(<?php echo json_encode($product); ?>); return false;' title="Sửa"><i class="fa-solid fa-pen"></i></a>
                        <a class="btn ghost" style="border-color: rgba(248,113,113,0.3); color: #f87171;" href="?delete_product=<?php echo $product['id']; ?>" onclick="return confirm('Xóa sản phẩm này?')" title="Xóa"><i class="fa-solid fa-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
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
    document.getElementById('form-title').innerText = 'Sửa sản phẩm: ' + product.name; 
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
    
    // Image preview
    const imgPreview = document.getElementById('preview_img');
    if (product.image) {
        imgPreview.src = '../img/' + product.image;
        imgPreview.style.display = 'block';
    } else {
        imgPreview.style.display = 'none';
        imgPreview.src = '';
    }
    
    // Load variants
    const list = document.getElementById('variants-list');
    list.innerHTML = '';
    if (product.variants && product.variants.length > 0) {
        product.variants.forEach(v => addVariantRow(v));
    } else {
        addVariantRow(); // Add default empty row if no variants
    }

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resetForm() {
    document.getElementById('form-title').innerText = 'Thêm sản phẩm mới';
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
    document.getElementById('image_input').value = ''; // Reset file input
    document.getElementById('preview_img').style.display = 'none';
    document.getElementById('preview_img').src = '';
    
    // Clear old variant rows
    const list = document.getElementById('variants-list');
    list.innerHTML = '';
    // Thêm 1 dòng trống mặc định
    addVariantRow();
}

function addVariantRow(data = null) {
    const list = document.getElementById('variants-list');
    const index = list.children.length; // Ensure unique index for new rows
    const div = document.createElement('div');
    div.className = 'variant-row';
    div.style.cssText = 'display: grid; grid-template-columns: 1fr 1fr 1fr 30px; gap: 8px; margin-bottom: 8px; align-items: center;';
    
    // Default values
    const idVal = data ? data.id : '';
    const sizeVal = data ? data.size : 'F';
    const colorVal = data ? (data.color || '') : '';
    const stockVal = data ? data.stock : 1;

    // Use dataset index to avoid conflicts when deleting rows (though simple append works for now)
    // Using a timestamp might be safer but index is ok if we just append
    
    div.innerHTML = `
        <input type="hidden" name="variants[${index}][id]" value="${idVal}">
        <input type="hidden" name="variants[${index}][delete]" value="0" class="del-flag">
        <input class="input" style="padding: 6px; font-size: 13px;" type="text" name="variants[${index}][size]" placeholder="Size (S, M, L...)" value="${sizeVal}" required>
        <input class="input" style="padding: 6px; font-size: 13px;" type="text" name="variants[${index}][color]" placeholder="Màu sắc" value="${colorVal}">
        <input class="input" style="padding: 6px; font-size: 13px;" type="number" name="variants[${index}][stock]" placeholder="SL" value="${stockVal}" min="0">
        <button type="button" class="btn ghost" style="padding: 4px; border-color: #fee2e2; color: #ef4444;" onclick="removeVariant(this)" title="Xóa"><i class="fa-solid fa-times"></i></button>
    `;
    list.appendChild(div);
}

function removeVariant(btn) {
    const row = btn.parentElement;
    const idInput = row.querySelector('input[name*="[id]"]');
    if (idInput && idInput.value) {
        // If ID exists (saved in DB), hide row and mark for deletion
        row.style.display = 'none';
        row.querySelector('.del-flag').value = 1;
    } else {
        // If new (not saved), remove from DOM
        row.remove();
    }
}
</script>
        <input class="input" style="padding: 6px; font-size: 13px;" type="text" name="variants[${index}][color]" placeholder="Màu sắc" value="${colorVal}">
        <input class="input" style="padding: 6px; font-size: 13px;" type="number" name="variants[${index}][stock]" placeholder="SL" value="${stockVal}" min="0">
        <button type="button" class="btn ghost" style="padding: 4px; border-color: #fee2e2; color: #ef4444;" onclick="removeVariant(this)" title="Xóa"><i class="fa-solid fa-times"></i></button>
    `;
    list.appendChild(div);
}

function removeVariant(btn) {
    const row = btn.parentElement;
    const idInput = row.querySelector('input[name*="[id]"]');
    if (idInput && idInput.value) {
        // Nếu đã có ID (đã lưu trong DB), đánh dấu xóa ẩn
        row.style.display = 'none';
        row.querySelector('.del-flag').value = 1;
    } else {
        // Nếu chưa lưu, xóa luôn khỏi DOM
        row.remove();
    }
}
</script>
<?php
admin_footer();
