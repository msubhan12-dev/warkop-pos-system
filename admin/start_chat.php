<?php
/**
 * Start New Chat
 * Simple page to initiate new chat with staff member
 * Usage: 
 *   - From any page, add link: <a href="start_chat.php">Start Chat</a>
 *   - Or with pre-selected user: <a href="start_chat.php?user_id=3">Chat with User 3</a>
 */

require_once '../config/config.php';
requireRole(['owner', 'admin', 'kasir', 'dapur', 'pelayan']);

// If user_id passed, redirect to staff_chat with selection
if (isset($_GET['user_id'])) {
    header('Location: staff_chat.php?user_id=' . intval($_GET['user_id']));
    exit;
}

// Otherwise, redirect to staff_chat
header('Location: staff_chat.php');
exit;
?>
