<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('organization');

// Get organization information
$userId = $_SESSION['user_id'];
$query = "SELECT * FROM organizations WHERE user_id = ?";
$organization = executeQuery($query, [$userId], ['single' => true]);

if (!$organization) {
    $_SESSION['error'] = 'Organization information not found';
    redirect(SITE_URL);
}

// Get all guard requests for this organization
$query = "SELECT gr.*, l.name as location_name, s.name as shift_name, s.start_time, s.end_time
          FROM guard_requests gr 
          JOIN locations l ON gr.location_id = l.id 
          JOIN shifts s ON gr.shift_id = s.id 
          WHERE gr.organization_id = ? 
          ORDER BY gr.created_at DESC";
$requests = executeQuery($query, [$organization['id']]);
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
                    <h1>Guard Requests</h1>
                    <a href="request-guard.php" class="btn btn-primary">
                        <i data-lucide="plus"></i> New Request
                    </a>
                </div>
                
                <!-- Request Statistics -->
                <div class="stats-cards">
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="file-text"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo count($requests); ?></h3>
                            <p>Total Requests</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="clock"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo count(array_filter($requests, function($r) { return $r['status'] === 'pending'; })); ?></h3>
                            <p>Pending</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="check-circle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo count(array_filter($requests, function($r) { return $r['status'] === 'approved'; })); ?></h3>
                            <p>Approved</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="shield"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo array_sum(array_column($requests, 'number_of_guards')); ?></h3>
                            <p>Guards Requested</p>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2>All Requests</h2>
                        <div class="card-actions">
                            <select id="statusFilter" class="form-control" style="width: auto; display: inline-block;">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($requests)): ?>
                            <div class="requests-list">
                                <?php foreach ($requests as $request): ?>
                                <div class="request-card" data-status="<?php echo $request['status']; ?>">
                                    <div class="request-header">
                                        <div class="request-info">
                                            <h3><?php echo sanitize($request['location_name']); ?></h3>
                                            <div class="request-meta">
                                                <span class="request-guards">
                                                    <i data-lucide="shield"></i>
                                                    <?php echo $request['number_of_guards']; ?> Guard<?php echo $request['number_of_guards'] > 1 ? 's' : ''; ?>
                                                </span>
                                                <span class="request-shift">
                                                    <i data-lucide="clock"></i>
                                                    <?php echo sanitize($request['shift_name']); ?>
                                                </span>
                                                <span class="request-date">
                                                    <i data-lucide="calendar"></i>
                                                    <?php echo formatDate($request['start_date'], 'd M Y'); ?>
                                                    <?php if ($request['end_date']): ?>
                                                        - <?php echo formatDate($request['end_date'], 'd M Y'); ?>
                                                    <?php else: ?>
                                                        (Ongoing)
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="request-status">
                                            <span class="badge badge-<?php echo getRequestStatusClass($request['status']); ?>">
                                                <?php echo ucfirst($request['status']); ?>
                                            </span>
                                            <div class="request-time">
                                                Requested <?php echo getTimeAgo($request['created_at']); ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="request-body">
                                        <div class="request-details">
                                            <div class="detail-item">
                                                <strong>Shift Time:</strong>
                                                <?php echo formatTime($request['start_time']) . ' - ' . formatTime($request['end_time']); ?>
                                            </div>
                                            <div class="detail-item">
                                                <strong>Reason:</strong>
                                                <?php echo sanitize($request['reason']); ?>
                                            </div>
                                            <?php if (!empty($request['notes'])): ?>
                                            <div class="detail-item">
                                                <strong>Admin Notes:</strong>
                                                <?php echo sanitize($request['notes']); ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="request-footer">
                                        <div class="request-actions">
                                            <button class="btn btn-sm btn-outline" onclick="viewRequest(<?php echo $request['id']; ?>)">
                                                <i data-lucide="eye"></i> View Details
                                            </button>
                                            
                                            <?php if ($request['status'] === 'pending'): ?>
                                            <button class="btn btn-sm btn-warning" onclick="editRequest(<?php echo $request['id']; ?>)">
                                                <i data-lucide="edit"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="cancelRequest(<?php echo $request['id']; ?>)">
                                                <i data-lucide="x"></i> Cancel
                                            </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($request['status'] === 'approved'): ?>
                                            <span class="text-success">
                                                <i data-lucide="check-circle"></i> Guards will be assigned soon
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <div class="no-duty-icon">
                                    <i data-lucide="file-text"></i>
                                </div>
                                <p>You haven't made any guard requests yet.</p>
                                <a href="request-guard.php" class="btn btn-primary">
                                    <i data-lucide="plus"></i> Make Your First Request
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
    .card-actions {
        display: flex;
        gap: 1rem;
        align-items: center;
    }
    
    .requests-list {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .request-card {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 1.5rem;
        transition: box-shadow 0.2s ease;
    }
    
    .request-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .request-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }
    
    .request-info h3 {
        margin: 0 0 0.5rem 0;
        font-size: 1.25rem;
        color: #333;
    }
    
    .request-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        font-size: 0.875rem;
        color: #666;
    }
    
    .request-meta span {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }
    
    .request-status {
        text-align: right;
        flex-shrink: 0;
    }
    
    .request-time {
        font-size: 0.875rem;
        color: #666;
        margin-top: 0.5rem;
    }
    
    .request-body {
        margin-bottom: 1rem;
    }
    
    .request-details {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .detail-item {
        font-size: 0.875rem;
        color: #555;
    }
    
    .detail-item strong {
        color: #333;
        margin-right: 0.5rem;
    }
    
    .request-footer {
        padding-top: 1rem;
        border-top: 1px solid #f0f0f0;
    }
    
    .request-actions {
        display: flex;
        gap: 0.5rem;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .text-success {
        color: var(--success-color);
        font-size: 0.875rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }
    
    .no-data {
        text-align: center;
        padding: 3rem 1rem;
    }
    
    .no-duty-icon {
        width: 80px;
        height: 80px;
        background-color: #f0f0f0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
    }
    
    @media (max-width: 768px) {
        .request-header {
            flex-direction: column;
            gap: 1rem;
        }
        
        .request-meta {
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .request-actions {
            flex-direction: column;
            align-items: flex-start;
        }
    }
    </style>

    <script>
        lucide.createIcons();
        
        // Status filter
        document.getElementById('statusFilter').addEventListener('change', function() {
            const selectedStatus = this.value;
            const cards = document.querySelectorAll('.request-card');
            
            cards.forEach(card => {
                const status = card.dataset.status;
                if (!selectedStatus || status === selectedStatus) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
        
        function viewRequest(id) {
            window.location.href = 'view-request.php?id=' + id;
        }
        
        function editRequest(id) {
            window.location.href = 'edit-request.php?id=' + id;
        }
        
        function cancelRequest(id) {
            if (confirm('Are you sure you want to cancel this request?')) {
                // Implement cancel functionality
                window.location.href = 'cancel-request.php?id=' + id;
            }
        }
        
        function getTimeAgo(datetime) {
            const now = new Date();
            const time = new Date(datetime);
            const diff = Math.floor((now - time) / 1000);
            
            if (diff < 60) return 'just now';
            if (diff < 3600) return Math.floor(diff / 60) + ' minutes ago';
            if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
            if (diff < 604800) return Math.floor(diff / 86400) + ' days ago';
            return time.toLocaleDateString();
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