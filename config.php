<?php
/**
 * Configuration File
 * Lead Management System
 */

// Prevent direct access
if (!defined('CONFIG_LOADED')) {
    define('CONFIG_LOADED', true);
}

// Application Configuration
define('APP_NAME', 'Lead Management System');
define('APP_VERSION', '2.0.0');
define('APP_URL', ''); // Set your domain here

// Database Configuration (Update these with your actual credentials)
define('DB_HOST', 'localhost');
define('DB_USER', 'u345095192_aura');
define('DB_PASS', 'Aura@1212');
define('DB_NAME', 'u345095192_auralead');

// Security Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('CSRF_TOKEN_EXPIRY', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// File Upload Configuration
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['csv', 'xlsx', 'pdf']);
define('UPLOAD_PATH', 'uploads/');

// Pagination Configuration
define('ITEMS_PER_PAGE', 20);
define('MAX_ITEMS_PER_PAGE', 100);

// Email Configuration (for future features)
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('FROM_EMAIL', '');
define('FROM_NAME', APP_NAME);

// Date/Time Configuration
define('DEFAULT_TIMEZONE', 'UTC');
define('DATE_FORMAT', 'M j, Y');
define('DATETIME_FORMAT', 'M j, Y g:i A');

// Application Features (Enable/Disable)
define('ENABLE_ANALYTICS', true);
define('ENABLE_EXPORT', true);
define('ENABLE_EMAIL_NOTIFICATIONS', false);
define('ENABLE_ACTIVITY_LOGS', false);
define('ENABLE_FILE_UPLOADS', false);

// Development/Production Settings
define('ENVIRONMENT', 'production'); // 'development' or 'production'
define('DEBUG_MODE', false);
define('LOG_ERRORS', true);
define('DISPLAY_ERRORS', false);

// Apply environment settings
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
} else {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Set timezone
date_default_timezone_set(DEFAULT_TIMEZONE);

// Industry options for leads
$industry_options = [
    'Technology',
    'Healthcare', 
    'Finance',
    'Education',
    'Retail',
    'Manufacturing',
    'Real Estate',
    'Food & Beverage',
    'Energy',
    'Transportation',
    'Entertainment',
    'Non-profit',
    'Government',
    'Other'
];

// Lead status options
$status_options = [
    '' => 'Select Status',
    'Interested' => 'Interested',
    'Not Interested' => 'Not Interested', 
    'Budget Not Met' => 'Budget Not Met',
    'Meeting Scheduled' => 'Meeting Scheduled'
];

// Service categories
$service_categories = [
    'Digital Services',
    'Digital Marketing',
    'Design Services', 
    'Development Services',
    'Consulting Services',
    'Other'
];

// Ad platforms
$ad_platforms = [
    'Facebook',
    'Google Ads',
    'Instagram',
    'LinkedIn', 
    'Twitter',
    'YouTube',
    'TikTok',
    'Other'
];

// Lead sources
$lead_sources = [
    'Manual' => 'Manual Entry',
    'Facebook Ad' => 'Facebook Ad',
    'Google Ad' => 'Google Ad',
    'Instagram Ad' => 'Instagram Ad', 
    'LinkedIn Ad' => 'LinkedIn Ad',
    'Website Form' => 'Website Contact Form',
    'Phone Call' => 'Phone Call',
    'Email' => 'Email Inquiry',
    'Referral' => 'Referral',
    'Other' => 'Other'
];

// User roles and permissions
$role_permissions = [
    'admin' => [
        'view_all_leads', 'edit_leads', 'add_leads', 'delete_leads',
        'view_analytics', 'export_leads', 'manage_users', 'manage_services',
        'manage_ads', 'assign_leads'
    ],
    'sales' => [
        'edit_leads', 'add_leads', 'export_leads'
    ],
    'marketing' => [
        'view_all_leads', 'view_analytics', 'export_leads', 'manage_ads', 'view_ads'
    ]
];

// CSS Classes for status badges
$status_classes = [
    'Interested' => 'bg-green-100 text-green-800',
    'Not Interested' => 'bg-red-100 text-red-800',
    'Budget Not Met' => 'bg-yellow-100 text-yellow-800', 
    'Meeting Scheduled' => 'bg-blue-100 text-blue-800',
    '' => 'bg-gray-100 text-gray-800'
];

// Platform badge classes
$platform_classes = [
    'facebook' => 'bg-blue-100 text-blue-800',
    'google ads' => 'bg-green-100 text-green-800',
    'instagram' => 'bg-pink-100 text-pink-800',
    'linkedin' => 'bg-indigo-100 text-indigo-800',
    'twitter' => 'bg-cyan-100 text-cyan-800',
    'youtube' => 'bg-red-100 text-red-800'
];

/**
 * Get configuration value
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function get_config($key, $default = null) {
    global $industry_options, $status_options, $service_categories, 
           $ad_platforms, $lead_sources, $role_permissions,
           $status_classes, $platform_classes;
    
    $configs = [
        'industry_options' => $industry_options,
        'status_options' => $status_options,
        'service_categories' => $service_categories,
        'ad_platforms' => $ad_platforms,
        'lead_sources' => $lead_sources,
        'role_permissions' => $role_permissions,
        'status_classes' => $status_classes,
        'platform_classes' => $platform_classes
    ];
    
    return isset($configs[$key]) ? $configs[$key] : $default;
}

/**
 * Check if feature is enabled
 * @param string $feature
 * @return bool
 */
function feature_enabled($feature) {
    $features = [
        'analytics' => ENABLE_ANALYTICS,
        'export' => ENABLE_EXPORT,
        'email_notifications' => ENABLE_EMAIL_NOTIFICATIONS,
        'activity_logs' => ENABLE_ACTIVITY_LOGS,
        'file_uploads' => ENABLE_FILE_UPLOADS
    ];
    
    return isset($features[$feature]) ? $features[$feature] : false;
}
?>