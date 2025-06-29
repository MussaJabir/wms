<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Set charset
    if (!$conn->set_charset("utf8mb4")) {
        throw new Exception("Error setting charset: " . $conn->error);
    }

    // Test connection with a simple query
    if (!$conn->query("SELECT 1")) {
        throw new Exception("Database connection test failed: " . $conn->error);
    }

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage() . "\nDebug Info:\n" .
        "Host: " . DB_HOST . "\n" .
        "User: " . DB_USER . "\n" .
        "Database: " . DB_NAME . "\n" .
        "PHP Version: " . PHP_VERSION . "\n" .
        "MySQL Extension: " . (extension_loaded('mysqli') ? "Loaded" : "Not loaded")
    );
}

// Get system statistics
function getSystemStats() {
    global $conn;
    
    $stats = [
        'total_clients' => 0,
        'total_collectors' => 0,
        'pending_requests' => 0,
        'total_revenue' => 0
    ];
    
    // Get total clients
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'client'");
    if ($row = $result->fetch_assoc()) {
        $stats['total_clients'] = $row['count'];
    }
    
    // Get total collectors
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'collector'");
    if ($row = $result->fetch_assoc()) {
        $stats['total_collectors'] = $row['count'];
    }
    
    // Get pending requests
    $result = $conn->query("SELECT COUNT(*) as count FROM requests WHERE status = 'pending'");
    if ($row = $result->fetch_assoc()) {
        $stats['pending_requests'] = $row['count'];
    }
    
    // Get total revenue
    $result = $conn->query("SELECT SUM(amount) as total FROM payments WHERE status = 'completed'");
    if ($row = $result->fetch_assoc()) {
        $stats['total_revenue'] = $row['total'] ?? 0;
    }
    
    return $stats;
}

// Get all pending requests
function getAllPendingRequests() {
    global $conn;
    
    $sql = "SELECT r.*, 
            c.name as client_name,
            col.name as collector_name
            FROM requests r
            LEFT JOIN users c ON r.client_id = c.id
            LEFT JOIN users col ON r.collector_id = col.id
            WHERE r.status = 'pending'
            ORDER BY r.created_at DESC";
            
    return $conn->query($sql);
}

// Get all collectors
function getAllCollectors() {
    global $conn;
    
    $sql = "SELECT id, name, email, phone FROM users WHERE role = 'collector' ORDER BY name";
    return $conn->query($sql);
}

// Assign collector to request
function assignCollector($request_id, $collector_id) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE requests SET collector_id = ?, status = 'assigned', updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ii", $collector_id, $request_id);
    
    return $stmt->execute();
}

// Create database tables if they don't exist
function createTables() {
    global $conn;
    
    $tables = [
        "users" => "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'collector', 'client') NOT NULL,
            phone VARCHAR(20),
            address TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        "zones" => "CREATE TABLE IF NOT EXISTS zones (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        "zone_collectors" => "CREATE TABLE IF NOT EXISTS zone_collectors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            zone_id INT NOT NULL,
            collector_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE CASCADE,
            FOREIGN KEY (collector_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_zone_collector (zone_id, collector_id)
        )",
        
        "requests" => "CREATE TABLE IF NOT EXISTS requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_id INT NOT NULL,
            collector_id INT,
            pickup_date DATE NOT NULL,
            location TEXT NOT NULL,
            status ENUM('pending', 'assigned', 'completed', 'cancelled') DEFAULT 'pending',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES users(id),
            FOREIGN KEY (collector_id) REFERENCES users(id)
        )",
        
        "payments" => "CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            request_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
            payment_date TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (request_id) REFERENCES requests(id)
        )"
    ];
    
    foreach ($tables as $name => $sql) {
        if (!$conn->query($sql)) {
            die("Error creating table $name: " . $conn->error);
        }
    }
}

// Get client requests
function getClientRequests($client_id) {
    global $conn;
    
    $sql = "SELECT r.*, 
            c.name as collector_name,
            p.status as payment_status,
            p.amount as payment_amount
            FROM requests r
            LEFT JOIN users c ON r.collector_id = c.id
            LEFT JOIN payments p ON r.id = p.request_id
            WHERE r.client_id = ?
            ORDER BY r.created_at DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    
    return $stmt->get_result();
}

// Get collector requests
function getCollectorRequests($collector_id) {
    global $conn;
    
    $sql = "SELECT r.*, 
            c.name as client_name,
            c.phone as client_phone,
            c.address as client_address
            FROM requests r
            JOIN users c ON r.client_id = c.id
            WHERE r.collector_id = ?
            ORDER BY r.pickup_date ASC, r.created_at DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $collector_id);
    $stmt->execute();
    
    return $stmt->get_result();
}

