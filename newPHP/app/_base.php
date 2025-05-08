<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';
$pdo->exec("SET time_zone = '+08:00'");

// ------------------------------
// 🔐 User Authentication
// ------------------------------
function registerUser($name, $email, $password, $phone, $gender, $dob)
{
    global $pdo;
    try {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $defaultPhoto = "/newPHP/app/uploads/defaultprofilephoto.jpg";
        $stmt = $pdo->prepare("INSERT INTO member (Name, Email, Password, PhoneNumber, Gender, DateOfBirth, ProfilePhoto) VALUES (?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$name, $email, $hashedPassword, $phone, $gender, $dob, $defaultPhoto]);
    } catch (PDOException $e) {
        error_log("Registration Error: " . $e->getMessage());
        return false;
    }
}

function loginUser($email, $password)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM member WHERE Email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
    
        if ($user && password_verify($password, $user['Password'])) {
            $_SESSION['member_id'] = $user['MemberID'];
            $_SESSION['name'] = $user['Name'];
            return true;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Login Error: " . $e->getMessage());
        return false;
    }
}    

function isLoggedIn()
{
    return isset($_SESSION['member_id']);
}

function getMemberProfilePhoto($memberId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT ProfilePhoto FROM member WHERE MemberID = ?");
        $stmt->execute([$memberId]);
        $result = $stmt->fetch();
        return $result && $result['ProfilePhoto'] ? $result['ProfilePhoto'] : 'defaultprofilephoto.jpg';
    } catch (PDOException $e) {
        error_log("Error getting member profile photo: " . $e->getMessage());
        return 'default-profile.png';
    }
}

function logoutUser() {
    session_unset();
    session_destroy();
    header("Location: ../index.php");
    exit();
}

function redirectIfNotLoggedIn() {
    if (!isset($_SESSION['member_id'])) {
        header("Location: /../auth/login.php");
        exit();
    }
}

function requireLogin($role = 'member') {
    if ($role === 'staff') {
        if (!isset($_SESSION['staff_id'])) {
            header("Location: staffLogin.php");

            exit();
        }
    } elseif ($role === 'member') {
        if (!isset($_SESSION['member_id'])) {
            header("Location: /../auth/login.php");
            exit();
        }
    }
}

// ------------------------------
// 📦 Product List + Filter
// ------------------------------
function getProducts($filters = [])
{
    global $pdo;
    $query = "SELECT p.*, c.CategoryName 
            FROM product p 
            LEFT JOIN category c ON p.CategoryID = c.CategoryID 
            WHERE 1=1";
    $params = [];

    if (!empty($filters['category'])) {
        $query .= " AND p.CategoryID = ?";
        $params[] = $filters['category'];
    }

    if (!empty($filters['search'])) {
        $query .= " AND (p.ProductName LIKE ? OR p.Description LIKE ?)";
        $params[] = "%" . $filters['search'] . "%";
        $params[] = "%" . $filters['search'] . "%";
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getCategories()
{
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM category ORDER BY CategoryName");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Get Categories Error: " . $e->getMessage());
        return [];
    }
}

// ------------------------------
// 📦 Product Detail
// ------------------------------
function getProduct($product_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT p.*, c.CategoryName 
                        FROM product p 
                        LEFT JOIN category c ON p.CategoryID = c.CategoryID 
                        WHERE p.ProductID = ?");
    $stmt->execute([$product_id]);
    return $stmt->fetch();
}

