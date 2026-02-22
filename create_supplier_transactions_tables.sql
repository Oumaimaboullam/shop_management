-- SQL to create tables for supplier transaction history and payments
-- Run this in your MySQL database

-- Add balance column to purchases table if it doesn't exist
ALTER TABLE `purchases` 
ADD COLUMN IF NOT EXISTS `balance` decimal(10,2) DEFAULT 0.00 COMMENT 'Remaining balance for purchase';

-- Add updated_at column to purchases table if missing
ALTER TABLE `purchases` 
ADD COLUMN IF NOT EXISTS `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Update existing purchases to calculate balance if it's NULL or 0
UPDATE `purchases` 
SET balance = total_amount - paid_amount 
WHERE balance IS NULL OR balance = 0;

-- Create view for supplier transaction summary
CREATE OR REPLACE VIEW `supplier_transaction_summary` AS
SELECT 
    s.id as supplier_id,
    s.name as supplier_name,
    s.phone as supplier_phone,
    s.balance as current_balance,
    COUNT(p.id) as total_transactions,
    COALESCE(SUM(p.total_amount), 0) as total_purchases,
    COALESCE(SUM(p.paid_amount), 0) as total_paid,
    COALESCE(SUM(p.balance), 0) as total_remaining,
    MAX(p.created_at) as last_transaction_date
FROM suppliers s
LEFT JOIN purchases p ON s.id = p.supplier_id
GROUP BY s.id, s.name, s.phone, s.balance;

-- Create supplier_payments table to track individual payments (optional, for detailed tracking)
CREATE TABLE IF NOT EXISTS `supplier_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `purchase_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `payment_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_mode_id` int(11) DEFAULT NULL,
  `payment_type` enum('initial_payment','remaining_payment','refund') NOT NULL DEFAULT 'initial_payment',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_purchase_id` (`purchase_id`),
  KEY `idx_supplier_id` (`supplier_id`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`purchase_id`) REFERENCES `purchases`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`payment_mode_id`) REFERENCES `payment_modes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
