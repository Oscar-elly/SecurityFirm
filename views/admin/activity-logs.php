<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('admin');

// Get activity logs with user information
$query = "SELECT al.*, u.name as user_name, u.role as user_role 
          FROM activity_logs al 
          JOIN users u ON al.user_id = u.id 
          ORDER BY al.created_at DESC 
          LIMIT 500";
$activityLogs = executeQuery($query);

// Get activity statistics
$statsQuery = "SELECT type, COUNT(*) as count 
               FROM activity_logs 
               WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
               GROUP BY type";
$activityStats = executeQuery($statsQuery);

// Get today's activities
$todayQuery = "SELECT COUNT(*) as count 
               FROM activity_logs 
               WHERE DATE(created_at) = CURDATE()";
$todayCount = executeQuery($todayQuery, [], ['single' => true])['count'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs | <?php echo SITE_NAME; ?></title>
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
                    <h1>Activity Logs</h1>
                    <p>Monitor system activities and user actions</p>
                </div>
                
                <!-- Activity Statistics -->
                <div class="stats-cards">
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="activity"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $todayCount; ?></h3>
                            <p>Today's Activities</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="users"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo count(array_unique(array_column($activityLogs, 'user_id'))); ?></h3>
                            <p>Active Users</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="list"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo count($activityStats); ?></h3>
                            <p>Activity Types</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="database"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo count($activityLogs); ?></h3>
                            <p>Total Logs</p>
                        </div>
                    </div>
                </div>
                
                <!-- Activity Type Distribution -->
                <div class="card">
                    <div class="card-header">
                        <h2>Activity Distribution (Last 30 Days)</h2>
                    </div>
                    <div class="card-body">
                        <div class="activity-stats-grid">
                            <?php foreach ($activityStats as $stat): ?>
                            <div class="activity-stat-item">
                                <div class="activity-stat-icon">
                                    <i data-lucide="<?php echo getActivityIcon($stat['type']); ?>"></i>
                                </div>
                                <div class="activity-stat-details">
                                    <h3><?php echo $stat['count']; ?></h3>
                                    <p><?php echo ucfirst(str_replace('_', ' ', $stat['type'])); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Activity Logs Table -->
                <div class="card">
                    <div class="card-header">
                        <h2>Recent Activity Logs</h2>
                        <div class="card-actions">
                            <select id="typeFilter" class="form-control" style="width: auto; display: inline-block;">
                                <option value="">All Types</option>
                                <?php foreach (array_unique(array_column($activityLogs, 'type')) as $type): ?>
                                <option value="<?php echo $type; ?>"><?php echo ucfirst(str_replace('_', ' ', $type)); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select id="roleFilter" class="form-control" style="width: auto; display: inline-block;">
                                <option value="">All Roles</option>
                                <option value="admin">Admin</option>
                                <option value="guard">Guard</option>
                                <option value="organization">Organization</option>
                            </select>
                            <input type="date" id="dateFilter" class="form-control" style="width: auto; display: inline-block;">
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="activityTable">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Role</th>
                                        <th>Activity</th>
                                        <th>Type</th>
                                        <th>IP Address</th>
                                        <th>Date & Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activityLogs as $log): ?>
                                    <tr data-type="<?php echo $log['type']; ?>" 
                                        data-role="<?php echo $log['user_role']; ?>"
                                        data-date="<?php echo date('Y-m-d', strtotime($log['created_at'])); ?>">
                                        <td>
                                            <div class="user-info">
                                                <div class="user-avatar">
                                                    <?php echo getInitials($log['user_name']); ?>
                                                </div>
                                                <div class="user-details">
                                                    <strong><?php echo sanitize($log['user_name']); ?></strong>
                                                    <small>ID: <?php echo $log['user_id']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo getRoleBadgeClass($log['user_role']); ?>">
                                                <?php echo ucfirst($log['user_role']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo sanitize($log['activity']); ?></td>
                                        <td>
                                            <span class="activity-type">
                                                <i data-lucide="<?php echo getActivityIcon($log['type']); ?>"></i>
                                                <?php echo ucfirst(str_replace('_', ' ', $log['type'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo sanitize($log['ip_address'] ?? 'N/A'); ?></td>
                                        <td>
                                            <div class="datetime-info">
                                                <div><?php echo formatDate($log['created_at'], 'd M Y'); ?></div>
                                                <small><?php echo formatDate($log['created_at'], 'h:i A'); ?></small>
                                            </div>
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

    <style>
    .card-actions {
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .activity-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }
    
    .activity-stat-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .activity-stat-icon {
        width: 40px;
        height: 40px;
        background: var(--primary-color);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .activity-stat-details h3 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 600;
    }
    
    .activity-stat-details p {
        margin: 0;
        color: #666;
        font-size: 0.875rem;
    }
    
    .user-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .user-avatar {
        width: 32px;
        height: 32px;
        background: var(--primary-color);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .user-details strong {
        display: block;
        font-size: 0.875rem;
    }
    
    .user-details small {
        color: #666;
        font-size: 0.75rem;
    }
    
    .activity-type {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
    }
    
    .datetime-info div {
        font-weight: 500;
    }
    
    .datetime-info small {
        color: #666;
    }
    
    @media (max-width: 768px) {
        .card-actions {
            flex-direction: column;
            align-items: stretch;
        }
        
        .card-actions select,
        .card-actions input {
            width: 100% !important;
        }
    }
    </style>

    <script>
        lucide.createIcons();
        
        // Filter functionality
        document.getElementById('typeFilter').addEventListener('change', filterTable);
        document.getElementById('roleFilter').addEventListener('change', filterTable);
        document.getElementById('dateFilter').addEventListener('change', filterTable);
        
        function filterTable() {
            const typeFilter = document.getElementById('typeFilter').value;
            const roleFilter = document.getElementById('roleFilter').value;
            const dateFilter = document.getElementById('dateFilter').value;
            const rows = document.querySelectorAll('#activityTable tbody tr');
            
            rows.forEach(row => {
                const type = row.dataset.type;
                const role = row.dataset.role;
                const date = row.dataset.date;
                
                const typeMatch = !typeFilter || type === typeFilter;
                const roleMatch = !roleFilter || role === roleFilter;
                const dateMatch = !dateFilter || date === dateFilter;
                
                if (typeMatch && roleMatch && dateMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        function getInitials(name) {
            return name.split(' ').map(word => word.charAt(0)).join('').substring(0, 2).toUpperCase();
        }
    </script>
</body>
</html>

<?php
function getActivityIcon($type) {
    switch ($type) {
        case 'auth': return 'log-in';
        case 'incident': return 'alert-triangle';
        case 'attendance': return 'clock';
        case 'assignment': return 'calendar';
        case 'request': return 'file-text';
        case 'evaluation': return 'star';
        case 'general': return 'activity';
        default: return 'circle';
    }
}

function getRoleBadgeClass($role) {
    switch ($role) {
        case 'admin': return 'danger';
        case 'guard': return 'success';
        case 'organization': return 'primary';
        default: return 'secondary';
    }
}
<<<<<<< HEAD
=======

function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $word) {
        $initials .= strtoupper(substr($word, 0, 1));
    }
    return substr($initials, 0, 2);
}
>>>>>>> e01608b833e801a50a96cb8615f011daabc9025b
?>