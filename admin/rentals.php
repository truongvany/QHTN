<?php
require_once __DIR__ . '/layout.php';

// Get filter parameters
$filterStatus = trim($_GET['status'] ?? '');
$filterCustomer = trim($_GET['customer'] ?? '');
$sortBy = trim($_GET['sort'] ?? 'due_date'); // due_date or created_at

// Build query for ongoing/upcoming rentals
$sql = "SELECT 
    od.id AS item_id,
    o.id AS order_id,
    o.created_at,
    o.status AS order_status,
    u.username AS customer_name,
    u.email,
    u.phone,
    p.name AS product_name,
    p.image,
    pv.size AS variant_size,
    pv.color AS variant_color,
    od.quantity,
    od.price,
    od.rental_start,
    od.rental_end,
    od.duration_days,
    od.status AS item_status,
    o.admin_notes
FROM order_details od
LEFT JOIN orders o ON od.order_id = o.id
LEFT JOIN users u ON o.user_id = u.id
LEFT JOIN products p ON od.product_id = p.id
LEFT JOIN product_variants pv ON od.variant_id = pv.id
WHERE od.rental_start IS NOT NULL 
    AND od.rental_end IS NOT NULL
    AND od.status != 'returned'";

$params = [];

// Apply filters
if ($filterStatus !== '') {
    $sql .= ' AND od.status = ?';
    $params[] = $filterStatus;
}

if ($filterCustomer !== '') {
    $sql .= ' AND (u.username LIKE ? OR u.email LIKE ?)';
    $params[] = "%$filterCustomer%";
    $params[] = "%$filterCustomer%";
}

