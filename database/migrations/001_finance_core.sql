-- =====================================================
-- Migration: 001_finance_core.sql
-- Description: Core tables for Double-Entry Accounting
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Chart of Accounts (COA)
CREATE TABLE IF NOT EXISTS `chart_of_accounts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `code` VARCHAR(20) NOT NULL COMMENT 'e.g. 1000',
    `name` VARCHAR(100) NOT NULL COMMENT 'e.g. Cash in Bank',
    `type` ENUM('asset', 'liability', 'equity', 'income', 'expense') NOT NULL,
    `subtype` VARCHAR(50) NULL COMMENT 'current_asset, long_term_liability, etc.',
    `description` TEXT NULL,
    `is_system` BOOLEAN DEFAULT FALSE COMMENT 'Prevent deletion of core accounts',
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_code` (`code`),
    UNIQUE KEY `unique_tenant_code` (`tenant_id`, `code`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Accounting Periods
CREATE TABLE IF NOT EXISTS `accounting_periods` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `fiscal_year` YEAR NOT NULL,
    `name` VARCHAR(50) NOT NULL COMMENT 'e.g. Jan 2025',
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `is_closed` BOOLEAN DEFAULT FALSE,
    `closed_at` TIMESTAMP NULL,
    `closed_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_dates` (`start_date`, `end_date`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`closed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Journal Entries (General Ledger Header)
CREATE TABLE IF NOT EXISTS `journal_entries` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `entry_date` DATE NOT NULL,
    `reference_number` VARCHAR(50) NOT NULL COMMENT 'Unique Journal ID usually',
    `description` TEXT NOT NULL,
    `status` ENUM('draft', 'posted', 'void') DEFAULT 'draft',
    `entity_type` VARCHAR(50) NULL COMMENT 'invoice, payment, expense, etc.',
    `entity_id` INT UNSIGNED NULL COMMENT 'ID of the related record',
    `created_by` INT UNSIGNED NULL,
    `posted_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_date` (`entry_date`),
    INDEX `idx_ref` (`reference_number`),
    INDEX `idx_entity` (`entity_type`, `entity_id`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Journal Entry Lines (Debits/Credits)
CREATE TABLE IF NOT EXISTS `journal_entry_lines` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `journal_entry_id` INT UNSIGNED NOT NULL,
    `account_id` INT UNSIGNED NOT NULL,
    `debit` DECIMAL(15,2) DEFAULT 0,
    `credit` DECIMAL(15,2) DEFAULT 0,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_entry` (`journal_entry_id`),
    INDEX `idx_account` (`account_id`),
    FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Seed: Standard Construction Chart of Accounts Template
-- Note: In a real app, this would be run via PHP for each new tenant. 
-- For SQL import compatibility, we will create a stored procedure or just manual insert instructions.
