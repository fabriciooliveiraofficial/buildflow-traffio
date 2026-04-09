-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 16/02/2026 às 15:16
-- Versão do servidor: 11.8.3-MariaDB-log
-- Versão do PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `u714643564_db_buildflow`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `accounting_periods`
--

DROP TABLE IF EXISTS `accounting_periods`;
CREATE TABLE `accounting_periods` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `fiscal_year` year(4) NOT NULL,
  `name` varchar(50) NOT NULL COMMENT 'e.g. Jan 2025',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_closed` tinyint(1) DEFAULT 0,
  `closed_at` timestamp NULL DEFAULT NULL,
  `closed_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL COMMENT 'created, updated, deleted, etc.',
  `entity_type` varchar(100) DEFAULT NULL COMMENT 'project, invoice, task, etc.',
  `entity_id` int(10) UNSIGNED DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `budgets`
--

DROP TABLE IF EXISTS `budgets`;
CREATE TABLE `budgets` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `project_id` int(10) UNSIGNED NOT NULL,
  `category` varchar(100) NOT NULL COMMENT 'labor, materials, equipment, etc.',
  `description` varchar(255) DEFAULT NULL,
  `budgeted_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `spent_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `budgets`
--

INSERT INTO `budgets` (`id`, `tenant_id`, `project_id`, `category`, `description`, `budgeted_amount`, `spent_amount`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Labor', 'Construction labor costs', 800000.00, 520000.00, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(2, 1, 1, 'Materials', 'Building materials and supplies', 900000.00, 650000.00, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(3, 1, 1, 'Equipment', 'Equipment rental and fuel', 200000.00, 145000.00, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(4, 1, 1, 'Subcontractors', 'Specialized subcontractor work', 400000.00, 310000.00, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(5, 1, 1, 'Permits', 'Building permits and inspections', 50000.00, 48000.00, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(6, 1, 2, 'Labor', 'Construction labor', 1500000.00, 600000.00, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(7, 1, 2, 'Materials', 'Specialized data center materials', 2000000.00, 800000.00, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(8, 1, 2, 'Equipment', 'Heavy equipment and tools', 500000.00, 200000.00, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(9, 1, 2, 'Technology', 'Server room specialized equipment', 800000.00, 400000.00, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(10, 1, 3, 'Labor', 'Labor costs', 300000.00, 240000.00, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(11, 1, 3, 'Materials', 'Construction materials', 350000.00, 280000.00, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(12, 1, 3, 'Equipment', 'Equipment rental', 50000.00, 40000.00, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(14, 7, 8, 'other', '', 13731.00, 0.00, '2025-12-10 01:26:18', '2025-12-10 01:26:18'),
(15, 7, 26, 'materials', '', 8700.00, 1990.56, '2025-12-11 18:42:51', '2025-12-11 19:51:18');

-- --------------------------------------------------------

--
-- Estrutura para tabela `chart_of_accounts`
--

DROP TABLE IF EXISTS `chart_of_accounts`;
CREATE TABLE `chart_of_accounts` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `code` varchar(20) NOT NULL COMMENT 'e.g. 1000',
  `name` varchar(100) NOT NULL COMMENT 'e.g. Cash in Bank',
  `type` enum('asset','liability','equity','income','expense') NOT NULL,
  `subtype` varchar(50) DEFAULT NULL COMMENT 'current_asset, long_term_liability, etc.',
  `description` text DEFAULT NULL,
  `is_system` tinyint(1) DEFAULT 0 COMMENT 'Prevent deletion of core accounts',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `chart_of_accounts`
--

INSERT INTO `chart_of_accounts` (`id`, `tenant_id`, `code`, `name`, `type`, `subtype`, `description`, `is_system`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, '1000', 'Cash on Hand', 'asset', 'current_asset', NULL, 1, 'active', '2025-12-08 18:37:19', '2025-12-08 18:37:19'),
(2, 1, '1001', 'Checking Account', 'asset', 'current_asset', NULL, 0, 'active', '2025-12-08 18:37:19', '2025-12-08 18:37:19'),
(3, 1, '1100', 'Accounts Receivable', 'asset', 'current_asset', NULL, 1, 'active', '2025-12-08 18:37:19', '2025-12-08 18:37:19'),
(4, 1, '1200', 'Inventory Asset', 'asset', 'current_asset', NULL, 1, 'active', '2025-12-08 18:37:19', '2025-12-08 18:37:19'),
(5, 1, '1500', 'Equipment & Machinery', 'asset', 'fixed_asset', NULL, 0, 'active', '2025-12-08 18:37:19', '2025-12-08 18:37:19'),
(6, 1, '2000', 'Accounts Payable', 'liability', 'current_liability', NULL, 1, 'active', '2025-12-08 18:37:19', '2025-12-08 18:37:19'),
(7, 1, '2100', 'Credit Card Payable', 'liability', 'current_liability', NULL, 0, 'active', '2025-12-08 18:37:19', '2025-12-08 18:37:19'),
(8, 1, '2200', 'Sales Tax Payable', 'liability', 'current_liability', NULL, 1, 'active', '2025-12-08 18:37:19', '2025-12-08 18:37:19'),
(9, 1, '2300', 'Payroll Liabilities', 'liability', 'current_liability', NULL, 1, 'active', '2025-12-08 18:37:19', '2025-12-08 18:37:19'),
(10, 1, '3000', 'Owner Equity', 'equity', 'equity', NULL, 1, 'active', '2025-12-08 18:37:19', '2025-12-08 18:37:19'),
(11, 1, '3900', 'Retained Earnings', 'equity', 'equity', NULL, 1, 'active', '2025-12-08 18:37:19', '2025-12-08 18:37:19'),
(12, 1, '4000', 'Construction Revenue', 'income', 'operating_revenue', NULL, 1, 'active', '2025-12-08 18:37:19', '2025-12-08 18:37:19'),
(13, 1, '4100', 'Service Revenue', 'income', 'operating_revenue', NULL, 0, 'active', '2025-12-08 18:37:19', '2025-12-08 18:37:19'),
(14, 1, '4200', 'Other Income', 'income', 'other_income', NULL, 0, 'active', '2025-12-08 18:37:19', '2025-12-08 18:37:19'),
(15, 1, '5000', 'Cost of Goods Sold - Labor', 'expense', 'cost_of_goods_sold', NULL, 1, 'active', '2025-12-08 18:37:19', '2025-12-08 18:37:19'),
(16, 1, '5100', 'Cost of Goods Sold - Materials', 'expense', 'cost_of_goods_sold', NULL, 1, 'active', '2025-12-08 18:37:19', '2025-12-08 18:37:19'),
(17, 1, '5200', 'Subcontractor Expense', 'expense', 'cost_of_goods_sold', NULL, 1, 'active', '2025-12-08 18:37:19', '2025-12-08 18:37:19'),
(18, 1, '6000', 'Advertising & Marketing', 'expense', 'operating_expense', NULL, 0, 'active', '2025-12-08 18:37:19', '2025-12-08 18:37:19'),
(19, 1, '6100', 'insurance', 'expense', 'operating_expense', NULL, 0, 'active', '2025-12-08 18:37:19', '2025-12-08 18:37:19'),
(20, 1, '6200', 'Office Supplies', 'expense', 'operating_expense', NULL, 0, 'active', '2025-12-08 18:37:19', '2025-12-08 18:37:19'),
(21, 1, '6300', 'Rent & Lease', 'expense', 'operating_expense', NULL, 0, 'active', '2025-12-08 18:37:19', '2025-12-08 18:37:19'),
(22, 1, '6400', 'Utilities', 'expense', 'operating_expense', NULL, 0, 'active', '2025-12-08 18:37:19', '2025-12-08 18:37:19'),
(24, 7, '23', 'Bank of America', 'expense', 'operating_expense', '', 0, 'active', '2025-12-08 20:16:03', '2025-12-09 20:34:48'),
(26, 10, '1000', 'Projects', 'asset', 'operating_revenue', '', 0, 'active', '2025-12-12 13:59:32', '2025-12-12 13:59:32'),
(27, 10, '2000', 'Materials', 'liability', 'operating_expense', '', 0, 'active', '2025-12-12 14:00:04', '2025-12-12 14:00:04'),
(28, 10, '1001', 'Bank of America 7898', 'asset', 'current_asset', '', 0, 'active', '2025-12-12 14:02:34', '2025-12-12 14:02:34'),
(29, 10, '2001', 'Home Depot', 'liability', 'current_liability', '', 0, 'active', '2025-12-12 14:02:55', '2025-12-12 14:02:55'),
(30, 10, '2002', 'Thumbtack', 'liability', 'operating_expense', '', 0, 'active', '2025-12-15 18:35:57', '2025-12-15 18:35:57'),
(31, 10, '2003', 'Amex-02009', 'liability', 'current_liability', '', 0, 'active', '2025-12-15 18:37:12', '2025-12-15 18:37:12');

-- --------------------------------------------------------

--
-- Estrutura para tabela `clients`
--

DROP TABLE IF EXISTS `clients`;
CREATE TABLE `clients` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('company','individual','government') DEFAULT 'company',
  `contact_person` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'USA',
  `industry` varchar(100) DEFAULT NULL,
  `payment_terms` int(11) DEFAULT 30 COMMENT 'Days until payment due',
  `credit_limit` decimal(15,2) DEFAULT NULL,
  `tax_id` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `clients`
--

INSERT INTO `clients` (`id`, `tenant_id`, `name`, `type`, `contact_person`, `email`, `phone`, `website`, `address`, `city`, `state`, `zip_code`, `country`, `industry`, `payment_terms`, `credit_limit`, `tax_id`, `notes`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Sunrise Properties LLC', 'company', 'David Wilson', 'david@sunriseprops.com', '(555) 111-2222', NULL, '456 Real Estate Blvd', 'Austin', 'TX', '78702', 'USA', 'Real Estate', 30, NULL, NULL, NULL, 'active', '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(2, 1, 'TechPark Developments', 'company', 'Lisa Chen', 'lisa@techpark.dev', '(555) 222-3333', NULL, '789 Innovation Dr', 'Austin', 'TX', '78703', 'USA', 'Technology', 45, NULL, NULL, NULL, 'active', '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(3, 1, 'City of Austin - Parks Dept', 'government', 'James Brown', 'jbrown@austintx.gov', '(555) 333-4444', NULL, '100 City Hall Plaza', 'Austin', 'TX', '78704', 'USA', 'Government', 60, NULL, NULL, NULL, 'active', '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(4, 1, 'Harbor View Homes', 'company', 'Maria Garcia', 'maria@harborview.com', '(555) 444-5555', NULL, '321 Lakeside Ave', 'Austin', 'TX', '78705', 'USA', 'Residential', 30, NULL, NULL, NULL, 'active', '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(5, 7, 'Teste01', 'company', 'Teste01', 'teste01@teste.com', '4049257000', NULL, '', '', '', '', 'USA', NULL, 30, NULL, NULL, '', 'active', '2025-12-08 17:20:16', '2025-12-08 17:20:16'),
(6, 7, 'Del Evans', 'company', 'Del Evans', 'delevans@email.com', '', NULL, '', '', '', '', 'USA', NULL, 30, NULL, NULL, '', 'active', '2025-12-09 22:41:05', '2025-12-09 22:41:05'),
(7, 7, 'Erica Wheller', 'company', 'Erica Wheller', 'whlerica3@gmail.com', '(786) 403-6777', NULL, '610 Augusta drive', 'Fairburn', 'GA', '30213', 'USA', NULL, 30, NULL, NULL, '', 'active', '2025-12-09 22:42:16', '2025-12-09 22:42:16'),
(8, 7, 'Gentil', 'company', 'Gentil', 'gentil@email.com', '', NULL, '', '', '', '', 'USA', NULL, 30, NULL, NULL, '', 'active', '2025-12-09 22:42:51', '2025-12-09 22:42:51'),
(9, 7, 'Ricardo', 'company', 'Ricardo', '', '', NULL, '', '', '', '', 'USA', NULL, 30, NULL, NULL, '', 'active', '2025-12-09 22:43:28', '2025-12-09 22:43:28'),
(10, 7, 'All Remodeling', 'company', 'All Remodeling', 'remodeling@allremodelingservices.com', '', NULL, '', '', '', '', 'USA', NULL, 30, NULL, NULL, '', 'active', '2025-12-09 22:44:24', '2025-12-09 22:44:24'),
(11, 7, 'Georgia', 'company', 'Georgia', 'georgia@email.com', '', NULL, '', '', '', '', 'USA', NULL, 30, NULL, NULL, '', 'active', '2025-12-09 22:44:49', '2025-12-09 22:44:49'),
(12, 7, 'Unarose Hogan', 'company', 'Unarose Hogan', 'unaroseh@gmail.com', '', NULL, '', '', '', '', 'USA', NULL, 30, NULL, NULL, '', 'active', '2025-12-09 22:45:10', '2025-12-09 22:45:10'),
(13, 7, 'Neni Valentine', 'company', 'Neni Valentine', 'nmvalentine@yahoo.com', '', NULL, '', '', '', '', 'USA', NULL, 30, NULL, NULL, '', 'active', '2025-12-09 22:45:41', '2025-12-09 22:45:41'),
(14, 7, 'Sean Cotton', 'company', 'Sean Cotton', 'seanwencotton@gmail.com', '', NULL, '', '', '', '', 'USA', NULL, 30, NULL, NULL, '', 'active', '2025-12-09 22:46:04', '2025-12-09 22:46:04'),
(15, 7, 'Visa Ferrell', 'company', 'Visa Ferrell', 'vferrell23@yahoo.com', '(240) 447-8100', NULL, '', '', '', '', 'USA', NULL, 30, NULL, NULL, '', 'active', '2025-12-09 22:47:00', '2025-12-09 22:47:00'),
(16, 7, 'Jane Abel', 'company', 'Jane Abel', 'janeabel7@yahoo.com', '', NULL, '', '', '', '', 'USA', NULL, 30, NULL, NULL, '', 'active', '2025-12-09 22:47:17', '2025-12-09 22:47:17'),
(17, 7, 'Restoration 1', 'company', 'Restoration 1', 'moses@r1atl.com', '(678) 238-2382', NULL, '', '', '', '', 'USA', NULL, 30, NULL, NULL, '', 'active', '2025-12-09 22:47:41', '2025-12-09 22:47:41'),
(18, 7, 'Nadia & Frank Wasti', 'company', 'Nadia & Frank Wasti', 'frank.wast@gmail.com', '', NULL, '', '', '', '', 'USA', NULL, 30, NULL, NULL, '', 'active', '2025-12-09 22:48:02', '2025-12-09 22:48:02'),
(19, 7, 'Walter', 'company', 'Walter ', '', '', NULL, '', '', '', '', 'USA', NULL, 30, NULL, NULL, '', 'active', '2025-12-10 19:50:42', '2025-12-10 19:50:42'),
(20, 10, 'Traffio-test', 'company', 'Fabricio', 'fabriciooliveiraofficial@gmail.com', '4049257024', NULL, 'Rua Desembargador Antonio Franco Ferreira da Costa 665 - Cajuru', 'Marietta', 'PR', '30144', 'USA', NULL, 30, NULL, NULL, '', 'active', '2025-12-11 15:27:41', '2025-12-11 15:27:41'),
(21, 7, 'Ricardo Ismael', 'company', 'Ricardo Ismael', '', '', NULL, '', '', '', '', 'USA', NULL, 30, NULL, NULL, '', 'active', '2025-12-16 16:41:35', '2025-12-16 16:41:35'),
(22, 7, 'Cintia', 'company', '', '', '', NULL, '', '', '', '', 'USA', NULL, 30, NULL, NULL, '', 'active', '2025-12-18 17:40:00', '2025-12-18 17:40:00'),
(23, 7, 'Bianca Furse', 'company', 'Bianca Furse', 'fursebianca@gmail.com', '', NULL, '1726 Fairburn Rd SW', 'Atlanta', 'GA', '', 'USA', NULL, 30, NULL, NULL, '', 'active', '2026-01-20 11:49:08', '2026-01-20 11:49:08'),
(24, 7, 'Angela Starkey', 'company', 'Angela Starkey', 'angelastarkey@gmail.com', '678 464 5451', NULL, '17 Rockland Pl', 'Decatur', 'GA', '30030', 'USA', NULL, 30, NULL, NULL, '', 'active', '2026-01-20 13:22:36', '2026-01-20 13:22:36'),
(25, 7, 'Monique Bell', 'company', 'Monique Bell', 'moniquerenee.cool@gmail.com', '', NULL, '492 Boone Rd', 'Newnan', 'GA', '30263', 'USA', NULL, 30, NULL, NULL, '', 'active', '2026-01-20 13:37:17', '2026-01-20 13:37:17'),
(26, 7, 'Renaldo Tozzo', 'company', 'Renaldo Tozzo', '', '', NULL, '', '', '', '', 'USA', NULL, 30, NULL, NULL, '', 'active', '2026-01-26 18:30:21', '2026-01-26 18:30:21');

-- --------------------------------------------------------

--
-- Estrutura para tabela `custom_categories`
--

DROP TABLE IF EXISTS `custom_categories`;
CREATE TABLE `custom_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `type` varchar(50) NOT NULL COMMENT 'projects, expenses, tasks, inventory',
  `name` varchar(100) NOT NULL,
  `color` varchar(20) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `developers`
--

DROP TABLE IF EXISTS `developers`;
CREATE TABLE `developers` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `role` enum('developer','support','admin') DEFAULT 'support',
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `developers`
--

INSERT INTO `developers` (`id`, `email`, `password`, `name`, `role`, `status`, `last_login_at`, `created_at`) VALUES
(4, 'admin@buildflow.dev', '$2y$10$SV7vrMrE4BHLvuEz30xGKOU6KNn1FcBliTDLvv6NnRawM97snsSQS', 'Admin Developer', 'admin', 'active', '2025-12-12 21:03:26', '2025-12-10 21:54:25');

-- --------------------------------------------------------

--
-- Estrutura para tabela `documents`
--

DROP TABLE IF EXISTS `documents`;
CREATE TABLE `documents` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `project_id` int(10) UNSIGNED DEFAULT NULL,
  `uploaded_by` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `size` int(10) UNSIGNED DEFAULT NULL COMMENT 'Bytes',
  `category` varchar(100) DEFAULT NULL COMMENT 'contract, blueprint, photo, permit, etc.',
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `email_automations`
--

DROP TABLE IF EXISTS `email_automations`;
CREATE TABLE `email_automations` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `trigger_event` varchar(50) NOT NULL COMMENT 'invoice_created, invoice_overdue, payment_received, etc.',
  `template_id` int(11) DEFAULT NULL,
  `is_enabled` tinyint(1) DEFAULT 1,
  `delay_minutes` int(11) DEFAULT 0 COMMENT 'Wait time before sending',
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional conditions for triggering' CHECK (json_valid(`conditions`)),
  `send_to` enum('client','user','custom') DEFAULT 'client',
  `custom_recipients` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Custom email addresses' CHECK (json_valid(`custom_recipients`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `email_bounces`
--

DROP TABLE IF EXISTS `email_bounces`;
CREATE TABLE `email_bounces` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `bounce_count` int(11) DEFAULT 1,
  `reason` text DEFAULT NULL,
  `first_bounce_at` datetime NOT NULL,
  `last_bounce_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `email_logs`
--

DROP TABLE IF EXISTS `email_logs`;
CREATE TABLE `email_logs` (
  `id` bigint(20) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `queue_id` bigint(20) DEFAULT NULL,
  `message_id` varchar(255) DEFAULT NULL COMMENT 'SMTP Message-ID header',
  `to_email` varchar(255) NOT NULL,
  `to_name` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `context_type` varchar(50) DEFAULT NULL,
  `context_id` int(11) DEFAULT NULL,
  `status` enum('sent','delivered','opened','clicked','bounced','failed') DEFAULT 'sent',
  `opened_at` datetime DEFAULT NULL,
  `opened_count` int(11) DEFAULT 0,
  `clicked_at` datetime DEFAULT NULL,
  `clicked_count` int(11) DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `smtp_response` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP when opened/clicked',
  `user_agent` text DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `email_logs`
--

INSERT INTO `email_logs` (`id`, `tenant_id`, `queue_id`, `message_id`, `to_email`, `to_name`, `subject`, `context_type`, `context_id`, `status`, `opened_at`, `opened_count`, `clicked_at`, `clicked_count`, `error_message`, `smtp_response`, `ip_address`, `user_agent`, `sent_at`, `created_at`) VALUES
(1, 10, NULL, '<bf_693c172abb4e89.22144757@>', 'fabriciooliveiraofficial@gmail.com', NULL, 'Teste Envio', NULL, NULL, 'sent', NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, '2025-12-12 08:22:51', '2025-12-12 08:22:51');

-- --------------------------------------------------------

--
-- Estrutura para tabela `email_queue`
--

DROP TABLE IF EXISTS `email_queue`;
CREATE TABLE `email_queue` (
  `id` bigint(20) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `to_addresses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '[{email, name}]' CHECK (json_valid(`to_addresses`)),
  `cc_addresses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`cc_addresses`)),
  `bcc_addresses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`bcc_addresses`)),
  `subject` varchar(255) NOT NULL,
  `body_html` text DEFAULT NULL,
  `body_plain` text DEFAULT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '[{filename, path, mime_type, size}]' CHECK (json_valid(`attachments`)),
  `template_id` int(11) DEFAULT NULL,
  `context_type` varchar(50) DEFAULT NULL COMMENT 'invoice, project, estimate, client',
  `context_id` int(11) DEFAULT NULL,
  `priority` tinyint(4) DEFAULT 5 COMMENT '1=highest, 10=lowest',
  `status` enum('pending','processing','sent','failed','cancelled') DEFAULT 'pending',
  `attempts` int(11) DEFAULT 0,
  `max_attempts` int(11) DEFAULT 3,
  `last_error` text DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL COMMENT 'For delayed sending',
  `processing_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL COMMENT 'user_id who initiated'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `email_settings`
--

DROP TABLE IF EXISTS `email_settings`;
CREATE TABLE `email_settings` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `smtp_host` varchar(255) DEFAULT NULL,
  `smtp_port` int(11) DEFAULT 587,
  `encryption` enum('none','tls','ssl','starttls') DEFAULT 'tls',
  `username` varchar(255) DEFAULT NULL,
  `password_encrypted` text DEFAULT NULL COMMENT 'AES-256 encrypted password',
  `sender_name` varchar(255) DEFAULT NULL,
  `sender_email` varchar(255) DEFAULT NULL,
  `reply_to_email` varchar(255) DEFAULT NULL,
  `signature_html` text DEFAULT NULL,
  `signature_plain` text DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verified_at` datetime DEFAULT NULL,
  `daily_limit` int(11) DEFAULT 100 COMMENT 'Max emails per day',
  `emails_sent_today` int(11) DEFAULT 0,
  `last_reset_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `email_settings`
--

INSERT INTO `email_settings` (`id`, `tenant_id`, `smtp_host`, `smtp_port`, `encryption`, `username`, `password_encrypted`, `sender_name`, `sender_email`, `reply_to_email`, `signature_html`, `signature_plain`, `is_verified`, `verified_at`, `daily_limit`, `emails_sent_today`, `last_reset_date`, `created_at`, `updated_at`) VALUES
(1, 10, 'smtp.hostinger.com', 465, 'ssl', 'noreplay@buildflow-traffio.com', 'frvqxk9qixhHNMfYaE5EzWStaDQSLogM+1ihZY46E+ft+B76oXRGnGgWwquA19Ly', 'Buildflow by Traffio', 'noreplay@buildflow-traffio.com', '', NULL, NULL, 1, '2025-12-12 08:20:01', 100, 1, '2025-12-12', '2025-12-12 08:13:57', '2025-12-12 13:22:51');

-- --------------------------------------------------------

--
-- Estrutura para tabela `email_templates`
--

DROP TABLE IF EXISTS `email_templates`;
CREATE TABLE `email_templates` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `slug` varchar(50) NOT NULL COMMENT 'invoice, estimate, reminder, welcome, etc.',
  `name` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body_html` text DEFAULT NULL,
  `body_plain` text DEFAULT NULL,
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'List of available tokens for this template' CHECK (json_valid(`variables`)),
  `is_system` tinyint(1) DEFAULT 0 COMMENT 'System templates cannot be deleted',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `email_unsubscribes`
--

DROP TABLE IF EXISTS `email_unsubscribes`;
CREATE TABLE `email_unsubscribes` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `unsubscribed_at` datetime NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `employees`
--

DROP TABLE IF EXISTS `employees`;
CREATE TABLE `employees` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Link to user account if exists',
  `employee_id` varchar(50) DEFAULT NULL COMMENT 'EMP-00001 style ID',
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `termination_date` date DEFAULT NULL,
  `job_title` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `payment_type` enum('hourly','daily','salary','project','commission') DEFAULT 'hourly',
  `hourly_rate` decimal(10,2) DEFAULT NULL,
  `daily_rate` decimal(10,2) DEFAULT NULL,
  `salary` decimal(15,2) DEFAULT NULL,
  `commission_rate` decimal(5,2) DEFAULT NULL COMMENT 'Percentage',
  `overtime_multiplier` decimal(3,2) DEFAULT 1.50,
  `overtime_threshold` int(11) DEFAULT 40 COMMENT 'Hours per week',
  `tax_id` varchar(50) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account` varchar(255) DEFAULT NULL,
  `bank_routing` varchar(50) DEFAULT NULL,
  `emergency_contact` varchar(255) DEFAULT NULL,
  `emergency_phone` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','inactive','terminated') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `employees`
--

INSERT INTO `employees` (`id`, `tenant_id`, `user_id`, `employee_id`, `first_name`, `last_name`, `email`, `phone`, `address`, `city`, `state`, `zip_code`, `hire_date`, `termination_date`, `job_title`, `department`, `payment_type`, `hourly_rate`, `daily_rate`, `salary`, `commission_rate`, `overtime_multiplier`, `overtime_threshold`, `tax_id`, `bank_name`, `bank_account`, `bank_routing`, `emergency_contact`, `emergency_phone`, `notes`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 4, 'EMP-001', 'Bob', 'Builder', 'bob@acmeconstruction.com', '(555) 456-7890', NULL, NULL, NULL, NULL, '2020-03-15', NULL, 'Lead Contractor', 'Construction', 'hourly', 45.00, NULL, NULL, NULL, 1.50, 40, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(2, 1, NULL, 'EMP-002', 'Carlos', 'Martinez', 'carlos@acmeconstruction.com', '(555) 567-8901', NULL, NULL, NULL, NULL, '2021-06-01', NULL, 'Electrician', 'Electrical', 'hourly', 38.00, NULL, NULL, NULL, 1.50, 40, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(3, 1, NULL, 'EMP-003', 'Diana', 'Johnson', 'diana@acmeconstruction.com', '(555) 678-9012', NULL, NULL, NULL, NULL, '2019-09-10', NULL, 'Plumber', 'Plumbing', 'hourly', 40.00, NULL, NULL, NULL, 1.50, 40, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(4, 1, NULL, 'EMP-004', 'Eric', 'Williams', 'eric@acmeconstruction.com', '(555) 789-0123', NULL, NULL, NULL, NULL, '2022-01-20', NULL, 'Carpenter', 'Construction', 'hourly', 35.00, NULL, NULL, NULL, 1.50, 40, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(5, 1, NULL, 'EMP-005', 'Frank', 'Davis', 'frank@acmeconstruction.com', '(555) 890-1234', NULL, NULL, NULL, NULL, '2021-11-15', NULL, 'HVAC Technician', 'HVAC', 'hourly', 42.00, NULL, NULL, NULL, 1.50, 40, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(6, 1, NULL, 'EMP-006', 'Grace', 'Miller', 'grace@acmeconstruction.com', '(555) 901-2345', NULL, NULL, NULL, NULL, '2023-02-01', NULL, 'Project Coordinator', 'Administration', 'salary', NULL, NULL, 4500.00, NULL, 1.50, 40, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(7, 7, NULL, 'EMP-00001', 'Felipe', 'Felipe', '', '', '', '', '', '', '2025-12-08', NULL, 'installer', 'field', 'project', 0.00, 170.00, 0.00, 0.00, 1.00, 40, '', '', '', '', '', '', '', 'active', '2025-12-08 20:11:09', '2025-12-09 17:59:50'),
(8, 7, NULL, 'EMP-00002', 'Jervin', 'Jervin', '', '', '', '', '', '', '2025-12-09', NULL, 'Subcontractor', 'field', 'project', 0.00, 0.00, 0.00, 3.00, 1.00, 40, '', '', '', '', '', '', '', 'active', '2025-12-09 14:11:10', '2025-12-09 18:46:30'),
(9, 7, NULL, 'EMP-00003', 'Matheus', 'Matheus', '', '', '', '', '', '', '2025-12-09', NULL, 'Helper', 'field', 'daily', 20.00, 100.00, 0.00, 0.00, 1.00, 40, '', '', '', '', '', '', '', 'active', '2025-12-09 14:13:12', '2025-12-09 21:25:57'),
(10, 7, NULL, 'EMP-00004', 'Marco', 'Ramirez', '', '', '', '', '', '', '2025-12-09', NULL, 'Painter', 'field', 'daily', 0.00, 100.00, 0.00, 0.00, 1.00, 40, '', '', '', '', '', '', '', 'active', '2025-12-09 14:13:53', '2025-12-09 18:46:13'),
(11, 7, NULL, 'EMP-00005', 'Eduardo', 'Eduardo', '', '', '', '', '', '', '2025-12-09', NULL, 'Helper', 'field', 'daily', 0.00, 100.00, 800.00, 0.00, 1.00, 40, '', '', '', '', '', '', 'Helper', 'active', '2025-12-09 14:14:18', '2025-12-09 18:46:44'),
(12, 7, NULL, 'EMP-00006', 'Santos', 'Filho', '', '', '', '', '', '', '2025-10-01', NULL, 'Helper', 'field', 'daily', 0.00, 170.00, 0.00, 0.00, 1.00, 40, '', '', '', '', '', '', '', 'active', '2025-12-09 22:59:13', '2025-12-10 15:44:56'),
(13, 7, NULL, 'EMP-00007', 'Santos', 'Santos', '', '', '', '', '', '', '2025-10-01', NULL, 'Helper', 'field', 'daily', 0.00, 170.00, 0.00, 0.00, 1.00, 40, '', '', '', '', '', '', '', 'active', '2025-12-09 22:59:13', '2025-12-10 15:45:07'),
(14, 7, NULL, 'EMP-00008', 'Dina', 'Tavares', '', '', '', '', '', '', '2025-10-01', NULL, 'Cleaner', 'field', 'project', 0.00, 0.00, 0.00, 0.00, 1.00, 0, '', '', '', '', '', '', '', 'active', '2025-12-09 22:59:13', '2025-12-09 18:45:54'),
(15, 7, NULL, 'EMP-00009', 'Ricardo', 'Ricardo', '', '', '', '', '', '', '2025-12-09', NULL, 'Electrictian', 'field', 'project', 0.00, 100.00, 0.00, 0.00, 1.00, 40, '', '', '', '', '', '', '', 'active', '2025-12-09 23:48:09', '2025-12-10 15:51:36'),
(16, 7, NULL, 'EMP-00010', 'Jhon', 'Jhon', '', '', '', '', '', '', '2025-12-09', NULL, 'Helper', 'field', 'daily', 0.00, 100.00, 0.00, 0.00, 1.00, 0, '', '', '', '', '', '', '', 'active', '2025-12-09 23:48:41', '2025-12-09 23:48:41'),
(17, 7, NULL, 'EMP-00011', 'Josué', 'Josué', '', '', '', '', '', '', '2025-12-09', NULL, 'Helper', 'field', 'daily', 0.00, 100.00, 0.00, 0.00, 1.00, 0, '', '', '', '', '', '', '', 'active', '2025-12-09 23:49:02', '2025-12-09 23:49:02'),
(18, 7, NULL, 'EMP-00012', 'Wanderlei', 'Melo', '', '', '', '', '', '', '2025-12-09', NULL, 'Tile Installer', 'field', 'daily', 0.00, 300.00, 0.00, 0.00, 1.00, 40, '', '', '', '', '', '', '', 'active', '2025-12-09 23:49:48', '2025-12-09 18:58:57'),
(19, 7, NULL, 'EMP-00013', 'Jean', 'Markel', '', '', '', '', '', '', '2025-12-09', NULL, 'Carpenter', 'field', 'daily', 0.00, 150.00, 0.00, 0.00, 1.00, 0, '', '', '', '', '', '', '', 'active', '2025-12-09 23:50:20', '2025-12-09 23:50:20'),
(20, 7, NULL, 'EMP-00014', 'Sofia', 'Couto', '', '(470) 783-1387', '', '', '', '', '2025-12-09', NULL, 'Sales', 'field', 'commission', 0.00, 0.00, 0.00, 3.00, 1.00, 40, '', '', '', '', '', '', '', 'active', '2025-12-09 23:50:57', '2025-12-09 18:52:48'),
(21, 7, NULL, 'EMP-00015', 'Jose', 'Borges Junior', '', '(404) 933-1420', '', '', '', '', '2025-12-09', NULL, 'Supervisor', 'management', 'daily', 0.00, 300.00, 4000.00, 0.00, 1.00, 40, '', '', '', '', '', '', '', 'active', '2025-12-09 23:51:30', '2025-12-09 18:58:10'),
(22, 7, NULL, 'EMP-00016', 'Leonel', 'Leonel', '', '', '', '', '', '', '2025-12-09', NULL, 'Helper', 'field', 'daily', 0.00, 170.00, 0.00, 0.00, 1.00, 40, '', '', '', '', '', '', '', 'active', '2025-12-09 23:52:24', '2025-12-09 18:57:26'),
(23, 7, NULL, 'EMP-00017', 'Esvin', 'Esvin', '', '', '', '', '', '', '2025-12-09', NULL, 'Helper', 'field', 'daily', 0.00, 180.00, 0.00, 0.00, 1.00, 40, '', '', '', '', '', '', '', 'active', '2025-12-09 23:53:27', '2025-12-09 18:59:14'),
(24, 7, NULL, 'EMP-00018', 'Aparecido', 'Aparecido', '', '', '', '', '', '', '2025-12-09', NULL, 'Helper', 'field', 'daily', 0.00, 170.00, 0.00, 0.00, 1.00, 40, '', '', '', '', '', '', '', 'active', '2025-12-09 23:53:52', '2025-12-09 19:21:22'),
(25, 7, NULL, 'EMP-00019', 'João', 'Alfredo', '', '', '', '', '', '', '2025-12-09', NULL, 'Installer', 'field', 'daily', 0.00, 250.00, 0.00, 0.00, 1.00, 0, '', '', '', '', '', '', '', 'active', '2025-12-09 23:54:18', '2025-12-09 23:54:18'),
(26, 7, NULL, 'EMP-00020', 'Antonio', 'Edmir Radovanski', '', '', '', '', '', '', '2025-12-09', NULL, 'Supervisor', 'management', 'daily', 0.00, 300.00, 4000.00, 0.00, 1.00, 40, '', '', '', '', '', '', '', 'active', '2025-12-09 23:54:46', '2025-12-10 09:30:40'),
(27, 7, NULL, 'EMP-00021', 'Sergio', 'Sergio', '', '', '', '', '', '', '2025-12-09', NULL, 'Installer', 'field', 'daily', 0.00, 150.00, 0.00, 0.00, 1.00, 0, '', '', '', '', '', '', '', 'active', '2025-12-09 23:55:17', '2025-12-09 18:57:45'),
(28, 7, NULL, 'EMP-00022', 'Daiane Radovanski', 'Oliveira', '', '', '', '', '', '', '2025-12-09', NULL, 'Assistent', 'office', 'salary', 0.00, 0.00, 800.00, 0.00, 1.00, 0, '', '', '', '', '', '', '', 'active', '2025-12-09 23:56:06', '2025-12-09 23:56:06'),
(29, 10, NULL, 'EMP-00001', 'teste', 'employee 01', 'fabriciooliveiraofficial@gmail.com', '4049257024', '665 Antonio Franco Ferreira da Costa', 'Marietta', 'GA', '30144', '2025-12-11', NULL, '', '', 'hourly', 20.00, 0.00, 0.00, 0.00, 1.50, 40, '', '', '', '', '', '', '', 'active', '2025-12-11 20:14:00', '2025-12-12 10:13:48'),
(30, 7, NULL, 'EMP-00023', 'Pablo', 'Pablo', '', '', '', '', '', '', '2025-12-10', NULL, 'Helper', 'field', 'daily', 0.00, 200.00, 0.00, 0.00, 1.50, 40, '', '', '', '', '', '', '', 'active', '2025-12-15 21:50:16', '2025-12-15 17:59:14'),
(31, 7, NULL, 'EMP-00024', 'Ebenezer', 'Ebenezer', '', '', '', '', '', '', '2026-01-20', NULL, '', '', 'project', 0.00, 0.00, 0.00, 0.00, 1.50, 40, '', '', '', '', '', '', '', 'active', '2026-01-20 13:45:41', '2026-01-20 13:45:41'),
(32, 7, NULL, 'EMP-00025', 'Gilvan ', 'Guimaraes', '', '', '', '', '', '', '2026-02-10', NULL, 'Cabenetry Installer', 'field', 'project', 0.00, 0.00, 0.00, 0.00, 1.50, 40, '', '', '', '', '', '', '', 'active', '2026-02-10 11:37:49', '2026-02-10 11:37:49');

-- --------------------------------------------------------

--
-- Estrutura para tabela `equipment`
--

DROP TABLE IF EXISTS `equipment`;
CREATE TABLE `equipment` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `make` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_cost` decimal(12,2) DEFAULT 0.00,
  `current_value` decimal(12,2) DEFAULT 0.00,
  `status` enum('available','in_use','maintenance','retired') DEFAULT 'available',
  `assigned_project_id` int(10) UNSIGNED DEFAULT NULL,
  `assigned_employee_id` int(10) UNSIGNED DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `last_maintenance_date` date DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `equipment_maintenance`
--

DROP TABLE IF EXISTS `equipment_maintenance`;
CREATE TABLE `equipment_maintenance` (
  `id` int(10) UNSIGNED NOT NULL,
  `equipment_id` int(10) UNSIGNED NOT NULL,
  `maintenance_date` date NOT NULL,
  `type` enum('preventive','repair','inspection') DEFAULT 'preventive',
  `description` text DEFAULT NULL,
  `cost` decimal(12,2) DEFAULT 0.00,
  `performed_by` varchar(255) DEFAULT NULL,
  `next_due_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `estimates`
--

DROP TABLE IF EXISTS `estimates`;
CREATE TABLE `estimates` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `client_id` int(10) UNSIGNED NOT NULL,
  `project_id` int(10) UNSIGNED DEFAULT NULL,
  `estimate_number` varchar(50) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `issue_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `tax_amount` decimal(12,2) DEFAULT 0.00,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `status` enum('draft','sent','approved','rejected','expired','converted') DEFAULT 'draft',
  `converted_invoice_id` int(10) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `estimate_items`
--

DROP TABLE IF EXISTS `estimate_items`;
CREATE TABLE `estimate_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `estimate_id` int(10) UNSIGNED NOT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` decimal(10,2) DEFAULT 1.00,
  `unit` varchar(50) DEFAULT 'unit',
  `unit_price` decimal(12,2) DEFAULT 0.00,
  `total` decimal(12,2) DEFAULT 0.00,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `expenses`
--

DROP TABLE IF EXISTS `expenses`;
CREATE TABLE `expenses` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `project_id` int(10) UNSIGNED NOT NULL,
  `budget_id` int(10) UNSIGNED DEFAULT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Who recorded the expense',
  `employee_id` int(10) UNSIGNED DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `expense_date` date NOT NULL,
  `vendor` varchar(255) DEFAULT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `receipt_path` varchar(255) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `is_billable` tinyint(1) DEFAULT 0,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `journal_entry_id` int(11) DEFAULT NULL,
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `expenses`
--

INSERT INTO `expenses` (`id`, `tenant_id`, `project_id`, `budget_id`, `user_id`, `employee_id`, `category`, `description`, `amount`, `expense_date`, `vendor`, `receipt_number`, `receipt_path`, `payment_method`, `is_billable`, `status`, `journal_entry_id`, `approved_by`, `approved_at`, `notes`, `created_at`, `updated_at`) VALUES
(10, 7, 8, NULL, 10, 22, 'labor', 'Leonel', 170.00, '2025-11-17', '', NULL, NULL, 'check', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 00:00:41', '2025-12-10 09:28:17'),
(11, 7, 8, NULL, 10, 27, 'labor', 'Sergio', 150.00, '2025-11-17', '', NULL, NULL, 'check', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 00:04:57', '2025-12-11 15:22:27'),
(12, 7, 8, NULL, 10, 21, 'labor', 'Jose Borges Junior', 300.00, '2025-11-17', '', NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 00:10:50', '2025-12-11 15:22:14'),
(13, 7, 8, NULL, 10, 27, 'labor', 'Sergio', 150.00, '2025-11-20', '', NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 00:12:25', '2025-12-10 09:31:18'),
(14, 7, 8, NULL, 10, NULL, 'materials', 'Home Depot', 403.35, '2025-11-24', '', NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 00:13:57', '2025-12-10 00:13:57'),
(15, 7, 8, NULL, 10, NULL, 'materials', 'Home Depot', 17.67, '2025-11-24', '', NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 00:14:36', '2025-12-10 00:14:36'),
(16, 7, 8, NULL, 10, NULL, 'materials', 'Home Depot', 137.60, '2025-11-24', '', NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 00:17:12', '2025-12-10 00:17:12'),
(17, 7, 8, NULL, 10, 18, 'labor', 'Wanderlei', 300.00, '2025-11-24', '', NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 00:18:11', '2025-12-10 09:27:57'),
(18, 7, 8, NULL, 10, 22, 'labor', 'Leonel', 170.00, '2025-11-24', '', NULL, NULL, 'check', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 00:18:46', '2025-12-11 15:22:37'),
(19, 7, 8, NULL, 10, NULL, 'materials', 'Home Depot', 197.83, '2025-11-25', 'Chase', NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 00:19:42', '2025-12-10 00:19:42'),
(20, 7, 8, NULL, 10, NULL, 'materials', 'Home Depot', 163.89, '2025-11-26', 'Chase', NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 00:20:09', '2025-12-10 00:20:09'),
(21, 7, 8, NULL, 10, 24, 'labor', 'Aparecido', 170.00, '2025-12-01', '', NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 00:30:04', '2025-12-09 20:01:16'),
(22, 7, 8, NULL, 10, 22, 'labor', 'Leonel', 170.00, '2025-12-01', '', NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 00:30:59', '2025-12-09 20:00:50'),
(23, 7, 8, NULL, 10, NULL, 'materials', 'Home Depot', 60.79, '2025-12-01', 'Chase', NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 00:36:18', '2025-12-10 00:36:18'),
(24, 7, 8, NULL, 10, 23, 'labor', 'Esvin', 180.00, '2025-12-03', '', NULL, NULL, 'check', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 00:41:30', '2025-12-09 20:01:37'),
(25, 7, 8, NULL, 10, 24, 'labor', 'Aparecido', 170.00, '2025-12-03', '', NULL, NULL, 'check', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 01:04:15', '2026-01-20 07:16:45'),
(26, 7, 8, NULL, 10, 24, 'labor', 'Aparecido', 170.00, '2025-11-20', '', NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 01:07:56', '2026-01-22 09:44:38'),
(27, 7, 9, NULL, 10, 24, 'labor', 'Aparecido', 170.00, '2025-12-04', '', NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 14:57:02', '2025-12-10 14:57:02'),
(37, 7, 9, NULL, 14, 24, 'labor', 'Aparecido ', 170.00, '2025-12-08', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 18:30:47', '2025-12-10 18:30:47'),
(38, 7, 9, NULL, 14, 27, 'labor', 'Sergio', 150.00, '2025-12-08', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 18:31:33', '2025-12-10 18:31:33'),
(39, 7, 9, NULL, 14, 24, 'labor', 'Aparecido ', 170.00, '2025-12-05', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 18:33:56', '2025-12-10 18:33:56'),
(40, 7, 9, NULL, 14, 27, 'labor', 'Sergio', 150.00, '2025-12-05', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 18:34:42', '2025-12-10 18:34:42'),
(42, 7, 9, NULL, 14, 27, 'labor', 'Sergio', 150.00, '2025-12-04', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 18:36:04', '2025-12-10 18:36:04'),
(44, 7, 9, NULL, 14, NULL, 'materials', 'Home Depot', 394.34, '2025-12-08', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 18:38:41', '2025-12-10 18:38:41'),
(45, 7, 9, NULL, 14, NULL, 'materials', 'Home Depot ', 178.34, '2025-12-08', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 18:40:16', '2025-12-10 18:40:16'),
(46, 7, 9, NULL, 14, NULL, 'materials', 'Home Depot ', 256.42, '2025-12-08', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 18:41:09', '2025-12-10 18:41:09'),
(47, 7, 9, NULL, 14, NULL, 'materials', 'Home Depot ', 333.77, '2025-12-04', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 18:42:24', '2025-12-10 18:42:24'),
(48, 7, 10, NULL, 14, 23, 'labor', 'Ervin', 180.00, '2025-12-08', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 18:45:38', '2025-12-10 18:45:38'),
(49, 7, 10, NULL, 14, 22, 'labor', 'Leonel ', 170.00, '2025-12-08', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 18:46:22', '2025-12-10 18:46:22'),
(50, 7, 10, NULL, 14, NULL, 'materials', 'Home Depot ', 29.25, '2025-12-09', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 18:47:15', '2025-12-10 18:47:15'),
(51, 7, 10, NULL, 14, 18, 'labor', 'Wanderley Melo', 300.00, '2025-12-06', '', NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 18:48:29', '2025-12-10 13:51:47'),
(52, 7, 10, NULL, 14, 25, 'labor', 'Joao Alfredo ', 125.00, '2025-12-05', '', NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 18:50:28', '2025-12-10 13:50:45'),
(53, 7, 10, NULL, 14, 18, 'labor', 'Wanderley Melo ', 300.00, '2025-12-05', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 18:51:35', '2025-12-10 18:51:35'),
(54, 7, 10, NULL, 14, 17, 'labor', 'Josué ', 50.00, '2025-12-05', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 18:52:25', '2025-12-10 18:52:25'),
(55, 7, 10, NULL, 14, NULL, 'materials', 'Home Depot ', 63.49, '2025-12-08', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 18:53:33', '2025-12-10 18:53:33'),
(56, 7, 10, NULL, 14, NULL, 'materials', 'Home Depot ', 136.08, '2025-12-05', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 18:54:33', '2025-12-10 18:54:33'),
(57, 7, 10, NULL, 14, 18, 'labor', 'Wanderley Melo ', 300.00, '2025-12-03', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 18:55:34', '2025-12-10 18:55:34'),
(58, 7, 10, NULL, 14, NULL, 'materials', 'Home Depot ', 5.25, '2025-12-03', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 18:57:38', '2025-12-10 18:57:38'),
(59, 7, 10, NULL, 14, NULL, 'materials', 'Home Depot ', 14.63, '2025-12-03', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 18:58:26', '2025-12-10 18:58:26'),
(60, 7, 10, NULL, 14, NULL, 'materials', 'Home Depot ', 189.17, '2025-12-03', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 18:59:25', '2025-12-10 18:59:25'),
(61, 7, 10, NULL, 14, 18, 'labor', 'Wanderley Melo ', 300.00, '2025-12-02', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 19:00:25', '2025-12-10 19:00:25'),
(62, 7, 10, NULL, 14, 27, 'labor', 'Sergio ', 150.00, '2025-12-02', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 19:01:14', '2025-12-10 19:01:14'),
(63, 7, 10, NULL, 14, NULL, 'materials', 'Home Depot ', 17.62, '2025-12-01', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 19:02:14', '2025-12-10 19:02:14'),
(64, 7, 10, NULL, 14, NULL, 'materials', 'Home Depot ', 267.26, '2025-12-02', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 19:03:22', '2025-12-10 19:03:22'),
(65, 7, 10, NULL, 14, 18, 'labor', 'Wanderley Melo ', 300.00, '2025-12-01', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 19:04:11', '2025-12-10 19:04:11'),
(66, 7, 10, NULL, 14, 27, 'labor', 'Sergio', 150.00, '2025-12-01', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 19:04:45', '2025-12-10 19:04:45'),
(67, 7, 10, NULL, 14, NULL, 'materials', 'Lowe\'s ', 332.54, '2025-12-01', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 19:06:25', '2025-12-10 19:06:25'),
(68, 7, 11, NULL, 14, 25, 'labor', 'Joao Alfredo ', 125.00, '2025-12-08', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 19:11:41', '2025-12-10 19:11:41'),
(69, 7, 11, NULL, 14, 21, 'labor', 'Jose Borges Junior ', 150.00, '2025-12-08', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 19:13:13', '2025-12-10 19:13:13'),
(70, 7, 11, NULL, 14, 18, 'labor', 'Wanderley Melo ', 150.00, '2025-12-08', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 19:13:57', '2025-12-10 19:13:57'),
(71, 7, 11, NULL, 14, 22, 'labor', 'Leonel ', 170.00, '2025-12-04', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 19:14:54', '2025-12-10 19:14:54'),
(72, 7, 11, NULL, 14, 8, 'labor', 'Jervin-subcontractor ', 1300.00, '2025-12-03', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 19:17:52', '2025-12-10 19:17:52'),
(73, 7, 11, NULL, 14, 22, 'labor', 'Leonel ', 170.00, '2025-12-03', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 19:19:06', '2025-12-10 19:19:06'),
(74, 7, 11, NULL, 14, 25, 'labor', 'Joao Alfredo ', 250.00, '2025-11-25', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 19:22:57', '2025-12-10 19:22:57'),
(75, 7, 11, NULL, 14, 24, 'labor', 'Aparecido ', 170.00, '2025-11-25', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 19:23:43', '2025-12-10 19:23:43'),
(76, 7, 11, NULL, 14, 22, 'labor', 'Leonel ', 170.00, '2025-11-20', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 19:25:59', '2025-12-10 19:25:59'),
(77, 7, 11, NULL, 14, 23, 'labor', 'Esvin ', 180.00, '2025-11-20', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 19:26:39', '2025-12-10 19:26:39'),
(78, 7, 11, NULL, 14, 21, 'labor', 'Jose Borges Junior ', 300.00, '2025-11-20', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 19:27:27', '2025-12-10 19:27:27'),
(79, 7, 11, NULL, 14, 27, 'labor', 'Sergio ', 150.00, '2025-11-18', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 19:28:40', '2025-12-10 19:28:40'),
(80, 7, 11, NULL, 14, 22, 'labor', 'Leonel ', 170.00, '2025-11-18', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 19:29:13', '2025-12-10 19:29:13'),
(81, 7, 30, NULL, 10, NULL, 'materials', 'Builders', 421.88, '2025-12-10', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 19:52:03', '2025-12-10 19:52:03'),
(82, 7, 12, NULL, 14, 18, 'labor', 'Wanderley Melo ', 150.00, '2025-12-08', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 19:54:52', '2025-12-10 19:54:52'),
(83, 7, 12, NULL, 14, 18, 'labor', 'Wanderley Melo ', 300.00, '2025-12-04', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 19:55:41', '2025-12-10 19:55:41'),
(84, 7, 31, NULL, 10, NULL, 'utilities', 'Builders', 68.99, '2025-12-10', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:00:21', '2025-12-10 20:00:21'),
(85, 7, 14, NULL, 10, NULL, 'materials', 'Home Depot', 53.23, '2025-12-09', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:02:18', '2025-12-10 20:02:18'),
(86, 7, 13, NULL, 14, 25, 'labor', 'Joao Alfredo ', 125.00, '2025-12-08', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:04:08', '2025-12-10 20:04:08'),
(87, 7, 13, NULL, 14, 21, 'labor', 'Jose Borges Junior ', 150.00, '2025-12-08', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:04:46', '2025-12-10 20:04:46'),
(88, 7, 13, NULL, 14, 25, 'labor', 'Joao Alfredo ', 125.00, '2025-11-20', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:06:48', '2025-12-10 20:06:48'),
(89, 7, 17, NULL, 14, 23, 'labor', 'Esvin', 180.00, '2025-12-04', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:12:25', '2025-12-10 20:12:25'),
(90, 7, 14, NULL, 10, 25, 'labor', 'João Alfredo', 250.00, '2025-12-06', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:12:41', '2025-12-10 20:12:41'),
(91, 7, 17, NULL, 14, 17, 'labor', 'Josué ', 100.00, '2025-12-04', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:12:47', '2025-12-10 20:12:47'),
(92, 7, 17, NULL, 14, 23, 'labor', 'Esvin', 180.00, '2025-12-02', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:13:56', '2025-12-10 20:13:56'),
(93, 7, 17, NULL, 14, 17, 'labor', 'Josué ', 100.00, '2025-12-02', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:14:32', '2025-12-10 20:14:32'),
(94, 7, 17, NULL, 14, 23, 'labor', 'Esvin', 180.00, '2025-12-01', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:15:27', '2025-12-10 20:15:27'),
(95, 7, 17, NULL, 14, NULL, 'materials', 'Sherwin-Williams ', 35.43, '2025-11-28', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:17:01', '2025-12-10 20:17:01'),
(96, 7, 17, NULL, 14, 17, 'labor', 'Josué ', 100.00, '2025-11-25', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:18:00', '2025-12-10 20:18:00'),
(97, 7, 17, NULL, 14, 23, 'labor', 'Esvin', 180.00, '2025-11-25', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:18:35', '2025-12-10 20:18:35'),
(98, 7, 17, NULL, 14, 27, 'labor', 'Sergio ', 150.00, '2025-11-25', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:19:02', '2025-12-10 20:19:02'),
(99, 7, 17, NULL, 14, NULL, 'materials', 'Home Depot ', 71.74, '2025-11-25', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:20:22', '2025-12-10 20:20:22'),
(100, 7, 17, NULL, 14, 23, 'labor', 'Esvin', 180.00, '2025-11-24', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:20:56', '2025-12-10 20:20:56'),
(101, 7, 17, NULL, 14, 27, 'labor', 'Sergio ', 150.00, '2025-11-24', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:21:18', '2025-12-10 20:21:18'),
(102, 7, 17, NULL, 14, 24, 'labor', 'Aparecido ', 170.00, '2025-11-24', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:21:44', '2025-12-10 20:21:44'),
(103, 7, 17, NULL, 14, 17, 'labor', 'Josué ', 100.00, '2025-11-24', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:22:18', '2025-12-10 20:22:18'),
(104, 7, 17, NULL, 14, NULL, 'materials', 'Home Depot ', 173.17, '2025-11-24', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:23:21', '2025-12-10 20:23:21'),
(105, 7, 17, NULL, 14, NULL, 'materials', 'Home Depot ', 457.53, '2025-11-24', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:24:59', '2025-12-10 20:24:59'),
(106, 7, 17, NULL, 14, 24, 'labor', 'Aparecido ', 170.00, '2025-11-18', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:28:55', '2025-12-10 20:28:55'),
(107, 7, 17, NULL, 14, 22, 'labor', 'Leonel ', 170.00, '2025-11-18', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:29:29', '2025-12-10 20:29:29'),
(108, 7, 22, NULL, 10, 23, 'labor', 'Esvin', 180.00, '2025-12-06', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:30:35', '2025-12-10 20:30:35'),
(109, 7, 22, NULL, 10, NULL, 'materials', 'Home Depot', 33.48, '2025-12-10', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:31:29', '2025-12-10 20:31:29'),
(110, 7, 22, NULL, 10, 22, 'labor', 'Leonel', 170.00, '2025-12-06', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:32:33', '2025-12-10 20:32:33'),
(111, 7, 17, NULL, 14, 22, 'labor', 'Leonel ', 170.00, '2025-10-16', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:32:43', '2025-12-10 20:32:43'),
(112, 7, 17, NULL, 14, 27, 'labor', 'Sergio ', 150.00, '2025-10-16', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:33:13', '2025-12-10 20:33:13'),
(113, 7, 22, NULL, 10, 23, 'labor', 'Esvin', 180.00, '2025-12-05', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:33:17', '2025-12-10 20:33:17'),
(114, 7, 22, NULL, 10, 22, 'labor', 'Leonel', 170.00, '2025-12-05', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:33:53', '2025-12-10 20:33:53'),
(115, 7, 17, NULL, 14, NULL, 'other', 'GFL', 75.80, '2025-10-20', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:36:11', '2025-12-10 20:36:11'),
(116, 7, 17, NULL, 14, NULL, 'materials', 'Home Depot ', 31.77, '2025-10-21', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:37:05', '2025-12-10 20:37:05'),
(117, 7, 17, NULL, 14, 27, 'labor', 'Sergio ', 150.00, '2025-10-20', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:37:39', '2025-12-10 20:37:39'),
(118, 7, 22, NULL, 10, 25, 'labor', 'Joao Alfredo', 250.00, '2025-12-04', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:37:47', '2025-12-10 20:37:47'),
(119, 7, 17, NULL, 14, 24, 'labor', 'Aparecido ', 170.00, '2025-10-20', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:38:04', '2025-12-10 20:38:04'),
(120, 7, 22, NULL, 10, 21, 'labor', 'Jose Borges Junior', 300.00, '2025-12-04', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:38:14', '2025-12-10 20:38:14'),
(121, 7, 31, NULL, 10, NULL, 'fuel', 'Costco', 75.60, '2025-12-07', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:39:35', '2025-12-10 20:39:35'),
(122, 7, 32, NULL, 14, 18, 'labor', 'Wanderley Melo ', 300.00, '2025-09-17', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:42:59', '2025-12-10 20:42:59'),
(123, 7, 32, NULL, 14, 13, 'labor', 'Santos ', 170.00, '2025-09-17', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:46:01', '2025-12-10 20:46:01'),
(124, 7, 32, NULL, 14, 12, 'labor', 'Santos Filho ', 170.00, '2025-09-17', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:46:28', '2025-12-10 20:46:28'),
(125, 7, 16, NULL, 10, NULL, 'materials', 'Home Depot', 116.41, '2025-11-18', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:47:04', '2025-12-10 20:47:04'),
(126, 7, 32, NULL, 14, 13, 'labor', 'Santos ', 170.00, '2025-09-18', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:47:17', '2025-12-10 20:47:17'),
(127, 7, 32, NULL, 14, 12, 'labor', 'Santos Filho ', 170.00, '2025-09-18', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:47:47', '2025-12-10 20:47:47'),
(128, 7, 32, NULL, 14, 13, 'labor', 'Santos ', 170.00, '2025-09-19', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:48:28', '2025-12-10 20:48:28'),
(129, 7, 16, NULL, 10, NULL, 'materials', 'Home Depot', 12.79, '2025-11-19', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:48:31', '2025-12-10 20:48:31'),
(130, 7, 32, NULL, 14, 12, 'labor', 'Santos Filho ', 170.00, '2025-09-19', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:48:55', '2025-12-10 20:48:55'),
(131, 7, 16, NULL, 10, 27, 'labor', 'Sergio', 150.00, '2025-11-18', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:49:23', '2025-12-10 20:49:23'),
(132, 7, 32, NULL, 14, 25, 'labor', 'Joao Alfredo ', 250.00, '2025-09-22', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:49:42', '2025-12-10 20:49:42'),
(133, 7, 16, NULL, 10, 23, 'labor', 'Esvin', 180.00, '2025-11-18', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:49:49', '2025-12-10 20:49:49'),
(134, 7, 32, NULL, 14, 24, 'labor', 'Aparecido ', 170.00, '2025-09-22', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:50:18', '2025-12-10 20:50:18'),
(135, 7, 16, NULL, 10, 21, 'labor', 'Jose Borges Junior', 300.00, '2025-11-18', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:50:22', '2025-12-10 20:50:22'),
(136, 7, 32, NULL, 14, 18, 'labor', 'Wanderley Melo ', 300.00, '2025-09-23', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:50:50', '2025-12-10 20:50:50'),
(137, 7, 32, NULL, 14, 24, 'labor', 'Aparecido ', 170.00, '2025-09-23', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:51:16', '2025-12-10 20:51:16'),
(138, 7, 32, NULL, 14, 25, 'labor', 'Joao Alfredo ', 250.00, '2025-09-23', '', NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:51:37', '2025-12-10 15:51:47'),
(139, 7, 21, NULL, 10, 15, 'labor', 'Ricardo', 700.00, '2025-11-19', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:52:27', '2025-12-10 20:52:27'),
(140, 7, 32, NULL, 14, 18, 'labor', 'Wanderley Melo ', 300.00, '2025-09-24', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:52:35', '2025-12-10 20:52:35'),
(141, 7, 32, NULL, 14, 24, 'labor', 'Aparecido ', 170.00, '2025-09-24', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:53:02', '2025-12-10 20:53:02'),
(142, 7, 16, NULL, 10, NULL, 'materials', 'Home Depot', 76.42, '2025-11-28', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:53:16', '2025-12-10 20:53:16'),
(143, 7, 32, NULL, 14, 25, 'labor', 'Joao Alfredo ', 250.00, '2025-09-24', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:53:34', '2025-12-10 20:53:34'),
(144, 7, 16, NULL, 10, NULL, 'materials', 'Home Depot', 162.35, '2025-12-01', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:54:02', '2025-12-10 20:54:02'),
(145, 7, 32, NULL, 14, 18, 'labor', 'Wanderley Melo ', 300.00, '2025-09-25', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:54:15', '2025-12-10 20:54:15'),
(146, 7, 16, NULL, 10, NULL, 'materials', 'Home Depot', 89.57, '2025-12-01', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:54:21', '2025-12-10 20:54:21'),
(147, 7, 32, NULL, 14, 24, 'labor', 'Aparecido ', 170.00, '2025-09-25', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:55:29', '2025-12-10 20:55:29'),
(148, 7, 16, NULL, 10, 25, 'labor', 'João Alfredo', 250.00, '2025-12-01', '', NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:55:29', '2025-12-10 15:57:20'),
(149, 7, 32, NULL, 14, 22, 'labor', 'Leonel ', 170.00, '2025-09-25', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:55:56', '2025-12-10 20:55:56'),
(150, 7, 16, NULL, 10, 17, 'labor', 'Josue', 100.00, '2025-12-01', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:55:56', '2025-12-10 20:55:56'),
(151, 7, 32, NULL, 14, 27, 'labor', 'Sergio ', 150.00, '2025-09-25', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:56:24', '2025-12-10 20:56:24'),
(152, 7, 16, NULL, 10, 21, 'labor', 'Jose Borges Junios', 300.00, '2025-12-01', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:56:25', '2025-12-10 20:56:25'),
(153, 7, 32, NULL, 14, 18, 'labor', 'Wanderley Melo ', 300.00, '2025-09-26', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:56:58', '2025-12-10 20:56:58'),
(154, 7, 16, NULL, 10, NULL, 'materials', 'Home Depot', 47.43, '2025-12-02', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:57:05', '2025-12-10 20:57:05'),
(155, 7, 32, NULL, 14, 24, 'labor', 'Aparecido ', 170.00, '2025-09-26', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:57:19', '2025-12-10 20:57:19'),
(156, 7, 16, NULL, 10, 25, 'labor', 'João Alfredo', 250.00, '2025-12-02', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:58:05', '2025-12-10 20:58:05'),
(157, 7, 32, NULL, 14, 18, 'labor', 'Wanderley Melo ', 300.00, '2025-09-29', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:58:08', '2025-12-10 20:58:08'),
(158, 7, 16, NULL, 10, 21, 'labor', 'Jose Borges Junior', 300.00, '2025-12-02', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:58:33', '2025-12-10 20:58:33'),
(159, 7, 32, NULL, 14, 22, 'labor', 'Leonel ', 170.00, '2025-09-29', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:58:41', '2025-12-10 20:58:41'),
(160, 7, 32, NULL, 14, 24, 'labor', 'Aparecido ', 170.00, '2025-09-29', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:59:06', '2025-12-10 20:59:06'),
(161, 7, 16, NULL, 10, NULL, 'materials', 'Home Depot', 171.92, '2025-12-04', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:59:08', '2025-12-10 20:59:08'),
(162, 7, 32, NULL, 14, 18, 'labor', 'Wanderley Melo ', 300.00, '2025-09-30', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 20:59:42', '2025-12-10 20:59:42'),
(163, 7, 32, NULL, 14, 24, 'labor', 'Aparecido ', 170.00, '2025-09-30', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:00:08', '2025-12-10 21:00:08'),
(164, 7, 32, NULL, 14, 22, 'labor', 'Leonel ', 170.00, '2025-09-30', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:00:43', '2025-12-10 21:00:43'),
(165, 7, 16, NULL, 10, NULL, 'materials', 'Home Depot', 69.78, '2025-12-02', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:00:45', '2025-12-10 21:00:45'),
(166, 7, 32, NULL, 14, 23, 'labor', 'Esvin', 180.00, '2025-09-30', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:01:09', '2025-12-10 21:01:09'),
(167, 7, 16, NULL, 10, NULL, 'materials', 'Home Depot', 86.42, '2025-12-01', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:01:16', '2025-12-10 21:01:16'),
(168, 7, 32, NULL, 14, 24, 'labor', 'Aparecido ', 170.00, '2025-10-01', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:02:19', '2025-12-10 21:02:19'),
(169, 7, 32, NULL, 14, 22, 'labor', 'Leonel ', 170.00, '2025-10-02', '', NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:03:26', '2025-12-10 16:04:58'),
(170, 7, 32, NULL, 14, 23, 'labor', 'Esvin', 180.00, '2025-10-01', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:03:50', '2025-12-10 21:03:50'),
(171, 7, 32, NULL, 14, 22, 'labor', 'Leonel ', 170.00, '2025-10-01', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:04:21', '2025-12-10 21:04:21'),
(172, 7, 32, NULL, 14, 23, 'labor', 'Esvin', 180.00, '2025-10-02', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:05:24', '2025-12-10 21:05:24'),
(173, 7, 32, NULL, 14, 27, 'labor', 'Sergio ', 150.00, '2025-10-02', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:05:55', '2025-12-10 21:05:55'),
(174, 7, 32, NULL, 14, 18, 'labor', 'Wanderley Melo ', 300.00, '2025-10-03', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:06:29', '2025-12-10 21:06:29'),
(175, 7, 32, NULL, 14, 18, 'labor', 'Wanderley Melo ', 300.00, '2025-10-06', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:07:14', '2025-12-10 21:07:14'),
(176, 7, 32, NULL, 14, 18, 'labor', 'Wanderley Melo ', 300.00, '2025-10-07', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:08:01', '2025-12-10 21:08:01'),
(177, 7, 32, NULL, 14, 22, 'labor', 'Leonel ', 170.00, '2025-10-07', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:08:29', '2025-12-10 21:08:29'),
(178, 7, 32, NULL, 14, 18, 'labor', 'Wanderley Melo ', 300.00, '2025-10-08', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:09:09', '2025-12-10 21:09:09'),
(179, 7, 32, NULL, 14, 22, 'labor', 'Leonel ', 170.00, '2025-10-08', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:09:39', '2025-12-10 21:09:39'),
(180, 7, 32, NULL, 14, 18, 'labor', 'Wanderley Melo ', 300.00, '2025-10-09', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:11:59', '2025-12-10 21:11:59'),
(181, 7, 32, NULL, 14, 22, 'labor', 'Leonel ', 170.00, '2025-10-09', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:12:25', '2025-12-10 21:12:25'),
(182, 7, 32, NULL, 14, 22, 'labor', 'Leonel ', 170.00, '2025-10-10', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:12:58', '2025-12-10 21:12:58'),
(183, 7, 32, NULL, 14, 18, 'labor', 'Wanderley Melo ', 300.00, '2025-10-13', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:13:32', '2025-12-10 21:13:32'),
(184, 7, 32, NULL, 14, 22, 'labor', 'Leonel ', 170.00, '2025-10-13', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:14:04', '2025-12-10 21:14:04'),
(185, 7, 32, NULL, 14, 27, 'labor', 'Sergio ', 150.00, '2025-10-13', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:14:28', '2025-12-10 21:14:28'),
(186, 7, 32, NULL, 14, 22, 'labor', 'Leonel ', 170.00, '2025-10-15', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:14:57', '2025-12-10 21:14:57'),
(187, 7, 32, NULL, 14, 27, 'labor', 'Sergio ', 150.00, '2025-10-15', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:15:16', '2025-12-10 21:15:16'),
(189, 7, 32, NULL, 14, 22, 'labor', 'Leonel ', 170.00, '2025-10-18', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:16:37', '2025-12-10 21:16:37'),
(190, 7, 32, NULL, 14, 27, 'labor', 'Sergio ', 150.00, '2025-10-18', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:17:12', '2025-12-10 21:17:12'),
(191, 7, 32, NULL, 14, 24, 'labor', 'Aparecido ', 170.00, '2025-10-20', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:17:47', '2025-12-10 21:17:47'),
(192, 7, 32, NULL, 14, 22, 'labor', 'Leonel ', 170.00, '2025-10-20', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:18:18', '2025-12-10 21:18:18'),
(193, 7, 32, NULL, 14, 24, 'labor', 'Aparecido ', 170.00, '2025-10-23', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:18:51', '2025-12-10 21:18:51'),
(194, 7, 32, NULL, 14, 27, 'labor', 'Sergio ', 150.00, '2025-10-23', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:19:15', '2025-12-10 21:19:15'),
(195, 7, 32, NULL, 14, 25, 'labor', 'Joao Alfredo ', 250.00, '2025-10-27', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:19:50', '2025-12-10 21:19:50'),
(196, 7, 32, NULL, 14, 21, 'labor', 'Jose Borges Junior ', 300.00, '2025-10-27', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:20:19', '2025-12-10 21:20:19'),
(197, 7, 32, NULL, 14, 24, 'labor', 'Aparecido ', 170.00, '2025-10-28', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:20:47', '2025-12-10 21:20:47'),
(198, 7, 32, NULL, 14, 22, 'labor', 'Leonel ', 170.00, '2025-10-28', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:21:20', '2025-12-10 21:21:20'),
(199, 7, 32, NULL, 14, 25, 'labor', 'Joao Alfredo ', 250.00, '2025-10-29', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:26:20', '2025-12-10 21:26:20'),
(200, 7, 32, NULL, 14, 21, 'labor', 'Jose Borges Junior ', 300.00, '2025-10-29', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:30:15', '2025-12-10 21:30:15'),
(201, 7, 32, NULL, 14, 25, 'labor', 'Joao Alfredo ', 250.00, '2025-10-30', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:32:58', '2025-12-10 21:32:58'),
(202, 7, 32, NULL, 14, 23, 'labor', 'Esvin', 180.00, '2025-10-30', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:33:34', '2025-12-10 21:33:34'),
(203, 7, 32, NULL, 14, 21, 'labor', 'Jose Borges Junior ', 300.00, '2025-10-30', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:35:23', '2025-12-10 21:35:23'),
(204, 7, 32, NULL, 14, 23, 'labor', 'Esvin', 180.00, '2025-10-31', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:35:54', '2025-12-10 21:35:54'),
(205, 7, 32, NULL, 14, 25, 'labor', 'Joao Alfredo ', 250.00, '2025-11-10', '', NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:36:24', '2025-12-10 16:37:12'),
(206, 7, 32, NULL, 14, 25, 'labor', 'Joao Alfredo ', 250.00, '2025-11-05', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:36:55', '2025-12-10 21:36:55'),
(207, 7, 32, NULL, 14, 25, 'labor', 'Joao Alfredo ', 125.00, '2025-11-13', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:38:12', '2025-12-10 21:38:12'),
(208, 7, 32, NULL, 14, 21, 'labor', 'Jose Borges Junior ', 150.00, '2025-11-13', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:38:46', '2025-12-10 21:38:46'),
(209, 7, 32, NULL, 14, 18, 'labor', 'Wanderley Melo ', 300.00, '2025-11-21', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:39:15', '2025-12-10 21:39:15'),
(210, 7, 32, NULL, 14, 21, 'labor', 'Jose Borges Junior ', 300.00, '2025-11-21', NULL, NULL, NULL, 'cash', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-10 21:39:45', '2025-12-10 21:39:45'),
(211, 7, 17, NULL, 10, NULL, 'materials', 'Home Depot', 78.02, '2025-12-11', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 14:43:20', '2025-12-11 14:43:20'),
(212, 7, 26, NULL, 10, 25, 'labor', 'João Alfredo', 250.00, '2025-10-13', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 15:27:50', '2025-12-11 15:27:50'),
(213, 7, 26, NULL, 10, 23, 'labor', 'Esvin', 180.00, '2025-10-13', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 15:28:16', '2025-12-11 15:28:16'),
(215, 7, 26, NULL, 10, NULL, 'materials', 'Floor & Decor', 87.00, '2025-10-13', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 15:29:50', '2025-12-11 15:29:50'),
(216, 7, 26, NULL, 10, NULL, 'materials', 'Home Depot', 128.69, '2025-10-15', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 15:30:29', '2025-12-11 15:30:29'),
(217, 7, 26, NULL, 10, 23, 'labor', 'Esvin', 180.00, '2025-10-13', '', NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 15:31:20', '2025-12-11 10:52:40'),
(218, 10, 33, NULL, 15, NULL, 'materials', 'Teste 01', 100.00, '2025-12-01', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 15:31:52', '2025-12-11 15:31:52'),
(219, 7, 26, NULL, 10, NULL, 'materials', 'Sherwin Willians', 8.64, '2025-10-17', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 15:32:20', '2025-12-11 15:32:20'),
(220, 10, 33, NULL, 15, NULL, 'materials', 'Teste 02', 150.00, '2025-12-02', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 15:32:26', '2025-12-11 15:32:26'),
(221, 7, 26, NULL, 10, NULL, 'materials', 'Sherwin Willians', 127.09, '2025-10-17', '', NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 15:32:39', '2025-12-11 10:39:09'),
(222, 7, 26, NULL, 10, NULL, 'materials', 'Home Depot', 73.15, '2025-10-17', '', NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 15:32:59', '2025-12-11 13:31:14'),
(223, 10, 33, NULL, 15, NULL, 'materials', 'Teste 03', 200.00, '2025-12-03', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 15:33:00', '2025-12-11 15:33:00'),
(224, 7, 26, NULL, 10, 27, 'labor', 'Sergio', 150.00, '2025-10-17', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 15:33:32', '2025-12-11 15:33:32'),
(225, 7, 26, NULL, 10, 23, 'labor', 'Esvin', 180.00, '2025-10-17', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 15:34:54', '2025-12-11 15:34:54'),
(226, 7, 26, NULL, 10, NULL, 'materials', 'Home Depor', 273.41, '2025-10-20', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 15:35:25', '2025-12-11 15:35:25'),
(227, 7, 26, NULL, 10, NULL, 'materials', 'Home Depot', 104.41, '2025-10-21', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 15:36:02', '2025-12-11 15:36:02'),
(228, 7, 26, NULL, 10, 23, 'labor', 'Esvin', 180.00, '2025-10-20', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 15:36:32', '2025-12-11 15:36:32'),
(229, 7, 26, NULL, 10, 25, 'labor', 'João Alfredo', 250.00, '2025-10-20', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 15:36:53', '2025-12-11 15:36:53'),
(230, 7, 26, NULL, 10, NULL, 'materials', 'Home Depot', 75.48, '2025-10-23', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 15:37:26', '2025-12-11 15:37:26'),
(231, 7, 26, NULL, 10, 25, 'labor', 'João Alfredo', 250.00, '2025-10-23', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 15:38:21', '2025-12-11 15:38:21'),
(232, 7, 26, NULL, 10, 23, 'labor', 'Esvin', 180.00, '2025-10-23', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 15:38:49', '2025-12-11 15:38:49'),
(233, 7, 26, NULL, 10, NULL, 'materials', 'Home Depot', 74.00, '2025-10-24', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 15:39:52', '2025-12-11 15:39:52'),
(234, 7, 26, NULL, 10, NULL, 'materials', 'Sherwin Willians', 101.80, '2025-10-23', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 15:40:34', '2025-12-11 15:40:34'),
(235, 7, 26, NULL, 10, NULL, 'materials', 'Home Depot', 44.91, '2025-10-27', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 15:41:12', '2025-12-11 15:41:12'),
(236, 7, 26, NULL, 10, NULL, 'materials', 'Home Depot', 34.82, '2025-10-28', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 15:41:54', '2025-12-11 15:41:54'),
(237, 7, 26, NULL, 10, NULL, 'materials', 'Sherwin Willians', 127.09, '2025-10-17', '', NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 15:42:29', '2025-12-11 13:30:41'),
(238, 7, 26, NULL, 10, NULL, 'materials', 'Home Depot', 73.42, '2025-10-17', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 15:42:56', '2025-12-11 15:42:56'),
(239, 7, 26, NULL, 10, NULL, 'materials', 'Home Depot', 120.23, '2025-11-03', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 15:43:34', '2025-12-11 15:43:34'),
(240, 7, 26, NULL, 10, 22, 'labor', 'Leonel', 170.00, '2025-11-03', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 15:44:06', '2025-12-11 15:44:06'),
(241, 7, 26, NULL, 10, 22, 'labor', 'Leonel', 170.00, '2025-11-05', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 15:44:30', '2025-12-11 15:44:30'),
(242, 7, 26, NULL, 10, 19, 'labor', 'Jean Markel', 150.00, '2025-10-13', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 15:47:17', '2025-12-11 15:47:17'),
(243, 7, 26, NULL, 10, NULL, 'materials', 'Sherwin Willians', 32.35, '2025-11-17', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 15:51:37', '2025-12-11 15:51:37'),
(244, 7, 26, NULL, 10, NULL, 'materials', 'Home Depot', 395.26, '2025-10-13', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 15:53:54', '2025-12-11 15:53:54'),
(245, 7, 26, NULL, 10, NULL, 'materials', 'Home Depot', 72.30, '2025-11-08', '', NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 18:35:20', '2025-12-11 14:15:19'),
(246, 7, 26, NULL, 10, NULL, 'materials', 'Home Depot', 36.51, '2025-11-08', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 18:35:43', '2025-12-11 18:35:43'),
(247, 7, 26, NULL, 10, 22, 'labor', 'Leonel', 170.00, '2025-11-11', '', NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 19:49:22', '2025-12-11 14:52:27'),
(249, 7, 31, NULL, 10, NULL, 'fuel', 'Racetrac', 25.79, '2025-12-11', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 20:05:14', '2025-12-11 20:05:14'),
(250, 7, 31, NULL, 10, NULL, 'fuel', 'Quick Trip', 32.29, '2025-12-11', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 20:05:36', '2025-12-11 20:05:36'),
(251, 7, 34, NULL, 10, 25, 'labor', 'João Alfredo', 75.00, '2025-12-10', '', NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 20:10:11', '2025-12-11 15:13:05'),
(252, 7, 30, NULL, 10, 25, 'labor', 'João Alfredo', 75.00, '2025-12-10', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 20:13:47', '2025-12-11 20:13:47'),
(253, 7, 35, NULL, 10, 25, 'labor', 'João Alfredo', 100.00, '2025-12-10', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 20:16:21', '2025-12-11 20:16:21'),
(254, 7, 11, NULL, 10, 18, 'labor', 'Wanderlei Melo', 300.00, '2025-12-10', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 20:17:11', '2025-12-11 20:17:11'),
(255, 7, 17, NULL, 10, 22, 'labor', 'Leonel', 170.00, '2025-12-10', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 20:19:58', '2025-12-11 20:19:58'),
(256, 7, 17, NULL, 10, 27, 'labor', 'Sergio', 150.00, '2025-12-10', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 20:20:15', '2025-12-11 20:20:15'),
(257, 7, 9, NULL, 10, 24, 'labor', 'Aparecido', 85.00, '2025-12-10', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 20:21:09', '2025-12-11 20:21:09'),
(258, 7, 9, NULL, 10, 23, 'labor', 'Esvin', 90.00, '2025-12-10', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 20:21:24', '2025-12-11 20:21:24'),
(259, 7, 8, NULL, 10, 24, 'labor', 'Aparecido', 85.00, '2025-12-10', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 20:23:03', '2025-12-11 20:23:03'),
(260, 7, 8, NULL, 10, 23, 'labor', 'Esvin', 90.00, '2025-12-10', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 20:23:40', '2025-12-11 20:23:40'),
(261, 7, 34, NULL, 10, 18, 'labor', 'Wanderlei Melo', 300.00, '2025-12-11', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-11 21:20:20', '2025-12-11 21:20:20'),
(262, 10, 33, NULL, 15, NULL, 'materials', 'Home Depot', 800.00, '2025-12-10', NULL, NULL, NULL, 'credit_card', 0, 'approved', 7, NULL, NULL, NULL, '2025-12-12 14:09:17', '2025-12-12 14:09:17'),
(263, 10, 33, NULL, 15, 29, 'labor', 'Teste', 160.00, '2025-12-12', NULL, NULL, NULL, 'transfer', 0, 'approved', 9, NULL, NULL, NULL, '2025-12-12 15:14:52', '2025-12-12 15:14:52'),
(264, 10, 33, NULL, 15, 29, 'labor', 'qualquer', 200.00, '2025-12-12', NULL, NULL, NULL, 'credit_card', 0, 'approved', 10, NULL, NULL, NULL, '2025-12-12 15:25:22', '2025-12-12 15:25:22'),
(265, 7, 34, NULL, 10, 30, 'labor', 'Pablo', 67.00, '2025-12-10', '', NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-15 22:03:43', '2025-12-15 17:04:46'),
(266, 7, 30, NULL, 10, 30, 'labor', 'Pablo', 66.00, '2025-12-10', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-15 22:06:03', '2025-12-15 22:06:03'),
(267, 7, 35, NULL, 10, 30, 'labor', 'Pablo', 66.00, '2025-12-10', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-15 22:06:30', '2025-12-15 22:06:30'),
(268, 7, 17, NULL, 10, 30, 'labor', 'Pablo', 200.00, '2025-12-11', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-15 22:10:07', '2025-12-15 22:10:07'),
(269, 7, 17, NULL, 10, 27, 'labor', 'Sergio', 150.00, '2025-12-10', '', NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-15 22:10:23', '2025-12-15 17:19:08'),
(270, 7, 17, NULL, 10, 25, 'labor', 'João Alfredo', 125.00, '2025-12-11', '', NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-15 22:10:47', '2025-12-15 17:11:13'),
(271, 7, 17, NULL, 10, 23, 'labor', 'Esvin', 90.00, '2025-12-11', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-15 22:11:07', '2025-12-15 22:11:07'),
(272, 7, 9, NULL, 10, 24, 'labor', 'Aparecido', 170.00, '2025-12-11', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-15 22:11:46', '2025-12-15 22:11:46'),
(273, 7, 9, NULL, 10, 25, 'labor', 'João Alfredo', 125.00, '2025-12-11', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-15 22:12:31', '2025-12-15 22:12:31'),
(274, 7, 9, NULL, 10, 23, 'labor', 'Esvin', 90.00, '2025-12-11', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-15 22:12:54', '2025-12-15 22:12:54'),
(275, 7, 36, NULL, 10, 9, 'labor', 'Matheus', 100.00, '2025-12-11', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-15 22:14:49', '2025-12-15 22:14:49'),
(276, 7, 31, NULL, 10, NULL, 'fuel', 'Quick Trip', 32.29, '2025-12-08', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-15 22:15:29', '2025-12-15 22:15:29'),
(277, 7, 17, NULL, 10, NULL, 'materials', 'Home Depot', 77.62, '2025-12-11', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-15 22:16:13', '2025-12-15 22:16:13'),
(278, 7, 36, NULL, 10, NULL, 'materials', 'Home Depot', 57.65, '2025-12-11', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-15 22:17:16', '2025-12-15 22:17:16'),
(279, 7, 9, NULL, 10, NULL, 'materials', 'Home Depot', 83.74, '2025-12-11', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-15 22:18:06', '2025-12-15 22:18:06'),
(280, 7, 17, NULL, 10, NULL, 'materials', 'Floor & Decor', 1757.78, '2025-12-10', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-15 22:20:57', '2025-12-15 22:20:57'),
(281, 7, 17, NULL, 10, NULL, 'materials', 'Home Depot', 171.05, '2025-12-10', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-15 22:21:55', '2025-12-15 22:21:55'),
(282, 7, 9, NULL, 10, NULL, 'materials', 'Home Depot', 143.50, '2025-12-11', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-15 22:23:37', '2025-12-15 22:23:37'),
(283, 7, 17, NULL, 10, NULL, 'materials', 'Sherwin Willians', 63.55, '2025-12-12', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-15 22:24:32', '2025-12-15 22:24:32'),
(284, 7, 17, NULL, 10, NULL, 'materials', 'Home Depot', 190.23, '2025-12-12', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-15 22:25:43', '2025-12-15 22:25:43'),
(285, 7, 17, NULL, 10, NULL, 'materials', 'Home Depot', 44.73, '2025-12-12', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-15 22:28:28', '2025-12-15 22:28:28'),
(286, 7, 8, NULL, 10, NULL, 'materials', 'Cabinetry', 5925.07, '2025-12-14', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-15 22:29:26', '2025-12-15 22:29:26'),
(287, 7, 36, NULL, 10, 25, 'labor', 'João Alfredo', 250.00, '2025-12-12', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-15 22:30:44', '2025-12-15 22:30:44'),
(288, 7, 36, NULL, 10, 24, 'labor', 'Aparecido', 170.00, '2025-12-12', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-15 22:31:07', '2025-12-15 22:31:07'),
(289, 7, 17, NULL, 10, 22, 'labor', 'Leonel', 170.00, '2025-12-12', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-15 22:33:53', '2025-12-15 22:33:53');
INSERT INTO `expenses` (`id`, `tenant_id`, `project_id`, `budget_id`, `user_id`, `employee_id`, `category`, `description`, `amount`, `expense_date`, `vendor`, `receipt_number`, `receipt_path`, `payment_method`, `is_billable`, `status`, `journal_entry_id`, `approved_by`, `approved_at`, `notes`, `created_at`, `updated_at`) VALUES
(290, 7, 17, NULL, 10, 30, 'labor', 'Pablo', 200.00, '2025-12-12', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-15 22:34:11', '2025-12-15 22:34:11'),
(291, 7, 17, NULL, 10, 27, 'labor', 'Sergio', 150.00, '2025-12-12', '', NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-15 22:34:28', '2025-12-15 17:40:28'),
(292, 7, 8, NULL, 10, NULL, 'materials', 'Home Depot', 124.78, '2025-12-15', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-15 22:39:44', '2025-12-15 22:39:44'),
(293, 7, 17, NULL, 10, NULL, 'materials', 'Home Depot', 60.06, '2025-12-15', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-15 22:40:49', '2025-12-15 22:40:49'),
(294, 7, 37, NULL, 10, 23, 'labor', 'Esvin', 180.00, '2025-12-12', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-16 16:43:53', '2025-12-16 16:43:53'),
(295, 7, 17, NULL, 10, 23, 'labor', 'Esvin', 180.00, '2025-12-15', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-16 16:45:00', '2025-12-16 16:45:00'),
(296, 7, 17, NULL, 10, 22, 'labor', 'Leonel', 170.00, '2025-12-17', '', NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-16 16:45:23', '2025-12-18 13:29:00'),
(297, 7, 36, NULL, 10, 27, 'labor', 'Sergio', 150.00, '2025-12-15', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-16 16:46:24', '2025-12-16 16:46:24'),
(298, 7, 36, NULL, 10, 24, 'labor', 'Aparecido', 170.00, '2025-12-15', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-16 16:46:43', '2025-12-16 16:46:43'),
(299, 7, 34, NULL, 10, 18, 'labor', 'Wanderlei Melo', 300.00, '2025-12-11', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-16 16:48:27', '2025-12-16 16:48:27'),
(300, 7, 8, NULL, 10, 25, 'labor', 'João Alfredo', 250.00, '2025-12-15', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-16 16:49:03', '2025-12-16 16:49:03'),
(301, 7, 8, NULL, 10, 30, 'labor', 'Pablo', 200.00, '2025-12-15', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-16 16:49:20', '2025-12-16 16:49:20'),
(302, 7, 29, NULL, 10, NULL, 'materials', 'Home Depot', 74.31, '2025-12-01', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-17 14:46:05', '2025-12-17 14:46:05'),
(303, 7, 32, NULL, 10, NULL, 'materials', 'Home Depot', 21.62, '2025-10-20', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-17 14:48:14', '2025-12-17 14:48:14'),
(304, 7, 32, NULL, 10, NULL, 'materials', 'Home Depot', 8.54, '2025-11-21', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-17 14:51:04', '2025-12-17 14:51:04'),
(305, 7, 38, NULL, 10, NULL, 'materials', 'Home Depot', 148.06, '2025-12-17', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-18 17:55:37', '2025-12-18 17:55:37'),
(306, 7, 8, NULL, 10, 23, 'labor', 'Esvin', 180.00, '2025-12-16', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-18 18:25:10', '2025-12-18 18:25:10'),
(307, 7, 8, NULL, 10, 30, 'labor', 'Pablo', 200.00, '2025-12-16', '', NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-18 18:25:24', '2025-12-18 13:27:06'),
(308, 7, 8, NULL, 10, 25, 'labor', 'João Alfredo', 250.00, '2025-12-16', '', NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-18 18:25:45', '2025-12-18 13:27:12'),
(309, 7, 36, NULL, 10, 24, 'labor', 'Aparecido', 170.00, '2025-12-16', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-18 18:26:25', '2025-12-18 18:26:25'),
(310, 7, 36, NULL, 10, 27, 'labor', 'Sergio', 150.00, '2025-12-16', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-18 18:26:43', '2025-12-18 18:26:43'),
(311, 7, 34, NULL, 10, 18, 'labor', 'Wanderlei Melo', 300.00, '2025-12-16', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-18 18:27:58', '2025-12-18 18:27:58'),
(312, 7, 34, NULL, 10, 17, 'labor', 'Josue', 100.00, '2025-12-16', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-18 18:28:18', '2025-12-18 18:28:18'),
(313, 7, 17, NULL, 10, 22, 'labor', 'Leonel', 170.00, '2025-12-11', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-18 18:28:48', '2025-12-18 18:28:48'),
(314, 7, 17, NULL, 10, 23, 'labor', 'Esvin', 180.00, '2025-12-17', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-18 18:29:22', '2025-12-18 18:29:22'),
(315, 7, 17, NULL, 10, 30, 'labor', 'Pablo', 200.00, '2025-12-17', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-18 18:29:40', '2025-12-18 18:29:40'),
(316, 7, 17, NULL, 10, 25, 'labor', 'João Alfredo', 250.00, '2025-12-17', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-18 18:30:03', '2025-12-18 18:30:03'),
(317, 7, 34, NULL, 10, 18, 'labor', 'Wanderlei Melo', 300.00, '2025-12-17', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-18 18:30:32', '2025-12-18 18:30:32'),
(318, 7, 36, NULL, 10, 24, 'labor', 'Aparecido', 170.00, '2025-12-17', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-18 18:30:57', '2025-12-18 18:30:57'),
(319, 7, 36, NULL, 10, 27, 'labor', 'Sergio', 150.00, '2025-12-17', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-18 18:31:13', '2025-12-18 18:31:13'),
(320, 7, 22, NULL, 10, 25, 'labor', 'João Alfredo', 125.00, '2025-12-18', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-19 19:21:34', '2025-12-19 19:21:34'),
(321, 7, 22, NULL, 10, 27, 'labor', 'Sergio', 150.00, '2025-12-19', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-19 19:22:01', '2025-12-19 19:22:01'),
(322, 7, 36, NULL, 10, 24, 'labor', 'Aparecido', 170.00, '2025-12-18', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-19 19:22:42', '2025-12-19 19:22:42'),
(323, 7, 36, NULL, 10, 23, 'labor', 'Esvin', 180.00, '2025-12-18', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-19 19:22:57', '2025-12-19 19:22:57'),
(324, 7, 36, NULL, 10, NULL, 'materials', 'Home Depot', 66.00, '2025-12-19', '', NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-19 19:23:53', '2025-12-19 14:24:22'),
(325, 7, 10, NULL, 10, NULL, 'materials', 'Home Depot', 13.94, '2025-12-18', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-19 19:25:00', '2025-12-19 19:25:00'),
(326, 7, 22, NULL, 10, NULL, 'materials', 'Home Depot', 97.47, '2025-12-19', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-19 19:25:36', '2025-12-19 19:25:36'),
(327, 7, 22, NULL, 10, NULL, 'materials', 'Home Depot', 84.53, '2025-12-19', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-19 19:25:54', '2025-12-19 19:25:54'),
(328, 7, 34, NULL, 10, 18, 'labor', 'Wanderlei Melo', 300.00, '2025-12-18', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-19 19:26:28', '2025-12-19 19:26:28'),
(329, 7, 27, NULL, 10, 30, 'labor', 'Pablo', 200.00, '2025-12-18', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-19 19:26:58', '2025-12-19 19:26:58'),
(330, 7, 27, NULL, 10, 22, 'labor', 'Leonel', 170.00, '2025-12-19', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2025-12-19 19:27:11', '2025-12-19 19:27:11'),
(331, 7, 39, NULL, 10, NULL, 'materials', 'Home Depot', 15.82, '2025-12-18', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 11:59:48', '2026-01-20 11:59:48'),
(332, 7, 39, NULL, 10, NULL, 'materials', 'Home Depot', 19.19, '2025-12-18', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 12:00:09', '2026-01-20 12:00:09'),
(334, 7, 8, NULL, 10, 18, 'labor', 'Wanderlei Melo', 300.00, '2026-01-08', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 12:23:15', '2026-01-20 12:23:15'),
(335, 7, 8, NULL, 10, 23, 'labor', 'Esvin', 180.00, '2026-01-08', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 12:23:37', '2026-01-20 12:23:37'),
(336, 7, 8, NULL, 10, 24, 'labor', 'Aparecido', 170.00, '2026-01-08', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 12:23:58', '2026-01-20 12:23:58'),
(337, 7, 9, NULL, 10, NULL, 'materials', 'Home Depot', 259.46, '2026-01-19', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 12:55:11', '2026-01-20 12:55:11'),
(338, 7, 9, NULL, 10, 25, 'labor', 'João Alfredo', 250.00, '2026-01-19', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 12:55:54', '2026-01-20 12:55:54'),
(339, 7, 9, NULL, 10, 9, 'labor', 'Matheus', 100.00, '2026-01-19', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 12:56:15', '2026-01-20 12:56:15'),
(340, 7, 9, NULL, 10, 23, 'labor', 'Esvin', 180.00, '2026-01-19', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 12:56:33', '2026-01-20 12:56:33'),
(341, 7, 9, NULL, 10, NULL, 'materials', 'Home Depot', 108.16, '2026-01-19', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 12:56:59', '2026-01-20 12:56:59'),
(342, 7, 9, NULL, 10, NULL, 'materials', 'Floor Decor', 51.00, '2025-12-23', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 12:57:51', '2026-01-20 12:57:51'),
(343, 7, 9, NULL, 10, NULL, 'materials', 'Floor Decor', 315.00, '2025-12-23', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 12:58:16', '2026-01-20 12:58:16'),
(344, 7, 9, NULL, 10, NULL, 'materials', 'Home Depot', 89.96, '2025-12-30', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 12:58:56', '2026-01-20 12:58:56'),
(345, 7, 40, NULL, 10, NULL, 'materials', 'Home Depot', 41.02, '2026-01-14', '', NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 13:27:28', '2026-01-20 08:28:41'),
(346, 7, 40, NULL, 10, NULL, 'materials', 'Sherwin Williams', 75.11, '2026-01-19', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 13:29:37', '2026-01-20 13:29:37'),
(347, 7, 40, NULL, 10, 18, 'labor', 'Wanderlei Melo', 300.00, '2026-01-15', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 13:30:17', '2026-01-20 13:30:17'),
(348, 7, 40, NULL, 10, 22, 'labor', 'Leonel', 170.00, '2026-01-15', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 13:30:37', '2026-01-20 13:30:37'),
(349, 7, 40, NULL, 10, 18, 'labor', 'Wanderlei Melo', 300.00, '2026-01-14', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 13:31:05', '2026-01-20 13:31:05'),
(350, 7, 40, NULL, 10, 9, 'labor', 'Matheus', 100.00, '2026-01-14', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 13:31:24', '2026-01-20 13:31:24'),
(351, 7, 40, NULL, 10, 27, 'labor', 'Sergio', 150.00, '2026-01-14', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 13:31:42', '2026-01-20 13:31:42'),
(352, 7, 40, NULL, 10, 22, 'labor', 'Leonel', 170.00, '2026-01-14', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 13:32:15', '2026-01-20 13:32:15'),
(353, 7, 40, NULL, 10, NULL, 'materials', 'GFL', 138.20, '2026-01-15', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 13:33:41', '2026-01-20 13:33:41'),
(354, 7, 40, NULL, 10, 18, 'labor', 'Wanderlei Melo', 300.00, '2026-01-19', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 13:34:16', '2026-01-20 13:34:16'),
(355, 7, 41, NULL, 10, 18, 'labor', 'Wanderlei Melo', 300.00, '2026-01-12', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 13:40:43', '2026-01-20 13:40:43'),
(356, 7, 41, NULL, 10, 24, 'labor', 'Aparecido', 170.00, '2026-01-12', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 13:41:03', '2026-01-20 13:41:03'),
(357, 7, 41, NULL, 10, 22, 'labor', 'Leonel', 170.00, '2026-01-12', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 13:41:20', '2026-01-20 13:41:20'),
(358, 7, 41, NULL, 10, 27, 'labor', 'Sergio', 150.00, '2026-01-12', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 13:41:37', '2026-01-20 13:41:37'),
(359, 7, 41, NULL, 10, NULL, 'materials', 'Home Depot', 125.97, '2026-01-15', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 13:42:06', '2026-01-20 13:42:06'),
(360, 7, 41, NULL, 10, 24, 'labor', 'Aparecido', 170.00, '2026-01-15', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 13:42:37', '2026-01-20 13:42:37'),
(361, 7, 41, NULL, 10, 27, 'labor', 'Sergio', 150.00, '2026-01-15', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 13:42:53', '2026-01-20 13:42:53'),
(362, 7, 41, NULL, 10, 18, 'labor', 'Wanderlei Melo', 300.00, '2026-01-13', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 13:43:20', '2026-01-20 13:43:20'),
(363, 7, 41, NULL, 10, 23, 'labor', 'Esvin', 180.00, '2026-01-13', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 13:43:37', '2026-01-20 13:43:37'),
(364, 7, 42, NULL, 10, 31, 'labor', 'Ebenezer', 1700.00, '2026-01-16', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 13:46:15', '2026-01-20 13:46:15'),
(365, 7, 31, NULL, 10, NULL, 'fuel', 'Kroger Fuel', 41.52, '2025-12-11', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 13:47:38', '2026-01-20 13:47:38'),
(367, 7, 34, NULL, 10, 25, 'labor', 'João Alfredo', 250.00, '2026-01-15', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 13:49:35', '2026-01-20 13:49:35'),
(368, 7, 34, NULL, 10, 9, 'labor', 'Matheus', 100.00, '2026-01-15', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 13:49:51', '2026-01-20 13:49:51'),
(369, 7, 34, NULL, 10, 25, 'labor', 'João Alfredo', 250.00, '2026-01-14', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 13:50:14', '2026-01-20 13:50:14'),
(370, 7, 34, NULL, 10, 24, 'labor', 'Aparecido', 170.00, '2026-01-14', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 13:50:31', '2026-01-20 13:50:31'),
(371, 7, 43, NULL, 10, NULL, 'fuel', 'Kroger Fuel', 41.52, '2026-01-14', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 13:53:24', '2026-01-20 13:53:24'),
(372, 7, 43, NULL, 10, NULL, 'fuel', 'Shell', 50.25, '2026-01-13', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 13:53:49', '2026-01-20 13:53:49'),
(373, 7, 43, NULL, 10, NULL, 'other', 'Bam Auto Repair- Pintura Honda', 1950.00, '2026-01-20', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-20 13:55:14', '2026-01-20 13:55:14'),
(374, 7, 39, NULL, 10, 8, 'labor', 'Jervin', 350.00, '2026-01-20', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-22 14:33:37', '2026-01-22 14:33:37'),
(375, 7, 39, NULL, 10, 20, 'labor', 'Comissão Sofia', 193.93, '2026-01-22', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-22 14:36:53', '2026-01-22 14:36:53'),
(376, 7, 8, NULL, 10, NULL, 'materials', 'Home Depot', 76.22, '2026-01-20', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-22 14:38:45', '2026-01-22 14:38:45'),
(377, 7, 8, NULL, 10, NULL, 'materials', 'Home Depot', 158.68, '2026-01-20', '', NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-22 14:39:04', '2026-01-22 09:39:54'),
(378, 7, 8, NULL, 10, 25, 'labor', 'João Alfredo', 250.00, '2026-01-20', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-22 14:39:47', '2026-01-22 14:39:47'),
(379, 7, 8, NULL, 10, 9, 'labor', 'Matheus', 100.00, '2026-01-20', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-22 14:40:18', '2026-01-22 14:40:18'),
(380, 7, 8, NULL, 10, 20, 'labor', 'Comissão Sofia', 2174.61, '2026-01-22', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-22 14:47:39', '2026-01-22 14:47:39'),
(381, 7, 9, NULL, 10, 20, 'labor', 'Comissão Sofia', 1193.43, '2026-01-22', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-22 14:51:46', '2026-01-22 14:51:46'),
(382, 7, 9, NULL, 10, 21, 'labor', 'Jose Borges Junior', 300.00, '2026-01-20', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-22 14:53:41', '2026-01-22 14:53:41'),
(383, 7, 9, NULL, 10, 23, 'labor', 'Esvin', 180.00, '2026-01-20', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-22 14:53:59', '2026-01-22 14:53:59'),
(384, 7, 41, NULL, 10, 24, 'labor', 'Aparecido', 170.00, '2026-01-20', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-22 14:55:31', '2026-01-22 14:55:31'),
(385, 7, 41, NULL, 10, 22, 'labor', 'Leonel', 170.00, '2026-01-20', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-22 14:55:48', '2026-01-22 14:55:48'),
(386, 7, 40, NULL, 10, NULL, 'materials', 'Home Depot', 10.22, '2026-01-20', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-22 14:56:23', '2026-01-22 14:56:23'),
(387, 7, 40, NULL, 10, NULL, 'materials', 'Home Depot', 11.52, '2026-01-20', '', NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-22 14:56:36', '2026-01-22 09:57:19'),
(388, 7, 40, NULL, 10, 18, 'labor', 'Wanderlei Melo', 300.00, '2026-01-20', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-22 14:57:03', '2026-01-22 14:57:03'),
(389, 7, 43, NULL, 10, NULL, 'fuel', 'Chevron', 50.02, '2026-01-21', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-22 14:58:05', '2026-01-22 14:58:05'),
(390, 7, 43, NULL, 10, NULL, 'fuel', 'Quick Trip', 52.48, '2026-01-22', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-22 20:41:14', '2026-01-22 20:41:14'),
(391, 7, 41, NULL, 10, NULL, 'materials', 'Home Depot', 105.53, '2026-01-21', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-22 20:42:21', '2026-01-22 20:42:21'),
(392, 7, 41, NULL, 10, NULL, 'fuel', 'Quick Trip', 59.78, '2026-01-21', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-22 20:43:37', '2026-01-22 20:43:37'),
(393, 7, 40, NULL, 10, 18, 'labor', 'Wanderlei Melo', 300.00, '2026-01-21', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-26 18:26:30', '2026-01-26 18:26:30'),
(394, 7, 40, NULL, 10, 18, 'labor', 'Wanderlei Melo', 300.00, '2026-01-22', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-26 18:26:52', '2026-01-26 18:26:52'),
(395, 7, 41, NULL, 10, 24, 'labor', 'Aparecido', 170.00, '2026-01-21', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-26 18:27:33', '2026-01-26 18:27:33'),
(396, 7, 41, NULL, 10, 23, 'labor', 'Esvin', 180.00, '2026-01-21', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-26 18:27:49', '2026-01-26 18:27:49'),
(397, 7, 34, NULL, 10, 25, 'labor', 'João Alfredo', 250.00, '2026-01-22', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-26 18:28:36', '2026-01-26 18:28:36'),
(398, 7, 34, NULL, 10, 9, 'labor', 'Matheus', 100.00, '2026-01-22', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-26 18:28:55', '2026-01-26 18:28:55'),
(399, 7, 8, NULL, 10, 25, 'labor', 'João Alfredo', 250.00, '2026-01-21', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-26 18:29:39', '2026-01-26 18:29:39'),
(400, 7, 8, NULL, 10, 9, 'labor', 'Matheus', 100.00, '2026-01-21', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-26 18:29:55', '2026-01-26 18:29:55'),
(401, 7, 44, NULL, 10, 22, 'labor', 'Leonel', 170.00, '2026-01-22', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-26 18:31:17', '2026-01-26 18:31:17'),
(402, 7, 44, NULL, 10, 27, 'labor', 'Sergio', 150.00, '2026-01-22', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-26 18:31:36', '2026-01-26 18:31:36'),
(403, 7, 44, NULL, 10, NULL, 'materials', 'Home Depot', 47.17, '2026-01-21', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-26 18:32:02', '2026-01-26 18:32:02'),
(404, 7, 40, NULL, 10, 18, 'labor', 'Wanderlei Melo', 300.00, '2026-01-23', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-26 21:54:01', '2026-01-26 21:54:01'),
(405, 7, 34, NULL, 10, 25, 'labor', 'João Alfredo', 250.00, '2026-01-23', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-26 21:54:39', '2026-01-26 21:54:39'),
(406, 7, 34, NULL, 10, 9, 'labor', 'Matheus ', 100.00, '2026-01-23', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-26 21:55:05', '2026-01-26 21:55:05'),
(407, 7, 43, NULL, 10, NULL, 'fuel', 'Shell', 47.68, '2026-01-26', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-26 22:38:15', '2026-01-26 22:38:15'),
(408, 7, 41, NULL, 10, NULL, 'permits', 'GFL', 95.00, '2026-01-27', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-27 22:54:24', '2026-01-27 22:54:24'),
(409, 7, 41, NULL, 10, NULL, 'materials', 'Home Depot', 14.57, '2026-01-27', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-27 22:54:55', '2026-01-27 22:54:55'),
(410, 7, 41, NULL, 10, NULL, 'materials', 'Sherwin Willians', 94.00, '2026-01-27', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-27 22:55:50', '2026-01-27 22:55:50'),
(411, 7, 41, NULL, 10, NULL, 'materials', 'Home Depot', 134.61, '2026-01-27', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-01-27 22:56:21', '2026-01-27 22:56:21'),
(412, 7, 22, NULL, 10, 25, 'labor', 'João Alfredo', 125.00, '2026-01-26', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 18:42:41', '2026-02-02 18:42:41'),
(413, 7, 22, NULL, 10, 9, 'labor', 'Matheus', 50.00, '2026-01-26', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 18:43:01', '2026-02-02 18:43:01'),
(414, 7, 43, NULL, 10, NULL, 'fuel', 'Cliper 71', 72.27, '2026-01-28', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 18:47:09', '2026-02-02 18:47:09'),
(415, 7, 40, NULL, 10, NULL, 'materials', 'Home Depot', 40.89, '2026-01-29', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 18:49:04', '2026-02-02 18:49:04'),
(416, 7, 43, NULL, 10, NULL, 'fuel', 'Costco', 67.65, '2026-01-29', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 18:49:55', '2026-02-02 18:49:55'),
(417, 7, 41, NULL, 10, 23, 'labor', 'Esvin', 180.00, '2026-01-29', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 18:50:38', '2026-02-02 18:50:38'),
(418, 7, 41, NULL, 10, 23, 'materials', 'Esvin', 180.00, '2026-01-28', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 18:51:02', '2026-02-02 18:51:02'),
(419, 7, 41, NULL, 10, 27, 'labor', 'Sergio', 150.00, '2026-01-28', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 18:51:23', '2026-02-02 18:51:23'),
(420, 7, 41, NULL, 10, 23, 'labor', 'Esvin', 180.00, '2026-01-27', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 18:51:48', '2026-02-02 18:51:48'),
(421, 7, 41, NULL, 10, 21, 'labor', 'Jose Borges Junior', 300.00, '2026-01-27', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 18:52:13', '2026-02-02 18:52:13'),
(422, 7, 40, NULL, 10, 27, 'labor', 'Sergio', 150.00, '2026-01-29', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 18:54:38', '2026-02-02 18:54:38'),
(423, 7, 40, NULL, 10, 18, 'labor', 'Wanderlei Melo', 300.00, '2026-01-28', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 18:55:04', '2026-02-02 18:55:04'),
(424, 7, 40, NULL, 10, 18, 'labor', 'Wanderlei Melo', 300.00, '2026-01-27', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 18:55:39', '2026-02-02 18:55:39'),
(425, 7, 40, NULL, 10, 8, 'labor', 'Jervin', 350.00, '2026-01-29', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 18:56:40', '2026-02-02 18:56:40'),
(426, 7, 40, NULL, 10, 8, 'labor', 'Jervin', 350.00, '2026-01-31', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 18:57:39', '2026-02-02 18:57:39'),
(427, 7, 40, NULL, 10, 23, 'labor', 'Esvin', 180.00, '2026-01-31', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 18:58:07', '2026-02-02 18:58:07'),
(428, 7, 40, NULL, 10, 27, 'labor', 'Sergio', 150.00, '2026-01-31', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 18:58:25', '2026-02-02 18:58:25'),
(429, 7, 40, NULL, 10, 23, 'labor', 'Esvin', 180.00, '2026-01-30', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 18:58:54', '2026-02-02 18:58:54'),
(430, 7, 40, NULL, 10, 27, 'labor', 'Sergio', 150.00, '2026-01-30', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 18:59:18', '2026-02-02 18:59:18'),
(431, 7, 40, NULL, 10, 8, 'labor', 'Jervin', 350.00, '2026-01-30', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 18:59:35', '2026-02-02 18:59:35'),
(432, 7, 40, NULL, 10, 18, 'labor', 'Wanderlei Melo', 300.00, '2026-01-29', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 19:00:34', '2026-02-02 19:00:34'),
(433, 7, 27, NULL, 10, 22, 'labor', 'Leonel', 170.00, '2026-01-29', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 19:01:46', '2026-02-02 19:01:46'),
(434, 7, 27, NULL, 10, 24, 'labor', 'Aparecido', 170.00, '2026-01-29', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 19:02:03', '2026-02-02 19:02:03'),
(435, 7, 27, NULL, 10, 24, 'labor', 'Aparecido', 170.00, '2026-01-28', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 19:02:40', '2026-02-02 19:02:40'),
(436, 7, 27, NULL, 10, 25, 'labor', 'João Alfredo', 125.00, '2026-01-28', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 19:03:02', '2026-02-02 19:03:02'),
(437, 7, 27, NULL, 10, 22, 'labor', 'Leonel', 170.00, '2026-01-28', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 19:03:30', '2026-02-02 19:03:30'),
(438, 7, 27, NULL, 10, 24, 'labor', 'Aparecido', 170.00, '2026-01-27', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 19:03:53', '2026-02-02 19:03:53'),
(439, 7, 27, NULL, 10, 25, 'labor', 'João Alfredo', 125.00, '2026-01-27', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 19:04:20', '2026-02-02 19:04:20'),
(440, 7, 27, NULL, 10, 22, 'labor', 'Leonel', 170.00, '2026-01-27', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 19:04:40', '2026-02-02 19:04:40'),
(441, 7, 27, NULL, 10, 27, 'labor', 'Sergio', 150.00, '2026-01-27', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 19:04:56', '2026-02-02 19:04:56'),
(442, 7, 27, NULL, 10, 18, 'labor', 'Wanderlei Melo', 300.00, '2026-01-30', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 19:05:23', '2026-02-02 19:05:23'),
(443, 7, 27, NULL, 10, 25, 'labor', 'João Alfredo', 250.00, '2026-01-30', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 19:05:44', '2026-02-02 19:05:44'),
(444, 7, 27, NULL, 10, 9, 'labor', 'Matheus', 100.00, '2026-01-30', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 19:06:02', '2026-02-02 19:06:02'),
(445, 7, 34, NULL, 10, 25, 'labor', 'João Alfredo', 250.00, '2026-01-29', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 19:07:20', '2026-02-02 19:07:20'),
(446, 7, 34, NULL, 10, 9, 'labor', 'Matheus', 100.00, '2026-01-29', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 19:07:37', '2026-02-02 19:07:37'),
(447, 7, 40, NULL, 10, NULL, 'materials', 'Home Depot', 52.71, '2026-01-30', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 19:08:18', '2026-02-02 19:08:18'),
(448, 7, 40, NULL, 10, NULL, 'materials', 'Home Depot', 79.32, '2026-01-30', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 19:08:44', '2026-02-02 19:08:44'),
(449, 7, 8, NULL, 10, NULL, 'materials', 'Home Depot', 36.45, '2026-02-02', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 19:09:26', '2026-02-02 19:09:26'),
(450, 7, 40, NULL, 10, NULL, 'materials', 'Roc Cabenetry', 32.14, '2026-02-02', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-02 22:39:32', '2026-02-02 22:39:32'),
(451, 7, 40, NULL, 10, NULL, 'materials', 'Sherwin Willians', 75.08, '2026-02-03', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-10 11:15:49', '2026-02-10 11:15:49'),
(452, 7, 40, NULL, 10, 23, 'labor', 'Esvin', 180.00, '2026-02-03', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-10 11:17:08', '2026-02-10 11:17:08'),
(453, 7, 40, NULL, 10, 8, 'labor', 'Jervin', 350.00, '2026-02-03', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-10 11:17:47', '2026-02-10 11:17:47'),
(454, 7, 40, NULL, 10, 27, 'labor', 'Sergio', 150.00, '2026-02-03', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-10 11:18:11', '2026-02-10 11:18:11'),
(455, 7, 41, NULL, 10, 18, 'labor', 'Wanderlei Melo', 300.00, '2026-02-03', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-10 11:26:30', '2026-02-10 11:26:30'),
(456, 7, 41, NULL, 10, 18, 'labor', 'Wanderlei Melo', 300.00, '2026-02-02', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-10 11:26:59', '2026-02-10 11:26:59'),
(457, 7, 34, NULL, 10, 9, 'labor', 'Matheus', 100.00, '2026-02-03', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-10 11:28:03', '2026-02-10 11:28:03'),
(458, 7, 34, NULL, 10, 22, 'labor', 'Leonel', 170.00, '2026-02-03', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-10 11:28:24', '2026-02-10 11:28:24'),
(459, 7, 27, NULL, 10, 24, 'labor', 'Aparecido', 170.00, '2026-02-02', '', NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-10 11:29:22', '2026-02-10 06:30:19'),
(460, 7, 27, NULL, 10, 23, 'labor', 'Esvin', 180.00, '2026-02-02', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-10 11:30:07', '2026-02-10 11:30:07'),
(461, 7, 45, NULL, 10, NULL, 'fuel', 'BP', 63.97, '2026-02-05', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-10 11:33:27', '2026-02-10 11:33:27'),
(462, 7, 45, NULL, 10, NULL, 'fuel', 'QT', 32.08, '2026-02-04', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-10 11:33:50', '2026-02-10 11:33:50'),
(463, 7, 41, NULL, 10, NULL, 'materials', 'Sherwin Willians', 88.93, '2026-02-05', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-10 11:34:35', '2026-02-10 11:34:35'),
(464, 7, 40, NULL, 10, NULL, 'materials', 'Roc Cabenetry', 248.12, '2026-02-05', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-10 11:36:06', '2026-02-10 11:36:06'),
(465, 7, 45, NULL, 10, NULL, 'fuel', 'Shell', 25.00, '2026-02-05', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-10 11:36:46', '2026-02-10 11:36:46'),
(466, 7, 8, NULL, 10, 32, 'labor', 'Gilvan Guimaraes', 2025.00, '2026-02-06', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-10 11:38:54', '2026-02-10 11:38:54'),
(467, 7, 41, NULL, 10, 18, 'labor', 'Wanderlei Melo', 300.00, '2026-02-04', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-10 11:40:04', '2026-02-10 11:40:04'),
(468, 7, 41, NULL, 10, 18, 'labor', 'Wanderlei Melo', 300.00, '2026-02-05', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-10 11:40:27', '2026-02-10 11:40:27'),
(469, 7, 41, NULL, 10, 23, 'labor', 'Esvin', 180.00, '2026-02-05', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-10 11:40:44', '2026-02-10 11:40:44'),
(470, 7, 27, NULL, 10, 9, 'labor', 'Matheus ', 100.00, '2026-02-05', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-10 11:41:32', '2026-02-10 11:41:32'),
(471, 7, 27, NULL, 10, 22, 'labor', 'Leonel', 170.00, '2026-02-05', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-10 11:41:55', '2026-02-10 11:41:55'),
(472, 7, 27, NULL, 10, 25, 'labor', 'Joao Alfredo', 250.00, '2026-02-05', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-10 11:42:14', '2026-02-10 11:42:14'),
(473, 7, 40, NULL, 10, 8, 'labor', 'Jervin', 350.00, '2026-02-05', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-10 11:42:50', '2026-02-10 11:42:50'),
(474, 7, 9, NULL, 10, NULL, 'materials', 'Amazon - Glass Door', 286.19, '2026-01-17', '', NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-10 11:43:53', '2026-02-10 06:45:56'),
(475, 7, 9, NULL, 10, NULL, 'materials', 'Amazon - Acquaer Sewage Pumo', 205.75, '2026-02-06', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-10 11:45:42', '2026-02-10 11:45:42'),
(476, 7, 40, NULL, 10, NULL, 'materials', 'Floor & Decor', 240.68, '2026-01-28', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-10 11:46:56', '2026-02-10 11:46:56'),
(477, 7, 41, NULL, 10, NULL, 'materials', 'Floor & Decor', 52.85, '2026-02-04', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-10 11:47:37', '2026-02-10 11:47:37'),
(478, 7, 41, NULL, 10, NULL, 'materials', 'Floor & Decor', 42.46, '2026-02-03', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-10 11:48:05', '2026-02-10 11:48:05'),
(479, 7, 45, NULL, 10, NULL, 'materials', 'GFL', 49.40, '2026-02-09', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-10 11:48:47', '2026-02-10 11:48:47'),
(480, 7, 41, NULL, 10, NULL, 'materials', 'Home Depot', 201.37, '2026-02-09', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-10 11:49:23', '2026-02-10 11:49:23'),
(481, 7, 45, NULL, 10, NULL, 'fuel', 'Shell', 39.03, '2026-02-09', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-10 11:49:58', '2026-02-10 11:49:58'),
(482, 7, 40, NULL, 10, NULL, 'materials', 'Cabenetry', 8352.04, '2026-01-28', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-11 16:07:37', '2026-02-11 16:07:37'),
(483, 7, 45, NULL, 10, NULL, 'fuel', 'Shell', 52.00, '2026-02-10', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-11 16:11:37', '2026-02-11 16:11:37'),
(484, 7, 41, NULL, 10, 23, 'labor', 'Esvin', 180.00, '2026-02-09', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-11 16:40:05', '2026-02-11 16:40:05'),
(485, 7, 41, NULL, 10, 27, 'labor', 'Sergio', 150.00, '2026-02-09', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-11 16:40:26', '2026-02-11 16:40:26'),
(486, 7, 45, NULL, 10, 9, 'labor', 'Matheus', 100.00, '2026-02-09', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-11 16:40:54', '2026-02-11 16:40:54'),
(487, 7, 45, NULL, 10, 22, 'labor', 'Leonel', 170.00, '2026-02-09', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-11 16:41:14', '2026-02-11 16:41:14'),
(489, 7, 40, NULL, 10, NULL, 'materials', 'Cabenetry', 2235.28, '2026-01-20', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-12 16:58:33', '2026-02-12 16:58:33'),
(490, 7, 41, NULL, 10, NULL, 'materials', 'Home Depot', 143.25, '2026-02-11', NULL, NULL, NULL, 'credit_card', 0, 'approved', NULL, NULL, NULL, NULL, '2026-02-12 16:59:22', '2026-02-12 16:59:22');

-- --------------------------------------------------------

--
-- Estrutura para tabela `inventory_categories`
--

DROP TABLE IF EXISTS `inventory_categories`;
CREATE TABLE `inventory_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `inventory_items`
--

DROP TABLE IF EXISTS `inventory_items`;
CREATE TABLE `inventory_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `supplier_id` int(10) UNSIGNED DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `unit` varchar(50) DEFAULT 'piece' COMMENT 'piece, kg, liter, meter, box, etc.',
  `quantity` decimal(15,2) DEFAULT 0.00,
  `min_quantity` decimal(15,2) DEFAULT 0.00 COMMENT 'Low stock threshold',
  `max_quantity` decimal(15,2) DEFAULT NULL COMMENT 'Maximum stock level',
  `unit_cost` decimal(15,2) DEFAULT NULL,
  `unit_price` decimal(15,2) DEFAULT NULL COMMENT 'Selling price',
  `location` varchar(255) DEFAULT NULL COMMENT 'Warehouse location',
  `last_restocked_at` timestamp NULL DEFAULT NULL,
  `status` enum('active','discontinued','out_of_stock') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `inventory_items`
--

INSERT INTO `inventory_items` (`id`, `tenant_id`, `supplier_id`, `sku`, `barcode`, `name`, `description`, `category`, `unit`, `quantity`, `min_quantity`, `max_quantity`, `unit_cost`, `unit_price`, `location`, `last_restocked_at`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'LUM-2X4-8', NULL, '2x4 Lumber 8ft', 'Standard construction lumber', 'Lumber', 'piece', 500.00, 100.00, NULL, 4.50, NULL, 'Warehouse A-1', NULL, 'active', '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(2, 1, 1, 'LUM-2X6-10', NULL, '2x6 Lumber 10ft', 'Heavy duty lumber', 'Lumber', 'piece', 250.00, 50.00, NULL, 7.25, NULL, 'Warehouse A-1', NULL, 'active', '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(3, 1, 1, 'PLY-4X8-3/4', NULL, 'Plywood 4x8 3/4\"', 'Construction grade plywood', 'Lumber', 'sheet', 150.00, 30.00, NULL, 32.00, NULL, 'Warehouse A-2', NULL, 'active', '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(4, 1, 1, 'CON-80LB', NULL, 'Concrete Mix 80lb', 'Ready-mix concrete', 'Concrete', 'bag', 200.00, 50.00, NULL, 5.50, NULL, 'Warehouse B-1', NULL, 'active', '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(5, 1, 2, 'WIRE-12-2', NULL, 'Electrical Wire 12/2', '250ft roll Romex', 'Electrical', 'roll', 45.00, 10.00, NULL, 85.00, NULL, 'Warehouse C-1', NULL, 'active', '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(6, 1, 2, 'WIRE-14-2', NULL, 'Electrical Wire 14/2', '250ft roll Romex', 'Electrical', 'roll', 60.00, 15.00, NULL, 65.00, NULL, 'Warehouse C-1', NULL, 'active', '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(7, 1, 2, 'BRKR-20A', NULL, 'Circuit Breaker 20A', 'Single pole breaker', 'Electrical', 'piece', 100.00, 20.00, NULL, 8.50, NULL, 'Warehouse C-2', NULL, 'active', '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(8, 1, 3, 'PIPE-PVC-2', NULL, 'PVC Pipe 2\" 10ft', 'Schedule 40 PVC', 'Plumbing', 'piece', 80.00, 20.00, NULL, 12.00, NULL, 'Warehouse D-1', NULL, 'active', '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(9, 1, 3, 'PIPE-COP-1/2', NULL, 'Copper Pipe 1/2\" 10ft', 'Type L copper', 'Plumbing', 'piece', 50.00, 15.00, NULL, 28.00, NULL, 'Warehouse D-1', NULL, 'active', '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(10, 1, 1, 'DRY-4X8', NULL, 'Drywall 4x8 1/2\"', 'Standard drywall', 'Drywall', 'sheet', 300.00, 50.00, NULL, 12.50, NULL, 'Warehouse A-3', NULL, 'active', '2025-12-08 13:55:36', '2025-12-08 13:55:36');

-- --------------------------------------------------------

--
-- Estrutura para tabela `inventory_transactions`
--

DROP TABLE IF EXISTS `inventory_transactions`;
CREATE TABLE `inventory_transactions` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `project_id` int(10) UNSIGNED DEFAULT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `type` enum('purchase','sale','adjustment','transfer','return','damaged') NOT NULL,
  `quantity` decimal(15,2) NOT NULL COMMENT 'Positive for in, negative for out',
  `quantity_before` decimal(15,2) DEFAULT NULL,
  `quantity_after` decimal(15,2) DEFAULT NULL,
  `unit_cost` decimal(15,2) DEFAULT NULL,
  `total_cost` decimal(15,2) DEFAULT NULL,
  `reference` varchar(255) DEFAULT NULL COMMENT 'PO number, invoice, etc.',
  `notes` text DEFAULT NULL,
  `transaction_date` timestamp NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `invoices`
--

DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `client_id` int(10) UNSIGNED NOT NULL,
  `project_id` int(10) UNSIGNED DEFAULT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `tax_amount` decimal(15,2) DEFAULT 0.00,
  `discount_amount` decimal(15,2) DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(15,2) DEFAULT 0.00,
  `status` enum('draft','sent','viewed','partial','paid','overdue','cancelled') DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `stripe_invoice_id` varchar(255) DEFAULT NULL,
  `payment_link` varchar(255) DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `invoices`
--

INSERT INTO `invoices` (`id`, `tenant_id`, `client_id`, `project_id`, `invoice_number`, `issue_date`, `due_date`, `subtotal`, `tax_rate`, `tax_amount`, `discount_amount`, `total_amount`, `paid_amount`, `status`, `notes`, `terms`, `stripe_invoice_id`, `payment_link`, `sent_at`, `paid_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 'INV-2024-001', '2024-03-01', '2024-03-31', 250000.00, 8.25, 20625.00, 0.00, 270625.00, 270625.00, 'paid', 'Progress payment - Foundation complete', NULL, NULL, NULL, NULL, NULL, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(2, 1, 1, 1, 'INV-2024-002', '2024-05-01', '2024-05-31', 350000.00, 8.25, 28875.00, 0.00, 378875.00, 378875.00, 'paid', 'Progress payment - Steel framework', NULL, NULL, NULL, NULL, NULL, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(3, 1, 1, 1, 'INV-2024-003', '2024-07-01', '2024-07-31', 300000.00, 8.25, 24750.00, 0.00, 324750.00, 200000.00, 'partial', 'Progress payment - Electrical complete', NULL, NULL, NULL, NULL, NULL, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(4, 1, 2, 2, 'INV-2024-004', '2024-04-01', '2024-05-01', 500000.00, 8.25, 41250.00, 0.00, 541250.00, 541250.00, 'paid', 'Initial deposit', NULL, NULL, NULL, NULL, NULL, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(5, 1, 2, 2, 'INV-2024-005', '2024-07-01', '2024-08-01', 750000.00, 8.25, 61875.00, 0.00, 811875.00, 0.00, 'sent', 'Progress payment - Site work', NULL, NULL, NULL, NULL, NULL, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(6, 1, 3, 3, 'INV-2024-006', '2024-08-01', '2024-09-01', 400000.00, 0.00, 0.00, 0.00, 400000.00, 400000.00, 'paid', 'Government project - Tax exempt', NULL, NULL, NULL, NULL, NULL, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(9, 7, 5, NULL, 'INV-20251209-082925-9294', '2025-12-09', '2025-12-09', 5000.00, 0.00, 0.00, 0.00, 5000.00, 5000.00, 'paid', 'Down Payment', NULL, NULL, NULL, NULL, '2025-12-09 08:29:25', '2025-12-09 13:29:25', '2025-12-09 13:29:25'),
(11, 7, 12, 8, 'INV-20251209-175546-1531', '2025-11-17', '2025-11-17', 13731.00, 0.00, 0.00, 0.00, 13731.00, 13731.00, 'paid', 'Pagamento via quickbooks', NULL, NULL, NULL, NULL, '2025-12-09 17:55:46', '2025-12-09 22:55:46', '2025-12-09 22:55:46'),
(12, 7, 12, 8, 'INV-001532', '2025-12-09', '2026-01-09', 27762.00, 2.00, 555.24, 500.00, 27817.24, 0.00, 'sent', '', NULL, NULL, NULL, '2025-12-09 20:39:15', NULL, '2025-12-10 01:39:06', '2025-12-10 01:39:15'),
(13, 7, 18, 16, 'INV-20251210-155954-7298', '2025-12-02', '2025-12-02', 3600.00, 0.00, 0.00, 0.00, 3600.00, 3600.00, 'paid', 'Pagamento Parcial', NULL, NULL, NULL, NULL, '2025-12-10 15:59:54', '2025-12-10 20:59:54', '2025-12-10 20:59:54'),
(14, 7, 13, 26, 'INV-20251211-132250-3660', '2025-10-14', '2025-10-14', 3850.00, 0.00, 0.00, 0.00, 3850.00, 3850.00, 'paid', 'Zelle Payment', NULL, NULL, NULL, NULL, '2025-12-11 13:22:50', '2025-12-11 18:22:50', '2025-12-11 18:22:50'),
(15, 7, 13, 26, 'INV-20251211-132440-5405', '2025-11-11', '2025-11-11', 3850.00, 0.00, 0.00, 0.00, 3850.00, 3850.00, 'paid', 'Zelle Payment', NULL, NULL, NULL, NULL, '2025-12-11 13:24:40', '2025-12-11 18:24:40', '2025-12-11 18:24:40'),
(16, 7, 13, 26, 'INV-20251211-132457-1329', '2025-11-11', '2025-11-11', 1000.00, 0.00, 0.00, 0.00, 1000.00, 1000.00, 'paid', 'Zelle Payment', NULL, NULL, NULL, NULL, '2025-12-11 13:24:57', '2025-12-11 18:24:57', '2025-12-11 18:24:57'),
(24, 10, 20, 33, 'INV-20251212-095947-7161', '2025-12-12', '2025-12-12', 2500.00, 0.00, 0.00, 0.00, 2500.00, 2500.00, 'paid', 'prj 9456', NULL, NULL, NULL, NULL, '2025-12-12 09:59:47', '2025-12-12 14:59:47', '2025-12-12 14:59:47'),
(25, 10, 20, 33, 'INV-20251212-100409-3044', '2025-12-12', '2025-12-12', 3500.00, 0.00, 0.00, 0.00, 3500.00, 3500.00, 'paid', 'PRJ 4596', NULL, NULL, NULL, NULL, '2025-12-12 10:04:09', '2025-12-12 15:04:09', '2025-12-12 15:04:09'),
(26, 10, 20, 33, 'INV-20251212-141732-2964', '2025-12-10', '2025-12-10', 500.00, 0.00, 0.00, 0.00, 500.00, 500.00, 'paid', 'teste 02', NULL, NULL, NULL, NULL, '2025-12-12 14:17:32', '2025-12-12 19:17:32', '2025-12-12 19:17:32'),
(27, 7, 7, 9, 'INV-20251217-094351-2054', '2025-12-03', '2025-12-03', 7074.00, 0.00, 0.00, 0.00, 7074.00, 7074.00, 'paid', 'Down Payment - QB', NULL, NULL, NULL, NULL, '2025-12-17 09:43:51', '2025-12-17 14:43:51', '2025-12-17 14:43:51'),
(28, 7, 16, 32, 'INV-20251217-095257-8998', '2025-09-26', '2025-09-26', 2000.00, 0.00, 0.00, 0.00, 2000.00, 2000.00, 'paid', 'QB- Partial Payment', NULL, NULL, NULL, NULL, '2025-12-17 09:52:57', '2025-12-17 14:52:57', '2025-12-17 14:52:57'),
(29, 7, 16, 32, 'INV-20251217-095325-3708', '2025-09-28', '2025-09-28', 2380.40, 0.00, 0.00, 0.00, 2380.40, 2380.40, 'paid', 'QB Payment', NULL, NULL, NULL, NULL, '2025-12-17 09:53:25', '2025-12-17 14:53:25', '2025-12-17 14:53:25'),
(30, 7, 16, 32, 'INV-20251217-095349-2258', '2025-10-06', '2025-10-06', 4000.00, 0.00, 0.00, 0.00, 4000.00, 4000.00, 'paid', 'QB Payment', NULL, NULL, NULL, NULL, '2025-12-17 09:53:49', '2025-12-17 14:53:49', '2025-12-17 14:53:49'),
(31, 7, 16, 32, 'INV-20251217-095416-3272', '2025-10-06', '2025-10-06', 5000.00, 0.00, 0.00, 0.00, 5000.00, 5000.00, 'paid', 'QB Payment', NULL, NULL, NULL, NULL, '2025-12-17 09:54:16', '2025-12-17 14:54:16', '2025-12-17 14:54:16'),
(32, 7, 16, 32, 'INV-20251217-095451-9295', '2025-10-16', '2025-10-16', 1906.65, 0.00, 0.00, 0.00, 1906.65, 1906.65, 'paid', 'QB Payment', NULL, NULL, NULL, NULL, '2025-12-17 09:54:51', '2025-12-17 14:54:51', '2025-12-17 14:54:51'),
(33, 7, 16, 32, 'INV-20251217-095515-6189', '2025-10-26', '2025-10-26', 4000.00, 0.00, 0.00, 0.00, 4000.00, 4000.00, 'paid', 'QB Payment', NULL, NULL, NULL, NULL, '2025-12-17 09:55:15', '2025-12-17 14:55:15', '2025-12-17 14:55:15'),
(34, 7, 16, 32, 'INV-20251217-095547-8843', '2025-10-30', '2025-10-30', 7000.00, 0.00, 0.00, 0.00, 7000.00, 7000.00, 'paid', 'QB Payment', NULL, NULL, NULL, NULL, '2025-12-17 09:55:47', '2025-12-17 14:55:47', '2025-12-17 14:55:47'),
(35, 7, 23, 39, 'INV-20260120-065519-5639', '2026-01-03', '2026-01-03', 1200.00, 0.00, 0.00, 0.00, 1200.00, 1200.00, 'paid', 'QB Payment', NULL, NULL, NULL, NULL, '2026-01-20 06:55:19', '2026-01-20 11:55:19', '2026-01-20 11:55:19'),
(36, 7, 23, 39, 'INV-20260120-065546-6072', '2026-01-11', '2026-01-11', 774.38, 0.00, 0.00, 0.00, 774.38, 774.38, 'paid', 'QB Payment', NULL, NULL, NULL, NULL, '2026-01-20 06:55:46', '2026-01-20 11:55:46', '2026-01-20 11:55:46'),
(37, 7, 12, 8, 'INV-20260120-073854-3374', '2025-12-29', '2025-12-29', 1550.00, 0.00, 0.00, 0.00, 1550.00, 1550.00, 'paid', 'QB Payment', NULL, NULL, NULL, NULL, '2026-01-20 07:38:54', '2026-01-20 12:38:54', '2026-01-20 12:38:54'),
(38, 7, 24, 40, 'INV-20260120-082652-6109', '2026-01-14', '2026-01-14', 23215.00, 0.00, 0.00, 0.00, 23215.00, 23215.00, 'paid', 'QB Payment', NULL, NULL, NULL, NULL, '2026-01-20 08:26:52', '2026-01-20 13:26:52', '2026-01-20 13:26:52'),
(39, 7, 25, 41, 'INV-20260120-083928-6247', '2026-01-11', '2026-01-11', 9715.00, 0.00, 0.00, 0.00, 9715.00, 9715.00, 'paid', 'QB Payment', NULL, NULL, NULL, NULL, '2026-01-20 08:39:28', '2026-01-20 13:39:28', '2026-01-20 13:39:28'),
(40, 7, 25, 41, 'INV-20260210-065338-2604', '2026-02-07', '2026-02-07', 6325.00, 0.00, 0.00, 0.00, 6325.00, 6325.00, 'paid', 'QB payment', NULL, NULL, NULL, NULL, '2026-02-10 06:53:38', '2026-02-10 11:53:38', '2026-02-10 11:53:38'),
(41, 7, 18, 22, 'INV-20260211-110358-5874', '2026-01-22', '2026-01-22', 4200.00, 0.00, 0.00, 0.00, 4200.00, 4200.00, 'paid', 'Final Payment', NULL, NULL, NULL, NULL, '2026-02-11 11:03:58', '2026-02-11 16:03:58', '2026-02-11 16:03:58');

-- --------------------------------------------------------

--
-- Estrutura para tabela `invoice_items`
--

DROP TABLE IF EXISTS `invoice_items`;
CREATE TABLE `invoice_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `invoice_id` int(10) UNSIGNED NOT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(15,2) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `description`, `quantity`, `unit_price`, `amount`, `sort_order`, `created_at`) VALUES
(1, 1, 'Foundation work - labor', 1.00, 100000.00, 100000.00, 1, '2025-12-08 13:55:36'),
(2, 1, 'Foundation materials - concrete, rebar', 1.00, 120000.00, 120000.00, 2, '2025-12-08 13:55:36'),
(3, 1, 'Site preparation and excavation', 1.00, 30000.00, 30000.00, 3, '2025-12-08 13:55:36'),
(4, 2, 'Steel framework - materials', 1.00, 200000.00, 200000.00, 1, '2025-12-08 13:55:36'),
(5, 2, 'Steel framework - installation labor', 1.00, 150000.00, 150000.00, 2, '2025-12-08 13:55:36'),
(6, 3, 'Electrical rough-in - materials', 1.00, 80000.00, 80000.00, 1, '2025-12-08 13:55:36'),
(7, 3, 'Electrical rough-in - labor', 1.00, 120000.00, 120000.00, 2, '2025-12-08 13:55:36'),
(8, 3, 'Panel and breaker installation', 1.00, 100000.00, 100000.00, 3, '2025-12-08 13:55:36'),
(9, 9, 'Down Payment', 1.00, 5000.00, 5000.00, 0, '2025-12-09 13:29:25'),
(11, 11, 'Pagamento via quickbooks', 1.00, 13731.00, 13731.00, 0, '2025-12-09 22:55:46'),
(12, 12, 'Descrição', 1.00, 27762.00, 27762.00, 0, '2025-12-10 01:39:06'),
(13, 13, 'Pagamento Parcial', 1.00, 3600.00, 3600.00, 0, '2025-12-10 20:59:54'),
(14, 14, 'Zelle Payment', 1.00, 3850.00, 3850.00, 0, '2025-12-11 18:22:50'),
(15, 15, 'Zelle Payment', 1.00, 3850.00, 3850.00, 0, '2025-12-11 18:24:40'),
(16, 16, 'Zelle Payment', 1.00, 1000.00, 1000.00, 0, '2025-12-11 18:24:57'),
(24, 24, 'prj 9456', 1.00, 2500.00, 2500.00, 0, '2025-12-12 14:59:47'),
(25, 25, 'PRJ 4596', 1.00, 3500.00, 3500.00, 0, '2025-12-12 15:04:09'),
(26, 26, 'teste 02', 1.00, 500.00, 500.00, 0, '2025-12-12 19:17:32'),
(27, 27, 'Down Payment - QB', 1.00, 7074.00, 7074.00, 0, '2025-12-17 14:43:51'),
(28, 28, 'QB- Partial Payment', 1.00, 2000.00, 2000.00, 0, '2025-12-17 14:52:57'),
(29, 29, 'QB Payment', 1.00, 2380.40, 2380.40, 0, '2025-12-17 14:53:25'),
(30, 30, 'QB Payment', 1.00, 4000.00, 4000.00, 0, '2025-12-17 14:53:49'),
(31, 31, 'QB Payment', 1.00, 5000.00, 5000.00, 0, '2025-12-17 14:54:16'),
(32, 32, 'QB Payment', 1.00, 1906.65, 1906.65, 0, '2025-12-17 14:54:51'),
(33, 33, 'QB Payment', 1.00, 4000.00, 4000.00, 0, '2025-12-17 14:55:15'),
(34, 34, 'QB Payment', 1.00, 7000.00, 7000.00, 0, '2025-12-17 14:55:47'),
(35, 35, 'QB Payment', 1.00, 1200.00, 1200.00, 0, '2026-01-20 11:55:19'),
(36, 36, 'QB Payment', 1.00, 774.38, 774.38, 0, '2026-01-20 11:55:46'),
(37, 37, 'QB Payment', 1.00, 1550.00, 1550.00, 0, '2026-01-20 12:38:54'),
(38, 38, 'QB Payment', 1.00, 23215.00, 23215.00, 0, '2026-01-20 13:26:52'),
(39, 39, 'QB Payment', 1.00, 9715.00, 9715.00, 0, '2026-01-20 13:39:28'),
(40, 40, 'QB payment', 1.00, 6325.00, 6325.00, 0, '2026-02-10 11:53:38'),
(41, 41, 'Final Payment', 1.00, 4200.00, 4200.00, 0, '2026-02-11 16:03:58');

-- --------------------------------------------------------

--
-- Estrutura para tabela `journal_entries`
--

DROP TABLE IF EXISTS `journal_entries`;
CREATE TABLE `journal_entries` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `entry_date` date NOT NULL,
  `reference_number` varchar(50) NOT NULL COMMENT 'Unique Journal ID usually',
  `description` text NOT NULL,
  `status` enum('draft','posted','void') DEFAULT 'draft',
  `entity_type` varchar(50) DEFAULT NULL COMMENT 'invoice, payment, expense, etc.',
  `entity_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'ID of the related record',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `posted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `journal_entries`
--

INSERT INTO `journal_entries` (`id`, `tenant_id`, `entry_date`, `reference_number`, `description`, `status`, `entity_type`, `entity_id`, `created_by`, `posted_at`, `created_at`, `updated_at`) VALUES
(1, 1, '2025-12-08', 'JE-1765219147-2658', 'Initial System Test Investment', 'posted', 'manual', NULL, NULL, NULL, '2025-12-08 18:39:07', '2025-12-08 18:39:07'),
(2, 1, '2025-12-08', 'JE-1765219948-7612', 'Initial System Test Investment', 'posted', 'manual', NULL, NULL, NULL, '2025-12-08 18:52:28', '2025-12-08 18:52:28'),
(3, 7, '2025-12-08', 'JE-1765225006-2598', 'teste01', 'void', 'manual', NULL, 10, NULL, '2025-12-08 20:16:46', '2025-12-10 17:46:36'),
(4, 7, '2025-12-10', 'JE-1765406323-4204', 'Labor: Wanderlei Melo - Daily Rate', 'void', 'manual', NULL, 13, NULL, '2025-12-10 22:38:43', '2025-12-10 17:43:30'),
(5, 10, '2025-12-10', 'JE-1765548371-5294', 'Home Depot', 'posted', 'expense', NULL, 15, NULL, '2025-12-12 14:06:11', '2025-12-12 14:06:11'),
(6, 10, '2025-12-10', 'JE-1765548377-4195', 'Home Depot', 'posted', 'expense', NULL, 15, NULL, '2025-12-12 14:06:17', '2025-12-12 14:06:17'),
(7, 10, '2025-12-10', 'JE-1765548557-1921', 'Home Depot', 'posted', 'expense', NULL, 15, NULL, '2025-12-12 14:09:17', '2025-12-12 14:09:17'),
(8, 10, '2025-12-12', 'JE-1765551849-7069', 'Income: PRJ 4596', 'posted', 'income', NULL, 15, NULL, '2025-12-12 15:04:09', '2025-12-12 15:04:09'),
(9, 10, '2025-12-12', 'JE-1765552492-9348', 'Teste', 'posted', 'expense', NULL, 15, NULL, '2025-12-12 15:14:52', '2025-12-12 15:14:52'),
(10, 10, '2025-12-12', 'JE-1765553122-1537', 'qualquer', 'posted', 'expense', NULL, 15, NULL, '2025-12-12 15:25:22', '2025-12-12 15:25:22'),
(11, 10, '2025-12-10', 'JE-1765567052-5840', 'Income: teste 02', 'posted', 'income', NULL, 15, NULL, '2025-12-12 19:17:32', '2025-12-12 19:17:32'),
(12, 10, '2025-12-15', 'JE-1765823901-5143', 'descrção', 'posted', 'manual', NULL, 15, NULL, '2025-12-15 18:38:21', '2025-12-15 18:38:21');

-- --------------------------------------------------------

--
-- Estrutura para tabela `journal_entry_lines`
--

DROP TABLE IF EXISTS `journal_entry_lines`;
CREATE TABLE `journal_entry_lines` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `journal_entry_id` int(10) UNSIGNED NOT NULL,
  `account_id` int(10) UNSIGNED NOT NULL,
  `debit` decimal(15,2) DEFAULT 0.00,
  `credit` decimal(15,2) DEFAULT 0.00,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `journal_entry_lines`
--

INSERT INTO `journal_entry_lines` (`id`, `tenant_id`, `journal_entry_id`, `account_id`, `debit`, `credit`, `description`, `created_at`) VALUES
(1, 1, 1, 1, 1000.00, 0.00, 'Cash Injection', '2025-12-08 18:39:07'),
(2, 1, 1, 22, 0.00, 1000.00, 'Owner Investment', '2025-12-08 18:39:07'),
(3, 1, 2, 1, 1000.00, 0.00, 'Cash Injection', '2025-12-08 18:52:28'),
(4, 1, 2, 22, 0.00, 1000.00, 'Owner Investment', '2025-12-08 18:52:28'),
(7, 7, 4, 24, 500.00, 0.00, 'testes', '2025-12-10 22:38:43'),
(8, 7, 4, 24, 0.00, 50.00, 'testes', '2025-12-10 22:38:43'),
(9, 7, 4, 24, 50.00, 0.00, 'testes', '2025-12-10 22:38:43'),
(10, 7, 4, 24, 0.00, 500.00, 'testes', '2025-12-10 22:38:43'),
(11, 7, 3, 24, 0.10, 0.00, 'Testes', '2025-12-10 22:45:37'),
(12, 7, 3, 24, 0.00, 0.10, '', '2025-12-10 22:45:37'),
(13, 10, 5, 29, 800.00, 0.00, '', '2025-12-12 14:06:11'),
(14, 10, 5, 28, 0.00, 800.00, '', '2025-12-12 14:06:11'),
(15, 10, 6, 29, 800.00, 0.00, '', '2025-12-12 14:06:17'),
(16, 10, 6, 28, 0.00, 800.00, '', '2025-12-12 14:06:17'),
(17, 10, 7, 29, 800.00, 0.00, '', '2025-12-12 14:09:17'),
(18, 10, 7, 28, 0.00, 800.00, '', '2025-12-12 14:09:17'),
(19, 10, 8, 28, 3500.00, 0.00, '', '2025-12-12 15:04:09'),
(20, 10, 8, 26, 0.00, 3500.00, '', '2025-12-12 15:04:09'),
(21, 10, 9, 27, 160.00, 0.00, '', '2025-12-12 15:14:52'),
(22, 10, 9, 28, 0.00, 160.00, '', '2025-12-12 15:14:52'),
(23, 10, 10, 26, 200.00, 0.00, '', '2025-12-12 15:25:22'),
(24, 10, 10, 28, 0.00, 200.00, '', '2025-12-12 15:25:22'),
(25, 10, 11, 28, 500.00, 0.00, '', '2025-12-12 19:17:32'),
(26, 10, 11, 26, 0.00, 500.00, '', '2025-12-12 19:17:32'),
(27, 10, 12, 30, 50.00, 0.00, 'leads', '2025-12-15 18:38:21'),
(28, 10, 12, 31, 0.00, 50.00, 'leads', '2025-12-15 18:38:21');

-- --------------------------------------------------------

--
-- Estrutura para tabela `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` varchar(100) NOT NULL COMMENT 'task_assigned, invoice_paid, etc.',
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional notification data' CHECK (json_valid(`data`)),
  `link` varchar(255) DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `notifications`
--

INSERT INTO `notifications` (`id`, `tenant_id`, `user_id`, `type`, `title`, `message`, `data`, `link`, `read_at`, `created_at`) VALUES
(1, 7, 10, 'task_created', 'New Task Created', 'Task created: Tarefa 05', NULL, '/projects/7#tasks', '2025-12-09 11:18:58', '2025-12-09 11:18:31'),
(2, 7, 10, 'task_created', 'New Task Created', 'Task created: tarefa 06', NULL, '/projects/7#tasks', '2025-12-09 11:20:17', '2025-12-09 11:19:59'),
(3, 7, 10, 'task_created', 'New Task Created', 'Task created: Teste 07', NULL, '/projects/7#tasks', '2025-12-09 11:25:08', '2025-12-09 11:24:48'),
(4, 7, 10, 'task_created', 'New Task Created', 'Task created: Tarefa 01', NULL, '/projects/7#tasks', '2025-12-09 12:33:31', '2025-12-09 12:33:00'),
(5, 7, 10, 'task_created', 'New Task Created', 'Task created: Tarefa 04', NULL, '/projects/7#tasks', '2025-12-09 12:36:28', '2025-12-09 12:36:15');

-- --------------------------------------------------------

--
-- Estrutura para tabela `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `invoice_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) NOT NULL COMMENT 'stripe, bank_transfer, cash, check',
  `reference_number` varchar(255) DEFAULT NULL,
  `stripe_payment_id` varchar(255) DEFAULT NULL,
  `stripe_charge_id` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'completed',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `journal_entry_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `payments`
--

INSERT INTO `payments` (`id`, `tenant_id`, `invoice_id`, `amount`, `payment_date`, `payment_method`, `reference_number`, `stripe_payment_id`, `stripe_charge_id`, `notes`, `status`, `created_at`, `updated_at`, `journal_entry_id`) VALUES
(1, 1, 1, 270625.00, '2024-03-25', 'bank_transfer', 'ACH-28374651', NULL, NULL, NULL, 'completed', '2025-12-08 13:55:36', '2025-12-08 13:55:36', NULL),
(2, 1, 2, 378875.00, '2024-05-28', 'bank_transfer', 'ACH-29485762', NULL, NULL, NULL, 'completed', '2025-12-08 13:55:36', '2025-12-08 13:55:36', NULL),
(3, 1, 3, 200000.00, '2024-07-20', 'check', 'CHK-10452', NULL, NULL, NULL, 'completed', '2025-12-08 13:55:36', '2025-12-08 13:55:36', NULL),
(4, 1, 4, 541250.00, '2024-04-15', 'bank_transfer', 'WIRE-TP-001', NULL, NULL, NULL, 'completed', '2025-12-08 13:55:36', '2025-12-08 13:55:36', NULL),
(5, 1, 6, 400000.00, '2024-08-20', 'bank_transfer', 'COA-PARKS-2024', NULL, NULL, NULL, 'completed', '2025-12-08 13:55:36', '2025-12-08 13:55:36', NULL),
(6, 7, 9, 5000.00, '2025-12-09', 'bank_transfer', NULL, NULL, NULL, 'Down Payment', 'completed', '2025-12-09 13:29:25', '2025-12-09 13:29:25', NULL),
(8, 7, 11, 13731.00, '2025-11-17', 'bank_transfer', NULL, NULL, NULL, 'Pagamento via quickbooks', 'completed', '2025-12-09 22:55:46', '2025-12-09 22:55:46', NULL),
(9, 7, 13, 3600.00, '2025-12-02', 'check', NULL, NULL, NULL, 'Pagamento Parcial', 'completed', '2025-12-10 20:59:54', '2025-12-10 20:59:54', NULL),
(10, 7, 14, 3850.00, '2025-10-14', 'bank_transfer', NULL, NULL, NULL, 'Zelle Payment', 'completed', '2025-12-11 18:22:50', '2025-12-11 18:22:50', NULL),
(11, 7, 15, 3850.00, '2025-11-11', 'bank_transfer', NULL, NULL, NULL, 'Zelle Payment', 'completed', '2025-12-11 18:24:40', '2025-12-11 18:24:40', NULL),
(12, 7, 16, 1000.00, '2025-11-11', 'bank_transfer', NULL, NULL, NULL, 'Zelle Payment', 'completed', '2025-12-11 18:24:57', '2025-12-11 18:24:57', NULL),
(20, 10, 24, 2500.00, '2025-12-12', 'cash', NULL, NULL, NULL, 'prj 9456', 'completed', '2025-12-12 14:59:47', '2025-12-12 14:59:47', NULL),
(21, 10, 25, 3500.00, '2025-12-12', 'bank_transfer', NULL, NULL, NULL, 'PRJ 4596', 'completed', '2025-12-12 15:04:09', '2025-12-12 15:04:09', NULL),
(22, 10, 26, 500.00, '2025-12-10', 'check', NULL, NULL, NULL, 'teste 02', 'completed', '2025-12-12 19:17:32', '2025-12-12 19:17:32', NULL),
(23, 7, 27, 7074.00, '2025-12-03', 'bank_transfer', NULL, NULL, NULL, 'Down Payment - QB', 'completed', '2025-12-17 14:43:51', '2025-12-17 14:43:51', NULL),
(24, 7, 28, 2000.00, '2025-09-26', 'bank_transfer', NULL, NULL, NULL, 'QB- Partial Payment', 'completed', '2025-12-17 14:52:57', '2025-12-17 14:52:57', NULL),
(25, 7, 29, 2380.40, '2025-09-28', 'bank_transfer', NULL, NULL, NULL, 'QB Payment', 'completed', '2025-12-17 14:53:25', '2025-12-17 14:53:25', NULL),
(26, 7, 30, 4000.00, '2025-10-06', 'bank_transfer', NULL, NULL, NULL, 'QB Payment', 'completed', '2025-12-17 14:53:49', '2025-12-17 14:53:49', NULL),
(27, 7, 31, 5000.00, '2025-10-06', 'bank_transfer', NULL, NULL, NULL, 'QB Payment', 'completed', '2025-12-17 14:54:16', '2025-12-17 14:54:16', NULL),
(28, 7, 32, 1906.65, '2025-10-16', 'bank_transfer', NULL, NULL, NULL, 'QB Payment', 'completed', '2025-12-17 14:54:51', '2025-12-17 14:54:51', NULL),
(29, 7, 33, 4000.00, '2025-10-26', 'bank_transfer', NULL, NULL, NULL, 'QB Payment', 'completed', '2025-12-17 14:55:15', '2025-12-17 14:55:15', NULL),
(30, 7, 34, 7000.00, '2025-10-30', 'bank_transfer', NULL, NULL, NULL, 'QB Payment', 'completed', '2025-12-17 14:55:47', '2025-12-17 14:55:47', NULL),
(31, 7, 35, 1200.00, '2026-01-03', 'bank_transfer', NULL, NULL, NULL, 'QB Payment', 'completed', '2026-01-20 11:55:19', '2026-01-20 11:55:19', NULL),
(32, 7, 36, 774.38, '2026-01-11', 'bank_transfer', NULL, NULL, NULL, 'QB Payment', 'completed', '2026-01-20 11:55:46', '2026-01-20 11:55:46', NULL),
(33, 7, 37, 1550.00, '2025-12-29', 'bank_transfer', NULL, NULL, NULL, 'QB Payment', 'completed', '2026-01-20 12:38:54', '2026-01-20 12:38:54', NULL),
(34, 7, 38, 23215.00, '2026-01-14', 'bank_transfer', NULL, NULL, NULL, 'QB Payment', 'completed', '2026-01-20 13:26:52', '2026-01-20 13:26:52', NULL),
(35, 7, 39, 9715.00, '2026-01-11', 'bank_transfer', NULL, NULL, NULL, 'QB Payment', 'completed', '2026-01-20 13:39:28', '2026-01-20 13:39:28', NULL),
(36, 7, 40, 6325.00, '2026-02-07', 'check', NULL, NULL, NULL, 'QB payment', 'completed', '2026-02-10 11:53:38', '2026-02-10 11:53:38', NULL),
(37, 7, 41, 4200.00, '2026-01-22', 'check', NULL, NULL, NULL, 'Final Payment', 'completed', '2026-02-11 16:03:58', '2026-02-11 16:03:58', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `payroll_periods`
--

DROP TABLE IF EXISTS `payroll_periods`;
CREATE TABLE `payroll_periods` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) DEFAULT NULL COMMENT 'Period name/description',
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `payment_date` date DEFAULT NULL,
  `status` enum('draft','processing','processed','paid') DEFAULT 'draft',
  `total_gross` decimal(15,2) DEFAULT 0.00,
  `total_deductions` decimal(15,2) DEFAULT 0.00,
  `total_net` decimal(15,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `processed_by` int(10) UNSIGNED DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `payroll_records`
--

DROP TABLE IF EXISTS `payroll_records`;
CREATE TABLE `payroll_records` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `payroll_period_id` int(10) UNSIGNED NOT NULL,
  `employee_id` int(10) UNSIGNED NOT NULL,
  `regular_hours` decimal(8,2) DEFAULT 0.00,
  `overtime_hours` decimal(8,2) DEFAULT 0.00,
  `regular_pay` decimal(15,2) DEFAULT 0.00,
  `overtime_pay` decimal(15,2) DEFAULT 0.00,
  `commission_pay` decimal(15,2) DEFAULT 0.00,
  `bonus` decimal(15,2) DEFAULT 0.00,
  `gross_pay` decimal(15,2) NOT NULL,
  `tax_deduction` decimal(15,2) DEFAULT 0.00,
  `insurance_deduction` decimal(15,2) DEFAULT 0.00,
  `other_deductions` decimal(15,2) DEFAULT 0.00,
  `deductions` decimal(15,2) GENERATED ALWAYS AS (`tax_deduction` + `insurance_deduction` + `other_deductions`) STORED,
  `net_pay` decimal(15,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_reference` varchar(255) DEFAULT NULL,
  `status` enum('pending','paid','failed') DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pending_signups`
--

DROP TABLE IF EXISTS `pending_signups`;
CREATE TABLE `pending_signups` (
  `id` int(10) UNSIGNED NOT NULL,
  `checkout_session_id` varchar(100) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `plan_id` int(10) UNSIGNED NOT NULL,
  `admin_first_name` varchar(100) DEFAULT NULL,
  `admin_last_name` varchar(100) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `status` enum('pending','completed','expired') DEFAULT 'pending',
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `pending_signups`
--

INSERT INTO `pending_signups` (`id`, `checkout_session_id`, `company_name`, `email`, `plan_id`, `admin_first_name`, `admin_last_name`, `metadata`, `status`, `expires_at`, `created_at`) VALUES
(1, 'cs_test_b1Ot4EXM4P8xdY9MVATsnJzxHIM1b8amQE7ygNXEhigBPYRCUgiHcTtvTr', 'Test Construction Co', 'test@example.com', 1, 'John', 'Doe', NULL, 'completed', '2025-12-12 10:10:58', '2025-12-11 15:10:58');

-- --------------------------------------------------------

--
-- Estrutura para tabela `projects`
--

DROP TABLE IF EXISTS `projects`;
CREATE TABLE `projects` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `client_id` int(10) UNSIGNED NOT NULL,
  `manager_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Project Manager user ID',
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `code` varchar(50) DEFAULT NULL COMMENT 'Project reference code',
  `address` text DEFAULT NULL COMMENT 'Project site address',
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `actual_end_date` date DEFAULT NULL,
  `estimated_hours` decimal(10,2) DEFAULT NULL,
  `actual_hours` decimal(10,2) DEFAULT 0.00,
  `contract_value` decimal(15,2) DEFAULT NULL,
  `status` enum('planning','in_progress','on_hold','completed','cancelled') DEFAULT 'planning',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `progress` tinyint(3) UNSIGNED DEFAULT 0 COMMENT 'Percentage complete',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `projects`
--

INSERT INTO `projects` (`id`, `tenant_id`, `client_id`, `manager_id`, `name`, `description`, `code`, `address`, `city`, `state`, `zip_code`, `start_date`, `end_date`, `actual_end_date`, `estimated_hours`, `actual_hours`, `contract_value`, `status`, `priority`, `progress`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 2, 'Sunrise Office Complex', 'Three-story office building with underground parking', 'PRJ-2024-001', '1000 Business Park Dr', 'Austin', 'TX', NULL, '2024-01-15', '2024-12-31', NULL, NULL, 0.00, 2500000.00, 'in_progress', 'high', 65, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(2, 1, 2, 2, 'TechPark Data Center', 'State-of-the-art data center facility with cooling systems', 'PRJ-2024-002', '500 Server Farm Rd', 'Austin', 'TX', NULL, '2024-03-01', '2025-02-28', NULL, NULL, 0.00, 5000000.00, 'in_progress', 'urgent', 40, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(3, 1, 3, 2, 'Zilker Park Pavilion', 'Public pavilion and restroom facilities renovation', 'PRJ-2024-003', 'Zilker Park', 'Austin', 'TX', NULL, '2024-06-01', '2024-10-31', NULL, NULL, 0.00, 750000.00, 'in_progress', 'medium', 80, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(4, 1, 4, 2, 'Harbor View Residences', 'Luxury townhome development - 12 units', 'PRJ-2024-004', '888 Waterfront Way', 'Austin', 'TX', NULL, '2024-02-01', '2025-06-30', NULL, NULL, 0.00, 3200000.00, 'planning', 'medium', 15, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(5, 1, 1, 2, 'Sunrise Parking Expansion', 'Additional parking structure for existing complex', 'PRJ-2024-005', '1000 Business Park Dr', 'Austin', 'TX', NULL, '2024-09-01', '2025-03-31', NULL, NULL, 0.00, 800000.00, 'planning', 'low', 0, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(8, 7, 12, NULL, 'kitchen Remodeling', '', 'PRJ-4300', '', NULL, NULL, NULL, '2025-10-01', '2025-12-31', NULL, 0.00, 0.00, 29012.00, 'planning', 'medium', 0, '2025-12-09 22:50:50', '2026-01-20 07:14:10'),
(9, 7, 7, NULL, 'Convert Room Into Bathroom', 'Transforming a room into a bathroom', 'PRJ-3853', '610 Augusta drive', NULL, NULL, NULL, '2025-12-04', '2026-01-10', NULL, 40.00, 0.00, 14148.00, 'in_progress', 'medium', 0, '2025-12-10 14:46:07', '2025-12-11 15:21:39'),
(10, 7, 9, NULL, 'Ricardo´s house', 'Renovation', 'PRJ-2174', '', NULL, NULL, NULL, '2025-12-01', '2025-12-31', NULL, 0.00, 0.00, 4000.00, 'planning', 'medium', 0, '2025-12-10 18:30:32', '2025-12-10 13:34:45'),
(11, 7, 17, NULL, '811 Etowah River Rd Dawsonville, ', 'Restoration', 'PRJ-7730', '811 Etowah River Rd Dawsonville, GA  30534', NULL, NULL, NULL, '2025-10-01', '2025-12-31', NULL, 0.00, 0.00, 0.00, 'in_progress', 'medium', 0, '2025-12-10 18:36:08', '2025-12-11 15:14:28'),
(12, 7, 17, NULL, '2665 Blackstock Dr Cumming,', 'Restoration', 'PRJ-3716', '2665 Blackstock Dr Cumming, GA  30041', NULL, NULL, NULL, '2025-11-01', '2025-12-31', NULL, 0.00, 0.00, 0.00, 'planning', 'medium', 0, '2025-12-10 18:39:13', '2025-12-10 18:39:13'),
(13, 7, 17, NULL, '3711 River Mansion Dr', 'Restoration', 'PRJ-7616', '3711 River Mansion Dr', NULL, NULL, NULL, '2025-12-01', '2025-12-31', NULL, 0.00, 0.00, 0.00, 'planning', 'medium', 0, '2025-12-10 18:40:07', '2025-12-10 18:40:07'),
(14, 7, 10, NULL, 'All Remodeling - Office', 'Office renovation', 'PRJ-8997', '15 Hamby Dr SE, Marietta, GA 30067', NULL, NULL, NULL, '2025-12-01', '2025-12-31', NULL, 0.00, 0.00, 0.00, 'planning', 'medium', 0, '2025-12-10 18:42:15', '2025-12-10 18:42:15'),
(15, 7, 17, NULL, '4346 Moonlight Walk, Lilburn', 'Restoration', 'PRJ-7590', '4346 Moonlight Walk, Lilburn', NULL, NULL, NULL, '2025-11-01', '2025-12-31', NULL, 0.00, 0.00, 0.00, 'planning', 'medium', 0, '2025-12-10 18:43:03', '2025-12-10 18:43:03'),
(16, 7, 18, NULL, 'Kitchen Renovation', 'Main floor Kitchen Remodeling', 'PRJ-3065', '3960 Merriweather woods Alpharetta, Georgia  30022', NULL, NULL, NULL, '2025-11-01', '2025-12-31', NULL, 0.00, 0.00, 7564.00, 'planning', 'medium', 0, '2025-12-10 18:46:45', '2025-12-10 13:47:04'),
(17, 7, 11, NULL, 'Georgia´s house', 'Renovation', 'PRJ-6987', '4815 Missy Way, Powder Springs', NULL, NULL, NULL, '2025-10-01', '2025-12-31', NULL, 0.00, 0.00, 0.00, 'in_progress', 'medium', 0, '2025-12-10 18:54:00', '2025-12-11 15:19:29'),
(18, 7, 17, NULL, '390 Tidwell Rd Alphareta ', 'Restoration', 'PRJ-3081', '390 Tidwell Rd Alphareta ', NULL, NULL, NULL, '2025-11-01', '2026-01-07', NULL, 0.00, 0.00, 0.00, 'planning', 'medium', 0, '2025-12-10 18:56:39', '2025-12-10 18:56:39'),
(19, 7, 17, NULL, '20 Makawee Trail Blue Ridge,', 'Restoration', 'PRJ-6503', '20 Makawee Trail Blue Ridge, GA  30513', NULL, NULL, NULL, '2025-11-01', '2025-12-31', NULL, 0.00, 0.00, 0.00, 'planning', 'medium', 0, '2025-12-10 18:57:19', '2025-12-10 18:57:19'),
(20, 7, 17, NULL, '859 Timberwalk Dr Ellijay, ', 'Restoration', 'PRJ-8632', '859 Timberwalk Dr Ellijay, GA  30540', NULL, NULL, NULL, '2025-11-01', '2025-12-30', NULL, 0.00, 0.00, 0.00, 'planning', 'medium', 0, '2025-12-10 18:57:58', '2025-12-10 18:57:58'),
(21, 7, 18, NULL, 'Gym/Game Room', 'Gym/Game Room', 'PRJ-4035', '3960 Merriweather Woods, Alpharetta, GA 30022', NULL, NULL, NULL, '2025-11-01', '2025-12-31', NULL, 0.00, 0.00, 12500.00, 'planning', 'medium', 0, '2025-12-10 18:59:52', '2025-12-10 14:00:02'),
(22, 7, 18, NULL, 'Kitchen Electrical Work', 'Kitchen Electrical Work', 'PRJ-6293', '3960 Merriweather Woods, Alpharetta', NULL, NULL, NULL, '2025-12-01', '2025-12-31', NULL, 0.00, 0.00, 4200.00, 'completed', 'medium', 0, '2025-12-10 19:01:56', '2026-02-11 11:03:58'),
(23, 7, 6, NULL, 'Del Evans´s Home', 'Renovation', 'PRJ-8522', '4611 Hickory Run Ct NW, Acworth,', NULL, NULL, NULL, '2025-10-01', '2025-11-01', NULL, 0.00, 0.00, 2200.00, 'completed', 'medium', 0, '2025-12-10 19:04:18', '2025-12-11 15:03:01'),
(24, 7, 17, NULL, 'Restoration 1 - Office', 'renovation', 'PRJ-6695', '', NULL, NULL, NULL, '2025-11-01', '2025-12-31', NULL, 0.00, 0.00, 0.00, 'planning', 'medium', 0, '2025-12-10 19:41:49', '2025-12-10 14:42:06'),
(25, 7, 14, NULL, 'Front Patio', 'Patio', 'PRJ-2460', '157 Berkeley Way , Hampton', NULL, NULL, NULL, '2025-10-01', '2025-12-30', NULL, 0.00, 0.00, 6490.00, 'in_progress', 'medium', 0, '2025-12-10 19:43:29', '2025-12-11 15:03:23'),
(26, 7, 13, NULL, 'Kitchen New Design', 'Kitchen Remodeling', 'PRJ-6473', '3250 Wildflower Rd, Rex', NULL, NULL, NULL, '2025-10-01', '2025-12-31', NULL, 0.00, 0.00, 8700.00, 'completed', 'medium', 0, '2025-12-10 19:45:40', '2025-12-11 15:02:26'),
(27, 7, 17, NULL, 'Templo', 'restoration', 'PRJ-2124', '', NULL, NULL, NULL, '2025-10-01', '2025-12-31', NULL, 0.00, 0.00, 0.00, 'planning', 'medium', 0, '2025-12-10 19:47:13', '2025-12-10 19:47:13'),
(28, 7, 17, NULL, '5615 Linden Dr, Gainesville', 'Restoration', 'PRJ-3127', '5615 Linden Dr, Gainesville', NULL, NULL, NULL, '2025-11-01', '2025-12-30', NULL, 0.00, 0.00, 0.00, 'planning', 'medium', 0, '2025-12-10 19:48:25', '2025-12-10 19:48:25'),
(29, 7, 8, NULL, 'Gentil´s Place', 'renovation', 'PRJ-2061', '', NULL, NULL, NULL, '2025-11-01', '2025-12-31', NULL, 0.00, 0.00, 0.00, 'planning', 'medium', 0, '2025-12-10 19:49:46', '2025-12-10 19:49:46'),
(30, 7, 19, NULL, 'Walter´s house', 'eplace water heater', 'PRJ-6617', '3190 Brookfield Dr Austell, GA  30106 United States - ', NULL, NULL, NULL, '2025-12-10', '2025-12-31', NULL, 0.00, 0.00, 0.00, 'planning', 'medium', 0, '2025-12-10 19:51:28', '2025-12-10 19:51:28'),
(31, 7, 10, NULL, 'Despesas Gerais ', '', 'PRJ-5266', '4040 Ebenezer Dr', NULL, NULL, NULL, '2025-10-01', '2025-12-31', NULL, 0.00, 0.00, 0.00, 'completed', 'medium', 0, '2025-12-10 19:59:04', '2026-01-20 08:51:46'),
(32, 7, 16, NULL, 'Jane´s House', 'Renovation', 'PRJ-1385', '3230 Plymouth Rock Dr Douglasville  30135 USA', NULL, NULL, NULL, '2025-09-01', '2025-12-31', NULL, 0.00, 0.00, 22900.00, 'completed', 'medium', 0, '2025-12-10 20:07:14', '2025-12-17 09:55:47'),
(33, 10, 20, NULL, 'Door Frame', '', 'PRJ-9141', '221 Suburban DR NW, Kennesaw - GA 30144', NULL, NULL, NULL, '2025-12-11', '2025-12-18', NULL, 160.00, 0.00, 0.00, 'planning', 'low', 0, '2025-12-11 15:28:30', '2025-12-11 15:28:30'),
(34, 7, 17, NULL, '5254 Mackenzie Ct Douglasville- Megan House', 'Instalar shower door glass', 'PRJ-2535', '5254 Mackenzie Ct Douglasville, GA  30135', NULL, NULL, NULL, '2025-12-10', '2025-12-31', NULL, 0.00, 0.00, 0.00, 'in_progress', 'medium', 0, '2025-12-11 20:08:18', '2026-01-20 08:48:56'),
(35, 7, 17, NULL, '1190 Springs, Canton', 'Repair LVP', 'PRJ-2793', '1190 Springs, Canton', NULL, NULL, NULL, '2025-12-10', '2025-12-31', NULL, 0.00, 0.00, 0.00, 'in_progress', 'medium', 0, '2025-12-11 20:15:19', '2025-12-11 15:15:24'),
(36, 7, 9, NULL, 'Basement Ricardo', 'Basement casa onde Ricardo mora', 'PRJ-1661', '', NULL, NULL, NULL, '2025-12-11', '2025-12-31', NULL, 0.00, 0.00, 4200.00, 'in_progress', 'medium', 0, '2025-12-15 22:13:47', '2025-12-15 17:30:04'),
(37, 7, 21, NULL, 'Ricardo Ismael´s house', '', '', '', NULL, NULL, NULL, '2025-12-12', '2025-12-31', NULL, 0.00, 0.00, 0.00, 'completed', 'medium', 0, '2025-12-16 16:42:43', '2025-12-17 09:36:01'),
(38, 7, 22, NULL, 'Laundry Room', '', 'PRJ-8484', '', NULL, NULL, NULL, '2025-12-15', '2025-12-31', NULL, 0.00, 0.00, 1750.00, 'in_progress', 'medium', 0, '2025-12-18 17:40:22', '2025-12-18 12:54:52'),
(39, 7, 23, NULL, 'Basement ', '', 'PRJ-7884', '1726 Fairburn Rd SW', NULL, NULL, NULL, '2025-12-18', '2026-01-31', NULL, 0.00, 0.00, 1974.38, 'in_progress', 'medium', 0, '2026-01-20 11:53:25', '2026-01-20 06:59:12'),
(40, 7, 24, NULL, 'Kitchen and Bathroom Remodeling', '', 'PRJ-3857', '17 Rockland Pl, Decatur, GA 30030', NULL, NULL, NULL, '2026-01-14', '2026-02-28', NULL, 0.00, 0.00, 46430.00, 'in_progress', 'medium', 0, '2026-01-20 13:25:11', '2026-01-20 08:25:45'),
(41, 7, 25, NULL, 'Kitchen Renovation', '', 'PRJ-4862', '1492 Boone Rd, Newnan, GA 30263', NULL, NULL, NULL, '2026-01-12', '2026-03-31', NULL, 0.00, 0.00, 32480.00, 'in_progress', 'medium', 0, '2026-01-20 13:38:26', '2026-02-10 06:52:31'),
(42, 7, 17, NULL, '506 Neese Rd Woodstock', '', 'PRJ-8286', '506 Neese Rd Woodstock', NULL, NULL, NULL, '2026-01-16', '2026-01-30', NULL, 0.00, 0.00, 0.00, 'in_progress', 'medium', 0, '2026-01-20 13:44:58', '2026-01-20 08:45:04'),
(43, 7, 10, NULL, 'Despesas Gerais - Jan-26', '', 'PRJ-9890', '', NULL, NULL, NULL, '2026-01-01', '2026-01-31', NULL, 0.00, 0.00, 0.00, 'completed', 'medium', 0, '2026-01-20 13:52:39', '2026-02-10 06:31:35'),
(44, 7, 26, NULL, 'Tozzo´s House', '', 'PRJ-2193', '', NULL, NULL, NULL, '2026-01-01', '2026-02-28', NULL, 0.00, 0.00, 0.00, 'planning', 'medium', 0, '2026-01-26 18:30:51', '2026-01-26 18:30:51'),
(45, 7, 10, NULL, 'Despesas gerais - Fevereiro', '', 'PRJ-6779', '', NULL, NULL, NULL, '2026-02-01', '2026-02-28', NULL, 0.00, 0.00, 0.00, 'in_progress', 'medium', 0, '2026-02-10 11:32:19', '2026-02-10 06:32:34');

-- --------------------------------------------------------

--
-- Estrutura para tabela `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
CREATE TABLE `purchase_orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `vendor_id` int(10) UNSIGNED DEFAULT NULL,
  `project_id` int(10) UNSIGNED DEFAULT NULL,
  `po_number` varchar(50) NOT NULL,
  `order_date` date NOT NULL,
  `expected_date` date DEFAULT NULL,
  `received_date` date DEFAULT NULL,
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `tax_amount` decimal(12,2) DEFAULT 0.00,
  `shipping_amount` decimal(12,2) DEFAULT 0.00,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `status` enum('draft','sent','partial','received','cancelled') DEFAULT 'draft',
  `shipping_address` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `purchase_order_items`
--

DROP TABLE IF EXISTS `purchase_order_items`;
CREATE TABLE `purchase_order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `purchase_order_id` int(10) UNSIGNED NOT NULL,
  `inventory_item_id` int(10) UNSIGNED DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `quantity_ordered` decimal(10,2) DEFAULT 1.00,
  `quantity_received` decimal(10,2) DEFAULT 0.00,
  `unit` varchar(50) DEFAULT 'unit',
  `unit_price` decimal(12,2) DEFAULT 0.00,
  `total` decimal(12,2) DEFAULT 0.00,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `remote_sessions`
--

DROP TABLE IF EXISTS `remote_sessions`;
CREATE TABLE `remote_sessions` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `ticket_id` int(10) UNSIGNED NOT NULL,
  `developer_id` int(10) UNSIGNED NOT NULL,
  `anydesk_id` varchar(20) NOT NULL,
  `session_start` datetime NOT NULL,
  `session_end` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of permission strings' CHECK (json_valid(`permissions`)),
  `is_system` tinyint(1) DEFAULT 0 COMMENT 'System roles cannot be deleted',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `roles`
--

INSERT INTO `roles` (`id`, `tenant_id`, `name`, `display_name`, `permissions`, `is_system`, `created_at`, `updated_at`) VALUES
(1, NULL, 'admin', 'Administrator', '[\"*\"]', 1, '2025-12-08 13:55:24', '2025-12-08 13:55:24'),
(2, NULL, 'project_manager', 'Project Manager', '[\"projects.*\", \"clients.*\", \"tasks.*\", \"time_logs.*\", \"budgets.*\", \"expenses.view\", \"invoices.view\", \"documents.*\", \"reports.view\"]', 1, '2025-12-08 13:55:24', '2025-12-08 13:55:24'),
(3, NULL, 'accountant', 'Accountant', '[\"invoices.*\", \"payments.*\", \"expenses.*\", \"budgets.*\", \"payroll.*\", \"reports.*\", \"clients.view\", \"projects.view\"]', 1, '2025-12-08 13:55:24', '2025-12-08 13:55:24'),
(4, NULL, 'contractor', 'Contractor', '[\"projects.view\", \"tasks.*\", \"time_logs.own\", \"documents.view\", \"inventory.view\"]', 1, '2025-12-08 13:55:24', '2025-12-08 13:55:24'),
(5, NULL, 'worker', 'Worker', '[\"tasks.view\", \"time_logs.own\"]', 1, '2025-12-08 13:55:24', '2025-12-08 13:55:24'),
(6, NULL, 'admin', 'Administrator', '[\"*\"]', 1, '2025-12-08 18:28:38', '2025-12-08 18:28:38'),
(7, NULL, 'project_manager', 'Project Manager', '[\"projects.*\", \"tasks.*\", \"time_logs.*\", \"documents.*\", \"reports.view\"]', 1, '2025-12-08 18:28:38', '2025-12-08 18:28:38'),
(8, NULL, 'contractor', 'Contractor', '[\"projects.view\", \"tasks.*\", \"time_logs.own\", \"documents.view\"]', 1, '2025-12-08 18:28:38', '2025-12-08 18:28:38'),
(9, NULL, 'worker', 'Worker', '[\"tasks.view\", \"time_logs.own\"]', 1, '2025-12-08 18:28:38', '2025-12-08 18:28:38'),
(10, 7, 'admin', 'Full Control', '[\"projects.view\",\"projects.create\",\"projects.edit\",\"projects.delete\",\"clients.view\",\"clients.create\",\"clients.edit\",\"clients.delete\",\"employees.view\",\"employees.create\",\"employees.edit\",\"employees.delete\",\"expenses.view\",\"expenses.create\",\"expenses.edit\",\"expenses.delete\",\"expenses.approve\",\"invoices.view\",\"invoices.create\",\"invoices.edit\",\"invoices.delete\",\"invoices.send\",\"reports.view\",\"reports.export\",\"settings.view\",\"settings.edit\",\"roles.manage\",\"users.manage\"]', 0, '2025-12-09 22:07:17', '2025-12-09 22:07:17'),
(11, 7, 'Editor', 'Medium Control', '[\"projects.view\",\"projects.create\",\"projects.edit\",\"clients.view\",\"clients.create\",\"clients.edit\",\"employees.view\",\"employees.create\",\"employees.edit\",\"expenses.view\",\"expenses.create\",\"expenses.edit\",\"expenses.approve\",\"invoices.view\",\"invoices.create\",\"invoices.edit\",\"invoices.send\",\"reports.view\",\"reports.export\",\"settings.view\"]', 0, '2025-12-10 02:39:22', '2025-12-10 02:39:22'),
(12, 7, 'Users', '', '[\"projects.view\",\"clients.view\",\"employees.view\",\"expenses.view\",\"invoices.view\",\"reports.view\"]', 0, '2025-12-10 02:40:17', '2025-12-10 02:40:17'),
(13, 10, 'admin', 'Administrator', '[\"*\"]', 1, '2025-12-11 15:11:52', '2025-12-11 15:11:52'),
(14, 10, 'project_manager', 'Project Manager', '[\"projects.*\", \"clients.*\", \"tasks.*\", \"time_logs.*\", \"budgets.*\", \"expenses.view\", \"invoices.view\", \"documents.*\", \"reports.view\"]', 1, '2025-12-11 15:11:52', '2025-12-11 15:11:52'),
(15, 10, 'accountant', 'Accountant', '[\"invoices.*\", \"payments.*\", \"expenses.*\", \"budgets.*\", \"payroll.*\", \"reports.*\", \"clients.view\", \"projects.view\"]', 1, '2025-12-11 15:11:52', '2025-12-11 15:11:52'),
(16, 10, 'contractor', 'Contractor', '[\"projects.view\", \"tasks.*\", \"time_logs.own\", \"documents.view\", \"inventory.view\"]', 1, '2025-12-11 15:11:52', '2025-12-11 15:11:52'),
(17, 10, 'worker', 'Worker', '[\"tasks.view\", \"time_logs.own\"]', 1, '2025-12-11 15:11:52', '2025-12-11 15:11:52');

-- --------------------------------------------------------

--
-- Estrutura para tabela `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `type` varchar(50) DEFAULT 'string' COMMENT 'string, json, boolean, number',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `settings`
--

INSERT INTO `settings` (`id`, `tenant_id`, `key`, `value`, `type`, `created_at`, `updated_at`) VALUES
(1, 1, 'company_name', 'Acme Construction Co.', 'string', '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(2, 1, 'company_address', '123 Builder Lane, Austin, TX 78701', 'string', '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(3, 1, 'company_phone', '(555) 123-4567', 'string', '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(4, 1, 'company_email', 'info@acmeconstruction.com', 'string', '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(5, 1, 'timezone', 'America/Chicago', 'string', '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(6, 1, 'date_format', 'MM/DD/YYYY', 'string', '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(7, 1, 'currency', 'USD', 'string', '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(8, 1, 'tax_rate', '8.25', 'number', '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(9, 1, 'invoice_prefix', 'INV-', 'string', '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(10, 1, 'invoice_footer', 'Thank you for your business! Payment is due within 30 days.', 'string', '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(11, 7, 'language', 'en', 'string', '2025-12-08 14:40:58', '2025-12-08 14:40:58'),
(12, 7, 'theme', 'light', 'string', '2025-12-08 14:40:58', '2025-12-08 14:40:58'),
(13, 7, 'timezone', 'America/New_York', 'string', '2025-12-08 14:40:58', '2025-12-08 14:40:58'),
(14, 7, 'date_format', 'Y-m-d', 'string', '2025-12-08 14:40:58', '2025-12-08 14:40:58'),
(15, 7, 'currency', 'USD', 'string', '2025-12-08 14:40:58', '2025-12-08 14:40:58'),
(16, 8, 'language', 'en', 'string', '2025-12-08 14:52:45', '2025-12-08 14:52:45'),
(17, 8, 'theme', 'light', 'string', '2025-12-08 14:52:45', '2025-12-08 14:52:45'),
(18, 8, 'timezone', 'America/New_York', 'string', '2025-12-08 14:52:45', '2025-12-08 14:52:45'),
(19, 8, 'date_format', 'Y-m-d', 'string', '2025-12-08 14:52:45', '2025-12-08 14:52:45'),
(20, 8, 'currency', 'USD', 'string', '2025-12-08 14:52:45', '2025-12-08 14:52:45'),
(21, 9, 'language', 'en', 'string', '2025-12-08 15:25:34', '2025-12-08 15:25:34'),
(22, 9, 'theme', 'light', 'string', '2025-12-08 15:25:34', '2025-12-08 15:25:34'),
(23, 9, 'timezone', 'America/New_York', 'string', '2025-12-08 15:25:34', '2025-12-08 15:25:34'),
(24, 9, 'date_format', 'Y-m-d', 'string', '2025-12-08 15:25:34', '2025-12-08 15:25:34'),
(25, 9, 'currency', 'USD', 'string', '2025-12-08 15:25:34', '2025-12-08 15:25:34'),
(26, 10, 'company_name', 'Test Construction Co', 'string', '2025-12-11 15:11:52', '2025-12-11 15:11:52'),
(27, 10, 'currency', 'USD', 'string', '2025-12-11 15:11:52', '2025-12-11 15:11:52'),
(28, 10, 'timezone', 'America/New_York', 'string', '2025-12-11 15:11:52', '2025-12-11 15:11:52'),
(29, 10, 'date_format', 'm/d/Y', 'string', '2025-12-11 15:11:52', '2025-12-11 15:11:52'),
(30, 10, 'fiscal_year_start', '01-01', 'string', '2025-12-11 15:11:52', '2025-12-11 15:11:52');

-- --------------------------------------------------------

--
-- Estrutura para tabela `stripe_connections`
--

DROP TABLE IF EXISTS `stripe_connections`;
CREATE TABLE `stripe_connections` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `connection_type` enum('connect','manual') NOT NULL DEFAULT 'connect',
  `stripe_account_id` varchar(100) DEFAULT NULL COMMENT 'acct_xxx for Connect',
  `stripe_user_id` varchar(100) DEFAULT NULL,
  `access_token` text DEFAULT NULL COMMENT 'OAuth access token (encrypted)',
  `refresh_token` text DEFAULT NULL COMMENT 'OAuth refresh token (encrypted)',
  `publishable_key` varchar(255) DEFAULT NULL COMMENT 'Manual mode',
  `secret_key` text DEFAULT NULL COMMENT 'Manual mode (encrypted)',
  `webhook_secret` text DEFAULT NULL COMMENT 'Webhook signing secret (encrypted)',
  `livemode` tinyint(1) DEFAULT 0,
  `charges_enabled` tinyint(1) DEFAULT 0,
  `payouts_enabled` tinyint(1) DEFAULT 0,
  `details_submitted` tinyint(1) DEFAULT 0,
  `business_name` varchar(255) DEFAULT NULL,
  `connected_at` datetime DEFAULT NULL,
  `last_sync_at` datetime DEFAULT NULL,
  `status` enum('pending','active','disconnected','error') DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `stripe_customers`
--

DROP TABLE IF EXISTS `stripe_customers`;
CREATE TABLE `stripe_customers` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `client_id` int(10) UNSIGNED NOT NULL,
  `stripe_customer_id` varchar(100) NOT NULL COMMENT 'cus_xxx',
  `email` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `default_payment_method` varchar(100) DEFAULT NULL COMMENT 'pm_xxx',
  `balance` int(11) DEFAULT 0 COMMENT 'Balance in cents',
  `currency` varchar(3) DEFAULT 'usd',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `synced_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `stripe_payment_intents`
--

DROP TABLE IF EXISTS `stripe_payment_intents`;
CREATE TABLE `stripe_payment_intents` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `stripe_payment_intent_id` varchar(100) NOT NULL COMMENT 'pi_xxx',
  `stripe_customer_id` varchar(100) DEFAULT NULL,
  `invoice_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Local invoice ID',
  `amount` int(11) NOT NULL COMMENT 'Amount in cents',
  `currency` varchar(3) DEFAULT 'usd',
  `status` varchar(50) NOT NULL,
  `payment_method` varchar(100) DEFAULT NULL,
  `client_secret` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `last_payment_error` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`last_payment_error`)),
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `stripe_prices`
--

DROP TABLE IF EXISTS `stripe_prices`;
CREATE TABLE `stripe_prices` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `stripe_price_id` varchar(100) NOT NULL COMMENT 'price_xxx',
  `stripe_product_id` varchar(100) NOT NULL,
  `unit_amount` int(11) NOT NULL COMMENT 'Amount in cents',
  `currency` varchar(3) DEFAULT 'usd',
  `recurring_interval` enum('day','week','month','year') DEFAULT NULL,
  `recurring_interval_count` int(11) DEFAULT NULL,
  `type` enum('one_time','recurring') DEFAULT 'one_time',
  `active` tinyint(1) DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `synced_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `stripe_products`
--

DROP TABLE IF EXISTS `stripe_products`;
CREATE TABLE `stripe_products` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `stripe_product_id` varchar(100) NOT NULL COMMENT 'prod_xxx',
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `synced_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `stripe_subscriptions`
--

DROP TABLE IF EXISTS `stripe_subscriptions`;
CREATE TABLE `stripe_subscriptions` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `stripe_subscription_id` varchar(100) NOT NULL COMMENT 'sub_xxx',
  `stripe_customer_id` varchar(100) NOT NULL,
  `client_id` int(10) UNSIGNED DEFAULT NULL,
  `status` varchar(50) NOT NULL,
  `current_period_start` datetime DEFAULT NULL,
  `current_period_end` datetime DEFAULT NULL,
  `cancel_at_period_end` tinyint(1) DEFAULT 0,
  `canceled_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  `trial_start` datetime DEFAULT NULL,
  `trial_end` datetime DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `synced_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `stripe_webhook_events`
--

DROP TABLE IF EXISTS `stripe_webhook_events`;
CREATE TABLE `stripe_webhook_events` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED DEFAULT NULL,
  `stripe_event_id` varchar(100) NOT NULL COMMENT 'evt_xxx',
  `event_type` varchar(100) NOT NULL,
  `stripe_account_id` varchar(100) DEFAULT NULL COMMENT 'For Connect events',
  `api_version` varchar(20) DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `processed` tinyint(1) DEFAULT 0,
  `processing_error` text DEFAULT NULL,
  `retry_count` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `processed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `stripe_webhook_events`
--

INSERT INTO `stripe_webhook_events` (`id`, `tenant_id`, `stripe_event_id`, `event_type`, `stripe_account_id`, `api_version`, `payload`, `processed`, `processing_error`, `retry_count`, `created_at`, `processed_at`) VALUES
(1, NULL, 'evt_1SdBX1K5swNIbm5cYcGqs9pR', 'checkout.session.completed', NULL, '2025-11-17.clover', '{\"id\":\"evt_1SdBX1K5swNIbm5cYcGqs9pR\",\"object\":\"event\",\"api_version\":\"2025-11-17.clover\",\"created\":1765465911,\"data\":{\"object\":{\"id\":\"cs_test_b1Ot4EXM4P8xdY9MVATsnJzxHIM1b8amQE7ygNXEhigBPYRCUgiHcTtvTr\",\"object\":\"checkout.session\",\"adaptive_pricing\":{\"enabled\":false},\"after_expiration\":null,\"allow_promotion_codes\":true,\"amount_subtotal\":0,\"amount_total\":0,\"automatic_tax\":{\"enabled\":false,\"liability\":null,\"provider\":null,\"status\":null},\"billing_address_collection\":null,\"branding_settings\":{\"background_color\":\"#ffffff\",\"border_style\":\"rounded\",\"button_color\":\"#0074d4\",\"display_name\":\"Traffio-SandBox\",\"font_family\":\"default\",\"icon\":null,\"logo\":null},\"cancel_url\":\"https:\\/\\/buildflow-traffio.com\\/?checkout=cancelled\",\"client_reference_id\":null,\"client_secret\":null,\"collected_information\":{\"business_name\":null,\"individual_name\":null,\"shipping_details\":null},\"consent\":null,\"consent_collection\":null,\"created\":1765465858,\"currency\":\"usd\",\"currency_conversion\":null,\"custom_fields\":[],\"custom_text\":{\"after_submit\":null,\"shipping_address\":null,\"submit\":null,\"terms_of_service_acceptance\":null},\"customer\":\"cus_TaMFqJirGasJ6N\",\"customer_account\":null,\"customer_creation\":\"always\",\"customer_details\":{\"address\":{\"city\":null,\"country\":\"US\",\"line1\":null,\"line2\":null,\"postal_code\":\"30144\",\"state\":null},\"business_name\":null,\"email\":\"test@example.com\",\"individual_name\":null,\"name\":\"John Doe\",\"phone\":null,\"tax_exempt\":\"none\",\"tax_ids\":[]},\"customer_email\":\"test@example.com\",\"discounts\":[],\"expires_at\":1765552257,\"invoice\":\"in_1SdBWyK5swNIbm5c5H5rvMyq\",\"invoice_creation\":null,\"livemode\":false,\"locale\":null,\"metadata\":{\"plan_id\":\"1\",\"admin_first_name\":\"John\",\"company_name\":\"Test Construction Co\",\"admin_last_name\":\"Doe\"},\"mode\":\"subscription\",\"origin_context\":null,\"payment_intent\":null,\"payment_link\":null,\"payment_method_collection\":\"always\",\"payment_method_configuration_details\":null,\"payment_method_options\":{\"card\":{\"request_three_d_secure\":\"automatic\"}},\"payment_method_types\":[\"card\"],\"payment_status\":\"paid\",\"permissions\":null,\"phone_number_collection\":{\"enabled\":false},\"recovered_from\":null,\"saved_payment_method_options\":{\"allow_redisplay_filters\":[\"always\"],\"payment_method_remove\":\"disabled\",\"payment_method_save\":null},\"setup_intent\":null,\"shipping_address_collection\":null,\"shipping_cost\":null,\"shipping_options\":[],\"status\":\"complete\",\"submit_type\":null,\"subscription\":\"sub_1SdBWyK5swNIbm5ceeYU3b0z\",\"success_url\":\"https:\\/\\/buildflow-traffio.com\\/checkout\\/success?session_id={CHECKOUT_SESSION_ID}\",\"total_details\":{\"amount_discount\":0,\"amount_shipping\":0,\"amount_tax\":0},\"ui_mode\":\"hosted\",\"url\":null,\"wallet_options\":null}},\"livemode\":false,\"pending_webhooks\":1,\"request\":{\"id\":null,\"idempotency_key\":null},\"type\":\"checkout.session.completed\"}', 1, NULL, 0, '2025-12-11 15:11:51', '2025-12-11 10:11:52'),
(2, NULL, 'evt_1SdBX2K5swNIbm5cojJXOuZi', 'invoice.paid', NULL, '2025-11-17.clover', '{\"id\":\"evt_1SdBX2K5swNIbm5cojJXOuZi\",\"object\":\"event\",\"api_version\":\"2025-11-17.clover\",\"created\":1765465911,\"data\":{\"object\":{\"id\":\"in_1SdBWyK5swNIbm5c5H5rvMyq\",\"object\":\"invoice\",\"account_country\":\"US\",\"account_name\":\"Traffio-SandBox\",\"account_tax_ids\":null,\"amount_due\":0,\"amount_overpaid\":0,\"amount_paid\":0,\"amount_remaining\":0,\"amount_shipping\":0,\"application\":null,\"attempt_count\":0,\"attempted\":true,\"auto_advance\":false,\"automatic_tax\":{\"disabled_reason\":null,\"enabled\":false,\"liability\":null,\"provider\":null,\"status\":null},\"automatically_finalizes_at\":null,\"billing_reason\":\"subscription_create\",\"collection_method\":\"charge_automatically\",\"created\":1765465908,\"currency\":\"usd\",\"custom_fields\":null,\"customer\":\"cus_TaMFqJirGasJ6N\",\"customer_account\":null,\"customer_address\":{\"city\":null,\"country\":\"US\",\"line1\":null,\"line2\":null,\"postal_code\":\"30144\",\"state\":null},\"customer_email\":\"test@example.com\",\"customer_name\":\"John Doe\",\"customer_phone\":null,\"customer_shipping\":null,\"customer_tax_exempt\":\"none\",\"customer_tax_ids\":[],\"default_payment_method\":null,\"default_source\":null,\"default_tax_rates\":[],\"description\":null,\"discounts\":[],\"due_date\":null,\"effective_at\":1765465908,\"ending_balance\":0,\"footer\":null,\"from_invoice\":null,\"hosted_invoice_url\":\"https:\\/\\/invoice.stripe.com\\/i\\/acct_1SdAPSK5swNIbm5c\\/test_YWNjdF8xU2RBUFNLNXN3TklibTVjLF9UYU1GTmlCc0s3YzhYY1NuRmJoT0kyeUZiWThNRnlOLDE1NjAwNjcxMg0200SGmcuhys?s=ap\",\"invoice_pdf\":\"https:\\/\\/pay.stripe.com\\/invoice\\/acct_1SdAPSK5swNIbm5c\\/test_YWNjdF8xU2RBUFNLNXN3TklibTVjLF9UYU1GTmlCc0s3YzhYY1NuRmJoT0kyeUZiWThNRnlOLDE1NjAwNjcxMg0200SGmcuhys\\/pdf?s=ap\",\"issuer\":{\"type\":\"self\"},\"last_finalization_error\":null,\"latest_revision\":null,\"lines\":{\"object\":\"list\",\"data\":[{\"id\":\"il_1SdBWyK5swNIbm5cLiX1uQnU\",\"object\":\"line_item\",\"amount\":0,\"currency\":\"usd\",\"description\":\"Per\\u00edodo de avalia\\u00e7\\u00e3o para Team 3 users Plan\",\"discount_amounts\":[],\"discountable\":true,\"discounts\":[],\"invoice\":\"in_1SdBWyK5swNIbm5c5H5rvMyq\",\"livemode\":false,\"metadata\":{\"plan_slug\":\"team\"},\"parent\":{\"invoice_item_details\":null,\"subscription_item_details\":{\"invoice_item\":null,\"proration\":false,\"proration_details\":{\"credited_items\":null},\"subscription\":\"sub_1SdBWyK5swNIbm5ceeYU3b0z\",\"subscription_item\":\"si_TaMFckUN3Or0Q0\"},\"type\":\"subscription_item_details\"},\"period\":{\"end\":1766675508,\"start\":1765465908},\"pretax_credit_amounts\":[],\"pricing\":{\"price_details\":{\"price\":\"price_1SdAYLK5swNIbm5chsreKFvT\",\"product\":\"prod_TaLEJ8n7rO4Miq\"},\"type\":\"price_details\",\"unit_amount_decimal\":\"0\"},\"quantity\":1,\"taxes\":[]}],\"has_more\":false,\"total_count\":1,\"url\":\"\\/v1\\/invoices\\/in_1SdBWyK5swNIbm5c5H5rvMyq\\/lines\"},\"livemode\":false,\"metadata\":[],\"next_payment_attempt\":null,\"number\":\"JOKO4HIL-0001\",\"on_behalf_of\":null,\"parent\":{\"quote_details\":null,\"subscription_details\":{\"metadata\":{\"plan_slug\":\"team\"},\"subscription\":\"sub_1SdBWyK5swNIbm5ceeYU3b0z\"},\"type\":\"subscription_details\"},\"payment_settings\":{\"default_mandate\":null,\"payment_method_options\":{\"acss_debit\":null,\"bancontact\":null,\"card\":{\"request_three_d_secure\":\"automatic\"},\"customer_balance\":null,\"konbini\":null,\"payto\":null,\"sepa_debit\":null,\"us_bank_account\":null},\"payment_method_types\":[\"card\"]},\"period_end\":1765465908,\"period_start\":1765465908,\"post_payment_credit_notes_amount\":0,\"pre_payment_credit_notes_amount\":0,\"receipt_number\":null,\"rendering\":null,\"shipping_cost\":null,\"shipping_details\":null,\"starting_balance\":0,\"statement_descriptor\":null,\"status\":\"paid\",\"status_transitions\":{\"finalized_at\":1765465908,\"marked_uncollectible_at\":null,\"paid_at\":1765465908,\"voided_at\":null},\"subtotal\":0,\"subtotal_excluding_tax\":0,\"test_clock\":null,\"total\":0,\"total_discount_amounts\":[],\"total_excluding_tax\":0,\"total_pretax_credit_amounts\":[],\"total_taxes\":[],\"webhooks_delivered_at\":1765465908}},\"livemode\":false,\"pending_webhooks\":1,\"request\":{\"id\":null,\"idempotency_key\":null},\"type\":\"invoice.paid\"}', 1, NULL, 0, '2025-12-11 15:11:52', '2025-12-11 10:11:52'),
(3, NULL, 'evt_1SiGDOK5swNIbm5coK3iLv8h', 'customer.subscription.updated', NULL, '2025-11-17.clover', '{\"id\":\"evt_1SiGDOK5swNIbm5coK3iLv8h\",\"object\":\"event\",\"api_version\":\"2025-11-17.clover\",\"created\":1766675554,\"data\":{\"object\":{\"id\":\"sub_1SdBWyK5swNIbm5ceeYU3b0z\",\"object\":\"subscription\",\"application\":null,\"application_fee_percent\":null,\"automatic_tax\":{\"disabled_reason\":null,\"enabled\":false,\"liability\":null},\"billing_cycle_anchor\":1766675508,\"billing_cycle_anchor_config\":null,\"billing_mode\":{\"flexible\":null,\"type\":\"classic\"},\"billing_thresholds\":null,\"cancel_at\":null,\"cancel_at_period_end\":false,\"canceled_at\":null,\"cancellation_details\":{\"comment\":null,\"feedback\":null,\"reason\":null},\"collection_method\":\"charge_automatically\",\"created\":1765465908,\"currency\":\"usd\",\"customer\":\"cus_TaMFqJirGasJ6N\",\"customer_account\":null,\"days_until_due\":null,\"default_payment_method\":\"pm_1SdBWwK5swNIbm5cTdQX8GrJ\",\"default_source\":null,\"default_tax_rates\":[],\"description\":null,\"discounts\":[],\"ended_at\":null,\"invoice_settings\":{\"account_tax_ids\":null,\"issuer\":{\"type\":\"self\"}},\"items\":{\"object\":\"list\",\"data\":[{\"id\":\"si_TaMFckUN3Or0Q0\",\"object\":\"subscription_item\",\"billing_thresholds\":null,\"created\":1765465908,\"current_period_end\":1769353908,\"current_period_start\":1766675508,\"discounts\":[],\"metadata\":[],\"plan\":{\"id\":\"price_1SdAYLK5swNIbm5chsreKFvT\",\"object\":\"plan\",\"active\":true,\"amount\":6000,\"amount_decimal\":\"6000\",\"billing_scheme\":\"per_unit\",\"created\":1765462149,\"currency\":\"usd\",\"interval\":\"month\",\"interval_count\":1,\"livemode\":false,\"metadata\":[],\"meter\":null,\"nickname\":null,\"product\":\"prod_TaLEJ8n7rO4Miq\",\"tiers_mode\":null,\"transform_usage\":null,\"trial_period_days\":null,\"usage_type\":\"licensed\"},\"price\":{\"id\":\"price_1SdAYLK5swNIbm5chsreKFvT\",\"object\":\"price\",\"active\":true,\"billing_scheme\":\"per_unit\",\"created\":1765462149,\"currency\":\"usd\",\"custom_unit_amount\":null,\"livemode\":false,\"lookup_key\":null,\"metadata\":[],\"nickname\":null,\"product\":\"prod_TaLEJ8n7rO4Miq\",\"recurring\":{\"interval\":\"month\",\"interval_count\":1,\"meter\":null,\"trial_period_days\":null,\"usage_type\":\"licensed\"},\"tax_behavior\":\"unspecified\",\"tiers_mode\":null,\"transform_quantity\":null,\"type\":\"recurring\",\"unit_amount\":6000,\"unit_amount_decimal\":\"6000\"},\"quantity\":1,\"subscription\":\"sub_1SdBWyK5swNIbm5ceeYU3b0z\",\"tax_rates\":[]}],\"has_more\":false,\"total_count\":1,\"url\":\"\\/v1\\/subscription_items?subscription=sub_1SdBWyK5swNIbm5ceeYU3b0z\"},\"latest_invoice\":\"in_1SiGDNK5swNIbm5crWqgxH2J\",\"livemode\":false,\"metadata\":{\"plan_slug\":\"team\"},\"next_pending_invoice_item_invoice\":null,\"on_behalf_of\":null,\"pause_collection\":null,\"payment_settings\":{\"payment_method_options\":{\"acss_debit\":null,\"bancontact\":null,\"card\":{\"network\":null,\"request_three_d_secure\":\"automatic\"},\"customer_balance\":null,\"konbini\":null,\"payto\":null,\"sepa_debit\":null,\"us_bank_account\":null},\"payment_method_types\":[\"card\"],\"save_default_payment_method\":\"off\"},\"pending_invoice_item_interval\":null,\"pending_setup_intent\":null,\"pending_update\":null,\"plan\":{\"id\":\"price_1SdAYLK5swNIbm5chsreKFvT\",\"object\":\"plan\",\"active\":true,\"amount\":6000,\"amount_decimal\":\"6000\",\"billing_scheme\":\"per_unit\",\"created\":1765462149,\"currency\":\"usd\",\"interval\":\"month\",\"interval_count\":1,\"livemode\":false,\"metadata\":[],\"meter\":null,\"nickname\":null,\"product\":\"prod_TaLEJ8n7rO4Miq\",\"tiers_mode\":null,\"transform_usage\":null,\"trial_period_days\":null,\"usage_type\":\"licensed\"},\"quantity\":1,\"schedule\":null,\"start_date\":1765465908,\"status\":\"active\",\"test_clock\":null,\"transfer_data\":null,\"trial_end\":1766675508,\"trial_settings\":{\"end_behavior\":{\"missing_payment_method\":\"create_invoice\"}},\"trial_start\":1765465908},\"previous_attributes\":{\"items\":{\"data\":[{\"id\":\"si_TaMFckUN3Or0Q0\",\"object\":\"subscription_item\",\"billing_thresholds\":null,\"created\":1765465908,\"current_period_end\":1766675508,\"current_period_start\":1765465908,\"discounts\":[],\"metadata\":[],\"plan\":{\"id\":\"price_1SdAYLK5swNIbm5chsreKFvT\",\"object\":\"plan\",\"active\":true,\"amount\":6000,\"amount_decimal\":\"6000\",\"billing_scheme\":\"per_unit\",\"created\":1765462149,\"currency\":\"usd\",\"interval\":\"month\",\"interval_count\":1,\"livemode\":false,\"metadata\":[],\"meter\":null,\"nickname\":null,\"product\":\"prod_TaLEJ8n7rO4Miq\",\"tiers_mode\":null,\"transform_usage\":null,\"trial_period_days\":null,\"usage_type\":\"licensed\"},\"price\":{\"id\":\"price_1SdAYLK5swNIbm5chsreKFvT\",\"object\":\"price\",\"active\":true,\"billing_scheme\":\"per_unit\",\"created\":1765462149,\"currency\":\"usd\",\"custom_unit_amount\":null,\"livemode\":false,\"lookup_key\":null,\"metadata\":[],\"nickname\":null,\"product\":\"prod_TaLEJ8n7rO4Miq\",\"recurring\":{\"interval\":\"month\",\"interval_count\":1,\"meter\":null,\"trial_period_days\":null,\"usage_type\":\"licensed\"},\"tax_behavior\":\"unspecified\",\"tiers_mode\":null,\"transform_quantity\":null,\"type\":\"recurring\",\"unit_amount\":6000,\"unit_amount_decimal\":\"6000\"},\"quantity\":1,\"subscription\":\"sub_1SdBWyK5swNIbm5ceeYU3b0z\",\"tax_rates\":[]}]},\"latest_invoice\":\"in_1SdBWyK5swNIbm5c5H5rvMyq\",\"status\":\"trialing\"}},\"livemode\":false,\"pending_webhooks\":1,\"request\":{\"id\":null,\"idempotency_key\":null},\"type\":\"customer.subscription.updated\"}', 1, NULL, 0, '2025-12-25 15:12:35', '2025-12-25 10:12:35'),
(4, NULL, 'evt_1SiHALK5swNIbm5cP44C5ZXL', 'invoice.paid', NULL, '2025-11-17.clover', '{\"id\":\"evt_1SiHALK5swNIbm5cP44C5ZXL\",\"object\":\"event\",\"api_version\":\"2025-11-17.clover\",\"created\":1766679209,\"data\":{\"object\":{\"id\":\"in_1SiGDNK5swNIbm5crWqgxH2J\",\"object\":\"invoice\",\"account_country\":\"US\",\"account_name\":\"Traffio-SandBox\",\"account_tax_ids\":null,\"amount_due\":6000,\"amount_overpaid\":0,\"amount_paid\":6000,\"amount_remaining\":0,\"amount_shipping\":0,\"application\":null,\"attempt_count\":1,\"attempted\":true,\"auto_advance\":false,\"automatic_tax\":{\"disabled_reason\":null,\"enabled\":false,\"liability\":null,\"provider\":null,\"status\":null},\"automatically_finalizes_at\":null,\"billing_reason\":\"subscription_cycle\",\"collection_method\":\"charge_automatically\",\"created\":1766675553,\"currency\":\"usd\",\"custom_fields\":null,\"customer\":\"cus_TaMFqJirGasJ6N\",\"customer_account\":null,\"customer_address\":{\"city\":null,\"country\":\"US\",\"line1\":null,\"line2\":null,\"postal_code\":\"30144\",\"state\":null},\"customer_email\":\"test@example.com\",\"customer_name\":\"John Doe\",\"customer_phone\":null,\"customer_shipping\":null,\"customer_tax_exempt\":\"none\",\"customer_tax_ids\":[],\"default_payment_method\":null,\"default_source\":null,\"default_tax_rates\":[],\"description\":null,\"discounts\":[],\"due_date\":null,\"effective_at\":1766679204,\"ending_balance\":0,\"footer\":null,\"from_invoice\":null,\"hosted_invoice_url\":\"https:\\/\\/invoice.stripe.com\\/i\\/acct_1SdAPSK5swNIbm5c\\/test_YWNjdF8xU2RBUFNLNXN3TklibTVjLF9UZmJRcG04TjY3QzJ6bGtsU08wZFNSRXdRZ0lvSjFkLDE1NzIyMDAwOQ02002GLovdxT?s=ap\",\"invoice_pdf\":\"https:\\/\\/pay.stripe.com\\/invoice\\/acct_1SdAPSK5swNIbm5c\\/test_YWNjdF8xU2RBUFNLNXN3TklibTVjLF9UZmJRcG04TjY3QzJ6bGtsU08wZFNSRXdRZ0lvSjFkLDE1NzIyMDAwOQ02002GLovdxT\\/pdf?s=ap\",\"issuer\":{\"type\":\"self\"},\"last_finalization_error\":null,\"latest_revision\":null,\"lines\":{\"object\":\"list\",\"data\":[{\"id\":\"il_1SiGDNK5swNIbm5ch3DegjTt\",\"object\":\"line_item\",\"amount\":6000,\"currency\":\"usd\",\"description\":\"1 \\u00d7 Team 3 users Plan (at $60.00 \\/ month)\",\"discount_amounts\":[],\"discountable\":true,\"discounts\":[],\"invoice\":\"in_1SiGDNK5swNIbm5crWqgxH2J\",\"livemode\":false,\"metadata\":{\"plan_slug\":\"team\"},\"parent\":{\"invoice_item_details\":null,\"subscription_item_details\":{\"invoice_item\":null,\"proration\":false,\"proration_details\":{\"credited_items\":null},\"subscription\":\"sub_1SdBWyK5swNIbm5ceeYU3b0z\",\"subscription_item\":\"si_TaMFckUN3Or0Q0\"},\"type\":\"subscription_item_details\"},\"period\":{\"end\":1769353908,\"start\":1766675508},\"pretax_credit_amounts\":[],\"pricing\":{\"price_details\":{\"price\":\"price_1SdAYLK5swNIbm5chsreKFvT\",\"product\":\"prod_TaLEJ8n7rO4Miq\"},\"type\":\"price_details\",\"unit_amount_decimal\":\"6000\"},\"quantity\":1,\"subtotal\":6000,\"taxes\":[]}],\"has_more\":false,\"total_count\":1,\"url\":\"\\/v1\\/invoices\\/in_1SiGDNK5swNIbm5crWqgxH2J\\/lines\"},\"livemode\":false,\"metadata\":[],\"next_payment_attempt\":null,\"number\":\"JOKO4HIL-0002\",\"on_behalf_of\":null,\"parent\":{\"quote_details\":null,\"subscription_details\":{\"metadata\":{\"plan_slug\":\"team\"},\"subscription\":\"sub_1SdBWyK5swNIbm5ceeYU3b0z\"},\"type\":\"subscription_details\"},\"payment_settings\":{\"default_mandate\":null,\"payment_method_options\":{\"acss_debit\":null,\"bancontact\":null,\"card\":{\"request_three_d_secure\":\"automatic\"},\"customer_balance\":null,\"konbini\":null,\"payto\":null,\"sepa_debit\":null,\"us_bank_account\":null},\"payment_method_types\":[\"card\"]},\"period_end\":1766675508,\"period_start\":1765465908,\"post_payment_credit_notes_amount\":0,\"pre_payment_credit_notes_amount\":0,\"receipt_number\":null,\"rendering\":null,\"shipping_cost\":null,\"shipping_details\":null,\"starting_balance\":0,\"statement_descriptor\":null,\"status\":\"paid\",\"status_transitions\":{\"finalized_at\":1766679204,\"marked_uncollectible_at\":null,\"paid_at\":1766679204,\"voided_at\":null},\"subtotal\":6000,\"subtotal_excluding_tax\":6000,\"test_clock\":null,\"total\":6000,\"total_discount_amounts\":[],\"total_excluding_tax\":6000,\"total_pretax_credit_amounts\":[],\"total_taxes\":[],\"webhooks_delivered_at\":1766675553}},\"livemode\":false,\"pending_webhooks\":1,\"request\":{\"id\":null,\"idempotency_key\":null},\"type\":\"invoice.paid\"}', 1, NULL, 0, '2025-12-25 16:13:30', '2025-12-25 11:13:30'),
(5, NULL, 'evt_1StUzcK5swNIbm5cWUXdd5cz', 'customer.subscription.updated', NULL, '2025-11-17.clover', '{\"id\":\"evt_1StUzcK5swNIbm5cWUXdd5cz\",\"object\":\"event\",\"api_version\":\"2025-11-17.clover\",\"created\":1769353968,\"data\":{\"object\":{\"id\":\"sub_1SdBWyK5swNIbm5ceeYU3b0z\",\"object\":\"subscription\",\"application\":null,\"application_fee_percent\":null,\"automatic_tax\":{\"disabled_reason\":null,\"enabled\":false,\"liability\":null},\"billing_cycle_anchor\":1766675508,\"billing_cycle_anchor_config\":null,\"billing_mode\":{\"flexible\":null,\"type\":\"classic\"},\"billing_thresholds\":null,\"cancel_at\":null,\"cancel_at_period_end\":false,\"canceled_at\":null,\"cancellation_details\":{\"comment\":null,\"feedback\":null,\"reason\":null},\"collection_method\":\"charge_automatically\",\"created\":1765465908,\"currency\":\"usd\",\"customer\":\"cus_TaMFqJirGasJ6N\",\"customer_account\":null,\"days_until_due\":null,\"default_payment_method\":\"pm_1SdBWwK5swNIbm5cTdQX8GrJ\",\"default_source\":null,\"default_tax_rates\":[],\"description\":null,\"discounts\":[],\"ended_at\":null,\"invoice_settings\":{\"account_tax_ids\":null,\"issuer\":{\"type\":\"self\"}},\"items\":{\"object\":\"list\",\"data\":[{\"id\":\"si_TaMFckUN3Or0Q0\",\"object\":\"subscription_item\",\"billing_thresholds\":null,\"created\":1765465908,\"current_period_end\":1772032308,\"current_period_start\":1769353908,\"discounts\":[],\"metadata\":[],\"plan\":{\"id\":\"price_1SdAYLK5swNIbm5chsreKFvT\",\"object\":\"plan\",\"active\":true,\"amount\":6000,\"amount_decimal\":\"6000\",\"billing_scheme\":\"per_unit\",\"created\":1765462149,\"currency\":\"usd\",\"interval\":\"month\",\"interval_count\":1,\"livemode\":false,\"metadata\":[],\"meter\":null,\"nickname\":null,\"product\":\"prod_TaLEJ8n7rO4Miq\",\"tiers_mode\":null,\"transform_usage\":null,\"trial_period_days\":null,\"usage_type\":\"licensed\"},\"price\":{\"id\":\"price_1SdAYLK5swNIbm5chsreKFvT\",\"object\":\"price\",\"active\":true,\"billing_scheme\":\"per_unit\",\"created\":1765462149,\"currency\":\"usd\",\"custom_unit_amount\":null,\"livemode\":false,\"lookup_key\":null,\"metadata\":[],\"nickname\":null,\"product\":\"prod_TaLEJ8n7rO4Miq\",\"recurring\":{\"interval\":\"month\",\"interval_count\":1,\"meter\":null,\"trial_period_days\":null,\"usage_type\":\"licensed\"},\"tax_behavior\":\"unspecified\",\"tiers_mode\":null,\"transform_quantity\":null,\"type\":\"recurring\",\"unit_amount\":6000,\"unit_amount_decimal\":\"6000\"},\"quantity\":1,\"subscription\":\"sub_1SdBWyK5swNIbm5ceeYU3b0z\",\"tax_rates\":[]}],\"has_more\":false,\"total_count\":1,\"url\":\"\\/v1\\/subscription_items?subscription=sub_1SdBWyK5swNIbm5ceeYU3b0z\"},\"latest_invoice\":\"in_1StUzbK5swNIbm5com1wVIVt\",\"livemode\":false,\"metadata\":{\"plan_slug\":\"team\"},\"next_pending_invoice_item_invoice\":null,\"on_behalf_of\":null,\"pause_collection\":null,\"payment_settings\":{\"payment_method_options\":{\"acss_debit\":null,\"bancontact\":null,\"card\":{\"network\":null,\"request_three_d_secure\":\"automatic\"},\"customer_balance\":null,\"konbini\":null,\"payto\":null,\"sepa_debit\":null,\"us_bank_account\":null},\"payment_method_types\":[\"card\"],\"save_default_payment_method\":\"off\"},\"pending_invoice_item_interval\":null,\"pending_setup_intent\":null,\"pending_update\":null,\"plan\":{\"id\":\"price_1SdAYLK5swNIbm5chsreKFvT\",\"object\":\"plan\",\"active\":true,\"amount\":6000,\"amount_decimal\":\"6000\",\"billing_scheme\":\"per_unit\",\"created\":1765462149,\"currency\":\"usd\",\"interval\":\"month\",\"interval_count\":1,\"livemode\":false,\"metadata\":[],\"meter\":null,\"nickname\":null,\"product\":\"prod_TaLEJ8n7rO4Miq\",\"tiers_mode\":null,\"transform_usage\":null,\"trial_period_days\":null,\"usage_type\":\"licensed\"},\"quantity\":1,\"schedule\":null,\"start_date\":1765465908,\"status\":\"active\",\"test_clock\":null,\"transfer_data\":null,\"trial_end\":1766675508,\"trial_settings\":{\"end_behavior\":{\"missing_payment_method\":\"create_invoice\"}},\"trial_start\":1765465908},\"previous_attributes\":{\"items\":{\"data\":[{\"id\":\"si_TaMFckUN3Or0Q0\",\"object\":\"subscription_item\",\"billing_thresholds\":null,\"created\":1765465908,\"current_period_end\":1769353908,\"current_period_start\":1766675508,\"discounts\":[],\"metadata\":[],\"plan\":{\"id\":\"price_1SdAYLK5swNIbm5chsreKFvT\",\"object\":\"plan\",\"active\":true,\"amount\":6000,\"amount_decimal\":\"6000\",\"billing_scheme\":\"per_unit\",\"created\":1765462149,\"currency\":\"usd\",\"interval\":\"month\",\"interval_count\":1,\"livemode\":false,\"metadata\":[],\"meter\":null,\"nickname\":null,\"product\":\"prod_TaLEJ8n7rO4Miq\",\"tiers_mode\":null,\"transform_usage\":null,\"trial_period_days\":null,\"usage_type\":\"licensed\"},\"price\":{\"id\":\"price_1SdAYLK5swNIbm5chsreKFvT\",\"object\":\"price\",\"active\":true,\"billing_scheme\":\"per_unit\",\"created\":1765462149,\"currency\":\"usd\",\"custom_unit_amount\":null,\"livemode\":false,\"lookup_key\":null,\"metadata\":[],\"nickname\":null,\"product\":\"prod_TaLEJ8n7rO4Miq\",\"recurring\":{\"interval\":\"month\",\"interval_count\":1,\"meter\":null,\"trial_period_days\":null,\"usage_type\":\"licensed\"},\"tax_behavior\":\"unspecified\",\"tiers_mode\":null,\"transform_quantity\":null,\"type\":\"recurring\",\"unit_amount\":6000,\"unit_amount_decimal\":\"6000\"},\"quantity\":1,\"subscription\":\"sub_1SdBWyK5swNIbm5ceeYU3b0z\",\"tax_rates\":[]}]},\"latest_invoice\":\"in_1SiGDNK5swNIbm5crWqgxH2J\"}},\"livemode\":false,\"pending_webhooks\":1,\"request\":{\"id\":null,\"idempotency_key\":null},\"type\":\"customer.subscription.updated\"}', 1, NULL, 0, '2026-01-25 15:12:48', '2026-01-25 10:12:48'),
(6, NULL, 'evt_1StVw8K5swNIbm5cIGxJVRtN', 'invoice.paid', NULL, '2025-11-17.clover', '{\"id\":\"evt_1StVw8K5swNIbm5cIGxJVRtN\",\"object\":\"event\",\"api_version\":\"2025-11-17.clover\",\"created\":1769357596,\"data\":{\"object\":{\"id\":\"in_1StUzbK5swNIbm5com1wVIVt\",\"object\":\"invoice\",\"account_country\":\"US\",\"account_name\":\"Traffio-SandBox\",\"account_tax_ids\":null,\"amount_due\":6000,\"amount_overpaid\":0,\"amount_paid\":6000,\"amount_remaining\":0,\"amount_shipping\":0,\"application\":null,\"attempt_count\":1,\"attempted\":true,\"auto_advance\":false,\"automatic_tax\":{\"disabled_reason\":null,\"enabled\":false,\"liability\":null,\"provider\":null,\"status\":null},\"automatically_finalizes_at\":null,\"billing_reason\":\"subscription_cycle\",\"collection_method\":\"charge_automatically\",\"created\":1769353967,\"currency\":\"usd\",\"custom_fields\":null,\"customer\":\"cus_TaMFqJirGasJ6N\",\"customer_account\":null,\"customer_address\":{\"city\":null,\"country\":\"US\",\"line1\":null,\"line2\":null,\"postal_code\":\"30144\",\"state\":null},\"customer_email\":\"test@example.com\",\"customer_name\":\"John Doe\",\"customer_phone\":null,\"customer_shipping\":null,\"customer_tax_exempt\":\"none\",\"customer_tax_ids\":[],\"default_payment_method\":null,\"default_source\":null,\"default_tax_rates\":[],\"description\":null,\"discounts\":[],\"due_date\":null,\"effective_at\":1769357591,\"ending_balance\":0,\"footer\":null,\"from_invoice\":null,\"hosted_invoice_url\":\"https:\\/\\/invoice.stripe.com\\/i\\/acct_1SdAPSK5swNIbm5c\\/test_YWNjdF8xU2RBUFNLNXN3TklibTVjLF9UckRRaGpKVEhlOTF1NkU0bHdjNTM1SXdNb3pSdkpLLDE1OTg5ODM5Ng0200GNCHxo31?s=ap\",\"invoice_pdf\":\"https:\\/\\/pay.stripe.com\\/invoice\\/acct_1SdAPSK5swNIbm5c\\/test_YWNjdF8xU2RBUFNLNXN3TklibTVjLF9UckRRaGpKVEhlOTF1NkU0bHdjNTM1SXdNb3pSdkpLLDE1OTg5ODM5Ng0200GNCHxo31\\/pdf?s=ap\",\"issuer\":{\"type\":\"self\"},\"last_finalization_error\":null,\"latest_revision\":null,\"lines\":{\"object\":\"list\",\"data\":[{\"id\":\"il_1StUzbK5swNIbm5cIagCOuEj\",\"object\":\"line_item\",\"amount\":6000,\"currency\":\"usd\",\"description\":\"1 \\u00d7 Team 3 users Plan (at $60.00 \\/ month)\",\"discount_amounts\":[],\"discountable\":true,\"discounts\":[],\"invoice\":\"in_1StUzbK5swNIbm5com1wVIVt\",\"livemode\":false,\"metadata\":{\"plan_slug\":\"team\"},\"parent\":{\"invoice_item_details\":null,\"subscription_item_details\":{\"invoice_item\":null,\"proration\":false,\"proration_details\":{\"credited_items\":null},\"subscription\":\"sub_1SdBWyK5swNIbm5ceeYU3b0z\",\"subscription_item\":\"si_TaMFckUN3Or0Q0\"},\"type\":\"subscription_item_details\"},\"period\":{\"end\":1772032308,\"start\":1769353908},\"pretax_credit_amounts\":[],\"pricing\":{\"price_details\":{\"price\":\"price_1SdAYLK5swNIbm5chsreKFvT\",\"product\":\"prod_TaLEJ8n7rO4Miq\"},\"type\":\"price_details\",\"unit_amount_decimal\":\"6000\"},\"quantity\":1,\"subtotal\":6000,\"taxes\":[]}],\"has_more\":false,\"total_count\":1,\"url\":\"\\/v1\\/invoices\\/in_1StUzbK5swNIbm5com1wVIVt\\/lines\"},\"livemode\":false,\"metadata\":[],\"next_payment_attempt\":null,\"number\":\"JOKO4HIL-0003\",\"on_behalf_of\":null,\"parent\":{\"quote_details\":null,\"subscription_details\":{\"metadata\":{\"plan_slug\":\"team\"},\"subscription\":\"sub_1SdBWyK5swNIbm5ceeYU3b0z\"},\"type\":\"subscription_details\"},\"payment_settings\":{\"default_mandate\":null,\"payment_method_options\":{\"acss_debit\":null,\"bancontact\":null,\"card\":{\"request_three_d_secure\":\"automatic\"},\"customer_balance\":null,\"konbini\":null,\"payto\":null,\"sepa_debit\":null,\"us_bank_account\":null},\"payment_method_types\":[\"card\"]},\"period_end\":1769353908,\"period_start\":1766675508,\"post_payment_credit_notes_amount\":0,\"pre_payment_credit_notes_amount\":0,\"receipt_number\":null,\"rendering\":null,\"shipping_cost\":null,\"shipping_details\":null,\"starting_balance\":0,\"statement_descriptor\":null,\"status\":\"paid\",\"status_transitions\":{\"finalized_at\":1769357591,\"marked_uncollectible_at\":null,\"paid_at\":1769357591,\"voided_at\":null},\"subtotal\":6000,\"subtotal_excluding_tax\":6000,\"test_clock\":null,\"total\":6000,\"total_discount_amounts\":[],\"total_excluding_tax\":6000,\"total_pretax_credit_amounts\":[],\"total_taxes\":[],\"webhooks_delivered_at\":1769353967}},\"livemode\":false,\"pending_webhooks\":1,\"request\":{\"id\":null,\"idempotency_key\":null},\"type\":\"invoice.paid\"}', 1, NULL, 0, '2026-01-25 16:13:17', '2026-01-25 11:13:17');

-- --------------------------------------------------------

--
-- Estrutura para tabela `subscription_history`
--

DROP TABLE IF EXISTS `subscription_history`;
CREATE TABLE `subscription_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `event_type` enum('created','upgraded','downgraded','canceled','reactivated','payment_failed','payment_succeeded','trial_started','trial_ended','suspended','unsuspended') NOT NULL,
  `from_plan_id` int(10) UNSIGNED DEFAULT NULL,
  `to_plan_id` int(10) UNSIGNED DEFAULT NULL,
  `stripe_event_id` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `subscription_history`
--

INSERT INTO `subscription_history` (`id`, `tenant_id`, `event_type`, `from_plan_id`, `to_plan_id`, `stripe_event_id`, `amount`, `notes`, `created_at`) VALUES
(1, 10, 'created', NULL, 1, NULL, 60.00, NULL, '2025-12-11 15:11:52');

-- --------------------------------------------------------

--
-- Estrutura para tabela `subscription_plans`
--

DROP TABLE IF EXISTS `subscription_plans`;
CREATE TABLE `subscription_plans` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `stripe_product_id` varchar(100) DEFAULT NULL COMMENT 'prod_xxx',
  `stripe_price_id` varchar(100) DEFAULT NULL COMMENT 'price_xxx',
  `price_monthly` decimal(10,2) NOT NULL,
  `price_yearly` decimal(10,2) DEFAULT NULL,
  `user_limit` int(11) NOT NULL DEFAULT 3,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Feature flags for this plan' CHECK (json_valid(`features`)),
  `is_popular` tinyint(1) DEFAULT 0 COMMENT 'Highlight as recommended',
  `trial_days` int(11) DEFAULT 14,
  `sort_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `subscription_plans`
--

INSERT INTO `subscription_plans` (`id`, `name`, `slug`, `description`, `stripe_product_id`, `stripe_price_id`, `price_monthly`, `price_yearly`, `user_limit`, `features`, `is_popular`, `trial_days`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Team', 'team', 'Ideal for small construction teams getting started with digital operations.', NULL, NULL, 60.00, NULL, 3, '{\"clients\": true, \"projects\": true, \"expenses\": true, \"time_tracking\": true, \"estimates\": true, \"invoices\": true, \"basic_reports\": true, \"email_notifications\": true, \"support\": \"standard\"}', 0, 14, 1, 'active', '2025-12-11 13:28:15', '2025-12-11 13:28:15'),
(2, 'Business', 'business', 'Best for growing companies that need stronger collaboration and financial visibility.', NULL, NULL, 90.00, NULL, 5, '{\"clients\": true, \"projects\": true, \"expenses\": true, \"time_tracking\": true, \"estimates\": true, \"invoices\": true, \"basic_reports\": true, \"email_notifications\": true, \"payroll\": true, \"inventory\": true, \"advanced_analytics\": true, \"pwa_offline\": true, \"support\": \"priority\", \"support_tickets\": true}', 1, 14, 2, 'active', '2025-12-11 13:28:15', '2025-12-11 13:28:15'),
(3, 'Professional', 'professional', 'Designed for medium-sized construction companies with full operational complexity.', NULL, NULL, 160.00, NULL, 10, '{\"clients\": true, \"projects\": true, \"expenses\": true, \"time_tracking\": true, \"estimates\": true, \"invoices\": true, \"basic_reports\": true, \"email_notifications\": true, \"payroll\": true, \"inventory\": true, \"advanced_analytics\": true, \"pwa_offline\": true, \"support_tickets\": true, \"advanced_reports\": true, \"custom_branding\": true, \"export_tools\": true, \"automated_workflows\": true, \"support\": \"priority_chat\"}', 0, 14, 3, 'active', '2025-12-11 13:28:15', '2025-12-11 13:28:15'),
(4, 'Additional User Seat', 'extra-seat', 'Add additional licensed seats for teams over 10 users.', NULL, NULL, 14.00, NULL, 1, '{}', 0, 14, 99, 'active', '2025-12-11 13:28:15', '2025-12-11 13:28:15');

-- --------------------------------------------------------

--
-- Estrutura para tabela `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `payment_terms` int(11) DEFAULT 30,
  `tax_id` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `suppliers`
--

INSERT INTO `suppliers` (`id`, `tenant_id`, `name`, `contact_person`, `email`, `phone`, `address`, `city`, `state`, `zip_code`, `payment_terms`, `tax_id`, `notes`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'BuildersSupply Co.', 'Tom Wilson', 'tom@builderssupply.com', '(555) 111-0001', '100 Warehouse Way', 'Austin', 'TX', NULL, 30, NULL, NULL, 'active', '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(2, 1, 'ElectroPro Distributors', 'Amy Lee', 'amy@electropro.com', '(555) 222-0002', '200 Electric Ave', 'Austin', 'TX', NULL, 45, NULL, NULL, 'active', '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(3, 1, 'PlumbMaster Supply', 'Chris Davis', 'chris@plumbmaster.com', '(555) 333-0003', '300 Pipe Lane', 'Austin', 'TX', NULL, 30, NULL, NULL, 'active', '2025-12-08 13:55:36', '2025-12-08 13:55:36');

-- --------------------------------------------------------

--
-- Estrutura para tabela `support_tickets`
--

DROP TABLE IF EXISTS `support_tickets`;
CREATE TABLE `support_tickets` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `project_id` int(10) UNSIGNED DEFAULT NULL,
  `ticket_number` varchar(20) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `category` enum('bug','feature','billing','usability','performance','security','other') DEFAULT 'other',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('new','open','in_progress','awaiting_info','resolved','closed') DEFAULT 'new',
  `os_name` varchar(50) DEFAULT NULL,
  `os_version` varchar(50) DEFAULT NULL,
  `browser_name` varchar(50) DEFAULT NULL,
  `browser_version` varchar(50) DEFAULT NULL,
  `screen_resolution` varchar(20) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `timezone` varchar(50) DEFAULT NULL,
  `anydesk_id` varchar(20) DEFAULT NULL,
  `assigned_to` int(10) UNSIGNED DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `satisfaction_rating` tinyint(4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `support_tickets`
--

INSERT INTO `support_tickets` (`id`, `tenant_id`, `user_id`, `project_id`, `ticket_number`, `subject`, `description`, `category`, `priority`, `status`, `os_name`, `os_version`, `browser_name`, `browser_version`, `screen_resolution`, `user_agent`, `timezone`, `anydesk_id`, `assigned_to`, `assigned_at`, `resolved_at`, `closed_at`, `satisfaction_rating`, `created_at`, `updated_at`) VALUES
(1, 7, 13, 9, 'TKT-37CA7D94', 'teste', 'isso aqui auqilo ali\n\n**Steps to Reproduce:**\n1\n2\n3\n4\n5\n6\n7\n', 'bug', 'medium', 'new', 'Linux', '', 'Chrome', '143', '1255x815', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', 'America/Sao_Paulo', NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-10 20:09:32', '2025-12-10 20:09:32'),
(2, 7, 13, 17, 'TKT-45B4183A', 'teste 02', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\n\nInteger a ipsum vitae urna varius egestas. Integer laoreet, sapien eget vehicula vehicula, odio lorem scelerisque magna, nec gravida libero nulla eget risus. Nulla facilisi. Donec at magna ut nulla pharetra cursus. Curabitur auctor, tellus in congue vestibulum, lacus lacus convallis justo, at fermentum libero felis nec ligula.\n\nDonec et urna vel risus feugiat pharetra. Proin id lacus vitae velit accumsan venenatis. Aenean non mi vel nisi lacinia maximus. Duis efficitur, sapien quis bibendum auctor, lectus risus feugiat sapien, ac pulvinar orci est a arcu. Integer id augue vitae urna tristique tempus.\n\nEtiam accumsan urna a mauris dapibus, nec aliquet nunc convallis. Phasellus eget justo et libero ultrices posuere. Cras euismod, arcu nec congue convallis, ipsum nunc cursus nibh, vel condimentum sapien orci non libero. Integer ullamcorper felis sit amet felis placerat, eu convallis lorem iaculis.\n\nSed vehicula magna at lacus interdum, quis laoreet nulla condimentum. Aliquam erat volutpat. Cras et nulla in turpis consectetur suscipit. Vivamus lobortis, risus sit amet cursus tincidunt, erat turpis placerat ex, ut placerat justo lorem vel ligula. Fusce non diam felis.\n\n**Steps to Reproduce:**\nLorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\n\nInteger a ipsum vitae urna varius egestas. Integer laoreet, sapien eget vehicula vehicula, odio lorem scelerisque magna, nec gravida libero nulla eget risus. Nulla facilisi. Donec at magna ut nulla pharetra cursus. Curabitur auctor, tellus in congue vestibulum, lacus lacus convallis justo, at fermentum libero felis nec ligula.\n\nDonec et urna vel risus feugiat pharetra. Proin id lacus vitae velit accumsan venenatis. Aenean non mi vel nisi lacinia maximus. Duis efficitur, sapien quis bibendum auctor, lectus risus feugiat sapien, ac pulvinar orci est a arcu. Integer id augue vitae urna tristique tempus.\n\nEtiam accumsan urna a mauris dapibus, nec aliquet nunc convallis. Phasellus eget justo et libero ultrices posuere. Cras euismod, arcu nec congue convallis, ipsum nunc cursus nibh, vel condimentum sapien orci non libero. Integer ullamcorper felis sit amet felis placerat, eu convallis lorem iaculis.\n\nSed vehicula magna at lacus interdum, quis laoreet nulla condimentum. Aliquam erat volutpat. Cras et nulla in turpis consectetur suscipit. Vivamus lobortis, risus sit amet cursus tincidunt, erat turpis placerat ex, ut placerat justo lorem vel ligula. Fusce non diam felis.', 'bug', 'medium', 'new', 'Linux', '', 'Chrome', '143', '1255x815', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', 'America/Sao_Paulo', NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-10 20:13:15', '2025-12-10 20:13:15'),
(3, 7, 13, 15, 'TKT-4FF0C40F', 'teste 03', 'etstes', 'bug', 'medium', 'new', 'Linux', '', 'Chrome', '143', '1255x815', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', 'America/Sao_Paulo', NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-10 20:15:59', '2025-12-10 20:15:59'),
(4, 7, 13, 15, 'TKT-65DD82A4', 'teste 03', 'etstes', 'bug', 'medium', 'new', 'Linux', '', 'Chrome', '143', '1255x815', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', 'America/Sao_Paulo', NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-10 20:21:49', '2025-12-10 20:21:49'),
(5, 7, 13, 18, 'TKT-6799DBC2', 'teste 04', 'Seu comando SQL foi executado com sucesso.\nDESCRIBE support_tickets;\n[ Editar em linha ] [ Editar ] [ Criar código PHP ]\nField\nType\nNull\nKey\nDefault\nExtra\nid\nint(10) unsigned\nNO\nPRI\nNULL\nauto_increment\ntenant_id\nint(10) unsigned\nNO\nMUL\nNULL\nuser_id\nint(10) unsigned\nNO\nMUL\nNULL\nproject_id\nint(10) unsigned\nYES\nMUL\nNULL\nticket_number\nvarchar(20)\nNO\nUNI\nNULL\nsubject\nvarchar(255)\nNO\nNULL\ndescription\ntext\nNO\nNULL\ncategory\nenum(\'bug\',\'feature\',\'billing\',\'usability\',\'performance\',\'security\',\'other\')\nYES\nother\npriority\nenum(\'low\',\'medium\',\'high\',\'urgent\')\nYES\nMUL\nmedium\nstatus\nenum(\'new\',\'open\',\'in_progress\',\'awaiting_info\',\'resolved\',\'closed\')\nYES\nnew\nos_name\nvarchar(50)\nYES\nNULL\nos_version\nvarchar(50)\nYES\nNULL\nbrowser_name\nvarchar(50)\nYES\nNULL\nbrowser_version\nvarchar(50)\nYES\nNULL\nscreen_resolution\nvarchar(20)\nYES\nNULL\nuser_agent\ntext\nYES\nNULL\ntimezone\nvarchar(50)\nYES\nNULL\nanydesk_id\nvarchar(20)\nYES\nNULL\nassigned_to\nint(10) unsigned\nYES\nMUL\nNULL\nassigned_at\ndatetime\nYES\nNULL\nresolved_at\ndatetime\nYES\nNULL\nclosed_at\ndatetime\nYES\nNULL\nsatisfaction_rating\ntinyint(4)\nYES\nNULL\ncreated_at\ntimestamp\nYES\nMUL\ncurrent_timestamp()\nupdated_at\ntimestamp\nYES\ncurrent_timestamp()\non update current_timestamp()\n\n\n**Steps to Reproduce:**\nSeu comando SQL foi executado com sucesso.\nDESCRIBE support_tickets;\n[ Editar em linha ] [ Editar ] [ Criar código PHP ]\nField\nType\nNull\nKey\nDefault\nExtra\nid\nint(10) unsigned\nNO\nPRI\nNULL\nauto_increment\ntenant_id\nint(10) unsigned\nNO\nMUL\nNULL\nuser_id\nint(10) unsigned\nNO\nMUL\nNULL\nproject_id\nint(10) unsigned\nYES\nMUL\nNULL\nticket_number\nvarchar(20)\nNO\nUNI\nNULL\nsubject\nvarchar(255)\nNO\nNULL\ndescription\ntext\nNO\nNULL\ncategory\nenum(\'bug\',\'feature\',\'billing\',\'usability\',\'performance\',\'security\',\'other\')\nYES\nother\npriority\nenum(\'low\',\'medium\',\'high\',\'urgent\')\nYES\nMUL\nmedium\nstatus\nenum(\'new\',\'open\',\'in_progress\',\'awaiting_info\',\'resolved\',\'closed\')\nYES\nnew\nos_name\nvarchar(50)\nYES\nNULL\nos_version\nvarchar(50)\nYES\nNULL\nbrowser_name\nvarchar(50)\nYES\nNULL\nbrowser_version\nvarchar(50)\nYES\nNULL\nscreen_resolution\nvarchar(20)\nYES\nNULL\nuser_agent\ntext\nYES\nNULL\ntimezone\nvarchar(50)\nYES\nNULL\nanydesk_id\nvarchar(20)\nYES\nNULL\nassigned_to\nint(10) unsigned\nYES\nMUL\nNULL\nassigned_at\ndatetime\nYES\nNULL\nresolved_at\ndatetime\nYES\nNULL\nclosed_at\ndatetime\nYES\nNULL\nsatisfaction_rating\ntinyint(4)\nYES\nNULL\ncreated_at\ntimestamp\nYES\nMUL\ncurrent_timestamp()\nupdated_at\ntimestamp\nYES\ncurrent_timestamp()\non update current_timestamp()\n', 'bug', 'medium', 'new', 'Linux', '', 'Chrome', '143', '1255x815', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', 'America/Sao_Paulo', NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-10 20:22:17', '2025-12-10 20:22:17'),
(6, 7, 13, 15, 'TKT-80F0C169', 'teste 06', 'Nullam nec turpis et arcu egestas commodo. Integer sit amet metus non tortor tincidunt interdum. Donec et metus mollis, ultricies est at, ultricies nulla. Morbi non libero magna. Praesent imperdiet magna ac ipsum cursus, ut fermentum turpis tincidunt.\n\nIn hac habitasse platea dictumst. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Nam eu nunc non augue tincidunt suscipit. Suspendisse potenti. Aliquam erat volutpat. Integer vel turpis sed purus scelerisque euismod.\n\nPraesent placerat, magna in vehicula vestibulum, felis urna cursus lorem, sed vestibulum quam eros vel libero. Vivamus commodo, odio sed fringilla pretium, sem nulla feugiat odio, in cursus elit dolor et ex.\n\n**Steps to Reproduce:**\nNullam nec turpis et arcu egestas commodo. Integer sit amet metus non tortor tincidunt interdum. Donec et metus mollis, ultricies est at, ultricies nulla. Morbi non libero magna. Praesent imperdiet magna ac ipsum cursus, ut fermentum turpis tincidunt.\n\nIn hac habitasse platea dictumst. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Nam eu nunc non augue tincidunt suscipit. Suspendisse potenti. Aliquam erat volutpat. Integer vel turpis sed purus scelerisque euismod.\n\nPraesent placerat, magna in vehicula vestibulum, felis urna cursus lorem, sed vestibulum quam eros vel libero. Vivamus commodo, odio sed fringilla pretium, sem nulla feugiat odio, in cursus elit dolor et ex.', 'bug', 'medium', 'new', 'Windows', '10/11', 'Chrome', '143', '1920x1080', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'America/Sao_Paulo', NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-10 20:29:03', '2025-12-10 20:29:03');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tasks`
--

DROP TABLE IF EXISTS `tasks`;
CREATE TABLE `tasks` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `project_id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'For subtasks',
  `assigned_to` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `estimated_hours` decimal(8,2) DEFAULT NULL,
  `actual_hours` decimal(8,2) DEFAULT NULL,
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `tasks`
--

INSERT INTO `tasks` (`id`, `tenant_id`, `project_id`, `parent_id`, `assigned_to`, `title`, `description`, `start_date`, `due_date`, `completed_at`, `estimated_hours`, `actual_hours`, `status`, `priority`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, 4, 'Foundation inspection', 'Final inspection of foundation work', '2024-01-20', '2024-01-25', NULL, 8.00, NULL, 'completed', 'high', 0, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(2, 1, 1, NULL, 4, 'Steel framework installation', 'Install main steel structure', '2024-02-01', '2024-03-15', NULL, 200.00, NULL, 'completed', 'high', 0, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(3, 1, 1, NULL, 4, 'Electrical rough-in', 'Electrical wiring for all floors', '2024-04-01', '2024-05-15', NULL, 150.00, NULL, 'completed', 'medium', 0, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(4, 1, 1, NULL, 4, 'HVAC system installation', 'Install heating and cooling systems', '2024-05-01', '2024-06-30', NULL, 180.00, NULL, 'in_progress', 'high', 0, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(5, 1, 1, NULL, 4, 'Interior finishing', 'Drywall, painting, and trim work', '2024-07-01', '2024-09-30', NULL, 300.00, NULL, 'pending', 'medium', 0, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(6, 1, 2, NULL, 4, 'Site preparation', 'Clear and level site', '2024-03-01', '2024-03-15', NULL, 40.00, NULL, 'completed', 'high', 0, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(7, 1, 2, NULL, 4, 'Server room construction', 'Build server room with raised floors', '2024-04-01', '2024-06-30', NULL, 250.00, NULL, 'in_progress', 'urgent', 0, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(8, 1, 2, NULL, 4, 'Cooling system installation', 'Install precision cooling units', '2024-07-01', '2024-09-30', NULL, 200.00, NULL, 'pending', 'urgent', 0, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(9, 1, 3, NULL, 4, 'Demolition of old structure', 'Remove existing pavilion', '2024-06-01', '2024-06-15', NULL, 40.00, NULL, 'completed', 'medium', 0, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(10, 1, 3, NULL, 4, 'New pavilion construction', 'Build new covered pavilion', '2024-06-20', '2024-08-31', NULL, 160.00, NULL, 'completed', 'medium', 0, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(11, 1, 3, NULL, 4, 'Restroom renovation', 'Update restroom facilities', '2024-08-01', '2024-09-30', NULL, 100.00, NULL, 'in_progress', 'medium', 0, '2025-12-08 13:55:36', '2025-12-08 13:55:36');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tenants`
--

DROP TABLE IF EXISTS `tenants`;
CREATE TABLE `tenants` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `subdomain` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'USA',
  `logo` varchar(255) DEFAULT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Tenant-specific settings' CHECK (json_valid(`settings`)),
  `status` enum('active','suspended','cancelled') DEFAULT 'active',
  `plan` varchar(50) DEFAULT 'basic',
  `stripe_customer_id` varchar(255) DEFAULT NULL,
  `stripe_subscription_id` varchar(100) DEFAULT NULL,
  `subscription_plan_id` int(10) UNSIGNED DEFAULT NULL,
  `subscription_status` enum('trialing','active','past_due','canceled','suspended','incomplete') DEFAULT NULL,
  `subscription_started_at` datetime DEFAULT NULL,
  `subscription_ends_at` datetime DEFAULT NULL,
  `trial_ends_at` datetime DEFAULT NULL,
  `user_limit` int(11) DEFAULT 3,
  `extra_seats` int(11) DEFAULT 0,
  `billing_email` varchar(255) DEFAULT NULL,
  `grace_period_ends_at` datetime DEFAULT NULL COMMENT 'When past_due access expires',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `tenants`
--

INSERT INTO `tenants` (`id`, `name`, `subdomain`, `email`, `phone`, `address`, `city`, `state`, `zip_code`, `country`, `logo`, `settings`, `status`, `plan`, `stripe_customer_id`, `stripe_subscription_id`, `subscription_plan_id`, `subscription_status`, `subscription_started_at`, `subscription_ends_at`, `trial_ends_at`, `user_limit`, `extra_seats`, `billing_email`, `grace_period_ends_at`, `created_at`, `updated_at`) VALUES
(1, 'Acme Construction Co.', 'acme', 'admin@acmeconstruction.com', '(555) 123-4567', '123 Builder Lane', 'Austin', 'TX', '78701', 'USA', NULL, NULL, 'active', 'professional', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 0, NULL, NULL, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(7, 'All Remodeling Services', 'allremodelingservices', 'daianeradol@gmail.com', NULL, NULL, NULL, NULL, NULL, 'USA', NULL, NULL, 'active', 'basic', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 0, NULL, NULL, '2025-12-08 14:40:58', '2025-12-08 14:40:58'),
(8, 'teste1', 'teste1', 'daianeradol+1@gmail.com', NULL, NULL, NULL, NULL, NULL, 'USA', NULL, NULL, 'active', 'basic', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 0, NULL, NULL, '2025-12-08 14:52:45', '2025-12-08 14:52:45'),
(9, 'Teste02', 'teste02', 'teste02@teste02.com', NULL, NULL, NULL, NULL, NULL, 'USA', NULL, NULL, 'active', 'basic', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 0, NULL, NULL, '2025-12-08 15:25:34', '2025-12-08 15:25:34'),
(10, 'Test Construction Co', 'test-construction-co', 'test@example.com', NULL, NULL, NULL, NULL, NULL, 'USA', NULL, NULL, 'active', 'team', 'cus_TaMFqJirGasJ6N', 'sub_1SdBWyK5swNIbm5ceeYU3b0z', 1, 'trialing', '2025-12-11 10:11:52', NULL, '2025-12-25 10:11:48', 3, 0, 'test@example.com', NULL, '2025-12-11 15:11:52', '2025-12-11 15:11:52');

-- --------------------------------------------------------

--
-- Estrutura para tabela `ticket_attachments`
--

DROP TABLE IF EXISTS `ticket_attachments`;
CREATE TABLE `ticket_attachments` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `ticket_id` int(10) UNSIGNED NOT NULL,
  `message_id` int(10) UNSIGNED DEFAULT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(10) UNSIGNED NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `ticket_messages`
--

DROP TABLE IF EXISTS `ticket_messages`;
CREATE TABLE `ticket_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `ticket_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `is_internal` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `ticket_messages`
--

INSERT INTO `ticket_messages` (`id`, `tenant_id`, `ticket_id`, `user_id`, `message`, `is_internal`, `created_at`, `updated_at`) VALUES
(1, 7, 6, 13, 'Nullam nec turpis et arcu egestas commodo. Integer sit amet metus non tortor tincidunt interdum. Donec et metus mollis, ultricies est at, ultricies nulla. Morbi non libero magna. Praesent imperdiet magna ac ipsum cursus, ut fermentum turpis tincidunt.\n\nIn hac habitasse platea dictumst. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Nam eu nunc non augue tincidunt suscipit. Suspendisse potenti. Aliquam erat volutpat. Integer vel turpis sed purus scelerisque euismod.\n\nPraesent placerat, magna in vehicula vestibulum, felis urna cursus lorem, sed vestibulum quam eros vel libero. Vivamus commodo, odio sed fringilla pretium, sem nulla feugiat odio, in cursus elit dolor et ex.\n\n**Steps to Reproduce:**\nNullam nec turpis et arcu egestas commodo. Integer sit amet metus non tortor tincidunt interdum. Donec et metus mollis, ultricies est at, ultricies nulla. Morbi non libero magna. Praesent imperdiet magna ac ipsum cursus, ut fermentum turpis tincidunt.\n\nIn hac habitasse platea dictumst. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Nam eu nunc non augue tincidunt suscipit. Suspendisse potenti. Aliquam erat volutpat. Integer vel turpis sed purus scelerisque euismod.\n\nPraesent placerat, magna in vehicula vestibulum, felis urna cursus lorem, sed vestibulum quam eros vel libero. Vivamus commodo, odio sed fringilla pretium, sem nulla feugiat odio, in cursus elit dolor et ex.', 0, '2025-12-10 20:29:03', '2025-12-10 20:29:03');

-- --------------------------------------------------------

--
-- Estrutura para tabela `time_logs`
--

DROP TABLE IF EXISTS `time_logs`;
CREATE TABLE `time_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `employee_id` int(10) UNSIGNED NOT NULL,
  `project_id` int(10) UNSIGNED DEFAULT NULL,
  `task_id` int(10) UNSIGNED DEFAULT NULL,
  `log_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `hours` decimal(5,2) NOT NULL,
  `break_hours` decimal(5,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `is_overtime` tinyint(1) DEFAULT 0,
  `billing_rate` decimal(10,2) DEFAULT NULL,
  `billable` tinyint(1) DEFAULT 1,
  `approved` tinyint(1) DEFAULT 0,
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `timer_started_at` timestamp NULL DEFAULT NULL COMMENT 'For active timer tracking',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `time_logs`
--

INSERT INTO `time_logs` (`id`, `tenant_id`, `employee_id`, `project_id`, `task_id`, `log_date`, `start_time`, `end_time`, `hours`, `break_hours`, `description`, `is_overtime`, `billing_rate`, `billable`, `approved`, `approved_by`, `approved_at`, `timer_started_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, NULL, '2025-12-07', '07:00:00', '16:00:00', 8.00, 0.00, 'HVAC ductwork installation', 0, NULL, 1, 1, NULL, NULL, NULL, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(2, 1, 2, 1, NULL, '2025-12-07', '07:30:00', '16:30:00', 8.00, 0.00, 'Electrical panel installation', 0, NULL, 1, 1, NULL, NULL, NULL, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(3, 1, 5, 1, NULL, '2025-12-07', '08:00:00', '18:00:00', 9.00, 0.00, 'HVAC unit setup - overtime', 1, NULL, 1, 1, NULL, NULL, NULL, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(4, 1, 1, 2, NULL, '2025-12-06', '07:00:00', '15:00:00', 7.00, 0.00, 'Server room framing', 0, NULL, 1, 1, NULL, NULL, NULL, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(5, 1, 3, 3, NULL, '2025-12-06', '08:00:00', '17:00:00', 8.00, 0.00, 'Restroom plumbing', 0, NULL, 1, 1, NULL, NULL, NULL, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(6, 1, 4, 1, NULL, '2025-12-05', '07:00:00', '16:00:00', 8.00, 0.00, 'Interior trim work', 0, NULL, 1, 0, NULL, NULL, NULL, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(7, 1, 1, 1, NULL, '2025-12-08', '07:00:00', '12:00:00', 5.00, 0.00, 'Morning shift - continuing HVAC', 0, NULL, 1, 0, NULL, NULL, NULL, '2025-12-08 13:55:36', '2025-12-08 13:55:36');

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `two_factor_enabled` tinyint(1) DEFAULT 0,
  `two_factor_secret` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'User preferences (theme, language, etc.)' CHECK (json_valid(`preferences`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `tenant_id`, `role_id`, `first_name`, `last_name`, `email`, `password`, `phone`, `avatar`, `two_factor_enabled`, `two_factor_secret`, `email_verified_at`, `last_login_at`, `status`, `preferences`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'John', 'Admin', 'admin@acmeconstruction.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(555) 123-4567', NULL, 0, NULL, NULL, NULL, 'active', NULL, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(2, 1, 2, 'Sarah', 'Manager', 'sarah@acmeconstruction.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(555) 234-5678', NULL, 0, NULL, NULL, NULL, 'active', NULL, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(3, 1, 3, 'Mike', 'Accountant', 'mike@acmeconstruction.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(555) 345-6789', NULL, 0, NULL, NULL, NULL, 'active', NULL, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(4, 1, 4, 'Bob', 'Contractor', 'bob@acmeconstruction.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(555) 456-7890', NULL, 0, NULL, NULL, NULL, 'active', NULL, '2025-12-08 13:55:36', '2025-12-08 13:55:36'),
(10, 7, 10, 'Daiane Radovanski', 'Olliveira', 'daianeradol@gmail.com', '$2y$12$H6YbwyZNKIwXq01jV1MQDeEnXpyddypYXZ5zRbkVmzkH6IJGAsBcy', '4049257024', NULL, 0, NULL, NULL, '2026-02-12 11:55:11', 'active', NULL, '2025-12-08 14:40:58', '2026-02-12 16:55:11'),
(11, 8, 1, 'Daiane', 'Oliveira', 'daianeradol+1@gmail.com', '$2y$12$sLzrtFpZ1gVpv4dMQOCb9uxzUScjeOXShDR3JrrvpgQENRZxmQBpO', NULL, NULL, 0, NULL, NULL, NULL, 'active', NULL, '2025-12-08 14:52:45', '2025-12-08 14:52:45'),
(12, 9, 1, 'Teste02', 'Teste02', 'teste02@teste02.com', '$2y$12$Mi65mDzG5JEJgjYFaCSoJOeygpEpZZSbwvzY/V0ScYVPHzquIEZxK', NULL, NULL, 0, NULL, NULL, NULL, 'active', NULL, '2025-12-08 15:25:34', '2025-12-08 15:25:34'),
(13, 7, 10, 'Fabricio Santos', 'Oliveira', 'fabriciooliveiraoficial@hotmail.com', '$2y$10$4GEiwk8QjJUYobPZzX/gXun6Y4FiSw85PaW/1lMvuRrDHvtcYHkku', NULL, NULL, 0, NULL, '2025-12-09 21:55:17', '2025-12-12 16:02:22', 'active', NULL, '2025-12-10 02:55:17', '2025-12-12 21:02:22'),
(14, 7, 11, 'Amanda Radovanski', 'Oliveira', 'dinharadol@gmail.com', '$2y$10$StGgJVn7n55QLv8JopI5/.97C1EElMQXe8PkylMzTazujp.AkQ59K', NULL, NULL, 0, NULL, '2025-12-10 13:03:27', '2025-12-10 14:51:15', 'active', NULL, '2025-12-10 18:03:27', '2025-12-10 19:51:15'),
(15, 10, 13, 'John', 'Doe', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 0, NULL, NULL, '2025-12-18 19:04:55', 'active', NULL, '2025-12-11 15:11:52', '2025-12-19 00:04:55'),
(16, 7, 10, 'Jose', 'Borges', 'remodeling@allremodelingservices.com', '$2y$10$fm/Dl7mKzfVvvonBGZAhY.OvcO6P3aKNTV1ABvnHHEuqfrZzbE0vO', NULL, NULL, 0, NULL, '2026-02-10 07:00:41', '2026-02-11 08:04:27', 'active', NULL, '2026-02-10 12:00:41', '2026-02-11 13:04:27');

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_invitations`
--

DROP TABLE IF EXISTS `user_invitations`;
CREATE TABLE `user_invitations` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL,
  `token` varchar(64) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `invited_by` int(10) UNSIGNED DEFAULT NULL,
  `message` text DEFAULT NULL COMMENT 'Optional personal message in invitation',
  `status` enum('pending','accepted','expired','cancelled') DEFAULT 'pending',
  `expires_at` timestamp NOT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `user_invitations`
--

INSERT INTO `user_invitations` (`id`, `tenant_id`, `email`, `role_id`, `token`, `first_name`, `last_name`, `invited_by`, `message`, `status`, `expires_at`, `accepted_at`, `created_at`, `updated_at`) VALUES
(1, 7, 'fabriciooliveiraoficial@hotmail.com', 11, '8126cf9e62b4a11df0a1b51df64d99ae5d6f21657d8f85df333c58db492f194a', 'Fabricio Santos', 'Oliveira', NULL, 'Acesso sistema de gestão', 'accepted', '2025-12-16 21:51:04', '2025-12-09 21:55:17', '2025-12-10 02:41:13', '2025-12-10 02:55:17'),
(2, 7, 'dinharadol@gmail.com', 11, '0c4a042f4e80b4c1698cc1c5d3d6086faab8060f2c836a9c4c3eec83e20d78e1', 'Amanda Radovanski', 'Oliveira', NULL, 'Lançamentos financeiros', 'accepted', '2025-12-17 12:54:47', '2025-12-10 13:03:27', '2025-12-10 17:54:47', '2025-12-10 18:03:27'),
(3, 7, 'jose.borgesjunior@icloud.com', 11, '4a2733c1bec8af178b87864a574a5fbc39842afc4d8e6753ac09951cf26b23ee', 'Jose ', 'Borges', NULL, NULL, 'expired', '2026-02-03 09:35:44', NULL, '2026-01-26 19:10:55', '2026-02-10 11:55:53'),
(4, 7, 'antonioradovanski@gmail.com', 11, '890175df6feaa4ac95f023cbe836b557fe76589c4090f6b977d842a774414cdb', 'Antonio', 'Radovanski', NULL, NULL, 'expired', '2026-02-03 09:35:41', NULL, '2026-01-26 19:11:34', '2026-02-10 11:55:53'),
(5, 7, 'remodeling@allremodelingservices.com', 11, '2ce518e3f941966fd6de2fac5b95eefa281b1a1dd649794bc8f5ebe55ec71b8b', 'Jose', 'Borges', NULL, NULL, 'expired', '2026-02-03 17:57:33', NULL, '2026-01-27 22:57:33', '2026-02-09 21:30:58'),
(6, 7, 'remodeling@allremodelingservices.com', 10, 'bb05d3e7937da6d09026655d398e1ab8a65a6f581e951adee0399dcc78c86ed6', 'Jose', 'Borges', NULL, NULL, 'accepted', '2026-02-17 06:59:58', '2026-02-10 07:00:41', '2026-02-10 11:59:58', '2026-02-10 12:00:41');

-- --------------------------------------------------------

--
-- Estrutura para tabela `vendors`
--

DROP TABLE IF EXISTS `vendors`;
CREATE TABLE `vendors` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `contact_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `zip` varchar(20) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `payment_terms` varchar(100) DEFAULT 'Net 30',
  `tax_id` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `version_releases`
--

DROP TABLE IF EXISTS `version_releases`;
CREATE TABLE `version_releases` (
  `id` int(10) UNSIGNED NOT NULL,
  `version` varchar(20) NOT NULL,
  `build` varchar(50) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL COMMENT 'Release name e.g. "Summer Update"',
  `release_notes` text DEFAULT NULL COMMENT 'Summary of the release',
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of new features' CHECK (json_valid(`features`)),
  `fixes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of bug fixes' CHECK (json_valid(`fixes`)),
  `improvements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of improvements' CHECK (json_valid(`improvements`)),
  `is_published` tinyint(1) DEFAULT 0,
  `force_update` tinyint(1) DEFAULT 0,
  `force_update_message` text DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `published_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `version_releases`
--

INSERT INTO `version_releases` (`id`, `version`, `build`, `name`, `release_notes`, `features`, `fixes`, `improvements`, `is_published`, `force_update`, `force_update_message`, `published_at`, `published_by`, `created_at`, `updated_at`) VALUES
(1, '1.0.0', '20241210', 'Initial Release', 'First production release of BuildFlow ERP', '[\"Initial release of BuildFlow ERP\", \"Project management dashboard\", \"Client and invoice management\", \"Time tracking with live timer\", \"Expense tracking and approvals\", \"Multi-tenant architecture\"]', '[]', '[]', 1, 0, NULL, '2025-12-12 19:47:52', NULL, '2025-12-12 19:47:52', '2025-12-12 19:47:52'),
(2, '1.0.1', '20251212', 'Update Dec 12, 2025', 'System update', '[\"Bug fixes and improvements\"]', '[]', '[]', 1, 0, NULL, '2025-12-12 15:28:47', 4, '2025-12-12 20:28:47', '2025-12-12 20:28:47');

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_employee_hours`
-- (Veja abaixo para a visão atual)
--
DROP TABLE IF EXISTS `v_employee_hours`;
CREATE TABLE `v_employee_hours` (
`employee_id` int(10) unsigned
,`tenant_id` int(10) unsigned
,`employee_name` varchar(201)
,`payment_type` enum('hourly','daily','salary','project','commission')
,`hourly_rate` decimal(10,2)
,`month` varchar(7)
,`total_hours` decimal(27,2)
,`overtime_hours` decimal(27,2)
,`billable_hours` decimal(27,2)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_project_financials`
-- (Veja abaixo para a visão atual)
--
DROP TABLE IF EXISTS `v_project_financials`;
CREATE TABLE `v_project_financials` (
`id` int(10) unsigned
,`tenant_id` int(10) unsigned
,`name` varchar(255)
,`client_id` int(10) unsigned
,`client_name` varchar(255)
,`status` enum('planning','in_progress','on_hold','completed','cancelled')
,`progress` tinyint(3) unsigned
,`total_budget` decimal(37,2)
,`total_spent` decimal(37,2)
,`budget_remaining` decimal(38,2)
,`total_hours` decimal(27,2)
);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `accounting_periods`
--
ALTER TABLE `accounting_periods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `idx_dates` (`start_date`,`end_date`),
  ADD KEY `closed_by` (`closed_by`);

--
-- Índices de tabela `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created` (`created_at`);

--
-- Índices de tabela `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `idx_project` (`project_id`),
  ADD KEY `idx_category` (`category`);

--
-- Índices de tabela `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_tenant_code` (`tenant_id`,`code`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `idx_code` (`code`);

--
-- Índices de tabela `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `idx_status` (`status`);

--
-- Índices de tabela `custom_categories`
--
ALTER TABLE `custom_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_tenant_type_name` (`tenant_id`,`type`,`name`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `idx_type` (`type`);

--
-- Índices de tabela `developers`
--
ALTER TABLE `developers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `idx_project` (`project_id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Índices de tabela `email_automations`
--
ALTER TABLE `email_automations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_tenant_trigger` (`tenant_id`,`trigger_event`),
  ADD KEY `idx_tenant_enabled` (`tenant_id`,`is_enabled`),
  ADD KEY `idx_template` (`template_id`);

--
-- Índices de tabela `email_bounces`
--
ALTER TABLE `email_bounces`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `idx_email` (`email`);

--
-- Índices de tabela `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant_date` (`tenant_id`,`sent_at`),
  ADD KEY `idx_tenant_context` (`tenant_id`,`context_type`,`context_id`),
  ADD KEY `idx_message_id` (`message_id`),
  ADD KEY `idx_queue` (`queue_id`);

--
-- Índices de tabela `email_queue`
--
ALTER TABLE `email_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant_status` (`tenant_id`,`status`),
  ADD KEY `idx_status_priority` (`status`,`priority`,`scheduled_at`),
  ADD KEY `idx_context` (`tenant_id`,`context_type`,`context_id`),
  ADD KEY `idx_template` (`template_id`);

--
-- Índices de tabela `email_settings`
--
ALTER TABLE `email_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tenant_id` (`tenant_id`),
  ADD KEY `idx_tenant` (`tenant_id`);

--
-- Índices de tabela `email_templates`
--
ALTER TABLE `email_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_tenant_slug` (`tenant_id`,`slug`),
  ADD KEY `idx_tenant_active` (`tenant_id`,`is_active`);

--
-- Índices de tabela `email_unsubscribes`
--
ALTER TABLE `email_unsubscribes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_tenant_email` (`tenant_id`,`email`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `idx_email` (`email`);

--
-- Índices de tabela `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_employee_id` (`employee_id`),
  ADD KEY `idx_status` (`status`);

--
-- Índices de tabela `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_project_id` (`assigned_project_id`),
  ADD KEY `assigned_employee_id` (`assigned_employee_id`),
  ADD KEY `idx_equipment_tenant` (`tenant_id`),
  ADD KEY `idx_equipment_status` (`status`);

--
-- Índices de tabela `equipment_maintenance`
--
ALTER TABLE `equipment_maintenance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipment_id` (`equipment_id`);

--
-- Índices de tabela `estimates`
--
ALTER TABLE `estimates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `converted_invoice_id` (`converted_invoice_id`),
  ADD KEY `idx_estimate_tenant` (`tenant_id`),
  ADD KEY `idx_estimate_client` (`client_id`),
  ADD KEY `idx_estimate_status` (`status`);

--
-- Índices de tabela `estimate_items`
--
ALTER TABLE `estimate_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `estimate_id` (`estimate_id`);

--
-- Índices de tabela `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `idx_project` (`project_id`),
  ADD KEY `idx_budget` (`budget_id`),
  ADD KEY `idx_date` (`expense_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_employee` (`employee_id`),
  ADD KEY `idx_expenses_journal_entry` (`journal_entry_id`);

--
-- Índices de tabela `inventory_categories`
--
ALTER TABLE `inventory_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Índices de tabela `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `idx_supplier` (`supplier_id`),
  ADD KEY `idx_sku` (`sku`),
  ADD KEY `idx_barcode` (`barcode`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_status` (`status`);

--
-- Índices de tabela `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `idx_item` (`item_id`),
  ADD KEY `idx_project` (`project_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_date` (`transaction_date`),
  ADD KEY `user_id` (`user_id`);

--
-- Índices de tabela `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_tenant_invoice` (`tenant_id`,`invoice_number`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `idx_project` (`project_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_due_date` (`due_date`);

--
-- Índices de tabela `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_invoice` (`invoice_id`);

--
-- Índices de tabela `journal_entries`
--
ALTER TABLE `journal_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `idx_date` (`entry_date`),
  ADD KEY `idx_ref` (`reference_number`),
  ADD KEY `idx_entity` (`entity_type`,`entity_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Índices de tabela `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_entry` (`journal_entry_id`),
  ADD KEY `idx_account` (`account_id`);

--
-- Índices de tabela `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_read` (`read_at`),
  ADD KEY `idx_type` (`type`);

--
-- Índices de tabela `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `idx_invoice` (`invoice_id`),
  ADD KEY `idx_date` (`payment_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_payments_journal_entry` (`journal_entry_id`);

--
-- Índices de tabela `payroll_periods`
--
ALTER TABLE `payroll_periods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `idx_dates` (`period_start`,`period_end`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `processed_by` (`processed_by`);

--
-- Índices de tabela `payroll_records`
--
ALTER TABLE `payroll_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `idx_period` (`payroll_period_id`),
  ADD KEY `idx_employee` (`employee_id`),
  ADD KEY `idx_status` (`status`);

--
-- Índices de tabela `pending_signups`
--
ALTER TABLE `pending_signups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `checkout_session_id` (`checkout_session_id`),
  ADD KEY `idx_session` (`checkout_session_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Índices de tabela `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `idx_manager` (`manager_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_dates` (`start_date`,`end_date`);

--
-- Índices de tabela `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `idx_po_tenant` (`tenant_id`),
  ADD KEY `idx_po_vendor` (`vendor_id`),
  ADD KEY `idx_po_status` (`status`);

--
-- Índices de tabela `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_order_id` (`purchase_order_id`),
  ADD KEY `inventory_item_id` (`inventory_item_id`);

--
-- Índices de tabela `remote_sessions`
--
ALTER TABLE `remote_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `developer_id` (`developer_id`),
  ADD KEY `idx_ticket` (`ticket_id`),
  ADD KEY `idx_tenant` (`tenant_id`);

--
-- Índices de tabela `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant` (`tenant_id`);

--
-- Índices de tabela `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_tenant_key` (`tenant_id`,`key`),
  ADD KEY `idx_tenant` (`tenant_id`);

--
-- Índices de tabela `stripe_connections`
--
ALTER TABLE `stripe_connections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_tenant` (`tenant_id`),
  ADD KEY `idx_stripe_account` (`stripe_account_id`),
  ADD KEY `idx_status` (`status`);

--
-- Índices de tabela `stripe_customers`
--
ALTER TABLE `stripe_customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_tenant_client` (`tenant_id`,`client_id`),
  ADD UNIQUE KEY `unique_stripe_customer` (`tenant_id`,`stripe_customer_id`),
  ADD KEY `idx_stripe_customer` (`stripe_customer_id`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `idx_client` (`client_id`);

--
-- Índices de tabela `stripe_payment_intents`
--
ALTER TABLE `stripe_payment_intents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_payment_intent` (`tenant_id`,`stripe_payment_intent_id`),
  ADD KEY `idx_invoice` (`invoice_id`),
  ADD KEY `idx_status` (`tenant_id`,`status`),
  ADD KEY `idx_tenant` (`tenant_id`);

--
-- Índices de tabela `stripe_prices`
--
ALTER TABLE `stripe_prices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_stripe_price` (`tenant_id`,`stripe_price_id`),
  ADD KEY `idx_product` (`tenant_id`,`stripe_product_id`),
  ADD KEY `idx_tenant` (`tenant_id`);

--
-- Índices de tabela `stripe_products`
--
ALTER TABLE `stripe_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_stripe_product` (`tenant_id`,`stripe_product_id`),
  ADD KEY `idx_active` (`tenant_id`,`active`),
  ADD KEY `idx_tenant` (`tenant_id`);

--
-- Índices de tabela `stripe_subscriptions`
--
ALTER TABLE `stripe_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_subscription` (`tenant_id`,`stripe_subscription_id`),
  ADD KEY `idx_customer` (`stripe_customer_id`),
  ADD KEY `idx_status` (`tenant_id`,`status`),
  ADD KEY `idx_tenant` (`tenant_id`);

--
-- Índices de tabela `stripe_webhook_events`
--
ALTER TABLE `stripe_webhook_events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_event` (`stripe_event_id`),
  ADD KEY `idx_tenant_type` (`tenant_id`,`event_type`),
  ADD KEY `idx_processed` (`processed`,`created_at`),
  ADD KEY `idx_stripe_account` (`stripe_account_id`);

--
-- Índices de tabela `subscription_history`
--
ALTER TABLE `subscription_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `idx_event_type` (`event_type`),
  ADD KEY `idx_created` (`created_at`);

--
-- Índices de tabela `subscription_plans`
--
ALTER TABLE `subscription_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_status` (`status`);

--
-- Índices de tabela `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `idx_status` (`status`);

--
-- Índices de tabela `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_ticket_number` (`ticket_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `idx_tenant_status` (`tenant_id`,`status`),
  ADD KEY `idx_assigned` (`assigned_to`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_created` (`created_at`);

--
-- Índices de tabela `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `idx_project` (`project_id`),
  ADD KEY `idx_parent` (`parent_id`),
  ADD KEY `idx_assigned` (`assigned_to`),
  ADD KEY `idx_status` (`status`);

--
-- Índices de tabela `tenants`
--
ALTER TABLE `tenants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subdomain` (`subdomain`),
  ADD KEY `idx_subdomain` (`subdomain`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_subscription_status` (`subscription_status`),
  ADD KEY `idx_stripe_subscription` (`stripe_subscription_id`);

--
-- Índices de tabela `ticket_attachments`
--
ALTER TABLE `ticket_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `message_id` (`message_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_ticket` (`ticket_id`),
  ADD KEY `idx_tenant` (`tenant_id`);

--
-- Índices de tabela `ticket_messages`
--
ALTER TABLE `ticket_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_ticket` (`ticket_id`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_tenant` (`tenant_id`);

--
-- Índices de tabela `time_logs`
--
ALTER TABLE `time_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `idx_employee` (`employee_id`),
  ADD KEY `idx_project` (`project_id`),
  ADD KEY `idx_task` (`task_id`),
  ADD KEY `idx_date` (`log_date`),
  ADD KEY `idx_approved` (`approved`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_tenant_email` (`tenant_id`,`email`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `idx_role` (`role_id`);

--
-- Índices de tabela `user_invitations`
--
ALTER TABLE `user_invitations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_expires` (`expires_at`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `invited_by` (`invited_by`);

--
-- Índices de tabela `vendors`
--
ALTER TABLE `vendors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vendor_tenant` (`tenant_id`),
  ADD KEY `idx_vendor_status` (`status`);

--
-- Índices de tabela `version_releases`
--
ALTER TABLE `version_releases`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_version` (`version`),
  ADD KEY `idx_published` (`is_published`),
  ADD KEY `idx_published_at` (`published_at`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `accounting_periods`
--
ALTER TABLE `accounting_periods`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `budgets`
--
ALTER TABLE `budgets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de tabela `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT de tabela `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de tabela `custom_categories`
--
ALTER TABLE `custom_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `developers`
--
ALTER TABLE `developers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `email_automations`
--
ALTER TABLE `email_automations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `email_bounces`
--
ALTER TABLE `email_bounces`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `email_queue`
--
ALTER TABLE `email_queue`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `email_settings`
--
ALTER TABLE `email_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `email_templates`
--
ALTER TABLE `email_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `email_unsubscribes`
--
ALTER TABLE `email_unsubscribes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT de tabela `equipment`
--
ALTER TABLE `equipment`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `equipment_maintenance`
--
ALTER TABLE `equipment_maintenance`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `estimates`
--
ALTER TABLE `estimates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `estimate_items`
--
ALTER TABLE `estimate_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=491;

--
-- AUTO_INCREMENT de tabela `inventory_categories`
--
ALTER TABLE `inventory_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT de tabela `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT de tabela `journal_entries`
--
ALTER TABLE `journal_entries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de tabela `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT de tabela `payroll_periods`
--
ALTER TABLE `payroll_periods`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `payroll_records`
--
ALTER TABLE `payroll_records`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pending_signups`
--
ALTER TABLE `pending_signups`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT de tabela `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `remote_sessions`
--
ALTER TABLE `remote_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de tabela `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de tabela `stripe_connections`
--
ALTER TABLE `stripe_connections`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `stripe_customers`
--
ALTER TABLE `stripe_customers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `stripe_payment_intents`
--
ALTER TABLE `stripe_payment_intents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `stripe_prices`
--
ALTER TABLE `stripe_prices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `stripe_products`
--
ALTER TABLE `stripe_products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `stripe_subscriptions`
--
ALTER TABLE `stripe_subscriptions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `stripe_webhook_events`
--
ALTER TABLE `stripe_webhook_events`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `subscription_history`
--
ALTER TABLE `subscription_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `subscription_plans`
--
ALTER TABLE `subscription_plans`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de tabela `tenants`
--
ALTER TABLE `tenants`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `ticket_attachments`
--
ALTER TABLE `ticket_attachments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `ticket_messages`
--
ALTER TABLE `ticket_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `time_logs`
--
ALTER TABLE `time_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `user_invitations`
--
ALTER TABLE `user_invitations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `vendors`
--
ALTER TABLE `vendors`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `version_releases`
--
ALTER TABLE `version_releases`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

-- --------------------------------------------------------

--
-- Estrutura para view `v_employee_hours`
--
DROP TABLE IF EXISTS `v_employee_hours`;

CREATE ALGORITHM=UNDEFINED  SQL SECURITY INVOKER VIEW `v_employee_hours`  AS SELECT `e`.`id` AS `employee_id`, `e`.`tenant_id` AS `tenant_id`, concat(`e`.`first_name`,' ',`e`.`last_name`) AS `employee_name`, `e`.`payment_type` AS `payment_type`, `e`.`hourly_rate` AS `hourly_rate`, date_format(`tl`.`log_date`,'%Y-%m') AS `month`, sum(`tl`.`hours`) AS `total_hours`, sum(case when `tl`.`is_overtime` then `tl`.`hours` else 0 end) AS `overtime_hours`, sum(case when `tl`.`billable` then `tl`.`hours` else 0 end) AS `billable_hours` FROM (`employees` `e` left join `time_logs` `tl` on(`e`.`id` = `tl`.`employee_id`)) WHERE `e`.`status` = 'active' GROUP BY `e`.`id`, date_format(`tl`.`log_date`,'%Y-%m') ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_project_financials`
--
DROP TABLE IF EXISTS `v_project_financials`;

CREATE ALGORITHM=UNDEFINED  SQL SECURITY INVOKER VIEW `v_project_financials`  AS SELECT `p`.`id` AS `id`, `p`.`tenant_id` AS `tenant_id`, `p`.`name` AS `name`, `p`.`client_id` AS `client_id`, `c`.`name` AS `client_name`, `p`.`status` AS `status`, `p`.`progress` AS `progress`, coalesce(sum(`b`.`budgeted_amount`),0) AS `total_budget`, coalesce(sum(`b`.`spent_amount`),0) AS `total_spent`, coalesce(sum(`b`.`budgeted_amount`),0) - coalesce(sum(`b`.`spent_amount`),0) AS `budget_remaining`, (select coalesce(sum(`time_logs`.`hours`),0) from `time_logs` where `time_logs`.`project_id` = `p`.`id`) AS `total_hours` FROM ((`projects` `p` left join `clients` `c` on(`p`.`client_id` = `c`.`id`)) left join `budgets` `b` on(`p`.`id` = `b`.`project_id`)) GROUP BY `p`.`id` ;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `accounting_periods`
--
ALTER TABLE `accounting_periods`
  ADD CONSTRAINT `accounting_periods_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `accounting_periods_ibfk_2` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `activity_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `budgets`
--
ALTER TABLE `budgets`
  ADD CONSTRAINT `budgets_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `budgets_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  ADD CONSTRAINT `chart_of_accounts_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `clients`
--
ALTER TABLE `clients`
  ADD CONSTRAINT `clients_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `custom_categories`
--
ALTER TABLE `custom_categories`
  ADD CONSTRAINT `custom_categories_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `documents_ibfk_3` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employees_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `equipment`
--
ALTER TABLE `equipment`
  ADD CONSTRAINT `equipment_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `equipment_ibfk_2` FOREIGN KEY (`assigned_project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `equipment_ibfk_3` FOREIGN KEY (`assigned_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `equipment_maintenance`
--
ALTER TABLE `equipment_maintenance`
  ADD CONSTRAINT `equipment_maintenance_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `estimates`
--
ALTER TABLE `estimates`
  ADD CONSTRAINT `estimates_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `estimates_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `estimates_ibfk_3` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `estimates_ibfk_4` FOREIGN KEY (`converted_invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `estimate_items`
--
ALTER TABLE `estimate_items`
  ADD CONSTRAINT `estimate_items_ibfk_1` FOREIGN KEY (`estimate_id`) REFERENCES `estimates` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `expenses_ibfk_3` FOREIGN KEY (`budget_id`) REFERENCES `budgets` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `expenses_ibfk_4` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `expenses_ibfk_5` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `expenses_ibfk_6` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `inventory_categories`
--
ALTER TABLE `inventory_categories`
  ADD CONSTRAINT `inventory_categories_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_categories_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `inventory_categories` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `inventory_items_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_items_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD CONSTRAINT `inventory_transactions_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_transactions_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_transactions_ibfk_3` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_transactions_ibfk_4` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  ADD CONSTRAINT `invoices_ibfk_3` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `journal_entries`
--
ALTER TABLE `journal_entries`
  ADD CONSTRAINT `journal_entries_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `journal_entries_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  ADD CONSTRAINT `journal_entry_lines_ibfk_1` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `journal_entry_lines_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`id`);

--
-- Restrições para tabelas `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `payroll_periods`
--
ALTER TABLE `payroll_periods`
  ADD CONSTRAINT `payroll_periods_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payroll_periods_ibfk_2` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `payroll_records`
--
ALTER TABLE `payroll_records`
  ADD CONSTRAINT `payroll_records_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payroll_records_ibfk_2` FOREIGN KEY (`payroll_period_id`) REFERENCES `payroll_periods` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payroll_records_ibfk_3` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `projects_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  ADD CONSTRAINT `projects_ibfk_3` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_orders_ibfk_2` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_orders_ibfk_3` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `purchase_order_items_ibfk_1` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_order_items_ibfk_2` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `remote_sessions`
--
ALTER TABLE `remote_sessions`
  ADD CONSTRAINT `remote_sessions_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `remote_sessions_ibfk_2` FOREIGN KEY (`developer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `roles`
--
ALTER TABLE `roles`
  ADD CONSTRAINT `roles_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `settings`
--
ALTER TABLE `settings`
  ADD CONSTRAINT `settings_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `suppliers`
--
ALTER TABLE `suppliers`
  ADD CONSTRAINT `suppliers_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD CONSTRAINT `support_tickets_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `support_tickets_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `support_tickets_ibfk_3` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `support_tickets_ibfk_4` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_3` FOREIGN KEY (`parent_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_4` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `ticket_attachments`
--
ALTER TABLE `ticket_attachments`
  ADD CONSTRAINT `ticket_attachments_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ticket_attachments_ibfk_2` FOREIGN KEY (`message_id`) REFERENCES `ticket_messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ticket_attachments_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `ticket_messages`
--
ALTER TABLE `ticket_messages`
  ADD CONSTRAINT `ticket_messages_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ticket_messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `time_logs`
--
ALTER TABLE `time_logs`
  ADD CONSTRAINT `time_logs_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `time_logs_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `time_logs_ibfk_3` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `time_logs_ibfk_4` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `time_logs_ibfk_5` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

--
-- Restrições para tabelas `user_invitations`
--
ALTER TABLE `user_invitations`
  ADD CONSTRAINT `user_invitations_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_invitations_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_invitations_ibfk_3` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `vendors`
--
ALTER TABLE `vendors`
  ADD CONSTRAINT `vendors_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
