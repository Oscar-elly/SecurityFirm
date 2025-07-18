<?php
session_start();

// Load required files with existence checks
$requiredFiles = [
    '../../includes/config.php',
    '../../includes/functions.php',
    '../../includes/db.php'
];

foreach ($requiredFiles as $file) {
    if (!file_exists($file)) {
        die("Error: Required file '$file' is missing.");
    }
    require_once $file;
}

// Verify database connection
global $conn;
if (!$conn || $conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Verify user session and role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../../login.php");
    exit;
}

requireRole('organization');

// Get organization ID with error handling
$organizationId = getOrganizationId($_SESSION['user_id']);
if ($organizationId === false || $organizationId <= 0) {
    die("Invalid organization ID. Please contact support.");
}
error_log("Debug: User ID = " . $_SESSION['user_id']);
error_log("Debug: Organization ID = " . $organizationId);

// Initialize all variables with default values
$stats = [
    'incidents' => [
        'total' => 0,
        'open' => 0,
        'resolved' => 0,
        'critical' => 0
    ],
    'guards' => [
        'total' => 0,
        'active' => 0,
        'avg_rating' => 'N/A'
    ],
    'requests' => [
        'total' => 0,
        'pending' => 0,
        'approved' => 0,
        'guards_requested' => 0
    ],
    'utilization' => [
        'rate' => 0,
        'active' => 0,
        'total' => 0
    ],
    'attendance' => [
        'rate' => 0,
        'present' => 0,
        'absent' => 0
    ]
];

$riskData = [];
$locationsAtRisk = [];
$recentReports = [];

