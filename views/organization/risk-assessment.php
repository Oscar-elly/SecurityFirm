<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('organization');

$organizationId = $_SESSION['user_id'];

// Fetch risk assessment data (example: incidents by risk level, locations at risk, etc.)
$riskData = executeQuery("
    SELECT severity, COUNT(*) as count
    FROM incidents
    WHERE user_id = ?
    GROUP BY severity
    ORDER BY FIELD(severity, 'critical', 'high', 'medium', 'low')
", [$organizationId]);

$locationsAtRisk = executeQuery("
    SELECT l.name as location_name, COUNT(i.id) as incident_count
    FROM locations l
    LEFT JOIN incidents i ON l.id = i.location_id
    WHERE l.user_id = ?
    GROUP BY l.id, l.name
    HAVING incident_count > 0
    ORDER BY incident_count DESC
    LIMIT 10
", [$organizationId]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Risk Assessment | <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../../assets/css/styles.css" />
    <link rel="stylesheet" href="../../assets/css/dashboard.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/organization-sidebar.php'; ?>

        <main class="main-content">
            <?php include '../includes/top-nav.php'; ?>

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
                        <ul class="risk-list">
                            <?php foreach ($riskData as $risk): ?>
                            <li>
                                <span class="badge badge-<?php echo strtolower($risk['severity']); ?>">
                                    <?php echo ucfirst($risk['severity']); ?>
                                </span>
                                <span><?php echo $risk['count']; ?> incidents</span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
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
                                <strong><?php echo sanitize($location['location_name']); ?></strong>
                                <span class="badge badge-danger"><?php echo $location['incident_count']; ?> incidents</span>
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
    </style>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
