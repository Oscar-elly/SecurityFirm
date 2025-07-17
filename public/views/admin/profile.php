<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('admin');

$userId = $_SESSION['user_id'];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);

    // Handle profile picture upload if any
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/profile_pictures/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileTmpPath = $_FILES['profile_picture']['tmp_name'];
        $fileName = basename($_FILES['profile_picture']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($fileExt, $allowedExts)) {
            $newFileName = 'admin_' . $userId . '.' . $fileExt;
            $destPath = $uploadDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                // Update profile picture path in DB
                $queryPic = "UPDATE users SET profile_picture = ? WHERE id = ?";
                executeQuery($queryPic, [$newFileName, $userId]);
            } else {
                $_SESSION['error'] = 'Failed to upload profile picture.';
            }
        } else {
            $_SESSION['error'] = 'Invalid file type for profile picture.';
        }
    }

    $query = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
    $result = executeQuery($query, [$name, $email, $phone, $userId]);

    if ($result) {
        $_SESSION['success'] = 'Profile updated successfully';
    } else {
        $_SESSION['error'] = 'Failed to update profile';
    }
    redirect($_SERVER['PHP_SELF']);
}

// Get current user profile
$query = "SELECT * FROM users WHERE id = ?";
$user = executeQuery($query, [$userId], ['single' => true]);

// Handle theme mode toggle via session or DB (simplified here)
if (isset($_POST['toggle_theme'])) {
    $_SESSION['theme_mode'] = ($_SESSION['theme_mode'] ?? 'light') === 'light' ? 'dark' : 'light';
    redirect($_SERVER['PHP_SELF']);
}

$themeMode = $_SESSION['theme_mode'] ?? 'light';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Profile | <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../../assets/css/styles.css" />
    <link rel="stylesheet" href="../../assets/css/dashboard.css" />
    <style>
        body.light-mode {
            background-color: #f5f5f5;
            color: #333;
        }
        body.dark-mode {
            background-color: #121212;
            color: #eee;
        }
        .profile-picture {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-color);
            margin-bottom: 1rem;
        }
    </style>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="<?php echo $themeMode; ?>-mode">
    <div class="dashboard-container">
        <?php include '../includes/admin-sidebar.php'; ?>

        <main class="main-content">
            <?php include '../includes/top-nav.php'; ?>

            <div class="dashboard-content">
                <div class="dashboard-header">
                    <h1>Admin Profile</h1>
                    <p>Manage your account profile and preferences</p>
                </div>

                <?php echo flashMessage('success'); ?>
                <?php echo flashMessage('error'); ?>

                <div class="card">
                    <div class="card-header">
                        <h2>Profile Information</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="update_profile" value="1" />
                            <div class="form-group" style="text-align:center;">
                                <?php if (!empty($user['profile_picture']) && file_exists('../../uploads/profile_pictures/' . $user['profile_picture'])): ?>
                                    <img src="../../uploads/profile_pictures/<?php echo sanitize($user['profile_picture']); ?>" alt="Profile Picture" class="profile-picture" />
                                <?php else: ?>
                                    <img src="../../assets/images/default-profile.png" alt="Profile Picture" class="profile-picture" />
                                <?php endif; ?>
                                <input type="file" name="profile_picture" accept="image/*" />
                            </div>
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" value="<?php echo sanitize($user['name']); ?>" required />
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" value="<?php echo sanitize($user['email']); ?>" required />
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo sanitize($user['phone']); ?>" />
                            </div>
                            <div class="form-group">
                                <label>Theme Mode</label>
                                <form method="POST" style="margin-top: 0;">
                                    <input type="hidden" name="toggle_theme" value="1" />
                                    <button type="submit" class="btn btn-secondary">
                                        Switch to <?php echo $themeMode === 'light' ? 'Dark' : 'Light'; ?> Mode
                                    </button>
                                </form>
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
