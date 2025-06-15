<?php
include_once 'config/database.php';
require_once 'config/auth_check.php';

// Handle API key update
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_api') {
    $claude_api_key = trim($_POST['claude_api_key']);
    
    if ($_SESSION['user_type'] === 'guest') {
        setFlashMessage('Guest users cannot save API keys permanently.', 'warning');
    } else {
        // Update API key for registered users
        $stmt = $pdo->prepare("UPDATE users SET claude_api_key = ? WHERE id = ?");
        if ($stmt->execute([$claude_api_key, $_SESSION['user_id']])) {
            setFlashMessage('API key updated successfully!', 'success');
        } else {
            setFlashMessage('Error updating API key.', 'error');
        }
    }
}

// Get user data
$user_data = null;
if ($_SESSION['user_type'] === 'registered') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_data = $stmt->fetch();
}

// Get user statistics
$user_sessions = 0;
$user_questions = 0;
$user_accuracy = 0;

if ($_SESSION['user_type'] === 'registered') {
    $user_sessions = $pdo->prepare("SELECT COUNT(*) FROM test_sessions WHERE user_id = ?");
    $user_sessions->execute([$_SESSION['user_id']]);
    $user_sessions = $user_sessions->fetchColumn();
    
    $user_questions = $pdo->prepare("SELECT COUNT(*) FROM question_logs ql JOIN test_sessions ts ON ql.session_id = ts.id WHERE ts.user_id = ?");
    $user_questions->execute([$_SESSION['user_id']]);
    $user_questions = $user_questions->fetchColumn();
    
    if ($user_questions > 0) {
        $correct = $pdo->prepare("SELECT COUNT(*) FROM question_logs ql JOIN test_sessions ts ON ql.session_id = ts.id WHERE ts.user_id = ? AND ql.is_correct = 1");
        $correct->execute([$_SESSION['user_id']]);
        $correct = $correct->fetchColumn();
        $user_accuracy = round(($correct / $user_questions) * 100, 1);
    }
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Test Analysis Platform</title>
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
                <a href="profile.php" class="active">Profile</a>
                <a href="logout.php">Logout</a>
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
                <h1>Profile Settings</h1>
                <p>Manage your account and configure AI analysis</p>
            </div>

            <div class="profile-grid">
                <!-- User Info -->
                <div class="profile-card">
                    <h2>Account Information</h2>
                    <div class="user-info">
                        <div class="info-item">
                            <label>Username</label>
                            <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                        </div>
                        <div class="info-item">
                            <label>Account Type</label>
                            <span class="user-type <?= $_SESSION['user_type'] ?>">
                                <?= ucfirst($_SESSION['user_type']) ?>
                            </span>
                        </div>
                        <?php if ($user_data && $user_data['created_at']): ?>
                        <div class="info-item">
                            <label>Member Since</label>
                            <span><?= date('M d, Y', strtotime($user_data['created_at'])) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($user_data && $user_data['last_login']): ?>
                        <div class="info-item">
                            <label>Last Login</label>
                            <span><?= date('M d, Y g:i A', strtotime($user_data['last_login'])) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="profile-card">
                    <h2>Your Statistics</h2>
                    <div class="stats-grid-small">
                        <div class="stat-item">
                            <div class="stat-number"><?= $user_sessions ?></div>
                            <div class="stat-label">Sessions</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?= $user_questions ?></div>
                            <div class="stat-label">Questions</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?= $user_accuracy ?>%</div>
                            <div class="stat-label">Accuracy</div>
                        </div>
                    </div>
                </div>

                <!-- Claude API Configuration -->
                <div class="profile-card api-card">
                    <h2>Claude AI Integration</h2>
                    <p>Add your Claude API key to enable AI-powered analysis and insights</p>
                    
                    <?php if ($_SESSION['user_type'] === 'guest'): ?>
                        <div class="guest-warning">
                            <p>Guest users cannot save API keys permanently. Create an account to use AI features.</p>
                            <a href="auth.php" class="btn btn-primary">Create Account</a>
                        </div>
                    <?php else: ?>
                        <form method="POST" class="api-form">
                            <input type="hidden" name="action" value="update_api">
                            
                            <div class="form-group">
                                <label for="claude_api_key">Claude API Key</label>
                                <input type="password" id="claude_api_key" name="claude_api_key" 
                                       placeholder="sk-ant-..." 
                                       value="<?= $user_data['claude_api_key'] ? '••••••••••••••••' : '' ?>">
                                <small>
                                    Get your API key from <a href="https://console.anthropic.com/" target="_blank">Anthropic Console</a>
                                </small>
                            </div>

                            <div class="api-features">
                                <h4>AI Features</h4>
                                <ul>
                                    <li>Personalized performance insights</li>
                                    <li>Study recommendations based on weak areas</li>
                                    <li>Question difficulty analysis</li>
                                    <li>Pattern recognition in mistakes</li>
                                    <li>Adaptive learning suggestions</li>
                                </ul>
                            </div>

                            <button type="submit" class="btn btn-primary">Save API Key</button>
                        </form>

                        <?php if ($user_data['claude_api_key']): ?>
                            <div class="api-status">
                                <span class="status-indicator active"></span>
                                <span>Claude AI integration active</span>
                            </div>
                        <?php else: ?>
                            <div class="api-status">
                                <span class="status-indicator inactive"></span>
                                <span>Claude AI integration not configured</span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Data Management -->
                <?php if ($_SESSION['user_type'] === 'registered'): ?>
                <div class="profile-card">
                    <h2>Data Management</h2>
                    <div class="data-actions">
                        <button class="btn btn-outline" onclick="exportData()">Export My Data</button>
                        <button class="btn btn-outline" onclick="confirmDelete()">Delete Account</button>
                    </div>
                    <p class="data-note">Export includes all your test sessions, questions, and analytics data</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Manual Test Analysis Platform</p>
        </div>
    </footer>

    <script>
        function exportData() {
            if (confirm('Export all your test data? This will download a JSON file.')) {
                window.location.href = 'export-data.php';
            }
        }

        function confirmDelete() {
            if (confirm('Are you sure you want to delete your account? This cannot be undone.')) {
                if (confirm('This will permanently delete all your data. Continue?')) {
                    window.location.href = 'delete-account.php';
                }
            }
        }

        // Toggle API key visibility
        document.getElementById('claude_api_key').addEventListener('focus', function() {
            if (this.value === '••••••••••••••••') {
                this.value = '';
                this.type = 'text';
            }
        });
    </script>
</body>
</html>