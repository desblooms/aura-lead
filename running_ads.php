<?php
/**
 * Running Ads Management Page (Marketing Role)
 * Lead Management System
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Require marketing or admin access
require_login();
require_role(['marketing', 'admin']);

$current_user = get_logged_in_user();
$errors = [];
$success_message = '';

// Handle ad creation
if ($_POST && isset($_POST['add_ad'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $ad_name = sanitize_input($_POST['ad_name']);
        $service_id = (int)$_POST['service_id'];
        $platform = sanitize_input($_POST['platform']);
        $budget = (float)$_POST['budget'];
        $start_date = sanitize_input($_POST['start_date']);
        $end_date = sanitize_input($_POST['end_date']);
        $target_audience = sanitize_input($_POST['target_audience']);
        $ad_copy = sanitize_input($_POST['ad_copy']);
        $assigned_sales_member = (int)$_POST['assigned_sales_member'];

        // Validation
        if (empty($ad_name) || empty($service_id) || empty($platform) || empty($start_date) || empty($assigned_sales_member)) {
            $errors[] = 'All required fields must be filled.';
        }

        if ($budget < 0) {
            $errors[] = 'Budget must be a positive number.';
        }

        if (!empty($end_date) && strtotime($end_date) < strtotime($start_date)) {
            $errors[] = 'End date must be after start date.';
        }

        // Create ad if no errors
        if (empty($errors)) {
            $query = "INSERT INTO running_ads (ad_name, service_id, platform, budget, start_date, end_date, target_audience, ad_copy, assigned_sales_member, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $ad_name,
                $service_id,
                $platform,
                $budget,
                $start_date,
                $end_date ?: null,
                $target_audience,
                $ad_copy,
                $assigned_sales_member,
                $current_user['id']
            ];
            
            if (execute_query($query, $params, 'issdssssii')) {
                $success_message = 'Running ad created successfully!';
                // Clear form
                $_POST = [];
            } else {
                $errors[] = 'Failed to create ad. Please try again.';
            }
        }
    }
}

// Handle ad status toggle
if ($_POST && isset($_POST['toggle_ad_status'])) {
    $ad_id = (int)$_POST['ad_id'];
    $current_status = (int)$_POST['current_status'];
    $new_status = $current_status ? 0 : 1;
    
    $query = "UPDATE running_ads SET is_active = ? WHERE id = ?";
    execute_query($query, [$new_status, $ad_id], 'ii');
    $success_message = 'Ad status updated successfully!';
}

// Get all running ads with related data
$ads = get_all("
    SELECT ra.*, s.service_name, s.service_category, u.full_name as assigned_sales_name, c.full_name as created_by_name,
           COUNT(l.id) as lead_count
    FROM running_ads ra 
    LEFT JOIN services s ON ra.service_id = s.id 
    LEFT JOIN users u ON ra.assigned_sales_member = u.id 
    LEFT JOIN users c ON ra.created_by = c.id
    LEFT JOIN leads l ON ra.id = l.source_ad_id
    GROUP BY ra.id
    ORDER BY ra.created_at DESC
");

// Get active services for dropdown
$services = get_all("SELECT id, service_name, service_category FROM services WHERE is_active = 1 ORDER BY service_name");

// Get sales staff for assignment
$sales_staff = get_all("SELECT id, full_name FROM users WHERE role = 'sales' AND is_active = 1 ORDER BY full_name");

$csrf_token = generate_csrf_token();

// Platform options
$platforms = [
    'Facebook',
    'Google Ads',
    'Instagram', 
    'LinkedIn',
    'Twitter',
    'YouTube',
    'TikTok',
    'Other'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Running Ads Management - Lead Management System</title>
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
                    <?php if ($current_user['role'] === 'admin'): ?>
                        <a href="users.php" class="text-blue-600 hover:text-blue-800">Users</a>
                        <a href="services.php" class="text-blue-600 hover:text-blue-800">Services</a>
                    <?php endif; ?>
                    <a href="analytics.php" class="text-blue-600 hover:text-blue-800">Analytics</a>
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
                            <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-500 ml-1 md:ml-2">Running Ads</span>
                        </div>
                    </li>
                </ol>
            </nav>
            <h1 class="text-3xl font-bold text-gray-900 mt-2">Running Ads Management</h1>
            <p class="text-gray-600">Create and manage marketing campaigns with automatic lead assignment</p>
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

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <?php
            $total_ads = count($ads);
            $active_ads = array_filter($ads, function($ad) { return $ad['is_active']; });
            $total_budget = array_sum(array_column($ads, 'budget'));
            $total_leads_from_ads = array_sum(array_column($ads, 'lead_count'));
            ?>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600">Total Ads</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $total_ads; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600">Active Ads</p>
                        <p class="text-2xl font-bold text-green-600"><?php echo count($active_ads); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600">Total Budget</p>
                        <p class="text-2xl font-bold text-blue-600">$<?php echo number_format($total_budget, 2); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600">Generated Leads</p>
                        <p class="text-2xl font-bold text-purple-600"><?php echo $total_leads_from_ads; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <!-- Add Ad Form -->
            <div class="xl:col-span-1">
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Create New Ad Campaign</h3>
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="space-y-4">
                            <div>
                                <label for="ad_name" class="block text-sm font-medium text-gray-700">Ad Name *</label>
                                <input 
                                    type="text" 
                                    id="ad_name" 
                                    name="ad_name" 
                                    value="<?php echo isset($_POST['ad_name']) ? sanitize_output($_POST['ad_name']) : ''; ?>"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    required
                                >
                            </div>

                            <div>
                                <label for="service_id" class="block text-sm font-medium text-gray-700">Service/Product *</label>
                                <select 
                                    id="service_id" 
                                    name="service_id" 
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    required
                                >
                                    <option value="">Select Service</option>
                                    <?php foreach ($services as $service): ?>
                                        <option value="<?php echo $service['id']; ?>" <?php echo (isset($_POST['service_id']) && $_POST['service_id'] == $service['id']) ? 'selected' : ''; ?>>
                                            <?php echo sanitize_output($service['service_name']) . ' (' . sanitize_output($service['service_category']) . ')'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="platform" class="block text-sm font-medium text-gray-700">Platform *</label>
                                <select 
                                    id="platform" 
                                    name="platform" 
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    required
                                >
                                    <option value="">Select Platform</option>
                                    <?php foreach ($platforms as $platform): ?>
                                        <option value="<?php echo $platform; ?>" <?php echo (isset($_POST['platform']) && $_POST['platform'] === $platform) ? 'selected' : ''; ?>>
                                            <?php echo $platform; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="budget" class="block text-sm font-medium text-gray-700">Budget ($)</label>
                                <input 
                                    type="number" 
                                    id="budget" 
                                    name="budget" 
                                    step="0.01"
                                    min="0"
                                    value="<?php echo isset($_POST['budget']) ? sanitize_output($_POST['budget']) : ''; ?>"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                >
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date *</label>
                                    <input 
                                        type="date" 
                                        id="start_date" 
                                        name="start_date" 
                                        value="<?php echo isset($_POST['start_date']) ? sanitize_output($_POST['start_date']) : date('Y-m-d'); ?>"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        required
                                    >
                                </div>

                                <div>
                                    <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                                    <input 
                                        type="date" 
                                        id="end_date" 
                                        name="end_date" 
                                        value="<?php echo isset($_POST['end_date']) ? sanitize_output($_POST['end_date']) : ''; ?>"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    >
                                </div>
                            </div>

                            <div>
                                <label for="assigned_sales_member" class="block text-sm font-medium text-gray-700">Assign Leads To *</label>
                                <select 
                                    id="assigned_sales_member" 
                                    name="assigned_sales_member" 
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    required
                                >
                                    <option value="">Select Sales Member</option>
                                    <?php foreach ($sales_staff as $staff): ?>
                                        <option value="<?php echo $staff['id']; ?>" <?php echo (isset($_POST['assigned_sales_member']) && $_POST['assigned_sales_member'] == $staff['id']) ? 'selected' : ''; ?>>
                                            <?php echo sanitize_output($staff['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Leads from this ad will be automatically assigned to this sales member</p>
                            </div>

                            <div>
                                <label for="target_audience" class="block text-sm font-medium text-gray-700">Target Audience</label>
                                <textarea 
                                    id="target_audience" 
                                    name="target_audience" 
                                    rows="2"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="e.g., Small business owners, 25-45 years old..."
                                ><?php echo isset($_POST['target_audience']) ? sanitize_output($_POST['target_audience']) : ''; ?></textarea>
                            </div>

                            <div>
                                <label for="ad_copy" class="block text-sm font-medium text-gray-700">Ad Copy</label>
                                <textarea 
                                    id="ad_copy" 
                                    name="ad_copy" 
                                    rows="3"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Your ad text/description..."
                                ><?php echo isset($_POST['ad_copy']) ? sanitize_output($_POST['ad_copy']) : ''; ?></textarea>
                            </div>

                            <button 
                                type="submit" 
                                name="add_ad" 
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                            >
                                Create Ad Campaign
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Running Ads List -->
            <div class="xl:col-span-2">
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Active Ad Campaigns</h3>
                        <p class="text-sm text-gray-500"><?php echo $total_ads; ?> total campaigns</p>
                    </div>
                    
                    <?php if (empty($ads)): ?>
                        <div class="text-center py-12">
                            <p class="text-gray-500">No ad campaigns found.</p>
                            <p class="text-sm text-gray-400 mt-2">Create your first campaign to start generating leads automatically.</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campaign</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Platform</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Budget</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Leads</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($ads as $ad): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo sanitize_output($ad['ad_name']); ?>
                                                    </div>
                                                    <?php if ($ad['ad_copy']): ?>
                                                        <div class="text-sm text-gray-500 truncate max-w-xs">
                                                            <?php echo sanitize_output(substr($ad['ad_copy'], 0, 50)) . (strlen($ad['ad_copy']) > 50 ? '...' : ''); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo sanitize_output($ad['service_name']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo sanitize_output($ad['service_category']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                    <?php 
                                                    switch(strtolower($ad['platform'])) {
                                                        case 'facebook': echo 'bg-blue-100 text-blue-800'; break;
                                                        case 'google ads': echo 'bg-green-100 text-green-800'; break;
                                                        case 'instagram': echo 'bg-pink-100 text-pink-800'; break;
                                                        case 'linkedin': echo 'bg-indigo-100 text-indigo-800'; break;
                                                        default: echo 'bg-gray-100 text-gray-800'; break;
                                                    }
                                                    ?>">
                                                    <?php echo sanitize_output($ad['platform']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                $<?php echo number_format($ad['budget'], 2); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <div><?php echo format_date($ad['start_date']); ?></div>
                                                <?php if ($ad['end_date']): ?>
                                                    <div class="text-xs">to <?php echo format_date($ad['end_date']); ?></div>
                                                <?php else: ?>
                                                    <div class="text-xs text-green-600">Ongoing</div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo sanitize_output($ad['assigned_sales_name']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                    <?php echo $ad['lead_count'] > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                                    <?php echo $ad['lead_count']; ?> leads
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                    <?php echo $ad['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                    <?php echo $ad['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="ad_id" value="<?php echo $ad['id']; ?>">
                                                    <input type="hidden" name="current_status" value="<?php echo $ad['is_active']; ?>">
                                                    <button type="submit" name="toggle_ad_status" 
                                                            class="<?php echo $ad['is_active'] ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'; ?>">
                                                        <?php echo $ad['is_active'] ? 'Pause' : 'Resume'; ?>
                                                    </button>
                                                </form>
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

        <!-- Ad Details Modal (could be enhanced with JavaScript) -->
        <div class="mt-8">
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Campaign Performance Summary</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600"><?php echo count($active_ads); ?></div>
                        <div class="text-sm text-gray-500">Active Campaigns</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">$<?php echo number_format(array_sum(array_column($active_ads, 'budget')), 2); ?></div>
                        <div class="text-sm text-gray-500">Active Budget</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600"><?php echo $total_leads_from_ads; ?></div>
                        <div class="text-sm text-gray-500">Total Leads Generated</div>
                    </div>
                </div>
                
                <?php if ($total_budget > 0): ?>
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <div class="text-sm text-gray-600">
                            <strong>Cost per Lead:</strong> 
                            $<?php echo $total_leads_from_ads > 0 ? number_format($total_budget / $total_leads_from_ads, 2) : '0.00'; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const startDate = document.getElementById('start_date');
            const endDate = document.getElementById('end_date');
            
            form.addEventListener('submit', function(e) {
                const adName = document.getElementById('ad_name').value.trim();
                const serviceId = document.getElementById('service_id').value;
                const platform = document.getElementById('platform').value;
                const assignedSales = document.getElementById('assigned_sales_member').value;

                if (!adName || !serviceId || !platform || !assignedSales) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                    return false;
                }

                // Validate date range
                if (startDate.value && endDate.value) {
                    if (new Date(endDate.value) < new Date(startDate.value)) {
                        e.preventDefault();
                        alert('End date must be after start date.');
                        return false;
                    }
                }
            });

            // Auto-set minimum date for end date
            startDate.addEventListener('change', function() {
                endDate.min = this.value;
            });
        });
    </script>
</body>
</html>