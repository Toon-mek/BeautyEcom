<?php
require_once __DIR__ . '/../_base.php';
$email = '';
$error = '';
$success = '';
$linkHTML = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    if (empty($email)) {
        $error = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $member = findMemberByEmail($email);
        if ($member) {
            $token = createPasswordResetToken($member['MemberID']);
            echo "<pre>DEBUG: Token created = $token</pre>";
            $link = "resetPassword.php?token=$token";

            $success = "A reset link has been generated below:";
            $linkHTML = "<a href='$link'>$link</a>";
        } else {
            $error = "Email not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link href="../css/style.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
    <style>
        .form-container {
            max-width: 400px;
            margin: 50px auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .form-container h2 {
            text-align: center;
            margin-bottom: 25px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            font-weight: bold;
            margin-bottom: 6px;
        }
        .form-input {
            width: 100%;
            padding: 10px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .btn {
            background-color: #e67e22;
            color: white;
            padding: 10px 0;
            width: 100%;
            border: none;
            border-radius: 5px;
            font-size: 16px;
        }
        .alert-box {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #842029;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Forgot Password</h2>

        <?php if ($error): ?>
            <div class="alert-box alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert-box alert-success"><?= htmlspecialchars($success) ?></div>
            <div style="word-break: break-all; text-align:center;"><?= $linkHTML ?></div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="post">
            <div class="form-group">
                <label for="email" class="form-label">Email:</label>
                <input type="email" class="form-input" id="email" name="email" required value="<?= htmlspecialchars($email) ?>">
            </div>
            <div class="form-group">
                <button type="submit" class="btn">Send Reset Link</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
