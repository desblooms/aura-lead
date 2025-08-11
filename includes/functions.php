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
    return get_all("SELECT id, service_name, service_category FROM services WHERE is_active = 1 ORDER BY service_category, service_name");
}

/**
 * Get all services with stats
 * @return array
 */
function get_services_with_stats() {
    $query = "SELECT s.*, 
                     COUNT(DISTINCT ra.id) as running_ads_count,
                     COUNT(DISTINCT l.id) as leads_count,
                     u.full_name as created_by_name
              FROM services s 
              LEFT JOIN running_ads ra ON s.id = ra.service_id AND ra.is_active = 1
              LEFT JOIN leads l ON FIND_IN_SET(s.id, REPLACE(REPLACE(l.selected_service_ids, '[', ''), ']', ''))
              LEFT JOIN users u ON s.created_by = u.id
              GROUP BY s.id
              ORDER BY s.created_at DESC";
    return get_all($query);
}

/**
 * Get service by ID
 * @param int $service_id
 * @return array|null
 */
function get_service_by_id($service_id) {
    return get_row("SELECT * FROM services WHERE id = ?", [$service_id], 'i');
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
 * Get leads by service for analytics
 * @param int $service_id
 * @return array
 */
function get_leads_by_service($service_id) {
    $query = "SELECT l.*, u.full_name as assigned_user 
              FROM leads l 
              LEFT JOIN users u ON l.assigned_to = u.id 
              WHERE FIND_IN_SET(?, REPLACE(REPLACE(l.selected_service_ids, '[', ''), ']', ''))
              ORDER BY l.created_at DESC";
    return get_all($query, [$service_id], 'i');
}

/**
 * Format date for display
 * @param string $date
 * @return string
 */
function format_date($date) {
    if (!$date) return 'Not set';
    return date('M j, Y', strtotime($date));
}

/**
 * Format datetime for display
 * @param string $datetime
 * @return string
 */
function format_datetime($datetime) {
    if (!$datetime) return 'Not set';
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
 * @param int|null $service_id
 * @return array
 */
function get_lead_stats_by_date($start_date, $end_date, $ad_id = null, $service_id = null) {
    $where_clause = "WHERE l.created_at BETWEEN ? AND ?";
    $params = [$start_date, $end_date];
    $types = 'ss';
    
    if ($ad_id) {
        $where_clause .= " AND l.source_ad_id = ?";
        $params[] = $ad_id;
        $types .= 'i';
    }
    
    if ($service_id) {
        $where_clause .= " AND FIND_IN_SET(?, REPLACE(REPLACE(l.selected_service_ids, '[', ''), ']', ''))";
        $params[] = $service_id;
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
 * Get top performing services
 * @param int $limit
 * @return array
 */
function get_top_performing_services($limit = 5) {
    $query = "SELECT s.*, 
                     COUNT(DISTINCT l.id) as lead_count,
                     SUM(CASE WHEN l.client_status IN ('Interested', 'Meeting Scheduled') THEN 1 ELSE 0 END) as quality_leads
              FROM services s 
              LEFT JOIN leads l ON FIND_IN_SET(s.id, REPLACE(REPLACE(l.selected_service_ids, '[', ''), ']', ''))
              WHERE s.is_active = 1
              GROUP BY s.id
              HAVING lead_count > 0
              ORDER BY quality_leads DESC, lead_count DESC
              LIMIT ?";
    
    return get_all($query, [$limit], 'i');
}

/**
 * Get service performance analytics
 * @param int|null $service_id
 * @return array
 */
function get_service_analytics($service_id = null) {
    $where_clause = $service_id ? "WHERE s.id = ?" : "WHERE s.is_active = 1";
    $params = $service_id ? [$service_id] : [];
    $types = $service_id ? 'i' : '';
    
    $query = "SELECT s.*, 
                     COUNT(DISTINCT l.id) as total_leads,
                     COUNT(DISTINCT ra.id) as running_ads_count,
                     SUM(CASE WHEN l.client_status = 'Interested' THEN 1 ELSE 0 END) as interested_leads,
                     SUM(CASE WHEN l.client_status = 'Meeting Scheduled' THEN 1 ELSE 0 END) as meeting_leads,
                     SUM(CASE WHEN l.client_status = 'Not Interested' THEN 1 ELSE 0 END) as not_interested_leads,
                     SUM(CASE WHEN l.client_status = 'Budget Not Met' THEN 1 ELSE 0 END) as budget_not_met_leads,
                     SUM(ra.budget) as total_ad_budget
              FROM services s 
              LEFT JOIN running_ads ra ON s.id = ra.service_id AND ra.is_active = 1
              LEFT JOIN leads l ON FIND_IN_SET(s.id, REPLACE(REPLACE(l.selected_service_ids, '[', ''), ']', ''))
              $where_clause
              GROUP BY s.id
              ORDER BY total_leads DESC";
    
    return $service_id ? get_row($query, $params, $types) : get_all($query, $params, $types);
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
        case 'view_service_analytics':
            return in_array($role, ['admin', 'marketing']);
        case 'create_services':
            return $role === 'admin';
        case 'edit_services':
            return $role === 'admin';
        case 'delete_services':
            return $role === 'admin';
        default:
            return false;
    }
}

/**
 * Get current logged in user information
 * @return array|null
 */
function get_logged_in_user() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        return null;
    }
    
    $query = "SELECT id, username, full_name, role FROM users WHERE id = ? AND is_active = 1";
    return get_row($query, [$_SESSION['user_id']], 'i');
}

/**
 * Get sales staff only for assignment
 * @return array
 */
function get_sales_staff() {
    $query = "SELECT id, username, full_name FROM users WHERE role = 'sales' AND is_active = 1 ORDER BY full_name";
    return get_all($query);
}

/**
 * Get all users for management
 * @return array
 */
function get_all_users() {
    $query = "SELECT id, username, full_name, role, is_active, created_at FROM users ORDER BY created_at DESC";
    return get_all($query);
}

/**
 * Create activity log entry (future enhancement)
 * @param int $user_id
 * @param string $action
 * @param string $details
 * @param int|null $lead_id
 */
function log_activity($user_id, $action, $details, $lead_id = null) {
    // Future implementation for activity logging
    // Could create an activity_logs table to track user actions
    $query = "INSERT INTO activity_logs (user_id, action, details, lead_id, created_at) VALUES (?, ?, ?, ?, NOW())";
    // execute_query($query, [$user_id, $action, $details, $lead_id], 'issi');
}

/**
 * Get dashboard statistics for current user
 * @param int $user_id
 * @param string $role
 * @return array
 */
function get_dashboard_stats($user_id, $role) {
    $stats = [
        'total_leads' => 0,
        'interested_leads' => 0,
        'meeting_leads' => 0,
        'not_interested_leads' => 0,
        'pending_followups' => 0,
        'overdue_followups' => 0,
        'this_month_leads' => 0,
        'active_campaigns' => 0
    ];
    
    // Build query based on role
    $where_clause = '';
    $params = [];
    $types = '';
    
    if ($role === 'sales') {
        $where_clause = 'WHERE l.assigned_to = ?';
        $params[] = $user_id;
        $types = 'i';
    }
    
    // Get lead statistics
    $query = "SELECT 
                COUNT(*) as total_leads,
                SUM(CASE WHEN l.client_status = 'Interested' THEN 1 ELSE 0 END) as interested_leads,
                SUM(CASE WHEN l.client_status = 'Meeting Scheduled' THEN 1 ELSE 0 END) as meeting_leads,
                SUM(CASE WHEN l.client_status = 'Not Interested' THEN 1 ELSE 0 END) as not_interested_leads,
                SUM(CASE WHEN l.follow_up IS NOT NULL AND DATE(l.follow_up) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as pending_followups,
                SUM(CASE WHEN l.follow_up IS NOT NULL AND DATE(l.follow_up) < CURDATE() THEN 1 ELSE 0 END) as overdue_followups,
                SUM(CASE WHEN MONTH(l.created_at) = MONTH(CURDATE()) AND YEAR(l.created_at) = YEAR(CURDATE()) THEN 1 ELSE 0 END) as this_month_leads
              FROM leads l 
              $where_clause";
    
    $result = get_row($query, $params, $types);
    if ($result) {
        $stats = array_merge($stats, $result);
    }
    
    // Get active campaigns (for marketing and admin)
    if (in_array($role, ['admin', 'marketing'])) {
        $campaign_query = "SELECT COUNT(*) as active_campaigns FROM running_ads WHERE is_active = 1";
        $campaign_result = get_row($campaign_query);
        if ($campaign_result) {
            $stats['active_campaigns'] = $campaign_result['active_campaigns'];
        }
    }
    
    return $stats;
}

/**
 * Parse selected service IDs from JSON or comma-separated string
 * @param string $selected_service_ids
 * @return array
 */
function parse_selected_service_ids($selected_service_ids) {
    if (empty($selected_service_ids)) {
        return [];
    }
    
    // Try to parse as JSON first
    $decoded = json_decode($selected_service_ids, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return array_map('intval', $decoded);
    }
    
    // Fallback to comma-separated parsing
    $ids = explode(',', str_replace(['"', '[', ']', ' '], '', $selected_service_ids));
    return array_filter(array_map('intval', $ids));
}

/**
 * Get service names from IDs
 * @param array $service_ids
 * @return array
 */
function get_service_names_by_ids($service_ids) {
    if (empty($service_ids)) {
        return [];
    }
    
    $placeholders = str_repeat('?,', count($service_ids) - 1) . '?';
    $query = "SELECT id, service_name FROM services WHERE id IN ($placeholders) ORDER BY service_name";
    $types = str_repeat('i', count($service_ids));
    
    return get_all($query, $service_ids, $types);
}

/**
 * Format service list for display
 * @param string $required_services
 * @param string $selected_service_ids
 * @return string
 */
function format_service_display($required_services, $selected_service_ids) {
    if (!empty($required_services)) {
        return $required_services;
    }
    
    if (!empty($selected_service_ids)) {
        $service_ids = parse_selected_service_ids($selected_service_ids);
        if (!empty($service_ids)) {
            $services = get_service_names_by_ids($service_ids);
            return implode(', ', array_column($services, 'service_name'));
        }
    }
    
    return 'No services specified';
}

/**
 * Get recent activity for dashboard
 * @param int $user_id
 * @param string $role
 * @param int $limit
 * @return array
 */
function get_recent_activity($user_id, $role, $limit = 10) {
    $where_clause = '';
    $params = [$limit];
    $types = 'i';
    
    if ($role === 'sales') {
        $where_clause = 'WHERE l.assigned_to = ?';
        $params = [$user_id, $limit];
        $types = 'ii';
    }
    
    $query = "SELECT l.id, l.client_name, l.client_status, l.created_at, l.updated_at,
                     u.full_name as assigned_user,
                     ra.ad_name as source_campaign
              FROM leads l 
              LEFT JOIN users u ON l.assigned_to = u.id
              LEFT JOIN running_ads ra ON l.source_ad_id = ra.id
              $where_clause
              ORDER BY l.updated_at DESC 
              LIMIT ?";
    
    return get_all($query, $params, $types);
}

/**
 * Search leads with filters
 * @param array $filters
 * @param int $user_id
 * @param string $role
 * @return array
 */
function search_leads($filters, $user_id, $role) {
    $where_conditions = [];
    $params = [];
    $types = '';
    
    // Role-based access control
    if ($role === 'sales') {
        $where_conditions[] = 'l.assigned_to = ?';
        $params[] = $user_id;
        $types .= 'i';
    }
    
    // Search filters
    if (!empty($filters['search'])) {
        $search_term = '%' . $filters['search'] . '%';
        $where_conditions[] = '(l.client_name LIKE ? OR l.email LIKE ? OR l.phone LIKE ? OR l.required_services LIKE ?)';
        $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
        $types .= 'ssss';
    }
    
    if (!empty($filters['status'])) {
        $where_conditions[] = 'l.client_status = ?';
        $params[] = $filters['status'];
        $types .= 's';
    }
    
    if (!empty($filters['industry'])) {
        $where_conditions[] = 'l.industry = ?';
        $params[] = $filters['industry'];
        $types .= 's';
    }
    
    if (!empty($filters['assigned_to'])) {
        $where_conditions[] = 'l.assigned_to = ?';
        $params[] = $filters['assigned_to'];
        $types .= 'i';
    }
    
    if (!empty($filters['date_from'])) {
        $where_conditions[] = 'DATE(l.created_at) >= ?';
        $params[] = $filters['date_from'];
        $types .= 's';
    }
    
    if (!empty($filters['date_to'])) {
        $where_conditions[] = 'DATE(l.created_at) <= ?';
        $params[] = $filters['date_to'];
        $types .= 's';
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $query = "SELECT l.*, u.full_name as assigned_user, ra.ad_name as source_ad_name, s.service_name as source_service
              FROM leads l 
              LEFT JOIN users u ON l.assigned_to = u.id 
              LEFT JOIN running_ads ra ON l.source_ad_id = ra.id
              LEFT JOIN services s ON ra.service_id = s.id
              $where_clause
              ORDER BY l.created_at DESC";
    
    return get_all($query, $params, $types);
}

/**
 * Validate service data
 * @param array $data
 * @return array Array of validation errors
 */
function validate_service_data($data) {
    $errors = [];
    
    if (empty($data['service_name'])) {
        $errors[] = 'Service name is required.';
    } elseif (strlen($data['service_name']) > 255) {
        $errors[] = 'Service name must be less than 255 characters.';
    }
    
    if (empty($data['service_category'])) {
        $errors[] = 'Service category is required.';
    } elseif (!in_array($data['service_category'], get_service_categories())) {
        $errors[] = 'Invalid service category.';
    }
    
    if (!empty($data['description']) && strlen($data['description']) > 1000) {
        $errors[] = 'Description must be less than 1000 characters.';
    }
    
    return $errors;
}

/**
 * Validate running ad data
 * @param array $data
 * @return array Array of validation errors
 */
function validate_ad_data($data) {
    $errors = [];
    
    if (empty($data['ad_name'])) {
        $errors[] = 'Ad name is required.';
    } elseif (strlen($data['ad_name']) > 255) {
        $errors[] = 'Ad name must be less than 255 characters.';
    }
    
    if (empty($data['service_id'])) {
        $errors[] = 'Service selection is required.';
    } elseif (!is_numeric($data['service_id'])) {
        $errors[] = 'Invalid service selection.';
    }
    
    if (empty($data['platform'])) {
        $errors[] = 'Platform is required.';
    } elseif (!in_array($data['platform'], get_ad_platforms())) {
        $errors[] = 'Invalid platform selection.';
    }
    
    if (!empty($data['budget']) && (!is_numeric($data['budget']) || $data['budget'] < 0)) {
        $errors[] = 'Budget must be a positive number.';
    }
    
    if (empty($data['start_date'])) {
        $errors[] = 'Start date is required.';
    } elseif (!strtotime($data['start_date'])) {
        $errors[] = 'Invalid start date format.';
    }
    
    if (!empty($data['end_date'])) {
        if (!strtotime($data['end_date'])) {
            $errors[] = 'Invalid end date format.';
        } elseif (!empty($data['start_date']) && strtotime($data['end_date']) < strtotime($data['start_date'])) {
            $errors[] = 'End date must be after start date.';
        }
    }
    
    if (empty($data['assigned_sales_member'])) {
        $errors[] = 'Sales member assignment is required.';
    } elseif (!is_numeric($data['assigned_sales_member'])) {
        $errors[] = 'Invalid sales member selection.';
    }
    
    return $errors;
}

/**
 * Get export data for leads
 * @param array $lead_ids
 * @return array
 */
function get_export_lead_data($lead_ids = []) {
    $where_clause = '';
    $params = [];
    $types = '';
    
    if (!empty($lead_ids)) {
        $placeholders = str_repeat('?,', count($lead_ids) - 1) . '?';
        $where_clause = "WHERE l.id IN ($placeholders)";
        $params = $lead_ids;
        $types = str_repeat('i', count($lead_ids));
    }
    
    $query = "SELECT l.id, l.client_name, l.required_services, l.website, l.phone, l.email,
                     l.call_enquiry, l.mail, l.whatsapp, l.follow_up, l.client_status,
                     l.notes, l.industry, l.lead_source, l.created_at, l.updated_at,
                     u.full_name as assigned_user,
                     ra.ad_name as source_campaign,
                     s.service_name as source_service
              FROM leads l 
              LEFT JOIN users u ON l.assigned_to = u.id
              LEFT JOIN running_ads ra ON l.source_ad_id = ra.id
              LEFT JOIN services s ON ra.service_id = s.id
              $where_clause
              ORDER BY l.created_at DESC";
    
    return get_all($query, $params, $types);
}

/**
 * Clean up old sessions and temporary data
 * @param int $days_old
 */
function cleanup_old_data($days_old = 30) {
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-$days_old days"));
    
    // Clean up old CSRF tokens (if stored in database)
    // execute_query("DELETE FROM session_tokens WHERE created_at < ?", [$cutoff_date], 's');
    
    // Clean up old activity logs (if implemented)
    // execute_query("DELETE FROM activity_logs WHERE created_at < ?", [$cutoff_date], 's');
}

/**
 * Generate unique slug for URLs
 * @param string $text
 * @return string
 */
function generate_slug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Format currency for display
 * @param float $amount
 * @param string $currency
 * @return string
 */
function format_currency($amount, $currency = ') {
    return $currency . number_format($amount, 2);
}

/**
 * Calculate percentage
 * @param int $value
 * @param int $total
 * @param int $decimals
 * @return float
 */
function calculate_percentage($value, $total, $decimals = 1) {
    if ($total == 0) return 0;
    return round(($value / $total) * 100, $decimals);
}

/**
 * Get time ago string
 * @param string $datetime
 * @return string
 */
function time_ago($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31536000) . ' years ago';
}

/**
 * Truncate text to specified length
 * @param string $text
 * @param int $length
 * @param string $suffix
 * @return string
 */
function truncate_text($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . $suffix;
}

/**
 * Check if current user can access lead
 * @param array $lead
 * @param int $user_id
 * @param string $role
 * @return bool
 */
function can_access_lead($lead, $user_id, $role) {
    if ($role === 'admin' || $role === 'marketing') {
        return true;
    }
    
    if ($role === 'sales') {
        return $lead['assigned_to'] == $user_id;
    }
    
    return false;
}

/**
 * Get system configuration value
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function get_config($key, $default = null) {
    // Future implementation for system configuration
    // Could create a config table to store system settings
    $configs = [
        'items_per_page' => 20,
        'date_format' => 'M j, Y',
        'datetime_format' => 'M j, Y g:i A',
        'currency_symbol' => ',
        'timezone' => 'UTC',
        'company_name' => 'Lead Management System',
        'max_file_upload_size' => '5MB'
    ];
    
    return isset($configs[$key]) ? $configs[$key] : $default;
}
?>