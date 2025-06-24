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

// Get all guards assigned to this organization's locations
$query = "SELECT DISTINCT u.name, g.id_number, u.phone, u.email, 
                 da.start_date, da.end_date, da.status as assignment_status,
                 l.name as location_name, s.name as shift_name, s.start_time, s.end_time,
                 g.joining_date, g.qualification
          FROM duty_assignments da 
          JOIN guards g ON da.guard_id = g.id 
          JOIN users u ON g.user_id = u.id 
          JOIN locations l ON da.location_id = l.id 
          JOIN shifts s ON da.shift_id = s.id 
          WHERE l.organization_id = ? 
          ORDER BY da.start_date DESC";
$guards = executeQuery($query, [$organization['id']]);

// Get current active guards
$activeGuards = array_filter($guards, function($guard) {
    return $guard['assignment_status'] === 'active' && 
           strtotime($guard['start_date']) <= time() && 
           (!$guard['end_date'] || strtotime($guard['end_date']) >= time());
});

// Get guard performance data
$performanceQuery = "SELECT g.id_number, u.name, 
                            AVG(pe.overall_rating) as avg_rating,
                            COUNT(pe.id) as evaluation_count
                     FROM guards g 
                     JOIN users u ON g.user_id = u.id 
                     JOIN duty_assignments da ON g.id = da.guard_id 
                     JOIN locations l ON da.location_id = l.id 
                     LEFT JOIN performance_evaluations pe ON g.id = pe.guard_id 
                     WHERE l.organization_id = ? 
                     GROUP BY g.id, u.name, g.id_number";
