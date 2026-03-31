-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : dim. 08 mars 2026 à 15:51
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `shop_management`
--

-- --------------------------------------------------------

--
-- Structure de la table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `articles`
--

CREATE TABLE `articles` (
  `id` int(11) NOT NULL,
  `barcode` varchar(50) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `purchase_price` decimal(10,2) NOT NULL,
  `percentage_of_sales_profit` decimal(5,2) DEFAULT 0.00,
  `wholesale_percentage` decimal(5,2) DEFAULT 0.00,
  `wholesale` decimal(10,2) DEFAULT 0.00,
  `sale_price` decimal(10,2) NOT NULL,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(20) DEFAULT NULL,
  `stock_alert_level` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `articles`
--

INSERT INTO `articles` (`id`, `barcode`, `reference`, `category_id`, `name`, `description`, `purchase_price`, `percentage_of_sales_profit`, `wholesale_percentage`, `wholesale`, `sale_price`, `tax_rate`, `unit`, `stock_alert_level`, `is_active`, `created_at`) VALUES
(1, '1001', 'LAP001', 1, 'Laptop Pro 15\"', 'High-performance laptop with 16GB RAM', 800.00, 0.00, 0.00, 0.00, 1200.00, 20.00, NULL, 10, 1, '2026-02-22 23:25:53'),
(2, '1002', 'LAP002', 1, 'Wireless Mouse', 'Ergonomic wireless mouse', 15.00, 0.00, 0.00, 0.00, 25.00, 20.00, NULL, 50, 1, '2026-02-22 23:25:53'),
(3, '1003', 'LAP003', 1, 'USB-C Hub', '7-in-1 USB hub with HDMI', 30.00, 0.00, 0.00, 0.00, 45.00, 20.00, NULL, 25, 1, '2026-02-22 23:25:53'),
(4, '2001', 'CLO001', 2, 'Cotton T-Shirt', 'Premium cotton t-shirt', 8.00, 0.00, 0.00, 0.00, 15.00, 20.00, NULL, 100, 1, '2026-02-22 23:25:53'),
(5, '2002', 'CLO002', 2, 'Denim Jeans', 'Classic blue denim jeans', 20.00, 0.00, 0.00, 0.00, 35.00, 20.00, NULL, 50, 1, '2026-02-22 23:25:53'),
(6, '2003', 'CLO003', 2, 'Winter Jacket', 'Warm winter jacket', 40.00, 0.00, 0.00, 0.00, 65.00, 20.00, NULL, 30, 1, '2026-02-22 23:25:53'),
(7, '3001', 'FOO001', 3, 'Coffee Beans', 'Premium arabica coffee beans', 12.00, 0.00, 0.00, 0.00, 20.00, 10.00, NULL, 20, 1, '2026-02-22 23:25:53'),
(8, '3002', 'FOO002', 3, 'Green Tea', 'Organic green tea leaves', 8.00, 0.00, 0.00, 0.00, 15.00, 10.00, NULL, 30, 1, '2026-02-22 23:25:53'),
(9, '3003', 'FOO003', 3, 'Mineral Water', 'Natural mineral water', 0.50, 0.00, 0.00, 0.00, 1.50, 10.00, NULL, 200, 1, '2026-02-22 23:25:53'),
(10, '4001', 'OFF001', 4, 'Notebook Set', 'Premium notebook set', 5.00, 0.00, 0.00, 0.00, 12.00, 20.00, NULL, 40, 1, '2026-02-22 23:25:53'),
(11, '4002', 'OFF002', 4, 'Desk Lamp', 'LED desk lamp with adjustable brightness', 15.00, 0.00, 0.00, 0.00, 30.00, 20.00, NULL, 20, 1, '2026-02-22 23:25:53'),
(12, '5001', 'HOM001', 5, 'Plant Pot', 'Ceramic plant pot with drainage', 8.00, 0.00, 0.00, 0.00, 15.00, 20.00, NULL, 60, 1, '2026-02-22 23:25:53'),
(13, '5002', 'HOM002', 5, 'Garden Tools Set', 'Basic gardening tools set', 25.00, 0.00, 0.00, 0.00, 45.00, 20.00, NULL, 15, 1, '2026-02-22 23:25:53');

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `categories`
--

INSERT INTO `categories` (`id`, `name`, `parent_id`, `description`) VALUES
(1, 'Electronics', NULL, NULL),
(2, 'Clothing', NULL, NULL),
(3, 'Food & Beverages', NULL, NULL),
(4, 'Office Supplies', NULL, NULL),
(5, 'Home & Garden', NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `charges`
--

CREATE TABLE `charges` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `charge_payments`
--

CREATE TABLE `charge_payments` (
  `id` int(11) NOT NULL,
  `charge_id` int(11) NOT NULL,
  `payment_mode_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `clients`
--

INSERT INTO `clients` (`id`, `name`, `phone`, `email`, `address`, `balance`, `created_at`) VALUES
(1, 'John Smith', '+1-555-0101', 'john.smith@email.com', '123 Main St, Anytown, USA', 0.00, '2026-02-22 23:25:53'),
(2, 'Sarah Johnson', '+1-555-0102', 'sarah.j@email.com', '456 Oak Ave, Somewhere, USA', 150.00, '2026-02-22 23:25:53'),
(3, 'Mike Davis', '+1-555-0103', 'mike.davis@email.com', '789 Pine Rd, Elsewhere, USA', 0.00, '2026-02-22 23:25:53'),
(4, 'Emily Wilson', '+1-555-0104', 'emily.w@email.com', '321 Elm St, Nowhere, USA', 75.00, '2026-02-22 23:25:53'),
(5, 'Robert Brown', '+1-555-0105', 'robert.brown@email.com', '654 Maple Dr, Anywhere, USA', 0.00, '2026-02-22 23:25:53'),
(6, 'Lisa Anderson', '+1-555-0106', 'lisa.a@email.com', '987 Cedar Ln, Everywhere, USA', 200.00, '2026-02-22 23:25:53'),
(7, 'David Martinez', '+1-555-0107', 'david.m@email.com', '147 Birch Way, Someplace, USA', 0.00, '2026-02-22 23:25:53'),
(8, 'Jennifer Taylor', '+1-555-0108', 'jennifer.t@email.com', '258 Spruce Ct, Anycity, USA', 100.00, '2026-02-22 23:25:53');

-- --------------------------------------------------------

--
-- Structure de la table `draft_items`
--

CREATE TABLE `draft_items` (
  `id` int(11) NOT NULL,
  `draft_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `total_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `draft_orders`
--

CREATE TABLE `draft_orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `document_type` enum('sale','invoice','quote') NOT NULL,
  `payment_mode_id` int(11) DEFAULT NULL,
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `status` enum('draft','confirmed') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `favorites`
--

CREATE TABLE `favorites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `entity_type` enum('sale','invoice','purchase','charge') NOT NULL,
  `entity_id` int(11) NOT NULL,
  `payment_mode_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `payments`
--

INSERT INTO `payments` (`id`, `entity_type`, `entity_id`, `payment_mode_id`, `amount`, `payment_date`, `created_at`) VALUES
(1, 'sale', 12, 1, 130.00, '2026-02-23', '2026-02-22 23:40:41'),
(2, 'sale', 13, 2, 1330.00, '2026-02-23', '2026-02-22 23:41:09');

-- --------------------------------------------------------

--
-- Structure de la table `payment_modes`
--

CREATE TABLE `payment_modes` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `payment_modes`
--

INSERT INTO `payment_modes` (`id`, `name`) VALUES
(1, 'Cash'),
(2, 'Check'),
(3, 'Bank Transfer'),
(4, 'Credit Card');

-- --------------------------------------------------------

--
-- Structure de la table `payment_situations`
--

CREATE TABLE `payment_situations` (
  `id` int(11) NOT NULL,
  `entity_type` enum('client','supplier') NOT NULL,
  `entity_id` int(11) NOT NULL,
  `balance` decimal(10,2) DEFAULT 0.00,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `purchases`
--

CREATE TABLE `purchases` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('pending','paid','partial') DEFAULT 'pending',
  `type_of_payment` enum('cash','check','transfer') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `purchases`
--

INSERT INTO `purchases` (`id`, `supplier_id`, `invoice_number`, `total_amount`, `paid_amount`, `status`, `type_of_payment`, `created_at`) VALUES
(1, 1, 'PUR-001', 5000.00, 5000.00, 'paid', 'transfer', '2024-08-15 08:30:00'),
(2, 2, 'PUR-002', 3000.00, 2000.00, 'partial', 'check', '2024-09-10 12:20:00'),
(3, 3, 'PUR-003', 2500.00, 2500.00, 'paid', 'cash', '2024-10-05 07:15:00'),
(4, 4, 'PUR-004', 1800.00, 1800.00, 'paid', 'transfer', '2024-11-12 10:45:00'),
(5, 5, 'PUR-005', 3200.00, 1600.00, 'partial', 'check', '2024-12-08 15:30:00'),
(6, 1, 'PUR-006', 4500.00, 4500.00, 'paid', 'transfer', '2025-01-10 12:20:00'),
(7, 2, 'PUR-007', 2800.00, 2800.00, 'paid', 'cash', '2025-01-25 09:45:00'),
(8, 3, 'PUR-008', 2200.00, 2200.00, 'paid', 'transfer', '2025-02-05 14:30:00');

-- --------------------------------------------------------

--
-- Structure de la table `purchase_items`
--

CREATE TABLE `purchase_items` (
  `id` int(11) NOT NULL,
  `purchase_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `purchase_items`
--

INSERT INTO `purchase_items` (`id`, `purchase_id`, `article_id`, `quantity`, `unit_price`, `total_price`) VALUES
(1, 1, 1, 5, 800.00, 4000.00),
(2, 1, 2, 20, 15.00, 300.00),
(3, 1, 3, 10, 30.00, 300.00),
(4, 2, 4, 50, 8.00, 400.00),
(5, 2, 5, 30, 20.00, 600.00),
(6, 2, 6, 20, 40.00, 800.00),
(7, 3, 7, 30, 12.00, 360.00),
(8, 3, 8, 40, 8.00, 320.00),
(9, 3, 9, 100, 0.50, 50.00),
(10, 4, 10, 15, 5.00, 75.00),
(11, 4, 11, 20, 15.00, 300.00),
(12, 5, 12, 25, 8.00, 200.00),
(13, 5, 13, 15, 25.00, 375.00),
(14, 6, 1, 8, 800.00, 6400.00),
(15, 6, 2, 25, 15.00, 375.00),
(16, 7, 4, 35, 8.00, 280.00),
(17, 7, 5, 40, 20.00, 800.00),
(18, 8, 7, 20, 12.00, 240.00),
(19, 8, 8, 25, 8.00, 200.00);

-- --------------------------------------------------------

--
-- Structure de la table `returns`
--

CREATE TABLE `returns` (
  `id` int(11) NOT NULL,
  `document_type` enum('sale','invoice','purchase') NOT NULL,
  `document_id` int(11) NOT NULL,
  `status` enum('draft','confirmed') DEFAULT 'draft',
  `total_amount` decimal(10,2) NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `return_items`
--

CREATE TABLE `return_items` (
  `id` int(11) NOT NULL,
  `return_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `document_type` enum('sale','invoice','quote') NOT NULL,
  `payment_mode_id` int(11) DEFAULT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `subtotal_amount` decimal(10,2) NOT NULL,
  `discount_type` enum('percent','fixed') DEFAULT NULL,
  `discount_value` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `advance_payment` decimal(10,2) DEFAULT 0.00,
  `refund_amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('draft','confirmed','paid','partial','cancelled') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `sales`
--

INSERT INTO `sales` (`id`, `client_id`, `user_id`, `document_type`, `payment_mode_id`, `invoice_number`, `subtotal_amount`, `discount_type`, `discount_value`, `discount_amount`, `total_amount`, `paid_amount`, `advance_payment`, `refund_amount`, `status`, `created_at`) VALUES
(1, 1, 1, 'sale', 1, 'SALE-001', 2400.00, 'percent', 5.00, 120.00, 2280.00, 2280.00, 0.00, 0.00, 'paid', '2024-08-20 12:30:00'),
(2, 2, 2, 'sale', 2, 'SALE-002', 1800.00, 'fixed', 100.00, 100.00, 1700.00, 1700.00, 0.00, 0.00, 'paid', '2024-09-15 14:45:00'),
(3, 3, 3, 'sale', 1, 'SALE-003', 3200.00, 'percent', 10.00, 320.00, 2880.00, 2000.00, 0.00, 0.00, 'partial', '2024-10-10 09:20:00'),
(4, 4, 1, 'sale', 3, 'SALE-004', 1500.00, NULL, 0.00, 0.00, 1500.00, 1500.00, 0.00, 0.00, 'paid', '2024-11-05 12:15:00'),
(5, 5, 2, 'sale', 2, 'SALE-005', 2800.00, 'percent', 5.00, 140.00, 2660.00, 2660.00, 0.00, 0.00, 'paid', '2024-12-12 09:30:00'),
(6, 6, 3, 'sale', 1, 'SALE-006', 950.00, NULL, 0.00, 0.00, 950.00, 950.00, 0.00, 0.00, 'paid', '2024-12-18 14:45:00'),
(7, 7, 1, 'sale', 4, 'SALE-007', 4200.00, 'percent', 8.00, 336.00, 3864.00, 3864.00, 0.00, 0.00, 'paid', '2025-01-08 11:20:00'),
(8, 8, 2, 'sale', 3, 'SALE-008', 2100.00, 'fixed', 150.00, 150.00, 1950.00, 1950.00, 0.00, 0.00, 'paid', '2025-01-22 13:10:00'),
(9, 1, 3, 'sale', 2, 'SALE-009', 1800.00, NULL, 0.00, 0.00, 1800.00, 900.00, 0.00, 0.00, 'partial', '2025-02-05 15:30:00'),
(10, 3, 1, 'sale', 1, 'SALE-010', 3500.00, 'percent', 5.00, 175.00, 3325.00, 3325.00, 0.00, 0.00, 'paid', '2025-02-14 08:45:00'),
(11, 4, 1, 'quote', 1, NULL, 0.00, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'draft', '2026-02-22 23:40:22'),
(12, 4, 1, 'sale', 1, NULL, 130.00, NULL, 0.00, 0.00, 130.00, 130.00, 0.00, 0.00, 'paid', '2026-02-22 23:40:41'),
(13, 8, 1, 'quote', 2, NULL, 1330.00, NULL, 0.00, 0.00, 1330.00, 0.00, 0.00, 0.00, 'draft', '2026-02-22 23:41:09');

-- --------------------------------------------------------

--
-- Structure de la table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `article_id`, `quantity`, `unit_price`, `total_price`) VALUES
(1, 1, 1, 1, 1200.00, 1200.00),
(2, 1, 2, 2, 25.00, 50.00),
(3, 1, 3, 1, 45.00, 45.00),
(4, 2, 4, 3, 15.00, 45.00),
(5, 2, 5, 2, 35.00, 70.00),
(6, 2, 6, 1, 65.00, 65.00),
(7, 3, 1, 2, 1200.00, 2400.00),
(8, 4, 2, 1, 25.00, 25.00),
(9, 4, 7, 2, 20.00, 40.00),
(10, 4, 8, 1, 15.00, 15.00),
(11, 5, 4, 4, 15.00, 60.00),
(12, 5, 5, 3, 35.00, 105.00),
(13, 5, 6, 2, 65.00, 130.00),
(14, 6, 7, 2, 20.00, 40.00),
(15, 6, 8, 3, 15.00, 45.00),
(16, 6, 9, 5, 1.50, 7.50),
(17, 7, 1, 3, 1200.00, 3600.00),
(18, 7, 2, 2, 25.00, 50.00),
(19, 8, 10, 1, 30.00, 30.00),
(20, 8, 11, 1, 15.00, 15.00),
(21, 9, 1, 1, 1200.00, 1200.00),
(22, 9, 3, 2, 20.00, 40.00),
(23, 10, 2, 1, 25.00, 25.00),
(24, 10, 4, 2, 15.00, 30.00),
(25, 11, 2, 3, 0.00, 0.00),
(26, 11, 3, 2, 0.00, 0.00),
(27, 11, 6, 1, 0.00, 0.00),
(28, 11, 5, 1, 0.00, 0.00),
(29, 12, 3, 1, 45.00, 45.00),
(30, 12, 2, 2, 25.00, 50.00),
(31, 12, 5, 1, 35.00, 35.00),
(32, 13, 3, 1, 45.00, 45.00),
(33, 13, 2, 2, 25.00, 50.00),
(34, 13, 5, 1, 35.00, 35.00),
(35, 13, 1, 1, 1200.00, 1200.00);

-- --------------------------------------------------------

--
-- Structure de la table `sale_returns`
--

CREATE TABLE `sale_returns` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `total_refund` decimal(10,2) NOT NULL DEFAULT 0.00,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `sale_returns`
--

INSERT INTO `sale_returns` (`id`, `sale_id`, `user_id`, `client_id`, `total_refund`, `reason`, `created_at`) VALUES
(7, 5, 1, NULL, 95.00, 'OK', '2026-02-22 23:42:24');

-- --------------------------------------------------------

--
-- Structure de la table `sale_return_items`
--

CREATE TABLE `sale_return_items` (
  `id` int(11) NOT NULL,
  `return_id` int(11) NOT NULL,
  `sale_item_id` int(11) DEFAULT NULL,
  `article_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `refund_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `sale_return_items`
--

INSERT INTO `sale_return_items` (`id`, `return_id`, `sale_item_id`, `article_id`, `quantity`, `refund_amount`, `unit_price`, `total_price`) VALUES
(1, 7, 11, 4, 2, 30.00, 0.00, 0.00),
(2, 7, 11, 4, 2, 30.00, 0.00, 0.00),
(3, 7, 12, 5, 1, 35.00, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Structure de la table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `key_name` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `type` varchar(50) DEFAULT 'string'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `settings`
--

INSERT INTO `settings` (`id`, `key_name`, `value`, `type`) VALUES
(1, 'company_name', 'My Shop', 'string'),
(2, 'tax_rate_default', '20', 'decimal'),
(3, 'currency_symbol', 'DH', 'string'),
(4, 'receipt_header', 'Thank you for your purchase!', 'string'),
(6, 'company_address', '123 Rue Business, Casablanca, Maroc', 'string'),
(7, 'company_phone', '+212 5XX XXX XXX', 'string'),
(8, 'company_email', 'info@societe.ma', 'string'),
(9, 'company_website', 'www.societe.ma', 'string'),
(10, 'company_rc', 'RC: 123456', 'string'),
(11, 'company_ice', 'ICE: 00123456789', 'string'),
(12, 'company_cnss', 'CNSS: 1234567', 'string'),
(13, 'company_bank', 'Banque: BMCE - RIB: 007 780 0001234567800001 18', 'string'),
(14, 'company_logo', '', 'string'),
(15, 'tax_rate', '20.00', 'string');

-- --------------------------------------------------------

--
-- Structure de la table `stock`
--

CREATE TABLE `stock` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit_price` decimal(10,2) DEFAULT 0.00,
  `location` varchar(100) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `stock`
--

INSERT INTO `stock` (`id`, `article_id`, `quantity`, `unit_price`, `location`, `updated_at`) VALUES
(1, 1, 25, 1200.00, 'Warehouse A', '2026-02-22 23:37:54'),
(2, 2, 73, 25.00, 'Warehouse A', '2026-02-22 23:40:41'),
(3, 3, 39, 45.00, 'Warehouse A', '2026-02-22 23:40:41'),
(4, 4, 124, 15.00, 'Warehouse B', '2026-02-22 23:42:24'),
(5, 5, 65, 35.00, 'Warehouse B', '2026-02-22 23:42:24'),
(6, 6, 35, 65.00, 'Warehouse B', '2026-02-22 23:37:54'),
(7, 7, 45, 20.00, 'Warehouse C', '2026-02-22 23:37:54'),
(8, 8, 80, 15.00, 'Warehouse C', '2026-02-22 23:37:54'),
(9, 9, 200, 1.50, 'Warehouse C', '2026-02-22 23:37:54'),
(10, 10, 55, 12.00, 'Warehouse D', '2026-02-22 23:37:54'),
(11, 11, 30, 30.00, 'Warehouse D', '2026-02-22 23:37:54'),
(12, 12, 85, 15.00, 'Warehouse E', '2026-02-22 23:37:54'),
(13, 13, 25, 45.00, 'Warehouse E', '2026-02-22 23:37:54'),
(14, 1, 10, 1200.00, NULL, '2026-02-22 23:37:54'),
(15, 2, 23, 25.00, NULL, '2026-02-22 23:40:41'),
(16, 3, 14, 45.00, NULL, '2026-02-22 23:40:41'),
(17, 4, 12, 15.00, NULL, '2026-02-22 23:42:24'),
(18, 5, 12, 35.00, NULL, '2026-02-22 23:42:24'),
(19, 6, 20, 65.00, NULL, '2026-02-22 23:37:54'),
(20, 7, 50, 20.00, NULL, '2026-02-22 23:37:54'),
(21, 8, 30, 15.00, NULL, '2026-02-22 23:37:54'),
(22, 9, 40, 1.50, NULL, '2026-02-22 23:37:54'),
(23, 10, 6, 12.00, NULL, '2026-02-22 23:37:54');

-- --------------------------------------------------------

--
-- Structure de la table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `type` enum('in','out','adjustment') NOT NULL,
  `quantity` int(11) NOT NULL,
  `source` enum('purchase','sale','return','manual') NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `article_id`, `type`, `quantity`, `source`, `reference_id`, `created_at`) VALUES
(1, 3, 'out', 1, 'sale', 12, '2026-02-22 23:40:41'),
(2, 2, 'out', 2, 'sale', 12, '2026-02-22 23:40:41'),
(3, 5, 'out', 1, 'sale', 12, '2026-02-22 23:40:41'),
(4, 4, 'in', 2, 'return', 7, '2026-02-22 23:42:24'),
(5, 4, 'in', 2, 'return', 7, '2026-02-22 23:42:24'),
(6, 5, 'in', 1, 'return', 7, '2026-02-22 23:42:24');

-- --------------------------------------------------------

--
-- Structure de la table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `phone`, `email`, `address`, `balance`, `created_at`) VALUES
(1, 'TechSupply Co', '+1-555-0123', 'info@techsupply.com', '123 Tech Street, Silicon Valley, CA', 1500.00, '2026-02-22 23:25:53'),
(2, 'Fashion Wholesale', '+1-555-0456', 'sales@fashionwholesale.com', '456 Fashion Ave, New York, NY', 800.00, '2026-02-22 23:25:53'),
(3, 'Food Distributors Inc', '+1-555-0789', 'orders@fooddist.com', '789 Food Blvd, Chicago, IL', 500.00, '2026-02-22 23:25:53'),
(4, 'Office Depot Supplier', '+1-555-0321', 'contact@officedepot.com', '321 Office Park, Houston, TX', 300.00, '2026-02-22 23:25:53'),
(5, 'Home & Garden Co', '+1-555-0654', 'info@homegarden.com', '654 Garden Lane, Portland, OR', 600.00, '2026-02-22 23:25:53');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','manager','cashier') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `password_hash`, `role`, `is_active`, `created_at`) VALUES
(1, 'Administrator', 'admin', '$2y$10$SlVb7ULtdHH7d3XomVHDNOzuy04yM3rJvEQUwlXoTM/CX.hu2liwa', 'admin', 1, '2026-02-22 23:25:46'),
(2, 'Manager User', 'manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 1, '2026-02-22 23:25:53'),
(3, 'Cashier User', 'cashier', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cashier', 1, '2026-02-22 23:25:53');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `barcode` (`barcode`),
  ADD KEY `category_id` (`category_id`);

--
-- Index pour la table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Index pour la table `charges`
--
ALTER TABLE `charges`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `charge_payments`
--
ALTER TABLE `charge_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `charge_id` (`charge_id`),
  ADD KEY `payment_mode_id` (`payment_mode_id`);

--
-- Index pour la table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `draft_items`
--
ALTER TABLE `draft_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `draft_id` (`draft_id`),
  ADD KEY `article_id` (`article_id`);

--
-- Index pour la table `draft_orders`
--
ALTER TABLE `draft_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Index pour la table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favorite` (`user_id`,`article_id`),
  ADD KEY `article_id` (`article_id`);

--
-- Index pour la table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_mode_id` (`payment_mode_id`);

--
-- Index pour la table `payment_modes`
--
ALTER TABLE `payment_modes`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `payment_situations`
--
ALTER TABLE `payment_situations`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_purchases_created_at` (`created_at`),
  ADD KEY `idx_purchases_supplier_id` (`supplier_id`);

--
-- Index pour la table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_id` (`purchase_id`),
  ADD KEY `article_id` (`article_id`);

--
-- Index pour la table `returns`
--
ALTER TABLE `returns`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `return_items`
--
ALTER TABLE `return_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `return_id` (`return_id`),
  ADD KEY `article_id` (`article_id`);

--
-- Index pour la table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sales_created_at` (`created_at`),
  ADD KEY `idx_sales_client_id` (`client_id`),
  ADD KEY `idx_sales_user_id` (`user_id`),
  ADD KEY `idx_sales_status` (`status`);

--
-- Index pour la table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `article_id` (`article_id`);

--
-- Index pour la table `sale_returns`
--
ALTER TABLE `sale_returns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Index pour la table `sale_return_items`
--
ALTER TABLE `sale_return_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `return_id` (`return_id`),
  ADD KEY `article_id` (`article_id`);

--
-- Index pour la table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key_name` (`key_name`);

--
-- Index pour la table `stock`
--
ALTER TABLE `stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `article_id` (`article_id`);

--
-- Index pour la table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `article_id` (`article_id`);

--
-- Index pour la table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `articles`
--
ALTER TABLE `articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `charges`
--
ALTER TABLE `charges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `charge_payments`
--
ALTER TABLE `charge_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `draft_items`
--
ALTER TABLE `draft_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `draft_orders`
--
ALTER TABLE `draft_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `payment_modes`
--
ALTER TABLE `payment_modes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `payment_situations`
--
ALTER TABLE `payment_situations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `purchase_items`
--
ALTER TABLE `purchase_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT pour la table `returns`
--
ALTER TABLE `returns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `return_items`
--
ALTER TABLE `return_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT pour la table `sale_returns`
--
ALTER TABLE `sale_returns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `sale_return_items`
--
ALTER TABLE `sale_return_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `stock`
--
ALTER TABLE `stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT pour la table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `articles`
--
ALTER TABLE `articles`
  ADD CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Contraintes pour la table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`);

--
-- Contraintes pour la table `charge_payments`
--
ALTER TABLE `charge_payments`
  ADD CONSTRAINT `charge_payments_ibfk_1` FOREIGN KEY (`charge_id`) REFERENCES `charges` (`id`),
  ADD CONSTRAINT `charge_payments_ibfk_2` FOREIGN KEY (`payment_mode_id`) REFERENCES `payment_modes` (`id`);

--
-- Contraintes pour la table `draft_items`
--
ALTER TABLE `draft_items`
  ADD CONSTRAINT `draft_items_ibfk_1` FOREIGN KEY (`draft_id`) REFERENCES `draft_orders` (`id`),
  ADD CONSTRAINT `draft_items_ibfk_2` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`);

--
-- Contraintes pour la table `draft_orders`
--
ALTER TABLE `draft_orders`
  ADD CONSTRAINT `draft_orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `draft_orders_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`);

--
-- Contraintes pour la table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`);

--
-- Contraintes pour la table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`payment_mode_id`) REFERENCES `payment_modes` (`id`);

--
-- Contraintes pour la table `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `purchases_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Contraintes pour la table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD CONSTRAINT `purchase_items_ibfk_1` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`),
  ADD CONSTRAINT `purchase_items_ibfk_2` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`);

--
-- Contraintes pour la table `return_items`
--
ALTER TABLE `return_items`
  ADD CONSTRAINT `return_items_ibfk_1` FOREIGN KEY (`return_id`) REFERENCES `returns` (`id`),
  ADD CONSTRAINT `return_items_ibfk_2` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`);

--
-- Contraintes pour la table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  ADD CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`);

--
-- Contraintes pour la table `sale_returns`
--
ALTER TABLE `sale_returns`
  ADD CONSTRAINT `sale_returns_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  ADD CONSTRAINT `sale_returns_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `sale_returns_ibfk_3` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`);

--
-- Contraintes pour la table `sale_return_items`
--
ALTER TABLE `sale_return_items`
  ADD CONSTRAINT `sale_return_items_ibfk_1` FOREIGN KEY (`return_id`) REFERENCES `sale_returns` (`id`),
  ADD CONSTRAINT `sale_return_items_ibfk_2` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`);

--
-- Contraintes pour la table `stock`
--
ALTER TABLE `stock`
  ADD CONSTRAINT `stock_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`);

--
-- Contraintes pour la table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
