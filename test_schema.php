<?php
require_once __DIR__ . '/config/config.php';
$db = getDB();
$stmt = $db->query("DESCRIBE menus");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
