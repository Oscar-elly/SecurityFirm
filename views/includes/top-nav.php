<?php
// Helper functions for top navigation

/**
 * Get the title of the current page
 * 
 * @return string Page title
 */
function getPageTitle() {
    $page = basename($_SERVER['PHP_SELF'], '.php');
    $page = str_replace('-', ' ', $page);
    return ucwords($page);
}

/**
 * Get unread notifications count for current user
 * 
 * @return int Count of unread notifications
 */
function getUnreadNotificationsCount() {
    global $conn;
    
    $userId = $_SESSION['user_id'];
    $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $row = $result->fetch_assoc()) {
            return $row['count'];
        }
    }
    
    return 0;
}

/**
 * Get recent notifications for current user
 * 
 * @param int $limit Number of notifications to get
 * @return array Notifications
 */
function getRecentNotifications($limit = 5) {
    global $conn;
    
    $userId = $_SESSION['user_id'];
    $query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        
        return $notifications;
    }
    
    return [];
}

/**
 * Get icon for notification type
 * 
 * @param string $type Notification type
 * @return string Icon name
 */
function getNotificationIcon($type) {
    switch ($type) {
        case 'incident':
            return 'alert-triangle';
        case 'assignment':
            return 'calendar';
        case 'message':
            return 'message-square';
        case 'system':
            return 'info';
        case 'attendance':
            return 'clock';
        case 'request':
            return 'file-text';
        default:
            return 'bell';
    }
}

/**
 * Get unread messages count for current user
 * 
 * @return int Count of unread messages
 */
function getUnreadMessagesCount() {
    global $conn;
    
    $userId = $_SESSION['user_id'];
    $query = "SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $row = $result->fetch_assoc()) {
            return $row['count'];
        }
    }
    
    return 0;
}

/**
 * Get recent messages for current user
 * 
 * @param int $limit Number of messages to get
 * @return array Messages with sender information
 */
function getRecentMessages($limit = 5) {
    global $conn;
    
    $userId = $_SESSION['user_id'];
    $query = "SELECT m.*, u.name as sender_name 
              FROM messages m 
              JOIN users u ON m.sender_id = u.id 
              WHERE m.receiver_id = ? 
              ORDER BY m.created_at DESC 
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        
        return $messages;
    }
    
    return [];
}

/**
 * Get initials from name
 * 
 * @param string $name Full name
 * @return string Initials
 */
function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    
    foreach ($words as $word) {
        $initials .= strtoupper(substr($word, 0, 1));
    }
    
    return substr($initials, 0, 2);
}

/**
 * Get time ago from datetime
 * 
 * @param string $datetime Datetime string
 * @return string Time ago
 */
function getTimeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $time);
    }
}
?>

<header class="top-nav">
    <div class="left-section">
        <button id="mobile-sidebar-toggle" class="mobile-sidebar-toggle">
            <i data-lucide="menu"></i>
        </button>
        <div class="page-title">
            <h2><?php echo getPageTitle(); ?></h2>
        </div>
    </div>
    
    <div class="right-section">
        <div class="search-container">
            <form action="search.php" method="GET">
                <div class="search-input">
                    <i data-lucide="search"></i>
                    <input type="text" name="q" placeholder="Search...">
                </div>
            </form>
        </div>
        
        <div class="notifications-dropdown">
            <button class="notifications-btn">
                <i data-lucide="bell"></i>
                <?php
                // Get unread notifications count
                $notificationsCount = getUnreadNotificationsCount();
                if ($notificationsCount > 0):
                ?>
                <span class="badge"><?php echo $notificationsCount; ?></span>
                <?php endif; ?>
            </button>
            
            <div class="dropdown-menu">
                <div class="dropdown-header">
                    <h3>Notifications</h3>
                    <a href="../shared/notifications.php">View All</a>
                </div>
                
                <div class="dropdown-body">
                    <?php
                    // Get recent notifications
                    $notifications = getRecentNotifications(5);
                    if (!empty($notifications)):
                        foreach ($notifications as $notification):
                    ?>
                    <a href="<?php echo $notification['link'] ? '/SecurityFirm/' . ltrim($notification['link'], '/') : '#'; ?>" class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                        <div class="notification-icon">
                            <i data-lucide="<?php echo getNotificationIcon($notification['type']); ?>"></i>
                        </div>
                        <div class="notification-details">
                            <div class="notification-title"><?php echo sanitize($notification['title']); ?></div>
                            <div class="notification-time"><?php echo getTimeAgo($notification['created_at']); ?></div>
                        </div>
                    </a>
                    <?php
                        endforeach;
                    else:
                    ?>
                    <div class="no-data">No notifications to display.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="messages-dropdown">
            <button class="messages-btn">
                <i data-lucide="message-square"></i>
                <?php
                // Get unread messages count
                $messagesCount = getUnreadMessagesCount();
                if ($messagesCount > 0):
                ?>
                <span class="badge"><?php echo $messagesCount; ?></span>
                <?php endif; ?>
            </button>
            
            <div class="dropdown-menu">
                <div class="dropdown-header">
                    <h3>Messages</h3>
                    <a href="../shared/messages.php">View All</a>
                </div>
                
                <div class="dropdown-body">
                    <?php
                    // Get recent messages
                    $messages = getRecentMessages(5);
                    if (!empty($messages)):
                        foreach ($messages as $message):
                    ?>
                    <a href="../shared/messages.php?id=<?php echo $message['id']; ?>" class="message-item <?php echo $message['is_read'] ? '' : 'unread'; ?>">
                        <div class="message-avatar">
                            <?php echo getInitials($message['sender_name']); ?>
                        </div>
                        <div class="message-details">
                            <div class="message-sender"><?php echo sanitize($message['sender_name']); ?></div>
                            <div class="message-subject"><?php echo sanitize($message['subject']); ?></div>
                            <div class="message-time"><?php echo getTimeAgo($message['created_at']); ?></div>
                        </div>
                    </a>
                    <?php
                        endforeach;
                    else:
                    ?>
                    <div class="no-data">No messages to display.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="user-dropdown">
            <button class="user-btn">
                <div class="user-avatar">
                    <?php echo getInitials($_SESSION['name']); ?>
                </div>
                <span class="user-name"><?php echo $_SESSION['name']; ?></span>
                <i data-lucide="chevron-down"></i>
            </button>
            
            <div class="dropdown-menu">
                <a href="settings.php#profile-tab" class="dropdown-item">
                    <i data-lucide="user"></i>
                    <span>Profile</span>
                </a>
                
                <a href="settings.php" class="dropdown-item">
                    <i data-lucide="settings"></i>
                    <span>Settings</span>
                </a>
                
                <div class="dropdown-divider"></div>
                
                <a href="../../logout.php" class="dropdown-item">
                    <i data-lucide="log-out"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
</header>