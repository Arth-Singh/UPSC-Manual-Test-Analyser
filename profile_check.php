<?php
// Profile check - include this in protected pages
session_start();

if (!isset($_SESSION['profile_id'])) {
    header('Location: select-profile.php');
    exit;
}

// Set profile context for database queries
$current_profile_id = $_SESSION['profile_id'];
$current_profile_name = $_SESSION['profile_name'];
?>