<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('organization');

// Fetch security status data (example: summary of incidents, guard status, etc.)
$query = "SELECT COUNT(*) as total_incidents FROM incidents WHERE organization_id = ?";
$incidentCount = executeQuery($query, [$_SESSION['organization_id']], ['single' => true])['total_incidents'] ?? 0;

$query = "SELECT COUNT(*) as active_guards FROM guards WHERE organization_id = ? AND status = 'active'";
$activeGuards = executeQuery($query, [$_SESSION['organization_id']], ['single' => true])['active_guards'] ?? 0;

$query = "SELECT COUNT(*) as pending_requests FROM guard_requests WHERE organization_id = ? AND status = 'pending'";
$pendingRequests = executeQuery($query, [$_SESSION['organization_id']], ['single' => true])['pending_requests'] ?? 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Security Status | <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../../assets/css/styles.css" />
    <link rel="stylesheet" href="../../assets/css/dashboard.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/organization-sidebar.php'; ?>

        <main class="main-content">
            <?php include '../includes/top-nav.php'; ?>

            <div class="dashboard-content">
                <div class="dashboard-header">
                    <h1>Security Status</h1>
                    <p>Overview of your organization's security status</p>
                </div>

                <div class="stats-cards">
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="alert-triangle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $incidentCount; ?></h3>
                            <p>Total Incidents</p>
                        </div>
                    </div>

                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="shield"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $activeGuards; ?></h3>
                            <p>Active Guards</p>
                        </div>
                    </div>

                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="file-text"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $pendingRequests; ?></h3>
                            <p>Pending Guard Requests</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
