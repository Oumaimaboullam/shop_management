-- SQL to fix payments table structure for supplier payments
-- Run this in your MySQL database

-- Add notes column to payments table if it doesn't exist
ALTER TABLE `payments` 
ADD COLUMN IF NOT EXISTS `notes` text DEFAULT NULL COMMENT 'Payment notes or reference';

-- Add created_at column to payments table if missing
ALTER TABLE `payments` 
ADD COLUMN IF NOT EXISTS `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- Add updated_at column to payments table if missing
ALTER TABLE `payments` 
ADD COLUMN IF NOT EXISTS `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Check current payments table structure
DESCRIBE `payments`;
