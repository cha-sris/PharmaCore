<!-- sidebar.php -->
<?php
// Ensure session is started (if not already)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// If user is not logged in, redirect to login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../auth/login.php");
    exit;
}

// Get the current running file name (e.g. "alerts.php", "medicines.php")
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="../assets/images/pharmacore_icon.svg" alt="PharmaCore" class="sidebar-logo">
        <span class="sidebar-brand">PharmaCore</span>
        <!-- <button class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Close sidebar">✕</button> -->
    </div>

    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="../modules/dashboard.php" class="sidebar-link <?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>">
                    <img src="../assets/images/dashboard.svg" class="sidebar-icon" alt="dashboard icon">
                    <span class="sidebar-text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="../modules/medicines.php" class="sidebar-link <?php echo ($current_page === 'medicines.php') ? 'active' : ''; ?>">
                    <img src="../assets/images/medicine.svg" class="sidebar-icon" alt="medicine icon">
                    <span class="sidebar-text">Medicines</span>
                </a>
            </li>
            <li>
                <!-- <a href="../modules/patients.php" class="sidebar-link <?php echo ($current_page === 'patients.php') ? 'active' : ''; ?>">
                    <img src="../assets/images/category-2.svg" class="sidebar-icon" alt="category icon">
                    <span class="sidebar-text">Categories</span>
                </a>
            </li> -->
            <li>
                <a href="../modules/alerts.php" class="sidebar-link <?php echo ($current_page === 'alerts.php') ? 'active' : ''; ?>">
                    <img src="../assets/images/alert-triangle.svg" class="sidebar-icon" alt="alert icon">
                    <span class="sidebar-text">Alerts</span>
                </a>
            </li>
            <li>
                <a href="../modules/suppliers.php" class="sidebar-link <?php echo ($current_page === 'suppliers.php') ? 'active' : ''; ?>">
                    <img src="../assets/images/medicine-company.svg" class="sidebar-icon" alt="suppliers icon">
                    <span class="sidebar-text">Suppliers</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <a href="../auth/logout.php" class="sidebar-link logout-link">
            <img src="../assets/images/logout.svg" class="sidebar-icon" alt="logout icon">
            <span class="sidebar-text">Logout</span>
        </a>
    </div>
</aside>

<!-- Overlay for mobile (click to close) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>