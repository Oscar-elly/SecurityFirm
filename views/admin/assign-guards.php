<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('admin');

$errors = [];
$success = '';

$location_id = isset($_GET['location_id']) ? intval($_GET['location_id']) : 0;
$number_of_guards = isset($_GET['number_of_guards']) ? intval($_GET['number_of_guards']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $location_id = intval($_POST['location_id']);
    $shift_id = intval($_POST['shift_id']);
    $selected_guards = isset($_POST['guards']) ? $_POST['guards'] : [];
    $start_date = sanitize($_POST['start_date']);

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
        foreach ($selected_guards as $guard_id) {
            $queryInsert = "INSERT INTO duty_assignments (guard_id, location_id, shift_id, start_date, status, created_at)
                            VALUES (?, ?, ?, ?, 'active', NOW())";
            $result = executeQuery($queryInsert, [$guard_id, $location_id, $shift_id, $start_date]);
            if ($result) {
                $assignedCount++;
            }
        }
        if ($assignedCount === count($selected_guards)) {
            $success = "Successfully assigned $assignedCount guards.";
        } else {
            $errors[] = "Assigned $assignedCount guards, but failed to assign some.";
        }
    }
}

// Fetch locations and shifts for form dropdowns
$locations = executeQuery("SELECT id, name FROM locations ORDER BY name ASC");
$shifts = executeQuery("SELECT id, name FROM shifts ORDER BY name ASC");

// Fetch available guards (not currently assigned to the location and shift)
$availableGuards = [];
if ($location_id > 0) {
    $queryAssigned = "SELECT guard_id FROM duty_assignments WHERE location_id = ? AND status = 'active'";
    $assignedGuards = executeQuery($queryAssigned, [$location_id]);
    $assignedGuardIds = array_column($assignedGuards, 'guard_id');

    $placeholders = '';
    $params = [];
    if (!empty($assignedGuardIds)) {
        $placeholders = str_repeat('?,', count($assignedGuardIds));
        $placeholders = rtrim($placeholders, ',');
        $params = $assignedGuardIds;
    }

    $queryAvailable = "SELECT g.id, u.name, g.id_number FROM guards g JOIN users u ON g.user_id = u.id";
    if (!empty($assignedGuardIds)) {
        $queryAvailable .= " WHERE g.id NOT IN ($placeholders)";
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

                <form method="POST" novalidate>
                    <div class="form-group">
                        <label for="location_id">Location</label>
                        <select id="location_id" name="location_id" required>
                            <option value="">Select Location</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?php echo $location['id']; ?>" <?php echo ($location_id == $location['id']) ? 'selected' : ''; ?>>
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
                                <option value="<?php echo $shift['id']; ?>" <?php echo (isset($_POST['shift_id']) && $_POST['shift_id'] == $shift['id']) ? 'selected' : ''; ?>>
                                    <?php echo sanitize($shift['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Available Guards (Select to Assign)</label>
                        <div class="guard-list">
                            <?php if (!empty($availableGuards)): ?>
                                <?php foreach ($availableGuards as $guard): ?>
                                    <div class="guard-item">
                                        <input type="checkbox" id="guard_<?php echo $guard['id']; ?>" name="guards[]" value="<?php echo $guard['id']; ?>" />
                                        <label for="guard_<?php echo $guard['id']; ?>"><?php echo sanitize($guard['name']); ?> (<?php echo sanitize($guard['id_number']); ?>)</label>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>No available guards found.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo isset($_POST['start_date']) ? sanitize($_POST['start_date']) : date('Y-m-d'); ?>" required />
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
    </script>
</body>
</html>
