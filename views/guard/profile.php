<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('guard');

// Get guard information
$userId = $_SESSION['user_id'];
$query = "SELECT u.*, g.* FROM users u 
          JOIN guards g ON u.id = g.user_id 
          WHERE u.id = ?";
$guard = executeQuery($query, [$userId], ['single' => true]);

if (!$guard) {
    $_SESSION['error'] = 'Guard information not found';
    redirect(SITE_URL);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $emergency_contact = sanitize($_POST['emergency_contact']);
    $emergency_phone = sanitize($_POST['emergency_phone']);
    
    // Update user information
    $userQuery = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
    $userResult = executeQuery($userQuery, [$name, $email, $phone, $userId]);
    
    // Update guard information
    $guardQuery = "UPDATE guards SET address = ?, emergency_contact = ?, emergency_phone = ? WHERE user_id = ?";
    $guardResult = executeQuery($guardQuery, [$address, $emergency_contact, $emergency_phone, $userId]);
    
    if ($userResult && $guardResult) {
        $_SESSION['name'] = $name; // Update session name
        logActivity($userId, "Updated profile information", 'profile');
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
    <title>My Profile | <?php echo SITE_NAME; ?></title>
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
                    <h1>My Profile</h1>
                    <p>Manage your personal information</p>
                </div>
                
                <?php echo flashMessage('success'); ?>
                <?php echo flashMessage('error'); ?>
                
                <!-- Profile Overview -->
                <div class="card">
                    <div class="card-body">
                        <div class="profile-header">
                            <div class="profile-avatar">
                                <?php echo getInitials($guard['name']); ?>
                            </div>
                            <div class="profile-info">
                                <h2><?php echo sanitize($guard['name']); ?></h2>
                                <p class="profile-id">Guard ID: <?php echo sanitize($guard['id_number']); ?></p>
                                <span class="badge badge-<?php echo $guard['status'] === 'active' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($guard['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Profile Form -->
                <div class="card">
                    <div class="card-header">
                        <h2>Personal Information</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="name">Full Name</label>
                                    <input type="text" id="name" name="name" value="<?php echo sanitize($guard['name']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" value="<?php echo sanitize($guard['email']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone">Phone</label>
                                    <input type="tel" id="phone" name="phone" value="<?php echo sanitize($guard['phone']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="id_number">ID Number</label>
                                    <input type="text" id="id_number" value="<?php echo sanitize($guard['id_number']); ?>" readonly>
                                    <small class="form-help">Contact admin to change ID number</small>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea id="address" name="address" rows="3" required><?php echo sanitize($guard['address']); ?></textarea>
                            </div>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="emergency_contact">Emergency Contact Name</label>
                                    <input type="text" id="emergency_contact" name="emergency_contact" value="<?php echo sanitize($guard['emergency_contact']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="emergency_phone">Emergency Contact Phone</label>
                                    <input type="tel" id="emergency_phone" name="emergency_phone" value="<?php echo sanitize($guard['emergency_phone']); ?>" required>
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
                
                <!-- Read-only Information -->
                <div class="card">
                    <div class="card-header">
                        <h2>Employment Information</h2>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Date of Birth:</label>
                                <span><?php echo formatDate($guard['date_of_birth'], 'd M Y'); ?></span>
                            </div>
                            
                            <div class="info-item">
                                <label>Gender:</label>
                                <span><?php echo ucfirst($guard['gender']); ?></span>
                            </div>
                            
                            <div class="info-item">
                                <label>Joining Date:</label>
                                <span><?php echo formatDate($guard['joining_date'], 'd M Y'); ?></span>
                            </div>
                            
                            <div class="info-item">
                                <label>Account Created:</label>
                                <span><?php echo formatDate($guard['created_at'], 'd M Y'); ?></span>
                            </div>
                        </div>
                        
                        <?php if (!empty($guard['qualification'])): ?>
                        <div class="qualifications">
                            <label>Qualifications:</label>
                            <p><?php echo sanitize($guard['qualification']); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="note">
                            <p><strong>Note:</strong> To update employment information such as date of birth, gender, or qualifications, please contact your administrator.</p>
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
    
    .profile-id {
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
    
    .form-help {
        display: block;
        margin-top: 0.25rem;
        font-size: 0.875rem;
        color: #666;
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
    
    .qualifications {
        margin-bottom: 1.5rem;
    }
    
    .qualifications label {
        font-weight: 600;
        color: #333;
        display: block;
        margin-bottom: 0.5rem;
    }
    
    .qualifications p {
        margin: 0;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
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