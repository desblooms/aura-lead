<?php
/**
 * Enhanced Utility Functions
 * Lead Management System with Marketing Ads Features
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
 * Get leads based on user role with enhanced features
 * @param int $user_id
 * @param string $role
 * @return array
 */
function get_leads_by_role($user_id, $role) {
    switch ($role) {
        case 'admin':
        case 'marketing':
            // Get all leads with assigned user info and ad source
            $query = "SELECT l.*, u.full_name as assigned_user, ra.ad_name as source_ad_name, s.service_name as source_service
                     FROM leads l 
                     LEFT JOIN users u ON l.assigned_to = u.id 
                     LEFT JOIN running_ads ra ON l.source_ad_id = ra.id
                     LEFT JOIN services s ON ra.service_id = s.id
                     ORDER BY l.created_at DESC";
            return get_all($query);
            
        case 'sales':
            // Get only assigned leads with ad source info
            $query = "SELECT l.*, u.full_name as assigned_user, ra.ad_name as source_ad_name, s.service_name as source_service
                     FROM leads l 
                     LEFT JOIN users u ON l.assigned_to = u.id 
                     LEFT JOIN running_ads ra ON l.source_ad_id = ra.id
                     LEFT JOIN services s ON ra.service_id = s.id
                     WHERE l.assigned_to = ? 
                     ORDER BY l.created_at DESC";
            return get_all($query, [$user_id], 'i');
            
        default:
            return [];
    }
}

/**
 * Get single lead by ID with role check and enhanced info
 * @param int $lead_id
 * @param int $user_id
 * @param string $role
 * @return array|null
 */
function get_lead_by_id($lead_id, $user_id, $role) {
    if ($role === 'admin' || $role === 'marketing') {
        $query = "SELECT l.*, u.full_name as assigned_user, ra.ad_name as source_ad_name, s.service_name as source_service
                 FROM leads l 
                 LEFT JOIN users u ON l.assigned_to = u.id 
                 LEFT JOIN running_ads ra ON l.source_ad_id = ra.id
                 LEFT JOIN services s ON ra.service_id = s.id
                 WHERE l.id = ?";
        return get_row($query, [$lead_id], 'i');
    } else if ($role === 'sales') {
        $query = "SELECT l.*, u.full_name as assigned_user, ra.ad_name as source_ad_name, s.service_name as source_service
                 FROM leads l 
                 LEFT JOIN users u ON l.assigned_to = u.id 
                 LEFT JOIN running_ads ra ON l.source_ad_id = ra.id
                 LEFT JOIN services s ON ra.service_id = s.id
                 WHERE l.id = ? AND l.assigned_to = ?";
        return get_row($query, [$lead_id, $user_id], 'ii');
    }
    
    return null;
}

/**
 * Get active services for dropdown
 * @return array
 */
function get_active_services() {
    return get_all("SELECT id, service_name, service_category FROM services WHERE is_active = 1 ORDER BY service_name");
}

/**
 * Get running ads with lead count
 * @param bool $active_only
 * @return array
 */
function get_running_ads_with_stats($active_only = false) {
    $where_clause = $active_only ? "WHERE ra.is_active = 1" : "";
    
    $query = "SELECT ra.*, s.service_name, s.service_category, u.full_name as assigned_sales_name,
                     COUNT(l.id) as lead_count,
                     SUM(CASE WHEN l.client_status = 'Interested' THEN 1 ELSE 0 END) as interested_leads,
                     SUM(CASE WHEN l.client_status = 'Meeting Scheduled' THEN 1 ELSE 0 END) as meeting_leads
              FROM running_ads ra 
              LEFT JOIN services s ON ra.service_id = s.id 
              LEFT JOIN users u ON ra.assigned_sales_member = u.id
              LEFT JOIN leads l ON ra.id = l.source_ad_id
              $where_clause
              GROUP BY ra.id
              ORDER BY ra.created_at DESC";
    
    return get_all($query);
}

/**
 * Get leads by ad source for analytics
 * @param int $ad_id
 * @return array
 */
