<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
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
                        $newFileName = 'user_' . $_SESSION['user_id'] . '.' . $fileExt;
                        $destPath = $uploadDir . $newFileName;
                        if (move_uploaded_file($fileTmpPath, $destPath)) {
                            // Update profile picture path in DB
                            $queryPic = "UPDATE users SET profile_picture = ? WHERE id = ?";
                            executeQuery($queryPic, [$newFileName, $_SESSION['user_id']]);
                        } else {
                            $_SESSION['error'] = 'Failed to upload profile picture.';
                        }
                    } else {
                        $_SESSION['error'] = 'Invalid file type for profile picture.';
                    }
                }
                
                $query = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
                $result = executeQuery($query, [$name, $email, $phone, $_SESSION['user_id']]);
                
                if ($result) {
                    $_SESSION['name'] = $name;
                    $_SESSION['success'] = 'Profile updated successfully';
                } else {
                    $_SESSION['error'] = 'Failed to update profile';
                }
                break;
                
            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                if ($new_password !== $confirm_password) {
                    $_SESSION['error'] = 'New passwords do not match';
                    break;
                }
                
                // Verify current password
                $query = "SELECT password FROM users WHERE id = ?";
                $user = executeQuery($query, [$_SESSION['user_id']], ['single' => true]);
                
                if (!password_verify($current_password, $user['password'])) {
                    $_SESSION['error'] = 'Current password is incorrect';
                    break;
                }
                
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $query = "UPDATE users SET password = ? WHERE id = ?";
                $result = executeQuery($query, [$hashed_password, $_SESSION['user_id']]);
                
                if ($result) {
                    $_SESSION['success'] = 'Password changed successfully';
                } else {
                    $_SESSION['error'] = 'Failed to change password';
                }
                break;
                
            case 'update_system_settings':
                // This would typically update a settings table
                $_SESSION['success'] = 'System settings updated successfully';
                break;
        }
        redirect($_SERVER['PHP_SELF']);
    }
}

// Get current user information
$query = "SELECT * FROM users WHERE id = ?";
$currentUser = executeQuery($query, [$_SESSION['user_id']], ['single' => true]);

