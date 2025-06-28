<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('guard');

$duty_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$duty_id) {
    $_SESSION['error'] = 'Invalid duty assignment ID';
    redirect('schedule.php');
}

// Get guard information
$userId = $_SESSION['user_id'];
$query = "SELECT g.* FROM guards g JOIN users u ON g.user_id = u.id WHERE g.user_id = ?";
$guard = executeQuery($query, [$userId], ['single' => true]);

if (!$guard) {
    $_SESSION['error'] = 'Guard information not found';
    redirect(SITE_URL);
}

// Get duty assignment details
$query = "SELECT da.*, l.name as location_name, l.address as location_address, 
                 l.latitude, l.longitude, l.contact_person, l.contact_phone,
                 o.name as organization_name, s.name as shift_name, s.start_time, s.end_time,
                 u.name as created_by_name
          FROM duty_assignments da 
          JOIN locations l ON da.location_id = l.id 
          JOIN organizations o ON l.organization_id = o.id 
          JOIN shifts s ON da.shift_id = s.id 
          JOIN users u ON da.created_by = u.id 
          WHERE da.id = ? AND da.guard_id = ?";
$duty = executeQuery($query, [$duty_id, $guard['id']], ['single' => true]);

if (!$duty) {
    $_SESSION['error'] = 'Duty assignment not found or you do not have access to it';
    redirect('schedule.php');
}

// Get attendance records for this duty
$query = "SELECT * FROM attendance WHERE duty_assignment_id = ? ORDER BY check_in_time DESC";
$attendanceRecords = executeQuery($query, [$duty_id]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duty Details | <?php echo SITE_NAME; ?></title>
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
                    <h1>Duty Assignment Details</h1>
                    <div class="dashboard-actions">
                        <a href="schedule.php" class="btn btn-outline">
                            <i data-lucide="arrow-left"></i> Back to Schedule
                        </a>
                        <?php if ($duty['status'] === 'active'): ?>
                        <a href="dashboard.php" class="btn btn-primary">
                            <i data-lucide="activity"></i> Go to Dashboard
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Duty Information Card -->
                <div class="card duty-card">
                    <div class="card-header">
                        <h2>Assignment Information</h2>
                        <span class="badge badge-<?php echo getAssignmentStatusClass($duty['status']); ?>">
                            <?php echo ucfirst($duty['status']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="duty-details">
                            <div class="duty-info">
                                <div class="info-group">
                                    <label>Organization:</label>
                                    <span><?php echo sanitize($duty['organization_name']); ?></span>
                                </div>
                                
                                <div class="info-group">
                                    <label>Location:</label>
                                    <span><?php echo sanitize($duty['location_name']); ?></span>
                                </div>
                                
                                <div class="info-group">
                                    <label>Address:</label>
                                    <span><?php echo sanitize($duty['location_address']); ?></span>
                                </div>
                                
                                <div class="info-group">
                                    <label>Shift:</label>
                                    <span><?php echo sanitize($duty['shift_name']); ?> (<?php echo formatTime($duty['start_time']); ?> - <?php echo formatTime($duty['end_time']); ?>)</span>
                                </div>
                                
                                <div class="info-group">
                                    <label>Assignment Period:</label>
                                    <span>
                                        <?php echo formatDate($duty['start_date'], 'd M Y'); ?>
                                        <?php if ($duty['end_date']): ?>
                                            - <?php echo formatDate($duty['end_date'], 'd M Y'); ?>
                                        <?php else: ?>
                                            - Ongoing
                                        <?php endif; ?>
                                    </span>
                                </div>
                                
                                <div class="info-group">
                                    <label>Site Contact:</label>
                                    <span><?php echo sanitize($duty['contact_person']); ?> - <?php echo sanitize($duty['contact_phone']); ?></span>
                                </div>
                                
                                <div class="info-group">
                                    <label>Assigned By:</label>
                                    <span><?php echo sanitize($duty['created_by_name']); ?></span>
                                </div>
                                
                                <div class="info-group">
                                    <label>Assignment Date:</label>
                                    <span><?php echo formatDate($duty['created_at'], 'd M Y, h:i A'); ?></span>
                                </div>
                                
                                <?php if (!empty($duty['notes'])): ?>
                                <div class="info-group">
                                    <label>Special Instructions:</label>
                                    <span><?php echo sanitize($duty['notes']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Location Map -->
                        <?php if ($duty['latitude'] && $duty['longitude']): ?>
                        <div class="location-map">
                            <h3>Location Map</h3>
                            <div id="map"></div>
                            <div class="map-actions">
                                <button class="btn btn-secondary" onclick="openInMaps()">
                                    <i data-lucide="external-link"></i> Open in Maps
                                </button>
                                <button class="btn btn-outline" onclick="getDirections()">
                                    <i data-lucide="navigation"></i> Get Directions
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Attendance History -->
                <div class="card">
                    <div class="card-header">
                        <h2>Attendance History</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($attendanceRecords)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Check In</th>
                                            <th>Check Out</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($attendanceRecords as $record): ?>
                                        <tr>
                                            <td><?php echo formatDate($record['check_in_time'], 'd M Y'); ?></td>
                                            <td><?php echo formatDate($record['check_in_time'], 'h:i A'); ?></td>
                                            <td>
                                                <?php echo $record['check_out_time'] ? formatDate($record['check_out_time'], 'h:i A') : 'Not checked out'; ?>
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
                                            <td><?php echo sanitize($record['notes']); ?></td>
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
                                <p>No attendance records found for this duty assignment.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php if ($duty['latitude'] && $duty['longitude']): ?>
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&callback=initMap" async defer></script>
    <script>
        let map;
        let dutyLocation = {
            lat: <?php echo $duty['latitude']; ?>,
            lng: <?php echo $duty['longitude']; ?>
        };
        
        function initMap() {
            map = new google.maps.Map(document.getElementById("map"), {
                center: dutyLocation,
                zoom: 15,
                mapTypeId: "roadmap",
            });
            
            new google.maps.Marker({
                position: dutyLocation,
                map: map,
                title: "<?php echo addslashes($duty['location_name']); ?>",
            });
        }
        
        function openInMaps() {
            const url = `https://maps.google.com/maps?q=${dutyLocation.lat},${dutyLocation.lng}&z=15`;
            window.open(url, '_blank');
        }
        
        function getDirections() {
            const url = `https://maps.google.com/maps/dir/?api=1&destination=${dutyLocation.lat},${dutyLocation.lng}`;
            window.open(url, '_blank');
        }
    </script>
    <?php endif; ?>

    <style>
    .location-map {
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 1px solid #eee;
    }
    
    .location-map h3 {
        margin-bottom: 1rem;
        color: var(--primary-color);
    }
    
    #map {
        width: 100%;
        height: 300px;
        border-radius: 8px;
        margin-bottom: 1rem;
    }
    
    .map-actions {
        display: flex;
        gap: 1rem;
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
    </script>
</body>
</html>

<?php
function formatTime($time) {
    return date('h:i A', strtotime($time));
}

function getAssignmentStatusClass($status) {
    switch ($status) {
        case 'active': return 'success';
        case 'completed': return 'secondary';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
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