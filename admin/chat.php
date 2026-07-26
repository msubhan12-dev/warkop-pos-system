<?php
require_once '../config/config.php';
requireRole(['owner', 'kasir', 'dapur', 'pelayan']);

$db = getDB();
$current_user_id = $_SESSION['user_id'] ?? 0;

// Get conversations
$conversations = [];
try {
    $sql = "
        SELECT DISTINCT 
            CASE WHEN sender_id = $current_user_id THEN recipient_id ELSE sender_id END as user_id,
            MAX(created_at) as last_time,
            (SELECT message FROM staff_messages WHERE (sender_id = $current_user_id AND recipient_id = user_id) OR (sender_id = user_id AND recipient_id = $current_user_id) ORDER BY created_at DESC LIMIT 1) as last_msg
        FROM staff_messages
        WHERE sender_id = $current_user_id OR recipient_id = $current_user_id
        GROUP BY user_id
        ORDER BY last_time DESC
    ";
    $result = $db->query($sql);
    $convs = $result->fetchAll();
    
    foreach ($convs as $c) {
        $u = $db->query("SELECT id, full_name, role FROM users WHERE id = " . $c['user_id'])->fetch();
        if ($u) $conversations[] = array_merge($c, $u);
    }
} catch (Exception $e) {}

// Get all staff
$all_staff = [];
try {
    $all_staff = $db->query("
        SELECT id, full_name, role FROM users 
        WHERE is_active = 1 AND role IN ('owner','kasir','dapur','pelayan') 
        AND id != $current_user_id 
        ORDER BY full_name
    ")->fetchAll();
} catch (Exception $e) {}

?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Chat - Warkop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #fff;
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .status-bar {
            height: 25px;
            background: linear-gradient(135deg, #128C7E 0%, #075E54 100%);
            color: white;
            font-size: 10px;
            padding: 4px 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .app {
            flex: 1;
            display: flex;
            overflow: hidden;
        }
        
        /* Sidebar */
        .sidebar {
            width: 360px;
            background: white;
            display: flex;
            flex-direction: column;
            border-right: 1px solid #e5e5e5;
        }
        
        .sidebar-header {
            background: #128C7E;
            color: white;
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }
        
        .sidebar-title {
            font-size: 20px;
            font-weight: 500;
        }
        
        .sidebar-icons {
            display: flex;
            gap: 16px;
        }
        
        .sidebar-icons button {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
        }
        
        .search-box {
            padding: 8px 16px;
            border-bottom: 1px solid #e5e5e5;
            flex-shrink: 0;
        }
        
        .search-box input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 16px;
            background: #f0f0f0;
            border: none;
            font-size: 13px;
        }
        
        .search-box input:focus {
            outline: none;
            background: #e8f5e9;
        }
        
        .chats-list {
            flex: 1;
            overflow-y: auto;
        }
        
        .chat-row {
            display: flex;
            padding: 8px 8px;
            cursor: pointer;
            transition: background 0.15s;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .chat-row:hover {
            background: #f5f5f5;
        }
        
        .chat-row.active {
            background: #e8f5e9;
        }
        
        .chat-avatar {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #128C7E 0%, #075E54 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            flex-shrink: 0;
            margin: 8px;
        }
        
        .chat-info {
            flex: 1;
            padding: 8px;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .chat-name {
            font-weight: 500;
            font-size: 14px;
            color: #000;
        }
        
        .chat-msg {
            font-size: 12px;
            color: #999;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 2px;
        }
        
        .chat-time {
            padding: 8px;
            font-size: 12px;
            color: #999;
        }
        
        .empty-sidebar {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #ccc;
        }
        
        .empty-sidebar i {
            font-size: 80px;
            margin-bottom: 16px;
        }
        
        /* Chat Area */
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: white;
            display: none;
        }
        
        .chat-area.active {
            display: flex;
        }
        
        .chat-header {
            background: #128C7E;
            color: white;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
            border-bottom: 1px solid #10705E;
        }
        
        .chat-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .back-btn {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            display: none;
        }
        
        .back-btn.show {
            display: block;
        }
        
        .chat-header-name {
            font-size: 14px;
            font-weight: 500;
        }
        
        .chat-header-status {
            font-size: 11px;
            opacity: 0.8;
        }
        
        .messages {
            flex: 1;
            overflow-y: auto;
            padding: 12px 16px;
            background: #ECE5DD;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .msg-group {
            display: flex;
            margin-bottom: 8px;
        }
        
        .msg-group.sent {
            justify-content: flex-end;
        }
        
        .msg-bubble {
            max-width: 70%;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 13px;
            line-height: 1.4;
            word-wrap: break-word;
        }
        
        .msg-group.sent .msg-bubble {
            background: #DCF8C6;
            color: #000;
            border-radius: 8px 0 8px 8px;
        }
        
        .msg-group.received .msg-bubble {
            background: white;
            color: #000;
            border-radius: 0 8px 8px 8px;
            box-shadow: 0 1px 1px rgba(0,0,0,0.1);
        }
        
        .msg-time {
            font-size: 11px;
            color: #999;
            padding: 0 8px;
            margin-top: 4px;
            text-align: right;
        }
        
        .msg-group.received .msg-time {
            text-align: left;
        }
        
        .input-box {
            padding: 12px 16px;
            background: #f0f0f0;
            border-top: 1px solid #e0e0e0;
            display: none;
            flex-shrink: 0;
        }
        
        .input-box.active {
            display: flex;
        }
        
        .input-group {
            display: flex;
            gap: 8px;
            align-items: flex-end;
        }
        
        .input-group input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 16px;
            font-size: 13px;
            background: white;
        }
        
        .input-group input:focus {
            outline: none;
            border-color: #128C7E;
        }
        
        .send-btn {
            background: none;
            border: none;
            color: #128C7E;
            font-size: 18px;
            cursor: pointer;
            padding: 4px 8px;
        }
        
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 99;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 8px;
            padding: 20px;
            width: 90%;
            max-width: 360px;
        }
        
        .modal-title {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 16px;
        }
        
        .modal-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-bottom: 16px;
            font-size: 13px;
        }
        
        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        
        .modal-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
        }
        
        .modal-btn-cancel {
            background: #e0e0e0;
            color: #000;
        }
        
        .modal-btn-ok {
            background: #128C7E;
            color: white;
        }
        
        .scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        
        .scrollbar::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 3px;
        }
        
        .scrollbar::-webkit-scrollbar-thumb:hover {
            background: #999;
        }
        
        /* Mobile */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: absolute;
                top: 25px;
                bottom: 0;
                z-index: 10;
                max-height: 100%;
                display: none;
            }
            
            .sidebar.show {
                display: flex;
            }
            
            .chat-area {
                position: absolute;
                inset: 25px 0 0 0;
            }
            
            .back-btn {
                display: block;
            }
            
            .msg-bubble {
                max-width: 85%;
            }
        }
    </style>
