<?php
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/layout.php';

// Handle updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $settings = [
        'bank_name'   => $_POST['bank_name'] ?? '',
        'bank_number' => $_POST['bank_number'] ?? '',
        'bank_owner'  => $_POST['bank_owner'] ?? '',
        'bank_bin'    => $_POST['bank_bin'] ?? '',
        'qr_template' => $_POST['qr_template'] ?? 'compact2',
    ];

    try {
        $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        foreach ($settings as $key => $value) {
            $stmt->execute([$value, $key]);
        }
        set_flash('success', 'Đã cập nhật cấu hình ngân hàng.');
    } catch (Exception $e) {
        set_flash('error', 'Lỗi: ' . $e->getMessage());
    }
    header('Location: settings.php');
    exit;
}

// Fetch current settings
$stmt = $conn->query("SELECT setting_key, setting_value FROM settings");
$currentSettings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $currentSettings[$row['setting_key']] = $row['setting_value'];
}

admin_header('Cấu hình hệ thống', 'settings');
?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header" style="margin-bottom: 20px;">
        <h3><i class="fa-solid fa-building-columns"></i> Cấu hình Chuyển khoản & VietQR</h3>
        <p style="color: #888; font-size: 0.9em;">Thông tin này sẽ hiển thị tại trang thanh toán và dùng để tạo mã QR.</p>
    </div>

    <form method="POST">
        <div class="form-group" style="margin-bottom: 15px;">
            <label style="display:block; margin-bottom: 5px; font-weight: 600;">Tên Ngân hàng</label>
            <input class="input" type="text" name="bank_name" value="<?php echo htmlspecialchars($currentSettings['bank_name'] ?? ''); ?>" placeholder="VD: MB Bank, Vietcombank..." required>
        </div>

        <div class="form-group" style="margin-bottom: 15px;">
            <label style="display:block; margin-bottom: 5px; font-weight: 600;">Số Tài khoản</label>
            <input class="input" type="text" name="bank_number" value="<?php echo htmlspecialchars($currentSettings['bank_number'] ?? ''); ?>" placeholder="VD: 033xxxxxxx" required>
        </div>

        <div class="form-group" style="margin-bottom: 15px;">
            <label style="display:block; margin-bottom: 5px; font-weight: 600;">Chủ Tài khoản</label>
            <input class="input" type="text" name="bank_owner" value="<?php echo htmlspecialchars($currentSettings['bank_owner'] ?? ''); ?>" placeholder="VD: NGUYEN VAN A" required>
        </div>

        <div class="form-group" style="margin-bottom: 15px;">
            <label style="display:block; margin-bottom: 5px; font-weight: 600;">Mã BIN Ngân hàng (VietQR)</label>
            <input class="input" type="text" name="bank_bin" value="<?php echo htmlspecialchars($currentSettings['bank_bin'] ?? ''); ?>" placeholder="VD: 970422 (MB), 970436 (VCB)..." required>
            <small style="color: #888; display: block; margin-top: 5px;">
                Tra cứu mã BIN tại <a href="https://vietqr.io/danh-sach-api-lien-ket-qr-code-ngan-hang" target="_blank" style="color: var(--accent-color);">VietQR.io</a>
            </small>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display:block; margin-bottom: 5px; font-weight: 600;">Giao diện QR</label>
            <select class="input" name="qr_template">
                <?php
                $opts = ['compact' => 'Compact', 'compact2' => 'Compact 2', 'qr_only' => 'QR Only', 'print' => 'Print'];
                foreach ($opts as $val => $label) {
                    $sel = ($currentSettings['qr_template'] ?? '') === $val ? 'selected' : '';
                    echo "<option value=\"$val\" $sel>$label</option>";
                }
                ?>
            </select>
        </div>

        <button type="submit" name="update_settings" class="btn primary" style="width: 100%; justify-content: center;">
            <i class="fa-solid fa-save"></i> Lưu cấu hình
        </button>
    </form>
</div>

<?php admin_footer(); ?>