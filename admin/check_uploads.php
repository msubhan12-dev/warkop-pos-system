<?php
require_once '../config/config.php';

$chat_dir = ROOT_PATH . '/uploads/chat';
$uploads_dir = ROOT_PATH . '/uploads';

echo "<h2>Upload Diagnostics</h2>";
echo "<p><strong>UPLOADS_PATH:</strong> " . UPLOADS_PATH . "</p>";
echo "<p><strong>UPLOADS_URL:</strong> " . UPLOADS_URL . "</p>";
echo "<p><strong>ROOT_PATH:</strong> " . ROOT_PATH . "</p>";

// Check uploads dir
if (is_dir($uploads_dir)) {
    echo "<p>✓ /uploads directory exists</p>";
    echo "<p>Permissions: " . substr(sprintf('%o', fileperms($uploads_dir)), -4) . "</p>";
} else {
    echo "<p>✗ /uploads directory NOT found</p>";
}

// Check chat dir
if (is_dir($chat_dir)) {
    echo "<p>✓ /uploads/chat directory exists</p>";
    echo "<p>Permissions: " . substr(sprintf('%o', fileperms($chat_dir)), -4) . "</p>";
    
    $files = scandir($chat_dir);
    echo "<p>Files in /uploads/chat:</p>";
    echo "<ul>";
    foreach ($files as $f) {
        if ($f !== '.' && $f !== '..') {
            echo "<li>$f</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p>✗ /uploads/chat directory NOT found - trying to create...</p>";
    if (@mkdir($chat_dir, 0755, true)) {
        echo "<p>✓ Created /uploads/chat directory</p>";
    } else {
        echo "<p>✗ Failed to create /uploads/chat directory</p>";
    }
}

// Test file write
$test_file = $chat_dir . '/test_' . time() . '.txt';
if (@file_put_contents($test_file, 'test')) {
    echo "<p>✓ Can write files to /uploads/chat</p>";
    unlink($test_file);
} else {
    echo "<p>✗ Cannot write files to /uploads/chat</p>";
}

// List recent chat messages in DB
echo "<h3>Recent messages in database:</h3>";
$db = getDB();
$stmt = $db->query("SELECT id, message_type, media_url FROM staff_messages ORDER BY id DESC LIMIT 5");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Type</th><th>Media URL</th></tr>";
foreach ($rows as $row) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['message_type'] . "</td>";
    echo "<td>" . htmlspecialchars($row['media_url']) . "</td>";
    echo "</tr>";
}
echo "</table>";
?>
