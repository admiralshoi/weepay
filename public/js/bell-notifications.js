/**
 * Bell Notifications - Infinity Scroll Implementation
 */

(function() {
    'use strict';

    // State
    let isOpen = false;
    let isLoading = false;
    let hasMore = true;
    let currentCursor = null;
    let notifications = [];

    // Elements
    let trigger, dropdown, list, badge, loading, empty, markAllBtn;

    /**
     * Initialize bell notifications
     */
    function init() {
        trigger = document.getElementById('bellNotificationsTrigger');
        dropdown = document.getElementById('bellNotificationsDropdown');
        list = document.getElementById('bellNotificationsList');
        badge = document.getElementById('bellBadge');
        loading = document.getElementById('bellLoading');
        empty = document.getElementById('bellEmpty');
        markAllBtn = document.getElementById('markAllReadBtn');

        if (!trigger || !dropdown) return;

        // Event listeners
        trigger.addEventListener('click', toggleDropdown);
        markAllBtn?.addEventListener('click', markAllAsRead);

        // Close on click outside
        document.addEventListener('click', function(e) {
            if (isOpen && !dropdown.contains(e.target) && !trigger.contains(e.target)) {
                closeDropdown();
            }
        });

        // Infinite scroll
        list.addEventListener('scroll', handleScroll);

        // Load unread count on page load
        loadUnreadCount();
    }

    /**
     * Toggle dropdown visibility
     */
    function toggleDropdown(e) {
        e.stopPropagation();
        if (isOpen) {
            closeDropdown();
        } else {
            openDropdown();
        }
    }

    /**
     * Open dropdown and load notifications
     */
    function openDropdown() {
        isOpen = true;
        dropdown.classList.add('show');

        // Reset state and load fresh
        notifications = [];
        currentCursor = null;
        hasMore = true;
        list.innerHTML = '';
        list.appendChild(loading);
        loading.style.display = 'flex';
        empty.style.display = 'none';

        loadNotifications();
    }

    /**
     * Close dropdown
     */
    function closeDropdown() {
        isOpen = false;
        dropdown.classList.remove('show');
    }

    /**
     * Load unread count
     */
    async function loadUnreadCount() {
        try {
            const result = await get(platformLinks.api.user.notificationsUnreadCount);
            if (result.status === 'success') {
                updateBadge(result.data.unread_count);
            }
        } catch (e) {
            console.error('Failed to load unread count:', e);
        }
    }

    /**
     * Load notifications with pagination
     */
    async function loadNotifications() {
        if (isLoading || !hasMore) return;

        isLoading = true;

        try {
            const params = { per_page: 15 };
            if (currentCursor) {
                params.cursor = currentCursor;
            }

            const result = await post(platformLinks.api.user.notificationsList, params);

            if (result.status === 'success') {
                const data = result.data;
                const items = data.data || [];

                // Update pagination state
                hasMore = data.pagination?.next || false;
                currentCursor = data.pagination?.after || null;

                // Update unread count from meta (only on first load)
                if (notifications.length === 0 && data.meta?.unread_count !== undefined) {
                    updateBadge(data.meta.unread_count);
                }

                // Append notifications
                items.forEach(notification => {
                    notifications.push(notification);
                    appendNotificationItem(notification);
                });

                // Show empty state if no notifications
                if (notifications.length === 0) {
                    empty.style.display = 'flex';
                }
            }
        } catch (e) {
            console.error('Failed to load notifications:', e);
        } finally {
            isLoading = false;
            loading.style.display = 'none';
        }
    }

    /**
     * Append a notification item to the list
     */
    function appendNotificationItem(notification) {
        const item = document.createElement('div');
        item.className = 'bell-notification-item' + (notification.is_read ? ' is-read' : '');
        item.dataset.uid = notification.uid;

        const iconClass = getIconClass(notification.type, notification.icon);
        const iconBgClass = getIconBgClass(notification.type);

        item.innerHTML = `
            <div class="bell-notification-icon ${iconBgClass}">
                <i class="${iconClass}"></i>
            </div>
            <div class="bell-notification-content">
                <p class="bell-notification-title">${escapeHtml(notification.title)}</p>
                <p class="bell-notification-text">${escapeHtml(notification.content)}</p>
                <span class="bell-notification-time">${notification.time_ago}</span>
            </div>
            ${!notification.is_read ? '<span class="bell-notification-dot"></span>' : ''}
        `;

        // Click handler
        item.addEventListener('click', function() {
            markAsRead(notification.uid, item);
            if (notification.link) {
                window.location.href = notification.link;
            }
        });

        list.appendChild(item);
    }

    /**
     * Get icon class based on notification type
     */
    function getIconClass(type, customIcon) {
        if (customIcon) return customIcon;

        switch (type) {
            case 'success': return 'mdi mdi-check-circle';
            case 'warning': return 'mdi mdi-alert';
            case 'error': return 'mdi mdi-alert-circle';
            default: return 'mdi mdi-bell';
        }
    }

    /**
     * Get icon background class based on notification type
     */
    function getIconBgClass(type) {
        switch (type) {
            case 'success': return 'bg-success';
            case 'warning': return 'bg-warning';
            case 'error': return 'bg-danger';
            default: return 'bg-info';
        }
    }

    /**
     * Update badge count
     */
    function updateBadge(count) {
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }

    /**
     * Mark single notification as read
     */
    async function markAsRead(uid, itemElement) {
        try {
            await post(platformLinks.api.user.notificationsMarkRead, { uid: uid });

            // Update UI
            itemElement.classList.add('is-read');
            const dot = itemElement.querySelector('.bell-notification-dot');
            if (dot) dot.remove();

            // Update badge
            const currentCount = parseInt(badge.textContent) || 0;
            if (currentCount > 0) {
                updateBadge(currentCount - 1);
            }
        } catch (e) {
            console.error('Failed to mark as read:', e);
        }
    }

    /**
     * Mark all notifications as read
     */
    async function markAllAsRead() {
        try {
            await post(platformLinks.api.user.notificationsMarkAllRead, {});

            // Update all items UI
            list.querySelectorAll('.bell-notification-item').forEach(item => {
                item.classList.add('is-read');
                const dot = item.querySelector('.bell-notification-dot');
                if (dot) dot.remove();
            });

            // Clear badge
            updateBadge(0);

            showSuccessNotification('Alle notifikationer markeret som læst');
        } catch (e) {
            console.error('Failed to mark all as read:', e);
        }
    }

    /**
     * Handle scroll for infinite loading
     */
    function handleScroll() {
        if (isLoading || !hasMore) return;

        const scrollTop = list.scrollTop;
        const scrollHeight = list.scrollHeight;
        const clientHeight = list.clientHeight;

        // Load more when near bottom (100px threshold)
        if (scrollTop + clientHeight >= scrollHeight - 100) {
            // Show loading indicator at bottom
            loading.style.display = 'flex';
            list.appendChild(loading);
            loadNotifications();
        }
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose for external access if needed
    window.BellNotifications = {
        refresh: loadUnreadCount,
        open: openDropdown,
        close: closeDropdown
    };

})();

/**
 * User Dropdown
 */
(function() {
    'use strict';

    function init() {
        const trigger = document.getElementById('userDropdownTrigger');
        const menu = document.getElementById('userDropdownMenu');

        if (!trigger || !menu) return;

        trigger.addEventListener('click', function(e) {
            e.stopPropagation();
            menu.classList.toggle('show');

            // Close bell notifications if open
            const bellDropdown = document.getElementById('bellNotificationsDropdown');
            if (bellDropdown) bellDropdown.classList.remove('show');
        });

        // Close when clicking outside
        document.addEventListener('click', function(e) {
            if (!menu.contains(e.target) && !trigger.contains(e.target)) {
                menu.classList.remove('show');
            }
        });

        // Close on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                menu.classList.remove('show');
            }
        });
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
