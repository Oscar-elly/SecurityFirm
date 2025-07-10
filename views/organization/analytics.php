<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('organization');

$organizationId = $_SESSION['user_id'] ?? null;

if (!$organizationId) {
    header('Location: ../login.php');
    exit;
}

// Monthly incident trends for organization
$incidentTrends = executeQuery("
    SELECT DATE_FORMAT(incident_time, '%Y-%m') as month, 
           COUNT(*) as count,
           severity
    FROM incidents 
    WHERE organization_id = ? AND incident_time >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(incident_time, '%Y-%m'), severity
    ORDER BY month DESC
", [$organizationId]);

// Guard performance analytics for organization
$guardPerformance = executeQuery("
    SELECT AVG(overall_rating) as avg_rating,
           COUNT(*) as total_evaluations,
           MONTH(evaluation_date) as month
    FROM performance_evaluations pe
    JOIN guards g ON pe.guard_id = g.id
    WHERE g.organization_id = ? AND evaluation_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY MONTH(evaluation_date)
    ORDER BY month
", [$organizationId]);

// Attendance analytics for organization
$attendanceStats = executeQuery("
    SELECT status, COUNT(*) as count,
           ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM attendance a JOIN duty_assignments da ON a.duty_assignment_id = da.id JOIN guards g ON da.guard_id = g.id WHERE g.organization_id = ?)), 2) as percentage
    FROM attendance a
    JOIN duty_assignments da ON a.duty_assignment_id = da.id
    JOIN guards g ON da.guard_id = g.id
    WHERE g.organization_id = ? AND check_in_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY status
", [$organizationId, $organizationId]);

if ($attendanceStats === false) {
    $attendanceStats = [];
}

