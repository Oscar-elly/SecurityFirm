<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('admin');

// Fetch attendance records with guard and location info
$query = "SELECT a.id, g.id_number, u.name as guard_name, a.date, a.status, l.name as location_name
          FROM attendance a
          JOIN guards g ON a.guard_id = g.id
          JOIN users u ON g.user_id = u.id
          JOIN locations l ON a.location_id = l.id
          ORDER BY a.date DESC, u.name ASC";
$attendanceRecords = executeQuery($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Attendance Records | <?php echo SITE_NAME; ?></title>
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
                    <h1>Attendance Records</h1>
                    <a href="export-attendance.php" class="btn btn-primary">
                        <i data-lucide="download"></i> Export Attendance
                    </a>
                </div>

                <?php if (!empty($attendanceRecords)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Guard ID</th>
                                    <th>Name</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Location</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendanceRecords as $record): ?>
                                    <tr>
                                        <td><?php echo sanitize($record['id_number']); ?></td>
                                        <td><?php echo sanitize($record['guard_name']); ?></td>
                                        <td><?php echo formatDate($record['date'], 'Y-m-d'); ?></td>
                                        <td><?php echo ucfirst(sanitize($record['status'])); ?></td>
                                        <td><?php echo sanitize($record['location_name']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="no-data">No attendance records found.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
