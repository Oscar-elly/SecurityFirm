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
            case 'add_shift':
                $name = sanitize($_POST['name']);
                $start_time = sanitize($_POST['start_time']);
                $end_time = sanitize($_POST['end_time']);
                $description = sanitize($_POST['description']);
                
                $query = "INSERT INTO shifts (name, start_time, end_time, description) VALUES (?, ?, ?, ?)";
                $result = executeQuery($query, [$name, $start_time, $end_time, $description]);
                
                if ($result) {
                    $_SESSION['success'] = 'Shift added successfully';
                } else {
                    $_SESSION['error'] = 'Failed to add shift';
                }
                break;
                
            case 'update_shift':
                $id = (int)$_POST['shift_id'];
                $name = sanitize($_POST['name']);
                $start_time = sanitize($_POST['start_time']);
                $end_time = sanitize($_POST['end_time']);
                $description = sanitize($_POST['description']);
                
                $query = "UPDATE shifts SET name = ?, start_time = ?, end_time = ?, description = ? WHERE id = ?";
                $result = executeQuery($query, [$name, $start_time, $end_time, $description, $id]);
                
                if ($result) {
                    $_SESSION['success'] = 'Shift updated successfully';
                } else {
                    $_SESSION['error'] = 'Failed to update shift';
                }
                break;
                
            case 'delete_shift':
                $id = (int)$_POST['shift_id'];
                
                // Check if shift is being used
                $checkQuery = "SELECT COUNT(*) as count FROM duty_assignments WHERE shift_id = ?";
                $checkResult = executeQuery($checkQuery, [$id], ['single' => true]);
                
                if ($checkResult['count'] > 0) {
                    $_SESSION['error'] = 'Cannot delete shift as it is being used in duty assignments';
                } else {
                    $query = "DELETE FROM shifts WHERE id = ?";
                    $result = executeQuery($query, [$id]);
                    
                    if ($result) {
                        $_SESSION['success'] = 'Shift deleted successfully';
                    } else {
                        $_SESSION['error'] = 'Failed to delete shift';
                    }
                }
                break;
        }
        redirect($_SERVER['PHP_SELF']);
    }
}

// Get all shifts
$query = "SELECT * FROM shifts ORDER BY start_time";
$shifts = executeQuery($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shifts Management | <?php echo SITE_NAME; ?></title>
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
                    <h1>Shifts Management</h1>
                    <button class="btn btn-primary" onclick="openAddShiftModal()">
                        <i data-lucide="plus"></i> Add New Shift
                    </button>
                </div>
                
                <?php echo flashMessage('success'); ?>
                <?php echo flashMessage('error'); ?>
                
                <div class="card">
                    <div class="card-header">
                        <h2>All Shifts</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Shift Name</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                        <th>Duration</th>
                                        <th>Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($shifts as $shift): ?>
                                    <tr>
                                        <td><?php echo sanitize($shift['name']); ?></td>
                                        <td><?php echo formatTime($shift['start_time']); ?></td>
                                        <td><?php echo formatTime($shift['end_time']); ?></td>
                                        <td><?php echo calculateShiftDuration($shift['start_time'], $shift['end_time']); ?></td>
                                        <td><?php echo sanitize($shift['description']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" onclick="editShift(<?php echo htmlspecialchars(json_encode($shift)); ?>)">
                                                <i data-lucide="edit"></i>
                                            </button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this shift?')">
                                                <input type="hidden" name="action" value="delete_shift">
                                                <input type="hidden" name="shift_id" value="<?php echo $shift['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i data-lucide="trash-2"></i>
                                                </button>
                                            </form>
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

    <!-- Add/Edit Shift Modal -->
    <div id="shiftModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Shift</h3>
                <button onclick="closeShiftModal()" class="btn btn-sm btn-outline">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="POST" id="shiftForm">
                <input type="hidden" name="action" id="formAction" value="add_shift">
                <input type="hidden" name="shift_id" id="shiftId">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Shift Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="start_time">Start Time</label>
                        <input type="time" id="start_time" name="start_time" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="end_time">End Time</label>
                        <input type="time" id="end_time" name="end_time" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeShiftModal()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Add Shift</button>
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
        max-width: 500px;
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
        
        function openAddShiftModal() {
            document.getElementById('modalTitle').textContent = 'Add New Shift';
            document.getElementById('formAction').value = 'add_shift';
            document.getElementById('submitBtn').textContent = 'Add Shift';
            document.getElementById('shiftForm').reset();
            document.getElementById('shiftModal').style.display = 'flex';
        }
        
        function editShift(shift) {
            document.getElementById('modalTitle').textContent = 'Edit Shift';
            document.getElementById('formAction').value = 'update_shift';
            document.getElementById('submitBtn').textContent = 'Update Shift';
            document.getElementById('shiftId').value = shift.id;
            document.getElementById('name').value = shift.name;
            document.getElementById('start_time').value = shift.start_time;
            document.getElementById('end_time').value = shift.end_time;
            document.getElementById('description').value = shift.description;
            document.getElementById('shiftModal').style.display = 'flex';
        }
        
        function closeShiftModal() {
            document.getElementById('shiftModal').style.display = 'none';
        }
    </script>
</body>
</html>

<?php
function formatTime($time) {
    return date('h:i A', strtotime($time));
}

function calculateShiftDuration($start, $end) {
    $start_time = strtotime($start);
    $end_time = strtotime($end);
    
    // Handle overnight shifts
    if ($end_time <= $start_time) {
        $end_time += 86400; // Add 24 hours
    }
    
    $duration = $end_time - $start_time;
    $hours = floor($duration / 3600);
    $minutes = floor(($duration % 3600) / 60);
    
    return $hours . 'h ' . $minutes . 'm';
}
?>