-- Stripe Integration Tables
-- Migration: 008_stripe_integration.sql
-- NOTE: Foreign keys removed for compatibility - tenant_id is still indexed for performance

-- Stripe Connections - stores tenant's Stripe account connection
CREATE TABLE IF NOT EXISTS stripe_connections (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    connection_type ENUM('connect', 'manual') NOT NULL DEFAULT 'connect',
    stripe_account_id VARCHAR(100) NULL COMMENT 'acct_xxx for Connect',
    stripe_user_id VARCHAR(100) NULL,
    access_token TEXT NULL COMMENT 'OAuth access token (encrypted)',
    refresh_token TEXT NULL COMMENT 'OAuth refresh token (encrypted)',
    publishable_key VARCHAR(255) NULL COMMENT 'Manual mode',
    secret_key TEXT NULL COMMENT 'Manual mode (encrypted)',
    webhook_secret TEXT NULL COMMENT 'Webhook signing secret (encrypted)',
    livemode TINYINT(1) DEFAULT 0,
    charges_enabled TINYINT(1) DEFAULT 0,
    payouts_enabled TINYINT(1) DEFAULT 0,
    details_submitted TINYINT(1) DEFAULT 0,
    business_name VARCHAR(255) NULL,
    connected_at DATETIME NULL,
    last_sync_at DATETIME NULL,
    status ENUM('pending', 'active', 'disconnected', 'error') DEFAULT 'pending',
    error_message TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_tenant (tenant_id),
    INDEX idx_stripe_account (stripe_account_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stripe Customers - maps local clients to Stripe customers
CREATE TABLE IF NOT EXISTS stripe_customers (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED NOT NULL,
    stripe_customer_id VARCHAR(100) NOT NULL COMMENT 'cus_xxx',
    email VARCHAR(255) NULL,
    name VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    default_payment_method VARCHAR(100) NULL COMMENT 'pm_xxx',
    balance INT DEFAULT 0 COMMENT 'Balance in cents',
    currency VARCHAR(3) DEFAULT 'usd',
    metadata JSON NULL,
    synced_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_tenant_client (tenant_id, client_id),
    UNIQUE KEY unique_stripe_customer (tenant_id, stripe_customer_id),
    INDEX idx_stripe_customer (stripe_customer_id),
    INDEX idx_tenant (tenant_id),
    INDEX idx_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stripe Products - synced products/services
CREATE TABLE IF NOT EXISTS stripe_products (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    stripe_product_id VARCHAR(100) NOT NULL COMMENT 'prod_xxx',
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    active TINYINT(1) DEFAULT 1,
    metadata JSON NULL,
    synced_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_stripe_product (tenant_id, stripe_product_id),
    INDEX idx_active (tenant_id, active),
    INDEX idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stripe Prices - pricing for products
CREATE TABLE IF NOT EXISTS stripe_prices (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    stripe_price_id VARCHAR(100) NOT NULL COMMENT 'price_xxx',
    stripe_product_id VARCHAR(100) NOT NULL,
    unit_amount INT NOT NULL COMMENT 'Amount in cents',
    currency VARCHAR(3) DEFAULT 'usd',
    recurring_interval ENUM('day', 'week', 'month', 'year') NULL,
    recurring_interval_count INT NULL,
    type ENUM('one_time', 'recurring') DEFAULT 'one_time',
    active TINYINT(1) DEFAULT 1,
    metadata JSON NULL,
    synced_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_stripe_price (tenant_id, stripe_price_id),
    INDEX idx_product (tenant_id, stripe_product_id),
    INDEX idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stripe Webhook Events - log all webhook events
CREATE TABLE IF NOT EXISTS stripe_webhook_events (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT UNSIGNED NULL,
    stripe_event_id VARCHAR(100) NOT NULL COMMENT 'evt_xxx',
    event_type VARCHAR(100) NOT NULL,
    stripe_account_id VARCHAR(100) NULL COMMENT 'For Connect events',
    api_version VARCHAR(20) NULL,
    payload JSON NOT NULL,
    processed TINYINT(1) DEFAULT 0,
    processing_error TEXT NULL,
    retry_count INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME NULL,
    UNIQUE KEY unique_event (stripe_event_id),
    INDEX idx_tenant_type (tenant_id, event_type),
    INDEX idx_processed (processed, created_at),
    INDEX idx_stripe_account (stripe_account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stripe Payment Intents - track payment attempts
CREATE TABLE IF NOT EXISTS stripe_payment_intents (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    stripe_payment_intent_id VARCHAR(100) NOT NULL COMMENT 'pi_xxx',
    stripe_customer_id VARCHAR(100) NULL,
    invoice_id INT UNSIGNED NULL COMMENT 'Local invoice ID',
    amount INT NOT NULL COMMENT 'Amount in cents',
    currency VARCHAR(3) DEFAULT 'usd',
    status VARCHAR(50) NOT NULL,
    payment_method VARCHAR(100) NULL,
    client_secret VARCHAR(255) NULL,
    description TEXT NULL,
    metadata JSON NULL,
    last_payment_error JSON NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_payment_intent (tenant_id, stripe_payment_intent_id),
    INDEX idx_invoice (invoice_id),
    INDEX idx_status (tenant_id, status),
    INDEX idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stripe Subscriptions - for recurring billing
CREATE TABLE IF NOT EXISTS stripe_subscriptions (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    stripe_subscription_id VARCHAR(100) NOT NULL COMMENT 'sub_xxx',
    stripe_customer_id VARCHAR(100) NOT NULL,
    client_id INT UNSIGNED NULL,
    status VARCHAR(50) NOT NULL,
    current_period_start DATETIME NULL,
    current_period_end DATETIME NULL,
    cancel_at_period_end TINYINT(1) DEFAULT 0,
    canceled_at DATETIME NULL,
    ended_at DATETIME NULL,
    trial_start DATETIME NULL,
    trial_end DATETIME NULL,
    metadata JSON NULL,
    synced_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_subscription (tenant_id, stripe_subscription_id),
    INDEX idx_customer (stripe_customer_id),
    INDEX idx_status (tenant_id, status),
    INDEX idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
