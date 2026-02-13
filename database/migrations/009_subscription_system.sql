-- =====================================================
-- SaaS Subscription System
-- Migration: 009_subscription_system.sql
-- =====================================================

-- -----------------------------------------------------
-- Table: subscription_plans
-- Stores the available subscription tiers
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS subscription_plans (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    description TEXT NULL,
    stripe_product_id VARCHAR(100) NULL COMMENT 'prod_xxx',
    stripe_price_id VARCHAR(100) NULL COMMENT 'price_xxx',
    price_monthly DECIMAL(10,2) NOT NULL,
    price_yearly DECIMAL(10,2) NULL,
    user_limit INT NOT NULL DEFAULT 3,
    features JSON NULL COMMENT 'Feature flags for this plan',
    is_popular TINYINT(1) DEFAULT 0 COMMENT 'Highlight as recommended',
    trial_days INT DEFAULT 14,
    sort_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Insert default subscription plans
-- -----------------------------------------------------
INSERT INTO subscription_plans (name, slug, description, price_monthly, user_limit, features, is_popular, sort_order) VALUES
(
    'Team',
    'team',
    'Ideal for small construction teams getting started with digital operations.',
    60.00,
    3,
    '{"clients": true, "projects": true, "expenses": true, "time_tracking": true, "estimates": true, "invoices": true, "basic_reports": true, "email_notifications": true, "support": "standard"}',
    0,
    1
),
(
    'Business',
    'business',
    'Best for growing companies that need stronger collaboration and financial visibility.',
    90.00,
    5,
    '{"clients": true, "projects": true, "expenses": true, "time_tracking": true, "estimates": true, "invoices": true, "basic_reports": true, "email_notifications": true, "payroll": true, "inventory": true, "advanced_analytics": true, "pwa_offline": true, "support": "priority", "support_tickets": true}',
    1,
    2
),
(
    'Professional',
    'professional',
    'Designed for medium-sized construction companies with full operational complexity.',
    160.00,
    10,
    '{"clients": true, "projects": true, "expenses": true, "time_tracking": true, "estimates": true, "invoices": true, "basic_reports": true, "email_notifications": true, "payroll": true, "inventory": true, "advanced_analytics": true, "pwa_offline": true, "support_tickets": true, "advanced_reports": true, "custom_branding": true, "export_tools": true, "automated_workflows": true, "support": "priority_chat"}',
    0,
    3
);

-- -----------------------------------------------------
-- Modify tenants table for subscription support
-- -----------------------------------------------------
ALTER TABLE tenants 
    ADD COLUMN IF NOT EXISTS stripe_subscription_id VARCHAR(100) NULL AFTER stripe_customer_id,
    ADD COLUMN IF NOT EXISTS subscription_plan_id INT UNSIGNED NULL AFTER stripe_subscription_id,
    ADD COLUMN IF NOT EXISTS subscription_status ENUM('trialing', 'active', 'past_due', 'canceled', 'suspended', 'incomplete') DEFAULT NULL AFTER subscription_plan_id,
    ADD COLUMN IF NOT EXISTS subscription_started_at DATETIME NULL AFTER subscription_status,
    ADD COLUMN IF NOT EXISTS subscription_ends_at DATETIME NULL AFTER subscription_started_at,
    ADD COLUMN IF NOT EXISTS trial_ends_at DATETIME NULL AFTER subscription_ends_at,
    ADD COLUMN IF NOT EXISTS user_limit INT DEFAULT 3 AFTER trial_ends_at,
    ADD COLUMN IF NOT EXISTS extra_seats INT DEFAULT 0 AFTER user_limit,
    ADD COLUMN IF NOT EXISTS billing_email VARCHAR(255) NULL AFTER extra_seats,
    ADD COLUMN IF NOT EXISTS grace_period_ends_at DATETIME NULL COMMENT 'When past_due access expires' AFTER billing_email;

-- Add indexes
ALTER TABLE tenants
    ADD INDEX IF NOT EXISTS idx_subscription_status (subscription_status),
    ADD INDEX IF NOT EXISTS idx_stripe_subscription (stripe_subscription_id);

-- -----------------------------------------------------
-- Table: subscription_history
-- Audit trail of subscription changes
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS subscription_history (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    event_type ENUM('created', 'upgraded', 'downgraded', 'canceled', 'reactivated', 'payment_failed', 'payment_succeeded', 'trial_started', 'trial_ended', 'suspended', 'unsuspended') NOT NULL,
    from_plan_id INT UNSIGNED NULL,
    to_plan_id INT UNSIGNED NULL,
    stripe_event_id VARCHAR(100) NULL,
    amount DECIMAL(10,2) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tenant (tenant_id),
    INDEX idx_event_type (event_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: pending_signups
-- Temporary storage during checkout process
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS pending_signups (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    checkout_session_id VARCHAR(100) NOT NULL UNIQUE,
    company_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    plan_id INT UNSIGNED NOT NULL,
    admin_first_name VARCHAR(100) NULL,
    admin_last_name VARCHAR(100) NULL,
    metadata JSON NULL,
    status ENUM('pending', 'completed', 'expired') DEFAULT 'pending',
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session (checkout_session_id),
    INDEX idx_status (status),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Add extra seat pricing (metered billing)
-- -----------------------------------------------------
INSERT INTO subscription_plans (name, slug, description, price_monthly, user_limit, features, sort_order, status) VALUES
(
    'Additional User Seat',
    'extra-seat',
    'Add additional licensed seats for teams over 10 users.',
    14.00,
    1,
    '{}',
    99,
    'active'
);
