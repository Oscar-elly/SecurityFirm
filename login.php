<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'];
$remember = isset($_POST['remember']) ? true : false;

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Please fill in all fields']);
    exit;
}

require_once 'includes/db.php';

$stmt = $conn->prepare("SELECT id, role, password, name FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];

        //added 
        error_log("Password verified for user ID: " . $user['id']);

        
        if ($remember) {
            $token = generateToken();
            $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            $stmt = $conn->prepare("INSERT INTO remember_tokens (user_id, token, expiry) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token = ?, expiry = ?");
            $stmt->bind_param("issss", $user['id'], $token, $expiry, $token, $expiry);
            $stmt->execute();
            
            setcookie('remember_token', $token, strtotime('+30 days'), '/', '', true, true);
        }
        
        // Log the login activity
        logActivity($user['id'], 'User logged in', 'auth');
        
        // Return success with redirect URL
        $redirectUrl = '';
        
        //added 
        switch (trim($user['role'])) {
    case 'admin':
        $redirectUrl = 'views/admin/dashboard.php';
        break;
    case 'guard':
        $redirectUrl = 'views/guard/dashboard.php';
        break;
    case 'organization':
        $redirectUrl = 'views/organization/dashboard.php';
        break;
    default:
        error_log("Unknown role: " . $user['role']);
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized role']);
        exit;
}

        //added 
        error_log("Login success: role = " . $user['role'] . ", redirect = " . $redirectUrl);
        
        echo json_encode([
            'success' => true,
            'redirect' => $redirectUrl
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid email or password']);
    }
} else {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid email or password']);
}

$stmt->close();
$conn->close();
// At the top of login.php

// require_once 'includes/config.php';
// require_once 'includes/functions.php';

// // Start session
// if (session_status() === PHP_SESSION_NONE) {
//     session_start();
// }

// // Handle login form submission
// $error = '';
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
//     $password = $_POST['password'];
    
//     if (empty($email) || empty($password)) {
//         $error = 'Please fill in all fields';
//     } else {
//         // Include and verify database connection
//         require_once 'includes/db.php';
        
//         if (!isset($conn) || !($conn instanceof mysqli)) {
//             error_log("Database connection failed in login.php");
//             $error = 'Database error. Please try again later.';
//         } else {
//             try {
//                 $stmt = $conn->prepare("SELECT id, role, password, name FROM users WHERE email = ?");
//                 if (!$stmt) {
//                     throw new Exception("Prepare failed: " . $conn->error);
//                 }
                
//                 $stmt->bind_param("s", $email);
//                 if (!$stmt->execute()) {
//                     throw new Exception("Execute failed: " . $stmt->error);
//                 }
                
//                 // Rest of your login logic...
                
//             } catch (Exception $e) {
//                 error_log("Login error: " . $e->getMessage());
//                 $error = 'An error occurred. Please try again.';
//             }
//         }
//     }
// }
?>