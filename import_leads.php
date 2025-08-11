<?php
/**
 * Import Leads from CSV
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
$import_preview = [];
$import_stats = [];

// Handle file upload and import
if ($_POST && isset($_POST['import_leads'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
            } else {
            $file = $_FILES['csv_file'];
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            // Validate file type
            if ($file_extension !== 'csv') {
                $errors[] = 'Only CSV files are allowed.';
            }
            
            // Validate file size (5MB max)
            if ($file['size'] > 5 * 1024 * 1024) {
                $errors[] = 'File size must be less than 5MB.';
            }
            
            if (empty($errors)) {
                try {
                    $csv_data = array_map('str_getcsv', file($file['tmp_name']));
                    
                    if (empty($csv_data)) {
                        $errors[] = 'The CSV file appears to be empty.';
                    } else {
                        $headers = array_shift($csv_data); // Get headers
                        $headers = array_map('trim', $headers); // Clean headers
                        
                        // Required fields mapping
                        $required_fields = ['client_name'];
                        $field_mapping = [
                            'client_name' => ['client_name', 'name', 'client', 'company', 'company_name'],
                            'email' => ['email', 'email_address', 'contact_email'],
                            'phone' => ['phone', 'phone_number', 'contact_number', 'mobile'],
                            'website' => ['website', 'url', 'company_website'],
                            'required_services' => ['services', 'required_services', 'needed_services'],
                            'industry' => ['industry', 'business_type', 'sector'],
                            'notes' => ['notes', 'description', 'comments', 'remarks'],
                            'call_enquiry' => ['call_enquiry', 'enquiry', 'inquiry', 'call_notes']
                        ];
                        
                        // Map CSV headers to database fields
                        $header_map = [];
                        foreach ($field_mapping as $db_field => $possible_headers) {
                            foreach ($headers as $index => $header) {
                                if (in_array(strtolower($header), array_map('strtolower', $possible_headers))) {
                                    $header_map[$db_field] = $index;
                                    break;
                                }
                            }
                        }
                        
                        // Check if required fields are present
                        $missing_required = [];
                        foreach ($required_fields as $field) {
                            if (!isset($header_map[$field])) {
                                $missing_required[] = $field;
                            }
                        }
                        
                        if (!empty($missing_required)) {
                            $errors[] = 'Missing required columns: ' . implode(', ', $missing_required);
                        } else {
                            // Process CSV data
                            $imported_count = 0;
                            $skipped_count = 0;
                            $error_count = 0;
                            $import_errors = [];
                            
                            foreach ($csv_data as $row_index => $row) {
                                $row_number = $row_index + 2; // +2 because array starts at 0 and we removed headers
                                
                                // Skip empty rows
                                if (empty(array_filter($row))) {
                                    $skipped_count++;
                                    continue;
                                }
                                
                                // Extract data from row
                                $lead_data = [];
                                foreach ($header_map as $db_field => $csv_index) {
                                    $lead_data[$db_field] = isset($row[$csv_index]) ? trim($row[$csv_index]) : '';
                                }
                                
                                // Validate required fields
                                if (empty($lead_data['client_name'])) {
                                    $import_errors[] = "Row $row_number: Client name is required";
                                    $error_count++;
                                    continue;
                                }
                                
                                // Validate email format if provided
                                if (!empty($lead_data['email']) && !validate_email($lead_data['email'])) {
                                    $import_errors[] = "Row $row_number: Invalid email format";
                                    $error_count++;
                                    continue;
                                }
                                
                                // Validate website URL if provided
                                if (!empty($lead_data['website']) && !validate_url($lead_data['website'])) {
                                    // Try to fix URL by adding http://
                                    if (!validate_url('http://' . $lead_data['website'])) {
                                        $import_errors[] = "Row $row_number: Invalid website URL";
                                        $error_count++;
                                        continue;
                                    } else {
                                        $lead_data['website'] = 'http://' . $lead_data['website'];
                                    }
                                }
                                
                                // Set default values
                                $assigned_to = null;
                                if ($current_user['role'] === 'admin') {
                                    // Admin can choose assignment or leave unassigned
                                    $assigned_to = !empty($_POST['default_assigned_to']) ? (int)$_POST['default_assigned_to'] : null;
                                } else {
                                    // Sales staff assign to themselves
                                    $assigned_to = $current_user['id'];
                                }
                                
                                // Insert lead into database
                                $query = "INSERT INTO leads (client_name, email, phone, website, required_services, industry, notes, call_enquiry, assigned_to, lead_source, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'CSV Import', NOW())";
                                
                                $params = [
                                    $lead_data['client_name'],
                                    $lead_data['email'] ?? '',
                                    $lead_data['phone'] ?? '',
                                    $lead_data['website'] ?? '',
                                    $lead_data['required_services'] ?? '',
                                    $lead_data['industry'] ?? '',
                                    $lead_data['notes'] ?? '',
                                    $lead_data['call_enquiry'] ?? '',
                                    $assigned_to
                                ];
                                
                                if (execute_query($query, $params, 'ssssssssi')) {
                                    $imported_count++;
                                } else {
                                    $import_errors[] = "Row $row_number: Database error occurred";
                                    $error_count++;
                                }
                            }
                            
                            // Set import statistics
                            $import_stats = [
                                'total_rows' => count($csv_data),
                                'imported' => $imported_count,
                                'skipped' => $skipped_count,
                                'errors' => $error_count,
                                'error_details' => $import_errors
                            ];
                            
                            if ($imported_count > 0) {
                                $success_message = "Successfully imported $imported_count leads!";
                                if ($error_count > 0) {
                                    $success_message .= " $error_count rows had errors.";
                                }
                                if ($skipped_count > 0) {
                                    $success_message .= " $skipped_count empty rows were skipped.";
                                }
                            } else {
                                $errors[] = 'No leads were imported. Please check your CSV file format.';
                            }
                        }
                    }
                } catch (Exception $e) {
                    $errors[] = 'Error processing CSV file: ' . $e->getMessage();
                }
            }
        }
    }
}

// Handle preview mode
if ($_POST && isset($_POST['preview_csv'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['csv_file'];
        $csv_data = array_map('str_getcsv', file($file['tmp_name']));
        
        if (!empty($csv_data)) {
            $headers = array_shift($csv_data);
            $import_preview = [
                'headers' => $headers,
                'sample_rows' => array_slice($csv_data, 0, 5), // Show first 5 rows
                'total_rows' => count($csv_data)
            ];
        }
    }
}

// Get sales staff for assignment (admin only)
$sales_staff = [];
if ($current_user['role'] === 'admin') {
    $sales_staff = get_sales_staff();
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Leads - Lead Management System</title>
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
                            <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-500 ml-1 md:ml-2">Import Leads</span>
                        </div>
                    </li>
                </ol>
            </nav>
            <h1 class="text-3xl font-bold text-gray-900 mt-2">Import Leads from CSV</h1>
            <p class="text-gray-600">Upload a CSV file to bulk import leads into the system</p>
        </div>

        <!-- Success Message -->
        <?php if ($success_message): ?>
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                <?php echo sanitize_output($success_message); ?>
                <?php if (!empty($import_stats)): ?>
                    <div class="mt-2 text-sm">
                        <strong>Import Summary:</strong>
                        <ul class="list-disc list-inside mt-1">
                            <li>Total rows processed: <?php echo $import_stats['total_rows']; ?></li>
                            <li>Successfully imported: <?php echo $import_stats['imported']; ?></li>
                            <?php if ($import_stats['errors'] > 0): ?>
                                <li>Errors: <?php echo $import_stats['errors']; ?></li>
                            <?php endif; ?>
                            <?php if ($import_stats['skipped'] > 0): ?>
                                <li>Empty rows skipped: <?php echo $import_stats['skipped']; ?></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>
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

        <!-- Import Errors Details -->
        <?php if (!empty($import_stats['error_details'])): ?>
            <div class="mb-6 bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                <h4 class="font-bold mb-2">Import Errors (First 10):</h4>
                <ul class="list-disc list-inside text-sm">
                    <?php foreach (array_slice($import_stats['error_details'], 0, 10) as $error): ?>
                        <li><?php echo sanitize_output($error); ?></li>
                    <?php endforeach; ?>
                    <?php if (count($import_stats['error_details']) > 10): ?>
                        <li>... and <?php echo count($import_stats['error_details']) - 10; ?> more errors</li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Instructions -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">CSV Import Instructions</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <ul class="list-disc list-inside space-y-1">
                            <li><strong>Required column:</strong> client_name (or name, client, company)</li>
                            <li><strong>Optional columns:</strong> email, phone, website, services, industry, notes</li>
                            <li>Column headers are automatically detected and mapped</li>
                            <li>Empty rows will be skipped automatically</li>
                            <li>Maximum file size: 5MB</li>
                            <li>Use UTF-8 encoding for special characters</li>
                        </ul>
                    </div>
                    <div class="mt-3">
                        <a href="#" onclick="downloadTemplate()" class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                            Download CSV Template
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload Form -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Upload CSV File</h3>
            </div>
            <div class="p-6">
                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <!-- File Upload -->
                    <div>
                        <label for="csv_file" class="block text-sm font-medium text-gray-700">CSV File *</label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-gray-400 transition-colors">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-gray-600">
                                    <label for="csv_file" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                        <span>Upload a CSV file</span>
                                        <input id="csv_file" name="csv_file" type="file" accept=".csv" class="sr-only" required onchange="handleFileSelect(this)">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">CSV files up to 5MB</p>
                            </div>
                        </div>
                        <div id="file-info" class="mt-2 text-sm text-gray-600" style="display: none;"></div>
                    </div>

                    <?php if ($current_user['role'] === 'admin'): ?>
                    <!-- Default Assignment -->
                    <div>
                        <label for="default_assigned_to" class="block text-sm font-medium text-gray-700">Default Assignment</label>
                        <select 
                            id="default_assigned_to" 
                            name="default_assigned_to" 
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="">Leave Unassigned</option>
                            <?php foreach ($sales_staff as $staff): ?>
                                <option value="<?php echo $staff['id']; ?>">
                                    <?php echo sanitize_output($staff['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">All imported leads will be assigned to this person (optional)</p>
                    </div>
                    <?php endif; ?>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                        <a href="index.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                            Cancel
                        </a>
                        <div class="space-x-4">
                            <button 
                                type="submit" 
                                name="preview_csv" 
                                class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                            >
                                Preview Data
                            </button>
                            <button 
                                type="submit" 
                                name="import_leads" 
                                class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                                onclick="return confirm('Are you sure you want to import these leads? This action cannot be undone.')"
                            >
                                Import Leads
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Preview Section -->
        <?php if (!empty($import_preview)): ?>
            <div class="mt-6 bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Data Preview</h3>
                    <p class="text-sm text-gray-500">
                        Showing first 5 rows of <?php echo $import_preview['total_rows']; ?> total rows
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <?php foreach ($import_preview['headers'] as $header): ?>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <?php echo sanitize_output($header); ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($import_preview['sample_rows'] as $row): ?>
                                <tr>
                                    <?php foreach ($row as $cell): ?>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo sanitize_output($cell); ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function handleFileSelect(input) {
            const fileInfo = document.getElementById('file-info');
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const fileSize = (file.size / 1024 / 1024).toFixed(2); // Convert to MB
                fileInfo.innerHTML = `Selected: <strong>${file.name}</strong> (${fileSize} MB)`;
                fileInfo.style.display = 'block';
            } else {
                fileInfo.style.display = 'none';
            }
        }

        function downloadTemplate() {
            // Create a sample CSV template
            const csvContent = [
                ['client_name', 'email', 'phone', 'website', 'required_services', 'industry', 'notes'],
                ['Acme Corporation', 'contact@acme.com', '+1234567890', 'https://acme.com', 'Website Development', 'Technology', 'Interested in custom solution'],
                ['Best Solutions Ltd', 'info@bestsolutions.com', '+0987654321', 'https://bestsolutions.com', 'Digital Marketing', 'Marketing', 'Needs SEO services'],
                ['Creative Agency', 'hello@creative.com', '+1122334455', '', 'Logo Design, Branding', 'Design', 'Startup company']
            ].map(row => row.join(',')).join('\n');

            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'leads_template.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }

        // Drag and drop functionality
        const dropZone = document.querySelector('.border-dashed');
        const fileInput = document.getElementById('csv_file');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            dropZone.classList.add('border-blue-400', 'bg-blue-50');
        }

        function unhighlight(e) {
            dropZone.classList.remove('border-blue-400', 'bg-blue-50');
        }

        dropZone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;

            if (files.length > 0) {
                fileInput.files = files;
                handleFileSelect(fileInput);
            }
        }
    </script>
</body>
</html>
        