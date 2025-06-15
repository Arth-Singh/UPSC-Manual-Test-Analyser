<?php
include_once 'config/database.php';
require_once 'config/profile_check.php';

// Get recent sessions and stats
$recent_sessions = getAllSessions($pdo);
$total_sessions = count($recent_sessions);
$total_questions = $pdo->query("SELECT COUNT(*) FROM question_logs")->fetchColumn();
$total_correct = $pdo->query("SELECT COUNT(*) FROM question_logs WHERE is_correct = 1")->fetchColumn();
$accuracy = $total_questions > 0 ? round(($total_correct / $total_questions) * 100, 1) : 0;

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual Test Analysis Platform</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="nav-logo">Test Analysis Platform</a>
            <div class="nav-links">
                <a href="index.php">Dashboard</a>
                <a href="create-session.php">New Test</a>
                <a href="analytics.php">Analytics</a>
                <a href="advanced-analytics.php">Advanced</a>
                <span class="profile-name"><?= htmlspecialchars($current_profile_name) ?></span>
                <a href="select-profile.php">Switch Profile</a>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <?php if ($flash): ?>
            <div class="flash-messages">
                <div class="flash-message flash-<?= $flash['type'] ?>">
                    <?= htmlspecialchars($flash['message']) ?>
                    <button class="flash-close" onclick="this.parentElement.remove()">×</button>
                </div>
            </div>
        <?php endif; ?>

        <div class="container">
            <!-- Hero Section -->
            <div class="hero-section">
                <h1>Manual Test Analysis Platform</h1>
                <p>Log your feelings and confidence for each question. Get detailed analytics to improve your performance.</p>
                
                <div class="quick-actions">
                    <a href="create-session.php" class="btn btn-primary btn-large">
                        Create New Test Session
                    </a>
                    <a href="analytics.php" class="btn btn-secondary btn-large">
                        View Analytics
                    </a>
                </div>
            </div>

            <!-- Statistics Overview -->
            <div class="stats-overview">
                <h2>Performance Overview</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?= $total_sessions ?></div>
                        <div class="stat-label">Test Sessions</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= $total_questions ?></div>
                        <div class="stat-label">Questions Logged</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= $total_correct ?></div>
                        <div class="stat-label">Correct Answers</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= $accuracy ?>%</div>
                        <div class="stat-label">Overall Accuracy</div>
                    </div>
                </div>
            </div>

            <!-- Recent Test Sessions -->
            <div class="recent-sessions">
                <h2>Recent Test Sessions</h2>
                
                <?php if (!empty($recent_sessions)): ?>
                    <div class="sessions-grid">
                        <?php foreach (array_slice($recent_sessions, 0, 6) as $session): ?>
                            <?php 
                            $accuracy = $session['logged_questions'] > 0 ? 
                                round(($session['correct_answers'] / $session['logged_questions']) * 100, 1) : 0;
                            ?>
                            <div class="session-card">
                                <div class="session-header">
                                    <h3><?= htmlspecialchars($session['test_name']) ?></h3>
                                    <div class="session-date"><?= date('M d, Y', strtotime($session['test_date'])) ?></div>
                                </div>
                                
                                <div class="session-stats">
                                    <div class="session-stat">
                                        <span class="stat-label">Questions:</span>
                                        <span class="stat-value"><?= $session['logged_questions'] ?>/<?= $session['total_questions'] ?></span>
                                    </div>
                                    <div class="session-stat">
                                        <span class="stat-label">Accuracy:</span>
                                        <span class="stat-value"><?= $accuracy ?>%</span>
                                    </div>
                                    <div class="session-stat">
                                        <span class="stat-label">Type:</span>
                                        <span class="stat-value"><?= htmlspecialchars($session['test_type']) ?></span>
                                    </div>
                                </div>
                                
                                <div class="session-actions">
                                    <?php if ($session['logged_questions'] < $session['total_questions']): ?>
                                        <a href="log-question.php?session_id=<?= $session['id'] ?>" class="btn btn-primary btn-small">
                                            Continue Logging
                                        </a>
                                    <?php endif; ?>
                                    <a href="view-session.php?id=<?= $session['id'] ?>" class="btn btn-secondary btn-small">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (count($recent_sessions) > 6): ?>
                        <div class="view-all">
                            <a href="all-sessions.php" class="btn btn-outline">View All Sessions →</a>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="empty-state">
                        <h3>No Test Sessions Yet</h3>
                        <p>Create your first test session to start analyzing your performance!</p>
                        <a href="create-session.php" class="btn btn-primary">Create Test Session</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Insights -->
            <?php if ($total_questions > 0): ?>
                <div class="quick-insights">
                    <h2>🔍 Quick Insights</h2>
                    <div class="insights-grid">
                        <?php
                        // Get feeling distribution
                        $feelings = getAnalysisData($pdo);
                        $most_common_feeling = '';
                        $max_count = 0;
                        foreach ($feelings as $feeling) {
                            if ($feeling['count'] > $max_count) {
                                $max_count = $feeling['count'];
                                $most_common_feeling = $feeling['feeling'];
                            }
                        }
                        
                        // Get subject performance
                        $subjects = getSubjectAnalysis($pdo);
                        $best_subject = '';
                        $best_accuracy = 0;
                        foreach ($subjects as $subject) {
                            $subject_accuracy = $subject['total_questions'] > 0 ? 
                                ($subject['correct_answers'] / $subject['total_questions']) * 100 : 0;
                            if ($subject_accuracy > $best_accuracy) {
                                $best_accuracy = $subject_accuracy;
                                $best_subject = $subject['subject'];
                            }
                        }
                        ?>
                        
                        <div class="insight-card">
                            <div class="insight-icon">😊</div>
                            <div class="insight-content">
                                <h4>Most Common Feeling</h4>
                                <p><strong><?= ucfirst(str_replace('_', ' ', $most_common_feeling)) ?></strong> (<?= $max_count ?> questions)</p>
                            </div>
                        </div>
                        
                        <?php if ($best_subject): ?>
                            <div class="insight-card">
                                <div class="insight-icon">🏆</div>
                                <div class="insight-content">
                                    <h4>Best Subject</h4>
                                    <p><strong><?= htmlspecialchars($best_subject) ?></strong> (<?= round($best_accuracy, 1) ?>% accuracy)</p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="insight-card">
                            <div class="insight-icon">📊</div>
                            <div class="insight-content">
                                <h4>Overall Progress</h4>
                                <p><?= $total_questions ?> questions analyzed across <?= $total_sessions ?> sessions</p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Manual Test Analysis Platform</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>