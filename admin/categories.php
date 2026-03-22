<?php
require_once __DIR__ . '/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    $id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $name = trim($_POST['category_name'] ?? '');
    $slug = trim($_POST['category_slug'] ?? '');

    if ($name === '') {
        set_flash('error', 'Tên danh mục không được trống.');
        header('Location: categories.php');
        exit();
    }

    if ($slug === '') {
        $slug = slugify($name);
    }

    $stmt = $pdo->prepare('SELECT id FROM categories WHERE slug = ?' . ($id ? ' AND id != ?' : ''));
    $stmt->execute($id ? [$slug, $id] : [$slug]);
    if ($stmt->fetch()) {
        set_flash('error', 'Slug đã tồn tại, hãy chọn slug khác.');
        header('Location: categories.php');
        exit();
    }

    if ($id > 0) {
        $pdo->prepare('UPDATE categories SET name = ?, slug = ? WHERE id = ?')->execute([$name, $slug, $id]);
        set_flash('success', 'Đã cập nhật danh mục.');
    } else {
        $pdo->prepare('INSERT INTO categories (name, slug) VALUES (?, ?)')->execute([$name, $slug]);
        set_flash('success', 'Đã thêm danh mục.');
    }

    header('Location: categories.php');
    exit();
}

if (isset($_GET['delete_category'])) {
    $deleteId = (int)$_GET['delete_category'];
    $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
    $checkStmt->execute([$deleteId]);
    $inUse = (int)$checkStmt->fetchColumn();

    if ($inUse > 0) {
        set_flash('error', 'Không thể xóa vì danh mục đang được sử dụng.');
    } else {
        $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$deleteId]);
        set_flash('success', 'Đã xóa danh mục.');
    }

    header('Location: categories.php');
    exit();
}

$categories = $pdo->query('SELECT * FROM categories ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);

admin_header('Danh mục', 'categories');
?>
<div class="grid grid-2">
    <div class="card">
        <h3 id="cat-title">Thêm danh mục</h3>
        <form method="POST">
            <input type="hidden" name="category_id" id="category_id">
            <input class="input" type="text" name="category_name" id="category_name" placeholder="Tên danh mục" required>
            <input class="input" type="text" name="category_slug" id="category_slug" placeholder="Slug (tùy chọn)">
            <div class="actions">
                <button type="submit" name="save_category"><i class="fa-solid fa-save"></i> Lưu</button>
                <a class="btn ghost" href="#" onclick="resetCategory(); return false;">Làm mới</a>
            </div>
        </form>
    </div>
    <div class="card">
        <div class="section-title">
            <h3>Danh sách danh mục</h3>
            <span class="badge"><i class="fa-solid fa-layer-group"></i> <?php echo count($categories); ?></span>
        </div>
        <table class="table">
            <tr><th>ID</th><th>Tên</th><th>Slug</th><th></th></tr>
            <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><?php echo $cat['id']; ?></td>
                    <td><?php echo htmlspecialchars($cat['name']); ?></td>
                    <td><?php echo htmlspecialchars($cat['slug']); ?></td>
                    <td class="actions">
                        <a class="btn ghost" href="#" onclick='editCategory(<?php echo json_encode($cat); ?>); return false;'><i class="fa-solid fa-pen"></i></a>
                        <a class="btn ghost" style="border-color: rgba(248,113,113,0.5); color: #fecdd3;" href="?delete_category=<?php echo $cat['id']; ?>" onclick="return confirm('Xóa danh mục này?')"><i class="fa-solid fa-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<script>
function editCategory(cat) {
    document.getElementById('cat-title').innerText = 'Sửa danh mục';
    document.getElementById('category_id').value = cat.id;
    document.getElementById('category_name').value = cat.name;
    document.getElementById('category_slug').value = cat.slug;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resetCategory() {
    document.getElementById('cat-title').innerText = 'Thêm danh mục';
    document.getElementById('category_id').value = '';
    document.getElementById('category_name').value = '';
    document.getElementById('category_slug').value = '';
}
</script>
<?php
admin_footer();
