<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireLogin();

$userId = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'send_message':
                $receiver_id = (int)$_POST['receiver_id'];
                $subject = sanitize($_POST['subject']);
                $message = sanitize($_POST['message']);
                
                $query = "INSERT INTO messages (sender_id, receiver_id, subject, message) VALUES (?, ?, ?, ?)";
                $result = executeQuery($query, [$userId, $receiver_id, $subject, $message]);
                
                if ($result) {
                    $_SESSION['success'] = 'Message sent successfully';
                } else {
                    $_SESSION['error'] = 'Failed to send message';
                }
                break;
                
            case 'mark_read':
                $message_id = (int)$_POST['message_id'];
                
                $query = "UPDATE messages SET is_read = 1 WHERE id = ? AND receiver_id = ?";
                $result = executeQuery($query, [$message_id, $userId]);
                break;
        }
        redirect($_SERVER['PHP_SELF']);
    }
}

// Get received messages
$query = "SELECT m.*, u.name as sender_name, u.role as sender_role 
          FROM messages m 
          JOIN users u ON m.sender_id = u.id 
          WHERE m.receiver_id = ? 
          ORDER BY m.created_at DESC";
$receivedMessages = executeQuery($query, [$userId]);

// Get sent messages
$query = "SELECT m.*, u.name as receiver_name, u.role as receiver_role 
          FROM messages m 
          JOIN users u ON m.receiver_id = u.id 
          WHERE m.sender_id = ? 
          ORDER BY m.created_at DESC";
$sentMessages = executeQuery($query, [$userId]);