// ------------------------------
// 🛒 Cart (Add/Fetch)
// ------------------------------
function handleAddToCart($product_id, $quantity)
{
    if (!isLoggedIn()) {
        $_SESSION['error'] = "Please login to add items to cart.";
        header("Location: ../auth/login.php");
        exit();
    }

    global $pdo;

    // Check if product exists and has enough stock
    $stmt = $pdo->prepare("SELECT Quantity, ProductName FROM product WHERE ProductID = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        $_SESSION['error'] = "Product not found.";
        header("Location: all_product.php");
        exit();
    }

    if ($product['Quantity'] < $quantity) {
        $_SESSION['error'] = "Sorry, only " . $product['Quantity'] . " items available in stock.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    try {
        $pdo->beginTransaction();

        // Get or create active cart
        $stmt = $pdo->prepare("SELECT CartID FROM cart WHERE MemberID = ? AND CartStatus = 'Active'");
        $stmt->execute([$_SESSION['member_id']]);
        $cart = $stmt->fetch();

        if (!$cart) {
            $stmt = $pdo->prepare("INSERT INTO cart (MemberID, CreatedAt, CartStatus) VALUES (?, NOW(), 'Active')");
            $stmt->execute([$_SESSION['member_id']]);
            $cart_id = $pdo->lastInsertId();
        } else {
            $cart_id = $cart['CartID'];
        }

        // Check if product already exists in cart
        $stmt = $pdo->prepare("SELECT CartItemID, Quantity FROM cartitem WHERE CartID = ? AND ProductID = ?");
        $stmt->execute([$cart_id, $product_id]);
        $existing_item = $stmt->fetch();

        if ($existing_item) {
            // Update quantity if product already in cart
            $new_quantity = $existing_item['Quantity'] + $quantity;
            if ($new_quantity > $product['Quantity']) {
                $_SESSION['error'] = "Cannot add more items. Cart would exceed available stock.";
                $pdo->rollBack();
                header("Location: " . $_SERVER['HTTP_REFERER']);
                exit();
            }
            $stmt = $pdo->prepare("UPDATE cartitem SET Quantity = ? WHERE CartItemID = ?");
            $stmt->execute([$new_quantity, $existing_item['CartItemID']]);
        } else {
            // Add new cart item
            $stmt = $pdo->prepare("INSERT INTO cartitem (CartID, ProductID, Quantity) VALUES (?, ?, ?)");
            $stmt->execute([$cart_id, $product_id, $quantity]);
        }

        $pdo->commit();
        $_SESSION['success'] = $product['ProductName'] . " has been added to your cart.";
        header("Location: ../order/cart.php");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Add to Cart Error: " . $e->getMessage());
        $_SESSION['error'] = "Error adding item to cart. Please try again.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
}

// ------------------------------
// 🛒 Cart Management (Logic)
// ------------------------------
function getCartItems($pdo)
{
    $stmt = $pdo->prepare("SELECT ci.*, p.ProductName, p.Price, p.ProdIMG1 
                        FROM cartitem ci 
                        JOIN product p ON ci.ProductID = p.ProductID 
                        JOIN cart c ON ci.CartID = c.CartID 
                        WHERE c.MemberID = ? AND c.CartStatus = 'Active'");
    $stmt->execute([$_SESSION['member_id']]);
    return $stmt->fetchAll();
}

function redirectIfCartIsEmpty($cart_items) {
    if (empty($cart_items)) {
        header("Location: cart.php");
        exit();
    }
}

function updateCartItem($pdo, $cart_item_id, $quantity)
{
    if ($quantity > 0) {
        $stmt = $pdo->prepare("UPDATE cartitem SET Quantity = ? WHERE CartItemID = ?");
        $stmt->execute([$quantity, $cart_item_id]);
    } else {
        removeCartItem($pdo, $cart_item_id);
    }
}

function removeCartItem($pdo, $cart_item_id)
{
    $stmt = $pdo->prepare("DELETE FROM cartitem WHERE CartItemID = ?");
    $stmt->execute([$cart_item_id]);
}

function calculateCartTotal($cart_items)
{
    $total = 0;
    foreach ($cart_items as $item) {
        $total += $item['Price'] * $item['Quantity'];
    }
    return $total;
}

function handleCartActions($pdo)
{
    if (!isset($_SESSION['member_id'])) {
        return;
    }

    // Handle remove item
    if (isset($_POST['remove_item'])) {
        $cartItemId = $_POST['remove_item'];
        removeCartItem($pdo, $cartItemId);
    }

    // Handle update cart quantities
    if (isset($_POST['update_cart']) && isset($_POST['quantity'])) {
        foreach ($_POST['quantity'] as $cartItemId => $quantity) {
            updateCartItemQuantity($pdo, $cartItemId, $quantity);
        }
    }

    // Handle checkout
    if (isset($_POST['checkout']) && isset($_POST['selected_items'])) {
        processCheckout($pdo, $_POST['selected_items']);
    }
}

function updateCartItemQuantity($pdo, $cartItemId, $quantity) {
    try {
        // Start transaction
        $pdo->beginTransaction();

        // Get product information and current cart item
        $stmt = $pdo->prepare("
            SELECT p.ProductID, p.Quantity as StockQuantity, ci.Quantity as CartQuantity 
            FROM cartitem ci 
            JOIN product p ON ci.ProductID = p.ProductID 
            WHERE ci.CartItemID = ?
        ");
        $stmt->execute([$cartItemId]);
        $item = $stmt->fetch();

        if (!$item) {
            throw new Exception("Cart item not found");
        }

        // Validate quantity
        $quantity = max(1, min((int)$quantity, $item['StockQuantity']));

        // Update cart item quantity
        $stmt = $pdo->prepare("UPDATE cartitem SET Quantity = ? WHERE CartItemID = ?");
        $stmt->execute([$quantity, $cartItemId]);

        // Commit transaction
        $pdo->commit();

    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $_SESSION['error'] = "Failed to update cart quantity.";
    }
}

function processCheckout($pdo, $selectedItems) {
    error_log('Selected items: ' . print_r($selectedItems, true));
    try {
        // Start transaction
        $pdo->beginTransaction();

        // Handle both single item (string) and multiple items (array)
        if (!is_array($selectedItems)) {
            $selectedItems = [$selectedItems]; // Convert single ID to array
        }

        // Get shipping fee from POST if available
        $shippingFee = isset($_POST['shipping_fee']) ? floatval($_POST['shipping_fee']) : 0;

        // Get voucher information
        $voucherId = $_POST['voucher_id'] ?? null;
        $voucherDiscount = isset($_POST['voucher_discount']) ? floatval($_POST['voucher_discount']) : 0;

        // Validate stock availability for all selected items
        $placeholders = str_repeat('?,', count($selectedItems) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT ci.CartItemID, ci.ProductID, ci.Quantity as CartQuantity, 
                   p.Quantity as StockQuantity, p.ProductName, p.Price 
            FROM cartitem ci 
            JOIN product p ON ci.ProductID = p.ProductID 
            WHERE ci.CartItemID IN ($placeholders)
        ");
        $stmt->execute($selectedItems);
        $items = $stmt->fetchAll();

        // Calculate order total with discount applied
        $subtotal = 0;
        foreach ($items as $item) {
            if ($item['CartQuantity'] > $item['StockQuantity']) {
                throw new Exception("Not enough stock for {$item['ProductName']}");
            }
            $subtotal += $item['Price'] * $item['CartQuantity'];
        }

        // Apply voucher discount
        if ($voucherDiscount > 0) {
            $discountAmount = $subtotal * ($voucherDiscount / 100);
            $subtotal -= $discountAmount;
        }

        // Add shipping fee
        $orderTotal = $subtotal + $shippingFee;

        // Create order with voucher ID
        $stmt = $pdo->prepare("
            INSERT INTO orders (MemberID, OrderDate, OrderStatus, OrderTotalAmount, VoucherID, ShippingFee) 
            VALUES (?, NOW(), 'Pending', ?, ?, ?)
        ");
        $stmt->execute([$_SESSION['member_id'], $orderTotal, $voucherId, $shippingFee]);
        $orderId = $pdo->lastInsertId();

        // Create order items and update stock
        foreach ($items as $item) {
            // Add to order items
            $stmt = $pdo->prepare("
                INSERT INTO orderitem (OrderID, ProductID, Quantity, OrderItemPrice) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $orderId, 
                $item['ProductID'], 
                $item['CartQuantity'],
                $item['Price']
            ]);

            // Update stock
            $stmt = $pdo->prepare("
                UPDATE product 
                SET Quantity = Quantity - ? 
                WHERE ProductID = ?
            ");
            $stmt->execute([$item['CartQuantity'], $item['ProductID']]);

            // Remove from cart
            $stmt = $pdo->prepare("DELETE FROM cartitem WHERE CartItemID = ?");
            $stmt->execute([$item['CartItemID']]);
        }

        // Commit transaction
        $pdo->commit();

        // Redirect to order confirmation
        header("Location: ../order/order_confirmation.php?order_id=" . $orderId);
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
        header("Location: ../order/cart.php");
        exit();
    }
}

// ------------------------------
// 🧾 Order Creation
// ------------------------------
function createOrder($cartItems)
{
    if (!isLoggedIn()) return false;

    global $pdo;
    try {
        $pdo->beginTransaction();

        $total = 0;
        foreach ($cartItems as $item) {
            $total += $item['Price'] * $item['Quantity'];
        }

        $stmt = $pdo->prepare("INSERT INTO `order` (MemberID, OrderTotalAmount) VALUES (?, ?)");
        $stmt->execute([$_SESSION['member_id'], $total]);
        $orderId = $pdo->lastInsertId();

        foreach ($cartItems as $item) {
            $stmt = $pdo->prepare("INSERT INTO orderitem (OrderID, ProductID, OrderItemQTY, OrderItemPrice) VALUES (?, ?, ?, ?)");
            $stmt->execute([$orderId, $item['ProductID'], $item['Quantity'], $item['Price']]);
        }

        $stmt = $pdo->prepare("UPDATE cart SET CartStatus = 'Inactive' WHERE MemberID = ? AND CartStatus = 'Active'");
        $stmt->execute([$_SESSION['member_id']]);

        $pdo->commit();
        return $orderId;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Create Order Error: " . $e->getMessage());
        return false;
    }
}

// ------------------------------
// 🔒 Admin Auth & Dashboard Info
// ------------------------------
function checkIfStaffLoggedIn()
{
    if (!isset($_SESSION['staff_id'])) {
        header("Location: ../auth/staffLogin.php");
        exit();
    }
}
function requireAdminPage() {
    require_once __DIR__ . '/../_base.php';
    requireLogin('staff');
}
function handleStaffLogin(PDO $pdo)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        // 🔍 1. Check staff table
        $stmt = $pdo->prepare("SELECT * FROM staff WHERE StaffUsername = ?");
        $stmt->execute([$username]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($staff) {
            // 🛡️ Block inactive accounts
            if ($staff['StaffStatus'] === 'Inactive') {
                $_SESSION['staff_login_error'] = "Your staff account is inactive. Please contact admin.";
                header("Location: staffLogin.php");
                exit();
            }

            // 🔐 Verify password
            if (password_verify($password, $staff['Password'])) {
                $_SESSION['staff_id'] = $staff['StaffUsername'];
                $_SESSION['staff_name'] = $staff['StaffName'] ?? $staff['StaffUsername'];
                $_SESSION['is_manager'] = false;

                // 🔁 First-time setup redirect
                if (!empty($staff['FirstTimeLogin'])) {
                    header("Location: ../auth/staffSetup.php");
                } else {
                    header("Location: ../admin/adminindex.php");
                }
                exit();
            }
        }

        // 🔍 2. Check manager table if not found in staff
        $stmt = $pdo->prepare("SELECT * FROM manager WHERE ManagerUsername = ?");
        $stmt->execute([$username]);
        $manager = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($manager && password_verify($password, $manager['Password'])) {
            $_SESSION['staff_id'] = $manager['ManagerUsername'];
            $_SESSION['staff_name'] = $manager['ManagerName'] ?? $manager['ManagerUsername'];
            $_SESSION['is_manager'] = true;

            header("Location: ../admin/adminindex.php");
            exit();
        }

        // ❌ Invalid credentials fallback
        $_SESSION['staff_login_error'] = "Invalid username or password.";
        header("Location: staffLogin.php");
        exit();

    } catch (PDOException $e) {
        error_log("Staff login error: " . $e->getMessage());
        $_SESSION['staff_login_error'] = "Login system error. Contact admin.";
        header("Location: staffLogin.php");
        exit();
    }
}


function isManager($staff_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM manager WHERE ManagerUsername = ?");
    $stmt->execute([$staff_id]);
    return $stmt->fetch() ? true : false;
}
function getDisplayName()
{
    return $_SESSION['staff_name'] ?? $_SESSION['staff_id'] ?? 'Admin';
}

function getTotalMembers()
{
    global $pdo;
    return $pdo->query("SELECT COUNT(*) FROM member")->fetchColumn();
}

function getTotalProducts()
{
    global $pdo;
    return $pdo->query("SELECT COUNT(*) FROM product")->fetchColumn();
}

function getPendingOrders($pdo) {
    $query = "SELECT o.OrderID, o.OrderDate, o.OrderTotalAmount, m.Name as CustomerName, 
              COUNT(oi.OrderItemID) as ItemCount
              FROM orders o 
              JOIN member m ON o.MemberID = m.MemberID
              LEFT JOIN orderitem oi ON o.OrderID = oi.OrderID
              WHERE o.OrderStatus = 'Pending'
              GROUP BY o.OrderID
              ORDER BY o.OrderDate DESC
              LIMIT 5";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll();
}
function getTotalSales($pdo)
{
    $result = $pdo->query("SELECT COALESCE(SUM(OrderTotalAmount), 0) FROM orders WHERE OrderStatus = 'Completed'")->fetchColumn();
    return $result;
}

// ------------------------------
// 👥 Member Management (Admin)
// ------------------------------
function fetchAllMembers($sort = 'CreatedAt', $order = 'desc')
{
    global $pdo;

    $allowedSortFields = ['MemberID', 'Name', 'Email', 'PhoneNumber', 'Gender', 'DateOfBirth', 'CreatedAt'];
    $allowedOrder = ['asc', 'desc'];

    if (!in_array($sort, $allowedSortFields)) $sort = 'CreatedAt';
    if (!in_array(strtolower($order), $allowedOrder)) $order = 'desc';

    $stmt = $pdo->prepare("SELECT * FROM member ORDER BY $sort $order");
    $stmt->execute();
    return $stmt->fetchAll();
}    

function deleteMember($id)
{
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM member WHERE MemberID = ?");
    $stmt->execute([$id]);
}
function editMember($data, $profilePhoto)
{
    global $pdo;
    $id       = $data['member_id'];
    $name     = $data['name'];
    $email    = $data['email'];
    $phone    = $data['phone'];
    $status   = $data['status'];

    if (!empty($data['password'])) {
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE member SET Name = ?, Email = ?, PhoneNumber = ?, ProfilePhoto = ?, MembershipStatus = ?, Password = ? WHERE MemberID = ?");
        $stmt->execute([$name, $email, $phone, $profilePhoto, $status, $password, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE member SET Name = ?, Email = ?, PhoneNumber = ?, ProfilePhoto = ?, MembershipStatus = ? WHERE MemberID = ?");
        $stmt->execute([$name, $email, $phone, $profilePhoto, $status, $id]);
    }
}

function handleDeleteMember()
{
    if (isset($_GET['delete'])) {
        deleteMember($_GET['delete']);
        header("Location: memberList.php");
        exit();
    }
}    

function handleEditMember()
{
    global $pdo;

    if (isset($_POST['edit_member'])) {
        $profilePhoto = null;

        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $targetDir = "../uploads/";
            $fileName = uniqid() . "_" . basename($_FILES['profile_photo']['name']);
            $targetFile = $targetDir . $fileName;
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $targetFile)) {
                $profilePhoto = $fileName;
            }
        } else {
            $stmt = $pdo->prepare("SELECT ProfilePhoto FROM member WHERE MemberID = ?");
            $stmt->execute([$_POST['member_id']]);
            $profilePhoto = $stmt->fetchColumn();
        }

        editMember($_POST, $profilePhoto);
        header("Location: memberList.php");
        exit();
    }
}

// ------------------------------
// 🛠️ Admin Product Management
// ------------------------------
function fetchAllProducts()
{
    global $pdo;
    $stmt = $pdo->query("SELECT p.*, c.CategoryName FROM product p LEFT JOIN category c ON p.CategoryID = c.CategoryID ORDER BY ProductID DESC");
    return $stmt->fetchAll();
}
function fetchAllCategories()
{
    global $pdo;
    return $pdo->query("SELECT * FROM category ORDER BY CategoryName")->fetchAll();
}
function handleDeleteProduct()
{
    global $pdo;
    if (isset($_GET['delete'])) {
        $stmt = $pdo->prepare("DELETE FROM product WHERE ProductID = ?");
        $stmt->execute([$_GET['delete']]);
        header("Location: productList.php");
        exit();
    }
}
function handleEditProduct()
{
    global $pdo;
    if (isset($_POST['edit_product'])) {
        $id = $_POST['product_id'];
        $category = $_POST['category'];
        $name = $_POST['name'];
        $desc = $_POST['description'];
        $price = $_POST['price'];
        $qty = $_POST['quantity'];

        // Load old image names
        $stmt = $pdo->prepare("SELECT ProdIMG1, ProdIMG2, ProdIMG3 FROM product WHERE ProductID = ?");
        $stmt->execute([$id]);
        $oldImages = $stmt->fetch();

        // Handle uploads
        $img1 = uploadImageOrKeep('ProdIMG1', $oldImages['ProdIMG1']);
        $img2 = uploadImageOrKeep('ProdIMG2', $oldImages['ProdIMG2']);
        $img3 = uploadImageOrKeep('ProdIMG3', $oldImages['ProdIMG3']);

        $stmt = $pdo->prepare("UPDATE product SET CategoryID=?, ProductName=?, Description=?, Price=?, Quantity=?, ProdIMG1=?, ProdIMG2=?, ProdIMG3=? WHERE ProductID=?");
        $stmt->execute([$category, $name, $desc, $price, $qty, $img1, $img2, $img3, $id]);

        header("Location: productList.php");
        exit();
    }
}
function handleAddProduct()
{
    global $pdo;
    if (isset($_POST['add_product'])) {
        $category = $_POST['category'];
        $name = $_POST['name'];
        $desc = $_POST['description'];
        $price = $_POST['price'];
        $qty = $_POST['quantity'];

        $img1 = uploadImageOrKeep('ProdIMG1');
        $img2 = uploadImageOrKeep('ProdIMG2');
        $img3 = uploadImageOrKeep('ProdIMG3');

        $stmt = $pdo->prepare("INSERT INTO product (CategoryID, ProductName, Description, Price, Quantity, ProdIMG1, ProdIMG2, ProdIMG3) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$category, $name, $desc, $price, $qty, $img1, $img2, $img3]);

        header("Location: productList.php");
        exit();
    }
}
// Helper to upload image or keep old if not uploaded
function uploadImageOrKeep($inputName, $oldValue = null)
{
    if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
        $fileName = uniqid() . "_" . basename($_FILES[$inputName]['name']);
        $targetFile = "../uploads/" . $fileName;
        move_uploaded_file($_FILES[$inputName]['tmp_name'], $targetFile);
        return $fileName;
    }
    return $oldValue ?? null;
}

// ------------------------------
// ✅ Order Confirmation
// ------------------------------
function redirectIfInvalidOrder($pdo, $order_id) {
    if (!isset($_SESSION['member_id']) || !isset($order_id)) {
        header("Location: /../index.php");
        exit();
    }

    $stmt = $pdo->prepare("SELECT o.*, m.Name, m.Email, m.PhoneNumber 
                        FROM `orders` o 
                        JOIN member m ON o.MemberID = m.MemberID 
                        WHERE o.OrderID = ? AND o.MemberID = ?");
    $stmt->execute([$order_id, $_SESSION['member_id']]);
    $order = $stmt->fetch();

    if (!$order) {
        header("Location: index.php");
        exit();
    }

    return $order;
}

function getOrderItems($pdo, $order_id) {
    $stmt = $pdo->prepare("SELECT oi.*, p.ProductName, p.ProdIMG1, oi.OrderItemPrice as Price 
                        FROM orderitem oi 
                        JOIN product p ON oi.ProductID = p.ProductID 
                        WHERE oi.OrderID = ?");
    $stmt->execute([$order_id]);
    return $stmt->fetchAll();
}

// ------------------------------
// 💰 Payment Processing
// ------------------------------
function createOrderPayment($order_id, $payment_method) 
{
    global $pdo;
    
    try {
        // Check if payment already exists for this order
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM payment WHERE OrderID = ?");
        $checkStmt->execute([$order_id]);
        $paymentExists = $checkStmt->fetchColumn() > 0;
        
        if (!$paymentExists) {
            // Get order total amount
            $orderStmt = $pdo->prepare("SELECT OrderTotalAmount FROM orders WHERE OrderID = ?");
            $orderStmt->execute([$order_id]);
            $orderTotal = $orderStmt->fetchColumn();
            
            // If OrderTotalAmount is not set, calculate it from order items
            if (!$orderTotal) {
                $itemsStmt = $pdo->prepare("
                    SELECT oi.*, p.Price 
                    FROM orderitem oi 
                    JOIN product p ON oi.ProductID = p.ProductID 
                    WHERE oi.OrderID = ?
                ");
                $itemsStmt->execute([$order_id]);
                $items = $itemsStmt->fetchAll();
                
                if (empty($items)) {
                    throw new Exception("Order not found or has no items");
                }
                
                $orderTotal = 0;
                foreach ($items as $item) {
                    // Get price and quantity, handling different possible column names
                    $price = isset($item['OrderItemPrice']) ? $item['OrderItemPrice'] : $item['Price'];
                    $quantity = isset($item['Quantity']) ? $item['Quantity'] : 
                               (isset($item['OrderItemQTY']) ? $item['OrderItemQTY'] : 1);
                    $orderTotal += $price * $quantity;
                }
                
                // Update the order total in the database
                $updateStmt = $pdo->prepare("UPDATE orders SET OrderTotalAmount = ? WHERE OrderID = ?");
                $updateStmt->execute([$orderTotal, $order_id]);
            }
            
            // Create payment record
            $stmt = $pdo->prepare("INSERT INTO payment (OrderID, PaymentDate, PaymentMethod, AmountPaid, PaymentStatus) 
                                  VALUES (?, NOW(), ?, ?, 'Pending')");
            $stmt->execute([$order_id, $payment_method, $orderTotal]);
            
            return $pdo->lastInsertId();
        }
        
        return true; // Payment already exists
    } catch (PDOException $e) {
        error_log("Create Payment Error: " . $e->getMessage());
        return false;
    }
}

function getPaymentDetails($pdo, $order_id, $explicit_payment_method = null) {
    // Use explicit method if provided, otherwise fallback to POST/session
    $payment_method = $explicit_payment_method;
    
    if (empty($payment_method)) {
        $payment_method = $_POST['payment_method'] ?? $_SESSION['last_payment_method'] ?? 'Bank Transfer';
    }
    
    // Check if payment record already exists
    $checkStmt = $pdo->prepare("SELECT * FROM payment WHERE OrderID = ?");
    $checkStmt->execute([$order_id]);
    $existingPayment = $checkStmt->fetch();
    
    if ($existingPayment) {
        // Always update with the explicit payment method if provided
        if (!empty($payment_method) && $payment_method != 'Bank Transfer') {
            $updateStmt = $pdo->prepare("UPDATE payment SET PaymentMethod = ? WHERE OrderID = ?");
            $updateStmt->execute([$payment_method, $order_id]);
        }
    } else {
        // Create payment if it doesn't exist
        createOrderPayment($order_id, $payment_method);
    }
    
    // Return updated payment details
    $stmt = $pdo->prepare("SELECT * FROM payment WHERE OrderID = ?");
    $stmt->execute([$order_id]);
    return $stmt->fetch();
}



// ------------------------------
// 👤 Member Settings Functions
// ------------------------------

function updateMemberPhoto($memberId, $file) {
    global $pdo;
    if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
        $targetDir = __DIR__ . '/uploads/';
        $fileName = uniqid() . '_' . basename($file['name']);
        $targetFile = $targetDir . $fileName;
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        
        if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                try {
                    $stmt = $pdo->prepare("UPDATE member SET ProfilePhoto=? WHERE MemberID=?");
                    $stmt->execute([$fileName, $memberId]);
                    return ["success" => "Profile photo updated successfully!"];
                } catch (PDOException $e) {
                    error_log("Photo Update Error: " . $e->getMessage());
                    return ["error" => "Error updating profile photo"];
                }
            }
        }
    }
    return ["error" => "Invalid file upload"];
}

function updateMemberAddress($memberId, $address) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE member SET address = ? WHERE MemberID = ?");
        $stmt->execute([$address, $memberId]);
        return ["success" => "Address updated successfully!"];
    } catch (PDOException $e) {
        error_log("Address Update Error: " . $e->getMessage());
        return ["error" => "Error updating address"];
    }
}

function updateMemberDetails($memberId, $data) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE member SET Name=?, Email=?, PhoneNumber=?, Gender=?, DateOfBirth=? WHERE MemberID=?");
        $stmt->execute([
            $data['name'] ?? '',
            $data['email'] ?? '',
            $data['phone'] ?? '',
            $data['gender'] ?? '',
            $data['dob'] ?? '',
            $memberId
        ]);
        return ["success" => "Profile updated successfully!"];
    } catch (PDOException $e) {
        error_log("Profile Update Error: " . $e->getMessage());
        return ["error" => "Error updating profile"];
    }
}

