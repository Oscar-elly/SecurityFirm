<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('organization');

$incident_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$incident_id) {
    $_SESSION['error'] = 'Invalid incident ID';
    redirect('incidents.php');
}

// Get organization ID for the current user
$org_query = "SELECT id FROM organizations WHERE user_id = ?";
$organization = executeQuery($org_query, [$_SESSION['user_id']], ['single' => true]);

if (!$organization) {
    $_SESSION['error'] = 'Organization not found';
    redirect('incidents.php');
}

// Get incident details - updated to check organization access
$query = "SELECT i.*, l.name as location_name, l.address as location_address,
                 o.name as organization_name, u.name as reporter_name
          FROM incidents i 
          JOIN locations l ON i.location_id = l.id 
          JOIN organizations o ON l.organization_id = o.id 
          JOIN users u ON i.reported_by = u.id 
          WHERE i.id = ? AND l.organization_id = ?";
$incident = executeQuery($query, [$incident_id, $organization['id']], ['single' => true]);

if (!$incident) {
    $_SESSION['error'] = 'Incident not found or you do not have access to it';
    redirect('incidents.php');
}

// Get incident media - modified to handle base64 encoded images
$query = "SELECT * FROM incident_media WHERE incident_id = ?";
$media = executeQuery($query, [$incident_id]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident Details | <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/guard-dashboard.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/organization-sidebar.php'; ?>
        
        <main class="main-content">
            <?php include '../includes/top-nav.php'; ?>
            
            <div class="dashboard-content">
                <div class="dashboard-header">
                    <h1>Incident Report</h1>
                    <div class="dashboard-actions">
                        <a href="incidents.php" class="btn btn-outline">
                            <i data-lucide="arrow-left"></i> Back to Incidents
                        </a>
                        <button class="btn btn-secondary" onclick="printReport()">
                            <i data-lucide="printer"></i> Print Report
                        </button>
                    </div>
                </div>
                
                <!-- Incident Details Card -->
                <div class="card">
                    <div class="card-header">
                        <h2><?php echo sanitize($incident['title']); ?></h2>
                        <div class="incident-badges">
                            <span class="badge badge-<?php echo getSeverityClass($incident['severity']); ?>">
                                <?php echo ucfirst($incident['severity']); ?> Priority
                            </span>
                            <span class="badge badge-<?php echo getStatusClass($incident['status']); ?>">
                                <?php echo ucfirst($incident['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="incident-details">
                            <div class="detail-section">
                                <h3>Incident Information</h3>
                                <div class="detail-grid">
                                    <div class="detail-item">
                                        <label>Incident ID:</label>
                                        <span>#<?php echo str_pad($incident['id'], 6, '0', STR_PAD_LEFT); ?></span>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <label>Date & Time:</label>
                                        <span><?php echo formatDate($incident['incident_time'], 'd M Y, h:i A'); ?></span>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <label>Reported By:</label>
                                        <span><?php echo sanitize($incident['reporter_name']); ?></span>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <label>Report Date:</label>
                                        <span><?php echo formatDate($incident['created_at'], 'd M Y, h:i A'); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="detail-section">
                                <h3>Location Details</h3>
                                <div class="detail-grid">
                                    <!-- <div class="detail-item">
                                        <label>Organization:</label>
                                        <span><?php echo sanitize($incident['organization_name']); ?></span>
                                    </div> -->
                                    
                                    <div class="detail-item">
                                        <label>Location:</label>
                                        <span><?php echo sanitize($incident['location_name']); ?></span>
                                    </div>
                                    
                                    <div class="detail-item full-width">
                                        <label>Address:</label>
                                        <span><?php echo sanitize($incident['location_address']); ?></span>
                                    </div>
                                    
                                    <?php if ($incident['latitude'] && $incident['longitude']): ?>
                                    <div class="detail-item">
                                        <label>GPS Coordinates:</label>
                                        <span>
                                            <?php echo $incident['latitude']; ?>, <?php echo $incident['longitude']; ?>
                                            <button class="btn btn-xs btn-outline" onclick="viewOnMap()">
                                                <i data-lucide="map-pin"></i> View on Map
                                            </button>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="detail-section">
                                <h3>Incident Description</h3>
                                <div class="description-content">
                                    <?php echo nl2br(sanitize($incident['description'])); ?>
                                </div>
                            </div>
                            
                            <?php if (!empty($media)): ?>
                            <div class="detail-section">
                                <h3>Attached Media</h3>
                                <div class="media-gallery">
                                    <?php foreach ($media as $file): ?>
                                    <div class="media-item">
                                        <?php if (strpos($file['file_type'], 'image') !== false): ?>
                                            <?php if (strpos($file['file_path'], 'data:image') === 0): ?>
                                                <!-- Display base64 encoded image -->
                                                <img src="<?php echo $file['file_path']; ?>" 
                                                     alt="Incident media" 
                                                     onclick="openMediaModal(this.src)">
                                            <?php else: ?>
                                                <!-- Display regular file path image -->
                                                <img src="../../uploads/<?php echo $file['file_path']; ?>" 
                                                     alt="Incident media" 
                                                     onclick="openMediaModal(this.src)">
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="file-item">
                                                <i data-lucide="file"></i>
                                                <span><?php echo basename($file['file_path']); ?></span>
                                                <?php if (strpos($file['file_path'], 'data:') === 0): ?>
                                                    <a href="<?php echo $file['file_path']; ?>" 
                                                       download="incident_media_<?php echo $file['id']; ?>" 
                                                       class="btn btn-xs btn-outline">
                                                        <i data-lucide="download"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="../../uploads/<?php echo $file['file_path']; ?>" 
                                                       target="_blank" class="btn btn-xs btn-outline">
                                                        <i data-lucide="download"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($file['description'])): ?>
                                            <div class="media-description">
                                                <?php echo sanitize($file['description']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Status Timeline -->
                <div class="card">
                    <div class="card-header">
                        <h2>Status Timeline</h2>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item completed">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h4>Incident Reported</h4>
                                    <p><?php echo formatDate($incident['created_at'], 'd M Y, h:i A'); ?></p>
                                    <small>Reported by <?php echo sanitize($incident['reporter_name']); ?></small>
                                </div>
                            </div>
                            
                            <?php if ($incident['status'] !== 'reported'): ?>
                            <div class="timeline-item completed">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h4>Under Investigation</h4>
                                    <p><?php echo formatDate($incident['updated_at'], 'd M Y, h:i A'); ?></p>
                                    <small>Investigation started by security team</small>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (in_array($incident['status'], ['resolved', 'closed'])): ?>
                            <div class="timeline-item completed">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h4>Incident Resolved</h4>
                                    <p><?php echo formatDate($incident['updated_at'], 'd M Y, h:i A'); ?></p>
                                    <small>Incident has been resolved</small>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($incident['status'] === 'closed'): ?>
                            <div class="timeline-item completed">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h4>Case Closed</h4>
                                    <p><?php echo formatDate($incident['updated_at'], 'd M Y, h:i A'); ?></p>
                                    <small>Incident case has been officially closed</small>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Media Modal -->
    <div id="mediaModal" class="modal" style="display: none;">
        <div class="modal-content media-modal">
            <div class="modal-header">
                <h3>Incident Media</h3>
                <button onclick="closeMediaModal()" class="btn btn-sm btn-outline">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="modal-body">
                <img id="modalImage" src="" alt="Incident media">
            </div>
        </div>
    </div>

    <style>
    .incident-badges {
        display: flex;
        gap: 0.5rem;
    }
    
    .incident-details {
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }
    
    .detail-section h3 {
        margin-bottom: 1rem;
        color: var(--primary-color);
        border-bottom: 2px solid var(--primary-color);
        padding-bottom: 0.5rem;
    }
    
    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
    }
    
    .detail-item {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .detail-item.full-width {
        grid-column: 1 / -1;
    }
    
    .detail-item label {
        font-weight: 600;
        color: #333;
        margin-bottom: 0;
    }
    
    .detail-item span {
        color: #666;
    }
    
    .description-content {
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
        line-height: 1.6;
        color: #555;
    }
    
    .media-gallery {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 1rem;
    }
    
    .media-item {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .media-item img {
        width: 100%;
        height: 150px;
        object-fit: cover;
        border-radius: 8px;
        cursor: pointer;
        transition: transform 0.2s ease;
    }
    
    .media-item img:hover {
        transform: scale(1.05);
    }
    
    .file-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .media-description {
        font-size: 0.8rem;
        color: #666;
        word-break: break-word;
    }
    
    .timeline {
        position: relative;
        padding-left: 2rem;
    }
    
    .timeline::before {
        content: '';
        position: absolute;
        left: 1rem;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e0e0e0;
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 2rem;
    }
    
    .timeline-marker {
        position: absolute;
        left: -2rem;
        top: 0;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #e0e0e0;
        border: 3px solid white;
    }
    
    .timeline-item.completed .timeline-marker {
        background: var(--success-color);
    }
    
    .timeline-content h4 {
        margin: 0 0 0.25rem 0;
        color: #333;
    }
    
    .timeline-content p {
        margin: 0 0 0.25rem 0;
        color: #666;
    }
    
    .timeline-content small {
        color: #999;
    }
    
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.8);
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .media-modal {
        max-width: 90vw;
        max-height: 90vh;
    }
    
    .media-modal .modal-body {
        text-align: center;
    }
    
    .media-modal img {
        max-width: 100%;
        max-height: 70vh;
        object-fit: contain;
    }
    
    .btn-xs {
        padding: 0.125rem 0.25rem;
        font-size: 0.75rem;
    }
    </style>

    <script>
        lucide.createIcons();
        
        function viewOnMap() {
            const lat = <?php echo $incident['latitude'] ?? 'null'; ?>;
            const lng = <?php echo $incident['longitude'] ?? 'null'; ?>;
            if (lat && lng) {
                window.open(`https://maps.google.com/maps?q=${lat},${lng}&z=15`, '_blank');
            }
        }
        
        function openMediaModal(src) {
            document.getElementById('modalImage').src = src;
            document.getElementById('mediaModal').style.display = 'flex';
        }
        
        function closeMediaModal() {
            document.getElementById('mediaModal').style.display = 'none';
        }
        
        function printReport() {
            window.print();
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