<?php
/**
 * Export Leads to CSV
 * Lead Management System
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Require login and export permission
require_login();

if (!has_permission('export_leads')) {
    redirect_with_message('index.php', 'You do not have permission to export leads.', 'error');
}

$current_user = get_current_user();

// Get leads based on user role
$leads = get_leads_by_role($current_user['id'], $current_user['role']);

// Set headers for CSV download
$filename = 'leads_export_' . date('Y-m-d_H-i-s') . '.csv';
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Create file handle
$output = fopen('php://output', 'w');

// Define CSV headers
$headers = [
    'ID',
    'Client Name',
    'Required Services',
    'Website',
    'Phone',
    'Email',
    'Call Enquiry',
    'Secondary Email',
    'WhatsApp',
    'Follow Up Date',
    'Status',
    'Notes',
    'Industry',
    'Created Date',
    'Updated Date'
];

// Add assigned user column for admin and marketing
if (in_array($current_user['role'], ['admin', 'marketing'])) {
    $headers[] = 'Assigned To';
}

// Write headers to CSV
fputcsv($output, $headers);

// Write data rows
foreach ($leads as $lead) {
    $row = [
        $lead['id'],
        $lead['client_name'],
        $lead['required_services'],
        $lead['website'],
        $lead['phone'],
        $lead['email'],
        $lead['call_enquiry'],
        $lead['mail'],
        $lead['whatsapp'],
        $lead['follow_up'],
        $lead['client_status'],
        $lead['notes'],
        $lead['industry'],
        $lead['created_at'],
        $lead['updated_at']
    ];
    
    // Add assigned user for admin and marketing
    if (in_array($current_user['role'], ['admin', 'marketing'])) {
        $row[] = $lead['assigned_user'] ?? 'Unassigned';
    }
    
    fputcsv($output, $row);
}

// Close file handle
fclose($output);

// Log export activity (future enhancement)
// log_activity($current_user['id'], 'exported_leads', count($leads) . ' leads exported');

exit();
?>