function updateMemberPassword($memberId, $currentPassword, $newPassword, $confirmPassword) {
    global $pdo;
    if ($newPassword !== $confirmPassword) {
        return ["error" => "New passwords do not match"];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT Password FROM member WHERE MemberID = ?");
        $stmt->execute([$memberId]);
        $member = $stmt->fetch();
        
        if (!$member || !password_verify($currentPassword, $member['Password'])) {
            return ["error" => "Current password is incorrect"];
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE member SET Password = ? WHERE MemberID = ?");
        $stmt->execute([$hashedPassword, $memberId]);
        return ["success" => "Password changed successfully!"];
    } catch (PDOException $e) {
        error_log("Password Update Error: " . $e->getMessage());
        return ["error" => "Error changing password"];
    }
}

function getMemberDetails($memberId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM member WHERE MemberID = ?");
        $stmt->execute([$memberId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get Member Details Error: " . $e->getMessage());
        return null;
    }
}

// 🔍 Get member by email
function findMemberByEmail($email)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM member WHERE Email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

// 🔐 Insert password reset token
function createPasswordResetToken($memberId) {
    global $pdo;
    $token = bin2hex(random_bytes(16));
    $expire = date('Y-m-d H:i:s', strtotime('+24 hours'));
    try {
        $stmt = $pdo->prepare("INSERT INTO token (id, expire, user_id) VALUES (?, ?, ?)");
        $stmt->execute([$token, $expire, $memberId]);
    } catch (PDOException $e) {
        echo "<strong>DB INSERT ERROR:</strong> " . $e->getMessage();
        exit;
    }

    return $token;
}
// 🧪 Validate token and return member
function getMemberByResetToken($token) {
    global $pdo;

    // Set matching timezone to prevent false expiry
    date_default_timezone_set('Asia/Kuala_Lumpur');

    $stmt = $pdo->prepare("
        SELECT t.*, m.* 
        FROM token t 
        JOIN member m ON t.user_id = m.MemberID 
        WHERE t.id = ? 
          AND t.expire > NOW()
        LIMIT 1
    ");
    $stmt->execute([$token]);
    return $stmt->fetch();
}

// 🔄 Reset member password by token
function resetPasswordByToken($token, $newPassword) {
    global $pdo;

    $member = getMemberByResetToken($token);
    if (!$member) return false;

    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE member SET Password = ? WHERE MemberID = ?");
    $stmt->execute([$hashed, $member['MemberID']]);

    $stmt = $pdo->prepare("DELETE FROM token WHERE id = ?");
    $stmt->execute([$token]);

    return true;
}

// ------------------------------
// 🎟️ Voucher Management (Admin)
// ------------------------------
function fetchAllVouchers($sort = 'CreatedAt', $order = 'desc')
{
    global $pdo;
    $allowedSortFields = ['VoucherID', 'Code', 'Discount', 'ExpiryDate', 'Status', 'CreatedAt', 'UpdatedAt'];
    $allowedOrder = ['asc', 'desc'];
    if (!in_array($sort, $allowedSortFields)) $sort = 'CreatedAt';
    if (!in_array(strtolower($order), $allowedOrder)) $order = 'desc';
    $stmt = $pdo->prepare("SELECT * FROM voucher ORDER BY $sort $order");
    $stmt->execute();
    return $stmt->fetchAll();
}

function addVoucher($data)
{
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO voucher (Code, Discount, ExpiryDate, Description, Status) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([
        $data['code'],
        $data['discount'],
        $data['expiry_date'],
        $data['description'],
        $data['status'] ?? 'Active'
    ]);
}

function editVoucher($id, $data)
{
    global $pdo;
    $stmt = $pdo->prepare("UPDATE voucher SET Code=?, Discount=?, ExpiryDate=?, Description=?, Status=? WHERE VoucherID=?");
    return $stmt->execute([
        $data['code'],
        $data['discount'],
        $data['expiry_date'],
        $data['description'],
        $data['status'],
        $id
    ]);
}

function deleteVoucher($id)
{
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM voucher WHERE VoucherID = ?");
    $stmt->execute([$id]);
}

function getVoucherById($id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM voucher WHERE VoucherID = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Order Management Functions
function updateOrderStatus($pdo, $orderId, $newStatus) {
    // Validate status
    $validStatuses = ['Pending', 'Completed', 'Cancelled'];
    if (!in_array($newStatus, $validStatuses)) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE orders SET OrderStatus = ? WHERE OrderID = ?");
        $stmt->execute([$newStatus, $orderId]);
        return true;
    } catch (PDOException $e) {
        error_log("Error updating order status: " . $e->getMessage());
        return false;
    }
}

function getOrders($pdo, $where = '', $params = [], $sort = 'OrderID', $dir = 'desc', $limit = 10, $offset = 0) {
    $query = "SELECT o.*, m.Name as CustomerName 
              FROM orders o 
              LEFT JOIN member m ON o.MemberID = m.MemberID 
              $where 
              ORDER BY $sort $dir 
              LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    return $stmt->fetchAll();
}

function getOrderDetails($pdo, $orderId) {
    $orderQuery = "SELECT o.*, m.Name as CustomerName, m.Email, m.PhoneNumber as Phone, m.Address
                   FROM orders o
                   LEFT JOIN member m ON o.MemberID = m.MemberID
                   WHERE o.OrderID = ?";
    $orderStmt = $pdo->prepare($orderQuery);
    $orderStmt->execute([$orderId]);
    return $orderStmt->fetch();
}

function countOrders($pdo, $where = '', $params = []) {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o 
                               LEFT JOIN member m ON o.MemberID = m.MemberID 
                               $where");
    $countStmt->execute($params);
    return $countStmt->fetchColumn();
}

function buildOrderSortLink($column, $label) {
    $currentSort = $_GET['sort'] ?? 'OrderID';
    $currentDir = $_GET['order'] ?? 'desc';
    $nextDir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';
    $arrow = ($currentSort === $column) ? ($currentDir === 'asc' ? '↑' : '↓') : '';
    return "<a href='?sort=$column&order=$nextDir'>" . htmlspecialchars($label) . " $arrow</a>";
}

function getTotalStaff()
{
    global $pdo;
    return $pdo->query("SELECT COUNT(*) FROM staff")->fetchColumn();
}

?>
