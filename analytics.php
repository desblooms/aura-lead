<?php
/**
 * Analytics Dashboard
 * Lead Management System
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Require login and analytics permission
require_login();
require_role(['admin', 'marketing']);

$current_user = get_logged_in_user();

// Get all leads for analytics (admin and marketing can see all)
$all_leads = get_all("SELECT l.*, u.full_name as assigned_user FROM leads l LEFT JOIN users u ON l.assigned_to = u.id ORDER BY l.created_at DESC");

// Calculate statistics
$total_leads = count($all_leads);

// Status breakdown
$status_counts = [];
$status_options = ['Interested', 'Not Interested', 'Budget Not Met', 'Meeting Scheduled', ''];
foreach ($status_options as $status) {
    $status_counts[$status] = count(array_filter($all_leads, function($lead) use ($status) {
        return $lead['client_status'] === $status;
    }));
}

// Industry breakdown
$industry_counts = [];
$industry_options = get_industry_options();
foreach ($industry_options as $industry) {
    $industry_counts[$industry] = count(array_filter($all_leads, function($lead) use ($industry) {
        return $lead['industry'] === $industry;
    }));
}

// Leads created over time (last 30 days)
$leads_by_date = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $leads_by_date[$date] = count(array_filter($all_leads, function($lead) use ($date) {
        return date('Y-m-d', strtotime($lead['created_at'])) === $date;
    }));
}

// Assignment statistics (for admin)
$assignment_stats = [];
if ($current_user['role'] === 'admin') {
    $unassigned = count(array_filter($all_leads, function($lead) {
        return empty($lead['assigned_to']);
    }));
    $assignment_stats['Unassigned'] = $unassigned;
    
    $sales_staff = get_sales_staff();
    foreach ($sales_staff as $staff) {
        $count = count(array_filter($all_leads, function($lead) use ($staff) {
            return $lead['assigned_to'] == $staff['id'];
        }));
        $assignment_stats[$staff['full_name']] = $count;
    }
}

// Follow-up statistics
$upcoming_followups = count(array_filter($all_leads, function($lead) {
    return !empty($lead['follow_up']) && strtotime($lead['follow_up']) >= strtotime('today') && strtotime($lead['follow_up']) <= strtotime('+7 days');
}));

$overdue_followups = count(array_filter($all_leads, function($lead) {
    return !empty($lead['follow_up']) && strtotime($lead['follow_up']) < strtotime('today');
}));

// Conversion rate calculation
$total_contacted = count(array_filter($all_leads, function($lead) {
    return !empty($lead['client_status']) && $lead['client_status'] !== '';
}));
$interested_count = $status_counts['Interested'];
$meetings_count = $status_counts['Meeting Scheduled'];
$conversion_rate = $total_contacted > 0 ? round((($interested_count + $meetings_count) / $total_contacted) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Lead Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                            <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-500 ml-1 md:ml-2">Analytics</span>
                        </div>
                    </li>
                </ol>
            </nav>
            <h1 class="text-3xl font-bold text-gray-900 mt-2">Lead Analytics</h1>
            <p class="text-gray-600">Comprehensive insights into your lead management performance</p>
        </div>

        <!-- Key Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Leads</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $total_leads; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Conversion Rate</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $conversion_rate; ?>%</p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Upcoming Follow-ups</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $upcoming_followups; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-2 bg-red-100 rounded-lg">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Overdue Follow-ups</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $overdue_followups; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Status Distribution -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Lead Status Distribution</h3>
                <div class="relative h-64">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <!-- Industry Breakdown -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Leads by Industry</h3>
                <div class="relative h-64">
                    <canvas id="industryChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Leads Over Time -->
        <div class="bg-white p-6 rounded-lg shadow mb-8">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Leads Created (Last 30 Days)</h3>
            <div class="relative h-64">
                <canvas id="timeChart"></canvas>
            </div>
        </div>

        <?php if ($current_user['role'] === 'admin'): ?>
        <!-- Assignment Distribution (Admin Only) -->
        <div class="bg-white p-6 rounded-lg shadow mb-8">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Lead Assignment Distribution</h3>
            <div class="relative h-64">
                <canvas id="assignmentChart"></canvas>
            </div>
        </div>
        <?php endif; ?>

        <!-- Detailed Statistics -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Status Breakdown Table -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Status Breakdown</h3>
                <div class="space-y-3">
                    <?php foreach ($status_counts as $status => $count): ?>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">
                                <?php echo $status ?: 'No Status'; ?>
                            </span>
                            <div class="flex items-center">
                                <span class="text-sm font-medium text-gray-900 mr-2"><?php echo $count; ?></span>
                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $total_leads > 0 ? ($count / $total_leads * 100) : 0; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Top Industries -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Top Industries</h3>
                <div class="space-y-3">
                    <?php 
                    // Sort industries by count and show top 5
                    arsort($industry_counts);
                    $top_industries = array_slice($industry_counts, 0, 5, true);
                    foreach ($top_industries as $industry => $count): 
                        if ($count > 0):
                    ?>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600"><?php echo $industry; ?></span>
                            <div class="flex items-center">
                                <span class="text-sm font-medium text-gray-900 mr-2"><?php echo $count; ?></span>
                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo $total_leads > 0 ? ($count / $total_leads * 100) : 0; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Chart.js configurations and data
        const chartColors = {
            primary: '#3B82F6',
            success: '#10B981',
            warning: '#F59E0B',
            danger: '#EF4444',
            info: '#06B6D4',
            secondary: '#6B7280'
        };

        // Status Distribution Pie Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_map(function($status) { return $status ?: 'No Status'; }, array_keys($status_counts))); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($status_counts)); ?>,
                    backgroundColor: [
                        chartColors.success,
                        chartColors.danger,
                        chartColors.warning,
                        chartColors.primary,
                        chartColors.secondary
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });

        // Industry Bar Chart
        const industryCtx = document.getElementById('industryChart').getContext('2d');
        const industryData = <?php echo json_encode($industry_counts); ?>;
        const industryLabels = Object.keys(industryData).filter(key => industryData[key] > 0);
        const industryValues = industryLabels.map(label => industryData[label]);

        const industryChart = new Chart(industryCtx, {
            type: 'bar',
            data: {
                labels: industryLabels,
                datasets: [{
                    label: 'Number of Leads',
                    data: industryValues,
                    backgroundColor: chartColors.primary,
                    borderColor: chartColors.primary,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                }
            }
        });

        // Time Series Chart
        const timeCtx = document.getElementById('timeChart').getContext('2d');
        const timeData = <?php echo json_encode($leads_by_date); ?>;
        const timeLabels = Object.keys(timeData);
        const timeValues = Object.values(timeData);

        const timeChart = new Chart(timeCtx, {
            type: 'line',
            data: {
                labels: timeLabels.map(date => new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })),
                datasets: [{
                    label: 'Leads Created',
                    data: timeValues,
                    borderColor: chartColors.success,
                    backgroundColor: chartColors.success + '20',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        <?php if ($current_user['role'] === 'admin'): ?>
        // Assignment Distribution Chart (Admin Only)
        const assignmentCtx = document.getElementById('assignmentChart').getContext('2d');
        const assignmentData = <?php echo json_encode($assignment_stats); ?>;
        const assignmentLabels = Object.keys(assignmentData);
        const assignmentValues = Object.values(assignmentData);

        const assignmentChart = new Chart(assignmentCtx, {
            type: 'bar',
            data: {
                labels: assignmentLabels,
                datasets: [{
                    label: 'Assigned Leads',
                    data: assignmentValues,
                    backgroundColor: chartColors.info,
                    borderColor: chartColors.info,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>