<?php
/**
 * Debug Script for Lead Management System
 * Upload this file to your server and run it to identify issues
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>Lead Management System - Debug Report</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .warning{color:orange;} .info{color:blue;}</style>";

// Test 1: PHP Version
echo "<h2>1. PHP Version Check</h2>";
$php_version = phpversion();
echo "<p class='info'>PHP Version: $php_version</p>";
if (version_compare($php_version, '7.4', '>=')) {
    echo "<p class='success'>✓ PHP version is compatible</p>";
} else {
    echo "<p class='error'>✗ PHP version might be too old</p>";
}

// Test 2: Required Extensions
echo "<h2>2. Required PHP Extensions</h2>";
$required_extensions = ['mysqli', 'session', 'filter'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p class='success'>✓ $ext extension is loaded</p>";
    } else {
        echo "<p class='error'>✗ $ext extension is missing</p>";
    }
}

// Test 3: File System Check
echo "<h2>3. File System Check</h2>";
$required_files = [
    'includes/connect.php',
    'includes/auth.php', 
    'includes/functions.php',
    'index.php',
    'login.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "<p class='success'>✓ $file exists</p>";
        // Check if file is readable
        if (is_readable($file)) {
            echo "<p class='info'>  - File is readable</p>";
        } else {
            echo "<p class='error'>  - File is not readable (check permissions)</p>";
        }
    } else {
        echo "<p class='error'>✗ $file is missing</p>";
    }
}

// Test 4: Directory Permissions
echo "<h2>4. Directory Permissions</h2>";
$dirs_to_check = ['.', 'includes'];
foreach ($dirs_to_check as $dir) {
    if (is_dir($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        echo "<p class='info'>$dir permissions: $perms</p>";
        if (is_writable($dir)) {
            echo "<p class='success'>✓ $dir is writable</p>";
        } else {
            echo "<p class='warning'>⚠ $dir is not writable</p>";
        }
    }
}

// Test 5: Database Connection
echo "<h2>5. Database Connection Test</h2>";
$db_config = [
    'host' => 'localhost',
    'user' => 'u345095192_aura',
    'pass' => 'Aura@1212',
    'name' => 'u345095192_auralead'
];

try {
    echo "<p class='info'>Attempting to connect to database...</p>";
    $conn = new mysqli($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name']);
    
    if ($conn->connect_error) {
        echo "<p class='error'>✗ Database connection failed: " . $conn->connect_error . "</p>";
    } else {
        echo "<p class='success'>✓ Database connected successfully</p>";
        
        // Test 6: Check if tables exist
        echo "<h2>6. Database Tables Check</h2>";
        $required_tables = ['users', 'leads'];
        foreach ($required_tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result && $result->num_rows > 0) {
                echo "<p class='success'>✓ Table '$table' exists</p>";
                
                // Count records
                $count_result = $conn->query("SELECT COUNT(*) as count FROM $table");
                if ($count_result) {
                    $count = $count_result->fetch_assoc()['count'];
                    echo "<p class='info'>  - Records in $table: $count</p>";
                }
            } else {
                echo "<p class='error'>✗ Table '$table' does not exist</p>";
            }
        }
        
        $conn->close();
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Database connection error: " . $e->getMessage() . "</p>";
}

// Test 7: Session Test
echo "<h2>7. Session Test</h2>";
if (session_start()) {
    echo "<p class='success'>✓ Sessions are working</p>";
    $_SESSION['test'] = 'working';
    if (isset($_SESSION['test'])) {
        echo "<p class='success'>✓ Session variables can be set and read</p>";
        unset($_SESSION['test']);
    }
} else {
    echo "<p class='error'>✗ Session start failed</p>";
}

// Test 8: Include Files Test
echo "<h2>8. Include Files Syntax Test</h2>";
$include_files = ['includes/connect.php', 'includes/functions.php', 'includes/auth.php'];

foreach ($include_files as $file) {
    if (file_exists($file)) {
        echo "<p class='info'>Testing $file...</p>";
        
        // Check for syntax errors using php -l equivalent
        $content = file_get_contents($file);
        if ($content !== false) {
            // Basic syntax checks
            $open_tags = substr_count($content, '<?php');
            $close_tags = substr_count($content, '?>');
            
            if ($open_tags > 0) {
                echo "<p class='success'>  ✓ PHP opening tags found</p>";
            }
            
            // Check for common syntax issues
            if (strpos($content, 'function ') !== false) {
                echo "<p class='success'>  ✓ Contains function definitions</p>";
            }
            
            // Try to include the file
            try {
                ob_start();
                include_once $file;
                $output = ob_get_clean();
                echo "<p class='success'>  ✓ File included successfully</p>";
            } catch (ParseError $e) {
                echo "<p class='error'>  ✗ Parse error in $file: " . $e->getMessage() . "</p>";
            } catch (Error $e) {
                echo "<p class='error'>  ✗ Error in $file: " . $e->getMessage() . "</p>";
            } catch (Exception $e) {
                echo "<p class='warning'>  ⚠ Exception in $file: " . $e->getMessage() . "</p>";
            }
        }
    }
}

// Test 9: Server Environment
echo "<h2>9. Server Environment</h2>";
echo "<p class='info'>Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";
echo "<p class='info'>Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</p>";
echo "<p class='info'>Current Directory: " . getcwd() . "</p>";
echo "<p class='info'>Script Path: " . __FILE__ . "</p>";

// Test 10: Memory and Execution
echo "<h2>10. PHP Configuration</h2>";
echo "<p class='info'>Memory Limit: " . ini_get('memory_limit') . "</p>";
echo "<p class='info'>Max Execution Time: " . ini_get('max_execution_time') . "</p>";
echo "<p class='info'>Display Errors: " . (ini_get('display_errors') ? 'On' : 'Off') . "</p>";
echo "<p class='info'>Log Errors: " . (ini_get('log_errors') ? 'On' : 'Off') . "</p>";

echo "<h2>Summary</h2>";
echo "<p>If you see any red ✗ items above, those need to be fixed first.</p>";
echo "<p>If everything shows green ✓, the issue might be in the specific page you're trying to access.</p>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ul>";
echo "<li>Fix any red errors shown above</li>";
echo "<li>If database connection fails, check your database credentials</li>";
echo "<li>If tables don't exist, import the database schema from db.sql</li>";
echo "<li>Check your web server error logs for more specific error messages</li>";
echo "</ul>";

echo "<hr>";
echo "<p><em>Debug script completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>