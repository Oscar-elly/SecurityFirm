<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('guard');

// Get guard information
$userId = $_SESSION['user_id'];
$query = "SELECT g.* FROM guards g JOIN users u ON g.user_id = u.id WHERE g.user_id = ?";
$guard = executeQuery($query, [$userId], ['single' => true]);

if (!$guard) {
    $_SESSION['error'] = 'Guard information not found';
    redirect(SITE_URL);
}

// Get attendance records for this guard
$query = "SELECT a.*, da.start_date, da.end_date, l.name as location_name, 
                 o.name as organization_name, s.name as shift_name, s.start_time, s.end_time
          FROM attendance a
          JOIN duty_assignments da ON a.duty_assignment_id = da.id
          JOIN locations l ON da.location_id = l.id
          JOIN organizations o ON l.organization_id = o.id
          JOIN shifts s ON da.shift_id = s.id
          WHERE da.guard_id = ?
          ORDER BY a.check_in_time DESC";
$attendanceRecords = executeQuery($query, [$guard['id']]);

// Calculate attendance statistics
$totalDays = count($attendanceRecords);
$presentDays = count(array_filter($attendanceRecords, function($r) { return $r['status'] === 'present'; }));
$lateDays = count(array_filter($attendanceRecords, function($r) { return $r['status'] === 'late'; }));
$absentDays = count(array_filter($attendanceRecords, function($r) { return $r['status'] === 'absent'; }));

$attendanceRate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 0;
$punctualityRate = $totalDays > 0 ? round((($presentDays) / $totalDays) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance | <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/guard-dashboard.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/guard-sidebar.php'; ?>
        
        <main class="main-content">
            <?php include '../includes/top-nav.php'; ?>
            
            <div class="dashboard-content">
                <div class="dashboard-header">
                    <h1>My Attendance</h1>
                    <div class="dashboard-actions">
                        <input type="month" id="monthFilter" class="form-control" value="<?php echo date('Y-m'); ?>">
                    </div>
                </div>
                
                <!-- Attendance Statistics -->
                <div class="stats-cards">
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="calendar"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $totalDays; ?></h3>
                            <p>Total Days</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="check-circle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $presentDays; ?></h3>
                            <p>Present Days</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="clock"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $lateDays; ?></h3>
                            <p>Late Days</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="trending-up"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $attendanceRate; ?>%</h3>
                            <p>Attendance Rate</p>
                        </div>
                    </div>
                </div>
                
                <!-- Attendance Records -->
                <div class="card">
                    <div class="card-header">
                        <h2>Attendance History</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($attendanceRecords)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="attendanceTable">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Location</th>
                                            <th>Organization</th>
                                            <th>Shift</th>
                                            <th>Check In</th>
                                            <th>Check Out</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($attendanceRecords as $record): ?>
                                        <tr data-month="<?php echo date('Y-m', strtotime($record['check_in_time'])); ?>">
                                            <td><?php echo formatDate($record['check_in_time'], 'd M Y'); ?></td>
                                            <td><?php echo sanitize($record['location_name']); ?></td>
                                            <td><?php echo sanitize($record['organization_name']); ?></td>
                                            <td>
                                                <?php echo sanitize($record['shift_name']); ?>
                                                <br><small><?php echo formatTime($record['start_time']) . ' - ' . formatTime($record['end_time']); ?></small>
                                            </td>
                                            <td>
                                                <?php echo formatDate($record['check_in_time'], 'h:i A'); ?>
                                                <?php if ($record['check_in_latitude'] && $record['check_in_longitude']): ?>
                                                <br><button class="btn btn-xs btn-outline" onclick="viewLocation(<?php echo $record['check_in_latitude']; ?>, <?php echo $record['check_in_longitude']; ?>)">
                                                    <i data-lucide="map-pin"></i>
                                                </button>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($record['check_out_time']): ?>
                                                    <?php echo formatDate($record['check_out_time'], 'h:i A'); ?>
                                                    <?php if ($record['check_out_latitude'] && $record['check_out_longitude']): ?>
                                                    <br><button class="btn btn-xs btn-outline" onclick="viewLocation(<?php echo $record['check_out_latitude']; ?>, <?php echo $record['check_out_longitude']; ?>)">
                                                        <i data-lucide="map-pin"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not checked out</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                if ($record['check_out_time']) {
                                                    echo calculateDuration($record['check_in_time'], $record['check_out_time']);
                                                } else {
                                                    echo '<span class="text-muted">In progress</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo getAttendanceStatusClass($record['status']); ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $record['status'])); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <div class="no-duty-icon">
                                    <i data-lucide="calendar-x"></i>
                                </div>
                                <p>No attendance records found.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Performance Summary -->
                <div class="card">
                    <div class="card-header">
                        <h2>Performance Summary</h2>
                    </div>
                    <div class="card-body">
                        <div class="performance-grid">
                            <div class="performance-item">
                                <div class="performance-label">Attendance Rate</div>
                                <div class="performance-bar">
                                    <div class="performance-fill" style="width: <?php echo $attendanceRate; ?>%"></div>
                                </div>
                                <div class="performance-value"><?php echo $attendanceRate; ?>%</div>
                            </div>
                            
                            <div class="performance-item">
                                <div class="performance-label">Punctuality Rate</div>
                                <div class="performance-bar">
                                    <div class="performance-fill" style="width: <?php echo $punctualityRate; ?>%"></div>
                                </div>
                                <div class="performance-value"><?php echo $punctualityRate; ?>%</div>
                            </div>
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
    
    .btn-xs {
        padding: 0.125rem 0.25rem;
        font-size: 0.75rem;
    }
    
    .performance-grid {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .performance-item {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .performance-label {
        min-width: 150px;
        font-weight: 500;
    }
    
    .performance-bar {
        flex: 1;
        height: 20px;
        background: #e0e0e0;
        border-radius: 10px;
        overflow: hidden;
    }
    
    .performance-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--success-color), var(--primary-color));
        transition: width 0.3s ease;
    }
    
    .performance-value {
        min-width: 60px;
        text-align: right;
        font-weight: 600;
        color: var(--primary-color);
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
    </style>

    <script>
        lucide.createIcons();
        
        // Month filter
        document.getElementById('monthFilter').addEventListener('change', function() {
            const selectedMonth = this.value;
            const rows = document.querySelectorAll('#attendanceTable tbody tr');
            
            rows.forEach(row => {
                const rowMonth = row.dataset.month;
                if (!selectedMonth || rowMonth === selectedMonth) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        function viewLocation(lat, lng) {
            window.open(`https://maps.google.com/maps?q=${lat},${lng}&z=15`, '_blank');
        }
    </script>
</body>
</html>

<?php
function formatTime($time) {
    return date('h:i A', strtotime($time));
}

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