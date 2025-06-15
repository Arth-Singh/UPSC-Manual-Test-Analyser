<?php
// Manual Test Analysis Platform - Database Configuration
$db_host = "localhost";
$db_username = "u834811746_ArthSingh";
$db_password = "24@AndheriBandra";
$db_name = "u834811746_upsc_test_db";

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Session management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Flash message functions
function setFlashMessage($message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

// Utility functions
function executeQuery($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

// Get all test sessions for current profile
function getAllSessions($pdo, $profile_id = null) {
    if (!$profile_id && isset($_SESSION['profile_id'])) {
        $profile_id = $_SESSION['profile_id'];
    }
    
    $sql = "SELECT *, 
            (SELECT COUNT(*) FROM question_logs WHERE session_id = test_sessions.id) as logged_questions,
            (SELECT COUNT(*) FROM question_logs WHERE session_id = test_sessions.id AND is_correct = 1) as correct_answers
            FROM test_sessions WHERE profile_id = ? ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$profile_id]);
    return $stmt ? $stmt->fetchAll() : [];
}

// Get session by ID
function getSessionById($pdo, $session_id) {
    $stmt = $pdo->prepare("SELECT * FROM test_sessions WHERE id = ?");
    $stmt->execute([$session_id]);
    return $stmt->fetch();
}

// Get questions for a session
function getQuestionsBySession($pdo, $session_id) {
    $sql = "SELECT * FROM question_logs WHERE session_id = ? ORDER BY question_number";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$session_id]);
    return $stmt->fetchAll();
}

// Get analysis data
function getAnalysisData($pdo, $session_id = null) {
    $where = $session_id ? "WHERE session_id = ?" : "";
    $params = $session_id ? [$session_id] : [];
    
    $sql = "SELECT 
                feeling,
                COUNT(*) as count,
                AVG(confidence_level) as avg_confidence,
                SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct_count,
                AVG(time_spent) as avg_time
            FROM question_logs $where GROUP BY feeling";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Get subject-wise performance
function getSubjectAnalysis($pdo, $session_id = null) {
    $where = $session_id ? "WHERE session_id = ?" : "";
    $params = $session_id ? [$session_id] : [];
    
    $sql = "SELECT 
                subject,
                COUNT(*) as total_questions,
                SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
                AVG(confidence_level) as avg_confidence,
                AVG(time_spent) as avg_time
            FROM question_logs $where GROUP BY subject";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
?>