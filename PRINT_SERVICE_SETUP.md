# Setup Auto-Print Service (Bluetooth 58printer)

Panduan setup auto-print untuk thermal printer Bluetooth "58printer" di Mac.

## Quick Start

### Step 1: Install Node.js
```bash
# Check apakah Node.js sudah installed
node --version

# Jika belum, install dari:
# https://nodejs.org/en/download/
```

### Step 2: Install Dependencies
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/warkop/print-service
npm install
```

### Step 3: Start Print Service
```bash
./start.sh
```

Output:
```
╔════════════════════════════════════════════╗
║  WARKOP Print Service                      ║
║  Port: 9100                                ║
║  Printer: 58printer (Bluetooth)           ║
║  Status: Running ✓                         ║
╚════════════════════════════════════════════╝
```

**JANGAN close terminal ini!** Biarkan jalan di background.

### Step 4: Test Print

**Test 1: Check service status**
```bash
curl http://localhost:9100/health
```

Output:
```json
{
  "status": "ok",
  "service": "WARKOP Print Service",
  "printer": "58printer",
  "queue": 0
}
```

**Test 2: Admin approve QRIS payment**
1. Buka `/admin/orders.php`
2. Klik order dengan QRIS verified
3. Klik "Print Resi"
4. Printer harusnya auto-print!

## How It Works

```
Admin click "Print Resi"
         ↓
Print Receipt Page
         ↓
Send HTML to Print Service (localhost:9100)
         ↓
Print Service Process
         ↓
Auto-Print ke 58printer
```

## Keep Service Running

### Option 1: Terminal Tab (Simple)
- Buka terminal baru
- Run `./start.sh`
- Biarkan terbuka

### Option 2: Background Process
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/warkop/print-service
nohup ./start.sh > /tmp/print-service.log 2>&1 &
```

### Option 3: Auto-Start on Mac Boot
Lihat di `print-service/README.md` untuk launchd setup.

## Troubleshooting

### "Print service not running" error
- Check print service terminal masih jalan
- Cek port 9100: `lsof -i :9100`

### Printer tidak print
- Cek printer "58printer" paired di Mac Bluetooth
- Try manual print dari TextEdit → Cmd+P → Select 58printer → Print

### Need to check queue
```bash
curl http://localhost:9100/queue
```

## File Locations

```
/Applications/XAMPP/xamppfiles/htdocs/warkop/
├── print-service/
│   ├── index.js           (Main service)
│   ├── package.json       (Dependencies)
│   ├── start.sh           (Start script)
│   └── README.md          (Detailed docs)
└── admin/
    └── print_receipt.php  (Trigger print)
```

## Next Steps

1. ✅ Install & start print service
2. ✅ Test dengan print receipt
3. ✅ Setup auto-start (optional)
4. ✅ Monitor logs untuk troubleshooting

Done! Auto-print siap digunakan.
