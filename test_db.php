<?php
require_once 'includes/config.php';

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    echo "Database connected successfully!";
    
    // Test users table exists
    $result = $conn->query("SELECT 1 FROM users LIMIT 1");
    if ($result) {
        echo "<br>Users table exists!";
    } else {
        echo "<br>Users table doesn't exist or error: " . $conn->error;
    }
    
    $conn->close();
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}