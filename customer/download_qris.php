<?php
require_once '../config/config.php';
require_once '../includes/QrisGenerator.php';

$orderNumber = clean($_GET['order'] ?? '');
if (empty($orderNumber)) {
    die("Order tidak valid.");
}

$db = getDB();
$stmt = $db->prepare("SELECT amount FROM payments p JOIN orders o ON o.id = p.order_id WHERE o.order_number = ? AND p.payment_method = 'qris'");
$stmt->execute([$orderNumber]);
$payment = $stmt->fetch();

if (!$payment) {
    die("Payment tidak ditemukan.");
}

$amount = $payment['amount'] + 1000; // Biaya Admin
$dynamicQrisString = QrisGenerator::generateDynamic($amount);
$encodedText = urlencode($dynamicQrisString);
$url = "https://api.qrserver.com/v1/create-qr-code/?size=350x350&data=" . $encodedText;

$image = @file_get_contents($url);

if ($image !== false) {
    $randomString = bin2hex(random_bytes(16));
    $randomFilename = 'qris_' . $randomString . '.png';
    
    header('Content-Description: File Transfer');
    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="' . $randomFilename . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . strlen($image));
    echo $image;
    exit;
} else {
    die("Gagal memuat QRIS.");
}
?>
