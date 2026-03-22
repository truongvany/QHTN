<?php
require_once __DIR__ . '/layout.php';

$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$variantId = isset($_GET['variant_id']) ? (int)$_GET['variant_id'] : 0;
$monthParam = isset($_GET['month']) ? trim($_GET['month']) : date('Y-m');

// Fetch all products
$allProducts = $pdo->query('SELECT id, name, image FROM products ORDER BY name ASC')->fetchAll();

$variants = [];
$productData = null;

if ($productId > 0) {
    // Fetch product details
    $productStmt = $pdo->prepare('SELECT id, name, image FROM products WHERE id = ?');
    $productStmt->execute([$productId]);
    $productData = $productStmt->fetch();
    
    // Fetch variants for this product
    $variantsStmt = $pdo->prepare('SELECT id, size, color, stock FROM product_variants WHERE product_id = ? ORDER BY size, color');
    $variantsStmt->execute([$productId]);
    $variants = $variantsStmt->fetchAll();
}

// Fetch inventory data for calendar
$inventoryData = [];
if ($variantId > 0) {
    try {
        // Get booked dates from order_details
        $stmt = $pdo->prepare('
            SELECT DATE(od.rental_start) as start_date, DATE(od.rental_end) as end_date, 
                   SUM(od.quantity) as booked_qty, COUNT(*) as booking_count
            FROM order_details od
            LEFT JOIN orders o ON od.order_id = o.id
            WHERE od.variant_id = ? 
              AND od.status != "returned"
              AND o.status != "cancelled"
              AND DATE(od.rental_start) >= DATE_SUB(?, INTERVAL 3 MONTH)
              AND DATE(od.rental_end) <= DATE_ADD(?, INTERVAL 3 MONTH)
            GROUP BY DATE(od.rental_start), DATE(od.rental_end)
        ');
        $stmt->execute([
            $variantId,
            $monthParam . '-01',
            $monthParam . '-01'
        ]);
        $inventoryData = $stmt->fetchAll();
    } catch (Exception $e) {
        error_log('Inventory query error: ' . $e->getMessage());
    }
}

// Get current variant stock
$currentVariant = null;
if ($variantId > 0) {
    $stmt = $pdo->prepare('SELECT id, size, color, stock FROM product_variants WHERE id = ? AND product_id = ?');
    $stmt->execute([$variantId, $productId]);
    $currentVariant = $stmt->fetch();
}

// Helper function to get days in month
function getDaysInMonth($year, $month) {
    return cal_days_in_month(CAL_GREGORIAN, $month, $year);
}

// Parse month parameter
list($year, $month) = explode('-', $monthParam);
$year = (int)$year;
$month = (int)$month;

// Calculate previous and next month
$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

$daysInMonth = getDaysInMonth($year, $month);
$firstDayOfWeek = date('N', mktime(0, 0, 0, $month, 1, $year)); // 1=Monday, 7=Sunday

admin_header('Quản lý Kho Hàng', 'inventory');
?>

<style>
    .inventory-header {
        display: flex;
        gap: 16px;
        margin-bottom: 24px;
        align-items: flex-end;
    }
    
    .inventory-header .input {
        flex: 1;
        max-width: 400px;
    }
    
    .inventory-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-bottom: 24px;
    }
    
    .product-selector {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
    }
    
    .product-selector h3 {
        margin: 0 0 12px 0;
        font-size: 16px;
        font-weight: 700;
    }
    
    .product-list {
        max-height: 400px;
        overflow-y: auto;
        border: 1px solid #f0f0f0;
        border-radius: 4px;
    }
    
    .product-item {
        padding: 12px;
        border-bottom: 1px solid #f0f0f0;
        cursor: pointer;
        display: flex;
        gap: 12px;
        align-items: left;
    }
    
    .product-item:last-child {
        border-bottom: none;
    }
    
    .product-item:hover {
        background: #f9f9f9;
    }
    
    .product-item.active {
        background: #ffd6e6;
        font-weight: 600;
    }
    
    .product-item img {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 4px;
        background: #f0f0f0;
    }
    
    .product-info {
        flex: 1;
        font-size: 13px;
    }
    
    .variant-selector {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
    }
    
    .variant-selector h3 {
        margin: 0 0 12px 0;
        font-size: 16px;
        font-weight: 700;
    }
    
    .variant-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 8px;
    }
    
    .variant-btn {
        padding: 12px;
        border: 2px solid #ddd;
        background: white;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
        text-align: center;
        transition: all 0.2s;
    }
    
    .variant-btn:hover {
        border-color: #e95a8a;
    }
    
    .variant-btn.active {
        border-color: #e95a8a;
        background: #ffd6e6;
        color: #c2185b;
    }
    
    .calendar-card {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
        grid-column: 1 / -1;
    }
    
    .calendar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .calendar-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
    }
    
    .calendar-nav {
        display: flex;
        gap: 12px;
    }
    
    .calendar-nav a {
        padding: 8px 12px;
        background: #f0f0f0;
        border-radius: 4px;
        text-decoration: none;
        color: #333;
        font-size: 12px;
        font-weight: 600;
    }
    
    .calendar-nav a:hover {
        background: #e0e0e0;
    }
    
    .weekdays {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 4px;
        margin-bottom: 8px;
    }
    
    .weekday {
        text-align: center;
        font-weight: 700;
        font-size: 12px;
        color: #999;
        padding: 8px;
    }
    
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 4px;
    }
    
    .calendar-day {
        aspect-ratio: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        background: white;
        position: relative;
    }
    
    .calendar-day.empty {
        background: #f9f9f9;
        border: none;
        cursor: default;
    }
    
    .calendar-day.available {
        background: #d4edda;
        color: #155724;
        border-color: #28a745;
    }
    
    .calendar-day.booked {
        background: #f8d7da;
        color: #721c24;
        border-color: #dc3545;
    }
    
    .calendar-day.partial {
        background: #fff3cd;
        color: #856404;
        border-color: #ffc107;
    }
    
    .calendar-day:hover:not(.empty) {
        box-shadow: 0 0 8px rgba(233, 90, 138, 0.3);
    }
    
    .stock-info {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        margin-top: 20px;
    }
    
    .stock-card {
        background: #f9f9f9;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 16px;
        text-align: center;
    }
    
    .stock-card .label {
        font-size: 12px;
        color: #999;
        font-weight: 600;
        margin-bottom: 8px;
    }
    
    .stock-card .value {
        font-size: 24px;
        font-weight: 700;
        color: #333;
    }
    
    .legend {
        display: flex;
        gap: 20px;
        padding: 16px;
        background: #f9f9f9;
        border-radius: 8px;
        margin-top: 16px;
    }
    
    .legend-item {
        display: flex;
        gap: 8px;
        align-items: center;
        font-size: 13px;
    }
    
    .legend-color {
        width: 24px;
        height: 24px;
        border-radius: 4px;
        border: 1px solid #ddd;
    }
    
    .color-available { background: #d4edda; border-color: #28a745; }
    .color-booked { background: #f8d7da; border-color: #dc3545; }
    .color-partial { background: #fff3cd; border-color: #ffc107; }
</style>

<div class="inventory-grid">
    <div class="product-selector">
        <h3>Chọn sản phẩm</h3>
        <div class="product-list">
            <?php foreach ($allProducts as $p): ?>
                <div class="product-item <?php echo $p['id'] === $productId ? 'active' : ''; ?>" 
                     onclick="selectProduct(<?php echo $p['id']; ?>)">
                    <?php if (!empty($p['image'])): ?>
                        <img src="../<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
                    <?php else: ?>
                        <div style="width: 40px; height: 40px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-image" style="color: #ccc;"></i>
                        </div>
                    <?php endif; ?>
                    <div class="product-info">
                        <div><?php echo htmlspecialchars($p['name']); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="variant-selector">
        <h3><?php echo $productData ? 'Chọn phiên bản' : 'Chọn sản phẩm trước'; ?></h3>
        <?php if (!empty($variants)): ?>
            <div class="variant-list">
                <?php foreach ($variants as $v): ?>
                    <button class="variant-btn <?php echo $v['id'] === $variantId ? 'active' : ''; ?>" 
                            onclick="selectVariant(<?php echo $productId; ?>, <?php echo $v['id']; ?>)">
                        <div><?php echo htmlspecialchars($v['size'] ?? '-'); ?></div>
                        <div><?php echo htmlspecialchars($v['color'] ?? '-'); ?></div>
                        <div style="font-size: 11px; color: #999; margin-top: 4px;">Kho: <?php echo $v['stock']; ?></div>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color: #999; text-align: center; padding: 20px;">Không có phiên bản nào</p>
        <?php endif; ?>
    </div>
</div>

<?php if ($variantId > 0 && $currentVariant): ?>
<div class="calendar-card">
    <div class="calendar-header">
        <h3><?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></h3>
        <div class="calendar-nav">
            <a href="?product_id=<?php echo $productId; ?>&variant_id=<?php echo $variantId; ?>&month=<?php echo sprintf('%04d-%02d', $prevYear, $prevMonth); ?>">
                ← Tháng trước
            </a>
            <a href="?product_id=<?php echo $productId; ?>&variant_id=<?php echo $variantId; ?>&month=<?php echo date('Y-m'); ?>">
                Hôm nay
            </a>
            <a href="?product_id=<?php echo $productId; ?>&variant_id=<?php echo $variantId; ?>&month=<?php echo sprintf('%04d-%02d', $nextYear, $nextMonth); ?>">
                Tháng sau →
            </a>
        </div>
    </div>
    
    <div class="weekdays">
        <div class="weekday">Thứ 2</div>
        <div class="weekday">Thứ 3</div>
        <div class="weekday">Thứ 4</div>
        <div class="weekday">Thứ 5</div>
        <div class="weekday">Thứ 6</div>
        <div class="weekday">Thứ 7</div>
        <div class="weekday">Chủ nhật</div>
    </div>
    
    <div class="calendar-grid">
        <?php 
        // Empty cells before first day of month
        for ($i = 1; $i < $firstDayOfWeek; $i++) {
            echo '<div class="calendar-day empty"></div>';
        }
        
        // Days of month
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $isBooked = false;
            $isPartial = false;
            
            // Check if date is booked
            foreach ($inventoryData as $booking) {
                $startDate = $booking['start_date'];
                $endDate = $booking['end_date'];
                if ($dateStr >= $startDate && $dateStr <= $endDate) {
                    if ($booking['booked_qty'] >= $currentVariant['stock']) {
                        $isBooked = true;
                    } else {
                        $isPartial = true;
                    }
                    break;
                }
            }
            
            $class = 'calendar-day';
            if ($isBooked) {
                $class .= ' booked';
                $display = '✕';
            } elseif ($isPartial) {
                $class .= ' partial';
                $display = '⚠';
            } else {
                $class .= ' available';
                $display = '✓';
            }
            
            echo '<div class="' . $class . '">' . $display . '</div>';
        }
        ?>
    </div>
    
    <div class="legend">
        <div class="legend-item">
            <div class="legend-color color-available"></div>
            <span>Có sẵn</span>
        </div>
        <div class="legend-item">
            <div class="legend-color color-partial"></div>
            <span>Còn một vài</span>
        </div>
        <div class="legend-item">
            <div class="legend-color color-booked"></div>
            <span>Hết</span>
        </div>
    </div>
    
    <div class="stock-info">
        <div class="stock-card">
            <div class="label">Tổng kho</div>
            <div class="value"><?php echo $currentVariant['stock']; ?></div>
        </div>
        <div class="stock-card">
            <div class="label">Đang cho thuê</div>
            <div class="value" id="bookedCount">-</div>
        </div>
        <div class="stock-card">
            <div class="label">Có sẵn</div>
            <div class="value" id="availableCount">-</div>
        </div>
    </div>
</div>

<script>
function selectProduct(productId) {
    window.location.href = '?product_id=' + productId;
}

function selectVariant(productId, variantId) {
    window.location.href = '?product_id=' + productId + '&variant_id=' + variantId;
}

// Calculate booked and available count on page load
document.addEventListener('DOMContentLoaded', function() {
    const totalStock = <?php echo $currentVariant['stock']; ?>;
    
    // Count booked items from inventory data
    let maxBooked = 0;
    const bookings = <?php echo json_encode($inventoryData); ?>;
    
    for (let booking of bookings) {
        if (parseInt(booking['booked_qty'] || 0) > maxBooked) {
            maxBooked = parseInt(booking['booked_qty']);
        }
    }
    
    const available = Math.max(0, totalStock - maxBooked);
    
    document.getElementById('bookedCount').textContent = maxBooked;
    document.getElementById('availableCount').textContent = available;
});
</script>

<?php else: ?>
    <div class="detail-card">
        <p style="text-align: center; color: #999; padding: 40px;">Chọn sản phẩm và phiên bản để xem lịch sẵn có</p>
    </div>
<?php endif; ?>

<?php
admin_footer();
