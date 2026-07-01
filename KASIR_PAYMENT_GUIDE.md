# Kasir Payment Management - Complete Guide

## 📋 Overview

Kasir sekarang bisa:
- ✅ Verifikasi pembayaran QRIS/Transfer dari customer
- ✅ Lihat bukti pembayaran dari pelanggan
- ✅ Approve atau reject pembayaran
- ✅ Cetak struk untuk pembayaran tunai
- ✅ Track status pembayaran

---

## 🎯 Workflow

### Kasir Payment Verification Flow

```
Customer Upload Proof
        ↓
Kasir Cek Bukti di "Verifikasi Pembayaran"
        ↓
Kasir klik "Terima" atau "Tolak"
        ↓
TERIMA:
  - Pembayaran diverifikasi
  - Order ke kitchen
  - Dapur terima notifikasi

TOLAK:
  - Order dibatalkan
  - Meja jadi available
  - Alasan penolakan disimpan
```

### Cash Payment Processing

```
Order Selesai di Kitchen
        ↓
Kasir lihat di tab "Tunai"
        ↓
Kasir klik "Cetak Struk"
        ↓
Print ke Thermal Printer
        ↓
Kasir klik "Selesai"
        ↓
Payment marked as completed
```

---

## 📱 Kasir Payment Page

### Access
```
http://10.143.149.22/warkop/kasir/payments.php
```

### Two Tabs

#### Tab 1: QRIS & Transfer (Menunggu Verifikasi)
- Lihat order yang customer sudah upload bukti
- Show proof image
- Show customer info & total
- Button: Terima / Tolak

**Data Ditampilkan:**
- No. Pesanan
- Nama Customer
- Meja / Tipe Order
- Waktu Order
- Metode: QRIS atau Transfer
- Total Pembayaran
- Bukti Pembayaran (image)

#### Tab 2: Tunai (Siap Cetak Struk)
- Lihat order yang status "completed"
- Button: Cetak Struk + Selesai

**Data Ditampilkan:**
- No. Pesanan
- Nama Customer
- Meja / Tipe Order
- Metode: Tunai
- Total Pembayaran
- Status: Siap Cetak Struk

---

## 🔧 How to Use

### Step 1: Kasir Login
```
Username: kasir1
Password: password
```

### Step 2: Go to Payment Verification
- From POS page → Click credit card icon (top right)
- Or direct: kasir/payments.php

### Step 3: Verify QRIS/Transfer Payment

**If Approve:**
1. Click "Terima" button
2. Confirm dialog
3. Payment verified ✅
4. Order sent to kitchen
5. Page auto-refresh

**If Reject:**
1. Click "Tolak" button
2. Enter reason
3. Click confirm
4. Order cancelled ✅
5. Payment marked rejected
6. Table becomes available

### Step 4: Process Cash Payment

**When order completed:**
1. Go to "Tunai" tab
2. Find order
3. Click "Cetak Struk"
4. Printer dialog opens
5. Select thermal printer (or PDF)
6. Click "Selesai" after printing
7. Payment marked as completed

---

## 🖨️ Receipt Printer Setup

### Thermal Printer Configuration

The receipt format is optimized for 80mm thermal printers:

```
Layout:
┌─────────────────────────┐
│      WARKOP OS          │
│  Sistem Kasir Terpadu   │
├─────────────────────────┤
│ No. Pesanan: ORD202...  │
│ Waktu: 21/06/2026 14:30 │
├─────────────────────────┤
│ Customer: John Doe      │
│                         │
│ Total:   Rp 125.000     │
│ Metode:  TUNAI          │
├─────────────────────────┤
│  Terima Kasih!          │
│ Selamat Menikmati       │
│                         │
│ Dicetak: 14:30:45       │
└─────────────────────────┘
```

### Browser Print Settings

1. **Select Printer:** Choose thermal printer
2. **Paper Size:** 80x150mm (or 80x200mm)
3. **Margins:** 0 (none)
4. **Destination:** Send to printer
5. **More Settings:**
   - Background graphics: OFF
   - Headers/Footers: OFF
   - Scale: 100%

### First Time Setup

**macOS:**
1. System Preferences → Printers & Scanners
2. Add thermal printer (+)
3. Select printer model
4. Browser will auto-detect

**Windows:**
1. Control Panel → Devices & Printers
2. Add printer (+)
3. Select thermal printer
4. Browser will auto-detect

**Linux:**
1. CUPS: localhost:631
2. Add printer
3. Browser will auto-detect

---

## 📊 Payment Status Tracking

### Payment States

```
QRIS/Transfer Payment:
pending (awaiting upload)
  ↓
pending (proof uploaded)
  ├─ Kasir approve → verified ✅ (order to kitchen)
  └─ Kasir reject → rejected ❌ (order cancelled)

Cash Payment:
pending (order in progress)
  ↓
pending (order completed)
  ├─ Kasir print & complete → success ✅
  └─ (stays pending if not completed)
```

### Tracking Where to Look

**From POS Page:**
- Active orders: Click list icon (top)

