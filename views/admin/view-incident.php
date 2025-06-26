<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('admin');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die('Invalid incident ID.');
}

// Fetch incident details with related user and location info
$query = "SELECT i.*, u.name as reporter_name, l.name as location_name
          FROM incidents i
          JOIN users u ON i.reported_by = u.id
          JOIN locations l ON i.location_id = l.id
          WHERE i.id = ?
          LIMIT 1";
$incident = executeQuery($query, [$id], ['single' => true]);

if (!$incident) {
    die('Incident not found.');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Incident Details | <?php echo SITE_NAME; ?></title>
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
                    <h1>Incident Details</h1>
                    <a href="incidents.php" class="btn btn-outline">
                        <i data-lucide="arrow-left"></i> Back to Incidents
                    </a>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h2><?php echo sanitize($incident['title']); ?></h2>
                        <p><strong>Description:</strong> <?php echo nl2br(sanitize($incident['description'])); ?></p>
                        <p><strong>Location:</strong> <?php echo sanitize($incident['location_name']); ?></p>
                        <p><strong>Severity:</strong> <?php echo ucfirst(sanitize($incident['severity'])); ?></p>
                        <p><strong>Reported By:</strong> <?php echo sanitize($incident['reporter_name']); ?></p>
                        <p><strong>Date:</strong> <?php echo formatDate($incident['incident_time']); ?></p>
                        <p><strong>Status:</strong> <?php echo ucfirst(sanitize($incident['status'])); ?></p>
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
