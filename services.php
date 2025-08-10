<?php
/**
 * Services Management Page (Admin Only)
 * Lead Management System
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Require admin access only
require_login();
require_role(['admin']);

$current_user = get_logged_in_user();
$errors = [];
$success_message = '';

// Handle service creation
if ($_POST && isset($_POST['add_service'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $service_name = sanitize_input($_POST['service_name']);
        $service_category = sanitize_input($_POST['service_category']);
        $description = sanitize_input($_POST['description']);

        // Validation
        if (empty($service_name)) {
            $errors[] = 'Service name is required.';
        }

        if (empty($service_category)) {
            $errors[] = 'Service category is required.';
        }

        // Create service if no errors
        if (empty($errors)) {
            $query = "INSERT INTO services (service_name, service_category, description, created_by) VALUES (?, ?, ?, ?)";
            
            if (execute_query($query, [$service_name, $service_category, $description, $current_user['id']], 'sssi')) {
                $success_message = 'Service created successfully!';
                // Clear form
                $_POST = [];
            } else {
                $errors[] = 'Failed to create service. Please try again.';
            }
        }
    }
}

// Handle service status toggle
if ($_POST && isset($_POST['toggle_service_status'])) {
    $service_id = (int)$_POST['service_id'];
    $current_status = (int)$_POST['current_status'];
    $new_status = $current_status ? 0 : 1;
    
    $query = "UPDATE services SET is_active = ? WHERE id = ?";
    execute_query($query, [$new_status, $service_id], 'ii');
    $success_message = 'Service status updated successfully!';
}

// Get all services
$services = get_all("SELECT s.*, u.full_name as created_by_name FROM services s LEFT JOIN users u ON s.created_by = u.id ORDER BY s.created_at DESC");

$csrf_token = generate_csrf_token();

// Service categories
$service_categories = [
    'Digital Services',
    'Digital Marketing', 
    'Design Services',
    'Development Services',
    'Consulting Services',
    'Other'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services Management - Lead Management System</title>
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
                    <a href="users.php" class="text-blue-600 hover:text-blue-800">Users</a>
                    <a href="running_ads.php" class="text-blue-600 hover:text-blue-800">Running Ads</a>
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
                            <span class="text-gray-500 ml-1 md:ml-2">Services Management</span>
                        </div>
                    </li>
                </ol>
            </nav>
            <h1 class="text-3xl font-bold text-gray-900 mt-2">Services Management</h1>
            <p class="text-gray-600">Manage your company's services and products</p>
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Add Service Form -->
            <div class="lg:col-span-1">
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Add New Service</h3>
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="space-y-4">
                            <div>
                                <label for="service_name" class="block text-sm font-medium text-gray-700">Service Name *</label>
                                <input 
                                    type="text" 
                                    id="service_name" 
                                    name="service_name" 
                                    value="<?php echo isset($_POST['service_name']) ? sanitize_output($_POST['service_name']) : ''; ?>"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    required
                                >
                            </div>

                            <div>
                                <label for="service_category" class="block text-sm font-medium text-gray-700">Category *</label>
                                <select 
                                    id="service_category" 
                                    name="service_category" 
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    required
                                >
                                    <option value="">Select Category</option>
                                    <?php foreach ($service_categories as $category): ?>
                                        <option value="<?php echo $category; ?>" <?php echo (isset($_POST['service_category']) && $_POST['service_category'] === $category) ? 'selected' : ''; ?>>
                                            <?php echo $category; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                                <textarea 
                                    id="description" 
                                    name="description" 
                                    rows="4"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Brief description of the service..."
                                ><?php echo isset($_POST['description']) ? sanitize_output($_POST['description']) : ''; ?></textarea>
                            </div>

                            <button 
                                type="submit" 
                                name="add_service" 
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                            >
                                Add Service
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Services List -->
            <div class="lg:col-span-2">
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Available Services</h3>
                        <p class="text-sm text-gray-500"><?php echo count($services); ?> total services</p>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created By</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($services as $service): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo sanitize_output($service['service_name']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo sanitize_output($service['description']); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                                <?php echo sanitize_output($service['service_category']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                <?php echo $service['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo $service['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo sanitize_output($service['created_by_name'] ?? 'Unknown'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                                <input type="hidden" name="current_status" value="<?php echo $service['is_active']; ?>">
                                                <button type="submit" name="toggle_service_status" 
                                                        class="<?php echo $service['is_active'] ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'; ?>">
                                                    <?php echo $service['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            
            form.addEventListener('submit', function(e) {
                const serviceName = document.getElementById('service_name').value.trim();
                const serviceCategory = document.getElementById('service_category').value;

                if (!serviceName || !serviceCategory) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                    return false;
                }
            });
        });
    </script>
</body>
</html>