</head>
<body>
    <div class="status-bar">
        <span>9:41</span>
        <span>Warkop Chat</span>
        <span>🔋</span>
    </div>
    
    <div class="app">
        <!-- Sidebar -->
        <div class="sidebar scrollbar" id="sidebar">
            <div class="sidebar-header">
                <span class="sidebar-title">Chats</span>
                <div class="sidebar-icons">
                    <button onclick="openNewChat()"><i class="fas fa-edit"></i></button>
                </div>
            </div>
            
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Cari...">
            </div>
            
            <div class="chats-list scrollbar" id="chatsList">
                <?php if (empty($conversations)): ?>
                <div class="empty-sidebar">
                    <i class="fas fa-inbox"></i>
                    <p>Belum ada chat</p>
                </div>
                <?php else: ?>
                    <?php foreach ($conversations as $c): ?>
                    <div class="chat-row" onclick="selectChat(<?= $c['user_id'] ?>, '<?= htmlspecialchars($c['full_name']) ?>')">
                        <div class="chat-avatar"><?= substr($c['full_name'], 0, 1) ?></div>
                        <div class="chat-info">
                            <div class="chat-name"><?= htmlspecialchars($c['full_name']) ?></div>
                            <div class="chat-msg"><?= htmlspecialchars($c['last_msg'] ?? '') ?></div>
                        </div>
                        <div class="chat-time"><?= date('H:i', strtotime($c['last_time'])) ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Chat Area -->
        <div class="chat-area" id="chatArea">
            <div class="chat-header">
                <div class="chat-header-left">
                    <button class="back-btn" onclick="backToChats()"><i class="fas fa-chevron-left"></i></button>
                    <div>
                        <div class="chat-header-name" id="chatName"></div>
                        <div class="chat-header-status">online</div>
                    </div>
                </div>
                <div class="sidebar-icons">
                    <button><i class="fas fa-phone"></i></button>
                    <button><i class="fas fa-video"></i></button>
                    <button><i class="fas fa-ellipsis-v"></i></button>
                </div>
            </div>
            
            <div class="messages scrollbar" id="messages"></div>
            
            <div class="input-box" id="inputBox">
                <div class="input-group">
                    <input type="text" id="msgInput" placeholder="Message..." autocomplete="off">
                    <button class="send-btn" onclick="sendMsg()"><i class="fas fa-paper-plane"></i></button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal -->
    <div class="modal" id="modal">
        <div class="modal-content">
            <div class="modal-title">New Chat</div>
            <select class="modal-input" id="staffSelect">
                <option value="">-- Select Contact --</option>
                <?php foreach ($all_staff as $s): ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-cancel" onclick="closeModal()">Cancel</button>
                <button class="modal-btn modal-btn-ok" onclick="startChat()">OK</button>
            </div>
        </div>
    </div>

