<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];

// User info
$stmtUser = $conn->prepare('SELECT username, email, phone, avatar, created_at FROM users WHERE id = ? LIMIT 1');
$stmtUser->execute([$userId]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

// Avatar fallback
$avatarPath = !empty($user['avatar']) && file_exists($user['avatar']) ? $user['avatar'] : 'img/default.jpg';

// Recent orders with product details
$sql = "SELECT o.id, o.total_price, o.status, o.created_at, 
        od.rental_start, od.rental_end, od.duration_days, p.name AS product_name, 
        od.quantity, od.price, p.image
        FROM orders o
        LEFT JOIN order_details od ON o.id = od.order_id
        LEFT JOIN products p ON od.product_id = p.id
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC, od.id DESC
        LIMIT 10";
$stmtOrders = $conn->prepare($sql);
$stmtOrders->execute([$userId]);
$orders = $stmtOrders->fetchAll(PDO::FETCH_ASSOC);

// Stats
$stmtTotal = $conn->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?');
$stmtTotal->execute([$userId]);
$totalOrders = (int)$stmtTotal->fetchColumn();

$stmtItems = $conn->prepare('SELECT COALESCE(SUM(quantity),0) FROM order_details od JOIN orders o ON od.order_id=o.id WHERE o.user_id = ?');
$stmtItems->execute([$userId]);
$totalItems = (int)$stmtItems->fetchColumn();

$pageTitle = 'Hồ sơ của tôi | QHTN';
include 'header.php';
?>

<div class="profile-container">
    <!-- Sidebar Navigation -->
    <aside class="profile-sidebar">
        <nav class="sidebar-menu">
            <a href="profile.php" class="menu-item active">
                <i class="fa-solid fa-circle-user"></i>
                <span>Thông tin cá nhân</span>
            </a>
            <a href="orders.php" class="menu-item">
                <i class="fa-solid fa-receipt"></i>
                <span>Đơn hàng</span>
            </a>
            <a href="addresses.php" class="menu-item">
                <i class="fa-solid fa-location-dot"></i>
                <span>Địa chỉ</span>
            </a>
            <a href="wishlist.php" class="menu-item">
                <i class="fa-solid fa-heart"></i>
                <span>Yêu thích</span>
            </a>
            <a href="settings.php" class="menu-item">
                <i class="fa-solid fa-gear"></i>
                <span>Cài đặt</span>
            </a>
            <a href="logout.php" class="menu-item logout">
                <i class="fa-solid fa-sign-out-alt"></i>
                <span>Đăng xuất</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="profile-content">
        <!-- Profile Card -->
        <div class="profile-card">
            <div class="profile-avatar-wrapper">
                <img id="profileAvatar" src="<?= htmlspecialchars($avatarPath) ?>" alt="Avatar" class="profile-avatar">
                <input type="file" id="avatarInput" accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;">
                <div class="avatar-upload-hint">
                    <i class="fa-solid fa-camera"></i>
                </div>
            </div>
            <div class="profile-info">
                <h1 class="profile-name"><?= htmlspecialchars($user['username'] ?? 'Thành viên') ?></h1>
                <p class="profile-email"><?= htmlspecialchars($user['email'] ?? '-') ?></p>
                <p class="profile-phone"><?= htmlspecialchars($user['phone'] ?? 'Chưa cập nhật') ?></p>
                <p class="profile-joined">Tham gia từ <?= date('d/m/Y', strtotime($user['created_at'])) ?></p>
            </div>
            <button class="btn-edit-profile" id="editProfileBtn">Chỉnh sửa hồ sơ</button>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-bag-shopping"></i>
                </div>
                <div class="stat-info">
                    <p class="stat-label">Tổng đơn hàng</p>
                    <p class="stat-value"><?= number_format($totalOrders) ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-box"></i>
                </div>
                <div class="stat-info">
                    <p class="stat-label">Sản phẩm đã thuê</p>
                    <p class="stat-value"><?= number_format($totalItems) ?></p>
                </div>
            </div>
        </div>

        <!-- Order History -->
        <div class="section">
            <h2 class="section-title">Lịch sử đặt hàng gần đây</h2>
            <div class="order-table-wrapper">
                <?php if (!empty($orders)): ?>
                    <table class="order-table">
                        <thead>
                            <tr>
                                <th>Mã đơn</th>
                                <th>Sản phẩm</th>
                                <th>Thời gian thuê</th>
                                <th>Số lượng</th>
                                <th>Thành tiền</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $o): 
                                $days = isset($o['duration_days']) ? max(1, (int)$o['duration_days']) : 1;
                                $totalPrice = (int)($o['price'] ?? 0) * max(1, (int)$o['quantity']) * $days;
                            ?>
                                <tr>
                                    <td class="order-id">#<?= (int)$o['id'] ?></td>
                                    <td class="product-name"><?= htmlspecialchars($o['product_name'] ?? '-') ?></td>
                                    <td class="rental-period">
                                        <span class="date"><?= htmlspecialchars($o['rental_start'] ?? '-') ?></span>
                                        <span class="separator">→</span>
                                        <span class="date"><?= htmlspecialchars($o['rental_end'] ?? '-') ?></span>
                                    </td>
                                    <td class="quantity"><?= (int)$o['quantity'] ?></td>
                                    <td class="price"><?= number_format($totalPrice) ?>đ</td>
                                    <td class="status">
                                        <span class="badge badge-<?= strtolower(str_replace(' ', '-', $o['status'] ?? 'pending')) ?>">
                                            <?= htmlspecialchars(ucfirst($o['status'] ?? 'pending')) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-inbox"></i>
                        <p>Chưa có đơn hàng nào</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- Edit Profile Modal -->
