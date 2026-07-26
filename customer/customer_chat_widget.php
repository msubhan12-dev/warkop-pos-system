<?php
/**
 * Customer Support Chat Widget
 * Include ini di menu_online.php atau track_order.php
 */
?>

<style>
.customer-chat-container {
    display: flex;
    flex-direction: column;
    height: 450px;
    background: white;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.customer-chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    background: #f9fafb;
}

.customer-chat-message {
    margin-bottom: 12px;
    display: flex;
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.customer-chat-message.own {
    justify-content: flex-end;
}

.customer-chat-message.staff {
    justify-content: flex-start;
}

.customer-message-bubble {
    max-width: 70%;
    padding: 10px 14px;
    border-radius: 8px;
    word-wrap: break-word;
    font-size: 14px;
    line-height: 1.4;
}

.customer-message-bubble.own {
    background: #10b981;
    color: white;
}

.customer-message-bubble.staff {
    background: #3b82f6;
    color: white;
}

.customer-message-time {
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
}

.customer-chat-input-area {
    padding: 12px;
    background: white;
    border-top: 1px solid #e5e7eb;
}

.customer-chat-input-wrapper {
    display: flex;
    gap: 8px;
}

.customer-chat-input {
    flex: 1;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    font-family: inherit;
}

.customer-chat-input:focus {
    outline: none;
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.customer-chat-header {
    padding: 12px 16px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    font-weight: bold;
    text-align: center;
}

.online-indicator {
    display: inline-block;
    width: 8px;
    height: 8px;
    background: #22c55e;
    border-radius: 50%;
    margin-right: 6px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
</style>

<div class="customer-chat-container" data-order-id="<?= $order_id ?>">
    <div class="customer-chat-header">
        <span class="online-indicator"></span>
        Customer Support Chat
    </div>
    
    <div class="customer-chat-messages" id="customerChatMessages">
        <div class="text-center text-gray-400 py-8 text-sm">Loading messages...</div>
    </div>
    
    <div class="customer-chat-input-area">
        <div class="customer-chat-input-wrapper">
            <input 
                type="text" 
                id="customerChatInput" 
                class="customer-chat-input" 
                placeholder="Ketik pertanyaan atau masalah..."
                autocomplete="off"
            >
            <button 
                onclick="sendCustomerMessage()" 
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-sm transition"
            >
                Kirim
            </button>
        </div>
    </div>
</div>

<script>
let customerChatOrderId = document.querySelector('.customer-chat-container')?.dataset.orderId;
let customerChatPoll;

function loadCustomerMessages() {
    if (!customerChatOrderId) return;
    
    fetch(`../customer/api_customer_chat.php?action=get_messages&order_id=${customerChatOrderId}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderCustomerMessages(data.messages);
            }
        })
        .catch(e => console.error('Chat error:', e));
}

function renderCustomerMessages(messages) {
    const container = document.getElementById('customerChatMessages');
    container.innerHTML = '';
    
    if (messages.length === 0) {
        container.innerHTML = '<div class="text-center text-gray-400 py-8 text-sm">Belum ada pesan. Ajukan pertanyaan!</div>';
        return;
    }
    
    messages.forEach(msg => {
        const isOwn = msg.sender_type === 'customer';
        const bubble = document.createElement('div');
        bubble.className = `customer-chat-message ${isOwn ? 'own' : 'staff'}`;
        
        const messageClass = `customer-message-bubble ${isOwn ? 'own' : 'staff'}`;
        const time = new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
        const senderName = isOwn ? 'Anda' : (msg.full_name || 'Support Team');
        
        bubble.innerHTML = `
            <div>
                <div class="${messageClass}">
                    ${escapeHtml(msg.message)}
                </div>
                <div class="customer-message-time text-right">${senderName} • ${time}</div>
            </div>
        `;
        
        container.appendChild(bubble);
    });
    
    // Auto scroll to bottom
    container.scrollTop = container.scrollHeight;
}

function sendCustomerMessage() {
    const input = document.getElementById('customerChatInput');
    const message = input.value.trim();
    
    if (!message || !customerChatOrderId) return;
    
    const formData = new FormData();
    formData.append('action', 'send_message');
    formData.append('order_id', customerChatOrderId);
    formData.append('message', message);
    formData.append('sender_type', 'customer');
    
    fetch('../customer/api_customer_chat.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            input.value = '';
            loadCustomerMessages();
        }
    });
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
    if (customerChatOrderId) {
        loadCustomerMessages();
        customerChatPoll = setInterval(loadCustomerMessages, 2000);
        
        document.getElementById('customerChatInput').addEventListener('keypress', e => {
            if (e.key === 'Enter') sendCustomerMessage();
        });
    }
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (customerChatPoll) clearInterval(customerChatPoll);
});
</script>
