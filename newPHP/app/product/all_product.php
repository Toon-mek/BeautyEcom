<?php
require_once __DIR__ . '/../_base.php';

// Prepare filter values
$filters = [
    'category' => $_GET['category'] ?? null,
    'search' => $_GET['search'] ?? '',
    'sort' => $_GET['sort'] ?? 'default'
];

// Fetch data from centralized base functions
$products = getProducts($filters);
$categories = getCategories();

// For sticky form
$category_id = $filters['category'];
$search = $filters['search'];
$current_sort = $filters['sort'];
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

    <div class="search-container">
        <form method="GET" action="" class="main-search-form">
            <div class="main-search-box">
                <i class="fas fa-search"></i>
                <input type="text" 
                       id="main-search" 
                       name="search" 
                       placeholder="Search products..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
        </form>
    </div>

    <div class="products-container">
        <!-- Mobile Filter Toggle -->
        <button class="filter-toggle" onclick="toggleFilters()">
            <i class="fas fa-filter"></i> Filters
        </button>

        <!-- Filters Sidebar -->
        <aside class="filters-sidebar" id="filtersSidebar">
            <div class="filters-header">
                <h3>Filters</h3>
                <button class="close-filters" onclick="toggleFilters()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="GET" action="" class="filters-form">
                <div class="filter-section">
                    <h4>Categories</h4>
                    <div class="category-list">
                        <label class="category-item">
                            <input type="radio" 
                                   name="category" 
                                   value="" 
                                   <?php echo (!$category_id) ? 'checked' : ''; ?>>
                            <span>All Categories</span>
                        </label>
                        <?php foreach($categories as $category): ?>
                            <label class="category-item">
                                <input type="radio" 
                                       name="category" 
                                       value="<?php echo $category['CategoryID']; ?>"
                                       <?php echo ($category_id == $category['CategoryID']) ? 'checked' : ''; ?>>
                                <span><?php echo htmlspecialchars($category['CategoryName']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="filter-section">
                    <h4>Sort By</h4>
                    <div class="sort-options">
                        <label class="sort-item">
                            <input type="radio" name="sort" value="default" <?php echo ($current_sort === 'default') ? 'checked' : ''; ?>>
                            <span>Featured</span>
                        </label>
                        <label class="sort-item">
                            <input type="radio" name="sort" value="price_asc" <?php echo ($current_sort === 'price_asc') ? 'checked' : ''; ?>>
                            <span>Price: Low to High</span>
                        </label>
                        <label class="sort-item">
                            <input type="radio" name="sort" value="price_desc" <?php echo ($current_sort === 'price_desc') ? 'checked' : ''; ?>>
                            <span>Price: High to Low</span>
                        </label>
                        <label class="sort-item">
                            <input type="radio" name="sort" value="name_asc" <?php echo ($current_sort === 'name_asc') ? 'checked' : ''; ?>>
                            <span>Name: A to Z</span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="apply-filters-btn">
                    Apply Filters
                </button>
            </form>
        </aside>

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

        // Auto-submit form when sort option or category changes
        document.querySelectorAll('input[name="sort"], input[name="category"]').forEach(input => {
            input.addEventListener('change', function() {
                this.closest('form').submit();
            });
        });

        // Handle search input - only submit on Enter
        const searchInput = document.getElementById('main-search');
        const searchForm = document.querySelector('.main-search-form');
        
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchForm.submit();
            }
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