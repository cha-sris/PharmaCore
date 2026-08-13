<?php
// Initialize session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../auth/login.php");
    exit;
}

// Include database config (if needed for real stats)
require_once "../config/config.php";

// Dummy data – replace with actual queries
$medicineCount = 142;
$patientCount = 89;
$orderCount = 37;
$lowStockCount = 5;
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PharmaCore</title>
    <link rel="shortcut icon" href="../assets/images/pharmacore_icon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>

    <!-- Include Sidebar -->
    <?php include '../includes/sidebar.php'; ?>

    <!-- Mobile menu toggle -->
    <button id="menuToggle" aria-label="Toggle sidebar">
        <img src="../assets/images/hamburger-menu.svg" id="hamburgerIcon" alt="hamburger icon">
    </button>

    <!-- Main content -->
    <main class="main-content">
        <!-- Top header with search -->
        <div class="top-header">
            <div class="header-row">
                <h1>Dashboard</h1>
                <!-- Search bar -->
                <div class="search-wrapper">
                    <form class="search-form" action="search.php" method="GET" role="search">
                        <div class="search-input-group">
                            <!-- <span class="search-icon">
                                </span> -->
                                <!-- <img src="../assets/images/search.svg" class="svg-icons" alt="search icon"> -->
                            <input type="text" name="query" class="search-input"
                                   placeholder="Search medicines..."
                                   aria-label="Search" autocomplete="off">
                            <button type="button" class="search-clear" aria-label="Clear search" style="display:none;">✕</button>
                        </div>
                        <button type="submit" class="search-submit">Search</button>
                    </form>
                </div>
                <!-- User info -->
                <div class="user-info">
                    <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-icon">💊</span>
                <div class="stat-label">Total Medicines</div>
                <div class="stat-value"><?php echo $medicineCount; ?></div>
            </div>
            <div class="stat-card">
                <span class="stat-icon">👤</span>
                <div class="stat-label">Patients</div>
                <div class="stat-value"><?php echo $patientCount; ?></div>
            </div>
            <div class="stat-card">
                <span class="stat-icon">📦</span>
                <div class="stat-label">Orders</div>
                <div class="stat-value"><?php echo $orderCount; ?></div>
            </div>
            <div class="stat-card">
                <span class="stat-icon">⚠️</span>
                <div class="stat-label">Low Stock Items</div>
                <div class="stat-value"><?php echo $lowStockCount; ?></div>
            </div>
        </div>

        <!-- Detailed panels -->
        <div class="dashboard-grid">
            <div class="panel">
                <h3>🕒 Recent Orders</h3>
                <ul>
                    <li><span>#ORD-1024 - Paracetamol</span> <span class="badge">Processing</span></li>
                    <li><span>#ORD-1023 - Amoxicillin</span> <span class="badge">Shipped</span></li>
                    <li><span>#ORD-1022 - Ibuprofen</span> <span class="badge">Delivered</span></li>
                    <li><span>#ORD-1021 - Aspirin</span> <span class="badge">Pending</span></li>
                </ul>
            </div>
            <div class="panel">
                <h3>⚡ Quick Actions</h3>
                <ul>
                    <li><a href="#" style="color: var(--text-primary); text-decoration: none;">➕ Add Medicine</a></li>
                    <li><a href="#" style="color: var(--text-primary); text-decoration: none;">👤 Register Patient</a></li>
                    <li><a href="#" style="color: var(--text-primary); text-decoration: none;">📦 Create Order</a></li>
                    <li><a href="#" style="color: var(--text-primary); text-decoration: none;">📊 Generate Report</a></li>
                </ul>
                <br>
                <h3>🔔 Alerts</h3>
                <ul>
                    <li><span>⚠️ 5 items below reorder level</span></li>
                    <li><span>📅 3 prescriptions expiring soon</span></li>
                </ul>
            </div>
        </div>

        <footer>
            &copy; <?php echo date('Y'); ?> PharmaCore. All rights reserved.
        </footer>
    </main>

    <!-- JavaScript for sidebar and search -->
    <script src="../assets/js/sidebar.js"></script>
    <script>
        // Sidebar toggle (mobile)
        document.getElementById('menuToggle').addEventListener('click', function() {
            window.dispatchEvent(new Event('openSidebar'));
        });

        // Close sidebar on link click (mobile)
        document.querySelectorAll('.sidebar-link').forEach(link => {
            link.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) {
                    const sidebar = document.getElementById('sidebar');
                    const overlay = document.getElementById('sidebarOverlay');
                    if (sidebar.classList.contains('open')) {
                        sidebar.classList.remove('open');
                        overlay.classList.remove('active');
                    }
                }
            });
        });

        // Search bar clear button
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('.search-input');
            const clearBtn = document.querySelector('.search-clear');

            if (searchInput && clearBtn) {
                searchInput.addEventListener('input', function() {
                    if (this.value.length > 0) {
                        clearBtn.style.display = 'block';
                    } else {
                        clearBtn.style.display = 'none';
                    }
                });

                clearBtn.addEventListener('click', function() {
                    searchInput.value = '';
                    searchInput.focus();
                    this.style.display = 'none';
                });
            }
        });
    </script>
</body>
</html>