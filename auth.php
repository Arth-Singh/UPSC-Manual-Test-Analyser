<?php
session_start();
include_once 'config/database.php';

// Handle login/registration
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'login') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        if (empty($username) || empty($password)) {
            setFlashMessage('Please enter both username and password.', 'error');
        } else {
            // Check user exists
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_type'] = $user['user_type'];
                
                // Update last login
                $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
                
                header('Location: index.php');
                exit;
            } else {
                setFlashMessage('Invalid username or password.', 'error');
            }
        }
    }
    
    if ($action === 'register') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($username) || empty($password)) {
            setFlashMessage('Please fill all required fields.', 'error');
        } elseif ($password !== $confirm_password) {
            setFlashMessage('Passwords do not match.', 'error');
        } elseif (strlen($password) < 6) {
            setFlashMessage('Password must be at least 6 characters.', 'error');
        } else {
            // Check if username exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->fetch()) {
                setFlashMessage('Username already exists.', 'error');
            } else {
                // Create user
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, user_type) VALUES (?, ?, 'registered')");
                
                if ($stmt->execute([$username, $password_hash])) {
                    $user_id = $pdo->lastInsertId();
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['user_type'] = 'registered';
                    
                    setFlashMessage('Account created successfully!', 'success');
                    header('Location: index.php');
                    exit;
                } else {
                    setFlashMessage('Error creating account.', 'error');
                }
            }
        }
    }
    
    if ($action === 'guest') {
        // Create guest session
        $guest_id = 'guest_' . uniqid();
        $_SESSION['user_id'] = $guest_id;
        $_SESSION['username'] = 'Guest User';
        $_SESSION['user_type'] = 'guest';
        
        header('Location: index.php');
        exit;
    }
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Test Analysis Platform</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Test Analysis Platform</h1>
                <p>Access your personalized test analytics</p>
            </div>

            <?php if ($flash): ?>
                <div class="flash-message flash-<?= $flash['type'] ?>">
                    <?= htmlspecialchars($flash['message']) ?>
                </div>
            <?php endif; ?>

            <div class="auth-tabs">
                <button class="tab-btn active" onclick="switchTab('login')">Login</button>
                <button class="tab-btn" onclick="switchTab('register')">Register</button>
            </div>

            <!-- Login Form -->
            <form method="POST" class="auth-form" id="login-form">
                <input type="hidden" name="action" value="login">
                
                <div class="form-group">
                    <label for="login_username">Username</label>
                    <input type="text" id="login_username" name="username" required 
                           placeholder="Enter your username" value="<?= $_POST['username'] ?? '' ?>">
                </div>

                <div class="form-group">
                    <label for="login_password">Password</label>
                    <input type="password" id="login_password" name="password" required 
                           placeholder="Enter your password">
                </div>

                <button type="submit" class="btn btn-primary btn-large">Login</button>
            </form>

            <!-- Register Form -->
            <form method="POST" class="auth-form hidden" id="register-form">
                <input type="hidden" name="action" value="register">
                
                <div class="form-group">
                    <label for="reg_username">Username</label>
                    <input type="text" id="reg_username" name="username" required 
                           placeholder="Choose a username">
                </div>

                <div class="form-group">
                    <label for="reg_password">Password</label>
                    <input type="password" id="reg_password" name="password" required 
                           placeholder="Create a password (min 6 characters)">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           placeholder="Confirm your password">
                </div>

                <button type="submit" class="btn btn-primary btn-large">Create Account</button>
            </form>

            <div class="auth-divider">
                <span>or</span>
            </div>

            <!-- Guest Login -->
            <form method="POST" class="guest-form">
                <input type="hidden" name="action" value="guest">
                <button type="submit" class="btn btn-outline btn-large">
                    Continue as Guest
                </button>
                <p class="guest-note">Guest sessions are temporary and data won't be saved permanently</p>
            </form>

            <div class="auth-features">
                <h3>Features</h3>
                <ul>
                    <li>Manual question-by-question analysis</li>
                    <li>Advanced statistical insights</li>
                    <li>Claude AI-powered recommendations</li>
                    <li>Performance tracking over time</li>
                    <li>Subject-wise breakdown</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            // Update tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Show/hide forms
            if (tab === 'login') {
                document.getElementById('login-form').classList.remove('hidden');
                document.getElementById('register-form').classList.add('hidden');
            } else {
                document.getElementById('login-form').classList.add('hidden');
                document.getElementById('register-form').classList.remove('hidden');
            }
        }

        // Auto-focus first input
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('login_username').focus();
        });
    </script>
</body>
</html>