// Get users for compose dropdown (based on role)
$usersQuery = "";
switch ($_SESSION['role']) {
    case 'admin':
        $usersQuery = "SELECT id, name, role FROM users WHERE id != ? ORDER BY role, name";
        break;
    case 'guard':
        $usersQuery = "SELECT id, name, role FROM users WHERE role IN ('admin', 'organization') AND id != ? ORDER BY role, name";
        break;
    case 'organization':
        $usersQuery = "SELECT id, name, role FROM users WHERE role IN ('admin', 'guard') AND id != ? ORDER BY role, name";
        break;
}
$users = executeQuery($usersQuery, [$userId]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | <?php echo SITE_NAME; ?></title>
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
                    <h1>Messages</h1>
                    <button class="btn btn-primary" onclick="openComposeModal()">
                        <i data-lucide="plus"></i> Compose Message
                    </button>
                </div>
                
                <?php echo flashMessage('success'); ?>
                <?php echo flashMessage('error'); ?>
                
                <!-- Message Tabs -->
                <div class="message-tabs">
                    <button class="tab-btn active" onclick="showTab('inbox')">
                        <i data-lucide="inbox"></i> Inbox 
                        <span class="badge"><?php echo count(array_filter($receivedMessages, function($m) { return !$m['is_read']; })); ?></span>
                    </button>
                    <button class="tab-btn" onclick="showTab('sent')">
                        <i data-lucide="send"></i> Sent
                    </button>
                </div>
                
                <!-- Inbox Tab -->
                <div id="inbox-tab" class="tab-content active">
                    <div class="card">
                        <div class="card-header">
                            <h2>Inbox</h2>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($receivedMessages)): ?>
                                <div class="messages-list">
                                    <?php foreach ($receivedMessages as $message): ?>
                                    <div class="message-item <?php echo $message['is_read'] ? '' : 'unread'; ?>" onclick="viewMessage(<?php echo $message['id']; ?>, 'received')">
                                        <div class="message-avatar">
                                            <?php echo getInitials($message['sender_name']); ?>
                                        </div>
                                        <div class="message-content">
                                            <div class="message-header">
                                                <div class="message-sender">
                                                    <?php echo sanitize($message['sender_name']); ?>
                                                    <span class="sender-role">(<?php echo ucfirst($message['sender_role']); ?>)</span>
                                                </div>
                                                <div class="message-time"><?php echo getTimeAgo($message['created_at']); ?></div>
                                            </div>
                                            <div class="message-subject"><?php echo sanitize($message['subject']); ?></div>
                                            <div class="message-preview"><?php echo substr(sanitize($message['message']), 0, 100) . '...'; ?></div>
                                        </div>
                                        <?php if (!$message['is_read']): ?>
                                        <div class="unread-indicator"></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="no-data">
                                    <div class="no-data-icon">
                                        <i data-lucide="inbox"></i>
                                    </div>
                                    <p>No messages in your inbox.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Sent Tab -->
                <div id="sent-tab" class="tab-content">
                    <div class="card">
                        <div class="card-header">
                            <h2>Sent Messages</h2>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($sentMessages)): ?>
                                <div class="messages-list">
                                    <?php foreach ($sentMessages as $message): ?>
                                    <div class="message-item" onclick="viewMessage(<?php echo $message['id']; ?>, 'sent')">
                                        <div class="message-avatar">
                                            <?php echo getInitials($message['receiver_name']); ?>
                                        </div>
                                        <div class="message-content">
                                            <div class="message-header">
                                                <div class="message-sender">
                                                    To: <?php echo sanitize($message['receiver_name']); ?>
                                                    <span class="sender-role">(<?php echo ucfirst($message['receiver_role']); ?>)</span>
                                                </div>
                                                <div class="message-time"><?php echo getTimeAgo($message['created_at']); ?></div>
                                            </div>
                                            <div class="message-subject"><?php echo sanitize($message['subject']); ?></div>
                                            <div class="message-preview"><?php echo substr(sanitize($message['message']), 0, 100) . '...'; ?></div>
                                        </div>
                                        <div class="message-status">
                                            <?php if ($message['is_read']): ?>
                                                <i data-lucide="check-circle" class="read-icon"></i>
                                            <?php else: ?>
                                                <i data-lucide="circle" class="unread-icon"></i>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="no-data">
                                    <div class="no-data-icon">
                                        <i data-lucide="send"></i>
                                    </div>
                                    <p>No sent messages.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Compose Message Modal -->
    <div id="composeModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Compose Message</h3>
                <button onclick="closeComposeModal()" class="btn btn-sm btn-outline">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="send_message">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="receiver_id">To</label>
                        <select id="receiver_id" name="receiver_id" required>
                            <option value="">Select recipient</option>
                            <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>">
                                <?php echo sanitize($user['name']) . ' (' . ucfirst($user['role']) . ')'; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" rows="6" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeComposeModal()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="send"></i> Send Message
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Message Modal -->
    <div id="viewModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="viewSubject"></h3>
                <button onclick="closeViewModal()" class="btn btn-sm btn-outline">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="message-details">
                    <div class="message-meta">
                        <div><strong>From:</strong> <span id="viewSender"></span></div>
                        <div><strong>Date:</strong> <span id="viewDate"></span></div>
                    </div>
                    <div class="message-body" id="viewMessage"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeViewModal()" class="btn btn-outline">Close</button>
                <button type="button" onclick="replyToMessage()" class="btn btn-primary" id="replyBtn">
                    <i data-lucide="reply"></i> Reply
                </button>
            </div>
        </div>
    </div>

    <style>
    .message-tabs {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }
    
    .tab-btn {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px 8px 0 0;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .tab-btn.active {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }
    
    .tab-btn .badge {
        background: var(--error-color);
        color: white;
        font-size: 0.75rem;
        padding: 0.125rem 0.375rem;
        border-radius: 10px;
        min-width: 18px;
        text-align: center;
    }
    
    .tab-btn.active .badge {
        background: rgba(255,255,255,0.3);
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }
    
    .messages-list {
        display: flex;
        flex-direction: column;
    }
    
    .message-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        border-bottom: 1px solid #f0f0f0;
        cursor: pointer;
        transition: background-color 0.2s ease;
        position: relative;
    }
    
    .message-item:hover {
        background-color: #f8f9fa;
    }
    
    .message-item.unread {
        background-color: rgba(26, 35, 126, 0.05);
        font-weight: 500;
    }
    
    .message-avatar {
        width: 40px;
        height: 40px;
        background: var(--primary-color);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        flex-shrink: 0;
    }
    
    .message-content {
        flex: 1;
        min-width: 0;
    }
    
    .message-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.25rem;
    }
    
    .message-sender {
        font-weight: 600;
        color: #333;
    }
    
    .sender-role {
        font-weight: 400;
        color: #666;
        font-size: 0.875rem;
    }
    
    .message-time {
        font-size: 0.875rem;
        color: #666;
    }
    
    .message-subject {
        font-weight: 500;
        margin-bottom: 0.25rem;
        color: #333;
    }
    
    .message-preview {
        font-size: 0.875rem;
        color: #666;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .unread-indicator {
        width: 8px;
        height: 8px;
        background: var(--primary-color);
        border-radius: 50%;
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
    }
    
    .message-status {
        flex-shrink: 0;
    }
    
    .read-icon {
        color: var(--success-color);
    }
    
    .unread-icon {
        color: #ccc;
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
    
    .message-details {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .message-meta {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
        font-size: 0.875rem;
    }
    
    .message-body {
        line-height: 1.6;
        white-space: pre-wrap;
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
    </style>

    <script>
        lucide.createIcons();
        
        let currentMessage = null;
        
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }
        
        function openComposeModal() {
            document.getElementById('composeModal').style.display = 'flex';
        }
        
        function closeComposeModal() {
            document.getElementById('composeModal').style.display = 'none';
        }
        
        function viewMessage(messageId, type) {
            // Find message data
            const messageData = <?php echo json_encode(array_merge($receivedMessages, $sentMessages)); ?>;
            currentMessage = messageData.find(m => m.id == messageId);
            
            if (currentMessage) {
                document.getElementById('viewSubject').textContent = currentMessage.subject;
                document.getElementById('viewSender').textContent = type === 'received' ? 
                    currentMessage.sender_name + ' (' + currentMessage.sender_role + ')' :
                    'To: ' + currentMessage.receiver_name + ' (' + currentMessage.receiver_role + ')';
                document.getElementById('viewDate').textContent = new Date(currentMessage.created_at).toLocaleString();
                document.getElementById('viewMessage').textContent = currentMessage.message;
                
                // Show/hide reply button
                const replyBtn = document.getElementById('replyBtn');
                if (type === 'received') {
                    replyBtn.style.display = 'inline-flex';
                } else {
                    replyBtn.style.display = 'none';
                }
                
                document.getElementById('viewModal').style.display = 'flex';
                
                // Mark as read if it's a received message
                if (type === 'received' && !currentMessage.is_read) {
                    markAsRead(messageId);
                }
            }
        }
        
        function closeViewModal() {
            document.getElementById('viewModal').style.display = 'none';
        }
        
        function replyToMessage() {
            if (currentMessage) {
                closeViewModal();
                
                // Pre-fill compose form
                document.getElementById('receiver_id').value = currentMessage.sender_id;
                document.getElementById('subject').value = 'Re: ' + currentMessage.subject;
                
                openComposeModal();
            }
        }
        
        function markAsRead(messageId) {
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=mark_read&message_id=' + messageId
            });
        }
        
        function getInitials(name) {
            return name.split(' ').map(word => word.charAt(0)).join('').substring(0, 2).toUpperCase();
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
