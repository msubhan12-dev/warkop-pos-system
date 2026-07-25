# QRIS Auto-Verification System - Real-Time Sync Flow

## Perubahan Sistem
Sistem pembayaran QRIS telah diupdate dari manual verification menjadi auto-verification dengan real-time sync antara customer dan admin.

---

## Flow Pembayaran QRIS (Customer → Admin)

### 1. **Customer Payment Page** (`customer/payment_qris.php`)
- Customer scan QRIS dan melakukan transfer
- Customer klik tombol **"Saya Sudah Bayar"**
- Loading overlay tampil selama **3 detik** (simulating process)
- Setelah 3 detik, sistem otomatis submit ke `confirm_qris.php`

### 2. **Auto-Verification** (`customer/confirm_qris.php`)
⚡ **NEW**: Pembayaran auto-verified langsung tanpa menunggu admin approval
- Order status: `pending` → `confirmed` ✓
- Payment status: `pending` → `verified` ✓
- Notification dikirim ke dapur/kasir/owner
- **Tidak perlu admin klik tombol "Terima"**

### 3. **Real-Time Polling** (Customer Side)
- JavaScript polling setiap **1 detik** cek status payment
- Jika payment verified → auto reload ke order success page
- Max polling: 5 menit (300 detik)

### 4. **Admin Dashboard Updates** (`admin/orders.php`)
- Admin dashboard polling setiap **5 detik** cek pending payments
- Saat ada pembayaran baru → auto reload page untuk show updated orders
- Order dengan QRIS auto-verified ditandai: **"Auto-Verified"** badge (hijau)

---

## File-File yang Berubah

### `/customer/confirm_qris.php` ✅
**Perubahan**: Auto-verify payment tanpa admin intervensi
```
OLD: Update payment status ke 'pending' → tunggu admin
NEW: Update payment status langsung ke 'verified' + order ke 'confirmed'
```

### `/customer/payment_qris.php` ✅
**Perubahan**: Polling lebih agresif (1 detik bukan 3 detik)
```
OLD: setInterval(checkPaymentStatus, 3000)
NEW: Recursive polling setiap 1 detik, max 5 menit
```

### `/admin/orders.php` ✅
**Perubahan**: 
- Hapus logic "needsVerification" 
- Tambah logic "autoVerifiedQris"
- Tambah badge "Auto-Verified" (hijau)
- Tambah polling dashboard (setiap 5 detik)

### `/admin/api_pending_payments.php` ✨ **NEW**
**File baru**: Real-time API untuk check pending QRIS payments
```json
GET /admin/api_pending_payments.php
Response: {
  "success": true,
  "count": 2,
  "payments": [...]
}
```

---

## User Experience

### Customer Experience
1. Scan QRIS
2. Bayar di e-wallet (Gopay, OVO, Dana, etc)
3. Klik **"Saya Sudah Bayar"**
4. Loading 3 detik → ✓ Success page
5. **Pesanan langsung masuk ke dapur** (tidak perlu tunggu admin)

### Admin/Kasir Experience
1. Dashboard polling otomatis setiap 5 detik
2. Pesanan QRIS langsung tampil dengan status **"Confirmed"**
3. Badge **"Auto-Verified"** (hijau) menunjukkan pembayaran sudah verified
4. **Tidak perlu manual approve/reject QRIS lagi** (auto-approve)

---

## Database Changes
**No schema changes** - semua menggunakan field yang sudah ada:
- `payments.verification_status` ← dari 'pending' langsung ke 'verified'
- `payments.verified_at` ← set ke NOW()
- `orders.status` ← dari 'pending' langsung ke 'confirmed'

---

## Technical Details

### Payment States
```
BEFORE (Manual):
pending (customer) → waiting admin approval → verified (admin approve)

AFTER (Auto):
pending (customer) → verified (auto on confirm) → confirmed (order to dapur)
```

### Polling Strategy
| Who | Where | Interval | Purpose |
|-----|-------|----------|---------|
| Customer | payment_qris.php | 1 sec | Check if payment verified |
| Admin | orders.php | 5 sec | Check new auto-verified QRIS |

### Notification Flow
```
confirm_qris.php auto-verify
    ↓
broadcastNotification() to ['kasir', 'dapur', 'owner']
    ↓
Message: "✓ Pesanan #XXX dari Customer telah DIKONFIRMASI OTOMATIS"
    ↓
Admin dashboard auto-reload dalam 5 detik
```

---

## Rollback Plan (if needed)
Jika ingin kembali ke manual verification:
1. Edit `customer/confirm_qris.php`: set `verification_status = 'pending'` (bukan 'verified')
2. Re-enable `verify_payment.php` approval buttons di modal detail pesanan
3. Ubah orders.php: kembalikan logic `$needsVerification = true`

---

## Testing Checklist
- ✅ Customer klik "Saya Sudah Bayar" → loading 3 detik
- ✅ Payment auto-verified di database
- ✅ Admin dashboard auto-reload dalam 5 detik
- ✅ Order status langsung "Confirmed"
- ✅ Notification terkirim ke dapur/kasir
- ✅ Customer polling detect verified → redirect ke success page

---

## Future Enhancements (Optional)
- WebSocket untuk real-time (lebih baik dari polling)
- Sound notification untuk admin saat ada order baru
- Toast notification di admin dashboard
- Automatic table release setelah order selesai
