<?php
// Initialize session and verify authentication
session_start();
if (!isset($_SESSION['id']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/config.php';

// Initialize variables
$totalMedicines = 0;
$totalSuppliers = 0;
$outOfStockCount = 0;
$lowStockCount = 0;
$expiredCount = 0;
$expiringSoonCount = 0;
$recentMedicines = [];
$recentSuppliers = [];
$recentSales = [];
$allMedicines = [];
$chartLabels = "[]";
$chartValues = "[]";
$saleMessage = "";
$saleError = "";

// Get selected chart filter (Default: this_month)
$chartFilter = $_GET['chart_filter'] ?? 'this_month';

// Handle flash messages from redirect
if (isset($_SESSION['sale_success'])) {
    $saleMessage = $_SESSION['sale_success'];
    unset($_SESSION['sale_success']);
}
if (isset($_SESSION['sale_error'])) {
    $saleError = $_SESSION['sale_error'];
    unset($_SESSION['sale_error']);
}

// Handle Quick Sale Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sell_medicine'])) {
    $medicineId = filter_input(INPUT_POST, 'medicine_id', FILTER_VALIDATE_INT);
    $quantitySold = filter_input(INPUT_POST, 'quantity_sold', FILTER_VALIDATE_INT);

    if ($medicineId && $quantitySold && $quantitySold > 0) {
        try {
            $pdo->beginTransaction();

            $stmtCheck = $pdo->prepare("SELECT name, stock FROM medicines WHERE id = ?");
            $stmtCheck->execute([$medicineId]);
            $medData = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($medData) {
                $currentStock = (int)$medData['stock'];
                if ($quantitySold <= $currentStock) {
                    $newStock = $currentStock - $quantitySold;

                    // Update stock
                    $stmtUpdate = $pdo->prepare("UPDATE medicines SET stock = ? WHERE id = ?");
                    $stmtUpdate->execute([$newStock, $medicineId]);

                    // Insert record into sales table
                    $stmtInsertSale = $pdo->prepare("INSERT INTO sales (medicine_id, quantity_sold) VALUES (?, ?)");
                    $stmtInsertSale->execute([$medicineId, $quantitySold]);

                    $pdo->commit();
                    $_SESSION['sale_success'] = "Successfully sold " . $quantitySold . " unit(s) of " . htmlspecialchars($medData['name']) . "!";
                } else {
                    $pdo->rollBack();
                    $_SESSION['sale_error'] = "Error: Not enough stock available. Current stock is " . $currentStock . ".";
                }
            } else {
                $pdo->rollBack();
                $_SESSION['sale_error'] = "Selected medicine not found.";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['sale_error'] = "Transaction failed: " . $e->getMessage();
        }
    } else {
        $_SESSION['sale_error'] = "Please select a valid medicine and enter a positive quantity.";
    }

    // Redirect to prevent duplicate form submission on refresh (F5)
    header("Location: dashboard.php?chart_filter=" . urlencode($chartFilter));
    exit();
}

// Fetch summary metrics and dropdown items safely
try {
    $totalMedicines = $pdo->query("SELECT COUNT(*) AS total FROM medicines")->fetch()['total'] ?? 0;
    $totalSuppliers = $pdo->query("SELECT COUNT(*) AS total FROM suppliers")->fetch()['total'] ?? 0;

    $outOfStockCount = $pdo->query("SELECT COUNT(*) AS total FROM medicines WHERE stock = 0")->fetch()['total'] ?? 0;
    $lowStockCount = $pdo->query("SELECT COUNT(*) AS total FROM medicines WHERE stock > 0 AND stock <= min_stock")->fetch()['total'] ?? 0;
    $expiredCount = $pdo->query("SELECT COUNT(*) AS total FROM medicines WHERE expiry_date < CURRENT_DATE")->fetch()['total'] ?? 0;
    $expiringSoonCount = $pdo->query("SELECT COUNT(*) AS total FROM medicines WHERE expiry_date BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY)")->fetch()['total'] ?? 0;

    $recentMedicines = $pdo->query("SELECT name, batch_number, stock FROM medicines ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    $recentSuppliers = $pdo->query("SELECT name, contact_person, phone, status FROM suppliers ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch Recent Sales Table Data
    $stmtRecentSales = $pdo->query("SELECT s.quantity_sold, s.sold_at, m.name, m.batch_number FROM sales s JOIN medicines m ON s.medicine_id = m.id ORDER BY s.sold_at DESC LIMIT 5");
    $recentSales = $stmtRecentSales->fetchAll(PDO::FETCH_ASSOC);

    // Filter SQL query based on timeframe selection
    $whereClause = "WHERE s.sold_at >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')"; // Default: This Month
    if ($chartFilter === 'last_month') {
        $whereClause = "WHERE s.sold_at >= DATE_FORMAT(CURRENT_DATE - INTERVAL 1 MONTH, '%Y-%m-01') AND s.sold_at < DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')";
    } elseif ($chartFilter === 'this_year') {
        $whereClause = "WHERE s.sold_at >= DATE_FORMAT(CURRENT_DATE, '%Y-01-01')";
    }

    // Fetch Aggregated Sold Medicines ordered chronologically by first sale date (oldest to newest)
    $chartQuery = "SELECT m.name, SUM(s.quantity_sold) as total_quantity, MIN(s.sold_at) as first_sale 
                   FROM sales s 
                   JOIN medicines m ON s.medicine_id = m.id 
                   {$whereClause}
                   GROUP BY s.medicine_id 
                   ORDER BY first_sale ASC 
                   LIMIT 7";
    $stmtChartData = $pdo->query($chartQuery);
    $chartData = $stmtChartData->fetchAll(PDO::FETCH_ASSOC);

    $chartLabels = json_encode(array_column($chartData, 'name'));
    $chartValues = json_encode(array_column($chartData, 'total_quantity'));

    $allMedicines = $pdo->query("SELECT id, name, stock FROM medicines WHERE stock > 0 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Silently handle errors
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PharmaCore</title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <!-- Chart.js CDN for visual graph -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <!-- Include Sidebar Navigation -->
    <?php include '../includes/sidebar.php'; ?>

    <!-- Main Layout Container -->
    <main class="main-content">
        <div class="container">
            
            <!-- Page Header -->
            <header class="page-header">
                <h2>
                    <img src="../assets/images/dashboard.svg" alt="Dashboard Icon" class="header-icon">
                    System Dashboard
                </h2>
                <div class="header-actions">
                    <span class="user-welcome">Welcome back, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></strong></span>
                </div>
            </header>

            <!-- Quick Sell Card -->
            <div class="sell-card">
                <h3>Quick Dispense / Sell Medicine</h3>
                <?php if (!empty($saleMessage)): ?>
                    <div class="alert-box alert-success"><?php echo $saleMessage; ?></div>
                <?php endif; ?>
                <?php if (!empty($saleError)): ?>
                    <div class="alert-box alert-error"><?php echo $saleError; ?></div>
                <?php endif; ?>
                <form action="dashboard.php?chart_filter=<?php echo urlencode($chartFilter); ?>" method="POST" class="sell-form">
                    <input type="hidden" name="sell_medicine" value="1">
                    <div class="form-group">
                        <label for="medicine_id">SELECT MEDICINE</label>
                        <select name="medicine_id" id="medicine_id" required>
                            <option value="">-- Choose Medicine --</option>
                            <?php foreach ($allMedicines as $m): ?>
                                <option value="<?php echo $m['id']; ?>">
                                    <?php echo htmlspecialchars($m['name']); ?> (Stock: <?php echo $m['stock']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group form-group-small">
                        <label for="quantity_sold">QUANTITY</label>
                        <input type="number" name="quantity_sold" id="quantity_sold" min="1" value="1" required>
                    </div>
                    <button type="submit" class="btn-sell">Complete Sale</button>
                </form>
            </div>

            <!-- Metrics Overview Grid -->
            <section class="stats-grid">
                
                <div class="stat-card">
                    <div class="stat-icon icon-blue">
                        <img src="../assets/images/medicine.svg" alt="Medicines Icon" class="svg-icon">
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">TOTAL MEDICINES</span>
                        <span class="stat-value"><?php echo number_format($totalMedicines); ?></span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon icon-green">
                        <img src="../assets/images/medicine-company.svg" alt="Suppliers Icon" class="svg-icon">
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">TOTAL SUPPLIERS</span>
                        <span class="stat-value"><?php echo number_format($totalSuppliers); ?></span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon icon-red">
                        <img src="../assets/images/bell.svg" alt="Out of Stock Icon" class="svg-icon">
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">OUT OF STOCK</span>
                        <span class="stat-value"><?php echo number_format($outOfStockCount); ?></span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon icon-yellow">
                        <img src="../assets/images/bell.svg" alt="Low Stock Icon" class="svg-icon">
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">LOW STOCK</span>
                        <span class="stat-value"><?php echo number_format($lowStockCount); ?></span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon icon-red">
                        <img src="../assets/images/bell.svg" alt="Expired Items Icon" class="svg-icon">
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">EXPIRED ITEMS</span>
                        <span class="stat-value"><?php echo number_format($expiredCount); ?></span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon icon-yellow">
                        <img src="../assets/images/bell.svg" alt="Expiring Soon Icon" class="svg-icon">
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">EXPIRING SOON (30D)</span>
                        <span class="stat-value"><?php echo number_format($expiringSoonCount); ?></span>
                    </div>
                </div>

            </section>

            <!-- Dashboard Tables Grid -->
            <div class="dashboard-tables-grid">
                
                <!-- Recently Sold Medicines Line Graph Visualizer -->
                <div class="table-card table-card-full">
                    <div class="table-header">
                        <h3>
                            <img src="../assets/images/recent-book.svg" alt="Sales Chart Icon" class="section-icon">
                            Recently Sold Medicines Trend (Oldest to Newest)
                        </h3>
                        <!-- Timeframe Filter Dropdown -->
                        <form method="GET" action="dashboard.php" class="header-form">
                            <select name="chart_filter" onchange="this.form.submit()" class="chart-filter-select">
                                <option value="this_month" <?php echo $chartFilter === 'this_month' ? 'selected' : ''; ?>>This Month</option>
                                <option value="last_month" <?php echo $chartFilter === 'last_month' ? 'selected' : ''; ?>>Last Month</option>
                                <option value="this_year" <?php echo $chartFilter === 'this_year' ? 'selected' : ''; ?>>This Year</option>
                            </select>
                        </form>
                    </div>
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>

                <!-- Recent Inventory Column -->
                <div class="table-card">
                    <div class="table-header">
                        <h3>
                            <img src="../assets/images/recent-book.svg" alt="Inventory Icon" class="section-icon">
                            Recently Added Medicines
                        </h3>
                        <a href="medicines.php" class="btn-link">View All</a>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>NAME</th>
                                <th>BATCH</th>
                                <th>STOCK</th>
                                <th>STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentMedicines)): ?>
                                <?php foreach ($recentMedicines as $med): ?>
                                    <tr>
                                        <td class="font-bold"><?php echo htmlspecialchars($med['name']); ?></td>
                                        <td><?php echo htmlspecialchars($med['batch_number'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($med['stock'] ?? 0); ?></td>
                                        <td>
                                            <?php if (($med['stock'] ?? 0) <= 0): ?>
                                                <span class="badge badge-inactive">Out of Stock</span>
                                            <?php elseif (($med['stock'] ?? 0) <= 10): ?>
                                                <span class="badge badge-inactive">Low</span>
                                            <?php else: ?>
                                                <span class="badge badge-active">In Stock</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="empty-cell">No recent medicines found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Recently Sold Medicines Column -->
                <div class="table-card">
                    <div class="table-header">
                        <h3>
                            <img src="../assets/images/recent-book.svg" alt="Sales Icon" class="section-icon">
                            Recently Sold Medicines Log
                        </h3>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>NAME</th>
                                <th>BATCH</th>
                                <th>QTY SOLD</th>
                                <th>DATE / TIME</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentSales)): ?>
                                <?php foreach ($recentSales as $sale): ?>
                                    <tr>
                                        <td class="font-bold"><?php echo htmlspecialchars($sale['name']); ?></td>
                                        <td><?php echo htmlspecialchars($sale['batch_number'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($sale['quantity_sold']); ?> Units</td>
                                        <td><?php echo htmlspecialchars(date('M d, Y H:i', strtotime($sale['sold_at']))); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="empty-cell">No recent sales recorded.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Recent Suppliers Column -->
                <div class="table-card table-card-full">
                    <div class="table-header">
                        <h3>
                            <img src="../assets/images/suppliers.svg" alt="Suppliers Icon" class="section-icon">
                            Suppliers
                        </h3>
                        <a href="suppliers.php" class="btn-link">View All</a>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>COMPANY</th>
                                <th>CONTACT</th>
                                <th>PHONE</th>
                                <th>STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentSuppliers)): ?>
                                <?php foreach ($recentSuppliers as $sup): ?>
                                    <tr>
                                        <td class="font-bold"><?php echo htmlspecialchars($sup['name']); ?></td>
                                        <td><?php echo htmlspecialchars($sup['contact_person'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($sup['phone'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge <?php echo ($sup['status'] ?? 'Active') === 'Active' ? 'badge-active' : 'badge-inactive'; ?>">
                                                <?php echo htmlspecialchars($sup['status'] ?? 'Active'); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="empty-cell">No suppliers found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>

        </div>
    </main>

    <!-- Script to Render Top Sales Line Graph -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('salesChart').getContext('2d');
    const labels = <?php echo $chartLabels; ?>;
    const values = <?php echo $chartValues; ?>;

    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(59, 130, 246, 0.35)');
    gradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels.length ? labels : ['No Sales Recorded'],
            datasets: [{
                label: 'Total Units Sold',
                data: values.length ? values : [0],
                borderColor: '#3b82f6',
                backgroundColor: gradient,
                borderWidth: 2.5,
                fill: true,
                tension: 0.35,
                pointBackgroundColor: '#3b82f6',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { 
                        precision: 0, 
                        color: '#94a3b8',
                        font: {
                            size: 16 // Change Y-axis font size here
                        }
                    },
                    grid: { color: 'rgba(255, 255, 255, 0.05)' }
                },
                x: {
                    ticks: { 
                        color: '#94a3b8',
                        font: {
                            size: 16 // Change X-axis (medicine names) font size here
                        }
                    },
                    grid: { display: false }
                }
            },
            plugins: {
                legend: {
                    labels: { 
                        color: '#f8fafc',
                        font: {
                            size: 16 // Change legend ("Total Units Sold") font size here
                        }
                    }
                }
            }
        }
    });
});
</script>
</body>
</html>