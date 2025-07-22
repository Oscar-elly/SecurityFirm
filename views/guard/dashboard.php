<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Verify authentication
if (!isset($_SESSION['user_id'])) {
    die('Session user_id not set');
}
if ($_SESSION['role'] !== 'guard') {
    die('Session role is not guard, found: ' . $_SESSION['role']);
}

// Optional: if you want to show some users (used for testing or listing)
$users = executeQuery("SELECT * FROM users LIMIT 10");

// Ensure the user has the correct role
requireRole('guard');

// Get guard information
$userId = $_SESSION['user_id'];

$query = "SELECT 
            g.*, 
            u.name AS user_name, 
            u.email AS user_email, 
            u.phone AS user_phone, 
            u.profile_image AS user_profile 
          FROM guards g 
          JOIN users u ON g.user_id = u.id 
          WHERE g.user_id = ?";

$guard = executeQuery($query, [$userId], ['single' => true]);

if (!$guard) {
    $_SESSION['error'] = 'Guard information not found';
    redirect(SITE_URL);
}

// Get current duty assignment
$guardId = $guard['id'];
$query = "SELECT da.*, l.name as location_name, l.address as location_address, 
                 l.latitude, l.longitude, s.name as shift_name, s.start_time, s.end_time, 
                 o.name as organization_name, o.user_id as organization_admin_id
          FROM duty_assignments da 
          JOIN locations l ON da.location_id = l.id 
          JOIN shifts s ON da.shift_id = s.id 
          JOIN organizations o ON l.organization_id = o.id 
          JOIN guards g ON da.guard_id = g.id 
          WHERE g.user_id = ? AND da.status = 'active' 
          AND CURDATE() BETWEEN da.start_date AND IFNULL(da.end_date, CURDATE()) 
          ORDER BY da.start_date DESC 
          LIMIT 1";

$currentDuty = executeQuery($query, [$userId]);

// Get today's attendance
$todayAttendance = null;
if (!empty($currentDuty)) {
    $query = "SELECT * FROM attendance 
              WHERE duty_assignment_id = ? 
              AND DATE(check_in_time) = CURDATE() 
              ORDER BY id DESC LIMIT 1";
    $attendanceResult = executeQuery($query, [$currentDuty[0]['id']]);

    if (!empty($attendanceResult)) {
        $todayAttendance = $attendanceResult[0];
    }
}

// Get upcoming duty assignments
$query = "SELECT da.*, l.name as location_name, s.name as shift_name, 
                 o.name as organization_name 
          FROM duty_assignments da 
          JOIN locations l ON da.location_id = l.id 
          JOIN shifts s ON da.shift_id = s.id 
          JOIN organizations o ON l.organization_id = o.id 
          JOIN guards g ON da.guard_id = g.id 
          WHERE g.user_id = ? AND da.status = 'active' 
          AND da.start_date > CURDATE() 
          ORDER BY da.start_date ASC 
          LIMIT 5";
$upcomingDuties = executeQuery($query, [$userId]);

// Get recent incidents reported by the guard
$query = "SELECT i.*, l.name as location_name 
          FROM incidents i 
          JOIN locations l ON i.location_id = l.id 
          WHERE i.reported_by = ? 
          ORDER BY i.created_at DESC 
          LIMIT 5";
$recentIncidents = executeQuery($query, [$userId]);

// Get recent performance evaluations
$query = "SELECT pe.*, u.name as evaluator_name 
          FROM performance_evaluations pe 
          JOIN users u ON pe.evaluator_id = u.id 
          JOIN guards g ON pe.guard_id = g.id 
          WHERE g.user_id = ? 
          ORDER BY pe.evaluation_date DESC 
          LIMIT 3";
$recentEvaluations = executeQuery($query, [$userId]);

