<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('guard');

// Get guard information
$userId = $_SESSION['user_id'];
$query = "SELECT g.* FROM guards g JOIN users u ON g.user_id = u.id WHERE g.user_id = ?";
$guard = executeQuery($query, [$userId], ['single' => true]);

if (!$guard) {
    $_SESSION['error'] = 'Guard information not found';
    redirect(SITE_URL);
}

// Get all duty assignments for this guard
$query = "SELECT da.*, l.name as location_name, l.address as location_address, 
                 o.name as organization_name, s.name as shift_name, s.start_time, s.end_time
          FROM duty_assignments da 
          JOIN locations l ON da.location_id = l.id 
          JOIN organizations o ON l.organization_id = o.id 
          JOIN shifts s ON da.shift_id = s.id 
          WHERE da.guard_id = ? 
          ORDER BY da.start_date DESC";
$assignments = executeQuery($query, [$guard['id']]);

// Separate assignments by status
$activeAssignments = [];
$upcomingAssignments = [];
$completedAssignments = [];

foreach ($assignments as $assignment) {
    if ($assignment['status'] === 'active') {
        $startDate = strtotime($assignment['start_date']);
        $endDate = $assignment['end_date'] ? strtotime($assignment['end_date']) : null;
        $today = strtotime(date('Y-m-d'));
        
        if ($startDate <= $today && (!$endDate || $endDate >= $today)) {
            $activeAssignments[] = $assignment;
        } elseif ($startDate > $today) {
            $upcomingAssignments[] = $assignment;
        }
    } else {
        $completedAssignments[] = $assignment;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule | <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/guard-dashboard.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/guard-sidebar.php'; ?>
        
        <main class="main-content">
            <?php include '../includes/top-nav.php'; ?>
            
            <div class="dashboard-content">
                <div class="dashboard-header">
                    <h1>My Schedule</h1>
                    <p>View all your duty assignments</p>
                </div>
                
                <!-- Active Assignments -->
                <div class="card dashboard-card">
                    <div class="card-header">
                        <h2>Active Assignments</h2>
                        <span class="badge badge-success"><?php echo count($activeAssignments); ?> Active</span>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($activeAssignments)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Organization</th>
                                            <th>Location</th>
                                            <th>Shift</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activeAssignments as $assignment): ?>
                                        <tr>
                                            <td><?php echo sanitize($assignment['organization_name']); ?></td>
                                            <td>
                                                <strong><?php echo sanitize($assignment['location_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo sanitize($assignment['location_address']); ?></small>
                                            </td>
                                            <td>
                                                <?php echo sanitize($assignment['shift_name']); ?><br>
                                                <small><?php echo formatTime($assignment['start_time']); ?> - <?php echo formatTime($assignment['end_time']); ?></small>
                                            </td>
                                            <td><?php echo formatDate($assignment['start_date'], 'd M Y'); ?></td>
                                            <td><?php echo $assignment['end_date'] ? formatDate($assignment['end_date'], 'd M Y') : 'Ongoing'; ?></td>
                                            <td>
                                                <a href="view-duty.php?id=<?php echo $assignment['id']; ?>" class="btn btn-sm btn-outline">
                                                    <i data-lucide="eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="no-duty">
                                <div class="no-duty-icon">
                                    <i data-lucide="calendar-x"></i>
                                </div>
                                <p>No active assignments at the moment.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Upcoming Assignments -->
                <div class="card dashboard-card">
                    <div class="card-header">
                        <h2>Upcoming Assignments</h2>
                        <span class="badge badge-primary"><?php echo count($upcomingAssignments); ?> Upcoming</span>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($upcomingAssignments)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Organization</th>
                                            <th>Location</th>
                                            <th>Shift</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Days Until Start</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($upcomingAssignments as $assignment): ?>
                                        <tr>
                                            <td><?php echo sanitize($assignment['organization_name']); ?></td>
                                            <td>
                                                <strong><?php echo sanitize($assignment['location_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo sanitize($assignment['location_address']); ?></small>
                                            </td>
                                            <td>
                                                <?php echo sanitize($assignment['shift_name']); ?><br>
                                                <small><?php echo formatTime($assignment['start_time']); ?> - <?php echo formatTime($assignment['end_time']); ?></small>
                                            </td>
                                            <td><?php echo formatDate($assignment['start_date'], 'd M Y'); ?></td>
                                            <td><?php echo $assignment['end_date'] ? formatDate($assignment['end_date'], 'd M Y') : 'Ongoing'; ?></td>
                                            <td>
                                                <?php 
                                                $daysUntil = round((strtotime($assignment['start_date']) - time()) / 86400);
                                                echo $daysUntil . ' day' . ($daysUntil != 1 ? 's' : '');
                                                ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="no-duty">
                                <div class="no-duty-icon">
                                    <i data-lucide="calendar"></i>
                                </div>
                                <p>No upcoming assignments scheduled.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Completed Assignments -->
                <div class="card dashboard-card">
                    <div class="card-header">
                        <h2>Assignment History</h2>
                        <span class="badge badge-secondary"><?php echo count($completedAssignments); ?> Completed</span>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($completedAssignments)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Organization</th>
                                            <th>Location</th>
                                            <th>Shift</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($completedAssignments, 0, 10) as $assignment): ?>
                                        <tr>
                                            <td><?php echo sanitize($assignment['organization_name']); ?></td>
                                            <td>
                                                <strong><?php echo sanitize($assignment['location_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo sanitize($assignment['location_address']); ?></small>
                                            </td>
                                            <td>
                                                <?php echo sanitize($assignment['shift_name']); ?><br>
                                                <small><?php echo formatTime($assignment['start_time']); ?> - <?php echo formatTime($assignment['end_time']); ?></small>
                                            </td>
                                            <td><?php echo formatDate($assignment['start_date'], 'd M Y'); ?></td>
                                            <td><?php echo $assignment['end_date'] ? formatDate($assignment['end_date'], 'd M Y') : 'N/A'; ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo getAssignmentStatusClass($assignment['status']); ?>">
                                                    <?php echo ucfirst($assignment['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="no-duty">
                                <div class="no-duty-icon">
                                    <i data-lucide="history"></i>
                                </div>
                                <p>No assignment history available.</p>
                            </div>
                        <?php endif; ?>
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

<?php
function formatTime($time) {
    return date('h:i A', strtotime($time));
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