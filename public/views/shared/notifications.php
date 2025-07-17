<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireLogin();

$userId = $_SESSION['user_id'];

// Handle mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'mark_read') {
        $notification_id = (int)$_POST['notification_id'];
        
        $query = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
        $result = executeQuery($query, [$notification_id, $userId]);
        
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    } elseif ($_POST['action'] === 'mark_all_read') {
        $query = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
        $result = executeQuery($query, [$userId]);
        
        if ($result) {
            $_SESSION['success'] = 'All notifications marked as read';
        } else {
            $_SESSION['error'] = 'Failed to mark notifications as read';
        }
        redirect($_SERVER['PHP_SELF']);
    }
}

// Get all notifications for this user
$query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$notifications = executeQuery($query, [$userId]);

// Separate read and unread
$unreadNotifications = array_filter($notifications, function($n) { return !$n['is_read']; });
$readNotifications = array_filter($notifications, function($n) { return $n['is_read']; });
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <div class="dashboard-container">
        <?php 
        switch ($_SESSION['role']) {
            case 'admin':
                include '../includes/admin-sidebar.php';
                break;
            case 'guard':
                include '../includes/guard-sidebar.php';
                break;
            case 'organization':
                include '../includes/organization-sidebar.php';
                break;
        }
        ?>
        
        <main class="main-content">
            <?php include '../includes/top-nav.php'; ?>
            
            <div class="dashboard-content">
                <div class="dashboard-header">
                    <h1>Notifications</h1>
                    <?php if (!empty($unreadNotifications)): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="mark_all_read">
                        <button type="submit" class="btn btn-outline">
                            <i data-lucide="check-circle"></i> Mark All as Read
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                
                <?php echo flashMessage('success'); ?>
                <?php echo flashMessage('error'); ?>
                
                <!-- Notification Statistics -->
                <div class="stats-cards">
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="bell"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo count($notifications); ?></h3>
                            <p>Total Notifications</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="bell-ring"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo count($unreadNotifications); ?></h3>
                            <p>Unread</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="check-circle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo count($readNotifications); ?></h3>
                            <p>Read</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="calendar"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo count(array_filter($notifications, function($n) { return date('Y-m-d', strtotime($n['created_at'])) === date('Y-m-d'); })); ?></h3>
                            <p>Today</p>
                        </div>
                    </div>
                </div>
                
                <!-- Unread Notifications -->
                <?php if (!empty($unreadNotifications)): ?>
                <div class="card">
                    <div class="card-header">
                        <h2>Unread Notifications</h2>
                        <span class="badge badge-primary"><?php echo count($unreadNotifications); ?></span>
                    </div>
                    <div class="card-body">
                        <div class="notifications-list">
                            <?php foreach ($unreadNotifications as $notification): ?>
                            <div class="notification-item unread" data-id="<?php echo $notification['id']; ?>">
                                <div class="notification-icon">
                                    <i data-lucide="<?php echo getNotificationIcon($notification['type']); ?>"></i>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-header">
                                        <h3><?php echo sanitize($notification['title']); ?></h3>
                                        <div class="notification-time"><?php echo getTimeAgo($notification['created_at']); ?></div>
                                    </div>
                                    <p><?php echo sanitize($notification['message']); ?></p>
                                    <?php if (!empty($notification['link'])): ?>
                                    <a href="<?php echo sanitize($notification['link']); ?>" class="notification-link">
                                        <i data-lucide="external-link"></i> View Details
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <div class="notification-actions">
                                    <button class="btn btn-sm btn-outline" onclick="markAsRead(<?php echo $notification['id']; ?>)">
                                        <i data-lucide="check"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- All Notifications -->
                <div class="card">
                    <div class="card-header">
                        <h2>All Notifications</h2>
                        <div class="card-actions">
                            <select id="typeFilter" class="form-control" style="width: auto; display: inline-block;">
                                <option value="">All Types</option>
                                <option value="incident">Incidents</option>
                                <option value="assignment">Assignments</option>
                                <option value="message">Messages</option>
                                <option value="system">System</option>
                                <option value="request">Requests</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($notifications)): ?>
                            <div class="notifications-list">
                                <?php foreach ($notifications as $notification): ?>
                                <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>" 
                                     data-id="<?php echo $notification['id']; ?>"
                                     data-type="<?php echo $notification['type']; ?>">
                                    <div class="notification-icon">
                                        <i data-lucide="<?php echo getNotificationIcon($notification['type']); ?>"></i>
                                    </div>
                                    <div class="notification-content">
                                        <div class="notification-header">
                                            <h3><?php echo sanitize($notification['title']); ?></h3>
                                            <div class="notification-time"><?php echo getTimeAgo($notification['created_at']); ?></div>
                                        </div>
                                        <p><?php echo sanitize($notification['message']); ?></p>
                                        <?php if (!empty($notification['link'])): ?>
                                        <a href="<?php echo sanitize($notification['link']); ?>" class="notification-link">
                                            <i data-lucide="external-link"></i> View Details
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!$notification['is_read']): ?>
                                    <div class="notification-actions">
                                        <button class="btn btn-sm btn-outline" onclick="markAsRead(<?php echo $notification['id']; ?>)">
                                            <i data-lucide="check"></i>
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <div class="no-data-icon">
                                    <i data-lucide="bell-off"></i>
                                </div>
                                <p>No notifications yet.</p>
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
    
    .notifications-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .notification-item {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: 1.5rem;
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        transition: all 0.2s ease;
    }
    
    .notification-item:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .notification-item.unread {
        background: rgba(26, 35, 126, 0.05);
        border-left: 4px solid var(--primary-color);
    }
    
    .notification-item.read {
        opacity: 0.8;
    }
    
    .notification-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .notification-item.unread .notification-icon {
        background: var(--primary-color);
        color: white;
    }
    
    .notification-item.read .notification-icon {
        background: #f0f0f0;
        color: #666;
    }
    
    .notification-content {
        flex: 1;
        min-width: 0;
    }
    
    .notification-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.5rem;
    }
    
    .notification-header h3 {
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
        color: #333;
    }
    
    .notification-time {
        font-size: 0.875rem;
        color: #666;
        flex-shrink: 0;
        margin-left: 1rem;
    }
    
    .notification-content p {
        margin: 0 0 0.5rem 0;
        color: #555;
        line-height: 1.5;
    }
    
    .notification-link {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.875rem;
        color: var(--primary-color);
        text-decoration: none;
    }
    
    .notification-link:hover {
        text-decoration: underline;
    }
    
    .notification-actions {
        flex-shrink: 0;
    }
    
    .no-data {
        text-align: center;
        padding: 3rem 1rem;
    }
    
    .no-data-icon {
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
        .notification-header {
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .notification-time {
            margin-left: 0;
        }
    }
    </style>

    <script>
        lucide.createIcons();
        
        // Type filter
        document.getElementById('typeFilter').addEventListener('change', function() {
            const selectedType = this.value;
            const items = document.querySelectorAll('.notification-item');
            
            items.forEach(item => {
                const type = item.dataset.type;
                if (!selectedType || type === selectedType) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
        
        function markAsRead(notificationId) {
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=mark_read&notification_id=' + notificationId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const item = document.querySelector(`[data-id="${notificationId}"]`);
                    if (item) {
                        item.classList.remove('unread');
                        item.classList.add('read');
                        
                        // Update icon styling
                        const icon = item.querySelector('.notification-icon');
                        icon.style.background = '#f0f0f0';
                        icon.style.color = '#666';
                        
                        // Remove action button
                        const actions = item.querySelector('.notification-actions');
                        if (actions) {
                            actions.remove();
                        }
                    }
                    
                    // Update page if needed
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
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
