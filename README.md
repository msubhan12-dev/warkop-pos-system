# WARKOP OS - Low Budget / Full Free Starter Plan

## 🎯 Deskripsi
Sistem manajemen warkop digital yang **FULL PHP NATIVE + MySQL** dengan UI **mobile responsive 1000000%** untuk tablet, smartphone, dan iOS. Dashboard admin realtime dengan Tailwind CSS yang clean dan modern.

## ✨ Fitur Utama
- 🔐 **Multi-Role System**: Owner, Kasir, Dapur, Pelayan, Customer
- 📱 **QR Menu Customer**: Scan QR meja → Pilih menu → Checkout
- 💰 **POS Kasir**: Input pesanan cepat, cetak struk, manajemen pembayaran
- 🔥 **Kitchen Display**: **REALTIME** order untuk dapur dengan SSE (<1 detik!)
- 🔔 **Real-time Notification**: SSE + Sound + Browser notification
- 📊 **Dashboard Owner**: Analytics realtime, omset, menu terlaris, jam ramai
- 💳 **Payment**: QRIS Ready, Cash, Transfer, Card
- 🔔 **Notifikasi**: Realtime notification system
- 📝 **Audit Logs**: Track semua aktivitas sistem

## 🛠️ Tech Stack
- **Backend**: PHP 7.4+ (Native, OOP Style)
- **Database**: MySQL 5.7+
- **Frontend**: HTML5 + Tailwind CSS (CDN)
- **JavaScript**: Vanilla JS + AJAX
- **Charts**: Chart.js
- **Icons**: Font Awesome 6
- **Server**: Apache (XAMPP)

## 📋 Requirement
- PHP >= 7.4
- MySQL >= 5.7
- Apache/Nginx
- Browser modern (Chrome, Safari, Firefox, Edge)

## 🚀 Instalasi

### 1. Clone/Copy Project
```bash
# Copy folder warkop ke htdocs XAMPP
cp -r warkop /Applications/XAMPP/xamppfiles/htdocs/
```

### 2. Setup Database
```bash
# Buka phpMyAdmin atau MySQL CLI
# Import file database
mysql -u root -p < database/warkop.sql

# Atau via phpMyAdmin:
# 1. Buka http://localhost/phpmyadmin
# 2. Create database 'warkop_db'
# 3. Import file database/warkop.sql
```

### 3. Konfigurasi Database
Edit file `config/database.php` jika perlu:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'warkop_db');
```

### 4. Setup Permissions (Mac/Linux)
```bash
chmod -R 755 /Applications/XAMPP/xamppfiles/htdocs/warkop
chmod -R 777 /Applications/XAMPP/xamppfiles/htdocs/warkop/uploads
```

### 5. Akses Aplikasi
Buka browser dan akses:
```
http://localhost/warkop
```

## 👥 Default Login Credentials

### Owner/Admin
- Username: `admin`
- Password: `password`

### Kasir
- Username: `kasir1`
- Password: `password`

### Dapur
- Username: `dapur1`
- Password: `password`

### Pelayan
- Username: `pelayan1`
- Password: `password`

## 📱 Halaman Utama

### 1. Customer (QR Menu)
- **URL**: `http://localhost/warkop/customer/menu.php`
- **URL dengan Meja**: `http://localhost/warkop/customer/menu.php?table=A1`
- **Fitur**:
  - Browse menu by category
  - Search menu
  - Add to cart
  - Checkout dengan QRIS/Cash
  - Order tracking

### 2. Kasir (POS System)
- **URL**: `http://localhost/warkop/kasir/`
- **Role**: kasir, pelayan, owner
- **Fitur**:
  - Quick order entry
  - Table selection
  - Real-time price calculation
  - Active orders monitoring
  - Print receipt

### 3. Dapur (Kitchen Display)
- **URL**: `http://localhost/warkop/dapur/`
- **Role**: dapur, owner
- **Fitur**:
  - **Real-time SSE notifications** (<1 second!)
  - Sound alert untuk order baru
  - Browser desktop notification
  - New orders notification
  - Start cooking tracking
  - Mark order ready
  - Cooking time tracking
  - Auto-reconnect on connection loss

### 4. Owner (Dashboard)
- **URL**: `http://localhost/warkop/admin/`
- **Role**: owner
- **Fitur**:
  - Daily statistics
  - Sales charts (hourly)
  - Top selling menus
  - Payment methods distribution
  - Recent orders monitoring
  - Auto refresh every 30s

