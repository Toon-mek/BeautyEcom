<?php
require_once __DIR__ . '/../_base.php';
$error = handleStaffLogin($pdo);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - Beauty & Wellness</title>
    <link href="../css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>

<body>
<div class="member-login">
    <a href="login.php" class="member-login-link">Member Login</a>
</div>
    <div class="form-container">
        <button type="button" class="return-btn" onclick="window.history.back();">
            <i class="fa fa-chevron-left" aria-hidden="true"></i>
        </button>
        <h2 class="text-center mb-4">Staff Login</h2>
        <?php if (isset($_SESSION['staff_login_error'])): ?>
            <div class="alert-box alert-error">
                <?= htmlspecialchars($_SESSION['staff_login_error']); ?>
            </div>
            <?php unset($_SESSION['staff_login_error']); ?>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-input" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-input" id="password" name="password" required>
            </div>
            <div class="form-group text-left">
                <a href="#" onclick="showForgotMessage()" class="forgot-password-link">Forgot Password?</a>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn">Login</button>
            </div>
        </form>
    </div>
</body>
</html>
<script src="../js/staffLogin.js"></script>