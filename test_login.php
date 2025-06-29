<?php
// Test script to debug login issues
require_once 'app/config/config.php';
require_once 'app/core/database.php';

echo "<h2>WMS Login Debug Test</h2>";

// Test database connection
echo "<h3>1. Database Connection Test</h3>";
try {
    $test_conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($test_conn->connect_error) {
        echo "❌ Database connection failed: " . $test_conn->connect_error;
    } else {
        echo "✅ Database connection successful<br>";
        echo "Host: " . DB_HOST . "<br>";
        echo "Database: " . DB_NAME . "<br>";
        echo "User: " . DB_USER . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage();
}

// Test user lookup
echo "<h3>2. User Lookup Test</h3>";
$email = 'admin@wms.com';
$stmt = $test_conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    echo "✅ User found: " . $user['name'] . "<br>";
    echo "Email: " . $user['email'] . "<br>";
    echo "Role: " . $user['role'] . "<br>";
    echo "Password hash: " . substr($user['password'], 0, 20) . "...<br>";
    
    // Test password verification
    echo "<h3>3. Password Verification Test</h3>";
    $test_password = '123456';
    $hash_from_db = $user['password'];
    
    echo "Testing password: '$test_password'<br>";
    echo "Hash from DB: " . substr($hash_from_db, 0, 20) . "...<br>";
    
    if (password_verify($test_password, $hash_from_db)) {
        echo "✅ Password verification successful!<br>";
    } else {
        echo "❌ Password verification failed!<br>";
        
        // Let's create a new hash for comparison
        $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
        echo "New hash for '$test_password': " . $new_hash . "<br>";
        
        if (password_verify($test_password, $new_hash)) {
            echo "✅ New hash verification works!<br>";
        } else {
            echo "❌ New hash verification also failed!<br>";
        }
    }
    
    // Show all users in database
    echo "<h3>4. All Users in Database</h3>";
    $all_users = $test_conn->query("SELECT id, name, email, role FROM users");
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
    while ($row = $all_users->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . $row['role'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} else {
    echo "❌ User not found for email: $email<br>";
    
    // Show all users in database
    echo "<h3>All Users in Database</h3>";
    $all_users = $test_conn->query("SELECT id, name, email, role FROM users");
    if ($all_users && $all_users->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
        while ($row = $all_users->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['name'] . "</td>";
            echo "<td>" . $row['email'] . "</td>";
            echo "<td>" . $row['role'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ No users found in database!";
    }
}

$test_conn->close();
?> 