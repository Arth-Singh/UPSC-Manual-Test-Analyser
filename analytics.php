<?php
include_once 'config/database.php';

// Get overall statistics
$total_sessions = $pdo->query("SELECT COUNT(*) FROM test_sessions")->fetchColumn();
$total_questions = $pdo->query("SELECT COUNT(*) FROM question_logs")->fetchColumn();
$total_correct = $pdo->query("SELECT COUNT(*) FROM question_logs WHERE is_correct = 1")->fetchColumn();
$overall_accuracy = $total_questions > 0 ? round(($total_correct / $total_questions) * 100, 1) : 0;

// Get feeling distribution
$feelings_data = getAnalysisData($pdo);

// Get subject performance
$subjects_data = getSubjectAnalysis($pdo);

// Get time-based analysis
$time_analysis = $pdo->query("
    SELECT 
        DATE(test_date) as date,
        COUNT(DISTINCT test_sessions.id) as sessions,
        COUNT(question_logs.id) as questions,
        AVG(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) * 100 as accuracy
    FROM test_sessions 
    LEFT JOIN question_logs ON test_sessions.id = question_logs.session_id
    WHERE test_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(test_date)
    ORDER BY date DESC
")->fetchAll();

// Get confidence vs accuracy correlation
$confidence_accuracy = $pdo->query("
    SELECT 
        confidence_level,
        COUNT(*) as count,
        AVG(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) * 100 as accuracy
    FROM question_logs 
    WHERE is_correct IS NOT NULL
    GROUP BY confidence_level
    ORDER BY confidence_level
")->fetchAll();

// Get difficulty analysis
$difficulty_analysis = $pdo->query("
    SELECT 
        difficulty,
        COUNT(*) as total,
        SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct,
        AVG(confidence_level) as avg_confidence,
        AVG(time_spent) as avg_time
    FROM question_logs 
    WHERE difficulty IS NOT NULL
    GROUP BY difficulty
    ORDER BY FIELD(difficulty, 'easy', 'medium', 'hard', 'very_hard')
")->fetchAll();

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - Test Analysis Platform</title>
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
                <a href="analytics.php" class="active">Analytics</a>
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
                <h1>📈 Analytics Dashboard</h1>
                <p>Comprehensive analysis of your test performance and learning patterns</p>
            </div>

            <!-- Key Metrics Overview -->
            <div class="metrics-overview">
                <div class="metrics-grid">
                    <div class="metric-card">
                        <div class="metric-icon">🎯</div>
                        <div class="metric-value"><?= $total_sessions ?></div>
                        <div class="metric-label">Total Sessions</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-icon">📝</div>
                        <div class="metric-value"><?= $total_questions ?></div>
                        <div class="metric-label">Questions Analyzed</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-icon">✅</div>
                        <div class="metric-value"><?= $total_correct ?></div>
                        <div class="metric-label">Correct Answers</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-icon">🎯</div>
                        <div class="metric-value"><?= $overall_accuracy ?>%</div>
                        <div class="metric-label">Overall Accuracy</div>
                    </div>
                </div>
            </div>

            <?php if ($total_questions > 0): ?>
                <!-- Charts Section -->
                <div class="charts-section">
                    <!-- Feelings Distribution -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <h2>💭 Feelings Distribution</h2>
                            <p>How you typically feel about questions</p>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="feelingsChart"></canvas>
                        </div>
                    </div>

                    <!-- Subject Performance -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <h2>📚 Subject-wise Performance</h2>
                            <p>Your accuracy across different subjects</p>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="subjectsChart"></canvas>
                        </div>
                    </div>

                    <!-- Confidence vs Accuracy -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <h2>🎯 Confidence vs Accuracy</h2>
                            <p>How your confidence correlates with correct answers</p>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="confidenceChart"></canvas>
                        </div>
                    </div>

                    <!-- Difficulty Analysis -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <h2>⚡ Difficulty Analysis</h2>
                            <p>Performance across different difficulty levels</p>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="difficultyChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Detailed Analytics Tables -->
                <div class="analytics-tables">
                    <!-- Feelings Analysis Table -->
                    <div class="table-container">
                        <h2>💭 Detailed Feelings Analysis</h2>
                        <div class="table-responsive">
                            <table class="analytics-table">
                                <thead>
                                    <tr>
                                        <th>Feeling</th>
                                        <th>Count</th>
                                        <th>Percentage</th>
                                        <th>Avg Confidence</th>
                                        <th>Accuracy</th>
                                        <th>Avg Time (sec)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($feelings_data as $feeling): ?>
                                        <?php 
                                        $percentage = round(($feeling['count'] / $total_questions) * 100, 1);
                                        $accuracy = $feeling['count'] > 0 ? round(($feeling['correct_count'] / $feeling['count']) * 100, 1) : 0;
                                        ?>
                                        <tr>
                                            <td><strong><?= ucfirst(str_replace('_', ' ', $feeling['feeling'])) ?></strong></td>
                                            <td><?= $feeling['count'] ?></td>
                                            <td><?= $percentage ?>%</td>
                                            <td><?= round($feeling['avg_confidence'], 1) ?>/10</td>
                                            <td class="<?= $accuracy >= 60 ? 'text-success' : 'text-danger' ?>"><?= $accuracy ?>%</td>
                                            <td><?= $feeling['avg_time'] ? round($feeling['avg_time']) : 'N/A' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Subject Performance Table -->
                    <div class="table-container">
                        <h2>📚 Subject Performance Breakdown</h2>
                        <div class="table-responsive">
                            <table class="analytics-table">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Questions</th>
                                        <th>Correct</th>
                                        <th>Accuracy</th>
                                        <th>Avg Confidence</th>
                                        <th>Avg Time (sec)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subjects_data as $subject): ?>
                                        <?php $accuracy = round(($subject['correct_answers'] / $subject['total_questions']) * 100, 1); ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($subject['subject']) ?></strong></td>
                                            <td><?= $subject['total_questions'] ?></td>
                                            <td><?= $subject['correct_answers'] ?></td>
                                            <td class="<?= $accuracy >= 60 ? 'text-success' : 'text-danger' ?>"><?= $accuracy ?>%</td>
                                            <td><?= round($subject['avg_confidence'], 1) ?>/10</td>
                                            <td><?= $subject['avg_time'] ? round($subject['avg_time']) : 'N/A' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Key Insights -->
                <div class="insights-section">
                    <h2>🔍 Key Insights & Recommendations</h2>
                    <div class="insights-grid">
                        <?php
                        // Generate insights based on data
                        $insights = [];
                        
                        // Most common feeling insight
                        $most_common_feeling = $feelings_data[0] ?? null;
                        if ($most_common_feeling) {
                            $feeling_name = ucfirst(str_replace('_', ' ', $most_common_feeling['feeling']));
                            $insights[] = [
                                'icon' => '💭',
                                'title' => 'Dominant Feeling',
                                'text' => "You most often feel <strong>{$feeling_name}</strong> about questions ({$most_common_feeling['count']} times). This indicates your general approach to problem-solving."
                            ];
                        }
                        
                        // Confidence vs accuracy insight
                        if (!empty($confidence_accuracy)) {
                            $high_conf = array_filter($confidence_accuracy, fn($c) => $c['confidence_level'] >= 8);
                            $high_conf_acc = !empty($high_conf) ? round(array_sum(array_column($high_conf, 'accuracy')) / count($high_conf), 1) : 0;
                            
                            $insights[] = [
                                'icon' => '🎯',
                                'title' => 'Confidence Calibration',
                                'text' => "When you're highly confident (8-10), your accuracy is <strong>{$high_conf_acc}%</strong>. " . 
                                         ($high_conf_acc >= 80 ? "Your confidence is well-calibrated!" : "Consider reviewing confident answers that were wrong.")
                            ];
                        }
                        
                        // Best and worst subjects
                        if (!empty($subjects_data)) {
                            usort($subjects_data, fn($a, $b) => ($b['correct_answers'] / $b['total_questions']) <=> ($a['correct_answers'] / $a['total_questions']));
                            $best_subject = $subjects_data[0];
                            $worst_subject = end($subjects_data);
                            
                            $best_acc = round(($best_subject['correct_answers'] / $best_subject['total_questions']) * 100, 1);
                            $worst_acc = round(($worst_subject['correct_answers'] / $worst_subject['total_questions']) * 100, 1);
                            
                            $insights[] = [
                                'icon' => '📈',
                                'title' => 'Subject Strengths',
                                'text' => "<strong>{$best_subject['subject']}</strong> is your strongest subject ({$best_acc}% accuracy). Consider using similar strategies for other subjects."
                            ];
                            
                            if ($worst_acc < 50) {
                                $insights[] = [
                                    'icon' => '📚',
                                    'title' => 'Improvement Area',
                                    'text' => "<strong>{$worst_subject['subject']}</strong> needs attention ({$worst_acc}% accuracy). Focus more practice time here."
                                ];
                            }
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
                    <div class="empty-icon">📊</div>
                    <h3>No Data Available</h3>
                    <p>Start logging questions to see detailed analytics and insights!</p>
                    <a href="create-session.php" class="btn btn-primary">Create First Session</a>
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
        // Chart.js configuration
        Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
        Chart.defaults.responsive = true;
        Chart.defaults.maintainAspectRatio = false;

        // Feelings Distribution Chart
        <?php if (!empty($feelings_data)): ?>
        const feelingsCtx = document.getElementById('feelingsChart').getContext('2d');
        new Chart(feelingsCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php echo implode(',', array_map(fn($f) => '"' . ucfirst(str_replace('_', ' ', $f['feeling'])) . '"', $feelings_data)); ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_column($feelings_data, 'count')); ?>],
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
                        labels: { padding: 20 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        // Subject Performance Chart
        <?php if (!empty($subjects_data)): ?>
        const subjectsCtx = document.getElementById('subjectsChart').getContext('2d');
        new Chart(subjectsCtx, {
            type: 'bar',
            data: {
                labels: [<?php echo implode(',', array_map(fn($s) => '"' . htmlspecialchars($s['subject']) . '"', $subjects_data)); ?>],
                datasets: [{
                    label: 'Accuracy (%)',
                    data: [<?php echo implode(',', array_map(fn($s) => round(($s['correct_answers'] / $s['total_questions']) * 100, 1), $subjects_data)); ?>],
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) { return value + '%'; }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Accuracy: ' + context.parsed.y + '%';
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        // Confidence vs Accuracy Chart
        <?php if (!empty($confidence_accuracy)): ?>
        const confidenceCtx = document.getElementById('confidenceChart').getContext('2d');
        new Chart(confidenceCtx, {
            type: 'scatter',
            data: {
                datasets: [{
                    label: 'Confidence vs Accuracy',
                    data: [<?php echo implode(',', array_map(fn($c) => '{x: ' . $c['confidence_level'] . ', y: ' . round($c['accuracy'], 1) . '}', $confidence_accuracy)); ?>],
                    backgroundColor: 'rgba(255, 99, 132, 0.6)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    pointRadius: 8,
                    pointHoverRadius: 10
                }]
            },
            options: {
                scales: {
                    x: {
                        title: { display: true, text: 'Confidence Level (1-10)' },
                        min: 1,
                        max: 10
                    },
                    y: {
                        title: { display: true, text: 'Accuracy (%)' },
                        beginAtZero: true,
                        max: 100
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Confidence: ${context.parsed.x}, Accuracy: ${context.parsed.y}%`;
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        // Difficulty Analysis Chart
        <?php if (!empty($difficulty_analysis)): ?>
        const difficultyCtx = document.getElementById('difficultyChart').getContext('2d');
        new Chart(difficultyCtx, {
            type: 'bar',
            data: {
                labels: [<?php echo implode(',', array_map(fn($d) => '"' . ucfirst($d['difficulty']) . '"', $difficulty_analysis)); ?>],
                datasets: [
                    {
                        label: 'Accuracy (%)',
                        data: [<?php echo implode(',', array_map(fn($d) => round(($d['correct'] / $d['total']) * 100, 1), $difficulty_analysis)); ?>],
                        backgroundColor: 'rgba(75, 192, 192, 0.6)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Avg Confidence',
                        data: [<?php echo implode(',', array_map(fn($d) => round($d['avg_confidence'], 1), $difficulty_analysis)); ?>],
                        backgroundColor: 'rgba(255, 206, 86, 0.6)',
                        borderColor: 'rgba(255, 206, 86, 1)',
                        borderWidth: 1,
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
                        beginAtZero: true,
                        max: 100
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: { display: true, text: 'Confidence (1-10)' },
                        beginAtZero: true,
                        max: 10,
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>