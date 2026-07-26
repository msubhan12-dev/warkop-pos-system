<?php
require_once '../config/config.php';
requireRole(['owner', 'kasir', 'dapur', 'pelayan']);

$db = getDB();
$current_user_id = $_SESSION['user_id'] ?? 0;
$pageTitle = 'Chat Tim';

// Get conversations (people user has chatted with)
$conversations = [];
try {
    $stmt = $db->prepare("
        SELECT DISTINCT 
            CASE 
                WHEN sender_id = ? THEN recipient_id
                ELSE sender_id
            END as other_user_id,
            MAX(created_at) as last_message_time,
            (SELECT message FROM staff_messages 
             WHERE (sender_id = ? AND recipient_id = other_user_id)
                OR (sender_id = other_user_id AND recipient_id = ?)
             ORDER BY created_at DESC LIMIT 1) as last_message,
            COUNT(CASE WHEN recipient_id = ? AND is_read = 0 THEN 1 END) as unread_count
        FROM staff_messages
        WHERE sender_id = ? OR recipient_id = ?
        GROUP BY other_user_id
        ORDER BY MAX(created_at) DESC
    ");
    $stmt->execute([$current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id]);
    $convList = $stmt->fetchAll();
    
    // Enrich with user details
    foreach ($convList as $conv) {
        $userStmt = $db->prepare("SELECT id, username, full_name, role FROM users WHERE id = ?");
        $userStmt->execute([$conv['other_user_id']]);
        $user = $userStmt->fetch();
        if ($user) {
            $conversations[] = array_merge($conv, $user);
        }
    }
} catch (Exception $e) {
    error_log("Chat Error: " . $e->getMessage());
}

// Get all available staff for "New Chat"
$allStaff = [];
try {
    $query = "SELECT id, username, full_name, role FROM users WHERE is_active = 1 AND role IN ('owner','kasir','dapur','pelayan')";
    if ($current_user_id) {
        $query .= " AND id != " . intval($current_user_id);
    }
    $query .= " ORDER BY full_name ASC";
    $stmt = $db->query($query);
    $allStaff = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Staff List Error: " . $e->getMessage());
}

include '../includes/header.php';
?>

<div class="p-6 max-w-5xl mx-auto">
    <h1 class="text-2xl font-bold text-slate-800 mb-6">
        <i class="fas fa-comments text-blue-600 mr-2"></i>Chat Tim
    </h1>
    
    <div class="grid grid-cols-3 gap-4 h-[600px]">
        
        <!-- Conversations List (Left) -->
        <div class="col-span-1 bg-white rounded-lg border border-slate-200 overflow-hidden shadow-sm flex flex-col">
            
            <!-- New Chat Button -->
            <div class="p-3 border-b border-slate-200 bg-slate-50">
                <button onclick="showNewChatMenu()" class="w-full px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold text-sm transition flex items-center justify-center gap-2">
                    <i class="fas fa-plus"></i> Chat Baru
                </button>
            </div>
            
            <!-- Conversations -->
            <div class="flex-1 overflow-y-auto">
                <?php if (empty($conversations)): ?>
                <div class="p-4 text-center text-slate-400 text-sm">
                    <i class="fas fa-inbox text-3xl mb-2 block opacity-30"></i>
                    Belum ada percakapan
                </div>
                <?php else: ?>
                    <?php foreach ($conversations as $conv): ?>
                    <div class="border-b border-slate-100 cursor-pointer hover:bg-slate-50 transition"
                         onclick="selectConversation(<?= $conv['other_user_id'] ?>, '<?= htmlspecialchars($conv['full_name']) ?>')">
                        <div class="p-3">
                            <div class="flex justify-between items-start mb-1">
                                <h4 class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($conv['full_name']) ?></h4>
                                <?php if ($conv['unread_count'] > 0): ?>
                                <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full font-bold">
                                    <?= $conv['unread_count'] ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <p class="text-slate-600 text-xs truncate"><?= htmlspecialchars(substr($conv['last_message'] ?? 'Belum ada pesan', 0, 40)) ?></p>
                            <p class="text-slate-400 text-xs mt-1">
                                <?= date('H:i', strtotime($conv['last_message_time'])) ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Chat Area (Right) -->
        <div class="col-span-2 bg-white rounded-lg border border-slate-200 overflow-hidden shadow-sm flex flex-col">
            
            <!-- Chat Header -->
            <div class="p-4 bg-blue-50 border-b border-slate-200" id="chatHeader" style="display: none;">
                <h3 class="font-bold text-slate-800" id="chatHeaderText"></h3>
            </div>
            
            <!-- Empty State -->
            <div id="emptyState" class="flex-1 flex items-center justify-center text-center text-slate-400">
                <div>
                    <i class="fas fa-comments text-5xl mb-3 block opacity-30"></i>
                    <p>Pilih percakapan untuk mulai chat</p>
                </div>
            </div>
            
            <!-- Chat Messages -->
            <div class="flex-1 overflow-y-auto p-4 bg-white" id="chatMessages" style="display: none;">
            </div>
            
            <!-- Chat Input -->
            <div class="p-4 bg-white border-t border-slate-200" id="chatInputContainer" style="display: none;">
                <div class="flex gap-2">
                    <input 
                        type="text" 
                        id="staffChatInput" 
                        class="flex-1 px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Ketik pesan..."
                        autocomplete="off"
                    >
                    <button 
                        onclick="sendStaffMessage()"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold transition"
                    >
                        Kirim
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Chat Modal -->
<div id="newChatModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center" style="display: none;">
    <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4">
        <h3 class="text-lg font-bold text-slate-800 mb-4">Chat Baru</h3>
        <select id="newChatSelect" class="w-full px-3 py-2 border border-slate-300 rounded-lg mb-4 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">-- Pilih Staff --</option>
            <?php foreach ($allStaff as $staff): ?>
            <option value="<?= $staff['id'] ?>"><?= htmlspecialchars($staff['full_name']) ?> (<?= $staff['role'] ?>)</option>
            <?php endforeach; ?>
        </select>
        <div class="flex gap-2">
            <button onclick="closeNewChatModal()" class="flex-1 px-4 py-2 border border-slate-300 rounded-lg font-bold text-slate-700 hover:bg-slate-50">
                Batal
            </button>
            <button onclick="startNewChat()" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold">
                Mulai Chat
            </button>
        </div>
    </div>
</div>

<script>
let currentChatUserId = 0;
let staffChatPoll;

function selectConversation(userId, userName) {
    currentChatUserId = userId;
    closeNewChatModal();
    
    document.getElementById('chatHeader').style.display = 'block';
    document.getElementById('chatHeaderText').textContent = '💬 ' + userName;
    document.getElementById('emptyState').style.display = 'none';
    document.getElementById('chatMessages').style.display = 'block';
    document.getElementById('chatInputContainer').style.display = 'block';
    document.getElementById('staffChatInput').focus();
    
    loadStaffMessages();
    if (staffChatPoll) clearInterval(staffChatPoll);
    staffChatPoll = setInterval(loadStaffMessages, 2000);
}

function loadStaffMessages() {
    if (!currentChatUserId) return;
    
    fetch(`api_staff_chat.php?action=get_messages&user_id=${currentChatUserId}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderStaffMessages(data.messages);
            }
        })
        .catch(e => console.error('Error:', e));
}

function renderStaffMessages(messages) {
    const container = document.getElementById('chatMessages');
    container.innerHTML = '';
    
    if (messages.length === 0) {
        container.innerHTML = '<div class="text-center text-slate-400">Mulai percakapan dengan mengirim pesan pertama</div>';
        return;
    }
    
    messages.forEach(msg => {
        const isOwn = msg.sender_id == <?= $current_user_id ?>;
        const bubble = document.createElement('div');
        bubble.className = `flex mb-3 ${isOwn ? 'justify-end' : 'justify-start'}`;
        
        const bgClass = isOwn ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-800';
        const time = new Date(msg.created_at).toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'});
        
        bubble.innerHTML = `
            <div style="max-width: 70%;">
                <div class="${bgClass} rounded-lg px-4 py-2 break-words">
                    ${escapeHtml(msg.message)}
                </div>
                <div class="text-xs text-slate-500 mt-1 ${isOwn ? 'text-right' : ''}">
                    ${time}
                </div>
            </div>
        `;
        
        container.appendChild(bubble);
    });
    
    container.scrollTop = container.scrollHeight;
}

function sendStaffMessage() {
    const input = document.getElementById('staffChatInput');
    const message = input.value.trim();
    
    if (!message || !currentChatUserId) return;
    
    const formData = new FormData();
    formData.append('action', 'send_message');
    formData.append('recipient_id', currentChatUserId);
    formData.append('message', message);
    
    fetch('api_staff_chat.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            input.value = '';
            loadStaffMessages();
            location.reload();
        }
    })
    .catch(e => console.error('Error:', e));
}

function showNewChatMenu() {
    document.getElementById('newChatModal').style.display = 'flex';
}

function closeNewChatModal() {
    document.getElementById('newChatModal').style.display = 'none';
    document.getElementById('newChatSelect').value = '';
}

function startNewChat() {
    const userId = document.getElementById('newChatSelect').value;
    if (!userId) {
        alert('Pilih staff terlebih dahulu');
        return;
    }
    selectConversation(parseInt(userId), document.getElementById('newChatSelect').options[document.getElementById('newChatSelect').selectedIndex].text);
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

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('staffChatInput').addEventListener('keypress', e => {
        if (e.key === 'Enter') sendStaffMessage();
    });
});

window.addEventListener('beforeunload', () => {
    if (staffChatPoll) clearInterval(staffChatPoll);
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeNewChatModal();
});
</script>

<?php include '../includes/footer.php'; ?>
