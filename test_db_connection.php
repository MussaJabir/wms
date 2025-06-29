<?php
// Simple database connection test
require_once 'app/config/config.php';

echo "<h2>Database Connection Test</h2>";

try {
    // Test database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        echo "❌ Connection failed: " . $conn->connect_error;
    } else {
        echo "✅ Database connected successfully!<br>";
        echo "Host: " . DB_HOST . "<br>";
        echo "Database: " . DB_NAME . "<br>";
        echo "User: " . DB_USER . "<br><br>";
        
        // Test users table
        $result = $conn->query("SELECT COUNT(*) as count FROM users");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "✅ Users table exists with " . $row['count'] . " users<br><br>";
            
            // Show all users
            $users = $conn->query("SELECT id, name, email, role FROM users");
            echo "<h3>All Users in Database:</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
            while ($user = $users->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $user['id'] . "</td>";
                echo "<td>" . $user['name'] . "</td>";
                echo "<td>" . $user['email'] . "</td>";
                echo "<td>" . $user['role'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Test specific user lookup
            echo "<h3>Testing User Lookup:</h3>";
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $email = 'admin@wms.com';
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($user = $result->fetch_assoc()) {
                echo "✅ User found: " . $user['name'] . "<br>";
                echo "Email: " . $user['email'] . "<br>";
                echo "Role: " . $user['role'] . "<br>";
                echo "Password hash: " . substr($user['password'], 0, 20) . "...<br>";
                
                // Test password verification
                $test_password = '123456';
                if (password_verify($test_password, $user['password'])) {
                    echo "✅ Password verification successful!<br>";
                } else {
                    echo "❌ Password verification failed!<br>";
                }
            } else {
                echo "❌ User not found for email: $email<br>";
            }
            
        } else {
            echo "❌ Error querying users table: " . $conn->error;
        }
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?> 