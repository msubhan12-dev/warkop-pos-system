-- WARKOP OS Database Schema
-- Low Budget / Full Free Starter Plan
-- Database: warkop_db

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `warkop_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `warkop_db`;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('owner','kasir','dapur','pelayan','customer') NOT NULL DEFAULT 'customer',
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: tables
-- ============================================================
CREATE TABLE `tables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `table_number` varchar(10) NOT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `capacity` int(11) DEFAULT 4,
  `status` enum('available','occupied','reserved') DEFAULT 'available',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `table_number` (`table_number`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: categories
-- ============================================================
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `description` text,
  `icon` varchar(50) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: menus
-- ============================================================
CREATE TABLE `menus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `is_recommended` tinyint(1) DEFAULT 0,
  `preparation_time` int(11) DEFAULT 15 COMMENT 'in minutes',
  `stock` int(11) DEFAULT NULL COMMENT 'null = unlimited',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_category` (`category_id`),
  KEY `idx_is_available` (`is_available`),
  KEY `idx_is_recommended` (`is_recommended`),
  CONSTRAINT `fk_menu_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: orders
-- ============================================================
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(20) NOT NULL,
  `table_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `order_type` enum('dine_in','take_away') DEFAULT 'dine_in',
  `status` enum('pending','confirmed','cooking','ready','served','completed','cancelled') DEFAULT 'pending',
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text,
  `created_by` int(11) DEFAULT NULL COMMENT 'user_id kasir or customer',
  `served_by` int(11) DEFAULT NULL COMMENT 'user_id pelayan',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `idx_table` (`table_id`),
  KEY `idx_status` (`status`),
  KEY `idx_order_type` (`order_type`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_created_by` (`created_by`),
  CONSTRAINT `fk_order_table` FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_order_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_order_server` FOREIGN KEY (`served_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: order_items
-- ============================================================
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `menu_name` varchar(100) NOT NULL COMMENT 'snapshot for history',
  `price` decimal(10,2) NOT NULL COMMENT 'snapshot for history',
  `quantity` int(11) NOT NULL DEFAULT 1,
  `subtotal` decimal(10,2) NOT NULL,
  `notes` text,
  `status` enum('pending','cooking','ready','served') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_menu` (`menu_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_orderitem_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_orderitem_menu` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: payments
-- ============================================================
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `payment_method` enum('cash','qris','transfer','card') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) NOT NULL,
  `change_amount` decimal(10,2) DEFAULT 0.00,
  `transaction_id` varchar(100) DEFAULT NULL COMMENT 'for digital payment',
  `status` enum('pending','success','failed') DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL COMMENT 'user_id kasir',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_status` (`status`),
  KEY `idx_payment_method` (`payment_method`),
  KEY `idx_created_by` (`created_by`),
  CONSTRAINT `fk_payment_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payment_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: kitchen_tickets
-- ============================================================
CREATE TABLE `kitchen_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `ticket_number` varchar(20) NOT NULL,
  `table_number` varchar(10) DEFAULT NULL,
  `menu_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `notes` text,
  `priority` enum('normal','urgent') DEFAULT 'normal',
  `status` enum('new','cooking','ready','served') DEFAULT 'new',
  `cooking_started_at` timestamp NULL DEFAULT NULL,
  `ready_at` timestamp NULL DEFAULT NULL,
  `served_at` timestamp NULL DEFAULT NULL,
  `prepared_by` int(11) DEFAULT NULL COMMENT 'user_id dapur',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_order_item` (`order_item_id`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_prepared_by` (`prepared_by`),
  CONSTRAINT `fk_ticket_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ticket_orderitem` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ticket_preparer` FOREIGN KEY (`prepared_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: shifts
-- ============================================================
CREATE TABLE `shifts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `shift_start` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `shift_end` timestamp NULL DEFAULT NULL,
  `initial_cash` decimal(10,2) DEFAULT 0.00,
  `final_cash` decimal(10,2) DEFAULT NULL,
  `total_sales` decimal(10,2) DEFAULT 0.00,
  `total_orders` int(11) DEFAULT 0,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_shift_start` (`shift_start`),
  CONSTRAINT `fk_shift_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: notifications
-- ============================================================
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT 'null = broadcast',
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') DEFAULT 'info',
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: audit_logs
-- ============================================================
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_value` text,
  `new_value` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_table_name` (`table_name`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DEFAULT DATA: Admin User
-- ============================================================
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `role`, `phone`, `is_active`) VALUES
('admin', 'admin@warkop.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'owner', '081234567890', 1),
('kasir1', 'kasir@warkop.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kasir 1', 'kasir', '081234567891', 1),
('dapur1', 'dapur@warkop.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Chef 1', 'dapur', '081234567892', 1),
('pelayan1', 'pelayan@warkop.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Pelayan 1', 'pelayan', '081234567893', 1);
-- Default password for all: password

-- ============================================================
-- DEFAULT DATA: Tables
-- ============================================================
INSERT INTO `tables` (`table_number`, `capacity`, `status`) VALUES
('A1', 2, 'available'),
('A2', 2, 'available'),
('A3', 4, 'available'),
('A4', 4, 'available'),
('A5', 6, 'available'),
('B1', 2, 'available'),
('B2', 4, 'available'),
('B3', 4, 'available'),
('B4', 6, 'available'),
('B5', 8, 'available');

-- ============================================================
-- DEFAULT DATA: Categories
-- ============================================================
INSERT INTO `categories` (`name`, `slug`, `description`, `icon`, `sort_order`) VALUES
('Kopi', 'kopi', 'Aneka kopi pilihan', '☕', 1),
('Non Kopi', 'non-kopi', 'Minuman tanpa kopi', '🥤', 2),
('Makanan Berat', 'makanan-berat', 'Nasi dan lauk pauk', '🍛', 3),
('Makanan Ringan', 'makanan-ringan', 'Cemilan dan snack', '🍟', 4),
('Dessert', 'dessert', 'Penutup manis', '🍰', 5);

-- ============================================================
-- DEFAULT DATA: Menus
-- ============================================================
INSERT INTO `menus` (`category_id`, `name`, `slug`, `description`, `price`, `is_available`, `is_recommended`, `preparation_time`) VALUES
-- Kopi
(1, 'Kopi Hitam', 'kopi-hitam', 'Kopi robusta asli', 8000.00, 1, 1, 5),
(1, 'Kopi Susu', 'kopi-susu', 'Kopi dengan susu segar', 10000.00, 1, 1, 5),
(1, 'Kopi Tubruk', 'kopi-tubruk', 'Kopi tubruk tradisional', 7000.00, 1, 0, 5),
(1, 'Cappuccino', 'cappuccino', 'Espresso dengan foam susu', 15000.00, 1, 1, 7),
(1, 'Latte', 'latte', 'Espresso dengan susu', 15000.00, 1, 0, 7),
-- Non Kopi
(2, 'Teh Tarik', 'teh-tarik', 'Teh dengan susu kental', 8000.00, 1, 0, 5),
(2, 'Teh Manis', 'teh-manis', 'Teh manis hangat', 5000.00, 1, 0, 3),
(2, 'Jeruk Peras', 'jeruk-peras', 'Jeruk peras segar', 10000.00, 1, 1, 5),
(2, 'Es Teh', 'es-teh', 'Teh manis dingin', 5000.00, 1, 0, 3),
(2, 'Coklat Panas', 'coklat-panas', 'Minuman coklat hangat', 12000.00, 1, 0, 5),
-- Makanan Berat
(3, 'Nasi Goreng', 'nasi-goreng', 'Nasi goreng spesial', 18000.00, 1, 1, 15),
(3, 'Mie Goreng', 'mie-goreng', 'Mie goreng spesial', 15000.00, 1, 1, 12),
(3, 'Nasi Kuning', 'nasi-kuning', 'Nasi kuning komplit', 20000.00, 1, 0, 10),
(3, 'Nasi Ayam', 'nasi-ayam', 'Nasi dengan ayam goreng', 22000.00, 1, 1, 15),
-- Makanan Ringan
(4, 'Pisang Goreng', 'pisang-goreng', 'Pisang goreng crispy', 8000.00, 1, 1, 10),
(4, 'Kentang Goreng', 'kentang-goreng', 'French fries crispy', 10000.00, 1, 1, 10),
(4, 'Tahu Isi', 'tahu-isi', 'Tahu isi sayuran', 8000.00, 1, 0, 10),
(4, 'Roti Bakar', 'roti-bakar', 'Roti bakar dengan topping', 12000.00, 1, 1, 8),
-- Dessert
(5, 'Es Krim', 'es-krim', 'Es krim vanilla', 10000.00, 1, 0, 3),
(5, 'Puding', 'puding', 'Puding coklat', 8000.00, 1, 0, 3);

-- ============================================================
-- INDEXES untuk Performance
-- ============================================================
-- Already defined in table creation above

-- ============================================================
-- VIEWS untuk Dashboard
-- ============================================================
CREATE OR REPLACE VIEW `v_daily_sales` AS
SELECT 
    DATE(created_at) as sales_date,
    COUNT(*) as total_orders,
    SUM(total) as total_sales,
    AVG(total) as avg_order_value
FROM orders
WHERE status = 'completed'
GROUP BY DATE(created_at);

CREATE OR REPLACE VIEW `v_top_menus` AS
SELECT 
    m.id,
    m.name,
    m.category_id,
    c.name as category_name,
    COUNT(oi.id) as order_count,
    SUM(oi.quantity) as total_quantity,
    SUM(oi.subtotal) as total_revenue
FROM menus m
LEFT JOIN order_items oi ON m.id = oi.menu_id
LEFT JOIN categories c ON m.category_id = c.id
GROUP BY m.id, m.name, m.category_id, c.name
ORDER BY total_quantity DESC;

CREATE OR REPLACE VIEW `v_active_orders` AS
SELECT 
    o.id,
    o.order_number,
    o.table_id,
    t.table_number,
    o.status,
    o.total,
    o.created_at,
    COUNT(oi.id) as item_count
FROM orders o
LEFT JOIN tables t ON o.table_id = t.id
LEFT JOIN order_items oi ON o.id = oi.order_id
WHERE o.status NOT IN ('completed', 'cancelled')
GROUP BY o.id, o.order_number, o.table_id, t.table_number, o.status, o.total, o.created_at;

-- ============================================================
-- DONE
-- ============================================================