<div class="modal" id="editProfileModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Chỉnh sửa hồ sơ</h2>
            <button class="modal-close" id="editModalClose">&times;</button>
        </div>
        <form id="editProfileForm" class="modal-body">
            <div class="form-group">
                <label for="editUsername">Tên người dùng</label>
                <input type="text" id="editUsername" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
            </div>
            <div class="form-group">
                <label for="editEmail">Email</label>
                <input type="email" id="editEmail" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
            <div class="form-group">
                <label for="editPhone">Số điện thoại</label>
                <input type="tel" id="editPhone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" id="editModalCancel">Hủy</button>
                <button type="submit" class="btn-submit">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const profileAvatar = document.getElementById('profileAvatar');
    const avatarInput = document.getElementById('avatarInput');
    const editProfileBtn = document.getElementById('editProfileBtn');
    const editModal = document.getElementById('editProfileModal');
    const editModalClose = document.getElementById('editModalClose');
    const editModalCancel = document.getElementById('editModalCancel');
    const editProfileForm = document.getElementById('editProfileForm');

    // Avatar upload
    profileAvatar.addEventListener('click', function() {
        avatarInput.click();
    });

    avatarInput.addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            const formData = new FormData();
            formData.append('avatar', this.files[0]);

            fetch('avatar_upload.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    profileAvatar.src = data.avatar_url + '?t=' + Date.now();
                    alert(data.message);
                } else {
                    alert('Lỗi: ' + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('Có lỗi xảy ra');
            });
        }
    });

    // Edit profile modal
    editProfileBtn.addEventListener('click', function() {
        editModal.classList.add('active');
    });

    editModalClose.addEventListener('click', function() {
        editModal.classList.remove('active');
    });

    editModalCancel.addEventListener('click', function() {
        editModal.classList.remove('active');
    });

    window.addEventListener('click', function(e) {
        if (e.target === editModal) {
            editModal.classList.remove('active');
        }
    });

    editProfileForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch('edit_profile_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                // Update display
                document.querySelector('.profile-name').textContent = data.username;
                document.querySelector('.profile-email').textContent = data.email;
                document.querySelector('.profile-phone').textContent = data.phone || 'Chưa cập nhật';
                
                editModal.classList.remove('active');
                alert(data.message);
            } else {
                alert('Lỗi: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Có lỗi xảy ra');
        });
    });
});
</script>

<?php include 'footer.php'; ?>
