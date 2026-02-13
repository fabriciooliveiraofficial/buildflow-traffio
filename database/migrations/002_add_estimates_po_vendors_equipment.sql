-- =====================================================
-- Migration: Add Estimates, Purchase Orders, Vendors, Equipment
-- Run after 001_create_tables.sql
-- =====================================================

SET NAMES 'utf8mb4';

-- =====================================================
-- Estimates (Quotes)
-- =====================================================
CREATE TABLE IF NOT EXISTS `estimates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `client_id` INT UNSIGNED NOT NULL,
    `project_id` INT UNSIGNED NULL,
    `estimate_number` VARCHAR(50) NOT NULL,
    `title` VARCHAR(255) NULL,
    `issue_date` DATE NOT NULL,
    `expiry_date` DATE NULL,
    `subtotal` DECIMAL(12,2) DEFAULT 0.00,
    `tax_rate` DECIMAL(5,2) DEFAULT 0.00,
    `tax_amount` DECIMAL(12,2) DEFAULT 0.00,
    `total_amount` DECIMAL(12,2) DEFAULT 0.00,
    `status` ENUM('draft','sent','approved','rejected','expired','converted') DEFAULT 'draft',
    `converted_invoice_id` INT UNSIGNED NULL,
    `notes` TEXT NULL,
    `terms` TEXT NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`converted_invoice_id`) REFERENCES `invoices`(`id`) ON DELETE SET NULL,
    INDEX `idx_estimate_tenant` (`tenant_id`),
    INDEX `idx_estimate_client` (`client_id`),
    INDEX `idx_estimate_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `estimate_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `estimate_id` INT UNSIGNED NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    `quantity` DECIMAL(10,2) DEFAULT 1.00,
    `unit` VARCHAR(50) DEFAULT 'unit',
    `unit_price` DECIMAL(12,2) DEFAULT 0.00,
    `total` DECIMAL(12,2) DEFAULT 0.00,
    `sort_order` INT DEFAULT 0,
    FOREIGN KEY (`estimate_id`) REFERENCES `estimates`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Vendors (Suppliers)
-- =====================================================
CREATE TABLE IF NOT EXISTS `vendors` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `code` VARCHAR(50) NULL,
    `contact_name` VARCHAR(255) NULL,
    `email` VARCHAR(255) NULL,
    `phone` VARCHAR(50) NULL,
    `address` TEXT NULL,
    `city` VARCHAR(100) NULL,
    `state` VARCHAR(100) NULL,
    `zip` VARCHAR(20) NULL,
    `category` VARCHAR(100) NULL,
    `payment_terms` VARCHAR(100) DEFAULT 'Net 30',
    `tax_id` VARCHAR(50) NULL,
    `notes` TEXT NULL,
    `status` ENUM('active','inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    INDEX `idx_vendor_tenant` (`tenant_id`),
    INDEX `idx_vendor_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Purchase Orders
-- =====================================================
CREATE TABLE IF NOT EXISTS `purchase_orders` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `vendor_id` INT UNSIGNED NULL,
    `project_id` INT UNSIGNED NULL,
    `po_number` VARCHAR(50) NOT NULL,
    `order_date` DATE NOT NULL,
    `expected_date` DATE NULL,
    `received_date` DATE NULL,
    `subtotal` DECIMAL(12,2) DEFAULT 0.00,
    `tax_rate` DECIMAL(5,2) DEFAULT 0.00,
    `tax_amount` DECIMAL(12,2) DEFAULT 0.00,
    `shipping_amount` DECIMAL(12,2) DEFAULT 0.00,
    `total_amount` DECIMAL(12,2) DEFAULT 0.00,
    `status` ENUM('draft','sent','partial','received','cancelled') DEFAULT 'draft',
    `shipping_address` TEXT NULL,
    `notes` TEXT NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`vendor_id`) REFERENCES `vendors`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE SET NULL,
    INDEX `idx_po_tenant` (`tenant_id`),
    INDEX `idx_po_vendor` (`vendor_id`),
    INDEX `idx_po_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `purchase_order_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `purchase_order_id` INT UNSIGNED NOT NULL,
    `inventory_item_id` INT UNSIGNED NULL,
    `description` VARCHAR(255) NOT NULL,
    `quantity_ordered` DECIMAL(10,2) DEFAULT 1.00,
    `quantity_received` DECIMAL(10,2) DEFAULT 0.00,
    `unit` VARCHAR(50) DEFAULT 'unit',
    `unit_price` DECIMAL(12,2) DEFAULT 0.00,
    `total` DECIMAL(12,2) DEFAULT 0.00,
    `sort_order` INT DEFAULT 0,
    FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Equipment
-- =====================================================
CREATE TABLE IF NOT EXISTS `equipment` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `code` VARCHAR(50) NULL,
    `type` VARCHAR(100) NULL,
    `make` VARCHAR(100) NULL,
    `model` VARCHAR(100) NULL,
    `serial_number` VARCHAR(100) NULL,
    `purchase_date` DATE NULL,
    `purchase_cost` DECIMAL(12,2) DEFAULT 0.00,
    `current_value` DECIMAL(12,2) DEFAULT 0.00,
    `status` ENUM('available','in_use','maintenance','retired') DEFAULT 'available',
    `assigned_project_id` INT UNSIGNED NULL,
    `assigned_employee_id` INT UNSIGNED NULL,
    `location` VARCHAR(255) NULL,
    `last_maintenance_date` DATE NULL,
    `next_maintenance_date` DATE NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`assigned_project_id`) REFERENCES `projects`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`assigned_employee_id`) REFERENCES `employees`(`id`) ON DELETE SET NULL,
    INDEX `idx_equipment_tenant` (`tenant_id`),
    INDEX `idx_equipment_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Equipment Maintenance Log
-- =====================================================
CREATE TABLE IF NOT EXISTS `equipment_maintenance` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `equipment_id` INT UNSIGNED NOT NULL,
    `maintenance_date` DATE NOT NULL,
    `type` ENUM('preventive','repair','inspection') DEFAULT 'preventive',
    `description` TEXT NULL,
    `cost` DECIMAL(12,2) DEFAULT 0.00,
    `performed_by` VARCHAR(255) NULL,
    `next_due_date` DATE NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`equipment_id`) REFERENCES `equipment`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
