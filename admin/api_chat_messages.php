<?php
require_once '../config/config.php';
requireRole(['owner', 'kasir', 'dapur', 'pelayan']);

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$db = getDB();

if ($action === 'get_messages') {
    $order_id = intval($_GET['order_id'] ?? 0);
    
    if (!$order_id) {
        echo json_encode(['success' => false, 'message' => 'Order ID required']);
        exit;
    }
    
    $stmt = $db->prepare("
        SELECT cm.*, u.username, u.full_name
        FROM chat_messages cm
        JOIN users u ON cm.sender_id = u.id
        WHERE cm.order_id = ?
        ORDER BY cm.created_at ASC
        LIMIT 100
    ");
    $stmt->execute([$order_id]);
    $messages = $stmt->fetchAll();
    
    // Mark as read
    $db->prepare("UPDATE chat_messages SET is_read = 1 WHERE order_id = ? AND sender_id != ?")->execute([$order_id, $_SESSION['user_id']]);
    
    echo json_encode(['success' => true, 'messages' => $messages]);
    
} elseif ($action === 'send_message') {
    $order_id = intval($_POST['order_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    $tagged_user_id = intval($_POST['tagged_user_id'] ?? 0);
    
    if (!$order_id || !$message) {
        echo json_encode(['success' => false, 'message' => 'Order ID and message required']);
        exit;
    }
    
    $message_type = 'text';
    if ($tagged_user_id > 0) {
        $message_type = 'tag';
    }
    
    try {
        $stmt = $db->prepare("
            INSERT INTO chat_messages (order_id, sender_id, sender_type, message, message_type, tagged_user_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$order_id, $_SESSION['user_id'], 'staff', $message, $message_type, $tagged_user_id ?: null]);
        
        echo json_encode(['success' => true, 'message' => 'Message sent']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
} elseif ($action === 'get_unread_count') {
    $order_id = intval($_GET['order_id'] ?? 0);
    
    $stmt = $db->prepare("
        SELECT COUNT(*) as unread_count FROM chat_messages
        WHERE order_id = ? AND is_read = 0 AND sender_id != ?
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $result = $stmt->fetch();
    
    echo json_encode(['success' => true, 'unread_count' => $result['unread_count']]);
    
} elseif ($action === 'get_staff') {
    $stmt = $db->query("SELECT id, username, full_name, role FROM users WHERE is_active = 1 AND role IN ('owner','kasir','dapur','pelayan')");
    $staff = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'staff' => $staff]);
}
?>
