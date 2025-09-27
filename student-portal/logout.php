<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Log the logout activity
if (isAdmin()) {
    logActivity("Admin logged out");
} elseif (isStudent()) {
    logActivity("Student {$_SESSION['student_id']} logged out");
}

// Clear remember me cookie
clearRememberMeCookie();

// Destroy session
session_destroy();

// Redirect to login page
header('Location: index.php');
exit();
?>