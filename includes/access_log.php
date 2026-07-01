<?php
/**
 * Access Logger
 * Track who accesses what and when
 */

function logAccess($page, $action = 'view', $details = []) {
    $logDir = ROOT_PATH . '/logs';
    
    // Create logs directory if not exists
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/access_' . date('Y-m-d') . '.log';
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $userId = $_SESSION['user_id'] ?? 'guest';
    $userRole = $_SESSION['user_role'] ?? 'none';
    $userName = $_SESSION['user_name'] ?? 'unknown';
    
    // Build log entry
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $ipAddress,
        'user_id' => $userId,
        'user_role' => $userRole,
        'user_name' => $userName,
        'page' => $page,
        'action' => $action,
        'details' => json_encode($details),
        'user_agent' => substr($userAgent, 0, 100)
    ];
    
    // Write log
    $logLine = json_encode($logEntry) . "\n";
    file_put_contents($logFile, $logLine, FILE_APPEND);
    
    // Also log to PHP error log
    error_log("[ACCESS] {$page} - {$action} - IP:{$ipAddress} - User:{$userId}({$userRole})");
}

/**
 * Get access logs for specific date
 */
function getAccessLogs($date = null) {
    if (!$date) {
        $date = date('Y-m-d');
    }
    
    $logFile = ROOT_PATH . '/logs/access_' . $date . '.log';
    
    if (!file_exists($logFile)) {
        return [];
    }
    
    $logs = [];
    $lines = file($logFile, FILE_IGNORE_NEW_LINES);
    
    foreach ($lines as $line) {
        if (!empty($line)) {
            $logs[] = json_decode($line, true);
        }
    }
    
    return $logs;
}
