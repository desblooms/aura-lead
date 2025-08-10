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
$current_user = get_current_user();
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
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-gray-900">Lead Management System</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Welcome, <?php echo sanitize_output($current_user['full_name']); ?></span>
                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">
                        <?php echo ucfirst($current_user['role']); ?>
                    </span>
                    <?php if (has_permission('view_analytics')): ?>
                        <a href="analytics.php" class="text-blue-600 hover:text-blue-800">Analytics</a>
                    <?php endif; ?>
                    <a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-sm">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 px-4">
        <!-- Flash Messages -->
        <?php if ($flash_message): ?>
            <div class="mb-6 p-4 rounded-md <?php echo $flash_message['type'] === 'success' ? 'bg-green-100 text-green-700' : ($flash_message['type'] === 'error' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'); ?>">
                <?php echo sanitize_output($flash_message['text']); ?>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Leads Dashboard</h2>
                <p class="text-gray-600">
                    <?php 
                    if ($current_user['role'] === 'sales') {
                        echo "Manage your assigned leads";
                    } else {
                        echo "View and manage all leads";
                    }
                    ?>
                </p>
            </div>
            <div class="mt-4 sm:mt-0 flex space-x-3">
                <?php if (has_permission('add_leads')): ?>
                    <a href="lead_add.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        Add New Lead
                    </a>
                <?php endif; ?>
                <?php if (has_permission('export_leads')): ?>
                    <a href="export.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        Export CSV
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600">Total Leads</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo count($leads); ?></p>
                    </div>
                </div>
            </div>
            
            <?php
            $interested = array_filter($leads, function($lead) { return $lead['client_status'] === 'Interested'; });
            $meetings = array_filter($leads, function($lead) { return $lead['client_status'] === 'Meeting Scheduled'; });
            $not_interested = array_filter($leads, function($lead) { return $lead['client_status'] === 'Not Interested'; });
            ?>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600">Interested</p>
                        <p class="text-2xl font-bold text-green-600"><?php echo count($interested); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600">Meetings Scheduled</p>
                        <p class="text-2xl font-bold text-blue-600"><?php echo count($meetings); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600">Not Interested</p>
                        <p class="text-2xl font-bold text-red-600"><?php echo count($not_interested); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leads Table -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Recent Leads
                </h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                    <?php echo count($leads); ?> total leads
                </p>
            </div>
            
            <?php if (empty($leads)): ?>
                <div class="text-center py-12">
                    <p class="text-gray-500">No leads found.</p>
                    <?php if (has_permission('add_leads')): ?>
                        <a href="lead_add.php" class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm">
                            Add Your First Lead
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Desktop Table -->
                <div class="hidden lg:block">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Follow Up</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Industry</th>
                                <?php if ($current_user['role'] === 'admin'): ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To</th>
                                <?php endif; ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($leads as $lead): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo sanitize_output($lead['client_name']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo sanitize_output($lead['required_services']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo sanitize_output($lead['email']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo sanitize_output($lead['phone']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if (has_permission('edit_leads')): ?>
                                            <select class="status-select text-sm rounded-full px-2 py-1 <?php echo get_status_class($lead['client_status']); ?>" 
                                                    data-lead-id="<?php echo $lead['id']; ?>" data-field="client_status">
                                                <?php foreach (get_status_options() as $value => $label): ?>
                                                    <option value="<?php echo $value; ?>" <?php echo $lead['client_status'] === $value ? 'selected' : ''; ?>>
                                                        <?php echo $label; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php else: ?>
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo get_status_class($lead['client_status']); ?>">
                                                <?php echo $lead['client_status'] ?: 'No Status'; ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php if (has_permission('edit_leads')): ?>
                                            <input type="date" class="editable-field border-gray-300 rounded text-sm" 
                                                   value="<?php echo $lead['follow_up']; ?>" 
                                                   data-lead-id="<?php echo $lead['id']; ?>" 
                                                   data-field="follow_up">
                                        <?php else: ?>
                                            <?php echo $lead['follow_up'] ? format_date($lead['follow_up']) : 'Not set'; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo sanitize_output($lead['industry']); ?>
                                    </td>
                                    <?php if ($current_user['role'] === 'admin'): ?>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo sanitize_output($lead['assigned_user'] ?? 'Unassigned'); ?>
                                    </td>
                                    <?php endif; ?>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="lead_edit.php?id=<?php echo $lead['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                                        <?php if ($current_user['role'] === 'admin'): ?>
                                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this lead?');">
                                                <input type="hidden" name="lead_id" value="<?php echo $lead['id']; ?>">
                                                <button type="submit" name="delete_lead" class="text-red-600 hover:text-red-900">Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="lg:hidden">
                    <div class="space-y-4 p-4">
                        <?php foreach ($leads as $lead): ?>
                            <div class="bg-white border rounded-lg p-4 shadow">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="text-lg font-medium text-gray-900">
                                        <?php echo sanitize_output($lead['client_name']); ?>
                                    </h3>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo get_status_class($lead['client_status']); ?>">
                                        <?php echo $lead['client_status'] ?: 'No Status'; ?>
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 mb-2"><?php echo sanitize_output($lead['required_services']); ?></p>
                                <div class="space-y-1 text-sm">
                                    <p><span class="font-medium">Email:</span> <?php echo sanitize_output($lead['email']); ?></p>
                                    <p><span class="font-medium">Phone:</span> <?php echo sanitize_output($lead['phone']); ?></p>
                                    <p><span class="font-medium">Industry:</span> <?php echo sanitize_output($lead['industry']); ?></p>
                                    <p><span class="font-medium">Follow Up:</span> <?php echo $lead['follow_up'] ? format_date($lead['follow_up']) : 'Not set'; ?></p>
                                </div>
                                <div class="mt-3 flex justify-end">
                                    <a href="lead_edit.php?id=<?php echo $lead['id']; ?>" 
                                       class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
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
            notification.className = `fixed top-4 right-4 p-4 rounded-md z-50 ${type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`;
            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
    </script>
</body>
</html>