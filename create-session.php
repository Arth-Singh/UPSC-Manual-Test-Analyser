<?php
include_once 'config/database.php';
require_once 'config/profile_check.php';

if ($_POST) {
    $test_name = trim($_POST['test_name']);
    $test_date = $_POST['test_date'];
    $test_type = $_POST['test_type'];
    $total_questions = (int)$_POST['total_questions'];
    $notes = trim($_POST['notes']);
    
    if (empty($test_name) || empty($test_date) || $total_questions < 1) {
        setFlashMessage('Please fill all required fields correctly.', 'error');
    } else {
        $sql = "INSERT INTO test_sessions (profile_id, test_name, test_date, test_type, total_questions, notes) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = executeQuery($pdo, $sql, [$current_profile_id, $test_name, $test_date, $test_type, $total_questions, $notes]);
        
        if ($stmt) {
            $session_id = $pdo->lastInsertId();
            setFlashMessage('Test session created successfully!', 'success');
            header("Location: log-question.php?session_id=$session_id");
            exit;
        } else {
            setFlashMessage('Error creating test session.', 'error');
        }
    }
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Test Session</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
            <div class="page-header">
                <h1>✨ Create New Test Session</h1>
                <p>Start a new test session to log your question-by-question analysis</p>
            </div>

            <div class="form-container">
                <form method="POST" class="session-form">
                    <div class="form-group">
                        <label for="test_name">Test Name *</label>
                        <input type="text" id="test_name" name="test_name" required 
                               placeholder="e.g., UPSC Prelims Mock Test 5" value="<?= $_POST['test_name'] ?? '' ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="test_date">Test Date *</label>
                            <input type="date" id="test_date" name="test_date" required 
                                   value="<?= $_POST['test_date'] ?? date('Y-m-d') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="test_type">Test Type</label>
                            <select id="test_type" name="test_type">
                                <option value="Mock Test">Mock Test</option>
                                <option value="Previous Year">Previous Year Paper</option>
                                <option value="Subject Test">Subject-wise Test</option>
                                <option value="Sectional Test">Sectional Test</option>
                                <option value="Speed Test">Speed Test</option>
                                <option value="Practice Set">Practice Set</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="total_questions">Total Questions *</label>
                        <input type="number" id="total_questions" name="total_questions" required 
                               min="1" max="200" placeholder="100" value="<?= $_POST['total_questions'] ?? '' ?>">
                        <small>How many questions are in this test?</small>
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes (Optional)</label>
                        <textarea id="notes" name="notes" rows="3" 
                                  placeholder="Any additional notes about this test session..."><?= $_POST['notes'] ?? '' ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-large">
                            🚀 Create Session & Start Logging
                        </button>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>

            <!-- Help Section -->
            <div class="help-section">
                <h2>💡 How It Works</h2>
                <div class="help-grid">
                    <div class="help-card">
                        <div class="help-icon">1️⃣</div>
                        <h3>Create Session</h3>
                        <p>Set up a new test session with basic details</p>
                    </div>
                    <div class="help-card">
                        <div class="help-icon">2️⃣</div>
                        <h3>Log Questions</h3>
                        <p>For each question, log your feeling, confidence, and answer</p>
                    </div>
                    <div class="help-card">
                        <div class="help-icon">3️⃣</div>
                        <h3>Analyze Results</h3>
                        <p>Get detailed insights and graphs about your performance</p>
                    </div>
                </div>
                
                <div class="feelings-guide">
                    <h3>🎯 Feeling Categories</h3>
                    <div class="feelings-list">
                        <div class="feeling-item">
                            <strong>Confident:</strong> You were sure about the answer
                        </div>
                        <div class="feeling-item">
                            <strong>Guessed:</strong> You made an educated guess
                        </div>
                        <div class="feeling-item">
                            <strong>Confused:</strong> You were unsure between options
                        </div>
                        <div class="feeling-item">
                            <strong>Blank:</strong> You had no idea about the answer
                        </div>
                        <div class="feeling-item">
                            <strong>Time Pressure:</strong> You rushed due to time constraints
                        </div>
                        <div class="feeling-item">
                            <strong>Careless:</strong> You knew the answer but made a mistake
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Manual Test Analysis Platform. Track your progress! 📈</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>