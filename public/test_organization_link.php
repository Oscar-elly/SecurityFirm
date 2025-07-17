<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    die("No user logged in.");
}

$userId = $_SESSION['user_id'];

echo "Testing organization linkage for user ID: $userId\n";

$organizationId = getOrganizationId($userId);

if ($organizationId === false) {
    echo "No organization found for user ID $userId\n";
} else {
    echo "Organization ID for user ID $userId is: $organizationId\n";
}
?>
