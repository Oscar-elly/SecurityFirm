<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('admin');

$errors = [];
$success = '';

// Get request details if coming from request approval
$request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;
$request_data = [];
if ($request_id > 0) {
    $query = "SELECT gr.*, l.name as location_name, s.name as shift_name, 
                     s.start_time, s.end_time, o.name as organization_name
              FROM guard_requests gr
              JOIN locations l ON gr.location_id = l.id
              JOIN shifts s ON gr.shift_id = s.id
              JOIN organizations o ON gr.organization_id = o.id
              WHERE gr.id = ?";
    $request_data = executeQuery($query, [$request_id], ['single' => true]);
    
    if ($request_data) {
        $location_id = $request_data['location_id'];
        $shift_id = $request_data['shift_id'];
        $number_of_guards = $request_data['number_of_guards'];
        $start_date = $request_data['start_date'];
        $end_date = $request_data['end_date'];
        $reason = $request_data['reason'];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $location_id = intval($_POST['location_id']);
    $shift_id = intval($_POST['shift_id']);
    $selected_guards = isset($_POST['guards']) ? $_POST['guards'] : [];
    $start_date = sanitize($_POST['start_date']);
    $end_date = sanitize($_POST['end_date']);
    $reason = sanitize($_POST['reason'] ?? '');
    $request_id = intval($_POST['request_id'] ?? 0);

    if ($location_id <= 0) {
        $errors[] = 'Please select a valid location.';
    }
    if ($shift_id <= 0) {
        $errors[] = 'Please select a valid shift.';
    }
    if (empty($selected_guards)) {
        $errors[] = 'Please select at least one guard to assign.';
    }
    if (empty($start_date)) {
        $errors[] = 'Please select a start date.';
    }

    if (empty($errors)) {
        $assignedCount = 0;
        $created_by = $_SESSION['user_id'];

        foreach ($selected_guards as $guard_id) {
            $queryInsert = "INSERT INTO duty_assignments (guard_id, location_id, shift_id, 
                           start_date, end_date, status, created_at, created_by)
                           VALUES (?, ?, ?, ?, ?, 'active', NOW(), ?)";
            $result = executeQuery($queryInsert, [
                $guard_id, 
                $location_id, 
                $shift_id, 
                $start_date,
                !empty($end_date) ? $end_date : null,
                $created_by
            ]);
            
            if ($result) {
                $assignedCount++;
            }
        }
        
        if ($assignedCount > 0) {
            $success = "Successfully assigned $assignedCount guards.";
            
            // Update request status if this was from a request
            if ($request_id > 0) {
                $queryUpdate = "UPDATE guard_requests SET status = 'completed' WHERE id = ?";
                executeQuery($queryUpdate, [$request_id]);
            }
        } else {
            $errors[] = "Failed to assign any guards.";
        }
    }
}

// Fetch locations and shifts for form dropdowns
$locations = executeQuery("SELECT id, name FROM locations ORDER BY name ASC");
$shifts = executeQuery("SELECT id, name, start_time, end_time FROM shifts ORDER BY name ASC");