// Get system statistics
$systemStats = [
    'total_users' => executeQuery("SELECT COUNT(*) as count FROM users", [], ['single' => true])['count'],
    'total_guards' => executeQuery("SELECT COUNT(*) as count FROM guards", [], ['single' => true])['count'],
    'total_organizations' => executeQuery("SELECT COUNT(*) as count FROM organizations", [], ['single' => true])['count'],
    'total_incidents' => executeQuery("SELECT COUNT(*) as count FROM incidents", [], ['single' => true])['count'],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | <?php echo SITE_NAME; ?></title>
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
                    <h1>Settings</h1>
                    <p>Manage your account and system settings</p>
                </div>
                
                <?php echo flashMessage('success'); ?>
                <?php echo flashMessage('error'); ?>
                
                <!-- Settings Tabs -->
                <div class="settings-tabs">
                    <button class="tab-btn active" onclick="showTab('profile')">
                        <i data-lucide="user"></i> Profile Settings
                    </button>
                    <button class="tab-btn" onclick="showTab('security')">
                        <i data-lucide="shield"></i> Security
                    </button>
                    <button class="tab-btn" onclick="showTab('system')">
                        <i data-lucide="settings"></i> System Settings
                    </button>
                    <button class="tab-btn" onclick="showTab('backup')">
                        <i data-lucide="database"></i> Backup & Restore
                    </button>
                </div>
                
                <!-- Profile Settings Tab -->
                <div id="profile-tab" class="tab-content active">
                    <div class="card">
                        <div class="card-header">
                            <h2>Profile Information</h2>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="update_profile">
                                <div class="form-row" style="align-items: center;">
                                    <div class="form-group" style="flex: 0 0 150px; text-align: center;">
                                        <?php if (!empty($currentUser['profile_picture']) && file_exists('../../uploads/profile_pictures/' . $currentUser['profile_picture'])): ?>
                                            <img src="../../uploads/profile_pictures/<?php echo sanitize($currentUser['profile_picture']); ?>" alt="Profile Picture" class="profile-picture" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary-color); margin-bottom: 1rem;">
                                        <?php else: ?>
                                            <img src="../../assets/images/default-profile.png" alt="Profile Picture" class="profile-picture" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary-color); margin-bottom: 1rem;">
                                        <?php endif; ?>
                                        <input type="file" name="profile_picture" accept="image/*" />
                                    </div>
                                    <div style="flex: 1;">
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="name">Full Name</label>
                                                <input type="text" id="name" name="name" value="<?php echo sanitize($currentUser['name']); ?>" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="email">Email Address</label>
                                                <input type="email" id="email" name="email" value="<?php echo sanitize($currentUser['email']); ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="phone">Phone Number</label>
                                                <input type="tel" id="phone" name="phone" value="<?php echo sanitize($currentUser['phone']); ?>">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="role">Role</label>
                                                <input type="text" id="role" value="<?php echo ucfirst($currentUser['role']); ?>" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i data-lucide="save"></i> Update Profile
                                    </button>
                                </div>
                            </form>
                            <form method="POST" style="margin-top: 1rem;">
                                <input type="hidden" name="toggle_theme" value="1" />
                                <button type="submit" class="btn btn-secondary">
                                    Switch to <?php echo ($_SESSION['theme_mode'] ?? 'light') === 'light' ? 'Dark' : 'Light'; ?> Mode
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Security Tab -->
                <div id="security-tab" class="tab-content">
                    <div class="card">
                        <div class="card-header">
                            <h2>Change Password</h2>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="change_password">
                                <div class="form-group">
                                    <label for="current_password">Current Password</label>
                                    <input type="password" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="new_password">New Password</label>
                                    <input type="password" id="new_password" name="new_password" required minlength="6">
                                    <small class="form-help">Password must be at least 6 characters long</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i data-lucide="lock"></i> Change Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h2>Security Information</h2>
                        </div>
                        <div class="card-body">
                            <div class="security-info">
                                <div class="info-item">
                                    <i data-lucide="calendar"></i>
                                    <div>
                                        <strong>Account Created</strong>
                                        <p><?php echo formatDate($currentUser['created_at'], 'd M Y, h:i A'); ?></p>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <i data-lucide="clock"></i>
                                    <div>
                                        <strong>Last Updated</strong>
                                        <p><?php echo formatDate($currentUser['updated_at'], 'd M Y, h:i A'); ?></p>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <i data-lucide="shield-check"></i>
                                    <div>
                                        <strong>Account Status</strong>
                                        <p><span class="badge badge-success"><?php echo ucfirst($currentUser['status']); ?></span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- System Settings Tab -->
                <div id="system-tab" class="tab-content">
                    <div class="card">
                        <div class="card-header">
                            <h2>System Overview</h2>
                        </div>
                        <div class="card-body">
                            <div class="system-stats">
                                <div class="stat-item">
                                    <div class="stat-icon">
                                        <i data-lucide="users"></i>
                                    </div>
                                    <div class="stat-details">
                                        <h3><?php echo $systemStats['total_users']; ?></h3>
                                        <p>Total Users</p>
                                    </div>
                                </div>
                                
                                <div class="stat-item">
                                    <div class="stat-icon">
                                        <i data-lucide="shield"></i>
                                    </div>
                                    <div class="stat-details">
                                        <h3><?php echo $systemStats['total_guards']; ?></h3>
                                        <p>Security Guards</p>
                                    </div>
                                </div>
                                
                                <div class="stat-item">
                                    <div class="stat-icon">
                                        <i data-lucide="building-2"></i>
                                    </div>
                                    <div class="stat-details">
                                        <h3><?php echo $systemStats['total_organizations']; ?></h3>
                                        <p>Organizations</p>
                                    </div>
                                </div>
                                
                                <div class="stat-item">
                                    <div class="stat-icon">
                                        <i data-lucide="alert-triangle"></i>
                                    </div>
                                    <div class="stat-details">
                                        <h3><?php echo $systemStats['total_incidents']; ?></h3>
                                        <p>Total Incidents</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h2>System Configuration</h2>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_system_settings">
                                <div class="form-group">
                                    <label for="site_name">Site Name</label>
                                    <input type="text" id="site_name" name="site_name" value="<?php echo SITE_NAME; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="admin_email">Admin Email</label>
                                    <input type="email" id="admin_email" name="admin_email" value="<?php echo ADMIN_EMAIL; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="session_timeout">Session Timeout (minutes)</label>
                                    <input type="number" id="session_timeout" name="session_timeout" value="<?php echo SESSION_LIFETIME / 60; ?>">
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i data-lucide="save"></i> Update Settings
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Backup & Restore Tab -->
                <div id="backup-tab" class="tab-content">
                    <div class="card">
                        <div class="card-header">
                            <h2>Database Backup</h2>
                        </div>
                        <div class="card-body">
                            <div class="backup-section">
                                <div class="backup-info">
                                    <i data-lucide="database"></i>
                                    <div>
                                        <h3>Create Database Backup</h3>
                                        <p>Download a complete backup of your database including all users, incidents, and system data.</p>
                                    </div>
                                </div>
                                <button class="btn btn-primary" onclick="createBackup()">
                                    <i data-lucide="download"></i> Create Backup
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h2>System Maintenance</h2>
                        </div>
                        <div class="card-body">
                            <div class="maintenance-actions">
                                <div class="maintenance-item">
                                    <div class="maintenance-info">
                                        <i data-lucide="trash-2"></i>
                                        <div>
                                            <h3>Clear Activity Logs</h3>
                                            <p>Remove old activity logs to free up database space.</p>
                                        </div>
                                    </div>
                                    <button class="btn btn-warning" onclick="clearLogs()">
                                        <i data-lucide="trash-2"></i> Clear Logs
                                    </button>
                                </div>
                                
                                <div class="maintenance-item">
                                    <div class="maintenance-info">
                                        <i data-lucide="refresh-cw"></i>
                                        <div>
                                            <h3>Reset System Cache</h3>
                                            <p>Clear system cache to improve performance.</p>
                                        </div>
                                    </div>
                                    <button class="btn btn-secondary" onclick="clearCache()">
                                        <i data-lucide="refresh-cw"></i> Clear Cache
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
    .settings-tabs {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .tab-btn {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 1rem 1.5rem;
        background: none;
        border: none;
        border-bottom: 2px solid transparent;
        cursor: pointer;
        transition: all 0.2s ease;
        color: #666;
    }
    
    .tab-btn.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
    }
    
    .tab-btn:hover {
        color: var(--primary-color);
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    
    .form-actions {
        margin-top: 1.5rem;
        padding-top: 1rem;
        border-top: 1px solid #eee;
    }
    
    .form-help {
        display: block;
        margin-top: 0.25rem;
        font-size: 0.875rem;
        color: #666;
    }
    
    .security-info {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .info-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .info-item i {
        width: 24px;
        height: 24px;
        color: var(--primary-color);
    }
    
    .info-item strong {
        display: block;
        margin-bottom: 0.25rem;
    }
    
    .info-item p {
        margin: 0;
        color: #666;
    }
    
    .system-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .stat-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1.5rem;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .stat-icon {
        width: 48px;
        height: 48px;
        background: var(--primary-color);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .stat-details h3 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 600;
    }
    
    .stat-details p {
        margin: 0;
        color: #666;
        font-size: 0.875rem;
    }
    
    .backup-section,
    .maintenance-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem;
        background: #f8f9fa;
        border-radius: 8px;
        margin-bottom: 1rem;
    }
    
    .backup-info,
    .maintenance-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .backup-info i,
    .maintenance-info i {
        width: 24px;
        height: 24px;
        color: var(--primary-color);
    }
    
    .backup-info h3,
    .maintenance-info h3 {
        margin: 0 0 0.25rem 0;
        font-size: 1rem;
    }
    
    .backup-info p,
    .maintenance-info p {
        margin: 0;
        color: #666;
        font-size: 0.875rem;
    }
    
    .maintenance-actions {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .backup-section,
        .maintenance-item {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }
        
        .settings-tabs {
            flex-wrap: wrap;
        }
    }
    </style>

    <script>
        lucide.createIcons();
        
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }
        
        function createBackup() {
            if (confirm('This will create a database backup. Continue?')) {
                // In a real application, this would trigger a backup process
                alert('Backup created successfully! Download will start shortly.');
                // window.location.href = 'backup.php';
            }
        }
        
        function clearLogs() {
            if (confirm('This will permanently delete old activity logs. Continue?')) {
                // In a real application, this would clear old logs
                alert('Activity logs cleared successfully!');
            }
        }
        
        function clearCache() {
            if (confirm('This will clear the system cache. Continue?')) {
                // In a real application, this would clear cache
                alert('System cache cleared successfully!');
            }
        }
        
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
