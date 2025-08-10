<?php
/**
 * Utility Functions
 * Lead Management System
 */

require_once 'connect.php';

/**
 * Sanitize and escape output for HTML display
 * @param string $string
 * @return string
 */
function sanitize_output($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize input data
 * @param string $input
 * @return string
 */
function sanitize_input($input) {
    return trim(stripslashes(htmlspecialchars($input)));
}

/**
 * Validate email format
 * @param string $email
 * @return bool
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (basic validation)
 * @param string $phone
 * @return bool
 */
function validate_phone($phone) {
    return preg_match('/^[\+]?[0-9\s\-\(\)]+$/', $phone);
}

/**
 * Validate URL format
 * @param string $url
 * @return bool
 */
function validate_url($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Check if user is admin
 * @return bool
 */
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Check if user is sales staff
 * @return bool
 */
function is_sales() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'sales';
}

/**
 * Check if user is marketing
 * @return bool
 */
function is_marketing() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'marketing';
}

/**
 * Get leads based on user role
 * @param int $user_id
 * @param string $role
 * @return array
 */
function get_leads_by_role($user_id, $role) {
    switch ($role) {
        case 'admin':
        case 'marketing':
            // Get all leads with assigned user info
            $query = "SELECT l.*, u.full_name as assigned_user 
                     FROM leads l 
                     LEFT JOIN users u ON l.assigned_to = u.id 
                     ORDER BY l.created_at DESC";
            return get_all($query);
            
        case 'sales':
            // Get only assigned leads
            $query = "SELECT l.*, u.full_name as assigned_user 
                     FROM leads l 
                     LEFT JOIN users u ON l.assigned_to = u.id 
                     WHERE l.assigned_to = ? 
                     ORDER BY l.created_at DESC";
            return get_all($query, [$user_id], 'i');
            
        default:
            return [];
    }
}

/**
 * Get single lead by ID with role check
 * @param int $lead_id
 * @param int $user_id
 * @param string $role
 * @return array|null
 */
function get_lead_by_id($lead_id, $user_id, $role) {
    if ($role === 'admin' || $role === 'marketing') {
        $query = "SELECT l.*, u.full_name as assigned_user 
                 FROM leads l 
                 LEFT JOIN users u ON l.assigned_to = u.id 
                 WHERE l.id = ?";
        return get_row($query, [$lead_id], 'i');
    } else if ($role === 'sales') {
        $query = "SELECT l.*, u.full_name as assigned_user 
                 FROM leads l 
                 LEFT JOIN users u ON l.assigned_to = u.id 
                 WHERE l.id = ? AND l.assigned_to = ?";
        return get_row($query, [$lead_id, $user_id], 'ii');
    }
    
    return null;
}

/**
 * Format date for display
 * @param string $date
 * @return string
 */
function format_date($date) {
    return date('M j, Y', strtotime($date));
}

/**
 * Format datetime for display
 * @param string $datetime
 * @return string
 */
function format_datetime($datetime) {
    return date('M j, Y g:i A', strtotime($datetime));
}

/**
 * Get status badge class for styling
 * @param string $status
 * @return string
 */
function get_status_class($status) {
    switch ($status) {
        case 'Interested':
            return 'bg-green-100 text-green-800';
        case 'Not Interested':
            return 'bg-red-100 text-red-800';
        case 'Budget Not Met':
            return 'bg-yellow-100 text-yellow-800';
        case 'Meeting Scheduled':
            return 'bg-blue-100 text-blue-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

/**
 * Generate CSRF token
 * @return string
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token
 * @return bool
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get industry options
 * @return array
 */
function get_industry_options() {
    return [
        'Technology',
        'Healthcare',
        'Finance',
        'Education',
        'Retail',
        'Manufacturing',
        'Real Estate',
        'Food & Beverage',
        'Energy',
        'Transportation',
        'Entertainment',
        'Non-profit',
        'Government',
        'Other'
    ];
}

/**
 * Get status options
 * @return array
 */
function get_status_options() {
    return [
        '' => 'Select Status',
        'Interested' => 'Interested',
        'Not Interested' => 'Not Interested',
        'Budget Not Met' => 'Budget Not Met',
        'Meeting Scheduled' => 'Meeting Scheduled'
    ];
}

/**
 * Redirect with message
 * @param string $url
 * @param string $message
 * @param string $type (success, error, info)
 */
function redirect_with_message($url, $message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header("Location: $url");
    exit();
}

/**
 * Get and clear flash message
 * @return array|null
 */
function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'text' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'info'
        ];
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return $message;
    }
    return null;
}
?>