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

// Get locations where this guard is assigned
$query = "SELECT DISTINCT l.id, l.name, o.name as organization_name 
          FROM locations l 
          JOIN organizations o ON l.organization_id = o.id 
          JOIN duty_assignments da ON l.id = da.location_id 
          WHERE da.guard_id = ? AND da.status = 'active'";
$locations = executeQuery($query, [$guard['id']]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $location_id = (int)$_POST['location_id'];
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $incident_time = sanitize($_POST['incident_time']);
    $severity = sanitize($_POST['severity']);
    $latitude = filter_input(INPUT_POST, 'latitude', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $longitude = filter_input(INPUT_POST, 'longitude', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    
    // Insert incident
    $query = "INSERT INTO incidents (reported_by, location_id, title, description, incident_time, severity, latitude, longitude, status) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'reported')";
    $result = executeQuery($query, [$userId, $location_id, $title, $description, $incident_time, $severity, $latitude, $longitude]);
    
    if ($result && $result['insert_id']) {
        $incidentId = $result['insert_id'];
        
        // Handle file uploads
        if (!empty($_FILES['media']['name'][0])) {
            for ($i = 0; $i < count($_FILES['media']['name']); $i++) {
                if ($_FILES['media']['error'][$i] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['media']['name'][$i],
                        'type' => $_FILES['media']['type'][$i],
                        'tmp_name' => $_FILES['media']['tmp_name'][$i],
                        'error' => $_FILES['media']['error'][$i],
                        'size' => $_FILES['media']['size'][$i]
                    ];
                    
                    $uploadResult = uploadFile($file, 'incidents');
                    if ($uploadResult) {
                        $mediaQuery = "INSERT INTO incident_media (incident_id, file_path, file_type) VALUES (?, ?, ?)";
                        executeQuery($mediaQuery, [$incidentId, $uploadResult['path'], $uploadResult['type']]);
                    }
                }
            }
        }
        
        // Log activity
        logActivity($userId, "Reported incident: " . $title, 'incident');
        
        // Send notification to admin
        $adminQuery = "SELECT id FROM users WHERE role = 'admin'";
        $admins = executeQuery($adminQuery);
        foreach ($admins as $admin) {
            $notificationQuery = "INSERT INTO notifications (user_id, title, message, type, link) 
                                  VALUES (?, ?, ?, 'incident', 'views/admin/incidents.php')";
            executeQuery($notificationQuery, [$admin['id'], 'New Incident Reported', $_SESSION['name'] . ' reported: ' . $title]);
        }

        // Send SMS alerts to admins
        $adminIds = array_column($admins, 'id');
        if (!empty($adminIds)) {
            $placeholders = implode(',', array_fill(0, count($adminIds), '?'));
            $phoneQuery = "SELECT phone FROM users WHERE id IN ($placeholders)";
            $phones = executeQuery($phoneQuery, $adminIds);
            $phoneNumbers = array_column($phones, 'phone');

            $smsMessage = "Alert: New Incident Reported by " . $_SESSION['name'] . ": " . $title;
            sendSMS($phoneNumbers, $smsMessage);
        }
        
        $_SESSION['success'] = 'Incident reported successfully';
        redirect('incidents.php');
    } else {
        $_SESSION['error'] = 'Failed to report incident';
    }
}

// Get location from URL if provided
$selectedLocationId = isset($_GET['location_id']) ? (int)$_GET['location_id'] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Incident | <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family:Inter:wght@400;500&display=swap" rel="stylesheet">
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
                    <h1>Report Incident</h1>
                    <p>Report security incidents or unusual activities</p>
                </div>
                
                <?php echo flashMessage('error'); ?>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Incident Report Form</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" id="incidentForm">
                            <div class="form-group">
                                <label for="location_id">Location *</label>
                                <select id="location_id" name="location_id" required>
                                    <option value="">Select Location</option>
                                    <?php foreach ($locations as $location): ?>
                                    <option value="<?php echo $location['id']; ?>" <?php echo $selectedLocationId == $location['id'] ? 'selected' : ''; ?>>
                                        <?php echo sanitize($location['name']) . ' - ' . sanitize($location['organization_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="title">Incident Title *</label>
                                <input type="text" id="title" name="title" required placeholder="Brief description of the incident">
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Detailed Description *</label>
                                <textarea id="description" name="description" required rows="6" placeholder="Provide detailed information about what happened, who was involved, and any other relevant details"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="incident_time">Incident Date & Time *</label>
                                <input type="datetime-local" id="incident_time" name="incident_time" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="severity">Severity Level *</label>
                                <select id="severity" name="severity" required>
                                    <option value="">Select Severity</option>
                                    <option value="low">Low - Minor issue, no immediate threat</option>
                                    <option value="medium">Medium - Moderate concern, requires attention</option>
                                    <option value="high">High - Serious issue, immediate action needed</option>
                                    <option value="critical">Critical - Emergency situation, urgent response required</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="media">Attach Photos/Videos (Optional)</label>
                                <input type="file" id="media" name="media[]" multiple accept="image/*,video/*">
                                <small class="form-help">You can attach multiple files. Supported formats: JPG, PNG, MP4, MOV</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Location Coordinates</label>
                                <div style="display: flex; gap: 1rem;">
                                    <input type="hidden" id="latitude" name="latitude">
                                    <input type="hidden" id="longitude" name="longitude">
                                    <button type="button" id="getLocationBtn" class="btn btn-outline">
                                        <i data-lucide="map-pin"></i> Get Current Location
                                    </button>
                                    <span id="locationStatus" class="location-status"></span>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <div class="alert alert-info">
                                    <strong>Important:</strong> Please ensure all information is accurate and complete. 
                                    This report will be immediately sent to the security management team and relevant authorities if necessary.
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <a href="dashboard.php" class="btn btn-outline">Cancel</a>
                                <button type="submit" class="btn btn-danger">
                                    <i data-lucide="alert-triangle"></i> Submit Incident Report
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
    .form-actions {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        margin-top: 2rem;
        padding-top: 1rem;
        border-top: 1px solid #eee;
    }
    
    .location-status {
        display: flex;
        align-items: center;
        font-size: 0.875rem;
        color: #666;
    }
    
    .location-status.success {
        color: #4caf50;
    }
    
    .location-status.error {
        color: #f44336;
    }
    
    .form-help {
        display: block;
        margin-top: 0.25rem;
        font-size: 0.875rem;
        color: #666;
    }
    </style>

    <script>
        lucide.createIcons();
        
        // Set current date and time as default
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            const localDateTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
            document.getElementById('incident_time').value = localDateTime;
        });
        
        // Get current location
        document.getElementById('getLocationBtn').addEventListener('click', function() {
            const statusElement = document.getElementById('locationStatus');
            const btn = this;
            
            btn.disabled = true;
            statusElement.textContent = 'Getting location...';
            statusElement.className = 'location-status';
            
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        document.getElementById('latitude').value = position.coords.latitude;
                        document.getElementById('longitude').value = position.coords.longitude;
                        
                        statusElement.textContent = 'Location captured successfully';
                        statusElement.className = 'location-status success';
                        btn.disabled = false;
                    },
                    function(error) {
                        statusElement.textContent = 'Failed to get location: ' + error.message;
                        statusElement.className = 'location-status error';
                        btn.disabled = false;
                    }
                );
            } else {
                statusElement.textContent = 'Geolocation is not supported by this browser';
                statusElement.className = 'location-status error';
                btn.disabled = false;
            }
        });
        
        // Form validation
        document.getElementById('incidentForm').addEventListener('submit', function(e) {
            const severity = document.getElementById('severity').value;
            
            if (severity === 'critical' || severity === 'high') {
                if (!confirm('This is a ' + severity + ' severity incident. Are you sure you want to submit this report? This will trigger immediate notifications to the security team.')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    </script>
</body>
</html>