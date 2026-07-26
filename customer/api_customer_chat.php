<?php
require_once '../config/config.php';

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
        SELECT cm.*, u.full_name, u.username
        FROM customer_support_messages csm
        LEFT JOIN users u ON csm.staff_id = u.id
        WHERE csm.order_id = ?
        ORDER BY csm.created_at ASC
        LIMIT 100
    ");
    $stmt->execute([$order_id]);
    $messages = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'messages' => $messages]);
    
} elseif ($action === 'send_message') {
    $order_id = intval($_POST['order_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    $sender_type = $_POST['sender_type'] ?? 'customer'; // 'customer' or 'staff'
    
    if (!$order_id || !$message) {
        echo json_encode(['success' => false, 'message' => 'Order ID and message required']);
        exit;
    }
    
    try {
        $staff_id = null;
        if ($sender_type === 'staff' && isset($_SESSION['user_id'])) {
            $staff_id = $_SESSION['user_id'];
        }
        
        $stmt = $db->prepare("
            INSERT INTO customer_support_messages (order_id, sender_type, staff_id, message)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$order_id, $sender_type, $staff_id, $message]);
        
        echo json_encode(['success' => true, 'message' => 'Message sent']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
