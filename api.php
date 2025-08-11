<?php
/**
 * API Endpoints for AJAX Operations
 * Lead Management System
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Set JSON header
header('Content-Type: application/json');

// Require login for all API endpoints
require_login();

$current_user = get_logged_in_user();
$response = ['success' => false, 'message' => '', 'data' => null];

// Get the action from request
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'update_lead_field':
            handleUpdateLeadField();
            break;
            
        case 'get_lead_data':
            handleGetLeadData();
            break;
            
        case 'get_services':
            handleGetServices();
            break;
            
        case 'get_running_ads':
            handleGetRunningAds();
            break;
            
        case 'get_dashboard_stats':
            handleGetDashboardStats();
            break;
            
        case 'assign_lead':
            handleAssignLead();
            break;
            
        case 'bulk_update':
            handleBulkUpdate();
            break;
            
        case 'get_lead_suggestions':
            handleGetLeadSuggestions();
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit();

/**
 * Update a single lead field via AJAX
 */
function handleUpdateLeadField() {
    global $response, $current_user;
    
    if (!has_permission('edit_leads')) {
        throw new Exception('You do not have permission to edit leads');
    }
    
    $lead_id = (int)($_POST['lead_id'] ?? 0);
    $field = sanitize_input($_POST['field'] ?? '');
    $value = sanitize_input($_POST['value'] ?? '');
    
    if (!$lead_id || !$field) {
        throw new Exception('Missing required parameters');
    }
    
    // Check if user can access this lead
    $lead = get_lead_by_id($lead_id, $current_user['id'], $current_user['role']);
    if (!$lead) {
        throw new Exception('Lead not found or access denied');
    }
    
    // Validate field
    $allowed_fields = ['follow_up', 'client_status', 'notes', 'industry'];
    if (!in_array($field, $allowed_fields)) {
        throw new Exception('Invalid field specified');
    }
    
    // Update the lead
    $query = "UPDATE leads SET $field = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    if (execute_query($query, [$value, $lead_id], 'si')) {
        $response['success'] = true;
        $response['message'] = 'Lead updated successfully';
        $response['data'] = ['field' => $field, 'value' => $value];
    } else {
        throw new Exception('Failed to update lead');
    }
}

/**
 * Get lead data by ID
 */
function handleGetLeadData() {
    global $response, $current_user;
    
    $lead_id = (int)($_GET['lead_id'] ?? 0);
    
    if (!$lead_id) {
        throw new Exception('Lead ID is required');
    }
    
    $lead = get_lead_by_id($lead_id, $current_user['id'], $current_user['role']);
    if (!$lead) {
        throw new Exception('Lead not found or access denied');
    }
    
    $response['success'] = true;
    $response['data'] = $lead;
}

/**
 * Get active services for dropdowns
 */
function handleGetServices() {
    global $response;
    
    $services = get_active_services();
    
    $response['success'] = true;
    $response['data'] = $services;
}

/**
 * Get running ads for dropdowns
 */
