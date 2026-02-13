-- Developer Authentication System
-- Separate table for developer/support staff credentials

CREATE TABLE IF NOT EXISTS developers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    role ENUM('developer', 'support', 'admin') DEFAULT 'support',
    avatar VARCHAR(255) NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin developer
-- Password: "admin123" (you should change this immediately!)
INSERT INTO developers (email, password, name, role) VALUES (
    'admin@buildflow.dev',
    '$2y$10$LhKwNZQ5r5ERPMoQJbPy4uWwXH/Hn4L4WvJq.Kz.YHwZtZRfnQyOm',
    'Admin Developer',
    'admin'
);

SELECT 'Developers table created with default admin!' AS Status;
