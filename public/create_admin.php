<?php
// Include database configuration
require_once '../app/config/config.php';
require_once '../app/core/database.php';
require_once '../app/core/functions.php';

// Start session
session_start();

// Check if admin already exists
$check_admin = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");

if ($check_admin->num_rows > 0) {
    echo "<h2>❌ Admin user already exists!</h2>";
    echo "<p>An admin user is already present in the database.</p>";
    echo "<p><a href='index.php'>Go to Login</a></p>";
    exit();
}

// Admin user details
$admin_data = [
    'name' => 'System Administrator',
    'email' => 'admin@wms.com',
    'password' => 'admin123', // This will be hashed automatically
    'role' => 'admin',
    'phone' => '+1234567890',
    'address' => 'System Address'
];

try {
    // Hash the password
    $hashed_password = password_hash($admin_data['password'], PASSWORD_DEFAULT);
    
    // Prepare and execute the insert query
    $stmt = $conn->prepare("
        INSERT INTO users (name, email, password, role, phone, address, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    $stmt->bind_param("ssssss", 
        $admin_data['name'],
        $admin_data['email'],
        $hashed_password,
        $admin_data['role'],
        $admin_data['phone'],
        $admin_data['address']
    );
    
    if ($stmt->execute()) {
        echo "<h2>✅ Admin user created successfully!</h2>";
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>Login Credentials:</h3>";
        echo "<p><strong>Email:</strong> " . htmlspecialchars($admin_data['email']) . "</p>";
        echo "<p><strong>Password:</strong> " . htmlspecialchars($admin_data['password']) . "</p>";
        echo "</div>";
        echo "<p><strong>Important:</strong> Please delete this file after creating the admin user for security reasons.</p>";
        echo "<p><a href='index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login</a></p>";
    } else {
        echo "<h2>❌ Error creating admin user!</h2>";
        echo "<p>Error: " . $stmt->error . "</p>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Database Error!</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin User - WMS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        h2 {
            color: #333;
            text-align: center;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        a {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        a:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <h1>🚀 Waste Management System</h1>
    <h2>Admin User Creation</h2>
    
    <div class="warning">
        <strong>⚠️ Security Notice:</strong> This file should be deleted after creating the admin user.
    </div>
    
    <div class="success">
        <h3>✅ Admin user created successfully!</h3>
        <p><strong>Email:</strong> admin@wms.com</p>
        <p><strong>Password:</strong> admin123</p>
        <p><strong>Role:</strong> Administrator</p>
    </div>
    
    <p><a href="index.php">Go to Login Page</a></p>
    
    <div class="warning">
        <strong>Remember:</strong> Delete this file (create_admin.php) after creating the admin user for security!
    </div>
</body>
</html> 