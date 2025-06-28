<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('admin');

$org_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$org_id) {
    $_SESSION['error'] = 'Invalid organization ID';
    redirect('organizations.php');
}

// Get organization information
$query = "SELECT u.*, o.* FROM users u 
          JOIN organizations o ON u.id = o.user_id 
          WHERE u.id = ? AND u.role = 'organization'";
$organization = executeQuery($query, [$org_id], ['single' => true]);

if (!$organization) {
    $_SESSION['error'] = 'Organization not found';
    redirect('organizations.php');
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
    $contract_start = sanitize($_POST['contract_start_date']);
    $contract_end = sanitize($_POST['contract_end_date']);
    
    // Update user information
    $userQuery = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
    $userResult = executeQuery($userQuery, [$name, $email, $contact_phone, $org_id]);
    
    // Update organization information
    $orgQuery = "UPDATE organizations SET name = ?, address = ?, contact_person = ?, 
                 contact_phone = ?, industry = ?, contract_start_date = ?, contract_end_date = ? 
                 WHERE user_id = ?";
    $orgResult = executeQuery($orgQuery, [$org_name, $address, $contact_person, $contact_phone, 
                                         $industry, $contract_start, $contract_end, $org_id]);
    
    if ($userResult && $orgResult) {
        logActivity($_SESSION['user_id'], "Updated organization profile: " . $org_name, 'admin');
        $_SESSION['success'] = 'Organization profile updated successfully';
        redirect('view-organization.php?id=' . $org_id);
    } else {
        $_SESSION['error'] = 'Failed to update organization profile';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Organization - <?php echo sanitize($organization['name']); ?> | <?php echo SITE_NAME; ?></title>
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
                    <h1>Edit Organization Profile</h1>
                    <div class="dashboard-actions">
                        <a href="view-organization.php?id=<?php echo $org_id; ?>" class="btn btn-outline">
                            <i data-lucide="arrow-left"></i> Back to Profile
                        </a>
                    </div>
                </div>
                
                <?php echo flashMessage('error'); ?>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Organization Information</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="name">Contact Person Name *</label>
                                    <input type="text" id="name" name="name" value="<?php echo sanitize($organization['name']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email *</label>
                                    <input type="email" id="email" name="email" value="<?php echo sanitize($organization['email']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="org_name">Organization Name *</label>
                                    <input type="text" id="org_name" name="org_name" value="<?php echo sanitize($organization['name']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="industry">Industry *</label>
                                    <input type="text" id="industry" name="industry" value="<?php echo sanitize($organization['industry']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="contact_person">Contact Person *</label>
                                    <input type="text" id="contact_person" name="contact_person" value="<?php echo sanitize($organization['contact_person']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="contact_phone">Contact Phone *</label>
                                    <input type="tel" id="contact_phone" name="contact_phone" value="<?php echo sanitize($organization['contact_phone']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Address *</label>
                                <textarea id="address" name="address" rows="3" required><?php echo sanitize($organization['address']); ?></textarea>
                            </div>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="contract_start_date">Contract Start Date</label>
                                    <input type="date" id="contract_start_date" name="contract_start_date" value="<?php echo $organization['contract_start_date']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="contract_end_date">Contract End Date</label>
                                    <input type="date" id="contract_end_date" name="contract_end_date" value="<?php echo $organization['contract_end_date']; ?>">
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <a href="view-organization.php?id=<?php echo $org_id; ?>" class="btn btn-outline">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i data-lucide="save"></i> Update Organization
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
        
        // Ensure contract end date is after start date
        document.getElementById('contract_start_date').addEventListener('change', function() {
            const endDateInput = document.getElementById('contract_end_date');
            endDateInput.min = this.value;
            
            if (endDateInput.value && endDateInput.value < this.value) {
                endDateInput.value = '';
            }
        });
    </script>
</body>
</html>