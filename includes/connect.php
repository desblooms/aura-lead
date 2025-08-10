<?php
/**
 * Database Connection File
 * Lead Management System
 * 
 * Improved version with better error handling
 */

// Prevent direct access
if (!defined('DB_INCLUDE_CHECK')) {
    define('DB_INCLUDE_CHECK', true);
}

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'u345095192_aura');
define('DB_PASS', 'Aura@1212');
define('DB_NAME', 'u345095192_auralead');

// Global connection variable
$conn = null;

try {
    // Create connection with error handling
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        // Log the error
        error_log("Database connection failed: " . $conn->connect_error);
        
        // Don't show sensitive info to users in production
        if (ini_get('display_errors')) {
            die("Database connection failed: " . $conn->connect_error);
        } else {
            die("Database connection error. Please contact administrator.");
        }
    }

    // Set charset to prevent character encoding issues
    if (!$conn->set_charset("utf8")) {
        error_log("Error loading character set utf8: " . $conn->error);
    }
    
} catch (Exception $e) {
    error_log("Database connection exception: " . $e->getMessage());
    die("Database configuration error. Please contact administrator.");
}

/**
 * Function to safely escape string for database
 * @param string $string
 * @return string
 */
function escape_string($string) {
    global $conn;
    if ($conn === null) {
        throw new Exception("Database connection not available");
    }
    return $conn->real_escape_string($string);
}

/**
 * Function to execute prepared statement with proper error handling
 * @param string $query
 * @param array $params
 * @param string $types
 * @return mysqli_result|bool
 */
function execute_query($query, $params = [], $types = '') {
    global $conn;
    
    if ($conn === null) {
        throw new Exception("Database connection not available");
    }
    
    try {
        // If no parameters, execute directly
        if (empty($params)) {
            $result = $conn->query($query);
            if ($result === false) {
                error_log("Query error: " . $conn->error . " | Query: " . $query);
                throw new Exception("Query execution failed");
            }
            return $result;
        }
        
        // Prepare statement
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error . " | Query: " . $query);
            throw new Exception("Query preparation failed");
        }
        
        // Bind parameters if provided
        if (!empty($params)) {
            if (empty($types)) {
                // Auto-detect types if not provided
                $types = str_repeat('s', count($params));
            }
            
            if (!$stmt->bind_param($types, ...$params)) {
                error_log("Bind param failed: " . $stmt->error);
                $stmt->close();
                throw new Exception("Parameter binding failed");
            }
        }
        
        // Execute statement
        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error . " | Query: " . $query);
            $stmt->close();
            throw new Exception("Query execution failed");
        }
        
        // Get result
        $result = $stmt->get_result();
        
        // For INSERT, UPDATE, DELETE operations
        if ($result === false) {
            $affected_rows = $stmt->affected_rows;
            $stmt->close();
            return $affected_rows > 0;
        }
        
        $stmt->close();
        return $result;
        
    } catch (Exception $e) {
        error_log("Database operation error: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Function to get single row with error handling
 * @param string $query
 * @param array $params
 * @param string $types
 * @return array|null
 */
function get_row($query, $params = [], $types = '') {
    try {
        $result = execute_query($query, $params, $types);
        
        if ($result === false || $result === true) {
            return null;
        }
        
        $row = $result->fetch_assoc();
        $result->free();
        
        return $row;
        
    } catch (Exception $e) {
        error_log("get_row error: " . $e->getMessage());
        return null;
    }
}

/**
 * Function to get all rows with error handling
 * @param string $query
 * @param array $params
 * @param string $types
 * @return array
 */
function get_all($query, $params = [], $types = '') {
    try {
        $result = execute_query($query, $params, $types);
        
        if ($result === false || $result === true) {
            return [];
        }
        
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();
        
        return $rows;
        
    } catch (Exception $e) {
        error_log("get_all error: " . $e->getMessage());
        return [];
    }
}

/**
 * Function to get last insert ID
 * @return int
 */
function get_last_insert_id() {
    global $conn;
    if ($conn === null) {
        return 0;
    }
    return $conn->insert_id;
}

/**
 * Function to close database connection
 */
function close_connection() {
    global $conn;
    if ($conn !== null) {
        $conn->close();
        $conn = null;
    }
}

// Optional: Register shutdown function to close connection
register_shutdown_function('close_connection');
?>