<?php
include_once 'config/database.php';
require_once 'config/profile_check.php';

$session_id = $_GET['session_id'] ?? 0;

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

// Get current progress
$logged_questions = $pdo->prepare("SELECT COUNT(*) FROM question_logs WHERE session_id = ?");
$logged_questions->execute([$session_id]);
$current_count = $logged_questions->fetchColumn();

$next_question_number = $current_count + 1;

// Handle form submission
if ($_POST) {
    $question_number = (int)$_POST['question_number'];
    $subject = trim($_POST['subject']);
    $topic = trim($_POST['topic']);
    $feeling = $_POST['feeling'];
    $time_spent = $_POST['time_spent'] ? (int)$_POST['time_spent'] : null;
    $my_answer = $_POST['my_answer'] ?: null;
    $correct_answer = $_POST['correct_answer'] ?: null;
    $question_text = trim($_POST['question_text']);
    $explanation = trim($_POST['explanation']);
    $review_needed = isset($_POST['review_needed']) ? 1 : 0;
    
    // Calculate if answer is correct
    $is_correct = null;
    if ($my_answer && $correct_answer) {
        $is_correct = ($my_answer === $correct_answer) ? 1 : 0;
    }
    
    if ($question_number && $feeling) {
        $sql = "INSERT INTO question_logs (session_id, question_number, subject, topic, feeling, 
                time_spent, my_answer, correct_answer, is_correct, question_text, 
                explanation, review_needed) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = executeQuery($pdo, $sql, [
            $session_id, $question_number, $subject, $topic, $feeling,
            $time_spent, $my_answer, $correct_answer, $is_correct,
            $question_text, $explanation, $review_needed
        ]);
        
        if ($stmt) {
            // Update session progress
            $pdo->prepare("UPDATE test_sessions SET completed_questions = ? WHERE id = ?")
                ->execute([$current_count + 1, $session_id]);
                
            setFlashMessage("Question {$question_number} logged successfully!", 'success');
            
            // Check if test is complete
            if ($question_number >= $session['total_questions']) {
                header("Location: view-session.php?id=$session_id&completed=1");
                exit;
            } else {
                header("Location: log-question.php?session_id=$session_id");
                exit;
            }
        } else {
            setFlashMessage('Error logging question.', 'error');
        }
    } else {
        setFlashMessage('Please fill required fields.', 'error');
    }
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Question - <?= htmlspecialchars($session['test_name']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
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
                        <span class="meta-item">📊 <?= $current_count ?>/<?= $session['total_questions'] ?> questions logged</span>
                        <span class="meta-item">🎯 <?= $session['test_type'] ?></span>
                    </div>
                </div>
                
                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= ($current_count / $session['total_questions']) * 100 ?>%"></div>
                    </div>
                    <div class="progress-text"><?= round(($current_count / $session['total_questions']) * 100, 1) ?>% Complete</div>
                </div>
            </div>

            <!-- Question Logging Form -->
            <div class="question-form-container">
                <form method="POST" class="question-form-laptop">
                    <input type="hidden" name="question_number" value="<?= $next_question_number ?>">
                    
                    <div class="form-header-inline">
                        <h2>Question #<?= $next_question_number ?></h2>
                        <div class="quick-actions-inline">
                            <button type="submit" class="btn btn-primary">Log & Continue</button>
                            <a href="view-session.php?id=<?= $session_id ?>" class="btn btn-secondary">View Progress</a>
                        </div>
                    </div>
                    
                    <!-- Main Form Grid -->
                    <div class="form-grid-laptop">
                        <!-- Left Column -->
                        <div class="form-column">
                            <div class="form-group">
                                <label for="feeling">Feeling *</label>
                                <select id="feeling" name="feeling" required>
                                    <option value="">Select feeling</option>
                                    <option value="confident">Confident</option>
                                    <option value="guessed">Guessed</option>
                                    <option value="confused">Confused</option>
                                    <option value="blank">Blank</option>
                                    <option value="time_pressure">Time Pressure</option>
                                    <option value="careless">Careless</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="subject">Subject</label>
                                <select id="subject" name="subject">
                                    <option value="">Select Subject</option>
                                    <option value="History">History</option>
                                    <option value="Geography">Geography</option>
                                    <option value="Polity">Polity</option>
                                    <option value="Economy">Economy</option>
                                    <option value="Science">Science & Tech</option>
                                    <option value="Environment">Environment</option>
                                    <option value="Current Affairs">Current Affairs</option>
                                    <option value="Art & Culture">Art & Culture</option>
                                    <option value="Ethics">Ethics</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="topic">Topic</label>
                                <input type="text" id="topic" name="topic" placeholder="Subtopic or area">
                            </div>

                            <div class="form-group">
                                <label for="time_spent">Time (seconds)</label>
                                <input type="number" id="time_spent" name="time_spent" min="0" max="600" placeholder="90">
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="form-column">
                            <div class="form-row-compact">
                                <div class="form-group">
                                    <label for="my_answer">Your Answer</label>
                                    <select id="my_answer" name="my_answer">
                                        <option value="">-</option>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                        <option value="D">D</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="correct_answer">Correct</label>
                                    <select id="correct_answer" name="correct_answer">
                                        <option value="">-</option>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                        <option value="D">D</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="question_text">Question Text (optional)</label>
                                <textarea id="question_text" name="question_text" rows="2" 
                                          placeholder="Copy question for reference..."></textarea>
                            </div>

                            <div class="form-group">
                                <label for="explanation">Notes (optional)</label>
                                <textarea id="explanation" name="explanation" rows="2" 
                                          placeholder="Explanation, learnings, mistakes..."></textarea>
                            </div>

                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" id="review_needed" name="review_needed">
                                    <label for="review_needed">Mark for review</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Manual Test Analysis Platform. Track your progress! 📈</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
    <script>
        // Auto-save form data to localStorage
        const form = document.querySelector('.question-form');
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            // Load saved data
            const savedValue = localStorage.getItem(`question_${input.name}`);
            if (savedValue && input.type !== 'hidden') {
                input.value = savedValue;
            }
            
            // Save on change
            input.addEventListener('change', () => {
                localStorage.setItem(`question_${input.name}`, input.value);
            });
        });
        
        // Clear localStorage on successful submission
        form.addEventListener('submit', () => {
            inputs.forEach(input => {
                localStorage.removeItem(`question_${input.name}`);
            });
        });
    </script>
</body>
</html>