<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../_base.php';

// Ensure the user is logged in
redirectIfNotLoggedIn();

// Get filters from query parameters
$sort = $_GET['sort'] ?? 'OrderDate';
$dir = $_GET['order'] ?? 'desc';
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status_filter'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Validate sort and direction
$allowedSort = ['OrderID', 'OrderDate', 'OrderStatus', 'OrderTotalAmount'];
$allowedDir = ['asc', 'desc'];

if (!in_array($sort, $allowedSort)) {
    $sort = 'OrderDate';
}

if (!in_array(strtolower($dir), $allowedDir)) {
    $dir = 'desc';
}

// Build query conditions
$where = 'WHERE o.MemberID = :member_id';
$params = [':member_id' => $_SESSION['member_id']];

if (!empty($search)) {
    $where .= " AND (o.OrderID LIKE :search OR o.OrderStatus LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($statusFilter)) {
    $where .= " AND o.OrderStatus = :status";
    $params[':status'] = $statusFilter;
}

// Count total orders for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o $where");
$countStmt->execute($params);
$totalOrders = $countStmt->fetchColumn();
$totalPages = ceil($totalOrders / $perPage);

// Get orders with pagination
$query = "SELECT o.*, p.PaymentMethod, p.PaymentStatus 
          FROM orders o 
          LEFT JOIN payment p ON o.OrderID = p.OrderID 
          $where 
          ORDER BY $sort $dir 
          LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);

// Bind parameters
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', (int) $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);

$stmt->execute();
$orders = $stmt->fetchAll();

// Helper function to build sort links
function buildSortLink($column, $label)
{
    $currentSort = $_GET['sort'] ?? 'OrderDate';
    $currentDir = $_GET['order'] ?? 'desc';
    $nextDir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';
    $arrow = ($currentSort === $column) ? ($currentDir === 'asc' ? '↑' : '↓') : '';
    
    $query = $_GET;
    $query['sort'] = $column;
    $query['order'] = $nextDir;
    $queryString = http_build_query($query);
    
    return "<a href='?$queryString'>" . htmlspecialchars($label) . " $arrow</a>";
}

// Get order status for status filter
$statusStmt = $pdo->prepare("SELECT DISTINCT OrderStatus FROM orders WHERE MemberID = ?");
$statusStmt->execute([$_SESSION['member_id']]);
$statuses = $statusStmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - Beauty & Wellness</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php require_once __DIR__ . '/../_head.php'; ?>
    
    <div class="order-history-container">
        <h1>Order History</h1>
        
        <div class="filters-section">
            <form method="GET" class="filters-form">
                <div class="filter-group">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Search order #" class="filter-input">
                </div>
                <div class="filter-group">
                    <label for="status_filter">Status</label>
                    <select name="status_filter" id="status_filter" class="filter-select">
                        <option value="">All Status</option>
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?php echo $status; ?>" <?php echo ($statusFilter === $status) ? 'selected' : ''; ?>>
                                <?php echo $status; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="sort">Sort By</label>
                    <select name="sort" id="sort" class="filter-select">
                        <option value="OrderDate" <?php echo ($sort === 'OrderDate') ? 'selected' : ''; ?>>Order Date</option>
                        <option value="OrderID" <?php echo ($sort === 'OrderID') ? 'selected' : ''; ?>>Order ID</option>
                        <option value="OrderTotalAmount" <?php echo ($sort === 'OrderTotalAmount') ? 'selected' : ''; ?>>Total Amount</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="order">Order</label>
                    <select name="order" id="order" class="filter-select">
                        <option value="desc" <?php echo ($dir === 'desc') ? 'selected' : ''; ?>>Newest First</option>
                        <option value="asc" <?php echo ($dir === 'asc') ? 'selected' : ''; ?>>Oldest First</option>
                    </select>
                </div>
                <div class="filter-group">
                    <button type="submit" class="view-btn">Apply Filters</button>
                </div>
            </form>
        </div>
        
        <?php if (count($orders) > 0): ?>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th><?php echo buildSortLink('OrderID', 'Order #'); ?></th>
                        <th><?php echo buildSortLink('OrderDate', 'Date'); ?></th>
                        <th><?php echo buildSortLink('OrderStatus', 'Status'); ?></th>
                        <th>Payment Method</th>
                        <th>Payment Status</th>
                        <th><?php echo buildSortLink('OrderTotalAmount', 'Total'); ?></th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['OrderID']; ?></td>
                            <td><?php echo date('M d, Y g:i A', strtotime($order['OrderDate'])); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($order['OrderStatus']); ?>">
                                    <?php echo $order['OrderStatus']; ?>
                                </span>
                            </td>
                            <td><?php echo !empty($order['PaymentMethod']) ? htmlspecialchars($order['PaymentMethod']) : 'Not specified'; ?></td>
                            <td>
                                <?php if(!empty($order['PaymentStatus'])): ?>
                                    <span class="status-badge <?php echo ($order['PaymentStatus'] === 'Paid') ? 'status-completed' : 'status-pending'; ?>">
                                        <?php echo $order['PaymentStatus']; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge status-pending">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>RM <?php echo number_format($order['OrderTotalAmount'], 2); ?></td>
                            <td>
                                <a href="order_confirmation.php?order_id=<?php echo $order['OrderID']; ?>" class="view-btn">View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php
                    $queryParams = $_GET;
                    for ($p = 1; $p <= $totalPages; $p++): 
                        $queryParams['page'] = $p;
                        $queryString = http_build_query($queryParams);
                    ?>
                        <a href="?<?php echo $queryString; ?>" 
                           class="<?php echo $p == $page ? 'active' : ''; ?>">
                            <?php echo $p; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-orders">
                <i class="fas fa-shopping-bag"></i>
                <h3>No Orders Found</h3>
                <p>You haven't placed any orders yet, or no orders match your filter criteria.</p>
                <a href="/../product/all_product.php" class="shop-now-btn">Shop Now</a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php require_once __DIR__ . '/../_foot.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-submit form when filters change
            const filterForm = document.querySelector('.filters-form');
            const filterInputs = filterForm.querySelectorAll('select, input[type="text"]');
            
            filterInputs.forEach(input => {
                if (input.tagName === 'SELECT') {
                    input.addEventListener('change', function() {
                        filterForm.submit();
                    });
                }
            });
        });
    </script>
</body>
</html>