**From Payment Page:**
- Tab "QRIS & Transfer": Pending verifications
- Tab "Tunai": Ready to print receipts

**From Admin Orders:**
- All payment history
- Verification status
- Who verified & when

---

## 🔐 Security & Authorization

### Who Can Access
- ✅ Kasir role
- ✅ Owner/Admin role
- ❌ Others (Dapur, Pelayan)

### Actions Tracked
- Approve QRIS payment (logged)
- Reject QRIS payment + reason (logged)
- Complete cash payment (logged)
- Print receipt (logged)

### Audit Trail
All actions recorded in:
```sql
SELECT * FROM audit_logs 
WHERE action IN ('complete_payment', 'verify')
ORDER BY created_at DESC;
```

---

## 🐛 Troubleshooting

### Issue: "No payments to verify"
**Cause:** No QRIS/Transfer orders with uploaded proof
**Solution:**
- Customer must select QRIS/Transfer method
- Customer must upload proof
- Check if order was created successfully

### Issue: Receipt doesn't print
**Cause:** 
- Printer not installed
- Browser print dialog cancelled
- Printer not connected
**Solution:**
1. Check printer is installed & online
2. Try print to PDF first
3. Check printer drivers

### Issue: Payment status stuck "pending"
**Cause:** Payment method is cash but not completed
**Solution:**
1. Kasir must click "Selesai" after printing
2. Or manually update in admin panel

### Issue: "Access denied"
**Cause:** User role is not kasir/owner
**Solution:**
- Only kasir and owner can access
- Login with correct credentials

### Issue: Bukti pembayaran tidak muncul
**Cause:** 
- Image not uploaded
- File path incorrect
- Upload directory missing
**Solution:**
```bash
# Check directory
ls -la /Applications/XAMPP/xamppfiles/htdocs/warkop/uploads/payment_proofs/

# Check permissions
chmod 755 /Applications/XAMPP/xamppfiles/htdocs/warkop/uploads/payment_proofs/
```

---

## 📈 Best Practices

### For Kasir

1. **Check Bukti Carefully**
   - Verify amount matches order total
   - Check transaction status is "success"
   - Look for recipient account name

2. **Reject Policy**
   - Clear reason for rejection
   - Amount mismatch? → Reject
   - Recipient wrong? → Reject
   - Status "pending"? → Ask customer to verify first

3. **Printer Maintenance**
   - Keep printer online
   - Check paper supply regularly
   - Test print weekly
   - Clean thermal head monthly

4. **Daily Closing**
   - All QRIS payments verified
   - All tunai receipts printed
   - No pending payments left

### For Admin

1. **Monitor Payment Queue**
   - Check daily pending verifications
   - Review rejected payments
   - Track verification time

2. **Audit**
   - Review who approved/rejected what
   - Check for patterns of fraud
   - Reconcile with bank statements

3. **Performance**
   - Approval rate (should be >95%)
   - Average verification time (should be <5 min)
   - Customer satisfaction

---

## 🔗 Related Pages

### For Kasir
- POS System: `/kasir/index.php`
- Payment Verification: `/kasir/payments.php`
- Logout: `/admin/logout.php`

### For Admin/Owner
- Orders: `/admin/orders.php` (see all payments)
- Dashboard: `/admin/index.php` (payment stats)

### For Customer
- QR Menu: `/customer/menu.php`
- QRIS Payment: `/customer/payment_qris.php`

---

## 📝 File Reference

### New Files
- `kasir/payments.php` - Payment verification interface
- `kasir/process_payment.php` - Payment processing API

### Modified Files
- `kasir/index.php` - Added payment button
- `admin/verify_payment.php` - Already supports kasir auth

---

## 🎓 Training Checklist

### For New Kasir

- [ ] Login to POS system
- [ ] Access payments page
- [ ] View QRIS/Transfer pending payments
- [ ] Approve sample payment
- [ ] View receipt in Tab 2
- [ ] Test print receipt
- [ ] Complete payment
- [ ] Test reject payment
- [ ] Understand rejection reasons
- [ ] Know printer troubleshooting

---

## 📞 Support

**Error Messages Explanation:**

| Error | Meaning | Action |
|-------|---------|--------|
| "Payment not found" | Payment ID invalid | Refresh page |
| "Access denied" | Wrong role | Login as kasir |
| "Invalid action" | Unknown request | Report bug |
| "Printer not found" | No printer | Add printer to system |

---

## 🚀 Future Enhancements

Planned improvements:

- [ ] Receipt customization (logo, message)
- [ ] Batch print receipts
- [ ] Email receipt to customer
- [ ] SMS payment confirmation
- [ ] Drawer sensor (auto open on print)
- [ ] Daily cash reconciliation report
- [ ] Payment analytics dashboard

---

## Version Info

- **Feature Version:** 1.0
- **Implemented:** June 21, 2026
- **Status:** Production Ready ✅
- **Tested:** All functions verified

---

**Happy Selling!** 🎉

For technical issues, check browser console (F12) or XAMPP logs.