<script>
let currentUserId = 0;
let pollInterval;

function selectChat(uid, name) {
    currentUserId = uid;
    
    document.querySelectorAll('.chat-row').forEach(el => el.classList.remove('active'));
    if (event.target.closest('.chat-row')) {
        event.target.closest('.chat-row').classList.add('active');
    }
    
    document.getElementById('chatName').textContent = name;
    document.getElementById('chatArea').classList.add('active');
    document.getElementById('inputBox').classList.add('active');
    
    if (window.innerWidth <= 768) {
        document.getElementById('sidebar').classList.remove('show');
    }
    
    document.getElementById('msgInput').focus();
    loadMessages();
    
    if (pollInterval) clearInterval(pollInterval);
    pollInterval = setInterval(loadMessages, 2000);
}

function loadMessages() {
    if (!currentUserId) return;
    fetch(`api_staff_chat.php?action=get_messages&user_id=${currentUserId}`)
        .then(r => r.json())
        .then(d => {
            if (d.success) showMessages(d.messages);
        });
}

function showMessages(msgs) {
    const box = document.getElementById('messages');
    box.innerHTML = '';
    
    msgs.forEach(m => {
        const isSent = m.sender_id == <?= $current_user_id ?>;
        const div = document.createElement('div');
        div.className = 'msg ' + (isSent ? 'sent' : 'received');
        const time = new Date(m.created_at).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'});
        
        div.innerHTML = `
            <div>
                <div class="msg-bubble">${escapeHtml(m.message)}</div>
                <div class="msg-time">${time}${isSent ? ' ✓✓' : ''}</div>
            </div>
        `;
        
        box.appendChild(div);
    });
    
    box.scrollTop = box.scrollHeight;
}

function sendMsg() {
    const inp = document.getElementById('msgInput');
    const msg = inp.value.trim();
    if (!msg || !currentUserId) return;
    
    inp.value = '';
    inp.disabled = true;
    
    const fd = new FormData();
    fd.append('action', 'send_message');
    fd.append('recipient_id', currentUserId);
    fd.append('message', msg);
    
    fetch('api_staff_chat.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            inp.disabled = false;
            inp.focus();
            if (d.success) loadMessages();
        });
}

function openNewChat() {
    document.getElementById('modal').classList.add('show');
}

function closeModal() {
    document.getElementById('modal').classList.remove('show');
}

function startChat() {
    const id = document.getElementById('staffSelect').value;
    const name = document.getElementById('staffSelect').options[document.getElementById('staffSelect').selectedIndex].text;
    if (!id) return;
    closeModal();
    selectChat(parseInt(id), name);
}

function backToChats() {
    document.getElementById('chatArea').classList.remove('active');
    if (window.innerWidth <= 768) {
        document.getElementById('sidebar').classList.add('show');
    }
    if (pollInterval) clearInterval(pollInterval);
}

function escapeHtml(t) {
    const m = {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'};
    return t.replace(/[&<>"']/g, c => m[c]);
}

document.getElementById('msgInput').addEventListener('keypress', e => {
    if (e.key === 'Enter') sendMsg();
});

// Search
document.getElementById('searchInput').addEventListener('input', e => {
    const term = e.target.value.toLowerCase();
    document.querySelectorAll('.chat-row').forEach(row => {
        const name = row.querySelector('.chat-name').textContent.toLowerCase();
        row.style.display = name.includes(term) ? '' : 'none';
    });
});

window.addEventListener('resize', () => {
    if (window.innerWidth > 768) {
        document.getElementById('sidebar').classList.remove('show');
    }
});
</script>
</body>
</html>
