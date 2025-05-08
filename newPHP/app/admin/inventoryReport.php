<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../_base.php';

// Ensure staff is logged in
requireLogin('staff');

// Get inventory statistics
function getInventoryStats($pdo) {
    $stats = [];
    
    // Total products
    $stmt = $pdo->query("SELECT COUNT(*) FROM product");
    $stats['total_products'] = $stmt->fetchColumn();
    
    // Total stock value
    $stmt = $pdo->query("SELECT SUM(Price * Quantity) FROM product");
    $stats['total_value'] = $stmt->fetchColumn();
    
    // Low stock items (less than 10)
    $stmt = $pdo->query("SELECT COUNT(*) FROM product WHERE Quantity < 10");
    $stats['low_stock_count'] = $stmt->fetchColumn();
    
    // Out of stock items
    $stmt = $pdo->query("SELECT COUNT(*) FROM product WHERE Quantity = 0");
    $stats['out_of_stock_count'] = $stmt->fetchColumn();
    
    return $stats;
}

// Get low stock products
function getLowStockProducts($pdo) {
    $stmt = $pdo->prepare("
        SELECT p.*, c.CategoryName 
        FROM product p 
        LEFT JOIN category c ON p.CategoryID = c.CategoryID 
        WHERE p.Quantity < 10 
        ORDER BY p.Quantity ASC
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Get stock value by category
function getStockValueByCategory($pdo) {
    $stmt = $pdo->prepare("
        SELECT c.CategoryName, 
               COUNT(p.ProductID) as ProductCount,
               SUM(p.Quantity) as TotalQuantity,
               SUM(p.Price * p.Quantity) as TotalValue
        FROM category c
        LEFT JOIN product p ON c.CategoryID = p.CategoryID
        GROUP BY c.CategoryID
        ORDER BY TotalValue DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

$stats = getInventoryStats($pdo);
$lowStockProducts = getLowStockProducts($pdo);
$categoryStats = getStockValueByCategory($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Report - Beauty & Wellness</title>
    <link href="../css/admin.css" rel="stylesheet">
    <style>
        /* Reset and Base Styles */
        .inventory-report * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .inventory-report {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f6fa;
        }

        .inventory-report .inventory-content {
            flex: 1;
            padding: 2rem;
            margin-left: 250px; /* for sidebar */
            max-width: 1200px;   /* limit width */
            margin-right: auto;
            margin-left: 250px;  /* for sidebar */
        }

        /* Header */
        .inventory-header {
            margin-bottom: 2rem;
        }

        .inventory-header h1 {
            color: #2c3e50;
            font-size: 2rem;
            font-weight: 600;
        }

        /* Stats Cards */
        .inventory-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 2rem;
            overflow-x: auto;
            padding-bottom: 1rem;
        }

        .inventory-stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            min-width: 220px;
            flex: 1 1 220px;
            transition: transform 0.2s ease;
        }

        .inventory-stat-card:hover {
            transform: translateY(-5px);
        }

        .inventory-stat-card h3 {
            color: #7f8c8d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .inventory-stat-card p {
            color: #2c3e50;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .inventory-stat-card.low-stock p {
            color: #e74c3c;
        }

        .inventory-stat-card.out-of-stock p {
            color: #c0392b;
        }

        /* Section Styles */
        .inventory-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .inventory-section-header {
            color: #2c3e50;
            font-size: 1.4rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0f0f0;
        }

        /* Table Styles */
        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .inventory-table th,
        .inventory-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }

        .inventory-table th {
            background-color: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .inventory-table tr:hover {
            background-color: #f8f9fa;
        }

        /* Category Grid */
        .inventory-category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .inventory-category-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            transition: transform 0.2s ease;
        }

        .inventory-category-card:hover {
            transform: translateY(-3px);
        }

        .inventory-category-card h4 {
            color: #2c3e50;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e0e0e0;
        }

        .inventory-category-stat {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            color: #666;
        }

        .inventory-category-stat .value {
            font-weight: 600;
            color: #3498db;
        }

        /* Scrollbar Styling */
        .inventory-stats::-webkit-scrollbar {
            height: 6px;
        }

        .inventory-stats::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .inventory-stats::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .inventory-stats::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .inventory-report .inventory-content {
                max-width: 100%;
                margin-left: 200px;
            }
        }

        @media (max-width: 900px) {
            .inventory-report .inventory-content {
                margin-left: 0;
                padding: 1rem;
            }
        }

        .inventory-center-wrap {
            display: flex;
            justify-content: center;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="admin-flex-container">
        <?php include 'adminSidebar.php'; ?>
        <div class="inventory-report">
            <div class="inventory-center-wrap">
                <div class="inventory-content">
                    <div class="inventory-header">
                        <h1>Inventory Report</h1>
                    </div>
                    
                    <div class="inventory-stats">
                        <div class="inventory-stat-card">
                            <h3>Total Products</h3>
                            <p><?php echo number_format($stats['total_products']); ?></p>
                        </div>
                        <div class="inventory-stat-card">
                            <h3>Total Stock Value</h3>
                            <p>RM <?php echo number_format($stats['total_value'], 2); ?></p>
                        </div>
                        <div class="inventory-stat-card low-stock">
                            <h3>Low Stock Items</h3>
                            <p><?php echo number_format($stats['low_stock_count']); ?></p>
                        </div>
                        <div class="inventory-stat-card out-of-stock">
                            <h3>Out of Stock Items</h3>
                            <p><?php echo number_format($stats['out_of_stock_count']); ?></p>
                        </div>
                    </div>
                    
                    <div class="inventory-section">
                        <h2 class="inventory-section-header">Low Stock Alert</h2>
                        <table class="inventory-table">
                            <thead>
                                <tr>
                                    <th>Product ID</th>
                                    <th>Category</th>
                                    <th>Product Name</th>
                                    <th>Current Stock</th>
                                    <th>Price</th>
                                    <th>Stock Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lowStockProducts as $product): ?>
                                    <tr>
                                        <td><?php echo $product['ProductID']; ?></td>
                                        <td><?php echo htmlspecialchars($product['CategoryName']); ?></td>
                                        <td><?php echo htmlspecialchars($product['ProductName']); ?></td>
                                        <td><?php echo $product['Quantity']; ?></td>
                                        <td>RM <?php echo number_format($product['Price'], 2); ?></td>
                                        <td>RM <?php echo number_format($product['Price'] * $product['Quantity'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="inventory-section">
                        <h2 class="inventory-section-header">Stock Value by Category</h2>
                        <div class="inventory-category-grid">
                            <?php foreach ($categoryStats as $category): ?>
                                <div class="inventory-category-card">
                                    <h4><?php echo htmlspecialchars($category['CategoryName']); ?></h4>
                                    <div class="inventory-category-stat">
                                        <span>Products</span>
                                        <span class="value"><?php echo number_format($category['ProductCount']); ?></span>
                                    </div>
                                    <div class="inventory-category-stat">
                                        <span>Total Quantity</span>
                                        <span class="value"><?php echo number_format($category['TotalQuantity']); ?></span>
                                    </div>
                                    <div class="inventory-category-stat">
                                        <span>Total Value</span>
                                        <span class="value">RM <?php echo number_format($category['TotalValue'], 2); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 