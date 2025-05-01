<?php
require_once __DIR__ . '/../_base.php';

// Allowed sort columns and directions
$allowedSort = ['default', 'price_asc', 'price_desc', 'name_asc'];
$allowedDir = ['asc', 'desc'];

// Get filter values
$sort = $_GET['sort'] ?? 'default';
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12; // Products per page
$offset = ($page - 1) * $perPage;

// Validate sort
if (!in_array($sort, $allowedSort)) {
    $sort = 'default';
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

if (!empty($category)) {
    $whereClauses[] = "p.CategoryID = :category";
    $params[':category'] = $category;
}

if ($whereClauses) {
    $where = 'WHERE ' . implode(' AND ', $whereClauses);
}

// Count total products for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM product p $where");
$countStmt->execute($params);
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $perPage);

// Build ORDER BY clause based on sort
$orderBy = '';
switch ($sort) {
    case 'price_asc':
        $orderBy = 'ORDER BY Price ASC';
        break;
    case 'price_desc':
        $orderBy = 'ORDER BY Price DESC';
        break;
    case 'name_asc':
        $orderBy = 'ORDER BY ProductName ASC';
        break;
    default:
        $orderBy = 'ORDER BY ProductID DESC';
}

// Main query with pagination
$query = "SELECT p.*, c.CategoryName 
          FROM product p 
          LEFT JOIN category c ON p.CategoryID = c.CategoryID 
          $where 
          $orderBy 
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

// Get categories for dropdown
$categories = getCategories();

// For sticky form
$current_sort = $sort;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Beauty & Wellness</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php require_once __DIR__ . '/../_head.php'; ?>
    
    <div class="products-hero">
        <div class="products-hero-content">
            <h1>Our Products</h1>
            <p>Discover our collection of beauty and wellness products</p>
        </div>
    </div>

    <div class="products-container">
        <!-- Filters Section -->
        <div class="products-filters">
            <form method="GET" action="" class="filters-form">
                <div class="filter-group">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" 
                               name="search" 
                               placeholder="Search products..."
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>

                <div class="filter-group">
                    <select name="category" class="filter-select">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo $cat['CategoryID']; ?>"
                                    <?php echo ($category == $cat['CategoryID']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['CategoryName']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <select name="sort" class="filter-select">
                        <option value="default" <?php echo ($current_sort === 'default') ? 'selected' : ''; ?>>Featured</option>
                        <option value="price_asc" <?php echo ($current_sort === 'price_asc') ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_desc" <?php echo ($current_sort === 'price_desc') ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="name_asc" <?php echo ($current_sort === 'name_asc') ? 'selected' : ''; ?>>Name: A to Z</option>
                    </select>
                </div>
            </form>
        </div>

        <!-- Products Grid -->
        <main class="products-grid">
            <?php if(count($products) > 0): ?>
                <?php foreach($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <img src="../uploads/<?php echo htmlspecialchars($product['ProdIMG1']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['ProductName']); ?>">
                            <div class="product-actions">
                                <a href="product.php?id=<?php echo $product['ProductID']; ?>" 
                                   class="view-details-btn">
                                    View Details
                                </a>
                            </div>
                        </div>
                        <div class="product-info">
                            <h3 class="product-name">
                                <?php echo htmlspecialchars($product['ProductName']); ?>
                            </h3>
                            <p class="product-category">
                                <?php echo htmlspecialchars($product['CategoryName']); ?>
                            </p>
                            <p class="product-price">
                                RM <?php echo number_format($product['Price'], 2); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <a href="?sort=<?php echo $sort; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&page=<?php echo $p; ?>"
                               class="<?php echo $p == $page ? 'active' : ''; ?>">
                                <?php echo $p; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-products">
                    <i class="fas fa-box-open"></i>
                    <h2>No Products Found</h2>
                    <p>We couldn't find any products matching your criteria.</p>
                    <a href="?">Clear Filters</a>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        function toggleFilters() {
            const sidebar = document.getElementById('filtersSidebar');
            sidebar.classList.toggle('active');
            document.body.classList.toggle('filters-open');
        }

        // Auto-submit form when any filter changes
        document.querySelectorAll('.filter-select, .search-box input').forEach(element => {
            element.addEventListener('change', function() {
                this.closest('form').submit();
            });
        });

        // Handle search input - submit on Enter or after typing stops
        const searchInput = document.querySelector('.search-box input');
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.closest('form').submit();
            }, 500); // Submit after 500ms of no typing
        });

        // Show/hide mobile filter button on scroll
        let lastScroll = 0;
        window.addEventListener('scroll', () => {
            const filterToggle = document.querySelector('.filter-toggle');
            const currentScroll = window.pageYOffset;
            
            if (currentScroll <= 0) {
                filterToggle.classList.remove('scroll-up');
            }
            
            if (currentScroll > lastScroll && !filterToggle.classList.contains('scroll-down')) {
                filterToggle.classList.remove('scroll-up');
                filterToggle.classList.add('scroll-down');
            }
            
            if (currentScroll < lastScroll && filterToggle.classList.contains('scroll-down')) {
                filterToggle.classList.remove('scroll-down');
                filterToggle.classList.add('scroll-up');
            }
            
            lastScroll = currentScroll;
        });
    </script>

    <?php require_once __DIR__ . '/../_foot.php'; ?>
</body>
</html>