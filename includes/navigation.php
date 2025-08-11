<?php
/**
 * Universal Navigation Include
 * Lead Management System
 * 
 * Include this file in all pages for consistent navigation
 */

// Make sure user is logged in
if (!is_logged_in()) {
    return;
}

$current_user = get_logged_in_user();
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Define navigation items based on roles
$nav_items = [
    'core' => [
        'title' => 'Core Features',
        'items' => [
            'index' => [
                'title' => 'Dashboard',
                'icon' => 'fas fa-home',
                'url' => 'index.php',
                'roles' => ['admin', 'sales', 'marketing']
            ],
            'lead_add' => [
                'title' => 'Add Lead',
                'icon' => 'fas fa-plus',
                'url' => 'lead_add.php',
                'roles' => ['admin', 'sales']
            ],
            'search_leads' => [
                'title' => 'Search Leads',
                'icon' => 'fas fa-search',
                'url' => 'search_leads.php',
                'roles' => ['admin', 'sales', 'marketing']
            ]
        ]
    ],
    'data' => [
        'title' => 'Data Management',
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
            ]
        ]
    ],
    'analytics' => [
        'title' => 'Analytics & Reports',
        'items' => [
            'analytics' => [
                'title' => 'Analytics Dashboard',
                'icon' => 'fas fa-chart-bar',
                'url' => 'analytics.php',
                'roles' => ['admin', 'marketing']
            ],
            'activity' => [
                'title' => 'Activity & Notifications',
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
                'title' => 'Running Campaigns',
                'icon' => 'fas fa-bullhorn',
                'url' => 'running_ads.php',
                'roles' => ['admin', 'marketing']
            ],
            'services' => [
                'title' => 'Services Management',
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
                'title' => 'User Management',
                'icon' => 'fas fa-users',
                'url' => 'users.php',
                'roles' => ['admin']
            ],
            'setup' => [
                'title' => 'System Setup',
                'icon' => 'fas fa-wrench',
                'url' => 'setup.php',
                'roles' => ['admin']
            ],
            'debug' => [
                'title' => 'System Debug',
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
function user_has_nav_access($nav_item, $user_role) {
    return in_array($user_role, $nav_item['roles']);
}

/**
 * Generate desktop navigation HTML
 */
function render_desktop_navigation($nav_items, $current_user, $current_page) {
    $html = '<div class="hidden lg:flex items-center space-x-1">';
    
    foreach ($nav_items as $section_key => $section) {
        $visible_items = [];
        
        // Filter items based on user role
        foreach ($section['items'] as $key => $item) {
            if (user_has_nav_access($item, $current_user['role'])) {
                $visible_items[$key] = $item;
            }
        }
        
        // Only show section if it has visible items
        if (!empty($visible_items)) {
            $section_class = get_section_class($section_key);
            $html .= '<div class="flex items-center space-x-1 px-3 py-2 bg-gray-50 rounded-lg">';
            
            foreach ($visible_items as $key => $item) {
                $active_class = ($current_page === $key) ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:text-blue-600 hover:bg-blue-50';
                $html .= '<a href="' . $item['url'] . '" class="nav-link px-3 py-2 ' . $active_class . ' rounded-md text-sm font-medium transition-colors">';
                $html .= '<i class="' . $item['icon'] . ' mr-1"></i>' . $item['title'];
                $html .= '</a>';
            }
            
            $html .= '</div>';
        }
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Generate mobile navigation HTML
 */
function render_mobile_navigation($nav_items, $current_user, $current_page) {
    $html = '<div id="mobile-menu" class="lg:hidden mobile-menu border-t border-gray-200" style="display: none;">';
    $html .= '<div class="px-4 py-4 space-y-3">';
    
    foreach ($nav_items as $section_key => $section) {
        $visible_items = [];
        
        // Filter items based on user role
        foreach ($section['items'] as $key => $item) {
            if (user_has_nav_access($item, $current_user['role'])) {
                $visible_items[$key] = $item;
            }
        }
        
        // Only show section if it has visible items
        if (!empty($visible_items)) {
            $html .= '<div class="space-y-2">';
            $html .= '<h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">' . $section['title'] . '</h3>';
            
            foreach ($visible_items as $key => $item) {
                $active_class = ($current_page === $key) ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50';
                $html .= '<a href="' . $item['url'] . '" class="flex items-center px-3 py-2 ' . $active_class . ' rounded-lg">';
                $html .= '<i class="' . $item['icon'] . ' w-5 mr-3"></i>' . $item['title'];
                $html .= '</a>';
            }
            
            $html .= '</div>';
        }
    }
    
    $html .= '</div></div>';
    return $html;
}

/**
 * Get section-specific styling class
 */
function get_section_class($section_key) {
    $classes = [
        'core' => 'bg-blue-50',
        'data' => 'bg-orange-50',
        'analytics' => 'bg-green-50',
        'marketing' => 'bg-purple-50',
        'admin' => 'bg-red-50'
    ];
    
    return $classes[$section_key] ?? 'bg-gray-50';
}

/**
 * Generate breadcrumb navigation
 */
function render_breadcrumb($current_page, $nav_items) {
    $html = '<nav class="flex" aria-label="Breadcrumb">';
    $html .= '<ol class="inline-flex items-center space-x-1 md:space-x-3">';
    $html .= '<li class="inline-flex items-center">';
    $html .= '<a href="index.php" class="text-gray-700 hover:text-gray-900">Dashboard</a>';
    $html .= '</li>';
    
    // Find current page in nav items
    $current_item = null;
    foreach ($nav_items as $section) {
        foreach ($section['items'] as $key => $item) {
            if ($key === $current_page) {
                $current_item = $item;
                break 2;
            }
        }
    }
    
    if ($current_item && $current_page !== 'index') {
        $html .= '<li>';
        $html .= '<div class="flex items-center">';
        $html .= '<svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">';
        $html .= '<path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>';
        $html .= '</svg>';
        $html .= '<span class="text-gray-500 ml-1 md:ml-2">' . $current_item['title'] . '</span>';
        $html .= '</div>';
        $html .= '</li>';
    }
    
    $html .= '</ol>';
    $html .= '</nav>';
    
    return $html;
}

/**
 * Generate complete navigation bar
 */
function render_complete_navigation($current_user, $current_page, $nav_items) {
    ob_start();
    ?>
    <nav class="bg-white/90 backdrop-blur-lg shadow-xl border-b border-white/20 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <!-- Logo and Brand -->
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-chart-line text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">Lead Management Pro</h1>
                        <p class="text-xs text-gray-500">Advanced CRM System</p>
                    </div>
                </div>

                <!-- Desktop Navigation -->
                <?php echo render_desktop_navigation($nav_items, $current_user, $current_page); ?>

                <!-- User Info and Actions -->
                <div class="flex items-center space-x-4">
                    <!-- User Info -->
                    <div class="hidden lg:flex items-center space-x-3 bg-gray-50 rounded-full px-4 py-2">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center">
                            <span class="text-white text-sm font-semibold">
                                <?php echo strtoupper(substr($current_user['full_name'], 0, 1)); ?>
                            </span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900"><?php echo sanitize_output($current_user['full_name']); ?></p>
                            <p class="text-xs text-blue-600"><?php echo ucfirst($current_user['role']); ?></p>
                        </div>
                    </div>
                    
                    <!-- Logout Button -->
                    <a href="logout.php" class="bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition-all shadow-lg hover:shadow-xl">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>

                    <!-- Mobile Menu Button -->
                    <button class="lg:hidden p-2 text-gray-600 hover:text-gray-900" onclick="toggleMobileMenu()">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation -->
        <?php echo render_mobile_navigation($nav_items, $current_user, $current_page); ?>
    </nav>

    <style>
        .nav-link {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .nav-link:hover::before {
            left: 100%;
        }
        
        .mobile-menu {
            backdrop-filter: blur(20px);
            background: rgba(255, 255, 255, 0.95);
        }
    </style>

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        }

        // Auto-hide mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('mobile-menu');
            const button = event.target.closest('button');
            
            if (menu && !menu.contains(event.target) && !button) {
                menu.style.display = 'none';
            }
        });
    </script>
    <?php
    return ob_get_clean();
}
?>

