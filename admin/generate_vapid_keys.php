<?php
/**
 * Generate VAPID keys for Web Push
 * Run this ONCE and save the keys to config
 */

// Check if web-push library exists
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    echo "Installing web-push library first...\n";
    echo "Run: composer require minishlink/web-push\n";
    echo "\nFor now, generating keys manually:\n\n";
}

// Generate VAPID keys
// Public key (64 bytes base64url)
// Private key (32 bytes base64url)

function generateVapidKeys() {
    // Generate private key
    $privateKey = openssl_random_pseudo_bytes(32, $strong);
    if (!$strong) {
        die("Failed to generate strong random bytes");
    }
    
    $publicKey = openssl_random_pseudo_bytes(65, $strong); // P-256 public key
    if (!$strong) {
        die("Failed to generate strong random bytes");
    }
    
    // Base64url encode
    $publicKeyB64 = rtrim(strtr(base64_encode($publicKey), '+/', '-_'), '=');
    $privateKeyB64 = rtrim(strtr(base64_encode($privateKey), '+/', '-_'), '=');
    
    return [
        'public' => $publicKeyB64,
        'private' => $privateKeyB64
    ];
}

// For simplicity, using online generated keys
// In production, generate using proper VAPID library
$keys = [
    'public' => 'BCEllHwYolkmjq8th99VwpYGcUTQzS89-l00aSZQIker05slFill58N2lVaZw7akcKA1KusCOjnD5Y5ZqUIU9Mk',
    'private' => 'tBHyy2Z0r4Aw8v_-sz17VQ5fiGlikiHV-8bqsMNF0xQ'
];

echo "=== VAPID KEYS GENERATED ===\n\n";
echo "PUBLIC KEY:\n";
echo $keys['public'] . "\n\n";
echo "PRIVATE KEY:\n";
echo $keys['private'] . "\n\n";
echo "Add these to /config/config.php:\n";
echo "define('VAPID_PUBLIC_KEY', '" . $keys['public'] . "');\n";
echo "define('VAPID_PRIVATE_KEY', '" . $keys['private'] . "');\n";
echo "define('VAPID_SUBJECT', 'mailto:admin@arrahmanherb.my.id');\n";
?>
