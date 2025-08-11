<?php
/**
 * Search and Filter Leads Page
 * Lead Management System
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Require login
require_login();

$current_user = get_logged_in_user();
$search_results = [];
$total_results = 0;

// Handle search form submission
if ($_GET) {
    $filters = [
        'search' => sanitize_input($_GET['search'] ?? ''),
        'status' => sanitize_input($_GET['status'] ?? ''),
        'industry' => sanitize_input($_GET['industry'] ?? ''),
        'assigned_to' => (int)($_GET['assigned_to'] ?? 0),
        'date_from' => sanitize_input($_GET['date_from'] ?? ''),
        'date_to' => sanitize_input($_GET['date_to'] ?? ''),
        'lead_source' => sanitize_input($_GET['lead_source'] ?? ''),
        'has_follow_up' => isset($_GET['has_follow_up']) ? 1 : 0
    ];
    
    // Remove empty filters
    $filters = array_filter($filters, function($value) {
        return $value !== '' && $value !== 0;
    });
    
    if (!empty($filters)) {
        $search_results = search_leads($filters, $current_user['id'], $current_user['role']);
        $total_results = count($search_results);
    }
}

// Get data for dropdowns
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
    <title>Search Leads - Lead Management System</title>
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
                    <a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-sm">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 px-4">
        <!-- Page Header -->
        <div class="mb-6">
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="index.php" class="text-gray-700 hover:text-gray-900">Dashboard</a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-500 ml-1 md:ml-2">Search Leads</span>
                        </div>
                    </li>
                </ol>
            </nav>
            <h1 class="text-3xl font-bold text-gray-900 mt-2">Search & Filter Leads</h1>
            <p class="text-gray-600">Find specific leads using advanced search criteria</p>
        </div>

        <!-- Search Form -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Search Criteria</h3>
            </div>
            <div class="p-6">
                <form method="GET" action="" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Text Search -->
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700">Search Keywords</label>
                            <input 
                                type="text" 
                                id="search" 
                                name="search" 
                                value="<?php echo sanitize_output($_GET['search'] ?? ''); ?>"
                                placeholder="Client name, email, phone, services..."
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select 
                                id="status" 
                                name="status" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">All Statuses</option>
                                <?php foreach (get_status_options() as $value => $label): ?>
                                    <?php if ($value !== ''): ?>
                                        <option value="<?php echo $value; ?>" <?php echo ($_GET['status'] ?? '') === $value ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Industry Filter -->
                        <div>
                            <label for="industry" class="block text-sm font-medium text-gray-700">Industry</label>
                            <select 
                                id="industry" 
                                name="industry" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">All Industries</option>
                                <?php foreach (get_industry_options() as $industry): ?>
                                    <option value="<?php echo $industry; ?>" <?php echo ($_GET['industry'] ?? '') === $industry ? 'selected' : ''; ?>>
                                        <?php echo $industry; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Lead Source Filter -->
                        <div>
                            <label for="lead_source" class="block text-sm font-medium text-gray-700">Lead Source</label>
                            <select 
                                id="lead_source" 
                                name="lead_source" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">All Sources</option>
                                <?php foreach (get_lead_sources() as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo ($_GET['lead_source'] ?? '') === $value ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Date From -->
                        <div>
                            <label for="date_from" class="block text-sm font-medium text-gray-700">Date From</label>
                            <input 
                                type="date" 
                                id="date_from" 
                                name="date_from" 
                                value="<?php echo sanitize_output($_GET['date_from'] ?? ''); ?>"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>

                        <!-- Date To -->
                        <div>
                            <label for="date_to" class="block text-sm font-medium text-gray-700">Date To</label>
                            <input 
                                type="date" 
                                id="date_to" 
                                name="date_to" 
                                value="<?php echo sanitize_output($_GET['date_to'] ?? ''); ?>"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>

                        <?php if ($current_user['role'] === 'admin'): ?>
                        <!-- Assigned To Filter (Admin Only) -->
                        <div>
                            <label for="assigned_to" class="block text-sm font-medium text-gray-700">Assigned To</label>
                            <select 
                                id="assigned_to" 
                                name="assigned_to" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">All Sales Staff</option>
                                <option value="-1" <?php echo ($_GET['assigned_to'] ?? '') === '-1' ? 'selected' : ''; ?>>Unassigned</option>
                                <?php foreach ($sales_staff as $staff): ?>
                                    <option value="<?php echo $staff['id']; ?>" <?php echo ($_GET['assigned_to'] ?? '') == $staff['id'] ? 'selected' : ''; ?>>
                                        <?php echo sanitize_output($staff['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <!-- Has Follow-up -->
                        <div class="flex items-center">
                            <input 
                                type="checkbox" 
                                id="has_follow_up" 
                                name="has_follow_up" 
                                value="1"
                                <?php echo isset($_GET['has_follow_up']) ? 'checked' : ''; ?>
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            >
                            <label for="has_follow_up" class="ml-2 block text-sm text-gray-900">
                                Has Follow-up Date
                            </label>
                        </div>
                    </div>

                    <!-- Search Actions -->
                    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                        <button 
                            type="button"
                            onclick="clearSearch()"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded"
                        >
                            Clear All
                        </button>
                        <button 
                            type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline"
                        >
                            Search Leads
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Search Results -->
        <?php if ($_GET): ?>
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Search Results</h3>
                            <p class="text-sm text-gray-500">Found <?php echo $total_results; ?> leads matching your criteria</p>
                        </div>
                        <?php if ($total_results > 0 && has_permission('export_leads')): ?>
                            <a href="export.php?<?php echo http_build_query($_GET); ?>" 
                               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm">
                                Export Results
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($total_results === 0): ?>
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.34 0-4.29-1.445-5.103-3.5m0 0A7.962 7.962 0 014 9c0-4.418 3.582-8 8-8s8 3.582 8 8-3.582 8-8 8a7.96 7.96 0 01-2-.253z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No leads found</h3>
                        <p class="mt-1 text-sm text-gray-500">Try adjusting your search criteria.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Industry</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Follow Up</th>
                                    <?php if ($current_user['role'] === 'admin'): ?>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To</th>
                                    <?php endif; ?>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($search_results as $lead): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center">
                                                        <span class="text-white font-medium text-sm">
                                                            <?php echo strtoupper(substr($lead['client_name'], 0, 1)); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo sanitize_output($lead['client_name']); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        <?php echo sanitize_output(truncate_text($lead['required_services'], 50)); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo sanitize_output($lead['email']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo sanitize_output($lead['phone']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo get_status_class($lead['client_status']); ?>">
                                                <?php echo $lead['client_status'] ?: 'No Status'; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo sanitize_output($lead['industry']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">
                                                <?php echo sanitize_output($lead['lead_source']); ?>
                                            </span>
                                            <?php if ($lead['source_ad_name']): ?>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    <?php echo sanitize_output($lead['source_ad_name']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php if ($lead['follow_up']): ?>
                                                <div class="flex items-center text-orange-600">
                                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    <?php echo format_date($lead['follow_up']); ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-gray-400">Not set</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php if ($current_user['role'] === 'admin'): ?>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo sanitize_output($lead['assigned_user'] ?? 'Unassigned'); ?>
                                        </td>
                                        <?php endif; ?>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="lead_edit.php?id=<?php echo $lead['id']; ?>" 
                                               class="text-blue-600 hover:text-blue-900">
                                                View/Edit
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function clearSearch() {
            // Clear all form fields
            document.getElementById('search').value = '';
            document.getElementById('status').value = '';
            document.getElementById('industry').value = '';
            document.getElementById('lead_source').value = '';
            document.getElementById('date_from').value = '';
            document.getElementById('date_to').value = '';
            document.getElementById('has_follow_up').checked = false;
            
            <?php if ($current_user['role'] === 'admin'): ?>
            document.getElementById('assigned_to').value = '';
            <?php endif; ?>
            
            // Optionally redirect to clear URL parameters
            window.location.href = 'search_leads.php';
        }

        // Auto-submit form when quick filters change
        document.addEventListener('DOMContentLoaded', function() {
            const quickFilters = ['status', 'industry', 'lead_source'];
            
            quickFilters.forEach(filterId => {
                const element = document.getElementById(filterId);
                if (element) {
                    element.addEventListener('change', function() {
                        // Optional: Auto-submit on filter change
                        // this.form.submit();
                    });
                }
            });
        });
    </script>
</body>
</html>