## 🗂️ Struktur Database

### Tables
1. **users** - User management & roles
2. **tables** - Table management & QR codes
3. **categories** - Menu categories
4. **menus** - Menu items with pricing
5. **orders** - Customer orders
6. **order_items** - Order details
7. **payments** - Payment transactions
8. **kitchen_tickets** - Kitchen order tickets
9. **shifts** - Kasir shift management
10. **notifications** - System notifications
11. **audit_logs** - Activity logging

### Views (untuk Analytics)
- `v_daily_sales` - Daily sales summary
- `v_top_menus` - Top selling menus
- `v_active_orders` - Current active orders

## 🎨 UI/UX Features

### Mobile Responsive
- ✅ **1000000% Responsive** untuk semua device
- ✅ Touch-friendly buttons (min 44x44px)
- ✅ Smooth scrolling & transitions
- ✅ Bottom navigation untuk mobile
- ✅ Sticky headers
- ✅ Optimized untuk iOS & Android
- ✅ PWA-ready (meta tags sudah ada)

### Design System
- **Color Scheme**: Purple/Pink gradient primary, Green untuk kasir, Orange untuk dapur
- **Font**: System font stack (SF Pro, Segoe UI, Roboto)
- **Components**: Cards, badges, buttons, modals
- **Icons**: Font Awesome 6
- **Animations**: Smooth transitions, pulse effects

## 🔧 Kustomisasi

### Menambah Menu Baru
1. Login sebagai Owner
2. Buka Menu Management
3. Klik "Tambah Menu"
4. Isi detail menu & upload gambar
5. Save

### Generate QR Code Meja
```php
// Gunakan library QRCode.js atau PHP QR Code
// URL format: customer/menu.php?table=A1
```

### Ubah Tax Rate
Edit file `config/config.php`:
```php
define('TAX_RATE', 0.10); // 10% PPN
```

## 📊 Analytics & Reports

Dashboard Owner menyediakan:
- Total orders hari ini
- Pendapatan harian
- Active orders count
- Available tables
- Hourly sales chart
- Payment method distribution
- Top 5 menus hari ini
- Recent orders list

## 🔒 Security Features
- Password hashing (bcrypt)
- SQL injection prevention (PDO prepared statements)
- XSS protection (htmlspecialchars)
- Session management dengan timeout
- Role-based access control (RBAC)
- Audit logging semua aktivitas

## 🐛 Troubleshooting

### Database Connection Error
```
Error: Connection failed
Solution: Cek config/database.php, pastikan MySQL running
```

### Upload Image Gagal
```
Error: Failed to upload file
Solution: chmod 777 uploads/ folder
```

### Session Timeout
```
Error: Session expired
Solution: Ubah SESSION_TIMEOUT di config/config.php
```

## 📈 Roadmap

### Phase 1 (DONE ✅)
- QR Menu Customer
- POS Kasir System
- Kitchen Display
- Dashboard Owner Analytics

### Phase 2 (Future)
- QRIS Integration (Midtrans/Xendit)
- Print Struk (Thermal Printer)
- SMS/WhatsApp Notification
- Inventory Management

### Phase 3 (Future)
- Delivery Internal System
- Customer Loyalty Program
- Menu Recommendations (AI)

### Phase 4 (Future)
- Multi Cabang Support
- Franchise Management
- Mobile Apps (Flutter)

## 💡 Tips

### Performance
- Enable PHP OPcache di production
- Gunakan MySQL query caching
- Compress images sebelum upload
- Minify CSS/JS di production

### Production Deployment
```php
// Ubah di config/config.php
error_reporting(0);
ini_set('display_errors', 0);

// Enable HTTPS
define('APP_URL', 'https://yourdomain.com/warkop');
```

## 📞 Support & Contact

Jika ada pertanyaan atau butuh bantuan:
- **GitHub Issues**: [Create Issue]
- **Email**: support@warkop.local
- **Docs**: Baca file README.md ini

## 📄 License

MIT License - Free untuk digunakan dan dimodifikasi

## 🙏 Credits

Dibuat dengan ❤️ untuk digitalisasi UMKM Indonesia
- PHP Native + MySQL
- Tailwind CSS
- Font Awesome
- Chart.js

---

**WARKOP OS v1.0.0** - Low Budget / Full Free Starter Plan
© <?= date('Y') ?> - All Rights Reserved
