<?php
session_start();

// Check if user has selected a profile
if (isset($_SESSION['profile_id']) && $_SESSION['profile_id']) {
    // Redirect to dashboard
    header('Location: index.php');
    exit;
} else {
    // Redirect to profile selection
    header('Location: select-profile.php');
    exit;
}
?>