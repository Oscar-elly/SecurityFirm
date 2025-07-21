<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';
require_once '../../vendor/autoload.php'; // Require Composer's autoload for Dompdf

// Verify user is logged in and has organization role
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.0 401 Unauthorized');
    die('Unauthorized access');
}

requireRole('organization');

$incident_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$incident_id) {
    header('HTTP/1.0 400 Bad Request');
    die('Invalid incident ID');
}


// helper functions 
function getSeverityClass1($severity) {
    switch (strtolower($severity)) {
        case 'low': return 'success';
        case 'medium': return 'warning';
        case 'high': 
        case 'critical': return 'danger';
        default: return 'secondary';
    }
}

function getStatusClass1($status) {
    switch (strtolower($status)) {
        case 'reported': return 'warning';
        case 'investigating': return 'primary';
        case 'resolved': return 'success';
        case 'closed': return 'secondary';
        default: return 'secondary';
    }
}

function formatDate1($date, $format = 'd M Y, h:i A') {
    if (empty($date) || $date == '0000-00-00 00:00:00') {
        return 'N/A';
    }
    $datetime = new DateTime($date);
    return $datetime->format($format);
}

function sanitize1($input) {
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

// Rest of your existing code...

// Get organization ID for the current user
$org_query = "SELECT id FROM organizations WHERE user_id = ?";
$organization = executeQuery($org_query, [$_SESSION['user_id']], ['single' => true]);

if (!$organization) {
    header('HTTP/1.0 403 Forbidden');
    die('Organization not found');
}

// Get incident details with organization check
$query = "SELECT i.*, l.name as location_name, l.address as location_address,
                 o.name as organization_name, u.name as reporter_name
          FROM incidents i 
          JOIN locations l ON i.location_id = l.id 
          JOIN organizations o ON l.organization_id = o.id 
          JOIN users u ON i.reported_by = u.id 
          WHERE i.id = ? AND l.organization_id = ?";
$incident = executeQuery($query, [$incident_id, $organization['id']], ['single' => true]);

if (!$incident) {
    header('HTTP/1.0 404 Not Found');
    die('Incident not found or access denied');
}

// Get incident media
$query = "SELECT * FROM incident_media WHERE incident_id = ?";
$media = executeQuery($query, [$incident_id]);

// Create PDF using Dompdf
$dompdf = new \Dompdf\Dompdf();
$dompdf->set_option('isHtml5ParserEnabled', true);
$dompdf->set_option('isRemoteEnabled', true);

// HTML content for PDF
ob_start(); // Start output buffering
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Incident Report - #<?php echo str_pad($incident['id'], 6, '0', STR_PAD_LEFT); ?></title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; line-height: 1.6; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #333; font-size: 20px; }
        .header p { margin: 5px 0 0; color: #666; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; margin: 0 5px; }
        .badge-success { background-color: #d4edda; color: #155724; }
        .badge-warning { background-color: #fff3cd; color: #856404; }
        .badge-danger { background-color: #f8d7da; color: #721c24; }
        .badge-secondary { background-color: #e2e3e5; color: #383d41; }
        .badge-primary { background-color: #cce5ff; color: #004085; }
        .detail-section { margin-bottom: 15px; page-break-inside: avoid; }
        .detail-section h3 { color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 5px; font-size: 14px; margin: 10px 0; }
        .detail-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; margin-bottom: 10px; }
        .detail-item { margin-bottom: 6px; }
        .detail-item label { font-weight: bold; display: block; font-size: 11px; }
        .detail-item span { font-size: 11px; }
        .description-content { background: #f9f9f9; padding: 10px; border-radius: 5px; margin-top: 5px; font-size: 11px; }
        .media-gallery { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 10px; }
        .media-item { page-break-inside: avoid; }
        .media-item img { max-width: 150px; max-height: 150px; border: 1px solid #ddd; }
        .file-item { display: flex; align-items: center; gap: 5px; font-size: 11px; }
        .timeline { position: relative; padding-left: 20px; margin-top: 15px; }
        .timeline::before { content: ""; position: absolute; left: 10px; top: 0; bottom: 0; width: 2px; background: #e0e0e0; }
        .timeline-item { position: relative; margin-bottom: 12px; page-break-inside: avoid; }
        .timeline-marker { position: absolute; left: -20px; top: 5px; width: 10px; height: 10px; border-radius: 50%; background: #e0e0e0; border: 2px solid white; }
        .timeline-item.completed .timeline-marker { background: #28a745; }
        .timeline-content h4 { margin: 0 0 2px 0; color: #333; font-size: 12px; }
        .timeline-content p { margin: 0 0 2px 0; color: #666; font-size: 11px; }
        .timeline-content small { color: #999; font-size: 10px; }
        .footer { margin-top: 20px; text-align: right; font-size: 10px; color: #777; border-top: 1px solid #eee; padding-top: 5px; }
        .status-badges { display: flex; justify-content: space-between; margin-bottom: 15px; }
        .full-width { grid-column: span 2; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Incident Report - #<?php echo str_pad($incident['id'], 6, '0', STR_PAD_LEFT); ?></h1>
        <p>Generated on <?php echo date('F j, Y'); ?> by <?php echo SITE_NAME; ?></p>
    </div>
    
    <div class="incident-info">
        <div class="status-badges">
            <div>
                <span class="badge badge-<?php echo getSeverityClass1($incident['severity']); ?>">
                    <?php echo ucfirst($incident['severity']); ?> Priority
                </span>
            </div>
            <div>
                <span class="badge badge-<?php echo getStatusClass1($incident['status']); ?>">
                    <?php echo ucfirst($incident['status']); ?>
                </span>
            </div>
        </div>
        
        <div class="detail-section">
            <h3>Incident Information</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>Incident ID:</label>
                    <span>#<?php echo str_pad($incident['id'], 6, '0', STR_PAD_LEFT); ?></span>
                </div>
                
                <div class="detail-item">
                    <label>Date & Time:</label>
                    <span><?php echo formatDate1($incident['incident_time'], 'd M Y, h:i A'); ?></span>
                </div>
                
                <div class="detail-item">
                    <label>Reported By:</label>
                    <span><?php echo sanitize1($incident['reporter_name']); ?></span>
                </div>
                
                <div class="detail-item">
                    <label>Report Date:</label>
                    <span><?php echo formatDate1($incident['created_at'], 'd M Y, h:i A'); ?></span>
                </div>
            </div>
        </div>
        
        <div class="detail-section">
            <h3>Location Details</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>Organization:</label>
                    <span><?php echo sanitize1($incident['organization_name']); ?></span>
                </div>
                
                <div class="detail-item">
                    <label>Location:</label>
                    <span><?php echo sanitize1($incident['location_name']); ?></span>
                </div>
                
                <div class="detail-item full-width">
                    <label>Address:</label>
                    <span><?php echo sanitize1($incident['location_address']); ?></span>
                </div>
                
                <?php if ($incident['latitude'] && $incident['longitude']): ?>
                <div class="detail-item full-width">
                    <label>GPS Coordinates:</label>
                    <span><?php echo $incident['latitude']; ?>, <?php echo $incident['longitude']; ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="detail-section">
            <h3>Incident Description</h3>
            <div class="description-content">
                <?php echo nl2br(sanitize1($incident['description'])); ?>
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
                            <img src="<?php echo $file['file_path']; ?>" alt="Incident media">
                        <?php else: ?>
                            <img src="../../uploads/<?php echo $file['file_path']; ?>" alt="Incident media">
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="file-item">
                            <span><?php echo basename($file['file_path']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($file['description'])): ?>
                        <div class="media-description"><?php echo sanitize1($file['description']); ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="detail-section">
            <h3>Status Timeline</h3>
            <div class="timeline">
                <div class="timeline-item completed">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <h4>Incident Reported</h4>
                        <p><?php echo formatDate1($incident['created_at'], 'd M Y, h:i A'); ?></p>
                        <small>Reported by <?php echo sanitize1($incident['reporter_name']); ?></small>
                    </div>
                </div>
                
                <?php if ($incident['status'] !== 'reported'): ?>
                <div class="timeline-item completed">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <h4>Under Investigation</h4>
                        <p><?php echo formatDate1($incident['updated_at'], 'd M Y, h:i A'); ?></p>
                        <small>Investigation started by security team</small>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (in_array($incident['status'], ['resolved', 'closed'])): ?>
                <div class="timeline-item completed">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <h4>Incident Resolved</h4>
                        <p><?php echo formatDate1($incident['updated_at'], 'd M Y, h:i A'); ?></p>
                        <small>Incident has been resolved</small>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($incident['status'] === 'closed'): ?>
                <div class="timeline-item completed">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <h4>Case Closed</h4>
                        <p><?php echo formatDate1($incident['updated_at'], 'd M Y, h:i A'); ?></p>
                        <small>Incident case has been officially closed</small>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="footer">
            <p>Confidential Document - <?php echo SITE_NAME; ?> - <?php echo date('Y'); ?></p>
        </div>
    </div>
</body>
</html>
<?php
$html = ob_get_clean(); // Get the buffered HTML

// Load HTML to Dompdf
$dompdf->loadHtml($html);

// Set paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render the HTML as PDF
$dompdf->render();

// Generate a filename
$filename = 'incident_report_' . str_pad($incident['id'], 6, '0', STR_PAD_LEFT) . '.pdf';

// Output the generated PDF
$dompdf->stream($filename, [
    'Attachment' => true,
    'compress' => true
]);

exit;