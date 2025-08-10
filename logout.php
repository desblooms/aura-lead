<?php
/**
 * Logout Script
 * Lead Management System
 */

require_once '../includes/auth.php';

// Log out user
logout_user();

// Redirect to login page
header('Location: login.php');
exit();
?>