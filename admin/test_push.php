<?php
require_once '../config/config.php';
requireRole(['owner', 'kasir', 'dapur', 'pelayan']);

$db = getDB();
$user_id = $_SESSION['user_id'];
?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Push Notifications</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            padding: 20px;
            max-width: 600px;
            margin: 0 auto;
        }
        .box {
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 16px;
            margin: 12px 0;
        }
        button {
            background: #128C7E;
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        button:hover {
            background: #0d7164;
        }
        .status {
            padding: 12px;
            margin: 12px 0;
            border-radius: 6px;
            font-size: 14px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        code {
            background: #fff;
            padding: 4px 8px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <h1>Test Push Notifications</h1>
    
    <div class="box">
        <h3>Status Saat Ini</h3>
        <div id="status" class="status info">Memeriksa browser support...</div>
    </div>
    
    <div class="box">
        <h3>Langganan Push</h3>
        <button onclick="subscribeToPush()">Aktifkan Notifikasi Push</button>
        <button onclick="unsubscribeFromPush()" style="background: #dc3545;">Nonaktifkan Notifikasi Push</button>
        <div id="subStatus"></div>
    </div>
    
    <div class="box">
        <h3>Test Notifikasi</h3>
        <p>Klik tombol di bawah untuk mengirim notifikasi test:</p>
        <button onclick="sendTestNotification()">Kirim Notifikasi Test</button>
        <div id="testStatus"></div>
    </div>
    
    <div class="box">
        <h3>Info</h3>
        <ul>
            <li>Buka halaman ini di Chrome, Edge, atau Firefox untuk mendapatkan push notifications</li>
            <li>Safari iOS belum mendukung Web Push (gunakan Chrome pada Android)</li>
            <li>Klik "Aktifkan Notifikasi Push" dan izinkan permission</li>
            <li>Notifikasi akan muncul di status bar Android meski browser ditutup</li>
        </ul>
    </div>

<script>
const VAPID_PUBLIC_KEY = '<?= VAPID_PUBLIC_KEY ?>';

function statusMsg(type, msg) {
    const el = document.getElementById('status');
    el.className = 'status ' + type;
    el.textContent = msg;
}

function subStatusMsg(type, msg) {
    const el = document.getElementById('subStatus');
    el.className = 'status ' + type;
    el.textContent = msg;
}

function testStatusMsg(type, msg) {
    const el = document.getElementById('testStatus');
    el.className = 'status ' + type;
    el.textContent = msg;
}

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');
    
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    
    return outputArray;
}

async function subscribeToPush() {
    if (!('serviceWorker' in navigator)) {
        subStatusMsg('error', '❌ Browser tidak mendukung Service Worker');
        return;
    }
    
    if (!('PushManager' in window)) {
        subStatusMsg('error', '❌ Browser tidak mendukung Push Manager');
        return;
    }
    
    try {
        subStatusMsg('info', '⏳ Registering service worker...');
        
        const registration = await navigator.serviceWorker.register('/service-worker.js', {
            scope: '/'
        });
        console.log('Service Worker registered:', registration);
        
        // Wait for service worker to be ready
        await navigator.serviceWorker.ready;
        console.log('Service Worker ready');
        
        subStatusMsg('info', '⏳ Requesting notification permission...');
        
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            subStatusMsg('error', '❌ Notifikasi ditolak');
            return;
        }
        
        subStatusMsg('info', '⏳ Subscribing to push...');
        
        // Get fresh registration after permission granted
        const reg = await navigator.serviceWorker.ready;
        
        if (!reg.pushManager) {
            subStatusMsg('error', '❌ pushManager tidak tersedia');
            return;
        }
        
        const subscription = await reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
        });
        
        console.log('Push subscription:', subscription);
        
        // Send to server
        const data = {
            action: 'subscribe',
            endpoint: subscription.endpoint
        };
        
        if (subscription.getKey) {
            const authKey = subscription.getKey('auth');
            const p256dhKey = subscription.getKey('p256dh');
            
            if (authKey) {
                data.auth_key = btoa(String.fromCharCode.apply(null, new Uint8Array(authKey)));
            }
            if (p256dhKey) {
                data.p256dh_key = btoa(String.fromCharCode.apply(null, new Uint8Array(p256dhKey)));
            }
        }
        
        const res = await fetch('/admin/api_push_notification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams(data)
        });
        
        const result = await res.json();
        console.log('Server response:', result);
        
        if (result.success) {
            subStatusMsg('success', '✅ Push notification berhasil diaktifkan!');
        } else {
            subStatusMsg('error', '❌ Error: ' + result.message);
        }
    } catch (err) {
        console.error('Error:', err);
        subStatusMsg('error', '❌ Error: ' + err.message);
    }
}

async function unsubscribeFromPush() {
    try {
        subStatusMsg('info', '⏳ Unsubscribing...');
        
        if (!('serviceWorker' in navigator)) {
            subStatusMsg('error', '❌ Service Worker tidak support');
            return;
        }
        
        const registration = await navigator.serviceWorker.ready;
        
        if (!registration.pushManager) {
            subStatusMsg('error', '❌ Push Manager tidak tersedia');
            return;
        }
        
        const subscription = await registration.pushManager.getSubscription();
        
        if (!subscription) {
            subStatusMsg('info', 'ℹ️ Belum ada subscription aktif');
            return;
        }
        
        const data = {
            action: 'unsubscribe',
            endpoint: subscription.endpoint
        };
        
        await fetch('/admin/api_push_notification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams(data)
        });
        
        await subscription.unsubscribe();
        subStatusMsg('success', '✅ Push notification dinonaktifkan');
    } catch (err) {
        console.error('Error:', err);
        subStatusMsg('error', '❌ Error: ' + err.message);
    }
}

async function sendTestNotification() {
    try {
        testStatusMsg('info', '⏳ Mengirim notifikasi test...');
        
        const res = await fetch('/admin/api_push_notification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                action: 'send_message_notification',
                recipient_id: <?= $user_id ?>,
                sender_id: 1,
                message: 'Ini adalah pesan test dari push notification system 🎉',
                message_type: 'text'
            })
        });
        
        const result = await res.json();
        console.log('Push result:', result);
        
        if (result.success) {
            testStatusMsg('success', '✅ Notifikasi test dikirim! (sent: ' + result.sent + ')');
        } else {
            testStatusMsg('error', '❌ Error: ' + result.message);
        }
    } catch (err) {
        console.error('Error:', err);
        testStatusMsg('error', '❌ Error: ' + err.message);
    }
}

// Check browser support on load
window.addEventListener('load', () => {
    let support = [];
    if ('serviceWorker' in navigator) support.push('✅ Service Worker');
    if ('PushManager' in window) support.push('✅ Push Manager');
    if ('Notification' in window) support.push('✅ Notification API');
    
    if (support.length === 3) {
        statusMsg('success', '✅ Browser mendukung push notifications: ' + support.join(', '));
    } else {
        statusMsg('error', 'Browser support: ' + (support.length > 0 ? support.join(', ') : '❌ Tidak didukung'));
    }
});

// Auto-init if service worker ready
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.ready.then(reg => {
        console.log('Service worker is ready');
        
        if (!reg.pushManager) {
            console.log('Push manager not available');
            return;
        }
        
        reg.pushManager.getSubscription().then(sub => {
            if (sub) {
                subStatusMsg('success', '✅ Sudah subscribe ke push notifications');
            }
        }).catch(err => {
            console.error('Error checking subscription:', err);
        });
    }).catch(err => {
        console.error('Service worker ready error:', err);
    });
}
</script>
</body>
</html>
