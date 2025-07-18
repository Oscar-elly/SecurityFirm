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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_location':
                $name = sanitize($_POST['name']);
                $address = sanitize($_POST['address']);
                $latitude = filter_input(INPUT_POST, 'latitude', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                $longitude = filter_input(INPUT_POST, 'longitude', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                $contact_person = sanitize($_POST['contact_person']);
                $contact_phone = sanitize($_POST['contact_phone']);
                
                $query = "INSERT INTO locations (organization_id, name, address, latitude, longitude, contact_person, contact_phone, status) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, 'active')";
                $result = executeQuery($query, [$organization['id'], $name, $address, $latitude, $longitude, $contact_person, $contact_phone]);
                
                if ($result) {
                    $_SESSION['success'] = 'Location added successfully';
                } else {
                    $_SESSION['error'] = 'Failed to add location';
                }
                break;
                
            case 'update_status':
                $location_id = (int)$_POST['location_id'];
                $status = sanitize($_POST['status']);
                
                $query = "UPDATE locations SET status = ? WHERE id = ? AND organization_id = ?";
                $result = executeQuery($query, [$status, $location_id, $organization['id']]);
                
                if ($result) {
                    $_SESSION['success'] = 'Location status updated successfully';
                } else {
                    $_SESSION['error'] = 'Failed to update location status';
                }
                break;
        }
        redirect($_SERVER['PHP_SELF']);
    }
}

// Get all locations for this organization
$query = "SELECT * FROM locations WHERE organization_id = ? ORDER BY created_at DESC";
$locations = executeQuery($query, [$organization['id']]);
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
                    <h1>Locations Management</h1>
                    <button class="btn btn-primary" onclick="openAddLocationModal()">
                        <i data-lucide="plus"></i> Add New Location
                    </button>
                </div>
                
                <?php echo flashMessage('success'); ?>
                <?php echo flashMessage('error'); ?>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Your Locations</h2>
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
                                            <th>Contact Phone</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($locations as $location): ?>
                                        <tr>
                                            <td><?php echo sanitize($location['name']); ?></td>
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
                                                <button class="btn btn-sm btn-warning" onclick="editLocation(<?php echo $location['id']; ?>)">
                                                    <i data-lucide="edit"></i>
                                                </button>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="location_id" value="<?php echo $location['id']; ?>">
                                                    <input type="hidden" name="status" value="<?php echo $location['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                                    <button type="submit" class="btn btn-sm <?php echo $location['status'] === 'active' ? 'btn-danger' : 'btn-success'; ?>">
                                                        <i data-lucide="<?php echo $location['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <div class="no-duty-icon">
                                    <i data-lucide="map-pin"></i>
                                </div>
                                <p>No locations added yet. Add your first location to get started.</p>
                                <button class="btn btn-primary" onclick="openAddLocationModal()">
                                    <i data-lucide="plus"></i> Add Location
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Location Modal -->
    <div id="addLocationModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Location</h3>
                <button onclick="closeAddLocationModal()" class="btn btn-sm btn-outline">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_location">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Location Name *</label>
                        <input type="text" id="name" name="name" required placeholder="e.g., Main Office, Warehouse A">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address *</label>
                        <textarea id="address" name="address" required rows="3" placeholder="Full address including city and postal code"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Coordinates (Optional)</label>
                        <div style="display: flex; gap: 1rem; align-items: center;">
                            <input type="number" id="latitude" name="latitude" step="any" placeholder="Latitude">
                            <input type="number" id="longitude" name="longitude" step="any" placeholder="Longitude">
                            <button type="button" id="getLocationBtn" class="btn btn-outline">
                                <i data-lucide="map-pin"></i> Get Current
                            </button>
                        </div>
                        <small class="form-help">Coordinates help with accurate guard tracking and incident reporting</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_person">Contact Person *</label>
                        <input type="text" id="contact_person" name="contact_person" required placeholder="Site manager or responsible person">
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_phone">Contact Phone *</label>
                        <input type="tel" id="contact_phone" name="contact_phone" required placeholder="+254-XXX-XXXXXX">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeAddLocationModal()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Location</button>
                </div>
            </form>
        </div>
    </div>

    <style>
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .modal-content {
        background: white;
        border-radius: 8px;
        width: 90%;
        max-width: 600px;
        max-height: 90vh;
        overflow-y: auto;
    }
    
    .modal-header {
        padding: 1rem;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-body {
        padding: 1rem;
    }
    
    .modal-footer {
        padding: 1rem;
        border-top: 1px solid #eee;
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
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
    
    .form-help {
        display: block;
        margin-top: 0.25rem;
        font-size: 0.875rem;
        color: #666;
    }
    </style>

    <script>
        lucide.createIcons();
        
        function openAddLocationModal() {
            document.getElementById('addLocationModal').style.display = 'flex';
        }
        
        function closeAddLocationModal() {
            document.getElementById('addLocationModal').style.display = 'none';
        }
        
        function viewLocation(id) {
            window.location.href = 'view-location.php?id=' + id;
        }
        
        function editLocation(id) {
            window.location.href = 'edit-location.php?id=' + id;
        }
        
        // Get current location
        document.getElementById('getLocationBtn').addEventListener('click', function() {
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader"></i> Getting...';
            
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        document.getElementById('latitude').value = position.coords.latitude.toFixed(6);
                        document.getElementById('longitude').value = position.coords.longitude.toFixed(6);
                        
                        btn.disabled = false;
                        btn.innerHTML = '<i data-lucide="check"></i> Got Location';
                        lucide.createIcons();
                        
                        setTimeout(() => {
                            btn.innerHTML = '<i data-lucide="map-pin"></i> Get Current';
                            lucide.createIcons();
                        }, 2000);
                    },
                    function(error) {
                        alert('Failed to get location: ' + error.message);
                        btn.disabled = false;
                        btn.innerHTML = '<i data-lucide="map-pin"></i> Get Current';
                        lucide.createIcons();
                    }
                );
            } else {
                alert('Geolocation is not supported by this browser');
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="map-pin"></i> Get Current';
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>