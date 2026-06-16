# Server-Sent Events (SSE) - Real-time Notifications

## 🚀 Implemented!

WARKOP OS sekarang menggunakan **SSE (Server-Sent Events)** untuk notifikasi real-time di Kitchen Display.

## ⚡ How It Works

### Architecture:
```
CUSTOMER/KASIR               DATABASE              KITCHEN DISPLAY
    Buat Order      →     INSERT order      ←  SSE Stream (open)
                           ↓
                         Detect new
                           ↓
                         PUSH event    →    🔔 BUNYI! (<1 detik)
```

### Technical Flow:
1. **Kitchen Display buka halaman** → Initialize SSE connection
2. **SSE stream tetap open** → Server loop check database setiap 1 detik
3. **Ada order baru** → Server PUSH event ke client
4. **Client terima event** → Play sound + Show notification + Reload page

## 📊 Performance Comparison

| Metric | Old (Polling) | New (SSE) |
|--------|--------------|-----------|
| **Latency** | 0-5 seconds | <1 second |
| **Requests/hour** | 720 | 1 |
| **Bandwidth** | High | Low |
| **Server Load** | 720 queries | ~3600 queries* |
| **Connection** | Close-open | Keep-alive |

*Meskipun query lebih banyak, lebih efficient karena 1 connection

## 🎯 Events

### Available Events:

1. **`connected`** - Initial connection established
   ```json
   {
     "type": "connected",
     "message": "Stream connected",
     "new_count": 3,
     "cooking_count": 2,
     "timestamp": "2026-06-16 14:30:00"
   }
   ```

2. **`new_order`** - NEW ORDER ARRIVED! 🔔
   ```json
   {
     "type": "new_order",
     "count": 1,
     "total_new": 4,
     "orders": [
       {
         "id": 123,
         "ticket_number": "TKT20260616ABCD",
         "menu_name": "Nasi Goreng",
         "quantity": 2,
         "table_number": "A1",
         "customer_name": "John Doe"
       }
     ],
     "timestamp": "2026-06-16 14:30:15"
   }
   ```

3. **`order_started`** - Order mulai dimasak
   ```json
   {
     "type": "order_started",
     "total_new": 3,
     "total_cooking": 3,
     "timestamp": "2026-06-16 14:31:00"
   }
   ```

4. **`order_ready`** - Order siap disajikan
   ```json
   {
     "type": "order_ready",
     "total_cooking": 2,
     "timestamp": "2026-06-16 14:35:00"
   }
   ```

5. **`ping`** - Keep-alive (every 30s)
   ```json
   {
     "type": "ping",
     "timestamp": "2026-06-16 14:30:30"
   }
   ```

## 🔧 Configuration

### PHP Settings (Auto-configured in stream):
```php
set_time_limit(0);                    // No timeout
ini_set('max_execution_time', 0);     // Unlimited execution
header('X-Accel-Buffering: no');      // Disable nginx buffering
```

### XAMPP/Apache Settings:
Default XAMPP settings sudah oke untuk SSE. Jika masih timeout:

Edit `php.ini`:
```ini
max_execution_time = 0
max_input_time = -1
```

Edit `httpd.conf` (Apache):
```apache
Timeout 300
KeepAlive On
KeepAliveTimeout 300
```

## 🎵 Sound Notification

### Method 1: Audio File (Recommended)
Upload sound ke `assets/sounds/`:
- `notification.mp3`
- `notification.ogg`

Free sound sources:
- https://notificationsounds.com/
- https://freesound.org/
- https://mixkit.co/free-sound-effects/notification/

### Method 2: Generated Tone (Fallback)
Jika file audio tidak ada, sistem pakai **Web Audio API** untuk generate tone "ding-ding".

File: `assets/js/notification-sound.js`

## 🔔 Browser Notification

Kitchen Display akan request permission untuk browser notification.

User harus klik **"Allow"** untuk enable:
- Desktop notification
- Sound notification
- Vibration (mobile)

## 🐛 Troubleshooting

### Connection terus disconnect?
- Cek PHP max_execution_time
- Cek Apache timeout settings
- Cek browser console untuk error

### Sound tidak bunyi?
- Browser butuh user interaction dulu (klik halaman)
- Cek file sound ada di `assets/sounds/`
- Fallback akan pakai generated tone

### Notification tidak muncul?
- Klik "Allow" saat browser minta permission
- Cek browser notification settings
- Cek Do Not Disturb mode (Mac/iOS)

### SSE stream error 500?
- Cek PHP error log
- Pastikan database connection oke
- Cek session valid

## 📱 Browser Support

| Browser | SSE Support | Sound | Notification |
|---------|-------------|-------|--------------|
| Chrome 90+ | ✅ | ✅ | ✅ |
| Firefox 88+ | ✅ | ✅ | ✅ |
| Safari 14+ | ✅ | ✅ | ✅ |
| Edge 90+ | ✅ | ✅ | ✅ |
| iOS Safari | ✅ | ⚠️* | ⚠️* |

*iOS requires user interaction first

## 🔒 Security

- ✅ Role-based access (requireRole)
- ✅ Session validation
- ✅ Database prepared statements
- ✅ Error handling & logging
- ✅ Auto-reconnect on failure

## 📈 Monitoring

Check browser console untuk logs:
```
🔌 Connecting to SSE stream...
✅ SSE Connected
✅ Stream connected: {type: "connected", ...}
💓 Keep-alive ping
🔥 NEW ORDER EVENT: {count: 1, orders: [...]}
```

## 🎯 Future Improvements

- [ ] WebSocket untuk two-way communication
- [ ] Sound customization per event
- [ ] Push notification via service worker
- [ ] Multiple kitchen display sync
- [ ] Order priority auto-escalation

## 📚 References

- MDN: https://developer.mozilla.org/en-US/docs/Web/API/Server-sent_events
- HTML5 SSE: https://html.spec.whatwg.org/multipage/server-sent-events.html
- Browser Notification API: https://developer.mozilla.org/en-US/docs/Web/API/Notifications_API

---

**Status:** ✅ **ACTIVE & WORKING**

Sekarang kitchen display dapat notifikasi real-time (<1 detik) setiap ada order baru! 🔥
