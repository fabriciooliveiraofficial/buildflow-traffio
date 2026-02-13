-- =====================================================
-- Construction ERP - Complete Database Setup Script
-- Multi-Tenant Architecture
-- 
-- Run this script in phpMyAdmin or MySQL client
-- =====================================================

-- Set character set
SET NAMES 'utf8mb4';
SET CHARACTER SET utf8mb4;

-- Create database (uncomment if needed)
-- CREATE DATABASE IF NOT EXISTS construction_erp 
--     CHARACTER SET utf8mb4 
--     COLLATE utf8mb4_unicode_ci;
-- USE construction_erp;

-- =====================================================
-- DROP EXISTING TABLES (for clean install)
-- =====================================================
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `documents`;
DROP TABLE IF EXISTS `custom_categories`;
DROP TABLE IF EXISTS `suppliers`;
DROP TABLE IF EXISTS `inventory_transactions`;
DROP TABLE IF EXISTS `inventory_items`;
DROP TABLE IF EXISTS `inventory_categories`;
DROP TABLE IF EXISTS `payroll_records`;
DROP TABLE IF EXISTS `payroll_periods`;
DROP TABLE IF EXISTS `time_logs`;
DROP TABLE IF EXISTS `employees`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `invoice_items`;
DROP TABLE IF EXISTS `invoices`;
DROP TABLE IF EXISTS `expenses`;
DROP TABLE IF EXISTS `budgets`;
DROP TABLE IF EXISTS `tasks`;
DROP TABLE IF EXISTS `projects`;
DROP TABLE IF EXISTS `clients`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `tenants`;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- CORE TABLES
-- =====================================================

