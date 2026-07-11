-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 01, 2026 at 11:28 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `warkop_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_value`, `new_value`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-06-15 18:58:37'),
(2, 1, 'logout', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-15 19:04:45'),
(3, 2, 'login', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-15 19:04:59'),
(4, 2, 'create', 'orders', 1, NULL, '{\"order_number\":\"ORD202606167IR2\",\"customer\":\"iban\",\"total\":90200,\"created_by\":\"kasir1\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-15 19:05:27'),
(5, 2, 'logout', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-15 19:05:41'),
(6, 3, 'login', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-15 19:06:01'),
(7, 3, 'logout', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-15 19:12:00'),
(8, 3, 'login', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-06-15 19:13:14'),
(9, 3, 'update', 'kitchen_tickets', 1, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-15 19:14:12'),
(10, 3, 'update', 'kitchen_tickets', 1, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-15 19:14:16'),
(11, 3, 'update', 'kitchen_tickets', 2, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-15 19:14:19'),
(12, 3, 'update', 'kitchen_tickets', 3, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-15 19:14:20'),
(13, 3, 'update', 'kitchen_tickets', 4, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-15 19:14:21'),
(14, 3, 'update', 'kitchen_tickets', 5, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-15 19:14:22'),
(15, 3, 'update', 'kitchen_tickets', 2, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-15 19:14:24'),
(16, 3, 'update', 'kitchen_tickets', 4, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-15 19:14:25'),
(17, 3, 'update', 'kitchen_tickets', 3, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-15 19:14:26'),
(18, 3, 'update', 'kitchen_tickets', 5, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-15 19:14:28'),
(19, 3, 'logout', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-15 19:14:51'),
(20, 2, 'login', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-15 19:14:58'),
(21, 2, 'logout', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-15 19:15:05'),
(22, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-15 19:15:18'),
(23, 4, 'logout', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-15 19:15:21'),
(24, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-15 19:15:30'),
(25, 4, 'logout', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-15 19:15:38'),
(26, 2, 'login', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-15 19:15:44'),
(27, 2, 'logout', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-15 19:16:14'),
(28, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-15 19:16:30'),
(29, 1, 'logout', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-15 19:17:20'),
(30, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-16 17:41:04'),
(31, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:29:13'),
(32, 1, 'create', 'orders', 2, NULL, '{\"order_number\":\"ORD20260621FUXR\",\"customer\":\"yanto\",\"total\":58300,\"created_by\":\"admin\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:31:35'),
(33, 1, 'create', 'orders', 3, NULL, '{\"order_number\":\"ORD202606212CTM\",\"customer\":\"yanto\",\"total\":58300,\"created_by\":\"admin\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:31:39'),
(34, 1, 'update', 'kitchen_tickets', 6, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:31:51'),
(35, 1, 'update', 'kitchen_tickets', 9, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:31:53'),
(36, 1, 'update', 'kitchen_tickets', 11, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:31:55'),
(37, 1, 'update', 'kitchen_tickets', 10, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:31:56'),
(38, 1, 'update', 'kitchen_tickets', 8, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:31:56'),
(39, 1, 'update', 'kitchen_tickets', 7, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:31:56'),
(40, 1, 'update', 'kitchen_tickets', 12, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:31:58'),
(41, 1, 'update', 'kitchen_tickets', 13, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:31:59'),
(42, 1, 'update', 'kitchen_tickets', 13, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:32:00'),
(43, 1, 'update', 'kitchen_tickets', 14, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:32:01'),
(44, 1, 'update', 'kitchen_tickets', 15, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:32:02'),
(45, 1, 'update', 'kitchen_tickets', 6, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:32:07'),
(46, 1, 'update', 'kitchen_tickets', 7, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:32:08'),
(47, 1, 'update', 'kitchen_tickets', 11, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:32:09'),
(48, 1, 'update', 'kitchen_tickets', 8, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:32:12'),
(49, 1, 'update', 'kitchen_tickets', 9, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:32:13'),
(50, 1, 'update', 'kitchen_tickets', 10, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:32:14'),
(51, 1, 'update', 'kitchen_tickets', 10, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:32:14'),
(52, 1, 'update', 'kitchen_tickets', 10, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:32:14'),
(53, 1, 'update', 'kitchen_tickets', 10, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:32:14'),
(54, 1, 'update', 'kitchen_tickets', 12, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:32:14'),
(55, 1, 'update', 'kitchen_tickets', 12, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:32:15'),
(56, 1, 'update', 'kitchen_tickets', 12, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:32:15'),
(57, 1, 'update', 'kitchen_tickets', 12, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:32:15'),
(58, 1, 'update', 'kitchen_tickets', 13, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:32:15'),
(59, 1, 'update', 'kitchen_tickets', 13, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:32:16'),
(60, 1, 'update', 'kitchen_tickets', 13, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:32:16'),
(61, 1, 'update', 'kitchen_tickets', 13, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:32:16'),
(62, 1, 'update', 'kitchen_tickets', 14, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:32:16'),
(63, 1, 'update', 'kitchen_tickets', 14, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:32:16'),
(64, 1, 'update', 'kitchen_tickets', 14, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:32:17'),
(65, 1, 'update', 'kitchen_tickets', 14, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:32:17'),
(66, 1, 'update', 'kitchen_tickets', 15, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:32:17'),
(67, 1, 'logout', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:33:37'),
(68, NULL, 'create', 'orders', 4, NULL, '{\"order_number\":\"ORD20260621GAMV\",\"customer\":\"yadi\",\"total\":16500}', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-06-21 12:34:26'),
(69, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:35:28'),
(70, 1, 'update', 'kitchen_tickets', 16, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:35:40'),
(71, 1, 'update', 'kitchen_tickets', 16, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:35:43'),
(72, 1, 'logout', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 12:39:26'),
(73, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:09:04'),
(74, NULL, 'create', 'orders', 5, NULL, '{\"order_number\":\"ORD2026062124Z3\",\"customer\":\"To\",\"total\":13200}', '10.143.149.205', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-06-21 13:09:38'),
(75, 1, 'update', 'kitchen_tickets', 17, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:09:56'),
(76, 1, 'update', 'kitchen_tickets', 17, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:09:57'),
(77, 1, 'logout', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:25:50'),
(78, NULL, 'create', 'orders', 6, NULL, '{\"order_number\":\"ORD20260621K8Q3\",\"customer\":\"a\",\"total\":25300,\"payment_method\":\"qris\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:26:04'),
(79, NULL, 'create', 'orders', 7, NULL, '{\"order_number\":\"ORD20260621CDNU\",\"customer\":\"To\",\"total\":16500,\"payment_method\":\"qris\"}', '10.143.149.205', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-06-21 13:28:04'),
(80, 1, 'login', 'users', 1, NULL, NULL, '10.143.149.205', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-06-21 13:29:27'),
(81, NULL, 'create', 'orders', 8, NULL, '{\"order_number\":\"ORD20260621OY63\",\"customer\":\"nama\",\"total\":22000,\"payment_method\":\"qris\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:33:20'),
(82, 1, 'login', 'users', 1, NULL, NULL, '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:35:16'),
(83, 1, 'logout', 'users', 1, NULL, NULL, '10.143.149.205', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-06-21 13:48:01'),
(84, NULL, 'create', 'orders', 9, NULL, '{\"order_number\":\"ORD20260621FURB\",\"customer\":\"To\",\"total\":8800,\"payment_method\":\"qris\"}', '10.143.149.205', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-06-21 13:49:13'),
(85, 1, 'verify', 'payments', 9, '{\"status\":\"pending\"}', '{\"status\":\"verified\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:01'),
(86, 1, 'update', 'kitchen_tickets', 18, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:46'),
(87, 1, 'update', 'kitchen_tickets', 19, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:47'),
(88, 1, 'update', 'kitchen_tickets', 20, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:48'),
(89, 1, 'update', 'kitchen_tickets', 20, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:48'),
(90, 1, 'update', 'kitchen_tickets', 20, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:48'),
(91, 1, 'update', 'kitchen_tickets', 20, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:48'),
(92, 1, 'update', 'kitchen_tickets', 21, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:49'),
(93, 1, 'update', 'kitchen_tickets', 21, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:49'),
(94, 1, 'update', 'kitchen_tickets', 21, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:49'),
(95, 1, 'update', 'kitchen_tickets', 21, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:49'),
(96, 1, 'update', 'kitchen_tickets', 22, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:50'),
(97, 1, 'update', 'kitchen_tickets', 22, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:50'),
(98, 1, 'update', 'kitchen_tickets', 22, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:50'),
(99, 1, 'update', 'kitchen_tickets', 22, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:50'),
(100, 1, 'update', 'kitchen_tickets', 18, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:52'),
(101, 1, 'update', 'kitchen_tickets', 18, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:52'),
(102, 1, 'update', 'kitchen_tickets', 18, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:52'),
(103, 1, 'update', 'kitchen_tickets', 18, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:52'),
(104, 1, 'update', 'kitchen_tickets', 19, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:53'),
(105, 1, 'update', 'kitchen_tickets', 19, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:53'),
(106, 1, 'update', 'kitchen_tickets', 19, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:53'),
(107, 1, 'update', 'kitchen_tickets', 19, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:53'),
(108, 1, 'update', 'kitchen_tickets', 20, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:53'),
(109, 1, 'update', 'kitchen_tickets', 20, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:54'),
(110, 1, 'update', 'kitchen_tickets', 20, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:54'),
(111, 1, 'update', 'kitchen_tickets', 20, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:54'),
(112, 1, 'update', 'kitchen_tickets', 21, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:54'),
(113, 1, 'update', 'kitchen_tickets', 21, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:54'),
(114, 1, 'update', 'kitchen_tickets', 21, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:54'),
(115, 1, 'update', 'kitchen_tickets', 21, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:55'),
(116, 1, 'update', 'kitchen_tickets', 22, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:55'),
(117, 1, 'update', 'kitchen_tickets', 22, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:55'),
(118, 1, 'update', 'kitchen_tickets', 22, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:55'),
(119, 1, 'update', 'kitchen_tickets', 22, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:50:56'),
(120, 1, 'login', 'users', 1, NULL, NULL, '10.143.149.205', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-06-21 13:52:52'),
(121, 1, 'verify', 'payments', 9, '{\"status\":\"pending\"}', '{\"status\":\"verified\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 13:54:44'),
(122, NULL, 'create', 'orders', 10, NULL, '{\"order_number\":\"ORD20260621BMD0\",\"customer\":\"aaa\",\"total\":16500,\"payment_method\":\"qris\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 14:01:39'),
(123, 1, 'verify', 'payments', 10, '{\"status\":\"pending\"}', '{\"status\":\"verified\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 14:01:54'),
(124, 1, 'verify', 'payments', 10, '{\"status\":\"pending\"}', '{\"status\":\"verified\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 14:03:58'),
(125, 1, 'verify', 'payments', 10, '{\"status\":\"pending\"}', '{\"status\":\"verified\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 14:09:14'),
(126, 1, 'verify', 'payments', 10, '{\"status\":\"pending\"}', '{\"status\":\"verified\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 14:09:57'),
(127, 1, 'create', 'orders', 11, NULL, '{\"order_number\":\"ORD20260621FYUG\",\"customer\":\"a\",\"total\":7700,\"created_by\":\"admin\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 14:18:45'),
(128, 1, 'login', 'users', 1, NULL, NULL, '10.143.149.172', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Mobile Safari/537.36', '2026-06-21 14:35:03'),
(129, NULL, 'create', 'orders', 12, NULL, '{\"order_number\":\"ORD20260621RGI2\",\"customer\":\"Mamat\",\"total\":16500,\"payment_method\":\"qris\"}', '10.143.149.172', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Mobile Safari/537.36', '2026-06-21 14:50:37'),
(130, 1, 'logout', 'users', 1, NULL, NULL, '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 14:51:58'),
(131, NULL, 'create', 'orders', 13, NULL, '{\"order_number\":\"ORD20260621FEX5\",\"customer\":\"ad\",\"total\":16500,\"payment_method\":\"qris\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 14:52:10'),
(132, NULL, 'create', 'orders', 14, NULL, '{\"order_number\":\"ORD20260621OW7I\",\"customer\":\"yadi\",\"total\":16500,\"payment_method\":\"qris\"}', '10.143.149.22', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-06-21 14:53:53'),
(133, NULL, 'create', 'orders', 15, NULL, '{\"order_number\":\"ORD202606213PJL\",\"customer\":\"Too\",\"total\":8800,\"payment_method\":\"qris\"}', '10.143.149.205', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-06-21 14:57:10'),
(134, 1, 'login', 'users', 1, NULL, NULL, '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 14:59:46'),
(135, 1, 'verify', 'payments', 15, '{\"status\":\"pending\"}', '{\"status\":\"verified\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:00:02'),
(136, 1, 'verify', 'payments', 15, '{\"status\":\"pending\"}', '{\"status\":\"verified\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:01:11'),
(137, NULL, 'create', 'orders', 16, NULL, '{\"order_number\":\"ORD202606216INL\",\"customer\":\"Opik\",\"total\":25300,\"payment_method\":\"qris\"}', '10.143.149.35', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-21 15:01:53'),
(138, 1, 'verify', 'payments', 16, '{\"status\":\"pending\"}', '{\"status\":\"verified\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:02:21'),
(139, 1, 'verify', 'payments', 16, '{\"status\":\"pending\"}', '{\"status\":\"verified\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:02:40'),
(140, NULL, 'create', 'orders', 17, NULL, '{\"order_number\":\"ORD20260621H5GX\",\"customer\":\"Ffff\",\"total\":16500,\"payment_method\":\"qris\"}', '10.143.149.172', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Mobile Safari/537.36', '2026-06-21 15:03:31'),
(141, NULL, 'create', 'orders', 18, NULL, '{\"order_number\":\"ORD20260621FY7W\",\"customer\":\"Aaaa\",\"total\":13200,\"payment_method\":\"qris\"}', '10.143.149.172', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Mobile Safari/537.36', '2026-06-21 15:05:55'),
(142, 1, 'verify', 'payments', 13, '{\"status\":\"pending\"}', '{\"status\":\"verified\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:11:07'),
(143, NULL, 'create', 'orders', 19, NULL, '{\"order_number\":\"ORD20260621EUFH\",\"customer\":\"To\",\"total\":8800,\"payment_method\":\"qris\"}', '10.143.149.205', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-06-21 15:11:46'),
(144, 1, 'verify', 'payments', 19, '{\"status\":\"pending\"}', '{\"status\":\"verified\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:12:11'),
(145, 1, 'verify', 'payments', 19, '{\"status\":\"pending\"}', '{\"status\":\"verified\"}', '10.143.149.22', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-06-21 15:12:19'),
(146, 1, 'verify', 'payments', 19, '{\"status\":\"pending\"}', '{\"status\":\"verified\"}', '10.143.149.22', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-06-21 15:12:27'),
(147, 1, 'verify', 'payments', 19, '{\"status\":\"pending\"}', '{\"status\":\"verified\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:12:48'),
(148, 1, 'update', 'kitchen_tickets', 23, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:12:54'),
(149, 1, 'update', 'kitchen_tickets', 23, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:12:54'),
(150, 1, 'update', 'kitchen_tickets', 23, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:12:54'),
(151, 1, 'update', 'kitchen_tickets', 23, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:12:54'),
(152, 1, 'update', 'kitchen_tickets', 24, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:12:54'),
(153, 1, 'update', 'kitchen_tickets', 24, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:12:55'),
(154, 1, 'update', 'kitchen_tickets', 24, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:12:55'),
(155, 1, 'update', 'kitchen_tickets', 24, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:12:55'),
(156, 1, 'update', 'kitchen_tickets', 25, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:12:55'),
(157, 1, 'update', 'kitchen_tickets', 25, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:12:55'),
(158, 1, 'update', 'kitchen_tickets', 25, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:12:56'),
(159, 1, 'update', 'kitchen_tickets', 25, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:12:56'),
(160, 1, 'update', 'kitchen_tickets', 26, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:12:56'),
(161, 1, 'update', 'kitchen_tickets', 26, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:12:56'),
(162, 1, 'update', 'kitchen_tickets', 26, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:12:57'),
(163, 1, 'update', 'kitchen_tickets', 26, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:12:57'),
(164, 1, 'update', 'kitchen_tickets', 27, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:12:57'),
(165, 1, 'update', 'kitchen_tickets', 27, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:12:57'),
(166, 1, 'update', 'kitchen_tickets', 27, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:12:57'),
(167, 1, 'update', 'kitchen_tickets', 27, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:12:58'),
(168, 1, 'update', 'kitchen_tickets', 28, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:12:58'),
(169, 1, 'update', 'kitchen_tickets', 28, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:12:58'),
(170, 1, 'update', 'kitchen_tickets', 28, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:12:58'),
(171, 1, 'update', 'kitchen_tickets', 28, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:12:58'),
(172, 1, 'update', 'kitchen_tickets', 29, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:12:59'),
(173, 1, 'update', 'kitchen_tickets', 29, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:12:59'),
(174, 1, 'update', 'kitchen_tickets', 29, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:12:59'),
(175, 1, 'update', 'kitchen_tickets', 29, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:12:59'),
(176, 1, 'update', 'kitchen_tickets', 30, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:00'),
(177, 1, 'update', 'kitchen_tickets', 30, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:00'),
(178, 1, 'update', 'kitchen_tickets', 30, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:00'),
(179, 1, 'update', 'kitchen_tickets', 30, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:00'),
(180, 1, 'update', 'kitchen_tickets', 31, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:00'),
(181, 1, 'update', 'kitchen_tickets', 31, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:01'),
(182, 1, 'update', 'kitchen_tickets', 31, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:01'),
(183, 1, 'update', 'kitchen_tickets', 31, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:01'),
(184, 1, 'update', 'kitchen_tickets', 32, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:01'),
(185, 1, 'update', 'kitchen_tickets', 32, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:02'),
(186, 1, 'update', 'kitchen_tickets', 32, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:02'),
(187, 1, 'update', 'kitchen_tickets', 32, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:02'),
(188, 1, 'update', 'kitchen_tickets', 33, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:02'),
(189, 1, 'update', 'kitchen_tickets', 33, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:02'),
(190, 1, 'update', 'kitchen_tickets', 33, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:03'),
(191, 1, 'update', 'kitchen_tickets', 33, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:03'),
(192, 1, 'update', 'kitchen_tickets', 23, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:04'),
(193, 1, 'update', 'kitchen_tickets', 23, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:04'),
(194, 1, 'update', 'kitchen_tickets', 23, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:04'),
(195, 1, 'update', 'kitchen_tickets', 23, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:04'),
(196, 1, 'update', 'kitchen_tickets', 24, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:05'),
(197, 1, 'update', 'kitchen_tickets', 24, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:05'),
(198, 1, 'update', 'kitchen_tickets', 24, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:05'),
(199, 1, 'update', 'kitchen_tickets', 24, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:05'),
(200, 1, 'update', 'kitchen_tickets', 25, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:06'),
(201, 1, 'update', 'kitchen_tickets', 25, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:06'),
(202, 1, 'update', 'kitchen_tickets', 25, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:06');
INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_value`, `new_value`, `ip_address`, `user_agent`, `created_at`) VALUES
(203, 1, 'update', 'kitchen_tickets', 25, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:06'),
(204, 1, 'update', 'kitchen_tickets', 26, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:06'),
(205, 1, 'update', 'kitchen_tickets', 26, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:07'),
(206, 1, 'update', 'kitchen_tickets', 26, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:07'),
(207, 1, 'update', 'kitchen_tickets', 26, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:07'),
(208, 1, 'update', 'kitchen_tickets', 27, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:07'),
(209, 1, 'update', 'kitchen_tickets', 27, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:07'),
(210, 1, 'update', 'kitchen_tickets', 27, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:08'),
(211, 1, 'update', 'kitchen_tickets', 27, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:08'),
(212, 1, 'update', 'kitchen_tickets', 28, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:08'),
(213, 1, 'update', 'kitchen_tickets', 28, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:08'),
(214, 1, 'update', 'kitchen_tickets', 28, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:08'),
(215, 1, 'update', 'kitchen_tickets', 28, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:09'),
(216, 1, 'update', 'kitchen_tickets', 29, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:09'),
(217, 1, 'update', 'kitchen_tickets', 29, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:09'),
(218, 1, 'update', 'kitchen_tickets', 29, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:09'),
(219, 1, 'update', 'kitchen_tickets', 29, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:10'),
(220, 1, 'update', 'kitchen_tickets', 30, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:10'),
(221, 1, 'update', 'kitchen_tickets', 30, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:10'),
(222, 1, 'update', 'kitchen_tickets', 30, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:10'),
(223, 1, 'update', 'kitchen_tickets', 30, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:10'),
(224, 1, 'update', 'kitchen_tickets', 31, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:11'),
(225, 1, 'update', 'kitchen_tickets', 31, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:11'),
(226, 1, 'update', 'kitchen_tickets', 31, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:11'),
(227, 1, 'update', 'kitchen_tickets', 31, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:11'),
(228, 1, 'update', 'kitchen_tickets', 32, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:12'),
(229, 1, 'update', 'kitchen_tickets', 32, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:12'),
(230, 1, 'update', 'kitchen_tickets', 32, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:12'),
(231, 1, 'update', 'kitchen_tickets', 32, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:12'),
(232, 1, 'update', 'kitchen_tickets', 33, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:13'),
(233, 1, 'update', 'kitchen_tickets', 33, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:13'),
(234, 1, 'update', 'kitchen_tickets', 33, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:13'),
(235, 1, 'update', 'kitchen_tickets', 33, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:13'),
(236, 1, 'verify', 'payments', 19, '{\"status\":\"pending\"}', '{\"status\":\"verified\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:13:25'),
(237, 1, 'verify', 'payments', 19, '{\"status\":\"pending\"}', '{\"status\":\"verified\"}', '10.143.149.22', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:16:10'),
(238, 1, 'logout', 'users', 1, NULL, NULL, '10.143.149.22', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-06-21 15:21:24'),
(239, 1, 'login', 'users', 1, NULL, NULL, '10.143.149.22', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-06-21 15:21:30'),
(240, NULL, 'create', 'orders', 20, NULL, '{\"order_number\":\"ORD20260621P36S\",\"customer\":\"Too\",\"total\":8800,\"payment_method\":\"qris\"}', '10.143.149.205', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-06-21 15:32:31'),
(241, NULL, 'create', 'orders', 21, NULL, '{\"order_number\":\"ORD20260621528H\",\"customer\":\"Opik\",\"total\":29700,\"payment_method\":\"cash\"}', '10.143.149.35', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-21 15:32:52'),
(242, NULL, 'create', 'orders', 22, NULL, '{\"order_number\":\"ORD20260621ZRKC\",\"customer\":\"Y\",\"total\":16500,\"payment_method\":\"qris\"}', '10.143.149.46', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-21 15:39:41'),
(243, NULL, 'create', 'orders', 23, NULL, '{\"order_number\":\"ORD202606215Q0M\",\"customer\":\"Cf\",\"total\":16500,\"payment_method\":\"qris\"}', '10.143.149.205', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-06-21 15:41:35'),
(244, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:47:43'),
(245, 1, 'update', 'kitchen_tickets', 38, '{\"status\":\"new\"}', '{\"status\":\"cooking\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:49:26'),
(246, 1, 'update', 'kitchen_tickets', 38, '{\"status\":\"cooking\"}', '{\"status\":\"ready\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:49:29'),
(247, 1, 'create', 'orders', 24, NULL, '{\"order_number\":\"ORD20260621TL7Y\",\"customer\":\"i\",\"total\":51700,\"created_by\":\"admin\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-21 15:50:40'),
(248, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Safari/605.1.15', '2026-06-30 16:44:44'),
(249, 1, 'create', 'orders', 25, NULL, '{\"order_number\":\"ORD20260630PTH3\",\"customer\":\"a\",\"total\":16500,\"created_by\":\"admin\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Safari/605.1.15', '2026-06-30 16:45:01'),
(250, 1, 'create', 'orders', 26, NULL, '{\"order_number\":\"ORD20260630QS5U\",\"customer\":\"a\",\"total\":7700,\"created_by\":\"admin\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Safari/605.1.15', '2026-06-30 16:48:00'),
(251, 1, 'logout', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Safari/605.1.15', '2026-06-30 16:48:28'),
(252, 2, 'login', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Safari/605.1.15', '2026-06-30 16:48:55'),
(253, 2, 'create', 'orders', 27, NULL, '{\"order_number\":\"ORD20260630G5CI\",\"customer\":\"a\",\"total\":7700,\"created_by\":\"kasir1\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Safari/605.1.15', '2026-06-30 16:49:21'),
(254, 2, 'logout', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Safari/605.1.15', '2026-06-30 17:01:51'),
(255, NULL, 'create', 'orders', 28, NULL, '{\"order_number\":\"ORD202607010JGM\",\"customer\":\"nama\",\"total\":25300,\"payment_method\":\"qris\"}', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-06-30 17:29:19'),
(256, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-30 17:30:10'),
(257, 1, 'verify_payment', 'payments', 28, '{\"verification_status\":\"pending\",\"status\":\"pending\"}', '{\"verification_status\":\"verified\",\"status\":\"success\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-30 17:30:19'),
(258, 1, 'verify_payment', 'payments', 28, '{\"verification_status\":\"pending\",\"status\":\"pending\"}', '{\"verification_status\":\"verified\",\"status\":\"success\"}', NULL, NULL, '2026-06-30 17:34:14'),
(259, 1, 'verify_payment', 'payments', 16, '{\"verification_status\":\"pending\",\"status\":\"pending\"}', '{\"verification_status\":\"verified\",\"status\":\"success\"}', NULL, NULL, '2026-06-30 17:34:41'),
(260, 1, 'verify_payment', 'payments', 22, '{\"verification_status\":\"pending\",\"status\":\"pending\"}', '{\"verification_status\":\"verified\",\"status\":\"success\"}', NULL, NULL, '2026-06-30 17:35:33'),
(261, 1, 'verify_payment', 'payments', 28, '{\"verification_status\":\"pending\",\"status\":\"pending\"}', '{\"verification_status\":\"verified\",\"status\":\"success\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-30 17:37:41'),
(262, 1, 'verify_payment', 'payments', 28, '{\"verification_status\":\"pending\",\"status\":\"pending\"}', '{\"verification_status\":\"verified\",\"status\":\"success\"}', NULL, NULL, '2026-06-30 17:40:05'),
(263, 1, 'verify_payment', 'payments', 28, '{\"verification_status\":\"pending\",\"status\":\"pending\"}', '{\"verification_status\":\"verified\",\"status\":\"success\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-30 17:50:02'),
(264, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-01 03:39:48'),
(265, 1, 'create', 'orders', 29, NULL, '{\"order_number\":\"ORD20260701PF0H\",\"customer\":\"yadi\",\"total\":15000,\"payment_method\":\"qris\"}', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-07-01 03:44:16'),
(266, 1, 'verify_payment', 'payments', 29, '{\"verification_status\":\"pending\",\"status\":\"pending\"}', '{\"verification_status\":\"verified\",\"status\":\"success\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-01 03:44:36'),
(267, 1, 'create', 'orders', 30, NULL, '{\"order_number\":\"ORD20260701HW24\",\"customer\":\"yanto\",\"total\":15000,\"payment_method\":\"qris\"}', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-07-01 04:22:02'),
(268, 1, 'verify_payment', 'payments', 30, '{\"verification_status\":\"pending\",\"status\":\"pending\"}', '{\"verification_status\":\"verified\",\"status\":\"success\"}', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-07-01 04:22:31'),
(269, 1, 'create', 'orders', 31, NULL, '{\"order_number\":\"ORD20260701W1PK\",\"customer\":\"yanto\",\"total\":32000,\"created_by\":\"admin\"}', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-07-01 04:23:35'),
(270, 1, 'create', 'orders', 32, NULL, '{\"order_number\":\"ORD20260701WG24\",\"customer\":\"yadi\",\"total\":57000,\"payment_method\":\"cash\"}', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-07-01 04:28:31'),
(271, 1, 'create', 'orders', 33, NULL, '{\"order_number\":\"ORD202607012GXY\",\"customer\":\"yanto\",\"total\":15000,\"payment_method\":\"qris\"}', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-07-01 04:29:10'),
(272, 1, 'verify_payment', 'payments', 33, '{\"verification_status\":\"pending\",\"status\":\"pending\"}', '{\"verification_status\":\"verified\",\"status\":\"success\"}', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-07-01 04:29:43'),
(273, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Safari/605.1.15', '2026-07-01 20:51:46'),
(274, 1, 'create', 'orders', 34, NULL, '{\"order_number\":\"ORD20260702CP9U\",\"customer\":\"m\",\"total\":56000,\"created_by\":\"admin\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Safari/605.1.15', '2026-07-01 21:12:23'),
(275, 1, 'create', 'orders', 35, NULL, '{\"order_number\":\"ORD20260702MCNI\",\"customer\":\"a\",\"total\":35000,\"created_by\":\"admin\"}', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Safari/605.1.15', '2026-07-01 21:13:03'),
(276, 1, 'logout', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Safari/605.1.15', '2026-07-01 21:14:16');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Kopi', 'kopi', 'Aneka kopi pilihan', '☕', 1, 1, '2026-06-15 18:39:52', '2026-06-15 18:39:52'),
(2, 'Non Kopi', 'non-kopi', 'Minuman tanpa kopi', '🥤', 2, 1, '2026-06-15 18:39:52', '2026-06-15 18:39:52'),
(3, 'Makanan Berat', 'makanan-berat', 'Nasi dan lauk pauk', '🍛', 3, 1, '2026-06-15 18:39:52', '2026-06-15 18:39:52'),
(4, 'Makanan Ringan', 'makanan-ringan', 'Cemilan dan snack', '🍟', 4, 1, '2026-06-15 18:39:52', '2026-06-15 18:39:52'),
(5, 'Dessert', 'dessert', 'Penutup manis', '🍰', 5, 1, '2026-06-15 18:39:52', '2026-06-15 18:39:52');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `expense_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kitchen_tickets`
--

CREATE TABLE `kitchen_tickets` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `ticket_number` varchar(20) NOT NULL,
  `table_number` varchar(10) DEFAULT NULL,
  `menu_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `priority` enum('normal','urgent') DEFAULT 'normal',
  `status` enum('new','cooking','ready','served') DEFAULT 'new',
  `cooking_started_at` timestamp NULL DEFAULT NULL,
  `ready_at` timestamp NULL DEFAULT NULL,
  `served_at` timestamp NULL DEFAULT NULL,
  `prepared_by` int(11) DEFAULT NULL COMMENT 'user_id dapur',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kitchen_tickets`
--

INSERT INTO `kitchen_tickets` (`id`, `order_id`, `order_item_id`, `ticket_number`, `table_number`, `menu_name`, `quantity`, `notes`, `priority`, `status`, `cooking_started_at`, `ready_at`, `served_at`, `prepared_by`, `created_at`) VALUES
(1, 1, 1, 'TKT20260616WMDS', 'A1', 'Cappuccino', 2, NULL, 'normal', 'ready', '2026-06-15 19:14:12', '2026-06-15 19:14:16', NULL, 3, '2026-06-15 19:05:27'),
(2, 1, 2, 'TKT20260616RD2M', 'A1', 'Kopi Tubruk', 1, NULL, 'normal', 'ready', '2026-06-15 19:14:19', '2026-06-15 19:14:24', NULL, 3, '2026-06-15 19:05:27'),
(3, 1, 3, 'TKT20260616TSBN', 'A1', 'Pisang Goreng', 1, NULL, 'normal', 'ready', '2026-06-15 19:14:20', '2026-06-15 19:14:26', NULL, 3, '2026-06-15 19:05:27'),
(4, 1, 4, 'TKT202606161OBJ', 'A1', 'Nasi Ayam', 1, NULL, 'normal', 'ready', '2026-06-15 19:14:21', '2026-06-15 19:14:25', NULL, 3, '2026-06-15 19:05:27'),
(5, 1, 5, 'TKT20260616VPEG', 'A1', 'Mie Goreng', 1, NULL, 'normal', 'ready', '2026-06-15 19:14:22', '2026-06-15 19:14:28', NULL, 3, '2026-06-15 19:05:27'),
(6, 2, 6, 'TKT20260621BEK2', 'A1', 'Cappuccino', 1, NULL, 'normal', 'ready', '2026-06-21 12:31:51', '2026-06-21 12:32:07', NULL, 1, '2026-06-21 12:31:35'),
(7, 2, 7, 'TKT202606211UCJ', 'A1', 'Tahu Isi', 1, NULL, 'normal', 'ready', '2026-06-21 12:31:56', '2026-06-21 12:32:08', NULL, 1, '2026-06-21 12:31:35'),
(8, 2, 8, 'TKT202606211BG0', 'A1', 'Puding', 1, NULL, 'normal', 'ready', '2026-06-21 12:31:56', '2026-06-21 12:32:12', NULL, 1, '2026-06-21 12:31:35'),
(9, 2, 9, 'TKT20260621DMLT', 'A1', 'Es Krim', 1, NULL, 'normal', 'ready', '2026-06-21 12:31:53', '2026-06-21 12:32:13', NULL, 1, '2026-06-21 12:31:35'),
(10, 2, 10, 'TKT20260621XYLB', 'A1', 'Roti Bakar', 1, NULL, 'normal', 'ready', '2026-06-21 12:31:56', '2026-06-21 12:32:14', NULL, 1, '2026-06-21 12:31:35'),
(11, 3, 11, 'TKT20260621TURI', 'A1', 'Cappuccino', 1, NULL, 'normal', 'ready', '2026-06-21 12:31:55', '2026-06-21 12:32:09', NULL, 1, '2026-06-21 12:31:39'),
(12, 3, 12, 'TKT20260621A7O1', 'A1', 'Tahu Isi', 1, NULL, 'normal', 'ready', '2026-06-21 12:31:58', '2026-06-21 12:32:15', NULL, 1, '2026-06-21 12:31:39'),
(13, 3, 13, 'TKT20260621CRU2', 'A1', 'Puding', 1, NULL, 'normal', 'ready', '2026-06-21 12:32:00', '2026-06-21 12:32:16', NULL, 1, '2026-06-21 12:31:39'),
(14, 3, 14, 'TKT202606212LAQ', 'A1', 'Es Krim', 1, NULL, 'normal', 'ready', '2026-06-21 12:32:01', '2026-06-21 12:32:17', NULL, 1, '2026-06-21 12:31:39'),
(15, 3, 15, 'TKT20260621ZDR5', 'A1', 'Roti Bakar', 1, NULL, 'normal', 'ready', '2026-06-21 12:32:02', '2026-06-21 12:32:17', NULL, 1, '2026-06-21 12:31:39'),
(16, 4, 16, 'TKT20260621NOWR', NULL, 'Cappuccino', 1, NULL, 'normal', 'ready', '2026-06-21 12:35:40', '2026-06-21 12:35:43', NULL, 1, '2026-06-21 12:34:26'),
(17, 5, 17, 'TKT20260621QRVT', NULL, 'Coklat Panas', 1, NULL, 'normal', 'ready', '2026-06-21 13:09:56', '2026-06-21 13:09:57', NULL, 1, '2026-06-21 13:09:38'),
(18, 6, 18, 'TKT202606217E9D', NULL, 'Cappuccino', 1, NULL, 'normal', 'ready', '2026-06-21 13:50:46', '2026-06-21 13:50:52', NULL, 1, '2026-06-21 13:26:04'),
(19, 6, 19, 'TKT20260621IUJK', NULL, 'Kopi Hitam', 1, NULL, 'normal', 'ready', '2026-06-21 13:50:47', '2026-06-21 13:50:53', NULL, 1, '2026-06-21 13:26:04'),
(20, 7, 20, 'TKT20260621R36B', NULL, 'Cappuccino', 1, NULL, 'normal', 'ready', '2026-06-21 13:50:48', '2026-06-21 13:50:54', NULL, 1, '2026-06-21 13:28:04'),
(21, 8, 21, 'TKT20260621Q28X', NULL, 'Kopi Susu', 2, NULL, 'normal', 'ready', '2026-06-21 13:50:49', '2026-06-21 13:50:55', NULL, 1, '2026-06-21 13:33:20'),
(22, 9, 22, 'TKT202606217EGD', NULL, 'Kopi Hitam', 1, NULL, 'normal', 'ready', '2026-06-21 13:50:50', '2026-06-21 13:50:56', NULL, 1, '2026-06-21 13:49:13'),
(23, 10, 23, 'TKT202606210XTP', NULL, 'Cappuccino', 1, NULL, 'normal', 'ready', '2026-06-21 15:12:54', '2026-06-21 15:13:04', NULL, 1, '2026-06-21 14:01:39'),
(24, 11, 24, 'TKT2026062146RL', NULL, 'Kopi Tubruk', 1, NULL, 'normal', 'ready', '2026-06-21 15:12:55', '2026-06-21 15:13:05', NULL, 1, '2026-06-21 14:18:45'),
(25, 12, 25, 'TKT20260621ZT8I', NULL, 'Cappuccino', 1, NULL, 'normal', 'ready', '2026-06-21 15:12:56', '2026-06-21 15:13:06', NULL, 1, '2026-06-21 14:50:37'),
(26, 13, 26, 'TKT202606211H6E', NULL, 'Cappuccino', 1, NULL, 'normal', 'ready', '2026-06-21 15:12:57', '2026-06-21 15:13:07', NULL, 1, '2026-06-21 14:52:10'),
(27, 14, 27, 'TKT20260621S1DW', NULL, 'Cappuccino', 1, NULL, 'normal', 'ready', '2026-06-21 15:12:58', '2026-06-21 15:13:08', NULL, 1, '2026-06-21 14:53:53'),
(28, 15, 28, 'TKT202606219VIC', NULL, 'Kopi Hitam', 1, NULL, 'normal', 'ready', '2026-06-21 15:12:58', '2026-06-21 15:13:09', NULL, 1, '2026-06-21 14:57:10'),
(29, 16, 29, 'TKT20260621DBCS', NULL, 'Cappuccino', 1, NULL, 'normal', 'ready', '2026-06-21 15:12:59', '2026-06-21 15:13:10', NULL, 1, '2026-06-21 15:01:53'),
(30, 16, 30, 'TKT20260621XFNU', NULL, 'Kopi Hitam', 1, NULL, 'normal', 'ready', '2026-06-21 15:13:00', '2026-06-21 15:13:10', NULL, 1, '2026-06-21 15:01:53'),
(31, 17, 31, 'TKT20260621NZ7M', NULL, 'Cappuccino', 1, NULL, 'normal', 'ready', '2026-06-21 15:13:01', '2026-06-21 15:13:11', NULL, 1, '2026-06-21 15:03:31'),
(32, 18, 32, 'TKT202606217ZCB', NULL, 'Coklat Panas', 1, NULL, 'normal', 'ready', '2026-06-21 15:13:02', '2026-06-21 15:13:12', NULL, 1, '2026-06-21 15:05:55'),
(33, 19, 33, 'TKT202606211MPO', NULL, 'Kopi Hitam', 1, NULL, 'normal', 'ready', '2026-06-21 15:13:03', '2026-06-21 15:13:13', NULL, 1, '2026-06-21 15:11:46'),
(34, 20, 34, 'TKT202606218ZPI', NULL, 'Kopi Hitam', 1, NULL, 'normal', 'new', NULL, NULL, NULL, NULL, '2026-06-21 15:32:31'),
(35, 21, 35, 'TKT20260621I4DO', NULL, 'Latte', 1, NULL, 'normal', 'new', NULL, NULL, NULL, NULL, '2026-06-21 15:32:52'),
(36, 21, 36, 'TKT202606219OYS', NULL, 'Coklat Panas', 1, NULL, 'normal', 'new', NULL, NULL, NULL, NULL, '2026-06-21 15:32:52'),
(37, 22, 37, 'TKT20260621PQ1N', NULL, 'Cappuccino', 1, NULL, 'normal', 'new', NULL, NULL, NULL, NULL, '2026-06-21 15:39:41'),
(38, 23, 38, 'TKT20260621Y6KB', NULL, 'Cappuccino', 1, NULL, 'normal', 'ready', '2026-06-21 15:49:26', '2026-06-21 15:49:29', NULL, 1, '2026-06-21 15:41:35'),
(39, 24, 39, 'TKT20260621KHJ8', NULL, 'Jeruk Peras', 1, NULL, 'normal', 'new', NULL, NULL, NULL, NULL, '2026-06-21 15:50:40'),
(40, 24, 40, 'TKT20260621JWH8', NULL, 'Kopi Tubruk', 1, NULL, 'normal', 'new', NULL, NULL, NULL, NULL, '2026-06-21 15:50:40'),
(41, 24, 41, 'TKT20260621OZNE', NULL, 'Nasi Ayam', 1, NULL, 'normal', 'new', NULL, NULL, NULL, NULL, '2026-06-21 15:50:40'),
(42, 24, 42, 'TKT20260621ETSO', NULL, 'Pisang Goreng', 1, NULL, 'normal', 'new', NULL, NULL, NULL, NULL, '2026-06-21 15:50:40'),
(43, 25, 43, 'TKT20260630E1TD', NULL, 'Cappuccino', 1, NULL, 'normal', 'new', NULL, NULL, NULL, NULL, '2026-06-30 16:45:01'),
(44, 26, 44, 'TKT20260630RKW3', NULL, 'Kopi Tubruk', 1, NULL, 'normal', 'new', NULL, NULL, NULL, NULL, '2026-06-30 16:48:00'),
(45, 27, 45, 'TKT20260630ECZR', NULL, 'Kopi Tubruk', 1, NULL, 'normal', 'new', NULL, NULL, NULL, NULL, '2026-06-30 16:49:21'),
(46, 31, 50, 'TKT20260701YSID', NULL, 'Cappuccino', 1, NULL, 'normal', 'new', NULL, NULL, NULL, NULL, '2026-07-01 04:23:35'),
(47, 31, 51, 'TKT202607019J2Z', NULL, 'Kopi Tubruk', 1, NULL, 'normal', 'new', NULL, NULL, NULL, NULL, '2026-07-01 04:23:35'),
(48, 31, 52, 'TKT202607013F98', NULL, 'Kopi Susu', 1, NULL, 'normal', 'new', NULL, NULL, NULL, NULL, '2026-07-01 04:23:35'),
(49, 34, 56, 'TKT202607024FQ5', NULL, 'Es Teh', 2, NULL, 'normal', 'new', NULL, NULL, NULL, NULL, '2026-07-01 21:12:23'),
(50, 34, 57, 'TKT20260702ORDN', NULL, 'Jeruk Peras', 1, NULL, 'normal', 'new', NULL, NULL, NULL, NULL, '2026-07-01 21:12:23'),
(51, 34, 58, 'TKT20260702K2HI', NULL, 'Kentang Goreng', 1, NULL, 'normal', 'new', NULL, NULL, NULL, NULL, '2026-07-01 21:12:23'),
(52, 34, 59, 'TKT202607022B1A', NULL, 'Nasi Goreng', 1, NULL, 'normal', 'new', NULL, NULL, NULL, NULL, '2026-07-01 21:12:23'),
(53, 34, 60, 'TKT20260702J83C', NULL, 'Kopi Hitam', 1, NULL, 'normal', 'new', NULL, NULL, NULL, NULL, '2026-07-01 21:12:23'),
(54, 35, 61, 'TKT202607023ZWX', NULL, 'Kopi Susu', 1, NULL, 'normal', 'new', NULL, NULL, NULL, NULL, '2026-07-01 21:13:03'),
(55, 35, 62, 'TKT202607029F2K', NULL, 'Es Teh', 1, NULL, 'normal', 'new', NULL, NULL, NULL, NULL, '2026-07-01 21:13:03'),
(56, 35, 63, 'TKT202607026RYC', NULL, 'Jeruk Peras', 1, NULL, 'normal', 'new', NULL, NULL, NULL, NULL, '2026-07-01 21:13:03'),
(57, 35, 64, 'TKT20260702A3VP', NULL, 'Kentang Goreng', 1, NULL, 'normal', 'new', NULL, NULL, NULL, NULL, '2026-07-01 21:13:03');

-- --------------------------------------------------------

--
-- Table structure for table `menus`
--

CREATE TABLE `menus` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `is_recommended` tinyint(1) DEFAULT 0,
  `preparation_time` int(11) DEFAULT 15 COMMENT 'in minutes',
  `stock` int(11) DEFAULT NULL COMMENT 'null = unlimited',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menus`
--

INSERT INTO `menus` (`id`, `category_id`, `name`, `slug`, `description`, `price`, `image`, `is_available`, `is_recommended`, `preparation_time`, `stock`, `created_at`, `updated_at`) VALUES
(1, 1, 'Kopi Hitam', 'kopi-hitam', 'Kopi robusta asli', 8000.00, 'menu/43d49dd42c434e7d865ddcab43f54a30.jpeg', 1, 1, 5, NULL, '2026-06-15 18:39:52', '2026-07-01 04:13:48'),
(2, 1, 'Kopi Susu', 'kopi-susu', 'Kopi dengan susu segar', 10000.00, 'menu/0add21ec45b63025dbf6ad7617c1fd9a.jpeg', 1, 1, 5, NULL, '2026-06-15 18:39:52', '2026-07-01 04:14:11'),
(3, 1, 'Kopi Tubruk', 'kopi-tubruk', 'Kopi tubruk tradisional', 7000.00, 'menu/e40283b9ab2529854369c01c16565f14.jpeg', 1, 0, 5, NULL, '2026-06-15 18:39:52', '2026-07-01 04:14:35'),
(4, 1, 'Cappuccino', 'cappuccino', 'Espresso dengan foam susu', 15000.00, 'menu/22394aea4fa9d25f44692b19705bdb97.jpeg', 1, 1, 7, NULL, '2026-06-15 18:39:52', '2026-07-01 04:09:54'),
(5, 1, 'Latte', 'latte', 'Espresso dengan susu', 15000.00, 'menu/1bd401ac986632c5a4504a0f840f997a.jpeg', 1, 0, 7, NULL, '2026-06-15 18:39:52', '2026-07-01 04:15:09'),
(6, 2, 'Teh Tarik', 'teh-tarik', 'Teh dengan susu kental', 8000.00, 'menu/ee08e80c81f6b46c4b0380a6bae43971.jpeg', 1, 0, 5, NULL, '2026-06-15 18:39:52', '2026-07-01 04:17:24'),
(7, 2, 'Teh Manis', 'teh-manis', 'Teh manis hangat', 5000.00, NULL, 0, 0, 3, NULL, '2026-06-15 18:39:52', '2026-07-01 04:16:50'),
(8, 2, 'Jeruk Peras', 'jeruk-peras', 'Jeruk peras segar', 10000.00, 'menu/cc2f8327c4dd1d9384496847ead75eb2.jpeg', 1, 1, 5, NULL, '2026-06-15 18:39:52', '2026-07-01 04:16:42'),
(9, 2, 'Es Teh', 'es-teh', 'Teh manis dingin', 5000.00, 'menu/36007bbf3698998d4d76977086753132.jpeg', 1, 0, 3, NULL, '2026-06-15 18:39:52', '2026-07-01 04:16:18'),
(10, 2, 'Coklat Panas', 'coklat-panas', 'Minuman coklat hangat', 12000.00, 'menu/a49b14ecc0dca0cc04b1a3cfd13d9582.jpeg', 1, 0, 5, NULL, '2026-06-15 18:39:52', '2026-07-01 04:15:34'),
(11, 3, 'Nasi Goreng', 'nasi-goreng', 'Nasi goreng spesial', 18000.00, 'menu/9a13693490564fdc8d61aa79eb700917.jpeg', 1, 1, 15, NULL, '2026-06-15 18:39:52', '2026-07-01 04:19:52'),
(12, 3, 'Mie Goreng', 'mie-goreng', 'Mie goreng spesial', 15000.00, 'menu/7fbea3e2570e15429a3b79fd798665d2.jpeg', 1, 1, 12, NULL, '2026-06-15 18:39:52', '2026-07-01 04:17:50'),
(13, 3, 'Nasi Kuning', 'nasi-kuning', 'Nasi kuning komplit', 20000.00, NULL, 0, 0, 10, NULL, '2026-06-15 18:39:52', '2026-07-01 04:18:18'),
(14, 3, 'Nasi Ayam', 'nasi-ayam', 'Nasi dengan ayam goreng', 22000.00, NULL, 0, 1, 15, NULL, '2026-06-15 18:39:52', '2026-07-01 04:18:10'),
(15, 4, 'Pisang Goreng', 'pisang-goreng', 'Pisang goreng crispy', 8000.00, 'menu/b2b44ed71f3aad70734a311e3f8c0151.jpeg', 1, 1, 10, NULL, '2026-06-15 18:39:52', '2026-07-01 04:20:09'),
(16, 4, 'Kentang Goreng', 'kentang-goreng', 'French fries crispy', 10000.00, 'menu/3e0df6dd956445d35467a8d9694e63df.jpeg', 1, 1, 10, NULL, '2026-06-15 18:39:52', '2026-07-01 04:20:01'),
(17, 4, 'Tahu Isi', 'tahu-isi', 'Tahu isi sayuran', 8000.00, NULL, 0, 0, 10, NULL, '2026-06-15 18:39:52', '2026-07-01 04:18:26'),
(18, 4, 'Roti Bakar', 'roti-bakar', 'Roti bakar dengan topping', 12000.00, 'menu/86a077f738afe517f62f143215636bec.jpeg', 1, 1, 8, NULL, '2026-06-15 18:39:52', '2026-07-01 04:20:18'),
(19, 5, 'Es Krim', 'es-krim', 'Es krim vanilla', 10000.00, NULL, 0, 0, 3, NULL, '2026-06-15 18:39:52', '2026-07-01 04:18:28'),
(20, 5, 'Puding', 'puding', 'Puding coklat', 8000.00, NULL, 0, 0, 3, NULL, '2026-06-15 18:39:52', '2026-07-01 04:18:32'),
(21, 1, 'kopi baru', 'kopi-baru', 'barang baru', 8000.00, 'menu/5388f769730ef52f5c5111aed5bef089.jpeg', 0, 0, 15, NULL, '2026-07-01 04:24:21', '2026-07-01 04:24:34');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'null = broadcast',
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') DEFAULT 'info',
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `link`, `is_read`, `created_at`) VALUES
(1, 1, 'Pesanan Baru', 'Pesanan #ORD202606167IR2 dari iban', 'info', NULL, 0, '2026-06-15 19:05:27'),
(2, 3, 'Pesanan Baru', 'Pesanan #ORD202606167IR2 dari iban', 'info', NULL, 0, '2026-06-15 19:05:27'),
(3, 1, 'Pesanan Siap', 'Pesanan #ORD202606167IR2 Meja A1 siap disajikan', 'success', NULL, 0, '2026-06-15 19:14:28'),
(4, 2, 'Pesanan Siap', 'Pesanan #ORD202606167IR2 Meja A1 siap disajikan', 'success', NULL, 0, '2026-06-15 19:14:28'),
(5, 4, 'Pesanan Siap', 'Pesanan #ORD202606167IR2 Meja A1 siap disajikan', 'success', NULL, 0, '2026-06-15 19:14:28'),
(6, 1, 'Pesanan Baru', 'Pesanan #ORD20260621FUXR dari yanto', 'info', NULL, 0, '2026-06-21 12:31:35'),
(7, 3, 'Pesanan Baru', 'Pesanan #ORD20260621FUXR dari yanto', 'info', NULL, 0, '2026-06-21 12:31:35'),
(8, 1, 'Pesanan Baru', 'Pesanan #ORD202606212CTM dari yanto', 'info', NULL, 0, '2026-06-21 12:31:39'),
(9, 3, 'Pesanan Baru', 'Pesanan #ORD202606212CTM dari yanto', 'info', NULL, 0, '2026-06-21 12:31:39'),
(10, 1, 'Pesanan Siap', 'Pesanan #ORD20260621FUXR Meja A1 siap disajikan', 'success', NULL, 0, '2026-06-21 12:32:14'),
(11, 2, 'Pesanan Siap', 'Pesanan #ORD20260621FUXR Meja A1 siap disajikan', 'success', NULL, 0, '2026-06-21 12:32:14'),
(12, 4, 'Pesanan Siap', 'Pesanan #ORD20260621FUXR Meja A1 siap disajikan', 'success', NULL, 0, '2026-06-21 12:32:14'),
(13, 1, 'Pesanan Siap', 'Pesanan #ORD20260621FUXR Meja A1 siap disajikan', 'success', NULL, 0, '2026-06-21 12:32:14'),
(14, 2, 'Pesanan Siap', 'Pesanan #ORD20260621FUXR Meja A1 siap disajikan', 'success', NULL, 0, '2026-06-21 12:32:14'),
(15, 4, 'Pesanan Siap', 'Pesanan #ORD20260621FUXR Meja A1 siap disajikan', 'success', NULL, 0, '2026-06-21 12:32:14'),
(16, 1, 'Pesanan Siap', 'Pesanan #ORD20260621FUXR Meja A1 siap disajikan', 'success', NULL, 0, '2026-06-21 12:32:14'),
(17, 2, 'Pesanan Siap', 'Pesanan #ORD20260621FUXR Meja A1 siap disajikan', 'success', NULL, 0, '2026-06-21 12:32:14'),
(18, 4, 'Pesanan Siap', 'Pesanan #ORD20260621FUXR Meja A1 siap disajikan', 'success', NULL, 0, '2026-06-21 12:32:14'),
(19, 1, 'Pesanan Siap', 'Pesanan #ORD20260621FUXR Meja A1 siap disajikan', 'success', NULL, 0, '2026-06-21 12:32:14'),
(20, 2, 'Pesanan Siap', 'Pesanan #ORD20260621FUXR Meja A1 siap disajikan', 'success', NULL, 0, '2026-06-21 12:32:14'),
(21, 4, 'Pesanan Siap', 'Pesanan #ORD20260621FUXR Meja A1 siap disajikan', 'success', NULL, 0, '2026-06-21 12:32:14'),
(22, 1, 'Pesanan Siap', 'Pesanan #ORD202606212CTM Meja A1 siap disajikan', 'success', NULL, 0, '2026-06-21 12:32:17'),
(23, 2, 'Pesanan Siap', 'Pesanan #ORD202606212CTM Meja A1 siap disajikan', 'success', NULL, 0, '2026-06-21 12:32:17'),
(24, 4, 'Pesanan Siap', 'Pesanan #ORD202606212CTM Meja A1 siap disajikan', 'success', NULL, 0, '2026-06-21 12:32:17'),
(25, 1, 'Pesanan Baru', 'Pesanan baru #ORD20260621GAMV dari yadi', 'info', '/admin/orders.php?id=4', 0, '2026-06-21 12:34:26'),
(26, 2, 'Pesanan Baru', 'Pesanan baru #ORD20260621GAMV dari yadi', 'info', '/admin/orders.php?id=4', 0, '2026-06-21 12:34:26'),
(27, 3, 'Pesanan Baru', 'Pesanan baru #ORD20260621GAMV dari yadi', 'info', '/admin/orders.php?id=4', 0, '2026-06-21 12:34:26'),
(28, 1, 'Pesanan Siap', 'Pesanan #ORD20260621GAMV Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 12:35:43'),
(29, 2, 'Pesanan Siap', 'Pesanan #ORD20260621GAMV Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 12:35:43'),
(30, 4, 'Pesanan Siap', 'Pesanan #ORD20260621GAMV Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 12:35:43'),
(31, 1, 'Pesanan Baru', 'Pesanan baru #ORD2026062124Z3 dari To', 'info', '/admin/orders.php?id=5', 0, '2026-06-21 13:09:38'),
(32, 2, 'Pesanan Baru', 'Pesanan baru #ORD2026062124Z3 dari To', 'info', '/admin/orders.php?id=5', 0, '2026-06-21 13:09:38'),
(33, 3, 'Pesanan Baru', 'Pesanan baru #ORD2026062124Z3 dari To', 'info', '/admin/orders.php?id=5', 0, '2026-06-21 13:09:38'),
(34, 1, 'Pesanan Siap', 'Pesanan #ORD2026062124Z3 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:09:57'),
(35, 2, 'Pesanan Siap', 'Pesanan #ORD2026062124Z3 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:09:57'),
(36, 4, 'Pesanan Siap', 'Pesanan #ORD2026062124Z3 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:09:57'),
(37, 1, 'Verifikasi Pembayaran QRIS', 'Pesanan #ORD20260621K8Q3 menunggu verifikasi pembayaran QRIS', 'warning', '/admin/orders.php?id=6', 0, '2026-06-21 13:26:04'),
(38, 1, 'Verifikasi Pembayaran QRIS', 'Pesanan #ORD20260621CDNU menunggu verifikasi pembayaran QRIS', 'warning', '/admin/orders.php?id=7', 0, '2026-06-21 13:28:04'),
(39, 1, 'Verifikasi Pembayaran QRIS', 'Pesanan #ORD20260621OY63 menunggu verifikasi pembayaran QRIS', 'warning', '/admin/orders.php?id=8', 0, '2026-06-21 13:33:20'),
(40, 1, 'Verifikasi Pembayaran QRIS', 'Pesanan #ORD20260621FURB menunggu verifikasi pembayaran QRIS', 'warning', '/admin/orders.php?id=9', 0, '2026-06-21 13:49:13'),
(41, 3, 'Pesanan Baru (QRIS Terverifikasi)', 'Pesanan #ORD20260621FURB dari To siap dimasak', 'info', NULL, 0, '2026-06-21 13:50:01'),
(42, 1, 'Pesanan Siap', 'Pesanan #ORD20260621K8Q3 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:53'),
(43, 2, 'Pesanan Siap', 'Pesanan #ORD20260621K8Q3 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:53'),
(44, 4, 'Pesanan Siap', 'Pesanan #ORD20260621K8Q3 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:53'),
(45, 1, 'Pesanan Siap', 'Pesanan #ORD20260621K8Q3 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:53'),
(46, 2, 'Pesanan Siap', 'Pesanan #ORD20260621K8Q3 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:53'),
(47, 4, 'Pesanan Siap', 'Pesanan #ORD20260621K8Q3 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:53'),
(48, 1, 'Pesanan Siap', 'Pesanan #ORD20260621K8Q3 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:53'),
(49, 2, 'Pesanan Siap', 'Pesanan #ORD20260621K8Q3 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:53'),
(50, 4, 'Pesanan Siap', 'Pesanan #ORD20260621K8Q3 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:53'),
(51, 1, 'Pesanan Siap', 'Pesanan #ORD20260621K8Q3 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:53'),
(52, 2, 'Pesanan Siap', 'Pesanan #ORD20260621K8Q3 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:53'),
(53, 4, 'Pesanan Siap', 'Pesanan #ORD20260621K8Q3 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:53'),
(54, 1, 'Pesanan Siap', 'Pesanan #ORD20260621CDNU Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:53'),
(55, 2, 'Pesanan Siap', 'Pesanan #ORD20260621CDNU Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:53'),
(56, 4, 'Pesanan Siap', 'Pesanan #ORD20260621CDNU Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:53'),
(57, 1, 'Pesanan Siap', 'Pesanan #ORD20260621CDNU Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:54'),
(58, 2, 'Pesanan Siap', 'Pesanan #ORD20260621CDNU Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:54'),
(59, 4, 'Pesanan Siap', 'Pesanan #ORD20260621CDNU Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:54'),
(60, 1, 'Pesanan Siap', 'Pesanan #ORD20260621CDNU Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:54'),
(61, 2, 'Pesanan Siap', 'Pesanan #ORD20260621CDNU Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:54'),
(62, 4, 'Pesanan Siap', 'Pesanan #ORD20260621CDNU Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:54'),
(63, 1, 'Pesanan Siap', 'Pesanan #ORD20260621CDNU Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:54'),
(64, 2, 'Pesanan Siap', 'Pesanan #ORD20260621CDNU Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:54'),
(65, 4, 'Pesanan Siap', 'Pesanan #ORD20260621CDNU Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:54'),
(66, 1, 'Pesanan Siap', 'Pesanan #ORD20260621OY63 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:54'),
(67, 2, 'Pesanan Siap', 'Pesanan #ORD20260621OY63 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:54'),
(68, 4, 'Pesanan Siap', 'Pesanan #ORD20260621OY63 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:54'),
(69, 1, 'Pesanan Siap', 'Pesanan #ORD20260621OY63 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:54'),
(70, 2, 'Pesanan Siap', 'Pesanan #ORD20260621OY63 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:54'),
(71, 4, 'Pesanan Siap', 'Pesanan #ORD20260621OY63 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:54'),
(72, 1, 'Pesanan Siap', 'Pesanan #ORD20260621OY63 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:54'),
(73, 2, 'Pesanan Siap', 'Pesanan #ORD20260621OY63 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:54'),
(74, 4, 'Pesanan Siap', 'Pesanan #ORD20260621OY63 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:54'),
(75, 1, 'Pesanan Siap', 'Pesanan #ORD20260621OY63 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:55'),
(76, 2, 'Pesanan Siap', 'Pesanan #ORD20260621OY63 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:55'),
(77, 4, 'Pesanan Siap', 'Pesanan #ORD20260621OY63 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:55'),
(78, 1, 'Pesanan Siap', 'Pesanan #ORD20260621FURB Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:55'),
(79, 2, 'Pesanan Siap', 'Pesanan #ORD20260621FURB Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:55'),
(80, 4, 'Pesanan Siap', 'Pesanan #ORD20260621FURB Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:55'),
(81, 1, 'Pesanan Siap', 'Pesanan #ORD20260621FURB Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:55'),
(82, 2, 'Pesanan Siap', 'Pesanan #ORD20260621FURB Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:55'),
(83, 4, 'Pesanan Siap', 'Pesanan #ORD20260621FURB Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:55'),
(84, 1, 'Pesanan Siap', 'Pesanan #ORD20260621FURB Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:55'),
(85, 2, 'Pesanan Siap', 'Pesanan #ORD20260621FURB Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:55'),
(86, 4, 'Pesanan Siap', 'Pesanan #ORD20260621FURB Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:55'),
(87, 1, 'Pesanan Siap', 'Pesanan #ORD20260621FURB Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:56'),
(88, 2, 'Pesanan Siap', 'Pesanan #ORD20260621FURB Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:56'),
(89, 4, 'Pesanan Siap', 'Pesanan #ORD20260621FURB Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 13:50:56'),
(90, 3, 'Pesanan Baru (QRIS Terverifikasi)', 'Pesanan #ORD20260621FURB dari To siap dimasak', 'info', NULL, 0, '2026-06-21 13:54:44'),
(91, 1, 'Verifikasi Pembayaran QRIS', 'Pesanan #ORD20260621BMD0 menunggu verifikasi pembayaran QRIS', 'warning', '/admin/orders.php?id=10', 0, '2026-06-21 14:01:39'),
(92, 3, 'Pesanan Baru (QRIS Terverifikasi)', 'Pesanan #ORD20260621BMD0 dari aaa siap dimasak', 'info', NULL, 0, '2026-06-21 14:01:54'),
(93, 3, 'Pesanan Baru (QRIS Terverifikasi)', 'Pesanan #ORD20260621BMD0 dari aaa siap dimasak', 'info', NULL, 0, '2026-06-21 14:03:58'),
(94, 3, 'Pesanan Baru (QRIS Terverifikasi)', 'Pesanan #ORD20260621BMD0 dari aaa siap dimasak', 'info', NULL, 0, '2026-06-21 14:09:14'),
(95, 3, 'Pesanan Baru (QRIS Terverifikasi)', 'Pesanan #ORD20260621BMD0 dari aaa siap dimasak', 'info', NULL, 0, '2026-06-21 14:09:57'),
(96, 1, 'Pesanan Baru', 'Pesanan #ORD20260621FYUG dari a', 'info', NULL, 0, '2026-06-21 14:18:45'),
(97, 3, 'Pesanan Baru', 'Pesanan #ORD20260621FYUG dari a', 'info', NULL, 0, '2026-06-21 14:18:45'),
(98, 1, 'Verifikasi Pembayaran QRIS', 'Pesanan #ORD20260621RGI2 menunggu verifikasi pembayaran QRIS', 'warning', '/admin/orders.php?id=12', 0, '2026-06-21 14:50:37'),
(99, 1, 'Verifikasi Pembayaran QRIS', 'Pesanan #ORD20260621FEX5 menunggu verifikasi pembayaran QRIS', 'warning', '/admin/orders.php?id=13', 0, '2026-06-21 14:52:10'),
(100, 1, 'Verifikasi Pembayaran QRIS', 'Pesanan #ORD20260621OW7I menunggu verifikasi pembayaran QRIS', 'warning', '/admin/orders.php?id=14', 0, '2026-06-21 14:53:53'),
(101, 1, 'Verifikasi Pembayaran QRIS', 'Pesanan #ORD202606213PJL menunggu verifikasi pembayaran QRIS', 'warning', '/admin/orders.php?id=15', 0, '2026-06-21 14:57:10'),
(102, 3, 'Pesanan Baru (QRIS Terverifikasi)', 'Pesanan #ORD202606213PJL dari Too siap dimasak', 'info', NULL, 0, '2026-06-21 15:00:02'),
(103, 3, 'Pesanan Baru (QRIS Terverifikasi)', 'Pesanan #ORD202606213PJL dari Too siap dimasak', 'info', NULL, 0, '2026-06-21 15:01:11'),
(104, 1, 'Verifikasi Pembayaran QRIS', 'Pesanan #ORD202606216INL menunggu verifikasi pembayaran QRIS', 'warning', '/admin/orders.php?id=16', 0, '2026-06-21 15:01:53'),
(105, 3, 'Pesanan Baru (QRIS Terverifikasi)', 'Pesanan #ORD202606216INL dari Opik siap dimasak', 'info', NULL, 0, '2026-06-21 15:02:21'),
(106, 3, 'Pesanan Baru (QRIS Terverifikasi)', 'Pesanan #ORD202606216INL dari Opik siap dimasak', 'info', NULL, 0, '2026-06-21 15:02:40'),
(107, 1, 'Verifikasi Pembayaran QRIS', 'Pesanan #ORD20260621H5GX menunggu verifikasi pembayaran QRIS', 'warning', '/admin/orders.php?id=17', 0, '2026-06-21 15:03:31'),
(108, 1, 'Verifikasi Pembayaran QRIS', 'Pesanan #ORD20260621FY7W menunggu verifikasi pembayaran QRIS', 'warning', '/admin/orders.php?id=18', 0, '2026-06-21 15:05:55'),
(109, 3, 'Pesanan Baru (QRIS Terverifikasi)', 'Pesanan #ORD20260621FEX5 dari ad siap dimasak', 'info', NULL, 0, '2026-06-21 15:11:07'),
(110, 1, 'Verifikasi Pembayaran QRIS', 'Pesanan #ORD20260621EUFH menunggu verifikasi pembayaran QRIS', 'warning', '/admin/orders.php?id=19', 0, '2026-06-21 15:11:46'),
(111, 3, 'Pesanan Baru (QRIS Terverifikasi)', 'Pesanan #ORD20260621EUFH dari To siap dimasak', 'info', NULL, 0, '2026-06-21 15:12:11'),
(112, 3, 'Pesanan Baru (QRIS Terverifikasi)', 'Pesanan #ORD20260621EUFH dari To siap dimasak', 'info', NULL, 0, '2026-06-21 15:12:19'),
(113, 3, 'Pesanan Baru (QRIS Terverifikasi)', 'Pesanan #ORD20260621EUFH dari To siap dimasak', 'info', NULL, 0, '2026-06-21 15:12:27'),
(114, 3, 'Pesanan Baru (QRIS Terverifikasi)', 'Pesanan #ORD20260621EUFH dari To siap dimasak', 'info', NULL, 0, '2026-06-21 15:12:48'),
(115, 1, 'Pesanan Siap', 'Pesanan #ORD20260621BMD0 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:04'),
(116, 2, 'Pesanan Siap', 'Pesanan #ORD20260621BMD0 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:04'),
(117, 4, 'Pesanan Siap', 'Pesanan #ORD20260621BMD0 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:04'),
(118, 1, 'Pesanan Siap', 'Pesanan #ORD20260621BMD0 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:04'),
(119, 2, 'Pesanan Siap', 'Pesanan #ORD20260621BMD0 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:04'),
(120, 4, 'Pesanan Siap', 'Pesanan #ORD20260621BMD0 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:04'),
(121, 1, 'Pesanan Siap', 'Pesanan #ORD20260621BMD0 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:04'),
(122, 2, 'Pesanan Siap', 'Pesanan #ORD20260621BMD0 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:04'),
(123, 4, 'Pesanan Siap', 'Pesanan #ORD20260621BMD0 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:04'),
(124, 1, 'Pesanan Siap', 'Pesanan #ORD20260621BMD0 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:04'),
(125, 2, 'Pesanan Siap', 'Pesanan #ORD20260621BMD0 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:04'),
(126, 4, 'Pesanan Siap', 'Pesanan #ORD20260621BMD0 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:04'),
(127, 1, 'Pesanan Siap', 'Pesanan #ORD20260621FYUG Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:05'),
(128, 2, 'Pesanan Siap', 'Pesanan #ORD20260621FYUG Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:05'),
(129, 4, 'Pesanan Siap', 'Pesanan #ORD20260621FYUG Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:05'),
(130, 1, 'Pesanan Siap', 'Pesanan #ORD20260621FYUG Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:05'),
(131, 2, 'Pesanan Siap', 'Pesanan #ORD20260621FYUG Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:05'),
(132, 4, 'Pesanan Siap', 'Pesanan #ORD20260621FYUG Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:05'),
(133, 1, 'Pesanan Siap', 'Pesanan #ORD20260621FYUG Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:05'),
(134, 2, 'Pesanan Siap', 'Pesanan #ORD20260621FYUG Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:05'),
(135, 4, 'Pesanan Siap', 'Pesanan #ORD20260621FYUG Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:05'),
(136, 1, 'Pesanan Siap', 'Pesanan #ORD20260621FYUG Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:05'),
(137, 2, 'Pesanan Siap', 'Pesanan #ORD20260621FYUG Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:05'),
(138, 4, 'Pesanan Siap', 'Pesanan #ORD20260621FYUG Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:05'),
(139, 1, 'Pesanan Siap', 'Pesanan #ORD20260621RGI2 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:06'),
(140, 2, 'Pesanan Siap', 'Pesanan #ORD20260621RGI2 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:06'),
(141, 4, 'Pesanan Siap', 'Pesanan #ORD20260621RGI2 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:06'),
(142, 1, 'Pesanan Siap', 'Pesanan #ORD20260621RGI2 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:06'),
(143, 2, 'Pesanan Siap', 'Pesanan #ORD20260621RGI2 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:06'),
(144, 4, 'Pesanan Siap', 'Pesanan #ORD20260621RGI2 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:06'),
(145, 1, 'Pesanan Siap', 'Pesanan #ORD20260621RGI2 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:06'),
(146, 2, 'Pesanan Siap', 'Pesanan #ORD20260621RGI2 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:06'),
(147, 4, 'Pesanan Siap', 'Pesanan #ORD20260621RGI2 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:06'),
(148, 1, 'Pesanan Siap', 'Pesanan #ORD20260621RGI2 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:06'),
(149, 2, 'Pesanan Siap', 'Pesanan #ORD20260621RGI2 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:06'),
(150, 4, 'Pesanan Siap', 'Pesanan #ORD20260621RGI2 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:06'),
(151, 1, 'Pesanan Siap', 'Pesanan #ORD20260621FEX5 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:06'),
(152, 2, 'Pesanan Siap', 'Pesanan #ORD20260621FEX5 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:06'),
(153, 4, 'Pesanan Siap', 'Pesanan #ORD20260621FEX5 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:06'),
(154, 1, 'Pesanan Siap', 'Pesanan #ORD20260621FEX5 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:07'),
(155, 2, 'Pesanan Siap', 'Pesanan #ORD20260621FEX5 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:07'),
(156, 4, 'Pesanan Siap', 'Pesanan #ORD20260621FEX5 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:07'),
(157, 1, 'Pesanan Siap', 'Pesanan #ORD20260621FEX5 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:07'),
(158, 2, 'Pesanan Siap', 'Pesanan #ORD20260621FEX5 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:07'),
(159, 4, 'Pesanan Siap', 'Pesanan #ORD20260621FEX5 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:07'),
(160, 1, 'Pesanan Siap', 'Pesanan #ORD20260621FEX5 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:07'),
(161, 2, 'Pesanan Siap', 'Pesanan #ORD20260621FEX5 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:07'),
(162, 4, 'Pesanan Siap', 'Pesanan #ORD20260621FEX5 Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:07'),
(163, 1, 'Pesanan Siap', 'Pesanan #ORD20260621OW7I Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:07'),
(164, 2, 'Pesanan Siap', 'Pesanan #ORD20260621OW7I Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:07'),
(165, 4, 'Pesanan Siap', 'Pesanan #ORD20260621OW7I Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:07'),
(166, 1, 'Pesanan Siap', 'Pesanan #ORD20260621OW7I Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:07'),
(167, 2, 'Pesanan Siap', 'Pesanan #ORD20260621OW7I Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:07'),
(168, 4, 'Pesanan Siap', 'Pesanan #ORD20260621OW7I Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:07'),
(169, 1, 'Pesanan Siap', 'Pesanan #ORD20260621OW7I Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:08'),
(170, 2, 'Pesanan Siap', 'Pesanan #ORD20260621OW7I Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:08'),
(171, 4, 'Pesanan Siap', 'Pesanan #ORD20260621OW7I Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:08'),
(172, 1, 'Pesanan Siap', 'Pesanan #ORD20260621OW7I Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:08'),
(173, 2, 'Pesanan Siap', 'Pesanan #ORD20260621OW7I Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:08'),
(174, 4, 'Pesanan Siap', 'Pesanan #ORD20260621OW7I Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:08'),
(175, 1, 'Pesanan Siap', 'Pesanan #ORD202606213PJL Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:08'),
(176, 2, 'Pesanan Siap', 'Pesanan #ORD202606213PJL Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:08'),
(177, 4, 'Pesanan Siap', 'Pesanan #ORD202606213PJL Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:08'),
(178, 1, 'Pesanan Siap', 'Pesanan #ORD202606213PJL Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:08'),
(179, 2, 'Pesanan Siap', 'Pesanan #ORD202606213PJL Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:08'),
(180, 4, 'Pesanan Siap', 'Pesanan #ORD202606213PJL Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:08'),
(181, 1, 'Pesanan Siap', 'Pesanan #ORD202606213PJL Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:08'),
(182, 2, 'Pesanan Siap', 'Pesanan #ORD202606213PJL Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:08'),
(183, 4, 'Pesanan Siap', 'Pesanan #ORD202606213PJL Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:08'),
(184, 1, 'Pesanan Siap', 'Pesanan #ORD202606213PJL Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:09'),
(185, 2, 'Pesanan Siap', 'Pesanan #ORD202606213PJL Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:09'),
(186, 4, 'Pesanan Siap', 'Pesanan #ORD202606213PJL Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:09'),
(187, 1, 'Pesanan Siap', 'Pesanan #ORD202606216INL Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:10'),
(188, 2, 'Pesanan Siap', 'Pesanan #ORD202606216INL Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:10'),
(189, 4, 'Pesanan Siap', 'Pesanan #ORD202606216INL Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:10'),
(190, 1, 'Pesanan Siap', 'Pesanan #ORD202606216INL Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:10'),
(191, 2, 'Pesanan Siap', 'Pesanan #ORD202606216INL Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:10'),
(192, 4, 'Pesanan Siap', 'Pesanan #ORD202606216INL Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:10'),
(193, 1, 'Pesanan Siap', 'Pesanan #ORD202606216INL Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:10'),
(194, 2, 'Pesanan Siap', 'Pesanan #ORD202606216INL Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:10'),
(195, 4, 'Pesanan Siap', 'Pesanan #ORD202606216INL Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:10'),
(196, 1, 'Pesanan Siap', 'Pesanan #ORD202606216INL Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:10'),
(197, 2, 'Pesanan Siap', 'Pesanan #ORD202606216INL Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:10'),
(198, 4, 'Pesanan Siap', 'Pesanan #ORD202606216INL Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:10'),
(199, 1, 'Pesanan Siap', 'Pesanan #ORD20260621H5GX Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:11'),
(200, 2, 'Pesanan Siap', 'Pesanan #ORD20260621H5GX Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:11'),
(201, 4, 'Pesanan Siap', 'Pesanan #ORD20260621H5GX Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:11'),
(202, 1, 'Pesanan Siap', 'Pesanan #ORD20260621H5GX Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:11'),
(203, 2, 'Pesanan Siap', 'Pesanan #ORD20260621H5GX Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:11'),
(204, 4, 'Pesanan Siap', 'Pesanan #ORD20260621H5GX Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:11'),
(205, 1, 'Pesanan Siap', 'Pesanan #ORD20260621H5GX Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:11'),
(206, 2, 'Pesanan Siap', 'Pesanan #ORD20260621H5GX Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:11'),
(207, 4, 'Pesanan Siap', 'Pesanan #ORD20260621H5GX Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:11'),
(208, 1, 'Pesanan Siap', 'Pesanan #ORD20260621H5GX Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:11'),
(209, 2, 'Pesanan Siap', 'Pesanan #ORD20260621H5GX Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:11'),
(210, 4, 'Pesanan Siap', 'Pesanan #ORD20260621H5GX Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:11'),
(211, 1, 'Pesanan Siap', 'Pesanan #ORD20260621FY7W Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:12'),
(212, 2, 'Pesanan Siap', 'Pesanan #ORD20260621FY7W Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:12'),
(213, 4, 'Pesanan Siap', 'Pesanan #ORD20260621FY7W Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:12'),
(214, 1, 'Pesanan Siap', 'Pesanan #ORD20260621FY7W Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:12'),
(215, 2, 'Pesanan Siap', 'Pesanan #ORD20260621FY7W Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:12'),
(216, 4, 'Pesanan Siap', 'Pesanan #ORD20260621FY7W Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:12'),
(217, 1, 'Pesanan Siap', 'Pesanan #ORD20260621FY7W Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:12'),
(218, 2, 'Pesanan Siap', 'Pesanan #ORD20260621FY7W Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:12'),
(219, 4, 'Pesanan Siap', 'Pesanan #ORD20260621FY7W Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:12'),
(220, 1, 'Pesanan Siap', 'Pesanan #ORD20260621FY7W Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:12'),
(221, 2, 'Pesanan Siap', 'Pesanan #ORD20260621FY7W Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:12'),
(222, 4, 'Pesanan Siap', 'Pesanan #ORD20260621FY7W Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:12'),
(223, 1, 'Pesanan Siap', 'Pesanan #ORD20260621EUFH Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:13'),
(224, 2, 'Pesanan Siap', 'Pesanan #ORD20260621EUFH Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:13'),
(225, 4, 'Pesanan Siap', 'Pesanan #ORD20260621EUFH Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:13'),
(226, 1, 'Pesanan Siap', 'Pesanan #ORD20260621EUFH Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:13'),
(227, 2, 'Pesanan Siap', 'Pesanan #ORD20260621EUFH Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:13'),
(228, 4, 'Pesanan Siap', 'Pesanan #ORD20260621EUFH Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:13'),
(229, 1, 'Pesanan Siap', 'Pesanan #ORD20260621EUFH Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:13'),
(230, 2, 'Pesanan Siap', 'Pesanan #ORD20260621EUFH Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:13'),
(231, 4, 'Pesanan Siap', 'Pesanan #ORD20260621EUFH Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:13'),
(232, 1, 'Pesanan Siap', 'Pesanan #ORD20260621EUFH Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:13'),
(233, 2, 'Pesanan Siap', 'Pesanan #ORD20260621EUFH Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:13'),
(234, 4, 'Pesanan Siap', 'Pesanan #ORD20260621EUFH Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:13:13'),
(235, 3, 'Pesanan Baru (QRIS Terverifikasi)', 'Pesanan #ORD20260621EUFH dari To siap dimasak', 'info', NULL, 0, '2026-06-21 15:13:25'),
(236, 3, 'Pesanan Baru (QRIS Terverifikasi)', 'Pesanan #ORD20260621EUFH dari To siap dimasak', 'info', NULL, 0, '2026-06-21 15:16:10'),
(237, 1, 'Verifikasi Pembayaran QRIS', 'Pesanan #ORD20260621P36S menunggu verifikasi pembayaran QRIS', 'warning', '/admin/orders.php?id=20', 0, '2026-06-21 15:32:31'),
(238, 1, 'Pesanan Baru', 'Pesanan baru #ORD20260621528H dari Opik', 'info', '/admin/orders.php?id=21', 0, '2026-06-21 15:32:52'),
(239, 2, 'Pesanan Baru', 'Pesanan baru #ORD20260621528H dari Opik', 'info', '/admin/orders.php?id=21', 0, '2026-06-21 15:32:52'),
(240, 3, 'Pesanan Baru', 'Pesanan baru #ORD20260621528H dari Opik', 'info', '/admin/orders.php?id=21', 0, '2026-06-21 15:32:52'),
(241, 1, 'Verifikasi Pembayaran QRIS', 'Pesanan #ORD20260621ZRKC menunggu verifikasi pembayaran QRIS', 'warning', '/admin/orders.php?id=22', 0, '2026-06-21 15:39:41'),
(242, 1, 'Verifikasi Pembayaran QRIS', 'Pesanan #ORD202606215Q0M menunggu verifikasi pembayaran QRIS', 'warning', '/admin/orders.php?id=23', 0, '2026-06-21 15:41:35'),
(243, 1, 'Pesanan Siap', 'Pesanan #ORD202606215Q0M Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:49:29'),
(244, 2, 'Pesanan Siap', 'Pesanan #ORD202606215Q0M Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:49:29'),
(245, 4, 'Pesanan Siap', 'Pesanan #ORD202606215Q0M Take Away siap disajikan', 'success', NULL, 0, '2026-06-21 15:49:29'),
(246, 1, 'Pesanan Baru', 'Pesanan #ORD20260621TL7Y dari i', 'info', NULL, 0, '2026-06-21 15:50:40'),
(247, 3, 'Pesanan Baru', 'Pesanan #ORD20260621TL7Y dari i', 'info', NULL, 0, '2026-06-21 15:50:40'),
(248, 1, 'Pesanan Baru', 'Pesanan #ORD20260630PTH3 dari a', 'info', NULL, 0, '2026-06-30 16:45:01'),
(249, 3, 'Pesanan Baru', 'Pesanan #ORD20260630PTH3 dari a', 'info', NULL, 0, '2026-06-30 16:45:01'),
(250, 1, 'Pesanan Baru', 'Pesanan #ORD20260630QS5U dari a', 'info', NULL, 0, '2026-06-30 16:48:00'),
(251, 3, 'Pesanan Baru', 'Pesanan #ORD20260630QS5U dari a', 'info', NULL, 0, '2026-06-30 16:48:00'),
(252, 1, 'Pesanan Baru', 'Pesanan #ORD20260630G5CI dari a', 'info', NULL, 0, '2026-06-30 16:49:21'),
(253, 3, 'Pesanan Baru', 'Pesanan #ORD20260630G5CI dari a', 'info', NULL, 0, '2026-06-30 16:49:21'),
(254, 1, 'Verifikasi Pembayaran QRIS', 'Pesanan #ORD202607010JGM menunggu verifikasi pembayaran QRIS', 'warning', '/admin/orders.php?id=28', 0, '2026-06-30 17:29:19'),
(255, 1, 'Verifikasi Pembayaran QRIS', 'Pesanan #ORD20260701PF0H menunggu verifikasi pembayaran QRIS', 'warning', '/admin/orders.php?id=29', 0, '2026-07-01 03:44:16'),
(256, 1, 'Verifikasi Pembayaran QRIS', 'Pesanan #ORD20260701HW24 menunggu verifikasi pembayaran QRIS', 'warning', '/admin/orders.php?id=30', 0, '2026-07-01 04:22:02'),
(257, 1, 'Pesanan Baru', 'Pesanan #ORD20260701W1PK dari yanto', 'info', NULL, 0, '2026-07-01 04:23:35'),
(258, 3, 'Pesanan Baru', 'Pesanan #ORD20260701W1PK dari yanto', 'info', NULL, 0, '2026-07-01 04:23:35'),
(259, 1, 'Pesanan Baru', 'Pesanan baru #ORD20260701WG24 dari yadi', 'info', '/admin/orders.php?id=32', 0, '2026-07-01 04:28:31'),
(260, 2, 'Pesanan Baru', 'Pesanan baru #ORD20260701WG24 dari yadi', 'info', '/admin/orders.php?id=32', 0, '2026-07-01 04:28:31'),
(261, 3, 'Pesanan Baru', 'Pesanan baru #ORD20260701WG24 dari yadi', 'info', '/admin/orders.php?id=32', 0, '2026-07-01 04:28:31'),
(262, 1, 'Verifikasi Pembayaran QRIS', 'Pesanan #ORD202607012GXY menunggu verifikasi pembayaran QRIS', 'warning', '/admin/orders.php?id=33', 0, '2026-07-01 04:29:10'),
(263, 1, 'Pesanan Baru', 'Pesanan #ORD20260702CP9U dari m', 'info', NULL, 0, '2026-07-01 21:12:23'),
(264, 3, 'Pesanan Baru', 'Pesanan #ORD20260702CP9U dari m', 'info', NULL, 0, '2026-07-01 21:12:23'),
(265, 1, 'Pesanan Baru', 'Pesanan #ORD20260702MCNI dari a', 'info', NULL, 0, '2026-07-01 21:13:03'),
(266, 3, 'Pesanan Baru', 'Pesanan #ORD20260702MCNI dari a', 'info', NULL, 0, '2026-07-01 21:13:03');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `table_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `order_type` enum('dine_in','take_away') DEFAULT 'dine_in',
  `status` enum('pending','confirmed','cooking','ready','served','completed','cancelled') DEFAULT 'pending',
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL COMMENT 'user_id kasir or customer',
  `served_by` int(11) DEFAULT NULL COMMENT 'user_id pelayan',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `table_id`, `customer_name`, `customer_phone`, `order_type`, `status`, `subtotal`, `tax`, `total`, `notes`, `created_by`, `served_by`, `created_at`, `updated_at`, `completed_at`) VALUES
(1, 'ORD202606167IR2', 1, 'iban', NULL, 'dine_in', 'ready', 82000.00, 8200.00, 90200.00, NULL, 2, NULL, '2026-06-15 19:05:27', '2026-06-15 19:14:28', NULL),
(2, 'ORD20260621FUXR', 1, 'yanto', NULL, 'dine_in', 'ready', 53000.00, 5300.00, 58300.00, NULL, 1, NULL, '2026-06-21 12:31:35', '2026-06-21 12:32:14', NULL),
(3, 'ORD202606212CTM', 1, 'yanto', NULL, 'dine_in', 'ready', 53000.00, 5300.00, 58300.00, NULL, 1, NULL, '2026-06-21 12:31:39', '2026-06-21 12:32:17', NULL),
(4, 'ORD20260621GAMV', NULL, 'yadi', '08194983948', 'dine_in', 'ready', 15000.00, 1500.00, 16500.00, 'gula', NULL, NULL, '2026-06-21 12:34:26', '2026-06-21 12:35:43', NULL),
(5, 'ORD2026062124Z3', NULL, 'To', '085236597955', 'dine_in', 'ready', 12000.00, 1200.00, 13200.00, '', NULL, NULL, '2026-06-21 13:09:38', '2026-06-21 13:09:57', NULL),
(6, 'ORD20260621K8Q3', NULL, 'a', '08194983948', 'dine_in', 'ready', 23000.00, 2300.00, 25300.00, '', NULL, NULL, '2026-06-21 13:26:04', '2026-06-21 13:50:53', NULL),
(7, 'ORD20260621CDNU', NULL, 'To', '0856958558', 'dine_in', 'ready', 15000.00, 1500.00, 16500.00, '', NULL, NULL, '2026-06-21 13:28:04', '2026-06-21 13:50:53', NULL),
(8, 'ORD20260621OY63', NULL, 'nama', '08194983948', 'dine_in', 'completed', 20000.00, 2000.00, 22000.00, 'a', NULL, NULL, '2026-06-21 13:33:20', '2026-06-30 17:36:49', '2026-06-30 17:36:49'),
(9, 'ORD20260621FURB', NULL, 'To', '087576264', 'dine_in', 'completed', 8000.00, 800.00, 8800.00, '', NULL, NULL, '2026-06-21 13:49:13', '2026-06-30 17:36:49', '2026-06-30 17:36:49'),
(10, 'ORD20260621BMD0', NULL, 'aaa', '08194983948', 'dine_in', 'completed', 15000.00, 1500.00, 16500.00, 'a', NULL, NULL, '2026-06-21 14:01:39', '2026-06-30 17:36:49', '2026-06-30 17:36:49'),
(11, 'ORD20260621FYUG', NULL, 'a', NULL, 'take_away', 'ready', 7000.00, 700.00, 7700.00, NULL, 1, NULL, '2026-06-21 14:18:45', '2026-06-21 15:13:05', NULL),
(12, 'ORD20260621RGI2', NULL, 'Mamat', '0825154345', 'dine_in', 'ready', 15000.00, 1500.00, 16500.00, '', NULL, NULL, '2026-06-21 14:50:37', '2026-06-21 15:13:06', NULL),
(13, 'ORD20260621FEX5', NULL, 'ad', '08194983948', 'dine_in', 'completed', 15000.00, 1500.00, 16500.00, '', NULL, NULL, '2026-06-21 14:52:10', '2026-06-30 17:36:49', '2026-06-30 17:36:49'),
(14, 'ORD20260621OW7I', NULL, 'yadi', '08194983948', 'dine_in', 'ready', 15000.00, 1500.00, 16500.00, '', NULL, NULL, '2026-06-21 14:53:53', '2026-06-21 15:13:07', NULL),
(15, 'ORD202606213PJL', NULL, 'Too', '085236597955', 'dine_in', 'completed', 8000.00, 800.00, 8800.00, '', NULL, NULL, '2026-06-21 14:57:10', '2026-06-30 17:36:49', '2026-06-30 17:36:49'),
(16, 'ORD202606216INL', NULL, 'Opik', '089919331616', 'dine_in', 'completed', 23000.00, 2300.00, 25300.00, 'Mohon ditambah kan packing', NULL, NULL, '2026-06-21 15:01:53', '2026-06-30 17:34:41', '2026-06-30 17:34:41'),
(17, 'ORD20260621H5GX', NULL, 'Ffff', '0825154345', 'dine_in', 'completed', 15000.00, 1500.00, 16500.00, '', NULL, NULL, '2026-06-21 15:03:31', '2026-06-30 17:36:49', '2026-06-30 17:36:49'),
(18, 'ORD20260621FY7W', NULL, 'Aaaa', '0825154345', 'dine_in', 'completed', 12000.00, 1200.00, 13200.00, '', NULL, NULL, '2026-06-21 15:05:55', '2026-06-30 17:36:49', '2026-06-30 17:36:49'),
(19, 'ORD20260621EUFH', NULL, 'To', '0856958558', 'dine_in', 'completed', 8000.00, 800.00, 8800.00, '', NULL, NULL, '2026-06-21 15:11:46', '2026-06-30 17:36:49', '2026-06-30 17:36:49'),
(20, 'ORD20260621P36S', NULL, 'Too', '085236597955', 'dine_in', 'completed', 8000.00, 800.00, 8800.00, '', NULL, NULL, '2026-06-21 15:32:31', '2026-06-30 17:36:49', '2026-06-30 17:36:49'),
(21, 'ORD20260621528H', NULL, 'Opik', '089919331616', 'dine_in', 'confirmed', 27000.00, 2700.00, 29700.00, 'Pedes banget', NULL, NULL, '2026-06-21 15:32:52', '2026-06-21 15:32:52', NULL),
(22, 'ORD20260621ZRKC', NULL, 'Y', '0854316191', 'dine_in', 'completed', 15000.00, 1500.00, 16500.00, '', NULL, NULL, '2026-06-21 15:39:41', '2026-06-30 17:35:33', '2026-06-30 17:35:33'),
(23, 'ORD202606215Q0M', NULL, 'Cf', '0856958558', 'dine_in', 'completed', 15000.00, 1500.00, 16500.00, '', NULL, NULL, '2026-06-21 15:41:35', '2026-06-30 17:36:49', '2026-06-30 17:36:49'),
(24, 'ORD20260621TL7Y', NULL, 'i', NULL, 'take_away', 'confirmed', 47000.00, 4700.00, 51700.00, NULL, 1, NULL, '2026-06-21 15:50:40', '2026-06-21 15:50:40', NULL),
(25, 'ORD20260630PTH3', NULL, 'a', NULL, 'take_away', 'confirmed', 15000.00, 1500.00, 16500.00, NULL, 1, NULL, '2026-06-30 16:45:01', '2026-06-30 16:45:01', NULL),
(26, 'ORD20260630QS5U', NULL, 'a', NULL, 'take_away', 'confirmed', 7000.00, 700.00, 7700.00, NULL, 1, NULL, '2026-06-30 16:48:00', '2026-06-30 16:48:00', NULL),
(27, 'ORD20260630G5CI', NULL, 'a', NULL, 'take_away', 'confirmed', 7000.00, 700.00, 7700.00, NULL, 2, NULL, '2026-06-30 16:49:21', '2026-06-30 16:49:21', NULL),
(28, 'ORD202607010JGM', NULL, 'nama', '08194983948', 'take_away', 'completed', 23000.00, 2300.00, 25300.00, 'a', NULL, NULL, '2026-06-30 17:29:19', '2026-06-30 17:50:02', '2026-06-30 17:50:02'),
(29, 'ORD20260701PF0H', NULL, 'yadi', '08194983948', 'take_away', 'completed', 15000.00, 0.00, 15000.00, '', NULL, NULL, '2026-07-01 03:44:16', '2026-07-01 03:44:36', '2026-07-01 03:44:36'),
(30, 'ORD20260701HW24', NULL, 'yanto', '08194983948', 'take_away', 'completed', 15000.00, 0.00, 15000.00, 'nambah gula', NULL, NULL, '2026-07-01 04:22:02', '2026-07-01 04:22:31', '2026-07-01 04:22:31'),
(31, 'ORD20260701W1PK', NULL, 'yanto', NULL, 'take_away', 'confirmed', 32000.00, 0.00, 32000.00, NULL, 1, NULL, '2026-07-01 04:23:35', '2026-07-01 04:23:35', NULL),
(32, 'ORD20260701WG24', NULL, 'yadi', '08194983948', 'take_away', 'confirmed', 57000.00, 0.00, 57000.00, 'asem', NULL, NULL, '2026-07-01 04:28:31', '2026-07-01 04:28:31', NULL),
(33, 'ORD202607012GXY', NULL, 'yanto', '08194983948', 'take_away', 'completed', 15000.00, 0.00, 15000.00, 'sedep', NULL, NULL, '2026-07-01 04:29:10', '2026-07-01 04:29:43', '2026-07-01 04:29:43'),
(34, 'ORD20260702CP9U', NULL, 'm', NULL, 'take_away', 'confirmed', 56000.00, 0.00, 56000.00, NULL, 1, NULL, '2026-07-01 21:12:23', '2026-07-01 21:12:23', NULL),
(35, 'ORD20260702MCNI', NULL, 'a', NULL, 'take_away', 'confirmed', 35000.00, 0.00, 35000.00, NULL, 1, NULL, '2026-07-01 21:13:03', '2026-07-01 21:13:03', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `menu_name` varchar(100) NOT NULL COMMENT 'snapshot for history',
  `price` decimal(10,2) NOT NULL COMMENT 'snapshot for history',
  `quantity` int(11) NOT NULL DEFAULT 1,
  `subtotal` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','cooking','ready','served') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `menu_id`, `menu_name`, `price`, `quantity`, `subtotal`, `notes`, `status`, `created_at`) VALUES
(1, 1, 4, 'Cappuccino', 15000.00, 2, 30000.00, NULL, 'ready', '2026-06-15 19:05:27'),
(2, 1, 3, 'Kopi Tubruk', 7000.00, 1, 7000.00, NULL, 'ready', '2026-06-15 19:05:27'),
(3, 1, 15, 'Pisang Goreng', 8000.00, 1, 8000.00, NULL, 'ready', '2026-06-15 19:05:27'),
(4, 1, 14, 'Nasi Ayam', 22000.00, 1, 22000.00, NULL, 'ready', '2026-06-15 19:05:27'),
(5, 1, 12, 'Mie Goreng', 15000.00, 1, 15000.00, NULL, 'ready', '2026-06-15 19:05:27'),
(6, 2, 4, 'Cappuccino', 15000.00, 1, 15000.00, NULL, 'ready', '2026-06-21 12:31:35'),
(7, 2, 17, 'Tahu Isi', 8000.00, 1, 8000.00, NULL, 'ready', '2026-06-21 12:31:35'),
(8, 2, 20, 'Puding', 8000.00, 1, 8000.00, NULL, 'ready', '2026-06-21 12:31:35'),
(9, 2, 19, 'Es Krim', 10000.00, 1, 10000.00, NULL, 'ready', '2026-06-21 12:31:35'),
(10, 2, 18, 'Roti Bakar', 12000.00, 1, 12000.00, NULL, 'ready', '2026-06-21 12:31:35'),
(11, 3, 4, 'Cappuccino', 15000.00, 1, 15000.00, NULL, 'ready', '2026-06-21 12:31:39'),
(12, 3, 17, 'Tahu Isi', 8000.00, 1, 8000.00, NULL, 'ready', '2026-06-21 12:31:39'),
(13, 3, 20, 'Puding', 8000.00, 1, 8000.00, NULL, 'ready', '2026-06-21 12:31:39'),
(14, 3, 19, 'Es Krim', 10000.00, 1, 10000.00, NULL, 'ready', '2026-06-21 12:31:39'),
(15, 3, 18, 'Roti Bakar', 12000.00, 1, 12000.00, NULL, 'ready', '2026-06-21 12:31:39'),
(16, 4, 4, 'Cappuccino', 15000.00, 1, 15000.00, NULL, 'ready', '2026-06-21 12:34:26'),
(17, 5, 10, 'Coklat Panas', 12000.00, 1, 12000.00, NULL, 'ready', '2026-06-21 13:09:38'),
(18, 6, 4, 'Cappuccino', 15000.00, 1, 15000.00, NULL, 'ready', '2026-06-21 13:26:04'),
(19, 6, 1, 'Kopi Hitam', 8000.00, 1, 8000.00, NULL, 'ready', '2026-06-21 13:26:04'),
(20, 7, 4, 'Cappuccino', 15000.00, 1, 15000.00, NULL, 'ready', '2026-06-21 13:28:04'),
(21, 8, 2, 'Kopi Susu', 10000.00, 2, 20000.00, NULL, 'ready', '2026-06-21 13:33:20'),
(22, 9, 1, 'Kopi Hitam', 8000.00, 1, 8000.00, NULL, 'ready', '2026-06-21 13:49:13'),
(23, 10, 4, 'Cappuccino', 15000.00, 1, 15000.00, NULL, 'ready', '2026-06-21 14:01:39'),
(24, 11, 3, 'Kopi Tubruk', 7000.00, 1, 7000.00, NULL, 'ready', '2026-06-21 14:18:45'),
(25, 12, 4, 'Cappuccino', 15000.00, 1, 15000.00, NULL, 'ready', '2026-06-21 14:50:37'),
(26, 13, 4, 'Cappuccino', 15000.00, 1, 15000.00, NULL, 'ready', '2026-06-21 14:52:10'),
(27, 14, 4, 'Cappuccino', 15000.00, 1, 15000.00, NULL, 'ready', '2026-06-21 14:53:53'),
(28, 15, 1, 'Kopi Hitam', 8000.00, 1, 8000.00, NULL, 'ready', '2026-06-21 14:57:10'),
(29, 16, 4, 'Cappuccino', 15000.00, 1, 15000.00, NULL, 'ready', '2026-06-21 15:01:53'),
(30, 16, 1, 'Kopi Hitam', 8000.00, 1, 8000.00, NULL, 'ready', '2026-06-21 15:01:53'),
(31, 17, 4, 'Cappuccino', 15000.00, 1, 15000.00, NULL, 'ready', '2026-06-21 15:03:31'),
(32, 18, 10, 'Coklat Panas', 12000.00, 1, 12000.00, NULL, 'ready', '2026-06-21 15:05:55'),
(33, 19, 1, 'Kopi Hitam', 8000.00, 1, 8000.00, NULL, 'ready', '2026-06-21 15:11:46'),
(34, 20, 1, 'Kopi Hitam', 8000.00, 1, 8000.00, NULL, 'pending', '2026-06-21 15:32:31'),
(35, 21, 5, 'Latte', 15000.00, 1, 15000.00, NULL, 'pending', '2026-06-21 15:32:52'),
(36, 21, 10, 'Coklat Panas', 12000.00, 1, 12000.00, NULL, 'pending', '2026-06-21 15:32:52'),
(37, 22, 4, 'Cappuccino', 15000.00, 1, 15000.00, NULL, 'pending', '2026-06-21 15:39:41'),
(38, 23, 4, 'Cappuccino', 15000.00, 1, 15000.00, NULL, 'ready', '2026-06-21 15:41:35'),
(39, 24, 8, 'Jeruk Peras', 10000.00, 1, 10000.00, NULL, 'pending', '2026-06-21 15:50:40'),
(40, 24, 3, 'Kopi Tubruk', 7000.00, 1, 7000.00, NULL, 'pending', '2026-06-21 15:50:40'),
(41, 24, 14, 'Nasi Ayam', 22000.00, 1, 22000.00, NULL, 'pending', '2026-06-21 15:50:40'),
(42, 24, 15, 'Pisang Goreng', 8000.00, 1, 8000.00, NULL, 'pending', '2026-06-21 15:50:40'),
(43, 25, 4, 'Cappuccino', 15000.00, 1, 15000.00, NULL, 'pending', '2026-06-30 16:45:01'),
(44, 26, 3, 'Kopi Tubruk', 7000.00, 1, 7000.00, NULL, 'pending', '2026-06-30 16:48:00'),
(45, 27, 3, 'Kopi Tubruk', 7000.00, 1, 7000.00, NULL, 'pending', '2026-06-30 16:49:21'),
(46, 28, 4, 'Cappuccino', 15000.00, 1, 15000.00, NULL, 'pending', '2026-06-30 17:29:19'),
(47, 28, 1, 'Kopi Hitam', 8000.00, 1, 8000.00, NULL, 'pending', '2026-06-30 17:29:19'),
(48, 29, 4, 'Cappuccino', 15000.00, 1, 15000.00, NULL, 'pending', '2026-07-01 03:44:16'),
(49, 30, 4, 'Cappuccino', 15000.00, 1, 15000.00, NULL, 'pending', '2026-07-01 04:22:02'),
(50, 31, 4, 'Cappuccino', 15000.00, 1, 15000.00, NULL, 'pending', '2026-07-01 04:23:35'),
(51, 31, 3, 'Kopi Tubruk', 7000.00, 1, 7000.00, NULL, 'pending', '2026-07-01 04:23:35'),
(52, 31, 2, 'Kopi Susu', 10000.00, 1, 10000.00, NULL, 'pending', '2026-07-01 04:23:35'),
(53, 32, 4, 'Cappuccino', 15000.00, 3, 45000.00, NULL, 'pending', '2026-07-01 04:28:31'),
(54, 32, 10, 'Coklat Panas', 12000.00, 1, 12000.00, NULL, 'pending', '2026-07-01 04:28:31'),
(55, 33, 4, 'Cappuccino', 15000.00, 1, 15000.00, NULL, 'pending', '2026-07-01 04:29:10'),
(56, 34, 9, 'Es Teh', 5000.00, 2, 10000.00, NULL, 'pending', '2026-07-01 21:12:23'),
(57, 34, 8, 'Jeruk Peras', 10000.00, 1, 10000.00, NULL, 'pending', '2026-07-01 21:12:23'),
(58, 34, 16, 'Kentang Goreng', 10000.00, 1, 10000.00, NULL, 'pending', '2026-07-01 21:12:23'),
(59, 34, 11, 'Nasi Goreng', 18000.00, 1, 18000.00, NULL, 'pending', '2026-07-01 21:12:23'),
(60, 34, 1, 'Kopi Hitam', 8000.00, 1, 8000.00, NULL, 'pending', '2026-07-01 21:12:23'),
(61, 35, 2, 'Kopi Susu', 10000.00, 1, 10000.00, NULL, 'pending', '2026-07-01 21:13:03'),
(62, 35, 9, 'Es Teh', 5000.00, 1, 5000.00, NULL, 'pending', '2026-07-01 21:13:03'),
(63, 35, 8, 'Jeruk Peras', 10000.00, 1, 10000.00, NULL, 'pending', '2026-07-01 21:13:03'),
(64, 35, 16, 'Kentang Goreng', 10000.00, 1, 10000.00, NULL, 'pending', '2026-07-01 21:13:03');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_method` enum('cash','qris','transfer','card') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) NOT NULL,
  `change_amount` decimal(10,2) DEFAULT 0.00,
  `transaction_id` varchar(100) DEFAULT NULL COMMENT 'for digital payment',
  `status` enum('pending','success','failed') DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL COMMENT 'user_id kasir',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `proof_of_payment` varchar(255) DEFAULT NULL COMMENT 'path to proof image (QRIS)',
  `verification_status` enum('pending','verified','rejected') DEFAULT 'pending' COMMENT 'for QRIS verification',
  `verified_by` int(11) DEFAULT NULL COMMENT 'admin user_id',
  `verified_at` timestamp NULL DEFAULT NULL,
  `verification_notes` text DEFAULT NULL COMMENT 'admin notes on verification'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `payment_method`, `amount`, `paid_amount`, `change_amount`, `transaction_id`, `status`, `paid_at`, `created_by`, `created_at`, `proof_of_payment`, `verification_status`, `verified_by`, `verified_at`, `verification_notes`) VALUES
(1, 1, 'cash', 90200.00, 0.00, 0.00, NULL, 'pending', NULL, 2, '2026-06-15 19:05:27', NULL, 'pending', NULL, NULL, NULL),
(2, 2, 'cash', 58300.00, 0.00, 0.00, NULL, 'pending', NULL, 1, '2026-06-21 12:31:35', NULL, 'pending', NULL, NULL, NULL),
(3, 3, 'cash', 58300.00, 0.00, 0.00, NULL, 'pending', NULL, 1, '2026-06-21 12:31:39', NULL, 'pending', NULL, NULL, NULL),
(4, 4, 'qris', 16500.00, 0.00, 0.00, NULL, 'pending', NULL, NULL, '2026-06-21 12:34:26', NULL, 'pending', NULL, NULL, NULL),
(5, 5, 'cash', 13200.00, 0.00, 0.00, NULL, 'pending', NULL, NULL, '2026-06-21 13:09:38', NULL, 'pending', NULL, NULL, NULL),
(6, 6, 'qris', 25300.00, 0.00, 0.00, NULL, 'pending', NULL, NULL, '2026-06-21 13:26:04', NULL, 'pending', NULL, NULL, NULL),
(7, 7, 'qris', 16500.00, 0.00, 0.00, NULL, 'pending', NULL, NULL, '2026-06-21 13:28:04', NULL, 'pending', NULL, NULL, NULL),
(8, 8, 'qris', 22000.00, 22000.00, 0.00, NULL, 'success', '2026-06-30 17:36:49', NULL, '2026-06-21 13:33:20', 'payment_proofs/1782050438_0924d115de19427bbd6a2435e86ef2af.jpg', 'verified', 1, '2026-06-21 15:32:10', NULL),
(9, 9, 'qris', 8800.00, 8800.00, 0.00, NULL, 'success', '2026-06-30 17:36:49', NULL, '2026-06-21 13:49:13', 'payment_proofs/1782049860_Screenshot_2026-06-07-15-28-30-981_com.nextgen.nh.jpg', 'verified', 1, '2026-06-21 13:54:44', NULL),
(10, 10, 'qris', 16500.00, 16500.00, 0.00, NULL, 'success', '2026-06-30 17:36:49', NULL, '2026-06-21 14:01:39', 'payment_proofs/1782050969_0924d115de19427bbd6a2435e86ef2af.jpg', 'verified', 1, '2026-06-21 14:09:57', NULL),
(11, 11, 'cash', 7700.00, 0.00, 0.00, NULL, 'pending', NULL, 1, '2026-06-21 14:18:45', NULL, 'pending', NULL, NULL, NULL),
(12, 12, 'qris', 16500.00, 0.00, 0.00, NULL, 'pending', NULL, NULL, '2026-06-21 14:50:37', NULL, 'pending', NULL, NULL, NULL),
(13, 13, 'qris', 16500.00, 16500.00, 0.00, NULL, 'success', '2026-06-30 17:36:49', NULL, '2026-06-21 14:52:10', 'payment_proofs/1782053613_61b26d3bd06c4227a80117d91582814b.jpg', 'verified', 1, '2026-06-21 15:11:07', NULL),
(14, 14, 'qris', 16500.00, 0.00, 0.00, NULL, 'pending', NULL, NULL, '2026-06-21 14:53:53', NULL, 'pending', NULL, NULL, NULL),
(15, 15, 'qris', 8800.00, 8800.00, 0.00, NULL, 'success', '2026-06-30 17:36:49', NULL, '2026-06-21 14:57:10', 'payment_proofs/1782054692_Screenshot_2026-06-07-15-28-30-981_com.nextgen.nh.jpg', 'verified', 1, '2026-06-21 15:01:11', NULL),
(16, 16, 'qris', 25300.00, 25300.00, 0.00, NULL, 'success', '2026-06-30 17:34:41', NULL, '2026-06-21 15:01:53', 'payment_proofs/1782055946_1000127147.jpg', 'verified', 1, '2026-06-30 17:34:41', NULL),
(17, 17, 'qris', 16500.00, 16500.00, 0.00, NULL, 'success', '2026-06-30 17:36:49', NULL, '2026-06-21 15:03:31', NULL, 'verified', 1, '2026-06-21 15:42:33', NULL),
(18, 18, 'qris', 13200.00, 13200.00, 0.00, NULL, 'success', '2026-06-30 17:36:49', NULL, '2026-06-21 15:05:55', NULL, 'verified', 1, '2026-06-21 15:32:38', NULL),
(19, 19, 'qris', 8800.00, 8800.00, 0.00, NULL, 'success', '2026-06-30 17:36:49', NULL, '2026-06-21 15:11:46', 'payment_proofs/1782055929_Screenshot_2026-06-06-20-52-04-631_com.instagram.android.jpg', 'verified', 1, '2026-06-21 15:36:59', NULL),
(20, 20, 'qris', 8800.00, 8800.00, 0.00, NULL, 'success', '2026-06-30 17:36:49', NULL, '2026-06-21 15:32:31', 'payment_proofs/1782056116_Screenshot_2026-06-07-15-28-30-981_com.nextgen.nh.jpg', 'verified', 1, '2026-06-21 15:35:44', NULL),
(21, 21, 'cash', 29700.00, 0.00, 0.00, NULL, 'success', NULL, NULL, '2026-06-21 15:32:52', NULL, 'pending', NULL, NULL, NULL),
(22, 22, 'qris', 16500.00, 16500.00, 0.00, NULL, 'success', '2026-06-30 17:35:33', NULL, '2026-06-21 15:39:41', 'payment_proofs/1782056952_9419.jpg', 'verified', 1, '2026-06-30 17:35:33', NULL),
(23, 23, 'qris', 16500.00, 16500.00, 0.00, NULL, 'success', '2026-06-30 17:36:49', NULL, '2026-06-21 15:41:35', 'payment_proofs/1782056840_Screenshot_2026-06-07-15-28-30-981_com.nextgen.nh.jpg', 'verified', 1, '2026-06-21 15:42:02', NULL),
(24, 24, 'cash', 51700.00, 0.00, 0.00, NULL, 'pending', NULL, 1, '2026-06-21 15:50:40', NULL, 'pending', NULL, NULL, NULL),
(25, 25, 'cash', 16500.00, 0.00, 0.00, NULL, 'pending', NULL, 1, '2026-06-30 16:45:01', NULL, 'pending', NULL, NULL, NULL),
(26, 26, 'cash', 7700.00, 0.00, 0.00, NULL, 'pending', NULL, 1, '2026-06-30 16:48:00', NULL, 'pending', NULL, NULL, NULL),
(27, 27, 'cash', 7700.00, 90000.00, 82300.00, NULL, 'success', '2026-06-30 16:49:21', 2, '2026-06-30 16:49:21', NULL, 'pending', NULL, NULL, NULL),
(28, 28, 'qris', 25300.00, 25300.00, 0.00, NULL, 'success', '2026-06-30 17:50:02', NULL, '2026-06-30 17:29:19', 'payment_proofs/15818d3165f9870836b2483103fc618d.jpg', 'verified', 1, '2026-06-30 17:50:02', NULL),
(29, 29, 'qris', 15000.00, 15000.00, 0.00, NULL, 'success', '2026-07-01 03:44:36', NULL, '2026-07-01 03:44:16', 'payment_proofs/7cf72d0fa03c0d6288bea2cb78dea730.jpg', 'verified', 1, '2026-07-01 03:44:36', NULL),
(30, 30, 'qris', 15000.00, 15000.00, 0.00, NULL, 'success', '2026-07-01 04:22:31', NULL, '2026-07-01 04:22:02', 'payment_proofs/a5e9d9939b9d7e436d4aecdb9952f1f9.jpeg', 'verified', 1, '2026-07-01 04:22:31', NULL),
(31, 31, 'cash', 32000.00, 0.00, 0.00, NULL, 'pending', NULL, 1, '2026-07-01 04:23:35', NULL, 'pending', NULL, NULL, NULL),
(32, 32, 'cash', 57000.00, 0.00, 0.00, NULL, 'success', NULL, NULL, '2026-07-01 04:28:31', NULL, 'pending', NULL, NULL, NULL),
(33, 33, 'qris', 15000.00, 15000.00, 0.00, NULL, 'success', '2026-07-01 04:29:43', NULL, '2026-07-01 04:29:10', 'payment_proofs/74d2c1a1a1d2d99191c28ffadf3122c5.jpeg', 'verified', 1, '2026-07-01 04:29:43', NULL),
(34, 34, 'cash', 56000.00, 0.00, 0.00, NULL, 'pending', NULL, 1, '2026-07-01 21:12:23', NULL, 'pending', NULL, NULL, NULL),
(35, 35, 'cash', 35000.00, 0.00, 0.00, NULL, 'pending', NULL, 1, '2026-07-01 21:13:03', NULL, 'pending', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `shifts`
--

CREATE TABLE `shifts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `shift_start` timestamp NOT NULL DEFAULT current_timestamp(),
  `shift_end` timestamp NULL DEFAULT NULL,
  `initial_cash` decimal(10,2) DEFAULT 0.00,
  `final_cash` decimal(10,2) DEFAULT NULL,
  `total_sales` decimal(10,2) DEFAULT 0.00,
  `total_orders` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tables`
--

CREATE TABLE `tables` (
  `id` int(11) NOT NULL,
  `table_number` varchar(10) NOT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `capacity` int(11) DEFAULT 4,
  `status` enum('available','occupied','reserved') DEFAULT 'available',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tables`
--

INSERT INTO `tables` (`id`, `table_number`, `qr_code`, `capacity`, `status`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'A1', NULL, 2, 'available', 1, '2026-06-15 18:39:52', '2026-07-01 21:03:41'),
(2, 'A2', NULL, 2, 'available', 1, '2026-06-15 18:39:52', '2026-06-15 18:39:52'),
(3, 'A3', NULL, 4, 'available', 1, '2026-06-15 18:39:52', '2026-06-15 18:39:52'),
(4, 'A4', NULL, 4, 'available', 1, '2026-06-15 18:39:52', '2026-06-15 18:39:52'),
(5, 'A5', NULL, 6, 'available', 1, '2026-06-15 18:39:52', '2026-06-15 18:39:52'),
(6, 'B1', NULL, 2, 'available', 1, '2026-06-15 18:39:52', '2026-06-15 18:39:52'),
(7, 'B2', NULL, 4, 'available', 1, '2026-06-15 18:39:52', '2026-06-15 18:39:52'),
(8, 'B3', NULL, 4, 'available', 1, '2026-06-15 18:39:52', '2026-06-15 18:39:52'),
(9, 'B4', NULL, 6, 'available', 1, '2026-06-15 18:39:52', '2026-06-15 18:39:52'),
(10, 'B5', NULL, 8, 'available', 1, '2026-06-15 18:39:52', '2026-06-15 18:39:52');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('owner','kasir','dapur','pelayan','customer') NOT NULL DEFAULT 'customer',
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `phone`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@warkop.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'owner', '081234567890', 1, '2026-06-15 18:39:52', '2026-06-15 18:39:52'),
(2, 'kasir1', 'kasir@warkop.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kasir 1', 'kasir', '081234567891', 1, '2026-06-15 18:39:52', '2026-06-15 18:39:52'),
(3, 'dapur1', 'dapur@warkop.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Chef 1', 'dapur', '081234567892', 1, '2026-06-15 18:39:52', '2026-06-15 18:39:52'),
(4, 'pelayan1', 'pelayan@warkop.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Pelayan 1', 'pelayan', '081234567893', 1, '2026-06-15 18:39:52', '2026-06-15 18:39:52');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_active_orders`
-- (See below for the actual view)
--
CREATE TABLE `v_active_orders` (
`id` int(11)
,`order_number` varchar(20)
,`table_id` int(11)
,`table_number` varchar(10)
,`status` enum('pending','confirmed','cooking','ready','served','completed','cancelled')
,`total` decimal(10,2)
,`created_at` timestamp
,`item_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_daily_sales`
-- (See below for the actual view)
--
CREATE TABLE `v_daily_sales` (
`sales_date` date
,`total_orders` bigint(21)
,`total_sales` decimal(32,2)
,`avg_order_value` decimal(14,6)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_top_menus`
-- (See below for the actual view)
--
CREATE TABLE `v_top_menus` (
`id` int(11)
,`name` varchar(100)
,`category_id` int(11)
,`category_name` varchar(50)
,`order_count` bigint(21)
,`total_quantity` decimal(32,0)
,`total_revenue` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_table_name` (`table_name`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_sort_order` (`sort_order`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `kitchen_tickets`
--
ALTER TABLE `kitchen_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_order_item` (`order_item_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_prepared_by` (`prepared_by`);

--
-- Indexes for table `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_is_available` (`is_available`),
  ADD KEY `idx_is_recommended` (`is_recommended`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_table` (`table_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_order_type` (`order_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `fk_order_server` (`served_by`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_menu` (`menu_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_payment_method` (`payment_method`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_verification_status` (`verification_status`),
  ADD KEY `idx_verified_by` (`verified_by`);

--
-- Indexes for table `shifts`
--
ALTER TABLE `shifts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_shift_start` (`shift_start`);

--
-- Indexes for table `tables`
--
ALTER TABLE `tables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `table_number` (`table_number`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=277;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kitchen_tickets`
--
ALTER TABLE `kitchen_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=267;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `shifts`
--
ALTER TABLE `shifts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tables`
--
ALTER TABLE `tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `kitchen_tickets`
--
ALTER TABLE `kitchen_tickets`
  ADD CONSTRAINT `fk_ticket_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ticket_orderitem` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ticket_preparer` FOREIGN KEY (`prepared_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `menus`
--
ALTER TABLE `menus`
  ADD CONSTRAINT `fk_menu_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_order_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_order_server` FOREIGN KEY (`served_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_order_table` FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_orderitem_menu` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_orderitem_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_payment_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_payment_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `shifts`
--
ALTER TABLE `shifts`
  ADD CONSTRAINT `fk_shift_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
