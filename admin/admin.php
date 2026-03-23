<?php
require_once __DIR__ . '/init.php';
header('Location: dashboard.php');
exit();

?>
    .stat-info p {
            margin: 0;
            color: #777;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .tabs-control {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
        }

        .tab-btn {
            background: transparent;
            border: none;
            padding: 12px 25px;
            font-size: 16px;
            font-weight: 700;
            color: #777;
            cursor: pointer;
            position: relative;
        }

        .tab-btn.active {
            color: var(--accent);
        }

        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--accent);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.03);
            padding: 25px;
            border: 1px solid #e0e0e0;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        th,
        td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        th {
            background: #f9fafb;
            font-weight: 600;
        }

        .product-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #eee;
        }

        .price-tag {
            color: var(--accent);
            font-weight: 700;
        }

        .btn-primary {
            background: var(--accent);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-icon {
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 16px;
            padding: 5px;
        }

        .btn-edit {
            color: #2980b9;
        }

        .btn-del {
            color: #e74c3c;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert.success {
            background: #dcfce7;
            color: #166534;
        }

        .alert.error {
            background: #fee2e2;
            color: #991b1b;
        }

        details[open] summary~* {
            animation: sweep .5s ease-in-out;
        }

        details>summary {
            list-style: none;
            outline: none;
            cursor: pointer;
        }

        .form-container {
            background: #fafafa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 13px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .order-items {
            display: none;
            background: #f9f9f9;
            padding: 10px;
            margin-top: 10px;
            border-radius: 5px;
        }

        .show-detail-check:checked~.order-items {
            display: block;
        }
    </style>
</head>

<body>

    <div class="navbar">
        <h1>ADMIN PANEL</h1>
        <div class="nav-links">
            <span>Hi, <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></strong></span>
            <a href="../index.php"><i class="fa-solid fa-house"></i> Trang chủ</a>
            <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a>
        </div>
    </div>

        <?php if ($msg): ?>
            <div class="alert <?= $msg_type ?>">
                <i class="fa-solid <?= $msg_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?>"></i>
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <div class="tabs-control">
            <button class="tab-btn active" onclick="switchTab('dashboard')">Dashboard</button>
            <button class="tab-btn" onclick="switchTab('products')">Sản Phẩm</button>
            <button class="tab-btn" onclick="switchTab('categories')">Danh Mục</button>
            <button class="tab-btn" onclick="switchTab('orders')">Đơn Hàng</button>
        </div>

        <div id="tab-dashboard" class="tab-content active">
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fa-solid fa-shirt stat-icon"></i>
                    <div class="stat-info">
                        <h3><?= number_format($totalProducts) ?></h3>
                        <p>Sản phẩm</p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fa-solid fa-receipt stat-icon"></i>
                    <div class="stat-info">
                        <h3><?= number_format($totalOrders) ?></h3>
                        <p>Đơn hàng</p>
                    </div>
                </div>
                    <div class="stat-card">
                    <i class="fa-solid fa-users stat-icon"></i>
                    <div class="stat-info">
                        <h3><?= number_format($totalUsers) ?></h3>
                        <p>Người dùng</p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fa-solid fa-coins stat-icon"></i>
                    <div class="stat-info">
                        <h3><?= number_format($revenue) ?>đ</h3>
                        <p>Doanh thu (tổng)</p>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-bottom:20px;">
                <h3 style="margin-top:0;">Đơn hàng mới nhất</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Mã ĐH</th>
                                <th>Khách</th>
                                <th>Email</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th>Ngày</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($latestOrders as $o): ?>
                                <tr>
                                    <td><b>#<?= $o['id'] ?></b></td>
                                    <td><?= htmlspecialchars($o['username'] ?? 'Guest') ?></td>
                                    <td style="font-size:12px; color:#666;"><?= htmlspecialchars($o['email'] ?? '-') ?></td>
                                    <td class="price-tag"><?= number_format($o['total_price']) ?>đ</td>
                                    <td><?= htmlspecialchars($o['status']) ?></td>
                                    <td style="font-size:12px; color:#666;"><?= $o['created_at'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <h3 style="margin-top:0;">Top danh mục theo số sản phẩm</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Danh mục</th>
                                <th>Số sản phẩm</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topCategories as $cat): ?>
                                <tr>
                                    <td><?= htmlspecialchars($cat['name']) ?></td>
                                    <td><?= $cat['total'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="tab-products" class="tab-content">
            <div class="card">
                <details id="product-form-details">
                    <summary class="btn-primary" style="width: fit-content; margin-bottom: 20px;">
                        <i class="fa-solid fa-plus"></i> Thêm sản phẩm
                    </summary>
                    <div class="form-container">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="save_product" value="1">
                            <input type="hidden" name="id" id="edit-id" value="">
                            <input type="hidden" name="current_image" id="edit-current-image" value="">

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div class="form-group">
                                    <label>Tên sản phẩm</label>
                                    <input type="text" name="name" id="edit-name" required placeholder="Nhập tên...">
                                </div>
                                <div class="form-group">
                                    <label>Giá thuê (VNĐ)</label>
                                    <input type="number" name="price" id="edit-price" required>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div class="form-group">
                                    <label>Danh mục</label>
                                    <select name="category_id" id="edit-category">
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Hình ảnh (chọn để thay đổi)</label>
                                    <input type="file" name="image" accept="image/*">
                                </div>
                            </div>

                            <button type="submit" class="btn-primary" style="width:100%">Lưu lại</button>
                            <button type="button" onclick="resetForm()"
                                style="margin-top:10px; width:100%; padding:8px; border:1px solid #ddd; background:#fff; cursor:pointer;">Hủy
                                bỏ</button>
                        </form>
                    </div>
                </details>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Ảnh</th>
                                <th>Tên sản phẩm</th>
                                <th>Giá</th>
                                <th>Danh mục</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p):
                                $filename = basename($p['image']);
                                $imgShow = !empty($filename) ? '../img/' . $filename : '../img/default.jpg';
                                ?>
                                <tr>
                                    <td><img src="<?= $imgShow ?>" class="product-img"></td>
                                    <td><b><?= htmlspecialchars($p['name']) ?></b></td>
                                    <td class="price-tag"><?= number_format($p['price']) ?>đ</td>
                                    <td><?= htmlspecialchars($p['category_name']) ?></td>
                                    <td>
                                        <button class="btn-icon btn-edit" onclick='editProduct(<?= json_encode($p) ?>)'>
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <form method="POST" style="display:inline;"
                                            onsubmit="return confirm('Xóa sản phẩm này?');">
                                            <input type="hidden" name="delete_id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="btn-icon btn-del"><i
                                                    class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="tab-orders" class="tab-content">
            <div class="card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Mã ĐH</th>
                                <th>Khách</th>
                                <th>Email</th>
                                <th>SĐT</th>
                                <th>Tổng tiền</th>
                                <th>Ghi chú</th>
                                <th>Chi tiết</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><b>#<?= $order['id'] ?></b></td>
                                    <td><?= htmlspecialchars($order['username'] ?? 'Guest') ?></td>
                                    <td style="font-size:12px; color:#666; max-width:160px;">
                                        <?= htmlspecialchars($order['email'] ?? '-') ?></td>
                                    <td style="font-size:12px; color:#666; max-width:100px;">
                                        <?= htmlspecialchars($order['phone'] ?? '') ?></td>
                                    <td class="price-tag"><?= number_format($order['total_price']) ?>đ</td>
                                    <td style="font-size:12px; color:#666; max-width:200px;">
                                        <?= htmlspecialchars($order['note']) ?></td>
                                    <td>
                                        <label for="toggle-<?= $order['id'] ?>"
                                            style="color:var(--accent); cursor:pointer; font-weight:600; font-size:13px;">
                                            Xem <i class="fa-solid fa-chevron-down"></i>
                                        </label>
                                        <input type="checkbox" id="toggle-<?= $order['id'] ?>" class="show-detail-check"
                                            style="display:none;">

                                        <div class="order-items">
                                            <?php
                                            $stmtD = $conn->prepare("
                                                SELECT od.*, p.name, p.image 
                                                FROM order_details od 
                                                JOIN products p ON od.product_id = p.id 
                                                WHERE od.order_id = ?
                                            ");
                                            $stmtD->execute([$order['id']]);
                                            while ($d = $stmtD->fetch(PDO::FETCH_ASSOC)):
                                                // Xử lý ảnh chi tiết
                                                $dFilename = basename($d['image']);
                                                $dImgShow = !empty($dFilename) ? '../img/' . $dFilename : '../img/default.jpg';
                                                ?>
                                                <div
                                                    style="border-bottom:1px dashed #ddd; padding:8px 0; display: flex; align-items: center; gap: 10px;">
                                                    <img src="<?= $dImgShow ?>"
                                                        style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid #eee;">
                                                    <div>
                                                        <b><?= htmlspecialchars($d['name']) ?></b><br>
                                                        <small style="color: #666;">SL: <?= $d['quantity'] ?> x
                                                            <?= number_format($d['price']) ?>đ</small>
                                                    </div>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="tab-categories" class="tab-content">
            <div class="card">
                <h3 style="margin-top:0;">Quản lý danh mục</h3>
                <div class="form-container" style="margin-bottom:20px;">
                    <form method="POST" onsubmit="return validateCategoryForm();">
                        <input type="hidden" name="save_category" value="1">
                        <input type="hidden" name="cat_id" id="cat-id" value="">
                        <div class="form-group">
                            <label>Tên danh mục</label>
                            <input type="text" name="cat_name" id="cat-name" required placeholder="Nhập tên danh mục...">
                        </div>
                        <div style="display:flex; gap:10px;">
                            <button type="submit" class="btn-primary" style="flex:1;">Lưu danh mục</button>
                            <button type="button" onclick="resetCategoryForm()"
                                style="flex:1; border:1px solid #ddd; background:#fff; cursor:pointer; border-radius:8px;">Hủy</button>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên</th>
                                <th>Slug</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td><?= $cat['id'] ?></td>
                                    <td><?= htmlspecialchars($cat['name']) ?></td>
                                    <td style="font-size:12px; color:#777;"><?= htmlspecialchars($cat['slug']) ?></td>
                                    <td>
                                        <button class="btn-icon btn-edit" onclick='editCategory(<?= json_encode($cat) ?>)'>
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Xóa danh mục này?');">
                                            <input type="hidden" name="delete_category_id" value="<?= $cat['id'] ?>">
                                            <button type="submit" class="btn-icon btn-del"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <script>
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));

            document.getElementById('tab-' + tabName).classList.add('active');
            const btns = document.querySelectorAll('.tab-btn');
            if (tabName === 'dashboard') btns[0].classList.add('active');
            if (tabName === 'products') btns[1].classList.add('active');
            if (tabName === 'categories') btns[2].classList.add('active');
            if (tabName === 'orders') btns[3].classList.add('active');

            localStorage.setItem('admin_active_tab', tabName);
        }

        document.addEventListener("DOMContentLoaded", () => {
            const savedTab = localStorage.getItem('admin_active_tab') || 'dashboard';
            switchTab(savedTab);
        });

        function editProduct(product) {
            document.getElementById('product-form-details').open = true;
            document.getElementById('edit-id').value = product.id;
            document.getElementById('edit-name').value = product.name;
            document.getElementById('edit-price').value = product.price;
            document.getElementById('edit-category').value = product.category_id;

            let imgName = product.image;
            if (imgName && imgName.includes('/')) {
                imgName = imgName.split('/').pop();
            }
            document.getElementById('edit-current-image').value = imgName || '';

            document.getElementById('product-form-details').scrollIntoView({ behavior: 'smooth' });
        }

        function resetForm() {
            document.getElementById('edit-id').value = "";
            document.getElementById('edit-name').value = "";
            document.getElementById('edit-price').value = "";
            document.getElementById('edit-category').selectedIndex = 0;
            document.getElementById('edit-current-image').value = "";
            document.getElementById('product-form-details').open = false;
        }

        function editCategory(cat) {
            document.getElementById('cat-id').value = cat.id;
            document.getElementById('cat-name').value = cat.name;
            document.getElementById('cat-name').focus();
        }

        function resetCategoryForm() {
            document.getElementById('cat-id').value = "";
            document.getElementById('cat-name').value = "";
        }

        function validateCategoryForm() {
            const name = document.getElementById('cat-name').value.trim();
            if (!name) {
                alert('Tên danh mục không được để trống');
                return false;
            }
            return true;
        }
    </script>
</body>

</html>