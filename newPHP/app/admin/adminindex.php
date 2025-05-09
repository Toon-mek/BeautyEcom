<?php
require_once __DIR__ . '/../_base.php';
requireLogin('staff');
$isManager = isManager($_SESSION['staff_id']);
$displayName = getDisplayName();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Beauty & Wellness</title>
    <link href="../css/admin.css" rel="stylesheet">
</head>
<body>
    <?php include 'adminSidebar.php'; ?>

    <!-- Main Content -->
    <main class="admin-main-content">
        <div class="admin-dashboard-header">
            <h1>Welcome to Admin Dashboard</h1>
            <p>Manage your store's operations and monitor performance</p>
            <div class="welcome-icon">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>

        <div class="admin-dashboard-cards">
            <div class="admin-dashboard-card members">
                <div class="admin-dashboard-icon">
                    <div class="icon icon-users"></div>
                </div>
                <div class="admin-dashboard-info">
                    <h3>Total Members</h3>
                    <p><?php echo getTotalMembers(); ?></p>
                </div>
            </div>

            <div class="admin-dashboard-card products">
                <div class="admin-dashboard-icon">
                    <div class="icon icon-products"></div>
                </div>
                <div class="admin-dashboard-info">
                    <h3>Total Products</h3>
                    <p><?php echo getTotalProducts(); ?></p>
                </div>
            </div>

            <div class="admin-dashboard-card staff">
                <div class="admin-dashboard-icon">
                    <div class="icon icon-staff"></div>
                </div>
                <div class="admin-dashboard-info">
                    <h3>Total Staff</h3>
                    <p><?php echo getTotalStaff(); ?></p>
                </div>
            </div>

            <div class="admin-dashboard-card orders">
                <div class="admin-dashboard-icon">
                    <div class="icon icon-orders"></div>
                </div>
                <div class="admin-dashboard-info">
                    <h3>Pending Orders</h3>
                    <p><?php echo count(getPendingOrders($pdo)); ?></p>
                </div>
            </div>

            <div class="admin-dashboard-card sales">
                <div class="admin-dashboard-icon">
                    <div class="icon icon-sales"></div>
                </div>
                <div class="admin-dashboard-info">
                    <h3>Total Sales</h3>
                    <p><?php echo number_format(getTotalSales($pdo), 2); ?></p>
                </div>
            </div>

            
        </div>
    </main>
</body>
</html>