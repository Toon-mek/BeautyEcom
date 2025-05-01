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
        $stmt = $pdo->prepare("INSERT INTO member (Name, Email, Password, PhoneNumber, Gender, DateOfBirth) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$name, $email, $hashedPassword, $phone, $gender, $dob]);
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

function logoutUser() {
    session_unset();
    session_destroy();
    header("Location: /../index.php");
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
            header("Location: /../auth/staffLogin.php");
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
    try {
        // Start transaction
        $pdo->beginTransaction();

        // Validate stock availability for all selected items
        $stmt = $pdo->prepare("
            SELECT ci.CartItemID, ci.ProductID, ci.Quantity as CartQuantity, 
                   p.Quantity as StockQuantity, p.ProductName 
            FROM cartitem ci 
            JOIN product p ON ci.ProductID = p.ProductID 
            WHERE ci.CartItemID IN (" . str_repeat('?,', count($selectedItems) - 1) . "?)
        ");
        $stmt->execute($selectedItems);
        $items = $stmt->fetchAll();

        foreach ($items as $item) {
            if ($item['CartQuantity'] > $item['StockQuantity']) {
                throw new Exception("Not enough stock for {$item['ProductName']}");
            }
        }

        // Create order
        $stmt = $pdo->prepare("
            INSERT INTO orders (MemberID, OrderDate, OrderStatus) 
            VALUES (?, NOW(), 'Pending')
        ");
        $stmt->execute([$_SESSION['member_id']]);
        $orderId = $pdo->lastInsertId();

        // Create order items and update stock
        foreach ($items as $item) {
            // Add to order items
            $stmt = $pdo->prepare("
                INSERT INTO orderitem (OrderID, ProductID, Quantity) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$orderId, $item['ProductID'], $item['CartQuantity']]);

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
        header("Location: ../order/confirmation.php?order_id=" . $orderId);
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
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
function handleStaffLogin()
{
    global $pdo;

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Check in staff table
        $stmt = $pdo->prepare("SELECT * FROM staff WHERE StaffUsername = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // If not found, check manager table
        if (!$user) {
            $stmt = $pdo->prepare("SELECT * FROM manager WHERE ManagerUsername = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
        }

        if ($user && password_verify($password, $user['Password'])) {
            $_SESSION['staff_id'] = $username;
            $_SESSION['staff_name'] = $user['StaffName'] ?? $user['ManagerName'] ?? $username;
            
            // Check if it's a first-time login for staff (not managers)
            if (isset($user['FirstTimeLogin']) && $user['FirstTimeLogin'] == 1) {
                header("Location: ../auth/staffSetup.php");
                exit();
            }
            
            header("Location: ../admin/adminindex.php");
            exit();
        } else {
            return "Invalid username or password";
        }
    }
    return null;
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

function getPendingOrders()
{
    global $pdo;
    return $pdo->query("SELECT COUNT(*) FROM `order` WHERE OrderStatus = 'Pending'")->fetchColumn();
}

function getTotalSales()
{
    global $pdo;
    return $pdo->query("SELECT SUM(OrderTotalAmount) FROM `order` WHERE OrderStatus = 'Completed'")->fetchColumn();
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
        header("Location: index.php");
        exit();
    }

    $stmt = $pdo->prepare("SELECT o.*, m.Name, m.Email, m.PhoneNumber 
                        FROM `order` o 
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
    $stmt = $pdo->prepare("SELECT oi.*, p.ProductName, p.ProdIMG1 
                        FROM orderitem oi 
                        JOIN product p ON oi.ProductID = p.ProductID 
                        WHERE oi.OrderID = ?");
    $stmt->execute([$order_id]);
    return $stmt->fetchAll();
}

function getPaymentDetails($pdo, $order_id) {
    $stmt = $pdo->prepare("SELECT * FROM payment WHERE OrderID = ?");
    $stmt->execute([$order_id]);
    return $stmt->fetch();
}

function getMemberProfilePhoto($member_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT ProfilePhoto FROM member WHERE MemberID = ?");
        $stmt->execute([$member_id]);
        $member = $stmt->fetch();
        return $member['ProfilePhoto'] ?: 'default-profile.png';
    } catch (PDOException $e) {
        error_log("Get Profile Photo Error: " . $e->getMessage());
        return 'default-profile.png';
    }
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

?>
