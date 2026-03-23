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
    <table class="table" style="border-collapse: separate; border-spacing: 0; width: 100%;">
        <thead>
            <tr>
                <th style="padding: 16px; border-bottom: 2px solid #f3f4f6; color: #6b7280; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;">Đơn hàng</th>
                <th style="padding: 16px; border-bottom: 2px solid #f3f4f6; color: #6b7280; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;">Khách hàng</th>
                <th style="padding: 16px; border-bottom: 2px solid #f3f4f6; color: #6b7280; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;">Tổng tiền</th>
                <th style="padding: 16px; border-bottom: 2px solid #f3f4f6; color: #6b7280; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;">Trạng thái</th>
                <th style="padding: 16px; border-bottom: 2px solid #f3f4f6; color: #6b7280; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; text-align: right;">Thao tác</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
            <tr style="background: #fff; transition: background 0.2s;">
                <td style="padding: 16px; border-bottom: none;">
                    <strong style="color: #111827; font-size: 16px;">#<?php echo $order['id']; ?></strong><br>
                    <small style="color: #6b7280; font-weight: 500; display: inline-block; margin-top: 4px;"><i class="fa-regular fa-clock" style="margin-right: 4px;"></i><?php echo date('H:i - d/m/Y', strtotime($order['created_at'])); ?></small>
                </td>
                <td style="padding: 16px; border-bottom: none;">
                    <strong style="color: #111827; font-size: 14px;"><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                    <div style="color: #6b7280; font-size: 13px; margin-top: 4px;">
                        <span style="display: inline-block; width: 16px; text-align: center;"><i class="fa-solid fa-envelope"></i></span> <?php echo htmlspecialchars($order['email']); ?><br>
                        <span style="display: inline-block; width: 16px; text-align: center;"><i class="fa-solid fa-phone"></i></span> <?php echo htmlspecialchars($order['phone']); ?>
                    </div>
                </td>
                <td style="padding: 16px; border-bottom: none;">
                    <strong style="color: #6366f1; font-size: 16px;"><?php echo number_format($order['total_price'], 0, ',', '.'); ?> đ</strong><br>
                    <small style="color: #6b7280; display: inline-block; margin-top: 4px; font-weight: 600; text-transform: uppercase;"><i class="fa-regular fa-credit-card" style="margin-right: 4px;"></i><?php echo htmlspecialchars($order['payment_method']); ?></small>
                </td>
                <td style="padding: 16px; border-bottom: none;">
                    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                        <?php 
                            $statusLabels = [
                                'pending' => 'Chờ xử lý',
                                'confirmed' => 'Đã xác nhận',
                                'ongoing' => 'Đang thuê',
                                'returned' => 'Đã trả',
                                'cancelled' => 'Đã hủy'
                            ];
                            echo htmlspecialchars($statusLabels[$order['status']] ?? $order['status']); 
                        ?>
                    </span>
                </td>
                <td style="padding: 16px; border-bottom: none; text-align: right;">
                    <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="btn ghost" style="padding: 8px 16px; font-size: 13px; border-radius: 8px; font-weight: 600;"><i class="fa-solid fa-eye"></i> Chi tiết</a>
                </td>
            </tr>
            <tr>
                <td colspan="5" style="padding: 0 16px 24px 16px; border-bottom: 1px solid #eef2ff;">
                    <div style="background: #f8fafc; border-radius: 12px; padding: 16px; border: 1px solid #e2e8f0; margin-top: 8px;">
                        <h4 style="margin: 0 0 12px 0; font-size: 12px; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;"><i class="fa-solid fa-box-open" style="margin-right: 6px;"></i>Sản phẩm đơn hàng</h4>
                        <div style="display: grid; gap: 8px;">
                            <?php foreach ($order['items'] as $item): ?>
                            <div style="display: flex; align-items: center; justify-content: space-between; background: #fff; padding: 12px 16px; border-radius: 8px; border: 1px solid #eef2ff; box-shadow: 0 2px 4px rgba(15,23,42,0.02);">
                                <div>
                                    <strong style="font-size: 14px; color: #1e293b;">
                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                    </strong>
                                    <div style="color: #64748b; font-size: 13px; margin-top: 4px;">
                                        <?php if (!empty($item['variant_id'])): ?>
                                            <span style="margin-right: 12px;"><i class="fa-solid fa-tag" style="margin-right: 4px;"></i><?php echo htmlspecialchars(trim(($item['variant_size'] ?? '') . ' ' . ($item['variant_color'] ?? ''))); ?></span>
                                        <?php endif; ?>
                                        <span><i class="fa-solid fa-cubes" style="margin-right: 4px;"></i>Số lượng: <strong><?php echo $item['quantity']; ?></strong></span>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <strong style="color: #0f172a; font-size: 14px;"><?php echo number_format($item['price'], 0, ',', '.'); ?> đ</strong>
                                    <?php if (!empty($item['rental_start']) && !empty($item['rental_end'])): ?>
                                        <div style="margin-top: 6px;">
                                            <span style="background: #e0e7ff; color: #4f46e5; font-size: 12px; padding: 4px 10px; border-radius: 6px; font-weight: 600;">
                                                <i class="fa-regular fa-calendar" style="margin-right: 4px;"></i> 
                                                <?php echo date('d/m/Y', strtotime($item['rental_start'])); ?> → <?php echo date('d/m/Y', strtotime($item['rental_end'])); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
admin_footer();