try {
    // 1. Incident Statistics
    $incidentStatsQuery = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN i.status IN ('reported', 'investigating') THEN 1 ELSE 0 END) as open,
        SUM(CASE WHEN i.status = 'resolved' THEN 1 ELSE 0 END) as resolved,
        SUM(CASE WHEN i.severity IN ('high', 'critical') THEN 1 ELSE 0 END) as critical
        FROM incidents i
        JOIN locations l ON i.location_id = l.id
        WHERE l.organization_id = ?";
    
    error_log("Incident Stats Query: " . $incidentStatsQuery);
    $result = executeQuery2($incidentStatsQuery, [$organizationId], ['single' => true]);
    if ($result) {
        $stats['incidents'] = array_merge($stats['incidents'], $result);
        error_log("Incident Stats Result: " . print_r($result, true));
    } else {
        error_log("Query failed: Incident Stats Query");
    }

    // 2. Guard Statistics
    // 2. Guard Statistics - Updated Query
    $guardStatsQuery = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN u.status = 'active' THEN 1 ELSE 0 END) as active
        FROM guards g
        JOIN users u ON g.user_id = u.id
        WHERE g.organization_id = ?";
    
    error_log("Guard Stats Query: " . $guardStatsQuery);
    $result = executeQuery2($guardStatsQuery, [$organizationId], ['single' => true]);
    if ($result) {
        $stats['guards'] = array_merge($stats['guards'], $result);
        error_log("Guard Stats Result: " . print_r($result, true));
    } else {
        error_log("Query failed: Guard Stats Query");
    }

    // 3. Guard Performance
    $guardPerformanceQuery = "SELECT 
        ROUND(AVG(pe.overall_rating), 1) as avg_rating
        FROM performance_evaluations pe
        JOIN guards g ON pe.guard_id = g.id
        WHERE g.organization_id = ?";
    
    error_log("Guard Performance Query: " . $guardPerformanceQuery);
    $result = executeQuery2($guardPerformanceQuery, [$organizationId], ['single' => true]);
    if ($result && $result['avg_rating'] !== null) {
        $stats['guards']['avg_rating'] = $result['avg_rating'];
        error_log("Guard Performance Result: " . print_r($result, true));
    } else {
        error_log("Query failed or no data: Guard Performance Query");
    }

    // 4. Guard Requests
    $guardRequestsQuery = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(number_of_guards) as guards_requested
        FROM guard_requests 
        WHERE organization_id = ?";
    
    error_log("Guard Requests Query: " . $guardRequestsQuery);
    $result = executeQuery2($guardRequestsQuery, [$organizationId], ['single' => true]);
    if ($result) {
        $stats['requests'] = array_merge($stats['requests'], $result);
        error_log("Guard Requests Result: " . print_r($result, true));
    } else {
        error_log("Query failed: Guard Requests Query");
    }

    // 5. Risk Assessment Data
    $riskDataQuery = "SELECT 
        severity, COUNT(*) as count
        FROM incidents i
        JOIN locations l ON i.location_id = l.id
        WHERE l.organization_id = ?
        GROUP BY severity
        ORDER BY FIELD(severity, 'critical', 'high', 'medium', 'low')";
    
    error_log("Risk Data Query: " . $riskDataQuery);
    $riskData = executeQuery2($riskDataQuery, [$organizationId]);
    if ($riskData === false) {
        error_log("Query failed: Risk Data Query");
        $riskData = [];
    } else {
        error_log("Risk Data Result: " . print_r($riskData, true));
    }

    // 6. Locations at Risk
    $locationsQuery = "SELECT 
        l.id, l.name as location_name, 
        COUNT(i.id) as incident_count
        FROM locations l
        LEFT JOIN incidents i ON l.id = i.location_id
        WHERE l.organization_id = ?
        GROUP BY l.id, l.name
        HAVING incident_count > 0
        ORDER BY incident_count DESC
        LIMIT 5";
    
    error_log("Locations At Risk Query: " . $locationsQuery);
    $locationsAtRisk = executeQuery2($locationsQuery, [$organizationId]);
    if ($locationsAtRisk === false) {
        error_log("Query failed: Locations At Risk Query");
        $locationsAtRisk = [];
    } else {
        error_log("Locations At Risk Result: " . print_r($locationsAtRisk, true));
    }

    // 7. Guard Utilization
    // 7. Guard Utilization - Updated Query
    $guardUtilization = executequery2("
        SELECT 
            COUNT(DISTINCT da.guard_id) as active_guards,
            (SELECT COUNT(DISTINCT da2.guard_id) 
            FROM duty_assignments da2
            JOIN locations l2 ON da2.location_id = l2.id
            WHERE l2.organization_id = ?) as total_guards,
            ROUND((COUNT(DISTINCT da.guard_id) * 100.0 / 
                (SELECT COUNT(DISTINCT da3.guard_id) 
                FROM duty_assignments da3
                JOIN locations l3 ON da3.location_id = l3.id
                WHERE l3.organization_id = ?)), 2) as utilization_rate
        FROM duty_assignments da
        JOIN locations l ON da.location_id = l.id
        WHERE l.organization_id = ? AND da.status = 'active' 
        AND CURDATE() BETWEEN da.start_date AND IFNULL(da.end_date, CURDATE())
    ", [$organizationId, $organizationId, $organizationId]) ?: [];

    $utilization = $guardUtilization[0] ?? ['active_guards' => 0, 'total_guards' => 0, 'utilization_rate' => 0];

    // 8. Attendance Stats
    $attendanceQuery = "SELECT 
        status, COUNT(*) as count,
        ROUND((COUNT(*) * 100.0 / NULLIF((
            SELECT COUNT(*) 
            FROM attendance a 
            JOIN duty_assignments da ON a.duty_assignment_id = da.id 
            JOIN guards g ON da.guard_id = g.id
            WHERE g.organization_id = ?
        ), 0)), 2) as percentage
        FROM attendance a
        JOIN duty_assignments da ON a.duty_assignment_id = da.id
        JOIN guards g ON da.guard_id = g.id
        WHERE g.organization_id = ? 
        AND a.check_in_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY status";
    
    error_log("Attendance Query: " . $attendanceQuery);
    $attendanceResults = executeQuery2($attendanceQuery, [$organizationId, $organizationId]);
    if ($attendanceResults === false) {
        error_log("Query failed: Attendance Query");
        $attendanceResults = [];
    } else {
        error_log("Attendance Results: " . print_r($attendanceResults, true));
    }
    foreach ($attendanceResults as $row) {
        if ($row['status'] === 'present') {
            $stats['attendance']['rate'] = $row['percentage'];
            $stats['attendance']['present'] = $row['count'];
        } else {
            $stats['attendance']['absent'] += $row['count'];
        }
    }

    // 9. Recent Incident Reports
    $recentReportsQuery = "SELECT 
        i.id, i.title, l.name as location_name, 
        i.severity, i.incident_time, i.status
        FROM incidents i
        JOIN locations l ON i.location_id = l.id
        WHERE l.organization_id = ?
        ORDER BY i.incident_time DESC
        LIMIT 5";
    
    error_log("Recent Reports Query: " . $recentReportsQuery);
    $recentReports = executeQuery2($recentReportsQuery, [$organizationId]);
    if ($recentReports === false) {
        error_log("Query failed: Recent Reports Query");
        $recentReports = [];
    } else {
        error_log("Recent Reports Result: " . print_r($recentReports, true));
    }

} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $_SESSION['error'] = "Failed to load dashboard data. Please try again.";
}

