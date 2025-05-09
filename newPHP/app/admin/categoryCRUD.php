<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../_base.php';

// Ensure staff is logged in
requireLogin('staff');



// Handle Category Creation
if (isset($_POST['create_category'])) {
    $categoryName = trim($_POST['category_name']);
    $categoryDescription = trim($_POST['category_description']);
    
    if (empty($categoryName) || empty($categoryDescription)) {
        $_SESSION['error'] = "Category name and description are required.";
        $_SESSION['add_error'] = true;
    } else {
        // Check for duplicate (case-insensitive, alphabet-insensitive)
        $normalizedInput = normalizeCategoryName($categoryName);
        $stmt = $pdo->prepare("SELECT CategoryName FROM Category");
        $stmt->execute();
        $allNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $duplicate = false;
        foreach ($allNames as $existingName) {
            if (normalizeCategoryName($existingName) === $normalizedInput) {
                $duplicate = true;
                break;
            }
        }
        if ($duplicate) {
            $_SESSION['error'] = "A category with this name already exists.";
            $_SESSION['add_error'] = true;
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO Category (CategoryName, CategoryDescription) VALUES (?, ?)");
                if ($stmt->execute([$categoryName, $categoryDescription])) {
                    $_SESSION['success'] = "Category created successfully.";
                } else {
                    $_SESSION['error'] = "Error creating category.";
                    $_SESSION['add_error'] = true;
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error creating category: " . $e->getMessage();
                $_SESSION['add_error'] = true;
            }
        }
    }
    header("Location: categoryCRUD.php");
    exit();
}

// Handle Category Update
if (isset($_POST['update_category'])) {
    $categoryId = $_POST['category_id'];
    $categoryName = trim($_POST['category_name']);
    $categoryDescription = trim($_POST['category_description']);
    
    if (empty($categoryName) || empty($categoryDescription)) {
        $_SESSION['error'] = "Category name and description are required.";
    } else {
        // Check for duplicate (case-insensitive, alphabet-insensitive), excluding current category
        $normalizedInput = normalizeCategoryName($categoryName);
        $stmt = $pdo->prepare("SELECT CategoryID, CategoryName FROM Category WHERE CategoryID != ?");
        $stmt->execute([$categoryId]);
        $allNames = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $duplicate = false;
        foreach ($allNames as $row) {
            if (normalizeCategoryName($row['CategoryName']) === $normalizedInput) {
                $duplicate = true;
                break;
            }
        }
        if ($duplicate) {
            $_SESSION['error'] = "A category with this name already exists.";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE Category SET CategoryName = ?, CategoryDescription = ? WHERE CategoryID = ?");
                if ($stmt->execute([$categoryName, $categoryDescription, $categoryId])) {
                    $_SESSION['success'] = "Category updated successfully.";
                } else {
                    $_SESSION['error'] = "Error updating category.";
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error updating category: " . $e->getMessage();
            }
        }
    }
    header("Location: categoryCRUD.php");
    exit();
}

// Handle Category Deletion
if (isset($_GET['delete'])) {
    $categoryId = $_GET['delete'];
    try {
        // Check if category is in use
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Product WHERE CategoryID = ?");
        $stmt->execute([$categoryId]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $_SESSION['error'] = "Cannot delete category: It is being used by products.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM Category WHERE CategoryID = ?");
            if ($stmt->execute([$categoryId])) {
                $_SESSION['success'] = "Category deleted successfully.";
            } else {
                $_SESSION['error'] = "Error deleting category.";
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting category: " . $e->getMessage();
    }
    header("Location: categoryCRUD.php");
    exit();
}

// Get all categories
$search = $_GET['search'] ?? '';
$where = '';
$params = [];

$sort = $_GET['sort'] ?? 'CategoryID';
$dir = $_GET['order'] ?? 'asc';

$allowedSort = ['CategoryID', 'CategoryName'];
$allowedDir = ['asc', 'desc'];

if (!in_array($sort, $allowedSort)) {
    $sort = 'CategoryID';
}
if (!in_array(strtolower($dir), $allowedDir)) {
    $dir = 'asc';
}

if (!empty($search)) {
    $where = "WHERE CategoryName LIKE :search";
    $params[':search'] = "%$search%";
}

$stmt = $pdo->prepare("SELECT * FROM Category $where ORDER BY $sort $dir");
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management</title>
    <link href="../css/admin.css" rel="stylesheet">
</head>
<body>
    <div class="admin-flex-container">
        <?php include 'adminSidebar.php'; ?>
        <div class="admin-main-content">
            <h1>Category Management</h1>

            <form method="GET" style="margin-bottom: 20px; display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap;">
                <div class="filter-group" style="display: flex; flex-direction: column;">
                    <label for="search" style="margin-bottom: 5px;">Search</label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search categories..." class="crud-select">
                </div>
            </form>

            <button class="crud-btn add-btn" onclick="showAddModal()" style="margin-bottom:18px;background:#27ae60;color:#fff;">Add Category</button>

            <!-- Add Category Modal -->
            <div class="modal-overlay" id="addModal" style="display: none;">
                <div class="modal-content">
                    <button type="button" class="modal-close-btn" onclick="closeAddModal()">&times;</button>
                    <h2>Add New Category</h2>
                    <?php if (isset($_SESSION['add_error']) && isset($_SESSION['error'])): ?>
                        <div class="alert alert-error"><?php echo $_SESSION['error']; ?></div>
                    <?php endif; ?>
                    <form method="POST" class="crud-form">
                        <div class="form-group">
                            <label for="add_category_name">Category Name</label>
                            <input type="text" id="add_category_name" name="category_name" required class="crud-input">
                        </div>
                        <div class="form-group">
                            <label for="add_category_description">Category Description</label>
                            <textarea id="add_category_description" name="category_description" class="crud-input" rows="3"></textarea>
                        </div>
                        <button type="submit" name="create_category" class="crud-btn" style="background:#27ae60;color:#fff;">Add Category</button>
                    </form>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <!-- Categories Table -->
            <table class="product-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Category Name</th>
                        <th>Category Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo $category['CategoryID']; ?></td>
                            <td><?php echo htmlspecialchars($category['CategoryName']); ?></td>
                            <td>
                                <span class="truncate-description" title="<?= htmlspecialchars($category['CategoryDescription']) ?>">
                                    <?= htmlspecialchars($category['CategoryDescription']) ?>
                                </span>
                            </td>
                            <td>
                                <button class="crud-btn edit-btn" onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)">Edit</button>
                                <a href="?delete=<?php echo $category['CategoryID']; ?>" class="crud-btn delete-btn" 
                                   onclick="return confirm('Are you sure you want to delete this category?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal-overlay" id="editModal" style="display: none;">
        <div class="modal-content">
            <button type="button" class="modal-close-btn" onclick="closeEditModal()">&times;</button>
            <h2>Edit Category</h2>
            <form method="POST" class="crud-form">
                <input type="hidden" name="category_id" id="edit_category_id">
                <div class="form-group">
                    <label for="edit_category_name">Category Name</label>
                    <input type="text" id="edit_category_name" name="category_name" required class="crud-input">
                </div>
                <div class="form-group">
                    <label for="edit_category_description">Category Description</label>
                    <textarea id="edit_category_description" name="category_description" class="crud-input" rows="3"></textarea>
                </div>
                <button type="submit" name="update_category" class="crud-btn">Update Category</button>
            </form>
        </div>
    </div>

    <script src="../js/categoryCRUD.js"></script>
</body>
</html> 