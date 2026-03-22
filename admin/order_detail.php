<?php
require_once __DIR__ . '/layout.php';

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$orderId) {
    header('Location: orders.php');
    exit;
}

// Fetch order with customer info
$orderStmt = $pdo->prepare('SELECT o.*, u.username AS customer_name, u.email, u.phone, u.avatar FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?');
$orderStmt->execute([$orderId]);
$order = $orderStmt->fetch();

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Fetch order items
$itemsStmt = $pdo->prepare('SELECT od.*, p.id AS product_id, p.name AS product_name, p.image, pv.size AS variant_size, pv.color AS variant_color FROM order_details od LEFT JOIN products p ON od.product_id = p.id LEFT JOIN product_variants pv ON od.variant_id = pv.id WHERE od.order_id = ?');
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll();

// Fetch status history (if any audit table exists, otherwise show current status)
$statusHistory = [
    ['timestamp' => $order['created_at'], 'status' => 'created', 'note' => 'Đơn hàng được tạo'],
    ['timestamp' => $order['updated_at'] ?? $order['created_at'], 'status' => $order['status'], 'note' => 'Cập nhật trạng thái hiện tại']
];

if (!empty($order['returned_at'])) {
    $statusHistory[] = ['timestamp' => $order['returned_at'], 'status' => 'returned', 'note' => 'Đơn hàng hoàn trả'];
}

admin_header('Chi tiết đơn hàng #' . $orderId, 'orders');
?>

