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

// Fetch all products and categories
$products = fetchAllProducts($pdo);
$categories = fetchAllCategories($pdo);
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
        <button class="crud-btn add-btn" onclick="showAddForm()" style="margin-bottom:18px;background:#27ae60;color:#fff;">Add Product</button>
        <!-- Product Table -->
        <table class="product-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Category</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Quantity</th>
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
                    <td><?php echo htmlspecialchars($product['Description']); ?></td>
                    <td><?php echo number_format($product['Price'], 2); ?></td>
                    <td><?php echo $product['Quantity']; ?></td>
                    <td>
                        <?php if ($product['ProdIMG1']): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($product['ProdIMG1']); ?>" class="product-img-thumb" alt="IMG1">
                        <?php else: ?>
                            <span style="color:#aaa;">No Img</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($product['ProdIMG2']): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($product['ProdIMG2']); ?>" class="product-img-thumb" alt="IMG2">
                        <?php else: ?>
                            <span style="color:#aaa;">No Img</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($product['ProdIMG3']): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($product['ProdIMG3']); ?>" class="product-img-thumb" alt="IMG3">
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