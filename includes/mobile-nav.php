<?php
/**
 * Mobile App-Style Universal Navigation System
 * Lead Management System
 * 
 * Include this file in all pages for consistent mobile app experience
 */

// Make sure user is logged in
if (!is_logged_in()) {
    return;
}

$current_user = get_logged_in_user();
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Define comprehensive navigation items based on roles
$mobile_nav_items = [
    'home' => [
        'title' => 'Home',
        'icon' => 'fas fa-home',
        'url' => 'index.php',
        'roles' => ['admin', 'sales', 'marketing'],
        'badge' => null
    ],
    'add' => [
        'title' => 'Add',
        'icon' => 'fas fa-plus-circle',
        'url' => 'lead_add.php',
        'roles' => ['admin', 'sales'],
        'badge' => null
    ],
    'search' => [
        'title' => 'Search',
        'icon' => 'fas fa-search',
        'url' => 'search_leads.php',
        'roles' => ['admin', 'sales', 'marketing'],
        'badge' => null
    ],
    'analytics' => [
        'title' => 'Analytics',
        'icon' => 'fas fa-chart-bar',
        'url' => 'analytics.php',
        'roles' => ['admin', 'marketing'],
        'badge' => null
    ],
    'more' => [
        'title' => 'More',
        'icon' => 'fas fa-ellipsis-h',
        'url' => '#',
        'roles' => ['admin', 'sales', 'marketing'],
        'badge' => null
    ]
];

// Additional pages accessible through "More" menu
$more_menu_items = [
    'core' => [
        'title' => 'Core Features',
        'items' => [
            'import_leads' => [
                'title' => 'Import Leads',
                'icon' => 'fas fa-upload',
                'url' => 'import_leads.php',
                'roles' => ['admin', 'sales']
            ],
            'export' => [
                'title' => 'Export Data',
                'icon' => 'fas fa-download',
                'url' => 'export.php',
                'roles' => ['admin', 'sales', 'marketing']
            ],
            'activity' => [
                'title' => 'Activity',
                'icon' => 'fas fa-bell',
                'url' => 'activity.php',
                'roles' => ['admin', 'marketing']
            ]
        ]
    ],
    'marketing' => [
        'title' => 'Marketing Tools',
        'items' => [
            'running_ads' => [
                'title' => 'Campaigns',
                'icon' => 'fas fa-bullhorn',
                'url' => 'running_ads.php',
                'roles' => ['admin', 'marketing']
            ],
            'services' => [
                'title' => 'Services',
                'icon' => 'fas fa-cogs',
                'url' => 'services.php',
                'roles' => ['admin']
            ]
        ]
    ],
    'admin' => [
        'title' => 'Administration',
        'items' => [
            'users' => [
                'title' => 'Users',
                'icon' => 'fas fa-users',
                'url' => 'users.php',
                'roles' => ['admin']
            ],
            'setup' => [
                'title' => 'Setup',
                'icon' => 'fas fa-wrench',
                'url' => 'setup.php',
                'roles' => ['admin']
            ],
            'debug' => [
                'title' => 'Debug',
                'icon' => 'fas fa-bug',
                'url' => 'debug.php',
                'roles' => ['admin']
            ]
        ]
    ]
];

/**
 * Check if user has access to a navigation item
 */
function user_has_mobile_access($nav_item, $user_role) {
    return in_array($user_role, $nav_item['roles']);
}

/**
 * Get notification counts for badges
 */
function get_notification_counts($user_id, $user_role) {
    $counts = [];
    
    // Get upcoming follow-ups count
    if (in_array($user_role, ['admin', 'sales', 'marketing'])) {
        $follow_up_count = 0;
        try {
            if ($user_role === 'sales') {
                $query = "SELECT COUNT(*) as count FROM leads WHERE assigned_to = ? AND follow_up IS NOT NULL AND DATE(follow_up) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
                $result = get_row($query, [$user_id], 'i');
            } else {
                $query = "SELECT COUNT(*) as count FROM leads WHERE follow_up IS NOT NULL AND DATE(follow_up) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
                $result = get_row($query);
            }
            $follow_up_count = $result['count'] ?? 0;
        } catch (Exception $e) {
            $follow_up_count = 0;
        }
        
        $counts['notifications'] = $follow_up_count;
    }
    
    return $counts;
}

