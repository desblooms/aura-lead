<?php
/**
 * Database Connection File
 * Lead Management System
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'u345095192_aura');
define('DB_PASS', 'Aura@1212');
define('DB_NAME', 'u345095192_auralead');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8");

/**
 * Function to safely escape string for database
 * @param string $string
 * @return string
 */
function escape_string($string) {
    global $conn;
    return $conn->real_escape_string($string);
}

/**
 * Function to execute prepared statement
 * @param string $query
 * @param array $params
 * @param string $types
 * @return mysqli_result|bool
 */
function execute_query($query, $params = [], $types = '') {
    global $conn;
    
    if (empty($params)) {
        return $conn->query($query);
    }
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $result = $stmt->execute();
    
    if ($stmt->error) {
        die("Execute failed: " . $stmt->error);
    }
    
    return $stmt->get_result() ?: $result;
}

/**
 * Function to get single row
 * @param string $query
 * @param array $params
 * @param string $types
 * @return array|null
 */
function get_row($query, $params = [], $types = '') {
    $result = execute_query($query, $params, $types);
    return $result->fetch_assoc();
}

/**
 * Function to get all rows
 * @param string $query
 * @param array $params
 * @param string $types
 * @return array
 */
function get_all($query, $params = [], $types = '') {
    $result = execute_query($query, $params, $types);
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}
?>