<?php
session_start();

// Load required files with existence checks
$requiredFiles = [
    '../../includes/config.php',
    '../../includes/functions.php',
    '../../includes/db.php'
];

foreach ($requiredFiles as $file) {
    if (!file_exists($file)) {
        die("Error: Required file '$file' is missing.");
    }
    require_once $file;
}

// Verify user role and session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../../login.php");
    exit;
}

requireRole('organization');

// Validate organization ID
$organizationId = filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT);
if ($organizationId === false || $organizationId <= 0) {
    die("Invalid user ID");
}
$organization = executeQuery2("SELECT id FROM organizations WHERE user_id = ?", [$_SESSION['user_id']], ['single' => true]);

if (!$organization || !isset($organization['id'])) {
    die("Organization not found for this user.");
}

$organizationId = $organization['id'];


// Fetch risk assessment data with error handling
try {
// Fetch risk assessment data by severity
    $riskData = executeQuery2("
        SELECT i.severity, COUNT(*) as count
        FROM incidents i
        JOIN locations l ON i.location_id = l.id
        JOIN organizations o ON l.organization_id = o.id
        WHERE o.id = ?
        GROUP BY i.severity
        ORDER BY FIELD(i.severity, 'critical', 'high', 'medium', 'low')
    ", [$organizationId]) ?: [];

    // Fetch locations with most incidents
    $locationsAtRisk = executeQuery2("
        SELECT l.name as location_name, COUNT(i.id) as incident_count
        FROM locations l
        LEFT JOIN incidents i ON l.id = i.location_id
        WHERE l.organization_id = ?
        GROUP BY l.id, l.name
        HAVING incident_count > 0
        ORDER BY incident_count DESC
        LIMIT 10
    ", [$organizationId]) ?: [];
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $riskData = [];
    $locationsAtRisk = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Risk Assessment | <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../../assets/css/styles.css" />
    <link rel="stylesheet" href="../../assets/css/dashboard.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <div class="dashboard-container">
        <?php 
        $sidebar = '../includes/organization-sidebar.php';
        if (file_exists($sidebar)) {
            include $sidebar;
        } else {
            error_log("Sidebar file missing: $sidebar");
        }
        ?>

        <main class="main-content">
            <?php 
            $topNav = '../includes/top-nav.php';
            if (file_exists($topNav)) {
                include $topNav;
            } else {
                error_log("Top nav file missing: $topNav");
            }
            ?>

            <div class="dashboard-content">
                <div class="dashboard-header">
                    <h1>Risk Assessment</h1>
                    <p>Overview of security risks and vulnerable locations</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>Incidents by Severity</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($riskData)): ?>
                        <ul class="risk-list">
                            <?php foreach ($riskData as $risk): ?>
                            <li class="<?php echo htmlspecialchars(strtolower($risk['severity'])); ?>">
                                <span class="badge badge-<?php echo htmlspecialchars(strtolower($risk['severity'])); ?>">
                                    <?php echo htmlspecialchars(ucfirst($risk['severity'])); ?>
                                </span>
                                <span><?php echo htmlspecialchars($risk['count']); ?> incidents</span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else: ?>
                        <p>No incident data available.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>Locations at Risk</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($locationsAtRisk)): ?>
                        <ul class="location-risk-list">
                            <?php foreach ($locationsAtRisk as $location): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($location['location_name']); ?></strong>
                                <span class="badge badge-danger"><?php echo htmlspecialchars($location['incident_count']); ?> incidents</span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else: ?>
                        <p>No locations with reported incidents.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
    .risk-list {
        list-style: none;
        padding: 0;
        display: flex;
        gap: 1rem;
    }
    .risk-list li {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 8px;
        flex: 1;
        text-align: center;
        font-weight: 600;
    }
    .location-risk-list {
        list-style: none;
        padding: 0;
    }
    .location-risk-list li {
        padding: 0.5rem 0;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .risk-list li.critical {
        background-color: #f8d7da;
        color: #721c24;
    }
    .risk-list li.high {
        background-color: #fff3cd;
        color: #856404;
    }
    .risk-list li.medium {
        background-color: #fff8e1;
        color: #665c00;
    }
    .risk-list li.low {
        background-color: #d4edda;
        color: #155724;
    }
    </style>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
