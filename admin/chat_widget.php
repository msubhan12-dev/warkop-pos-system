<?php
/**
 * Chat Widget Component
 * Include this in order detail pages
 */
?>

<style>
.chat-container {
    display: flex;
    flex-direction: column;
    height: 500px;
    background: white;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    background: #f9fafb;
}

.chat-message {
    margin-bottom: 12px;
    display: flex;
    flex-direction: column;
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.chat-message.own {
    align-items: flex-end;
}

.chat-message.other {
    align-items: flex-start;
}

.message-bubble {
    max-width: 70%;
    padding: 10px 14px;
    border-radius: 8px;
    word-wrap: break-word;
    font-size: 14px;
}

.message-bubble.own {
    background: #3b82f6;
    color: white;
}

.message-bubble.other {
    background: #e5e7eb;
    color: #111827;
}

.message-bubble.tagged {
    background: #fbbf24;
    color: #78350f;
    font-weight: 500;
}

.message-time {
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
}

.chat-input-area {
    padding: 12px;
    background: white;
    border-top: 1px solid #e5e7eb;
}

.chat-input-wrapper {
    display: flex;
    gap: 8px;
}

.chat-input {
    flex: 1;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    font-family: inherit;
}

.chat-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.tag-dropdown {
    position: absolute;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    max-height: 200px;
    overflow-y: auto;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.tag-option {
    padding: 8px 12px;
    cursor: pointer;
    font-size: 14px;
}

.tag-option:hover {
    background: #f3f4f6;
}

.tag-option.selected {
    background: #dbeafe;
    color: #1e40af;
}
</style>

<div class="chat-container" data-order-id="<?= $order_id ?>">
    <div class="chat-messages" id="chatMessages">
        <div class="text-center text-gray-400 py-8">Loading messages...</div>
    </div>
    
    <div class="chat-input-area">
        <div class="chat-input-wrapper">
            <input 
                type="text" 
                id="chatInput" 
                class="chat-input" 
                placeholder="Type message (@ to tag staff)..."
                autocomplete="off"
            >
            <button 
                onclick="sendChatMessage()" 
                class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-bold text-sm transition"
            >
                Send
            </button>
        </div>
    </div>
</div>

<script>
let chatOrderId = document.querySelector('.chat-container')?.dataset.orderId;
let chatPollInterval;
let taggedUserId = 0;

function loadChatMessages() {
    if (!chatOrderId) return;
    
    fetch(`api_chat_messages.php?action=get_messages&order_id=${chatOrderId}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderMessages(data.messages);
            }
        })
        .catch(e => console.error('Chat error:', e));
}

function renderMessages(messages) {
    const container = document.getElementById('chatMessages');
    container.innerHTML = '';
    
    messages.forEach(msg => {
        const isOwn = msg.sender_id == <?= $_SESSION['user_id'] ?>;
        const bubble = document.createElement('div');
        bubble.className = `chat-message ${isOwn ? 'own' : 'other'}`;
        
        const messageClass = msg.message_type === 'tag' ? 'message-bubble tagged' : `message-bubble ${isOwn ? 'own' : 'other'}`;
        
        const time = new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
        
        bubble.innerHTML = `
            <div class="${messageClass}">
                ${msg.message_type === 'tag' ? `<strong>@${msg.tagged_user || 'Staff'}</strong><br>` : ''}
                ${escapeHtml(msg.message)}
            </div>
            <div class="message-time">${msg.full_name || msg.username} • ${time}</div>
        `;
        
        container.appendChild(bubble);
    });
    
    // Auto scroll to bottom
    container.scrollTop = container.scrollHeight;
}

function sendChatMessage() {
    const input = document.getElementById('chatInput');
    const message = input.value.trim();
    
    if (!message || !chatOrderId) return;
    
    const formData = new FormData();
    formData.append('action', 'send_message');
    formData.append('order_id', chatOrderId);
    formData.append('message', message);
    if (taggedUserId) formData.append('tagged_user_id', taggedUserId);
    
    fetch('api_chat_messages.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            input.value = '';
            taggedUserId = 0;
            loadChatMessages();
        }
    });
}

function handleAtMention() {
    const input = document.getElementById('chatInput');
    const text = input.value;
    
    if (text.includes('@') && !text.includes('@')) {
        // Show staff dropdown
        fetch('api_chat_messages.php?action=get_staff')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showStaffDropdown(data.staff, input);
                }
            });
    }
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Auto-load messages every 2 seconds
document.addEventListener('DOMContentLoaded', () => {
    if (chatOrderId) {
        loadChatMessages();
        chatPollInterval = setInterval(loadChatMessages, 2000);
        
        document.getElementById('chatInput').addEventListener('keypress', e => {
            if (e.key === 'Enter') sendChatMessage();
        });
    }
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (chatPollInterval) clearInterval(chatPollInterval);
});
</script>
