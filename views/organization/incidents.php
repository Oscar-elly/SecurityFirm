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

// Get all incidents at this organization's locations
$query = "SELECT i.*, l.name as location_name, u.name as reporter_name, u.role as reporter_role
          FROM incidents i 
          JOIN locations l ON i.location_id = l.id 
          JOIN users u ON i.reported_by = u.id 
          WHERE l.organization_id = ? 
          ORDER BY i.incident_time DESC";
$incidents = executeQuery($query, [$organization['id']]);

// Get incident statistics
$totalIncidents = count($incidents);
$openIncidents = count(array_filter($incidents, function($i) { return in_array($i['status'], ['reported', 'investigating']); }));
$resolvedIncidents = count(array_filter($incidents, function($i) { return $i['status'] === 'resolved'; }));
$criticalIncidents = count(array_filter($incidents, function($i) { return in_array($i['severity'], ['high', 'critical']); }));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incidents | <?php echo SITE_NAME; ?></title>
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
                    <h1>Security Incidents</h1>
                    <p>Monitor and track security incidents at your locations</p>
                </div>
                
                <!-- Incident Statistics -->
                <div class="stats-cards">
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="file-text"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $totalIncidents; ?></h3>
                            <p>Total Incidents</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="clock"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $openIncidents; ?></h3>
                            <p>Open Incidents</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="check-circle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $resolvedIncidents; ?></h3>
                            <p>Resolved</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="alert-triangle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $criticalIncidents; ?></h3>
                            <p>High Priority</p>
                        </div>
                    </div>
                </div>
                
                <!-- Incidents List -->
                <div class="card">
                    <div class="card-header">
                        <h2>Incident Reports</h2>
                        <div class="card-actions">
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
                            <select id="locationFilter" class="form-control" style="width: auto; display: inline-block;">
                                <option value="">All Locations</option>
                                <?php foreach (array_unique(array_column($incidents, 'location_name')) as $location): ?>
                                <option value="<?php echo $location; ?>"><?php echo sanitize($location); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($incidents)): ?>
                            <div class="incidents-list">
                                <?php foreach ($incidents as $incident): ?>
                                <div class="incident-card" 
                                     data-status="<?php echo $incident['status']; ?>" 
                                     data-severity="<?php echo $incident['severity']; ?>"
                                     data-location="<?php echo $incident['location_name']; ?>">
                                    <div class="incident-header">
                                        <div class="incident-title">
                                            <h3><?php echo sanitize($incident['title']); ?></h3>
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
                                        <p><?php echo sanitize($incident['description']); ?></p>
                                    </div>
                                    
                                    <div class="incident-footer">
                                        <div class="incident-actions">
                                            <button class="btn btn-sm btn-outline" onclick="viewIncident(<?php echo $incident['id']; ?>)">
                                                <i data-lucide="eye"></i> View Details
                                            </button>
                                            <?php if ($incident['latitude'] && $incident['longitude']): ?>
                                            <button class="btn btn-sm btn-secondary" onclick="viewLocation(<?php echo $incident['latitude']; ?>, <?php echo $incident['longitude']; ?>)">
                                                <i data-lucide="map-pin"></i> View Location
                                            </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-primary" onclick="downloadReport(<?php echo $incident['id']; ?>)">
                                                <i data-lucide="download"></i> Report
                                            </button>
                                        </div>
                                        <div class="incident-time">
                                            Reported <?php echo getTimeAgo($incident['created_at']); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <div class="no-duty-icon">
                                    <i data-lucide="shield-check"></i>
                                </div>
                                <p>No incidents have been reported at your locations.</p>
                                <p class="text-muted">This is good news! Your security measures are working effectively.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Incident Trends -->
                <?php if (!empty($incidents)): ?>
                <div class="card">
                    <div class="card-header">
                        <h2>Incident Trends</h2>
                    </div>
                    <div class="card-body">
                        <div class="trends-grid">
                            <div class="trend-item">
                                <div class="trend-label">Most Common Severity</div>
                                <div class="trend-value">
                                    <?php 
                                    $severities = array_count_values(array_column($incidents, 'severity'));
                                    $mostCommon = array_keys($severities, max($severities))[0];
                                    echo ucfirst($mostCommon);
                                    ?>
                                </div>
                            </div>
                            
                            <div class="trend-item">
                                <div class="trend-label">Most Affected Location</div>
                                <div class="trend-value">
                                    <?php 
                                    $locations = array_count_values(array_column($incidents, 'location_name'));
                                    if (!empty($locations)) {
                                        $mostAffected = array_keys($locations, max($locations))[0];
                                        echo sanitize($mostAffected);
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <div class="trend-item">
                                <div class="trend-label">Resolution Rate</div>
                                <div class="trend-value">
                                    <?php echo $totalIncidents > 0 ? round(($resolvedIncidents / $totalIncidents) * 100, 1) : 0; ?>%
                                </div>
                            </div>
                            
                            <div class="trend-item">
                                <div class="trend-label">This Month</div>
                                <div class="trend-value">
                                    <?php 
                                    $thisMonth = count(array_filter($incidents, function($i) {
                                        return date('Y-m', strtotime($i['incident_time'])) === date('Y-m');
                                    }));
                                    echo $thisMonth;
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <style>
    .card-actions {
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .incidents-list {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .incident-card {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 1.5rem;
        transition: box-shadow 0.2s ease;
    }
    
    .incident-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .incident-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }
    
    .incident-title h3 {
        margin: 0 0 0.5rem 0;
        font-size: 1.25rem;
        color: #333;
    }
    
    .incident-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        font-size: 0.875rem;
        color: #666;
    }
    
    .incident-meta span {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }
    
    .incident-badges {
        display: flex;
        gap: 0.5rem;
        flex-shrink: 0;
    }
    
    .incident-body {
        margin-bottom: 1rem;
        color: #555;
        line-height: 1.6;
    }
    
    .incident-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 1rem;
        border-top: 1px solid #f0f0f0;
    }
    
    .incident-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    
    .incident-time {
        font-size: 0.875rem;
        color: #666;
    }
    
    .trends-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
    }
    
    .trend-item {
        text-align: center;
        padding: 1.5rem;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .trend-label {
        font-size: 0.875rem;
        color: #666;
        margin-bottom: 0.5rem;
    }
    
    .trend-value {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--primary-color);
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
    
    @media (max-width: 768px) {
        .incident-header {
            flex-direction: column;
            gap: 1rem;
        }
        
        .incident-meta {
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .incident-footer {
            flex-direction: column;
            gap: 1rem;
            align-items: flex-start;
        }
        
        .card-actions {
            flex-direction: column;
            align-items: stretch;
        }
    }
    </style>

    <script>
        lucide.createIcons();
        
        // Filter functionality
        document.getElementById('statusFilter').addEventListener('change', filterIncidents);
        document.getElementById('severityFilter').addEventListener('change', filterIncidents);
        document.getElementById('locationFilter').addEventListener('change', filterIncidents);
        
        function filterIncidents() {
            const statusFilter = document.getElementById('statusFilter').value;
            const severityFilter = document.getElementById('severityFilter').value;
            const locationFilter = document.getElementById('locationFilter').value;
            const cards = document.querySelectorAll('.incident-card');
            
            cards.forEach(card => {
                const status = card.dataset.status;
                const severity = card.dataset.severity;
                const location = card.dataset.location;
                
                const statusMatch = !statusFilter || status === statusFilter;
                const severityMatch = !severityFilter || severity === severityFilter;
                const locationMatch = !locationFilter || location === locationFilter;
                
                if (statusMatch && severityMatch && locationMatch) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        function viewIncident(id) {
            window.location.href = 'view-incident.php?id=' + id;
        }
        
        function viewLocation(lat, lng) {
            window.open(`https://maps.google.com/maps?q=${lat},${lng}&z=15`, '_blank');
        }
        
        function downloadReport(id) {
            window.location.href = 'download-incident-report.php?id=' + id;
        }
        
        function getTimeAgo(datetime) {
            const now = new Date();
            const time = new Date(datetime);
            const diff = Math.floor((now - time) / 1000);
            
            if (diff < 60) return 'just now';
            if (diff < 3600) return Math.floor(diff / 60) + ' minutes ago';
            if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
            if (diff < 604800) return Math.floor(diff / 86400) + ' days ago';
            return time.toLocaleDateString();
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