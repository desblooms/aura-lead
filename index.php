<?php
/**
 * Dashboard / Lead Listing Page with Mobile Navigation
 * Lead Management System
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/navigation.php';
require_once 'includes/mobile-nav.php';

// Require login
require_login();

// Get current user
$current_user = get_logged_in_user();
if (!$current_user) {
    header('Location: login.php');
    exit();
}

// Get flash message
$flash_message = get_flash_message();

// Get leads based on role
$leads = get_leads_by_role($current_user['id'], $current_user['role']);

// Handle inline editing for allowed roles
if ($_POST && isset($_POST['update_lead'])) {
    $lead_id = (int)$_POST['lead_id'];
    $field = sanitize_input($_POST['field']);
    $value = sanitize_input($_POST['value']);
    
    // Check if user can edit this lead
    $lead = get_lead_by_id($lead_id, $current_user['id'], $current_user['role']);
    
    if ($lead && has_permission('edit_leads')) {
        $allowed_fields = ['follow_up', 'client_status', 'notes'];
        
        if (in_array($field, $allowed_fields)) {
            $query = "UPDATE leads SET $field = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            execute_query($query, [$value, $lead_id], 'si');
            
            // Return JSON response for AJAX
            if (isset($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit();
            }
            
            redirect_with_message('index.php', 'Lead updated successfully', 'success');
        }
    }
}

// Handle lead deletion (admin only)
if ($_POST && isset($_POST['delete_lead'])) {
    if ($current_user['role'] === 'admin') {
        $lead_id = (int)$_POST['lead_id'];
        $query = "DELETE FROM leads WHERE id = ?";
        execute_query($query, [$lead_id], 'i');
        redirect_with_message('index.php', 'Lead deleted successfully', 'success');
    }
}

// Get current page for navigation
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Calculate statistics
$interested = array_filter($leads, function($lead) { return $lead['client_status'] === 'Interested'; });
$meetings = array_filter($leads, function($lead) { return $lead['client_status'] === 'Meeting Scheduled'; });
$not_interested = array_filter($leads, function($lead) { return $lead['client_status'] === 'Not Interested'; });
$budget_not_met = array_filter($leads, function($lead) { return $lead['client_status'] === 'Budget Not Met'; });

// Prepare stats array for mobile
$mobile_stats = [
    [
        'label' => 'Interested',
        'value' => count($interested),
        'icon' => 'fas fa-heart',
        'color' => 'text-green-600',
        'bg_color' => 'bg-green-100',
        'icon_color' => 'text-green-500'
    ],
    [
        'label' => 'Meetings',
        'value' => count($meetings),
        'icon' => 'fas fa-calendar',
        'color' => 'text-blue-600',
        'bg_color' => 'bg-blue-100',
        'icon_color' => 'text-blue-500'
    ],
    [
        'label' => 'Not Interested',
        'value' => count($not_interested),
        'icon' => 'fas fa-times',
        'color' => 'text-red-600',
        'bg_color' => 'bg-red-100',
        'icon_color' => 'text-red-500'
    ],
    [
        'label' => 'Budget Issues',
        'value' => count($budget_not_met),
        'icon' => 'fas fa-dollar-sign',
        'color' => 'text-yellow-600',
        'bg_color' => 'bg-yellow-100',
        'icon_color' => 'text-yellow-500'
    ]
];

// Get sales staff for assignment (admin only)
$sales_staff = [];
if ($current_user['role'] === 'admin') {
    $sales_staff = get_sales_staff();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Lead Management Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Mobile App-like Styles */
        .mobile-card {
            background: linear-gradient(145deg, #ffffff, #f8fafc);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .mobile-card:active {
            transform: scale(0.98);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }
        
        .status-badge {
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .floating-action {
            position: fixed;
            bottom: 80px;
            right: 20px;
            z-index: 40;
            box-shadow: 0 8px 32px rgba(59, 130, 246, 0.35);
        }
        
        .mobile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            backdrop-filter: blur(20px);
        }
        
        .mobile-tab-bar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .mobile-tab-item {
            transition: all 0.2s ease;
        }
        
        .mobile-tab-item.active {
            color: #3b82f6;
            transform: translateY(-2px);
        }
        
        .mobile-tab-item:not(.active):active {
            transform: scale(0.95);
        }
        
        /* Desktop ClickUp-inspired Styles */
        @media (min-width: 1024px) {
            .desktop-card {
                background: rgba(255, 255, 255, 0.98);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.2);
                transition: all 0.3s ease;
            }
            
            .desktop-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
            }
            
            .table-row:hover {
                background: linear-gradient(90deg, rgba(59, 130, 246, 0.03), rgba(59, 130, 246, 0.08));
                transform: translateX(4px);
                transition: all 0.2s ease;
            }
        }
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .slide-up {
            animation: slideUp 0.3s ease-out;
        }
        
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .ripple {
            position: relative;
            overflow: hidden;
        }
        
        .ripple::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .ripple:active::before {
            width: 300px;
            height: 300px;
        }

        /* More menu styles */
        #more-menu.show {
            transform: translateY(0);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 min-h-screen">
    
    <!-- Mobile Layout -->
    <?php echo render_mobile_header($current_user, 'Dashboard'); ?>
    
    <!-- Flash Messages for Mobile -->
    <?php echo render_mobile_flash_message($flash_message); ?>

    <!-- Mobile Stats Cards -->
    <?php echo render_mobile_stats($mobile_stats); ?>

    <!-- Mobile Leads List -->
    <div class="lg:hidden px-4 pb-24">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-900">Recent Leads</h2>
            <div class="flex items-center space-x-2">
                <a href="search_leads.php" class="w-8 h-8 bg-white rounded-xl flex items-center justify-center shadow-sm">
                    <i class="fas fa-search text-gray-600 text-sm"></i>
                </a>
                <button class="w-8 h-8 bg-white rounded-xl flex items-center justify-center shadow-sm">
                    <i class="fas fa-filter text-gray-600 text-sm"></i>
                </button>
            </div>
        </div>
        
        <?php if (empty($leads)): ?>
            <div class="mobile-card rounded-3xl p-8 text-center">
                <div class="w-20 h-20 bg-gray-100 rounded-3xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-inbox text-gray-400 text-3xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">No leads yet</h3>
                <p class="text-gray-500 mb-6">Start by adding your first lead to get started</p>
                <?php if (has_permission('add_leads')): ?>
                    <a href="lead_add.php" class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-8 py-3 rounded-2xl font-semibold ripple">
                        Add First Lead
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach (array_slice($leads, 0, 10) as $lead): ?>
                    <div class="mobile-card rounded-3xl p-5 ripple" onclick="window.location='lead_edit.php?id=<?php echo $lead['id']; ?>'">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center space-x-3 flex-1">
                                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center">
                                    <span class="text-white font-bold text-sm">
                                        <?php echo strtoupper(substr($lead['client_name'], 0, 1)); ?>
                                    </span>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900 text-base leading-tight">
                                        <?php echo sanitize_output($lead['client_name']); ?>
                                    </h3>
                                    <p class="text-gray-500 text-sm mt-1">
                                        <?php echo sanitize_output(substr($lead['required_services'], 0, 30)) . (strlen($lead['required_services']) > 30 ? '...' : ''); ?>
                                    </p>
                                </div>
                            </div>
                            <span class="status-badge inline-flex px-3 py-1 text-xs font-semibold rounded-full <?php echo get_status_class($lead['client_status']); ?>">
                                <?php echo $lead['client_status'] ?: 'New'; ?>
                            </span>
                        </div>
                        
                        <div class="flex items-center justify-between text-sm text-gray-600">
                            <div class="flex items-center space-x-4">
                                <div class="flex items-center space-x-1">
                                    <i class="fas fa-envelope text-gray-400"></i>
                                    <span><?php echo sanitize_output(substr($lead['email'], 0, 20)) . (strlen($lead['email']) > 20 ? '...' : ''); ?></span>
                                </div>
                            </div>
                            <i class="fas fa-chevron-right text-gray-400"></i>
                        </div>
                        
                        <?php if ($lead['follow_up']): ?>
                            <div class="mt-3 pt-3 border-t border-gray-100">
                                <div class="flex items-center text-sm text-orange-600">
                                    <i class="fas fa-clock mr-2"></i>
                                    <span class="font-medium">Follow up: <?php echo format_date($lead['follow_up']); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <?php if (count($leads) > 10): ?>
                    <div class="text-center mt-6">
                        <a href="search_leads.php" class="text-blue-600 font-semibold">
                            View all <?php echo count($leads); ?> leads →
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Floating Action Button for Mobile -->
    <?php if (has_permission('add_leads')): ?>
        <?php echo render_mobile_fab('lead_add.php', 'fas fa-plus', 'Add Lead'); ?>
    <?php endif; ?>

    <!-- Mobile Bottom Navigation -->
    <?php echo render_mobile_bottom_nav($current_user, $current_page, $mobile_nav_items); ?>

    <!-- Desktop Layout -->
    <div class="hidden lg:block">
        <!-- Desktop Navigation -->
        <?php echo render_complete_navigation($current_user, $current_page, $nav_items); ?>

        <div class="max-w-7xl mx-auto py-6 px-6">
            <!-- Flash Messages -->
            <?php if ($flash_message): ?>
                <div class="mb-6 p-4 rounded-2xl backdrop-blur-lg slide-up <?php echo $flash_message['type'] === 'success' ? 'bg-green-100/80 text-green-700 border border-green-200' : ($flash_message['type'] === 'error' ? 'bg-red-100/80 text-red-700 border border-red-200' : 'bg-blue-100/80 text-blue-700 border border-blue-200'); ?>">
                    <div class="flex items-center">
                        <i class="fas fa-<?php echo $flash_message['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> mr-3 text-lg"></i>
                        <span class="font-medium"><?php echo sanitize_output($flash_message['text']); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="mb-8 flex flex-col xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Leads Overview</h2>
                    <p class="text-gray-600 text-lg">
                        <?php 
                        if ($current_user['role'] === 'sales') {
                            echo "Manage your assigned leads and track progress";
                        } else {
                            echo "Complete overview of all leads and team performance";
                        }
                        ?>
                    </p>
                </div>
                <div class="mt-6 xl:mt-0 flex flex-wrap gap-3">
                    <?php if (has_permission('add_leads')): ?>
                        <a href="lead_add.php" class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-6 py-3 rounded-xl font-semibold transition-all shadow-lg hover:shadow-xl flex items-center ripple">
                            <i class="fas fa-plus mr-2"></i>New Lead
                        </a>
                    <?php endif; ?>
                    <?php if (has_permission('export_leads')): ?>
                        <a href="export.php" class="bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-6 py-3 rounded-xl font-semibold transition-all shadow-lg hover:shadow-xl flex items-center ripple">
                            <i class="fas fa-download mr-2"></i>Export
                        </a>
                    <?php endif; ?>
                    <a href="import_leads.php" class="bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white px-6 py-3 rounded-xl font-semibold transition-all shadow-lg hover:shadow-xl flex items-center ripple">
                        <i class="fas fa-upload mr-2"></i>Import
                    </a>
                </div>
            </div>

            <!-- Stats Cards Desktop -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
                <div class="desktop-card rounded-2xl p-6 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-600 uppercase tracking-wide">Total Leads</p>
                            <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo count($leads); ?></p>
                        </div>
                        <div class="p-3 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl">
                            <i class="fas fa-users text-white text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="desktop-card rounded-2xl p-6 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-600 uppercase tracking-wide">Interested</p>
                            <p class="text-3xl font-bold text-green-600 mt-1"><?php echo count($interested); ?></p>
                        </div>
                        <div class="p-3 bg-gradient-to-r from-green-500 to-green-600 rounded-xl">
                            <i class="fas fa-heart text-white text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="desktop-card rounded-2xl p-6 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-600 uppercase tracking-wide">Meetings</p>
                            <p class="text-3xl font-bold text-blue-600 mt-1"><?php echo count($meetings); ?></p>
                        </div>
                        <div class="p-3 bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl">
                            <i class="fas fa-calendar text-white text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="desktop-card rounded-2xl p-6 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-600 uppercase tracking-wide">Conversion Rate</p>
                            <p class="text-3xl font-bold text-purple-600 mt-1">
                                <?php 
                                $total_contacted = count(array_filter($leads, function($lead) { 
                                    return !empty($lead['client_status']); 
                                }));
                                $converted = count($interested) + count($meetings);
                                echo $total_contacted > 0 ? round(($converted / $total_contacted) * 100, 1) : 0;
                                ?>%
                            </p>
                        </div>
                        <div class="p-3 bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl">
                            <i class="fas fa-chart-line text-white text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Desktop Table -->
            <div class="desktop-card rounded-2xl shadow-xl overflow-hidden">
                <div class="px-6 py-6 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">All Leads</h3>
                            <p class="text-gray-500 mt-1"><?php echo count($leads); ?> total leads • Updated just now</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <button class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                                <i class="fas fa-filter"></i>
                            </button>
                            <a href="search_leads.php" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                                <i class="fas fa-search"></i>
                            </a>
                            <button class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                                <i class="fas fa-columns"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <?php if (empty($leads)): ?>
                    <div class="text-center py-16">
                        <div class="w-24 h-24 bg-gray-100 rounded-3xl flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-inbox text-gray-400 text-4xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">No leads found</h3>
                        <p class="text-gray-500 text-lg mb-8">Start building your pipeline by adding your first lead</p>
                        <?php if (has_permission('add_leads')): ?>
                            <a href="lead_add.php" class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-8 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all ripple">
                                <i class="fas fa-plus mr-2"></i>Add Your First Lead
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50/50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Client</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Contact</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Follow Up</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Industry</th>
                                    <?php if ($current_user['role'] === 'admin'): ?>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Assigned To</th>
                                    <?php endif; ?>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($leads as $lead): ?>
                                    <tr class="table-row hover:bg-blue-50/30 transition-all duration-200 border-l-4 border-transparent hover:border-blue-500">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg">
                                                    <span class="text-white font-bold text-sm"><?php echo strtoupper(substr($lead['client_name'], 0, 1)); ?></span>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-bold text-gray-900">
                                                        <?php echo sanitize_output($lead['client_name']); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500 mt-1">
                                                        <?php echo sanitize_output(substr($lead['required_services'], 0, 40)) . (strlen($lead['required_services']) > 40 ? '...' : ''); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900 flex items-center mb-1">
                                                <i class="fas fa-envelope text-gray-400 mr-2"></i>
                                                <span class="font-medium"><?php echo sanitize_output($lead['email']); ?></span>
                                            </div>
                                            <div class="text-sm text-gray-500 flex items-center">
                                                <i class="fas fa-phone text-gray-400 mr-2"></i>
                                                <?php echo sanitize_output($lead['phone']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if (has_permission('edit_leads')): ?>
                                                <select class="status-select text-sm rounded-full px-4 py-2 font-semibold border-0 focus:ring-2 focus:ring-blue-500 <?php echo get_status_class($lead['client_status']); ?>" 
                                                        data-lead-id="<?php echo $lead['id']; ?>" data-field="client_status">
                                                    <?php foreach (get_status_options() as $value => $label): ?>
                                                        <option value="<?php echo $value; ?>" <?php echo $lead['client_status'] === $value ? 'selected' : ''; ?>>
                                                            <?php echo $label; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php else: ?>
                                                <span class="inline-flex px-4 py-2 text-sm font-semibold rounded-full <?php echo get_status_class($lead['client_status']); ?>">
                                                    <?php echo $lead['client_status'] ?: 'No Status'; ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600 font-medium">
                                            <?php echo sanitize_output($lead['industry']); ?>
                                        </td>
                                        <?php if ($current_user['role'] === 'admin'): ?>
                                        <td class="px-6 py-4 text-sm text-gray-600 font-medium">
                                            <?php echo sanitize_output($lead['assigned_user'] ?? 'Unassigned'); ?>
                                        </td>
                                        <?php endif; ?>
                                        <td class="px-6 py-4 text-sm">
                                            <div class="flex items-center space-x-3">
                                                <a href="lead_edit.php?id=<?php echo $lead['id']; ?>" 
                                                   class="text-blue-600 hover:text-blue-800 font-semibold flex items-center px-3 py-1 rounded-lg hover:bg-blue-50 transition-all">
                                                    <i class="fas fa-eye mr-1"></i>View
                                                </a>
                                                <?php if ($current_user['role'] === 'admin'): ?>
                                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this lead?');">
                                                        <input type="hidden" name="lead_id" value="<?php echo $lead['id']; ?>">
                                                        <button type="submit" name="delete_lead" class="text-red-600 hover:text-red-800 font-semibold flex items-center px-3 py-1 rounded-lg hover:bg-red-50 transition-all">
                                                            <i class="fas fa-trash mr-1"></i>Delete
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions Section -->
            <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="desktop-card rounded-2xl p-6 shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-lg font-bold text-gray-900">Quick Actions</h4>
                        <i class="fas fa-bolt text-yellow-500 text-xl"></i>
                    </div>
                    <div class="space-y-3">
                        <?php if (has_permission('add_leads')): ?>
                        <a href="lead_add.php" class="flex items-center p-3 rounded-xl hover:bg-gray-50 transition-colors">
                            <i class="fas fa-plus-circle text-blue-500 mr-3"></i>
                            <span class="font-medium">Add New Lead</span>
                        </a>
                        <?php endif; ?>
                        <a href="search_leads.php" class="flex items-center p-3 rounded-xl hover:bg-gray-50 transition-colors">
                            <i class="fas fa-search text-green-500 mr-3"></i>
                            <span class="font-medium">Search Leads</span>
                        </a>
                        <a href="import_leads.php" class="flex items-center p-3 rounded-xl hover:bg-gray-50 transition-colors">
                            <i class="fas fa-upload text-purple-500 mr-3"></i>
                            <span class="font-medium">Import from CSV</span>
                        </a>
                    </div>
                </div>

                <div class="desktop-card rounded-2xl p-6 shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-lg font-bold text-gray-900">Recent Activity</h4>
                        <i class="fas fa-clock text-blue-500 text-xl"></i>
                    </div>
                    <div class="space-y-3">
                        <?php 
                        // Get recent leads (last 3)
                        $recent_leads = array_slice($leads, 0, 3);
                        if (empty($recent_leads)): 
                        ?>
                            <p class="text-gray-500 text-sm">No recent activity</p>
                        <?php else: ?>
                            <?php foreach ($recent_leads as $recent_lead): ?>
                            <div class="flex items-center p-3 rounded-xl hover:bg-gray-50 transition-colors">
                                <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center mr-3">
                                    <span class="text-white text-xs font-bold">
                                        <?php echo strtoupper(substr($recent_lead['client_name'], 0, 1)); ?>
                                    </span>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-sm"><?php echo sanitize_output($recent_lead['client_name']); ?></p>
                                    <p class="text-xs text-gray-500">Added recently</p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="desktop-card rounded-2xl p-6 shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-lg font-bold text-gray-900">Performance</h4>
                        <i class="fas fa-chart-pie text-green-500 text-xl"></i>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-600">Conversion Rate</span>
                                <span class="text-sm font-bold text-green-600">
                                    <?php 
                                    $total_contacted = count(array_filter($leads, function($lead) { 
                                        return !empty($lead['client_status']); 
                                    }));
                                    $converted = count($interested) + count($meetings);
                                    echo $total_contacted > 0 ? round(($converted / $total_contacted) * 100, 1) : 0;
                                    ?>%
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-gradient-to-r from-green-500 to-green-600 h-2 rounded-full" 
                                     style="width: <?php echo $total_contacted > 0 ? ($converted / $total_contacted) * 100 : 0; ?>%"></div>
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-600">Response Rate</span>
                                <span class="text-sm font-bold text-blue-600">
                                    <?php 
                                    $total_leads = count($leads);
                                    echo $total_leads > 0 ? round(($total_contacted / $total_leads) * 100, 1) : 0;
                                    ?>%
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full" 
                                     style="width: <?php echo $total_leads > 0 ? ($total_contacted / $total_leads) * 100 : 0; ?>%"></div>
                            </div>
                        </div>
                        
                        <?php if (has_permission('view_analytics')): ?>
                        <a href="analytics.php" class="block text-center bg-gradient-to-r from-purple-600 to-purple-700 text-white px-4 py-2 rounded-xl font-semibold hover:from-purple-700 hover:to-purple-800 transition-all">
                            View Full Analytics
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Handle inline editing
        document.addEventListener('DOMContentLoaded', function() {
            // Status select changes
            document.querySelectorAll('.status-select').forEach(function(select) {
                select.addEventListener('change', function() {
                    updateField(this.dataset.leadId, this.dataset.field, this.value);
                });
            });

            // Date field changes
            document.querySelectorAll('.editable-field').forEach(function(field) {
                field.addEventListener('change', function() {
                    updateField(this.dataset.leadId, this.dataset.field, this.value);
                });
            });

            // Add slide-up animation to cards
            const cards = document.querySelectorAll('.mobile-card, .desktop-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.classList.add('slide-up');
                }, index * 100);
            });
        });

        function updateField(leadId, field, value) {
            // Show loading state
            const target = document.querySelector(`[data-lead-id="${leadId}"][data-field="${field}"]`);
            if (target) {
                target.style.opacity = '0.6';
                target.style.pointerEvents = 'none';
            }

            fetch('index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `update_lead=1&lead_id=${leadId}&field=${field}&value=${encodeURIComponent(value)}&ajax=1`
            })
            .then(response => response.json())
            .then(data => {
                if (target) {
                    target.style.opacity = '1';
                    target.style.pointerEvents = 'auto';
                }
                
                if (data.success) {
                    showNotification('Updated successfully', 'success');
                    // Add success animation
                    if (target) {
                        target.classList.add('pulse-animation');
                        setTimeout(() => target.classList.remove('pulse-animation'), 2000);
                    }
                } else {
                    showNotification('Update failed', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (target) {
                    target.style.opacity = '1';
                    target.style.pointerEvents = 'auto';
                }
                showNotification('Update failed', 'error');
            });
        }

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded-2xl z-50 backdrop-blur-lg shadow-xl slide-up ${type === 'success' ? 'bg-green-100/90 text-green-800 border border-green-200' : 'bg-red-100/90 text-red-800 border border-red-200'}`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} mr-3 text-lg"></i>
                    <span class="font-semibold">${message}</span>
                </div>
            `;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.transform = 'translateX(400px)';
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Mobile touch interactions
        if (window.innerWidth < 1024) {
            document.querySelectorAll('.mobile-card').forEach(card => {
                let touchStartY = 0;
                
                card.addEventListener('touchstart', function(e) {
                    touchStartY = e.touches[0].clientY;
                    this.style.transform = 'scale(0.98)';
                });
                
                card.addEventListener('touchend', function(e) {
                    this.style.transform = 'scale(1)';
                    
                    // Simple swipe detection
                    const touchEndY = e.changedTouches[0].clientY;
                    const swipeDistance = touchStartY - touchEndY;
                    
                    if (Math.abs(swipeDistance) > 50) {
                        // Optional: Handle swipe gestures
                        console.log('Swipe detected');
                    }
                });

                card.addEventListener('touchcancel', function() {
                    this.style.transform = 'scale(1)';
                });
            });

            // Mobile tab animation
            document.querySelectorAll('.mobile-tab-item').forEach(tab => {
                tab.addEventListener('touchstart', function() {
                    if (!this.classList.contains('active')) {
                        this.style.transform = 'scale(0.9)';
                    }
                });
                
                tab.addEventListener('touchend', function() {
                    this.style.transform = 'scale(1)';
                });
            });
        }

        // Desktop hover effects
        if (window.innerWidth >= 1024) {
            document.querySelectorAll('.desktop-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-4px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });
        }

        // Ripple effect for buttons
        document.querySelectorAll('.ripple').forEach(button => {
            button.addEventListener('click', function(e) {
                let ripple = document.createElement('span');
                let rect = this.getBoundingClientRect();
                let size = Math.max(rect.width, rect.height);
                let x = e.clientX - rect.left - size / 2;
                let y = e.clientY - rect.top - size / 2;
                
                ripple.style.cssText = `
                    position: absolute;
                    width: ${size}px;
                    height: ${size}px;
                    background: rgba(255, 255, 255, 0.3);
                    border-radius: 50%;
                    transform: translate(${x}px, ${y}px) scale(0);
                    animation: ripple-animation 0.6s ease-out;
                    pointer-events: none;
                `;
                
                const style = document.createElement('style');
                style.textContent = `
                    @keyframes ripple-animation {
                        to {
                            transform: translate(${x}px, ${y}px) scale(4);
                            opacity: 0;
                        }
                    }
                `;
                document.head.appendChild(style);
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                    style.remove();
                }, 600);
            });
        });

        // Mobile menu functions
        function toggleMoreMenu() {
            const overlay = document.getElementById('more-menu-overlay');
            const menu = document.getElementById('more-menu');
            
            if (menu.classList.contains('show')) {
                closeMoreMenu();
            } else {
                openMoreMenu();
            }
        }
        
        function openMoreMenu() {
            const overlay = document.getElementById('more-menu-overlay');
            const menu = document.getElementById('more-menu');
            
            overlay.style.display = 'block';
            setTimeout(() => {
                menu.classList.add('show');
            }, 10);
        }
        
        function closeMoreMenu() {
            const overlay = document.getElementById('more-menu-overlay');
            const menu = document.getElementById('more-menu');
            
            menu.classList.remove('show');
            setTimeout(() => {
                overlay.style.display = 'none';
            }, 300);
        }
        
        // Close menu when clicking on a menu item
        document.querySelectorAll('#more-menu a').forEach(link => {
            link.addEventListener('click', closeMoreMenu);
        });
        
        // Handle back button to close menu
        window.addEventListener('popstate', closeMoreMenu);
    </script>
</body>
</html>