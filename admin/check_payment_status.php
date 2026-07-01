<?php
require_once '../config/config.php';
requireRole(['owner']);

$orderNumber = clean($_GET['order'] ?? '');

if (!$orderNumber) {
    echo "Masukkan order number di URL: ?order=ORD20260621ZI3PJL";
    exit;
}

$db = getDB();
$stmt = $db->prepare("
    SELECT p.*, o.order_number
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    WHERE o.order_number = ?
");
$stmt->execute([$orderNumber]);
$payment = $stmt->fetch();

if (!$payment) {
    echo "Order tidak ditemukan: $orderNumber";
    exit;
}

echo "<pre>";
echo "Order Number: " . $payment['order_number'] . "\n";
echo "Payment ID: " . $payment['id'] . "\n";
echo "Verification Status: " . $payment['verification_status'] . "\n";
echo "Verified By: " . ($payment['verified_by'] ?? 'NULL') . "\n";
echo "Verified At: " . ($payment['verified_at'] ?? 'NULL') . "\n";
echo "Proof of Payment: " . ($payment['proof_of_payment'] ?? 'NULL') . "\n";
echo "\nFull Payment Data:\n";
print_r($payment);
echo "</pre>";
?>
