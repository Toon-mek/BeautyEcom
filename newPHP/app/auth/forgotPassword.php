<?php
require_once __DIR__ . '/../_base.php';
$error = null;
$success = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    // Check if email exists in database
    $stmt = $pdo->prepare("SELECT MemberID FROM member WHERE Email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user) {
        // Generate a unique reset token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store the reset token in the database
        $stmt = $pdo->prepare("UPDATE member SET ResetToken = ?, ResetTokenExpiry = ? WHERE Email = ?");
        $stmt->execute([$token, $expiry, $email]);
        
        // Send reset link (for now just show it since email setup isn't done)
        $resetLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/resetPassword.php?token=" . $token;
        $success = "Password reset link has been generated: <br>" . $resetLink;
        
        // TODO: In production, you would send this via email instead of displaying it
    } else {
        $error = "No account found with this email address.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Beauty & Wellness</title>
    <link href="../css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <button type="button" class="return-btn" onclick="window.history.back();">
            <i class="fa fa-chevron-left" aria-hidden="true"></i>
        </button>
        <h2 class="text-center mb-4">Forgot Password</h2>
        
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email" class="form-label">Email address</label>
                <input type="email" class="form-input" id="email" name="email" required>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn">Request Password Reset</button>
            </div>
        </form>
        
        <div class="text-center mt-3">
            <p>Remember your password? <a href="login.php">Login here</a></p>
        </div>
    </div>
</body>
</html> 