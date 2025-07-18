<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('admin');

// Handle request actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $request_id = (int)$_POST['request_id'];
    $action = sanitize($_POST['action']);
    
    if ($action === 'approve') {
        $query = "UPDATE guard_requests SET status = 'approved' WHERE id = ?";
        $result = executeQuery($query, [$request_id]);
        
        if ($result) {
            $_SESSION['success'] = 'Guard request approved successfully';
        } else {
            $_SESSION['error'] = 'Failed to approve guard request';
        }
    } elseif ($action === 'reject') {
        $notes = sanitize($_POST['notes'] ?? '');
        $query = "UPDATE guard_requests SET status = 'rejected', notes = ? WHERE id = ?";
        $result = executeQuery($query, [$notes, $request_id]);
        
        if ($result) {
            $_SESSION['success'] = 'Guard request rejected';
        } else {
            $_SESSION['error'] = 'Failed to reject guard request';
        }
    }
    
    redirect($_SERVER['PHP_SELF']);
}

// Get all guard requests
$query = "SELECT gr.*, o.name as organization_name, l.name as location_name, 
                 s.name as shift_name, s.start_time, s.end_time
          FROM guard_requests gr 
          JOIN organizations o ON gr.organization_id = o.id 
          JOIN locations l ON gr.location_id = l.id 
          JOIN shifts s ON gr.shift_id = s.id 
          ORDER BY gr.created_at DESC";
$requests = executeQuery($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guard Requests | <?php echo SITE_NAME; ?></title>
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
                    <h1>Guard Requests</h1>
                    <div class="dashboard-actions">
                        <select id="statusFilter" class="form-control" style="width: auto; display: inline-block;">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>
                
                <?php echo flashMessage('success'); ?>
                <?php echo flashMessage('error'); ?>
                
                <div class="card">
                    <div class="card-header">
                        <h2>All Guard Requests</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="requestsTable">
                                <thead>
                                    <tr>
                                        <th>Organization</th>
                                        <th>Location</th>
                                        <th>Guards Needed</th>
                                        <th>Shift</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Status</th>
                                        <th>Requested</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requests as $request): ?>
                                    <tr data-status="<?php echo $request['status']; ?>">
                                        <td><?php echo sanitize($request['organization_name']); ?></td>
                                        <td><?php echo sanitize($request['location_name']); ?></td>
                                        <td>
                                            <span class="badge badge-primary"><?php echo $request['number_of_guards']; ?></span>
                                        </td>
                                        <td>
                                            <?php echo sanitize($request['shift_name']); ?>
                                            <br><small><?php echo formatTime($request['start_time']) . ' - ' . formatTime($request['end_time']); ?></small>
                                        </td>
                                        <td><?php echo formatDate($request['start_date'], 'd M Y'); ?></td>
                                        <td><?php echo $request['end_date'] ? formatDate($request['end_date'], 'd M Y') : 'Ongoing'; ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo getRequestStatusClass($request['status']); ?>">
                                                <?php echo ucfirst($request['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($request['created_at']); ?></td>
                                        <td>
                                            <?php if ($request['status'] === 'pending'): ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to approve this request?')">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i data-lucide="check"></i>
                                                </button>
                                            </form>
                                            
                                            <button class="btn btn-sm btn-danger" onclick="rejectRequest(<?php echo $request['id']; ?>)">
                                                <i data-lucide="x"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($request['status'] === 'approved'): ?>
                                            <button class="btn btn-sm btn-primary" onclick="assignGuards(<?php echo $request['id']; ?>)">
                                                <i data-lucide="user-plus"></i> Assign
                                            </button>
                                            <?php endif; ?>
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

    <!-- Reject Request Modal -->
    <div id="rejectModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Reject Guard Request</h3>
                <button onclick="closeRejectModal()" class="btn btn-sm btn-outline">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="POST" id="rejectForm">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="request_id" id="rejectRequestId">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="notes">Reason for Rejection</label>
                        <textarea id="notes" name="notes" rows="4" placeholder="Please provide a reason for rejecting this request"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeRejectModal()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Request</button>
                </div>
            </form>
        </div>
    </div>

    <style>
    .dashboard-actions {
        display: flex;
        gap: 1rem;
        align-items: center;
    }
    
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
        max-width: 500px;
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
        
        // Status filter
        document.getElementById('statusFilter').addEventListener('change', function() {
            const selectedStatus = this.value;
            const rows = document.querySelectorAll('#requestsTable tbody tr');
            
            rows.forEach(row => {
                const status = row.dataset.status;
                if (!selectedStatus || status === selectedStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        function viewRequest(id) {
            window.location.href = 'view-request.php?id=' + id;
        }
        
        function rejectRequest(id) {
            document.getElementById('rejectRequestId').value = id;
            document.getElementById('rejectModal').style.display = 'flex';
        }
        
        function closeRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
        }
        
        function assignGuards(requestId) {
            window.location.href = 'assign-guards.php?request_id=' + requestId;
        }
    </script>
</body>
</html>

<?php
function formatTime($time) {
    return date('h:i A', strtotime($time));
}

function getRequestStatusClass($status) {
    switch ($status) {
        case 'pending': return 'warning';
        case 'approved': return 'success';
        case 'rejected': return 'danger';
        case 'completed': return 'secondary';
        default: return 'secondary';
    }
}
?>