<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('admin');

// Fetch summary data for reports

// Total guards
$totalGuards = executeQuery("SELECT COUNT(*) as count FROM guards", [], ['single' => true])['count'] ?? 0;

// Total organizations
$totalOrganizations = executeQuery("SELECT COUNT(*) as count FROM organizations", [], ['single' => true])['count'] ?? 0;

// Total locations
$totalLocations = executeQuery("SELECT COUNT(*) as count FROM locations", [], ['single' => true])['count'] ?? 0;

// Recent incidents count by severity
$incidentSeverities = executeQuery("SELECT severity, COUNT(*) as count FROM incidents GROUP BY severity");

// Attendance summary: count by status
$attendanceSummary = executeQuery("SELECT status, COUNT(*) as count FROM attendance GROUP BY status");

// Duty assignments overview: count by status
$dutyAssignmentsSummary = executeQuery("SELECT status, COUNT(*) as count FROM duty_assignments GROUP BY status");

// Performance evaluations average overall rating
$avgPerformance = executeQuery("SELECT AVG(overall_rating) as avg_rating FROM performance_evaluations", [], ['single' => true])['avg_rating'] ?? 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reports - <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet" />
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
                    <h1>Reports</h1>
                    <a href="reports_pdf.php" class="btn btn-primary" style="float: right; margin-top: -40px;">
                        <i data-lucide="download"></i> Download PDF
                    </a>
                </div>

                <div class="card-group">
                    <div class="card">
                        <div class="card-body">
                            <h2>Total Guards</h2>
                            <p class="report-number"><?php echo $totalGuards; ?></p>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h2>Total Organizations</h2>
                            <p class="report-number"><?php echo $totalOrganizations; ?></p>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h2>Total Locations</h2>
                            <p class="report-number"><?php echo $totalLocations; ?></p>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h2>Average Performance Rating</h2>
                            <p class="report-number"><?php echo number_format($avgPerformance, 2); ?></p>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>Incidents by Severity</h2>
                    </div>
                    <div class="card-body">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Severity</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($incidentSeverities as $row): ?>
                                <tr>
                                    <td><?php echo ucfirst(sanitize($row['severity'])); ?></td>
                                    <td><?php echo (int)$row['count']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>Attendance Summary</h2>
                    </div>
                    <div class="card-body">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendanceSummary as $row): ?>
                                <tr>
                                    <td><?php echo ucfirst(sanitize($row['status'])); ?></td>
                                    <td><?php echo (int)$row['count']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>Duty Assignments Summary</h2>
                    </div>
                    <div class="card-body">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dutyAssignmentsSummary as $row): ?>
                                <tr>
                                    <td><?php echo ucfirst(sanitize($row['status'])); ?></td>
                                    <td><?php echo (int)$row['count']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
