<?php
require_once __DIR__ . '/../_base.php';

// Prepare filter values
$filters = [
    'category' => $_GET['category'] ?? null,
    'search' => $_GET['search'] ?? ''
];

// Fetch data from centralized base functions
$products = getProducts($filters);
$categories = getCategories();

// For sticky form
$category_id = $filters['category'];
$search = $filters['search'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Beauty & Wellness</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php require_once __DIR__ . '/../_head.php'; ?>
    <div class="container py-5">
        <div class="row">
            <!-- Filters -->
            <div class="col-md-3">
                <div class="filter-section">
                    <h4>Filters</h4>
                    <form method="GET" action="">
                        <div class="mb-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">All Categories</option>
                                <?php foreach($categories as $category): ?>
                                    <option value="<?php echo $category['CategoryID']; ?>" <?php echo ($category_id == $category['CategoryID']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['CategoryName']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-dark w-100">Apply Filters</button>
                    </form>
                </div>
            </div>

            <!-- Products -->
            <div class="col-md-9">
                <div class="row">
                    <?php if(count($products) > 0): ?>
                        <?php foreach($products as $product): ?>
                            <div class="col-md-4">
                                <div class="card product-card">
                                    <img src="../uploads/<?php echo htmlspecialchars($product['ProdIMG1']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['ProductName']); ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($product['ProductName']); ?></h5>
                                        <p class="card-text"><?php echo htmlspecialchars($product['CategoryName']); ?></p>
                                        <p class="card-text"><strong>RM <?php echo number_format($product['Price'], 2); ?></strong></p>
                                        <a href="product.php?id=<?php echo $product['ProductID']; ?>" class="btn btn-dark">View Details</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                No products found matching your criteria.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php require_once __DIR__ . '/../_foot.php'; ?>
</body>
</html>