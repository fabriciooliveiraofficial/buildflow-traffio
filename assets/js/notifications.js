/**
 * Notification System
 */
document.addEventListener('DOMContentLoaded', () => {
    console.log('Notifications script loaded');
    const notificationBtn = document.querySelector('button[title="Notifications"]');
    console.log('Notification button:', notificationBtn);

    if (!notificationBtn) {
        console.error('Notification button not found!');
        return;
    }

    // Find badge - try multiple selectors for compatibility
    let badge = notificationBtn.querySelector('.badge') ||
        notificationBtn.querySelector('.notification-badge');

    let notificationDropdown = null;

    // Create dropdown element
    function createDropdown() {
        const dropdown = document.createElement('div');
        dropdown.className = 'notification-dropdown';
        dropdown.innerHTML = `
            <div class="notification-header">
                <h3>Notifications</h3>
                <button class="mark-all-read">Mark all read</button>
            </div>
            <div class="notification-list">
                <div class="notification-empty">Loading...</div>
            </div>
        `;
        document.body.appendChild(dropdown);
        return dropdown;
    }

    // Toggle dropdown
    notificationBtn.addEventListener('click', (e) => {
        console.log('Notification button clicked');
        e.stopPropagation();
        if (!notificationDropdown) {
            notificationDropdown = createDropdown();
            loadNotifications();
        }

        const rect = notificationBtn.getBoundingClientRect();
        notificationDropdown.style.top = `${rect.bottom + 10}px`;
        notificationDropdown.style.right = `${window.innerWidth - rect.right}px`;
        notificationDropdown.classList.toggle('active');
    });

    // Close on click outside
    document.addEventListener('click', (e) => {
        if (notificationDropdown &&
            !notificationDropdown.contains(e.target) &&
            !notificationBtn.contains(e.target)) {
            notificationDropdown.classList.remove('active');
        }
    });

    // Mark all read
    document.addEventListener('click', async (e) => {
        if (e.target.classList.contains('mark-all-read')) {
            try {
                await ERP.api.post('/notifications/read-all');
                loadNotifications();
                updateBadge(0);
            } catch (error) {
                console.error('Failed to mark all read:', error);
            }
        }
    });

    // Mark single read, clear, and redirect
    document.addEventListener('click', async (e) => {
        const item = e.target.closest('.notification-item');
        if (item && !e.target.closest('.mark-all-read')) {
            const id = item.dataset.id;
            let link = item.dataset.link;

            // Mark as read and remove from UI
            try {
                await ERP.api.post(`/notifications/${id}/read`);
                item.remove(); // Remove from dropdown

                // Update badge count
                const remaining = notificationDropdown.querySelectorAll('.notification-item:not(.read)').length;
                updateBadge(remaining);

                // Show empty message if no notifications left
                const list = notificationDropdown.querySelector('.notification-list');
                if (list && list.querySelectorAll('.notification-item').length === 0) {
                    list.innerHTML = '<div class="notification-empty">No new notifications</div>';
                }
            } catch (error) {
                console.error('Failed to mark read:', error);
            }

            // Redirect to link
            if (link && link !== '#') {
                // Prepend tenant base path from current URL (e.g., /t/tenant-slug)
                const pathMatch = window.location.pathname.match(/^(\/t\/[^/]+)/);
                if (pathMatch) {
                    link = pathMatch[1] + link;
                }
                window.location.href = link;
            }
        }
    });

    // Load notifications
    async function loadNotifications() {
        try {
            const response = await ERP.api.get('/notifications');
            // Handle response structure - data is wrapped in 'data' property
            const data = response.data || response;
            const notifications = data.notifications || [];
            const unread_count = data.unread_count || 0;

            updateBadge(unread_count);

            // Only render if dropdown exists
            if (notificationDropdown) {
                renderNotifications(notifications);
            }
        } catch (error) {
            console.error('Failed to load notifications:', error);
        }
    }

    function renderNotifications(notifications) {
        if (!notificationDropdown) return;

        const list = notificationDropdown.querySelector('.notification-list');
        if (!list) return;

        if (!notifications || notifications.length === 0) {
            list.innerHTML = '<div class="notification-empty">No new notifications</div>';
            return;
        }

        list.innerHTML = notifications.map(n => `
            <div class="notification-item ${n.read_at ? 'read' : ''}" data-id="${n.id}" data-link="${n.link || '#'}">
                <div class="notification-content">
                    <div class="notification-title">${n.title}</div>
                    <div class="notification-message">${n.message}</div>
                    <div class="notification-time">${formatTime(n.created_at)}</div>
                </div>
                ${!n.read_at ? '<div class="notification-dot"></div>' : ''}
            </div>
        `).join('');
    }

    function updateBadge(count) {
        if (!badge) return; // Guard against missing badge element

        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }

    function formatTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000); // seconds

        if (diff < 60) return 'Just now';
        if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
        if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
        return date.toLocaleDateString();
    }

    // Initial check
    loadNotifications();

    // Expose loadNotifications globally for other scripts to refresh notifications
    window.loadNotifications = loadNotifications;

    // Poll every 60 seconds
    setInterval(loadNotifications, 60000);
});

// Add styles dynamically
const style = document.createElement('style');
style.textContent = `
    .notification-dropdown {
        position: fixed;
        width: 320px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 1000;
        display: none;
        max-height: 400px;
        display: none; /* explicitly hidden initially */
        flex-direction: column;
        border: 1px solid var(--border-color);
    }
    .notification-dropdown.active {
        display: flex;
    }
    .notification-header {
        padding: 12px 16px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .notification-header h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
    }
    .mark-all-read {
        background: none;
        border: none;
        color: var(--primary-color);
        font-size: 12px;
        cursor: pointer;
    }
    .notification-list {
        overflow-y: auto;
        max-height: 350px;
    }
    .notification-item {
        padding: 12px 16px;
        border-bottom: 1px solid var(--border-color);
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background 0.2s;
    }
    .notification-item:hover {
        background: var(--bg-hover);
    }
    .notification-item.read {
        opacity: 0.7;
    }
    .notification-title {
        font-weight: 500;
        font-size: 14px;
        margin-bottom: 4px;
    }
    .notification-message {
        font-size: 13px;
        color: var(--text-secondary);
        margin-bottom: 4px;
    }
    .notification-time {
        font-size: 11px;
        color: var(--text-tertiary);
    }
    .notification-dot {
        width: 8px;
        height: 8px;
        background: var(--primary-color);
        border-radius: 50%;
        flex-shrink: 0;
        margin-left: 8px;
    }
    .notification-empty {
        padding: 20px;
        text-align: center;
        color: var(--text-secondary);
        font-size: 14px;
    }
    .badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #ef4444;
        color: white;
        font-size: 10px;
        font-weight: bold;
        min-width: 16px;
        height: 16px;
        border-radius: 8px;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 0 4px;
    }
`;
document.head.appendChild(style);
