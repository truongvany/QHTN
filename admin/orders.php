<?php
require_once __DIR__ . '/layout.php';

$search = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$params = [];
$sql = 'SELECT o.*, u.username AS customer_name, u.email, u.phone FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE 1=1';

if ($search !== '') {
    $sql .= ' AND (o.id = ? OR u.email LIKE ? OR u.username LIKE ?)';
    $params = [(int)$search, "%$search%", "%$search%"];    
}

if ($statusFilter !== '') {
    $sql .= ' AND o.status = ?';
    $params[] = $statusFilter;
}

$sql .= ' ORDER BY o.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($orders as &$order) {
    $itemsStmt = $pdo->prepare('SELECT od.*, p.name AS product_name, pv.size AS variant_size, pv.color AS variant_color FROM order_details od LEFT JOIN products p ON od.product_id = p.id LEFT JOIN product_variants pv ON od.variant_id = pv.id WHERE od.order_id = ?');
    $itemsStmt->execute([$order['id']]);
    $order['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($order);

admin_header('Đơn hàng', 'orders');
?>
<div class="card">
    <div class="section-title">
        <div>
            <h3>Đơn hàng</h3>
            <p class="muted">Tổng <?php echo count($orders); ?> đơn</p>
        </div>
        <form class="orders-filter" method="GET">
            <input class="input" type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tìm ID, email, tên">
            <select class="input" name="status">
                <option value="">— Tất cả trạng thái —</option>
                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Chờ xử lý</option>
                <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                <option value="ongoing" <?php echo $statusFilter === 'ongoing' ? 'selected' : ''; ?>>Đang thuê</option>
                <option value="returned" <?php echo $statusFilter === 'returned' ? 'selected' : ''; ?>>Đã trả</option>
                <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Hủy</option>
            </select>
            <button class="btn ghost" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Tìm</button>
        </form>
    </div>
    <table class="table">
            <tr><th>ID</th><th>Khách hàng</th><th>Email</th><th>SĐT</th><th>Ngày</th><th>Trạng thái</th><th>Thanh toán</th><th>Tổng</th><th>Thời gian thuê</th><th></th></tr>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td>#<?php echo $order['id']; ?></td>
                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                <td><?php echo htmlspecialchars($order['email']); ?></td>
                <td><?php echo htmlspecialchars($order['phone']); ?></td>
                <td><?php echo $order['created_at']; ?></td>
                    <td><span class="tag"><?php echo htmlspecialchars($order['status']); ?></span></td>
                <td><span class="tag"><?php echo htmlspecialchars($order['payment_method']); ?></span></td>
                <td><?php echo number_format($order['total_price'], 0, ',', '.'); ?> đ</td>
                <td>
                    <?php
                    $ranges = [];
                    foreach ($order['items'] as $it) {
                        if (!empty($it['rental_start']) && !empty($it['rental_end'])) {
                            $ranges[] = htmlspecialchars($it['rental_start'] . ' → ' . $it['rental_end']);
                        }
                    }
                    echo $ranges ? implode('<br>', $ranges) : '-';
                    ?>
                </td>
                <td style="text-align: center;">
                    <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="btn ghost" style="padding: 6px 12px; font-size: 12px;">Chi tiết</a>
                </td>
            </tr>
            <tr>
                <td colspan="10">
                    <strong>Sản phẩm:</strong>
                    <ul>
                        <?php foreach ($order['items'] as $item): ?>
                            <li><?php echo htmlspecialchars($item['product_name']); ?>
                                <?php if (!empty($item['variant_id'])): ?>
                                    · <?php echo htmlspecialchars(trim(($item['variant_size'] ?? '') . ' ' . ($item['variant_color'] ?? ''))); ?>
                                <?php endif; ?>
                                 (SL: <?php echo $item['quantity']; ?>) - <?php echo number_format($item['price'], 0, ',', '.'); ?> đ
                                <?php if (!empty($item['rental_start']) && !empty($item['rental_end'])): ?>
                                    · <?php echo htmlspecialchars($item['rental_start']); ?> → <?php echo htmlspecialchars($item['rental_end']); ?>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php
admin_footer();
