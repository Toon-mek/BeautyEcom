<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../_base.php';

// Ensure staff is logged in
requireLogin('staff');

// Handle Delete Product
handleDeleteProduct($pdo);

// Handle Edit Product
handleEditProduct($pdo);

// Handle Add Product
handleAddProduct($pdo);

// Allowed sort columns and directions
$allowedSort = ['ProductID', 'CategoryName', 'ProductName', 'Price', 'Quantity'];
$allowedDir = ['asc', 'desc'];

$sort = $_GET['sort'] ?? 'ProductID';
$dir = $_GET['order'] ?? 'asc';
$search = $_GET['search'] ?? '';
$categoryFilter = $_GET['category_filter'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Validate sort and order
if (!in_array($sort, $allowedSort)) {
    $sort = 'ProductID';
}

if (!in_array(strtolower($dir), $allowedDir)) {
    $dir = 'asc';
}

// Build WHERE clause
$where = '';
$params = [];
$whereClauses = [];

if (!empty($search)) {
    $whereClauses[] = "(ProductName LIKE :search_name OR Description LIKE :search_desc)";
    $params[':search_name'] = "%$search%";
    $params[':search_desc'] = "%$search%";
}

if (!empty($categoryFilter)) {
    $whereClauses[] = "p.CategoryID = :category";
    $params[':category'] = $categoryFilter;
}

if ($whereClauses) {
    $where = 'WHERE ' . implode(' AND ', $whereClauses);
}

// Count for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM product p $where");
$countStmt->execute($params);
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $perPage);

// Main query with pagination
$query = "SELECT p.*, c.CategoryName 
          FROM product p 
          LEFT JOIN category c ON p.CategoryID = c.CategoryID 
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
$products = $stmt->fetchAll();

// Fetch categories for filter dropdown
$categories = fetchAllCategories($pdo);

