<?php
/**
 * Chat System Verification Script
 * Run this in browser after deployment: https://arrahmanherb.my.id/admin/verify_chat_setup.php
 * PROTECTED - Admin only
 */

require_once '../config/config.php';
requireRole(['owner', 'admin']);

$checks = [];
$db = null;

try {
    $db = getDB();
} catch (Exception $e) {
    die("❌ Database connection failed: " . $e->getMessage());
}

// Check 1: Database connection
$checks[] = [
    'name' => 'Database Connection',
    'status' => $db ? 'PASS' : 'FAIL',
    'details' => $db ? 'Connected to ' . DB_NAME : 'Connection failed'
];

// Check 2: staff_messages table
try {
    $result = $db->query("SELECT COUNT(*) FROM staff_messages");
    $checks[] = [
        'name' => 'staff_messages Table',
        'status' => 'PASS',
        'details' => 'Table exists, ' . $result->fetchColumn() . ' messages'
    ];
} catch (Exception $e) {
    $checks[] = [
        'name' => 'staff_messages Table',
        'status' => 'FAIL',
        'details' => $e->getMessage()
    ];
}

// Check 3: customer_support_messages table
try {
    $result = $db->query("SELECT COUNT(*) FROM customer_support_messages");
    $checks[] = [
        'name' => 'customer_support_messages Table',
        'status' => 'PASS',
        'details' => 'Table exists, ' . $result->fetchColumn() . ' messages'
    ];
} catch (Exception $e) {
    $checks[] = [
        'name' => 'customer_support_messages Table',
        'status' => 'FAIL',
        'details' => $e->getMessage()
    ];
}

// Check 4: chat_messages table
try {
    $result = $db->query("SELECT COUNT(*) FROM chat_messages");
    $checks[] = [
        'name' => 'chat_messages Table',
        'status' => 'PASS',
        'details' => 'Table exists, ' . $result->fetchColumn() . ' messages'
    ];
} catch (Exception $e) {
    $checks[] = [
        'name' => 'chat_messages Table',
        'status' => 'FAIL',
        'details' => $e->getMessage()
    ];
}

// Check 5: Users count
try {
    $result = $db->query("SELECT COUNT(*) FROM users WHERE is_active = 1");
    $count = $result->fetchColumn();
    $checks[] = [
        'name' => 'Active Users',
        'status' => $count > 1 ? 'PASS' : 'WARN',
        'details' => "Found $count active users (need at least 2 for chat)"
    ];
} catch (Exception $e) {
    $checks[] = [
        'name' => 'Active Users',
        'status' => 'FAIL',
        'details' => $e->getMessage()
    ];
}

// Check 6: staff_chat.php file
$staff_chat_exists = file_exists('admin/staff_chat.php');
$checks[] = [
    'name' => 'staff_chat.php File',
    'status' => $staff_chat_exists ? 'PASS' : 'FAIL',
    'details' => $staff_chat_exists ? 'File exists' : 'File not found at admin/staff_chat.php'
];

// Check 7: api_staff_chat.php file
$api_staff_exists = file_exists('admin/api_staff_chat.php');
$checks[] = [
    'name' => 'api_staff_chat.php File',
    'status' => $api_staff_exists ? 'PASS' : 'FAIL',
    'details' => $api_staff_exists ? 'File exists' : 'File not found at admin/api_staff_chat.php'
];

// Check 8: Test API endpoint
if ($api_staff_exists) {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 5
        ]
    ]);
    $response = @file_get_contents('admin/api_staff_chat.php?action=get_unread_count', false, $context);
    $is_json = json_decode($response) !== null;
    $checks[] = [
        'name' => 'API Endpoint',
        'status' => $is_json ? 'PASS' : 'FAIL',
        'details' => $is_json ? 'API responding correctly' : 'API not responding with JSON'
    ];
}

// Check 9: header.php includes Chat Tim
$header_content = file_get_contents('includes/header.php');
$has_chat_menu = strpos($header_content, 'Chat Tim') !== false && strpos($header_content, 'staff_chat') !== false;
$checks[] = [
    'name' => 'Chat Tim Menu',
    'status' => $has_chat_menu ? 'PASS' : 'FAIL',
    'details' => $has_chat_menu ? 'Menu item found in header' : 'Menu item not in header.php'
];

