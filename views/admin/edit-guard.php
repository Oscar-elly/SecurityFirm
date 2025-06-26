<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('admin');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_number = sanitize($_POST['id_number']);
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $organization_id = intval($_POST['organization_id']);
    $status = sanitize($_POST['status']);

    if (empty($id_number)) {
        $errors[] = 'ID Number is required.';
    }
    if (empty($name)) {
        $errors[] = 'Name is required.';
    }
    if (empty($email)) {
        $errors[] = 'Email is required.';
    }

    if (empty($errors)) {
        if ($id > 0) {
            // Update existing guard
            $query = "UPDATE guards SET id_number = ?, status = ? WHERE id = ?";
            $result = executeQuery($query, [$id_number, $status, $id]);

            $queryUser = "UPDATE users SET name = ?, email = ?, phone = ?, organization_id = ? WHERE id = (SELECT user_id FROM guards WHERE id = ?)";
            $resultUser = executeQuery($queryUser, [$name, $email, $phone, $organization_id, $id]);

            if ($result && $resultUser) {
                $success = 'Guard updated successfully.';
            } else {
                $errors[] = 'Failed to update guard.';
            }
        } else {
            // Insert new user
            $queryUser = "INSERT INTO users (name, email, phone, organization_id, role) VALUES (?, ?, ?, ?, 'guard')";
            $resultUser = executeQuery($queryUser, [$name, $email, $phone, $organization_id]);

            if ($resultUser) {
                $user_id = $conn->insert_id;
                $queryGuard = "INSERT INTO guards (user_id, id_number, status) VALUES (?, ?, ?)";
                $resultGuard = executeQuery($queryGuard, [$user_id, $id_number, $status]);

                if ($resultGuard) {
                    $success = 'Guard added successfully.';
                    $id = $conn->insert_id;
                } else {
                    $errors[] = 'Failed to add guard.';
                }
            } else {
                $errors[] = 'Failed to add user.';
            }
        }
    }
}

// Fetch guard and user data if editing
$guard = [
    'id_number' => '',
    'status' => 'active',
    'name' => '',
    'email' => '',
    'phone' => '',
    'organization_id' => 0
];

if ($id > 0) {
    $guardData = executeQuery("SELECT g.id_number, g.status, u.name, u.email, u.phone, u.organization_id FROM guards g JOIN users u ON g.user_id = u.id WHERE g.id = ?", [$id], ['single' => true]);
    if (!$guardData) {
        die('Guard not found.');
    }
    $guard = $guardData;
}

// Fetch organizations for dropdown
$organizations = executeQuery("SELECT id, name FROM organizations ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo $id > 0 ? 'Edit' : 'Add'; ?> Guard | <?php echo SITE_NAME; ?></title>
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
                    <h1><?php echo $id > 0 ? 'Edit' : 'Add'; ?> Guard</h1>
                    <a href="view-guard.php" class="btn btn-outline">
                        <i data-lucide="arrow-left"></i> Back to Guards
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
                        <label for="id_number">ID Number</label>
                        <input type="text" id="id_number" name="id_number" value="<?php echo sanitize($guard['id_number']); ?>" required />
                    </div>

                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo sanitize($guard['name']); ?>" required />
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo sanitize($guard['email']); ?>" required />
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo sanitize($guard['phone']); ?>" />
                    </div>

                    <div class="form-group">
                        <label for="organization_id">Organization</label>
                        <select id="organization_id" name="organization_id" required>
                            <option value="">Select Organization</option>
                            <?php foreach ($organizations as $org): ?>
                                <option value="<?php echo $org['id']; ?>" <?php echo $org['id'] == $guard['organization_id'] ? 'selected' : ''; ?>>
                                    <?php echo sanitize($org['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" required>
                            <option value="active" <?php echo $guard['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $guard['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i data-lucide="save"></i> <?php echo $id > 0 ? 'Update' : 'Add'; ?> Guard
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
