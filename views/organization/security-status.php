<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('organization');

if (!isset($_SESSION['user_id'])) {
    // Redirect to login or error page if user_id is not set
    // Redirect to organization login page instead of API login.php
    header('Location: login.php');
    exit;
}

$organizationId = $_SESSION['user_id'];

// Incident statistics
$incidentStatsQuery = "SELECT 
    COUNT(*) as total_incidents,
    SUM(CASE WHEN status IN ('reported', 'investigating') THEN 1 ELSE 0 END) as open_incidents,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_incidents,
    SUM(CASE WHEN severity IN ('high', 'critical') THEN 1 ELSE 0 END) as critical_incidents
    FROM incidents i
    JOIN locations l ON i.location_id = l.id
    JOIN organizations o ON l.user_id = o.id
    WHERE o.user_id = ?";
$incidentStats = executeQuery($incidentStatsQuery, [$organizationId], ['single' => true]);

// Guard statistics
$guardStatsQuery = "SELECT 
    COUNT(*) as total_guards,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_guards
    FROM guards g
    JOIN users u ON g.user_id = u.id
    WHERE u.id = ?";
$guardStats = executeQuery($guardStatsQuery, [$organizationId], ['single' => true]);

// Guard performance average rating
$guardPerformanceQuery = "SELECT AVG(pe.overall_rating) as avg_rating
    FROM performance_evaluations pe
    JOIN guards g ON pe.guard_id = g.id
    WHERE g.user_id = ?";
$guardPerformance = executeQuery($guardPerformanceQuery, [$organizationId], ['single' => true]);

// Guard requests statistics
$guardRequestsQuery = "SELECT 
    COUNT(*) as total_requests,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
    SUM(number_of_guards) as guards_requested
    FROM guard_requests WHERE user_id = ?";
$guardRequests = executeQuery($guardRequestsQuery, [$organizationId], ['single' => true]);

// Risk assessment data
$riskData = executeQuery("
    SELECT severity, COUNT(*) as count
    FROM incidents
    WHERE user_id = ?
    GROUP BY severity
    ORDER BY FIELD(severity, 'critical', 'high', 'medium', 'low')
", [$organizationId]);

$locationsAtRisk = executeQuery("
    SELECT l.name as location_name, COUNT(i.id) as incident_count
    FROM locations l
    LEFT JOIN incidents i ON l.id = i.location_id
    WHERE l.user_id = ?
    GROUP BY l.id, l.name
    HAVING incident_count > 0
    ORDER BY incident_count DESC
    LIMIT 5
", [$organizationId]);

// Analytics key metrics
$guardUtilizationQuery = "SELECT COUNT(DISTINCT da.guard_id) as active_guards,
    (SELECT COUNT(*) FROM guards WHERE user_id = ?) as total_guards,
    ROUND((COUNT(DISTINCT da.guard_id) * 100.0 / (SELECT COUNT(*) FROM guards WHERE user_id = ?)), 2) as utilization_rate
    FROM duty_assignments da
    JOIN guards g ON da.guard_id = g.id
    WHERE g.user_id = ? AND da.status = 'active' 
    AND CURDATE() BETWEEN da.start_date AND IFNULL(da.end_date, CURDATE())";
$guardUtilization = executeQuery($guardUtilizationQuery, [$organizationId, $organizationId, $organizationId], ['single' => true]);

$attendanceStatsQuery = "SELECT status, COUNT(*) as count,
    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM attendance a JOIN duty_assignments da ON a.duty_assignment_id = da.id JOIN guards g ON da.guard_id = g.id WHERE g.user_id = ?)), 2) as percentage
    FROM attendance a
    JOIN duty_assignments da ON a.duty_assignment_id = da.id
    JOIN guards g ON da.guard_id = g.id
    WHERE g.user_id = ? AND check_in_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY status";
$attendanceStats = executeQuery($attendanceStatsQuery, [$organizationId, $organizationId]);

$presentRate = 0;
foreach ($attendanceStats as $stat) {
    if ($stat['status'] === 'present') {
        $presentRate = $stat['percentage'];
        break;
    }
}

