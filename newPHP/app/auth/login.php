<?php
require_once __DIR__ . '/../_base.php';
$successMsg = $_SESSION['success'] ?? '';
unset($_SESSION['success']);
$error = null;
$success = null;
if (isset($_GET['registered']) && $_GET['registered'] == 1) {
    $success = "Registration successful! Please login with your credentials.";
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM member WHERE Email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['Password'])) {
        if ($user['MembershipStatus'] !== 'Active') {
            $error = "Your account has been blocked. Please contact our customer service.";
        } else {
            $_SESSION['user_id'] = $user['MemberID'];
            $_SESSION['user_role'] = 'member';
            $_SESSION['member_id'] = $user['MemberID'];
            header("Location: ../index.php");
            exit();
        }
    } else {
        $error = "Invalid email or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - Beauty & Wellness</title>
    <link href="../css/style.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
    <style>
        .alert-box {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            animation: slideDown 0.4s ease;
            text-align: center;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #842029;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-15px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
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

        <?php if ($successMsg): ?>
            <div class="alert-box alert-success"><?= htmlspecialchars(string: $successMsg) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert-box alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert-box alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="email" class="form-label">Email address</label>
                <input type="email" class="form-input" id="email" name="email" required />
            </div>
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-input" id="password" name="password" required />
            </div>
            <div class="form-group">
                <a href="forgotPassword.php" class="forgot-password-link">Forgot Password?</a>
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