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
    <style>
        .admin-main-content {
            padding: clamp(15px, 3vw, 30px);
            flex: 1;
            min-height: 100vh;
            background: #f8f9fa;
            width: 100%;
        }

        .admin-dashboard-header {
            background: linear-gradient(135deg, #f1c40f 0%, #f39c12 100%);
            padding: clamp(20px, 4vw, 35px) clamp(25px, 5vw, 40px);
            border-radius: clamp(10px, 2vw, 15px);
            margin-bottom: clamp(20px, 4vw, 35px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
            width: calc(100% - clamp(20px, 4vw, 40px));
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        .admin-dashboard-header h1 {
            margin: 0;
            font-size: clamp(22px, 3.5vw, 32px);
            font-weight: 600;
            position: relative;
            color: #2c3e50;
            text-shadow: none;
            line-height: 1.2;
        }

        .admin-dashboard-header p {
            margin: clamp(8px, 1.5vw, 12px) 0 0;
            font-size: clamp(13px, 1.8vw, 16px);
            position: relative;
            color: #7f8c8d;
            text-shadow: none;
            line-height: 1.4;
        }

        .admin-dashboard-header .welcome-icon {
            position: absolute;
            right: clamp(20px, 4vw, 35px);
            top: 50%;
            transform: translateY(-50%);
            width: clamp(45px, 8vw, 60px);
            height: clamp(45px, 8vw, 60px);
            background: rgba(255,255,255,0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: clamp(20px, 3vw, 24px);
            opacity: 0.8;
            color: #2c3e50;
        }

        .admin-dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: clamp(15px, 2vw, 25px);
            margin: 0 auto;
            max-width: 1400px;
        }

        .admin-dashboard-card {
            background: white;
            border-radius: 15px;
            padding: clamp(20px, 3vw, 25px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            align-items: center;
            gap: clamp(15px, 2vw, 20px);
            position: relative;
            overflow: hidden;
            min-height: 120px;
        }

        .admin-dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .admin-dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: currentColor;
            opacity: 0.7;
        }

        .admin-dashboard-icon {
            width: clamp(50px, 8vw, 60px);
            height: clamp(50px, 8vw, 60px);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }

        .admin-dashboard-info {
            flex: 1;
        }

        .admin-dashboard-info h3 {
            margin: 0 0 8px;
            font-size: clamp(14px, 2vw, 16px);
            font-weight: 600;
            color: #2c3e50;
        }

        .admin-dashboard-info p {
            margin: 0;
            font-size: clamp(20px, 3vw, 24px);
            font-weight: 700;
            color: #2c3e50;
        }

        .admin-dashboard-card.members {
            color: #3498db;
        }

        .admin-dashboard-card.products {
            color: #e67e22;
        }

        .admin-dashboard-card.orders {
            color: #e74c3c;
        }

        .admin-dashboard-card.sales {
            color: #27ae60;
        }

        /* CSS Icons */
        .icon {
            position: relative;
            width: clamp(24px, 4vw, 32px);
            height: clamp(24px, 4vw, 32px);
        }

        .icon-users {
            border: 3px solid currentColor;
            border-radius: 50%;
            margin-left: 12px;
        }

        .icon-users::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border: 3px solid currentColor;
            border-radius: 50%;
            left: -12px;
        }

        .icon-products {
            border: 3px solid currentColor;
            border-radius: 4px;
        }

        .icon-products::before {
            content: '';
            position: absolute;
            width: 40%;
            height: 25%;
            border: 3px solid currentColor;
            border-radius: 2px;
            top: -8px;
            left: 50%;
            transform: translateX(-50%);
        }

        .icon-orders {
            border: 3px solid currentColor;
            border-radius: 4px;
        }

        .icon-orders::after {
            content: '';
            position: absolute;
            width: 50%;
            height: 3px;
            background: currentColor;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .icon-sales {
            position: relative;
        }

        .icon-sales::before {
            content: 'RM';
            font-size: clamp(18px, 3vw, 24px);
            font-weight: bold;
        }

        @media (max-width: 768px) {
            .admin-dashboard-cards {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }

            .admin-dashboard-card {
                min-height: 100px;
            }

            .admin-dashboard-header {
                padding: clamp(15px, 3vw, 25px);
                margin-bottom: 20px;
            }

            .admin-dashboard-header .welcome-icon {
                width: clamp(35px, 6vw, 45px);
                height: clamp(35px, 6vw, 45px);
                right: clamp(15px, 3vw, 25px);
            }
        }

        @media (max-width: 480px) {
            .admin-dashboard-cards {
                grid-template-columns: 1fr;
            }

            .admin-main-content {
                padding: 12px;
            }

            .admin-dashboard-header {
                padding: 15px;
                margin-bottom: 15px;
            }

            .admin-dashboard-header h1 {
                font-size: clamp(20px, 5vw, 24px);
            }

            .admin-dashboard-header p {
                font-size: clamp(12px, 3.5vw, 14px);
                margin-top: 6px;
            }

            .admin-dashboard-header .welcome-icon {
                width: 35px;
                height: 35px;
                font-size: 18px;
                right: 15px;
            }
        }
    </style>
</head>
<body>
<div class="admin-flex-container">
    <?php include 'adminSidebar.php'; ?>
    <div class="admin-main-content">
        <div class="admin-dashboard-header">
            <h1>Welcome, <?php echo htmlspecialchars($displayName); ?>!</h1>
            <p>Here's your dashboard overview</p>
            <div class="welcome-icon">👋</div>
        </div>

        <div class="admin-dashboard-cards">
            <div class="admin-dashboard-card members">
                <div class="admin-dashboard-icon">
                    <span class="icon icon-users"></span>
                </div>
                <div class="admin-dashboard-info">
                    <h3>Total Members</h3>
                    <p><?php echo getTotalMembers(); ?></p>
                </div>
            </div>

            <div class="admin-dashboard-card products">
                <div class="admin-dashboard-icon">
                    <span class="icon icon-products"></span>
                </div>
                <div class="admin-dashboard-info">
                    <h3>Total Products</h3>
                    <p><?php echo getTotalProducts(); ?></p>
                </div>
            </div>

            <div class="admin-dashboard-card staff">
                <div class="admin-dashboard-icon">
                    <span class="icon icon-users"></span>
                </div>
                <div class="admin-dashboard-info">
                    <h3>Total Staff</h3>
                    <p><?php echo getTotalStaff(); ?></p>
                </div>
            </div>

            <div class="admin-dashboard-card orders">
                <div class="admin-dashboard-icon">
                    <span class="icon icon-orders"></span>
                </div>
                <div class="admin-dashboard-info">
                    <h3>Pending Orders</h3>
                    <?php
                    $pendingOrders = getPendingOrders($pdo);
                    if (count($pendingOrders) > 0): ?>
                    <p><?php echo count($pendingOrders); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($isManager): ?>
            <div class="admin-dashboard-card sales">
                <div class="admin-dashboard-icon">
                    <span class="icon icon-sales"></span>
                </div>
                <div class="admin-dashboard-info">
                    <h3>Total Sales</h3>
                    <p>RM <?php echo number_format(getTotalSales($pdo), 2); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>