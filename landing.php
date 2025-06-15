<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Analysis Platform</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Test Analysis Platform</h1>
                <p>Advanced analytics for UPSC test performance</p>
            </div>

            <div class="auth-features">
                <h3>Features</h3>
                <ul>
                    <li>Manual question-by-question analysis</li>
                    <li>Advanced statistical insights with charts</li>
                    <li>Claude AI-powered recommendations</li>
                    <li>Performance tracking over time</li>
                    <li>Subject-wise detailed breakdown</li>
                    <li>Confidence vs accuracy analysis</li>
                    <li>Personalized study recommendations</li>
                </ul>
            </div>

            <div style="text-align: center; margin-top: 32px;">
                <a href="auth.php" class="btn btn-primary btn-large">Get Started</a>
                <p class="guest-note" style="margin-top: 16px;">
                    Create an account or continue as guest to start analyzing your test performance
                </p>
            </div>
        </div>
    </div>

    <script>
        // Auto-redirect to auth if no session
        setTimeout(() => {
            window.location.href = 'auth.php';
        }, 3000);
    </script>
</body>
</html>