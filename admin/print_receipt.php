<?php
require_once '../config/config.php';
requireRole(['kasir', 'owner']);

$orderId = (int)($_GET['order'] ?? 0);
if (!$orderId) {
    die('Order not found');
}

$db = getDB();

// Get order details
$stmt = $db->prepare("
    SELECT o.*, t.table_number, u.full_name as kasir_name
    FROM orders o
    LEFT JOIN tables t ON o.table_id = t.id
    LEFT JOIN users u ON o.created_by = u.id
    WHERE o.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    die('Order not found');
}

// Get order items
$stmt = $db->prepare("
    SELECT oi.*, m.name as menu_name
    FROM order_items oi
    JOIN menus m ON oi.menu_id = m.id
    WHERE oi.order_id = ?
");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll();

// Get payment info
$payment = getPaymentDetails($orderId);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Receipt - <?= $order['order_number'] ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @page {
            size: auto;
            margin: 0;
        }
        
        body {
            font-family: 'Courier New', Courier, monospace;
            background: white;
            color: black;
            padding: 3mm;
            width: 100%;
        }
        
        .receipt {
            width: 58mm; /* standard pocket thermal size, prints perfectly on 58mm & 80mm */
            max-width: 100%;
            margin: 0 auto;
            background: white;
            font-size: 8pt;
            line-height: 1.3;
        }
        
        .header {
            text-align: center;
            border-bottom: 1.5px dashed #000;
            padding-bottom: 3mm;
            margin-bottom: 3mm;
        }
        
        .header h1 {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 1mm;
        }
        
        .header p {
            font-size: 8pt;
            color: #000;
        }
        
        .order-info {
            border-bottom: 1px dashed #000;
            padding-bottom: 2mm;
            margin-bottom: 3mm;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1mm;
        }
        
        .info-label {
            font-weight: normal;
        }
        
        .items {
            border-bottom: 1px dashed #000;
            padding-bottom: 2mm;
            margin-bottom: 3mm;
        }
        
        .item-row {
            margin-bottom: 2.5mm;
        }
        
        .item-title {
            font-weight: bold;
        }
        
        .item-details {
            display: flex;
            justify-content: space-between;
            font-size: 7.5pt;
            margin-top: 0.5mm;
            padding-left: 2mm;
        }
        
        .totals {
            border-bottom: 1px dashed #000;
            padding-bottom: 2mm;
            margin-bottom: 3mm;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1mm;
        }
        
        .total-row.grand {
            font-weight: bold;
            font-size: 9.5pt;
            border-top: 1px dashed #000;
            padding-top: 1.5mm;
            margin-top: 1.5mm;
        }
        
        .payment-status-box {
            text-align: center;
            border: 1.5px solid #000;
            padding: 1.5mm;
            margin: 3mm 0;
            font-weight: bold;
            font-size: 9pt;
            letter-spacing: 0.1em;
        }
        
        .footer {
            text-align: center;
            font-size: 7.5pt;
            padding-top: 2mm;
        }
        
        .footer-line {
            margin-bottom: 1mm;
        }
        
        @media print {
            body {
                padding: 2mm;
            }
            .receipt {
                width: 58mm;
                page-break-after: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <!-- Header -->
        <div class="header">
            <h1><?= APP_NAME ?></h1>
            <p>Struk Pembelian di ArrahmanHerb</p>
        </div>
        
        <!-- Order Info -->
        <div class="order-info">
            <div class="info-row">
                <span class="info-label">No. Pesanan:</span>
                <span style="font-weight: bold;"><?= $order['order_number'] ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Tanggal:</span>
                <span><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Pelanggan:</span>
                <span><?= $order['customer_name'] ?></span>
            </div>
            <?php if ($order['table_number']): ?>
            <div class="info-row">
                <span class="info-label">Layanan:</span>
                <span>Meja <?= $order['table_number'] ?></span>
            </div>
            <?php elseif ($order['order_type'] === 'delivery'): ?>
            <div class="info-row">
                <span class="info-label">Layanan:</span>
                <span>Pesan Antar</span>
            </div>
            <?php if (!empty($order['delivery_address'])): ?>
            <div class="info-row" style="margin-top: 1mm;">
                <span class="info-label">Alamat:</span>
                <span style="font-size: 7.5pt; max-width: 60%; text-align: right;"><?= nl2br(htmlspecialchars($order['delivery_address'])) ?></span>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <div class="info-row">
                <span class="info-label">Layanan:</span>
                <span>Take Away</span>
            </div>
            <?php endif; ?>
            <?php if ($order['kasir_name']): ?>
            <div class="info-row">
                <span class="info-label">Kasir:</span>
                <span><?= $order['kasir_name'] ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Items -->
        <div class="items">
            <?php foreach ($items as $item): ?>
            <div class="item-row">
                <div class="item-title"><?= $item['menu_name'] ?></div>
                <div class="item-details">
                    <span><?= $item['quantity'] ?> x <?= number_format($item['price'], 0, ',', '.') ?></span>
                    <span><?= number_format($item['subtotal'], 0, ',', '.') ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Totals -->
        <div class="totals">
            <div class="total-row grand">
                <span>TOTAL:</span>
                <span>Rp <?= number_format($order['total'], 0, ',', '.') ?></span>
            </div>
        </div>
        
        <!-- Payment Details -->
        <?php if ($payment): ?>
        <div class="order-info" style="border-bottom: none; margin-bottom: 0; padding-bottom: 0;">
            <div class="info-row">
                <span class="info-label">Metode:</span>
                <span style="font-weight: bold; text-transform: uppercase;">
                    <?= $payment['payment_method'] === 'qris' ? 'QRIS' : ($payment['payment_method'] === 'transfer' ? 'TRANSFER' : 'TUNAI') ?>
                </span>
            </div>
            <?php if ($payment['payment_method'] === 'cash'): ?>
            <div class="info-row">
                <span class="info-label">Bayar:</span>
                <span><?= number_format($payment['paid_amount'], 0, ',', '.') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Kembali:</span>
                <span><?= number_format($payment['change_amount'], 0, ',', '.') ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="payment-status-box">
            *** <?= $payment['status'] === 'success' ? 'LUNAS / PAID' : 'BELUM BAYAR' ?> ***
        </div>
        <?php endif; ?>
        
        <!-- Footer -->
        <div class="footer">
            <div class="footer-line">------------------------</div>
            <div class="footer-line">Terima Kasih!</div>
            <div class="footer-line">Selamat Menikmati</div>
            <div class="footer-line"><?= date('d/m/Y H:i:s') ?></div>
            <div class="footer-line">------------------------</div>
        </div>
    </div>
    
    <!-- Action Buttons (Hidden when printing) -->
    <style>
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin: 20px auto;
            max-width: 58mm;
            padding: 0 10px;
        }
        .btn {
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
        }
        .btn-standard {
            background: #10b981;
            color: white;
        }
        .btn-thermer {
            background: #3b82f6;
            color: white;
        }
        .btn-close {
            background: #ef4444;
            color: white;
        }
        @media print {
            .action-buttons { display: none; }
        }
    </style>

    <?php
    // Generate plain text receipt for Thermal Apps
    $rawText = "      " . APP_NAME . "\n";
    $rawText .= "      Sistem Kasir Terpadu\n";
    $rawText .= "================================\n";
    $rawText .= "No. Pesanan: " . $order['order_number'] . "\n";
    $rawText .= "Tanggal: " . date('d/m/Y H:i', strtotime($order['created_at'])) . "\n";
    $rawText .= "Pelanggan: " . $order['customer_name'] . "\n";
    if ($order['table_number']) $rawText .= "Nomor Meja: Meja " . $order['table_number'] . "\n";
    else $rawText .= "Tipe: Take Away\n";
    $rawText .= "================================\n";
    foreach($items as $item) {
        $rawText .= $item['menu_name'] . "\n";
        $qtyPrice = $item['quantity'] . "x " . number_format($item['price'],0,',','.');
        $subtotal = number_format($item['subtotal'],0,',','.');
        $rawText .= str_pad($qtyPrice, 16, " ") . str_pad($subtotal, 16, " ", STR_PAD_LEFT) . "\n";
    }
    $rawText .= "================================\n";
    $rawText .= str_pad("TOTAL:", 16, " ") . str_pad("Rp " . number_format($order['total'],0,',','.'), 16, " ", STR_PAD_LEFT) . "\n";
    
    if ($payment) {
        $method = $payment['payment_method'] === 'qris' ? 'QRIS' : ($payment['payment_method'] === 'transfer' ? 'TRANSFER' : 'TUNAI');
        $rawText .= "Metode: " . $method . "\n";
        if ($payment['payment_method'] === 'cash') {
            $rawText .= "Bayar: " . number_format($payment['paid_amount'],0,',','.') . "\n";
            $rawText .= "Kembali: " . number_format($payment['change_amount'],0,',','.') . "\n";
        }
        $status = $payment['status'] === 'success' ? 'LUNAS / PAID' : 'BELUM BAYAR';
        $rawText .= "\n*** " . $status . " ***\n";
    }
    
    $rawText .= "--------------------------------\n";
    $rawText .= "         Terima Kasih!          \n";
    $rawText .= "       Selamat Menikmati        \n";
    $rawText .= "\n\n\n";
    ?>

    <div class="action-buttons">
        <button class="btn btn-thermer" onclick="printThermer()">
            <svg style="width:16px;height:16px" viewBox="0 0 24 24"><path fill="currentColor" d="M17.75,3C17.75,3 17.75,4.5 17.75,4.5C17.75,4.5 19.5,4.5 19.5,4.5C19.5,4.5 19.5,21 19.5,21C19.5,21 4.5,21 4.5,21C4.5,21 4.5,4.5 4.5,4.5C4.5,4.5 6.25,4.5 6.25,4.5C6.25,4.5 6.25,3 6.25,3C6.25,3 3,3 3,3C3,3 3,22.5 3,22.5C3,22.5 21,22.5 21,22.5C21,22.5 21,3 21,3C21,3 17.75,3 17.75,3M8,11.5L9.5,13L13,9.5L14.5,11L9.5,16L6.5,13L8,11.5M10.5,3H13.5V6H10.5V3Z" /></svg>
            Kirim ke Thermer / RawBT (HP Android)
        </button>
        <button class="btn btn-serial" onclick="printWebSerial()" style="background: #8b5cf6; color: white;">
            <svg style="width:16px;height:16px" viewBox="0 0 24 24"><path fill="currentColor" d="M7,15H9C9,16.08 10.37,17 12,17C13.63,17 15,16.08 15,15C15,13.9 13.96,13.5 11.76,12.97C9.64,12.44 7,11.78 7,9C7,7.21 8.47,5.69 10.5,5.18V3H13.5V5.18C15.53,5.69 17,7.21 17,9H15C15,7.92 13.63,7 12,7C10.37,7 9,7.92 9,9C9,10.1 10.04,10.5 12.24,11.03C14.36,11.56 17,12.22 17,15C17,16.79 15.53,18.31 13.5,18.82V21H10.5V18.82C8.47,18.31 7,16.79 7,15Z" /></svg>
            Direct Bluetooth Laptop (Web Serial)
        </button>
        <button class="btn btn-standard" onclick="window.print()">
            <svg style="width:16px;height:16px" viewBox="0 0 24 24"><path fill="currentColor" d="M18,3H6V7H18V3M19,12A1,1 0 0,1 18,11A1,1 0 0,1 19,10A1,1 0 0,1 20,11A1,1 0 0,1 19,12M16,19H8V14H16V19M19,8H5A3,3 0 0,0 2,11V17H6V21H18V17H22V11A3,3 0 0,0 19,8Z" /></svg>
            Print Standar (Browser)
        </button>
        <button class="btn btn-close" onclick="window.close()">Tutup Jendela</button>
    </div>
    
    <script>
        const rawReceiptText = <?= json_encode($rawText) ?>;

        function printThermer() {
            if (navigator.share) {
                // Gunakan fitur Share bawaan Android agar user bisa pilih aplikasi Thermer
                navigator.share({
                    title: 'Struk Pesanan',
                    text: rawReceiptText,
                }).catch(err => {
                    console.log('Share dibatalkan atau gagal', err);
                });
            } else {
                // Fallback jika browser sangat jadul: Copy ke clipboard
                navigator.clipboard.writeText(rawReceiptText).then(() => {
                    alert('Teks struk berhasil dicopy! Silakan buka aplikasi Thermer dan paste di sana.');
                }).catch(err => {
                    alert('Gagal menyalin teks. Browser tidak mendukung.');
                });
            }
        }
        
        async function printWebSerial() {
            if (!('serial' in navigator)) {
                alert('Maaf, Web Serial API tidak didukung di browser ini. Gunakan Google Chrome atau Microsoft Edge di Laptop/PC.');
                return;
            }
            
            try {
                let port;
                // Cek apakah browser sudah pernah diberi izin ke port Bluetooth ini sebelumnya
                const ports = await navigator.serial.getPorts();
                
                if (ports.length > 0) {
                    // Langsung pakai port yang sudah tersimpan otomatis (Tanpa Popup)
                    port = ports[0]; 
                } else {
                    // Jika baru pertama kali, minta izin (Muncul Popup Chrome)
                    port = await navigator.serial.requestPort();
                }
                
                // Buka koneksi port
                await port.open({ baudRate: 9600 });
                
                const writer = port.writable.getWriter();
                const encoder = new TextEncoder();
                
                // ESC/POS Commands
                const initCmd = new Uint8Array([0x1B, 0x40]); // ESC @ (Initialize)
                const cutCmd = new Uint8Array([0x1D, 0x56, 0x41, 0x10]); // GS V A (Cut)
                
                // Tulis ke printer
                await writer.write(initCmd);
                await writer.write(encoder.encode(rawReceiptText));
                await writer.write(cutCmd);
                
                writer.releaseLock();
                await port.close();
                
                console.log('Berhasil mencetak via Bluetooth Laptop!');
            } catch (err) {
                console.error(err);
                if (err.name !== 'NotFoundError') {
                    alert('Gagal ngeprint: ' + err.message + '\n\nPastikan printer sudah terhubung via Bluetooth ke Laptop, dan pilih port yang bertuliskan "Standard Serial over Bluetooth link".');
                }
            }
        }
    </script>
</body>
</html>
