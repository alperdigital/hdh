/**
 * HDH: Gift Exchange Panel JavaScript
 * Handles toggle functionality and gift exchange management
 * Exact copy of tasks-panel.js logic
 */

(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        const giftIcon = document.getElementById('gift-exchange-icon-toggle');
        const giftPanel = document.getElementById('gift-exchange-panel');
        const giftOverlay = document.getElementById('gift-exchange-panel-overlay');
        const giftClose = document.getElementById('gift-exchange-panel-close');
        
        if (!giftIcon) {
            return; // Silently fail if icon not found
        }
        
        if (!giftPanel) {
            return; // Silently fail if panel not found
        }
        
        // Configuration
        const config = {
            ajaxUrl: hdhGiftExchange?.ajaxUrl || '/wp-admin/admin-ajax.php',
            nonce: hdhGiftExchange?.nonce || '',
            currentUserId: hdhGiftExchange?.currentUserId || 0,
            pollInterval: 3000, // 3 seconds for messages
        };
        
        // State
        let pollTimer = null;
        let currentExchangeId = null;
        let lastMessageId = 0;
        
        /**
         * Open gift exchange panel
         */
        function openGiftPanel() {
            giftPanel.classList.add('active');
            if (giftOverlay) {
                giftOverlay.classList.add('active');
            }
            document.body.style.overflow = 'hidden'; // Prevent background scroll
            loadExchanges();
        }
        
        /**
         * Close gift exchange panel
         */
        function closeGiftPanel() {
            giftPanel.classList.remove('active');
            if (giftOverlay) {
                giftOverlay.classList.remove('active');
            }
            document.body.style.overflow = ''; // Restore scroll
            stopPolling();
            currentExchangeId = null;
        }
        
        /**
         * Toggle panel
         */
        function handleToggle(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            if (giftPanel.classList.contains('active')) {
                closeGiftPanel();
            } else {
                openGiftPanel();
            }
        }
        
        // Support both click and touch events for better mobile compatibility
        let isToggling = false; // Prevent multiple rapid toggles
        
        giftIcon.addEventListener('click', function(e) {
            if (isToggling) return;
            isToggling = true;
            handleToggle(e);
            setTimeout(function() {
                isToggling = false;
            }, 300);
        }, { passive: false });
        
        giftIcon.addEventListener('touchend', function(e) {
            if (isToggling) return;
            isToggling = true;
            e.preventDefault();
            handleToggle(e);
            setTimeout(function() {
                isToggling = false;
            }, 300);
        }, { passive: false });
        
        // Also add mousedown for desktop
        giftIcon.addEventListener('mousedown', function(e) {
            e.preventDefault();
        });
        
        /**
         * Close panel on close button click
         */
        if (giftClose) {
            giftClose.addEventListener('click', function(e) {
                e.stopPropagation();
                closeGiftPanel();
            });
        }
        
        /**
         * Close panel on overlay click
         */
        if (giftOverlay) {
            giftOverlay.addEventListener('click', function(e) {
                e.stopPropagation();
                closeGiftPanel();
            });
        }
        
        /**
         * Close panel on Escape key
         */
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && giftPanel.classList.contains('active')) {
                closeGiftPanel();
            }
        });
        
        /**
         * Load exchanges list
         */
        function loadExchanges() {
            const loading = document.getElementById('gift-exchange-loading');
            const empty = document.getElementById('gift-exchange-empty');
            const list = document.getElementById('gift-exchanges-list');
            
            if (loading) loading.style.display = 'block';
            if (empty) empty.style.display = 'none';
            if (list) list.style.display = 'none';
            
            fetch(config.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'hdh_get_gift_exchanges',
                    nonce: config.nonce,
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (loading) loading.style.display = 'none';
                
                if (data.success && data.data.exchanges && data.data.exchanges.length > 0) {
                    renderExchangesList(data.data.exchanges);
                    updateBadgeCount(data.data.total_unread || 0);
                } else {
                    if (empty) empty.style.display = 'block';
                    if (list) list.style.display = 'none';
                    updateBadgeCount(0);
                }
            })
            .catch(error => {
                console.error('Error loading exchanges:', error);
                if (loading) loading.style.display = 'none';
                if (empty) empty.style.display = 'block';
                if (list) list.style.display = 'none';
            });
        }
        
        /**
         * Render exchanges list
         */
        function renderExchangesList(exchanges) {
            const list = document.getElementById('gift-exchanges-list');
            const empty = document.getElementById('gift-exchange-empty');
            
            if (!list) return;
            
            if (exchanges.length === 0) {
                if (empty) empty.style.display = 'block';
                list.style.display = 'none';
                return;
            }
            
            if (empty) empty.style.display = 'none';
            list.style.display = 'block';
            
            let html = '';
            exchanges.forEach(exchange => {
                const unreadBadge = exchange.unread_count > 0 
                    ? `<span class="exchange-unread-badge">${exchange.unread_count}</span>` 
                    : '';
                
                html += `
                    <div class="exchange-item" data-exchange-id="${exchange.id}">
                        <div class="exchange-info">
                            <div class="exchange-counterpart">${escapeHtml(exchange.counterpart_name || 'Bilinmeyen')}</div>
                            <div class="exchange-listing">${escapeHtml(exchange.listing_title || 'İlan')}</div>
                        </div>
                        ${unreadBadge}
                    </div>
                `;
            });
            
            list.innerHTML = html;
            
            // Attach click handlers
            document.querySelectorAll('.exchange-item').forEach(item => {
                item.addEventListener('click', function() {
                    const exchangeId = parseInt(this.getAttribute('data-exchange-id'));
                    openChat(exchangeId);
                });
            });
        }
        
        /**
         * Open chat for an exchange
         */
        function openChat(exchangeId) {
            currentExchangeId = exchangeId;
            
            // Load exchange details and messages
            Promise.all([
                fetch(config.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'hdh_get_gift_exchanges',
                        nonce: config.nonce,
                    }),
                }).then(r => r.json()),
                fetch(config.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'hdh_get_gift_messages',
                        nonce: config.nonce,
                        exchange_id: exchangeId,
                    }),
                }).then(r => r.json())
            ])
            .then(([exchangesData, messagesData]) => {
                const exchange = exchangesData.data.exchanges.find(e => e.id == exchangeId);
                if (!exchange) return;
                
                renderChatView(exchange, messagesData.data.messages || []);
                startPolling();
            })
            .catch(error => {
                console.error('Error loading chat:', error);
                showToast('Chat yüklenemedi', 'error');
            });
        }
        
        /**
         * Render chat view
         */
        function renderChatView(exchange, messages) {
            const content = document.getElementById('gift-exchange-panel-content');
            if (!content) return;
            
            const isCompleted = exchange.status === 'COMPLETED';
            const isDisputed = exchange.status === 'DISPUTED';
            const isLocked = isCompleted || isDisputed;
            
            // Determine completion status
            let completionStatus = '';
            if (isCompleted) {
                completionStatus = '✅ Hediyeleşme tamamlandı';
            } else if (isDisputed) {
                completionStatus = '⚠️ Şikayet edildi';
            } else if (exchange.completed_owner_at || exchange.completed_offerer_at) {
                completionStatus = 'Karşı tarafın onayı bekleniyor';
            }
            
            let html = `
                <div class="gift-exchange-chat-view">
                    <div class="chat-header">
                        <button class="btn-back-to-list" id="btn-back-to-exchanges">← Listeye Dön</button>
                        <div class="chat-counterpart">${escapeHtml(exchange.counterpart_name || 'Bilinmeyen')}</div>
                        <div class="chat-listing">${escapeHtml(exchange.listing_title || 'İlan')}</div>
                    </div>
                    
                    <div class="chat-messages" id="chat-messages-${exchange.id}">
                        ${renderMessages(messages)}
                    </div>
                    
                    ${!isLocked ? `
                        <div class="chat-input-container">
                            <input type="text" 
                                   class="chat-input" 
                                   id="chat-input-${exchange.id}" 
                                   placeholder="Mesajınızı yazın..."
                                   maxlength="1000">
                            <button class="chat-send-btn" id="chat-send-${exchange.id}">📨</button>
                        </div>
                    ` : ''}
                    
                    ${completionStatus ? `<div class="chat-status">${completionStatus}</div>` : ''}
                    
                    <div class="chat-actions">
                        ${!isLocked ? `
                            <button class="btn-complete-exchange" data-exchange-id="${exchange.id}">
                                ✅ Hediyeleşme Tamamlandı
                            </button>
                            <button class="btn-report-exchange" data-exchange-id="${exchange.id}">
                                ⚠️ Şikayet Et
                            </button>
                        ` : ''}
                    </div>
                </div>
            `;
            
            content.innerHTML = html;
            
            // Scroll to bottom
            const messagesContainer = document.getElementById(`chat-messages-${exchange.id}`);
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
            
            // Attach event handlers
            const backBtn = document.getElementById('btn-back-to-exchanges');
            if (backBtn) {
                backBtn.addEventListener('click', function() {
                    stopPolling();
                    currentExchangeId = null;
                    loadExchanges();
                });
            }
            
            if (!isLocked) {
                const sendBtn = document.getElementById(`chat-send-${exchange.id}`);
                const input = document.getElementById(`chat-input-${exchange.id}`);
                
                if (sendBtn && input) {
                    sendBtn.addEventListener('click', function() {
                        sendMessage(exchange.id, input.value.trim());
                    });
                    
                    input.addEventListener('keypress', function(e) {
                        if (e.key === 'Enter') {
                            sendMessage(exchange.id, input.value.trim());
                        }
                    });
                }
            }
            
            const completeBtn = document.querySelector('.btn-complete-exchange');
            if (completeBtn) {
                completeBtn.addEventListener('click', function() {
                    const exchangeId = parseInt(this.getAttribute('data-exchange-id'));
                    completeExchange(exchangeId);
                });
            }
            
            const reportBtn = document.querySelector('.btn-report-exchange');
            if (reportBtn) {
                reportBtn.addEventListener('click', function() {
                    const exchangeId = parseInt(this.getAttribute('data-exchange-id'));
                    if (confirm('Bu hediyeleşmeyi şikayet etmek istediğinize emin misiniz?')) {
                        reportExchange(exchangeId);
                    }
                });
            }
            
            // Mark messages as read
            markMessagesRead(exchange.id);
        }
        
        /**
         * Render messages
         */
        function renderMessages(messages) {
            if (!messages || messages.length === 0) {
                return '<div class="chat-empty">Henüz mesaj yok</div>';
            }
            
            let html = '';
            messages.forEach(msg => {
                html += renderSingleMessage(msg);
            });
            
            return html;
        }
        
        /**
         * Send message
         */
        function sendMessage(exchangeId, message) {
            if (!message) return;
            
            const input = document.getElementById(`chat-input-${exchangeId}`);
            const sendBtn = document.getElementById(`chat-send-${exchangeId}`);
            
            if (input) input.disabled = true;
            if (sendBtn) sendBtn.disabled = true;
            
            fetch(config.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'hdh_send_gift_message',
                    nonce: config.nonce,
                    exchange_id: exchangeId,
                    message: message,
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (input) input.disabled = false;
                if (sendBtn) sendBtn.disabled = false;
                
                if (data.success) {
                    if (input) input.value = '';
                    // Force full reload after sending to ensure message appears
                    loadMessages(exchangeId, true, true);
                } else {
                    showToast(data.data?.message || 'Mesaj gönderilemedi', 'error');
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
                if (input) input.disabled = false;
                if (sendBtn) sendBtn.disabled = false;
                showToast('Bir hata oluştu', 'error');
            });
        }
        
        /**
         * Load messages (optimized - only updates new messages)
         */
        function loadMessages(exchangeId, scrollToBottom = false, forceFullReload = false) {
            fetch(config.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'hdh_get_gift_messages',
                    nonce: config.nonce,
                    exchange_id: exchangeId,
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.messages) {
                    const messagesContainer = document.getElementById(`chat-messages-${exchangeId}`);
                    if (!messagesContainer) return;
                    
                    const wasAtBottom = messagesContainer.scrollHeight - messagesContainer.scrollTop <= messagesContainer.clientHeight + 50;
                    
                    // Get current message IDs to detect new messages
                    const currentMessageIds = new Set();
                    if (!forceFullReload) {
                        messagesContainer.querySelectorAll('.chat-message').forEach(msg => {
                            const msgId = msg.getAttribute('data-message-id');
                            if (msgId) currentMessageIds.add(parseInt(msgId));
                        });
                    }
                    
                    // If we have new messages or force reload, update
                    const newMessages = data.data.messages.filter(msg => !currentMessageIds.has(parseInt(msg.id)));
                    const hasNewMessages = newMessages.length > 0 || forceFullReload;
                    
                    if (hasNewMessages) {
                        // Only update if there are new messages or force reload
                        if (forceFullReload || currentMessageIds.size === 0) {
                            // Full reload - replace all
                            messagesContainer.innerHTML = renderMessages(data.data.messages);
                        } else {
                            // Incremental update - append only new messages
                            newMessages.forEach(msg => {
                                const msgHtml = renderSingleMessage(msg);
                                messagesContainer.insertAdjacentHTML('beforeend', msgHtml);
                            });
                        }
                        
                        if (scrollToBottom || wasAtBottom) {
                            // Use requestAnimationFrame for smooth scroll
                            requestAnimationFrame(() => {
                                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                            });
                        }
                    }
                    
                    // Update last message ID
                    if (data.data.messages && data.data.messages.length > 0) {
                        const lastMsg = data.data.messages[data.data.messages.length - 1];
                        const newLastId = parseInt(lastMsg.id) || 0;
                        if (newLastId > lastMessageId) {
                            lastMessageId = newLastId;
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error loading messages:', error);
            });
        }
        
        /**
         * Render single message (for incremental updates)
         */
        function renderSingleMessage(msg) {
            const sideClass = msg.side || 'left';
            const timeStr = msg.created_at ? new Date(msg.created_at).toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' }) : '';
            
            return `
                <div class="chat-message ${sideClass}" data-message-id="${msg.id}">
                    <div class="message-content">${escapeHtml(msg.message)}</div>
                    ${timeStr ? `<div class="message-time">${timeStr}</div>` : ''}
                </div>
            `;
        }
        
        /**
         * Start polling for messages
         */
        function startPolling() {
            stopPolling();
            
            if (!currentExchangeId) return;
            
            pollTimer = setInterval(function() {
                loadMessages(currentExchangeId);
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
         * Mark messages as read
         */
        function markMessagesRead(exchangeId) {
            fetch(config.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'hdh_mark_messages_read',
                    nonce: config.nonce,
                    exchange_id: exchangeId,
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update badge count
                    loadExchanges();
                }
            })
            .catch(error => {
                console.error('Error marking messages read:', error);
            });
        }
        
        /**
         * Complete exchange
         */
        function completeExchange(exchangeId) {
            fetch(config.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'hdh_complete_gift_exchange',
                    nonce: config.nonce,
                    exchange_id: exchangeId,
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.data?.message || 'Hediyeleşme tamamlandı', 'success');
                    // Reload chat view
                    openChat(exchangeId);
                    // Reload exchanges list to update badge
                    loadExchanges();
                } else {
                    showToast(data.data?.message || 'Tamamlanamadı', 'error');
                }
            })
            .catch(error => {
                console.error('Error completing exchange:', error);
                showToast('Bir hata oluştu', 'error');
            });
        }
        
        /**
         * Report exchange
         */
        function reportExchange(exchangeId) {
            fetch(config.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'hdh_report_gift_exchange',
                    nonce: config.nonce,
                    exchange_id: exchangeId,
                    reason: '',
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Şikayet bildirildi', 'success');
                    // Reload chat view
                    openChat(exchangeId);
                    // Reload exchanges list
                    loadExchanges();
                } else {
                    showToast(data.data?.message || 'Şikayet edilemedi', 'error');
                }
            })
            .catch(error => {
                console.error('Error reporting exchange:', error);
                showToast('Bir hata oluştu', 'error');
            });
        }
        
        /**
         * Update badge count
         */
        function updateBadgeCount(count) {
            const badge = document.getElementById('gift-exchange-icon-badge');
            if (badge) {
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : count;
                    badge.style.display = 'block';
                } else {
                    badge.style.display = 'none';
                }
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
        
        /**
         * Show toast notification
         */
        function showToast(message, type) {
            const toast = document.createElement('div');
            toast.className = 'toast toast-' + type;
            toast.textContent = message;
            toast.style.cssText = 'position: fixed; bottom: 100px; left: 50%; transform: translateX(-50%); background: ' + (type === 'success' ? 'var(--farm-green)' : '#dc3545') + '; color: #FFFFFF; padding: 14px 24px; border-radius: 10px; font-weight: 600; z-index: 10001; box-shadow: 0 4px 12px rgba(0,0,0,0.2); max-width: 90%; text-align: center; opacity: 0; transition: opacity 0.3s ease, transform 0.3s ease;';
            document.body.appendChild(toast);
            
            setTimeout(function() { 
                toast.style.opacity = '1'; 
                toast.style.transform = 'translateX(-50%) translateY(0)'; 
            }, 10);
            
            setTimeout(function() { 
                toast.style.opacity = '0'; 
                toast.style.transform = 'translateX(-50%) translateY(20px)'; 
                setTimeout(function() { 
                    toast.remove(); 
                }, 300); 
            }, 3000);
        }
        
        // Poll for badge updates when panel is closed
        setInterval(function() {
            if (!giftPanel.classList.contains('active')) {
                fetch(config.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'hdh_get_gift_exchanges',
                        nonce: config.nonce,
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateBadgeCount(data.data.total_unread || 0);
                    }
                })
                .catch(error => {
                    // Silent fail
                });
            }
        }, 10000); // Every 10 seconds
    });
})();

