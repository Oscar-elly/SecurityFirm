<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('admin');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_organization':
                $name = sanitize($_POST['name']);
                $email = sanitize($_POST['email']);
                $org_name = sanitize($_POST['org_name']);
                $address = sanitize($_POST['address']);
                $contact_person = sanitize($_POST['contact_person']);
                $contact_phone = sanitize($_POST['contact_phone']);
                $industry = sanitize($_POST['industry']);
                $contract_start = sanitize($_POST['contract_start_date']);
                $contract_end = sanitize($_POST['contract_end_date']);
                
                // Generate password
                $password = password_hash('org123', PASSWORD_DEFAULT);
                
                // Insert user
                $userQuery = "INSERT INTO users (name, email, password, role, phone, status) VALUES (?, ?, ?, 'organization', ?, 'active')";
                $userResult = executeQuery($userQuery, [$name, $email, $password, $contact_phone]);
                
                if ($userResult && $userResult['insert_id']) {
                    // Insert organization details
                    $orgQuery = "INSERT INTO organizations (user_id, name, address, contact_person, contact_phone, industry, contract_start_date, contract_end_date) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $orgResult = executeQuery($orgQuery, [$userResult['insert_id'], $org_name, $address, $contact_person, $contact_phone, $industry, $contract_start, $contract_end]);
                    
                    if ($orgResult) {
                        $_SESSION['success'] = 'Organization added successfully';
                    } else {
                        $_SESSION['error'] = 'Failed to add organization details';
                    }
                } else {
                    $_SESSION['error'] = 'Failed to create user account';
                }
                break;
        }
        redirect($_SERVER['PHP_SELF']);
    }
}

// Get all organizations
$query = "SELECT u.*, o.* FROM users u 
          JOIN organizations o ON u.id = o.user_id 
          WHERE u.role = 'organization' 
          ORDER BY u.created_at DESC";
$organizations = executeQuery($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizations Management | <?php echo SITE_NAME; ?></title>
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
                    <h1>Organizations Management</h1>
                    <button class="btn btn-primary" onclick="openAddOrgModal()">
                        <i data-lucide="plus"></i> Add New Organization
                    </button>
                </div>
                
                <?php echo flashMessage('success'); ?>
                <?php echo flashMessage('error'); ?>
                
                <div class="card">
                    <div class="card-header">
                        <h2>All Organizations</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Organization Name</th>
                                        <th>Contact Person</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Industry</th>
                                        <th>Contract End</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($organizations as $org): ?>
                                    <tr>
                                        <td><?php echo sanitize($org['name']); ?></td>
                                        <td><?php echo sanitize($org['contact_person']); ?></td>
                                        <td><?php echo sanitize($org['email']); ?></td>
                                        <td><?php echo sanitize($org['contact_phone']); ?></td>
                                        <td><?php echo sanitize($org['industry']); ?></td>
                                        <td><?php echo $org['contract_end_date'] ? formatDate($org['contract_end_date'], 'd M Y') : 'N/A'; ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $org['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($org['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline" onclick="viewOrganization(<?php echo $org['user_id']; ?>)">
                                                <i data-lucide="eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning" onclick="editOrganization(<?php echo $org['user_id']; ?>)">
                                                <i data-lucide="edit"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Organization Modal -->
    <div id="addOrgModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Organization</h3>
                <button onclick="closeAddOrgModal()" class="btn btn-sm btn-outline">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_organization">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Contact Person Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="org_name">Organization Name</label>
                        <input type="text" id="org_name" name="org_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_person">Contact Person</label>
                        <input type="text" id="contact_person" name="contact_person" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_phone">Contact Phone</label>
                        <input type="tel" id="contact_phone" name="contact_phone" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="industry">Industry</label>
                        <input type="text" id="industry" name="industry" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="contract_start_date">Contract Start Date</label>
                        <input type="date" id="contract_start_date" name="contract_start_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="contract_end_date">Contract End Date</label>
                        <input type="date" id="contract_end_date" name="contract_end_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeAddOrgModal()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Organization</button>
                </div>
            </form>
        </div>
    </div>

    <style>
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .modal-content {
        background: white;
        border-radius: 8px;
        width: 90%;
        max-width: 600px;
        max-height: 90vh;
        overflow-y: auto;
    }
    
    .modal-header {
        padding: 1rem;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-body {
        padding: 1rem;
    }
    
    .modal-footer {
        padding: 1rem;
        border-top: 1px solid #eee;
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
    }
    </style>

    <script>
        lucide.createIcons();
        
        function openAddOrgModal() {
            document.getElementById('addOrgModal').style.display = 'flex';
        }
        
        function closeAddOrgModal() {
            document.getElementById('addOrgModal').style.display = 'none';
        }
        
        function viewOrganization(id) {
            window.location.href = 'view-organization.php?id=' + id;
        }
        
        function editOrganization(id) {
            window.location.href = 'edit-organization.php?id=' + id;
        }
    </script>
</body>
</html>