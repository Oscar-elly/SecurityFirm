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
            case 'add_guard':
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
                
                // Generate password
                $password = password_hash('guard123', PASSWORD_DEFAULT);
                
                // Insert user
                $userQuery = "INSERT INTO users (name, email, password, role, phone, status) VALUES (?, ?, ?, 'guard', ?, 'active')";
                $userResult = executeQuery($userQuery, [$name, $email, $password, $phone]);
                
                if ($userResult && $userResult['insert_id']) {
                    // Insert guard details
                    $guardQuery = "INSERT INTO guards (user_id, id_number, date_of_birth, gender, address, emergency_contact, emergency_phone, joining_date, qualification) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), ?)";
                    $guardResult = executeQuery($guardQuery, [$userResult['insert_id'], $id_number, $date_of_birth, $gender, $address, $emergency_contact, $emergency_phone, $qualification]);
                    
                    if ($guardResult) {
                        $_SESSION['success'] = 'Guard added successfully';
                    } else {
                        $_SESSION['error'] = 'Failed to add guard details';
                    }
                } else {
                    $_SESSION['error'] = 'Failed to create user account';
                }
                break;
                
            case 'update_status':
                $userId = (int)$_POST['user_id'];
                $status = sanitize($_POST['status']);
                
                $query = "UPDATE users SET status = ? WHERE id = ? AND role = 'guard'";
                $result = executeQuery($query, [$status, $userId]);
                
                if ($result) {
                    $_SESSION['success'] = 'Guard status updated successfully';
                } else {
                    $_SESSION['error'] = 'Failed to update guard status';
                }
                break;
        }
        redirect($_SERVER['PHP_SELF']);
    }
}

// Get all guards
$query = "SELECT u.*, g.* FROM users u 
          JOIN guards g ON u.id = g.user_id 
          WHERE u.role = 'guard' 
          ORDER BY u.created_at DESC";
$guards = executeQuery($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guards Management | <?php echo SITE_NAME; ?></title>
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
                    <h1>Guards Management</h1>
                    <button class="btn btn-primary" onclick="openAddGuardModal()">
                        <i data-lucide="plus"></i> Add New Guard
                    </button>
                </div>
                
                <?php echo flashMessage('success'); ?>
                <?php echo flashMessage('error'); ?>
                
                <div class="card">
                    <div class="card-header">
                        <h2>All Guards</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>ID Number</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Joining Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($guards as $guard): ?>
                                    <tr>
                                        <td><?php echo sanitize($guard['name']); ?></td>
                                        <td><?php echo sanitize($guard['id_number']); ?></td>
                                        <td><?php echo sanitize($guard['email']); ?></td>
                                        <td><?php echo sanitize($guard['phone']); ?></td>
                                        <td><?php echo formatDate($guard['joining_date'], 'd M Y'); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $guard['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($guard['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline" onclick="viewGuard(<?php echo $guard['user_id']; ?>)" style="pointer-events:auto;">
                                                <i data-lucide="eye" style="pointer-events:none;"></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning" onclick="editGuard(<?php echo $guard['user_id']; ?>)" style="pointer-events:auto;">
                                                <i data-lucide="edit" style="pointer-events:none;"></i>
                                            </button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="user_id" value="<?php echo $guard['user_id']; ?>">
                                                <input type="hidden" name="status" value="<?php echo $guard['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                                <button type="submit" class="btn btn-sm <?php echo $guard['status'] === 'active' ? 'btn-danger' : 'btn-success'; ?>">
                                                    <i data-lucide="<?php echo $guard['status'] === 'active' ? 'user-x' : 'user-check'; ?>"></i>
                                                </button>
                                            </form>
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

    <!-- Add Guard Modal -->
    <div id="addGuardModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Guard</h3>
                <button onclick="closeAddGuardModal()" class="btn btn-sm btn-outline">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_guard">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="id_number">ID Number</label>
                        <input type="text" id="id_number" name="id_number" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="emergency_contact">Emergency Contact Name</label>
                        <input type="text" id="emergency_contact" name="emergency_contact" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="emergency_phone">Emergency Contact Phone</label>
                        <input type="tel" id="emergency_phone" name="emergency_phone" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="qualification">Qualifications</label>
                        <textarea id="qualification" name="qualification"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeAddGuardModal()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Guard</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Guard Modal -->
    <div id="guardModal" class="modal" style="display: none;">
        <div class="modal-content" id="guardModalContent">
            <!-- Content loaded dynamically -->
        </div>
        <button onclick="closeGuardModal()" class="btn btn-sm btn-outline" style="position: absolute; top: 10px; right: 10px;">
            <i data-lucide="x"></i>
        </button>
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
        
        function openAddGuardModal() {
            document.getElementById('addGuardModal').style.display = 'flex';
        }
        
        function closeAddGuardModal() {
            document.getElementById('addGuardModal').style.display = 'none';
        }
        
        function viewGuard(id) {
            // Load guard details in modal
            fetch('view-guard.php?id=' + id)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(html => {
                    const modalContent = document.getElementById('guardModalContent');
                    if (modalContent) {
                        modalContent.innerHTML = html;
                        document.getElementById('guardModal').style.display = 'flex';
                    } else {
                        console.error('Modal content container not found');
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                });
        }
        
        function editGuard(id) {
            // Load guard edit form in modal
            fetch('edit-guard.php?id=' + id)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(html => {
                    const modalContent = document.getElementById('guardModalContent');
                    if (modalContent) {
                        modalContent.innerHTML = html;
                        document.getElementById('guardModal').style.display = 'flex';
                    } else {
                        console.error('Modal content container not found');
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                });
        }
    </script>
</body>
</html>