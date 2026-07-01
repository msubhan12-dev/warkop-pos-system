# QRIS Payment - Quick Start Guide

## 🎯 In 2 Minutes

### What Changed?
Customer can now pay with QRIS instead of cash, with proof-of-payment verification.

---

## 🧪 Test It Now

### Step 1: Customer Orders with QRIS
```
1. Open: http://10.143.149.22/warkop/customer/menu.php
2. Select items → Click "Checkout"
3. Fill customer name & phone
4. Select "QRIS" payment method
5. Click "Konfirmasi Pesanan"
```

### Step 2: Customer Uploads Proof
```
1. You'll see QRIS payment page
2. See QR Code with total amount
3. Select any image file (jpg/png)
4. Click "Kirim Bukti Pembayaran"
5. Wait for admin verification
```

### Step 3: Admin Verifies
```
1. Login as admin: http://10.143.149.22/warkop
   - Username: admin
   - Password: password
2. Go to "Pesanan" menu
3. Find order with orange badge "Menunggu verifikasi..."
4. Click to view details
5. See payment proof image
6. Click "Terima Pembayaran" or "Tolak Pembayaran"
```

### Step 4: Kitchen Receives Order
```
1. If approved → Order goes to kitchen
2. Open kitchen display: http://10.143.149.22/warkop/admin/kitchen.php
3. New order appears automatically
```

---

## 📋 New Features

### For Customers
- ✅ QR Code display with payment amount
- ✅ Drag & drop proof upload
- ✅ Image preview before submit
- ✅ Payment status tracking

### For Admin
- ✅ Pending QRIS orders highlighted (orange badge)
- ✅ View payment proof image
- ✅ Approve or reject payment
- ✅ Add rejection reason if needed
- ✅ Full verification history

### New Pages
- `customer/payment_qris.php` - QRIS payment & upload
- `admin/verify_payment.php` - Backend verification

---

## 💾 Database

**New columns in `payments` table:**
- `proof_of_payment` - Image path
- `verification_status` - pending/verified/rejected
- `verified_by` - Admin ID who verified
- `verified_at` - When verified
- `verification_notes` - Rejection reason

---

## ⚠️ Important Notes

### Order Status Flow for QRIS
```
APPROVED:
pending (awaiting verification) → confirmed → cooking → ready → served → completed

REJECTED:
pending → cancelled (table becomes available)
```

### Cash Payments (Unchanged)
```
pending → confirmed → cooking → ready → served → completed
(No verification, goes to kitchen immediately)
```

---

## 🐛 Common Issues

| Issue | Solution |
|-------|----------|
| "File bukan gambar" | Use JPG or PNG, check file size < 5MB |
| Upload fails | Check `/uploads/payment_proofs/` exists |
| Admin doesn't see order | Make sure proof was uploaded |
| Kitchen doesn't get notification | Order must be approved first |
| "Payment not found" | Order must have QRIS payment method |

---

## 📞 Need Help?

1. **Full Documentation:** Read `QRIS_PAYMENT_GUIDE.md`
2. **Implementation Details:** See `IMPLEMENTATION_SUMMARY.md`
3. **Database Schema:** Check `database/warkop.sql`
4. **Functions:** Look in `includes/functions.php`

---

## 🚀 What's Next?

### To Integrate Real QRIS Payment:
1. Sign up with Midtrans or Xendit
2. Get API credentials
3. Replace QR generation in `payment_qris.php`
4. Add webhook for auto-verification

### To Customize:
1. Edit colors in `customer/payment_qris.php` (currently slate/grey)
2. Change QRIS info in payment page
3. Customize verification UI in `admin/orders.php`

---

## ✅ Tested Features

- [x] Customer QRIS checkout
- [x] QR code display
- [x] File upload with validation
- [x] Admin order view
- [x] Payment approval
- [x] Payment rejection
- [x] Kitchen notification (after approval)
- [x] Mobile responsive
- [x] Order status tracking
- [x] Audit logging

---

## 📊 Test Data

**Login Credentials:**
```
Admin:
- Username: admin
- Password: password

You can test both QRIS and cash payments
```

---

## 🎨 Theme

Colors changed from purple to slate (professional grey):
- Primary: #475569 (slate-700)
- Secondary: #64748B (slate-500)
- Accent: Green for approve, Red for reject

---

**That's it! You're ready to test QRIS payments.** 🎉

Go to http://10.143.149.22/warkop and try it out!

---

**Version:** 1.0  
**Last Updated:** June 21, 2026
