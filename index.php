<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';


// // Redirect to appropriate dashboard if already logged in
// if (isset($_SESSION['user_id'])) {
//     switch ($_SESSION['role']) {
//         case 'admin':
//             header('Location: views/admin/dashboard.php');
//             break;
//         case 'guard':
//             header('Location: views/guard/dashboard.php');
//             break;
//         case 'organization':
//             header('Location: views/organization/dashboard.php');
//             break;
//     }
//     exit();
// }

// Handle login form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        // Include database connection
        require_once 'includes/db.php';
        
        // Check credentials
        $stmt = $conn->prepare("SELECT id, role, password, name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];
                
                // Redirect based on role
                switch ($user['role']) {
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
            } else {
                $error = 'Invalid email or password';
            }
        } else {
            $error = 'Invalid email or password';
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
    <title>SecureConnect Kenya | Security Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-form-container">
            <div class="login-header">
                <h1>SecureConnect <span>Kenya</span></h1>
                <p>Security Management System</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form class="login-form" method="POST" action="">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group remember-forgot">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="forgot-password.php" class="forgot-password">Forgot Password?</a>
                </div>
                
                <button type="submit" class="login-button">Log In</button>
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