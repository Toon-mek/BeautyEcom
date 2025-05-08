<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../_base.php';


requireLogin('staff');

// Get date range from request or default to current month
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Get total sales
$total_sales = getTotalSales($pdo);

// Get total completed orders in date range
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE OrderStatus = 'Completed' AND OrderDate BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$total_orders = $stmt->fetchColumn();

// Get sales by date range
$stmt = $pdo->prepare("
    SELECT DATE(OrderDate) as sale_date, 
           COUNT(*) as order_count,
           SUM(OrderTotalAmount) as daily_total
    FROM orders 
    WHERE OrderStatus = 'Completed'
    AND OrderDate BETWEEN ? AND ?
    GROUP BY DATE(OrderDate)
    ORDER BY sale_date DESC
");
$stmt->execute([$start_date, $end_date]);
$sales_by_date = $stmt->fetchAll();

// Get top selling products
$stmt = $pdo->prepare("
    SELECT p.ProductName, 
           SUM(oi.Quantity) as total_quantity,
           SUM(oi.Quantity * oi.OrderItemPrice) as total_revenue
    FROM orderitem oi
    JOIN product p ON oi.ProductID = p.ProductID
    JOIN orders o ON oi.OrderID = o.OrderID
    WHERE o.OrderStatus = 'Completed'
    AND o.OrderDate BETWEEN ? AND ?
    GROUP BY p.ProductID
    ORDER BY total_quantity DESC
    LIMIT 5
");
$stmt->execute([$start_date, $end_date]);
$top_products = $stmt->fetchAll();

// Get sales by category
$stmt = $pdo->prepare("
    SELECT c.CategoryName,
           COUNT(DISTINCT o.OrderID) as order_count,
           SUM(oi.Quantity * oi.OrderItemPrice) as total_revenue
    FROM orders o
    JOIN orderitem oi ON o.OrderID = oi.OrderID
    JOIN product p ON oi.ProductID = p.ProductID
    JOIN category c ON p.CategoryID = c.CategoryID
    WHERE o.OrderStatus = 'Completed'
    AND o.OrderDate BETWEEN ? AND ?
    GROUP BY c.CategoryID
    ORDER BY total_revenue DESC
    LIMIT 3
");
$stmt->execute([$start_date, $end_date]);
$sales_by_category = $stmt->fetchAll();

// Get payment method distribution
$stmt = $pdo->prepare("
    SELECT PaymentMethod,
           COUNT(*) as payment_count,
           SUM(AmountPaid) as total_amount
    FROM payment
    WHERE PaymentStatus = 'Paid'
    AND PaymentDate BETWEEN ? AND ?
    GROUP BY PaymentMethod
");
$stmt->execute([$start_date, $end_date]);
$payment_distribution = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report - Admin Panel</title>
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .sales-report * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        .sales-report {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f6fa;
        }
        .sales-center-wrap {
            display: flex;
            justify-content: flex-start;
            width: 100%;
        }
        .sales-content {
            flex: 1;
            padding: 2rem;
            margin-left: 250px; /* for sidebar */
            width: 100%;
            max-width: none;
            margin-right: 0;
        }
        .sales-header {
            margin-bottom: 2rem;
        }
        .sales-header h1 {
            color: #2c3e50;
            font-size: 2rem;
            font-weight: 600;
        }
        .sales-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 2rem;
            overflow-x: auto;
            padding-bottom: 1rem;
        }
        .sales-stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            min-width: 220px;
            flex: 1 1 220px;
            transition: transform 0.2s ease;
        }
        .sales-stat-card:hover {
            transform: translateY(-5px);
        }
        .sales-stat-card h3 {
            color: #7f8c8d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }
        .sales-stat-card p, .sales-stat-card .value {
            color: #2c3e50;
            font-size: 1.8rem;
            font-weight: 700;
        }
        .sales-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .sales-section-header {
            color: #2c3e50;
            font-size: 1.4rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0f0f0;
        }
        .sales-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .sales-table th,
        .sales-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        .sales-table th {
            background-color: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        .sales-table tr:hover {
            background-color: #f8f9fa;
        }
        .sales-no-data {
            text-align: center;
            padding: 1.5rem;
            color: #7f8c8d;
            font-style: italic;
        }
        @media (max-width: 1200px) {
            .sales-content {
                max-width: 100%;
                margin-left: 200px;
            }
        }
        @media (max-width: 900px) {
            .sales-content {
                margin-left: 0;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-flex-container">
        <?php include 'adminSidebar.php'; ?>
        <div class="sales-report">
            <div class="sales-center-wrap">
                <div class="sales-content">
                    <div class="sales-header">
                        <h1>Sales Report</h1>
                        <form class="sales-date-filter" method="GET" style="margin-top: 1rem; margin-bottom: 2rem; display: flex; gap: 1rem;">
                            <input type="date" name="start_date" value="<?php echo $start_date; ?>">
                            <input type="date" name="end_date" value="<?php echo $end_date; ?>">
                            <button type="submit">Apply Filter</button>
                        </form>
                    </div>
                    <div class="sales-stats">
                        <div class="sales-stat-card">
                            <h3>Total Sales</h3>
                            <div class="value">RM <?php echo number_format($total_sales, 2); ?></div>
                        </div>
                        <div class="sales-stat-card">
                            <h3>Total Orders</h3>
                            <div class="value"><?php echo $total_orders; ?></div>
                        </div>
                        <div class="sales-stat-card">
                            <h3>Average Order Value</h3>
                            <div class="value">RM <?php echo $total_orders > 0 ? number_format($total_sales / $total_orders, 2) : '0.00'; ?></div>
                        </div>
                    </div>
                    <div class="sales-section">
                        <h2 class="sales-section-header">Sales by Date</h2>
                        <?php if (!empty($sales_by_date)): ?>
                            <table class="sales-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Orders</th>
                                        <th>Total Sales</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sales_by_date as $sale): ?>
                                        <tr>
                                            <td><?php echo date('d M Y', strtotime($sale['sale_date'])); ?></td>
                                            <td><?php echo $sale['order_count']; ?></td>
                                            <td>RM <?php echo number_format($sale['daily_total'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="sales-no-data">No sales data available for the selected period</div>
                        <?php endif; ?>
                    </div>
                    <div class="sales-section">
                        <h2 class="sales-section-header">Top Selling Products</h2>
                        <?php if (!empty($top_products)): ?>
                            <table class="sales-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Units Sold</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_products as $product): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['ProductName']); ?></td>
                                            <td><?php echo $product['total_quantity']; ?></td>
                                            <td>RM <?php echo number_format($product['total_revenue'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="sales-no-data">No product sales data available for the selected period</div>
                        <?php endif; ?>
                    </div>
                    <div class="sales-section">
                        <h2 class="sales-section-header">Sales by Category</h2>
                        <?php if (!empty($sales_by_category)): ?>
                            <table class="sales-table">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Orders</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sales_by_category as $category): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($category['CategoryName']); ?></td>
                                            <td><?php echo $category['order_count']; ?></td>
                                            <td>RM <?php echo number_format($category['total_revenue'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="sales-no-data">No category sales data available for the selected period</div>
                        <?php endif; ?>
                    </div>
                    <div class="sales-section">
                        <h2 class="sales-section-header">Payment Method Distribution</h2>
                        <?php if (!empty($payment_distribution)): ?>
                            <table class="sales-table">
                                <thead>
                                    <tr>
                                        <th>Payment Method</th>
                                        <th>Transactions</th>
                                        <th>Total Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payment_distribution as $payment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($payment['PaymentMethod']); ?></td>
                                            <td><?php echo $payment['payment_count']; ?></td>
                                            <td>RM <?php echo number_format($payment['total_amount'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="sales-no-data">No payment data available for the selected period</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 