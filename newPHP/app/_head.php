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

                    <?php if (isLoggedIn()): ?>
                        <li class="profile-dropdown">
                            <?php
                            $profilePhoto = getMemberProfilePhoto($_SESSION['member_id']);
                            $photoPath = "/newPHP/app/uploads/" . htmlspecialchars($profilePhoto);
                            $defaultPhoto = "/newPHP/app/uploads/default-profile.png";
                            
                            // Check if file exists, if not use default
                            if (!file_exists(__DIR__ . '/uploads/' . $profilePhoto)) {
                                $photoPath = $defaultPhoto;
                            }
                            ?>
                            <img src="<?php echo $photoPath; ?>" 
                                 alt="Profile" 
                                 class="profile-photo"
                                 onerror="this.src='<?php echo $defaultPhoto; ?>'">
                            <div class="dropdown-content">
                                <a href="/newPHP/app/member/settings.php">Settings</a>
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
