-- SQL to add missing columns for analytics
-- Run this in your MySQL database

-- Add advance_payment column to sales table if it doesn't exist
ALTER TABLE `sales` 
ADD COLUMN IF NOT EXISTS `advance_payment` DECIMAL(10,2) DEFAULT 0.00 AFTER `paid_amount`;

-- Add unit_price column to stock table if it doesn't exist
ALTER TABLE `stock` 
ADD COLUMN IF NOT EXISTS `unit_price` DECIMAL(10,2) DEFAULT 0.00 AFTER `quantity`;

-- Update unit_price with sale_price from articles table
UPDATE stock s 
JOIN articles a ON s.article_id = a.id 
SET s.unit_price = a.sale_price
WHERE s.unit_price = 0.00;

-- Check current table structures
DESCRIBE `sales`;
DESCRIBE `stock`;
