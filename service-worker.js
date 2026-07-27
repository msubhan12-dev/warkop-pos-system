/**
 * Service Worker for Web Push Notifications
 * Handles push events for staff chat notifications
 */

self.addEventListener('install', (event) => {
    console.log('[ServiceWorker] Installing...');
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    console.log('[ServiceWorker] Activating...');
    event.waitUntil(clients.claim());
});

// Handle push notifications from server
self.addEventListener('push', (event) => {
    console.log('[ServiceWorker] Push notification received');
    
    if (!event.data) {
        console.log('[ServiceWorker] No data in push event');
        return;
    }
    
    try {
        const data = event.data.json();
        console.log('[ServiceWorker] Push data:', data);
        
        const options = {
            body: data.message || 'Pesan baru di chat',
            icon: '/assets/img/warkop_banner.png',
            badge: '/assets/img/warkop_banner.png',
            tag: 'staff-chat-notification',
            requireInteraction: false,
            vibrate: [200, 100, 200],
            data: {
                url: data.url || '/admin/staff_chat.php',
                sender_id: data.sender_id,
                recipient_id: data.recipient_id
            }
        };
        
        event.waitUntil(
            self.registration.showNotification(
                data.title || 'Chat Tim',
                options
            )
        );
    } catch (e) {
        console.error('[ServiceWorker] Error handling push:', e);
    }
});

// Handle message from client
self.addEventListener('message', (event) => {
    console.log('[ServiceWorker] Message received:', event.data);
    
    if (event.data && event.data.type === 'show_notification') {
        const data = event.data;
        const options = {
            body: data.message || 'Pesan baru di chat',
            icon: '/assets/img/warkop_banner.png',
            badge: '/assets/img/warkop_banner.png',
            tag: 'staff-chat-notification',
            requireInteraction: false,
            vibrate: [200, 100, 200],
            data: {
                url: data.url || '/admin/staff_chat.php',
                sender_id: data.sender_id,
                recipient_id: data.recipient_id
            }
        };
        
        self.registration.showNotification(
            data.title || 'Chat Tim',
            options
        );
    }
});

// Handle notification click - open the chat page
self.addEventListener('notificationclick', (event) => {
    console.log('[ServiceWorker] Notification clicked');
    event.notification.close();
    
    const data = event.notification.data;
    const url = data.url || '/admin/staff_chat.php';
    
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            // Check if there's already a window/tab with the target URL open
            for (let i = 0; i < windowClients.length; i++) {
                const client = windowClients[i];
                if (client.url === url && 'focus' in client) {
                    return client.focus();
                }
            }
            // If not, open a new window/tab
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});
