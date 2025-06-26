<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('organization');

$userId = $_SESSION['user_id'];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);

    $query = "UPDATE organizations SET name = ?, email = ?, phone = ?, address = ? WHERE user_id = ?";
    $result = executeQuery($query, [$name, $email, $phone, $address, $userId]);

    if ($result) {
        $_SESSION['success'] = 'Profile updated successfully';
    } else {
        $_SESSION['error'] = 'Failed to update profile';
    }
    redirect($_SERVER['PHP_SELF']);
}

// Get current organization profile
$query = "SELECT * FROM organizations WHERE user_id = ?";
$organization = executeQuery($query, [$userId], ['single' => true]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Organization Profile | <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../../assets/css/styles.css" />
    <link rel="stylesheet" href="../../assets/css/dashboard.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/organization-sidebar.php'; ?>

        <main class="main-content">
            <?php include '../includes/top-nav.php'; ?>

            <div class="dashboard-content">
                <div class="dashboard-header">
                    <h1>Organization Profile</h1>
                    <p>Manage your organization's profile information</p>
                </div>

                <?php echo flashMessage('success'); ?>
                <?php echo flashMessage('error'); ?>

                <div class="card">
                    <div class="card-header">
                        <h2>Profile Information</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="update_profile" value="1" />
                            <div class="form-group">
                                <label for="name">Organization Name</label>
                                <input type="text" id="name" name="name" value="<?php echo sanitize($organization['name']); ?>" required />
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" value="<?php echo sanitize($organization['email']); ?>" required />
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo sanitize($organization['phone']); ?>" />
                            </div>
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea id="address" name="address" rows="3"><?php echo sanitize($organization['address']); ?></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i data-lucide="save"></i> Update Profile
                                </button>
                            </div>
                        </form>
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
