# QRIS Payment Flow - Implementation Guide

## Overview
WARKOP OS now fully supports QRIS payment method with proof-of-payment verification system.

## Payment Flow

### 1. Customer Selects QRIS
- Customer goes to checkout page
- Selects **QRIS** as payment method
- Submits order

### 2. Order Created with "pending" Status
- Order status: **pending** (not sent to kitchen yet)
- Payment status: **pending** verification
- Admin gets notification: "Verifikasi Pembayaran QRIS"
- Customer redirected to QRIS payment page

### 3. QRIS Payment Page
URL: `http://localhost/warkop/customer/payment_qris.php?order=ORD20260621XXXX`

Features:
- **QR Code Display** - Scannable QRIS code with payment amount
- **Upload Proof** - Customer uploads screenshot of successful payment
- **Drag & Drop** - File upload with preview
- **Supported Formats** - JPG, PNG, WebP (max 5MB)

### 4. Admin Verification
URL: `http://localhost/warkop/admin/orders.php`

**Pending QRIS Orders:**
- Highlighted in orange with warning badge
- "Menunggu verifikasi pembayaran QRIS"

**Verification Steps:**
1. Click order to view details
2. See payment method indicator (QRIS)
3. View proof of payment image (clickable to enlarge)
4. Two options:
   - **Terima Pembayaran** - Order goes to kitchen
   - **Tolak Pembayaran** - Order cancelled, reason required

### 5. Order Confirmation
If **Approved**:
- Order status changed to **confirmed**
- Kitchen tickets created automatically
- Kitchen staff gets notification
- Order appears in kitchen display system

If **Rejected**:
- Order status changed to **cancelled**
- Table becomes available again
- Payment marked as rejected with reason

## Database Structure

### payments Table Changes
New columns added:
```sql
- proof_of_payment VARCHAR(255) - Path to uploaded image
- verification_status ENUM - pending/verified/rejected
- verified_by INT - Admin user ID
- verified_at TIMESTAMP - When verified
- verification_notes TEXT - Rejection reason
```

### Updated Status Flow
**For QRIS:**
```
pending (customer paying) 
→ pending (awaiting verification)
→ confirmed (if approved) 
→ cooking 
→ ready 
→ served 
→ completed
```

OR if rejected:
```
pending → cancelled
```

**For Cash:**
```
pending → confirmed → cooking → ready → served → completed
```

## File Structure

### New Files Created
1. `/customer/payment_qris.php` - QRIS payment page
2. `/admin/verify_payment.php` - Payment verification API endpoint

### Modified Files
1. `/customer/checkout.php` - Updated to redirect to QRIS page
2. `/admin/orders.php` - Added payment verification UI
3. `/includes/functions.php` - Added helper functions
4. `/database/warkop.sql` - Updated payments table schema

### New Directories
1. `/uploads/payment_proofs/` - Stores proof images

## Key Features

### 1. QR Code Generation
- Uses QR Server API (free, no dependencies)
- Displays QR code with order number and amount
- Production ready: Can be replaced with Midtrans/Xendit API

### 2. File Upload Security
- File type validation (image only)
- File size limit (5MB)
- Path sanitization
- Proper MIME type checking

### 3. Admin Dashboard
- Shows pending QRIS orders with visual indicators
- One-click approval/rejection
- Full proof image preview
- Rejection reason tracking
- Timestamp tracking for all verifications

### 4. Notifications
- Customer: Payment received notification
- Kitchen: Order notification only after verification
- Admin: Real-time alerts for pending verification

## Testing the Feature

### Test Case 1: QRIS Payment Approval
1. Login as customer → go to menu
2. Select items → Checkout
3. Choose QRIS payment
4. On payment page, select dummy image file
5. Upload proof
6. Login as admin → Orders
7. Find order with "Menunggu verifikasi"
8. Click to view details
9. Click "Terima Pembayaran"
10. Verify order status changes to "confirmed"
11. Check kitchen display (if open)

