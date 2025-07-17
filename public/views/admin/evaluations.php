<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('admin');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_evaluation':
                $guard_id = (int)$_POST['guard_id'];
                $evaluation_date = sanitize($_POST['evaluation_date']);
                $punctuality = (int)$_POST['punctuality'];
                $appearance = (int)$_POST['appearance'];
                $communication = (int)$_POST['communication'];
                $job_knowledge = (int)$_POST['job_knowledge'];
                $overall_rating = (int)$_POST['overall_rating'];
                $comments = sanitize($_POST['comments']);
                
                $query = "INSERT INTO performance_evaluations 
                          (guard_id, evaluator_id, evaluation_date, punctuality, appearance, communication, job_knowledge, overall_rating, comments) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $result = executeQuery($query, [$guard_id, $_SESSION['user_id'], $evaluation_date, $punctuality, $appearance, $communication, $job_knowledge, $overall_rating, $comments]);
                
                if ($result) {
                    // Send notification to guard
                    $guardQuery = "SELECT user_id FROM guards WHERE id = ?";
                    $guardUser = executeQuery($guardQuery, [$guard_id], ['single' => true]);
                    if ($guardUser) {
                        $notificationQuery = "INSERT INTO notifications (user_id, title, message, type, link) 
                                              VALUES (?, ?, ?, 'evaluation', 'views/guard/evaluations.php')";
                        executeQuery($notificationQuery, [$guardUser['user_id'], 'New Performance Evaluation', 'You have received a new performance evaluation']);
                    }
                    
                    $_SESSION['success'] = 'Performance evaluation added successfully';
                } else {
                    $_SESSION['error'] = 'Failed to add performance evaluation';
                }
                break;
        }
        redirect($_SERVER['PHP_SELF']);
    }
}

// Get all evaluations with guard and evaluator information
$query = "SELECT pe.*, g.id_number, u.name as guard_name, ev.name as evaluator_name 
          FROM performance_evaluations pe 
          JOIN guards g ON pe.guard_id = g.id 
          JOIN users u ON g.user_id = u.id 
          JOIN users ev ON pe.evaluator_id = ev.id 
          ORDER BY pe.evaluation_date DESC";
$evaluations = executeQuery($query);

// Get guards for dropdown
$guardsQuery = "SELECT g.id, g.id_number, u.name FROM guards g JOIN users u ON g.user_id = u.id WHERE u.status = 'active'";
$guards = executeQuery($guardsQuery);

