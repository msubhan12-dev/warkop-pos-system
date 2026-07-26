<?php
require_once '../config/config.php';
requireRole(['owner']);
$db = getDB();

// Get the current month and year
$month = date('m');
$year = date('Y');
$bulan = date('F Y');

// Get Revenue (Pendapatan) for current month - grouped per day with order count (same status filter as revenue.php)
$stmt_rev = $db->prepare("
    SELECT 
        DATE(created_at) as date, 
        COUNT(*) as order_count,
        SUM(total) as amount 
    FROM orders 
    WHERE status IN ('confirmed', 'cooking', 'ready', 'served', 'completed') AND MONTH(created_at) = ? AND YEAR(created_at) = ?
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$stmt_rev->execute([$month, $year]);
$revenues = $stmt_rev->fetchAll();

// Get Expenses for current month
$stmt_exp = $db->prepare("
    SELECT expense_date as date, description, amount 
    FROM expenses 
    WHERE MONTH(expense_date) = ? AND YEAR(expense_date) = ?
    ORDER BY expense_date ASC
");
$stmt_exp->execute([$month, $year]);
$expenses = $stmt_exp->fetchAll();

// Get Stocks from ingredients table (bahan baku)
$stmt_stock = $db->query("
    SELECT name, unit, stock_quantity 
    FROM ingredients
    ORDER BY stock_quantity ASC
");
$stocks = $stmt_stock->fetchAll();

// Set Headers for Excel Download
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Laporan_Keuangan_Stok_".date('F_Y').".xls");
header("Pragma: no-cache");
header("Expires: 0");

?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-type" content="text/html;charset=utf-8" />
    <!-- Adding Excel specific styling -->
    <style>
        table { border-collapse: collapse; font-family: Arial, sans-serif; }
        th, td { border: 0.5pt solid #000000; padding: 5px; }
        .title { font-size: 18pt; font-weight: bold; text-align: center; border: none; }
        .subtitle { font-size: 12pt; font-weight: bold; text-align: center; border: none; }
        .header { background-color: #2E7D32; color: #FFFFFF; font-weight: bold; text-align: center; }
        .header-blue { background-color: #1565C0; color: #FFFFFF; font-weight: bold; text-align: center; }
        .money { mso-number-format:"\#\,\#\#0"; text-align: right; }
        .date { mso-number-format:"yyyy\-mm\-dd"; text-align: center; }
        .negative { color: #D32F2F; }
        .positive { color: #388E3C; }
        .warning { background-color: #FFEBEE; color: #D32F2F; font-weight: bold; text-align: center; }
        .normal { text-align: center; }
        .total-row { font-weight: bold; background-color: #E8F5E9; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="4" class="title">LAPORAN KEUANGAN & STOK HERBAL</td>
        </tr>
        <tr>
            <td colspan="4" class="subtitle">Bulan: <?= $bulan ?></td>
        </tr>
        <tr><td colspan="4" style="border:none;"></td></tr>

        <!-- BAGIAN 1: KEUANGAN -->
        <tr>
            <th colspan="4" class="header">1. RINCIAN PENDAPATAN & PENGELUARAN</th>
        </tr>
        <tr>
            <th style="background-color:#E0E0E0; font-weight:bold; text-align:center;">Tanggal</th>
            <th style="background-color:#E0E0E0; font-weight:bold; text-align:center;">Keterangan</th>
            <th style="background-color:#E0E0E0; font-weight:bold; text-align:center;">Tipe</th>
            <th style="background-color:#E0E0E0; font-weight:bold; text-align:center;">Nominal (Rp)</th>
        </tr>
        
        <?php
        // Row 8 is the first data row (Rows: 1-Title, 2-Subtitle, 3-Empty, 4-Section Header, 5-Table Headers)
        $startRow = 6; 
        $currentRow = $startRow;
        
        // Output Revenues
        foreach ($revenues as $rev) {
            $dayNames = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
            $dayName = $dayNames[date('w', strtotime($rev['date']))];
            $formattedDate = $dayName . ', ' . date('d M Y', strtotime($rev['date']));
            $desc = 'Pendapatan POS - ' . $rev['order_count'] . ' Pesanan Selesai';
            echo "<tr>";
            echo "<td class='date'>" . $formattedDate . "</td>";
            echo "<td>" . $desc . "</td>";
            echo "<td class='positive' style='text-align:center;'>Pendapatan</td>";
            echo "<td class='money positive'>+" . number_format($rev['amount'], 0, ',', '.') . "</td>";
            echo "</tr>";
            $currentRow++;
        }
        
        // Output Expenses
        foreach ($expenses as $exp) {
            $dayNames = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
            $dayName = $dayNames[date('w', strtotime($exp['date']))];
            $formattedDate = $dayName . ', ' . date('d M Y', strtotime($exp['date']));
            echo "<tr>";
            echo "<td class='date'>" . $formattedDate . "</td>";
            echo "<td>" . htmlspecialchars($exp['description']) . "</td>";
            echo "<td class='negative' style='text-align:center;'>Pengeluaran</td>";
            echo "<td class='money negative'>-" . number_format($exp['amount'], 0, ',', '.') . "</td>";
            echo "</tr>";
            $currentRow++;
        }
        
        $endRow = $currentRow - 1;
        
        // If there is no data, create a dummy row so formula doesn't break
        if ($startRow > $endRow) {
            echo "<tr><td colspan='3'>Tidak ada data bulan ini</td><td class='money'>0</td></tr>";
            $startRow = $currentRow;
            $endRow = $currentRow;
            $currentRow++;
        }
        ?>
        
        <!-- Totals using Excel Formulas -->
        <tr class="total-row">
            <td colspan="3" style="text-align: right;">LABA BERSIH BULAN INI:</td>
            <!-- Here is the raw Excel Formula -->
            <td class="money">=SUM(D<?= $startRow ?>:D<?= $endRow ?>)</td>
        </tr>
        
        <tr><td colspan="4" style="border:none;"></td></tr>
        <tr><td colspan="4" style="border:none;"></td></tr>

        <!-- BAGIAN 2: STOK BAHAN BAKU -->
        <tr>
            <th colspan="4" class="header-blue">2. LAPORAN STOK BAHAN BAKU SAAT INI</th>
        </tr>
        <tr>
            <th style="background-color:#E0E0E0; font-weight:bold; text-align:center;">Nama Bahan Baku</th>
            <th style="background-color:#E0E0E0; font-weight:bold; text-align:center;">Satuan</th>
            <th style="background-color:#E0E0E0; font-weight:bold; text-align:center;">Jumlah Stok</th>
            <th style="background-color:#E0E0E0; font-weight:bold; text-align:center;">Status</th>
        </tr>
        <?php foreach ($stocks as $stock): 
            // Flag stock as warning if <= 10
            $isLow = $stock['stock_quantity'] <= 10;
            $rowClass = $isLow ? 'warning' : 'normal';
            $statusLabel = $isLow ? 'Perlu Ditambah' : 'Cukup';
        ?>
        <tr>
            <td><?= htmlspecialchars($stock['name']) ?></td>
            <td class="normal"><?= htmlspecialchars($stock['unit']) ?></td>
            <td class="<?= $rowClass ?>"><?= $stock['stock_quantity'] ?></td>
            <td class="<?= $rowClass ?>"><?= $statusLabel ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($stocks)): ?>
        <tr><td colspan="4" style="text-align:center;">Belum ada data stok bahan baku.</td></tr>
        <?php endif; ?>
    </table>
</body>
</html>
