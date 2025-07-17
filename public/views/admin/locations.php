<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('admin');

// Get all locations with organization information
$query = "SELECT l.*, o.name as organization_name, o.contact_person, o.contact_phone as org_phone
          FROM locations l 
          JOIN organizations o ON l.organization_id = o.id 
          ORDER BY o.name, l.name";
$locations = executeQuery($query);

// Get organizations for filtering
$orgQuery = "SELECT id, name FROM organizations ORDER BY name";
$organizations = executeQuery($orgQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Locations Management | <?php echo SITE_NAME; ?></title>
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
                    <h1>Locations Management</h1>
                    <div class="dashboard-actions">
                        <select id="orgFilter" class="form-control" style="width: auto; display: inline-block;">
                            <option value="">All Organizations</option>
                            <?php foreach ($organizations as $org): ?>
                            <option value="<?php echo $org['id']; ?>"><?php echo sanitize($org['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-primary" onclick="viewMap()">
                            <i data-lucide="map"></i> View Map
                        </button>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2>All Locations</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="locationsTable">
                                <thead>
                                    <tr>
                                        <th>Location Name</th>
                                        <th>Organization</th>
                                        <th>Address</th>
                                        <th>Contact Person</th>
                                        <th>Contact Phone</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($locations as $location): ?>
                                    <tr data-org="<?php echo $location['organization_id']; ?>">
                                        <td>
                                            <strong><?php echo sanitize($location['name']); ?></strong>
                                            <?php if ($location['latitude'] && $location['longitude']): ?>
                                            <br><small class="text-muted">
                                                <i data-lucide="map-pin"></i> 
                                                <?php echo $location['latitude']; ?>, <?php echo $location['longitude']; ?>
                                            </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo sanitize($location['organization_name']); ?>
                                            <br><small class="text-muted"><?php echo sanitize($location['contact_person']); ?></small>
                                        </td>
                                        <td><?php echo sanitize($location['address']); ?></td>
                                        <td><?php echo sanitize($location['contact_person']); ?></td>
                                        <td><?php echo sanitize($location['contact_phone']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $location['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($location['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline" onclick="viewLocation(<?php echo $location['id']; ?>)">
                                                <i data-lucide="eye"></i>
                                            </button>
                                            <?php if ($location['latitude'] && $location['longitude']): ?>
                                            <button class="btn btn-sm btn-secondary" onclick="viewOnMap(<?php echo $location['latitude']; ?>, <?php echo $location['longitude']; ?>)">
                                                <i data-lucide="map-pin"></i>
                                            </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-primary" onclick="viewGuards(<?php echo $location['id']; ?>)">
                                                <i data-lucide="shield"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Location Statistics -->
                <div class="card">
                    <div class="card-header">
                        <h2>Location Statistics</h2>
                    </div>
                    <div class="card-body">
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i data-lucide="map-pin"></i>
                                </div>
                                <div class="stat-details">
                                    <h3><?php echo count($locations); ?></h3>
                                    <p>Total Locations</p>
                                </div>
                            </div>
                            
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i data-lucide="check-circle"></i>
                                </div>
                                <div class="stat-details">
                                    <h3><?php echo count(array_filter($locations, function($l) { return $l['status'] === 'active'; })); ?></h3>
                                    <p>Active Locations</p>
                                </div>
                            </div>
                            
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i data-lucide="building-2"></i>
                                </div>
                                <div class="stat-details">
                                    <h3><?php echo count($organizations); ?></h3>
                                    <p>Organizations</p>
                                </div>
                            </div>
                            
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i data-lucide="navigation"></i>
                                </div>
                                <div class="stat-details">
                                    <h3><?php echo count(array_filter($locations, function($l) { return $l['latitude'] && $l['longitude']; })); ?></h3>
                                    <p>GPS Enabled</p>
                                </div>
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
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }
    
    .stat-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .stat-icon {
        width: 48px;
        height: 48px;
        background: var(--primary-color);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .stat-details h3 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 600;
    }
    
    .stat-details p {
        margin: 0;
        color: #666;
        font-size: 0.875rem;
    }
    </style>

    <script>
        lucide.createIcons();
        
        // Organization filter
        document.getElementById('orgFilter').addEventListener('change', function() {
            const selectedOrg = this.value;
            const rows = document.querySelectorAll('#locationsTable tbody tr');
            
            rows.forEach(row => {
                const orgId = row.dataset.org;
                if (!selectedOrg || orgId === selectedOrg) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        function viewLocation(id) {
            window.location.href = 'view-location.php?id=' + id;
        }
        
        function viewOnMap(lat, lng) {
            window.open(`https://maps.google.com/maps?q=${lat},${lng}&z=15`, '_blank');
        }
        
        function viewGuards(locationId) {
            window.location.href = 'location-guards.php?location_id=' + locationId;
        }
        
        function viewMap() {
            window.location.href = 'locations-map.php';
        }
    </script>
</body>
</html>