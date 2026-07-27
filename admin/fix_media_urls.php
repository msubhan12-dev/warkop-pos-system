<?php
require_once '../config/config.php';

$db = getDB();

// Fix messages with "Array" as media_url
$stmt = $db->prepare("UPDATE staff_messages SET media_url = NULL WHERE media_url = 'Array'");
$result = $stmt->execute();

echo "<h2>Fixed Media URLs</h2>";
echo "<p>Rows updated: " . $stmt->rowCount() . "</p>";

// Verify
$check = $db->query("SELECT COUNT(*) as cnt FROM staff_messages WHERE media_url = 'Array'")->fetch();
echo "<p>Remaining 'Array' entries: " . $check['cnt'] . "</p>";

echo "<p><a href='check_uploads.php'>Back to diagnostics</a></p>";
?>
