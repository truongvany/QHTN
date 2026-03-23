<?php
require_once __DIR__ . '/layout.php';

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$orderId) {
    header('Location: orders.php');
    exit;
}

$orderStmt = $pdo->prepare('SELECT o.*, u.username AS customer_name, u.email, u.phone, u.avatar FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?');
$orderStmt->execute([$orderId]);
$order = $orderStmt->fetch();

if (!$order) {
    header('Location: orders.php');
    exit;
}

$itemsStmt = $pdo->prepare('SELECT od.*, p.name AS product_name, pv.size AS variant_size, pv.color AS variant_color FROM order_details od LEFT JOIN products p ON od.product_id = p.id LEFT JOIN product_variants pv ON od.variant_id = pv.id WHERE od.order_id = ?');
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll();

$statusHistory = [
    ['timestamp' => $order['created_at'], 'note' => 'Đơn hàng được tạo']
];
if ($order['updated_at'] !== $order['created_at']) {
    $statusHistory[] = ['timestamp' => $order['updated_at'], 'note' => 'Cập nhật trạng thái: ' . $order['status']];
}

admin_header('Chi tiết đơn #'.$orderId, 'orders');

$statusLabels = [
    'pending' => 'Chờ xử lý',
    'confirmed' => 'Đã xác nhận',
    'ongoing' => 'Đang thuê',
    'returned' => 'Đã trả',
    'cancelled' => 'Đã hủy'
];
$currentLabel = $statusLabels[$order['status']] ?? $order['status'];
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div>
        <h2 style="margin: 0; font-size: 24px; color: #1e293b; display: flex; align-items: center; gap: 12px;">
            Đơn hàng #<?php echo $order['id']; ?>
            <span class="status-badge status-<?php echo strtolower($order['status']); ?>" style="font-size: 13px; padding: 6px 16px; border-radius: 8px;">
                <?php echo htmlspecialchars($currentLabel); ?>
            </span>
        </h2>
        <p style="color: #64748b; margin: 8px 0 0 0;"><i class="fa-regular fa-clock" style="margin-right: 6px;"></i>Tạo lúc: <?php echo date('H:i - d/m/Y', strtotime($order['created_at'])); ?></p>
    </div>
    
    <div style="background: white; padding: 12px 20px; border-radius: 12px; border: 1px solid #eef2ff; display: flex; gap: 12px; align-items: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <select id="statusSelect" style="padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1; outline: none; background: #f8fafc; font-weight: 500; color: #334155;">
            <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Chờ xử lý</option>
            <option value="confirmed" <?php echo $order['status'] === 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
            <option value="ongoing" <?php echo $order['status'] === 'ongoing' ? 'selected' : ''; ?>>Đang thuê</option>
            <option value="returned" <?php echo $order['status'] === 'returned' ? 'selected' : ''; ?>>Đã trả</option>
            <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Hủy bỏ</option>
        </select>
        <button onclick="saveOrderStatus()" style="background: #4f46e5; color: white; border: none; padding: 8px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: background 0.2s;">
            Cập nhật
        </button>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">
    <!-- Main Content: Products & History -->
    <div style="display: grid; gap: 24px;">
        <!-- Products -->
        <div style="background: white; border: 1px solid #eef2ff; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <h3 style="margin: 0 0 20px 0; font-size: 16px; color: #1e293b;"><i class="fa-solid fa-box-open" style="margin-right: 10px; color: #6366f1;"></i>Sản phẩm đơn hàng</h3>
            <div style="display: grid; gap: 12px;">
                <?php foreach ($items as $item): ?>
                <div style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #f1f5f9;">
                    <div>
                        <strong style="font-size: 15px; color: #0f172a; display: block; margin-bottom: 6px;">
                            <?php echo htmlspecialchars($item['product_name']); ?>
                        </strong>
                        <div style="color: #64748b; font-size: 13px; display: flex; gap: 16px; margin-bottom: 8px;">
                            <?php if (!empty($item['variant_size']) || !empty($item['variant_color'])): ?>
                                <span><i class="fa-solid fa-tag" style="margin-right: 4px;"></i><?php echo htmlspecialchars(trim(($item['variant_size'] ?? '') . ' ' . ($item['variant_color'] ?? ''))); ?></span>
                            <?php endif; ?>
                            <span><i class="fa-solid fa-cubes" style="margin-right: 4px;"></i>SL: <strong><?php echo $item['quantity']; ?></strong></span>
                        </div>
                        <?php if (!empty($item['rental_start']) && !empty($item['rental_end'])): ?>
                            <div style="font-size: 12px; color: #4f46e5; font-weight: 500; background: #e0e7ff; display: inline-block; padding: 4px 10px; border-radius: 6px;">
                                <i class="fa-regular fa-calendar-days" style="margin-right: 6px;"></i>Thuê: <?php echo date('d/m/Y', strtotime($item['rental_start'])); ?> → <?php echo date('d/m/Y', strtotime($item['rental_end'])); ?>
                            </div>
                            <!-- Rental Item Actions -->
                            <div style="margin-top: 10px; display: flex; gap: 8px;">
                                <button onclick="updateItemStatus(<?php echo $item['id']; ?>, 'collected')" style="padding: 4px 8px; border: 1px solid #cbd5e1; background: white; border-radius: 6px; font-size: 11px; cursor: pointer; color: #334155;">Đã lấy</button>
                                <button onclick="updateItemStatus(<?php echo $item['id']; ?>, 'in-transit')" style="padding: 4px 8px; border: 1px solid #cbd5e1; background: white; border-radius: 6px; font-size: 11px; cursor: pointer; color: #334155;">Vận chuyển</button>
                                <button onclick="updateItemStatus(<?php echo $item['id']; ?>, 'in-use')" style="padding: 4px 8px; border: 1px solid #cbd5e1; background: white; border-radius: 6px; font-size: 11px; cursor: pointer; color: #334155;">Đang dùng</button>
                                <button onclick="updateItemStatus(<?php echo $item['id']; ?>, 'returned')" style="padding: 4px 8px; border: 1px solid #fca5a5; color: #ef4444; background: #fef2f2; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer;">Đã trả đồ</button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="text-align: right;">
                        <span style="display: block; font-weight: 700; color: #0f172a; font-size: 16px; margin-bottom: 8px;">
                            <?php echo number_format($item['price'], 0, ',', '.'); ?> đ
                        </span>
                        <?php
                            $itemStatusLbs = [
                                'pending' => 'Chờ xử lý',
                                'collected' => 'Đã lấy',
                                'in-transit' => 'Đang giao',
                                'in-use' => 'Đang dùng',
                                'returned' => 'Đã trả'
                            ];
                            $iStatus = $itemStatusLbs[$item['status']] ?? $item['status'];
                            $ibadge = str_replace('-', '', $item['status']);
                        ?>
                        <div class="status-badge status-<?php echo htmlspecialchars($ibadge ?? 'pending'); ?>" style="font-size: 11px;">
                            <?php echo htmlspecialchars($iStatus); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top: 24px; border-top: 2px dashed #e2e8f0; padding-top: 24px; display: flex; justify-content: space-between; align-items: center;">
                <span style="color: #64748b; font-weight: 600; font-size: 15px;">Tổng cộng (Bao gồm cọc)</span>
                <span style="font-size: 24px; font-weight: 800; color: #4f46e5;"><?php echo number_format($order['total_price'], 0, ',', '.'); ?> đ</span>
            </div>
        </div>
        
        <!-- Timeline -->
        <div style="background: white; border: 1px solid #eef2ff; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <h3 style="margin: 0 0 20px 0; font-size: 16px; color: #1e293b;"><i class="fa-solid fa-timeline" style="margin-right: 10px; color: #6366f1;"></i>Lịch sử trạng thái</h3>
            <div>
                <?php foreach ($statusHistory as $event): ?>
                    <div style="display: flex; gap: 16px; padding: 12px 0; border-left: 2px solid #e0e7ff; margin-left: 8px; padding-left: 20px; position: relative;">
                        <div style="position: absolute; width: 10px; height: 10px; border-radius: 50%; background: #4f46e5; left: -6px; top: 16px; border: 4px solid #fff; box-shadow: 0 0 0 1px #e0e7ff;"></div>
                        <div>
                            <div style="font-size: 12px; color: #94a3b8; font-weight: 600; margin-bottom: 4px;"><?php echo date('H:i - d/m/Y', strtotime($event['timestamp'])); ?></div>
                            <div style="color: #334155; font-weight: 500; font-size: 14px;"><?php echo htmlspecialchars($event['note']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar: Customer & Internal Notes -->
    <div style="display: grid; gap: 24px;">
        <!-- Customer Info -->
        <div style="background: white; border: 1px solid #eef2ff; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <h3 style="margin: 0 0 20px 0; font-size: 16px; color: #1e293b;"><i class="fa-solid fa-user" style="margin-right: 10px; color: #6366f1;"></i>Khách hàng</h3>
            
            <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px;">
                <div style="width: 50px; height: 50px; background: #e0e7ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #4f46e5; font-weight: 700; font-size: 20px;">
                    <?php echo strtoupper(substr($order['customer_name'] ?? '?', 0, 1)); ?>
                </div>
                <div>
                    <h4 style="margin: 0 0 4px 0; font-size: 16px; color: #0f172a;"><?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?></h4>
                    <span style="color: #64748b; font-size: 13px;">Thành viên hệ thống</span>
                </div>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <span style="display: block; color: #94a3b8; font-size: 12px; text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Liên hệ</span>
                    <div style="display: flex; gap: 8px; color: #334155; font-size: 14px; margin-bottom: 6px;">
                        <i class="fa-solid fa-envelope" style="color: #cbd5e1; margin-top: 3px;"></i>
                        <?php echo htmlspecialchars($order['email'] ?? '-'); ?>
                    </div>
                    <div style="display: flex; gap: 8px; color: #334155; font-size: 14px;">
                        <i class="fa-solid fa-phone" style="color: #cbd5e1; margin-top: 3px;"></i>
                        <?php echo htmlspecialchars($order['phone'] ?? '-'); ?>
                    </div>
                </div>
                <?php if (!empty($order['note'])): ?>
                <div style="background: #fdf8f6; padding: 12px; border-radius: 8px; border: 1px solid #ffedd5;">
                    <span style="display: block; color: #f97316; font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;"><i class="fa-solid fa-triangle-exclamation" style="margin-right: 4px;"></i>Ghi chú lúc mua hàng</span>
                    <p style="margin: 0; font-size: 13px; color: #431407; line-height: 1.5;"><?php echo nl2br(htmlspecialchars($order['note'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Internal notes -->
        <div style="background: white; border: 1px solid #eef2ff; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <h3 style="margin: 0 0 16px 0; font-size: 16px; color: #1e293b;"><i class="fa-solid fa-lock" style="margin-right: 10px; color: #6366f1;"></i>Ghi chú nội bộ</h3>
            <textarea id="adminNotes" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px; font-family: inherit; font-size: 14px; min-height: 100px; resize: vertical; margin-bottom: 12px; background: #f8fafc; outline: none; transition: border-color 0.2s;" placeholder="Chỉ nhân viên thấy..."></textarea>
            <button onclick="saveAdminNotes()" style="width: 100%; background: white; color: #4f46e5; border: 1px solid #4f46e5; padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                Lưu ghi chú
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', loadAdminNotes);

function loadAdminNotes() {
    fetch('api/get_order_notes.php?order_id=<?php echo $orderId; ?>')
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' && data.notes) {
                document.getElementById('adminNotes').value = data.notes;
            }
        })
        .catch(err => console.error('Error loading notes:', err));
}

function saveOrderStatus() {
    const status = document.getElementById('statusSelect').value;
    
    fetch('api/order_update_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            order_id: <?php echo $orderId; ?>,
            status: status
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Cập nhật trạng thái thành công!');
            location.reload();
        } else {
            alert('Lỗi: ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Có lỗi mạng khi cập nhật trạng thái.');
    });
}

function saveAdminNotes() {
    const notes = document.getElementById('adminNotes').value;
    const currentStatus = document.getElementById('statusSelect').value;
    
    fetch('api/order_update_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            order_id: <?php echo $orderId; ?>,
            status: currentStatus,
            admin_notes: notes
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Đã lưu ghi chú nội bộ!');
        } else {
            alert('Lỗi: ' + data.message);
        }
    })
    .catch(err => alert('Lỗi khi lưu ghi chú nội bộ.'));
}

function updateItemStatus(itemId, newStatus) {
    if (confirm('Cập nhật thẻ trạng thái của sản phẩm này?')) {
        fetch('api/order_update_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                order_id: <?php echo $orderId; ?>,
                item_id: itemId,
                status: newStatus
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload(); // reload to show changes properly
            } else {
                alert('Lỗi: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Lỗi khi cập nhật trạng thái sản phẩm.');
        });
    }
}
</script>

<?php
admin_footer();
