<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('admin');

$location_id = isset($_GET['location_id']) ? intval($_GET['location_id']) : 0;
if ($location_id <= 0) die('Invalid location ID.');

// Fetch location details
$location = executeQuery2("SELECT * FROM locations WHERE id = ?", [$location_id], ['single' => true]);
if (!$location) die('Location not found.');

// Debug: Show raw assignment data
$debug_assignments = executeQuery2("
    SELECT da.*, g.id_number, u.name as guard_name 
    FROM duty_assignments da
    JOIN guards g ON da.guard_id = g.id
    JOIN users u ON g.user_id = u.id
    WHERE da.location_id = ?
", [$location_id]);

// Final working query
$guards = executeQuery2("
    SELECT 
        g.id, 
        g.id_number, 
        u.name as guard_name, 
        u.email, 
        u.phone, 
        g.status as guard_status,
        da.start_date,
        da.end_date,
        da.status as assignment_status,
        CASE 
            WHEN da.status = 'active' AND CURDATE() BETWEEN da.start_date AND IFNULL(da.end_date, '9999-12-31') THEN 'active'
            WHEN da.status = 'active' AND CURDATE() < da.start_date THEN 'upcoming'
            WHEN da.status = 'active' THEN 'completed'
            ELSE 'inactive'
        END as status_display
    FROM duty_assignments da
    JOIN guards g ON da.guard_id = g.id
    JOIN users u ON g.user_id = u.id
    WHERE da.location_id = ?
    ORDER BY 
        status_display = 'active' DESC,
        status_display = 'upcoming' DESC,
        u.name ASC
", [$location_id]);

// Test database connection
$test = executeQuery2("SELECT 1 as test");
error_log("Database connection test: " . print_r($test, true));

// Test simple guard query
$test_guards = executeQuery2("SELECT COUNT(*) as count FROM guards");
error_log("Guards count: " . print_r($test_guards, true));

// Debug output
echo '<div class="debug-panel" style="background:#f5f5f5;padding:15px;margin:15px;border:1px solid #ddd;">';
echo '<h3>Debug Information</h3>';
echo '<h4>Raw Assignment Data:</h4>';
echo '<pre>'.print_r($debug_assignments, true).'</pre>';
echo '<h4>Processed Guard Data:</h4>';
echo '<pre>'.print_r($guards, true).'</pre>';
echo '</div>';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guards at <?= htmlspecialchars($location['name']) ?> | <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <style>
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .badge-active { background-color: #d1fae5; color: #065f46; }
        .badge-upcoming { background-color: #e0f2fe; color: #075985; }
        .badge-completed { background-color: #f3f4f6; color: #6b7280; }
        .badge-inactive { background-color: #fee2e2; color: #b91c1c; }
    </style>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/admin-sidebar.php'; ?>
        
        <main class="main-content">
            <?php include '../includes/top-nav.php'; ?>
            
            <div class="dashboard-content">
                <div class="dashboard-header">
                    <h1>Guards at <?= htmlspecialchars($location['name']) ?></h1>
                    <div class="flex items-center gap-4">
                        <span class="date-info">Current date: <?= date('Y-m-d') ?></span>
                        <a href="view-location.php?id=<?= $location_id ?>" class="btn btn-outline">
                            <i data-lucide="arrow-left"></i> Back to Location
                        </a>
                    </div>
                </div>

                <?php if (!empty($guards)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID Number</th>
                                    <th>Name</th>
                                    <th>Contact</th>
                                    <th>Guard Status</th>
                                    <th>Assignment</th>
                                    <th>Period</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($guards as $guard): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($guard['id_number']) ?></td>
                                        <td><?= htmlspecialchars($guard['guard_name']) ?></td>
                                        <td>
                                            <div><?= htmlspecialchars($guard['email']) ?></div>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($guard['phone']) ?></div>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= $guard['guard_status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                                                <?= ucfirst(htmlspecialchars($guard['guard_status'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php $badge_class = 'badge-' . strtolower($guard['status_display']) ?>
                                            <span class="status-badge <?= $badge_class ?>">
                                                <?= ucfirst(htmlspecialchars($guard['status_display'])) ?>
                                            </span>
                                        </td>
                                        <td class="date-info">
                                            <?= date('M j, Y', strtotime($guard['start_date'])) ?>
                                            <?php if ($guard['end_date']): ?>
                                                &ndash; <?= date('M j, Y', strtotime($guard['end_date'])) ?>
                                            <?php else: ?>
                                                (Ongoing)
                                            <?php endif; ?>
                                        </td>
                                        <td class="flex gap-2">
                                            <a href="view-guard.php?id=<?= $guard['id'] ?>" class="btn btn-sm btn-outline" title="View">
                                                <i data-lucide="eye"></i>
                                            </a>
                                            <a href="edit-guard.php?id=<?= $guard['id'] ?>" class="btn btn-sm btn-outline" title="Edit">
                                                <i data-lucide="edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i data-lucide="users" class="text-gray-400" width="48" height="48"></i>
                        <p>No guards assigned to this location.</p>
                        <a href="assign-guard.php?location_id=<?= $location_id ?>" class="btn btn-primary mt-4">
                            <i data-lucide="plus"></i> Assign a Guard
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>