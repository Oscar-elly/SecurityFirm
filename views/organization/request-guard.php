<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('organization');

// Get organization information
$userId = $_SESSION['user_id'];
$query = "SELECT * FROM organizations WHERE user_id = ?";
$organization = executeQuery($query, [$userId], ['single' => true]);

if (!$organization) {
    $_SESSION['error'] = 'Organization information not found';
    redirect(SITE_URL);
}

// Get organization locations
$query = "SELECT * FROM locations WHERE user_id = ? AND status = 'active'";
$locations = executeQuery($query, [$organization['id']]);

// Get available shifts
$query = "SELECT * FROM shifts ORDER BY start_time";
$shifts = executeQuery($query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $location_id = (int)$_POST['location_id'];
    $number_of_guards = (int)$_POST['number_of_guards'];
    $shift_id = (int)$_POST['shift_id'];
    $start_date = sanitize($_POST['start_date']);
    $end_date = sanitize($_POST['end_date']);
    $reason = sanitize($_POST['reason']);
    
    // Insert guard request
    $query = "INSERT INTO guard_requests (user_id, location_id, number_of_guards, shift_id, start_date, end_date, reason, status) 
              VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
    $result = executeQuery($query, [$organization['id'], $location_id, $number_of_guards, $shift_id, $start_date, $end_date, $reason]);
    
    if ($result) {
        // Log activity
        logActivity($userId, "Requested " . $number_of_guards . " guard(s) for location", 'request');
        
        // Send notification to admin
        $adminQuery = "SELECT id FROM users WHERE role = 'admin'";
        $admins = executeQuery($adminQuery);
        foreach ($admins as $admin) {
            $notificationQuery = "INSERT INTO notifications (user_id, title, message, type, link) 
                                  VALUES (?, ?, ?, 'request', 'views/admin/guard-requests.php')";
            executeQuery($notificationQuery, [$admin['id'], 'New Guard Request', $organization['name'] . ' requested ' . $number_of_guards . ' guard(s)']);
        }
        
        $_SESSION['success'] = 'Guard request submitted successfully';
        redirect('guard-requests.php');
    } else {
        $_SESSION['error'] = 'Failed to submit guard request';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Security Guards | <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/organization-dashboard.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/organization-sidebar.php'; ?>
        
        <main class="main-content">
            <?php include '../includes/top-nav.php'; ?>
            
            <div class="dashboard-content">
                <div class="dashboard-header">
                    <h1>Request Security Guards</h1>
                    <p>Submit a request for additional security personnel</p>
                </div>
                
                <?php echo flashMessage('error'); ?>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Guard Request Form</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="guardRequestForm">
                            <div class="form-group">
                                <label for="location_id">Location *</label>
                                <select id="location_id" name="location_id" required>
                                    <option value="">Select Location</option>
                                    <?php foreach ($locations as $location): ?>
                                    <option value="<?php echo $location['id']; ?>">
                                        <?php echo sanitize($location['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($locations)): ?>
                                <small class="form-help">
                                    <a href="locations.php">Add a location</a> first before requesting guards.
                                </small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="number_of_guards">Number of Guards Required *</label>
                                <input type="number" id="number_of_guards" name="number_of_guards" min="1" max="10" required>
                                <small class="form-help">Maximum 10 guards per request</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="shift_id">Preferred Shift *</label>
                                <select id="shift_id" name="shift_id" required>
                                    <option value="">Select Shift</option>
                                    <?php foreach ($shifts as $shift): ?>
                                    <option value="<?php echo $shift['id']; ?>">
                                        <?php echo sanitize($shift['name']) . ' (' . formatTime($shift['start_time']) . ' - ' . formatTime($shift['end_time']) . ')'; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="start_date">Start Date *</label>
                                <input type="date" id="start_date" name="start_date" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="end_date">End Date (Optional)</label>
                                <input type="date" id="end_date" name="end_date">
                                <small class="form-help">Leave empty for ongoing assignment</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="reason">Reason for Request *</label>
                                <textarea id="reason" name="reason" required rows="4" placeholder="Please provide details about why you need additional security guards, any specific requirements, or special circumstances"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <div class="alert alert-info">
                                    <strong>Note:</strong> Your request will be reviewed by our security management team. 
                                    You will be notified once the request is approved and guards are assigned.
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <a href="dashboard.php" class="btn btn-outline">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i data-lucide="send"></i> Submit Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Request Guidelines -->
                <div class="card">
                    <div class="card-header">
                        <h2>Request Guidelines</h2>
                    </div>
                    <div class="card-body">
                        <div class="guidelines-grid">
                            <div class="guideline-item">
                                <div class="guideline-icon">
                                    <i data-lucide="clock"></i>
                                </div>
                                <div class="guideline-content">
                                    <h3>Processing Time</h3>
                                    <p>Regular requests are processed within 24-48 hours. Emergency requests are handled immediately.</p>
                                </div>
                            </div>
                            
                            <div class="guideline-item">
                                <div class="guideline-icon">
                                    <i data-lucide="calendar"></i>
                                </div>
                                <div class="guideline-content">
                                    <h3>Advance Notice</h3>
                                    <p>Please submit requests at least 48 hours in advance for better guard availability.</p>
                                </div>
                            </div>
                            
                            <div class="guideline-icon">
                                <div class="guideline-icon">
                                    <i data-lucide="shield-check"></i>
                                </div>
                                <div class="guideline-content">
                                    <h3>Guard Qualifications</h3>
                                    <p>All guards are professionally trained, licensed, and background-checked for your security.</p>
                                </div>
                            </div>
                            
                            <div class="guideline-item">
                                <div class="guideline-icon">
                                    <i data-lucide="phone"></i>
                                </div>
                                <div class="guideline-content">
                                    <h3>Emergency Requests</h3>
                                    <p>For urgent security needs, call our emergency hotline: +254-700-000-000</p>
                                </div>
                            </div>
                        </div>
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
    
    .form-help {
        display: block;
        margin-top: 0.25rem;
        font-size: 0.875rem;
        color: #666;
    }
    
    .guidelines-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
    }
    
    .guideline-item {
        display: flex;
        gap: 1rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .guideline-icon {
        width: 48px;
        height: 48px;
        background: var(--primary-color);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .guideline-content h3 {
        margin: 0 0 0.5rem 0;
        font-size: 1rem;
        font-weight: 600;
    }
    
    .guideline-content p {
        margin: 0;
        font-size: 0.875rem;
        color: #666;
    }
    </style>

    <script>
        lucide.createIcons();
        
        // Set minimum start date to today
        document.getElementById('start_date').min = new Date().toISOString().split('T')[0];
        
        // Update end date minimum when start date changes
        document.getElementById('start_date').addEventListener('change', function() {
            const endDateInput = document.getElementById('end_date');
            endDateInput.min = this.value;
            
            // Clear end date if it's before the new start date
            if (endDateInput.value && endDateInput.value < this.value) {
                endDateInput.value = '';
            }
        });
        
        // Form validation
        document.getElementById('guardRequestForm').addEventListener('submit', function(e) {
            const startDate = new Date(document.getElementById('start_date').value);
            const endDate = document.getElementById('end_date').value ? new Date(document.getElementById('end_date').value) : null;
            
            if (endDate && endDate <= startDate) {
                alert('End date must be after start date');
                e.preventDefault();
                return false;
            }
            
            const numberOfGuards = parseInt(document.getElementById('number_of_guards').value);
            if (numberOfGuards > 5) {
                if (!confirm('You are requesting ' + numberOfGuards + ' guards. Large requests may take longer to process. Continue?')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    </script>
</body>
</html>

<?php
function formatTime($time) {
    return date('h:i A', strtotime($time));
}
?>