// Sort
if ($sortBy === 'created_at') {
    $sql .= ' ORDER BY o.created_at DESC';
} else {
    $sql .= ' ORDER BY od.rental_end ASC';
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rentals = $stmt->fetchAll();

// Calculate days remaining for each rental
$now = new DateTime();
foreach ($rentals as &$rental) {
    $endDate = new DateTime($rental['rental_end']);
    $diff = $endDate->diff($now);
    $daysLeft = $diff->days;
    if ($endDate < $now) {
        $daysLeft = -$daysLeft;
        $rental['overdue'] = true;
    } else {
        $rental['overdue'] = false;
    }
    $rental['days_left'] = $daysLeft;
    
    // Determine urgency class
    if ($daysLeft < 0) {
        $rental['urgency_class'] = 'overdue';
        $rental['urgency_text'] = '⚠️ Quá hạn';
    } elseif ($daysLeft < 3) {
        $rental['urgency_class'] = 'critical';
        $rental['urgency_text'] = '🔴 Sắp hết (' . $daysLeft . ' ngày)';
    } elseif ($daysLeft < 7) {
        $rental['urgency_class'] = 'warning';
        $rental['urgency_text'] = '🟠 Cạnh tranh (' . $daysLeft . ' ngày)';
    } else {
        $rental['urgency_class'] = 'ok';
        $rental['urgency_text'] = '✅ Còn ' . $daysLeft . ' ngày';
    }
}
unset($rental);

admin_header('Quản lý Thuê Hàng', 'rentals');
?>

<style>
    .filter-form {
        display: flex;
        gap: 12px;
        margin-bottom: 20px;
        align-items: flex-end;
    }
    
    .filter-form .input {
        flex: 1;
        max-width: 300px;
    }
    
    .status-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .status-pending { background: #fff3cd; color: #856404; }
    .status-collected { background: #cfe2ff; color: #084298; }
    .status-in-transit { background: #d1ecf1; color: #0c5460; }
    .status-in-use { background: #d4edda; color: #155724; }
    .status-returned { background: #e2e3e5; color: #383d41; }
    
    .urgency-overdue { color: #dc3545; font-weight: 700; }
    .urgency-critical { color: #dc3545; font-weight: 700; }
    .urgency-warning { color: #fd7e14; font-weight: 600; }
    .urgency-ok { color: #28a745; }
    
    .rental-row {
        cursor: pointer;
        transition: background-color 0.2s;
    }
    
    .rental-row:hover {
        background-color: #f5f5f5;
    }
    
    .action-btn {
        padding: 6px 12px;
        border: none;
        background: #007bff;
        color: white;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
    }
    
    .action-btn:hover {
        background: #0056b3;
    }
    
    .action-btn.danger {
        background: #dc3545;
    }
    
    .action-btn.danger:hover {
        background: #c82333;
    }
    
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
    }
    
    .modal.active {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .modal-content {
        background: white;
        padding: 24px;
        border-radius: 8px;
        max-width: 500px;
        width: 90%;
    }
    
    .modal-header {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 16px;
    }
    
    .modal-body {
        margin-bottom: 16px;
    }
    
    .modal-footer {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
    }
    
    textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-family: Arial, sans-serif;
        font-size: 13px;
        min-height: 80px;
        resize: vertical;
    }
</style>

<div class="card">
    <div class="section-title">
        <div>
            <h3>Quản lý Thuê Hàng</h3>
            <p class="muted">Theo dõi những đơn hàng còn hoạt động</p>
        </div>
    </div>
    
    <form method="GET" class="filter-form">
        <input type="text" name="customer" placeholder="Tìm tên khách hoặc email" value="<?php echo htmlspecialchars($filterCustomer); ?>" class="input">
        <select name="status" class="input">
            <option value="">— Tất cả trạng thái —</option>
            <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Chờ xửa lý</option>
            <option value="collected" <?php echo $filterStatus === 'collected' ? 'selected' : ''; ?>>Đã lấy</option>
            <option value="in-transit" <?php echo $filterStatus === 'in-transit' ? 'selected' : ''; ?>>Đang vận chuyển</option>
            <option value="in-use" <?php echo $filterStatus === 'in-use' ? 'selected' : ''; ?>>Đang sử dụng</option>
        </select>
        <select name="sort" class="input">
            <option value="due_date" <?php echo $sortBy === 'due_date' ? 'selected' : ''; ?>>Sắp hết hạn</option>
            <option value="created_at" <?php echo $sortBy === 'created_at' ? 'selected' : ''; ?>>Mới nhất</option>
        </select>
        <button type="submit" class="btn ghost">🔍 Lọc</button>
    </form>
    
    <?php if (empty($rentals)): ?>
        <p style="text-align: center; color: #999; padding: 40px;">Không có đơn hàng nào đang hoạt động</p>
    <?php else: ?>
        <table class="table">
            <tr>
                <th>Mã đơn</th>
                <th>Khách hàng</th>
                <th>Sản phẩm</th>
                <th>Ngày nhận</th>
                <th>Ngày trả</th>
                <th>Thời gian còn lại</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
            <?php foreach ($rentals as $rental): ?>
                <tr class="rental-row">
                    <td><strong>#<?php echo $rental['order_id']; ?></strong></td>
                    <td>
                        <div><?php echo htmlspecialchars($rental['customer_name']); ?></div>
                        <small style="color: #666;"><?php echo htmlspecialchars($rental['email']); ?></small>
                    </td>
                    <td>
                        <div><?php echo htmlspecialchars($rental['product_name']); ?></div>
                        <?php if ($rental['variant_size'] || $rental['variant_color']): ?>
                            <small style="color: #666;">
                                <?php echo htmlspecialchars(trim(($rental['variant_size'] ?? '') . ' ' . ($rental['variant_color'] ?? ''))); ?>
                            </small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo date('d/m/Y', strtotime($rental['rental_start'])); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($rental['rental_end'])); ?></td>
                    <td>
                        <span class="urgency-<?php echo $rental['urgency_class']; ?>">
                            <?php echo $rental['urgency_text']; ?>
                        </span>
                    </td>
                    <td>
                        <span class="status-badge status-<?php echo str_replace('-', '', $rental['item_status']); ?>">
                            <?php echo htmlspecialchars($rental['item_status']); ?>
                        </span>
                    </td>
                    <td>
                        <button class="action-btn" onclick="openNoteModal(<?php echo $rental['order_id']; ?>, '<?php echo htmlspecialchars(addslashes($rental['customer_name'])); ?>')">📝 Note</button>
                        <button class="action-btn danger" onclick="markReturned(<?php echo $rental['order_id']; ?>, <?php echo $rental['item_id']; ?>, '<?php echo htmlspecialchars(addslashes($rental['product_name'])); ?>')">✓ Trả</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<!-- Note Modal -->
<div class="modal" id="noteModal">
    <div class="modal-content">
        <div class="modal-header">Thêm ghi chú cho đơn hàng <span id="modalOrderId"></span></div>
        <div class="modal-body">
            <small style="color: #666;">Khách: <span id="modalCustomer"></span></small>
            <textarea id="noteText" placeholder="Nhập ghi chú nội bộ..."></textarea>
        </div>
        <div class="modal-footer">
            <button class="btn" onclick="closeNoteModal()">Hủy</button>
            <button class="btn primary" onclick="saveNote()">Lưu ghi chú</button>
        </div>
    </div>
</div>

<script>
let currentOrderId = null;

function openNoteModal(orderId, customerName) {
    currentOrderId = orderId;
    document.getElementById('modalOrderId').textContent = '#' + orderId;
    document.getElementById('modalCustomer').textContent = customerName;
    
    // Load existing notes
    document.getElementById('noteText').value = '';
    
    document.getElementById('noteModal').classList.add('active');
}

function closeNoteModal() {
    document.getElementById('noteModal').classList.remove('active');
    currentOrderId = null;
}

function saveNote() {
    if (!currentOrderId) return;
    
    const note = document.getElementById('noteText').value;
    
    fetch('api/order_update_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            order_id: currentOrderId,
            status: 'ongoing',
            admin_notes: note
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Ghi chú đã lưu');
            closeNoteModal();
        } else {
            alert('Lỗi: ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Lỗi khi lưu ghi chú');
    });
}

function markReturned(orderId, itemId, productName) {
    if (confirm('Xác nhận đơn hàng "' + productName + '" đã được trả lại?')) {
        fetch('api/mark_returned.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                order_id: orderId,
                item_id: itemId
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert('Đã đánh dấu là trả lại');
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

// Close modal when clicking outside
document.getElementById('noteModal').addEventListener('click', function(e) {
    if (e.target === this) closeNoteModal();
});
</script>

<?php
admin_footer();