function get_leads_by_ad_source($ad_id) {
    $query = "SELECT l.*, u.full_name as assigned_user 
              FROM leads l 
              LEFT JOIN users u ON l.assigned_to = u.id 
              WHERE l.source_ad_id = ? 
              ORDER BY l.created_at DESC";
    return get_all($query, [$ad_id], 'i');
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
 * Get platform badge class for styling
 * @param string $platform
 * @return string
 */
function get_platform_class($platform) {
    switch (strtolower($platform)) {
        case 'facebook':
            return 'bg-blue-100 text-blue-800';
        case 'google ads':
            return 'bg-green-100 text-green-800';
        case 'instagram':
            return 'bg-pink-100 text-pink-800';
        case 'linkedin':
            return 'bg-indigo-100 text-indigo-800';
        case 'twitter':
            return 'bg-cyan-100 text-cyan-800';
        case 'youtube':
            return 'bg-red-100 text-red-800';
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
 * Get service category options
 * @return array
 */
function get_service_categories() {
    return [
        'Digital Services',
        'Digital Marketing',
        'Design Services',
        'Development Services',
        'Consulting Services',
        'Other'
    ];
}

/**
 * Get ad platform options
 * @return array
 */
function get_ad_platforms() {
    return [
        'Facebook',
        'Google Ads',
        'Instagram',
        'LinkedIn',
        'Twitter',
        'YouTube',
        'TikTok',
        'Other'
    ];
}

/**
 * Get lead source options
 * @return array
 */
function get_lead_sources() {
    return [
        'Manual' => 'Manual Entry',
        'Facebook Ad' => 'Facebook Ad',
        'Google Ad' => 'Google Ad',
        'Instagram Ad' => 'Instagram Ad',
        'LinkedIn Ad' => 'LinkedIn Ad',
        'Website Form' => 'Website Contact Form',
        'Phone Call' => 'Phone Call',
        'Email' => 'Email Inquiry',
        'Referral' => 'Referral',
        'Other' => 'Other'
    ];
}

/**
 * Calculate conversion rate for ads
 * @param int $total_leads
 * @param int $converted_leads
 * @return float
 */
function calculate_conversion_rate($total_leads, $converted_leads) {
    if ($total_leads == 0) return 0;
    return round(($converted_leads / $total_leads) * 100, 2);
}

/**
 * Calculate cost per lead
 * @param float $budget
 * @param int $leads_count
 * @return float
 */
function calculate_cost_per_lead($budget, $leads_count) {
    if ($leads_count == 0) return 0;
    return round($budget / $leads_count, 2);
}

/**
 * Get lead statistics by date range
 * @param string $start_date
 * @param string $end_date
 * @param int|null $ad_id
 * @return array
 */
function get_lead_stats_by_date($start_date, $end_date, $ad_id = null) {
    $where_clause = "WHERE l.created_at BETWEEN ? AND ?";
    $params = [$start_date, $end_date];
    $types = 'ss';
    
    if ($ad_id) {
        $where_clause .= " AND l.source_ad_id = ?";
        $params[] = $ad_id;
        $types .= 'i';
    }
    
    $query = "SELECT 
                COUNT(*) as total_leads,
                SUM(CASE WHEN l.client_status = 'Interested' THEN 1 ELSE 0 END) as interested,
                SUM(CASE WHEN l.client_status = 'Meeting Scheduled' THEN 1 ELSE 0 END) as meetings,
                SUM(CASE WHEN l.client_status = 'Not Interested' THEN 1 ELSE 0 END) as not_interested,
                SUM(CASE WHEN l.client_status = 'Budget Not Met' THEN 1 ELSE 0 END) as budget_not_met
              FROM leads l 
              $where_clause";
    
    return get_row($query, $params, $types);
}

/**
 * Get top performing ads
 * @param int $limit
 * @return array
 */
function get_top_performing_ads($limit = 5) {
    $query = "SELECT ra.*, s.service_name, 
                     COUNT(l.id) as lead_count,
                     SUM(CASE WHEN l.client_status IN ('Interested', 'Meeting Scheduled') THEN 1 ELSE 0 END) as quality_leads,
                     ra.budget
              FROM running_ads ra 
              LEFT JOIN services s ON ra.service_id = s.id 
              LEFT JOIN leads l ON ra.id = l.source_ad_id
              WHERE ra.is_active = 1
              GROUP BY ra.id
              HAVING lead_count > 0
              ORDER BY quality_leads DESC, lead_count DESC
              LIMIT ?";
    
    return get_all($query, [$limit], 'i');
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

/**
 * Enhanced permission checking for new features
 * @param string $permission
 * @return bool
 */
function has_permission($permission) {
    $user = get_logged_in_user();
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
        case 'manage_services':
            return $role === 'admin';
        case 'manage_ads':
            return in_array($role, ['admin', 'marketing']);
        case 'view_ads':
            return in_array($role, ['admin', 'marketing']);
        case 'assign_leads':
            return in_array($role, ['admin', 'marketing']);
        default:
            return false;
    }
}

/**
 * Check if user needs to be imported from original functions.php
 */
if (!function_exists('get_logged_in_user')) {
    /**
     * Get current logged in user information
     * @return array|null
     */
    function get_logged_in_user() {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            return null;
        }
        
        $query = "SELECT id, username, full_name, role FROM users WHERE id = ?";
        return get_row($query, [$_SESSION['user_id']], 'i');
    }
}

if (!function_exists('get_sales_staff')) {
    /**
     * Get sales staff only for assignment
     * @return array
     */
    function get_sales_staff() {
        $query = "SELECT id, username, full_name FROM users WHERE role = 'sales' AND is_active = 1 ORDER BY full_name";
        return get_all($query);
    }
}
?>