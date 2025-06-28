<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('admin');

$guard_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$guard_id) {
    $_SESSION['error'] = 'Invalid guard ID';
    redirect('guards.php');
}

// Get guard information
$query = "SELECT u.*, g.* FROM users u 
          JOIN guards g ON u.id = g.user_id 
          WHERE u.id = ? AND u.role = 'guard'";
$guard = executeQuery($query, [$guard_id], ['single' => true]);

if (!$guard) {
    $_SESSION['error'] = 'Guard not found';
    redirect('guards.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $id_number = sanitize($_POST['id_number']);
    $date_of_birth = sanitize($_POST['date_of_birth']);
    $gender = sanitize($_POST['gender']);
    $address = sanitize($_POST['address']);
    $emergency_contact = sanitize($_POST['emergency_contact']);
    $emergency_phone = sanitize($_POST['emergency_phone']);
    $qualification = sanitize($_POST['qualification']);
    
    // Update user information
    $userQuery = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
    $userResult = executeQuery($userQuery, [$name, $email, $phone, $guard_id]);
    
    // Update guard information
    $guardQuery = "UPDATE guards SET id_number = ?, date_of_birth = ?, gender = ?, address = ?, 
                   emergency_contact = ?, emergency_phone = ?, qualification = ? WHERE user_id = ?";
    $guardResult = executeQuery($guardQuery, [$id_number, $date_of_birth, $gender, $address, 
                                              $emergency_contact, $emergency_phone, $qualification, $guard_id]);
    
    if ($userResult && $guardResult) {
        logActivity($_SESSION['user_id'], "Updated guard profile: " . $name, 'admin');
        $_SESSION['success'] = 'Guard profile updated successfully';
        redirect('view-guard.php?id=' . $guard_id);
    } else {
        $_SESSION['error'] = 'Failed to update guard profile';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Guard - <?php echo sanitize($guard['name']); ?> | <?php echo SITE_NAME; ?></title>
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
                    <h1>Edit Guard Profile</h1>
                    <div class="dashboard-actions">
                        <a href="view-guard.php?id=<?php echo $guard_id; ?>" class="btn btn-outline">
                            <i data-lucide="arrow-left"></i> Back to Profile
                        </a>
                    </div>
                </div>
                
                <?php echo flashMessage('error'); ?>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Guard Information</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="name">Full Name *</label>
                                    <input type="text" id="name" name="name" value="<?php echo sanitize($guard['name']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email *</label>
                                    <input type="email" id="email" name="email" value="<?php echo sanitize($guard['email']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone">Phone *</label>
                                    <input type="tel" id="phone" name="phone" value="<?php echo sanitize($guard['phone']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="id_number">ID Number *</label>
                                    <input type="text" id="id_number" name="id_number" value="<?php echo sanitize($guard['id_number']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="date_of_birth">Date of Birth *</label>
                                    <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo $guard['date_of_birth']; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="gender">Gender *</label>
                                    <select id="gender" name="gender" required>
                                        <option value="male" <?php echo $guard['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="female" <?php echo $guard['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="other" <?php echo $guard['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Address *</label>
                                <textarea id="address" name="address" rows="3" required><?php echo sanitize($guard['address']); ?></textarea>
                            </div>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="emergency_contact">Emergency Contact Name *</label>
                                    <input type="text" id="emergency_contact" name="emergency_contact" value="<?php echo sanitize($guard['emergency_contact']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="emergency_phone">Emergency Contact Phone *</label>
                                    <input type="tel" id="emergency_phone" name="emergency_phone" value="<?php echo sanitize($guard['emergency_phone']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="qualification">Qualifications</label>
                                <textarea id="qualification" name="qualification" rows="3"><?php echo sanitize($guard['qualification']); ?></textarea>
                            </div>
                            
                            <div class="form-actions">
                                <a href="view-guard.php?id=<?php echo $guard_id; ?>" class="btn btn-outline">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i data-lucide="save"></i> Update Guard
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1rem;
    }
    
    .form-actions {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        margin-top: 2rem;
        padding-top: 1rem;
        border-top: 1px solid #eee;
    }
    </style>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>