/**
 * Generate mobile header with gradient
 */
function render_mobile_header($current_user, $page_title = null) {
    $notification_counts = get_notification_counts($current_user['id'], $current_user['role']);
    
    if (!$page_title) {
        $page_titles = [
            'index' => 'Lead Manager',
            'lead_add' => 'Add Lead',
            'lead_edit' => 'Edit Lead',
            'search_leads' => 'Search Leads',
            'analytics' => 'Analytics',
            'import_leads' => 'Import Leads',
            'export' => 'Export Data',
            'running_ads' => 'Campaigns',
            'services' => 'Services',
            'users' => 'User Management',
            'activity' => 'Activity',
            'setup' => 'System Setup',
            'debug' => 'Debug'
        ];
        
        $current_page = basename($_SERVER['PHP_SELF'], '.php');
        $page_title = $page_titles[$current_page] ?? 'Lead Management';
    }
    
    ob_start();
    ?>
    <div class="lg:hidden">
        <!-- Mobile Header with Gradient -->
        <div class="mobile-header text-white sticky top-0 z-50">
            <div class="px-4 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-white/20 backdrop-blur-lg rounded-2xl flex items-center justify-center border border-white/30">
                            <i class="fas fa-chart-line text-white text-lg"></i>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold"><?php echo $page_title; ?></h1>
                            <p class="text-white/80 text-sm">Professional CRM</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <!-- Notification Bell -->
                        <?php if ($notification_counts['notifications'] > 0): ?>
                        <div class="relative">
                            <a href="activity.php" class="w-10 h-10 bg-white/20 backdrop-blur-lg rounded-xl flex items-center justify-center border border-white/30">
                                <i class="fas fa-bell text-white"></i>
                            </a>
                            <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold">
                                <?php echo min($notification_counts['notifications'], 9); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <!-- User Avatar -->
                        <div class="w-10 h-10 bg-white/20 backdrop-blur-lg rounded-full flex items-center justify-center border border-white/30">
                            <span class="text-white text-sm font-bold">
                                <?php echo strtoupper(substr($current_user['full_name'], 0, 1)); ?>
                            </span>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium"><?php echo explode(' ', $current_user['full_name'])[0]; ?></p>
                            <p class="text-xs text-white/80"><?php echo ucfirst($current_user['role']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .mobile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            backdrop-filter: blur(20px);
        }
    </style>
    <?php
    return ob_get_clean();
}

/**
 * Generate mobile bottom navigation
 */
