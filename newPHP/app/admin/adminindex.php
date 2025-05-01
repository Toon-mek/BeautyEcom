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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="../css/admin.css" rel="stylesheet">
</head>
<body>
<div class="admin-flex-container">
    <?php include 'adminSidebar.php'; ?>
    <div class="admin-main-content">
        <div class="admin-dashboard-header">
            <h1>Welcome, <?php echo htmlspecialchars($displayName); ?>!</h1>
            <p>Here is an overview of your admin dashboard.</p>
        </div>
        <div class="admin-dashboard-cards">
            <div class="admin-dashboard-card">
                <div class="admin-dashboard-icon" style="background:#eaf6fb;color:#3498db;">
                    <i class="fa fa-user-circle" aria-hidden="true"></i>
                </div>
                <div class="admin-dashboard-info">
                    <h3>Total Members</h3>
                    <p><?php echo getTotalMembers(); ?></p>
                </div>
            </div>
            <div class="admin-dashboard-card">
                <div class="admin-dashboard-icon" style="background:#fdf6e3;color:#e67e22;">🛒</div>
                <div class="admin-dashboard-info">
                    <h3>Total Products</h3>
                    <p><?php echo getTotalProducts($pdo); ?></p>
                </div>
            </div>
            <div class="admin-dashboard-card">
                <div class="admin-dashboard-icon" style="background:#fbeee6;color:#e74c3c;">📦</div>
                <div class="admin-dashboard-info">
                    <h3>Pending Orders</h3>
                    <p><?php echo getPendingOrders(); ?></p>
                </div>
            </div>
            <?php if ($isManager): ?>
            <div class="admin-dashboard-card">
                <div class="admin-dashboard-icon" style="background:#eafbe6;color:#27ae60;">💰</div>
                <div class="admin-dashboard-info">
                    <h3>Total Sales</h3>
                    <p>RM <?php echo number_format(getTotalSales(), 2); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>