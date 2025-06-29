-- Fix admin password with fresh hash
-- This will update the admin user's password to '123456' with a fresh hash

UPDATE users 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
WHERE email = 'admin@wms.com';

-- Verify the update
SELECT id, name, email, role, LEFT(password, 20) as password_preview 
FROM users 
WHERE email = 'admin@wms.com'; 