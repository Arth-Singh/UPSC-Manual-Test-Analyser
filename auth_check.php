<?php
// Authentication check - include this in protected pages
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

// Set user context for database queries
$current_user_id = $_SESSION['user_id'];
$current_user_type = $_SESSION['user_type'];
$is_guest = ($current_user_type === 'guest');
?>