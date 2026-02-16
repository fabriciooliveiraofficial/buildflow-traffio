/*
 * Buildflow Native Service Worker
 * Handles Push Notifications & Toast Routing
 */

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('push', (event) => {
    if (!(self.Notification && self.Notification.permission === 'granted')) {
        return;
    }

    const data = event.data ? event.data.json() : { title: 'New Update', body: 'You have a new work notification.' };

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            const focusedClient = clientList.find(c => c.focused);
            const anyClient = clientList.length > 0;

            // If app is focused/open, send a message to show a Toast instead of a system notification
            if (focusedClient || anyClient) {
                clientList.forEach(client => {
                    client.postMessage({
                        type: 'PUSH_TOAST',
                        title: data.title,
                        body: data.body
                    });
                });

                // We still show background notification if all clients are backgrounded
                if (focusedClient) return;
            }

            // Fallback to system notification if app is closed or backgrounded
            return self.registration.showNotification(data.title, {
                body: data.body,
                icon: '/assets/img/icon.png',
                badge: '/assets/img/icon.png',
                data: data.url || '/portal/'
            });
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    event.waitUntil(
        self.clients.openWindow(event.notification.data)
    );
});
