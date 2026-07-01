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
            <p>Sistem Kasir Terpadu</p>
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
                <span class="info-label">Nomor Meja:</span>
                <span>Meja <?= $order['table_number'] ?></span>
            </div>
            <?php else: ?>
            <div class="info-row">
                <span class="info-label">Tipe:</span>
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
    
    <script>
        // Trigger print immediately on load
        window.onload = function() {
            setTimeout(() => {
                window.print();
            }, 300);
        };
        
        // Auto close tab after print prompt finishes
        window.onafterprint = function() {
            window.close();
        };
    </script>
</body>
</html>
