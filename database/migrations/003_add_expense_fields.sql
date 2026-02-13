-- Add missing columns to expenses table
ALTER TABLE `expenses`
ADD COLUMN `receipt_number` VARCHAR(50) NULL AFTER `vendor`,
ADD COLUMN `payment_method` VARCHAR(50) NULL AFTER `receipt_path`,
ADD COLUMN `is_billable` BOOLEAN DEFAULT FALSE AFTER `payment_method`;
