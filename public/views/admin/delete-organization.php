<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('admin');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die('Invalid organization ID.');
}

// Delete organization
$query = "DELETE FROM organizations WHERE id = ?";
$result = executeQuery($query, [$id]);

if ($result) {
    $_SESSION['success'] = 'Organization deleted successfully.';
} else {
    $_SESSION['error'] = 'Failed to delete organization.';
}

header('Location: view-organizations.php');
exit;
?>