// Location incident distribution for organization
// Location incident distribution for organization
$locationIncidents = executeQuery("
    SELECT l.name as location_name,
           COUNT(i.id) as incident_count
    FROM locations l
    LEFT JOIN incidents i ON l.id = i.location_id
    WHERE l.organization_id = ?
    GROUP BY l.id, l.name
    ORDER BY incident_count DESC
    LIMIT 10
", [$organizationId]) ?: [];

if ($locationIncidents === false) {
    $locationIncidents = [];
}

// Response time analytics for organization
$responseTimeData = executeQuery("
    SELECT severity,
           AVG(TIMESTAMPDIFF(HOUR, created_at, 
               CASE 
                   WHEN status = 'investigating' THEN updated_at
                   WHEN status = 'resolved' THEN updated_at
                   ELSE NOW()
               END)) as avg_response_hours
    FROM incidents 
    WHERE organization_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY severity
", [$organizationId]);

if ($responseTimeData === false) {
    $responseTimeData = [];
}

// Guard utilization for organization
$guardUtilization = executeQuery("
    SELECT COUNT(DISTINCT da.guard_id) as active_guards,
           (SELECT COUNT(*) FROM guards WHERE organization_id = ?) as total_guards,
           ROUND((COUNT(DISTINCT da.guard_id) * 100.0 / (SELECT COUNT(*) FROM guards WHERE organization_id = ?)), 2) as utilization_rate
    FROM duty_assignments da
    JOIN guards g ON da.guard_id = g.id
    WHERE g.organization_id = ? AND da.status = 'active' 
    AND CURDATE() BETWEEN da.start_date AND IFNULL(da.end_date, CURDATE())
", [$organizationId, $organizationId, $organizationId]);

$utilization = $guardUtilization[0] ?? ['active_guards' => 0, 'total_guards' => 0, 'utilization_rate' => 0];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Security Analytics | <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../../assets/css/styles.css" />
    <link rel="stylesheet" href="../../assets/css/dashboard.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/organization-sidebar.php'; ?>

        <main class="main-content">
            <?php include '../includes/top-nav.php'; ?>

            <div class="dashboard-content">
                <div class="dashboard-header">
                    <h1>Security Analytics</h1>
                    <p>Comprehensive security analytics and insights for your organization</p>
                </div>

                <!-- Key Metrics -->
                <div class="stats-cards">
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="shield"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $utilization['utilization_rate']; ?>%</h3>
                            <p>Guard Utilization</p>
                        </div>
                    </div>

                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="trending-up"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo !empty($guardPerformance) ? round(array_sum(array_column($guardPerformance, 'avg_rating')) / count($guardPerformance), 1) : 'N/A'; ?></h3>
                            <p>Avg Performance</p>
                        </div>
                    </div>

                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="clock"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo !empty($responseTimeData) ? round(array_sum(array_column($responseTimeData, 'avg_response_hours')) / count($responseTimeData), 1) : 'N/A'; ?>h</h3>
                            <p>Avg Response Time</p>
                        </div>
                    </div>

                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="check-circle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php 
                            $presentRate = 0;
                            foreach ($attendanceStats as $stat) {
                                if ($stat['status'] === 'present') {
                                    $presentRate = $stat['percentage'];
                                    break;
                                }
                            }
                            echo $presentRate;
                            ?>%</h3>
                            <p>Attendance Rate</p>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 1 -->
                <div class="charts-row">
                    <div class="card chart-card">
                        <div class="card-header">
                            <h2>Incident Trends (Last 12 Months)</h2>
                        </div>
                        <div class="card-body">
                            <canvas id="incidentTrendsChart"></canvas>
                        </div>
                    </div>

                    <div class="card chart-card">
                        <div class="card-header">
                            <h2>Attendance Distribution</h2>
                        </div>
                        <div class="card-body">
                            <canvas id="attendanceChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 2 -->
                <div class="charts-row">
                    <div class="card chart-card">
                        <div class="card-header">
                            <h2>Guard Performance Trends</h2>
                        </div>
                        <div class="card-body">
                            <canvas id="performanceChart"></canvas>
                        </div>
                    </div>

                    <div class="card chart-card">
                        <div class="card-header">
                            <h2>Response Time by Severity</h2>
                        </div>
                        <div class="card-body">
                            <canvas id="responseTimeChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Location Incidents Table -->
                <div class="card">
                    <div class="card-header">
                        <h2>Incident Distribution by Location</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Location</th>
                                        <th>Incident Count</th>
                                        <th>Risk Level</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if (is_array($locationIncidents) && !empty($locationIncidents)) {
                                        foreach ($locationIncidents as $location): 
                                            // Ensure we have valid data before trying to access array elements
                                            $locationName = isset($location['name']) ? sanitize($location['name']) : 'Unknown Location';
                                            $incidentCount = isset($location['incident_count']) ? (int)$location['incident_count'] : 0;
                                            ?>
                                            <tr>
                                                <td><?php echo $locationName; ?></td>
                                                <td><?php echo $incidentCount; ?></td>
                                                <td>
                                                    <?php 
                                                    $riskLevel = 'Low';
                                                    $riskClass = 'success';
                                                    if ($incidentCount > 10) {
                                                        $riskLevel = 'High';
                                                        $riskClass = 'danger';
                                                    } elseif ($incidentCount > 5) {
                                                        $riskLevel = 'Medium';
                                                        $riskClass = 'warning';
                                                    }
                                                    ?>
                                                    <span class="badge badge-<?php echo $riskClass; ?>"><?php echo $riskLevel; ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; 
                                    } else {
                                        echo '<tr><td colspan="3">No data available</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
    .charts-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .chart-card {
        min-height: 400px;
    }
    
    .chart-card .card-body {
        padding: 1rem;
        height: 300px;
    }
    
    @media (max-width: 768px) {
        .charts-row {
            grid-template-columns: 1fr;
        }
    }
    </style>

    <script>
        lucide.createIcons();
        
        // Incident Trends Chart
        const incidentData = <?php echo json_encode($incidentTrends); ?>;
        const incidentCtx = document.getElementById('incidentTrendsChart').getContext('2d');
        new Chart(incidentCtx, {
            type: 'line',
            data: {
                labels: [...new Set(incidentData.map(d => d.month))].sort(),
                datasets: [{
                    label: 'Total Incidents',
                    data: incidentData.reduce((acc, curr) => {
                        acc[curr.month] = (acc[curr.month] || 0) + parseInt(curr.count);
                        return acc;
                    }, {}),
                    borderColor: '#1a237e',
                    backgroundColor: 'rgba(26, 35, 126, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Attendance Chart
        const attendanceData = <?php echo json_encode($attendanceStats); ?>;
        const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
        new Chart(attendanceCtx, {
            type: 'doughnut',
            data: {
                labels: attendanceData.map(d => d.status.charAt(0).toUpperCase() + d.status.slice(1)),
                datasets: [{
                    data: attendanceData.map(d => d.count),
                    backgroundColor: ['#4caf50', '#ff9800', '#f44336', '#9e9e9e']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
        
        // Performance Chart
        const performanceData = <?php echo json_encode($guardPerformance); ?>;
        const performanceCtx = document.getElementById('performanceChart').getContext('2d');
        new Chart(performanceCtx, {
            type: 'bar',
            data: {
                labels: performanceData.map(d => 'Month ' + d.month),
                datasets: [{
                    label: 'Average Rating',
                    data: performanceData.map(d => d.avg_rating),
                    backgroundColor: '#0288d1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 5
                    }
                }
            }
        });
        
        // Response Time Chart
        const responseData = <?php echo json_encode($responseTimeData); ?>;
        const responseCtx = document.getElementById('responseTimeChart').getContext('2d');
        new Chart(responseCtx, {
            type: 'bar',
            data: {
                labels: responseData.map(d => d.severity.charAt(0).toUpperCase() + d.severity.slice(1)),
                datasets: [{
                    label: 'Average Response Time (Hours)',
                    data: responseData.map(d => d.avg_response_hours),
                    backgroundColor: ['#4caf50', '#ff9800', '#f44336', '#9c27b0']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
