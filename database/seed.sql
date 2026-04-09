-- =====================================================
-- Construction ERP - Sample Data Seed Script
-- 
-- Run this AFTER schema.sql to populate test data
-- =====================================================

SET NAMES 'utf8mb4';

-- =====================================================
-- TENANT & USERS
-- =====================================================

-- Create demo tenant
INSERT IGNORE INTO `tenants` (`id`, `name`, `subdomain`, `email`, `phone`, `address`, `city`, `state`, `zip_code`, `status`, `plan`) VALUES
(1, 'Acme Construction Co.', 'acme', 'admin@acmeconstruction.com', '(555) 123-4567', '123 Builder Lane', 'Austin', 'TX', '78701', 'active', 'professional');

-- Create demo users (password is 'password123' hashed with bcrypt)
INSERT IGNORE INTO `users` (`id`, `tenant_id`, `role_id`, `first_name`, `last_name`, `email`, `password`, `phone`, `status`) VALUES
(1, 1, 1, 'John', 'Admin', 'admin@acmeconstruction.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(555) 123-4567', 'active'),
(2, 1, 2, 'Sarah', 'Manager', 'sarah@acmeconstruction.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(555) 234-5678', 'active'),
(3, 1, 3, 'Mike', 'Accountant', 'mike@acmeconstruction.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(555) 345-6789', 'active'),
(4, 1, 4, 'Bob', 'Contractor', 'bob@acmeconstruction.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(555) 456-7890', 'active');

-- =====================================================
-- CLIENTS
-- =====================================================

INSERT IGNORE INTO `clients` (`id`, `tenant_id`, `name`, `type`, `contact_person`, `email`, `phone`, `address`, `city`, `state`, `zip_code`, `industry`, `payment_terms`, `status`) VALUES
(1, 1, 'Sunrise Properties LLC', 'company', 'David Wilson', 'david@sunriseprops.com', '(555) 111-2222', '456 Real Estate Blvd', 'Austin', 'TX', '78702', 'Real Estate', 30, 'active'),
(2, 1, 'TechPark Developments', 'company', 'Lisa Chen', 'lisa@techpark.dev', '(555) 222-3333', '789 Innovation Dr', 'Austin', 'TX', '78703', 'Technology', 45, 'active'),
(3, 1, 'City of Austin - Parks Dept', 'government', 'James Brown', 'jbrown@austintx.gov', '(555) 333-4444', '100 City Hall Plaza', 'Austin', 'TX', '78704', 'Government', 60, 'active'),
(4, 1, 'Harbor View Homes', 'company', 'Maria Garcia', 'maria@harborview.com', '(555) 444-5555', '321 Lakeside Ave', 'Austin', 'TX', '78705', 'Residential', 30, 'active');

-- =====================================================
-- PROJECTS
-- =====================================================

INSERT IGNORE INTO `projects` (`id`, `tenant_id`, `client_id`, `manager_id`, `name`, `description`, `code`, `address`, `city`, `state`, `start_date`, `end_date`, `contract_value`, `status`, `priority`, `progress`) VALUES
(1, 1, 1, 2, 'Sunrise Office Complex', 'Three-story office building with underground parking', 'PRJ-2024-001', '1000 Business Park Dr', 'Austin', 'TX', '2024-01-15', '2024-12-31', 2500000.00, 'in_progress', 'high', 65),
(2, 1, 2, 2, 'TechPark Data Center', 'State-of-the-art data center facility with cooling systems', 'PRJ-2024-002', '500 Server Farm Rd', 'Austin', 'TX', '2024-03-01', '2025-02-28', 5000000.00, 'in_progress', 'urgent', 40),
(3, 1, 3, 2, 'Zilker Park Pavilion', 'Public pavilion and restroom facilities renovation', 'PRJ-2024-003', 'Zilker Park', 'Austin', 'TX', '2024-06-01', '2024-10-31', 750000.00, 'in_progress', 'medium', 80),
(4, 1, 4, 2, 'Harbor View Residences', 'Luxury townhome development - 12 units', 'PRJ-2024-004', '888 Waterfront Way', 'Austin', 'TX', '2024-02-01', '2025-06-30', 3200000.00, 'planning', 'medium', 15),
(5, 1, 1, 2, 'Sunrise Parking Expansion', 'Additional parking structure for existing complex', 'PRJ-2024-005', '1000 Business Park Dr', 'Austin', 'TX', '2024-09-01', '2025-03-31', 800000.00, 'planning', 'low', 0);

