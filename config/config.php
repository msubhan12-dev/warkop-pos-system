<?php
/**
 * Application Configuration
 * WARKOP OS - Low Budget Free Plan
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Application settings
define('APP_NAME', 'ARRAHMANHERB');
define('APP_VERSION', '1.0.0');

// Dynamically determine the APP_URL based on how the user accesses the site
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
$basePath = str_replace(array('/admin', '/customer', '/config', '/includes', '/kasir'), '', $scriptPath);
if ($basePath == '/' || $basePath == '\\') $basePath = '';
define('APP_URL', $protocol . "://" . $host . $basePath);

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Paths
define('ROOT_PATH', dirname(__DIR__));
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');

// URL paths
define('BASE_URL', APP_URL);
define('ASSETS_URL', BASE_URL . '/assets');
define('UPLOADS_URL', BASE_URL . '/uploads');

// Tax configuration (PPN 0%)
define('TAX_RATE', 0.00);

// Order settings
define('ORDER_PREFIX', 'ORD');
define('TICKET_PREFIX', 'TKT');

// Pagination
define('ITEMS_PER_PAGE', 20);

// Session timeout (in seconds) - 8 hours
define('SESSION_TIMEOUT', 28800);

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// For production, use:
// error_reporting(0);
// ini_set('display_errors', 0);

// Load database
require_once ROOT_PATH . '/config/database.php';

// Load helper functions
require_once ROOT_PATH . '/includes/functions.php';

// Check session timeout
function checkSessionTimeout() {
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . '/login.php?timeout=1');
        exit;
    }
    $_SESSION['LAST_ACTIVITY'] = time();
}

// Check if user is logged in
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    checkSessionTimeout();
}

// Check user role
function requireRole($allowedRoles = []) {
    requireLogin();
    
    if (!in_array($_SESSION['user_role'], $allowedRoles)) {
        http_response_code(403);
        die('Access denied. You do not have permission to access this page.');
    }
}

// Get current user data
function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT id, username, email, full_name, role, phone FROM users WHERE id = ? AND is_active = 1");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Web Push Notifications - VAPID Keys
define('VAPID_PUBLIC_KEY', 'BCEllHwYolkmjq8th99VwpYGcUTQzS89-l00aSZQIker05slFill58N2lVaZw7akcKA1KusCOjnD5Y5ZqUIU9Mk');
define('VAPID_PRIVATE_KEY', 'tBHyy2Z0r4Aw8v_-sz17VQ5fiGlikiHV-8bqsMNF0xQ');
define('VAPID_SUBJECT', 'mailto:admin@arrahmanherb.my.id');

// Auto-initialize database tables and columns on first load
try {
    $db = getDB();
    
    // Check if staff_messages table exists
    $tableExists = false;
    try {
        $db->query("SELECT 1 FROM staff_messages LIMIT 1");
        $tableExists = true;
    } catch (Exception $e) {
        $tableExists = false;
    }
    
    if (!$tableExists) {
        // Create table
        $db->exec("CREATE TABLE IF NOT EXISTS `staff_messages` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `sender_id` int(11) NOT NULL,
            `recipient_id` int(11) NOT NULL,
            `message` text,
            `message_type` enum('text','image','sticker') DEFAULT 'text',
            `media_url` varchar(255) DEFAULT NULL,
            `is_read` tinyint(1) DEFAULT 0,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_sender_recipient` (`sender_id`, `recipient_id`),
            KEY `idx_is_read` (`is_read`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } else {
        // Table exists, check and add missing columns
        $columns_result = $db->query("DESCRIBE staff_messages")->fetchAll(PDO::FETCH_ASSOC);
        $existing_columns = array_column($columns_result, 'Field');
        
        // Add message_type if missing
        if (!in_array('message_type', $existing_columns)) {
            try {
                $db->exec("ALTER TABLE staff_messages ADD COLUMN message_type enum('text','image','sticker') DEFAULT 'text'");
            } catch (Exception $e) {}
        }
        
        // Add media_url if missing
        if (!in_array('media_url', $existing_columns)) {
            try {
                $db->exec("ALTER TABLE staff_messages ADD COLUMN media_url varchar(255) DEFAULT NULL");
            } catch (Exception $e) {}
        }
        
        // Add is_read if missing
        if (!in_array('is_read', $existing_columns)) {
            try {
                $db->exec("ALTER TABLE staff_messages ADD COLUMN is_read tinyint(1) DEFAULT 0");
            } catch (Exception $e) {}
        }
    }
    
    // Check if push_subscriptions table exists
    $pushTableExists = false;
    try {
        $db->query("SELECT 1 FROM push_subscriptions LIMIT 1");
        $pushTableExists = true;
    } catch (Exception $e) {
        $pushTableExists = false;
    }
    
    if (!$pushTableExists) {
        // Create push subscriptions table
        $db->exec("CREATE TABLE IF NOT EXISTS `push_subscriptions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `endpoint` text NOT NULL,
            `auth_key` varchar(255) DEFAULT NULL,
            `p256dh_key` varchar(255) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
} catch (Exception $e) {
    // Silently fail - not critical
}
