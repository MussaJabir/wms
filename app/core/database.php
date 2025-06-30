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

// Get all active requests (pending and assigned) for admin dashboard
function getAllActiveRequests() {
    global $conn;
    
    $sql = "SELECT r.*, 
            c.name as client_name,
            c.email as client_email,
            c.phone as client_phone,
            col.name as collector_name,
            col.email as collector_email,
            col.phone as collector_phone
            FROM requests r
            LEFT JOIN users c ON r.client_id = c.id
            LEFT JOIN users col ON r.collector_id = col.id
            WHERE r.status IN ('pending', 'assigned')
            ORDER BY r.status ASC, r.created_at DESC";
            
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

// Get all pending payments
function getAllPendingPayments() {
    global $conn;
    
    $sql = "SELECT p.*, 
            r.location, 
            r.pickup_date,
            c.name as client_name,
            c.email as client_email,
            col.name as collector_name
            FROM payments p
            JOIN requests r ON p.request_id = r.id
            JOIN users c ON r.client_id = c.id
            LEFT JOIN users col ON r.collector_id = col.id
            WHERE p.status = 'pending'
            ORDER BY p.created_at DESC";
            
    return $conn->query($sql);
}

// Update payment status
function updatePaymentStatus($payment_id, $status) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE payments SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $status, $payment_id);
    
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
            price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
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

// Get client requests (show all requests except those fully paid and cancelled ones)
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
            AND r.status != 'cancelled'
            AND (
                r.status != 'completed' 
                OR p.status IS NULL 
                OR p.status != 'completed'
            )
            ORDER BY r.created_at DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    
    return $stmt->get_result();
}

