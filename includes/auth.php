<?php
/**
 * Authentication and Session Handling
 * Lead Management System
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'connect.php';

/**
 * Check if user is logged in
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user information
 * @return array|null
 */
function get_current_user() {
    if (!is_logged_in()) {
        return null;
    }
    
    $query = "SELECT id, username, full_name, role FROM users WHERE id = ?";
    return get_row($query, [$_SESSION['user_id']], 'i');
}

/**
 * Authenticate user login
 * @param string $username
 * @param string $password
 * @return bool
 */
function authenticate_user($username, $password) {
    $query = "SELECT id, username, password, full_name, role FROM users WHERE username = ?";
    $user = get_row($query, [$username], 's');
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    
    return false;
}

/**
 * Log out user
 */
function logout_user() {
    session_unset();
    session_destroy();
}

/**
 * Require login - redirect to login if not authenticated
 */
function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Require specific role
 * @param string|array $required_roles
 */
function require_role($required_roles) {
    require_login();
    
    $user = get_current_user();
    if (!$user) {
        header('Location: login.php');
        exit();
    }
    
    if (is_string($required_roles)) {
        $required_roles = [$required_roles];
    }
    
    if (!in_array($user['role'], $required_roles)) {
        header('Location: index.php?error=unauthorized');
        exit();
    }
}

/**
 * Check if current user has permission for specific action
 * @param string $permission
 * @return bool
 */
function has_permission($permission) {
    $user = get_current_user();
    if (!$user) return false;
    
    $role = $user['role'];
    
    switch ($permission) {
        case 'view_all_leads':
            return in_array($role, ['admin', 'marketing']);
        case 'edit_leads':
            return in_array($role, ['admin', 'sales']);
        case 'add_leads':
            return in_array($role, ['admin', 'sales']);
        case 'delete_leads':
            return $role === 'admin';
        case 'view_analytics':
            return in_array($role, ['admin', 'marketing']);
        case 'export_leads':
            return in_array($role, ['admin', 'sales', 'marketing']);
        case 'manage_users':
            return $role === 'admin';
        default:
            return false;
    }
}

/**
 * Get user's assigned leads (for sales staff)
 * @param int $user_id
 * @return array
 */
function get_user_leads($user_id) {
    $query = "SELECT * FROM leads WHERE assigned_to = ? ORDER BY created_at DESC";
    return get_all($query, [$user_id], 'i');
}

/**
 * Get all users for assignment dropdown
 * @return array
 */
function get_all_users() {
    $query = "SELECT id, username, full_name, role FROM users ORDER BY full_name";
    return get_all($query);
}

/**
 * Get sales staff only for assignment
 * @return array
 */
function get_sales_staff() {
    $query = "SELECT id, username, full_name FROM users WHERE role = 'sales' ORDER BY full_name";
    return get_all($query);
}
?>