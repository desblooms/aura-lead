<?php
/**
 * Activity Logs and Notifications System
 * Lead Management System
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Require login
require_login();
require_role(['admin', 'marketing']);

$current_user = get_logged_in_user();

// Get activity logs with pagination
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

// Get total count for pagination
$total_logs = get_row("SELECT COUNT(*) as count FROM activity_logs")['count'] ?? 0;
$total_pages = ceil($total_logs / $limit);

// Get activity logs
$logs = get_all("
    SELECT al.*, u.full_name as user_name, l.client_name 
    FROM activity_logs al 
    LEFT JOIN users u ON al.user_id = u.id 
    LEFT JOIN leads l ON al.lead_id = l.id 
    ORDER BY al.created_at DESC 
    LIMIT ? OFFSET ?
", [$limit, $offset], 'ii');

// Get recent notifications
$notifications = get_all("
    SELECT 
        'follow_up' as type,
        l.id as lead_id,
        l.client_name,
        l.follow_up as date,
        u.full_name as assigned_user
    FROM leads l 
    LEFT JOIN users u ON l.assigned_to = u.id 
    WHERE l.follow_up IS NOT NULL 
    AND DATE(l.follow_up) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY l.follow_up ASC
    LIMIT 10
");

// Get overdue follow-ups
$overdue = get_all("
    SELECT 
        l.id as lead_id,
        l.client_name,
        l.follow_up as date,
        u.full_name as assigned_user,
        DATEDIFF(CURDATE(), l.follow_up) as days_overdue
    FROM leads l 
    LEFT JOIN users u ON l.assigned_to = u.id 
    WHERE l.follow_up IS NOT NULL 
    AND DATE(l.follow_up) < CURDATE()
    ORDER BY l.follow_up ASC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity & Notifications - Lead Management System</title>
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
                            <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-500 ml-1 md:ml-2">Activity & Notifications</span>
                        </div>
                    </li>
                </ol>
            </nav>
            <h1 class="text-3xl font-bold text-gray-900 mt-2">Activity & Notifications</h1>
            <p class="text-gray-600">Monitor system activity and manage follow-up notifications</p>
        </div>

        <!-- Notifications Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Upcoming Follow-ups -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 flex items-center">
                        <svg class="w-5 h-5 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                        </svg>
                        Upcoming Follow-ups (Next 7 Days)
                    </h3>
                    <p class="text-sm text-gray-500"><?php echo count($notifications); ?> leads need follow-up</p>
                </div>
                <div class="max-h-80 overflow-y-auto">
                    <?php if (empty($notifications)): ?>
                        <div class="p-6 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No upcoming follow-ups</h3>
                            <p class="mt-1 text-sm text-gray-500">All caught up!</p>
                        </div>
                    <?php else: ?>
                        <div class="divide-y divide-gray-200">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="p-4 hover:bg-gray-50">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900">
                                                <a href="lead_edit.php?id=<?php echo $notification['lead_id']; ?>" class="hover:text-blue-600">
                                                    <?php echo sanitize_output($notification['client_name']); ?>
                                                </a>
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                Assigned to: <?php echo sanitize_output($notification['assigned_user'] ?? 'Unassigned'); ?>
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-medium text-blue-600">
                                                <?php echo format_date($notification['date']); ?>
                                            </p>
                                            <?php 
                                            $days_until = ceil((strtotime($notification['date']) - time()) / (60*60*24));
                                            if ($days_until <= 1): 
                                            ?>
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                                    <?php echo $days_until === 0 ? 'Today' : ($days_until === 1 ? 'Tomorrow' : $days_until . ' days'); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-xs text-gray-500"><?php echo $days_until; ?> days</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Overdue Follow-ups -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 flex items-center">
                        <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        Overdue Follow-ups
                    </h3>
                    <p class="text-sm text-gray-500"><?php echo count($overdue); ?> leads are overdue</p>
                </div>
                <div class="max-h-80 overflow-y-auto">
                    <?php if (empty($overdue)): ?>
                        <div class="p-6 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No overdue follow-ups</h3>
                            <p class="mt-1 text-sm text-gray-500">Great job staying on top of things!</p>
                        </div>
                    <?php else: ?>
                        <div class="divide-y divide-gray-200">
                            <?php foreach ($overdue as $item): ?>
                                <div class="p-4 hover:bg-gray-50 bg-red-50">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900">
                                                <a href="lead_edit.php?id=<?php echo $item['lead_id']; ?>" class="hover:text-red-600">
                                                    <?php echo sanitize_output($item['client_name']); ?>
                                                </a>
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                Assigned to: <?php echo sanitize_output($item['assigned_user'] ?? 'Unassigned'); ?>
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-medium text-red-600">
                                                <?php echo format_date($item['date']); ?>
                                            </p>
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                                <?php echo $item['days_overdue']; ?> day<?php echo $item['days_overdue'] > 1 ? 's' : ''; ?> overdue
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Activity Logs -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">System Activity Log</h3>
                        <p class="text-sm text-gray-500">Recent user activities and system events</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-500">
                            Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $limit, $total_logs); ?> of <?php echo $total_logs; ?>
                        </span>
                    </div>
                </div>
            </div>

            <?php if (empty($logs)): ?>
                <div class="p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No activity logs</h3>
                    <p class="mt-1 text-sm text-gray-500">Activity logging is not yet enabled.</p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-gray-200">
                    <?php foreach ($logs as $log): ?>
                        <div class="p-4 hover:bg-gray-50">
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0">
                                    <?php 
                                    $icon_class = 'text-gray-400';
                                    $icon = 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z';
                                    
                                    switch ($log['action']) {
                                        case 'created_lead':
                                            $icon_class = 'text-green-500';
                                            $icon = 'M12 6v6m0 0v6m0-6h6m-6 0H6';
                                            break;
                                        case 'updated_lead':
                                            $icon_class = 'text-blue-500';
                                            $icon = 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z';
                                            break;
                                        case 'deleted_lead':
                                            $icon_class = 'text-red-500';
                                            $icon = 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16';
                                            break;
                                        case 'assigned_lead':
                                            $icon_class = 'text-purple-500';
                                            $icon = 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z';
                                            break;
                                    }
                                    ?>
                                    <svg class="w-5 h-5 <?php echo $icon_class; ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $icon; ?>" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm text-gray-900">
                                            <span class="font-medium"><?php echo sanitize_output($log['user_name'] ?? 'System'); ?></span>
                                            <?php echo sanitize_output($log['details']); ?>
                                            <?php if ($log['client_name']): ?>
                                                for <a href="lead_edit.php?id=<?php echo $log['lead_id']; ?>" class="text-blue-600 hover:text-blue-800 font-medium">
                                                    <?php echo sanitize_output($log['client_name']); ?>
                                                </a>
                                            <?php endif; ?>
                                        </p>
                                        <span class="text-xs text-gray-500"><?php echo time_ago($log['created_at']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="px-6 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Previous
                                    </a>
                                <?php endif; ?>
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Next
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Showing page <span class="font-medium"><?php echo $page; ?></span> of <span class="font-medium"><?php echo $total_pages; ?></span>
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <a href="?page=<?php echo $i; ?>" 
                                               class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i === $page ? 'border-blue-500 bg-blue-50 text-blue-600' : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-50'; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endfor; ?>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-refresh notifications every 5 minutes
        setInterval(function() {
            // You could implement AJAX refresh here
            // location.reload();
        }, 300000); // 5 minutes

        // Mark notifications as read (future enhancement)
        function markAsRead(notificationId) {
            // AJAX call to mark notification as read
            fetch('api.php?action=mark_notification_read', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `notification_id=${notificationId}`
            });
        }
    </script>
</body>
</html>