// Fetch available guards (not currently assigned to the location and shift)
$availableGuards = [];
if ($location_id > 0) {
    $queryAssigned = "SELECT guard_id FROM duty_assignments 
                     WHERE location_id = ? AND status = 'active'
                     AND (end_date IS NULL OR end_date >= CURDATE())";
    $assignedGuards = executeQuery($queryAssigned, [$location_id]);
    $assignedGuardIds = array_column($assignedGuards, 'guard_id');

    $placeholders = '';
    $params = [];
    if (!empty($assignedGuardIds)) {
        $placeholders = str_repeat('?,', count($assignedGuardIds));
        $placeholders = rtrim($placeholders, ',');
        $params = $assignedGuardIds;
    }

    $queryAvailable = "SELECT g.id, u.name, g.id_number FROM guards g 
                      JOIN users u ON g.user_id = u.id
                      WHERE status = 'active'";
    if (!empty($assignedGuardIds)) {
        $queryAvailable .= " AND g.id NOT IN ($placeholders)";
    }
    $queryAvailable .= " ORDER BY u.name ASC";

    $availableGuards = executeQuery($queryAvailable, $params);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Assign Guards | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/styles.css" />
    <link rel="stylesheet" href="../../assets/css/dashboard.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .guard-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 0.5rem;
            border-radius: 4px;
        }
        .guard-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.25rem;
        }
        .guard-item label {
            margin-left: 0.5rem;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .request-info {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .shift-details {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
        }
        .shift-time {
            background-color: #e9ecef;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/admin-sidebar.php'; ?>

        <main class="main-content">
            <?php include '../includes/top-nav.php'; ?>

            <div class="dashboard-content">
                <div class="dashboard-header">
                    <h1>Assign Guards</h1>
                    <a href="dashboard.php" class="btn btn-outline">
                        <i data-lucide="arrow-left"></i> Back to Dashboard
                    </a>
                </div>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo sanitize($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (!empty($request_data)): ?>
                <div class="request-info">
                    <h3>Request Details</h3>
                    <p><strong>Organization:</strong> <?php echo sanitize($request_data['organization_name']); ?></p>
                    <p><strong>Requested Guards:</strong> <?php echo $request_data['number_of_guards']; ?></p>
                    <p><strong>Reason:</strong> <?php echo sanitize($request_data['reason']); ?></p>
                    <div class="shift-details">
                        <span class="shift-time">
                            <i data-lucide="clock"></i> 
                            <?php echo date('h:i A', strtotime($request_data['start_time'])); ?> - 
                            <?php echo date('h:i A', strtotime($request_data['end_time'])); ?>
                        </span>
                        <span class="shift-time">
                            <i data-lucide="calendar"></i> 
                            <?php echo date('M j, Y', strtotime($request_data['start_date'])); ?>
                            <?php if (!empty($request_data['end_date'])): ?>
                                to <?php echo date('M j, Y', strtotime($request_data['end_date'])); ?>
                            <?php else: ?>
                                (Ongoing)
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
                <?php endif; ?>

                <form method="POST" novalidate>
                    <input type="hidden" name="request_id" value="<?php echo $request_id; ?>">
                    
                    <div class="form-group">
                        <label for="location_id">Location</label>
                        <select id="location_id" name="location_id" required>
                            <option value="">Select Location</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?php echo $location['id']; ?>" 
                                    <?php echo ($location_id == $location['id']) ? 'selected' : ''; ?>>
                                    <?php echo sanitize($location['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="shift_id">Shift</label>
                        <select id="shift_id" name="shift_id" required>
                            <option value="">Select Shift</option>
                            <?php foreach ($shifts as $shift): ?>
                                <option value="<?php echo $shift['id']; ?>" 
                                    data-start="<?php echo $shift['start_time']; ?>"
                                    data-end="<?php echo $shift['end_time']; ?>"
                                    <?php echo (isset($shift_id) && $shift_id == $shift['id']) ? 'selected' : ''; ?>>
                                    <?php echo sanitize($shift['name']); ?>
                                    (<?php echo date('h:i A', strtotime($shift['start_time'])); ?> - 
                                    <?php echo date('h:i A', strtotime($shift['end_time'])); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" 
                               value="<?php echo isset($start_date) ? sanitize($start_date) : date('Y-m-d'); ?>" required />
                    </div>

                    <div class="form-group">
                        <label for="end_date">End Date (Optional)</label>
                        <input type="date" id="end_date" name="end_date" 
                               value="<?php echo isset($end_date) ? sanitize($end_date) : ''; ?>" />
                    </div>

                    <div class="form-group">
                        <label for="reason">Assignment Reason (Optional)</label>
                        <textarea id="reason" name="reason" rows="3"><?php echo isset($reason) ? sanitize($reason) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Available Guards (Select to Assign)</label>
                        <div class="guard-list">
                            <?php if (!empty($availableGuards)): ?>
                                <?php foreach ($availableGuards as $guard): ?>
                                    <div class="guard-item">
                                        <input type="checkbox" id="guard_<?php echo $guard['id']; ?>" 
                                               name="guards[]" value="<?php echo $guard['id']; ?>" />
                                        <label for="guard_<?php echo $guard['id']; ?>">
                                            <?php echo sanitize($guard['name']); ?> (<?php echo sanitize($guard['id_number']); ?>)
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>No available guards found for this location.</p>
                                <p><a href="../guards/add.php" class="btn btn-sm btn-outline">Add New Guards</a></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i data-lucide="save"></i> Assign Selected Guards
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
        
        // Auto-select guards based on number needed when coming from request
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($number_of_guards)): ?>
                const checkboxes = document.querySelectorAll('.guard-item input[type="checkbox"]');
                const needed = <?php echo $number_of_guards; ?>;
                let selected = 0;
                
                for (let i = 0; i < checkboxes.length && selected < needed; i++) {
                    checkboxes[i].checked = true;
                    selected++;
                }
            <?php endif; ?>
            
            // Update shift times display when shift changes
            document.getElementById('shift_id').addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                // You could update a display of shift times here if needed
            });
        });
    </script>
</body>
</html>