function handleGetRunningAds() {
    global $response, $current_user;
    
    if (!in_array($current_user['role'], ['admin', 'marketing'])) {
        throw new Exception('Access denied');
    }
    
    $ads = get_all("
        SELECT ra.*, s.service_name, u.full_name as assigned_sales_name 
        FROM running_ads ra 
        LEFT JOIN services s ON ra.service_id = s.id 
        LEFT JOIN users u ON ra.assigned_sales_member = u.id 
        WHERE ra.is_active = 1 
        ORDER BY ra.ad_name
    ");
    
    $response['success'] = true;
    $response['data'] = $ads;
}

/**
 * Get dashboard statistics
 */
function handleGetDashboardStats() {
    global $response, $current_user;
    
    $stats = get_dashboard_stats($current_user['id'], $current_user['role']);
    
    $response['success'] = true;
    $response['data'] = $stats;
}

/**
 * Assign lead to sales staff (admin only)
 */
function handleAssignLead() {
    global $response, $current_user;
    
    if ($current_user['role'] !== 'admin') {
        throw new Exception('Only administrators can assign leads');
    }
    
    $lead_id = (int)($_POST['lead_id'] ?? 0);
    $assigned_to = (int)($_POST['assigned_to'] ?? 0);
    
    if (!$lead_id) {
        throw new Exception('Lead ID is required');
    }
    
    // Validate assignment target
    if ($assigned_to > 0) {
        $sales_person = get_row("SELECT id FROM users WHERE id = ? AND role = 'sales' AND is_active = 1", [$assigned_to], 'i');
        if (!$sales_person) {
            throw new Exception('Invalid sales person selected');
        }
    }
    
    $query = "UPDATE leads SET assigned_to = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    if (execute_query($query, [$assigned_to ?: null, $lead_id], 'ii')) {
        $response['success'] = true;
        $response['message'] = 'Lead assignment updated successfully';
    } else {
        throw new Exception('Failed to update assignment');
    }
}

/**
 * Bulk update multiple leads
 */
function handleBulkUpdate() {
    global $response, $current_user;
    
    if (!has_permission('edit_leads')) {
        throw new Exception('You do not have permission to edit leads');
    }
    
    $lead_ids = $_POST['lead_ids'] ?? [];
    $field = sanitize_input($_POST['field'] ?? '');
    $value = sanitize_input($_POST['value'] ?? '');
    
    if (empty($lead_ids) || !$field) {
        throw new Exception('Missing required parameters');
    }
    
    // Validate field
    $allowed_fields = ['client_status', 'industry', 'assigned_to'];
    if (!in_array($field, $allowed_fields)) {
        throw new Exception('Invalid field specified');
    }
    
    // Convert lead_ids to integers
    $lead_ids = array_map('intval', $lead_ids);
    $placeholders = str_repeat('?,', count($lead_ids) - 1) . '?';
    
    // Check user access to leads
    if ($current_user['role'] === 'sales') {
        // Sales can only update their own leads
        $where_clause = "id IN ($placeholders) AND assigned_to = ?";
        $params = array_merge($lead_ids, [$current_user['id']]);
        $types = str_repeat('i', count($lead_ids)) . 'i';
    } else {
        // Admin and marketing can update any leads (if they have edit permission)
        $where_clause = "id IN ($placeholders)";
        $params = $lead_ids;
        $types = str_repeat('i', count($lead_ids));
    }
    
    $query = "UPDATE leads SET $field = ?, updated_at = CURRENT_TIMESTAMP WHERE $where_clause";
    array_unshift($params, $value);
    $types = 's' . $types;
    
    if (execute_query($query, $params, $types)) {
        $response['success'] = true;
        $response['message'] = 'Leads updated successfully';
        $response['data'] = ['updated_count' => count($lead_ids)];
    } else {
        throw new Exception('Failed to update leads');
    }
}

/**
 * Get lead suggestions for autocomplete
 */
function handleGetLeadSuggestions() {
    global $response, $current_user;
    
    $query = sanitize_input($_GET['query'] ?? '');
    
    if (strlen($query) < 2) {
        throw new Exception('Query must be at least 2 characters');
    }
    
    // Build search query based on user role
    $search_term = '%' . $query . '%';
    $where_clause = '(client_name LIKE ? OR email LIKE ? OR phone LIKE ?)';
    $params = [$search_term, $search_term, $search_term];
    $types = 'sss';
    
    if ($current_user['role'] === 'sales') {
        $where_clause .= ' AND assigned_to = ?';
        $params[] = $current_user['id'];
        $types .= 'i';
    }
    
    $sql = "SELECT id, client_name, email, phone, client_status 
            FROM leads 
            WHERE $where_clause 
            ORDER BY client_name 
            LIMIT 10";
    
    $suggestions = get_all($sql, $params, $types);
    
    $response['success'] = true;
    $response['data'] = $suggestions;
}
?>