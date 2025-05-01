<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    
    try {
        // Get the message from POST data directly
        $message = $_POST['message'] ?? '';
        error_log("Received message: " . $message);
        
        if (empty($message)) {
            throw new Exception("Empty message received");
        }

        // Database connection
        $conn = new mysqli('127.0.0.1', 'root', '', 'ecomm');
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }

        // Process the query
        $reply = processUserQuery($message, $conn);
        
        if (empty($reply)) {
            throw new Exception("No response generated");
        }

        echo json_encode(['status' => 'success', 'reply' => $reply]);

    } catch (Exception $e) {
        error_log("Chat Error: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'reply' => 'Sorry, there was an error processing your request: ' . $e->getMessage()
        ]);
    } finally {
        if (isset($conn) && $conn) {
            $conn->close();
        }
    }
    exit;
}
?>

<?php

require_once __DIR__ . '/_base.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/_head.php';

// Fetch categories and one featured product from each category
$categories = fetchAllCategories($pdo);
$featuredProducts = [];

foreach ($categories as $category) {
    $stmt = $pdo->prepare("
        SELECT p.*, c.CategoryName 
        FROM product p 
        LEFT JOIN category c ON p.CategoryID = c.CategoryID 
        WHERE p.CategoryID = :category_id 
        ORDER BY p.ProductID DESC 
        LIMIT 1
    ");
    $stmt->execute([':category_id' => $category['CategoryID']]);
    $product = $stmt->fetch();
    if ($product) {
        $featuredProducts[] = $product;
    }
}
?>

<div class="hero-section">
    <div class="hero-content">
        <h1>Discover Your Beauty Journey</h1>
        <p>Premium beauty and wellness products for your unique needs</p>
        <div class="hero-buttons">
            <a href="product/all_product.php" class="btn btn-primary">Shop Now</a>
            <a href="#featured" class="btn btn-outline">View Featured</a>
        </div>
    </div>
    <div class="hero-overlay"></div>
</div>

<div class="categories-section">
    <div class="section-header">
        <h2>Shop by Category</h2>
        <p>Find the perfect products for your needs</p>
    </div>
    <div class="category-grid">
        <?php foreach ($categories as $category): ?>
            <a href="product/all_product.php?category=<?php echo $category['CategoryID']; ?>" class="category-card">
                <div class="category-icon">
                    <i class="fas fa-spa"></i>
                </div>
                <h3><?php echo htmlspecialchars($category['CategoryName']); ?></h3>
                <p><?php echo htmlspecialchars($category['CategoryDescription']); ?></p>
                <span class="category-link">Explore <i class="fas fa-arrow-right"></i></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="featured-products" id="featured">
    <div class="section-header">
        <h2>Featured Products</h2>
        <p>Our most popular and highly-rated items</p>
    </div>
    <div class="product-grid">
        <?php foreach ($featuredProducts as $product): ?>
            <div class="product-card">
                <div class="product-image">
                    <img src="uploads/<?php echo htmlspecialchars($product['ProdIMG1']); ?>" 
                         alt="<?php echo htmlspecialchars($product['ProductName']); ?>">
                    <div class="product-actions">
                        <a href="product/product.php?id=<?php echo $product['ProductID']; ?>" 
                           class="view-details-btn">View Details</a>
                    </div>
                </div>
                <div class="product-info">
                    <h3 class="product-name"><?php echo htmlspecialchars($product['ProductName']); ?></h3>
                    <p class="product-category"><?php echo htmlspecialchars($product['CategoryName']); ?></p>
                    <p class="product-price">RM <?php echo number_format($product['Price'], 2); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="view-all-container">
        <a href="product/all_product.php" class="btn btn-outline">View All Products</a>
    </div>
</div>

<!-- Chatbot Popup -->
<div id="chat-popup" style="position: fixed; bottom: 80px; right: 20px; width: 400px; background: #fff; border: 1px solid #ccc; border-radius: 10px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); display: none; z-index: 1000;">
    <div style="padding: 10px; background: #f4f4f4; border-bottom: 1px solid #ccc;">
        <strong>Chatbot Steve</strong>
        <div style="float: right;">
            <button onclick="toggleChat()" style="background: none; border: none; font-size: 16px; cursor: pointer;">&times;</button>
        </div>
    </div>
    <div id="chatbox" class="chatbox">
    </div>
    <div style="padding: 10px; border-top: 1px solid #ccc;">
        <input type="text" id="userInput" placeholder="Type a message..." style="width: 80%; padding: 5px;">
        <button onclick="sendMessage()" style="padding: 5px;">Send</button>
    </div>
</div>
<button onclick="toggleChat()" style="position: fixed; bottom: 20px; right: 20px; background: #007bff; color: #fff; border: none; border-radius: 50%; width: 50px; height: 50px; font-size: 20px; cursor: pointer; z-index: 1000;">💬</button>

<script>
    function toggleChat() {
        const chatPopup = document.getElementById('chat-popup');
        const isHidden = chatPopup.style.display === 'none' || chatPopup.style.display === '';
        chatPopup.style.display = isHidden ? 'block' : 'none';
        
        // Show welcome message when chat is opened
        if (isHidden) {
            const chatbox = document.getElementById('chatbox');
            fetch(window.location.href, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'message=help'
            })
            .then(async res => {
                if (!res.ok) {
                    throw new Error(`HTTP error! status: ${res.status}`);
                }
                const text = await res.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Server response:', text);
                    throw new Error('Invalid JSON response');
                }
            })
            .then(data => {
                if (data && data.reply) {
                    chatbox.innerHTML = `
                        <div class="bot-message" style="margin: 10px 0; padding: 8px; border-radius: 8px; background-color: #e3f2fd;">
                            <strong>Steve:</strong> ${data.reply.replace(/\n/g, '<br>')}
                        </div>
                    `;
                }
                chatbox.scrollTop = chatbox.scrollHeight;
            })
            .catch(error => {
                console.error('Error:', error);
                chatbox.innerHTML = `<div class="bot-message" style="margin: 10px 0; padding: 8px; border-radius: 8px; background-color: #e3f2fd;"><strong>Steve:</strong> How can I help you today?</div>`;
            });
        }
    }

    // Add event listener when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('userInput');
        
        // Add keypress event listener for Enter key
        input.addEventListener('keypress', function(event) {
            // Check if the pressed key is Enter
            if (event.key === 'Enter') {
                // Prevent default form submission
                event.preventDefault();
                // Trigger the send message function
                sendMessage();
            }
        });
    });
    function toggleExpand() {
    const chatbox = document.getElementById('chatbox');
    if (chatbox.classList.contains('chatbox-expanded')) {
        chatbox.classList.remove('chatbox-expanded');
        chatbox.classList.add('chatbox-collapsed');
    } else {
        chatbox.classList.remove('chatbox-collapsed');
        chatbox.classList.add('chatbox-expanded');
    }
    // Scroll to bottom after transition
    setTimeout(() => {
        chatbox.scrollTop = chatbox.scrollHeight;
    }, 300);
    }
    
    function sendMessage() {
        const input = document.getElementById('userInput');
        const message = input.value;
        if (!message.trim()) return;

        const chatbox = document.getElementById('chatbox');
        input.value = '';

        fetch(window.location.href, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'message=' + encodeURIComponent(message)
        })
        .then(async res => {
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            const text = await res.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Server response:', text);
                throw new Error('Invalid JSON response');
            }
        })
        .then(data => {
            console.log('Server response:', data);
            if (data && data.reply) {
                let formattedReply = data.reply
                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\n/g, '<br>');
                chatbox.innerHTML += `
                    <div class="user-message" style="margin: 10px 0; padding: 8px; border-radius: 8px; background-color: #f0f0f0;">
                        <strong>You:</strong> ${message}
                    </div>
                    <div class="bot-message" style="margin: 10px 0; padding: 8px; border-radius: 8px; background-color: #e3f2fd;">
                        <strong>Steve:</strong> ${formattedReply}
                    </div>
                `;
            } else {
                throw new Error('Invalid response format');
            }
            chatbox.scrollTop = chatbox.scrollHeight;
        })
        .catch(error => {
            console.error('Error:', error);
            chatbox.innerHTML += `<div class="bot"><strong>Steve:</strong> Sorry, there was an error processing your request. (${error.message})</div>`;
            chatbox.scrollTop = chatbox.scrollHeight;
        });
    }
