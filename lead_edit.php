<?php
/**
 * Enhanced Edit Lead Page with Service Selection and Ad Source Tracking
 * Lead Management System
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Require login
require_login();

$current_user = get_logged_in_user();
$errors = [];
$success_message = '';

// Get lead ID from URL
$lead_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$lead_id) {
    redirect_with_message('index.php', 'Lead not found.', 'error');
}

// Get lead data with role-based access
$lead = get_lead_by_id($lead_id, $current_user['id'], $current_user['role']);

if (!$lead) {
    redirect_with_message('index.php', 'Lead not found or access denied.', 'error');
}

// Get sales staff for assignment (admin only)
$sales_staff = [];
if ($current_user['role'] === 'admin') {
    $sales_staff = get_sales_staff();
}

// Get active running ads for source selection
$running_ads = get_all("
    SELECT ra.*, s.service_name, u.full_name as assigned_sales_name 
    FROM running_ads ra 
    LEFT JOIN services s ON ra.service_id = s.id 
    LEFT JOIN users u ON ra.assigned_sales_member = u.id 
    WHERE ra.is_active = 1 
    ORDER BY ra.ad_name
");

// Handle form submission
if ($_POST && isset($_POST['update_lead'])) {
    // Check if user has edit permission
    if (!has_permission('edit_leads')) {
        redirect_with_message('index.php', 'You do not have permission to edit leads.', 'error');
    }

    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        // Sanitize and validate inputs
        $client_name = sanitize_input($_POST['client_name']);
        $required_services = sanitize_input($_POST['required_services']);
        $selected_service_ids = sanitize_input($_POST['selected_service_ids']);
        $website = sanitize_input($_POST['website']);
        $phone = sanitize_input($_POST['phone']);
        $email = sanitize_input($_POST['email']);
        $call_enquiry = sanitize_input($_POST['call_enquiry']);
        $mail = sanitize_input($_POST['mail']);
        $whatsapp = sanitize_input($_POST['whatsapp']);
        $follow_up = sanitize_input($_POST['follow_up']);
        $client_status = sanitize_input($_POST['client_status']);
        $notes = sanitize_input($_POST['notes']);
        $industry = sanitize_input($_POST['industry']);
        $lead_source = sanitize_input($_POST['lead_source']);
        $source_ad_id = !empty($_POST['source_ad_id']) ? (int)$_POST['source_ad_id'] : null;
        
        // Assignment logic - only admin can change assignment
        if ($current_user['role'] === 'admin') {
            // Check if source ad assignment should override manual assignment
            if ($source_ad_id && isset($_POST['use_ad_assignment']) && $_POST['use_ad_assignment'] === '1') {
                // Get the ad details to auto-assign to the designated sales member
                $ad_details = get_row("SELECT assigned_sales_member FROM running_ads WHERE id = ?", [$source_ad_id], 'i');
                if ($ad_details) {
                    $assigned_to = $ad_details['assigned_sales_member'];
                } else {
                    $assigned_to = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
                }
            } else {
                $assigned_to = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
            }
        } else {
            // Keep current assignment for non-admin users
            $assigned_to = $lead['assigned_to'];
        }

        // Validation
        if (empty($client_name)) {
            $errors[] = 'Client name is required.';
        }

        if (!empty($email) && !validate_email($email)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (!empty($phone) && !validate_phone($phone)) {
            $errors[] = 'Please enter a valid phone number.';
        }

        if (!empty($website) && !validate_url($website)) {
            $errors[] = 'Please enter a valid website URL.';
        }

        if (!empty($follow_up) && !strtotime($follow_up)) {
            $errors[] = 'Please enter a valid follow-up date.';
        }

        // If no errors, update the lead
        if (empty($errors)) {
            $query = "UPDATE leads SET client_name = ?, required_services = ?, selected_service_ids = ?, website = ?, phone = ?, email = ?, call_enquiry = ?, mail = ?, whatsapp = ?, follow_up = ?, client_status = ?, notes = ?, industry = ?, assigned_to = ?, source_ad_id = ?, lead_source = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            
            $params = [
                $client_name,
                $required_services,
                $selected_service_ids,
                $website,
                $phone,
                $email,
                $call_enquiry,
                $mail,
                $whatsapp,
                $follow_up ?: null,
                $client_status,
                $notes,
                $industry,
                $assigned_to,
                $source_ad_id,
                $lead_source,
                $lead_id
            ];
            
            if (execute_query($query, $params, 'ssssssssssssiisi')) {
                // Refresh lead data
                $lead = get_lead_by_id($lead_id, $current_user['id'], $current_user['role']);
                $success_message = 'Lead updated successfully!';
            } else {
                $errors[] = 'Failed to update lead. Please try again.';
            }
        }
    }
}

// Generate CSRF token
$csrf_token = generate_csrf_token();

// Determine read-only mode
$is_readonly = !has_permission('edit_leads');

// Lead source options
$lead_sources = [
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Lead - Lead Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="text-xl font-bold text-gray-900">Lead Management System</a>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Welcome, <?php echo sanitize_output($current_user['full_name']); ?></span>
                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">
                        <?php echo ucfirst($current_user['role']); ?>
                    </span>
                    <a href="index.php" class="text-blue-600 hover:text-blue-800">Dashboard</a>
                    <?php if (has_permission('view_analytics')): ?>
                        <a href="analytics.php" class="text-blue-600 hover:text-blue-800">Analytics</a>
                    <?php endif; ?>
                    <?php if ($current_user['role'] === 'marketing' || $current_user['role'] === 'admin'): ?>
                        <a href="running_ads.php" class="text-blue-600 hover:text-blue-800">Running Ads</a>
                    <?php endif; ?>
                    <a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-sm">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto py-6 px-4">
        <!-- Page Header -->
        <div class="mb-6">
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="index.php" class="text-gray-700 hover:text-gray-900">Dashboard</a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-500 ml-1 md:ml-2">
                                <?php echo $is_readonly ? 'View Lead' : 'Edit Lead'; ?>
                            </span>
                        </div>
                    </li>
                </ol>
            </nav>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mt-2">
                        <?php echo sanitize_output($lead['client_name']); ?>
                    </h1>
                    <p class="text-gray-600">
                        <?php echo $is_readonly ? 'View lead details' : 'Edit lead information'; ?>
                    </p>
                </div>
                <?php if ($is_readonly): ?>
                    <div class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded">
                        Read Only
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Success Message -->
        <?php if ($success_message): ?>
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                <?php echo sanitize_output($success_message); ?>
            </div>
        <?php endif; ?>

        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo sanitize_output($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Lead Information -->
        <div class="bg-white shadow rounded-lg">
            <!-- Lead Meta Info -->
            <div class="bg-gray-50 px-6 py-3 border-b border-gray-200">
                <div class="flex flex-wrap items-center justify-between text-sm text-gray-600">
                    <div>
                        <span class="font-medium">Created:</span> <?php echo format_datetime($lead['created_at']); ?>
                    </div>
                    <div>
                        <span class="font-medium">Last Updated:</span> <?php echo format_datetime($lead['updated_at']); ?>
                    </div>
                    <?php if ($lead['assigned_user']): ?>
                        <div>
                            <span class="font-medium">Assigned to:</span> <?php echo sanitize_output($lead['assigned_user']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <form method="POST" action="" class="space-y-6 p-6">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <!-- Lead Source Information -->
                <?php if (!$is_readonly): ?>
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Lead Source Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="lead_source" class="block text-sm font-medium text-gray-700">Lead Source</label>
                            <select 
                                id="lead_source" 
                                name="lead_source" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                onchange="toggleAdSelection()"
                            >
                                <?php foreach ($lead_sources as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo ($lead['lead_source'] === $value) ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="ad_selection" style="display: none;">
                            <label for="source_ad_id" class="block text-sm font-medium text-gray-700">Running Ad Campaign</label>
                            <select 
                                id="source_ad_id" 
                                name="source_ad_id" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">Select Ad Campaign</option>
                                <?php foreach ($running_ads as $ad): ?>
                                    <option value="<?php echo $ad['id']; ?>" 
                                            data-assigned-to="<?php echo $ad['assigned_sales_member']; ?>"
                                            data-assigned-name="<?php echo sanitize_output($ad['assigned_sales_name']); ?>"
                                            <?php echo ($lead['source_ad_id'] == $ad['id']) ? 'selected' : ''; ?>>
                                        <?php echo sanitize_output($ad['ad_name']) . ' (' . sanitize_output($ad['service_name']) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Update lead assignment based on selected ad campaign</p>
                        </div>
                    </div>
                    
                    <!-- Auto-assignment notice -->
                    <div id="auto_assignment_notice" class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-md" style="display: none;">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <input type="checkbox" id="use_ad_assignment" name="use_ad_assignment" value="1" class="mt-1">
                            </div>
                            <div class="ml-3">
                                <label for="use_ad_assignment" class="text-sm text-blue-700 cursor-pointer">
                                    <strong>Auto-Assignment:</strong> Check to automatically assign this lead to <span id="assigned_sales_name"></span> based on the selected ad campaign.
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Client Information -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Client Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="client_name" class="block text-sm font-medium text-gray-700">
                                Client Name <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="client_name" 
                                name="client_name" 
                                value="<?php echo sanitize_output($lead['client_name']); ?>"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 <?php echo $is_readonly ? 'bg-gray-50' : ''; ?>"
                                <?php echo $is_readonly ? 'readonly' : ''; ?>
                                required
                            >
                        </div>

                        <div>
                            <label for="industry" class="block text-sm font-medium text-gray-700">Industry</label>
                            <select 
                                id="industry" 
                                name="industry" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 <?php echo $is_readonly ? 'bg-gray-50' : ''; ?>"
                                <?php echo $is_readonly ? 'disabled' : ''; ?>
                            >
                                <option value="">Select Industry</option>
                                <?php foreach (get_industry_options() as $industry_option): ?>
                                    <option value="<?php echo $industry_option; ?>" <?php echo $lead['industry'] === $industry_option ? 'selected' : ''; ?>>
                                        <?php echo $industry_option; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="required_services" class="block text-sm font-medium text-gray-700">Required Services</label>
                            <?php if (!$is_readonly): ?>
                            <div class="mt-1">
                                <select 
                                    id="service_selector" 
                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 mb-2"
                                >
                                    <option value="">Select a service to add</option>
                                    <?php 
                                    $active_services = get_active_services();
                                    $services_by_category = [];
                                    foreach ($active_services as $service) {
                                        $services_by_category[$service['service_category']][] = $service;
                                    }
                                    ?>
                                    <?php foreach ($services_by_category as $category => $services): ?>
                                        <optgroup label="<?php echo sanitize_output($category); ?>">
                                            <?php foreach ($services as $service): ?>
                                                <option value="<?php echo $service['id']; ?>" data-name="<?php echo sanitize_output($service['service_name']); ?>">
                                                    <?php echo sanitize_output($service['service_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                                
                                <!-- Selected Services Display -->
                                <div id="selected_services" class="min-h-[40px] border border-gray-300 rounded-md p-2 bg-gray-50">
                                    <div class="text-sm text-gray-500" id="no_services_message">No services selected</div>
                                </div>
                                
                                <!-- Hidden inputs to store selected services -->
                                <input type="hidden" id="required_services" name="required_services" value="<?php echo sanitize_output($lead['required_services']); ?>">
                                <input type="hidden" id="selected_service_ids" name="selected_service_ids" value="<?php echo sanitize_output($lead['selected_service_ids'] ?? ''); ?>">
                            </div>
                            <?php else: ?>
                            <input 
                                type="text" 
                                name="required_services"
                                value="<?php echo sanitize_output($lead['required_services']); ?>"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 bg-gray-50"
                                readonly
                            >
                            <?php endif; ?>
                        </div>

                        <div>
                            <label for="website" class="block text-sm font-medium text-gray-700">Website</label>
                            <input 
                                type="url" 
                                id="website" 
                                name="website" 
                                value="<?php echo sanitize_output($lead['website']); ?>"
                                placeholder="https://example.com"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 <?php echo $is_readonly ? 'bg-gray-50' : ''; ?>"
                                <?php echo $is_readonly ? 'readonly' : ''; ?>
                            >
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Contact Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                value="<?php echo sanitize_output($lead['email']); ?>"
                                placeholder="contact@example.com"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 <?php echo $is_readonly ? 'bg-gray-50' : ''; ?>"
                                <?php echo $is_readonly ? 'readonly' : ''; ?>
                            >
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                            <input 
                                type="tel" 
                                id="phone" 
                                name="phone" 
                                value="<?php echo sanitize_output($lead['phone']); ?>"
                                placeholder="+1234567890"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 <?php echo $is_readonly ? 'bg-gray-50' : ''; ?>"
                                <?php echo $is_readonly ? 'readonly' : ''; ?>
                            >
                        </div>

                        <div>
                            <label for="whatsapp" class="block text-sm font-medium text-gray-700">WhatsApp</label>
                            <input 
                                type="tel" 
                                id="whatsapp" 
                                name="whatsapp" 
                                value="<?php echo sanitize_output($lead['whatsapp']); ?>"
                                placeholder="+1234567890"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 <?php echo $is_readonly ? 'bg-gray-50' : ''; ?>"
                                <?php echo $is_readonly ? 'readonly' : ''; ?>
                            >
                        </div>

                        <div>
                            <label for="mail" class="block text-sm font-medium text-gray-700">Secondary Email</label>
                            <input 
                                type="email" 
                                id="mail" 
                                name="mail" 
                                value="<?php echo sanitize_output($lead['mail']); ?>"
                                placeholder="secondary@example.com"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 <?php echo $is_readonly ? 'bg-gray-50' : ''; ?>"
                                <?php echo $is_readonly ? 'readonly' : ''; ?>
                            >
                        </div>
                    </div>
                </div>

                <!-- Lead Details -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Lead Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="client_status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select 
                                id="client_status" 
                                name="client_status" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 <?php echo $is_readonly ? 'bg-gray-50' : ''; ?>"
                                <?php echo $is_readonly ? 'disabled' : ''; ?>
                            >
                                <?php foreach (get_status_options() as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo $lead['client_status'] === $value ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="follow_up" class="block text-sm font-medium text-gray-700">Follow-up Date</label>
                            <input 
                                type="date" 
                                id="follow_up" 
                                name="follow_up" 
                                value="<?php echo $lead['follow_up']; ?>"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 <?php echo $is_readonly ? 'bg-gray-50' : ''; ?>"
                                <?php echo $is_readonly ? 'readonly' : ''; ?>
                            >
                        </div>

                        <?php if ($current_user['role'] === 'admin'): ?>
                        <div id="manual_assignment">
                            <label for="assigned_to" class="block text-sm font-medium text-gray-700">Assign To</label>
                            <select 
                                id="assigned_to" 
                                name="assigned_to" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 <?php echo $is_readonly ? 'bg-gray-50' : ''; ?>"
                                <?php echo $is_readonly ? 'disabled' : ''; ?>
                            >
                                <option value="">Unassigned</option>
                                <?php foreach ($sales_staff as $staff): ?>
                                    <option value="<?php echo $staff['id']; ?>" <?php echo $lead['assigned_to'] == $staff['id'] ? 'selected' : ''; ?>>
                                        <?php echo sanitize_output($staff['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div class="md:col-span-2">
                            <label for="call_enquiry" class="block text-sm font-medium text-gray-700">Call Enquiry Details</label>
                            <textarea 
                                id="call_enquiry" 
                                name="call_enquiry" 
                                rows="3"
                                placeholder="Details about the initial call or enquiry..."
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 <?php echo $is_readonly ? 'bg-gray-50' : ''; ?>"
                                <?php echo $is_readonly ? 'readonly' : ''; ?>
                            ><?php echo sanitize_output($lead['call_enquiry']); ?></textarea>
                        </div>

                        <div class="md:col-span-2">
                            <label for="notes" class="block text-sm font-medium text-gray-700">Additional Notes</label>
                            <textarea 
                                id="notes" 
                                name="notes" 
                                rows="4"
                                placeholder="Any additional information about the lead..."
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 <?php echo $is_readonly ? 'bg-gray-50' : ''; ?>"
                                <?php echo $is_readonly ? 'readonly' : ''; ?>
                            ><?php echo sanitize_output($lead['notes']); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                    <a href="index.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                        Back to Dashboard
                    </a>
                    
                    <?php if (!$is_readonly): ?>
                        <div class="space-x-4">
                            <button 
                                type="button"
                                onclick="window.location.reload()"
                                class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded"
                            >
                                Reset
                            </button>
                            <button 
                                type="submit" 
                                name="update_lead" 
                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                            >
                                Update Lead
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Activity Log Section -->
        <div class="mt-8 bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Activity Log</h3>
                <p class="text-sm text-gray-500">Recent activities and updates for this lead</p>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-900">Lead created</p>
                            <p class="text-xs text-gray-500"><?php echo format_datetime($lead['created_at']); ?></p>
                        </div>
                    </div>
                    
                    <?php if ($lead['updated_at'] !== $lead['created_at']): ?>
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-900">Lead updated</p>
                            <p class="text-xs text-gray-500"><?php echo format_datetime($lead['updated_at']); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($lead['source_ad_name']): ?>
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-900">Generated from ad campaign</p>
                            <p class="text-xs text-gray-500">
                                <?php echo sanitize_output($lead['source_ad_name']); ?>
                                <?php if ($lead['source_service']): ?>
                                    (<?php echo sanitize_output($lead['source_service']); ?>)
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Service Selection Management
        let selectedServices = [];

        // Initialize selected services from existing lead data
        document.addEventListener('DOMContentLoaded', function() {
            const requiredServicesValue = document.getElementById('required_services')?.value;
            const selectedServiceIdsValue = document.getElementById('selected_service_ids')?.value;
            
            if (requiredServicesValue && selectedServiceIdsValue) {
                try {
                    const serviceIds = JSON.parse(selectedServiceIdsValue);
                    const serviceNames = requiredServicesValue.split(', ');
                    
                    serviceIds.forEach((id, index) => {
                        if (serviceNames[index]) {
                            selectedServices.push({
                                id: parseInt(id),
                                name: serviceNames[index]
                            });
                        }
                    });
                    
                    updateSelectedServicesDisplay();
                } catch (e) {
                    // If JSON parsing fails, try to parse as comma-separated values
                    if (requiredServicesValue) {
                        const serviceNames = requiredServicesValue.split(', ');
                        serviceNames.forEach((name, index) => {
                            if (name.trim()) {
                                selectedServices.push({
                                    id: index + 1000, // Temporary ID for existing services
                                    name: name.trim()
                                });
                            }
                        });
                        updateSelectedServicesDisplay();
                    }
                }
            }
        });

        // Handle service selection (only if not readonly)
        const serviceSelector = document.getElementById('service_selector');
        if (serviceSelector) {
            serviceSelector.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption.value) {
                    const serviceId = parseInt(selectedOption.value);
                    const serviceName = selectedOption.getAttribute('data-name');
                    
                    // Check if service is already selected
                    if (!selectedServices.find(service => service.id === serviceId)) {
                        selectedServices.push({
                            id: serviceId,
                            name: serviceName
                        });
                        
                        updateSelectedServicesDisplay();
                        updateHiddenFields();
                    }
                    
                    // Reset selector
                    this.value = '';
                }
            });
        }

        // Remove service from selection
        function removeService(serviceId) {
            selectedServices = selectedServices.filter(service => service.id !== serviceId);
            updateSelectedServicesDisplay();
            updateHiddenFields();
        }

        // Update the visual display of selected services
        function updateSelectedServicesDisplay() {
            const container = document.getElementById('selected_services');
            const noServicesMessage = document.getElementById('no_services_message');
            
            if (!container) return;
            
            if (selectedServices.length === 0) {
                if (noServicesMessage) noServicesMessage.style.display = 'block';
                // Clear any service tags
                const serviceTags = container.querySelectorAll('.service-tag');
                serviceTags.forEach(tag => tag.remove());
            } else {
                if (noServicesMessage) noServicesMessage.style.display = 'none';
                
                // Clear existing tags
                const existingTags = container.querySelectorAll('.service-tag');
                existingTags.forEach(tag => tag.remove());
                
                // Add new tags
                selectedServices.forEach(service => {
                    const serviceTag = document.createElement('span');
                    serviceTag.className = 'service-tag inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-2 mb-2';
                    serviceTag.innerHTML = `
                        ${service.name}
                        <button type="button" onclick="removeService(${service.id})" class="ml-1 text-blue-600 hover:text-blue-800">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    `;
                    container.appendChild(serviceTag);
                });
            }
        }

        // Update hidden form fields
        function updateHiddenFields() {
            const serviceNames = selectedServices.map(service => service.name).join(', ');
            const serviceIds = JSON.stringify(selectedServices.map(service => service.id));
            
            const requiredServicesField = document.getElementById('required_services');
            const selectedServiceIdsField = document.getElementById('selected_service_ids');
            
            if (requiredServicesField) requiredServicesField.value = serviceNames;
            if (selectedServiceIdsField) selectedServiceIdsField.value = serviceIds;
        }

        // Toggle ad selection based on lead source
        function toggleAdSelection() {
            const leadSource = document.getElementById('lead_source')?.value;
            const adSelection = document.getElementById('ad_selection');
            const sourceAdSelect = document.getElementById('source_ad_id');
            
            if (!leadSource || !adSelection) return;
            
            if (leadSource.includes('Ad') || leadSource === 'Facebook Ad' || leadSource === 'Google Ad' || leadSource === 'Instagram Ad' || leadSource === 'LinkedIn Ad') {
                adSelection.style.display = 'block';
                if (sourceAdSelect) sourceAdSelect.required = false; // Not required for editing
            } else {
                adSelection.style.display = 'none';
                if (sourceAdSelect) {
                    sourceAdSelect.required = false;
                    sourceAdSelect.value = '';
                }
                hideAutoAssignmentNotice();
            }
        }

        // Show auto-assignment notice
        function showAutoAssignmentNotice(salesName) {
            const notice = document.getElementById('auto_assignment_notice');
            const assignedName = document.getElementById('assigned_sales_name');
            
            if (!notice || !assignedName) return;
            
            assignedName.textContent = salesName;
            notice.style.display = 'block';
        }

        // Hide auto-assignment notice
        function hideAutoAssignmentNotice() {
            const notice = document.getElementById('auto_assignment_notice');
            if (notice) notice.style.display = 'none';
        }

        // Handle ad selection change
        document.addEventListener('DOMContentLoaded', function() {
            const sourceAdSelect = document.getElementById('source_ad_id');
            if (sourceAdSelect) {
                sourceAdSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const assignedName = selectedOption.getAttribute('data-assigned-name');
                    
                    if (this.value && assignedName) {
                        showAutoAssignmentNotice(assignedName);
                    } else {
                        hideAutoAssignmentNotice();
                    }
                });
            }
            
            // Initialize form state
            toggleAdSelection();
            
            // Check if ad is already selected and show notice
            const currentAdSelection = sourceAdSelect?.value;
            if (currentAdSelection) {
                const selectedOption = sourceAdSelect.querySelector(`option[value="${currentAdSelection}"]`);
                if (selectedOption) {
                    const assignedName = selectedOption.getAttribute('data-assigned-name');
                    if (assignedName) {
                        showAutoAssignmentNotice(assignedName);
                    }
                }
            }
        });

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const clientNameField = document.getElementById('client_name');

            if (!form || <?php echo $is_readonly ? 'true' : 'false'; ?>) return;

            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                // Reset previous error states
                document.querySelectorAll('.border-red-500').forEach(field => {
                    field.classList.remove('border-red-500');
                });

                // Validate required fields
                if (clientNameField && !clientNameField.value.trim()) {
                    clientNameField.classList.add('border-red-500');
                    isValid = false;
                }

                // Validate email format if provided
                const emailField = document.getElementById('email');
                if (emailField && emailField.value && !isValidEmail(emailField.value)) {
                    emailField.classList.add('border-red-500');
                    isValid = false;
                }

                // Validate website URL if provided
                const websiteField = document.getElementById('website');
                if (websiteField && websiteField.value && !isValidURL(websiteField.value)) {
                    websiteField.classList.add('border-red-500');
                    isValid = false;
                }

                if (!isValid) {
                    e.preventDefault();
                    alert('Please check the highlighted fields and correct any errors.');
                }
            });

            // Phone number formatting
            document.querySelectorAll('input[type="tel"]').forEach(function(field) {
                field.addEventListener('input', function() {
                    // Allow only numbers, spaces, dashes, parentheses, and plus sign
                    this.value = this.value.replace(/[^\d\s\-\(\)\+]/g, '');
                });
            });

            // Show confirmation for unsaved changes
            let formChanged = false;
            const formElements = document.querySelectorAll('input, select, textarea');
            
            formElements.forEach(element => {
                element.addEventListener('change', function() {
                    formChanged = true;
                });
            });

            window.addEventListener('beforeunload', function(e) {
                if (formChanged) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });

            // Clear flag when form is submitted
            form.addEventListener('submit', function() {
                formChanged = false;
            });

            // Lead source change handler
            const leadSourceField = document.getElementById('lead_source');
            if (leadSourceField) {
                leadSourceField.addEventListener('change', toggleAdSelection);
            }
        });

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        function isValidURL(url) {
            try {
                new URL(url);
                return true;
            } catch {
                return false;
            }
        }
    </script>
</body>
</html>