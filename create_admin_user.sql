-- Create Admin User for Waste Management System
-- Password: admin123 (hashed with PHP password_hash)

INSERT INTO users (name, email, password, role, phone, address, created_at, updated_at) 
VALUES (
    'System Administrator',
    'admin@wms.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: admin123
    'admin',
    '+1234567890',
    'System Address',
    NOW(),
    NOW()
);

-- Alternative: Create admin with different credentials
-- INSERT INTO users (name, email, password, role, phone, address, created_at, updated_at) 
-- VALUES (
--     'Admin User',
--     'admin@example.com',
--     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: admin123
--     'admin',
--     '+1234567890',
--     'Admin Address',
--     NOW(),
--     NOW()
-- ); 