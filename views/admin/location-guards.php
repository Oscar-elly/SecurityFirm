<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('admin');

$location_id = isset($_GET['location_id']) ? intval($_GET['location_id']) : 0;

if ($location_id <= 0) {
    die('Invalid location ID.');
}

// Fetch location details
$location = executeQuery("SELECT * FROM locations WHERE id = ?", [$location_id], ['single' => true]);
if (!$location) {
    die('Location not found.');
}

// Fetch guards assigned to this location
$query = "SELECT g.id, g.id_number, u.name as guard_name, u.email, u.phone, g.status
          FROM duty_assignments da
          JOIN guards g ON da.guard_id = g.id
          JOIN users u ON g.user_id = u.id
          WHERE da.location_id = ? AND da.status = 'active'
          ORDER BY u.name ASC";
$guards = executeQuery($query, [$location_id]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Guards at Location | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/styles.css" />
    <link rel="stylesheet" href="../../assets/css/dashboard.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/admin-sidebar.php'; ?>

        <main class="main-content">
            <?php include '../includes/top-nav.php'; ?>

            <div class="dashboard-content">
                <div class="dashboard-header">
                    <h1>Guards Assigned to <?php echo sanitize($location['name']); ?></h1>
                    <a href="view-location.php?id=<?php echo $location_id; ?>" class="btn btn-outline">
                        <i data-lucide="arrow-left"></i> Back to Location
                    </a>
                </div>

                <?php if (!empty($guards)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID Number</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($guards as $guard): ?>
                                    <tr>
                                        <td><?php echo sanitize($guard['id_number']); ?></td>
                                        <td><?php echo sanitize($guard['guard_name']); ?></td>
                                        <td><?php echo sanitize($guard['email']); ?></td>
                                        <td><?php echo sanitize($guard['phone']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $guard['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst(sanitize($guard['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view-guard.php?id=<?php echo $guard['id']; ?>" class="btn btn-sm btn-outline">
                                                <i data-lucide="eye"></i> View
                                            </a>
                                            <a href="edit-guard.php?id=<?php echo $guard['id']; ?>" class="btn btn-sm btn-outline">
                                                <i data-lucide="edit"></i> Edit
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="no-data">No guards assigned to this location.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
