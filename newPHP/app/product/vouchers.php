<?php
require_once __DIR__ . '/../_base.php';

// Get all active vouchers that haven't expired
$stmt = $pdo->prepare("
    SELECT * FROM voucher 
    WHERE Status = 'Active' 
    AND ExpiryDate >= CURDATE()
    ORDER BY ExpiryDate ASC
");
$stmt->execute();
$vouchers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Vouchers - Beauty & Wellness</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php require_once __DIR__ . '/../_head.php'; ?>

    <div class="vouchers-container">
        <h1>Available Vouchers</h1>
        <p class="voucher-intro">Use these vouchers to get discounts on your purchases. Copy a voucher code and apply it during checkout.</p>
        
        <?php if (count($vouchers) > 0): ?>
            <div class="voucher-list">
                <?php foreach ($vouchers as $voucher): ?>
                    <div class="voucher-card">
                        <div class="voucher-content">
                            <div class="voucher-info">
                                <h2><?php echo htmlspecialchars($voucher['Discount']); ?>% OFF</h2>
                                <p class="voucher-description"><?php echo htmlspecialchars($voucher['Description']); ?></p>
                                <p class="voucher-expiry">Expires: <?php echo date('F j, Y', strtotime($voucher['ExpiryDate'])); ?></p>
                            </div>
                            <div class="voucher-code-container">
                                <div class="voucher-code" id="code-<?php echo $voucher['VoucherID']; ?>"><?php echo htmlspecialchars($voucher['Code']); ?></div>
                                <button class="copy-btn" data-code="<?php echo htmlspecialchars($voucher['Code']); ?>" 
                                        onclick="copyVoucherCode('<?php echo htmlspecialchars($voucher['Code']); ?>', this)">
                                    Copy Code
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-vouchers">
                <i class="fas fa-ticket-alt"></i>
                <h2>No Vouchers Available</h2>
                <p>Check back soon for special discounts and promotions!</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
    function copyVoucherCode(code, button) {
        // Create a temporary input element
        const tempInput = document.createElement('input');
        tempInput.value = code;
        document.body.appendChild(tempInput);
        
        // Select and copy the text
        tempInput.select();
        document.execCommand('copy');
        
        // Remove the temporary element
        document.body.removeChild(tempInput);
        
        // Change the button text to indicate success
        const originalText = button.textContent;
        button.textContent = 'Copied!';
        button.classList.add('copied');
        
        // Revert button text after 2 seconds
        setTimeout(() => {
            button.textContent = originalText;
            button.classList.remove('copied');
        }, 2000);
    }
    </script>

    <?php require_once __DIR__ . '/../_foot.php'; ?>
</body>
</html>