function render_mobile_bottom_nav($current_user, $current_page, $mobile_nav_items) {
    $notification_counts = get_notification_counts($current_user['id'], $current_user['role']);
    
    ob_start();
    ?>
    <div class="lg:hidden">
        <!-- Mobile Bottom Navigation -->
        <div class="fixed bottom-0 left-0 right-0 mobile-tab-bar z-50">
            <div class="flex justify-around py-2">
                <?php foreach ($mobile_nav_items as $key => $item): ?>
                    <?php if (user_has_mobile_access($item, $current_user['role'])): ?>
                        <?php 
                        $is_active = ($key === 'home' && $current_page === 'index') || 
                                    ($key === 'add' && $current_page === 'lead_add') ||
                                    ($key === 'search' && $current_page === 'search_leads') ||
                                    ($key === 'analytics' && $current_page === 'analytics') ||
                                    ($key === 'more' && in_array($current_page, ['import_leads', 'export', 'running_ads', 'services', 'users', 'activity', 'setup', 'debug']));
                        
                        $active_class = $is_active ? 'mobile-tab-item active' : 'mobile-tab-item';
                        $text_class = $is_active ? 'text-blue-600' : 'text-gray-500';
                        ?>
                        
                        <?php if ($key === 'more'): ?>
                            <button onclick="toggleMoreMenu()" class="<?php echo $active_class; ?> flex flex-col items-center py-3 px-4 <?php echo $text_class; ?>">
                                <i class="<?php echo $item['icon']; ?> text-xl mb-1"></i>
                                <span class="text-xs font-medium"><?php echo $item['title']; ?></span>
                            </button>
                        <?php else: ?>
                            <a href="<?php echo $item['url']; ?>" class="<?php echo $active_class; ?> flex flex-col items-center py-3 px-4 <?php echo $text_class; ?>">
                                <div class="relative">
                                    <i class="<?php echo $item['icon']; ?> text-xl mb-1"></i>
                                    <?php if ($key === 'analytics' && $notification_counts['notifications'] > 0): ?>
                                        <span class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full"></span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-xs font-medium"><?php echo $item['title']; ?></span>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- More Menu Overlay -->
        <div id="more-menu-overlay" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 lg:hidden" style="display: none;" onclick="closeMoreMenu()"></div>
        
        <!-- More Menu -->
        <div id="more-menu" class="fixed bottom-0 left-0 right-0 bg-white rounded-t-3xl shadow-2xl z-50 lg:hidden transform translate-y-full transition-transform duration-300">
            <div class="p-6">
                <!-- Menu Header -->
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900">More Options</h3>
                    <button onclick="closeMoreMenu()" class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-times text-gray-600"></i>
                    </button>
                </div>
                
                <!-- Menu Items -->
                <div class="space-y-6">
                    <?php global $more_menu_items; ?>
                    <?php foreach ($more_menu_items as $section_key => $section): ?>
                        <?php 
                        // Filter items based on user role
                        $visible_items = [];
                        foreach ($section['items'] as $key => $item) {
                            if (user_has_mobile_access($item, $current_user['role'])) {
                                $visible_items[$key] = $item;
                            }
                        }
                        ?>
                        
                        <?php if (!empty($visible_items)): ?>
                            <div>
                                <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">
                                    <?php echo $section['title']; ?>
                                </h4>
                                <div class="grid grid-cols-3 gap-4">
                                    <?php foreach ($visible_items as $key => $item): ?>
                                        <a href="<?php echo $item['url']; ?>" 
                                           class="flex flex-col items-center p-4 rounded-2xl hover:bg-gray-50 transition-colors <?php echo $current_page === $key ? 'bg-blue-50 text-blue-600' : 'text-gray-700'; ?>">
                                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center mb-2 <?php echo $current_page === $key ? 'shadow-lg' : ''; ?>">
                                                <i class="<?php echo $item['icon']; ?> text-white text-lg"></i>
                                            </div>
                                            <span class="text-xs font-medium text-center"><?php echo $item['title']; ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    
                    <!-- Logout Option -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Account</h4>
                        <div class="grid grid-cols-3 gap-4">
                            <a href="logout.php" class="flex flex-col items-center p-4 rounded-2xl hover:bg-red-50 text-red-600 transition-colors">
                                <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-red-600 rounded-2xl flex items-center justify-center mb-2">
                                    <i class="fas fa-sign-out-alt text-white text-lg"></i>
                                </div>
                                <span class="text-xs font-medium text-center">Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
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
            color: #3b82f6 !important;
            transform: translateY(-2px);
        }
        
        .mobile-tab-item:not(.active):active {
            transform: scale(0.95);
        }
        
        .mobile-tab-item:not(.active):hover {
            color: #6b7280;
        }

        #more-menu.show {
            transform: translateY(0);
        }

        .mobile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            backdrop-filter: blur(20px);
        }
    </style>
    
    <script>
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
        
        // Mobile tab animations
        document.querySelectorAll('.mobile-tab-item').forEach(tab => {
            tab.addEventListener('touchstart', function() {
                if (!this.classList.contains('active')) {
                    this.style.transform = 'scale(0.9)';
                }
            });
            
            tab.addEventListener('touchend', function() {
                if (!this.classList.contains('active')) {
                    this.style.transform = 'scale(1)';
                }
            });
        });
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Generate flash message display for mobile
 */
function render_mobile_flash_message($flash_message) {
    if (!$flash_message) return '';
    
    ob_start();
    ?>
    <div class="lg:hidden">
        <div class="mx-4 mt-4 p-4 rounded-2xl slide-up <?php echo $flash_message['type'] === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : ($flash_message['type'] === 'error' ? 'bg-red-100 text-red-800 border border-red-200' : 'bg-blue-100 text-blue-800 border border-blue-200'); ?>">
            <div class="flex items-center">
                <i class="fas fa-<?php echo $flash_message['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> mr-3 text-lg"></i>
                <span class="font-medium"><?php echo sanitize_output($flash_message['text']); ?></span>
            </div>
        </div>
    </div>
    
    <style>
        .slide-up {
            animation: slideUp 0.3s ease-out;
        }
        
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
    <?php
    return ob_get_clean();
}

