<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('organization');

$organizationId = $_SESSION['user_id'];

// Fetch reports data (example: incident reports, guard reports, etc.)
$incidentReports = executeQuery("
    SELECT i.*, l.name as location_name
    FROM incidents i
    JOIN locations l ON i.location_id = l.id
    WHERE l.user_id = ?
    ORDER BY i.incident_time DESC
    LIMIT 50
", [$organizationId]);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Reports | <?php echo SITE_NAME; ?></title>
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
                    <h1>Reports</h1>
                    <p>View and manage incident reports</p>
                </div>

                <?php if (!empty($incidentReports)): ?>
                <div class="card">
                    <div class="card-header">
                        <h2>Recent Incident Reports</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Location</th>
                                        <th>Severity</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($incidentReports as $report): ?>
                                    <tr>
                                        <td><?php echo sanitize($report['title']); ?></td>
                                        <td><?php echo sanitize($report['location_name']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo getSeverityClass($report['severity']); ?>">
                                                <?php echo ucfirst($report['severity']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($report['incident_time']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo getStatusClass($report['status']); ?>">
                                                <?php echo ucfirst($report['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <p>No incident reports found.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
