<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Log the logout activity if user is logged in
if (isset($_SESSION['user_id'])) {
    require_once 'includes/db.php';
    logActivity($_SESSION['user_id'], 'User logged out', 'auth');
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: index.php");
exit;
?>