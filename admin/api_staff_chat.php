<?php
// Output buffering to prevent accidental output before JSON header
ob_start();

try {
    require_once '../config/config.php';
    requireRole(['owner', 'kasir', 'dapur', 'pelayan']);
    
    // Clear buffer
    ob_end_clean();
    header('Content-Type: application/json');
    
    $action = $_REQUEST['action'] ?? $_GET['action'] ?? $_POST['action'] ?? null;
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
        
    } elseif ($action === 'get_messages') {
        $other_user_id = intval($_REQUEST['user_id'] ?? $_GET['user_id'] ?? 0);
        
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
            
            $upload_result = uploadImage($_FILES['image'], 'chat');
            if (!is_array($upload_result) || !$upload_result['success']) {
                $err_msg = is_array($upload_result) ? $upload_result['message'] : 'Upload failed';
                echo json_encode(['success' => false, 'message' => $err_msg]);
                exit;
            }
            
            // Build proper URL path - ensure it's a string
            $path = $upload_result['path'];
            $media_url = UPLOADS_URL . '/' . $path;
            // Ensure media_url is string, not array
            if (is_array($media_url)) {
                $media_url = json_encode($media_url);
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
            
            error_log("Message inserted - type: $message_type, url: $media_url");
            
            $msg_id = $db->lastInsertId();
            
            // Send notification via Service Worker message (for open browser)
            try {
                $push_data = [
                    'action' => 'send_message_notification',
                    'recipient_id' => $recipient_id,
                    'sender_id' => $_SESSION['user_id'],
                    'message' => $message ?: ($message_type === 'image' ? '[Foto]' : '[Sticker]'),
                    'message_type' => $message_type
                ];
                
                $context = stream_context_create([
                    'http' => [
                        'method' => 'POST',
                        'header' => 'Content-Type: application/x-www-form-urlencoded',
                        'content' => http_build_query($push_data),
                        'timeout' => 3
                    ]
                ]);
                
                @file_get_contents(BASE_URL . '/admin/api_push_notification.php', false, $context);
            } catch (Exception $e) {
                error_log("Push notification trigger error: " . $e->getMessage());
                // Don't fail the message send if push fails
            }
            
            echo json_encode(['success' => true, 'message' => 'Message sent', 'id' => $msg_id]);
        } catch (Exception $e) {
            error_log("Insert error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid action'
        ]);
    }

} catch (Exception $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
