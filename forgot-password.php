<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: views/admin/dashboard.php');
            break;
        case 'guard':
            header('Location: views/guard/dashboard.php');
            break;
        case 'organization':
            header('Location: views/organization/dashboard.php');
            break;
    }
    exit();
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    if (empty($email)) {
        $error = 'Please enter your email address';
    } else {
        // Include database connection
        require_once 'includes/db.php';
        
        // Check if email exists
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Generate reset token
            $token = generateToken();
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database
            $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expiry) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token = ?, expiry = ?");
            $stmt->bind_param("issss", $user['id'], $token, $expiry, $token, $expiry);
            $stmt->execute();
            
            // Send reset email (in a real application)
            // This is a placeholder for actual email sending code
            $resetLink = SITE_URL . '/reset-password.php?token=' . $token;
            
            // For demo purposes, we'll just show the reset link
            $success = 'Password reset link has been sent to your email address. Please check your inbox.';
            
            // In a real application, you would send an email with the reset link
            // For this demo, we'll log the reset link
            error_log('Password reset link for ' . $email . ': ' . $resetLink);
        } else {
            // Don't reveal if email exists or not for security reasons
            $success = 'If your email address exists in our database, you will receive a password recovery link at your email address shortly.';
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-form-container">
            <div class="login-header">
                <h1>SecureConnect <span>Kenya</span></h1>
                <p>Forgot Password</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form class="login-form" method="POST" action="">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                    <p class="form-help">Enter your email address and we'll send you a link to reset your password.</p>
                </div>
                
                <button type="submit" class="login-button">Reset Password</button>
                
                <div class="back-to-login">
                    <a href="index.php">Back to Login</a>
                </div>
            </form>
            
            <div class="login-footer">
                <p>© <?php echo date('Y'); ?> SecureConnect Kenya. All rights reserved.</p>
            </div>
        </div>
        
        <div class="login-image">
            <div class="overlay"></div>
        </div>
    </div>
    
    <script src="assets/js/login.js"></script>
</body>
</html>