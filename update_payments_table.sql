-- Update payments table to add missing fields
USE waste_management;

-- Add method and transaction_id fields to payments table
ALTER TABLE payments 
ADD COLUMN method VARCHAR(50) DEFAULT 'cash' AFTER amount,
ADD COLUMN transaction_id VARCHAR(100) DEFAULT NULL AFTER payment_date;

-- Update existing payments to have a default method
UPDATE payments SET method = 'cash' WHERE method IS NULL; 