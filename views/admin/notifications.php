<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('admin');

$userId = $_SESSION['user_id'];

// Mark notification as read if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $notificationId = (int)$_POST['notification_id'];
    $updateQuery = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
    executeQuery($updateQuery, [$notificationId, $userId]);
    header("Location: notifications.php");
    exit;
}

// Fetch notifications for user
$query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$notifications = executeQuery($query, [$userId]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Notifications - <?php echo SITE_NAME; ?></title>
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
                    <h1>Notifications</h1>
                </div>

                <?php if (empty($notifications)): ?>
                    <p>No notifications to display.</p>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Message</th>
                                        <th>Type</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($notifications as $notification): ?>
                                    <tr class="<?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                                        <td><?php echo sanitize($notification['title']); ?></td>
                                        <td><?php echo sanitize($notification['message']); ?></td>
                                        <td><?php echo ucfirst(sanitize($notification['type'])); ?></td>
                                        <td><?php echo formatDate($notification['created_at']); ?></td>
                                        <td><?php echo $notification['is_read'] ? 'Read' : 'Unread'; ?></td>
                                        <td>
                                            <?php if (!$notification['is_read']): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                <button type="submit" name="mark_read" class="btn btn-sm btn-primary">Mark as Read</button>
                                            </form>
                                            <?php else: ?>
                                            <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
