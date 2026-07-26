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
                <button class="btn-new" onclick="openModal()"><i class="fas fa-pen"></i></button>
            </div>
            
            <div class="search-box">
                <input type="text" id="search" placeholder="Cari...">
            </div>
            
            <div class="list-chats" id="list">
                <?php if (empty($conversations)): ?>
                <div class="empty-chats"><i class="fas fa-inbox"></i><p>Belum ada chat</p></div>
                <?php else: ?>
                <?php foreach ($conversations as $c): ?>
                <div class="chat-item" onclick="openChat(<?= $c['id'] ?>, '<?= addslashes($c['full_name']) ?>')">
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
                <button class="modal-btn modal-btn-cancel" onclick="closeModal()">Batal</button>
                <button class="modal-btn modal-btn-ok" onclick="startChat()">OK</button>
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

function loadConversations() {
    fetch('api_staff_chat.php?action=get_conversations')
        .then(r => r.json())
        .then(d => {
            if (d.success && d.conversations) {
                updateConversationsList(d.conversations);
            }
        })
        .catch(err => console.error('Load conv error:', err));
}

function updateConversationsList(convs) {
    const list = document.getElementById('list');
    if (!convs || convs.length === 0) {
        if (list.querySelector('.empty-chats')) return;
        list.innerHTML = '<div class="empty-chats"><i class="fas fa-inbox"></i><p>Belum ada chat</p></div>';
        return;
    }
    
    list.innerHTML = '';
    convs.forEach(c => {
        const item = document.createElement('div');
        item.className = 'chat-item';
        item.onclick = () => openChat(c.id, c.full_name);
        
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
}

function openChat(id, name) {
    userId = id;
    document.querySelectorAll('.chat-item').forEach(el => el.classList.remove('active'));
    if (event.target.closest('.chat-item')) {
        event.target.closest('.chat-item').classList.add('active');
    }
    
    document.getElementById('title').textContent = name;
    document.getElementById('main').classList.add('show');
    document.getElementById('input').classList.add('show');
    document.getElementById('back').classList.add('show');
    
    if (window.innerWidth <= 800) {
        document.getElementById('sidebar').style.display = 'none';
    }
    
    document.getElementById('msg').focus();
    loadMessages();
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(() => {
        loadMessages();
        loadConversations();
    }, 1000);
}

function loadMessages() {
    if (!userId) return;
    fetch(`api_staff_chat.php?action=get_messages&user_id=${userId}`)
        .then(r => r.json())
        .then(d => {
            if (d.success && d.messages) {
                renderMessages(d.messages);
                // Hide badge saat chat terbuka
                const badge = document.getElementById(`badge-${userId}`);
                if (badge) badge.style.display = 'none';
            }
        })
        .catch(err => console.error('Load error:', err));
}

function renderMessages(msgs) {
    const box = document.getElementById('messages');
    const oldCount = box.children.length;
    
    box.innerHTML = '';
    
    msgs.forEach((m, idx) => {
        const sent = m.sender_id == <?= $current_user_id ?>;
        const item = document.createElement('div');
        item.className = 'msg-item ' + (sent ? 'sent' : 'received');
        
        const content = document.createElement('div');
        content.className = 'msg-content';
        
        const bubble = document.createElement('div');
        bubble.className = 'bubble';
        
        let isNewMsg = false;
        
        if (m.message_type === 'image' && m.media_url) {
            const container = document.createElement('div');
            const img = document.createElement('img');
            img.src = m.media_url;
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
            isNewMsg = !sent && idx >= oldCount;
        } else if (m.message_type === 'sticker' && m.media_url) {
            bubble.style.background = 'transparent';
            bubble.style.boxShadow = 'none';
            bubble.style.fontSize = '48px';
            bubble.style.padding = '8px';
            bubble.textContent = m.media_url;
            isNewMsg = !sent && idx >= oldCount;
        } else if (m.message) {
            bubble.textContent = m.message;
            isNewMsg = !sent && idx >= oldCount;
        } else {
            continue;
        }
        
        const time = new Date(m.created_at).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'});
        const timeEl = document.createElement('div');
        timeEl.className = 'msg-time';
        timeEl.textContent = time + (sent ? ' ✓✓' : '');
        
        content.appendChild(bubble);
        content.appendChild(timeEl);
        item.appendChild(content);
        box.appendChild(item);
        
        if (isNewMsg) {
            playNotificationSound();
            const notifMsg = m.message_type === 'image' ? '📷 Foto dikirim' : (m.message_type === 'sticker' ? '😊 Sticker dikirim' : m.message);
            showBrowserNotification(notifMsg);
        }
    });
    
    box.scrollTop = box.scrollHeight;
}

function playNotificationSound() {
    const audio = new Audio('data:audio/wav;base64,UklGRiYAAABXQVZFZm10IBAAAAABAAEAQB8AAAB9AAACABAAZGF0YQIAAAAAAA==');
    audio.play().catch(err => console.log('Audio play failed:', err));
}

function showBrowserNotification(message) {
    if (!('Notification' in window)) return;
    
    if (Notification.permission === 'granted') {
        new Notification('Pesan Chat Baru', {
            body: message.substring(0, 100),
            icon: 'https://mms.img.susercontent.com/85fa98256609ae0a681bf062317895b0',
            badge: 'https://mms.img.susercontent.com/85fa98256609ae0a681bf062317895b0'
        });
    } else if (Notification.permission !== 'denied') {
        Notification.requestPermission().then(perm => {
            if (perm === 'granted') {
                new Notification('Pesan Chat Baru', {
                    body: message.substring(0, 100),
                    icon: 'https://mms.img.susercontent.com/85fa98256609ae0a681bf062317895b0'
                });
            }
        });
    }
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

// Initial load conversations
loadConversations();
setInterval(loadConversations, 3000);
</script>
</body>
</html>
