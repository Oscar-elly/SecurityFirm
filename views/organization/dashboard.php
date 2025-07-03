<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('organization');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch organization info
$organizationQuery = "SELECT * FROM organizations WHERE user_id = ?";
$organization = executeQuery($organizationQuery, [$userId], ['single' => true]);

// Locations count
$locationsCountQuery = "SELECT COUNT(*) as count FROM locations WHERE organization_id = ?";
$locationsCountResult = executeQuery($locationsCountQuery, [$organization['id']], ['single' => true]);
$locationsCount = $locationsCountResult['count'] ?? 0;

// Active guards count
$activeGuardsCountQuery = "SELECT COUNT(DISTINCT g.id) as count
    FROM duty_assignments da
    JOIN guards g ON da.guard_id = g.id
    JOIN locations l ON da.location_id = l.id
    WHERE l.organization_id = ? AND da.status = 'active' AND CURDATE() BETWEEN da.start_date AND IFNULL(da.end_date, CURDATE())";
$activeGuardsCountResult = executeQuery($activeGuardsCountQuery, [$organization['id']], ['single' => true]);
$activeGuardsCount = $activeGuardsCountResult['count'] ?? 0;

// Incidents count
$incidentsCountQuery = "SELECT COUNT(*) as count FROM incidents i
    JOIN locations l ON i.location_id = l.id
    WHERE l.organization_id = ?";
$incidentsCountResult = executeQuery($incidentsCountQuery, [$organization['id']], ['single' => true]);
$incidentsCount = $incidentsCountResult['count'] ?? 0;

// Guard requests count
$requestsCountQuery = "SELECT COUNT(*) as count FROM guard_requests WHERE organization_id = ?";
$requestsCountResult = executeQuery($requestsCountQuery, [$organization['id']], ['single' => true]);
$requestsCount = $requestsCountResult['count'] ?? 0;

// Get active guards details
$activeGuardsQuery = "SELECT u.name, g.id_number, l.name as location_name, s.name as shift_name, 
                 da.start_date, da.end_date 
          FROM duty_assignments da 
          JOIN guards g ON da.guard_id = g.id 
          JOIN users u ON g.user_id = u.id 
          JOIN locations l ON da.location_id = l.id 
          JOIN shifts s ON da.shift_id = s.id 
          WHERE l.organization_id = ? 
          AND da.status = 'active' 
          AND CURDATE() BETWEEN da.start_date AND IFNULL(da.end_date, CURDATE())";
$activeGuards = executeQuery($activeGuardsQuery, [$organization['id']]);

// Get recent incidents
$recentIncidentsQuery = "SELECT i.*, l.name as location_name, u.name as reporter_name 
          FROM incidents i 
          JOIN locations l ON i.location_id = l.id 
          JOIN users u ON i.reported_by = u.id 
          WHERE l.organization_id = ? 
          ORDER BY i.incident_time DESC 
          LIMIT 5";
$recentIncidents = executeQuery($recentIncidentsQuery, [$organization['id']]);

// Get pending guard requests
$pendingRequestsQuery = "SELECT gr.*, l.name as location_name, s.name as shift_name 
          FROM guard_requests gr 
          JOIN locations l ON gr.location_id = l.id 
          JOIN shifts s ON gr.shift_id = s.id 
          WHERE gr.organization_id = ? 
          AND gr.status IN ('pending', 'approved') 
          ORDER BY gr.created_at DESC 
          LIMIT 5";