// Update request status
function updateRequestStatus($request_id, $status) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE requests SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $status, $request_id);
    
    return $stmt->execute();
}

// Create payment
function createPayment($client_id, $request_id, $amount) {
    global $conn;
    
    // Verify the request belongs to the client
    $stmt = $conn->prepare("SELECT id FROM requests WHERE id = ? AND client_id = ?");
    $stmt->bind_param("ii", $request_id, $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false; // Request doesn't belong to client
    }
    
    // Create payment record
    $stmt = $conn->prepare("INSERT INTO payments (request_id, amount, status, payment_date) VALUES (?, ?, 'pending', NOW())");
    $stmt->bind_param("id", $request_id, $amount);
    
    return $stmt->execute();
}

// Get client payments
function getClientPayments($client_id) {
    global $conn;
    
    $sql = "SELECT p.*, r.location, r.pickup_date, r.status as request_status
            FROM payments p
            JOIN requests r ON p.request_id = r.id
            WHERE r.client_id = ?
            ORDER BY p.created_at DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    
    return $stmt->get_result();
}

// Create new request
function createRequest($client_id, $location, $pickup_date, $notes = '') {
    global $conn;
    
    $stmt = $conn->prepare("
        INSERT INTO requests (client_id, location, pickup_date, notes)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("isss", $client_id, $location, $pickup_date, $notes);
    
    return $stmt->execute();
}

// Update client profile
function updateClientProfile($client_id, $name, $email, $phone, $address) {
    global $conn;
    
    $stmt = $conn->prepare("
        UPDATE users 
        SET name = ?, email = ?, phone = ?, address = ?, updated_at = NOW()
        WHERE id = ? AND role = 'client'
    ");
    $stmt->bind_param("ssssi", $name, $email, $phone, $address, $client_id);
    
    return $stmt->execute();
}

// Change password
function changePassword($user_id, $current_password, $new_password) {
    global $conn;
    
    // First verify current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (password_verify($current_password, $row['password'])) {
            // Hash new password and update
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            return $stmt->execute();
        }
    }
    
    return false;
}

// Get client statistics
function getClientStatistics($client_id) {
    global $conn;
    
    $stats = [
        'total_requests' => 0,
        'completed_requests' => 0,
        'pending_requests' => 0,
        'total_spent' => 0
    ];
    
    // Get total requests
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM requests WHERE client_id = ?");
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stats['total_requests'] = $row['count'];
    }
    
    // Get completed requests
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM requests WHERE client_id = ? AND status = 'completed'");
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stats['completed_requests'] = $row['count'];
    }
    
    // Get pending requests
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM requests WHERE client_id = ? AND status = 'pending'");
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stats['pending_requests'] = $row['count'];
    }
    
    // Get total spent
    $stmt = $conn->prepare("
        SELECT SUM(p.amount) as total 
        FROM payments p 
        JOIN requests r ON p.request_id = r.id 
        WHERE r.client_id = ? AND p.status = 'completed'
    ");
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stats['total_spent'] = $row['total'] ?? 0;
    }
    
    return $stats;
}

// Create new user
function createUser($name, $email, $password, $role, $phone = '', $address = '') {
    global $conn;
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return false; // Email already exists
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = $conn->prepare("
        INSERT INTO users (name, email, password, role, phone, address, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->bind_param("ssssss", $name, $email, $hashed_password, $role, $phone, $address);
    
    return $stmt->execute();
}

// Get all users
function getAllUsers() {
    global $conn;
    
    $sql = "SELECT id, name, email, role, phone, address, created_at FROM users ORDER BY created_at DESC";
    return $conn->query($sql);
}

// Get user by ID
function getUserById($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id, name, email, role, phone, address, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_assoc();
}

// Update user
function updateUser($user_id, $name, $email, $role, $phone = '', $address = '', $password = '') {
    global $conn;
    
    // Check if email already exists for other users
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return false; // Email already exists
    }
    
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ?, role = ?, phone = ?, address = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssssssi", $name, $email, $hashed_password, $role, $phone, $address, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ?, phone = ?, address = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("sssssi", $name, $email, $role, $phone, $address, $user_id);
    }
    
    return $stmt->execute();
}

// Delete user
function deleteUser($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    
    return $stmt->execute();
}

// Zone Management Functions

