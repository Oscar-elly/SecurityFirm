<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('admin');

// Handle status updates via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $incident_id = isset($_POST['incident_id']) ? (int)$_POST['incident_id'] : 0;
    $status = isset($_POST['status']) ? sanitize($_POST['status']) : '';

    $response = ['success' => false, 'newStatus' => '', 'error' => ''];

    if ($incident_id > 0 && $status) {
        $query = "UPDATE incidents SET status = ? WHERE id = ?";
        $result = executeQuery($query, [$status, $incident_id]);

        if ($result) {
            $response['success'] = true;
            $response['newStatus'] = $status;
        } else {
            $response['error'] = 'Failed to update incident status';
        }
    } else {
        $response['error'] = 'Invalid incident ID or status';
    }

    // Check if AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } else {
        if ($response['success']) {
            $_SESSION['success'] = 'Incident status updated successfully';
        } else {
            $_SESSION['error'] = $response['error'];
        }
        redirect('view-incident.php?id=' . $incident_id);
    }
}

// Get incident ID from query parameter
$incidentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($incidentId <= 0) {
    $_SESSION['error'] = 'Invalid incident ID';
    redirect('incidents.php');
}

// Get incident details with location and reporter info
$query = "SELECT i.*, l.name as location_name, u.name as reporter_name, u.role as reporter_role
          FROM incidents i 
          JOIN locations l ON i.location_id = l.id 
          JOIN users u ON i.reported_by = u.id 
          WHERE i.id = ?";
$incident = executeQuery($query, [$incidentId], ['single' => true]);

if (!$incident) {
    $_SESSION['error'] = 'Incident not found';
    redirect('incidents.php');
}

function getSeverityClass($severity) {
    switch ($severity) {
        case 'low': return 'success';
        case 'medium': return 'warning';
        case 'high': return 'danger';
        case 'critical': return 'danger';
        default: return 'secondary';
    }
}

function getStatusClass($status) {
    switch ($status) {
        case 'reported': return 'warning';
        case 'investigating': return 'primary';
        case 'resolved': return 'success';
        case 'closed': return 'secondary';
        default: return 'secondary';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Incident Details | <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../../assets/css/styles.css" />
    <link rel="stylesheet" href="../../assets/css/dashboard.css" />
    <link rel="stylesheet" href="../../assets/css/organization-dashboard.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/admin-sidebar.php'; ?>

        <main class="main-content">
            <?php include '../includes/top-nav.php'; ?>

            <div class="dashboard-content">
                <div class="dashboard-header">
                    <h1>Incident Details</h1>
                    <a href="incidents.php" class="btn btn-outline">Back to Incidents</a>
                </div>

                <div class="card incident-card">
                    <div class="incident-header">
                        <div class="incident-title">
                            <h2><?php echo sanitize($incident['title']); ?></h2>
                            <div class="incident-meta">
                                <span class="incident-location">
                                    <i data-lucide="map-pin"></i>
                                    <?php echo sanitize($incident['location_name']); ?>
                                </span>
                                <span class="incident-reporter">
                                    <i data-lucide="user"></i>
                                    <?php echo sanitize($incident['reporter_name']); ?> (<?php echo ucfirst($incident['reporter_role']); ?>)
                                </span>
                                <span class="incident-date">
                                    <i data-lucide="calendar"></i>
                                    <?php echo formatDate($incident['incident_time']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="incident-badges">
                            <span class="badge badge-<?php echo getSeverityClass($incident['severity']); ?>">
                                <?php echo ucfirst($incident['severity']); ?>
                            </span>
                            <span class="badge badge-<?php echo getStatusClass($incident['status']); ?>">
                                <?php echo ucfirst($incident['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="incident-body">
                        <p><?php echo nl2br(sanitize($incident['description'])); ?></p>
                    </div>
                    <div class="incident-actions" style="margin-top: 1rem;">
                        <?php if ($incident['latitude'] && $incident['longitude']): ?>
                        <a href="https://maps.google.com/maps?q=<?php echo sanitize($incident['latitude']); ?>,<?php echo sanitize($incident['longitude']); ?>&z=15" target="_blank" class="btn btn-secondary">
                            <i data-lucide="map-pin"></i> View Location
                        </a>
                        <?php endif; ?>
                        <a href="download-incident-report.php?id=<?php echo $incident['id']; ?>" class="btn btn-primary">
                            <i data-lucide="download"></i> Download Report
                        </a>
                    </div>

                        <?php if ($incident['status'] !== 'closed'): ?>
                        <div class="dashboard-actions" style="margin-top: 1rem;">
                            <?php if ($incident['status'] === 'reported'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="incident_id" value="<?php echo $incident['id']; ?>">
                                <input type="hidden" name="status" value="investigating">
                                <button type="submit" class="btn btn-sm btn-primary">Start Investigation</button>
                            </form>
                            <?php endif; ?>

                            <?php if ($incident['status'] === 'investigating'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="incident_id" value="<?php echo $incident['id']; ?>">
                                <input type="hidden" name="status" value="resolved">
                                <button type="submit" class="btn btn-sm btn-primary">Mark Resolved</button>
                            </form>
                            <?php endif; ?>

                            <?php if ($incident['status'] === 'resolved'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="incident_id" value="<?php echo $incident['id']; ?>">
                                <input type="hidden" name="status" value="closed">
                                <button type="submit" class="btn btn-sm btn-primary">Close Incident</button>
                            </form>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();

        // AJAX form submission for status update
        document.addEventListener('DOMContentLoaded', function () {
            const forms = document.querySelectorAll('.dashboard-actions form');
            forms.forEach(form => {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const formData = new FormData(form);
                    fetch(form.action || window.location.href, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update buttons dynamically
                            updateStatusButtons(data.newStatus);
                        } else {
                            alert(data.error || 'Failed to update status');
                        }
                    })
                    .catch(() => {
                        alert('Error updating status');
                    });
                });
            });

            function updateStatusButtons(newStatus) {
                const container = document.querySelector('.dashboard-actions');
                if (!container) return;

                let html = '';
                if (newStatus === 'reported') {
                    html += `
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="incident_id" value="<?php echo $incident['id']; ?>">
                        <input type="hidden" name="status" value="investigating">
                        <button type="submit" class="btn btn-sm btn-primary">Start Investigation</button>
                    </form>`;
                } else if (newStatus === 'investigating') {
                    html += `
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="incident_id" value="<?php echo $incident['id']; ?>">
                        <input type="hidden" name="status" value="resolved">
                        <button type="submit" class="btn btn-sm btn-primary">Mark Resolved</button>
                    </form>`;
                } else if (newStatus === 'resolved') {
                    html += `
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="incident_id" value="<?php echo $incident['id']; ?>">
                        <input type="hidden" name="status" value="closed">
                        <button type="submit" class="btn btn-sm btn-primary">Close Incident</button>
                    </form>`;
                } else {
                    html = '<p>Incident is closed.</p>';
                }
                container.innerHTML = html;
            }
        });
    </script>
</body>
</html>
