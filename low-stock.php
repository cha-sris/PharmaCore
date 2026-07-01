<?php
$pageTitle = 'Low Stock Alerts';
require_once '../../config/db.php';
require_once '../../auth/auth_check.php';
require_once '../../includes/functions.php';

$lowStockItems = [];
try {
    $lowStockItems = $db->query("SELECT m.id, m.name, m.strength, m.reorder_level, SUM(s.quantity_in_stock) as current_stock
                                FROM medicines m
                                LEFT JOIN stock s ON m.id = s.medicine_id
                                WHERE m.is_active = 1
                                GROUP BY m.id
                                HAVING SUM(s.quantity_in_stock) <= m.reorder_level
                                ORDER BY (SUM(s.quantity_in_stock) / m.reorder_level) ASC, m.name")->fetchAll();
} catch (Exception $e) {}

include '../../includes/header.php';
?>

<div style="margin-bottom: 20px;">
    <p style="color: #666;">Medicines below reorder level</p>
</div>

<!-- Low Stock Table -->
<div class="table-wrapper">
    <table class="table">
        <thead>
            <tr>
                <th>Medicine</th>
                <th>Current Stock</th>
                <th>Reorder Level</th>
                <th>Shortage</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($lowStockItems)): ?>
                <?php foreach ($lowStockItems as $item): ?>
                    <?php 
                        $current = $item['current_stock'] ?? 0;
                        $shortage = max(0, $item['reorder_level'] - $current);
                        $percentage = round($current / $item['reorder_level'] * 100);
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                            <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($item['strength'] ?? ''); ?></div>
                        </td>
                        <td>
                            <strong style="color: #E74C3C;"><?php echo $current; ?></strong>
                            <div style="margin-top: 5px; width: 150px; height: 6px; background: #ddd; border-radius: 3px; overflow: hidden;">
                                <div style="height: 100%; width: <?php echo min(100, $percentage); ?>%; background: <?php echo $percentage < 50 ? '#E74C3C' : '#F39C12'; ?>;"></div>
                            </div>
                            <small style="color: #666;"><?php echo $percentage; ?>% of target</small>
                        </td>
                        <td><?php echo $item['reorder_level']; ?></td>
                        <td><strong style="color: #E74C3C;"><?php echo $shortage; ?> units</strong></td>
                        <td>
                            <?php if ($current == 0): ?>
                                <span class="badge badge-danger">Out of Stock</span>
                            <?php elseif ($current <= $item['reorder_level'] / 2): ?>
                                <span class="badge badge-danger">Critical</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Low</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="../stock/index.php" class="btn btn-small btn-secondary">Add Stock</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align: center; padding: 30px; color: #666;">✓ All stock levels are healthy</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../../includes/footer.php'; ?>