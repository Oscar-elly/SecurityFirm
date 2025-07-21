<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('organization');

// Get current user and organization information
$userId = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ?";
$currentUser = executeQuery($query, [$userId], ['single' => true]);

$query = "SELECT * FROM organizations WHERE user_id = ?";
$currentOrganization = executeQuery($query, [$userId], ['single' => true]);

// Initialize with default values if organization not found
if (!$currentOrganization) {
    $currentOrganization = [
        'name' => '',
        'contact_person' => '',
        'contact_phone' => ''
    ];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $name = sanitize($_POST['name'] ?? '');
                $contact_person = sanitize($_POST['contact_person'] ?? '');
                $contact_phone = sanitize($_POST['contact_phone'] ?? '');
                
                $query = "UPDATE organizations SET name = ?, contact_person = ?, contact_phone = ? WHERE user_id = ?";
                $result = executeQuery($query, [$name, $contact_person, $contact_phone, $userId]);
                
                if ($result) {
                    $_SESSION['success'] = 'Profile updated successfully';
                    logActivity($userId, "Updated organization profile", 'settings');
                } else {
                    $_SESSION['error'] = 'Failed to update profile';
                }
                break;
                
            case 'change_password':
                $current_password = $_POST['current_password'] ?? '';
                $new_password = $_POST['new_password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                
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

// Get system statistics with proper error handling
$systemStats = [
    'total_guards' => executeQuery("SELECT COUNT(*) as count FROM guards WHERE organization_id = ?", [$currentOrganization['id'] ?? 0], ['single' => true])['count'] ?? 0,
    'total_incidents' => executeQuery("SELECT COUNT(*) as count FROM incidents i JOIN locations l ON i.location_id = l.id WHERE l.organization_id = ?", [$currentOrganization['id'] ?? 0], ['single' => true])['count'] ?? 0,
    'total_locations' => executeQuery("SELECT COUNT(*) as count FROM locations WHERE organization_id = ?", [$currentOrganization['id'] ?? 0], ['single' => true])['count'] ?? 0,
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | <?php echo htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/organization-dashboard.css">
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
                    <p>Manage your account and organization settings</p>
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
                                    <strong>Organization Name</strong>
                                    <p><?php echo htmlspecialchars($currentOrganization['name'] ?? 'Not set', ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <i data-lucide="mail"></i>
                                <div>
                                    <strong>Email Address</strong>
                                    <p><?php echo htmlspecialchars($currentUser['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <i data-lucide="phone"></i>
                                <div>
                                    <strong>Contact Person</strong>
                                    <p><?php echo htmlspecialchars($currentOrganization['contact_person'] ?? 'Not set', ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <i data-lucide="phone-call"></i>
                                <div>
                                    <strong>Contact Phone</strong>
                                    <p><?php echo htmlspecialchars($currentOrganization['contact_phone'] ?? 'Not set', ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <i data-lucide="shield"></i>
                                <div>
                                    <strong>Account Type</strong>
                                    <p><?php echo htmlspecialchars(ucfirst($currentUser['role'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <i data-lucide="calendar"></i>
                                <div>
                                    <strong>Account Created</strong>
                                    <p><?php echo htmlspecialchars(formatDate($currentUser['created_at'] ?? '', 'd M Y'), ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <i data-lucide="activity"></i>
                                <div>
                                    <strong>Status</strong>
                                    <p>
                                        <span class="badge badge-<?php echo ($currentUser['status'] ?? '') === 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo htmlspecialchars(ucfirst($currentUser['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Profile Settings -->
                <div class="card">
                    <div class="card-header">
                        <h2>Organization Profile</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name">Organization Name</label>
                                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($currentOrganization['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required />
                                </div>
                                <div class="form-group">
                                    <label for="contact_person">Contact Person</label>
                                    <input type="text" id="contact_person" name="contact_person" value="<?php echo htmlspecialchars($currentOrganization['contact_person'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required />
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="contact_phone">Contact Phone</label>
                                <input type="tel" id="contact_phone" name="contact_phone" value="<?php echo htmlspecialchars($currentOrganization['contact_phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required />
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i data-lucide="save"></i> Update Profile
                                </button>
                            </div>
                        </form>
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
                
                <!-- Organization Statistics -->
                <div class="card">
                    <div class="card-header">
                        <h2>Organization Statistics</h2>
                    </div>
                    <div class="card-body">
                        <div class="system-stats">
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i data-lucide="shield"></i>
                                </div>
                                <div class="stat-details">
                                    <h3><?php echo htmlspecialchars($systemStats['total_guards'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <p>Security Guards</p>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i data-lucide="alert-triangle"></i>
                                </div>
                                <div class="stat-details">
                                    <h3><?php echo htmlspecialchars($systemStats['total_incidents'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <p>Total Incidents</p>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i data-lucide="map-pin"></i>
                                </div>
                                <div class="stat-details">
                                    <h3><?php echo htmlspecialchars($systemStats['total_locations'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <p>Locations</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Support Information -->
                <div class="card">
                    <div class="card-header">
                        <h2>Support</h2>
                    </div>
                    <div class="card-body">
                        <div class="app-info">
                            <div class="info-section">
                                <h3>Need Help?</h3>
                                <p>If you need assistance with the security management system, please contact our support team.</p>
                                <div class="support-contacts">
                                    <div class="contact-item">
                                        <i data-lucide="mail"></i>
                                        <span><?php echo htmlspecialchars(ADMIN_EMAIL, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                    <div class="contact-item">
                                        <i data-lucide="phone"></i>
                                        <span>+254-700-000-000</span>
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
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
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
    
    .system-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
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
        width: 40px;
        height: 40px;
        background-color: var(--primary-light);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .stat-icon i {
        color: var(--primary-color);
    }
    
    .stat-details h3 {
        margin: 0 0 0.25rem 0;
        font-size: 1.5rem;
        color: var(--primary-color);
    }
    
    .stat-details p {
        margin: 0;
        color: #666;
    }
    
    .app-info {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
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
    
    @media (max-width: 768px) {
        .account-info {
            grid-template-columns: 1fr;
        }
        
        .system-stats {
            grid-template-columns: 1fr;
        }
        
        .form-row {
            grid-template-columns: 1fr;
        }
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