// Debug output
echo '<div style="background:#f5f5f5;padding:20px;margin:20px;border:1px solid #ddd;">';
echo '<h3>Debug Data - Organization ID: '.htmlspecialchars($organizationId).'</h3>';

echo '<h4>Stats</h4>';
echo '<pre>'.print_r($stats, true).'</pre>';

echo '<h4>Risk Data</h4>';
echo '<pre>'.print_r($riskData, true).'</pre>';

echo '<h4>Locations At Risk</h4>';
echo '<pre>'.print_r($locationsAtRisk, true).'</pre>';

echo '<h4>Recent Reports</h4>';
echo '<pre>'.print_r($recentReports, true).'</pre>';

echo '</div>';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Security Status | <?php echo htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8'); ?></title>
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

                <?php echo flashMessage('error'); ?>
                <?php echo flashMessage('success'); ?>

                <div class="stats-cards">
                    <!-- Incident Stats -->
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="alert-triangle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo htmlspecialchars($stats['incidents']['total'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p>Total Incidents</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="clock"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo htmlspecialchars($stats['incidents']['open'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p>Open Incidents</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="check-circle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo htmlspecialchars($stats['incidents']['resolved'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p>Resolved Incidents</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="alert-triangle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo htmlspecialchars($stats['incidents']['critical'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p>Critical Incidents</p>
                        </div>
                    </div>

                    <!-- Guard Stats -->
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="shield"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo htmlspecialchars($stats['guards']['active'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p>Active Guards</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="users"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo htmlspecialchars($stats['guards']['total'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p>Total Guards</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="star"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo htmlspecialchars($stats['guards']['avg_rating'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p>Avg Guard Rating</p>
                        </div>
                    </div>

                    <!-- Guard Requests -->
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="file-text"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo htmlspecialchars($stats['requests']['total'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p>Total Requests</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="clock"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo htmlspecialchars($stats['requests']['pending'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p>Pending Requests</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="check-circle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo htmlspecialchars($stats['requests']['approved'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p>Approved Requests</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="shield"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo htmlspecialchars($stats['requests']['guards_requested'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p>Guards Requested</p>
                        </div>
                    </div>

                    <!-- Analytics -->
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="trending-up"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $utilization['utilization_rate']; ?>%</h3>
                            <p>Guard Utilization</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="check-circle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo htmlspecialchars($stats['attendance']['rate'], ENT_QUOTES, 'UTF-8'); ?>%</h3>
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
                        <?php if (!empty($riskData)): ?>
                        <ul class="risk-list">
                            <?php foreach ($riskData as $risk): ?>
                            <li>
                                <span class="badge badge-<?php echo htmlspecialchars(strtolower($risk['severity']), ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars(ucfirst($risk['severity']), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <span><?php echo htmlspecialchars($risk['count'], ENT_QUOTES, 'UTF-8'); ?> incidents</span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else: ?>
                        <p>No incident severity data available.</p>
                        <?php endif; ?>
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
                                <strong><?php echo htmlspecialchars($location['location_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <span class="badge badge-danger"><?php echo htmlspecialchars($location['incident_count'], ENT_QUOTES, 'UTF-8'); ?> incidents</span>
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
                                        <td><?php echo htmlspecialchars($report['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($report['location_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo htmlspecialchars(strtolower($report['severity']), ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars(ucfirst($report['severity']), ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars(formatDate($report['incident_time']), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo htmlspecialchars(strtolower($report['status']), ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars(ucfirst($report['status']), ENT_QUOTES, 'UTF-8'); ?>
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
                    flex-wrap: wrap;
                }
                .risk-list li {
                    background: #f8f9fa;
                    padding: 1rem;
                    border-radius: 8px;
                    flex: 1;
                    min-width: 200px;
                    text-align: center;
                    font-weight: 600;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                .location-risk-list {
                    list-style: none;
                    padding: 0;
                }
                .location-risk-list li {
                    padding: 0.75rem 0;
                    border-bottom: 1px solid #eee;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                .badge-danger {
                    background-color: #f44336;
                    color: white;
                    padding: 0.25rem 0.5rem;
                    border-radius: 4px;
                    font-size: 0.875rem;
                }
                </style>

                <script>
                    // Initialize Lucide icons
                    document.addEventListener('DOMContentLoaded', function() {
                        lucide.createIcons();
                        
                        // Add any additional JavaScript functionality here
                        console.log('Dashboard loaded successfully');
                    });
                </script>
            </div>
        </main>
    </div>
</body>
</html>