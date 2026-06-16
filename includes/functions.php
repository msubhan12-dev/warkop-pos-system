<?php
/**
 * Helper Functions
 * WARKOP OS - Low Budget Free Plan
 */

/**
 * Sanitize input data
 */
function clean($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)))), 1, $length);
}

/**
 * Generate order number
 */
function generateOrderNumber() {
    return ORDER_PREFIX . date('Ymd') . generateRandomString(4);
}

/**
 * Generate ticket number
 */
function generateTicketNumber() {
    return TICKET_PREFIX . date('Ymd') . generateRandomString(4);
}

/**
 * Format currency (IDR)
 */
function formatRupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

/**
 * Format date time
 */
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    return date($format, strtotime($datetime));
}

/**
 * Time ago format
 */
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return $difference . ' detik lalu';
    } elseif ($difference < 3600) {
        return floor($difference / 60) . ' menit lalu';
    } elseif ($difference < 86400) {
        return floor($difference / 3600) . ' jam lalu';
    } elseif ($difference < 604800) {
        return floor($difference / 86400) . ' hari lalu';
    } else {
        return formatDateTime($datetime, 'd M Y');
    }
}

/**
 * Calculate tax
 */
function calculateTax($amount) {
    return $amount * TAX_RATE;
}

/**
 * Calculate total with tax
 */
function calculateTotal($subtotal) {
    $tax = calculateTax($subtotal);
    return $subtotal + $tax;
}

/**
 * Send JSON response
 */
function sendJSON($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Get status badge class
 */
function getStatusBadge($status) {
    $badges = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'confirmed' => 'bg-blue-100 text-blue-800',
        'cooking' => 'bg-orange-100 text-orange-800',
        'ready' => 'bg-green-100 text-green-800',
        'served' => 'bg-purple-100 text-purple-800',
        'completed' => 'bg-gray-100 text-gray-800',
        'cancelled' => 'bg-red-100 text-red-800',
        'new' => 'bg-yellow-100 text-yellow-800',
        'available' => 'bg-green-100 text-green-800',
        'occupied' => 'bg-red-100 text-red-800',
        'reserved' => 'bg-blue-100 text-blue-800',
        'success' => 'bg-green-100 text-green-800',
        'failed' => 'bg-red-100 text-red-800',
    ];
    
    return $badges[$status] ?? 'bg-gray-100 text-gray-800';
}

/**
 * Get status text (Bahasa Indonesia)
 */
function getStatusText($status) {
    $texts = [
        'pending' => 'Menunggu',
        'confirmed' => 'Dikonfirmasi',
        'cooking' => 'Dimasak',
        'ready' => 'Siap',
        'served' => 'Disajikan',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
        'new' => 'Baru',
        'available' => 'Tersedia',
        'occupied' => 'Terisi',
        'reserved' => 'Dipesan',
        'success' => 'Berhasil',
        'failed' => 'Gagal',
        'dine_in' => 'Makan di Tempat',
        'take_away' => 'Bungkus',
        'cash' => 'Tunai',
        'qris' => 'QRIS',
        'transfer' => 'Transfer',
        'card' => 'Kartu',
    ];
    
    return $texts[$status] ?? ucfirst($status);
}

/**
 * Create audit log
 */
function createAuditLog($action, $tableName, $recordId = null, $oldValue = null, $newValue = null) {
    $db = getDB();
    $userId = $_SESSION['user_id'] ?? null;
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $stmt = $db->prepare("
        INSERT INTO audit_logs (user_id, action, table_name, record_id, old_value, new_value, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $userId,
        $action,
        $tableName,
        $recordId,
        $oldValue ? json_encode($oldValue) : null,
        $newValue ? json_encode($newValue) : null,
        $ipAddress,
        $userAgent
    ]);
}

/**
 * Create notification
 */
function createNotification($userId, $title, $message, $type = 'info', $link = null) {
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO notifications (user_id, title, message, type, link)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([$userId, $title, $message, $type, $link]);
}

/**
 * Broadcast notification to all users with specific role
 */
function broadcastNotification($roles, $title, $message, $type = 'info', $link = null) {
    $db = getDB();
    
    // Get all users with specified roles
    $placeholders = str_repeat('?,', count($roles) - 1) . '?';
    $stmt = $db->prepare("SELECT id FROM users WHERE role IN ($placeholders) AND is_active = 1");
    $stmt->execute($roles);
    $users = $stmt->fetchAll();
    
    // Create notification for each user
    foreach ($users as $user) {
        createNotification($user['id'], $title, $message, $type, $link);
    }
}

/**
 * Upload image
 */
function uploadImage($file, $folder = 'menu') {
    $targetDir = UPLOADS_PATH . '/' . $folder . '/';
    
    // Create directory if not exists
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    $fileName = time() . '_' . basename($file['name']);
    $targetFile = $targetDir . $fileName;
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    
    // Check if image file is actual image
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        return ['success' => false, 'message' => 'File bukan gambar'];
    }
    
    // Check file size (max 5MB)
    if ($file['size'] > 5000000) {
        return ['success' => false, 'message' => 'File terlalu besar (max 5MB)'];
    }
    
    // Allow certain file formats
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($imageFileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Format file tidak didukung'];
    }
    
    // Upload file
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return [
            'success' => true,
            'filename' => $fileName,
            'path' => $folder . '/' . $fileName
        ];
    }
    
    return ['success' => false, 'message' => 'Gagal upload file'];
}

/**
 * Get unread notification count
 */
function getUnreadNotificationCount($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    return $result['count'];
}

/**
 * Validate phone number (Indonesia format)
 */
function validatePhone($phone) {
    // Remove spaces and dashes
    $phone = preg_replace('/[\s\-]/', '', $phone);
    
    // Check if starts with +62, 62, or 0
    if (preg_match('/^(\+62|62|0)8[1-9][0-9]{6,10}$/', $phone)) {
        return true;
    }
    
    return false;
}

/**
 * Get today's statistics
 */
function getTodayStats() {
    $db = getDB();
    
    $stats = [];
    
    // Total orders today
    $stmt = $db->query("
        SELECT COUNT(*) as count FROM orders 
        WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'
    ");
    $stats['orders'] = $stmt->fetch()['count'];
    
    // Total revenue today
    $stmt = $db->query("
        SELECT COALESCE(SUM(total), 0) as total FROM orders 
        WHERE DATE(created_at) = CURDATE() AND status = 'completed'
    ");
    $stats['revenue'] = $stmt->fetch()['total'];
    
    // Active orders
    $stmt = $db->query("
        SELECT COUNT(*) as count FROM orders 
        WHERE status IN ('pending', 'confirmed', 'cooking', 'ready', 'served')
    ");
    $stats['active_orders'] = $stmt->fetch()['count'];
    
    // Available tables
    $stmt = $db->query("
        SELECT COUNT(*) as count FROM tables 
        WHERE status = 'available' AND is_active = 1
    ");
    $stats['available_tables'] = $stmt->fetch()['count'];
    
    return $stats;
}
