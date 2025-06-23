<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('admin');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'assign_duty':
                $guard_id = (int)$_POST['guard_id'];
                $location_id = (int)$_POST['location_id'];
                $shift_id = (int)$_POST['shift_id'];
                $start_date = sanitize($_POST['start_date']);
                $end_date = sanitize($_POST['end_date']);
                $notes = sanitize($_POST['notes']);
                
                $query = "INSERT INTO duty_assignments (guard_id, location_id, shift_id, start_date, end_date, notes, created_by, status) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, 'active')";
                $result = executeQuery($query, [$guard_id, $location_id, $shift_id, $start_date, $end_date, $notes, $_SESSION['user_id']]);
                
                if ($result) {
                    $_SESSION['success'] = 'Duty assignment created successfully';
                } else {
                    $_SESSION['error'] = 'Failed to create duty assignment';
                }
                break;
                
            case 'update_status':
                $assignment_id = (int)$_POST['assignment_id'];
                $status = sanitize($_POST['status']);
                
                $query = "UPDATE duty_assignments SET status = ? WHERE id = ?";
                $result = executeQuery($query, [$status, $assignment_id]);
                
                if ($result) {
                    $_SESSION['success'] = 'Assignment status updated successfully';
                } else {
                    $_SESSION['error'] = 'Failed to update assignment status';
                }
                break;
        }
        redirect($_SERVER['PHP_SELF']);
    }
}

// Get all duty assignments
$query = "SELECT da.*, g.id_number, u.name as guard_name, l.name as location_name, 
                 o.name as organization_name, s.name as shift_name, s.start_time, s.end_time
          FROM duty_assignments da 
          JOIN guards g ON da.guard_id = g.id 
          JOIN users u ON g.user_id = u.id 
          JOIN locations l ON da.location_id = l.id 
          JOIN organizations o ON l.organization_id = o.id 
          JOIN shifts s ON da.shift_id = s.id 
          ORDER BY da.created_at DESC";
$assignments = executeQuery($query);

// Get guards for dropdown
$guardsQuery = "SELECT g.id, g.id_number, u.name FROM guards g JOIN users u ON g.user_id = u.id WHERE u.status = 'active'";
$guards = executeQuery($guardsQuery);

// Get locations for dropdown
$locationsQuery = "SELECT l.id, l.name, o.name as org_name FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE l.status = 'active'";
$locations = executeQuery($locationsQuery);

// Get shifts for dropdown
$shiftsQuery = "SELECT * FROM shifts ORDER BY start_time";
$shifts = executeQuery($shiftsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duty Assignments | <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/admin-sidebar.php'; ?>
        
        <main class="main-content">
            <?php include '../includes/top-nav.php'; ?>
            
            <div class="dashboard-content">
                <div class="dashboard-header">
                    <h1>Duty Assignments</h1>
                    <button class="btn btn-primary" onclick="openAssignModal()">
                        <i data-lucide="plus"></i> New Assignment
                    </button>
                </div>
                
                <?php echo flashMessage('success'); ?>
                <?php echo flashMessage('error'); ?>
                
                <div class="card">
                    <div class="card-header">
                        <h2>All Duty Assignments</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Guard</th>
                                        <th>ID Number</th>
                                        <th>Location</th>
                                        <th>Organization</th>
                                        <th>Shift</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assignments as $assignment): ?>
                                    <tr>
                                        <td><?php echo sanitize($assignment['guard_name']); ?></td>
                                        <td><?php echo sanitize($assignment['id_number']); ?></td>
                                        <td><?php echo sanitize($assignment['location_name']); ?></td>
                                        <td><?php echo sanitize($assignment['organization_name']); ?></td>
                                        <td><?php echo sanitize($assignment['shift_name']); ?> (<?php echo formatTime($assignment['start_time']); ?> - <?php echo formatTime($assignment['end_time']); ?>)</td>
                                        <td><?php echo formatDate($assignment['start_date'], 'd M Y'); ?></td>
                                        <td><?php echo $assignment['end_date'] ? formatDate($assignment['end_date'], 'd M Y') : 'Ongoing'; ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo getAssignmentStatusClass($assignment['status']); ?>">
                                                <?php echo ucfirst($assignment['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($assignment['status'] === 'active'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                                <input type="hidden" name="status" value="completed">
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i data-lucide="check"></i> Complete
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                                <input type="hidden" name="status" value="cancelled">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i data-lucide="x"></i> Cancel
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Assignment Modal -->
    <div id="assignModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>New Duty Assignment</h3>
                <button onclick="closeAssignModal()" class="btn btn-sm btn-outline">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="assign_duty">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="guard_id">Select Guard</label>
                        <select id="guard_id" name="guard_id" required>
                            <option value="">Choose a guard</option>
                            <?php foreach ($guards as $guard): ?>
                            <option value="<?php echo $guard['id']; ?>">
                                <?php echo sanitize($guard['name']) . ' (' . sanitize($guard['id_number']) . ')'; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="location_id">Select Location</label>
                        <select id="location_id" name="location_id" required>
                            <option value="">Choose a location</option>
                            <?php foreach ($locations as $location): ?>
                            <option value="<?php echo $location['id']; ?>">
                                <?php echo sanitize($location['name']) . ' - ' . sanitize($location['org_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="shift_id">Select Shift</label>
                        <select id="shift_id" name="shift_id" required>
                            <option value="">Choose a shift</option>
                            <?php foreach ($shifts as $shift): ?>
                            <option value="<?php echo $shift['id']; ?>">
                                <?php echo sanitize($shift['name']) . ' (' . formatTime($shift['start_time']) . ' - ' . formatTime($shift['end_time']) . ')'; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date">End Date (Optional)</label>
                        <input type="date" id="end_date" name="end_date">
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" placeholder="Any special instructions or notes"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeAssignModal()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Assignment</button>
                </div>
            </form>
        </div>
    </div>

    <style>
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .modal-content {
        background: white;
        border-radius: 8px;
        width: 90%;
        max-width: 600px;
        max-height: 90vh;
        overflow-y: auto;
    }
    
    .modal-header {
        padding: 1rem;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-body {
        padding: 1rem;
    }
    
    .modal-footer {
        padding: 1rem;
        border-top: 1px solid #eee;
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
    }
    </style>

    <script>
        lucide.createIcons();
        
        function openAssignModal() {
            document.getElementById('assignModal').style.display = 'flex';
        }
        
        function closeAssignModal() {
            document.getElementById('assignModal').style.display = 'none';
        }
        
        function formatTime(time) {
            return new Date('1970-01-01T' + time + 'Z').toLocaleTimeString('en-US', {
                timeZone: 'UTC',
                hour12: true,
                hour: 'numeric',
                minute: '2-digit'
            });
        }
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