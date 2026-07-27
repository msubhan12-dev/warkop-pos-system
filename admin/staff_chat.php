<?php
require_once '../config/config.php';
requireRole(['owner', 'kasir', 'dapur', 'pelayan']);

$db = getDB();
$current_user_id = $_SESSION['user_id'] ?? 0;

$conversations = [];
try {
    $stmt = $db->prepare("
        SELECT DISTINCT CASE WHEN sender_id = ? THEN recipient_id ELSE sender_id END as user_id
        FROM staff_messages
        WHERE sender_id = ? OR recipient_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$current_user_id, $current_user_id, $current_user_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $u) {
        $s2 = $db->prepare("SELECT id, full_name FROM users WHERE id = ?");
        $s2->execute([$u['user_id']]);
        $udata = $s2->fetch(PDO::FETCH_ASSOC);
        
        $s3 = $db->prepare("
            SELECT message FROM staff_messages 
            WHERE (sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?)
            ORDER BY id DESC LIMIT 1
        ");
        $s3->execute([$current_user_id, $u['user_id'], $u['user_id'], $current_user_id]);
        $last = $s3->fetch(PDO::FETCH_ASSOC);
        
        if ($udata && $last) {
            $conversations[] = array_merge($udata, ['last_msg' => $last['message']]);
        }
    }
} catch (Exception $e) {}

$all_staff = [];
try {
    $stmt = $db->prepare("
        SELECT id, full_name FROM users 
        WHERE is_active = 1 AND role IN ('owner','kasir','dapur','pelayan') 
        AND id != ? ORDER BY full_name
    ");
    $stmt->execute([$current_user_id]);
    $all_staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Chat Tim</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="manifest" href="manifest.json">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; width: 100%; overflow: hidden; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #fff; }
        
        .app { display: flex; width: 100%; height: 100%; }
        
        .sidebar { 
            width: 300px; 
            background: #fff; 
            border-right: 1px solid #ddd; 
            display: flex; 
            flex-direction: column;
        }
        
        .sidebar-header {
            padding: 14px;
            background: linear-gradient(135deg, #128C7E 0%, #0d7164 100%);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }
        
        .sidebar-header h2 { font-size: 18px; font-weight: 600; }
        .btn-new { background: none; border: none; color: white; font-size: 18px; cursor: pointer; padding: 4px; }
        
        .search-box { padding: 10px; flex-shrink: 0; }
        .search-box input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 20px;
            font-size: 13px;
            outline: none;
        }
        .search-box input:focus { border-color: #128C7E; background: #f9f9f9; }
        
        .list-chats { flex: 1; overflow-y: auto; }
        
        .chat-item {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            gap: 10px;
            align-items: center;
            transition: 0.15s;
        }
        
        .chat-item:hover { background: #f5f5f5; }
        .chat-item.active { background: #e8f5e9; }
        
        .avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #128C7E, #0d7164);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            flex-shrink: 0;
            font-size: 16px;
        }
        
        .chat-info { flex: 1; min-width: 0; }
        .chat-name { font-weight: 500; font-size: 14px; color: #000; }
        .chat-preview { font-size: 12px; color: #999; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        
        .unread-badge {
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
            flex-shrink: 0;
        }
        
        .empty-chats {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #ccc;
        }
        
        .empty-chats i { font-size: 48px; margin-bottom: 10px; }
        
        .main { flex: 1; display: flex; flex-direction: column; background: #fff; }
        
        .chat-header {
            padding: 12px 16px;
            background: linear-gradient(135deg, #128C7E 0%, #0d7164 100%);
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }
        
        .btn-back { display: none; background: none; border: none; color: white; font-size: 20px; cursor: pointer; padding: 4px; }
        .btn-back.show { display: block; }
        
        .chat-title { flex: 1; }
        .chat-title h3 { font-size: 14px; font-weight: 600; margin: 0; }
        .chat-title p { font-size: 11px; opacity: 0.8; margin: 0; }
        
        .messages-box {
            flex: 1;
            overflow-y: auto;
            padding: 12px;
            background: #ece5dd;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        
        .msg-item { display: flex; margin-bottom: 4px; }
        .msg-item.sent { justify-content: flex-end; }
        
        .msg-content { display: flex; flex-direction: column; align-items: flex-start; max-width: 70%; }
        .msg-item.sent .msg-content { align-items: flex-end; }
        
        .bubble {
            padding: 8px 12px;
            border-radius: 12px;
            font-size: 14px;
            line-height: 1.3;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        .msg-item.sent .bubble { background: #dcf8c6; color: #000; }
        .msg-item.received .bubble { background: white; color: #000; box-shadow: 0 1px 1px rgba(0,0,0,0.08); }
        
        .msg-time { font-size: 11px; color: #999; margin-top: 2px; padding: 0 6px; }
        
        .input-box {
            padding: 12px;
            background: #f0f0f0;
            display: none;
            gap: 8px;
            align-items: flex-end;
            flex-shrink: 0;
            border-top: 1px solid #ddd;
            flex-wrap: wrap;
        }
        
        .input-box.show { display: flex; }
        
        .input-box input {
            flex: 1;
            padding: 10px 14px;
            border: 1px solid #ccc;
            border-radius: 20px;
            font-size: 14px;
            outline: none;
            background: white;
        }
        
        .input-box input:focus { border-color: #128C7E; }
        
        .btn-send { background: none; border: none; color: #128C7E; font-size: 18px; cursor: pointer; padding: 4px 8px; }
        
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
        }
        
        .sticker-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin: 16px 0;
        }
        
        .sticker-btn {
            background: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 12px;
            font-size: 28px;
            cursor: pointer;
            transition: 0.2s;
        }
        
        .sticker-btn:hover {
            background: #e8e8e8;
            transform: scale(1.1);
        }
        
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.show { display: flex; }
        
        .modal-box { background: white; border-radius: 12px; padding: 20px; width: 90%; max-width: 340px; }
        .modal-title { font-size: 16px; font-weight: 600; margin-bottom: 14px; }
        
        .modal-select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 16px;
            outline: none;
        }
        
        .modal-select:focus { border-color: #128C7E; }
        
        .modal-buttons { display: flex; gap: 8px; justify-content: flex-end; }
        .modal-btn { padding: 10px 18px; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 500; }
        .modal-btn-cancel { background: #e0e0e0; color: #333; }
        .modal-btn-ok { background: #128C7E; color: white; }
        
        .list-chats::-webkit-scrollbar, .messages-box::-webkit-scrollbar { width: 6px; }
        .list-chats::-webkit-scrollbar-thumb, .messages-box::-webkit-scrollbar-thumb { background: #ccc; border-radius: 3px; }
        
        @media (max-width: 800px) {
            .app { flex-direction: column; }
            
            .sidebar {
                width: 100%;
                border-right: 0;
                border-bottom: 1px solid #ddd;
                max-height: 45%;
            }
            
            .main {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                width: 100%;
                height: 100%;
                display: none;
                z-index: 100;
            }
            
            .main.show {
                display: flex;
            }
            
            .btn-back.show {
                display: block;
            }
            
            .msg-content {
                max-width: 85%;
            }
        }
    </style>
</head>
<body>
    <div class="app">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>Chats</h2>
                <button class="btn-new" id="btnNew"><i class="fas fa-pen"></i></button>
            </div>
            
            <div class="search-box">
                <input type="text" id="search" placeholder="Cari...">
            </div>
            
            <div class="list-chats" id="list">
                <?php if (empty($conversations)): ?>
                <div class="empty-chats"><i class="fas fa-inbox"></i><p>Belum ada chat</p></div>
                <?php else: ?>
                <?php foreach ($conversations as $c): ?>
                <div class="chat-item" data-user-id="<?= $c['id'] ?>" data-user-name="<?= htmlspecialchars($c['full_name']) ?>" style="cursor: pointer;">
                    <div class="avatar"><?= substr($c['full_name'], 0, 1) ?></div>
                    <div class="chat-info">
                        <div class="chat-name"><?= htmlspecialchars($c['full_name']) ?></div>
                        <div class="chat-preview"><?= htmlspecialchars(substr($c['last_msg'] ?? '', 0, 50)) ?></div>
                    </div>
                    <span class="unread-badge" id="badge-<?= $c['id'] ?>" style="display:none;">1</span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="main" id="main">
            <div class="chat-header">
                <button class="btn-back" id="back" onclick="closeChat()"><i class="fas fa-chevron-left"></i></button>
                <div class="chat-title">
                    <h3 id="title"></h3>
                    <p>online</p>
                </div>
            </div>
            
            <div class="messages-box" id="messages"></div>
            
            <div class="input-box" id="input">
                <input type="file" id="imageInput" style="display:none;" accept="image/*">
                <button class="btn-send" onclick="document.getElementById('imageInput').click()" title="Upload Foto"><i class="fas fa-image"></i></button>
                <button class="btn-send" onclick="openStickerPicker()" title="Sticker"><i class="fas fa-face-smile"></i></button>
                <input type="text" id="msg" placeholder="Ketik pesan..." autocomplete="off">
                <button class="btn-send" onclick="sendMsg()"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>
    
    <div class="modal" id="modal">
        <div class="modal-box">
            <div class="modal-title">Mulai Chat Baru</div>
            <select class="modal-select" id="select">
                <option value="">-- Pilih Karyawan --</option>
                <?php foreach ($all_staff as $s): ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-cancel">Batal</button>
                <button class="modal-btn modal-btn-ok">OK</button>
            </div>
        </div>
    </div>
    
    <div class="modal" id="stickerModal">
        <div class="modal-box" style="max-width: 300px;">
            <div class="modal-title">Pilih Sticker</div>
            <div class="sticker-grid">
                <button class="sticker-btn" onclick="sendSticker('😀')">😀</button>
                <button class="sticker-btn" onclick="sendSticker('😂')">😂</button>
                <button class="sticker-btn" onclick="sendSticker('❤️')">❤️</button>
                <button class="sticker-btn" onclick="sendSticker('👍')">👍</button>
                <button class="sticker-btn" onclick="sendSticker('🔥')">🔥</button>
                <button class="sticker-btn" onclick="sendSticker('😍')">😍</button>
                <button class="sticker-btn" onclick="sendSticker('🎉')">🎉</button>
                <button class="sticker-btn" onclick="sendSticker('😎')">😎</button>
                <button class="sticker-btn" onclick="sendSticker('😴')">😴</button>
                <button class="sticker-btn" onclick="sendSticker('😤')">😤</button>
                <button class="sticker-btn" onclick="sendSticker('🤔')">🤔</button>
                <button class="sticker-btn" onclick="sendSticker('👋')">👋</button>
            </div>
        </div>
    </div>

<script>
let userId = 0, pollTimer;
const VAPID_PUBLIC_KEY = '<?= VAPID_PUBLIC_KEY ?>';

// Initialize Push Notifications
async function initPushNotifications() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        console.log('[Push] Browser does not support push notifications');
        return;
    }
    
    try {
        console.log('[Push] Registering Service Worker...');
        const registration = await navigator.serviceWorker.register('/service-worker.js', {
            scope: '/'
        });
        
        console.log('[Push] Service Worker registered:', registration);
        
        // Wait for service worker to be active
        await navigator.serviceWorker.ready;
        console.log('[Push] Service Worker is ready');
        
        // Get the registration again to ensure we have the ready one
        const reg = await navigator.serviceWorker.ready;
        
        if (!reg.pushManager) {
            console.log('[Push] Push manager not available on this browser');
            return;
        }
        
        // Try to subscribe to push
        const subscription = await reg.pushManager.getSubscription();
        
        if (subscription) {
            console.log('[Push] Already subscribed');
            // Send subscription to server
            sendSubscriptionToServer(subscription, 'subscribe');
        } else {
            // Request permission and subscribe
            console.log('[Push] Requesting notification permission...');
            const permission = await Notification.requestPermission();
            
            if (permission === 'granted') {
                console.log('[Push] Permission granted, subscribing...');
                
                // Subscribe to push
                const newSubscription = await reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
                });
                
                console.log('[Push] Subscribed:', newSubscription);
                sendSubscriptionToServer(newSubscription, 'subscribe');
            } else {
                console.log('[Push] Notification permission denied or dismissed');
            }
        }
    } catch (error) {
        console.error('[Push] Error initializing push notifications:', error);
    }
}

// Convert VAPID key to Uint8Array
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');
    
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    
    return outputArray;
}

// Send subscription to server
function sendSubscriptionToServer(subscription, action) {
    const data = {
        action: action,
        endpoint: subscription.endpoint
    };
    
    if (subscription.getKey) {
        const authKey = subscription.getKey('auth');
        const p256dhKey = subscription.getKey('p256dh');
        
        if (authKey) {
            data.auth_key = btoa(String.fromCharCode.apply(null, new Uint8Array(authKey)));
        }
        if (p256dhKey) {
            data.p256dh_key = btoa(String.fromCharCode.apply(null, new Uint8Array(p256dhKey)));
        }
    }
    
    fetch('/admin/api_push_notification.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams(data)
    })
    .then(r => r.json())
    .then(d => {
        console.log('[Push] Server response:', d);
    })
    .catch(err => {
        console.error('[Push] Error sending subscription:', err);
    });
}

function loadConversations() {
    console.log('loadConversations called');
    fetch('api_staff_chat.php?action=get_conversations', {
        method: 'GET',
        headers: {
            'Accept': 'application/json'
        }
    })
        .then(r => {
            console.log('Response status:', r.status);
            if (!r.ok) {
                console.error('HTTP error:', r.status);
                return null;
            }
            return r.text();
        })
        .then(text => {
            console.log('Response text:', text.substring(0, 200));
            if (!text) return;
            try {
                const d = JSON.parse(text);
                console.log('Conversations loaded:', d);
                if (d.success && d.conversations && d.conversations.length > 0) {
                    updateConversationsList(d.conversations);
                } else if (d.success && (!d.conversations || d.conversations.length === 0)) {
                    const list = document.getElementById('list');
                    if (!list.querySelector('.empty-chats')) {
                        list.innerHTML = '<div class="empty-chats"><i class="fas fa-inbox"></i><p>Belum ada chat</p></div>';
                    }
                } else {
                    console.warn('Unexpected response:', d);
                }
            } catch (e) {
                console.error('JSON parse error in loadConversations:', e);
                console.error('Response was:', text.substring(0, 500));
            }
        })
        .catch(err => console.error('Load conv error:', err));
}

function updateConversationsList(convs) {
    const list = document.getElementById('list');
    
    console.log('updateConversationsList called with:', convs);
    
    if (!convs || convs.length === 0) {
        console.log('No conversations, showing empty state');
        if (!list.querySelector('.empty-chats')) {
            list.innerHTML = '<div class="empty-chats"><i class="fas fa-inbox"></i><p>Belum ada chat</p></div>';
        }
        return;
    }
    
    console.log('Rendering ' + convs.length + ' conversations');
    list.innerHTML = '';
    convs.forEach((c, idx) => {
        console.log('Rendering conversation ' + idx + ':', c);
        const item = document.createElement('div');
        item.className = 'chat-item';
        item.dataset.userId = c.id;
        item.dataset.userName = c.full_name;
        item.style.cursor = 'pointer';
        
        item.innerHTML = `
            <div class="avatar">${c.full_name.charAt(0)}</div>
            <div class="chat-info">
                <div class="chat-name">${c.full_name}</div>
                <div class="chat-preview">${(c.last_msg || '').substring(0, 50)}</div>
            </div>
            <span class="unread-badge" id="badge-${c.id}" style="display:none;">1</span>
        `;
        
        list.appendChild(item);
    });
    
    attachConversationListeners();
}

function attachConversationListeners() {
    console.log('Attaching conversation listeners...');
    const items = document.querySelectorAll('.chat-item');
    console.log('Found ' + items.length + ' chat items');
    
    items.forEach(item => {
        item.removeEventListener('click', handleChatItemClick);
        item.addEventListener('click', handleChatItemClick);
    });
}

function checkUnreadMessages() {
    // Check each conversation for unread messages
    document.querySelectorAll('.chat-item').forEach(item => {
        const userId = parseInt(item.dataset.userId);
        const badge = document.getElementById(`badge-${userId}`);
        
        if (badge && userId !== window.currentOpenUserId) {
            // Show badge jika ada chat yang belum dibuka
            fetch(`/admin/api_staff_chat.php?action=get_messages&user_id=${userId}`)
                .then(r => r.json())
                .then(d => {
                    if (d.messages && d.messages.length > 0) {
                        // Ada pesan yang belum dibaca
                        const unread = d.messages.filter(m => !m.is_read && m.recipient_id == <?= $current_user_id ?>).length;
                        if (unread > 0) {
                            badge.textContent = unread > 9 ? '9+' : unread;
                            badge.style.display = 'flex';
                            
                            // Play sound untuk notifikasi
                            playNotificationSound();
                            
                            // Show browser notification via Service Worker
                            const lastMsg = d.messages[d.messages.length - 1];
                            const sender = d.messages[d.messages.length - 1].full_name || 'Tim';
                            const msgPreview = lastMsg.message ? lastMsg.message.substring(0, 50) : '[Foto/Sticker]';
                            
                            if ('serviceWorker' in navigator) {
                                navigator.serviceWorker.ready.then(reg => {
                                    if (reg.controller) {
                                        reg.controller.postMessage({
                                            type: 'show_notification',
                                            title: sender,
                                            message: msgPreview,
                                            url: '/admin/staff_chat.php',
                                            sender_id: lastMsg.sender_id,
                                            recipient_id: lastMsg.recipient_id
                                        });
                                    }
                                });
                            }
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                })
                .catch(err => console.error('Unread check error:', err));
        }
    });
}

function handleChatItemClick(e) {
    e.stopPropagation();
    e.preventDefault();
    const uid = parseInt(this.dataset.userId);
    const uname = this.dataset.userName;
    console.log('Chat item clicked - userId:', uid, 'userName:', uname);
    if (uid && uname) {
        openChat(uid, uname);
    } else {
        console.error('Invalid user data:', uid, uname);
    }
}

function openChat(id, name) {
    console.log('openChat called with:', id, name);
    userId = id;
    window.currentOpenUserId = id; // Track which chat is open
    
    document.querySelectorAll('.chat-item').forEach(el => el.classList.remove('active'));
    
    document.getElementById('title').textContent = name;
    document.getElementById('main').classList.add('show');
    document.getElementById('input').classList.add('show');
    document.getElementById('back').classList.add('show');
    
    if (window.innerWidth <= 800) {
        document.getElementById('sidebar').style.display = 'none';
    }
    
    document.getElementById('msg').focus();
    loadMessages();
    // Enable polling - ultra fast for instant real-time (100ms)
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(() => {
        loadMessages();
    }, 100);
}

function loadMessages() {
    if (!userId) return;
    
    console.log('loadMessages called with userId:', userId);
    fetch(`api_staff_chat.php?action=get_messages&user_id=${userId}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json'
        }
    })
        .then(r => {
            console.log('Messages response status:', r.status);
            if (!r.ok) {
                console.error('HTTP error:', r.status);
                return null;
            }
            return r.text();
        })
        .then(text => {
            if (!text) return;
            try {
                const d = JSON.parse(text);
                console.log('Messages loaded, count:', d.messages ? d.messages.length : 0);
                if (d.success && d.messages) {
                    renderMessages(d.messages);
                    // Hide badge saat chat terbuka
                    const badge = document.getElementById(`badge-${userId}`);
                    if (badge) badge.style.display = 'none';
                } else {
                    console.warn('API response not success:', d);
                }
            } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Response was:', text.substring(0, 500));
            }
        })
        .catch(err => console.error('Load error:', err));
}

function renderMessages(msgs) {
    const box = document.getElementById('messages');
    
    // If messages empty, clear and return
    if (!msgs || msgs.length === 0) {
        return;
    }
    
    // Get current message IDs yang sudah ditampilkan
    const existingIds = new Set();
    box.querySelectorAll('[data-msg-id]').forEach(el => {
        const id = parseInt(el.dataset.msgId);
        if (!isNaN(id)) {
            existingIds.add(id);
        }
    });
    
    // Track how many new messages added
    let newMessagesAdded = 0;
    
    // Only add NEW messages
    msgs.forEach((m) => {
        // Validate message has ID
        if (!m.id) {
            console.warn('Message without ID:', m);
            return;
        }
        
        const msgId = parseInt(m.id);
        if (isNaN(msgId) || existingIds.has(msgId)) {
            return; // Skip if already rendered
        }
        
        const sent = m.sender_id == <?= $current_user_id ?>;
        const item = document.createElement('div');
        item.className = 'msg-item ' + (sent ? 'sent' : 'received');
        item.dataset.msgId = msgId;
        
        const content = document.createElement('div');
        content.className = 'msg-content';
        
        const bubble = document.createElement('div');
        bubble.className = 'bubble';
        
        let hasContent = false;
        
        if (m.message_type === 'image' && m.media_url) {
            const container = document.createElement('div');
            const img = document.createElement('img');
            
            let imgUrl = m.media_url;
            if (typeof imgUrl === 'object') {
                imgUrl = imgUrl.path ? UPLOADS_URL + '/' + imgUrl.path : '';
            }
            
            img.src = imgUrl;
            img.className = 'image-preview';
            img.onerror = () => img.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="200" height="200"%3E%3Crect fill="%23ddd" width="200" height="200"/%3E%3Ctext x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="Arial" font-size="16" fill="%23999"%3EImage not found%3C/text%3E%3C/svg%3E';
            container.appendChild(img);
            if (m.message) {
                const caption = document.createElement('div');
                caption.style.marginTop = '6px';
                caption.style.fontSize = '13px';
                caption.textContent = m.message;
                container.appendChild(caption);
            }
            bubble.appendChild(container);
            hasContent = true;
        } else if (m.message_type === 'sticker' && (m.media_url || m.message)) {
            bubble.style.background = 'transparent';
            bubble.style.boxShadow = 'none';
            bubble.style.fontSize = '48px';
            bubble.style.padding = '8px';
            // Use media_url if available, otherwise use message (emoji)
            bubble.textContent = m.media_url || m.message;
            hasContent = true;
        } else if (m.message) {
            bubble.textContent = m.message;
            hasContent = true;
        }
        
        if (!hasContent) {
            return; // Skip empty messages
        }
        
        const time = new Date(m.created_at).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'});
        const timeEl = document.createElement('div');
        timeEl.className = 'msg-time';
        timeEl.textContent = time + (sent ? ' ✓✓' : '');
        
        content.appendChild(bubble);
        content.appendChild(timeEl);
        item.appendChild(content);
        box.appendChild(item);
        
        newMessagesAdded++;
        
        // Notify for new messages
        if (!sent) {
            playNotificationSound();
        }
    });
    
    // Auto-scroll to bottom if new messages added
    if (newMessagesAdded > 0) {
        setTimeout(() => box.scrollTop = box.scrollHeight, 100);
    }
}

function playNotificationSound() {
    const audio = new Audio('data:audio/wav;base64,UklGRiYAAABXQVZFZm10IBAAAAABAAEAQB8AAAB9AAACABAAZGF0YQIAAAAAAA==');
    audio.play().catch(err => console.log('Audio play failed:', err));
}

function sendMsg() {
    const inp = document.getElementById('msg');
    const msg = inp.value.trim();
    if (!msg || !userId) return;
    
    inp.value = '';
    inp.disabled = true;
    
    const fd = new FormData();
    fd.append('action', 'send_message');
    fd.append('recipient_id', userId);
    fd.append('message', msg);
    
    fetch('api_staff_chat.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            inp.disabled = false;
            inp.focus();
            if (d.success) loadMessages();
        })
        .catch(err => {
            inp.disabled = false;
            console.error('Send error:', err);
        });
}

function closeChat() {
    userId = 0;
    window.currentOpenUserId = 0;
    document.getElementById('main').classList.remove('show');
    document.getElementById('input').classList.remove('show');
    document.getElementById('sidebar').style.display = '';
    if (pollTimer) clearInterval(pollTimer);
}

function openModal() {
    document.getElementById('modal').classList.add('show');
}

function closeModal() {
    document.getElementById('modal').classList.remove('show');
}

function startChat() {
    const sel = document.getElementById('select');
    const id = sel.value;
    if (!id) return;
    const name = sel.options[sel.selectedIndex].text;
    closeModal();
    sel.value = '';
    openChat(parseInt(id), name);
}

document.getElementById('msg').addEventListener('keypress', e => {
    if (e.key === 'Enter') sendMsg();
});

document.getElementById('search').addEventListener('input', e => {
    const term = e.target.value.toLowerCase();
    document.querySelectorAll('.chat-item').forEach(item => {
        const name = item.querySelector('.chat-name').textContent.toLowerCase();
        item.style.display = name.includes(term) ? '' : 'none';
    });
});

document.getElementById('imageInput').addEventListener('change', e => {
    const file = e.target.files[0];
    if (!file) return;
    
    if (!userId) {
        alert('Buka chat terlebih dahulu');
        return;
    }
    
    const btn = document.querySelector('.btn-send[onclick*="imageInput"]');
    btn.disabled = true;
    btn.style.opacity = '0.5';
    
    const fd = new FormData();
    fd.append('action', 'send_message');
    fd.append('recipient_id', userId);
    fd.append('message_type', 'image');
    fd.append('message', '');
    fd.append('image', file);
    
    fetch('api_staff_chat.php', { 
        method: 'POST', 
        body: fd
    })
    .then(r => r.json())
    .then(d => {
        console.log('Upload response:', d);
        btn.disabled = false;
        btn.style.opacity = '1';
        e.target.value = '';
        
        if (d.success) {
            loadMessages();
        } else {
            alert('Gagal upload: ' + (d.message || 'Unknown error'));
        }
    })
    .catch(err => {
        console.error('Upload error:', err);
        btn.disabled = false;
        btn.style.opacity = '1';
        alert('Error: ' + err.message);
    });
});

function openStickerPicker() {
    document.getElementById('stickerModal').classList.add('show');
}

function sendSticker(sticker) {
    document.getElementById('stickerModal').classList.remove('show');
    
    const fd = new FormData();
    fd.append('action', 'send_message');
    fd.append('recipient_id', userId);
    fd.append('message_type', 'sticker');
    fd.append('message', sticker);
    
    fetch('api_staff_chat.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) loadMessages();
        });
}

// Setup button event listeners
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOMContentLoaded fired - setting up listeners');
    
    // Initialize push notifications
    console.log('[Push] Starting push notification initialization...');
    initPushNotifications().catch(err => {
        console.error('[Push] Initialization error:', err);
    });
    
    // Attach listeners to initial conversation items (from PHP)
    attachConversationListeners();
    
    // Pen icon - new chat button
    const btnNew = document.querySelector('.btn-new');
    console.log('btnNew element:', btnNew);
    if (btnNew) {
        btnNew.addEventListener('click', (e) => {
            console.log('Pen button clicked');
            e.preventDefault();
            e.stopPropagation();
            openModal();
        });
    }
    
    // Modal buttons
    const modalBtnOk = document.querySelector('.modal-btn-ok');
    console.log('modalBtnOk element:', modalBtnOk);
    if (modalBtnOk) {
        modalBtnOk.addEventListener('click', (e) => {
            console.log('OK button clicked');
            e.preventDefault();
            startChat();
        });
    }
    
    const modalBtnCancel = document.querySelector('.modal-btn-cancel');
    console.log('modalBtnCancel element:', modalBtnCancel);
    if (modalBtnCancel) {
        modalBtnCancel.addEventListener('click', (e) => {
            console.log('Cancel button clicked');
            e.preventDefault();
            closeModal();
        });
    }
    
    // Close modal on background click
    const modal = document.getElementById('modal');
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });
    }
    
    const stickerModal = document.getElementById('stickerModal');
    if (stickerModal) {
        stickerModal.addEventListener('click', (e) => {
            if (e.target === stickerModal) {
                stickerModal.classList.remove('show');
            }
        });
    }
    
    console.log('Initial load conversations...');
    // Initial load
    loadConversations();
    // Enable interval - refresh conversation list every 5 seconds
    setInterval(loadConversations, 5000);
});

// Fallback if DOMContentLoaded doesn't fire
setTimeout(() => {
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        console.log('Fallback load conversations');
        loadConversations();
        // Disabled interval for now
        // if (!window.convInterval) {
        //     window.convInterval = setInterval(loadConversations, 3000);
        // }
    }
}, 100);

</script>
</body>
</html>