-- =====================================================
-- TASKS
-- =====================================================

INSERT INTO `tasks` (`tenant_id`, `project_id`, `assigned_to`, `title`, `description`, `start_date`, `due_date`, `estimated_hours`, `status`, `priority`) VALUES
-- Project 1 tasks
(1, 1, 4, 'Foundation inspection', 'Final inspection of foundation work', '2024-01-20', '2024-01-25', 8, 'completed', 'high'),
(1, 1, 4, 'Steel framework installation', 'Install main steel structure', '2024-02-01', '2024-03-15', 200, 'completed', 'high'),
(1, 1, 4, 'Electrical rough-in', 'Electrical wiring for all floors', '2024-04-01', '2024-05-15', 150, 'completed', 'medium'),
(1, 1, 4, 'HVAC system installation', 'Install heating and cooling systems', '2024-05-01', '2024-06-30', 180, 'in_progress', 'high'),
(1, 1, 4, 'Interior finishing', 'Drywall, painting, and trim work', '2024-07-01', '2024-09-30', 300, 'pending', 'medium'),
-- Project 2 tasks
(1, 2, 4, 'Site preparation', 'Clear and level site', '2024-03-01', '2024-03-15', 40, 'completed', 'high'),
(1, 2, 4, 'Server room construction', 'Build server room with raised floors', '2024-04-01', '2024-06-30', 250, 'in_progress', 'urgent'),
(1, 2, 4, 'Cooling system installation', 'Install precision cooling units', '2024-07-01', '2024-09-30', 200, 'pending', 'urgent'),
-- Project 3 tasks
(1, 3, 4, 'Demolition of old structure', 'Remove existing pavilion', '2024-06-01', '2024-06-15', 40, 'completed', 'medium'),
(1, 3, 4, 'New pavilion construction', 'Build new covered pavilion', '2024-06-20', '2024-08-31', 160, 'completed', 'medium'),
(1, 3, 4, 'Restroom renovation', 'Update restroom facilities', '2024-08-01', '2024-09-30', 100, 'in_progress', 'medium');

-- =====================================================
-- BUDGETS
-- =====================================================

INSERT INTO `budgets` (`tenant_id`, `project_id`, `category`, `description`, `budgeted_amount`, `spent_amount`) VALUES
-- Project 1
(1, 1, 'Labor', 'Construction labor costs', 800000.00, 520000.00),
(1, 1, 'Materials', 'Building materials and supplies', 900000.00, 650000.00),
(1, 1, 'Equipment', 'Equipment rental and fuel', 200000.00, 145000.00),
(1, 1, 'Subcontractors', 'Specialized subcontractor work', 400000.00, 310000.00),
(1, 1, 'Permits', 'Building permits and inspections', 50000.00, 48000.00),
-- Project 2
(1, 2, 'Labor', 'Construction labor', 1500000.00, 600000.00),
(1, 2, 'Materials', 'Specialized data center materials', 2000000.00, 800000.00),
(1, 2, 'Equipment', 'Heavy equipment and tools', 500000.00, 200000.00),
(1, 2, 'Technology', 'Server room specialized equipment', 800000.00, 400000.00),
-- Project 3
(1, 3, 'Labor', 'Labor costs', 300000.00, 240000.00),
(1, 3, 'Materials', 'Construction materials', 350000.00, 280000.00),
(1, 3, 'Equipment', 'Equipment rental', 50000.00, 40000.00);

