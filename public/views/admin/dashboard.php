<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Verify authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../login.php');
    exit();
}

$users = executeQuery("SELECT * FROM users LIMIT 10");

// Check if user is logged in and has admin role
requireRole('admin');

// Get counts for dashboard
$counts = [
    'organizations' => 0,
    'guards' => 0,
    'locations' => 0,
    'incidents' => 0
];

// Get organizations count
$query = "SELECT COUNT(*) as count FROM organizations";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $counts['organizations'] = $row['count'];
}

// Get guards count
$query = "SELECT COUNT(*) as count FROM guards";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $counts['guards'] = $row['count'];
}

// Get locations count
$query = "SELECT COUNT(*) as count FROM locations";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $counts['locations'] = $row['count'];
}

// Get incidents count
$query = "SELECT COUNT(*) as count FROM incidents";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $counts['incidents'] = $row['count'];
}

// Get recent incidents
$query = "SELECT i.*, u.name as reporter_name, l.name as location_name 
          FROM incidents i 
          JOIN users u ON i.reported_by = u.id 
          JOIN locations l ON i.location_id = l.id 
          ORDER BY i.created_at DESC 
          LIMIT 5";
$recentIncidents = executeQuery($query);

// Get recent guard assignments
$query = "SELECT da.*, g.id_number, u.name as guard_name, l.name as location_name, s.name as shift_name 
          FROM duty_assignments da 
          JOIN guards g ON da.guard_id = g.id 
          JOIN users u ON g.user_id = u.id 
          JOIN locations l ON da.location_id = l.id 
          JOIN shifts s ON da.shift_id = s.id 
          ORDER BY da.created_at DESC 
          LIMIT 5";
$recentAssignments = executeQuery($query);

// Get pending guard requests
$query = "SELECT gr.*, o.name as organization_name, l.name as location_name, s.name as shift_name 
          FROM guard_requests gr 
          JOIN organizations o ON gr.organization_id = o.id 
          JOIN locations l ON gr.location_id = l.id 
          JOIN shifts s ON gr.shift_id = s.id 
          WHERE gr.status = 'pending' 
          ORDER BY gr.created_at DESC 
          LIMIT 5";
$pendingRequests = executeQuery($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include '../includes/admin-sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Navigation -->
            <?php include '../includes/top-nav.php'; ?>
            
            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <div class="dashboard-header">
                    <h1>Dashboard</h1>
                    <p>Welcome back, <?php echo $_SESSION['name']; ?>!</p>
                </div>
                
                <!-- Stats Cards -->
                <div class="stats-cards">
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="building-2"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $counts['organizations']; ?></h3>
                            <p>Organizations</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="users"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $counts['guards']; ?></h3>
                            <p>Guards</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="map-pin"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $counts['locations']; ?></h3>
                            <p>Locations</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="alert-triangle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $counts['incidents']; ?></h3>
                            <p>Incidents</p>
                        </div>
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
                            <p class="no-data">No recent incidents to display.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Guard Assignments -->
                <div class="card dashboard-card">
                    <div class="card-header">
                        <h2>Recent Guard Assignments</h2>
                        <a href="duty-assignments.php" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recentAssignments)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Guard</th>
                                            <th>ID Number</th>
                                            <th>Location</th>
                                            <th>Shift</th>
                                            <th>Start Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentAssignments as $assignment): ?>
                                            <tr>
                                                <td><?php echo sanitize($assignment['guard_name']); ?></td>
                                                <td><?php echo sanitize($assignment['id_number']); ?></td>
                                                <td><?php echo sanitize($assignment['location_name']); ?></td>
                                                <td><?php echo sanitize($assignment['shift_name']); ?></td>
                                                <td><?php echo formatDate($assignment['start_date'], 'd M Y'); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo getAssignmentStatusClass($assignment['status']); ?>">
                                                        <?php echo ucfirst(sanitize($assignment['status'])); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="no-data">No recent guard assignments to display.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Pending Guard Requests -->
                <div class="card dashboard-card">
                    <div class="card-header">
                        <h2>Pending Guard Requests</h2>
                        <a href="guard-requests.php" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($pendingRequests)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Organization</th>
                                            <th>Location</th>
                                            <th>Guards Needed</th>
                                            <th>Shift</th>
                                            <th>Start Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pendingRequests as $request): ?>
                                            <tr>
                                                <td><?php echo sanitize($request['organization_name']); ?></td>
                                                <td><?php echo sanitize($request['location_name']); ?></td>
                                                <td><?php echo sanitize($request['number_of_guards']); ?></td>
                                                <td><?php echo sanitize($request['shift_name']); ?></td>
                                                <td><?php echo formatDate($request['start_date'], 'd M Y'); ?></td>
                                                <td>
                                                    <a href="process-request.php?id=<?php echo $request['id']; ?>&action=approve" class="btn btn-sm btn-success">Approve</a>
                                                    <a href="process-request.php?id=<?php echo $request['id']; ?>&action=reject" class="btn btn-sm btn-danger">Reject</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="no-data">No pending guard requests to display.</p>
                        <?php endif; ?>
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
        
        function getAssignmentStatusClass($status) {
            switch ($status) {
                case 'active':
                    return 'success';
                case 'completed':
                    return 'secondary';
                case 'cancelled':
                    return 'danger';
                default:
                    return 'secondary';
            }
        }
        ?>
    </script>
</body>
</html>