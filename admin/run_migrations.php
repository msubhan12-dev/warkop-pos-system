<?php
require_once '../config/config.php';

$db = getDB();

// First, check if table exists
try {
    $db->query("SELECT 1 FROM staff_messages LIMIT 1");
    echo "Table exists. Checking columns...\n";
    
    // Check if columns exist and add if missing
    $columns_to_add = [
        'message_type' => "ALTER TABLE staff_messages ADD COLUMN message_type enum('text','image','sticker') DEFAULT 'text'",
        'media_url' => "ALTER TABLE staff_messages ADD COLUMN media_url varchar(255) DEFAULT NULL",
        'is_read' => "ALTER TABLE staff_messages ADD COLUMN is_read tinyint(1) DEFAULT 0"
    ];
    
    $result = $db->query("DESCRIBE staff_messages")->fetchAll(PDO::FETCH_COLUMN, 0);
    $existing_columns = array_map('strtolower', $result);
    
    foreach ($columns_to_add as $col => $sql) {
        if (!in_array(strtolower($col), $existing_columns)) {
            try {
                $db->exec($sql);
                echo "✓ Added column: $col\n";
            } catch (Exception $e) {
                echo "✗ Failed to add $col: " . $e->getMessage() . "\n";
            }
        } else {
            echo "✓ Column already exists: $col\n";
        }
    }
    
} catch (Exception $e) {
    echo "Table doesn't exist or error: " . $e->getMessage() . "\n";
    echo "Creating table...\n";
    
    $create_sql = "CREATE TABLE IF NOT EXISTS `staff_messages` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `sender_id` int(11) NOT NULL,
        `recipient_id` int(11) NOT NULL,
        `message` text,
        `message_type` enum('text','image','sticker') DEFAULT 'text',
        `media_url` varchar(255) DEFAULT NULL,
        `is_read` tinyint(1) DEFAULT 0,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_sender_recipient` (`sender_id`, `recipient_id`),
        KEY `idx_is_read` (`is_read`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    try {
        $db->exec($create_sql);
        echo "✓ Table created successfully\n";
    } catch (Exception $e) {
        echo "✗ Failed to create table: " . $e->getMessage() . "\n";
    }
}

echo "\nDone!";
?>
