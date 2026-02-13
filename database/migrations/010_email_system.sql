-- =====================================================
-- Email System Migration
-- Multi-tenant SMTP configuration, templates, queue, and logs
-- =====================================================

-- 1. SMTP Configuration (per tenant)
CREATE TABLE IF NOT EXISTS email_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL UNIQUE,
    smtp_host VARCHAR(255),
    smtp_port INT DEFAULT 587,
    encryption ENUM('none', 'tls', 'ssl', 'starttls') DEFAULT 'tls',
    username VARCHAR(255),
    password_encrypted TEXT COMMENT 'AES-256 encrypted password',
    sender_name VARCHAR(255),
    sender_email VARCHAR(255),
    reply_to_email VARCHAR(255),
    is_verified BOOLEAN DEFAULT FALSE,
    verified_at DATETIME NULL,
    daily_limit INT DEFAULT 100 COMMENT 'Max emails per day',
    emails_sent_today INT DEFAULT 0,
    last_reset_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Email Templates
CREATE TABLE IF NOT EXISTS email_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    slug VARCHAR(50) NOT NULL COMMENT 'invoice, estimate, reminder, welcome, etc.',
    name VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body_html TEXT,
    body_plain TEXT,
    variables JSON COMMENT 'List of available tokens for this template',
    is_system BOOLEAN DEFAULT FALSE COMMENT 'System templates cannot be deleted',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_tenant_slug (tenant_id, slug),
    INDEX idx_tenant_active (tenant_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Email Queue
CREATE TABLE IF NOT EXISTS email_queue (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    to_addresses JSON NOT NULL COMMENT '[{email, name}]',
    cc_addresses JSON NULL,
    bcc_addresses JSON NULL,
    subject VARCHAR(255) NOT NULL,
    body_html TEXT,
    body_plain TEXT,
    attachments JSON NULL COMMENT '[{filename, path, mime_type, size}]',
    template_id INT NULL,
    context_type VARCHAR(50) NULL COMMENT 'invoice, project, estimate, client',
    context_id INT NULL,
    priority TINYINT DEFAULT 5 COMMENT '1=highest, 10=lowest',
    status ENUM('pending', 'processing', 'sent', 'failed', 'cancelled') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    last_error TEXT NULL,
    scheduled_at DATETIME NULL COMMENT 'For delayed sending',
    processing_at DATETIME NULL,
    sent_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NULL COMMENT 'user_id who initiated',
    INDEX idx_tenant_status (tenant_id, status),
    INDEX idx_status_priority (status, priority, scheduled_at),
    INDEX idx_context (tenant_id, context_type, context_id),
    INDEX idx_template (template_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Email Logs
CREATE TABLE IF NOT EXISTS email_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    queue_id BIGINT NULL,
    message_id VARCHAR(255) NULL COMMENT 'SMTP Message-ID header',
    to_email VARCHAR(255) NOT NULL,
    to_name VARCHAR(255) NULL,
    subject VARCHAR(255),
    context_type VARCHAR(50) NULL,
    context_id INT NULL,
    status ENUM('sent', 'delivered', 'opened', 'clicked', 'bounced', 'failed') DEFAULT 'sent',
    opened_at DATETIME NULL,
    opened_count INT DEFAULT 0,
    clicked_at DATETIME NULL,
    clicked_count INT DEFAULT 0,
    error_message TEXT NULL,
    smtp_response TEXT NULL,
    ip_address VARCHAR(45) NULL COMMENT 'IP when opened/clicked',
    user_agent TEXT NULL,
    sent_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tenant_date (tenant_id, sent_at),
    INDEX idx_tenant_context (tenant_id, context_type, context_id),
    INDEX idx_message_id (message_id),
    INDEX idx_queue (queue_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Email Automations
CREATE TABLE IF NOT EXISTS email_automations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    trigger_event VARCHAR(50) NOT NULL COMMENT 'invoice_created, invoice_overdue, payment_received, etc.',
    template_id INT NULL,
    is_enabled BOOLEAN DEFAULT TRUE,
    delay_minutes INT DEFAULT 0 COMMENT 'Wait time before sending',
    conditions JSON NULL COMMENT 'Additional conditions for triggering',
    send_to ENUM('client', 'user', 'custom') DEFAULT 'client',
    custom_recipients JSON NULL COMMENT 'Custom email addresses',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_tenant_trigger (tenant_id, trigger_event),
    INDEX idx_tenant_enabled (tenant_id, is_enabled),
    INDEX idx_template (template_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