// Check 10: Database charset
try {
    $result = $db->query("SELECT DEFAULT_CHARSET FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
    $charset = $result->fetch()['DEFAULT_CHARSET'];
    $checks[] = [
        'name' => 'Database Charset',
        'status' => $charset == 'utf8mb4' ? 'PASS' : 'WARN',
        'details' => "Charset: $charset (utf8mb4 recommended)"
    ];
} catch (Exception $e) {
    $checks[] = [
        'name' => 'Database Charset',
        'status' => 'FAIL',
        'details' => $e->getMessage()
    ];
}

$pass_count = count(array_filter($checks, fn($c) => $c['status'] === 'PASS'));
$fail_count = count(array_filter($checks, fn($c) => $c['status'] === 'FAIL'));
$warn_count = count(array_filter($checks, fn($c) => $c['status'] === 'WARN'));

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat System Verification</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #333;
            margin-top: 0;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat {
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            font-weight: bold;
        }
        .stat.pass {
            background: #ecfdf5;
            color: #10b981;
        }
        .stat.fail {
            background: #fef2f2;
            color: #ef4444;
        }
        .stat.warn {
            background: #fffbeb;
            color: #f59e0b;
        }
        .checks {
            display: grid;
            gap: 12px;
        }
        .check {
            display: flex;
            align-items: center;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #d1d5db;
        }
        .check.pass {
            background: #f0fdf4;
            border-left-color: #10b981;
        }
        .check.fail {
            background: #fef2f2;
            border-left-color: #ef4444;
        }
        .check.warn {
            background: #fffbeb;
            border-left-color: #f59e0b;
        }
        .check-icon {
            font-size: 24px;
            margin-right: 15px;
            width: 30px;
            text-align: center;
        }
        .check-content {
            flex: 1;
        }
        .check-name {
            font-weight: bold;
            color: #333;
        }
        .check-details {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }
        .check-status {
            font-weight: bold;
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .pass .check-status {
            background: #d1fae5;
            color: #065f46;
        }
        .fail .check-status {
            background: #fee2e2;
            color: #7f1d1d;
        }
        .warn .check-status {
            background: #fef3c7;
            color: #78350f;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #666;
            font-size: 12px;
        }
        .action-needed {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            color: #78350f;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Chat System Verification</h1>
        
        <div class="stats">
            <div class="stat pass">
                <div style="font-size: 24px;">✓</div>
                <div><?= $pass_count ?> Passed</div>
            </div>
            <div class="stat fail">
                <div style="font-size: 24px;">✗</div>
                <div><?= $fail_count ?> Failed</div>
            </div>
            <div class="stat warn">
                <div style="font-size: 24px;">⚠</div>
                <div><?= $warn_count ?> Warnings</div>
            </div>
        </div>

        <h2 style="margin-top: 30px;">Detailed Checks</h2>
        <div class="checks">
            <?php foreach ($checks as $check): ?>
            <div class="check <?= strtolower($check['status']) ?>">
                <div class="check-icon">
                    <?php if ($check['status'] === 'PASS'): ?>
                        ✓
                    <?php elseif ($check['status'] === 'FAIL'): ?>
                        ✗
                    <?php else: ?>
                        ⚠
                    <?php endif; ?>
                </div>
                <div class="check-content">
                    <div class="check-name"><?= $check['name'] ?></div>
                    <div class="check-details"><?= $check['details'] ?></div>
                </div>
                <div class="check-status"><?= $check['status'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($fail_count > 0): ?>
        <div class="action-needed">
            <strong>⚠️ Action Required:</strong>
            <p><?= $fail_count ?> check(s) failed. Please fix the issues above before using the chat system.</p>
            <p><a href="FIX_CHAT_SYSTEM.md" style="color: #78350f;">See fix instructions</a></p>
        </div>
        <?php elseif ($warn_count > 0): ?>
        <div class="action-needed">
            <strong>ℹ️ Warnings:</strong>
            <p><?= $warn_count ?> warning(s) detected. System should work but consider addressing these items.</p>
        </div>
        <?php else: ?>
        <div style="background: #ecfdf5; border-left: 4px solid #10b981; padding: 15px; border-radius: 8px; margin-top: 20px; color: #065f46;">
            <strong>✓ All Checks Passed!</strong>
            <p>Chat system is ready to use. Try it out:</p>
            <p><a href="admin/staff_chat.php" style="color: #047857; font-weight: bold;">Open Staff Chat →</a></p>
        </div>
        <?php endif; ?>

        <div class="footer">
            <p>Chat System Verification | Generated: <?= date('Y-m-d H:i:s') ?></p>
            <p>Back to: <a href="index.php">Admin Dashboard</a> | Delete this file after verification: <code>rm admin/verify_chat_setup.php</code></p>
        </div>
    </div>
</body>
</html>
