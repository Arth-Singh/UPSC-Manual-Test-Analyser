<?php
include_once 'config/database.php';
include_once 'config/claude_api.php';
require_once 'config/profile_check.php';

// Get comprehensive statistics
$profile_stats = getProfileOverallStats($pdo, $current_profile_id);
$total_questions = $profile_stats['total_questions'];

// Enhanced Analytics Queries
$advanced_stats = [];

if ($total_questions > 0) {
    // 1. Time-based performance analysis
    $time_analysis = $pdo->prepare("
        SELECT 
            MONTH(ts.test_date) as month,
            YEAR(ts.test_date) as year,
            COUNT(ql.id) as questions,
            AVG(CASE WHEN ql.is_correct = 1 THEN 1 ELSE 0 END) * 100 as accuracy,
            AVG(ql.time_spent) as avg_time,
            COUNT(DISTINCT ts.id) as sessions
        FROM test_sessions ts
        LEFT JOIN question_logs ql ON ts.id = ql.session_id
        WHERE ts.profile_id = ?
        GROUP BY YEAR(ts.test_date), MONTH(ts.test_date)
        ORDER BY year DESC, month DESC
        LIMIT 12
    ");
    $time_analysis->execute([$current_profile_id]);
    $advanced_stats['time_analysis'] = $time_analysis->fetchAll();

    // 2. Feeling vs Performance Deep Analysis
    $feeling_performance = $pdo->prepare("
        SELECT 
            feeling,
            COUNT(*) as total_questions,
            SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct,
            AVG(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) * 100 as accuracy,
            AVG(time_spent) as avg_time,
            MIN(time_spent) as min_time,
            MAX(time_spent) as max_time,
            STDDEV(time_spent) as time_stddev
        FROM question_logs ql
        JOIN test_sessions ts ON ql.session_id = ts.id
        WHERE ts.profile_id = ? AND feeling IS NOT NULL
        GROUP BY feeling
        ORDER BY accuracy DESC
    ");
    $feeling_performance->execute([$current_profile_id]);
    $advanced_stats['feeling_performance'] = $feeling_performance->fetchAll();

    // 3. Subject mastery progression
    $subject_progression = $pdo->prepare("
        SELECT 
            subject,
            COUNT(*) as total_questions,
            SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct,
            AVG(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) * 100 as accuracy,
            AVG(time_spent) as avg_time,
            MIN(ts.test_date) as first_attempt,
            MAX(ts.test_date) as latest_attempt,
            COUNT(DISTINCT ts.id) as sessions_involved
        FROM question_logs ql
        JOIN test_sessions ts ON ql.session_id = ts.id
        WHERE ts.profile_id = ? AND subject IS NOT NULL
        GROUP BY subject
        ORDER BY accuracy DESC
    ");
    $subject_progression->execute([$current_profile_id]);
    $advanced_stats['subject_progression'] = $subject_progression->fetchAll();

    // 4. Speed vs Accuracy correlation
    $speed_accuracy = $pdo->prepare("
        SELECT 
            CASE 
                WHEN time_spent <= 30 THEN 'Very Fast (≤30s)'
                WHEN time_spent <= 60 THEN 'Fast (31-60s)'
                WHEN time_spent <= 90 THEN 'Medium (61-90s)'
                WHEN time_spent <= 120 THEN 'Slow (91-120s)'
                ELSE 'Very Slow (>120s)'
            END as speed_category,
            COUNT(*) as questions,
            AVG(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) * 100 as accuracy,
            AVG(time_spent) as avg_time
        FROM question_logs ql
        JOIN test_sessions ts ON ql.session_id = ts.id
        WHERE ts.profile_id = ? AND time_spent IS NOT NULL
        GROUP BY speed_category
        ORDER BY avg_time
    ");
    $speed_accuracy->execute([$current_profile_id]);
    $advanced_stats['speed_accuracy'] = $speed_accuracy->fetchAll();

    // 5. Weekly performance patterns
    $weekly_patterns = $pdo->prepare("
        SELECT 
            DAYNAME(ts.test_date) as day_of_week,
            COUNT(ql.id) as questions,
            AVG(CASE WHEN ql.is_correct = 1 THEN 1 ELSE 0 END) * 100 as accuracy,
            AVG(ql.time_spent) as avg_time,
            COUNT(DISTINCT ts.id) as sessions
        FROM test_sessions ts
        LEFT JOIN question_logs ql ON ts.id = ql.session_id
        WHERE ts.profile_id = ?
        GROUP BY DAYOFWEEK(ts.test_date), DAYNAME(ts.test_date)
        ORDER BY DAYOFWEEK(ts.test_date)
    ");
    $weekly_patterns->execute([$current_profile_id]);
    $advanced_stats['weekly_patterns'] = $weekly_patterns->fetchAll();

    // 6. Error pattern analysis
    $error_patterns = $pdo->prepare("
        SELECT 
            subject,
            feeling,
            COUNT(*) as wrong_answers,
            AVG(time_spent) as avg_time_on_wrong,
            GROUP_CONCAT(DISTINCT topic SEPARATOR ', ') as problematic_topics
        FROM question_logs ql
        JOIN test_sessions ts ON ql.session_id = ts.id
        WHERE ts.profile_id = ? AND ql.is_correct = 0
        GROUP BY subject, feeling
        HAVING wrong_answers >= 2
        ORDER BY wrong_answers DESC
        LIMIT 20
    ");
    $error_patterns->execute([$current_profile_id]);
    $advanced_stats['error_patterns'] = $error_patterns->fetchAll();

    // 7. Learning curve analysis
    $learning_curve = $pdo->prepare("
        SELECT 
            ROW_NUMBER() OVER (ORDER BY ts.test_date, ql.id) as question_sequence,
            ql.is_correct,
            ql.feeling,
            ql.subject,
            ts.test_date,
            ql.time_spent
        FROM question_logs ql
        JOIN test_sessions ts ON ql.session_id = ts.id
        WHERE ts.profile_id = ?
        ORDER BY ts.test_date, ql.id
    ");
    $learning_curve->execute([$current_profile_id]);
    $advanced_stats['learning_curve'] = $learning_curve->fetchAll();

    // 8. Confidence calibration analysis
    $confidence_calibration = $pdo->prepare("
        SELECT 
            CASE 
                WHEN confidence_level <= 3 THEN 'Low (1-3)'
                WHEN confidence_level <= 6 THEN 'Medium (4-6)'
                WHEN confidence_level <= 8 THEN 'High (7-8)'
                ELSE 'Very High (9-10)'
            END as confidence_range,
            COUNT(*) as questions,
            AVG(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) * 100 as actual_accuracy,
            AVG(confidence_level) as avg_confidence_level
        FROM question_logs ql
        JOIN test_sessions ts ON ql.session_id = ts.id
        WHERE ts.profile_id = ? AND confidence_level IS NOT NULL
        GROUP BY confidence_range
        ORDER BY avg_confidence_level
    ");
    $confidence_calibration->execute([$current_profile_id]);
    $advanced_stats['confidence_calibration'] = $confidence_calibration->fetchAll();
}

// Generate AI insights if API key available
$ai_insights = null;
$api_key = getProfileClaudeAPI($pdo, $current_profile_id);
if ($api_key && $total_questions > 0) {
    $ai_insights = generateProfileRecommendations($pdo, $current_profile_id);
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Analytics - Test Analysis Platform</title>
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
                <a href="advanced-analytics.php" class="active">Advanced</a>
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
            <div class="page-header">
                <h1>Advanced Statistical Analysis</h1>
                <p>Deep insights into your performance patterns and learning progression</p>
            </div>

            <?php if ($total_questions > 0): ?>
                
                <!-- AI Insights Section -->
                <?php if ($ai_insights): ?>
                <div class="ai-insights-section">
                    <h2>AI-Powered Insights</h2>
                    <div class="ai-insight-card">
                        <div class="ai-content">
                            <?= nl2br(htmlspecialchars($ai_insights['content'])) ?>
                        </div>
                        <div class="ai-footer">
                            <small>Generated by Claude 3.5 Haiku on <?= $ai_insights['generated_at'] ?></small>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Performance Timeline -->
                <div class="chart-container">
                    <div class="chart-header">
                        <h2>Performance Timeline</h2>
                        <p>Monthly accuracy trends and question volume</p>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="timelineChart"></canvas>
                    </div>
                </div>

                <!-- Feeling vs Performance Analysis -->
                <div class="stats-table-container">
                    <h2>Feeling-Based Performance Analysis</h2>
                    <div class="table-responsive">
                        <table class="analytics-table">
                            <thead>
                                <tr>
                                    <th>Feeling</th>
                                    <th>Questions</th>
                                    <th>Accuracy</th>
                                    <th>Avg Time</th>
                                    <th>Time Range</th>
                                    <th>Consistency</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($advanced_stats['feeling_performance'] as $feeling): ?>
                                    <?php 
                                    $consistency = $feeling['time_stddev'] ? round($feeling['time_stddev'], 1) : 0;
                                    $time_range = $feeling['min_time'] && $feeling['max_time'] ? 
                                        round($feeling['min_time']) . '-' . round($feeling['max_time']) . 's' : 'N/A';
                                    ?>
                                    <tr>
                                        <td><strong><?= ucfirst(str_replace('_', ' ', $feeling['feeling'])) ?></strong></td>
                                        <td><?= $feeling['total_questions'] ?></td>
                                        <td class="<?= $feeling['accuracy'] >= 60 ? 'text-success' : 'text-danger' ?>">
                                            <?= round($feeling['accuracy'], 1) ?>%
                                        </td>
                                        <td><?= $feeling['avg_time'] ? round($feeling['avg_time']) . 's' : 'N/A' ?></td>
                                        <td><?= $time_range ?></td>
                                        <td><?= $consistency > 0 ? $consistency . 's σ' : 'N/A' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Speed vs Accuracy Chart -->
                <div class="chart-container">
                    <div class="chart-header">
                        <h2>Speed vs Accuracy Analysis</h2>
                        <p>How your response time affects accuracy</p>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="speedAccuracyChart"></canvas>
                    </div>
                </div>

                <!-- Subject Mastery Matrix -->
                <div class="stats-table-container">
                    <h2>Subject Mastery Analysis</h2>
                    <div class="table-responsive">
                        <table class="analytics-table">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Total Questions</th>
                                    <th>Accuracy</th>
                                    <th>Sessions</th>
                                    <th>Avg Time</th>
                                    <th>First Attempt</th>
                                    <th>Latest Attempt</th>
                                    <th>Mastery Level</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($advanced_stats['subject_progression'] as $subject): ?>
                                    <?php 
                                    $mastery = 'Beginner';
                                    if ($subject['accuracy'] >= 80) $mastery = 'Expert';
                                    elseif ($subject['accuracy'] >= 65) $mastery = 'Advanced';
                                    elseif ($subject['accuracy'] >= 50) $mastery = 'Intermediate';
                                    ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($subject['subject']) ?></strong></td>
                                        <td><?= $subject['total_questions'] ?></td>
                                        <td class="<?= $subject['accuracy'] >= 60 ? 'text-success' : 'text-danger' ?>">
                                            <?= round($subject['accuracy'], 1) ?>%
                                        </td>
                                        <td><?= $subject['sessions_involved'] ?></td>
                                        <td><?= $subject['avg_time'] ? round($subject['avg_time']) . 's' : 'N/A' ?></td>
                                        <td><?= date('M d', strtotime($subject['first_attempt'])) ?></td>
                                        <td><?= date('M d', strtotime($subject['latest_attempt'])) ?></td>
                                        <td><span class="mastery-level mastery-<?= strtolower($mastery) ?>"><?= $mastery ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Weekly Performance Patterns -->
                <div class="chart-container">
                    <div class="chart-header">
                        <h2>Weekly Performance Patterns</h2>
                        <p>Your performance varies by day of the week</p>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="weeklyChart"></canvas>
                    </div>
                </div>

                <!-- Confidence Calibration -->
                <div class="chart-container">
                    <div class="chart-header">
                        <h2>Confidence Calibration</h2>
                        <p>How well your confidence predicts actual performance</p>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="confidenceChart"></canvas>
                    </div>
                </div>

                <!-- Error Patterns Analysis -->
                <?php if (!empty($advanced_stats['error_patterns'])): ?>
                <div class="stats-table-container">
                    <h2>Error Pattern Analysis</h2>
                    <div class="table-responsive">
                        <table class="analytics-table">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Feeling</th>
                                    <th>Wrong Answers</th>
                                    <th>Avg Time on Wrong</th>
                                    <th>Problematic Topics</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($advanced_stats['error_patterns'] as $pattern): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($pattern['subject']) ?></td>
                                        <td><?= ucfirst(str_replace('_', ' ', $pattern['feeling'])) ?></td>
                                        <td><?= $pattern['wrong_answers'] ?></td>
                                        <td><?= $pattern['avg_time_on_wrong'] ? round($pattern['avg_time_on_wrong']) . 's' : 'N/A' ?></td>
                                        <td class="topics-cell"><?= htmlspecialchars(substr($pattern['problematic_topics'], 0, 50)) ?><?= strlen($pattern['problematic_topics']) > 50 ? '...' : '' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Learning Curve -->
                <div class="chart-container">
                    <div class="chart-header">
                        <h2>Learning Curve (Rolling Average)</h2>
                        <p>Your accuracy improvement over time (50-question rolling average)</p>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="learningCurveChart"></canvas>
                    </div>
                </div>

            <?php else: ?>
                <div class="empty-state">
                    <h3>No Data Available</h3>
                    <p>Start logging questions to see advanced statistical analysis!</p>
                    <a href="create-session.php" class="btn btn-primary">Create First Session</a>
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
    <script>
        // Chart.js configuration
        Chart.defaults.font.family = "'Inter', 'Segoe UI', sans-serif";
        Chart.defaults.font.size = 12;
        Chart.defaults.responsive = true;
        Chart.defaults.maintainAspectRatio = false;

        <?php if ($total_questions > 0): ?>
            
        // Timeline Chart
        <?php if (!empty($advanced_stats['time_analysis'])): ?>
        const timelineCtx = document.getElementById('timelineChart').getContext('2d');
        new Chart(timelineCtx, {
            type: 'line',
            data: {
                labels: [<?php echo implode(',', array_map(fn($t) => '"' . date('M Y', mktime(0,0,0,$t['month'],1,$t['year'])) . '"', array_reverse($advanced_stats['time_analysis']))); ?>],
                datasets: [
                    {
                        label: 'Accuracy (%)',
                        data: [<?php echo implode(',', array_map(fn($t) => round($t['accuracy'], 1), array_reverse($advanced_stats['time_analysis']))); ?>],
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        tension: 0.4,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Questions',
                        data: [<?php echo implode(',', array_column(array_reverse($advanced_stats['time_analysis']), 'questions')); ?>],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: { display: true, text: 'Accuracy (%)' },
                        min: 0,
                        max: 100
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: { display: true, text: 'Questions' },
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });
        <?php endif; ?>

        // Speed vs Accuracy Chart
        <?php if (!empty($advanced_stats['speed_accuracy'])): ?>
        const speedCtx = document.getElementById('speedAccuracyChart').getContext('2d');
        new Chart(speedCtx, {
            type: 'bar',
            data: {
                labels: [<?php echo implode(',', array_map(fn($s) => '"' . $s['speed_category'] . '"', $advanced_stats['speed_accuracy'])); ?>],
                datasets: [{
                    label: 'Accuracy (%)',
                    data: [<?php echo implode(',', array_map(fn($s) => round($s['accuracy'], 1), $advanced_stats['speed_accuracy'])); ?>],
                    backgroundColor: 'rgba(59, 130, 246, 0.6)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: { display: true, text: 'Accuracy (%)' }
                    }
                }
            }
        });
        <?php endif; ?>

        // Weekly Patterns Chart
        <?php if (!empty($advanced_stats['weekly_patterns'])): ?>
        const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
        new Chart(weeklyCtx, {
            type: 'radar',
            data: {
                labels: [<?php echo implode(',', array_map(fn($w) => '"' . $w['day_of_week'] . '"', $advanced_stats['weekly_patterns'])); ?>],
                datasets: [{
                    label: 'Accuracy (%)',
                    data: [<?php echo implode(',', array_map(fn($w) => round($w['accuracy'], 1), $advanced_stats['weekly_patterns'])); ?>],
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.2)',
                    pointBackgroundColor: '#f59e0b',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#f59e0b'
                }]
            },
            options: {
                elements: {
                    line: {
                        borderWidth: 3
                    }
                },
                scales: {
                    r: {
                        angleLines: {
                            display: false
                        },
                        suggestedMin: 0,
                        suggestedMax: 100
                    }
                }
            }
        });
        <?php endif; ?>

        // Confidence Calibration Chart
        <?php if (!empty($advanced_stats['confidence_calibration'])): ?>
        const confCtx = document.getElementById('confidenceChart').getContext('2d');
        new Chart(confCtx, {
            type: 'scatter',
            data: {
                datasets: [{
                    label: 'Confidence vs Accuracy',
                    data: [
                        <?php foreach ($advanced_stats['confidence_calibration'] as $conf): ?>
                        {x: <?= round($conf['avg_confidence_level'], 1) ?>, y: <?= round($conf['actual_accuracy'], 1) ?>},
                        <?php endforeach; ?>
                    ],
                    backgroundColor: 'rgba(239, 68, 68, 0.6)',
                    borderColor: 'rgba(239, 68, 68, 1)',
                    pointRadius: 8
                }, {
                    label: 'Perfect Calibration',
                    data: [{x: 1, y: 10}, {x: 5, y: 50}, {x: 10, y: 100}],
                    type: 'line',
                    borderColor: 'rgba(156, 163, 175, 0.5)',
                    borderDash: [5, 5],
                    pointRadius: 0,
                    fill: false
                }]
            },
            options: {
                scales: {
                    x: {
                        title: { display: true, text: 'Confidence Level' },
                        min: 1,
                        max: 10
                    },
                    y: {
                        title: { display: true, text: 'Actual Accuracy (%)' },
                        min: 0,
                        max: 100
                    }
                }
            }
        });
        <?php endif; ?>

        // Learning Curve Chart
        <?php if (!empty($advanced_stats['learning_curve'])): ?>
        const learningData = [<?php echo implode(',', array_map(fn($l) => $l['is_correct'] ? '1' : '0', $advanced_stats['learning_curve'])); ?>];
        
        // Calculate rolling average
        const rollingAverage = [];
        const windowSize = 50;
        for (let i = windowSize - 1; i < learningData.length; i++) {
            const window = learningData.slice(i - windowSize + 1, i + 1);
            const avg = window.reduce((a, b) => a + parseInt(b), 0) / windowSize * 100;
            rollingAverage.push({x: i + 1, y: avg});
        }
        
        const learningCtx = document.getElementById('learningCurveChart').getContext('2d');
        new Chart(learningCtx, {
            type: 'line',
            data: {
                datasets: [{
                    label: 'Rolling Accuracy (50 questions)',
                    data: rollingAverage,
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    tension: 0.4,
                    pointRadius: 1
                }]
            },
            options: {
                scales: {
                    x: {
                        title: { display: true, text: 'Question Number' }
                    },
                    y: {
                        title: { display: true, text: 'Accuracy (%)' },
                        min: 0,
                        max: 100
                    }
                }
            }
        });
        <?php endif; ?>

        <?php endif; ?>
    </script>
</body>
</html>