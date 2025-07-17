<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('guard');

// Get guard information
$userId = $_SESSION['user_id'];
$query = "SELECT g.* FROM guards g JOIN users u ON g.user_id = u.id WHERE g.user_id = ?";
$guard = executeQuery($query, [$userId], ['single' => true]);

if (!$guard) {
    $_SESSION['error'] = 'Guard information not found';
    redirect(SITE_URL);
}

// Get all evaluations for this guard
$query = "SELECT pe.*, u.name as evaluator_name, u.role as evaluator_role
          FROM performance_evaluations pe 
          JOIN users u ON pe.evaluator_id = u.id 
          WHERE pe.guard_id = ? 
          ORDER BY pe.evaluation_date DESC";
$evaluations = executeQuery($query, [$guard['id']]);

// Calculate average ratings
$totalEvaluations = count($evaluations);
$avgPunctuality = 0;
$avgAppearance = 0;
$avgCommunication = 0;
$avgJobKnowledge = 0;
$avgOverall = 0;

if ($totalEvaluations > 0) {
    $avgPunctuality = round(array_sum(array_column($evaluations, 'punctuality')) / $totalEvaluations, 1);
    $avgAppearance = round(array_sum(array_column($evaluations, 'appearance')) / $totalEvaluations, 1);
    $avgCommunication = round(array_sum(array_column($evaluations, 'communication')) / $totalEvaluations, 1);
    $avgJobKnowledge = round(array_sum(array_column($evaluations, 'job_knowledge')) / $totalEvaluations, 1);
    $avgOverall = round(array_sum(array_column($evaluations, 'overall_rating')) / $totalEvaluations, 1);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Evaluations | <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/guard-dashboard.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/guard-sidebar.php'; ?>
        
        <main class="main-content">
            <?php include '../includes/top-nav.php'; ?>
            
            <div class="dashboard-content">
                <div class="dashboard-header">
                    <h1>My Performance Evaluations</h1>
                    <p>Track your performance and improvement over time</p>
                </div>
                
                <!-- Performance Overview -->
                <div class="card">
                    <div class="card-header">
                        <h2>Performance Overview</h2>
                        <span class="badge badge-primary"><?php echo $totalEvaluations; ?> Evaluations</span>
                    </div>
                    <div class="card-body">
                        <div class="performance-overview">
                            <div class="overall-rating">
                                <div class="rating-circle">
                                    <div class="rating-value"><?php echo $avgOverall; ?></div>
                                    <div class="rating-label">Overall Rating</div>
                                </div>
                                <div class="rating-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?php echo $i <= $avgOverall ? 'filled' : ''; ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            
                            <div class="criteria-breakdown">
                                <div class="criteria-item">
                                    <div class="criteria-label">Punctuality</div>
                                    <div class="criteria-bar">
                                        <div class="criteria-fill" style="width: <?php echo ($avgPunctuality / 5) * 100; ?>%"></div>
                                    </div>
                                    <div class="criteria-value"><?php echo $avgPunctuality; ?>/5</div>
                                </div>
                                
                                <div class="criteria-item">
                                    <div class="criteria-label">Appearance</div>
                                    <div class="criteria-bar">
                                        <div class="criteria-fill" style="width: <?php echo ($avgAppearance / 5) * 100; ?>%"></div>
                                    </div>
                                    <div class="criteria-value"><?php echo $avgAppearance; ?>/5</div>
                                </div>
                                
                                <div class="criteria-item">
                                    <div class="criteria-label">Communication</div>
                                    <div class="criteria-bar">
                                        <div class="criteria-fill" style="width: <?php echo ($avgCommunication / 5) * 100; ?>%"></div>
                                    </div>
                                    <div class="criteria-value"><?php echo $avgCommunication; ?>/5</div>
                                </div>
                                
                                <div class="criteria-item">
                                    <div class="criteria-label">Job Knowledge</div>
                                    <div class="criteria-bar">
                                        <div class="criteria-fill" style="width: <?php echo ($avgJobKnowledge / 5) * 100; ?>%"></div>
                                    </div>
                                    <div class="criteria-value"><?php echo $avgJobKnowledge; ?>/5</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Evaluation History -->
                <div class="card">
                    <div class="card-header">
                        <h2>Evaluation History</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($evaluations)): ?>
                            <div class="evaluations-list">
                                <?php foreach ($evaluations as $evaluation): ?>
                                    <div class="evaluation-card">
                                        <div class="evaluation-header">
                                            <div class="evaluation-date">
                                                <i data-lucide="calendar"></i>
                                                <span><?php echo formatDate($evaluation['evaluation_date'], 'd M Y'); ?></span>
                                            </div>
                                            <div class="evaluation-rating">
                                                <span>Overall: </span>
                                                <div class="rating">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <span class="star <?php echo $i <= $evaluation['overall_rating'] ? 'filled' : ''; ?>">★</span>
                                                    <?php endfor; ?>
                                                </div>
                                                <span class="rating-number"><?php echo $evaluation['overall_rating']; ?>/5</span>
                                            </div>
                                        </div>
                                        
                                        <div class="evaluation-body">
                                            <div class="evaluation-criteria">
                                                <div class="criteria-item">
                                                    <span>Punctuality:</span>
                                                    <div class="rating">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <span class="star <?php echo $i <= $evaluation['punctuality'] ? 'filled' : ''; ?>">★</span>
                                                        <?php endfor; ?>
                                                    </div>
                                                    <span class="rating-number"><?php echo $evaluation['punctuality']; ?>/5</span>
                                                </div>
                                                
                                                <div class="criteria-item">
                                                    <span>Appearance:</span>
                                                    <div class="rating">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <span class="star <?php echo $i <= $evaluation['appearance'] ? 'filled' : ''; ?>">★</span>
                                                        <?php endfor; ?>
                                                    </div>
                                                    <span class="rating-number"><?php echo $evaluation['appearance']; ?>/5</span>
                                                </div>
                                                
                                                <div class="criteria-item">
                                                    <span>Communication:</span>
                                                    <div class="rating">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <span class="star <?php echo $i <= $evaluation['communication'] ? 'filled' : ''; ?>">★</span>
                                                        <?php endfor; ?>
                                                    </div>
                                                    <span class="rating-number"><?php echo $evaluation['communication']; ?>/5</span>
                                                </div>
                                                
                                                <div class="criteria-item">
                                                    <span>Job Knowledge:</span>
                                                    <div class="rating">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <span class="star <?php echo $i <= $evaluation['job_knowledge'] ? 'filled' : ''; ?>">★</span>
                                                        <?php endfor; ?>
                                                    </div>
                                                    <span class="rating-number"><?php echo $evaluation['job_knowledge']; ?>/5</span>
                                                </div>
                                            </div>
                                            
                                            <?php if (!empty($evaluation['comments'])): ?>
                                                <div class="evaluation-comments">
                                                    <h4>Comments:</h4>
                                                    <p><?php echo sanitize($evaluation['comments']); ?></p>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="evaluation-footer">
                                                <p>Evaluated by: <strong><?php echo sanitize($evaluation['evaluator_name']); ?></strong> (<?php echo ucfirst($evaluation['evaluator_role']); ?>)</p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <div class="no-duty-icon">
                                    <i data-lucide="trending-up"></i>
                                </div>
                                <p>No performance evaluations available yet.</p>
                                <p class="text-muted">Your supervisor will conduct regular evaluations to help track your performance and development.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
    .performance-overview {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 2rem;
        align-items: center;
    }
    
    .overall-rating {
        text-align: center;
    }
    
    .rating-circle {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: white;
        margin-bottom: 1rem;
    }
    
    .rating-value {
        font-size: 2rem;
        font-weight: 700;
    }
    
    .rating-label {
        font-size: 0.875rem;
        opacity: 0.9;
    }
    
    .rating-stars {
        display: flex;
        justify-content: center;
        gap: 2px;
    }
    
    .criteria-breakdown {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .criteria-item {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .criteria-label {
        min-width: 120px;
        font-weight: 500;
    }
    
    .criteria-bar {
        flex: 1;
        height: 20px;
        background: #e0e0e0;
        border-radius: 10px;
        overflow: hidden;
    }
    
    .criteria-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--success-color), var(--primary-color));
        transition: width 0.3s ease;
    }
    
    .criteria-value {
        min-width: 50px;
        text-align: right;
        font-weight: 600;
        color: var(--primary-color);
    }
    
    .evaluations-list {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .evaluation-card {
        background-color: var(--white);
        border-radius: var(--border-radius-md);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
        border-left: 4px solid var(--primary-color);
        transition: transform var(--transition-normal), box-shadow var(--transition-normal);
    }
    
    .evaluation-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-lg);
    }
    
    .evaluation-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: var(--space-md);
        background-color: var(--gray-100);
    }
    
    .evaluation-date {
        display: flex;
        align-items: center;
        gap: var(--space-xs);
        color: var(--gray-800);
    }
    
    .evaluation-rating {
        display: flex;
        align-items: center;
        gap: var(--space-xs);
    }
    
    .rating {
        display: flex;
        gap: 2px;
    }
    
    .star {
        color: var(--gray-400);
        font-size: 1.2rem;
    }
    
    .star.filled {
        color: var(--accent-color);
    }
    
    .rating-number {
        font-weight: 600;
        color: var(--primary-color);
    }
    
    .evaluation-body {
        padding: var(--space-md);
    }
    
    .evaluation-criteria {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--space-md);
        margin-bottom: var(--space-md);
    }
    
    .evaluation-criteria .criteria-item {
        display: flex;
        flex-direction: column;
        gap: var(--space-xs);
        align-items: flex-start;
    }
    
    .evaluation-criteria .criteria-item span:first-child {
        font-weight: 500;
        color: var(--gray-700);
    }
    
    .evaluation-comments {
        margin-top: var(--space-md);
        padding-top: var(--space-md);
        border-top: 1px solid var(--gray-200);
    }
    
    .evaluation-comments h4 {
        margin: 0 0 var(--space-sm) 0;
        font-size: var(--font-size-md);
        color: var(--gray-800);
    }
    
    .evaluation-comments p {
        margin: 0;
        color: var(--gray-700);
        line-height: 1.6;
    }
    
    .evaluation-footer {
        margin-top: var(--space-md);
        font-size: var(--font-size-sm);
        color: var(--gray-600);
    }
    
    .evaluation-footer p {
        margin: 0;
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
        .performance-overview {
            grid-template-columns: 1fr;
            text-align: center;
        }
        
        .evaluation-header {
            flex-direction: column;
            align-items: flex-start;
            gap: var(--space-sm);
        }
        
        .evaluation-criteria {
            grid-template-columns: 1fr;
        }
    }
    </style>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>