/**
 * Generate breadcrumb for mobile (compact version)
 */
function render_mobile_breadcrumb($current_page) {
    $page_titles = [
        'index' => 'Dashboard',
        'lead_add' => 'Add Lead',
        'lead_edit' => 'Edit Lead',
        'search_leads' => 'Search',
        'analytics' => 'Analytics',
        'import_leads' => 'Import',
        'export' => 'Export',
        'running_ads' => 'Campaigns',
        'services' => 'Services',
        'users' => 'Users',
        'activity' => 'Activity'
    ];
    
    if ($current_page === 'index') return '';
    
    $current_title = $page_titles[$current_page] ?? 'Page';
    
    ob_start();
    ?>
    <div class="lg:hidden px-4 py-2 bg-gray-50">
        <div class="flex items-center text-sm">
            <a href="index.php" class="text-blue-600 font-medium">Home</a>
            <i class="fas fa-chevron-right mx-2 text-gray-400 text-xs"></i>
            <span class="text-gray-600"><?php echo $current_title; ?></span>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Generate complete mobile navigation system
 */
function render_complete_mobile_navigation($current_user, $current_page, $page_title = null, $show_breadcrumb = false) {
    global $mobile_nav_items;
    
    $output = '';
    
    // Mobile Header
    $output .= render_mobile_header($current_user, $page_title);
    
    // Breadcrumb (optional)
    if ($show_breadcrumb) {
        $output .= render_mobile_breadcrumb($current_page);
    }
    
    // Flash message placeholder (to be called separately)
    $output .= '<!-- Flash message will be inserted here -->';
    
    // Bottom Navigation
    $output .= render_mobile_bottom_nav($current_user, $current_page, $mobile_nav_items);
    
    return $output;
}

/**
 * Generate mobile-optimized card layout
 */
function render_mobile_card($title, $content, $extra_classes = '') {
    ob_start();
    ?>
    <div class="mobile-card rounded-3xl p-5 mb-4 <?php echo $extra_classes; ?>">
        <?php if ($title): ?>
            <h3 class="text-lg font-bold text-gray-900 mb-3"><?php echo $title; ?></h3>
        <?php endif; ?>
        <?php echo $content; ?>
    </div>
    
    <style>
        .mobile-card {
            background: linear-gradient(145deg, #ffffff, #f8fafc);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .mobile-card:active {
            transform: scale(0.98);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }
    </style>
    <?php
    return ob_get_clean();
}

/**
 * Generate mobile stats grid
 */
function render_mobile_stats($stats_array) {
    ob_start();
    ?>
    <div class="lg:hidden px-4 py-4">
        <div class="grid grid-cols-2 gap-4">
            <?php foreach ($stats_array as $stat): ?>
                <div class="mobile-card rounded-3xl p-5 ripple">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-3xl font-bold <?php echo $stat['color'] ?? 'text-blue-600'; ?>">
                                <?php echo $stat['value']; ?>
                            </p>
                            <p class="text-sm text-gray-600 font-medium"><?php echo $stat['label']; ?></p>
                        </div>
                        <div class="w-12 h-12 <?php echo $stat['bg_color'] ?? 'bg-blue-100'; ?> rounded-2xl flex items-center justify-center">
                            <i class="<?php echo $stat['icon']; ?> <?php echo $stat['icon_color'] ?? 'text-blue-500'; ?> text-xl"></i>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <style>
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
    </style>
    <?php
    return ob_get_clean();
}

/**
 * Generate mobile floating action button
 */
function render_mobile_fab($url, $icon = 'fas fa-plus', $tooltip = 'Add') {
    ob_start();
    ?>
    <div class="lg:hidden">
        <a href="<?php echo $url; ?>" class="floating-action" title="<?php echo $tooltip; ?>">
            <div class="w-14 h-14 bg-gradient-to-r from-blue-600 to-blue-700 rounded-2xl flex items-center justify-center shadow-xl ripple">
                <i class="<?php echo $icon; ?> text-white text-xl"></i>
            </div>
        </a>
    </div>
    
    <style>
        .floating-action {
            position: fixed;
            bottom: 80px;
            right: 20px;
            z-index: 40;
            box-shadow: 0 8px 32px rgba(59, 130, 246, 0.35);
        }
    </style>
    <?php
    return ob_get_clean();
}
?>