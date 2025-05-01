<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';

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
        $stmt = $pdo->prepare("SELECT * FROM member WHERE Email = ?");
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
function redirectIfNotLoggedIn() {
    if (!isset($_SESSION['member_id'])) {
        header("Location: ../auth/login.php");
        exit();
    }
}

function requireLogin($role = 'member') {
    if ($role === 'staff') {
        if (!isset($_SESSION['staff_id'])) {
            header("Location: ../auth/staffLogin.php");
            exit();
        }
    } elseif ($role === 'member') {
        if (!isset($_SESSION['member_id'])) {
            header("Location: ../auth/login.php");
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
        header("Location: ../auth/login.php");
        exit();
    }

    global $pdo;

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

    $stmt = $pdo->prepare("INSERT INTO cartitem (CartID, ProductID, Quantity) VALUES (?, ?, ?)");
    $stmt->execute([$cart_id, $product_id, $quantity]);

    header("Location: ../order/cart.php");
    exit();
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
        header("Location: ../auth/login.php");
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['update_cart']) && isset($_POST['quantity'])) {
            foreach ($_POST['quantity'] as $cart_item_id => $quantity) {
                updateCartItem($pdo, $cart_item_id, $quantity);
            }
        } elseif (isset($_POST['remove_item'])) {
            removeCartItem($pdo, $_POST['remove_item']);
        } elseif (isset($_POST['checkout'])) {
            header("Location: checkout.php");
            exit();
        }
    }
}

function processCheckout($pdo, $cart_items, $total) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_method'])) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO `order` (MemberID, OrderTotalAmount, OrderDate, OrderStatus) VALUES (?, ?, NOW(), 'Pending')");
            $stmt->execute([$_SESSION['member_id'], $total]);
            $orderId = $pdo->lastInsertId();

            foreach ($cart_items as $item) {
                $stmt = $pdo->prepare("INSERT INTO orderitem (OrderID, ProductID, OrderItemQTY, OrderItemPrice) VALUES (?, ?, ?, ?)");
                $stmt->execute([$orderId, $item['ProductID'], $item['Quantity'], $item['Price']]);
            }

            $stmt = $pdo->prepare("INSERT INTO payment (OrderID, PaymentMethod, PaymentStatus, AmountPaid) VALUES (?, ?, 'Paid', ?)");
            $stmt->execute([$orderId, $_POST['payment_method'], $total]);

            $stmt = $pdo->prepare("UPDATE cart SET CartStatus = 'Inactive' WHERE MemberID = ? AND CartStatus = 'Active'");
            $stmt->execute([$_SESSION['member_id']]);

            $pdo->commit();
            header("Location: order_confirmation.php?order_id=" . $orderId);
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            return "Checkout failed: " . $e->getMessage();
        }
    }
    return null;
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
function fetchAllMembers()
{
    global $pdo;
    return $pdo->query("SELECT * FROM member")->fetchAll();
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
    $id = $data['member_id'];
    $name = $data['name'];
    $email = $data['email'];
    $phone = $data['phone'];
    $gender = $data['gender'];
    $dob = $data['dob'];

    if (!empty($data['password'])) {
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE member SET Name=?, Email=?, PhoneNumber=?, ProfilePhoto=?, Gender=?, DateOfBirth=?, Password=? WHERE MemberID=?");
        $stmt->execute([$name, $email, $phone, $profilePhoto, $gender, $dob, $password, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE member SET Name=?, Email=?, PhoneNumber=?, ProfilePhoto=?, Gender=?, DateOfBirth=? WHERE MemberID=?");
        $stmt->execute([$name, $email, $phone, $profilePhoto, $gender, $dob, $id]);
    }
}

function handleDeleteMember()
{
    if (isset($_GET['delete'])) {
        deleteMember($_GET['delete']);
        header("Location: membership.php");
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
        header("Location: membership.php");
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

?>