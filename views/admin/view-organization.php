<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('admin');

$org_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$org_id) {
    $_SESSION['error'] = 'Invalid organization ID';
    redirect('organizations.php');
}

// Get organization information
$query = "SELECT u.*, o.* FROM users u 
          JOIN organizations o ON u.id = o.user_id 
          WHERE u.id = ? AND u.role = 'organization'";
$organization = executeQuery($query, [$org_id], ['single' => true]);

if (!$organization) {
    $_SESSION['error'] = 'Organization not found';
    redirect('organizations.php');
}

// Get organization locations
$query = "SELECT * FROM locations WHERE organization_id = ? ORDER BY created_at DESC";
$locations = executeQuery($query, [$organization['id']]);

// Get guard requests
$query = "SELECT gr.*, l.name as location_name, s.name as shift_name 
          FROM guard_requests gr 
          JOIN locations l ON gr.location_id = l.id 
          JOIN shifts s ON gr.shift_id = s.id 
          WHERE gr.organization_id = ? 
          ORDER BY gr.created_at DESC";
$requests = executeQuery($query, [$organization['id']]);

// Get incidents at organization locations
$query = "SELECT i.*, l.name as location_name, u.name as reporter_name 
          FROM incidents i 
          JOIN locations l ON i.location_id = l.id 
          JOIN users u ON i.reported_by = u.id 
          WHERE l.organization_id = ? 
          ORDER BY i.incident_time DESC 
          LIMIT 10";
