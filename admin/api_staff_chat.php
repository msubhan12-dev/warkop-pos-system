<?php
require_once '../config/config.php';
requireRole(['owner', 'kasir', 'dapur', 'pelayan']);

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$db = getDB();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($action === 'get_conversations') {
    $stmt = $db->prepare("
        SELECT DISTINCT 
            CASE WHEN sender_id = ? THEN recipient_id ELSE sender_id END as user_id
        FROM staff_messages
        WHERE sender_id = ? OR recipient_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $conversations = [];
    foreach ($users as $u) {
        $s2 = $db->prepare("SELECT id, full_name FROM users WHERE id = ?");
        $s2->execute([$u['user_id']]);
        $udata = $s2->fetch(PDO::FETCH_ASSOC);
        
        $s3 = $db->prepare("
            SELECT message FROM staff_messages 
            WHERE (sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?)
            ORDER BY id DESC LIMIT 1
        ");
        $s3->execute([$_SESSION['user_id'], $u['user_id'], $u['user_id'], $_SESSION['user_id']]);
        $last = $s3->fetch(PDO::FETCH_ASSOC);
        
        if ($udata && $last) {
            $conversations[] = array_merge($udata, ['last_msg' => $last['message']]);
        }
    }
    
    echo json_encode(['success' => true, 'conversations' => $conversations]);
    exit;
}

if ($action === 'get_messages') {
    $other_user_id = intval($_GET['user_id'] ?? 0);
    
    if (!$other_user_id) {
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        exit;
    }
    
    $stmt = $db->prepare("
        SELECT sm.*, u.username, u.full_name
        FROM staff_messages sm
        JOIN users u ON sm.sender_id = u.id
        WHERE (sm.sender_id = ? AND sm.recipient_id = ?)
           OR (sm.sender_id = ? AND sm.recipient_id = ?)
        ORDER BY sm.created_at ASC
        LIMIT 200
    ");
    $stmt->execute([$_SESSION['user_id'], $other_user_id, $other_user_id, $_SESSION['user_id']]);
    $messages = $stmt->fetchAll();
    
    $db->prepare("UPDATE staff_messages SET is_read = 1 WHERE recipient_id = ? AND sender_id = ?")->execute([$_SESSION['user_id'], $other_user_id]);
    
    echo json_encode(['success' => true, 'messages' => $messages]);
    
} elseif ($action === 'send_message') {
    $recipient_id = intval($_POST['recipient_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    $message_type = $_POST['message_type'] ?? 'text';
    
    if (!$recipient_id) {
        echo json_encode(['success' => false, 'message' => 'Recipient required']);
        exit;
    }
    
    if ($message_type === 'text' && !$message) {
        echo json_encode(['success' => false, 'message' => 'Message required']);
        exit;
    }
    
    $media_url = null;
    
    if ($message_type === 'image') {
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Image upload failed']);
            exit;
        }
        
        $media_url = uploadImage($_FILES['image']);
        if (!$media_url) {
            echo json_encode(['success' => false, 'message' => 'Image save failed']);
            exit;
        }
    } elseif ($message_type === 'sticker') {
        $media_url = $message;
        $message = null;
    }
    
    try {
        $stmt = $db->prepare("
            INSERT INTO staff_messages (sender_id, recipient_id, message, message_type, media_url)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $recipient_id, $message, $message_type, $media_url]);
        
        echo json_encode(['success' => true, 'message' => 'Message sent', 'id' => $db->lastInsertId()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function uploadImage($file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        error_log("Upload error: " . $file['error']);
        return null;
    }
    
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed)) {
        error_log("Invalid type: " . $file['type']);
        return null;
    }
    
    if ($file['size'] > 5 * 1024 * 1024) {
        error_log("File too large: " . $file['size']);
        return null;
    }
    
    $uploadDir = __DIR__ . '/../uploads/chat';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            error_log("Cannot create upload dir");
            return null;
        }
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        $ext = 'jpg';
    }
    
    $filename = 'chat_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $filepath = $uploadDir . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        chmod($filepath, 0644);
        return '/uploads/chat/' . $filename;
    }
    
    error_log("Cannot move file to: " . $filepath);
    return null;
}
?>
