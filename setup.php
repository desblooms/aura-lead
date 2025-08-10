<?php
/**
 * Setup and Fix Script for Lead Management System
 * Run this file to fix common issues and setup the database
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Lead Management System - Setup & Fix</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .warning{color:orange;} .info{color:blue;}</style>";

// Database configuration
$db_config = [
    'host' => 'localhost',
    'user' => 'u345095192_aura',
    'pass' => 'Aura@1212',
    'name' => 'u345095192_auralead'
];

try {
    // Connect to database
    $conn = new mysqli($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name']);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    echo "<p class='success'>✓ Database connected successfully</p>";
    
    // Check and create tables if they don't exist
    echo "<h2>Setting up database tables...</h2>";
    
    // Users table
    $users_table = "CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100),
        role ENUM('admin', 'sales', 'marketing') NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($users_table)) {
        echo "<p class='success'>✓ Users table ready</p>";
    } else {
        echo "<p class='error'>✗ Users table error: " . $conn->error . "</p>";
    }
    
    // Services table
    $services_table = "CREATE TABLE IF NOT EXISTS services (
        id INT PRIMARY KEY AUTO_INCREMENT,
        service_name VARCHAR(255) NOT NULL,
        service_category VARCHAR(100),
        description TEXT,
        is_active TINYINT(1) DEFAULT 1,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    if ($conn->query($services_table)) {
        echo "<p class='success'>✓ Services table ready</p>";
    } else {
        echo "<p class='error'>✗ Services table error: " . $conn->error . "</p>";
    }
    
    // Running ads table
    $ads_table = "CREATE TABLE IF NOT EXISTS running_ads (
        id INT PRIMARY KEY AUTO_INCREMENT,
        ad_name VARCHAR(255) NOT NULL,
        service_id INT,
        platform VARCHAR(100),
        budget DECIMAL(10,2) DEFAULT 0,
        start_date DATE,
        end_date DATE,
        target_audience TEXT,
        ad_copy TEXT,
        assigned_sales_member INT,
        is_active TINYINT(1) DEFAULT 1,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
        FOREIGN KEY (assigned_sales_member) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    if ($conn->query($ads_table)) {
        echo "<p class='success'>✓ Running ads table ready</p>";
    } else {
        echo "<p class='error'>✗ Running ads table error: " . $conn->error . "</p>";
    }
    
    // Check if leads table needs to be updated
    $leads_check = $conn->query("SHOW COLUMNS FROM leads LIKE 'source_ad_id'");
    if ($leads_check->num_rows == 0) {
        // Add new columns to leads table
        $alter_leads = [
            "ALTER TABLE leads ADD COLUMN source_ad_id INT",
            "ALTER TABLE leads ADD COLUMN lead_source VARCHAR(100) DEFAULT 'Manual'",
            "ALTER TABLE leads ADD FOREIGN KEY (source_ad_id) REFERENCES running_ads(id) ON DELETE SET NULL",
            "ALTER TABLE leads MODIFY follow_up DATE"
        ];
        
        foreach ($alter_leads as $query) {
            if ($conn->query($query)) {
                echo "<p class='success'>✓ Leads table updated</p>";
            } else {
                echo "<p class='warning'>⚠ Leads table update: " . $conn->error . "</p>";
            }
        }
    } else {
        echo "<p class='success'>✓ Leads table is up to date</p>";
    }
    
    // Insert default users if they don't exist
    echo "<h2>Setting up default users...</h2>";
    $default_password = password_hash('password', PASSWORD_DEFAULT);
    
    $users = [
        ['admin', $default_password, 'System Admin', 'admin'],
        ['john_sales', $default_password, 'John Sales', 'sales'],
        ['mary_marketing', $default_password, 'Mary Marketing', 'marketing']
    ];
    
    foreach ($users as $user) {
        $check_user = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check_user->bind_param("s", $user[0]);
        $check_user->execute();
        $result = $check_user->get_result();
        
        if ($result->num_rows == 0) {
            $insert_user = $conn->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
            $insert_user->bind_param("ssss", $user[0], $user[1], $user[2], $user[3]);
            if ($insert_user->execute()) {
                echo "<p class='success'>✓ Created user: {$user[0]}</p>";
            } else {
                echo "<p class='error'>✗ Failed to create user: {$user[0]}</p>";
            }
        } else {
            echo "<p class='info'>- User exists: {$user[0]}</p>";
        }
    }
    
    // Insert default services
    echo "<h2>Setting up default services...</h2>";
    $services = [
        ['Website Development', 'Development Services', 'Custom website development and design'],
        ['Digital Marketing', 'Digital Marketing', 'SEO, PPC, and social media marketing'],
        ['Logo Design', 'Design Services', 'Professional logo and brand identity design'],
        ['E-commerce Development', 'Development Services', 'Online store development and setup'],
        ['Content Marketing', 'Digital Marketing', 'Content creation and marketing strategy']
    ];
    
    foreach ($services as $service) {
        $check_service = $conn->prepare("SELECT id FROM services WHERE service_name = ?");
        $check_service->bind_param("s", $service[0]);
        $check_service->execute();
        $result = $check_service->get_result();
        
        if ($result->num_rows == 0) {
            $insert_service = $conn->prepare("INSERT INTO services (service_name, service_category, description, created_by) VALUES (?, ?, ?, 1)");
            $insert_service->bind_param("sss", $service[0], $service[1], $service[2]);
            if ($insert_service->execute()) {
                echo "<p class='success'>✓ Created service: {$service[0]}</p>";
            } else {
                echo "<p class='error'>✗ Failed to create service: {$service[0]}</p>";
            }
        } else {
            echo "<p class='info'>- Service exists: {$service[0]}</p>";
        }
    }
    
    echo "<h2>✅ Setup Complete!</h2>";
    echo "<div style='background:#f0f8ff;padding:15px;border-radius:5px;margin:20px 0;'>";
    echo "<h3>Login Credentials:</h3>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> username: admin / password: password</li>";
    echo "<li><strong>Sales:</strong> username: john_sales / password: password</li>";
    echo "<li><strong>Marketing:</strong> username: mary_marketing / password: password</li>";
    echo "</ul>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li>Delete this setup_fix.php file for security</li>";
    echo "<li>Go to <a href='login.php'>login.php</a> to start using the system</li>";
    echo "<li>Change the default passwords after first login</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Setup failed: " . $e->getMessage() . "</p>";
}

// File permissions check
echo "<h2>File Permissions Check</h2>";
$files_to_check = [
    'includes/connect.php',
    'includes/auth.php', 
    'includes/functions.php',
    'index.php',
    'login.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        if (is_readable($file)) {
            echo "<p class='success'>✓ {$file} is readable</p>";
        } else {
            echo "<p class='error'>✗ {$file} is not readable</p>";
        }
    } else {
        echo "<p class='warning'>⚠ {$file} does not exist</p>";
    }
}

echo "<hr>";
echo "<p><em>Setup completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>