<style>
    .order-detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-bottom: 32px;
    }
    
    .detail-card {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
    }
    
    .detail-card h3 {
        margin: 0 0 16px 0;
        font-size: 16px;
        font-weight: 700;
    }
    
    .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .detail-row:last-child {
        border-bottom: none;
    }
    
    .detail-label {
        font-weight: 600;
        color: #666;
        font-size: 13px;
    }
    
    .detail-value {
        text-align: right;
    }
    
    .customer-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 12px;
    }
    
    .status-select {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        margin: 8px 0;
        font-size: 14px;
    }
    
    .admin-notes {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-family: Arial, sans-serif;
        font-size: 13px;
        min-height: 100px;
        resize: vertical;
    }
    
    .items-section {
        margin: 32px 0;
    }
    
    .items-section h3 {
        margin: 0 0 12px 0;
        font-size: 16px;
        font-weight: 700;
    }
    
    .item-card {
        display: flex;
        gap: 16px;
        padding: 16px;
        background: #f9f9f9;
        border-radius: 8px;
        margin-bottom: 12px;
        border-left: 4px solid #e95a8a;
    }
    
    .item-image {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 4px;
        background: white;
    }
    
    .item-details {
        flex: 1;
    }
    
    .item-name {
        font-weight: 700;
        margin: 0 0 4px 0;
    }
    
    .item-info {
        font-size: 13px;
        color: #666;
        margin: 2px 0;
    }
    
    .item-status {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        margin-top: 8px;
    }
    
    .status-pending { background: #fff3cd; color: #856404; }
    .status-collected { background: #cfe2ff; color: #084298; }
    .status-in-transit { background: #d1ecf1; color: #0c5460; }
    .status-in-use { background: #d4edda; color: #155724; }
    .status-returned { background: #e2e3e5; color: #383d41; }
    
    .item-actions {
        display: flex;
        gap: 8px;
    }
    
    .item-action-btn {
        padding: 4px 8px;
        border: 1px solid #ddd;
        background: white;
        border-radius: 4px;
        cursor: pointer;
        font-size: 11px;
        font-weight: 600;
    }
    
    .item-action-btn:hover {
        background: #f0f0f0;
    }
    
    .timeline {
        margin: 24px 0;
        padding: 16px;
        background: #f9f9f9;
        border-radius: 8px;
    }
    
    .timeline-item {
        display: flex;
        gap: 16px;
        padding: 12px 0;
        border-left: 2px solid #e95a8a;
        padding-left: 16px;
        margin-left: 8px;
    }
    
    .timeline-item:first-child {
        border-left: 3px solid #e95a8a;
    }
    
    .timeline-time {
        font-size: 12px;
        color: #999;
        font-weight: 600;
        white-space: nowrap;
    }
    
    .timeline-content {
        flex: 1;
    }
    
    .timeline-status {
        font-weight: 700;
        color: #333;
    }
    
    .save-status-btn {
        background: #28a745;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
        font-size: 13px;
    }
    
    .save-status-btn:hover {
        background: #218838;
    }
    
    .total-row {
        display: flex;
        justify-content: space-between;
        font-size: 16px;
        font-weight: 700;
        padding: 12px 0;
        border-top: 2px solid #e95a8a;
        margin-top: 12px;
    }
    
    @media (max-width: 1024px) {
        .order-detail-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="order-detail-grid">
    <!-- Customer Info -->
    <div class="detail-card">
        <h3>Thông tin khách hàng</h3>
        <?php if (!empty($order['avatar'])): ?>
            <img src="../<?php echo htmlspecialchars($order['avatar']); ?>" alt="Avatar" class="customer-avatar">
        <?php else: ?>
            <div style="width: 60px; height: 60px; background: #e95a8a; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 24px; margin-bottom: 12px;">
                <?php echo strtoupper(substr($order['customer_name'], 0, 1)); ?>
            </div>
        <?php endif; ?>
        <div class="detail-row">
            <span class="detail-label">Tên khách</span>
            <span class="detail-value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Email</span>
            <span class="detail-value"><?php echo htmlspecialchars($order['email']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Điện thoại</span>
            <span class="detail-value"><?php echo htmlspecialchars($order['phone']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Ghi chú</span>
            <span class="detail-value"><?php echo htmlspecialchars($order['note'] ?? '-'); ?></span>
        </div>
    </div>
    
    <!-- Order Info & Status -->
    <div class="detail-card">
        <h3>Thông tin đơn hàng</h3>
        <div class="detail-row">
            <span class="detail-label">Mã đơn</span>
            <span class="detail-value">#<?php echo $order['id']; ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Ngày tạo</span>
            <span class="detail-value"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Thanh toán</span>
            <span class="detail-value"><?php echo htmlspecialchars($order['payment_method']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Tổng tiền</span>
            <span class="detail-value" style="font-size: 16px; font-weight: 700; color: #e95a8a;"><?php echo number_format($order['total_price'], 0, ',', '.'); ?> đ</span>
        </div>
        
        <hr style="margin: 16px 0; border: none; border-top: 1px solid #f0f0f0;">
        
        <h3 style="margin: 16px 0; font-size: 14px;">Trạng thái</h3>
        <select id="statusSelect" class="status-select">
            <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Chờ xử lý</option>
            <option value="confirmed" <?php echo $order['status'] === 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
            <option value="ongoing" <?php echo $order['status'] === 'ongoing' ? 'selected' : ''; ?>>Đang thuê</option>
            <option value="returned" <?php echo $order['status'] === 'returned' ? 'selected' : ''; ?>>Đã trả</option>
            <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Hủy</option>
        </select>
        
        <button class="save-status-btn" onclick="saveOrderStatus()">Lưu trạng thái</button>
    </div>
</div>

<!-- Admin Notes -->
<div class="detail-card">
    <h3>Ghi chú nội bộ</h3>
    <textarea id="adminNotes" class="admin-notes" placeholder="Nhập ghi chú cho đội ngũ..."></textarea>
    <button class="save-status-btn" onclick="saveAdminNotes()" style="margin-top: 8px;">Lưu ghi chú</button>
</div>

<!-- Status Timeline -->
<div class="detail-card">
    <h3>Lịch sử giao dịch</h3>
    <div class="timeline">
        <?php foreach ($statusHistory as $event): ?>
            <div class="timeline-item">
                <div class="timeline-time"><?php echo date('d/m H:i', strtotime($event['timestamp'])); ?></div>
                <div class="timeline-content">
                    <div class="timeline-status"><?php echo htmlspecialchars($event['note']); ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Order Items -->
<div class="items-section">
    <h3>Sản phẩm trong đơn hàng</h3>
    <?php foreach ($items as $item): ?>
        <div class="item-card">
            <?php if (!empty($item['image'])): ?>
                <img src="../<?php echo htmlspecialchars($item['image']); ?>" alt="Product" class="item-image">
            <?php else: ?>
                <div class="item-image" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-image" style="color: #ccc; font-size: 24px;"></i>
                </div>
            <?php endif; ?>
            
            <div class="item-details">
                <p class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></p>
                
                <?php if (!empty($item['variant_size']) || !empty($item['variant_color'])): ?>
                    <p class="item-info">
                        Phiên bản: 
                        <?php echo htmlspecialchars(trim(($item['variant_size'] ?? '') . ' ' . ($item['variant_color'] ?? ''))); ?>
                    </p>
                <?php endif; ?>
                
                <p class="item-info">Số lượng: <?php echo $item['quantity']; ?></p>
                <p class="item-info">Giá: <?php echo number_format($item['price'], 0, ',', '.'); ?> đ</p>
                
                <?php if (!empty($item['rental_start']) && !empty($item['rental_end'])): ?>
                    <p class="item-info">
                        Thuê: <?php echo date('d/m', strtotime($item['rental_start'])); ?> → 
                        <?php echo date('d/m/Y', strtotime($item['rental_end'])); ?>
                        (<?php echo $item['duration_days']; ?> ngày)
                    </p>
                <?php endif; ?>
                
                <span class="item-status status-<?php echo str_replace('-', '', $item['status']); ?>">
                    <?php echo htmlspecialchars($item['status'] ?? 'N/A'); ?>
                </span>
                
                <div class="item-actions" style="margin-top: 8px;">
                    <button class="item-action-btn" onclick="updateItemStatus(<?php echo $item['id']; ?>, 'collected')">Lấy</button>
                    <button class="item-action-btn" onclick="updateItemStatus(<?php echo $item['id']; ?>, 'in-transit')">Vận chuyển</button>
                    <button class="item-action-btn" onclick="updateItemStatus(<?php echo $item['id']; ?>, 'in-use')">Sử dụng</button>
                    <button class="item-action-btn" onclick="updateItemStatus(<?php echo $item['id']; ?>, 'returned')" style="border-color: #dc3545; color: #dc3545;">Trả</button>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
// Load admin notes on page load
document.addEventListener('DOMContentLoaded', function() {
    loadAdminNotes();
});

function loadAdminNotes() {
    fetch('api/get_order_notes.php?order_id=<?php echo $orderId; ?>')
        .then(res => res.json())
        .then(data => {
            if (data.notes) {
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
            alert('Cập nhật trạng thái thành công');
            location.reload();
        } else {
            alert('Lỗi: ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Lỗi khi cập nhật');
    });
}

function saveAdminNotes() {
    const notes = document.getElementById('adminNotes').value;
    
    fetch('api/order_update_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            order_id: <?php echo $orderId; ?>,
            admin_notes: notes
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Ghi chú đã lưu');
        } else {
            alert('Lỗi: ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Lỗi khi lưu ghi chú');
    });
}

function updateItemStatus(itemId, newStatus) {
    if (confirm('Cập nhật trạng thái thành "' + newStatus + '"?')) {
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
                location.reload();
            } else {
                alert('Lỗi: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Lỗi khi cập nhật');
        });
    }
}
</script>

<?php
admin_footer();
