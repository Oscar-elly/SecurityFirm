<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('organization');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $name = sanitize($_POST['name']);
                $email = sanitize($_POST['email']);
                $phone = sanitize($_POST['phone']);
                
                $query = "UPDATE organizations SET name = ?, email = ?, phone = ? WHERE user_id = ?";
                $result = executeQuery($query, [$name, $email, $phone, $_SESSION['user_id']]);
                
                if ($result) {
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

// Get current organization information
$query = "SELECT * FROM organizations WHERE user_id = ?";
$currentOrganization = executeQuery($query, [$_SESSION['user_id']], ['single' => true]);

// Get system statistics
$systemStats = [
    'total_users' => executeQuery("SELECT COUNT(*) as count FROM users WHERE role = 'organization'", [], ['single' => true])['count'],
    'total_guards' => executeQuery("SELECT COUNT(*) as count FROM guards WHERE user_id = ?", [$_SESSION['user_id']], ['single' => true])['count'],
    'total_incidents' => executeQuery("SELECT COUNT(*) as count FROM incidents WHERE user_id = ?", [$_SESSION['user_id']], ['single' => true])['count'],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Settings | <?php echo SITE_NAME; ?></title>
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
                </div>

                <!-- Profile Settings Tab -->
                <div id="profile-tab" class="tab-content active">
                    <div class="card">
                        <div class="card-header">
                            <h2>Profile Information</h2>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_profile" />
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="name">Organization Name</label>
                                        <input type="text" id="name" name="name" value="<?php echo sanitize($currentOrganization['name']); ?>" required />
                                    </div>
                                    <div class="form-group">
                                        <label for="email">Email Address</label>
                                        <input type="email" id="email" name="email" value="<?php echo sanitize($currentOrganization['email']); ?>" required />
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="phone">Phone Number</label>
                                        <input type="tel" id="phone" name="phone" value="<?php echo sanitize($currentOrganization['phone']); ?>" />
                                    </div>
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

                <!-- Security Tab -->
                <div id="security-tab" class="tab-content">
                    <div class="card">
                        <div class="card-header">
                            <h2>Change Password</h2>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="change_password" />
                                <div class="form-group">
                                    <label for="current_password">Current Password</label>
                                    <input type="password" id="current_password" name="current_password" required />
                                </div>
                                <div class="form-group">
                                    <label for="new_password">New Password</label>
                                    <input type="password" id="new_password" name="new_password" required minlength="6" />
                                    <small class="form-help">Password must be at least 6 characters long</small>
                                </div>
                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password" required />
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i data-lucide="lock"></i> Change Password
                                    </button>
                                </div>
                            </form>
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
                                        <p>Total Organizations</p>
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
