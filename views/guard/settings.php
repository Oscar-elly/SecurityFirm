<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('guard');

// Get current user information
$userId = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ?";
$currentUser = executeQuery($query, [$userId], ['single' => true]);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                if ($new_password !== $confirm_password) {
                    $_SESSION['error'] = 'New passwords do not match';
                    break;
                }
                
                // Verify current password
                if (!password_verify($current_password, $currentUser['password'])) {
                    $_SESSION['error'] = 'Current password is incorrect';
                    break;
                }
                
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $query = "UPDATE users SET password = ? WHERE id = ?";
                $result = executeQuery($query, [$hashed_password, $userId]);
                
                if ($result) {
                    logActivity($userId, "Changed password", 'security');
                    $_SESSION['success'] = 'Password changed successfully';
                } else {
                    $_SESSION['error'] = 'Failed to change password';
                }
                break;
        }
        redirect($_SERVER['PHP_SELF']);
    }
}
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
    <link rel="stylesheet" href="../../assets/css/guard-dashboard.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/guard-sidebar.php'; ?>
        
        <main class="main-content">
            <?php include '../includes/top-nav.php'; ?>
            
            <div class="dashboard-content">
                <div class="dashboard-header">
                    <h1>Settings</h1>
                    <p>Manage your account settings and preferences</p>
                </div>
                
                <?php echo flashMessage('success'); ?>
                <?php echo flashMessage('error'); ?>
                
                <!-- Account Information -->
                <div class="card">
                    <div class="card-header">
                        <h2>Account Information</h2>
                    </div>
                    <div class="card-body">
                        <div class="account-info">
                            <div class="info-item">
                                <i data-lucide="user"></i>
                                <div>
                                    <strong>Name</strong>
                                    <p><?php echo sanitize($currentUser['name']); ?></p>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <i data-lucide="mail"></i>
                                <div>
                                    <strong>Email</strong>
                                    <p><?php echo sanitize($currentUser['email']); ?></p>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <i data-lucide="phone"></i>
                                <div>
                                    <strong>Phone</strong>
                                    <p><?php echo sanitize($currentUser['phone']); ?></p>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <i data-lucide="shield"></i>
                                <div>
                                    <strong>Role</strong>
                                    <p><?php echo ucfirst($currentUser['role']); ?></p>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <i data-lucide="calendar"></i>
                                <div>
                                    <strong>Account Created</strong>
                                    <p><?php echo formatDate($currentUser['created_at'], 'd M Y'); ?></p>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <i data-lucide="activity"></i>
                                <div>
                                    <strong>Status</strong>
                                    <p>
                                        <span class="badge badge-<?php echo $currentUser['status'] === 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($currentUser['status']); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="account-actions">
                            <a href="profile.php" class="btn btn-primary">
                                <i data-lucide="edit"></i> Edit Profile
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Security Settings -->
                <div class="card">
                    <div class="card-header">
                        <h2>Security Settings</h2>
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
                
                <!-- App Information -->
                <div class="card">
                    <div class="card-header">
                        <h2>Application Information</h2>
                    </div>
                    <div class="card-body">
                        <div class="app-info">
                            <div class="info-section">
                                <h3>About <?php echo SITE_NAME; ?></h3>
                                <p>A comprehensive security management system designed to streamline security operations, 
                                   manage guard assignments, track incidents, and ensure effective communication between 
                                   security personnel and organizations.</p>
                            </div>
                            
                            <div class="info-section">
                                <h3>Support</h3>
                                <p>If you need assistance or have questions about using the system, please contact your administrator or the support team.</p>
                                <div class="support-contacts">
                                    <div class="contact-item">
                                        <i data-lucide="mail"></i>
                                        <span><?php echo ADMIN_EMAIL; ?></span>
                                    </div>
                                    <div class="contact-item">
                                        <i data-lucide="phone"></i>
                                        <span>+254-700-000-000</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="info-section">
                                <h3>Privacy & Security</h3>
                                <p>Your personal information is protected and used only for security management purposes. 
                                   All data is encrypted and stored securely.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
    .account-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
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
        flex-shrink: 0;
    }
    
    .info-item strong {
        display: block;
        margin-bottom: 0.25rem;
        color: #333;
    }
    
    .info-item p {
        margin: 0;
        color: #666;
    }
    
    .account-actions {
        padding-top: 1rem;
        border-top: 1px solid #eee;
    }
    
    .form-actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 2rem;
        padding-top: 1rem;
        border-top: 1px solid #eee;
    }
    
    .form-help {
        display: block;
        margin-top: 0.25rem;
        font-size: 0.875rem;
        color: #666;
    }
    
    .app-info {
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }
    
    .info-section h3 {
        margin-bottom: 1rem;
        color: var(--primary-color);
    }
    
    .info-section p {
        margin-bottom: 1rem;
        color: #666;
        line-height: 1.6;
    }
    
    .support-contacts {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .contact-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #666;
    }
    
    .contact-item i {
        width: 16px;
        height: 16px;
        color: var(--primary-color);
    }
    </style>

    <script>
        lucide.createIcons();
        
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