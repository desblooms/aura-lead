<?php
/**
 * Add Lead Page
 * Lead Management System
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Require login and appropriate permissions
require_login();
require_role(['admin', 'sales']);

$current_user = get_logged_in_user();
$errors = [];
$success_message = '';

// Get sales staff for assignment (admin only)
$sales_staff = [];
if ($current_user['role'] === 'admin') {
    $sales_staff = get_sales_staff();
}

// Handle form submission
if ($_POST && isset($_POST['add_lead'])) {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        // Sanitize and validate inputs
        $client_name = sanitize_input($_POST['client_name']);
        $required_services = sanitize_input($_POST['required_services']);
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
        
        // Assignment logic
        if ($current_user['role'] === 'admin') {
            $assigned_to = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
        } else {
            // Sales staff can only assign to themselves
            $assigned_to = $current_user['id'];
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

        // If no errors, insert the lead
        if (empty($errors)) {
            $query = "INSERT INTO leads (client_name, required_services, website, phone, email, call_enquiry, mail, whatsapp, follow_up, client_status, notes, industry, assigned_to) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $client_name,
                $required_services,
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
                $assigned_to
            ];
            
            if (execute_query($query, $params, 'ssssssssssssi')) {
                redirect_with_message('index.php', 'Lead added successfully!', 'success');
            } else {
                $errors[] = 'Failed to add lead. Please try again.';
            }
        }
    }
}

// Generate CSRF token
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Lead - Lead Management System</title>
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
                            <span class="text-gray-500 ml-1 md:ml-2">Add Lead</span>
                        </div>
                    </li>
                </ol>
            </nav>
            <h1 class="text-3xl font-bold text-gray-900 mt-2">Add New Lead</h1>
            <p class="text-gray-600">Fill in the details to add a new lead to the system.</p>
        </div>

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

        <!-- Add Lead Form -->
        <div class="bg-white shadow rounded-lg">
            <form method="POST" action="" class="space-y-6 p-6">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

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
                                value="<?php echo isset($_POST['client_name']) ? sanitize_output($_POST['client_name']) : ''; ?>"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                required
                            >
                        </div>

                        <div>
                            <label for="industry" class="block text-sm font-medium text-gray-700">Industry</label>
                            <select 
                                id="industry" 
                                name="industry" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">Select Industry</option>
                                <?php foreach (get_industry_options() as $industry): ?>
                                    <option value="<?php echo $industry; ?>" <?php echo (isset($_POST['industry']) && $_POST['industry'] === $industry) ? 'selected' : ''; ?>>
                                        <?php echo $industry; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="required_services" class="block text-sm font-medium text-gray-700">Required Services</label>
                            <input 
                                type="text" 
                                id="required_services" 
                                name="required_services" 
                                value="<?php echo isset($_POST['required_services']) ? sanitize_output($_POST['required_services']) : ''; ?>"
                                placeholder="e.g., Web Development, SEO, Marketing"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>

                        <div>
                            <label for="website" class="block text-sm font-medium text-gray-700">Website</label>
                            <input 
                                type="url" 
                                id="website" 
                                name="website" 
                                value="<?php echo isset($_POST['website']) ? sanitize_output($_POST['website']) : ''; ?>"
                                placeholder="https://example.com"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
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
                                value="<?php echo isset($_POST['email']) ? sanitize_output($_POST['email']) : ''; ?>"
                                placeholder="contact@example.com"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                            <input 
                                type="tel" 
                                id="phone" 
                                name="phone" 
                                value="<?php echo isset($_POST['phone']) ? sanitize_output($_POST['phone']) : ''; ?>"
                                placeholder="+1234567890"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>

                        <div>
                            <label for="whatsapp" class="block text-sm font-medium text-gray-700">WhatsApp</label>
                            <input 
                                type="tel" 
                                id="whatsapp" 
                                name="whatsapp" 
                                value="<?php echo isset($_POST['whatsapp']) ? sanitize_output($_POST['whatsapp']) : ''; ?>"
                                placeholder="+1234567890"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>

                        <div>
                            <label for="mail" class="block text-sm font-medium text-gray-700">Secondary Email</label>
                            <input 
                                type="email" 
                                id="mail" 
                                name="mail" 
                                value="<?php echo isset($_POST['mail']) ? sanitize_output($_POST['mail']) : ''; ?>"
                                placeholder="secondary@example.com"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
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
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            >
                                <?php foreach (get_status_options() as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo (isset($_POST['client_status']) && $_POST['client_status'] === $value) ? 'selected' : ''; ?>>
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
                                value="<?php echo isset($_POST['follow_up']) ? sanitize_output($_POST['follow_up']) : ''; ?>"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>

                        <?php if ($current_user['role'] === 'admin'): ?>
                        <div>
                            <label for="assigned_to" class="block text-sm font-medium text-gray-700">Assign To</label>
                            <select 
                                id="assigned_to" 
                                name="assigned_to" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">Unassigned</option>
                                <?php foreach ($sales_staff as $staff): ?>
                                    <option value="<?php echo $staff['id']; ?>" <?php echo (isset($_POST['assigned_to']) && $_POST['assigned_to'] == $staff['id']) ? 'selected' : ''; ?>>
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
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            ><?php echo isset($_POST['call_enquiry']) ? sanitize_output($_POST['call_enquiry']) : ''; ?></textarea>
                        </div>

                        <div class="md:col-span-2">
                            <label for="notes" class="block text-sm font-medium text-gray-700">Additional Notes</label>
                            <textarea 
                                id="notes" 
                                name="notes" 
                                rows="4"
                                placeholder="Any additional information about the lead..."
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            ><?php echo isset($_POST['notes']) ? sanitize_output($_POST['notes']) : ''; ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                    <a href="index.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                        Cancel
                    </a>
                    <button 
                        type="submit" 
                        name="add_lead" 
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                    >
                        Add Lead
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const clientNameField = document.getElementById('client_name');

            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                // Reset previous error states
                document.querySelectorAll('.border-red-500').forEach(field => {
                    field.classList.remove('border-red-500');
                });

                // Validate required fields
                if (!clientNameField.value.trim()) {
                    clientNameField.classList.add('border-red-500');
                    isValid = false;
                }

                // Validate email format if provided
                const emailField = document.getElementById('email');
                if (emailField.value && !isValidEmail(emailField.value)) {
                    emailField.classList.add('border-red-500');
                    isValid = false;
                }

                // Validate website URL if provided
                const websiteField = document.getElementById('website');
                if (websiteField.value && !isValidURL(websiteField.value)) {
                    websiteField.classList.add('border-red-500');
                    isValid = false;
                }

                if (!isValid) {
                    e.preventDefault();
                    alert('Please check the highlighted fields and correct any errors.');
                }
            });

            // Auto-focus first field
            clientNameField.focus();
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

        // Phone number formatting
        document.querySelectorAll('input[type="tel"]').forEach(function(field) {
            field.addEventListener('input', function() {
                // Allow only numbers, spaces, dashes, parentheses, and plus sign
                this.value = this.value.replace(/[^\d\s\-\(\)\+]/g, '');
            });
        });
    </script>
</body>
</html>