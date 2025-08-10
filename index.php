<?php
/**
 * Dashboard / Lead Listing Page
 * Lead Management System
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

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

// Get sales staff for assignment dropdown (admin only)
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
    <title>Dashboard - Lead Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Mobile-first styles */
        .mobile-card {
            background: linear-gradient(145deg, #ffffff, #f8fafc);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }
        
        .status-badge {
            backdrop-filter: blur(8px);
        }
        
        .floating-action {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 50;
            box-shadow: 0 8px 32px rgba(59, 130, 246, 0.35);
        }
        
        /* Desktop enhancements */
        @media (min-width: 1024px) {
            .desktop-sidebar {
                background: linear-gradient(180deg, #1e293b, #334155);
            }
            
            .desktop-card {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.2);
            }
            
            .table-row:hover {
                transform: translateY(-1px);
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            }
        }
        
        /* Tab bar for mobile */
        .mobile-tab-bar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-blue-50 min-h-screen">
    
    <!-- Mobile Header -->
    <div class="lg:hidden">
        <!-- Top App Bar -->
        <div class="bg-white shadow-lg sticky top-0 z-40">
            <div class="px-4 py-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-chart-line text-white text-sm"></i>
                        </div>
                        <div>
                            <h1 class="text-lg font-bold text-gray-900">Leads</h1>
                            <p class="text-xs text-gray-500"><?php echo count($leads); ?> total</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-gray-600 text-xs"></i>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-medium text-gray-900"><?php echo explode(' ', $current_user['full_name'])[0]; ?></p>
                            <p class="text-xs text-blue-600"><?php echo ucfirst($current_user['role']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php if ($flash_message): ?>
            <div class="mx-4 mt-3 p-3 rounded-xl <?php echo $flash_message['type'] === 'success' ? 'bg-green-100 text-green-700 border border-green-200' : ($flash_message['type'] === 'error' ? 'bg-red-100 text-red-700 border border-red-200' : 'bg-blue-100 text-blue-700 border border-blue-200'); ?>">
                <div class="flex items-center">
                    <i class="fas fa-<?php echo $flash_message['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> mr-2"></i>
                    <span class="text-sm"><?php echo sanitize_output($flash_message['text']); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Stats Cards Mobile -->
        <div class="px-4 py-4">
            <div class="grid grid-cols-2 gap-3 mb-4">
                <?php
                $interested = array_filter($leads, function($lead) { return $lead['client_status'] === 'Interested'; });
                $meetings = array_filter($leads, function($lead) { return $lead['client_status'] === 'Meeting Scheduled'; });
                $not_interested = array_filter($leads, function($lead) { return $lead['client_status'] === 'Not Interested'; });
                ?>
                
                <div class="mobile-card rounded-2xl p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-2xl font-bold text-green-600"><?php echo count($interested); ?></p>
                            <p class="text-xs text-gray-600">Interested</p>
                        </div>
                        <i class="fas fa-heart text-green-400 text-xl"></i>
                    </div>
                </div>
                
                <div class="mobile-card rounded-2xl p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-2xl font-bold text-blue-600"><?php echo count($meetings); ?></p>
                            <p class="text-xs text-gray-600">Meetings</p>
                        </div>
                        <i class="fas fa-calendar text-blue-400 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Leads List -->
        <div class="px-4 pb-20">
            <?php if (empty($leads)): ?>
                <div class="mobile-card rounded-2xl p-8 text-center">
                    <i class="fas fa-inbox text-gray-300 text-4xl mb-4"></i>
                    <p class="text-gray-500 mb-4">No leads found</p>
                    <?php if (has_permission('add_leads')): ?>
                        <a href="lead_add.php" class="bg-blue-600 text-white px-6 py-2 rounded-full text-sm font-medium">
                            Add First Lead
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($leads as $lead): ?>
                        <div class="mobile-card rounded-2xl p-4" onclick="window.location='lead_edit.php?id=<?php echo $lead['id']; ?>'">
                            <div class="flex justify-between items-start mb-3">
                                <h3 class="text-base font-semibold text-gray-900 pr-2">
                                    <?php echo sanitize_output($lead['client_name']); ?>
                                </h3>
                                <span class="status-badge inline-flex px-3 py-1 text-xs font-medium rounded-full <?php echo get_status_class($lead['client_status']); ?>">
                                    <?php echo $lead['client_status'] ?: 'New'; ?>
                                </span>
                            </div>
                            
                            <p class="text-sm text-gray-600 mb-3"><?php echo sanitize_output($lead['required_services']); ?></p>
                            
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <div class="flex items-center space-x-4">
                                    <div class="flex items-center">
                                        <i class="fas fa-envelope mr-1"></i>
                                        <span><?php echo sanitize_output(substr($lead['email'], 0, 15)) . (strlen($lead['email']) > 15 ? '...' : ''); ?></span>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-phone mr-1"></i>
                                        <span><?php echo sanitize_output($lead['phone']); ?></span>
                                    </div>
                                </div>
                                <i class="fas fa-chevron-right text-gray-400"></i>
                            </div>
                            
                            <?php if ($lead['follow_up']): ?>
                                <div class="mt-2 pt-2 border-t border-gray-100">
                                    <div class="flex items-center text-xs text-orange-600">
                                        <i class="fas fa-clock mr-1"></i>
                                        <span>Follow up: <?php echo format_date($lead['follow_up']); ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Mobile Bottom Tab Bar -->
        <div class="fixed bottom-0 left-0 right-0 mobile-tab-bar">
            <div class="flex justify-around py-2">
                <a href="index.php" class="flex flex-col items-center py-2 px-4 text-blue-600">
                    <i class="fas fa-home text-lg mb-1"></i>
                    <span class="text-xs">Home</span>
                </a>
                <?php if (has_permission('add_leads')): ?>
                <a href="lead_add.php" class="flex flex-col items-center py-2 px-4 text-gray-500">
                    <i class="fas fa-plus-circle text-lg mb-1"></i>
                    <span class="text-xs">Add</span>
                </a>
                <?php endif; ?>
                <?php if (has_permission('view_analytics')): ?>
                <a href="analytics.php" class="flex flex-col items-center py-2 px-4 text-gray-500">
                    <i class="fas fa-chart-bar text-lg mb-1"></i>
                    <span class="text-xs">Analytics</span>
                </a>
                <?php endif; ?>
                <a href="logout.php" class="flex flex-col items-center py-2 px-4 text-gray-500">
                    <i class="fas fa-sign-out-alt text-lg mb-1"></i>
                    <span class="text-xs">Logout</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Desktop Layout -->
    <div class="hidden lg:block">
        <!-- Desktop Header -->
        <nav class="bg-white/80 backdrop-blur-lg shadow-lg border-b border-white/20">
            <div class="max-w-7xl mx-auto px-6">
                <div class="flex justify-between h-16">
                    <div class="flex items-center space-x-4">
                        <div class="w-10 h-10 bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-chart-line text-white"></i>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">Lead Management System</h1>
                            <p class="text-sm text-gray-500">Professional CRM Dashboard</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-6">
                        <div class="flex items-center space-x-2 bg-gray-50 rounded-full px-4 py-2">
                            <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-semibold"><?php echo strtoupper(substr($current_user['full_name'], 0, 1)); ?></span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900"><?php echo sanitize_output($current_user['full_name']); ?></p>
                                <p class="text-xs text-blue-600"><?php echo ucfirst($current_user['role']); ?></p>
                            </div>
                        </div>
                        
                        <?php if (has_permission('view_analytics')): ?>
                            <a href="analytics.php" class="flex items-center space-x-2 text-gray-700 hover:text-blue-600 transition-colors">
                                <i class="fas fa-analytics"></i>
                                <span>Analytics</span>
                            </a>
                        <?php endif; ?>
                        
                        <a href="logout.php" class="bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition-all shadow-lg hover:shadow-xl">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto py-8 px-6">
            <!-- Flash Messages -->
            <?php if ($flash_message): ?>
                <div class="mb-6 p-4 rounded-2xl backdrop-blur-lg <?php echo $flash_message['type'] === 'success' ? 'bg-green-100/80 text-green-700 border border-green-200' : ($flash_message['type'] === 'error' ? 'bg-red-100/80 text-red-700 border border-red-200' : 'bg-blue-100/80 text-blue-700 border border-blue-200'); ?>">
                    <div class="flex items-center">
                        <i class="fas fa-<?php echo $flash_message['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> mr-3"></i>
                        <?php echo sanitize_output($flash_message['text']); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="mb-8 flex flex-col xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Leads Dashboard</h2>
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
                        <a href="lead_add.php" class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-6 py-3 rounded-xl font-medium transition-all shadow-lg hover:shadow-xl flex items-center">
                            <i class="fas fa-plus mr-2"></i>Add New Lead
                        </a>
                    <?php endif; ?>
                    <?php if (has_permission('export_leads')): ?>
                        <a href="export.php" class="bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-6 py-3 rounded-xl font-medium transition-all shadow-lg hover:shadow-xl flex items-center">
                            <i class="fas fa-download mr-2"></i>Export CSV
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats Cards Desktop -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
                <div class="desktop-card rounded-2xl p-6 shadow-lg">
                    <div class="flex items-center">
                        <div class="p-3 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl">
                            <i class="fas fa-users text-white text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Leads</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo count($leads); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="desktop-card rounded-2xl p-6 shadow-lg">
                    <div class="flex items-center">
                        <div class="p-3 bg-gradient-to-r from-green-500 to-green-600 rounded-xl">
                            <i class="fas fa-heart text-white text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Interested</p>
                            <p class="text-2xl font-bold text-green-600"><?php echo count($interested); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="desktop-card rounded-2xl p-6 shadow-lg">
                    <div class="flex items-center">
                        <div class="p-3 bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl">
                            <i class="fas fa-calendar text-white text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Meetings Scheduled</p>
                            <p class="text-2xl font-bold text-blue-600"><?php echo count($meetings); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="desktop-card rounded-2xl p-6 shadow-lg">
                    <div class="flex items-center">
                        <div class="p-3 bg-gradient-to-r from-red-500 to-red-600 rounded-xl">
                            <i class="fas fa-times-circle text-white text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Not Interested</p>
                            <p class="text-2xl font-bold text-red-600"><?php echo count($not_interested); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Desktop Table -->
            <div class="desktop-card rounded-2xl shadow-xl overflow-hidden">
                <div class="px-6 py-6 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">All Leads</h3>
                            <p class="text-gray-500 mt-1"><?php echo count($leads); ?> total leads in the system</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <button class="p-2 text-gray-400 hover:text-gray-600">
                                <i class="fas fa-filter"></i>
                            </button>
                            <button class="p-2 text-gray-400 hover:text-gray-600">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <?php if (empty($leads)): ?>
                    <div class="text-center py-16">
                        <i class="fas fa-inbox text-gray-300 text-6xl mb-4"></i>
                        <p class="text-gray-500 text-lg mb-6">No leads found in the system</p>
                        <?php if (has_permission('add_leads')): ?>
                            <a href="lead_add.php" class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-8 py-3 rounded-xl font-medium">
                                Add Your First Lead
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50/50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Client</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Contact</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Follow Up</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Industry</th>
                                    <?php if ($current_user['role'] === 'admin'): ?>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Assigned To</th>
                                    <?php endif; ?>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($leads as $lead): ?>
                                    <tr class="table-row hover:bg-blue-50/50 transition-all duration-200">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center">
                                                    <span class="text-white font-semibold text-sm"><?php echo strtoupper(substr($lead['client_name'], 0, 1)); ?></span>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-semibold text-gray-900">
                                                        <?php echo sanitize_output($lead['client_name']); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        <?php echo sanitize_output($lead['required_services']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900 flex items-center">
                                                <i class="fas fa-envelope text-gray-400 mr-2"></i>
                                                <?php echo sanitize_output($lead['email']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500 flex items-center mt-1">
                                                <i class="fas fa-phone text-gray-400 mr-2"></i>
                                                <?php echo sanitize_output($lead['phone']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if (has_permission('edit_leads')): ?>
                                                <select class="status-select text-sm rounded-full px-3 py-1 border-0 <?php echo get_status_class($lead['client_status']); ?>" 
                                                        data-lead-id="<?php echo $lead['id']; ?>" data-field="client_status">
                                                    <?php foreach (get_status_options() as $value => $label): ?>
                                                        <option value="<?php echo $value; ?>" <?php echo $lead['client_status'] === $value ? 'selected' : ''; ?>>
                                                            <?php echo $label; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php else: ?>
                                                <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full <?php echo get_status_class($lead['client_status']); ?>">
                                                    <?php echo $lead['client_status'] ?: 'No Status'; ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?php if (has_permission('edit_leads')): ?>
                                                <input type="date" class="editable-field border-gray-200 rounded-lg text-sm px-3 py-1" 
                                                       value="<?php echo $lead['follow_up']; ?>" 
                                                       data-lead-id="<?php echo $lead['id']; ?>" 
                                                       data-field="follow_up">
                                            <?php else: ?>
                                                <?php if ($lead['follow_up']): ?>
                                                    <div class="flex items-center text-orange-600">
                                                        <i class="fas fa-clock mr-2"></i>
                                                        <?php echo format_date($lead['follow_up']); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-gray-400">Not set</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            <?php echo sanitize_output($lead['industry']); ?>
                                        </td>
                                        <?php if ($current_user['role'] === 'admin'): ?>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            <?php echo sanitize_output($lead['assigned_user'] ?? 'Unassigned'); ?>
                                        </td>
                                        <?php endif; ?>
                                        <td class="px-6 py-4 text-sm">
                                            <div class="flex items-center space-x-3">
                                                <a href="lead_edit.php?id=<?php echo $lead['id']; ?>" 
                                                   class="text-blue-600 hover:text-blue-800 font-medium flex items-center">
                                                    <i class="fas fa-eye mr-1"></i>View
                                                </a>
                                                <?php if ($current_user['role'] === 'admin'): ?>
                                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this lead?');">
                                                        <input type="hidden" name="lead_id" value="<?php echo $lead['id']; ?>">
                                                        <button type="submit" name="delete_lead" class="text-red-600 hover:text-red-800 font-medium flex items-center">
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
        });

        function updateField(leadId, field, value) {
            fetch('index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `update_lead=1&lead_id=${leadId}&field=${field}&value=${encodeURIComponent(value)}&ajax=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success feedback
                    showNotification('Updated successfully', 'success');
                } else {
                    showNotification('Update failed', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Update failed', 'error');
            });
        }

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded-xl z-50 backdrop-blur-lg shadow-lg ${type === 'success' ? 'bg-green-100/90 text-green-700 border border-green-200' : 'bg-red-100/90 text-red-700 border border-red-200'}`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // Mobile touch interactions
        if (window.innerWidth < 1024) {
            document.querySelectorAll('.mobile-card').forEach(card => {
                card.addEventListener('touchstart', function() {
                    this.style.transform = 'scale(0.98)';
                });
                
                card.addEventListener('touchend', function() {
                    this.style.transform = 'scale(1)';
                });
            });
        }
    </script>
</body>
</html>