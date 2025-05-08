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
    $stmt = $pdo->query("SELECT COUNT(*) FROM product WHERE Quantity <= 10");
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
        WHERE p.Quantity <= 10 
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
                        <?php if (count($lowStockProducts) > 0): ?>
                        <div class="low-stock-warning">
                            <div class="low-stock-warning-icon">
                                <div class="warning-triangle"></div>
                            </div>
                            <div class="low-stock-warning-content">
                                <div class="low-stock-warning-title">Low Stock Warning</div>
                                <div class="low-stock-warning-message">
                                    There are <?php echo count($lowStockProducts); ?> products with stock levels at or below 10 units. 
                                    Please review the items below and consider restocking to maintain optimal inventory levels.
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
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