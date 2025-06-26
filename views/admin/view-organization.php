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

$organization = executeQuery("SELECT * FROM organizations WHERE user_id = ?", [$id], ['single' => true]);

if (!$organization) {
    die('Organization not found.');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Organization Details | <?php echo SITE_NAME; ?></title>
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
                    <h1>Organization Details</h1>
                    <a href="organizations.php" class="btn btn-outline">
                        <i data-lucide="arrow-left"></i> Back 
                    </a>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h2><?php echo sanitize($organization['name']); ?></h2>
                        <p><strong>Address:</strong> <?php echo sanitize($organization['address']); ?></p>
                        <p><strong>Contact Person:</strong> <?php echo sanitize($organization['contact_person']); ?></p>
                        <p><strong>Phone:</strong> <?php echo sanitize($organization['contact_phone']); ?></p>
                        <p><strong>Email:</strong> <?php echo sanitize($organization['email'] ?? ''); ?></p>
                        <p><strong>Industry:</strong> <?php echo sanitize($organization['industry']); ?></p>
                        <p><strong>Contract Start Date:</strong> <?php echo formatDate($organization['contract_start_date']); ?></p>
                        <p><strong>Contract End Date:</strong> <?php echo formatDate($organization['contract_end_date']); ?></p>
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
