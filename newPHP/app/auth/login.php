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
    // Track failed attempts and cooldown in session
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }
    $now = time();
    $attempts = &$_SESSION['login_attempts'];
    if (!isset($attempts[$email])) {
        $attempts[$email] = ['count' => 0, 'blocked_until' => 0];
    }
    if ($now < $attempts[$email]['blocked_until']) {
        $error = "Too many failed attempts. Please wait 20 seconds before trying again.";
    } else {
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
                // Reset attempts on successful login
                $attempts[$email] = ['count' => 0, 'blocked_until' => 0];
                header("Location: ../index.php");
                exit();
            }
        } else {
            $attempts[$email]['count']++;
            if ($attempts[$email]['count'] >= 3) {
                $attempts[$email]['blocked_until'] = $now + 20;
                $error = "Too many failed attempts. Please wait 20 seconds to try again.";
            } else {
                $error = "Invalid email or password";
            }
        }
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
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
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
    <a href="../index.php" class="logo-link">
        <img src="../backgroundimg/Logo.png" alt="Beauty & Wellness Logo">
    </a>
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
                <div style="position:relative;">
                    <input type="password" class="form-input" id="password" name="password" required />
                    <button type="button" id="togglePassword" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer;">
                        <span id="togglePasswordIcon">👁️</span>
                    </button>
                </div>
            </div>
            <div class="form-group">
            <div class="g-recaptcha" data-sitekey="6LetwzIrAAAAAJkfAxhzNQzSwtDDrZHuINFvpzC1"></div>
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
    <script src="../js/login.js"></script>
</body>

</html>