$pendingRequests = executeQuery($pendingRequestsQuery, [$organization['id']]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Organization Dashboard | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/styles.css" />
    <link rel="stylesheet" href="../../assets/css/dashboard.css" />
    <link rel="stylesheet" href="../../assets/css/organization-dashboard.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include '../includes/organization-sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Navigation -->
            <?php include '../includes/top-nav.php'; ?>
            
            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <div class="dashboard-header">
                    <h1>Welcome, <?php echo sanitize($organization['name']); ?></h1>
                    <p>Your security management dashboard</p>
                </div>
                
                <!-- Stats Cards -->
                <div class="stats-cards">
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="map-pin"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $locationsCount; ?></h3>
                            <p>Locations</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="shield"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $activeGuardsCount; ?></h3>
                            <p>Active Guards</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="alert-triangle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $incidentsCount; ?></h3>
                            <p>Incidents</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="file-text"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $requestsCount; ?></h3>
                            <p>Guard Requests</p>
                        </div>
                    </div>
                </div>
                
                <!-- Security Status Card -->
                <div class="card security-status-card">
                    <div class="card-header">
                        <h2>Security Status</h2>
                        <a href="security-status.php" class="btn btn-sm btn-outline">View Details</a>
                    </div>
                    <div class="card-body">
                        <div class="security-status-grid">
                            <!-- Location Coverage -->
                            <div class="security-status-item">
                                <div class="status-icon <?php echo $activeGuardsCount > 0 ? 'success' : 'warning'; ?>">
                                    <i data-lucide="shield-check"></i>
                                </div>
                                <div class="status-details">
                                    <h3>Location Coverage</h3>
                                    <p><?php echo $activeGuardsCount > 0 ? 'Active security coverage' : 'No active guards on duty'; ?></p>
                                </div>
                            </div>
                            
                            <!-- Recent Incidents Status -->
                            <div class="security-status-item">
                                <div class="status-icon <?php echo empty($recentIncidents) ? 'success' : 'warning'; ?>">
                                    <i data-lucide="alert-triangle"></i>
                                </div>
                                <div class="status-details">
                                    <h3>Incident Status</h3>
                                    <p>
                                        <?php 
                                        if (empty($recentIncidents)) {
                                            echo 'No recent incidents reported';
                                        } else {
                                            $unresolvedCount = 0;
                                            foreach ($recentIncidents as $incident) {
                                                if ($incident['status'] != 'resolved' && $incident['status'] != 'closed') {
                                                    $unresolvedCount++;
                                                }
                                            }
                                            echo $unresolvedCount . ' unresolved incident' . ($unresolvedCount != 1 ? 's' : '');
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Guard Request Status -->
                            <div class="security-status-item">
                                <div class="status-icon <?php echo empty($pendingRequests) ? 'success' : 'info'; ?>">
                                    <i data-lucide="clipboard-list"></i>
                                </div>
                                <div class="status-details">
                                    <h3>Guard Requests</h3>
                                    <p>
                                        <?php 
                                        if (empty($pendingRequests)) {
                                            echo 'No pending guard requests';
                                        } else {
                                            $pendingCount = 0;
                                            foreach ($pendingRequests as $request) {
                                                if ($request['status'] == 'pending') {
                                                    $pendingCount++;
                                                }
                                            }
                                            echo $pendingCount . ' pending request' . ($pendingCount != 1 ? 's' : '');
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Contract Status -->
                            <div class="security-status-item">
                                <div class="status-icon <?php echo strtotime($organization['contract_end_date']) > time() ? 'success' : 'danger'; ?>">
                                    <i data-lucide="calendar"></i>
                                </div>
                                <div class="status-details">
                                    <h3>Contract Status</h3>
                                    <p>
                                        <?php 
                                        if (empty($organization['contract_end_date'])) {
                                            echo 'No contract end date set';
                                        } else {
                                            $daysLeft = round((strtotime($organization['contract_end_date']) - time()) / 86400);
                                            if ($daysLeft > 0) {
                                                echo 'Contract valid for ' . $daysLeft . ' more day' . ($daysLeft != 1 ? 's' : '');
                                            } else {
                                                echo 'Contract expired ' . abs($daysLeft) . ' day' . (abs($daysLeft) != 1 ? 's' : '') . ' ago';
                                            }
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Active Guards -->
                <div class="card dashboard-card">
                    <div class="card-header">
                        <h2>Active Guards</h2>
                        <a href="guards.php" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($activeGuards)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Guard Name</th>
                                            <th>ID Number</th>
                                            <th>Location</th>
                                            <th>Shift</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activeGuards as $guard): ?>
                                            <tr>
                                                <td><?php echo sanitize($guard['name']); ?></td>
                                                <td><?php echo sanitize($guard['id_number']); ?></td>
                                                <td><?php echo sanitize($guard['location_name']); ?></td>
                                                <td><?php echo sanitize($guard['shift_name']); ?></td>
                                                <td><?php echo formatDate($guard['start_date'], 'd M Y'); ?></td>
                                                <td><?php echo $guard['end_date'] ? formatDate($guard['end_date'], 'd M Y') : 'Ongoing'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="no-data">No active guards at your locations.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Incidents -->
                <div class="card dashboard-card">
                    <div class="card-header">
                        <h2>Recent Incidents</h2>
                        <a href="incidents.php" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recentIncidents)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Location</th>
                                            <th>Severity</th>
                                            <th>Reported By</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentIncidents as $incident): ?>
                                            <tr>
                                                <td><?php echo sanitize($incident['title']); ?></td>
                                                <td><?php echo sanitize($incident['location_name']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo getSeverityClass($incident['severity']); ?>">
                                                        <?php echo ucfirst(sanitize($incident['severity'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo sanitize($incident['reporter_name']); ?></td>
                                                <td><?php echo formatDate($incident['incident_time']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo getStatusClass($incident['status']); ?>">
                                                        <?php echo ucfirst(sanitize($incident['status'])); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="no-data">No recent incidents reported.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Security Request Card -->
                <div class="card request-card">
                    <div class="card-header">
                        <h2>Request Security Guards</h2>
                    </div>
                    <div class="card-body">
                        <div class="request-options">
                            <a href="request-guard.php" class="request-option">
                                <div class="request-icon">
                                    <i data-lucide="user-plus"></i>
                                </div>
                                <div class="request-details">
                                    <h3>Regular Guard Request</h3>
                                    <p>Request additional security guards for your locations</p>
                                </div>
                            </a>
                            
                            <a href="request-emergency.php" class="request-option emergency">
                                <div class="request-icon">
                                    <i data-lucide="alert-circle"></i>
                                </div>
                                <div class="request-details">
                                    <h3>Emergency Request</h3>
                                    <p>Request immediate security reinforcement for urgent situations</p>
                                </div>
                            </a>
                            
                            <a href="guard-requests.php" class="request-option">
                                <div class="request-icon">
                                    <i data-lucide="list"></i>
                                </div>
                                <div class="request-details">
                                    <h3>View All Requests</h3>
                                    <p>Check the status of your previous security guard requests</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Helper functions
        <?php
        function getSeverityClass($severity) {
            switch ($severity) {
                case 'low':
                    return 'success';
                case 'medium':
                    return 'warning';
                case 'high':
                    return 'danger';
                case 'critical':
                    return 'danger';
                default:
                    return 'secondary';
            }
        }
        
        function getStatusClass($status) {
            switch ($status) {
                case 'reported':
                    return 'warning';
                case 'investigating':
                    return 'primary';
                case 'resolved':
                    return 'success';
                case 'closed':
                    return 'secondary';
                default:
                    return 'secondary';
            }
        }
        ?>
    </script>
</body>
</html>
