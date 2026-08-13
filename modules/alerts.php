<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../auth/login.php");
    exit;
}

require_once "../config/config.php";

$low_stock = [];
$expiring  = [];

try {
    // 1. Fetch low/out of stock items
    $stmt = $pdo->query("SELECT * FROM medicines WHERE stock <= min_stock ORDER BY stock ASC");
    $low_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Fetch expired/expiring items within 30 days
    $stmt = $pdo->query("SELECT *, DATEDIFF(expiry_date, CURDATE()) AS days_left 
                         FROM medicines 
                         WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) 
                         ORDER BY expiry_date ASC");
    $expiring = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $db_error = $e->getMessage();
}

// Calculate Summary Counts
$out_count     = count(array_filter($low_stock, fn($m) => (int)$m['stock'] === 0));
$low_count     = count($low_stock);
$expired_count = count(array_filter($expiring, fn($m) => (int)$m['days_left'] <= 0));
$soon_count    = count(array_filter($expiring, fn($m) => (int)$m['days_left'] > 0));
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Alerts - PharmaCore</title>
    <link rel="shortcut icon" href="../assets/images/pharmacore_icon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/alerts.css">
</head>
<body>

    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="container">

            <div class="page-header">
                <h2>
                    <!-- <img src="../assets/images/pharmacore_icon.svg" alt="Logo" class="header-icon">  -->
                    Inventory Alerts
                </h2>
            </div>

            <!-- METRIC CARDS -->
            <div class="metrics-grid">
                <div class="metric-card danger">
                    <img src="../assets/images/alert_danger.svg" alt="Alert" class="metric-icon">
                    <div>
                        <span>Out of Stock</span>
                        <h3><?= $out_count ?></h3>
                    </div>
                </div>

                <div class="metric-card warning">
                    <img src="../assets/images/low_stock.svg" alt="Low Stock" class="metric-icon">
                    <div>
                        <span>Low Stock</span>
                        <h3><?= $low_count ?></h3>
                    </div>
                </div>

                <div class="metric-card purple">
                    <img src="../assets/images/expired.svg" alt="Expired" class="metric-icon">
                    <div>
                        <span>Expired Items</span>
                        <h3><?= $expired_count ?></h3>
                    </div>
                </div>

                <div class="metric-card info">
                    <img src="../assets/images/expiring.svg" alt="Expiring" class="metric-icon">
                    <div>
                        <span>Expiring Soon (30d)</span>
                        <h3><?= $soon_count ?></h3>
                    </div>
                </div>
            </div>

            <!-- TABLE 1: STOCK ALERTS -->
            <section class="alert-section">
                <h3>Stock Quantity Alerts</h3>
                <div class="table-card">
                    <?php if (empty($low_stock)): ?>
                        <p class="empty-msg">All medicines are sufficiently stocked.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Medicine</th>
                                    <th>Category</th>
                                    <th>Location</th>
                                    <th>Stock</th>
                                    <th>Min Stock</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($low_stock as $med): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($med['name']) ?></strong></td>
                                        <td><?= htmlspecialchars($med['category'] ?: 'N/A') ?></td>
                                        <td><?= htmlspecialchars($med['location'] ?: 'N/A') ?></td>
                                        <td><strong><?= (int)$med['stock'] ?> Units</strong></td>
                                        <td><?= (int)$med['min_stock'] ?> Units</td>
                                        <td>
                                            <span class="badge <?= (int)$med['stock'] === 0 ? 'b-danger' : 'b-warning' ?>">
                                                <?= (int)$med['stock'] === 0 ? 'Out of Stock' : 'Low Stock' ?>
                                            </span>
                                        </td>
                                        <td><a href="medicines.php" class="btn-action">Update Stock</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </section>

            <!-- TABLE 2: EXPIRATION ALERTS -->
            <section class="alert-section">
                <h3>Expiration Alerts</h3>
                <div class="table-card">
                    <?php if (empty($expiring)): ?>
                        <p class="empty-msg">No medicines are expired or expiring within 30 days.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Medicine</th>
                                    <th>Batch No.</th>
                                    <th>Location</th>
                                    <th>Expiry Date</th>
                                    <th>Days Left</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expiring as $med): ?>
                                    <?php $days = (int)$med['days_left']; ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($med['name']) ?></strong></td>
                                        <td><?= htmlspecialchars($med['batch_number'] ?: 'N/A') ?></td>
                                        <td><?= htmlspecialchars($med['location'] ?: 'N/A') ?></td>
                                        <td><?= htmlspecialchars($med['expiry_date']) ?></td>
                                        <td><?= $days < 0 ? abs($days) . ' days ago' : ($days === 0 ? 'Today' : $days . ' days') ?></td>
                                        <td>
                                            <span class="badge <?= $days <= 0 ? 'b-purple' : 'b-info' ?>">
                                                <?= $days <= 0 ? 'Expired' : 'Expiring Soon' ?>
                                            </span>
                                        </td>
                                        <td><a href="medicines.php" class="btn-action">Manage Item</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </section>

        </div>
    </main>

</body>
</html>