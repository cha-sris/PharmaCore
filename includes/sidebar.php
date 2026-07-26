<!-- sidebar.php -->
<?php
// Ensure session is started (if not already)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// If user is not logged in, redirect to login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="../assets/images/pharmacore_icon.svg" alt="PharmaCore" class="sidebar-logo">
        <span class="sidebar-brand">PharmaCore</span>
        <button class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Close sidebar">✕</button>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="dashboard.php" class="sidebar-link active">
                    <span class="sidebar-icon">📊</span>
                    <span class="sidebar-text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="medicines.php" class="sidebar-link">
                    <span class="sidebar-icon">💊</span>
                    <span class="sidebar-text">Medicines</span>
                </a>
            </li>
            <li>
                <a href="patients.php" class="sidebar-link">
                    <span class="sidebar-icon">👤</span>
                    <span class="sidebar-text">Patients</span>
                </a>
            </li>
            <li>
                <a href="orders.php" class="sidebar-link">
                    <span class="sidebar-icon">📦</span>
                    <span class="sidebar-text">Orders</span>
                </a>
            </li>
            <li>
                <a href="suppliers.php" class="sidebar-link">
                    <span class="sidebar-icon">🏢</span>
                    <span class="sidebar-text">Suppliers</span>
                </a>
            </li>
            <li>
                <a href="reports.php" class="sidebar-link">
                    <span class="sidebar-icon">📈</span>
                    <span class="sidebar-text">Reports</span>
                </a>
            </li>
            <li>
                <a href="settings.php" class="sidebar-link">
                    <span class="sidebar-icon">⚙️</span>
                    <span class="sidebar-text">Settings</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <a href="../auth/logout.php" class="sidebar-link logout-link">
            <span class="sidebar-icon">🚪</span>
            <span class="sidebar-text">Logout</span>
        </a>
    </div>
</aside>

<!-- Overlay for mobile (click to close) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>