-- =====================================================
-- EMPLOYEES
-- =====================================================

INSERT INTO `employees` (`id`, `tenant_id`, `user_id`, `employee_id`, `first_name`, `last_name`, `email`, `phone`, `job_title`, `department`, `hire_date`, `payment_type`, `hourly_rate`, `overtime_threshold`, `status`) VALUES
(1, 1, 4, 'EMP-001', 'Bob', 'Builder', 'bob@acmeconstruction.com', '(555) 456-7890', 'Lead Contractor', 'Construction', '2020-03-15', 'hourly', 45.00, 40, 'active'),
(2, 1, NULL, 'EMP-002', 'Carlos', 'Martinez', 'carlos@acmeconstruction.com', '(555) 567-8901', 'Electrician', 'Electrical', '2021-06-01', 'hourly', 38.00, 40, 'active'),
(3, 1, NULL, 'EMP-003', 'Diana', 'Johnson', 'diana@acmeconstruction.com', '(555) 678-9012', 'Plumber', 'Plumbing', '2019-09-10', 'hourly', 40.00, 40, 'active'),
(4, 1, NULL, 'EMP-004', 'Eric', 'Williams', 'eric@acmeconstruction.com', '(555) 789-0123', 'Carpenter', 'Construction', '2022-01-20', 'hourly', 35.00, 40, 'active'),
(5, 1, NULL, 'EMP-005', 'Frank', 'Davis', 'frank@acmeconstruction.com', '(555) 890-1234', 'HVAC Technician', 'HVAC', '2021-11-15', 'hourly', 42.00, 40, 'active'),
(6, 1, NULL, 'EMP-006', 'Grace', 'Miller', 'grace@acmeconstruction.com', '(555) 901-2345', 'Project Coordinator', 'Administration', '2023-02-01', 'salary', NULL, 40, 'active');

-- Update salary for Grace
UPDATE `employees` SET `salary` = 4500.00 WHERE `id` = 6;

-- =====================================================
-- TIME LOGS
-- =====================================================

INSERT INTO `time_logs` (`tenant_id`, `employee_id`, `project_id`, `log_date`, `start_time`, `end_time`, `hours`, `description`, `is_overtime`, `billable`, `approved`) VALUES
-- Recent time logs
(1, 1, 1, CURDATE() - INTERVAL 1 DAY, '07:00:00', '16:00:00', 8.0, 'HVAC ductwork installation', FALSE, TRUE, TRUE),
(1, 2, 1, CURDATE() - INTERVAL 1 DAY, '07:30:00', '16:30:00', 8.0, 'Electrical panel installation', FALSE, TRUE, TRUE),
(1, 5, 1, CURDATE() - INTERVAL 1 DAY, '08:00:00', '18:00:00', 9.0, 'HVAC unit setup - overtime', TRUE, TRUE, TRUE),
(1, 1, 2, CURDATE() - INTERVAL 2 DAY, '07:00:00', '15:00:00', 7.0, 'Server room framing', FALSE, TRUE, TRUE),
(1, 3, 3, CURDATE() - INTERVAL 2 DAY, '08:00:00', '17:00:00', 8.0, 'Restroom plumbing', FALSE, TRUE, TRUE),
(1, 4, 1, CURDATE() - INTERVAL 3 DAY, '07:00:00', '16:00:00', 8.0, 'Interior trim work', FALSE, TRUE, FALSE),
(1, 1, 1, CURDATE(), '07:00:00', '12:00:00', 5.0, 'Morning shift - continuing HVAC', FALSE, TRUE, FALSE);

-- =====================================================
-- INVOICES
-- =====================================================