// Create new zone
function createZone($name, $description = '') {
    global $conn;
    
    $stmt = $conn->prepare("
        INSERT INTO zones (name, description, created_at, updated_at) 
        VALUES (?, ?, NOW(), NOW())
    ");
    
    if (!$stmt) {
        error_log("Zone creation - Prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("ss", $name, $description);
    
    if (!$stmt->execute()) {
        error_log("Zone creation - Execute failed: " . $stmt->error);
        return false;
    }
    
    return true;
}

// Get all zones
function getAllZones() {
    global $conn;
    
    $sql = "SELECT id, name, description, created_at FROM zones ORDER BY name ASC";
    return $conn->query($sql);
}

// Get zone by ID
function getZoneById($zone_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id, name, description, created_at FROM zones WHERE id = ?");
    $stmt->bind_param("i", $zone_id);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_assoc();
}

// Delete zone
function deleteZone($zone_id) {
    global $conn;
    
    // First delete zone_collectors associations
    $stmt = $conn->prepare("DELETE FROM zone_collectors WHERE zone_id = ?");
    if (!$stmt) {
        error_log("Zone deletion - Prepare failed for zone_collectors: " . $conn->error);
        return false;
    }
    $stmt->bind_param("i", $zone_id);
    if (!$stmt->execute()) {
        error_log("Zone deletion - Execute failed for zone_collectors: " . $stmt->error);
        return false;
    }
    
    // Then delete the zone
    $stmt = $conn->prepare("DELETE FROM zones WHERE id = ?");
    if (!$stmt) {
        error_log("Zone deletion - Prepare failed for zones: " . $conn->error);
        return false;
    }
    $stmt->bind_param("i", $zone_id);
    
    if (!$stmt->execute()) {
        error_log("Zone deletion - Execute failed for zones: " . $stmt->error);
        return false;
    }
    
    return true;
}

// Assign collector to zone
function assignCollectorToZone($zone_id, $collector_id) {
    global $conn;
    
    // Check if assignment already exists
    $stmt = $conn->prepare("SELECT id FROM zone_collectors WHERE zone_id = ? AND collector_id = ?");
    $stmt->bind_param("ii", $zone_id, $collector_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return false; // Already assigned
    }
    
    // Create assignment
    $stmt = $conn->prepare("
        INSERT INTO zone_collectors (zone_id, collector_id, created_at) 
        VALUES (?, ?, NOW())
    ");
    $stmt->bind_param("ii", $zone_id, $collector_id);
    
    return $stmt->execute();
}

// Remove collector from zone
function removeCollectorFromZone($zone_id, $collector_id) {
    global $conn;
    
    $stmt = $conn->prepare("DELETE FROM zone_collectors WHERE zone_id = ? AND collector_id = ?");
    $stmt->bind_param("ii", $zone_id, $collector_id);
    
    return $stmt->execute();
}

// Get collectors assigned to a zone
function getZoneCollectors($zone_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT u.id, u.name, u.email, u.phone, zc.created_at as assigned_at
        FROM zone_collectors zc
        JOIN users u ON zc.collector_id = u.id
        WHERE zc.zone_id = ?
        ORDER BY u.name
    ");
    $stmt->bind_param("i", $zone_id);
    $stmt->execute();
    
    return $stmt->get_result();
}

// Get zones assigned to a collector
function getCollectorZones($collector_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT z.id, z.name, z.description, zc.created_at as assigned_at
        FROM zone_collectors zc
        JOIN zones z ON zc.zone_id = z.id
        WHERE zc.collector_id = ?
        ORDER BY z.name
    ");
    $stmt->bind_param("i", $collector_id);
    $stmt->execute();
    
    return $stmt->get_result();
}

// Check if zone has pending requests
function getZonePendingRequests($zone_id) {
    global $conn;
    
    // Since the requests table doesn't have zone_id column, 
    // we'll check if there are any requests assigned to collectors in this zone
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM requests r
        JOIN zone_collectors zc ON r.collector_id = zc.collector_id
        WHERE zc.zone_id = ? AND r.status IN ('pending', 'assigned')
    ");
    $stmt->bind_param("i", $zone_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'];
}

// Get available collectors (not assigned to any zone)
function getAvailableCollectors() {
    global $conn;
    
    $sql = "
        SELECT u.id, u.name, u.email, u.phone
        FROM users u
        WHERE u.role = 'collector'
        AND u.id NOT IN (
            SELECT DISTINCT collector_id 
            FROM zone_collectors
        )
        ORDER BY u.name
    ";
    
    return $conn->query($sql);
}

// Initialize tables only once
if (!isset($GLOBALS['tables_initialized'])) {
    createTables();
    $GLOBALS['tables_initialized'] = true;
}
?> 