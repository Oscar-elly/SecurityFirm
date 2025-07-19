<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('organization');

// Get organization ID with proper validation
$organizationId = getOrganizationId($_SESSION['user_id']);
if (!$organizationId) {
    die("Invalid organization access");
}

// Fetch reports data with proper schema alignment
$incidentReports = executeQuery("
    SELECT 
        i.id,
        i.title,
        i.description,
        i.incident_time,
        i.severity,
        i.status,
        i.latitude,
        i.longitude,
        i.created_at,
        i.updated_at,
        l.name AS location_name,
        l.address AS location_address,
        u.name AS reported_by_name
    FROM incidents i
    JOIN locations l ON i.location_id = l.id
    JOIN users u ON i.reported_by = u.id
    WHERE l.organization_id = ?
    ORDER BY i.incident_time DESC
    LIMIT 50
", [$organizationId]);

// Initialize as array if query fails
if ($incidentReports === false) {
    $incidentReports = [];
    error_log("Failed to fetch incident reports for organization: " . $organizationId);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Reports | <?php echo htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8'); ?></title>
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
                        <h2>Recent Incident Reports (Last 50)</h2>
                        <div class="card-actions">
                            <button class="btn btn-primary" onclick="window.print()">
                                <i data-lucide="printer"></i> Print Report
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Location</th>
                                        <th>Reported By</th>
                                        <th>Severity</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($incidentReports as $report): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($report['title'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($report['location_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?>
                                            <?php if (!empty($report['location_address'])): ?>
                                            <br><small><?php echo htmlspecialchars(substr($report['location_address'], 0, 30) . '...', ENT_QUOTES, 'UTF-8'); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($report['reported_by_name'] ?? 'System', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo htmlspecialchars(strtolower($report['severity'] ?? 'medium'), ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars(ucfirst($report['severity'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars(formatDate($report['incident_time'] ?? time()), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo htmlspecialchars(strtolower($report['status'] ?? 'reported'), ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars(ucfirst($report['status'] ?? 'Reported'), ENT_QUOTES, 'UTF-8'); ?>
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
                <div class="alert alert-info">
                    <i data-lucide="info"></i>
                    No incident reports found for your organization.
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
            
            // Add any additional JavaScript functionality here
            console.log('Reports page loaded successfully');
        });
    </script>
</body>
</html>