<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Method not allowed']));
}

// Get and validate input data
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$email = filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL);
$password = $input['password'] ?? '';
$remember = filter_var($input['remember'] ?? false, FILTER_VALIDATE_BOOLEAN);

// Input validation
if (empty($email) || empty($password)) {
    http_response_code(400);
    exit(json_encode(['error' => 'Please fill in all fields']));
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid email format']));
}

// Database connection
require_once 'includes/db.php';
if ($conn->connect_error) {
    http_response_code(500);
    exit(json_encode(['error' => 'Database connection failed']));
}

// Set character encoding
$conn->set_charset("utf8mb4");

// User lookup
$stmt = $conn->prepare("SELECT id, role, password, name FROM users WHERE email = ?");
if (!$stmt) {
    http_response_code(500);
    exit(json_encode(['error' => 'Database query preparation failed']));
}

$stmt->bind_param("s", $email);
if (!$stmt->execute()) {
    http_response_code(500);
    exit(json_encode(['error' => 'Database query execution failed']));
}

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    http_response_code(401);
    exit(json_encode(['error' => 'Invalid email or password']));
}

$user = $result->fetch_assoc();

// Password verification with debugging
error_log("Login attempt for: " . $email);
error_log("Stored hash: " . $user['password']);

if (!password_verify($password, $user['password'])) {
    // Check if password needs rehashing
    if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $updateStmt->bind_param("si", $newHash, $user['id']);
        $updateStmt->execute();
    }
    
    http_response_code(401);
    exit(json_encode(['error' => 'Invalid email or password']));
}

// Login successful - set session
$_SESSION['user_id'] = $user['id'];
$_SESSION['role'] = $user['role'];
$_SESSION['name'] = $user['name'];

// Remember me functionality
if ($remember) {
    $token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    $stmt = $conn->prepare("INSERT INTO remember_tokens (user_id, token, expiry) VALUES (?, ?, ?) 
                           ON DUPLICATE KEY UPDATE token = VALUES(token), expiry = VALUES(expiry)");
    if ($stmt) {
        $stmt->bind_param("iss", $user['id'], $token, $expiry);
        $stmt->execute();
        setcookie('remember_token', $token, [
            'expires' => strtotime('+30 days'),
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }
}

// Log activity
logActivity($user['id'], 'User logged in', 'auth');

// Determine redirect URL
$roleRedirects = [
    'admin' => 'views/admin/dashboard.php',
    'guard' => 'views/guard/dashboard.php',
    'organization' => 'views/organization/dashboard.php'
];

if (!isset($roleRedirects[$user['role']])) {
    error_log("Unknown role: " . $user['role']);
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized role']));
}

// Successful response
echo json_encode([
    'success' => true,
    'redirect' => $roleRedirects[$user['role']]
]);

$stmt->close();
$conn->close();
?>