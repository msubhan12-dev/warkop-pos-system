# Network Thermal Printer Setup Guide

## 🖨️ Overview

Sekarang ada 2 cara print receipt:

1. **Browser Print** - Simple (recommended untuk awal)
   - Print via browser dialog
   - Bisa ke thermal printer, inkjet, atau PDF
   - Kasir pilih printer di dialog

2. **Network Printer** - Advanced (untuk production)
   - Direct connect ke thermal printer via network
   - Automatic print without dialog
   - ESC/POS commands

---

## 🔧 Network Printer Setup

### Step 1: Configure Printer Settings

**Go to:**
```
http://10.143.149.22/warkop/kasir/printer_settings.php
```

**Login as:**
- Username: `kasir1`
- Password: `password`

### Step 2: Find Printer IP

**Method 1: Printer Menu**
1. On thermal printer → Menu / Settings
2. Find Network Settings → View IP Address
3. Write down the IP (e.g., 192.168.1.100)

**Method 2: Router**
1. Open router admin (usually 192.168.1.1)
2. Login with router password
3. Check connected devices
4. Find printer name → note IP

**Method 3: Network Scanner**
```bash
# On Mac, use network scanner
# System Preferences → Printers & Scanners
# Add printer
# Should see printer with IP address
```

### Step 3: Fill in Settings

**On printer_settings.php page:**

| Field | Example | Notes |
|-------|---------|-------|
| Printer Type | Network Printer | Select for ESC/POS |
| IP Address | 192.168.1.100 | From step 2 |
| Port | 9100 | Default for ESC/POS |

### Step 4: Test Connection

1. Click "Test Koneksi" button
2. If success: ✅ "Koneksi berhasil!"
3. If fail: ❌ Check IP and port

### Step 5: Save Settings

1. Click "Simpan" button
2. Settings saved to session
3. Ready to print!

---

## 📱 How to Use

### From Kasir Payment Page

**Option 1: Browser Print (Default)**
```
1. Click "Cetak Struk"
2. Print dialog opens
3. Select printer (USB or Network)
4. Click Print
5. Receipt printed
```

**Option 2: Network Direct (After Configuration)**
```
1. Configure printer settings first
2. Click "Cetak Struk"
3. Receipt prints automatically
4. No dialog
```

---

## 🔌 Network Printer Connection Types

### Ethernet Connection
```
Thermal Printer
     ↓ (Ethernet Cable)
Network Router
     ↓ (WiFi)
Mac/Computer
```

**Best for:**
- Stable connection
- No interference
- Higher reliability

**Steps:**
1. Connect printer to router via Ethernet
2. Get IP from printer settings
3. Configure in system

### WiFi Connection
```
Thermal Printer (WiFi enabled)
     ↓ (WiFi)
Network Router
     ↓ (WiFi)
Mac/Computer
```

**Best for:**
- No cables needed
- Mobile positioning
- Flexible placement

**Steps:**
1. Configure printer WiFi SSID & password
2. Connect printer to same network as server
3. Get IP from printer settings
4. Configure in system

---

## 🖥️ ESC/POS Protocol

What is ESC/POS?
- Printer command language for thermal printers
- Universal standard
- Supports all major brands

**Supported Brands:**
- ✅ Epson (TM series)
- ✅ Star Micronics
- ✅ Bixolon
- ✅ Sunmi
- ✅ Most 80mm thermal printers

---

## 📋 Receipt Format

Receipt automatically formatted for 80mm printer:

```
┌──────────────────────────────┐
│         WARKOP OS            │
│   Sistem Kasir Terpadu       │
├──────────────────────────────┤
│ No. Pesanan: ORD202...       │
│ Waktu: 21/06/2026 14:30      │
├──────────────────────────────┤
│ Customer: John Doe           │
│ Meja: A1                     │
│                              │
│ 2x Kopi Hitam                │
│ 1x Nasi Goreng               │
│                              │
├──────────────────────────────┤
│            Total             │
│    Rp 125.000                │
│ Metode: TUNAI                │
├──────────────────────────────┤
│     Terima Kasih!            │
│   Selamat Menikmati          │
│                              │
│ 21/06/2026 14:30:45          │
└──────────────────────────────┘
```

---

## 🐛 Troubleshooting

### Issue: "Cannot connect to printer"

**Cause:** IP or port wrong

**Solution:**
1. Double-check IP address
2. Verify printer is powered on
3. Test from command line:
   ```bash
   # On Mac:
   telnet 192.168.1.100 9100
   # Should say "Connected"
   ```