### Test Case 2: QRIS Payment Rejection
1. Repeat steps 1-9 above
2. Click "Tolak Pembayaran"
3. Enter rejection reason
4. Confirm
5. Verify:
   - Order status = "cancelled"
   - Payment marked as rejected
   - Table status = "available"

### Test Case 3: Cash Payment (unchanged)
1. Login as customer
2. Checkout with cash payment
3. Verify order goes to kitchen immediately
4. Admin can process normally

## API Endpoints

### verify_payment.php
**URL:** `/admin/verify_payment.php`  
**Method:** POST  
**Auth:** Owner role only

#### Approve Payment
```php
POST /admin/verify_payment.php
{
  action: 'approve',
  payment_id: 123
}

Response:
{
  success: true,
  message: 'Pembayaran terverifikasi! Pesanan dikirim ke dapur.',
  order_id: 45
}
```

#### Reject Payment
```php
POST /admin/verify_payment.php
{
  action: 'reject',
  payment_id: 123,
  reason: 'Jumlah tidak sesuai'
}

Response:
{
  success: true,
  message: 'Pembayaran ditolak. Pesanan dibatalkan.'
}
```

## Production Deployment

### To Integrate Real QRIS Provider
Replace dummy QR generation in `payment_qris.php`:

**Option 1: Midtrans (Recommended)**
```php
require_once 'vendor/autoload.php';
$midtrans = new MidtransSnapAPI();
$qrCode = $midtrans->generateQRIS($orderId, $amount);
```

**Option 2: Xendit**
```php
$xendit = new XenditClient();
$qr = $xendit->createQRCode($amount, 'DYNAMIC');
```

**Option 3: Self-hosted**
- Install phpqrcode library
- Generate QRIS string format yourself
- Render with library

### Monitoring & Analytics
View pending verifications:
```sql
SELECT p.*, o.order_number, o.customer_name
FROM payments p
JOIN orders o ON p.order_id = o.id
WHERE p.payment_method = 'qris' 
  AND p.verification_status = 'pending'
  AND p.proof_of_payment IS NOT NULL;
```

## Troubleshooting

### Issue: "File bukan gambar"
- Ensure file is actual image (JPG, PNG, WebP)
- Check file size < 5MB
- Verify MIME type is correct

### Issue: "Payment not found" 
- Verify order was created with QRIS method
- Check payment record exists in DB

### Issue: Proof doesn't upload
- Check `/uploads/payment_proofs/` directory exists and writable
- Verify file permissions (755)
- Check server upload limits

### Issue: Kitchen doesn't get notified
- Ensure verification_status updated to 'verified'
- Check kitchen_tickets table for entries
- Verify dapur role user exists

## Security Considerations

1. **File Upload:**
   - Only image files accepted
   - Files stored outside web root recommended
   - Filenames randomized

2. **Verification:**
   - Only owner role can verify
   - All actions logged to audit_logs
   - IP address and user agent tracked

3. **Payment Data:**
   - Amount stored in payments table
   - No sensitive data in logs
   - Transaction ID field for future use

## Future Enhancements

1. **Auto-verification via ML:**
   - Analyze payment screenshot automatically
   - Detect if amount matches
   - Flag suspicious entries

2. **Payment Gateway Integration:**
   - Real QRIS from Midtrans/Xendit
   - Webhook callback for auto-verification
   - Real-time payment status

3. **Analytics:**
   - QRIS vs Cash payment ratio
   - Verification time metrics
   - Rejection rate analysis

4. **Customer Reminders:**
   - Email when payment pending
   - SMS notification on approval
   - Timeout handling for abandoned payments

## Support

For issues or questions, check:
1. Database schema in `/database/warkop.sql`
2. Functions in `/includes/functions.php`
3. Error logs in browser console
4. PHP error log in XAMPP

---

**Version:** 1.0  
**Last Updated:** June 21, 2026  
**Status:** Production Ready
