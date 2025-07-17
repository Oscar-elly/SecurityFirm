<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('admin');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die('Invalid location ID.');
}

// Fetch location details
$query = "SELECT * FROM locations WHERE id = ? LIMIT 1";
$location = executeQuery($query, [$id], ['single' => true]);

if (!$location) {
    die('Location not found.');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Location Details | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/styles.css" />
    <link rel="stylesheet" href="../../assets/css/dashboard.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/admin-sidebar.php'; ?>

        <main class="main-content">
            <?php include '../includes/top-nav.php'; ?>

            <div class="dashboard-content">
                <div class="dashboard-header">
                    <h1>Location Details</h1>
                    <a href="locations.php" class="btn btn-outline">
                        <i data-lucide="arrow-left"></i> Back to Locations
                    </a>
                </div>

                <div class="card">
                <div class="card-body">
                    <h2><?php echo sanitize($location['name']); ?></h2>
                    <p><strong>Address:</strong> <?php echo sanitize($location['address']); ?></p>
                    <p><strong>Latitude:</strong> <?php echo sanitize($location['latitude']); ?></p>
                    <p><strong>Longitude:</strong> <?php echo sanitize($location['longitude']); ?></p>
                    <p><strong>Status:</strong> <?php echo ucfirst(sanitize($location['status'])); ?></p>
                    <p><strong>Created At:</strong> <?php echo formatDate($location['created_at']); ?></p>
                    <p><strong>Updated At:</strong> <?php echo formatDate($location['updated_at']); ?></p>
                </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
