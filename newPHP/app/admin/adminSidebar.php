<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../_base.php';

requireLogin('staff');

// Determine if user is manager
$isManager = isManager($_SESSION['staff_id']);
?>


<div class="admin-sidebar">
    <div class="admin-sidebar-header">
        <h3>Admin Panel</h3>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['staff_name']); ?></p>
    </div>
    <ul class="admin-sidebar-menu">
        <li>
            <a href="adminindex.php"><span>Dashboard</span></a>
        </li>
        <li>
            <a href="memberList.php"><span>Member Management</span></a>
        </li>
        <li>
            <a href="productList.php"><span>Product Management</span></a>
        </li>
        <li>
            <a href="orderList.php"><span>Order Management</span></a>
        </li>
        <?php if ($isManager): ?>
        <li class="admin-manager-only">
            <a href="salesReport.php"><span>Sales Reports</span></a>
        </li>
        <li class="admin-manager-only">
            <a href="inventoryReport.php"><span>Inventory Reports</span></a>
        </li>
        <li class="admin-manager-only">
            <a href="staffList.php"><span>Staff Management</span></a>
        </li>
        <?php endif; ?>
        <li><a href="adminProfile.php">Profile</a></li>
    </ul>
</div>