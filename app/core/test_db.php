<?php
// Include configuration
require_once dirname(__DIR__) . '/config/config.php';

// Test database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "Database connection successful!\n";
    echo "Server info: " . $conn->server_info . "\n";
    echo "Host info: " . $conn->host_info . "\n";
    
    // Test if database exists
    $result = $conn->query("SELECT DATABASE()");
    $row = $result->fetch_row();
    echo "Current database: " . $row[0] . "\n";
    
    // Test if tables exist
    $tables = ['users', 'zones', 'zone_collectors', 'requests', 'payments'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        echo "Table '$table' exists: " . ($result->num_rows > 0 ? "Yes" : "No") . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    
    // Additional debugging information
    echo "\nDebug Information:\n";
    echo "DB_HOST: " . DB_HOST . "\n";
    echo "DB_USER: " . DB_USER . "\n";
    echo "DB_NAME: " . DB_NAME . "\n";
    echo "PHP Version: " . PHP_VERSION . "\n";
    echo "MySQL Extension: " . (extension_loaded('mysqli') ? "Loaded" : "Not loaded") . "\n";
} 