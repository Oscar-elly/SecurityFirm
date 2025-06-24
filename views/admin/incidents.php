<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('admin');

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $incident_id = (int)$_POST['incident_id'];
        $status = sanitize($_POST['status']);
        
        $query = "UPDATE incidents SET status = ? WHERE id = ?";
        $result = executeQuery($query, [$status, $incident_id]);
        
        if ($result) {
            $_SESSION['success'] = 'Incident status updated successfully';
        } else {
            $_SESSION['error'] = 'Failed to update incident status';
        }
        redirect($_SERVER['PHP_SELF']);
    }
}

// Get all incidents with related information
$query = "SELECT i.*, u.name as reporter_name, l.name as location_name, o.name as organization_name 
          FROM incidents i 
          JOIN users u ON i.reported_by = u.id 
          JOIN locations l ON i.location_id = l.id 
          JOIN organizations o ON l.organization_id = o.id 
          ORDER BY i.incident_time DESC";
$incidents = executeQuery($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incidents Management | <?php echo SITE_NAME; ?></title>
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
                    <h1>Incidents Management</h1>
                    <div class="dashboard-actions">
                        <select id="statusFilter" class="form-control" style="width: auto; display: inline-block;">
                            <option value="">All Statuses</option>
                            <option value="reported">Reported</option>
                            <option value="investigating">Investigating</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                        <select id="severityFilter" class="form-control" style="width: auto; display: inline-block;">
                            <option value="">All Severities</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                </div>
                
                <?php echo flashMessage('success'); ?>
                <?php echo flashMessage('error'); ?>
                
                <div class="card">
                    <div class="card-header">
                        <h2>All Incidents</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="incidentsTable">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Location</th>
                                        <th>Organization</th>
                                        <th>Severity</th>
                                        <th>Reported By</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($incidents as $incident): ?>
                                    <tr data-status="<?php echo $incident['status']; ?>" data-severity="<?php echo $incident['severity']; ?>">
                                        <td>
                                            <strong><?php echo sanitize($incident['title']); ?></strong>
                                            <br><small><?php echo substr(sanitize($incident['description']), 0, 100) . '...'; ?></small>
                                        </td>
                                        <td><?php echo sanitize($incident['location_name']); ?></td>
                                        <td><?php echo sanitize($incident['organization_name']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo getSeverityClass($incident['severity']); ?>">
                                                <?php echo ucfirst($incident['severity']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo sanitize($incident['reporter_name']); ?></td>
                                        <td><?php echo formatDate($incident['incident_time']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo getStatusClass($incident['status']); ?>">
                                                <?php echo ucfirst($incident['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline" onclick="viewIncident(<?php echo $incident['id']; ?>)">
                                                <i data-lucide="eye"></i>
                                            </button>
                                            <?php if ($incident['status'] !== 'closed'): ?>
                                            <div class="dropdown" style="display: inline-block;">
                                                <button class="btn btn-sm btn-primary dropdown-toggle" onclick="toggleDropdown(<?php echo $incident['id']; ?>)">
                                                    <i data-lucide="edit"></i>
                                                </button>
                                                <div class="dropdown-menu" id="dropdown-<?php echo $incident['id']; ?>">
                                                    <?php if ($incident['status'] === 'reported'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="incident_id" value="<?php echo $incident['id']; ?>">
                                                        <input type="hidden" name="status" value="investigating">
                                                        <button type="submit" class="dropdown-item">Start Investigation</button>
                                                    </form>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($incident['status'] === 'investigating'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="incident_id" value="<?php echo $incident['id']; ?>">
                                                        <input type="hidden" name="status" value="resolved">
                                                        <button type="submit" class="dropdown-item">Mark Resolved</button>
                                                    </form>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($incident['status'] === 'resolved'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="incident_id" value="<?php echo $incident['id']; ?>">
                                                        <input type="hidden" name="status" value="closed">
                                                        <button type="submit" class="dropdown-item">Close Incident</button>
                                                    </form>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
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
    
    .dropdown {
        position: relative;
    }
    
    .dropdown-menu {
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        display: none;
        z-index: 1000;
        min-width: 150px;
    }
    
    .dropdown-menu.show {
        display: block;
    }
    
    .dropdown-item {
        display: block;
        width: 100%;
        padding: 0.5rem 1rem;
        border: none;
        background: none;
        text-align: left;
        cursor: pointer;
        color: #333;
    }
    
    .dropdown-item:hover {
        background-color: #f8f9fa;
    }
    </style>

    <script>
        lucide.createIcons();
        
        function viewIncident(id) {
            window.location.href = 'view-incident.php?id=' + id;
        }
        
        function toggleDropdown(id) {
            const dropdown = document.getElementById('dropdown-' + id);
            dropdown.classList.toggle('show');
        }
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        });
        
        // Filter functionality
        document.getElementById('statusFilter').addEventListener('change', filterTable);
        document.getElementById('severityFilter').addEventListener('change', filterTable);
        
        function filterTable() {
            const statusFilter = document.getElementById('statusFilter').value;
            const severityFilter = document.getElementById('severityFilter').value;
            const rows = document.querySelectorAll('#incidentsTable tbody tr');
            
            rows.forEach(row => {
                const status = row.dataset.status;
                const severity = row.dataset.severity;
                
                const statusMatch = !statusFilter || status === statusFilter;
                const severityMatch = !severityFilter || severity === severityFilter;
                
                if (statusMatch && severityMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>

<?php
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