INSERT INTO `invoices` (`id`, `tenant_id`, `client_id`, `project_id`, `invoice_number`, `issue_date`, `due_date`, `subtotal`, `tax_rate`, `tax_amount`, `total_amount`, `paid_amount`, `status`, `notes`) VALUES
(1, 1, 1, 1, 'INV-2024-001', '2024-03-01', '2024-03-31', 250000.00, 8.25, 20625.00, 270625.00, 270625.00, 'paid', 'Progress payment - Foundation complete'),
(2, 1, 1, 1, 'INV-2024-002', '2024-05-01', '2024-05-31', 350000.00, 8.25, 28875.00, 378875.00, 378875.00, 'paid', 'Progress payment - Steel framework'),
(3, 1, 1, 1, 'INV-2024-003', '2024-07-01', '2024-07-31', 300000.00, 8.25, 24750.00, 324750.00, 200000.00, 'partial', 'Progress payment - Electrical complete'),
(4, 1, 2, 2, 'INV-2024-004', '2024-04-01', '2024-05-01', 500000.00, 8.25, 41250.00, 541250.00, 541250.00, 'paid', 'Initial deposit'),
(5, 1, 2, 2, 'INV-2024-005', '2024-07-01', '2024-08-01', 750000.00, 8.25, 61875.00, 811875.00, 0.00, 'sent', 'Progress payment - Site work'),
(6, 1, 3, 3, 'INV-2024-006', '2024-08-01', '2024-09-01', 400000.00, 0.00, 0.00, 400000.00, 400000.00, 'paid', 'Government project - Tax exempt');

-- Invoice items
INSERT INTO `invoice_items` (`invoice_id`, `description`, `quantity`, `unit_price`, `amount`, `sort_order`) VALUES
(1, 'Foundation work - labor', 1, 100000.00, 100000.00, 1),
(1, 'Foundation materials - concrete, rebar', 1, 120000.00, 120000.00, 2),
(1, 'Site preparation and excavation', 1, 30000.00, 30000.00, 3),
(2, 'Steel framework - materials', 1, 200000.00, 200000.00, 1),
(2, 'Steel framework - installation labor', 1, 150000.00, 150000.00, 2),
(3, 'Electrical rough-in - materials', 1, 80000.00, 80000.00, 1),
(3, 'Electrical rough-in - labor', 1, 120000.00, 120000.00, 2),
(3, 'Panel and breaker installation', 1, 100000.00, 100000.00, 3);

-- =====================================================
-- PAYMENTS
-- =====================================================

INSERT INTO `payments` (`tenant_id`, `invoice_id`, `amount`, `payment_date`, `payment_method`, `reference_number`, `status`) VALUES
(1, 1, 270625.00, '2024-03-25', 'bank_transfer', 'ACH-28374651', 'completed'),
(1, 2, 378875.00, '2024-05-28', 'bank_transfer', 'ACH-29485762', 'completed'),
(1, 3, 200000.00, '2024-07-20', 'check', 'CHK-10452', 'completed'),
(1, 4, 541250.00, '2024-04-15', 'bank_transfer', 'WIRE-TP-001', 'completed'),
(1, 6, 400000.00, '2024-08-20', 'bank_transfer', 'COA-PARKS-2024', 'completed');

-- =====================================================
-- INVENTORY
-- =====================================================

INSERT INTO `suppliers` (`id`, `tenant_id`, `name`, `contact_person`, `email`, `phone`, `address`, `city`, `state`, `payment_terms`, `status`) VALUES
(1, 1, 'BuildersSupply Co.', 'Tom Wilson', 'tom@builderssupply.com', '(555) 111-0001', '100 Warehouse Way', 'Austin', 'TX', 30, 'active'),
(2, 1, 'ElectroPro Distributors', 'Amy Lee', 'amy@electropro.com', '(555) 222-0002', '200 Electric Ave', 'Austin', 'TX', 45, 'active'),
(3, 1, 'PlumbMaster Supply', 'Chris Davis', 'chris@plumbmaster.com', '(555) 333-0003', '300 Pipe Lane', 'Austin', 'TX', 30, 'active');