// Get evaluation statistics
$statsQuery = "SELECT 
                AVG(overall_rating) as avg_overall,
                AVG(punctuality) as avg_punctuality,
                AVG(appearance) as avg_appearance,
                AVG(communication) as avg_communication,
                AVG(job_knowledge) as avg_job_knowledge,
                COUNT(*) as total_evaluations
                FROM performance_evaluations 
                WHERE evaluation_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$stats = executeQuery($statsQuery, [], ['single' => true]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Evaluations | <?php echo SITE_NAME; ?></title>
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
                    <h1>Performance Evaluations</h1>
                    <button class="btn btn-primary" onclick="openEvaluationModal()">
                        <i data-lucide="plus"></i> New Evaluation
                    </button>
                </div>
                
                <?php echo flashMessage('success'); ?>
                <?php echo flashMessage('error'); ?>
                
                <!-- Performance Statistics -->
                <div class="stats-cards">
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="star"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo round($stats['avg_overall'] ?? 0, 1); ?></h3>
                            <p>Average Rating</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="clock"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo round($stats['avg_punctuality'] ?? 0, 1); ?></h3>
                            <p>Punctuality</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="message-circle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo round($stats['avg_communication'] ?? 0, 1); ?></h3>
                            <p>Communication</p>
                        </div>
                    </div>
                    
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i data-lucide="file-text"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $stats['total_evaluations'] ?? 0; ?></h3>
                            <p>This Month</p>
                        </div>
                    </div>
                </div>
                
                <!-- Evaluations List -->
                <div class="card">
                    <div class="card-header">
                        <h2>All Evaluations</h2>
                        <div class="card-actions">
                            <select id="guardFilter" class="form-control" style="width: auto; display: inline-block;">
                                <option value="">All Guards</option>
                                <?php foreach (array_unique(array_column($evaluations, 'guard_name')) as $guardName): ?>
                                <option value="<?php echo $guardName; ?>"><?php echo sanitize($guardName); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="month" id="monthFilter" class="form-control" style="width: auto; display: inline-block;" value="<?php echo date('Y-m'); ?>">
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($evaluations)): ?>
                            <div class="evaluations-list">
                                <?php foreach ($evaluations as $evaluation): ?>
                                <div class="evaluation-card" 
                                     data-guard="<?php echo $evaluation['guard_name']; ?>"
                                     data-month="<?php echo date('Y-m', strtotime($evaluation['evaluation_date'])); ?>">
                                    <div class="evaluation-header">
                                        <div class="evaluation-info">
                                            <h3><?php echo sanitize($evaluation['guard_name']); ?></h3>
                                            <p>ID: <?php echo sanitize($evaluation['id_number']); ?></p>
                                            <p>Evaluated by: <?php echo sanitize($evaluation['evaluator_name']); ?></p>
                                        </div>
                                        <div class="evaluation-rating">
                                            <div class="overall-rating">
                                                <span class="rating-value"><?php echo $evaluation['overall_rating']; ?></span>
                                                <span class="rating-max">/5</span>
                                            </div>
                                            <div class="rating-stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <span class="star <?php echo $i <= $evaluation['overall_rating'] ? 'filled' : ''; ?>">★</span>
                                                <?php endfor; ?>
                                            </div>
                                            <div class="evaluation-date"><?php echo formatDate($evaluation['evaluation_date'], 'd M Y'); ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="evaluation-criteria">
                                        <div class="criteria-grid">
                                            <div class="criteria-item">
                                                <span class="criteria-label">Punctuality</span>
                                                <div class="criteria-rating">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <span class="star <?php echo $i <= $evaluation['punctuality'] ? 'filled' : ''; ?>">★</span>
                                                    <?php endfor; ?>
                                                    <span class="criteria-value"><?php echo $evaluation['punctuality']; ?>/5</span>
                                                </div>
                                            </div>
                                            
                                            <div class="criteria-item">
                                                <span class="criteria-label">Appearance</span>
                                                <div class="criteria-rating">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <span class="star <?php echo $i <= $evaluation['appearance'] ? 'filled' : ''; ?>">★</span>
                                                    <?php endfor; ?>
                                                    <span class="criteria-value"><?php echo $evaluation['appearance']; ?>/5</span>
                                                </div>
                                            </div>
                                            
                                            <div class="criteria-item">
                                                <span class="criteria-label">Communication</span>
                                                <div class="criteria-rating">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <span class="star <?php echo $i <= $evaluation['communication'] ? 'filled' : ''; ?>">★</span>
                                                    <?php endfor; ?>
                                                    <span class="criteria-value"><?php echo $evaluation['communication']; ?>/5</span>
                                                </div>
                                            </div>
                                            
                                            <div class="criteria-item">
                                                <span class="criteria-label">Job Knowledge</span>
                                                <div class="criteria-rating">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <span class="star <?php echo $i <= $evaluation['job_knowledge'] ? 'filled' : ''; ?>">★</span>
                                                    <?php endfor; ?>
                                                    <span class="criteria-value"><?php echo $evaluation['job_knowledge']; ?>/5</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($evaluation['comments'])): ?>
                                    <div class="evaluation-comments">
                                        <h4>Comments:</h4>
                                        <p><?php echo sanitize($evaluation['comments']); ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <div class="no-data-icon">
                                    <i data-lucide="star"></i>
                                </div>
                                <p>No performance evaluations found.</p>
                                <button class="btn btn-primary" onclick="openEvaluationModal()">
                                    <i data-lucide="plus"></i> Create First Evaluation
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Evaluation Modal -->
    <div id="evaluationModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>New Performance Evaluation</h3>
                <button onclick="closeEvaluationModal()" class="btn btn-sm btn-outline">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_evaluation">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="guard_id">Select Guard</label>
                        <select id="guard_id" name="guard_id" required>
                            <option value="">Choose a guard</option>
                            <?php foreach ($guards as $guard): ?>
                            <option value="<?php echo $guard['id']; ?>">
                                <?php echo sanitize($guard['name']) . ' (' . sanitize($guard['id_number']) . ')'; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="evaluation_date">Evaluation Date</label>
                        <input type="date" id="evaluation_date" name="evaluation_date" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="rating-section">
                        <h4>Performance Ratings (1-5 scale)</h4>
                        
                        <div class="rating-group">
                            <label for="punctuality">Punctuality</label>
                            <div class="rating-input">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <input type="radio" name="punctuality" value="<?php echo $i; ?>" id="punctuality_<?php echo $i; ?>" required>
                                <label for="punctuality_<?php echo $i; ?>" class="star-label">★</label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <div class="rating-group">
                            <label for="appearance">Appearance</label>
                            <div class="rating-input">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <input type="radio" name="appearance" value="<?php echo $i; ?>" id="appearance_<?php echo $i; ?>" required>
                                <label for="appearance_<?php echo $i; ?>" class="star-label">★</label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <div class="rating-group">
                            <label for="communication">Communication</label>
                            <div class="rating-input">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <input type="radio" name="communication" value="<?php echo $i; ?>" id="communication_<?php echo $i; ?>" required>
                                <label for="communication_<?php echo $i; ?>" class="star-label">★</label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <div class="rating-group">
                            <label for="job_knowledge">Job Knowledge</label>
                            <div class="rating-input">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <input type="radio" name="job_knowledge" value="<?php echo $i; ?>" id="job_knowledge_<?php echo $i; ?>" required>
                                <label for="job_knowledge_<?php echo $i; ?>" class="star-label">★</label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <div class="rating-group">
                            <label for="overall_rating">Overall Rating</label>
                            <div class="rating-input">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <input type="radio" name="overall_rating" value="<?php echo $i; ?>" id="overall_rating_<?php echo $i; ?>" required>
                                <label for="overall_rating_<?php echo $i; ?>" class="star-label">★</label>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="comments">Comments</label>
                        <textarea id="comments" name="comments" rows="4" placeholder="Additional comments about the guard's performance"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeEvaluationModal()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Evaluation</button>
                </div>
            </form>
        </div>
    </div>

    <style>
    .card-actions {
        display: flex;
        gap: 1rem;
        align-items: center;
    }
    
    .evaluations-list {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .evaluation-card {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 1.5rem;
        transition: box-shadow 0.2s ease;
    }
    
    .evaluation-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .evaluation-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1.5rem;
    }
    
    .evaluation-info h3 {
        margin: 0 0 0.5rem 0;
        font-size: 1.25rem;
    }
    
    .evaluation-info p {
        margin: 0.25rem 0;
        color: #666;
        font-size: 0.875rem;
    }
    
    .evaluation-rating {
        text-align: center;
    }
    
    .overall-rating {
        margin-bottom: 0.5rem;
    }
    
    .rating-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--primary-color);
    }
    
    .rating-max {
        font-size: 1.25rem;
        color: #666;
    }
    
    .rating-stars {
        margin-bottom: 0.5rem;
    }
    
    .star {
        color: #ddd;
        font-size: 1.2rem;
        margin: 0 1px;
    }
    
    .star.filled {
        color: var(--accent-color);
    }
    
    .evaluation-date {
        font-size: 0.875rem;
        color: #666;
    }
    
    .criteria-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }
    
    .criteria-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem;
        background: #f8f9fa;
        border-radius: 6px;
    }
    
    .criteria-label {
        font-weight: 500;
        color: #333;
    }
    
    .criteria-rating {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }
    
    .criteria-value {
        margin-left: 0.5rem;
        font-weight: 600;
        color: var(--primary-color);
    }
    
    .evaluation-comments {
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid #eee;
    }
    
    .evaluation-comments h4 {
        margin: 0 0 0.5rem 0;
        font-size: 1rem;
    }
    
    .evaluation-comments p {
        margin: 0;
        color: #555;
        line-height: 1.6;
    }
    
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
        max-width: 700px;
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
    
    .rating-section {
        margin: 1.5rem 0;
    }
    
    .rating-section h4 {
        margin-bottom: 1rem;
        color: #333;
    }
    
    .rating-group {
        margin-bottom: 1rem;
    }
    
    .rating-group > label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }
    
    .rating-input {
        display: flex;
        gap: 0.25rem;
    }
    
    .rating-input input[type="radio"] {
        display: none;
    }
    
    .star-label {
        font-size: 1.5rem;
        color: #ddd;
        cursor: pointer;
        transition: color 0.2s ease;
    }
    
    .rating-input input[type="radio"]:checked ~ .star-label,
    .rating-input input[type="radio"]:checked + .star-label {
        color: var(--accent-color);
    }
    
    .rating-input input[type="radio"]:hover + .star-label {
        color: var(--accent-color);
    }
    
    .no-data {
        text-align: center;
        padding: 3rem 1rem;
    }
    
    .no-data-icon {
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
        .evaluation-header {
            flex-direction: column;
            gap: 1rem;
        }
        
        .criteria-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>

    <script>
        lucide.createIcons();
        
        function openEvaluationModal() {
            document.getElementById('evaluationModal').style.display = 'flex';
        }
        
        function closeEvaluationModal() {
            document.getElementById('evaluationModal').style.display = 'none';
        }
        
        // Filter functionality
        document.getElementById('guardFilter').addEventListener('change', filterEvaluations);
        document.getElementById('monthFilter').addEventListener('change', filterEvaluations);
        
        function filterEvaluations() {
            const guardFilter = document.getElementById('guardFilter').value;
            const monthFilter = document.getElementById('monthFilter').value;
            const cards = document.querySelectorAll('.evaluation-card');
            
            cards.forEach(card => {
                const guard = card.dataset.guard;
                const month = card.dataset.month;
                
                const guardMatch = !guardFilter || guard === guardFilter;
                const monthMatch = !monthFilter || month === monthFilter;
                
                if (guardMatch && monthMatch) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        // Star rating functionality
        document.querySelectorAll('.rating-input').forEach(ratingGroup => {
            const inputs = ratingGroup.querySelectorAll('input[type="radio"]');
            const labels = ratingGroup.querySelectorAll('.star-label');
            
            labels.forEach((label, index) => {
                label.addEventListener('mouseover', () => {
                    labels.forEach((l, i) => {
                        if (i <= index) {
                            l.style.color = 'var(--accent-color)';
                        } else {
                            l.style.color = '#ddd';
                        }
                    });
                });
                
                label.addEventListener('mouseout', () => {
                    const checkedInput = ratingGroup.querySelector('input[type="radio"]:checked');
                    if (checkedInput) {
                        const checkedIndex = Array.from(inputs).indexOf(checkedInput);
                        labels.forEach((l, i) => {
                            if (i <= checkedIndex) {
                                l.style.color = 'var(--accent-color)';
                            } else {
                                l.style.color = '#ddd';
                            }
                        });
                    } else {
                        labels.forEach(l => l.style.color = '#ddd');
                    }
                });
            });
        });
    </script>
</body>
</html>