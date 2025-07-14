<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('organization');

// Get organization details
$orgRow = executeQuery("SELECT id, user_id FROM organizations WHERE user_id = ?", [$_SESSION['user_id']], ['single' => true]);
$organizationId = $orgRow['id'] ?? null;
$orgUserId = $orgRow['user_id'] ?? null;

if (!$organizationId || !$orgUserId) {
    die('Organization not found for current user.');
}

// 1. Incident Trends - FIXED
$incidentTrends = executeQuery("
    SELECT 
        DATE_FORMAT(i.incident_time, '%Y-%m') AS month,
        COUNT(*) AS count,
        i.severity
    FROM incidents i
    JOIN locations l ON i.location_id = l.id
    WHERE l.user_id = ?
    GROUP BY DATE_FORMAT(i.incident_time, '%Y-%m'), i.severity
    ORDER BY month DESC
", [$orgUserId]) ?: [];

// 2. Guard Performance - FIXED (assumes guards work at organization's locations)
$guardPerformance = executeQuery("
    SELECT 
        AVG(pe.overall_rating) as avg_rating,
        COUNT(*) as total_evaluations,
        MONTH(pe.evaluation_date) as month
    FROM performance_evaluations pe
    JOIN guards g ON pe.guard_id = g.id
    JOIN duty_assignments da ON g.id = da.guard_id
    JOIN locations l ON da.location_id = l.id
    WHERE l.user_id = ? AND pe.evaluation_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY MONTH(pe.evaluation_date)
    ORDER BY month
", [$orgUserId]) ?: [];

// 3. Attendance Analytics - FIXED
$attendanceStats = executeQuery("
    SELECT 
        a.status, 
        COUNT(*) as count,
        ROUND((COUNT(*) * 100.0 / (
            SELECT COUNT(*) 
            FROM attendance a2
            JOIN duty_assignments da2 ON a2.duty_assignment_id = da2.id
            JOIN locations l2 ON da2.location_id = l2.id
            WHERE l2.user_id = ? AND a2.check_in_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        )), 2) as percentage
    FROM attendance a
    JOIN duty_assignments da ON a.duty_assignment_id = da.id
    JOIN locations l ON da.location_id = l.id
    WHERE l.user_id = ? AND a.check_in_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY a.status
", [$orgUserId, $orgUserId]) ?: [];

// 4. Location Incidents - FIXED
$locationIncidents = executeQuery("
    SELECT 
        l.name as location_name,
        COUNT(i.id) as incident_count
    FROM locations l
    LEFT JOIN incidents i ON l.id = i.location_id
    WHERE l.user_id = ?
    GROUP BY l.id, l.name
    ORDER BY incident_count DESC
    LIMIT 10
", [$orgUserId]) ?: [];

// 5. Response Time Analytics - FIXED
$responseTimeData = executeQuery("
    SELECT 
        i.severity,
        AVG(TIMESTAMPDIFF(HOUR, i.created_at, 
            CASE 
                WHEN i.status = 'investigating' THEN i.updated_at
                WHEN i.status = 'resolved' THEN i.updated_at
                ELSE NOW()
            END)) as avg_response_hours
    FROM incidents i
    JOIN locations l ON i.location_id = l.id
    WHERE l.user_id = ? AND i.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY i.severity
", [$orgUserId]) ?: [];

// 6. Guard Utilization - FIXED
$guardUtilization = executeQuery("
    SELECT 
        COUNT(DISTINCT da.guard_id) as active_guards,
        (SELECT COUNT(DISTINCT da2.guard_id) 
         FROM duty_assignments da2
         JOIN locations l2 ON da2.location_id = l2.id
         WHERE l2.user_id = ?) as total_guards,
        ROUND((COUNT(DISTINCT da.guard_id) * 100.0 / 
            (SELECT COUNT(DISTINCT da3.guard_id) 
             FROM duty_assignments da3
             JOIN locations l3 ON da3.location_id = l3.id
             WHERE l3.user_id = ?)), 2) as utilization_rate
    FROM duty_assignments da
    JOIN locations l ON da.location_id = l.id
    WHERE l.user_id = ? AND da.status = 'active' 
    AND CURDATE() BETWEEN da.start_date AND IFNULL(da.end_date, CURDATE())
", [$orgUserId, $orgUserId, $orgUserId]) ?: [];

$utilization = $guardUtilization[0] ?? ['active_guards' => 0, 'total_guards' => 0, 'utilization_rate' => 0];


// Debug output
echo '<div style="background:#f5f5f5;padding:20px;margin:20px;border:1px solid #ddd;">';
echo '<h3>Debug Data - Organization ID: '.htmlspecialchars($organizationId).'</h3>';

echo '<h4>$incidentTrends</h4>';
echo '<pre>'.print_r($incidentTrends, true).'</pre>';

echo '<h4>$guardPerformance</h4>';
echo '<pre>'.print_r($guardPerformance, true).'</pre>';

echo '<h4>$attendanceStats</h4>';
echo '<pre>'.print_r($attendanceStats, true).'</pre>';

echo '<h4>$locationIncidents</h4>';
echo '<pre>'.print_r($locationIncidents, true).'</pre>';

echo '<h4>$responseTimeData</h4>';
echo '<pre>'.print_r($responseTimeData, true).'</pre>';

echo '<h4>$guardUtilization</h4>';
echo '<pre>'.print_r($guardUtilization, true).'</pre>';

echo '<h4>$utilization</h4>';
echo '<pre>'.print_r($utilization, true).'</pre>';

echo '</div>';
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

        // Helper function to safely parse JSON data
        function safeParse(json) {
            try {
                const data = JSON.parse(json);
                if (!Array.isArray(data)) {
                    console.error('Expected array but got:', typeof data);
                    return [];
                }
                return data;
            } catch (e) {
                console.error('JSON parse error:', e);
                return [];
            }
        }

        // Helper function to safely access array values
        function safeGet(array, key, defaultValue = null) {
            if (!Array.isArray(array)) return defaultValue;
            return array.map(item => item[key] ?? defaultValue);
        }

        // Incident Trends Chart
        const incidentData = safeGet(<?php echo json_encode($incidentTrends); ?>);
        const incidentCtx = document.getElementById('incidentTrendsChart')?.getContext('2d');
        
        if (incidentCtx) {
            const incidentMonths = [...new Set(safeGet(incidentData, 'month', ''))].sort();
            const incidentCounts = incidentMonths.map(month => {
                return incidentData
                    .filter(d => d.month === month)
                    .reduce((sum, curr) => sum + parseInt(curr.count || 0), 0);
            });

            new Chart(incidentCtx, {
                type: 'line',
                data: {
                    labels: incidentMonths,
                    datasets: [{
                        label: 'Total Incidents',
                        data: incidentCounts,
                        borderColor: '#1a237e',
                        backgroundColor: 'rgba(26, 35, 126, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Incidents'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Month'
                            }
                        }
                    }
                }
            });
        }

        // Attendance Chart
        const attendanceData = safeParse(<?php echo json_encode($attendanceStats); ?>);
        const attendanceCtx = document.getElementById('attendanceChart')?.getContext('2d');
        
        if (attendanceCtx) {
            const statusLabels = safeGet(attendanceData, 'status', 'unknown').map(
                status => status.charAt(0).toUpperCase() + status.slice(1)
            );
            const statusCounts = safeGet(attendanceData, 'count', 0);

            new Chart(attendanceCtx, {
                type: 'doughnut',
                data: {
                    labels: statusLabels,
                    datasets: [{
                        data: statusCounts,
                        backgroundColor: ['#4caf50', '#ff9800', '#f44336', '#9e9e9e'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
        }

        // Performance Chart
        const performanceData = safeParse(<?php echo json_encode($guardPerformance); ?>);
        const performanceCtx = document.getElementById('performanceChart')?.getContext('2d');
        
        if (performanceCtx) {
            const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
            const months = safeGet(performanceData, 'month', 1).map(m => monthNames[m-1] || `Month ${m}`);
            const ratings = safeGet(performanceData, 'avg_rating', 0);

            new Chart(performanceCtx, {
                type: 'bar',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Average Rating',
                        data: ratings,
                        backgroundColor: '#0288d1',
                        borderColor: '#01579b',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 5,
                            title: {
                                display: true,
                                text: 'Rating (1-5)'
                            }
                        }
                    }
                }
            });
        }

        // Response Time Chart
        const responseData = safeParse(<?php echo json_encode($responseTimeData); ?>);
        const responseCtx = document.getElementById('responseTimeChart')?.getContext('2d');
        
        if (responseCtx) {
            const severities = safeGet(responseData, 'severity', 'unknown').map(
                s => s.charAt(0).toUpperCase() + s.slice(1)
            );
            const responseTimes = safeGet(responseData, 'avg_response_hours', 0);

            new Chart(responseCtx, {
                type: 'bar',
                data: {
                    labels: severities,
                    datasets: [{
                        label: 'Avg Response Time (Hours)',
                        data: responseTimes,
                        backgroundColor: ['#4caf50', '#ff9800', '#f44336', '#9c27b0'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Hours'
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
