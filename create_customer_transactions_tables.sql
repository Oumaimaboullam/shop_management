-- SQL to create tables for customer transaction history and payments
-- Run this in your MySQL database

-- Create customer_payments table to track individual payments
CREATE TABLE IF NOT EXISTS `customer_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `payment_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_mode_id` int(11) DEFAULT NULL,
  `payment_type` enum('initial_payment','remaining_payment','refund') NOT NULL DEFAULT 'initial_payment',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sale_id` (`sale_id`),
  KEY `idx_client_id` (`client_id`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`sale_id`) REFERENCES `sales`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`payment_mode_id`) REFERENCES `payment_modes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add balance column to clients table if it doesn't exist
ALTER TABLE `clients` 
ADD COLUMN IF NOT EXISTS `balance` decimal(10,2) DEFAULT 0.00 COMMENT 'Customer account balance (positive = credit, negative = debt)';

-- Update sales table to ensure it has advance_payment column if missing
ALTER TABLE `sales` 
ADD COLUMN IF NOT EXISTS `advance_payment` decimal(10,2) DEFAULT 0.00 COMMENT 'Amount paid in advance by customer';

-- Add updated_at column to sales table if missing
ALTER TABLE `sales` 
ADD COLUMN IF NOT EXISTS `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Create view for customer transaction summary
CREATE OR REPLACE VIEW `customer_transaction_summary` AS
SELECT 
    c.id as client_id,
    c.name as client_name,
    c.phone as client_phone,
    c.balance as current_balance,
    COUNT(s.id) as total_transactions,
    COALESCE(SUM(s.total_amount), 0) as total_sales,
    COALESCE(SUM(s.advance_payment), 0) as total_advance_paid,
    COALESCE(SUM(s.total_amount - s.advance_payment), 0) as total_remaining,
    MAX(s.created_at) as last_transaction_date
FROM clients c
LEFT JOIN sales s ON c.id = s.client_id
GROUP BY c.id, c.name, c.phone, c.balance;

-- Insert some sample data if tables are empty (optional)
-- INSERT IGNORE INTO customer_payments (sale_id, client_id, payment_amount, payment_type, notes) 
-- SELECT s.id, s.client_id, s.advance_payment, 'initial_payment', 
--        CONCAT('Initial payment for sale #', s.id)
-- FROM sales s 
-- WHERE s.client_id IS NOT NULL 
-- AND s.advance_payment > 0 
-- LIMIT 5;