### Issue: "Connection timeout"

**Cause:** Printer not on network or network unreachable

**Solution:**
1. Check printer WiFi/Ethernet connection
2. Check Mac is on same network
3. Restart printer and Mac
4. Ping printer:
   ```bash
   ping 192.168.1.100
   ```

### Issue: Receipt prints but wrong format

**Cause:** Wrong printer type selected

**Solution:**
1. Verify printer supports ESC/POS
2. Use "Browser Print" if issues
3. Check printer documentation

### Issue: Nothing happens when clicking print

**Cause:** Network unreachable or printer offline

**Solution:**
1. Test connection in printer settings
2. Check printer error messages
3. Verify port number (default 9100)
4. Try USB connection as fallback

---

## 💡 Best Practices

### Setup
- Test connection before production
- Keep printer on same network as server
- Use static IP for printer (if possible)
- Document printer IP & port

### Usage
- Check printer daily (power, paper, connectivity)
- Keep printer firmware updated
- Regular maintenance (clean thermal head)
- Keep backup paper supply

### Troubleshooting
- Keep printer manual handy
- Test with telnet/ping first
- Check XAMPP logs for errors
- Try browser print as fallback

---

## 🔗 Printer Settings Page

### Access:
```
http://10.143.149.22/warkop/kasir/printer_settings.php
```

### Features:
- [x] Test connection button
- [x] Save settings
- [x] Printer type selection
- [x] IP & port configuration
- [x] Connection feedback

---

## 📊 Comparison: Browser vs Network Print

| Feature | Browser Print | Network Print |
|---------|---------------|---------------|
| Setup | None | IP + Port |
| Printer Dialog | Yes | No |
| Speed | Medium | Fast |
| Compatibility | Any printer | ESC/POS only |
| Automation | Manual | Can automate |
| Reliability | High | Very High |
| Cost | Free | Printer needed |

**Recommendation:**
- **Start with:** Browser Print (simple, works everywhere)
- **Production:** Network Print (fast, automatic)

---

## 🎯 Common Printer Models & Ports

| Brand | Model | Port | Notes |
|-------|-------|------|-------|
| Epson | TM-T20II | 9100 | Common |
| Epson | TM-T88V | 9100 | Popular |
| Star | TSP100 | 9100 | Reliable |
| Bixolon | SPP-R200 | 9100 | Budget friendly |
| Sunmi | T2mini | 9100 | WiFi capable |

**Note:** Most thermal printers use port 9100 for ESC/POS

---

## 🔌 Wiring Diagram

### Ethernet Setup
```
[Thermal Printer]
       ↓ (RJ45 Cable)
   [Network Router]
   /             \
  ↙               ↙
[Server]      [Kasir iPad/PC]
(XAMPP)
```

### WiFi Setup
```
[Thermal Printer (WiFi)]
        ↓ (WiFi)
  [Network Router]
  /             \
↙               ↙
[Server]    [Kasir iPad/PC]
(XAMPP)
```

---

## 📝 Setup Checklist

- [ ] Thermal printer purchased/available
- [ ] Printer connected to network (WiFi or Ethernet)
- [ ] Printer IP address identified
- [ ] Port verified (usually 9100)
- [ ] Connectivity tested (ping/telnet)
- [ ] Kasir settings page configured
- [ ] Test print successful
- [ ] Production-ready

---

## 🚀 Going Live

**Before deploying to production:**

1. **Test thoroughly**
   - Print 50+ test receipts
   - Check paper alignment
   - Verify receipt contents

2. **Document setup**
   - Record printer IP
   - Save port number
   - Note any special settings

3. **Train staff**
   - Show how to configure
   - Explain troubleshooting
   - Keep manual handy

4. **Monitor**
   - Check daily connectivity
   - Monitor print quality
   - Track any issues

---

## 📞 Support

**Quick Fixes:**
1. Check printer is powered on
2. Verify IP address correct
3. Test network connectivity
4. Try browser print as fallback

**For issues:**
1. Check XAMPP error logs
2. Check printer error messages
3. Verify network connection
4. Restart printer and server

---

## Files Reference

**New Files Created:**
- `kasir/print_network.php` - ESC/POS generation & network printing
- `kasir/printer_settings.php` - Printer configuration page

**Configuration:**
- Printer IP: Session-based (printer_settings.php)
- Printer Port: Default 9100 (configurable)
- Printer Type: Browser or Network (configurable)

---

**Version:** 1.0  
**Status:** Production Ready ✅  
**Last Updated:** June 21, 2026

Ready to setup network printer! 🖨️
