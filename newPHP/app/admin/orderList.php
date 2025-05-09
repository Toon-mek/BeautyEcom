<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../_base.php';

// Ensure staff is logged in
requireLogin('staff');

// Handle Update Order Status
if (isset($_POST['update_status'])) {
    $orderId = $_POST['order_id'];
    $newStatus = $_POST['status'];
    
    if (updateOrderStatus($pdo, $orderId, $newStatus)) {
        $_SESSION['success'] = "Order status updated successfully.";
    } else {
        $_SESSION['error'] = "Error updating order status.";
    }
    
    header("Location: orderList.php");
    exit();
}

// Handle Order Details Request
if (isset($_GET['action']) && $_GET['action'] === 'get_details' && isset($_GET['order_id'])) {
    $orderId = $_GET['order_id'];
    $order = getOrderDetails($pdo, $orderId);
    if (!$order) {
        die('Order not found');
    }
    $items = getOrderItems($pdo, $orderId);
    
    // Output order details HTML
    ?>
    <div class="order-details">
        <h2>Order #<?php echo $order['OrderID']; ?></h2>
        
        <div class="order-info">
            <h3>Order Information</h3>
            <p><strong>Date:</strong> <?php echo date('Y-m-d H:i', strtotime($order['OrderDate'])); ?></p>
            <p><strong>Status:</strong> <span class="status-<?php echo strtolower($order['OrderStatus']); ?>"><?php echo htmlspecialchars($order['OrderStatus']); ?></span></p>
            <p><strong>Total Amount:</strong> RM<?php echo number_format($order['OrderTotalAmount'], 2); ?></p>
        </div>

        <div class="customer-info">
            <h3>Customer Information</h3>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($order['CustomerName']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($order['Email']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['Phone']); ?></p>
            <p><strong>Address:</strong> <?php echo htmlspecialchars($order['Address']); ?></p>
        </div>

        <div class="order-items">
            <h3>Order Items</h3>
            <table class="order-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Image</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['ProductName']); ?></td>
                            <td>
                                <?php if ($item['ProdIMG1']): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($item['ProdIMG1']); ?>" 
                                         class="product-image" alt="<?php echo htmlspecialchars($item['ProductName']); ?>">
                                <?php else: ?>
                                    <span style="color:#aaa;">No Image</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $item['Quantity']; ?></td>
                            <td>RM<?php echo number_format($item['Price'], 2); ?></td>
                            <td>RM<?php echo number_format($item['Quantity'] * $item['Price'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    exit();
}

// Allowed sort columns and directions
$allowedSort = ['OrderID', 'OrderDate', 'OrderTotalAmount', 'OrderStatus', 'CustomerName'];
$allowedDir = ['asc', 'desc'];

$sort = $_GET['sort'] ?? 'OrderID';
$dir = $_GET['order'] ?? 'asc';
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status_filter'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Validate sort and order
if (!in_array($sort, $allowedSort)) {
    $sort = 'OrderID';
}

if (!in_array(strtolower($dir), $allowedDir)) {
    $dir = 'asc';
}

// Build WHERE clause
$where = '';
$params = [];
$whereClauses = [];

if (!empty($search)) {
    $whereClauses[] = "(o.OrderID LIKE :search_id OR m.Name LIKE :search_name)";
    $params[':search_id'] = "%$search%";
    $params[':search_name'] = "%$search%";
}

if (!empty($statusFilter)) {
    $whereClauses[] = "o.OrderStatus = :status";
    $params[':status'] = $statusFilter;
}

if ($whereClauses) {
    $where = 'WHERE ' . implode(' AND ', $whereClauses);
}

// Get orders and total count
$orders = getOrders($pdo, $where, $params, $sort, $dir, $perPage, $offset);
$totalOrders = countOrders($pdo, $where, $params);
$totalPages = ceil($totalOrders / $perPage);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management</title>
    <link href="../css/admin.css" rel="stylesheet">
</head>
<body>
    <div class="admin-flex-container">
        <?php include 'adminSidebar.php'; ?>
        <div class="admin-main-content">
            <h1>Order Management</h1>

            <form method="GET" class="order-filter-form">
                <div class="order-filter-group">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search orders..." class="crud-select" onchange="this.form.submit()">
                </div>
                <div class="order-filter-group">
                    <label for="status_filter">Status</label>
                    <select name="status_filter" id="status_filter" class="crud-select" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="Pending" <?php echo ($statusFilter === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="Completed" <?php echo ($statusFilter === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                        <option value="Cancelled" <?php echo ($statusFilter === 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="order-filter-group">
                    <label for="sort">Sort By</label>
                    <select name="sort" id="sort" class="crud-select" onchange="this.form.submit()">
                        <option value="OrderID" <?php echo ($sort === 'OrderID') ? 'selected' : ''; ?>>Order ID</option>
                        <option value="OrderDate" <?php echo ($sort === 'OrderDate') ? 'selected' : ''; ?>>Date</option>
                        <option value="OrderTotalAmount" <?php echo ($sort === 'OrderTotalAmount') ? 'selected' : ''; ?>>Amount</option>
                        <option value="OrderStatus" <?php echo ($sort === 'OrderStatus') ? 'selected' : ''; ?>>Status</option>
                        <option value="CustomerName" <?php echo ($sort === 'CustomerName') ? 'selected' : ''; ?>>Customer</option>
                    </select>
                </div>
                <div class="order-filter-group">
                    <label for="order">Order</label>
                    <select name="order" id="order" class="crud-select" onchange="this.form.submit()">
                        <option value="asc" <?php echo ($dir === 'asc') ? 'selected' : ''; ?>>Ascending</option>
                        <option value="desc" <?php echo ($dir === 'desc') ? 'selected' : ''; ?>>Descending</option>
                    </select>
                </div>
            </form>

            <!-- Order Table -->
            <table class="order-table">
                <thead>
                    <tr>
                        <th><?php echo buildOrderSortLink('OrderID', 'Order ID'); ?></th>
                        <th><?php echo buildOrderSortLink('OrderDate', 'Date'); ?></th>
                        <th><?php echo buildOrderSortLink('CustomerName', 'Customer'); ?></th>
                        <th><?php echo buildOrderSortLink('OrderTotalAmount', 'Amount'); ?></th>
                        <th><?php echo buildOrderSortLink('OrderStatus', 'Status'); ?></th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo $order['OrderID']; ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($order['OrderDate'])); ?></td>
                            <td><?php echo htmlspecialchars($order['CustomerName']); ?></td>
                            <td>RM<?php echo number_format($order['OrderTotalAmount'], 2); ?></td>
                            <td>
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="order_id" value="<?php echo $order['OrderID']; ?>">
                                    <select name="status" onchange="this.form.submit()" class="order-status-select" <?php if ($order['OrderStatus'] === 'Completed' || $order['OrderStatus'] === 'Cancelled') echo 'disabled'; ?>>
                                        <option value="Pending" <?php echo ($order['OrderStatus'] === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Completed" <?php echo ($order['OrderStatus'] === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                        <option value="Cancelled" <?php echo ($order['OrderStatus'] === 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                            </td>
                            <td>
                                <button class="crud-btn edit-btn" onclick="showOrderDetails(<?php echo $order['OrderID']; ?>)">View Details</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <a href="?sort=<?php echo $sort; ?>&order=<?php echo $dir; ?>&search=<?php echo urlencode($search); ?>&status_filter=<?php echo urlencode($statusFilter); ?>&page=<?php echo $p; ?>"
                           class="<?php echo $p == $page ? 'active' : ''; ?>">
                            <?php echo $p; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal-overlay order-details-modal" id="orderDetailsModal">
        <div class="modal-content">
            <button type="button" class="modal-close-btn" onclick="hideOrderDetails()">&times;</button>
            <div id="orderDetailsContent">
                <!-- Order details will be loaded here -->
            </div>
        </div>
    </div>

    <script src="../js/orderList.js"></script>
</body>
</html> 