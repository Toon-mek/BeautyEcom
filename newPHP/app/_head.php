<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/_base.php';
// Ensure uploads directory exists
$uploadsDir = __DIR__ . '/uploads';
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0777, true);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beauty & Wellness Shop</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="main-header">
        <div class="header-container">
            <a href="/newPHP/app/index.php" class="header-logo">Beauty & Wellness</a>
            <nav class="header-nav">
                <ul class="nav-list">
                    <li><a href="/newPHP/app/index.php" class="nav-link">Home</a></li>
                    <li><a href="/newPHP/app/product/all_product.php" class="nav-link">Products</a></li>
                    <li><a href="/newPHP/app/order/cart.php" class="nav-link">Cart</a></li>
                    <li><a href="/newPHP/app/product/vouchers.php" class="nav-link">Vouchers</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li class="profile-dropdown">
                            <?php
                            $profilePhoto = getMemberProfilePhoto($_SESSION['member_id']);
                            $defaultPhoto = "/newPHP/app/uploads/defaultprofilephoto.jpg";
                            $localPhotoPath = __DIR__ . "/uploads/" . $profilePhoto;
                            $photoPath = (!empty($profilePhoto) && file_exists($localPhotoPath))
                                ? "/newPHP/app/uploads/" . htmlspecialchars($profilePhoto)
                                : $defaultPhoto;
                            ?>
                            <img src="<?php echo $photoPath; ?>"
                                alt="Profile"
                                class="profile-photo"
                                onerror="this.onerror=null; this.src='<?php echo $defaultPhoto; ?>'">
                            <div class="dropdown-content">
                                <a href="/newPHP/app/member/settings.php">Settings</a>
                                <a href="/newPHP/app/order/member_order.php">Order History</a>
                                <a href="/newPHP/app/auth/logout.php">Logout</a>
                            </div>
                        </li>
                    <?php else: ?>
                        <li><a href="/newPHP/app/auth/login.php" class="nav-link">Log In</a></li>
                    <?php endif; ?>

                </ul>
            </nav>
        </div>
    </header>
    <main class="main-content">