INSERT INTO `inventory_items` (`id`, `tenant_id`, `supplier_id`, `sku`, `name`, `description`, `category`, `unit`, `quantity`, `min_quantity`, `unit_cost`, `location`, `status`) VALUES
(1, 1, 1, 'LUM-2X4-8', '2x4 Lumber 8ft', 'Standard construction lumber', 'Lumber', 'piece', 500, 100, 4.50, 'Warehouse A-1', 'active'),
(2, 1, 1, 'LUM-2X6-10', '2x6 Lumber 10ft', 'Heavy duty lumber', 'Lumber', 'piece', 250, 50, 7.25, 'Warehouse A-1', 'active'),
(3, 1, 1, 'PLY-4X8-3/4', 'Plywood 4x8 3/4"', 'Construction grade plywood', 'Lumber', 'sheet', 150, 30, 32.00, 'Warehouse A-2', 'active'),
(4, 1, 1, 'CON-80LB', 'Concrete Mix 80lb', 'Ready-mix concrete', 'Concrete', 'bag', 200, 50, 5.50, 'Warehouse B-1', 'active'),
(5, 1, 2, 'WIRE-12-2', 'Electrical Wire 12/2', '250ft roll Romex', 'Electrical', 'roll', 45, 10, 85.00, 'Warehouse C-1', 'active'),
(6, 1, 2, 'WIRE-14-2', 'Electrical Wire 14/2', '250ft roll Romex', 'Electrical', 'roll', 60, 15, 65.00, 'Warehouse C-1', 'active'),
(7, 1, 2, 'BRKR-20A', 'Circuit Breaker 20A', 'Single pole breaker', 'Electrical', 'piece', 100, 20, 8.50, 'Warehouse C-2', 'active'),
(8, 1, 3, 'PIPE-PVC-2', 'PVC Pipe 2" 10ft', 'Schedule 40 PVC', 'Plumbing', 'piece', 80, 20, 12.00, 'Warehouse D-1', 'active'),
(9, 1, 3, 'PIPE-COP-1/2', 'Copper Pipe 1/2" 10ft', 'Type L copper', 'Plumbing', 'piece', 50, 15, 28.00, 'Warehouse D-1', 'active'),
(10, 1, 1, 'DRY-4X8', 'Drywall 4x8 1/2"', 'Standard drywall', 'Drywall', 'sheet', 300, 50, 12.50, 'Warehouse A-3', 'active');

-- =====================================================
-- SETTINGS
-- =====================================================

INSERT INTO `settings` (`tenant_id`, `key`, `value`, `type`) VALUES
(1, 'company_name', 'Acme Construction Co.', 'string'),
(1, 'company_address', '123 Builder Lane, Austin, TX 78701', 'string'),
(1, 'company_phone', '(555) 123-4567', 'string'),
(1, 'company_email', 'info@acmeconstruction.com', 'string'),
(1, 'timezone', 'America/Chicago', 'string'),
(1, 'date_format', 'MM/DD/YYYY', 'string'),
(1, 'currency', 'USD', 'string'),
(1, 'tax_rate', '8.25', 'number'),
(1, 'invoice_prefix', 'INV-', 'string'),
(1, 'invoice_footer', 'Thank you for your business! Payment is due within 30 days.', 'string');

-- =====================================================
-- COMPLETED
-- =====================================================

SELECT 'Seed data inserted successfully!' AS Status;
SELECT 
    (SELECT COUNT(*) FROM tenants) AS Tenants,
    (SELECT COUNT(*) FROM users) AS Users,
    (SELECT COUNT(*) FROM clients) AS Clients,
    (SELECT COUNT(*) FROM projects) AS Projects,
    (SELECT COUNT(*) FROM tasks) AS Tasks,
    (SELECT COUNT(*) FROM employees) AS Employees,
    (SELECT COUNT(*) FROM invoices) AS Invoices,
    (SELECT COUNT(*) FROM inventory_items) AS InventoryItems;