// Get all client requests including fully completed ones (for history/admin purposes)
function getAllClientRequests($client_id) {
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

// Create payment with payment method details
function createPayment($client_id, $request_id, $amount, $payment_type, $payment_details = []) {
    global $conn;
    
    // Verify the request belongs to the client
    $stmt = $conn->prepare("SELECT id FROM requests WHERE id = ? AND client_id = ?");
    $stmt->bind_param("ii", $request_id, $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false; // Request doesn't belong to client
    }
    
    // Create phone payment record
    if ($payment_type === 'phone') {
        $phone_provider = $payment_details['phone_provider'] ?? null;
        $stmt = $conn->prepare("
            INSERT INTO payments (request_id, amount, payment_type, phone_provider, status, payment_date) 
            VALUES (?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->bind_param("idss", $request_id, $amount, $payment_type, $phone_provider);
        
        if ($stmt->execute()) {
            $payment_id = $conn->insert_id;
            
            // Send payment confirmation email
            require_once __DIR__ . '/email_service.php';
            
            // Get client details for email
            $client_stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
            $client_stmt->bind_param("i", $client_id);
            $client_stmt->execute();
            $client_result = $client_stmt->get_result();
            
            if ($client = $client_result->fetch_assoc()) {
                // Get request location
                $request_stmt = $conn->prepare("SELECT location FROM requests WHERE id = ?");
                $request_stmt->bind_param("i", $request_id);
                $request_stmt->execute();
                $request_result = $request_stmt->get_result();
                $request_data = $request_result->fetch_assoc();
                
                // Prepare email data
                $email_data = [
                    'provider' => $phone_provider,
                    'amount' => $amount,
                    'location' => $request_data['location'] ?? 'Not specified',
                    'payment_id' => $payment_id
                ];
                
                // Send email
                sendPaymentConfirmationEmail($client['email'], $client['name'], $email_data);
            }
            
            return true;
        }
        
        return false;
    } else {
        return false; // Invalid payment type
    }
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

// Create new request with automatic collector assignment
function createRequest($client_id, $location, $pickup_date, $notes = '') {
    global $conn;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First, find the zone by name (location)
        $stmt = $conn->prepare("SELECT id FROM zones WHERE name = ?");
        $stmt->bind_param("s", $location);
        $stmt->execute();
        $zone_result = $stmt->get_result();
        
        if ($zone_result->num_rows > 0) {
            $zone = $zone_result->fetch_assoc();
            $zone_id = $zone['id'];
            
            // Get collectors assigned to this zone
            $stmt = $conn->prepare("
                SELECT u.id FROM users u
                INNER JOIN zone_collectors zc ON u.id = zc.collector_id
                WHERE zc.zone_id = ? AND u.role = 'collector'
                ORDER BY RAND()
                LIMIT 1
            ");
            $stmt->bind_param("i", $zone_id);
            $stmt->execute();
            $collector_result = $stmt->get_result();
            
            if ($collector_result->num_rows > 0) {
                $collector = $collector_result->fetch_assoc();
                $collector_id = $collector['id'];
                
                // Create request with assigned collector
                $stmt = $conn->prepare("
                    INSERT INTO requests (client_id, collector_id, location, pickup_date, notes, status)
                    VALUES (?, ?, ?, ?, ?, 'assigned')
                ");
                $stmt->bind_param("iisss", $client_id, $collector_id, $location, $pickup_date, $notes);
                
                if ($stmt->execute()) {
                    $conn->commit();
                    return true;
                } else {
                    throw new Exception("Failed to create request");
                }
            } else {
                // No collectors assigned to this zone, create request as pending
                $stmt = $conn->prepare("
                    INSERT INTO requests (client_id, location, pickup_date, notes, status)
                    VALUES (?, ?, ?, ?, 'pending')
                ");
                $stmt->bind_param("isss", $client_id, $location, $pickup_date, $notes);
                
                if ($stmt->execute()) {
                    $conn->commit();
                    return true;
                } else {
                    throw new Exception("Failed to create request");
                }
            }
        } else {
            // Zone not found, create request as pending
            $stmt = $conn->prepare("
                INSERT INTO requests (client_id, location, pickup_date, notes, status)
                VALUES (?, ?, ?, ?, 'pending')
            ");
            $stmt->bind_param("isss", $client_id, $location, $pickup_date, $notes);
            
            if ($stmt->execute()) {
                $conn->commit();
                return true;
            } else {
                throw new Exception("Failed to create request");
            }
        }
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error creating request: " . $e->getMessage());
        return false;
    }
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
function createZone($name, $description = '', $price = 0.00) {
    global $conn;
    
    $stmt = $conn->prepare("
        INSERT INTO zones (name, description, price, created_at, updated_at) 
        VALUES (?, ?, ?, NOW(), NOW())
    ");
    
    if (!$stmt) {
        error_log("Zone creation - Prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("ssd", $name, $description, $price);
    
    if (!$stmt->execute()) {
        error_log("Zone creation - Execute failed: " . $stmt->error);
        return false;
    }
    
    return true;
}

// Get all zones
function getAllZones() {
    global $conn;
    
    $sql = "SELECT id, name, description, price, created_at FROM zones ORDER BY name ASC";
    return $conn->query($sql);
}

// Get zone by ID
function getZoneById($zone_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id, name, description, price, created_at FROM zones WHERE id = ?");
    $stmt->bind_param("i", $zone_id);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_assoc();
}

// Check if zone name exists (excluding specific ID)
function zoneNameExists($name, $exclude_id = 0) {
    global $conn;
    
    if ($exclude_id > 0) {
        $stmt = $conn->prepare("SELECT id FROM zones WHERE LOWER(name) = LOWER(?) AND id != ?");
        $stmt->bind_param("si", $name, $exclude_id);
    } else {
        $stmt = $conn->prepare("SELECT id FROM zones WHERE LOWER(name) = LOWER(?)");
        $stmt->bind_param("s", $name);
    }
    
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

// Update zone
function updateZone($zone_id, $name, $description = '', $price = 0.00) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE zones SET name = ?, description = ?, price = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssdi", $name, $description, $price, $zone_id);
    
    return $stmt->execute();
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

// Assign multiple collectors to a zone (replaces existing assignments)
function assignMultipleCollectorsToZone($zone_id, $collector_ids) {
    global $conn;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Remove all existing assignments for this zone
        $stmt = $conn->prepare("DELETE FROM zone_collectors WHERE zone_id = ?");
        $stmt->bind_param("i", $zone_id);
        $stmt->execute();
        
        // Add new assignments
        $success_count = 0;
        foreach ($collector_ids as $collector_id) {
            $collector_id = (int)$collector_id;
            
            // Check if collector exists and is a collector
            $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'collector'");
            $stmt->bind_param("i", $collector_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                // Create assignment
                $stmt = $conn->prepare("
                    INSERT INTO zone_collectors (zone_id, collector_id, created_at) 
                    VALUES (?, ?, NOW())
                ");
                $stmt->bind_param("ii", $zone_id, $collector_id);
                if ($stmt->execute()) {
                    $success_count++;
                }
            }
        }
        
        $conn->commit();
        return $success_count;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Multi-collector assignment error: " . $e->getMessage());
        return false;
    }
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

// REPORTING FUNCTIONS

// Get overall request statistics
function getRequestStats() {
    global $conn;
    
    $stats = [];
    
    // Total requests
    $result = $conn->query("SELECT COUNT(*) as total FROM requests");
    $stats['total_requests'] = $result->fetch_assoc()['total'];
    
    // Completed requests
    $result = $conn->query("SELECT COUNT(*) as completed FROM requests WHERE status = 'completed'");
    $stats['completed_requests'] = $result->fetch_assoc()['completed'];
    
    // Pending requests
    $result = $conn->query("SELECT COUNT(*) as pending FROM requests WHERE status = 'pending'");
    $stats['pending_requests'] = $result->fetch_assoc()['pending'];
    
    // Cancelled requests
    $result = $conn->query("SELECT COUNT(*) as cancelled FROM requests WHERE status = 'cancelled'");
    $stats['cancelled_requests'] = $result->fetch_assoc()['cancelled'];
    
    return $stats;
}

// Get payment statistics
function getPaymentStats() {
    global $conn;
    
    $stats = [];
    
    // Total payments
    $result = $conn->query("SELECT COUNT(*) as total FROM payments WHERE status != 'failed'");
    $stats['total_payments'] = $result->fetch_assoc()['total'];
    
    // Total revenue
    $result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'completed'");
    $stats['total_revenue'] = $result->fetch_assoc()['total'];
    
    // Average payment
    $result = $conn->query("SELECT COALESCE(AVG(amount), 0) as average FROM payments WHERE status = 'completed'");
    $stats['average_payment'] = $result->fetch_assoc()['average'];
    
    // Collected revenue (same as total for now)
    $stats['collected_revenue'] = $stats['total_revenue'];
    
    return $stats;
}

// Get top performing collectors
function getTopCollectors($limit = 5) {
    global $conn;
    
    $sql = "
        SELECT 
            u.name as collector_name,
            COUNT(r.id) as total_collections,
            COALESCE(SUM(p.amount), 0) as total_revenue
        FROM users u
        LEFT JOIN requests r ON u.id = r.collector_id AND r.status = 'completed'
        LEFT JOIN payments p ON r.id = p.request_id AND p.status = 'completed'
        WHERE u.role = 'collector'
        GROUP BY u.id, u.name
        ORDER BY total_collections DESC, total_revenue DESC
        LIMIT ?
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get zone performance statistics
function getZoneStats() {
    global $conn;
    
    $sql = "
        SELECT 
            z.name as zone_name,
            COUNT(r.id) as total_requests,
            COUNT(CASE WHEN r.status = 'completed' THEN 1 END) as completed_requests,
            COALESCE(SUM(CASE WHEN p.status = 'completed' THEN p.amount ELSE 0 END), 0) as total_revenue
        FROM zones z
        LEFT JOIN requests r ON z.name = r.location
        LEFT JOIN payments p ON r.id = p.request_id
        GROUP BY z.id, z.name
        ORDER BY total_requests DESC
    ";
    
    return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

// Get revenue trend over time (daily)
function getRevenueTrend($days = 7) {
    global $conn;
    
    $sql = "
        SELECT 
            DATE(p.created_at) as date,
            COALESCE(SUM(p.amount), 0) as total_revenue
        FROM payments p
        WHERE p.status = 'completed' 
        AND p.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        GROUP BY DATE(p.created_at)
        ORDER BY date ASC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $days);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get monthly revenue trend
function getMonthlyRevenueTrend($months = 6) {
    global $conn;
    
    $sql = "
        SELECT 
            DATE_FORMAT(p.created_at, '%Y-%m') as month,
            COALESCE(SUM(p.amount), 0) as total_revenue
        FROM payments p
        WHERE p.status = 'completed' 
        AND p.created_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
        GROUP BY DATE_FORMAT(p.created_at, '%Y-%m')
        ORDER BY month ASC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $months);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get recent activities for dashboard
function getRecentActivities($limit = 10) {
    global $conn;
    
    $sql = "
        (SELECT 
            'request' as type,
            CONCAT('New waste collection request from ', u.name) as activity,
            r.created_at as activity_time
        FROM requests r
        JOIN users u ON r.client_id = u.id
        ORDER BY r.created_at DESC
        LIMIT ?)
        UNION ALL
        (SELECT 
            'payment' as type,
            CONCAT('Payment of ₱', FORMAT(p.amount, 2), ' received') as activity,
            p.created_at as activity_time
        FROM payments p
        WHERE p.status = 'completed'
        ORDER BY p.created_at DESC
        LIMIT ?)
        ORDER BY activity_time DESC
        LIMIT ?
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $limit, $limit, $limit);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get customer satisfaction rating (based on completed requests)
function getCustomerSatisfactionStats() {
    global $conn;
    
    // For now, we'll calculate a basic satisfaction based on completion rate
    // In the future, you can add a ratings table for actual customer feedback
    $result = $conn->query("
        SELECT 
            COUNT(*) as total_requests,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_requests,
            COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_requests
        FROM requests
    ");
    
    $stats = $result->fetch_assoc();
    
    if ($stats['total_requests'] > 0) {
        $completion_rate = $stats['completed_requests'] / $stats['total_requests'];
        $cancellation_rate = $stats['cancelled_requests'] / $stats['total_requests'];
        
        // Calculate satisfaction score (4.0-5.0 based on completion rate)
        $satisfaction_score = 4.0 + ($completion_rate * 1.0) - ($cancellation_rate * 0.5);
        $satisfaction_score = max(1.0, min(5.0, $satisfaction_score)); // Clamp between 1-5
    } else {
        $satisfaction_score = 4.5; // Default score
    }
    
    return [
        'score' => round($satisfaction_score, 1),
        'total_reviews' => $stats['completed_requests']
    ];
}

// Initialize tables only once
if (!isset($GLOBALS['tables_initialized'])) {
    createTables();
    $GLOBALS['tables_initialized'] = true;
}
?> 