$performance = executeQuery($performanceQuery, [$organization['id']]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guard Details | <?php echo SITE_NAME; ?></title>
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
                    <h1>Guard Details</h1>
                    <p>View information about guards assigned to your locations</p>
                </div>
                
                <!-- Guard Statistics -->
                <div class="stats-cards">
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="shield"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo count($activeGuards); ?></h3>
                            <p>Active Guards</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="users"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo count(array_unique(array_column($guards, 'id_number'))); ?></h3>
                            <p>Total Guards</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="star"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo !empty($performance) ? round(array_sum(array_column($performance, 'avg_rating')) / count($performance), 1) : 'N/A'; ?></h3>
                            <p>Avg Rating</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="map-pin"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo count(array_unique(array_column($guards, 'location_name'))); ?></h3>
                            <p>Locations Covered</p>
                        </div>
                    </div>
                </div>
                
                <!-- Active Guards -->
                <div class="card">
                    <div class="card-header">
                        <h2>Currently Active Guards</h2>
                        <span class="badge badge-success"><?php echo count($activeGuards); ?> Active</span>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($activeGuards)): ?>
                            <div class="guards-grid">
                                <?php foreach ($activeGuards as $guard): ?>
                                <div class="guard-card">
                                    <div class="guard-header">
                                        <div class="guard-avatar">
                                            <?php echo getInitials($guard['name']); ?>
                                        </div>
                                        <div class="guard-info">
                                            <h3><?php echo sanitize($guard['name']); ?></h3>
                                            <p class="guard-id">ID: <?php echo sanitize($guard['id_number']); ?></p>
                                        </div>
                                        <div class="guard-status">
                                            <span class="badge badge-success">Active</span>
                                        </div>
                                    </div>
                                    
                                    <div class="guard-details">
                                        <div class="detail-item">
                                            <i data-lucide="map-pin"></i>
                                            <span><?php echo sanitize($guard['location_name']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <i data-lucide="clock"></i>
                                            <span><?php echo sanitize($guard['shift_name']); ?> (<?php echo formatTime($guard['start_time']) . ' - ' . formatTime($guard['end_time']); ?>)</span>
                                        </div>
                                        <div class="detail-item">
                                            <i data-lucide="calendar"></i>
                                            <span>Since <?php echo formatDate($guard['start_date'], 'd M Y'); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <i data-lucide="phone"></i>
                                            <span><?php echo sanitize($guard['phone']); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="guard-actions">
                                        <button class="btn btn-sm btn-outline" onclick="viewGuardProfile('<?php echo $guard['id_number']; ?>')">
                                            <i data-lucide="user"></i> Profile
                                        </button>
                                        <button class="btn btn-sm btn-secondary" onclick="contactGuard('<?php echo $guard['phone']; ?>')">
                                            <i data-lucide="phone"></i> Contact
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <div class="no-duty-icon">
                                    <i data-lucide="shield-off"></i>
                                </div>
                                <p>No guards are currently active at your locations.</p>
                                <a href="request-guard.php" class="btn btn-primary">
                                    <i data-lucide="plus"></i> Request Guards
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- All Guards History -->
                <div class="card">
                    <div class="card-header">
                        <h2>Guard Assignment History</h2>
                        <div class="card-actions">
                            <select id="locationFilter" class="form-control" style="width: auto; display: inline-block;">
                                <option value="">All Locations</option>
                                <?php foreach (array_unique(array_column($guards, 'location_name')) as $location): ?>
                                <option value="<?php echo $location; ?>"><?php echo sanitize($location); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="guardsTable">
                                <thead>
                                    <tr>
                                        <th>Guard Name</th>
                                        <th>ID Number</th>
                                        <th>Location</th>
                                        <th>Shift</th>
                                        <th>Assignment Period</th>
                                        <th>Status</th>
                                        <th>Contact</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($guards as $guard): ?>
                                    <tr data-location="<?php echo $guard['location_name']; ?>">
                                        <td>
                                            <strong><?php echo sanitize($guard['name']); ?></strong>
                                            <?php if (!empty($guard['qualification'])): ?>
                                            <br><small class="text-muted"><?php echo sanitize($guard['qualification']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo sanitize($guard['id_number']); ?></td>
                                        <td><?php echo sanitize($guard['location_name']); ?></td>
                                        <td>
                                            <?php echo sanitize($guard['shift_name']); ?>
                                            <br><small><?php echo formatTime($guard['start_time']) . ' - ' . formatTime($guard['end_time']); ?></small>
                                        </td>
                                        <td>
                                            <?php echo formatDate($guard['start_date'], 'd M Y'); ?>
                                            <?php if ($guard['end_date']): ?>
                                                - <?php echo formatDate($guard['end_date'], 'd M Y'); ?>
                                            <?php else: ?>
                                                - Ongoing
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo getAssignmentStatusClass($guard['assignment_status']); ?>">
                                                <?php echo ucfirst($guard['assignment_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="contact-info">
                                                <div><?php echo sanitize($guard['phone']); ?></div>
                                                <div><small><?php echo sanitize($guard['email']); ?></small></div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Performance Overview -->
                <?php if (!empty($performance)): ?>
                <div class="card">
                    <div class="card-header">
                        <h2>Guard Performance Overview</h2>
                    </div>
                    <div class="card-body">
                        <div class="performance-list">
                            <?php foreach ($performance as $perf): ?>
                            <div class="performance-item">
                                <div class="performance-guard">
                                    <strong><?php echo sanitize($perf['name']); ?></strong>
                                    <small>ID: <?php echo sanitize($perf['id_number']); ?></small>
                                </div>
                                <div class="performance-rating">
                                    <?php if ($perf['avg_rating']): ?>
                                    <div class="rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="star <?php echo $i <= round($perf['avg_rating']) ? 'filled' : ''; ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="rating-value"><?php echo round($perf['avg_rating'], 1); ?>/5</span>
                                    <small>(<?php echo $perf['evaluation_count']; ?> evaluation<?php echo $perf['evaluation_count'] != 1 ? 's' : ''; ?>)</small>
                                    <?php else: ?>
                                    <span class="text-muted">No evaluations yet</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
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
    }
    
    .guards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 1.5rem;
    }
    
    .guard-card {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 1.5rem;
        transition: box-shadow 0.2s ease;
    }
    
    .guard-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .guard-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    
    .guard-avatar {
        width: 50px;
        height: 50px;
        background: var(--primary-color);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        flex-shrink: 0;
    }
    
    .guard-info {
        flex: 1;
    }
    
    .guard-info h3 {
        margin: 0 0 0.25rem 0;
        font-size: 1.1rem;
    }
    
    .guard-id {
        margin: 0;
        color: #666;
        font-size: 0.875rem;
    }
    
    .guard-status {
        flex-shrink: 0;
    }
    
    .guard-details {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }
    
    .detail-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
        color: #555;
    }
    
    .detail-item i {
        width: 16px;
        height: 16px;
        color: #666;
    }
    
    .guard-actions {
        display: flex;
        gap: 0.5rem;
    }
    
    .contact-info {
        font-size: 0.875rem;
    }
    
    .performance-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .performance-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .performance-guard strong {
        display: block;
        margin-bottom: 0.25rem;
    }
    
    .performance-guard small {
        color: #666;
    }
    
    .performance-rating {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .rating-stars {
        display: flex;
        gap: 2px;
    }
    
    .star {
        color: #ddd;
        font-size: 1.2rem;
    }
    
    .star.filled {
        color: var(--accent-color);
    }
    
    .rating-value {
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
        .guards-grid {
            grid-template-columns: 1fr;
        }
        
        .performance-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
    }
    </style>

    <script>
        lucide.createIcons();
        
        // Location filter
        document.getElementById('locationFilter').addEventListener('change', function() {
            const selectedLocation = this.value;
            const rows = document.querySelectorAll('#guardsTable tbody tr');
            
            rows.forEach(row => {
                const location = row.dataset.location;
                if (!selectedLocation || location === selectedLocation) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        function viewGuardProfile(idNumber) {
            window.location.href = 'guard-profile.php?id=' + idNumber;
        }
        
        function contactGuard(phone) {
            window.location.href = 'tel:' + phone;
        }
        
        function getInitials(name) {
            return name.split(' ').map(word => word.charAt(0)).join('').substring(0, 2).toUpperCase();
        }
    </script>
</body>
</html>

<?php
function formatTime($time) {
    return date('h:i A', strtotime($time));
}

function getAssignmentStatusClass($status) {
    switch ($status) {
        case 'active': return 'success';
        case 'completed': return 'secondary';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}

function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $word) {
        $initials .= strtoupper(substr($word, 0, 1));
    }
    return substr($initials, 0, 2);
}
?>