<?php
include_once 'config/database.php';

// Handle profile creation and login
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $profile_name = trim($_POST['profile_name']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $claude_api_key = trim($_POST['claude_api_key']);
        
        if (empty($profile_name) || empty($password)) {
            setFlashMessage('Please enter profile name and password.', 'error');
        } elseif ($password !== $confirm_password) {
            setFlashMessage('Passwords do not match.', 'error');
        } elseif (strlen($password) < 4) {
            setFlashMessage('Password must be at least 4 characters.', 'error');
        } else {
            // Check if profile name exists
            $stmt = $pdo->prepare("SELECT id FROM profiles WHERE name = ?");
            $stmt->execute([$profile_name]);
            
            if ($stmt->fetch()) {
                setFlashMessage('Profile name already exists.', 'error');
            } else {
                // Create profile
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO profiles (name, password_hash, claude_api_key) VALUES (?, ?, ?)");
                
                if ($stmt->execute([$profile_name, $password_hash, $claude_api_key])) {
                    $profile_id = $pdo->lastInsertId();
                    $_SESSION['profile_id'] = $profile_id;
                    $_SESSION['profile_name'] = $profile_name;
                    
                    setFlashMessage('Profile created successfully!', 'success');
                    header('Location: index.php');
                    exit;
                } else {
                    setFlashMessage('Error creating profile.', 'error');
                }
            }
        }
    }
    
    if ($_POST['action'] === 'login') {
        $profile_id = (int)$_POST['profile_id'];
        $password = $_POST['password'];
        
        // Get profile info
        $stmt = $pdo->prepare("SELECT * FROM profiles WHERE id = ?");
        $stmt->execute([$profile_id]);
        $profile = $stmt->fetch();
        
        if ($profile && password_verify($password, $profile['password_hash'])) {
            $_SESSION['profile_id'] = $profile['id'];
            $_SESSION['profile_name'] = $profile['name'];
            
            // Update last accessed
            $pdo->prepare("UPDATE profiles SET last_accessed = NOW() WHERE id = ?")->execute([$profile['id']]);
            
            header('Location: index.php');
            exit;
        } else {
            setFlashMessage('Invalid password for this profile.', 'error');
        }
    }
    
    if ($_POST['action'] === 'delete') {
        $profile_id = (int)$_POST['profile_id'];
        $password = $_POST['password'];
        
        // Get profile info
        $stmt = $pdo->prepare("SELECT * FROM profiles WHERE id = ?");
        $stmt->execute([$profile_id]);
        $profile = $stmt->fetch();
        
        if ($profile && password_verify($password, $profile['password_hash'])) {
            // Delete profile (cascade will delete sessions and questions)
            $stmt = $pdo->prepare("DELETE FROM profiles WHERE id = ?");
            if ($stmt->execute([$profile_id])) {
                setFlashMessage('Profile deleted successfully.', 'success');
                // Clear session if it was the current profile
                if ($_SESSION['profile_id'] == $profile_id) {
                    session_unset();
                }
            } else {
                setFlashMessage('Error deleting profile.', 'error');
            }
        } else {
            setFlashMessage('Invalid password for profile deletion.', 'error');
        }
    }
}

// Get all profiles
$profiles = $pdo->query("SELECT * FROM profiles ORDER BY created_at DESC")->fetchAll();

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Profile - Test Analysis Platform</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Test Analysis Platform</h1>
                <p>Select a profile or create a new one</p>
            </div>

            <?php if ($flash): ?>
                <div class="flash-message flash-<?= $flash['type'] ?>">
                    <?= htmlspecialchars($flash['message']) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($profiles)): ?>
                <div class="profiles-section">
                    <h3>Existing Profiles</h3>
                    <div class="profiles-grid">
                        <?php foreach ($profiles as $profile): ?>
                            <div class="profile-option">
                                <div class="profile-info">
                                    <h4><?= htmlspecialchars($profile['name']) ?></h4>
                                    <p>Created: <?= date('M d, Y', strtotime($profile['created_at'])) ?></p>
                                    <p>Last Access: <?= date('M d, Y', strtotime($profile['last_accessed'])) ?></p>
                                    <p>API Key: <?= $profile['claude_api_key'] ? 'Configured' : 'Not set' ?></p>
                                </div>
                                
                                <!-- Login Form -->
                                <form method="POST" class="profile-access-form" style="margin-top: 12px;">
                                    <input type="hidden" name="action" value="login">
                                    <input type="hidden" name="profile_id" value="<?= $profile['id'] ?>">
                                    <div class="form-group-inline">
                                        <input type="password" name="password" placeholder="Password" required class="profile-password">
                                        <button type="submit" class="btn btn-primary btn-small">Access</button>
                                    </div>
                                </form>
                                
                                <!-- Delete Form -->
                                <details class="delete-section">
                                    <summary>Delete Profile</summary>
                                    <form method="POST" class="delete-form" onsubmit="return confirm('Are you sure? This will delete ALL data for this profile!')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="profile_id" value="<?= $profile['id'] ?>">
                                        <div class="form-group-inline">
                                            <input type="password" name="password" placeholder="Password to confirm" required class="profile-password">
                                            <button type="submit" class="btn btn-outline btn-small" style="color: #ef4444; border-color: #ef4444;">Delete</button>
                                        </div>
                                    </form>
                                </details>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="auth-divider">
                    <span>or</span>
                </div>
            <?php endif; ?>

            <div class="create-profile-section">
                <h3>Create New Profile</h3>
                <form method="POST" class="auth-form">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="form-group">
                        <label for="profile_name">Profile Name</label>
                        <input type="text" id="profile_name" name="profile_name" required 
                               placeholder="e.g., John's UPSC Prep" value="<?= $_POST['profile_name'] ?? '' ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required 
                                   placeholder="Enter password (min 4 chars)">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                   placeholder="Confirm password">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="claude_api_key">Claude API Key (Optional)</label>
                        <input type="password" id="claude_api_key" name="claude_api_key" 
                               placeholder="sk-ant-... (for AI analysis)">
                        <small>
                            Get your API key from <a href="https://console.anthropic.com/" target="_blank">Anthropic Console</a>
                        </small>
                    </div>

                    <button type="submit" class="btn btn-primary btn-large">Create Profile</button>
                </form>
            </div>

            <div class="auth-features">
                <h3>Features</h3>
                <ul>
                    <li>Manual question-by-question analysis</li>
                    <li>Advanced statistical insights with charts</li>
                    <li>Claude AI-powered recommendations (with API key)</li>
                    <li>Performance tracking over time</li>
                    <li>Subject-wise detailed breakdown</li>
                    <li>Confidence vs accuracy analysis</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Auto-focus profile name input
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('profile_name').focus();
        });
    </script>
</body>
</html>