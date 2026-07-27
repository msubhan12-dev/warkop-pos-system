<?php
/**
 * Push Notification API
 * Handles subscription and push notification sending for staff chat
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_REQUEST['action'] ?? null;
$db = getDB();
$user_id = $_SESSION['user_id'];

try {
    if ($action === 'subscribe') {
        // Subscribe to push notifications
        // Client sends: endpoint, auth_key, p256dh_key
        
        $endpoint = $_REQUEST['endpoint'] ?? '';
        $auth_key = $_REQUEST['auth_key'] ?? '';
        $p256dh_key = $_REQUEST['p256dh_key'] ?? '';
        
        if (!$endpoint) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing endpoint']);
            exit;
        }
        
        // Check if subscription already exists for this user and endpoint
        $stmt = $db->prepare("SELECT id FROM push_subscriptions WHERE user_id = ? AND endpoint = ?");
        $stmt->execute([$user_id, $endpoint]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing subscription
            $stmt = $db->prepare("UPDATE push_subscriptions SET auth_key = ?, p256dh_key = ?, updated_at = NOW() WHERE user_id = ? AND endpoint = ?");
            $stmt->execute([$auth_key, $p256dh_key, $user_id, $endpoint]);
        } else {
            // Insert new subscription
            $stmt = $db->prepare("INSERT INTO push_subscriptions (user_id, endpoint, auth_key, p256dh_key) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $endpoint, $auth_key, $p256dh_key]);
        }
        
        error_log("Push subscription saved for user $user_id");
        echo json_encode(['success' => true, 'message' => 'Subscription saved']);
        
    } elseif ($action === 'unsubscribe') {
        // Unsubscribe from push notifications
        $endpoint = $_REQUEST['endpoint'] ?? '';
        
        if (!$endpoint) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing endpoint']);
            exit;
        }
        
        $stmt = $db->prepare("DELETE FROM push_subscriptions WHERE user_id = ? AND endpoint = ?");
        $stmt->execute([$user_id, $endpoint]);
        
        echo json_encode(['success' => true, 'message' => 'Subscription removed']);
        
    } elseif ($action === 'send_message_notification') {
        // Called from api_staff_chat.php when a message is received
        // Body: recipient_id, sender_id, message, message_type
        
        $recipient_id = $_REQUEST['recipient_id'] ?? 0;
        $sender_id = $_REQUEST['sender_id'] ?? 0;
        $message = $_REQUEST['message'] ?? '';
        $message_type = $_REQUEST['message_type'] ?? 'text';
        
        if (!$recipient_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid recipient']);
            exit;
        }
        
        // Get recipient's push subscriptions
        $stmt = $db->prepare("SELECT * FROM push_subscriptions WHERE user_id = ?");
        $stmt->execute([$recipient_id]);
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($subscriptions)) {
            error_log("No push subscriptions for user $recipient_id");
            echo json_encode(['success' => true, 'message' => 'No subscriptions', 'sent' => 0]);
            exit;
        }
        
        // Get sender info
        $stmt = $db->prepare("SELECT full_name FROM users WHERE id = ?");
        $stmt->execute([$sender_id]);
        $sender = $stmt->fetch(PDO::FETCH_ASSOC);
        $sender_name = $sender['full_name'] ?? 'Tim';
        
        // Prepare notification message
        if ($message_type === 'image') {
            $notif_msg = $sender_name . ' mengirim foto';
        } elseif ($message_type === 'sticker') {
            $notif_msg = $sender_name . ' mengirim sticker';
        } else {
            $notif_msg = substr($message, 0, 100);
        }
        
        // Send push notification to each subscription
        $sent_count = 0;
        $failed_count = 0;
        
        foreach ($subscriptions as $sub) {
            $push_result = sendPushNotification(
                $sub['endpoint'],
                $sub['auth_key'],
                $sub['p256dh_key'],
                [
                    'title' => $sender_name,
                    'message' => $notif_msg,
                    'sender_id' => $sender_id,
                    'recipient_id' => $recipient_id,
                    'url' => BASE_URL . '/admin/staff_chat.php'
                ]
            );
            
            if ($push_result === true) {
                $sent_count++;
            } else {
                $failed_count++;
                error_log("Push send failed: " . $push_result);
            }
        }
        
        error_log("Push notifications sent: $sent_count, failed: $failed_count");
        echo json_encode(['success' => true, 'message' => 'Notifications sent', 'sent' => $sent_count, 'failed' => $failed_count]);
        
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log("Push API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Send push notification using Web Push protocol
 * Simplified version - sends JSON directly
 * For production, use: minishlink/web-push library with proper VAPID signing
 */
function sendPushNotification($endpoint, $auth_key, $p256dh_key, $data) {
    try {
        if (!function_exists('curl_init')) {
            return 'curl not available';
        }
        
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/octet-stream',
                'TTL: 3600',
                'Urgency: high'
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        
        curl_close($ch);
        
        // 201 Created or 200 OK is success
        if ($http_code >= 200 && $http_code < 300) {
            return true;
        } else if ($http_code === 401 || $http_code === 410) {
            // 401 Unauthorized or 410 Gone - subscription invalid
            return "Invalid subscription (HTTP $http_code)";
        } else {
            return "HTTP $http_code: $curl_error";
        }
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