-- Table: tenants
CREATE TABLE `tenants` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `subdomain` VARCHAR(100) NOT NULL UNIQUE,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(50) NULL,
    `address` TEXT NULL,
    `city` VARCHAR(100) NULL,
    `state` VARCHAR(100) NULL,
    `zip_code` VARCHAR(20) NULL,
    `country` VARCHAR(100) DEFAULT 'USA',
    `logo` VARCHAR(255) NULL,
    `settings` JSON NULL COMMENT 'Tenant-specific settings',
    `status` ENUM('active', 'suspended', 'cancelled') DEFAULT 'active',
    `plan` VARCHAR(50) DEFAULT 'basic',
    `stripe_customer_id` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_subdomain` (`subdomain`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: roles
CREATE TABLE `roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NULL,
    `name` VARCHAR(50) NOT NULL,
    `display_name` VARCHAR(100) NOT NULL,
    `permissions` JSON NULL COMMENT 'Array of permission strings',
    `is_system` BOOLEAN DEFAULT FALSE COMMENT 'System roles cannot be deleted',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: users
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `role_id` INT UNSIGNED NOT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(50) NULL,
    `avatar` VARCHAR(255) NULL,
    `two_factor_enabled` BOOLEAN DEFAULT FALSE,
    `two_factor_secret` VARCHAR(255) NULL,
    `email_verified_at` TIMESTAMP NULL,
    `last_login_at` TIMESTAMP NULL,
    `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    `preferences` JSON NULL COMMENT 'User preferences (theme, language, etc.)',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_tenant_email` (`tenant_id`, `email`),
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_role` (`role_id`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CLIENT & PROJECT MANAGEMENT
-- =====================================================

-- Table: clients
CREATE TABLE `clients` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `type` ENUM('company', 'individual', 'government') DEFAULT 'company',
    `contact_person` VARCHAR(255) NULL,
    `email` VARCHAR(255) NULL,
    `phone` VARCHAR(50) NULL,
    `website` VARCHAR(255) NULL,
    `address` TEXT NULL,
    `city` VARCHAR(100) NULL,
    `state` VARCHAR(100) NULL,
    `zip_code` VARCHAR(20) NULL,
    `country` VARCHAR(100) DEFAULT 'USA',
    `industry` VARCHAR(100) NULL,
    `payment_terms` INT DEFAULT 30 COMMENT 'Days until payment due',
    `credit_limit` DECIMAL(15,2) NULL,
    `tax_id` VARCHAR(50) NULL,
    `notes` TEXT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: projects
CREATE TABLE `projects` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `client_id` INT UNSIGNED NOT NULL,
    `manager_id` INT UNSIGNED NULL COMMENT 'Project Manager user ID',
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `code` VARCHAR(50) NULL COMMENT 'Project reference code',
    `address` TEXT NULL COMMENT 'Project site address',
    `city` VARCHAR(100) NULL,
    `state` VARCHAR(100) NULL,
    `zip_code` VARCHAR(20) NULL,
    `start_date` DATE NULL,
    `end_date` DATE NULL,
    `actual_end_date` DATE NULL,
    `estimated_hours` DECIMAL(10,2) NULL,
    `actual_hours` DECIMAL(10,2) DEFAULT 0,
    `contract_value` DECIMAL(15,2) NULL,
    `status` ENUM('planning', 'in_progress', 'on_hold', 'completed', 'cancelled') DEFAULT 'planning',
    `priority` ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    `progress` TINYINT UNSIGNED DEFAULT 0 COMMENT 'Percentage complete',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_client` (`client_id`),
    INDEX `idx_manager` (`manager_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_dates` (`start_date`, `end_date`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`),
    FOREIGN KEY (`manager_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: tasks
CREATE TABLE `tasks` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `project_id` INT UNSIGNED NOT NULL,
    `parent_id` INT UNSIGNED NULL COMMENT 'For subtasks',
    `assigned_to` INT UNSIGNED NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `start_date` DATE NULL,
    `due_date` DATE NULL,
    `completed_at` TIMESTAMP NULL,
    `estimated_hours` DECIMAL(8,2) NULL,
    `actual_hours` DECIMAL(8,2) NULL,
    `status` ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    `priority` ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    `sort_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_project` (`project_id`),
    INDEX `idx_parent` (`parent_id`),
    INDEX `idx_assigned` (`assigned_to`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`parent_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- BUDGETING & EXPENSES
-- =====================================================

-- Table: budgets
CREATE TABLE `budgets` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `project_id` INT UNSIGNED NOT NULL,
    `category` VARCHAR(100) NOT NULL COMMENT 'labor, materials, equipment, etc.',
    `description` VARCHAR(255) NULL,
    `budgeted_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `spent_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_project` (`project_id`),
    INDEX `idx_category` (`category`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: expenses
CREATE TABLE `expenses` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `project_id` INT UNSIGNED NOT NULL,
    `budget_id` INT UNSIGNED NULL,
    `user_id` INT UNSIGNED NULL COMMENT 'Who recorded the expense',
    `category` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `expense_date` DATE NOT NULL,
    `vendor` VARCHAR(255) NULL,
    `receipt_path` VARCHAR(255) NULL,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `approved_by` INT UNSIGNED NULL,
    `approved_at` TIMESTAMP NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_project` (`project_id`),
    INDEX `idx_budget` (`budget_id`),
    INDEX `idx_date` (`expense_date`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`budget_id`) REFERENCES `budgets`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INVOICING & PAYMENTS
-- =====================================================

-- Table: invoices
CREATE TABLE `invoices` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `client_id` INT UNSIGNED NOT NULL,
    `project_id` INT UNSIGNED NULL,
    `invoice_number` VARCHAR(50) NOT NULL,
    `issue_date` DATE NOT NULL,
    `due_date` DATE NOT NULL,
    `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `tax_rate` DECIMAL(5,2) DEFAULT 0,
    `tax_amount` DECIMAL(15,2) DEFAULT 0,
    `discount_amount` DECIMAL(15,2) DEFAULT 0,
    `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `paid_amount` DECIMAL(15,2) DEFAULT 0,
    `status` ENUM('draft', 'sent', 'viewed', 'partial', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',
    `notes` TEXT NULL,
    `terms` TEXT NULL,
    `stripe_invoice_id` VARCHAR(255) NULL,
    `payment_link` VARCHAR(255) NULL,
    `sent_at` TIMESTAMP NULL,
    `paid_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_tenant_invoice` (`tenant_id`, `invoice_number`),
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_client` (`client_id`),
    INDEX `idx_project` (`project_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_due_date` (`due_date`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`),
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: invoice_items
CREATE TABLE `invoice_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `invoice_id` INT UNSIGNED NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    `quantity` DECIMAL(10,2) NOT NULL DEFAULT 1,
    `unit_price` DECIMAL(15,2) NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `sort_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_invoice` (`invoice_id`),
    FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: payments
CREATE TABLE `payments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `invoice_id` INT UNSIGNED NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `payment_date` DATE NOT NULL,
    `payment_method` VARCHAR(50) NOT NULL COMMENT 'stripe, bank_transfer, cash, check',
    `reference_number` VARCHAR(255) NULL,
    `stripe_payment_id` VARCHAR(255) NULL,
    `stripe_charge_id` VARCHAR(255) NULL,
    `notes` TEXT NULL,
    `status` ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'completed',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_invoice` (`invoice_id`),
    INDEX `idx_date` (`payment_date`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- EMPLOYEES & PAYROLL
-- =====================================================

-- Table: employees
CREATE TABLE `employees` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NULL COMMENT 'Link to user account if exists',
    `employee_id` VARCHAR(50) NULL COMMENT 'EMP-00001 style ID',
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NULL,
    `phone` VARCHAR(50) NULL,
    `address` TEXT NULL,
    `city` VARCHAR(100) NULL,
    `state` VARCHAR(100) NULL,
    `zip_code` VARCHAR(20) NULL,
    `hire_date` DATE NULL,
    `termination_date` DATE NULL,
    `job_title` VARCHAR(100) NULL,
    `department` VARCHAR(100) NULL,
    `payment_type` ENUM('hourly', 'daily', 'salary', 'project', 'commission') DEFAULT 'hourly',
    `hourly_rate` DECIMAL(10,2) NULL,
    `daily_rate` DECIMAL(10,2) NULL,
    `salary` DECIMAL(15,2) NULL,
    `commission_rate` DECIMAL(5,2) NULL COMMENT 'Percentage',
    `overtime_multiplier` DECIMAL(3,2) DEFAULT 1.50,
    `overtime_threshold` INT DEFAULT 40 COMMENT 'Hours per week',
    `tax_id` VARCHAR(50) NULL,
    `bank_name` VARCHAR(100) NULL,
    `bank_account` VARCHAR(255) NULL,
    `bank_routing` VARCHAR(50) NULL,
    `emergency_contact` VARCHAR(255) NULL,
    `emergency_phone` VARCHAR(50) NULL,
    `notes` TEXT NULL,
    `status` ENUM('active', 'inactive', 'terminated') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_employee_id` (`employee_id`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: time_logs
CREATE TABLE `time_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `employee_id` INT UNSIGNED NOT NULL,
    `project_id` INT UNSIGNED NULL,
    `task_id` INT UNSIGNED NULL,
    `log_date` DATE NOT NULL,
    `start_time` TIME NULL,
    `end_time` TIME NULL,
    `hours` DECIMAL(5,2) NOT NULL,
    `break_hours` DECIMAL(5,2) DEFAULT 0,
    `description` TEXT NULL,
    `is_overtime` BOOLEAN DEFAULT FALSE,
    `billing_rate` DECIMAL(10,2) NULL,
    `billable` BOOLEAN DEFAULT TRUE,
    `approved` BOOLEAN DEFAULT FALSE,
    `approved_by` INT UNSIGNED NULL,
    `approved_at` TIMESTAMP NULL,
    `timer_started_at` TIMESTAMP NULL COMMENT 'For active timer tracking',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_employee` (`employee_id`),
    INDEX `idx_project` (`project_id`),
    INDEX `idx_task` (`task_id`),
    INDEX `idx_date` (`log_date`),
    INDEX `idx_approved` (`approved`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: payroll_periods
CREATE TABLE `payroll_periods` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NULL COMMENT 'Period name/description',
    `period_start` DATE NOT NULL,
    `period_end` DATE NOT NULL,
    `payment_date` DATE NULL,
    `status` ENUM('draft', 'processing', 'processed', 'paid') DEFAULT 'draft',
    `total_gross` DECIMAL(15,2) DEFAULT 0,
    `total_deductions` DECIMAL(15,2) DEFAULT 0,
    `total_net` DECIMAL(15,2) DEFAULT 0,
    `notes` TEXT NULL,
    `processed_by` INT UNSIGNED NULL,
    `processed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_dates` (`period_start`, `period_end`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`processed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: payroll_records
CREATE TABLE `payroll_records` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `payroll_period_id` INT UNSIGNED NOT NULL,
    `employee_id` INT UNSIGNED NOT NULL,
    `regular_hours` DECIMAL(8,2) DEFAULT 0,
    `overtime_hours` DECIMAL(8,2) DEFAULT 0,
    `regular_pay` DECIMAL(15,2) DEFAULT 0,
    `overtime_pay` DECIMAL(15,2) DEFAULT 0,
    `commission_pay` DECIMAL(15,2) DEFAULT 0,
    `bonus` DECIMAL(15,2) DEFAULT 0,
    `gross_pay` DECIMAL(15,2) NOT NULL,
    `tax_deduction` DECIMAL(15,2) DEFAULT 0,
    `insurance_deduction` DECIMAL(15,2) DEFAULT 0,
    `other_deductions` DECIMAL(15,2) DEFAULT 0,
    `deductions` DECIMAL(15,2) GENERATED ALWAYS AS (tax_deduction + insurance_deduction + other_deductions) STORED,
    `net_pay` DECIMAL(15,2) NOT NULL,
    `payment_method` VARCHAR(50) NULL,
    `payment_reference` VARCHAR(255) NULL,
    `status` ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    `paid_at` TIMESTAMP NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_period` (`payroll_period_id`),
    INDEX `idx_employee` (`employee_id`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`payroll_period_id`) REFERENCES `payroll_periods`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INVENTORY MANAGEMENT
-- =====================================================

-- Table: suppliers
CREATE TABLE `suppliers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `contact_person` VARCHAR(255) NULL,
    `email` VARCHAR(255) NULL,
    `phone` VARCHAR(50) NULL,
    `address` TEXT NULL,
    `city` VARCHAR(100) NULL,
    `state` VARCHAR(100) NULL,
    `zip_code` VARCHAR(20) NULL,
    `payment_terms` INT DEFAULT 30,
    `tax_id` VARCHAR(50) NULL,
    `notes` TEXT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: inventory_items
CREATE TABLE `inventory_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `supplier_id` INT UNSIGNED NULL,
    `sku` VARCHAR(100) NULL,
    `barcode` VARCHAR(100) NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `category` VARCHAR(100) NULL,
    `unit` VARCHAR(50) DEFAULT 'piece' COMMENT 'piece, kg, liter, meter, box, etc.',
    `quantity` DECIMAL(15,2) DEFAULT 0,
    `min_quantity` DECIMAL(15,2) DEFAULT 0 COMMENT 'Low stock threshold',
    `max_quantity` DECIMAL(15,2) NULL COMMENT 'Maximum stock level',
    `unit_cost` DECIMAL(15,2) NULL,
    `unit_price` DECIMAL(15,2) NULL COMMENT 'Selling price',
    `location` VARCHAR(255) NULL COMMENT 'Warehouse location',
    `last_restocked_at` TIMESTAMP NULL,
    `status` ENUM('active', 'discontinued', 'out_of_stock') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_supplier` (`supplier_id`),
    INDEX `idx_sku` (`sku`),
    INDEX `idx_barcode` (`barcode`),
    INDEX `idx_category` (`category`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: inventory_transactions
CREATE TABLE `inventory_transactions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `item_id` INT UNSIGNED NOT NULL,
    `project_id` INT UNSIGNED NULL,
    `user_id` INT UNSIGNED NULL,
    `type` ENUM('purchase', 'sale', 'adjustment', 'transfer', 'return', 'damaged') NOT NULL,
    `quantity` DECIMAL(15,2) NOT NULL COMMENT 'Positive for in, negative for out',
    `quantity_before` DECIMAL(15,2) NULL,
    `quantity_after` DECIMAL(15,2) NULL,
    `unit_cost` DECIMAL(15,2) NULL,
    `total_cost` DECIMAL(15,2) NULL,
    `reference` VARCHAR(255) NULL COMMENT 'PO number, invoice, etc.',
    `notes` TEXT NULL,
    `transaction_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_item` (`item_id`),
    INDEX `idx_project` (`project_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_date` (`transaction_date`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`item_id`) REFERENCES `inventory_items`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DOCUMENTS & FILES
-- =====================================================

-- Table: documents
CREATE TABLE `documents` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `project_id` INT UNSIGNED NULL,
    `uploaded_by` INT UNSIGNED NULL,
    `name` VARCHAR(255) NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `path` VARCHAR(255) NOT NULL,
    `mime_type` VARCHAR(100) NULL,
    `size` INT UNSIGNED NULL COMMENT 'Bytes',
    `category` VARCHAR(100) NULL COMMENT 'contract, blueprint, photo, permit, etc.',
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_project` (`project_id`),
    INDEX `idx_category` (`category`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SETTINGS & CONFIGURATION
-- =====================================================

-- Table: settings
CREATE TABLE `settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `key` VARCHAR(100) NOT NULL,
    `value` TEXT NULL,
    `type` VARCHAR(50) DEFAULT 'string' COMMENT 'string, json, boolean, number',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_tenant_key` (`tenant_id`, `key`),
    INDEX `idx_tenant` (`tenant_id`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: custom_categories
CREATE TABLE `custom_categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `type` VARCHAR(50) NOT NULL COMMENT 'projects, expenses, tasks, inventory',
    `name` VARCHAR(100) NOT NULL,
    `color` VARCHAR(20) NULL,
    `sort_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_type` (`type`),
    UNIQUE KEY `unique_tenant_type_name` (`tenant_id`, `type`, `name`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ACTIVITY & NOTIFICATIONS
-- =====================================================

-- Table: activity_logs
CREATE TABLE `activity_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NULL,
    `action` VARCHAR(100) NOT NULL COMMENT 'created, updated, deleted, etc.',
    `entity_type` VARCHAR(100) NULL COMMENT 'project, invoice, task, etc.',
    `entity_id` INT UNSIGNED NULL,
    `old_values` JSON NULL,
    `new_values` JSON NULL,
    `description` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_entity` (`entity_type`, `entity_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created` (`created_at`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: notifications
CREATE TABLE `notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `type` VARCHAR(100) NOT NULL COMMENT 'task_assigned, invoice_paid, etc.',
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NULL,
    `data` JSON NULL COMMENT 'Additional notification data',
    `link` VARCHAR(255) NULL,
    `read_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_read` (`read_at`),
    INDEX `idx_type` (`type`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DEFAULT DATA
-- =====================================================

-- Insert default system roles
INSERT INTO `roles` (`tenant_id`, `name`, `display_name`, `permissions`, `is_system`) VALUES
(NULL, 'admin', 'Administrator', '["*"]', TRUE),
(NULL, 'project_manager', 'Project Manager', '["projects.*", "clients.*", "tasks.*", "time_logs.*", "budgets.*", "expenses.view", "invoices.view", "documents.*", "reports.view"]', TRUE),
(NULL, 'accountant', 'Accountant', '["invoices.*", "payments.*", "expenses.*", "budgets.*", "payroll.*", "reports.*", "clients.view", "projects.view"]', TRUE),
(NULL, 'contractor', 'Contractor', '["projects.view", "tasks.*", "time_logs.own", "documents.view", "inventory.view"]', TRUE),
(NULL, 'worker', 'Worker', '["tasks.view", "time_logs.own"]', TRUE);

-- =====================================================
-- VIEWS (Optional - for reporting)
-- =====================================================

-- View: Project financial summary
CREATE OR REPLACE VIEW `v_project_financials` AS
SELECT 
    p.id,
    p.tenant_id,
    p.name,
    p.client_id,
    c.name AS client_name,
    p.status,
    p.progress,
    COALESCE(SUM(b.budgeted_amount), 0) AS total_budget,
    COALESCE(SUM(b.spent_amount), 0) AS total_spent,
    COALESCE(SUM(b.budgeted_amount), 0) - COALESCE(SUM(b.spent_amount), 0) AS budget_remaining,
    (SELECT COALESCE(SUM(hours), 0) FROM time_logs WHERE project_id = p.id) AS total_hours
FROM projects p
LEFT JOIN clients c ON p.client_id = c.id
LEFT JOIN budgets b ON p.id = b.project_id
GROUP BY p.id;

-- View: Employee hours summary
CREATE OR REPLACE VIEW `v_employee_hours` AS
SELECT 
    e.id AS employee_id,
    e.tenant_id,
    CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
    e.payment_type,
    e.hourly_rate,
    DATE_FORMAT(tl.log_date, '%Y-%m') AS month,
    SUM(tl.hours) AS total_hours,
    SUM(CASE WHEN tl.is_overtime THEN tl.hours ELSE 0 END) AS overtime_hours,
    SUM(CASE WHEN tl.billable THEN tl.hours ELSE 0 END) AS billable_hours
FROM employees e
LEFT JOIN time_logs tl ON e.id = tl.employee_id
WHERE e.status = 'active'
GROUP BY e.id, DATE_FORMAT(tl.log_date, '%Y-%m');

-- =====================================================
-- COMPLETED
-- =====================================================

SELECT 'Database setup completed successfully!' AS Status;
SELECT COUNT(*) AS `Tables Created` FROM information_schema.tables WHERE table_schema = DATABASE();
