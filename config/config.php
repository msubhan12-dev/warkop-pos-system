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

// For localhost development
// define('APP_URL', 'http://localhost/warkop');

// For network access (ganti 10.143.149.22 dengan IP Mac lo)
define('APP_URL', 'http://10.143.149.22/warkop');

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
        header('Location: ' . BASE_URL . '/index.php?timeout=1');
        exit;
    }
    $_SESSION['LAST_ACTIVITY'] = time();
}

// Check if user is logged in
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/index.php');
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
