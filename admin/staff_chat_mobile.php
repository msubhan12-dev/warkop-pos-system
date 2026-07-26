<?php
require_once '../config/config.php';
requireRole(['owner', 'kasir', 'dapur', 'pelayan']);

$db = getDB();
$current_user_id = $_SESSION['user_id'] ?? 0;
$pageTitle = 'Chat Tim';

// Get staff for conversation list
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

// Get all staff for new chat
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
    <title>Chat Tim</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto; background: #fff; height: 100vh; overflow: hidden; }
        
        .chat-page { display: none; height: 100vh; flex-direction: column; }
        .chat-page.active { display: flex; }
        .list-page { display: flex; flex-direction: column; height: 100vh; }
        
        .app-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .app-title { font-size: 18px; font-weight: 600; }
        .app-btn { background: none; border: none; color: white; font-size: 18px; cursor: pointer; }
        
        .chat-list { flex: 1; overflow-y: auto; }
        .chat-item-row {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .chat-item-row:active { background: #f5f5f5; }
        .chat-item-info { flex: 1; min-width: 0; }
        .chat-item-name { font-weight: 600; font-size: 14px; color: #333; }
        .chat-item-msg { font-size: 12px; color: #999; margin-top: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .chat-item-time { font-size: 11px; color: #ccc; margin-left: 8px; flex-shrink: 0; }
        
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #999;
        }
        
        .empty-state i { font-size: 40px; margin-bottom: 16px; opacity: 0.3; }
        
        .btn-fab {
            position: fixed;
            bottom: 20px;
            right: 16px;
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 10;
        }
        
        .btn-fab:active { transform: scale(0.95); }
        
        /* Chat Page Styles */
        .chat-header-bar {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .chat-back {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            padding: 8px;
            margin-left: -8px;
        }
        
        .chat-user-name { font-weight: 600; font-size: 16px; }
        
        .chat-messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            background: #fff;
            display: flex;
            flex-direction: column;
        }
        
        .chat-msg {
            margin-bottom: 12px;
            display: flex;
            align-items: flex-end;
            gap: 8px;
        }
        
        .chat-msg.own {
            justify-content: flex-end;
        }
        
        .msg-bubble {
            max-width: 75%;
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 13px;
            line-height: 1.5;
            word-wrap: break-word;
        }
        
        .chat-msg.own .msg-bubble {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            border-radius: 12px 2px 12px 12px;
        }
        
        .chat-msg.other .msg-bubble {
            background: #f0f0f0;
            color: #333;
            border-radius: 2px 12px 12px 12px;
        }
        
        .msg-time {
            font-size: 10px;
            color: #999;
            margin: 0 4px;
        }
        
        .msg-status {
            font-size: 12px;
            font-weight: 600;
        }
        
        .msg-status.delivered { color: #4caf50; }
        
        .chat-input-bar {
            padding: 12px 16px;
            background: white;
            border-top: 1px solid #e0e0e0;
            flex-shrink: 0;
            display: flex;
            gap: 8px;
        }
        
        .chat-input-bar input {
            flex: 1;
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 20px;
            font-size: 13px;
        }
        
        .chat-input-bar input:focus {
            outline: none;
            border-color: #007bff;
        }
        
        .chat-input-bar button {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        .chat-input-bar button:active { transform: scale(0.95); }
        
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 20;
            align-items: center;
            justify-content: center;
        }
        
        .modal-overlay.show { display: flex; }
        
        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
        }
        
        .modal-title { font-size: 16px; font-weight: 600; margin-bottom: 16px; }
        
        .modal-select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 13px;
        }
        
        .modal-buttons {
            display: flex;
            gap: 8px;
        }
        
        .modal-btn {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
        }
        
        .modal-btn-cancel {
            background: #f0f0f0;
            color: #333;
        }
        
        .modal-btn-ok {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }
        
        @media (max-height: 600px) {
            .app-header { padding: 8px 12px; }
            .chat-item-row { padding: 8px 12px; }
            .chat-input-bar { padding: 8px 12px; }
        }
    </style>
</head>
<body>
    <!-- List Page -->
    <div class="list-page" id="listPage">
        <div class="app-header">
            <span class="app-title">💬 Chat Tim</span>
            <button class="app-btn" onclick="openNewChat()"><i class="fas fa-pen"></i></button>
        </div>
        
        <div class="chat-list" id="chatList">
            <?php if (empty($conversations)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>Belum ada percakapan</p>
            </div>
            <?php else: ?>
                <?php foreach ($conversations as $c): ?>
                <div class="chat-item-row" onclick="openChat(<?= $c['user_id'] ?>, '<?= htmlspecialchars($c['full_name']) ?>')">
                    <div class="chat-item-info">
                        <div class="chat-item-name"><?= htmlspecialchars($c['full_name']) ?></div>
                        <div class="chat-item-msg"><?= htmlspecialchars(substr($c['last_msg'] ?? '', 0, 40)) ?></div>
                    </div>
                    <div class="chat-item-time"><?= date('H:i', strtotime($c['last_time'])) ?></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <button class="btn-fab" onclick="openNewChat()"><i class="fas fa-pen"></i></button>
    </div>
    
    <!-- Chat Page -->
    <div class="chat-page" id="chatPage">
        <div class="chat-header-bar">
            <button class="chat-back" onclick="backToList()"><i class="fas fa-chevron-left"></i></button>
            <span class="chat-user-name" id="chatUserName"></span>
            <div></div>
        </div>
        
        <div class="chat-messages-container" id="chatMessages"></div>
        
        <div class="chat-input-bar">
            <input type="text" id="chatInput" placeholder="Ketik pesan..." autocomplete="off">
            <button onclick="sendMsg()"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
    
    <!-- Modal -->
    <div class="modal-overlay" id="modal">
        <div class="modal-content">
            <div class="modal-title">Chat Baru</div>
            <select class="modal-select" id="staffSelect">
                <option value="">-- Pilih Staff --</option>
                <?php foreach ($all_staff as $s): ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-cancel" onclick="closeModal()">Batal</button>
                <button class="modal-btn modal-btn-ok" onclick="startNewChat()">Mulai</button>
            </div>
        </div>
    </div>

<script>
let currentUserId = 0;
let currentUserName = '';
let pollInterval;

function openChat(uid, name) {
    currentUserId = uid;
    currentUserName = name;
    
    document.getElementById('listPage').style.display = 'none';
    document.getElementById('chatPage').classList.add('active');
    document.getElementById('chatUserName').textContent = name;
    document.getElementById('chatInput').focus();
    
    loadMessages();
    if (pollInterval) clearInterval(pollInterval);
    pollInterval = setInterval(loadMessages, 2000);
}

function backToList() {
    document.getElementById('chatPage').classList.remove('active');
    document.getElementById('listPage').style.display = 'flex';
    if (pollInterval) clearInterval(pollInterval);
    currentUserId = 0;
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
    const c = document.getElementById('chatMessages');
    c.innerHTML = '';
    
    if (msgs.length === 0) {
        c.innerHTML = '<div style="flex:1; display:flex; align-items:center; justify-content:center; color:#999;">Mulai percakapan</div>';
        return;
    }
    
    msgs.forEach(m => {
        const own = m.sender_id == <?= $current_user_id ?>;
        const div = document.createElement('div');
        div.className = 'chat-msg' + (own ? ' own' : '');
        const time = new Date(m.created_at).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'});
        const status = m.is_read ? '✓✓' : '✓';
        
        div.innerHTML = `
            ${own ? '<span class="msg-time">' + time + '</span>' : ''}
            <div class="msg-bubble">${escapeHtml(m.message)}</div>
            ${own ? '<span class="msg-status delivered">' + status + '</span>' : '<span class="msg-time">' + time + '</span>'}
        `;
        
        c.appendChild(div);
    });
    
    c.scrollTop = c.scrollHeight;
}

function sendMsg() {
    const inp = document.getElementById('chatInput');
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
    document.getElementById('staffSelect').value = '';
}

function startNewChat() {
    const id = document.getElementById('staffSelect').value;
    const name = document.getElementById('staffSelect').options[document.getElementById('staffSelect').selectedIndex].text;
    if (!id) return;
    closeModal();
    openChat(parseInt(id), name);
}

function escapeHtml(t) {
    const m = {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'};
    return t.replace(/[&<>"']/g, c => m[c]);
}

document.getElementById('chatInput').addEventListener('keypress', e => {
    if (e.key === 'Enter') sendMsg();
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeModal();
});
</script>
</body>
</html>
