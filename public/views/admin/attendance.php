<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('admin');

// Get attendance records with related information
$query = "SELECT a.*, da.start_date, da.end_date, g.id_number, u.name as guard_name, 
                 l.name as location_name, o.name as organization_name, s.name as shift_name
          FROM attendance a
          JOIN duty_assignments da ON a.duty_assignment_id = da.id
          JOIN guards g ON da.guard_id = g.id
          JOIN users u ON g.user_id = u.id
          JOIN locations l ON da.location_id = l.id
          JOIN organizations o ON l.organization_id = o.id
          JOIN shifts s ON da.shift_id = s.id
          ORDER BY a.check_in_time DESC";
$attendanceRecords = executeQuery($query);

// Get attendance statistics
$today = date('Y-m-d');
$statsQuery = "SELECT 
                COUNT(*) as total_today,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_today,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_today,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_today
               FROM attendance 
               WHERE DATE(check_in_time) = ?";
$stats = executeQuery($statsQuery, [$today], ['single' => true]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management | <?php echo SITE_NAME; ?></title>
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
                    <h1>Attendance Management</h1>
                    <div class="dashboard-actions">
                        <input type="date" id="dateFilter" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        <button class="btn btn-primary" onclick="exportAttendance()">
                            <i data-lucide="download"></i> Export
                        </button>
                    </div>
                </div>
                
                <!-- Today's Statistics -->
                <div class="stats-cards">
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="users"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $stats['total_today'] ?? 0; ?></h3>
                            <p>Total Check-ins Today</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="check-circle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $stats['present_today'] ?? 0; ?></h3>
                            <p>On Time</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="clock"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $stats['late_today'] ?? 0; ?></h3>
                            <p>Late Arrivals</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="x-circle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $stats['absent_today'] ?? 0; ?></h3>
                            <p>Absent</p>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Attendance Records</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="attendanceTable">
                                <thead>
                                    <tr>
                                        <th>Guard</th>
                                        <th>ID Number</th>
                                        <th>Location</th>
                                        <th>Organization</th>
                                        <th>Shift</th>
                                        <th>Check In</th>
                                        <th>Check Out</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendanceRecords as $record): ?>
                                    <tr data-date="<?php echo date('Y-m-d', strtotime($record['check_in_time'])); ?>">
                                        <td><?php echo sanitize($record['guard_name']); ?></td>
                                        <td><?php echo sanitize($record['id_number']); ?></td>
                                        <td><?php echo sanitize($record['location_name']); ?></td>
                                        <td><?php echo sanitize($record['organization_name']); ?></td>
                                        <td><?php echo sanitize($record['shift_name']); ?></td>
                                        <td><?php echo formatDate($record['check_in_time']); ?></td>
                                        <td>
                                            <?php echo $record['check_out_time'] ? formatDate($record['check_out_time']) : 'Not checked out'; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($record['check_out_time']) {
                                                echo calculateDuration($record['check_in_time'], $record['check_out_time']);
                                            } else {
                                                echo 'In progress';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo getAttendanceStatusClass($record['status']); ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $record['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline" onclick="viewAttendanceDetails(<?php echo $record['id']; ?>)">
                                                <i data-lucide="eye"></i>
                                            </button>
                                            <?php if ($record['check_in_latitude'] && $record['check_in_longitude']): ?>
                                            <button class="btn btn-sm btn-secondary" onclick="viewLocation(<?php echo $record['check_in_latitude']; ?>, <?php echo $record['check_in_longitude']; ?>)">
                                                <i data-lucide="map-pin"></i>
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

    <style>
    .dashboard-actions {
        display: flex;
        gap: 1rem;
        align-items: center;
    }
    </style>

    <script>
        lucide.createIcons();
        
        // Date filter functionality
        document.getElementById('dateFilter').addEventListener('change', function() {
            const selectedDate = this.value;
            const rows = document.querySelectorAll('#attendanceTable tbody tr');
            
            rows.forEach(row => {
                const rowDate = row.dataset.date;
                if (!selectedDate || rowDate === selectedDate) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        function viewAttendanceDetails(id) {
            window.location.href = 'view-attendance.php?id=' + id;
        }
        
        function viewLocation(lat, lng) {
            window.open(`https://maps.google.com/maps?q=${lat},${lng}&z=15`, '_blank');
        }
        
        function exportAttendance() {
            const date = document.getElementById('dateFilter').value;
            window.location.href = 'export-attendance.php?date=' + date;
        }
    </script>
</body>
</html>

<?php
function getAttendanceStatusClass($status) {
    switch ($status) {
        case 'present': return 'success';
        case 'late': return 'warning';
        case 'absent': return 'danger';
        case 'early_departure': return 'warning';
        default: return 'secondary';
    }
}

function calculateDuration($start, $end) {
    $startTime = strtotime($start);
    $endTime = strtotime($end);
    $duration = $endTime - $startTime;
    
    $hours = floor($duration / 3600);
    $minutes = floor(($duration % 3600) / 60);
    
    return $hours . 'h ' . $minutes . 'm';
}
?>