$incidents = executeQuery($query, [$organization['id']]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Profile - <?php echo sanitize($organization['name']); ?> | <?php echo SITE_NAME; ?></title>
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
                    <h1>Organization Profile</h1>
                    <div class="dashboard-actions">
                        <a href="organizations.php" class="btn btn-outline">
                            <i data-lucide="arrow-left"></i> Back to Organizations
                        </a>
                        <button class="btn btn-warning" onclick="editOrganization(<?php echo $organization['user_id']; ?>)">
                            <i data-lucide="edit"></i> Edit Profile
                        </button>
                    </div>
                </div>
                
                <!-- Organization Profile Card -->
                <div class="card">
                    <div class="card-body">
                        <div class="org-profile">
                            <div class="org-avatar-large">
                                <?php echo getInitials($organization['name']); ?>
                            </div>
                            <div class="org-info">
                                <h2><?php echo sanitize($organization['name']); ?></h2>
                                <p class="org-industry"><?php echo sanitize($organization['industry']); ?></p>
                                <span class="badge badge-<?php echo $organization['status'] === 'active' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($organization['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="org-details-grid">
                            <div class="detail-section">
                                <h3>Contact Information</h3>
                                <div class="detail-item">
                                    <strong>Contact Person:</strong> <?php echo sanitize($organization['contact_person']); ?>
                                </div>
                                <div class="detail-item">
                                    <strong>Email:</strong> <?php echo sanitize($organization['email']); ?>
                                </div>
                                <div class="detail-item">
                                    <strong>Phone:</strong> <?php echo sanitize($organization['contact_phone']); ?>
                                </div>
                                <div class="detail-item">
                                    <strong>Address:</strong> <?php echo sanitize($organization['address']); ?>
                                </div>
                            </div>
                            
                            <div class="detail-section">
                                <h3>Contract Details</h3>
                                <div class="detail-item">
                                    <strong>Contract Start:</strong> 
                                    <?php echo $organization['contract_start_date'] ? formatDate($organization['contract_start_date'], 'd M Y') : 'Not set'; ?>
                                </div>
                                <div class="detail-item">
                                    <strong>Contract End:</strong> 
                                    <?php echo $organization['contract_end_date'] ? formatDate($organization['contract_end_date'], 'd M Y') : 'Not set'; ?>
                                </div>
                                <div class="detail-item">
                                    <strong>Account Created:</strong> <?php echo formatDate($organization['created_at'], 'd M Y'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Statistics -->
                <div class="stats-cards">
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="map-pin"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo count($locations); ?></h3>
                            <p>Locations</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="file-text"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo count($requests); ?></h3>
                            <p>Guard Requests</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="alert-triangle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo count($incidents); ?></h3>
                            <p>Incidents</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="check-circle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo count(array_filter($locations, function($l) { return $l['status'] === 'active'; })); ?></h3>
                            <p>Active Locations</p>
                        </div>
                    </div>
                </div>
                
                <!-- Locations -->
                <div class="card">
                    <div class="card-header">
                        <h2>Locations</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($locations)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Location Name</th>
                                            <th>Address</th>
                                            <th>Contact Person</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($locations as $location): ?>
                                        <tr>
                                            <td><?php echo sanitize($location['name']); ?></td>
                                            <td><?php echo sanitize($location['address']); ?></td>
                                            <td><?php echo sanitize($location['contact_person']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $location['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                    <?php echo ucfirst($location['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatDate($location['created_at'], 'd M Y'); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="no-data">No locations found for this organization.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Guard Requests -->
                <div class="card">
                    <div class="card-header">
                        <h2>Recent Guard Requests</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($requests)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Location</th>
                                            <th>Guards Needed</th>
                                            <th>Shift</th>
                                            <th>Start Date</th>
                                            <th>Status</th>
                                            <th>Requested</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($requests as $request): ?>
                                        <tr>
                                            <td><?php echo sanitize($request['location_name']); ?></td>
                                            <td><?php echo $request['number_of_guards']; ?></td>
                                            <td><?php echo sanitize($request['shift_name']); ?></td>
                                            <td><?php echo formatDate($request['start_date'], 'd M Y'); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo getRequestStatusClass($request['status']); ?>">
                                                    <?php echo ucfirst($request['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatDate($request['created_at'], 'd M Y'); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="no-data">No guard requests found for this organization.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Incidents -->
                <div class="card">
                    <div class="card-header">
                        <h2>Recent Incidents</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($incidents)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Location</th>
                                            <th>Severity</th>
                                            <th>Reporter</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($incidents as $incident): ?>
                                        <tr>
                                            <td><?php echo sanitize($incident['title']); ?></td>
                                            <td><?php echo sanitize($incident['location_name']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo getSeverityClass($incident['severity']); ?>">
                                                    <?php echo ucfirst($incident['severity']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo sanitize($incident['reporter_name']); ?></td>
                                            <td><?php echo formatDate($incident['incident_time'], 'd M Y'); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo getStatusClass($incident['status']); ?>">
                                                    <?php echo ucfirst($incident['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="no-data">No incidents found for this organization.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
    .org-profile {
        display: flex;
        align-items: center;
        gap: 2rem;
        margin-bottom: 2rem;
    }
    
    .org-avatar-large {
        width: 100px;
        height: 100px;
        background: var(--primary-color);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: 600;
    }
    
    .org-info h2 {
        margin: 0 0 0.5rem 0;
        font-size: 2rem;
    }
    
    .org-industry {
        margin: 0 0 1rem 0;
        color: #666;
        font-size: 1.1rem;
    }
    
    .org-details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
    }
    
    .detail-section h3 {
        margin-bottom: 1rem;
        color: var(--primary-color);
        border-bottom: 2px solid var(--primary-color);
        padding-bottom: 0.5rem;
    }
    
    .detail-item {
        margin-bottom: 0.75rem;
        padding: 0.5rem 0;
        border-bottom: 1px solid #f0f0f0;
    }
    </style>

    <script>
        lucide.createIcons();
        
        function editOrganization(id) {
            window.location.href = 'edit-organization.php?id=' + id;
        }
    </script>
</body>
</html>

<?php

function getRequestStatusClass($status) {
    switch ($status) {
        case 'pending': return 'warning';
        case 'approved': return 'success';
        case 'rejected': return 'danger';
        case 'completed': return 'secondary';
        default: return 'secondary';
    }
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