-- Ensure traffio user exists and has the correct password and permissions
CREATE USER IF NOT EXISTS 'traffio'@'%' IDENTIFIED BY 'secure_pass';
ALTER USER 'traffio'@'%' IDENTIFIED BY 'secure_pass';
GRANT ALL PRIVILEGES ON traffio_erp.* TO 'traffio'@'%';
FLUSH PRIVILEGES;
