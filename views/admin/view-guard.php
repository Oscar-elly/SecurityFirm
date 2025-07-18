<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('admin');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die('Invalid guard ID.');
}

// Fetch specific guard details with user info and organization
$query = "SELECT g.*, u.name as guard_name, u.email, u.phone, o.name as organization_name
          FROM guards g
          JOIN users u ON g.user_id = u.id
          LEFT JOIN organizations o ON u.organization_id = o.id
          WHERE g.user_id = ?
          LIMIT 1";
$guard = executeQuery($query, [$id], ['single' => true]);

if (!$guard) {
    die('Guard not found.');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Guards | <?php echo SITE_NAME; ?></title>
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
                    <h1>Guard Details</h1>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h2><?php echo sanitize($guard['guard_name']); ?></h2>
                        <p><strong>ID Number:</strong> <?php echo sanitize($guard['id_number']); ?></p>
                        <p><strong>Email:</strong> <?php echo sanitize($guard['email']); ?></p>
                        <p><strong>Phone:</strong> <?php echo sanitize($guard['phone']); ?></p>
                        <p><strong>Organization:</strong> <?php echo sanitize($guard['organization_name']); ?></p>
                        <p><strong>Status:</strong> 
                            <span class="badge badge-<?php echo $guard['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                <?php echo ucfirst(sanitize($guard['status'])); ?>
                            </span>
                        </p>
                        <a href="edit-guard.php?id=<?php echo $guard['id']; ?>" class="btn btn-primary">
                            <i data-lucide="edit"></i> Edit Guard
                        </a>
                        <a href="delete-guard.php?id=<?php echo $guard['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this guard?');">
                            <i data-lucide="trash-2"></i> Delete Guard
                        </a>
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