// Sorting link helper
function buildSortLink($column, $label)
{
    $currentSort = $_GET['sort'] ?? 'ProductID';
    $currentDir = $_GET['order'] ?? 'asc';
    $nextDir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';
    $arrow = ($currentSort === $column) ? ($currentDir === 'asc' ? '↑' : '↓') : '';
    return "<a href='?sort=$column&order=$nextDir'>" . htmlspecialchars($label) . " $arrow</a>";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management</title>
    <link href="../css/admin.css" rel="stylesheet">
</head>

<body>
    <div class="admin-flex-container">
        <?php include 'adminSidebar.php'; ?>
        <div class="admin-main-content">
            <h1>Product Management</h1>

            <form method="GET" style="margin-bottom: 20px; display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap;">
                <div class="filter-group" style="display: flex; flex-direction: column;">
                    <label for="search" style="margin-bottom: 5px;">Search</label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search products..." class="crud-select">
                </div>
                <div class="filter-group" style="display: flex; flex-direction: column;">
                    <label for="category_filter" style="margin-bottom: 5px;">Category</label>
                    <select name="category_filter" id="category_filter" class="crud-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['CategoryID']; ?>"
                                <?php echo ($categoryFilter == $cat['CategoryID']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['CategoryName']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group" style="display: flex; flex-direction: column;">
                    <label for="sort" style="margin-bottom: 5px;">Sort By</label>
                    <select name="sort" id="sort" class="crud-select">
                        <option value="ProductID" <?php echo ($sort === 'ProductID') ? 'selected' : ''; ?>>ID</option>
                        <option value="CategoryName" <?php echo ($sort === 'CategoryName') ? 'selected' : ''; ?>>Category</option>
                        <option value="ProductName" <?php echo ($sort === 'ProductName') ? 'selected' : ''; ?>>Name</option>
                        <option value="Price" <?php echo ($sort === 'Price') ? 'selected' : ''; ?>>Price</option>
                        <option value="Quantity" <?php echo ($sort === 'Quantity') ? 'selected' : ''; ?>>Quantity</option>
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

            <button class="crud-btn add-btn" onclick="showAddForm()" style="margin-bottom:18px;background:#27ae60;color:#fff;">Add Product</button>

            <!-- Product Table -->
            <div class="table-responsive">
                <table class="product-table">
                    <thead>
                        <tr>
                            <th><?php echo buildSortLink('ProductID', 'ID'); ?></th>
                            <th><?php echo buildSortLink('CategoryName', 'Category'); ?></th>
                            <th><?php echo buildSortLink('ProductName', 'Name'); ?></th>
                            <th>Description</th>
                            <th><?php echo buildSortLink('Price', 'Price'); ?></th>
                            <th><?php echo buildSortLink('Quantity', 'Quantity'); ?></th>
                            <th>Image 1</th>
                            <th>Image 2</th>
                            <th>Image 3</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo $product['ProductID']; ?></td>
                                <td><?php echo htmlspecialchars($product['CategoryName']); ?></td>
                                <td><?php echo htmlspecialchars($product['ProductName']); ?></td>
                                <td>
                                    <span class="truncate-description" title="<?= htmlspecialchars($product['Description']) ?>">
                                        <?= htmlspecialchars($product['Description']) ?>
                                    </span>
                                </td>

                                <td><?php echo number_format($product['Price'], 2); ?></td>
                                <td><?php echo $product['Quantity']; ?></td>
                                <td>
                                    <?php if ($product['ProdIMG1']): ?>
                                        <img src="../uploads/<?php echo htmlspecialchars($product['ProdIMG1']); ?>" class="product-image" alt="<?php echo htmlspecialchars($product['ProductName']); ?>">
                                    <?php else: ?>
                                        <span style="color:#aaa;">No Img</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($product['ProdIMG2']): ?>
                                        <img src="../uploads/<?php echo htmlspecialchars($product['ProdIMG2']); ?>" class="product-image" alt="<?php echo htmlspecialchars($product['ProductName']); ?>">
                                    <?php else: ?>
                                        <span style="color:#aaa;">No Img</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($product['ProdIMG3']): ?>
                                        <img src="../uploads/<?php echo htmlspecialchars($product['ProdIMG3']); ?>" class="product-image" alt="<?php echo htmlspecialchars($product['ProductName']); ?>">
                                    <?php else: ?>
                                        <span style="color:#aaa;">No Img</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="crud-btn edit-btn" onclick="showEditForm(
                                <?php echo $product['ProductID']; ?>,
                                '<?php echo htmlspecialchars(addslashes($product['CategoryID'])); ?>',
                                '<?php echo htmlspecialchars(addslashes($product['ProductName'])); ?>',
                                `<?php echo htmlspecialchars(addslashes($product['Description'])); ?>`,
                                '<?php echo $product['Price']; ?>',
                                '<?php echo $product['Quantity']; ?>',
                                '<?php echo htmlspecialchars(addslashes($product['ProdIMG1'])); ?>',
                                '<?php echo htmlspecialchars(addslashes($product['ProdIMG2'])); ?>',
                                '<?php echo htmlspecialchars(addslashes($product['ProdIMG3'])); ?>'
                            )">Edit</button>
                                    <a href="?delete=<?php echo $product['ProductID']; ?>" class="crud-btn delete-btn" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <a href="?sort=<?php echo $sort; ?>&order=<?php echo $dir; ?>&search=<?php echo urlencode($search); ?>&category_filter=<?php echo urlencode($categoryFilter); ?>&page=<?php echo $p; ?>"
                            class="<?php echo $p == $page ? 'active' : ''; ?>">
                            <?php echo $p; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Edit Product Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-content">
            <button type="button" class="modal-close-btn" onclick="hideEditForm()">&times;</button>
            <form class="crud-form" id="editForm" method="POST" enctype="multipart/form-data" style="margin-bottom:0;box-shadow:none;">
                <h3>Edit Product</h3>
                <input type="hidden" name="product_id" id="edit_product_id">
                <label>Category</label>
                <select name="category" id="edit_category" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['CategoryID']; ?>"><?php echo htmlspecialchars($cat['CategoryName']); ?></option>
                    <?php endforeach; ?>
                </select>
                <label>Name</label>
                <input type="text" name="name" id="edit_name" required>
                <label>Description</label>
                <textarea name="description" id="edit_description" required></textarea>
                <label>Price</label>
                <input type="number" step="0.01" name="price" id="edit_price" required>
                <label>Quantity</label>
                <input type="number" name="quantity" id="edit_quantity" required>
                <label>Image 1 (leave blank to keep current)</label>
                <input type="file" name="ProdIMG1" id="edit_img1" accept="image/*">
                <label>Image 2 (leave blank to keep current)</label>
                <input type="file" name="ProdIMG2" id="edit_img2" accept="image/*">
                <label>Image 3 (leave blank to keep current)</label>
                <input type="file" name="ProdIMG3" id="edit_img3" accept="image/*">
                <button type="submit" name="edit_product" class="crud-btn edit-btn">Update Product</button>
                <button type="button" class="crud-btn" onclick="hideEditForm()">Cancel</button>
            </form>
        </div>
    </div>
    <!-- Add Product Modal -->
    <div class="modal-overlay" id="addModal">
        <div class="modal-content">
            <button type="button" class="modal-close-btn" onclick="hideAddForm()">&times;</button>
            <form class="crud-form" id="addForm" method="POST" enctype="multipart/form-data" style="margin-bottom:0;box-shadow:none;">
                <h3>Add Product</h3>
                <label>Category</label>
                <select name="category" id="add_category" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['CategoryID']; ?>"><?php echo htmlspecialchars($cat['CategoryName']); ?></option>
                    <?php endforeach; ?>
                </select>
                <label>Name</label>
                <input type="text" name="name" id="add_name" required>
                <label>Description</label>
                <textarea name="description" id="add_description" required></textarea>
                <label>Price</label>
                <input type="number" step="0.01" name="price" id="add_price" required>
                <label>Quantity</label>
                <input type="number" name="quantity" id="add_quantity" required>
                <label>Image 1</label>
                <input type="file" name="ProdIMG1" id="add_img1" accept="image/*">
                <label>Image 2</label>
                <input type="file" name="ProdIMG2" id="add_img2" accept="image/*">
                <label>Image 3</label>
                <input type="file" name="ProdIMG3" id="add_img3" accept="image/*">
                <button type="submit" name="add_product" class="crud-btn add-btn">Add Product</button>
                <button type="button" class="crud-btn" onclick="hideAddForm()">Cancel</button>
            </form>
        </div>
    </div>
    <script src="../js/productList.js"></script>
</body>

</html>