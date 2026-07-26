<?php
/**
 * Customer Support Chat - Embeddable Widget
 * Include di menu_online.php atau halaman customer lain
 */

// Jangan require session jika dipanggil dari customer page yang sudah punya session
if (!isset($_SESSION['customer_session_id'])) {
    $_SESSION['customer_session_id'] = 'guest_' . time() . '_' . rand(1000, 9999);
}

$customer_session_id = $_SESSION['customer_session_id'] ?? 'guest';
?>

<style>
    .support-chat-widget {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 380px;
        height: 500px;
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        z-index: 9999;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    
    .support-header {
        padding: 16px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border-radius: 12px 12px 0 0;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .support-close {
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        font-size: 18px;
    }
    
    .support-messages {
        flex: 1;
        overflow-y: auto;
        padding: 16px;
        background: #fafafa;
    }
    
    .support-msg {
        margin-bottom: 12px;
        display: flex;
    }
    
    .support-msg.customer {
        justify-content: flex-end;
    }
    
    .support-bubble {
        max-width: 75%;
        padding: 10px 14px;
        border-radius: 12px;
        font-size: 13px;
        line-height: 1.5;
        word-wrap: break-word;
    }
    
    .support-msg.customer .support-bubble {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border-radius: 12px 2px 12px 12px;
    }
    
    .support-msg.admin .support-bubble {
        background: white;
        color: #333;
        border: 1px solid #e0e0e0;
        border-radius: 2px 12px 12px 12px;
    }
    
    .support-time {
        font-size: 11px;
        color: #999;
        margin-top: 4px;
    }
    
    .support-input {
        display: none;
        padding: 12px;
        border-top: 1px solid #e0e0e0;
        background: white;
    }
    
    .support-input.show {
        display: block;
    }
    
    .support-input-group {
        display: flex;
        gap: 8px;
    }
    
    .support-input-group input {
        flex: 1;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 13px;
    }
    
    .support-input-group input:focus {
        outline: none;
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16,185,129,0.1);
    }
    
    .support-input-group button {
        padding: 8px 14px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 12px;
    }
    
    .support-input-group button:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16,185,129,0.3);
    }
    
    .support-status {
        text-align: center;
        padding: 40px 20px;
        color: #999;
        font-size: 13px;
    }
    
    .support-online {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    
    .support-dot {
        width: 8px;
        height: 8px;
        background: #10b981;
        border-radius: 50%;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    
    .support-list::-webkit-scrollbar,
    .support-messages::-webkit-scrollbar {
        width: 6px;
    }
    
    .support-list::-webkit-scrollbar-track,
    .support-messages::-webkit-scrollbar-track {
        background: transparent;
    }
    
    .support-list::-webkit-scrollbar-thumb,
    .support-messages::-webkit-scrollbar-thumb {
        background: #ddd;
        border-radius: 3px;
    }
</style>

<div class="support-chat-widget" id="supportWidget">
    <div class="support-header">
        <div>
            <div style="font-size: 14px;">💬 Customer Support</div>
            <div style="font-size: 11px; opacity: 0.9; margin-top: 4px;" class="support-online">
                <span class="support-dot"></span> Online
            </div>
        </div>
        <button class="support-close" onclick="toggleSupportChat()">✕</button>
    </div>
    
    <div class="support-messages" id="supportMessages">
        <div class="support-status">
            Halo! Butuh bantuan?<br>
            Tim kami siap membantu Anda
        </div>
    </div>
    
    <div class="support-input show" id="supportInput">
        <div class="support-input-group">
            <input type="text" id="supportMsgInput" placeholder="Ketik pertanyaan..." autocomplete="off">
            <button onclick="sendSupportMsg()">Kirim</button>
        </div>
    </div>
</div>

<script>
let supportSessionId = '<?= $customer_session_id ?>';
let supportPoll;

function toggleSupportChat() {
    const widget = document.getElementById('supportWidget');
    widget.style.display = widget.style.display === 'none' ? 'flex' : 'none';
}

function loadSupportMessages() {
    fetch(`api_customer_support.php?action=get_messages&session=${supportSessionId}`)
        .then(r => r.json())
        .then(d => {
            if (d.success) showSupportMessages(d.messages);
        })
        .catch(() => {});
}

function showSupportMessages(msgs) {
    const c = document.getElementById('supportMessages');
    if (msgs.length === 0) {
        c.innerHTML = '<div class="support-status">Halo! Butuh bantuan?<br>Tim kami siap membantu Anda</div>';
        return;
    }
    
    c.innerHTML = '';
    msgs.forEach(m => {
        const isCustomer = m.sender_type === 'customer';
        const div = document.createElement('div');
        div.className = 'support-msg ' + (isCustomer ? 'customer' : 'admin');
        const time = new Date(m.created_at).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'});
        div.innerHTML = `
            <div>
                <div class="support-bubble">${escapeHtml(m.message)}</div>
                <div class="support-time">${time}</div>
            </div>
        `;
        c.appendChild(div);
    });
    c.scrollTop = c.scrollHeight;
}

function sendSupportMsg() {
    const inp = document.getElementById('supportMsgInput');
    const msg = inp.value.trim();
    if (!msg) return;
    
    inp.value = '';
    inp.disabled = true;
    
    const fd = new FormData();
    fd.append('action', 'send_message');
    fd.append('session_id', supportSessionId);
    fd.append('message', msg);
    fd.append('sender_type', 'customer');
    
    fetch('api_customer_support.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            inp.disabled = false;
            inp.focus();
            if (d.success) loadSupportMessages();
        })
        .catch(() => { inp.disabled = false; });
}

function escapeHtml(t) {
    const m = {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'};
    return t.replace(/[&<>"']/g, c => m[c]);
}

// Auto-load messages
loadSupportMessages();
supportPoll = setInterval(loadSupportMessages, 3000);

// Listen for Enter key
document.getElementById('supportMsgInput').addEventListener('keypress', e => {
    if (e.key === 'Enter') sendSupportMsg();
});

// Cleanup
window.addEventListener('beforeunload', () => {
    if (supportPoll) clearInterval(supportPoll);
});
</script>