// Recent incident reports
$recentReports = executeQuery("
    SELECT i.title, l.name as location_name, i.severity, i.incident_time, i.status
    FROM incidents i
    JOIN locations l ON i.location_id = l.id
    WHERE l.user_id = ?
    ORDER BY i.incident_time DESC
    LIMIT 5
", [$organizationId]);

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
                    <!-- Incident Stats -->
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="alert-triangle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $incidentStats['total_incidents']; ?></h3>
                            <p>Total Incidents</p>
                        </div>
                    </div>
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="clock"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $incidentStats['open_incidents']; ?></h3>
                            <p>Open Incidents</p>
                        </div>
                    </div>
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="check-circle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $incidentStats['resolved_incidents']; ?></h3>
                            <p>Resolved Incidents</p>
                        </div>
                    </div>
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="alert-triangle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $incidentStats['critical_incidents']; ?></h3>
                            <p>Critical Incidents</p>
                        </div>
                    </div>

                    <!-- Guard Stats -->
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="shield"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $guardStats['active_guards']; ?></h3>
                            <p>Active Guards</p>
                        </div>
                    </div>
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="users"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $guardStats['total_guards']; ?></h3>
                            <p>Total Guards</p>
                        </div>
                    </div>
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="star"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $guardPerformance['avg_rating'] ? round($guardPerformance['avg_rating'], 1) : 'N/A'; ?></h3>
                            <p>Avg Guard Rating</p>
                        </div>
                    </div>

                    <!-- Guard Requests -->
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="file-text"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $guardRequests['total_requests']; ?></h3>
                            <p>Total Guard Requests</p>
                        </div>
                    </div>
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="clock"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $guardRequests['pending_requests']; ?></h3>
                            <p>Pending Requests</p>
                        </div>
                    </div>
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="check-circle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $guardRequests['approved_requests']; ?></h3>
                            <p>Approved Requests</p>
                        </div>
                    </div>
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="shield"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $guardRequests['guards_requested']; ?></h3>
                            <p>Guards Requested</p>
                        </div>
                    </div>

                    <!-- Analytics -->
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="trending-up"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $guardUtilization['utilization_rate']; ?>%</h3>
                            <p>Guard Utilization</p>
                        </div>
                    </div>
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="check-circle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $presentRate; ?>%</h3>
                            <p>Attendance Rate</p>
                        </div>
                    </div>
                </div>

                <!-- Risk Assessment -->
                <div class="card">
                    <div class="card-header">
                        <h2>Incidents by Severity</h2>
                    </div>
                    <div class="card-body">
                        <ul class="risk-list">
                            <?php foreach ($riskData as $risk): ?>
                            <li>
                                <span class="badge badge-<?php echo strtolower($risk['severity']); ?>">
                                    <?php echo ucfirst($risk['severity']); ?>
                                </span>
                                <span><?php echo $risk['count']; ?> incidents</span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>Locations at Risk</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($locationsAtRisk)): ?>
                        <ul class="location-risk-list">
                            <?php foreach ($locationsAtRisk as $location): ?>
                            <li>
                                <strong><?php echo sanitize($location['location_name']); ?></strong>
                                <span class="badge badge-danger"><?php echo $location['incident_count']; ?> incidents</span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else: ?>
                        <p>No locations with reported incidents.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Reports -->
                <div class="card">
                    <div class="card-header">
                        <h2>Recent Incident Reports</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recentReports)): ?>
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
                                    <?php foreach ($recentReports as $report): ?>
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
                        <?php else: ?>
                        <p>No incident reports found.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <style>
                .risk-list {
                    list-style: none;
                    padding: 0;
                    display: flex;
                    gap: 1rem;
                }
                .risk-list li {
                    background: #f8f9fa;
                    padding: 1rem;
                    border-radius: 8px;
                    flex: 1;
                    text-align: center;
                    font-weight: 600;
                }
                .location-risk-list {
                    list-style: none;
                    padding: 0;
                }
                .location-risk-list li {
                    padding: 0.5rem 0;
                    border-bottom: 1px solid #eee;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                </style>

                <script>
                    lucide.createIcons();
                </script>
