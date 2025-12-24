/**
 * HDH: Notification Bell JavaScript
 * Handles notification bell interactions, dropdown, and auto-refresh
 */

(function() {
    'use strict';
    
    // Configuration
    const config = {
        ajaxUrl: hdhNotifications?.ajaxUrl || '/wp-admin/admin-ajax.php',
        nonce: hdhNotifications?.nonce || '',
        pollInterval: 30000, // 30 seconds
    };
    
    // State
    let pollTimer = null;
    let isDropdownOpen = false;
    
    /**
     * Initialize notification bell
     */
    function init() {
        const bell = document.getElementById('hdh-notification-bell');
        const dropdown = document.getElementById('hdh-notification-dropdown');
        
        if (!bell || !dropdown) {
            return;
        }
        
        // Toggle dropdown
        bell.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleDropdown();
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (isDropdownOpen && !bell.contains(e.target) && !dropdown.contains(e.target)) {
                closeDropdown();
            }
        });
        
        // Mark all as read button
        const markAllReadBtn = document.getElementById('btn-mark-all-read');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', handleMarkAllRead);
        }
        
        // Load notifications on first open
        bell.addEventListener('click', function() {
            if (!isDropdownOpen) {
                loadNotifications();
            }
        });
        
        // Start polling for unread count
        startPolling();
    }
    
    /**
     * Toggle dropdown
     */
    function toggleDropdown() {
        const dropdown = document.getElementById('hdh-notification-dropdown');
        const bell = document.getElementById('hdh-notification-bell');
        
        if (!dropdown || !bell) {
            return;
        }
        
        isDropdownOpen = !isDropdownOpen;
        
        if (isDropdownOpen) {
            dropdown.style.display = 'flex';
            bell.setAttribute('aria-expanded', 'true');
            loadNotifications();
        } else {
            closeDropdown();
        }
    }
    
    /**
     * Close dropdown
     */
    function closeDropdown() {
        const dropdown = document.getElementById('hdh-notification-dropdown');
        const bell = document.getElementById('hdh-notification-bell');
        
        if (!dropdown || !bell) {
            return;
        }
        
        isDropdownOpen = false;
        dropdown.style.display = 'none';
        bell.setAttribute('aria-expanded', 'false');
    }
    
    /**
     * Load notifications
     */
    function loadNotifications() {
        const listContainer = document.getElementById('hdh-notification-list');
        if (!listContainer) {
            return;
        }
        
        listContainer.innerHTML = '<div class="notification-loading"><span>Yükleniyor...</span></div>';
        
        fetch(config.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'hdh_get_notifications',
                nonce: config.nonce,
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.notifications) {
                renderNotifications(data.data.notifications);
            } else {
                listContainer.innerHTML = '<div class="notification-empty"><span class="notification-empty-icon">🔔</span><p class="notification-empty-text">Bildirim yok</p></div>';
            }
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
            listContainer.innerHTML = '<div class="notification-empty"><span class="notification-empty-icon">⚠️</span><p class="notification-empty-text">Bildirimler yüklenemedi</p></div>';
        });
    }
    
    /**
     * Render notifications
     */
    function renderNotifications(notifications) {
        const listContainer = document.getElementById('hdh-notification-list');
        if (!listContainer) {
            return;
        }
        
        if (notifications.length === 0) {
            listContainer.innerHTML = '<div class="notification-empty"><span class="notification-empty-icon">🔔</span><p class="notification-empty-text">Bildirim yok</p></div>';
            return;
        }
        
        let html = '';
        notifications.forEach(notification => {
            const isUnread = !notification.is_read;
            const timeAgo = formatTimeAgo(notification.created_at);
            const linkAttr = notification.link_url ? `href="${escapeHtml(notification.link_url)}"` : '';
            const clickHandler = notification.link_url ? `onclick="hdhMarkNotificationRead(${notification.id}, '${escapeHtml(notification.link_url)}')"` : '';
            
            html += `
                <div class="notification-item ${isUnread ? 'unread' : ''}" data-notification-id="${notification.id}">
                    <div class="notification-item-title">${escapeHtml(notification.title)}</div>
                    <div class="notification-item-message">${escapeHtml(notification.message)}</div>
                    <div class="notification-item-time">${timeAgo}</div>
                </div>
            `;
        });
        
        listContainer.innerHTML = html;
        
        // Add click handlers
        listContainer.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function() {
                const notificationId = parseInt(this.getAttribute('data-notification-id'));
                const notification = notifications.find(n => n.id === notificationId);
                if (notification && notification.link_url) {
                    markNotificationRead(notificationId, notification.link_url);
                } else {
                    markNotificationRead(notificationId);
                }
            });
        });
    }
    
    /**
     * Mark notification as read
     */
    function markNotificationRead(notificationId, linkUrl = null) {
        fetch(config.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'hdh_mark_notification_read',
                nonce: config.nonce,
                notification_id: notificationId,
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update UI
                const item = document.querySelector(`[data-notification-id="${notificationId}"]`);
                if (item) {
                    item.classList.remove('unread');
                }
                
                // Update badge count
                updateUnreadCount();
                
                // Navigate if link provided
                if (linkUrl) {
                    window.location.href = linkUrl;
                }
            }
        })
        .catch(error => {
            console.error('Error marking notification as read:', error);
            // Still navigate if link provided
            if (linkUrl) {
                window.location.href = linkUrl;
            }
        });
    }
    
    /**
     * Handle mark all as read
     */
    function handleMarkAllRead() {
        fetch(config.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'hdh_mark_all_notifications_read',
                nonce: config.nonce,
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update UI
                document.querySelectorAll('.notification-item.unread').forEach(item => {
                    item.classList.remove('unread');
                });
                
                // Update badge
                updateUnreadCount();
                
                // Hide mark all button
                const markAllBtn = document.getElementById('btn-mark-all-read');
                if (markAllBtn) {
                    markAllBtn.style.display = 'none';
                }
            }
        })
        .catch(error => {
            console.error('Error marking all as read:', error);
        });
    }
    
    /**
     * Update unread count
     */
    function updateUnreadCount() {
        fetch(config.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'hdh_get_unread_count',
                nonce: config.nonce,
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const count = data.data.count || 0;
                const badge = document.getElementById('hdh-notification-badge');
                
                if (count > 0) {
                    if (badge) {
                        badge.textContent = count > 99 ? '99+' : count;
                    } else {
                        // Create badge if it doesn't exist
                        const bell = document.getElementById('hdh-notification-bell');
                        if (bell) {
                            const newBadge = document.createElement('span');
                            newBadge.className = 'bell-badge';
                            newBadge.id = 'hdh-notification-badge';
                            newBadge.textContent = count > 99 ? '99+' : count;
                            bell.appendChild(newBadge);
                        }
                    }
                } else {
                    // Remove badge if count is 0
                    if (badge) {
                        badge.remove();
                    }
                    
                    // Hide mark all button
                    const markAllBtn = document.getElementById('btn-mark-all-read');
                    if (markAllBtn) {
                        markAllBtn.style.display = 'none';
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error updating unread count:', error);
        });
    }
    
    /**
     * Start polling for unread count
     */
    function startPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
        }
        
        pollTimer = setInterval(() => {
            updateUnreadCount();
        }, config.pollInterval);
    }
    
    /**
     * Stop polling
     */
    function stopPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }
    
    /**
     * Format time ago
     */
    function formatTimeAgo(timestamp) {
        const now = new Date();
        const time = new Date(timestamp);
        const diff = Math.floor((now - time) / 1000);
        
        if (diff < 60) {
            return 'Az önce';
        } else if (diff < 3600) {
            const minutes = Math.floor(diff / 60);
            return `${minutes} dakika önce`;
        } else if (diff < 86400) {
            const hours = Math.floor(diff / 3600);
            return `${hours} saat önce`;
        } else {
            const days = Math.floor(diff / 86400);
            return `${days} gün önce`;
        }
    }
    
    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Global function for onclick handlers
    window.hdhMarkNotificationRead = function(notificationId, linkUrl) {
        markNotificationRead(notificationId, linkUrl);
    };
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();