</script>

<?php
function processUserQuery($message, $conn) {
    try {
        $message = strtolower(trim($message));
        
        // Handle follow-up "yes" responses
        if ($message === 'yes') {
            try {
                // Check the previous response type from session
                if (strpos($_SESSION['last_response'] ?? '', 'Would you like me to show you more products?') !== false) {
                    // Show random product
                    $stmt = $conn->prepare(
                        "SELECT ProductID, ProductName, Description, Price 
                        FROM product 
                        WHERE ProductID != ? 
                        ORDER BY RAND() 
                        LIMIT 1"
                    );
                    $lastProductId = $_SESSION['last_product_id'] ?? 0;
                    $stmt->bind_param('i', $lastProductId);
                } else {
                    // Show random category products
                    $stmt = $conn->prepare(
                        "SELECT p.ProductID, p.ProductName, p.Price, p.Description, c.CategoryName, c.CategoryID
                        FROM product p 
                        JOIN category c ON p.CategoryID = c.CategoryID 
                        WHERE c.CategoryID != ? AND c.CategoryID IN (
                            SELECT CategoryID FROM category
                        )
                        ORDER BY RAND() 
                        LIMIT 3"
                    );
                    $lastCategoryId = $_SESSION['last_category_id'] ?? 0;
                    $stmt->bind_param('i', $lastCategoryId);
                }

                if (!$stmt->execute()) {
                    throw new Exception("Failed to execute query: " . $stmt->error);
                }

                $result = $stmt->get_result();

                if ($result && $result->num_rows > 0) {
                    if (strpos($_SESSION['last_response'] ?? '', 'Would you like me to show you more products?') !== false) {
                        $row = $result->fetch_assoc();
                        $_SESSION['last_product_id'] = $row['ProductID'];
                        $reply = "Here's another product you might like!\n\n" .
                                 "🔸 **{$row['ProductName']}**\n" .
                                 "💰 Price: **$" . number_format($row['Price'], 2) . "**\n" .
                                 "📝 {$row['Description']}\n\n" .
                                 "Would you like me to show you more products?";
                    } else {
                        $products = [];
                        $categoryFound = "";
                        while ($row = $result->fetch_assoc()) {
                            $categoryFound = $row['CategoryName'];
                            $_SESSION['last_category_id'] = $row['CategoryID'];
                            $products[] = "🔸 **{$row['ProductName']}**\n" .
                                         "💰 Price: **$" . number_format($row['Price'], 2) . "**\n" .
                                         "📝 {$row['Description']}";
                        }
                        $reply = "Let me show you some products from our **{$categoryFound}** collection:\n\n" . 
                                 implode("\n\n", $products) . 
                                 "\n\nWould you like to see another category?";
                    }
                    $_SESSION['last_response'] = $reply;
                    $stmt->close();
                    return $reply;
                }
                
                $stmt->close();
                return "Let me know what kind of products you're interested in, and I'll help you find them!";
                
            } catch (Exception $e) {
                error_log("Error processing 'yes' response: " . $e->getMessage());
                throw $e;
            }
        }

        $message = str_replace(' and ', ' & ', $message);
        $message = str_replace(' & ', ' & ', $message); // Normalize multiple spaces
        error_log("Processing query: " . $message);

        // Test database connection
        $test = $conn->query("SELECT 1");
        if (!$test) {
            throw new Exception("Database connection test failed");
        }

        // First, check if the message matches a product name
        $stmt = $conn->prepare(
            "SELECT ProductName, Description, Price 
            FROM product 
            WHERE LOWER(ProductName) LIKE ?"
        );
        $searchTerm = '%' . $message . '%';
        $stmt->bind_param('s', $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $products = [];
            while ($row = $result->fetch_assoc()) {
                $products[] = "I found **{$row['ProductName']}** for you!\n" .
                             "💰 It's priced at **$" . number_format($row['Price'], 2) . "**\n" .
                             "📝 Here's what makes it special:\n" .
                             "{$row['Description']}";
            }
            $stmt->close();
            return implode("\n\n", $products) . "\n\nWould you like me to show you more products?";
        }
        $stmt->close();

        // Category name matching
        $stmt = $conn->prepare(
            "SELECT p.ProductName, p.Price, p.Description, c.CategoryName 
            FROM product p 
            JOIN category c ON p.CategoryID = c.CategoryID 
            WHERE LOWER(REPLACE(REPLACE(c.CategoryName, '&', 'and'), 'and', '&')) LIKE ?"
        );
        $searchTerm = '%' . str_replace(['and', '&'], '%', $message) . '%';
        $stmt->bind_param('s', $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $products = [];
            $categoryFound = "";
            while ($row = $result->fetch_assoc()) {
                $categoryFound = $row['CategoryName'];
                $products[] = "🔸 **{$row['ProductName']}**\n" .
                             "💰 Available for **$" . number_format($row['Price'], 2) . "**\n" .
                             "📝 What makes it great:\n" .
                             "{$row['Description']}";
            }
            $stmt->close();
            return "Great choice! I found some amazing products in our **{$categoryFound}** collection:\n\n" . 
                   implode("\n\n", $products) . 
                   "\n\nWould you like me to show you more products?";
        }
        $stmt->close();

        // Search in category descriptions
        $stmt = $conn->prepare(
            "SELECT DISTINCT p.ProductName, p.Price, p.Description, c.CategoryName 
            FROM product p 
            JOIN category c ON p.CategoryID = c.CategoryID 
            WHERE LOWER(c.CategoryDescription) LIKE ?"
        );
        $searchTerm = '%' . strtolower($message) . '%';
        $stmt->bind_param('s', $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $products = [];
            $categoryFound = "";
            while ($row = $result->fetch_assoc()) {
                $categoryFound = $row['CategoryName'];
                $products[] = "🔸 **{$row['ProductName']}**\n" .
                            "💰 Price: **$" . number_format($row['Price'], 2) . "**\n" .
                            "📝 {$row['Description']}";
            }
            $stmt->close();
            return "I found some products in our **{$categoryFound}** category that might interest you:\n\n" . 
                   implode("\n\n", $products) . 
                   "\n\nWould you like more details about any of these products?";
        }
        $stmt->close();

        // If no direct matches found, continue with existing query patterns

        // Category products query (e.g., "hair care product" or "skin care products")
        if (preg_match('/(.+?) products?$/', $message, $matches)) {
            $categoryName = '%' . str_replace(['and', '&'], '%', $matches[1]) . '%';
            $stmt = $conn->prepare(
                "SELECT p.ProductName, p.Price, p.Description, c.CategoryName 
                FROM product p 
                JOIN category c ON p.CategoryID = c.CategoryID 
                WHERE LOWER(REPLACE(REPLACE(c.CategoryName, '&', 'and'), 'and', '&')) LIKE ?"
            );
            $stmt->bind_param('s', $categoryName);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $products = [];
                $categoryFound = "";
                while ($row = $result->fetch_assoc()) {
                    $categoryFound = $row['CategoryName'];
                    $products[] = "<b>{$row['ProductName']}</b>\nPrice: <b>$" . number_format($row['Price'], 2) . "</b>\n" .
                                "Description: {$row['Description']}";
                }
                $stmt->close();
                return "Products in <b>{$categoryFound}</b> category:\n\n" . implode("\n\n", $products);
            }
            $stmt->close();
            return "Sorry, I couldn't find any products in that category.";
        }

        // Direct price query (e.g., "[product name] price")
        if (preg_match('/(.+?) price$/', $message, $matches)) {
            $productName = '%' . $matches[1] . '%';
            $stmt = $conn->prepare(
                "SELECT ProductName, Price 
                FROM product 
                WHERE ProductName LIKE ?"
            );
            $stmt->bind_param('s', $productName);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $products = [];
                while ($row = $result->fetch_assoc()) {
                    $products[] = "**{$row['ProductName']}** is priced at **$" . number_format($row['Price'], 2) . "**";
                }
                $stmt->close();
                return "Let me check those prices for you!\n" . implode("\n", $products);
            }
            return "I couldn't find that product in our database. Could you try rephrasing or checking the spelling?";
        }

        // Specific product info query
        if (preg_match('/(tell|show|what|about|info|information) (me )?about (.+)/', $message, $matches)) {
            $productName = '%' . $matches[3] . '%';
            $stmt = $conn->prepare(
                "SELECT ProductName, Description, Price 
                FROM product 
                WHERE ProductName LIKE ?"
            );
            $stmt->bind_param('s', $productName);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $reply = "I found exactly what you're looking for!\n\n";
                $reply .= "**{$row['ProductName']}**\n";
                $reply .= "💰 Price: **$" . number_format($row['Price'], 2) . "**\n";
                $reply .= "📝 Description: {$row['Description']}\n\n";
                $reply .= "Would you like to know anything else about this product?";
                $stmt->close();
                return $reply;
            }
            return "I couldn't find that specific product, but I'd be happy to help you find something similar! Could you tell me what you're looking for?";
        }

        // Products in specific category query
        if (preg_match('/(what|show|list|display) products? in (.+)( category)?/', $message, $matches)) {
            $categoryName = '%' . $matches[2] . '%';
            $stmt = $conn->prepare(
                "SELECT p.ProductName, p.Price 
                FROM product p 
                JOIN category c ON p.CategoryID = c.CategoryID 
                WHERE c.CategoryName LIKE ?"
            );
            $stmt->bind_param('s', $categoryName);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $products = [];
                while ($row = $result->fetch_assoc()) {
                    $products[] = "<b>{$row['ProductName']}</b> - <b>$" . number_format($row['Price'], 2) . "</b>";
                }
                $stmt->close();
                return "Products in this category:\n" . implode("\n", $products);
            }
            $stmt->close();
            return "Sorry, I couldn't find any products in that category.";
        }

        // Price query for specific product (e.g., "what is the price of...")
        if (preg_match('/(what|how much|price|cost).*(is|of|for) (.+)/', $message, $matches)) {
            $productName = '%' . $matches[3] . '%';
            $stmt = $conn->prepare(
                "SELECT ProductName, Price 
                FROM product 
                WHERE ProductName LIKE ?"
            );
            $stmt->bind_param('s', $productName);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $products = [];
                while ($row = $result->fetch_assoc()) {
                    $products[] = "{$row['ProductName']}: $" . number_format($row['Price'], 2);
                }
                $stmt->close();
                return "Price Information:\n" . implode("\n", $products);
            }
            $stmt->close();
            return "Sorry, I couldn't find the price for that product.";
        }

        // Category query
        if (strpos($message, 'categories') !== false || strpos($message, 'category') !== false) {
            $query = "SELECT CategoryName, CategoryDescription FROM category";
            $result = $conn->query($query);
            
            if (!$result) {
                throw new Exception("Category query failed: " . $conn->error);
            }
            
            if ($result->num_rows > 0) {
                $categories = [];
                while ($row = $result->fetch_assoc()) {
                    $categories[] = "🔹 **{$row['CategoryName']}**: {$row['CategoryDescription']}";
                }
                return "I'd be happy to show you our categories! Here's what we have:\n\n" . implode("\n", $categories);
            }
            return "I'm currently having trouble accessing our category list. Please try again in a moment!";
        }

        // Product query
        if (strpos($message, 'product') !== false) {
            $stmt = $conn->prepare("SELECT ProductName, Description, Price FROM product LIMIT 5");
            
            if (!$stmt) {
                throw new Exception("Product query preparation failed: " . $conn->error);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Product query execution failed: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $products = [];
                while ($row = $result->fetch_assoc()) {
                    $products[] = "{$row['ProductName']} ($" . number_format($row['Price'], 2) . ")\n{$row['Description']}";
                }
                $stmt->close();
                return "Here are the products I found:\n\n" . implode("\n\n", $products);
            }
            $stmt->close();
            return "No products found.";
        }

        return "Hi! I'm Steve, and I'm here to help you find the perfect beauty products! Here's what I can do:\n\n" .
               "🔍 Show all categories\n" .
               "📦 Find specific products\n" .
               "ℹ️ Get product details\n" .
               "🏷️ Show category products\n" .
               "💰 Check product prices\n\n" .
               "What would you like to know about?";

    } catch (Exception $e) {
        error_log("Error in processUserQuery: " . $e->getMessage());
        throw $e; // Re-throw the exception to be caught by the main handler
    }
}
?>

<?php require_once __DIR__ . '/_foot.php'; ?>
