-- Add employee_id column to expenses table for labor expense tracking
ALTER TABLE `expenses`
ADD COLUMN `employee_id` INT UNSIGNED NULL AFTER `user_id`,
ADD INDEX `idx_employee` (`employee_id`),
ADD FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE SET NULL;