// Handle check-in/check-out form submission
// Handle check-in/check-out form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && !empty($currentDuty)) {
        $dutyId = $currentDuty[0]['id'];
        $latitude = filter_input(INPUT_POST, 'latitude', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $longitude = filter_input(INPUT_POST, 'longitude', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);
        
        // Get current time and shift times
        $now = date('Y-m-d H:i:s');
        $currentTime = strtotime($now);
        $shiftStartTime = strtotime(date('Y-m-d') . ' ' . $currentDuty[0]['start_time']);
        $shiftEndTime = strtotime(date('Y-m-d') . ' ' . $currentDuty[0]['end_time']);
        
        // Handle overnight shifts (where end time is next day)
        if ($shiftEndTime < $shiftStartTime) {
            $shiftEndTime = strtotime(date('Y-m-d', strtotime('+1 day')) . ' ' . $currentDuty[0]['end_time']);
        }
        
        // Calculate buffer time (15 minutes before/after shift)
        $bufferTime = 15 * 60; // 15 minutes in seconds
        $earliestCheckIn = $shiftStartTime - $bufferTime;
        $latestCheckIn = $shiftStartTime + (60 * 60); // 1 hour after shift start
        $earliestCheckOut = $shiftEndTime - (60 * 60); // 1 hour before shift end
        $latestCheckOut = $shiftEndTime + $bufferTime;

        if ($_POST['action'] === 'check_in' && empty($todayAttendance)) {
            // Validate check-in time window
            if ($currentTime < $earliestCheckIn) {
                $_SESSION['error'] = 'You cannot check in more than 15 minutes before your shift starts.';
                redirect($_SERVER['PHP_SELF']);
            }
            
            if ($currentTime > $latestCheckIn) {
                $_SESSION['error'] = 'You cannot check in more than 1 hour after your shift has started.';
                redirect($_SERVER['PHP_SELF']);
            }

            // Determine status (on-time or late)
            $status = 'present';
            if ($currentTime > $shiftStartTime + (30 * 60)) {
                $status = 'late';
            }

            // Record check-in
            $query = "INSERT INTO attendance (duty_assignment_id, check_in_time, check_in_latitude, check_in_longitude, status, notes) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            $result = executeQuery($query, [$dutyId, $now, $latitude, $longitude, $status, $notes]);

            if ($result) {
                logActivity($userId, "Checked in for duty at " . $currentDuty[0]['location_name'], 'attendance');
                
                // Notification for organization admin
                $orgAdminLink = "/organization/attendance/view?guard_id=" . $guardId . "&duty_id=" . $dutyId;
                $notificationTitle = "Guard Check-in";
                $notificationMessage = "Guard {$guard['user_name']} (ID: {$guard['id_number']}) has checked in at {$currentDuty[0]['location_name']} for {$currentDuty[0]['shift_name']} shift";
                
                $query = "INSERT INTO notifications (user_id, title, message, type, link) 
                          VALUES (?, ?, ?, 'attendance', ?)";
                executeQuery($query, [
                    $currentDuty[0]['organization_admin_id'], 
                    $notificationTitle, 
                    $notificationMessage,
                    $orgAdminLink
                ]);
                
                // Notification for system admins
                $adminLink = "/admin/attendance/view?guard_id=" . $guardId . "&duty_id=" . $dutyId;
                $adminQuery = "SELECT id FROM users WHERE role = 'admin' AND status = 'active'";
                $admins = executeQuery($adminQuery);
                
                foreach ($admins as $admin) {
                    $query = "INSERT INTO notifications (user_id, title, message, type, link) 
                              VALUES (?, ?, ?, 'attendance', ?)";
                    executeQuery($query, [
                        $admin['id'], 
                        $notificationTitle, 
                        $notificationMessage,
                        $adminLink
                    ]);
                }
                
                $_SESSION['success'] = 'Check-in successful';
                redirect($_SERVER['PHP_SELF']);
            } else {
                $_SESSION['error'] = 'Failed to check-in. Please try again.';
            }
            
        } elseif ($_POST['action'] === 'check_out' && !empty($todayAttendance) && empty($todayAttendance['check_out_time'])) {
            // Validate check-out time window
            if ($currentTime < $earliestCheckOut) {
                $_SESSION['error'] = 'You cannot check out more than 1 hour before your shift ends.';
                redirect($_SERVER['PHP_SELF']);
            }
            
            if ($currentTime > $latestCheckOut) {
                $_SESSION['error'] = 'You cannot check out more than 15 minutes after your shift has ended.';
                redirect($_SERVER['PHP_SELF']);
            }

            // Determine status (on-time or early departure)
            $status = $todayAttendance['status'];
            if ($currentTime < $shiftEndTime - (30 * 60)) {
                $status = 'early_departure';
            }
            
            // Record check-out
            $query = "UPDATE attendance SET check_out_time = ?, check_out_latitude = ?, 
                      check_out_longitude = ?, status = ?, notes = CONCAT(IFNULL(notes, ''), '\n', ?) 
                      WHERE id = ?";
            $result = executeQuery($query, [$now, $latitude, $longitude, $status, $notes, $todayAttendance['id']]);

            if ($result) {
                logActivity($userId, "Checked out from duty at " . $currentDuty[0]['location_name'], 'attendance');
                
                // Notification for organization admin
                $orgAdminLink = "/organization/attendance/view?guard_id=" . $guardId . "&duty_id=" . $dutyId;
                $notificationTitle = "Guard Check-out";
                $duration = calculateDuration($todayAttendance['check_in_time'], $now);
                $notificationMessage = "Guard {$guard['user_name']} (ID: {$guard['id_number']}) has checked out from {$currentDuty[0]['location_name']} after completing {$currentDuty[0]['shift_name']} shift. Duration: $duration";
                
                $query = "INSERT INTO notifications (user_id, title, message, type, link) 
                          VALUES (?, ?, ?, 'attendance', ?)";
                executeQuery($query, [
                    $currentDuty[0]['organization_admin_id'], 
                    $notificationTitle, 
                    $notificationMessage,
                    $orgAdminLink
                ]);
                
                // Notification for system admins
                $adminLink = "/admin/attendance/view?guard_id=" . $guardId . "&duty_id=" . $dutyId;
                $adminQuery = "SELECT id FROM users WHERE role = 'admin' AND status = 'active'";
                $admins = executeQuery($adminQuery);
                
                foreach ($admins as $admin) {
                    $query = "INSERT INTO notifications (user_id, title, message, type, link) 
                              VALUES (?, ?, ?, 'attendance', ?)";
                    executeQuery($query, [
                        $admin['id'], 
                        $notificationTitle, 
                        $notificationMessage,
                        $adminLink
                    ]);
                }
                
                $_SESSION['success'] = 'Check-out successful';
                redirect($_SERVER['PHP_SELF']);
            } else {
                $_SESSION['error'] = 'Failed to check-out. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guard Dashboard | <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/guard-dashboard.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include '../includes/guard-sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Navigation -->
            <?php include '../includes/top-nav.php'; ?>
            
            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <div class="dashboard-header">
                    <h1>Welcome, <?php echo $_SESSION['name']; ?></h1>
                    <p>Your security dashboard</p>
                </div>
                
                <!-- Success/Error Messages -->
                <?php echo flashMessage('success'); ?>
                <?php echo flashMessage('error'); ?>
                
                <!-- Current Duty Card -->
                <div class="card duty-card">
                    <div class="card-header">
                        <h2>Current Duty</h2>
                        <?php if (!empty($currentDuty)): ?>
                            <span class="badge badge-primary">Active</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">No Active Duty</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($currentDuty)): ?>
                        <div class="card-body">
                            <div class="duty-details">
                                <div class="duty-info">
                                    <div class="info-group">
                                        <label>Organization:</label>
                                        <span><?php echo sanitize($currentDuty[0]['organization_name']); ?></span>
                                    </div>
                                    
                                    <div class="info-group">
                                        <label>Location:</label>
                                        <span><?php echo sanitize($currentDuty[0]['location_name']); ?></span>
                                    </div>
                                    
                                    <div class="info-group">
                                        <label>Address:</label>
                                        <span><?php echo sanitize($currentDuty[0]['location_address']); ?></span>
                                    </div>
                                    
                                    <div class="info-group">
                                        <label>Shift:</label>
                                        <span><?php echo sanitize($currentDuty[0]['shift_name']); ?> (<?php echo formatTime($currentDuty[0]['start_time']); ?> - <?php echo formatTime($currentDuty[0]['end_time']); ?>)</span>
                                    </div>
                                </div>
                                
                                <div class="check-in-out">
                                    <!-- In the check-in form section -->
                                    <?php if (empty($todayAttendance)): ?>
                                        <!-- Check-in Form -->
                                        <form method="POST" action="" id="check-in-form">
                                            <input type="hidden" name="action" value="check_in">
                                            <input type="hidden" name="latitude" id="latitude">
                                            <input type="hidden" name="longitude" id="longitude">
                                            
                                            <div class="form-group">
                                                <label for="notes">Notes:</label>
                                                <textarea name="notes" id="notes" placeholder="Any notes about your check-in"></textarea>
                                            </div>
                                            
                                            <button type="submit" class="btn btn-success" id="check-in-btn" disabled>
                                                <i data-lucide="log-in"></i> Check In
                                            </button>
                                            
                                            <p class="location-status" id="location-status">Getting your location...</p>
                                            <?php if (!empty($currentDuty)): ?>
                                                <?php 
                                                    $shiftStart = strtotime($currentDuty[0]['start_time']);
                                                    $earliestCheckIn = date('h:i A', $shiftStart - (15 * 60));
                                                    $latestCheckIn = date('h:i A', $shiftStart + (60 * 60));
                                                ?>
                                                <p class="time-window">You can check in between <?php echo $earliestCheckIn; ?> and <?php echo $latestCheckIn; ?></p>
                                            <?php endif; ?>
                                        </form>
                                    <?php elseif (empty($todayAttendance['check_out_time'])): ?>
                                        <!-- Check-out Form -->
                                        <form method="POST" action="" id="check-out-form">
                                            <input type="hidden" name="action" value="check_out">
                                            <input type="hidden" name="latitude" id="latitude">
                                            <input type="hidden" name="longitude" id="longitude">
                                            
                                            <div class="form-group">
                                                <label for="notes">Notes:</label>
                                                <textarea name="notes" id="notes" placeholder="Any notes about your check-out"></textarea>
                                            </div>
                                            
                                            <button type="submit" class="btn btn-warning" id="check-out-btn" disabled>
                                                <i data-lucide="log-out"></i> Check Out
                                            </button>
                                            
                                            <p class="location-status" id="location-status">Getting your location...</p>
                                            <?php if (!empty($currentDuty)): ?>
                                                <?php 
                                                    $shiftEnd = strtotime($currentDuty[0]['end_time']);
                                                    // Handle overnight shifts
                                                    if (strtotime($currentDuty[0]['start_time']) > $shiftEnd) {
                                                        $shiftEnd = strtotime(date('Y-m-d', strtotime('+1 day')) . ' ' . $currentDuty[0]['end_time']);
                                                    }
                                                    $earliestCheckOut = date('h:i A', $shiftEnd - (60 * 60));
                                                    $latestCheckOut = date('h:i A', $shiftEnd + (15 * 60));
                                                ?>
                                                <p class="time-window">You can check out between <?php echo $earliestCheckOut; ?> and <?php echo $latestCheckOut; ?></p>
                                            <?php endif; ?>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Location Map -->
                            <div class="location-map">
                                <div id="map"></div>
                            </div>
                            
                            <!-- Quick Actions -->
                            <div class="quick-actions">
                                <a href="report-incident.php?location_id=<?php echo $currentDuty[0]['location_id']; ?>" class="btn btn-danger">
                                    <i data-lucide="alert-triangle"></i> Report Incident
                                </a>
                                
                                <a href="view-duty.php?id=<?php echo $currentDuty[0]['id']; ?>" class="btn btn-primary">
                                    <i data-lucide="eye"></i> View Details
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card-body">
                            <div class="no-duty">
                                <div class="no-duty-icon">
                                    <i data-lucide="calendar-x"></i>
                                </div>
                                <p>You don't have any active duty assigned for today.</p>
                                <a href="schedule.php" class="btn btn-primary">View Schedule</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Upcoming Duties -->
                <div class="card dashboard-card">
                    <div class="card-header">
                        <h2>Upcoming Duties</h2>
                        <a href="schedule.php" class="btn btn-sm btn-outline">View Full Schedule</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($upcomingDuties)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Organization</th>
                                            <th>Location</th>
                                            <th>Shift</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($upcomingDuties as $duty): ?>
                                            <tr>
                                                <td><?php echo sanitize($duty['organization_name']); ?></td>
                                                <td><?php echo sanitize($duty['location_name']); ?></td>
                                                <td><?php echo sanitize($duty['shift_name']); ?></td>
                                                <td><?php echo formatDate($duty['start_date'], 'd M Y'); ?></td>
                                                <td><?php echo $duty['end_date'] ? formatDate($duty['end_date'], 'd M Y') : 'Ongoing'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="no-data">No upcoming duties scheduled.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Incidents -->
                <div class="card dashboard-card">
                    <div class="card-header">
                        <h2>Recent Incidents</h2>
                        <a href="incidents.php" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recentIncidents)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Location</th>
                                            <th>Severity</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentIncidents as $incident): ?>
                                            <tr>
                                                <td><?php echo sanitize($incident['title']); ?></td>
                                                <td><?php echo sanitize($incident['location_name']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo getSeverityClass($incident['severity']); ?>">
                                                        <?php echo ucfirst(sanitize($incident['severity'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo formatDate($incident['incident_time']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo getStatusClass($incident['status']); ?>">
                                                        <?php echo ucfirst(sanitize($incident['status'])); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="no-data">No incidents reported.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Evaluations -->
                <div class="card dashboard-card">
                    <div class="card-header">
                        <h2>Recent Performance Evaluations</h2>
                        <a href="evaluations.php" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recentEvaluations)): ?>
                            <div class="evaluations-list">
                                <?php foreach ($recentEvaluations as $evaluation): ?>
                                    <div class="evaluation-card">
                                        <div class="evaluation-header">
                                            <div class="evaluation-date">
                                                <i data-lucide="calendar"></i>
                                                <span><?php echo formatDate($evaluation['evaluation_date'], 'd M Y'); ?></span>
                                            </div>
                                            <div class="evaluation-rating">
                                                <span>Overall: </span>
                                                <div class="rating">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <span class="star <?php echo $i <= $evaluation['overall_rating'] ? 'filled' : ''; ?>">★</span>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="evaluation-body">
                                            <div class="evaluation-criteria">
                                                <div class="criteria-item">
                                                    <span>Punctuality:</span>
                                                    <div class="rating">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <span class="star <?php echo $i <= $evaluation['punctuality'] ? 'filled' : ''; ?>">★</span>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                                
                                                <div class="criteria-item">
                                                    <span>Appearance:</span>
                                                    <div class="rating">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <span class="star <?php echo $i <= $evaluation['appearance'] ? 'filled' : ''; ?>">★</span>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                                
                                                <div class="criteria-item">
                                                    <span>Communication:</span>
                                                    <div class="rating">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <span class="star <?php echo $i <= $evaluation['communication'] ? 'filled' : ''; ?>">★</span>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                                
                                                <div class="criteria-item">
                                                    <span>Job Knowledge:</span>
                                                    <div class="rating">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <span class="star <?php echo $i <= $evaluation['job_knowledge'] ? 'filled' : ''; ?>">★</span>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <?php if (!empty($evaluation['comments'])): ?>
                                                <div class="evaluation-comments">
                                                    <p><strong>Comments:</strong> <?php echo sanitize($evaluation['comments']); ?></p>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="evaluation-footer">
                                                <p>Evaluated by: <?php echo sanitize($evaluation['evaluator_name']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="no-data">No evaluations available.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <?php if (!empty($currentDuty)): ?>
    <!-- Include Google Maps API -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCM-P8aABs132jn7dyh0sr6t3-bHx56BqE&callback=initMap" async defer></script>
    <script>
        // Initialize map
        let map;
        let dutyLocation = {
            lat: <?php echo $currentDuty[0]['latitude']; ?>,
            lng: <?php echo $currentDuty[0]['longitude']; ?>
        };
        
        function initMap() {
            // Create map with strict controls
            map = new google.maps.Map(document.getElementById("map"), {
                center: dutyLocation,
                zoom: 18, // Higher zoom level for precision
                mapTypeId: "roadmap",
                disableDefaultUI: true, // Disable default controls
                gestureHandling: "cooperative", // Require Ctrl+scroll to zoom
                keyboardShortcuts: false,
                clickableIcons: false,
                minZoom: 16, // Prevent zooming out too far
                maxZoom: 20  // Prevent zooming in too close
            });
            
            // Add marker for duty location
            new google.maps.Marker({
                position: dutyLocation,
                map: map,
                title: "Duty Location",
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 8,
                    fillColor: "#4CAF50",
                    fillOpacity: 1,
                    strokeColor: "#FFFFFF",
                    strokeWeight: 2
                }
            });
            
            // Draw precise circle (50m radius)
            new google.maps.Circle({
                strokeColor: "#4CAF50",
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: "#4CAF50",
                fillOpacity: 0.2,
                map: map,
                center: dutyLocation,
                radius: 50, // 50 meters for more precision
                clickable: false
            });
            
            // Try to get current location
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const userLocation = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude,
                        };
                        
                        // Add marker for user's location
                        new google.maps.Marker({
                            position: userLocation,
                            map: map,
                            icon: {
                                path: google.maps.SymbolPath.CIRCLE,
                                scale: 8,
                                fillColor: "#0288D1",
                                fillOpacity: 1,
                                strokeColor: "#FFFFFF",
                                strokeWeight: 2,
                            },
                            title: "Your Location",
                        });
                        
                        // Calculate distance between duty location and user location
                        const distance = calculateDistance(
                            dutyLocation.lat, dutyLocation.lng,
                            userLocation.lat, userLocation.lng
                        );
                        
                        // Update UI based on distance
                        updateLocationStatus(distance);
                        
                        // Update form inputs
                        document.getElementById("latitude").value = userLocation.lat;
                        document.getElementById("longitude").value = userLocation.lng;
                        
                        // Fit map to show both points
                        const bounds = new google.maps.LatLngBounds();
                        bounds.extend(dutyLocation);
                        bounds.extend(userLocation);
                        map.fitBounds(bounds);
                    },
                    (error) => {
                        console.error("Error getting location:", error);
                        document.getElementById("location-status").textContent = "Unable to get your location. Please enable location services.";
                        document.getElementById("location-status").classList.add("error");
                    }
                );
            } else {
                document.getElementById("location-status").textContent = "Geolocation is not supported by this browser.";
                document.getElementById("location-status").classList.add("error");
            }
        }
        
        // Calculate distance between two points using Haversine formula
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371; // Radius of the earth in km
            const dLat = deg2rad(lat2 - lat1);
            const dLon = deg2rad(lon2 - lon1);
            const a = 
                Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * 
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            const distance = R * c * 1000; // Distance in meters
            return distance;
        }
        
        function deg2rad(deg) {
            return deg * (Math.PI / 180);
        }
        
        // Update location status based on distance
        function updateLocationStatus(distance) {
            const locationStatus = document.getElementById("location-status");
            const checkInBtn = document.getElementById("check-in-btn");
            const checkOutBtn = document.getElementById("check-out-btn");
            
            if (distance <= 100) { // Within 100 meters
                locationStatus.textContent = "You are at the duty location (" + Math.round(distance) + " meters away).";
                locationStatus.classList.remove("error");
                locationStatus.classList.add("success");
                
                if (checkInBtn) checkInBtn.disabled = false;
                if (checkOutBtn) checkOutBtn.disabled = false;
            } else {
                locationStatus.textContent = "You are " + Math.round(distance) + " meters away from the duty location. You need to be within 100 meters to check in/out.";
                locationStatus.classList.remove("success");
                locationStatus.classList.add("error");
                
                if (checkInBtn) checkInBtn.disabled = true;
                if (checkOutBtn) checkOutBtn.disabled = true;
            }
        }
        
        // Initialize Lucide icons
        lucide.createIcons();
    </script>
    <?php else: ?>
    <script>
        // Initialize Lucide icons
        lucide.createIcons();
    </script>
    <?php endif; ?>
</body>
</html>

<?php
// Helper functions
function formatTime($time) {
    return date('h:i A', strtotime($time));
}

function getAttendanceStatusClass($status) {
    switch ($status) {
        case 'present':
            return 'success';
        case 'absent':
            return 'danger';
        case 'late':
            return 'warning';
        case 'early_departure':
            return 'warning';
        default:
            return 'secondary';
    }
}

function getSeverityClass($severity) {
    switch ($severity) {
        case 'low':
            return 'success';
        case 'medium':
            return 'warning';
        case 'high':
            return 'danger';
        case 'critical':
            return 'danger';
        default:
            return 'secondary';
    }
}

function getStatusClass($status) {
    switch ($status) {
        case 'reported':
            return 'warning';
        case 'investigating':
            return 'primary';
        case 'resolved':
            return 'success';
        case 'closed':
            return 'secondary';
        default:
            return 'secondary';
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