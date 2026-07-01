# WARKOP Auto-Print Service

Service untuk auto-print receipt ke thermal printer Bluetooth (58printer) di Mac.

## Setup

### 1. Install Node.js (jika belum)
```bash
# Download dari https://nodejs.org
# Atau pakai Homebrew
brew install node
```

### 2. Install dependencies
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/warkop/print-service
npm install
```

### 3. Start service
```bash
./start.sh
```

Atau langsung:
```bash
node index.js
```

Output akan terlihat seperti:
```
╔════════════════════════════════════════════╗
║  WARKOP Print Service                      ║
║  Port: 9100                                ║
║  Printer: 58printer (Bluetooth)           ║
║  Status: Running ✓                         ║
╚════════════════════════════════════════════╝

API Endpoints:
  GET  http://localhost:9100/health - Service status
  GET  http://localhost:9100/queue   - Print queue
  POST http://localhost:9100/print   - Send print job
```

## Usage

### Check Service Status
```bash
curl http://localhost:9100/health
```

### Check Print Queue
```bash
curl http://localhost:9100/queue
```

### Send Print Job
```bash
curl -X POST http://localhost:9100/print \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<html>...</html>",
    "orderId": 1,
    "orderNumber": "ORD202501001"
  }'
```

## How It Works

1. Admin approve pembayaran QRIS di `/admin/orders.php`
2. Admin klik "Print Resi"
3. Page membuka `/admin/print_receipt.php`
4. Print receipt page mengirim HTML receipt ke print service (localhost:9100)
5. Print service auto-print ke printer Bluetooth "58printer"
6. Receipt print otomatis tanpa confirmation dialog

## Troubleshooting

### Service tidak bisa connect ke printer
- Pastikan printer "58printer" sudah paired di Mac Bluetooth
- Cek printer nyala dan dalam jarak Bluetooth

### Service tidak jalan
```bash
# Check port sudah dipakai?
lsof -i :9100

# Kill process yang pakai port 9100
kill -9 <PID>
```

### Print tidak keluar
- Cek print queue: `curl http://localhost:9100/queue`
- Check Mac system print settings
- Coba print manual dari TextEdit ke 58printer untuk confirm printer OK

## Keep Running (Recommended)

### Option 1: Keep terminal open
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/warkop/print-service
./start.sh
```
(Jangan close terminal, biarkan jalan di background)

### Option 2: Use launchd (Auto-start on Mac boot)
Create `/Library/LaunchDaemons/com.warkop.printservice.plist`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <key>Label</key>
    <string>com.warkop.printservice</string>
    <key>ProgramArguments</key>
    <array>
        <string>/usr/local/bin/node</string>
        <string>/Applications/XAMPP/xamppfiles/htdocs/warkop/print-service/index.js</string>
    </array>
    <key>RunAtLoad</key>
    <true/>
    <key>KeepAlive</key>
    <true/>
    <key>StandardErrorPath</key>
    <string>/tmp/warkop-printservice.log</string>
    <key>StandardOutPath</key>
    <string>/tmp/warkop-printservice.log</string>
</dict>
</plist>
```

Load it:
```bash
sudo launchctl load /Library/LaunchDaemons/com.warkop.printservice.plist
```

## Monitor Logs
```bash
tail -f /tmp/warkop-printservice.log
```

## Stop Service
```bash
# Ctrl+C di terminal dimana service jalan

# Atau jika pakai launchd:
sudo launchctl unload /Library/LaunchDaemons/com.warkop.printservice.plist
```
