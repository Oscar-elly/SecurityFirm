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
            case 'add_user':
                $name = sanitize($_POST['name']);
                $email = sanitize($_POST['email']);
                $phone = sanitize($_POST['phone']);
                $role = sanitize($_POST['role']);
                
                // Generate password
                $password = password_hash('password123', PASSWORD_DEFAULT);
                
                $query = "INSERT INTO users (name, email, password, role, phone, status) VALUES (?, ?, ?, ?, ?, 'active')";
                $result = executeQuery($query, [$name, $email, $password, $role, $phone]);
                
                if ($result) {
                    $_SESSION['success'] = 'User added successfully';
                } else {
                    $_SESSION['error'] = 'Failed to add user';
                }
                break;
                
            case 'update_status':
                $userId = (int)$_POST['user_id'];
                $status = sanitize($_POST['status']);
                
                $query = "UPDATE users SET status = ? WHERE id = ?";
                $result = executeQuery($query, [$status, $userId]);
                
                if ($result) {
                    $_SESSION['success'] = 'User status updated successfully';
                } else {
                    $_SESSION['error'] = 'Failed to update user status';
                }
                break;
        }
        redirect($_SERVER['PHP_SELF']);
    }
}

// Get all users
$query = "SELECT * FROM users ORDER BY created_at DESC";
$users = executeQuery($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Users Management | <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../../assets/css/styles.css" />
    <link rel="stylesheet" href="../../assets/css/dashboard.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/admin-sidebar.php'; ?>

        <main class="main-content">
            <?php include '../includes/top-nav.php'; ?>

            <div class="dashboard-content">
                <div class="dashboard-header">
                    <h1>Users Management</h1>
                    <button class="btn btn-primary" onclick="openAddUserModal()">
                        <i data-lucide="plus"></i> Add New User
                    </button>
                </div>

                <?php echo flashMessage('success'); ?>
                <?php echo flashMessage('error'); ?>

                <div class="card">
                    <div class="card-header">
                        <h2>All Users</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo sanitize($user['name']); ?></td>
                                        <td><?php echo sanitize($user['email']); ?></td>
                                        <td><?php echo sanitize($user['phone']); ?></td>
                                        <td><?php echo ucfirst($user['role']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($user['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="status" value="<?php echo $user['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                                <button type="submit" class="btn btn-sm <?php echo $user['status'] === 'active' ? 'btn-danger' : 'btn-success'; ?>">
                                                    <i data-lucide="<?php echo $user['status'] === 'active' ? 'user-x' : 'user-check'; ?>"></i>
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

    <!-- Add User Modal -->
    <div id="addUserModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New User</h3>
                <button onclick="closeAddUserModal()" class="btn btn-sm btn-outline">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_user">
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
                        <label for="role">Role</label>
                        <select id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="admin">Admin</option>
                            <option value="guard">Guard</option>
                            <option value="organization">Organization</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeAddUserModal()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
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
        
        function openAddUserModal() {
            document.getElementById('addUserModal').style.display = 'flex';
        }
        
        function closeAddUserModal() {
            document.getElementById('addUserModal').style.display = 'none';
        }
    </script>
</body>
</html>
