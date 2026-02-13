-- =====================================================
-- Version Releases Table
-- Stores version history and release management
-- =====================================================

CREATE TABLE IF NOT EXISTS `version_releases` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `version` VARCHAR(20) NOT NULL,
    `build` VARCHAR(50) NULL,
    `name` VARCHAR(100) NULL COMMENT 'Release name e.g. "Summer Update"',
    `release_notes` TEXT NULL COMMENT 'Summary of the release',
    `features` JSON NULL COMMENT 'Array of new features',
    `fixes` JSON NULL COMMENT 'Array of bug fixes',
    `improvements` JSON NULL COMMENT 'Array of improvements',
    `is_published` BOOLEAN DEFAULT FALSE,
    `force_update` BOOLEAN DEFAULT FALSE,
    `force_update_message` TEXT NULL,
    `published_at` DATETIME NULL,
    `published_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_version` (`version`),
    INDEX `idx_published` (`is_published`),
    INDEX `idx_published_at` (`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert the initial v1.0.0 release
INSERT INTO `version_releases` (`version`, `build`, `name`, `release_notes`, `features`, `fixes`, `improvements`, `is_published`, `published_at`)
VALUES (
    '1.0.0',
    '20241210',
    'Initial Release',
    'First production release of BuildFlow ERP',
    '["Initial release of BuildFlow ERP", "Project management dashboard", "Client and invoice management", "Time tracking with live timer", "Expense tracking and approvals", "Multi-tenant architecture"]',
    '[]',
    '[]',
    TRUE,
    NOW()
) ON DUPLICATE KEY UPDATE `id` = `id`;
