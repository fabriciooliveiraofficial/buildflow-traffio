-- =====================================================
-- Email System Advanced Features Migration
-- Adds signature, unsubscribe, and bounce tables
-- =====================================================

-- Add signature columns to email_settings
ALTER TABLE email_settings 
ADD COLUMN IF NOT EXISTS signature_html TEXT NULL AFTER reply_to_email,
ADD COLUMN IF NOT EXISTS signature_plain TEXT NULL AFTER signature_html;

-- Unsubscribe list
CREATE TABLE IF NOT EXISTS email_unsubscribes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    unsubscribed_at DATETIME NOT NULL,
    ip_address VARCHAR(45) NULL,
    UNIQUE KEY unique_tenant_email (tenant_id, email),
    INDEX idx_tenant (tenant_id),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bounce list
CREATE TABLE IF NOT EXISTS email_bounces (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NULL,
    email VARCHAR(255) NOT NULL,
    bounce_count INT DEFAULT 1,
    reason TEXT NULL,
    first_bounce_at DATETIME NOT NULL,
    last_bounce_at DATETIME NOT NULL,
    INDEX idx_tenant (tenant_id),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
