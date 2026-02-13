-- =====================================================
-- Migration: 005_user_invitations.sql
-- Description: User invitation system for tenant admins
-- =====================================================

CREATE TABLE IF NOT EXISTS `user_invitations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `role_id` INT UNSIGNED NOT NULL,
    `token` VARCHAR(64) NOT NULL UNIQUE,
    `first_name` VARCHAR(100) NULL,
    `last_name` VARCHAR(100) NULL,
    `invited_by` INT UNSIGNED NULL,
    `message` TEXT NULL COMMENT 'Optional personal message in invitation',
    `status` ENUM('pending', 'accepted', 'expired', 'cancelled') DEFAULT 'pending',
    `expires_at` TIMESTAMP NOT NULL,
    `accepted_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    INDEX `idx_email` (`email`),
    INDEX `idx_token` (`token`),
    INDEX `idx_status` (`status`),
    INDEX `idx_expires` (`expires_at`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`invited_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
