<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('organization');

// Get organization information
$userId = $_SESSION['user_id'];
$query = "SELECT u.*, o.* FROM users u 
          JOIN organizations o ON u.id = o.user_id 
          WHERE u.id = ?";
$organization = executeQuery($query, [$userId], ['single' => true]);

if (!$organization) {
    $_SESSION['error'] = 'Organization information not found';
    redirect(SITE_URL);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $org_name = sanitize($_POST['org_name']);
    $address = sanitize($_POST['address']);
    $contact_person = sanitize($_POST['contact_person']);
    $contact_phone = sanitize($_POST['contact_phone']);
    $industry = sanitize($_POST['industry']);
    
    // Update user information
    $userQuery = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
    $userResult = executeQuery($userQuery, [$name, $email, $contact_phone, $userId]);
    
    // Update organization information
    $orgQuery = "UPDATE organizations SET name = ?, address = ?, contact_person = ?, 
                 contact_phone = ?, industry = ? WHERE user_id = ?";
    $orgResult = executeQuery($orgQuery, [$org_name, $address, $contact_person, $contact_phone, $industry, $userId]);
    
    if ($userResult && $orgResult) {
        $_SESSION['name'] = $name; // Update session name
        logActivity($userId, "Updated organization profile", 'profile');
        $_SESSION['success'] = 'Profile updated successfully';
        redirect($_SERVER['PHP_SELF']);
    } else {
        $_SESSION['error'] = 'Failed to update profile';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Profile | <?php echo SITE_NAME; ?></title>
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
                    <h1>Organization Profile</h1>
                    <p>Manage your organization information</p>
                </div>
                
                <?php echo flashMessage('success'); ?>
                <?php echo flashMessage('error'); ?>
                
                <!-- Profile Overview -->
                <div class="card">
                    <div class="card-body">
                        <div class="profile-header">
                            <div class="profile-avatar">
                                <?php echo getInitials($organization['name']); ?>
                            </div>
                            <div class="profile-info">
                                <h2><?php echo sanitize($organization['name']); ?></h2>
                                <p class="profile-industry"><?php echo sanitize($organization['industry']); ?></p>
                                <span class="badge badge-<?php echo $organization['status'] === 'active' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($organization['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Profile Form -->
                <div class="card">
                    <div class="card-header">
                        <h2>Organization Information</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="name">Contact Person Name</label>
                                    <input type="text" id="name" name="name" value="<?php echo sanitize($organization['name']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" value="<?php echo sanitize($organization['email']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="org_name">Organization Name</label>
                                    <input type="text" id="org_name" name="org_name" value="<?php echo sanitize($organization['name']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="industry">Industry</label>
                                    <input type="text" id="industry" name="industry" value="<?php echo sanitize($organization['industry']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="contact_person">Contact Person</label>
                                    <input type="text" id="contact_person" name="contact_person" value="<?php echo sanitize($organization['contact_person']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="contact_phone">Contact Phone</label>
                                    <input type="tel" id="contact_phone" name="contact_phone" value="<?php echo sanitize($organization['contact_phone']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea id="address" name="address" rows="3" required><?php echo sanitize($organization['address']); ?></textarea>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i data-lucide="save"></i> Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Contract Information -->
                <div class="card">
                    <div class="card-header">
                        <h2>Contract Information</h2>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Contract Start Date:</label>
                                <span><?php echo $organization['contract_start_date'] ? formatDate($organization['contract_start_date'], 'd M Y') : 'Not set'; ?></span>
                            </div>
                            
                            <div class="info-item">
                                <label>Contract End Date:</label>
                                <span><?php echo $organization['contract_end_date'] ? formatDate($organization['contract_end_date'], 'd M Y') : 'Not set'; ?></span>
                            </div>
                            
                            <div class="info-item">
                                <label>Account Created:</label>
                                <span><?php echo formatDate($organization['created_at'], 'd M Y'); ?></span>
                            </div>
                            
                            <div class="info-item">
                                <label>Last Updated:</label>
                                <span><?php echo formatDate($organization['updated_at'], 'd M Y'); ?></span>
                            </div>
                        </div>
                        
                        <div class="note">
                            <p><strong>Note:</strong> Contract dates are managed by the security management team. 
                               Please contact your account manager for any contract-related changes.</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
    .profile-header {
        display: flex;
        align-items: center;
        gap: 2rem;
        margin-bottom: 2rem;
    }
    
    .profile-avatar {
        width: 80px;
        height: 80px;
        background: var(--primary-color);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: 600;
    }
    
    .profile-info h2 {
        margin: 0 0 0.5rem 0;
        font-size: 1.5rem;
    }
    
    .profile-industry {
        margin: 0 0 1rem 0;
        color: #666;
        font-size: 1rem;
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1rem;
    }
    
    .form-actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 2rem;
        padding-top: 1rem;
        border-top: 1px solid #eee;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .info-item {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .info-item label {
        font-weight: 600;
        color: #333;
        margin-bottom: 0;
    }
    
    .info-item span {
        color: #666;
    }
    
    .note {
        padding: 1rem;
        background: #e3f2fd;
        border-left: 4px solid var(--secondary-color);
        border-radius: 4px;
    }
    
    .note p {
        margin: 0;
        color: #0277bd;
    }
    </style>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>

