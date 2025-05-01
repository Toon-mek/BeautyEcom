<?php
require_once __DIR__ . '/../_base.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (loginUser($email, $password)) {
        header("Location: ../index.php");
        exit();
    } else {
        $error = "Invalid email or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Beauty & Wellness</title>
    <link href="../css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
    <div class="staff-login">
        <a href="staffLogin.php" class="staff-login-link">Staff Login</a>
    </div>
    <div class="form-container">
        <button type="button" class="return-btn" onclick="window.history.back();">
            <i class="fa fa-chevron-left" aria-hidden="true"></i>
        </button>
        <h2 class="text-center mb-4">Login</h2>
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="email" class="form-label">Email address</label>
                <input type="email" class="form-input" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-input" id="password" name="password" required>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn">Login</button>
            </div>
        </form>
        <div class="text-center mt-3">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
</body>
</html>
