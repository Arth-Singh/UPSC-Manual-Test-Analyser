<?php
include_once 'config/database.php';

$session_id = $_GET['id'] ?? 0;
$completed = $_GET['completed'] ?? 0;

if (!$session_id) {
    header('Location: index.php');
    exit;
}

$session = getSessionById($pdo, $session_id);
if (!$session) {
    setFlashMessage('Session not found.', 'error');
    header('Location: index.php');
    exit;
}

$questions = getQuestionsBySession($pdo, $session_id);
$session_feelings = getAnalysisData($pdo, $session_id);
$session_subjects = getSubjectAnalysis($pdo, $session_id);

// Calculate session statistics
$total_logged = count($questions);
$correct_answers = array_sum(array_map(fn($q) => $q['is_correct'] ?? 0, $questions));
$session_accuracy = $total_logged > 0 ? round(($correct_answers / $total_logged) * 100, 1) : 0;
$avg_confidence = $total_logged > 0 ? round(array_sum(array_column($questions, 'confidence_level')) / $total_logged, 1) : 0;
$avg_time = array_filter(array_column($questions, 'time_spent'));
$avg_time_spent = !empty($avg_time) ? round(array_sum($avg_time) / count($avg_time)) : 0;

$flash = getFlashMessage();
if ($completed) {
    setFlashMessage("🎉 Test session '{$session['test_name']}' completed successfully!", 'success');
    $flash = getFlashMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Details - <?= htmlspecialchars($session['test_name']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="nav-logo">📊 Test Analysis Platform</a>
            <div class="nav-links">
                <a href="index.php">Dashboard</a>
                <a href="create-session.php">New Test</a>
                <a href="analytics.php">Analytics</a>
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
            <!-- Session Header -->
            <div class="session-header">
                <div class="session-info">
                    <h1>📝 <?= htmlspecialchars($session['test_name']) ?></h1>
                    <div class="session-meta">
                        <span class="meta-item">📅 <?= date('M d, Y', strtotime($session['test_date'])) ?></span>
                        <span class="meta-item">🎯 <?= htmlspecialchars($session['test_type']) ?></span>
                        <span class="meta-item">📊 <?= $total_logged ?>/<?= $session['total_questions'] ?> questions logged</span>
                        <?php if ($session['notes']): ?>
                            <span class="meta-item">📝 <?= htmlspecialchars($session['notes']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="session-actions">
                    <?php if ($total_logged < $session['total_questions']): ?>
                        <a href="log-question.php?session_id=<?= $session_id ?>" class="btn btn-primary">
                            ➕ Continue Logging
                        </a>
                    <?php endif; ?>
                    <button onclick="window.print()" class="btn btn-secondary">🖨️ Print Report</button>
                </div>
            </div>

            <?php if ($total_logged > 0): ?>
                <!-- Session Statistics -->
                <div class="session-stats">
                    <h2>📊 Session Performance</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">✅</div>
                            <div class="stat-number"><?= $correct_answers ?></div>
                            <div class="stat-label">Correct Answers</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">🎯</div>
                            <div class="stat-number"><?= $session_accuracy ?>%</div>
                            <div class="stat-label">Accuracy</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">💪</div>
                            <div class="stat-number"><?= $avg_confidence ?>/10</div>
                            <div class="stat-label">Avg Confidence</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">⏱️</div>
                            <div class="stat-number"><?= $avg_time_spent ?>s</div>
                            <div class="stat-label">Avg Time</div>
                        </div>
                    </div>
                </div>

                <!-- Session Charts -->
                <div class="charts-section">
                    <!-- Feelings Distribution -->
                    <?php if (!empty($session_feelings)): ?>
                        <div class="chart-container">
                            <div class="chart-header">
                                <h2>💭 Your Feelings This Session</h2>
                                <p>How you felt about questions in this test</p>
                            </div>
                            <div class="chart-wrapper">
                                <canvas id="sessionFeelingsChart"></canvas>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Subject Performance -->
                    <?php if (!empty($session_subjects)): ?>
                        <div class="chart-container">
                            <div class="chart-header">
                                <h2>📚 Subject Performance</h2>
                                <p>Your accuracy by subject in this session</p>
                            </div>
                            <div class="chart-wrapper">
                                <canvas id="sessionSubjectsChart"></canvas>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Question-by-Question Analysis -->
                <div class="questions-analysis">
                    <h2>📋 Question-by-Question Analysis</h2>
                    <div class="questions-grid">
                        <?php foreach ($questions as $question): ?>
                            <div class="question-card <?= $question['is_correct'] === 1 ? 'correct' : ($question['is_correct'] === 0 ? 'incorrect' : 'unanswered') ?>">
                                <div class="question-header">
                                    <div class="question-number">Q<?= $question['question_number'] ?></div>
                                    <div class="question-result">
                                        <?php if ($question['is_correct'] === 1): ?>
                                            <span class="result-icon correct">✅</span>
                                        <?php elseif ($question['is_correct'] === 0): ?>
                                            <span class="result-icon incorrect">❌</span>
                                        <?php else: ?>
                                            <span class="result-icon unanswered">❓</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="question-details">
                                    <div class="question-feeling">
                                        <strong>Feeling:</strong> <?= ucfirst(str_replace('_', ' ', $question['feeling'])) ?>
                                    </div>
                                    
                                    <?php if ($question['subject']): ?>
                                        <div class="question-subject">
                                            <strong>Subject:</strong> <?= htmlspecialchars($question['subject']) ?>
                                            <?php if ($question['topic']): ?>
                                                → <?= htmlspecialchars($question['topic']) ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="question-metrics">
                                        <span class="metric">
                                            <strong>Confidence:</strong> <?= $question['confidence_level'] ?>/10
                                        </span>
                                        <span class="metric">
                                            <strong>Difficulty:</strong> <?= ucfirst($question['difficulty']) ?>
                                        </span>
                                        <?php if ($question['time_spent']): ?>
                                            <span class="metric">
                                                <strong>Time:</strong> <?= $question['time_spent'] ?>s
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($question['my_answer'] && $question['correct_answer']): ?>
                                        <div class="question-answers">
                                            <span class="answer">Your: <?= $question['my_answer'] ?></span>
                                            <span class="answer">Correct: <?= $question['correct_answer'] ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($question['question_text']): ?>
                                        <div class="question-text">
                                            <strong>Question:</strong> <?= htmlspecialchars(substr($question['question_text'], 0, 150)) ?>
                                            <?php if (strlen($question['question_text']) > 150): ?>...<?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($question['explanation']): ?>
                                        <div class="question-explanation">
                                            <strong>Notes:</strong> <?= htmlspecialchars($question['explanation']) ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($question['tags']): ?>
                                        <div class="question-tags">
                                            <?php foreach (explode(',', $question['tags']) as $tag): ?>
                                                <span class="tag"><?= htmlspecialchars(trim($tag)) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($question['review_needed']): ?>
                                        <div class="review-flag">🔄 Marked for review</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Session Insights -->
                <div class="session-insights">
                    <h2>🔍 Session Insights</h2>
                    <div class="insights-grid">
                        <?php
                        // Generate session-specific insights
                        $insights = [];
                        
                        // Most common feeling
                        if (!empty($session_feelings)) {
                            usort($session_feelings, fn($a, $b) => $b['count'] <=> $a['count']);
                            $most_feeling = $session_feelings[0];
                            $feeling_name = ucfirst(str_replace('_', ' ', $most_feeling['feeling']));
                            $insights[] = [
                                'icon' => '💭',
                                'title' => 'Dominant Feeling',
                                'text' => "You mostly felt <strong>{$feeling_name}</strong> ({$most_feeling['count']} questions)"
                            ];
                        }
                        
                        // Best subject
                        if (!empty($session_subjects)) {
                            usort($session_subjects, fn($a, $b) => ($b['correct_answers']/$b['total_questions']) <=> ($a['correct_answers']/$a['total_questions']));
                            $best_subj = $session_subjects[0];
                            $best_acc = round(($best_subj['correct_answers'] / $best_subj['total_questions']) * 100, 1);
                            $insights[] = [
                                'icon' => '📈',
                                'title' => 'Strongest Subject',
                                'text' => "<strong>{$best_subj['subject']}</strong> ({$best_acc}% accuracy)"
                            ];
                        }
                        
                        // Performance summary
                        if ($session_accuracy >= 75) {
                            $insights[] = [
                                'icon' => '🎉',
                                'title' => 'Great Performance!',
                                'text' => "Excellent accuracy of {$session_accuracy}%. Keep up the good work!"
                            ];
                        } elseif ($session_accuracy >= 50) {
                            $insights[] = [
                                'icon' => '👍',
                                'title' => 'Good Progress',
                                'text' => "Decent performance at {$session_accuracy}%. Room for improvement."
                            ];
                        } else {
                            $insights[] = [
                                'icon' => '💪',
                                'title' => 'Learning Opportunity',
                                'text' => "Focus on weak areas. Review questions marked for review."
                            ];
                        }
                        
                        // Review needed count
                        $review_count = array_sum(array_column($questions, 'review_needed'));
                        if ($review_count > 0) {
                            $insights[] = [
                                'icon' => '🔄',
                                'title' => 'Review Required',
                                'text' => "{$review_count} questions marked for review. Prioritize these in your study plan."
                            ];
                        }
                        ?>
                        
                        <?php foreach ($insights as $insight): ?>
                            <div class="insight-card">
                                <div class="insight-icon"><?= $insight['icon'] ?></div>
                                <h3><?= $insight['title'] ?></h3>
                                <p><?= $insight['text'] ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">📝</div>
                    <h3>No Questions Logged Yet</h3>
                    <p>Start logging questions to see detailed analysis of this session.</p>
                    <a href="log-question.php?session_id=<?= $session_id ?>" class="btn btn-primary">Start Logging Questions</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Manual Test Analysis Platform. Track your progress! 📈</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
    <script>
        // Session Feelings Chart
        <?php if (!empty($session_feelings)): ?>
        const sessionFeelingsCtx = document.getElementById('sessionFeelingsChart').getContext('2d');
        new Chart(sessionFeelingsCtx, {
            type: 'pie',
            data: {
                labels: [<?php echo implode(',', array_map(fn($f) => '"' . ucfirst(str_replace('_', ' ', $f['feeling'])) . '"', $session_feelings)); ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_column($session_feelings, 'count')); ?>],
                    backgroundColor: [
                        '#4CAF50', '#2196F3', '#FF9800', '#F44336', '#9C27B0', '#607D8B'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 15 }
                    }
                }
            }
        });
        <?php endif; ?>

        // Session Subjects Chart
        <?php if (!empty($session_subjects)): ?>
        const sessionSubjectsCtx = document.getElementById('sessionSubjectsChart').getContext('2d');
        new Chart(sessionSubjectsCtx, {
            type: 'horizontalBar',
            data: {
                labels: [<?php echo implode(',', array_map(fn($s) => '"' . htmlspecialchars($s['subject']) . '"', $session_subjects)); ?>],
                datasets: [{
                    label: 'Accuracy (%)',
                    data: [<?php echo implode(',', array_map(fn($s) => round(($s['correct_answers'] / $s['total_questions']) * 100, 1), $session_subjects)); ?>],
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) { return value + '%'; }
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>