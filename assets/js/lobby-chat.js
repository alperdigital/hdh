/**
 * HDH: Lobby Chat JavaScript
 * Handles chat UI interactions, polling, and real-time updates
 */

(function() {
    'use strict';
    
    // Configuration
    const config = {
        ajaxUrl: (typeof hdhLobbyChat !== 'undefined' && hdhLobbyChat?.ajaxUrl) ? hdhLobbyChat.ajaxUrl : '/wp-admin/admin-ajax.php',
        nonce: (typeof hdhLobbyChat !== 'undefined' && hdhLobbyChat?.nonce) ? hdhLobbyChat.nonce : '',
        pollInterval: 5000, // 5 seconds
        messagesPerPage: 50,
    };
    
    // Debug: Log config if nonce is missing
    if (!config.nonce) {
        console.warn('HDH Lobby Chat: Nonce is missing. Check if hdhLobbyChat is properly localized.');
    }
    
    // State
    let pollTimer = null;
    let lastMessageId = 0;
    let isLoading = false;
    let offset = 0;
    let hasMoreMessages = true;
    
    /**
     * Initialize lobby chat
     */
    function init() {
        // Get initial last message ID
        const messagesList = document.getElementById('lobby-chat-messages-list');
        if (messagesList) {
            const lastMessage = messagesList.querySelector('.chat-message-wrapper:last-child');
            if (lastMessage) {
                lastMessageId = parseInt(lastMessage.getAttribute('data-message-id')) || 0;
            }
        }
        
        // Chat form submission
        const chatForm = document.getElementById('lobby-chat-form');
        if (chatForm) {
            chatForm.addEventListener('submit', handleSendMessage);
        }
        
        // Also handle button click directly (fallback)
        const sendBtn = document.getElementById('btn-send-chat-message');
        if (sendBtn) {
            sendBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const form = document.getElementById('lobby-chat-form');
                if (form) {
                    handleSendMessage(new Event('submit'));
                }
            });
        }
        
        // Character count
        const chatInput = document.getElementById('lobby-chat-input');
        if (chatInput) {
            chatInput.addEventListener('input', updateCharCount);
            updateCharCount(); // Initial count
        }
        
        // Load more button
        const loadMoreBtn = document.getElementById('btn-load-more-messages');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', handleLoadMore);
        }
        
        // Scroll to bottom button
        const scrollToBottomBtn = document.getElementById('btn-scroll-to-bottom');
        if (scrollToBottomBtn) {
            scrollToBottomBtn.addEventListener('click', scrollToBottom);
        }
        
        // Auto-scroll to bottom on new messages
        const messagesContainer = document.getElementById('lobby-chat-messages');
        if (messagesContainer) {
            // Check if user is near bottom
            messagesContainer.addEventListener('scroll', handleScroll);
        }
        
        // Start polling if logged in
        if (document.getElementById('lobby-chat-messages-list')) {
            startPolling();
        }
        
        // Auto-scroll to bottom initially
        setTimeout(scrollToBottom, 500);
    }
    
    /**
     * Handle send message
     */
    function handleSendMessage(e) {
        e.preventDefault();
        
        const input = document.getElementById('lobby-chat-input');
        if (!input) {
            return;
        }
        
        const message = input.value.trim();
        if (!message) {
            return;
        }
        
        const submitBtn = document.getElementById('btn-send-chat-message');
        if (submitBtn) {
            submitBtn.disabled = true;
            // Don't change icon, just disable
        }
        
        // Validate nonce
        if (!config.nonce) {
            showToast('Güvenlik hatası. Sayfayı yenileyin.', 'error');
            if (submitBtn) {
                submitBtn.disabled = false;
            }
            return;
        }
        
        // Send message
        fetch(config.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'hdh_send_chat_message',
                nonce: config.nonce,
                message: message,
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Clear input
                input.value = '';
                updateCharCount();
                
                // Force refresh messages (reset offset to get latest)
                offset = 0;
                lastMessageId = 0;
                loadMessages(false); // Full reload
                
                // Restart polling
                stopPolling();
                setTimeout(startPolling, 1000);
            } else {
                showToast(data.data?.message || 'Mesaj gönderilemedi', 'error');
            }
            
            if (submitBtn) {
                submitBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error sending chat message:', error);
            showToast('Bir hata oluştu. Lütfen tekrar deneyin.', 'error');
            if (submitBtn) {
                submitBtn.disabled = false;
            }
        });
    }
    
    /**
     * Update character count
     */
    function updateCharCount() {
        const input = document.getElementById('lobby-chat-input');
        const countEl = document.getElementById('chat-char-count');
        if (!input || !countEl) {
            return;
        }
        
        const maxLength = parseInt(input.getAttribute('maxlength')) || 200;
        const currentLength = input.value.length;
        countEl.textContent = `${currentLength} / ${maxLength}`;
        
        if (currentLength > maxLength * 0.9) {
            countEl.style.color = '#FF6347';
        } else {
            countEl.style.color = '';
        }
    }
    
    /**
     * Start polling for new messages
     */
    function startPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
        }
        
        pollTimer = setInterval(() => {
            if (!isLoading) {
                checkNewMessages();
            }
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
     * Check for new messages
     */
    function checkNewMessages() {
        const messagesList = document.getElementById('lobby-chat-messages-list');
        if (!messagesList) {
            return;
        }
        
        // Get current last message ID
        const currentLastMessage = messagesList.querySelector('.chat-message-wrapper:last-child');
        const currentLastId = currentLastMessage ? parseInt(currentLastMessage.getAttribute('data-message-id')) || 0 : 0;
        
        // Fetch latest messages
        fetch(config.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'hdh_get_chat_messages',
                nonce: config.nonce,
                limit: 10,
                offset: 0,
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.messages) {
                const messages = data.data.messages;
                if (messages.length > 0) {
                    const latestId = messages[messages.length - 1].id;
                    
                    // If we have new messages
                    if (latestId > currentLastId) {
                        // Check if user is near bottom
                        const messagesContainer = document.getElementById('lobby-chat-messages');
                        const isNearBottom = messagesContainer && 
                            (messagesContainer.scrollHeight - messagesContainer.scrollTop - messagesContainer.clientHeight < 100);
                        
                        // Add new messages
                        const newMessages = messages.filter(msg => msg.id > currentLastId);
                        if (newMessages.length > 0) {
                            appendMessages(newMessages);
                            
                            // Show new messages indicator if not near bottom
                            if (!isNearBottom) {
                                showNewMessagesIndicator();
                            } else {
                                scrollToBottom();
                            }
                        }
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error checking new messages:', error);
        });
    }
    
    /**
     * Load messages
     */
    function loadMessages(append = false) {
        if (isLoading) {
            return;
        }
        
        isLoading = true;
        const messagesList = document.getElementById('lobby-chat-messages-list');
        if (!messagesList) {
            isLoading = false;
            return;
        }
        
        fetch(config.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'hdh_get_chat_messages',
                nonce: config.nonce,
                limit: config.messagesPerPage,
                offset: offset,
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.messages) {
                const messages = data.data.messages;
                
                if (append) {
                    appendMessages(messages);
                } else {
                    renderMessages(messages);
                }
                
                hasMoreMessages = data.data.has_more;
                
                // Update offset
                offset += messages.length;
                
                // Show/hide load more button
                const loadMoreEl = document.getElementById('lobby-chat-load-more');
                if (loadMoreEl) {
                    loadMoreEl.style.display = hasMoreMessages ? 'block' : 'none';
                }
            }
            
            isLoading = false;
        })
        .catch(error => {
            console.error('Error loading messages:', error);
            isLoading = false;
        });
    }
    
    /**
     * Render messages
     */
    function renderMessages(messages) {
        const messagesList = document.getElementById('lobby-chat-messages-list');
        if (!messagesList) {
            return;
        }
        
        messagesList.innerHTML = '';
        appendMessages(messages);
    }
    
    /**
     * Append messages to list
     */
    function appendMessages(messages) {
        const messagesList = document.getElementById('lobby-chat-messages-list');
        if (!messagesList) {
            return;
        }
        
        messages.forEach(message => {
            const messageEl = createMessageElement(message);
            messagesList.appendChild(messageEl);
        });
        
        // Update last message ID
        if (messages.length > 0) {
            lastMessageId = messages[messages.length - 1].id;
        }
    }
    
    /**
     * Create message element (WhatsApp style)
     */
    function createMessageElement(message) {
        const currentUserId = (typeof hdhLobbyChat !== 'undefined' && hdhLobbyChat?.currentUserId) ? parseInt(hdhLobbyChat.currentUserId) : 0;
        const isOwnMessage = (parseInt(message.user_id) === currentUserId);
        
        const wrapper = document.createElement('div');
        wrapper.className = `chat-message-wrapper ${isOwnMessage ? 'message-own' : 'message-other'}`;
        wrapper.setAttribute('data-message-id', message.id);
        
        const bubble = document.createElement('div');
        bubble.className = 'chat-message-bubble';
        
        const userLevel = message.user_level || 1;
        const levelDigits = String(userLevel).length;
        const levelClass = `lvl-d${levelDigits}`;
        
        const timeFormatted = formatTimeWhatsApp(message.created_at);
        const isCensored = message.status === 'censored';
        
        // Header with user info
        const headerDiv = document.createElement('div');
        headerDiv.className = 'chat-message-header';
        
        const userLink = document.createElement('a');
        userLink.href = `/profil?user=${message.user_id}`;
        userLink.className = 'chat-message-user';
        
        const levelBadge = document.createElement('div');
        levelBadge.className = `hdh-level-badge ${levelClass}`;
        levelBadge.setAttribute('aria-label', `Seviye ${userLevel}`);
        levelBadge.textContent = userLevel;
        
        const farmName = document.createElement('span');
        farmName.className = 'chat-message-farm-name';
        farmName.textContent = message.user_name || 'Bilinmeyen';
        
        userLink.appendChild(levelBadge);
        userLink.appendChild(farmName);
        headerDiv.appendChild(userLink);
        
        // Content
        const contentDiv = document.createElement('div');
        contentDiv.className = `chat-message-content ${isCensored ? 'message-censored' : ''}`;
        // Message content is already sanitized by wp_kses_post on backend
        contentDiv.innerHTML = message.message;
        
        if (isCensored) {
            const censoredBadge = document.createElement('span');
            censoredBadge.className = 'censored-badge';
            censoredBadge.setAttribute('title', 'Bu mesaj moderasyon tarafından düzenlendi');
            censoredBadge.textContent = '⚠️';
            contentDiv.appendChild(censoredBadge);
        }
        
        // Footer with timestamp
        const footerDiv = document.createElement('div');
        footerDiv.className = 'chat-message-footer';
        
        const timeSpan = document.createElement('span');
        timeSpan.className = 'chat-message-time';
        timeSpan.textContent = timeFormatted;
        
        footerDiv.appendChild(timeSpan);
        
        // Assemble bubble
        bubble.appendChild(headerDiv);
        bubble.appendChild(contentDiv);
        bubble.appendChild(footerDiv);
        
        wrapper.appendChild(bubble);
        
        return wrapper;
    }
    
    /**
     * Format time WhatsApp style (HH:MM or dd.MM.yyyy HH:MM)
     */
    function formatTimeWhatsApp(timestamp) {
        const time = new Date(timestamp);
        const now = new Date();
        
        const messageDate = new Date(time.getFullYear(), time.getMonth(), time.getDate());
        const todayDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        
        const hours = String(time.getHours()).padStart(2, '0');
        const minutes = String(time.getMinutes()).padStart(2, '0');
        
        if (messageDate.getTime() === todayDate.getTime()) {
            // Today: show only time
            return `${hours}:${minutes}`;
        } else {
            // Not today: show date and time
            const day = String(time.getDate()).padStart(2, '0');
            const month = String(time.getMonth() + 1).padStart(2, '0');
            const year = time.getFullYear();
            return `${day}.${month}.${year} ${hours}:${minutes}`;
        }
    }
    
    /**
     * Format time ago (kept for backward compatibility if needed)
     */
    function formatTimeAgo(timestamp) {
        return formatTimeWhatsApp(timestamp);
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
     * Handle load more
     */
    function handleLoadMore() {
        loadMessages(true);
    }
    
    /**
     * Handle scroll
     */
    function handleScroll() {
        const messagesContainer = document.getElementById('lobby-chat-messages');
        if (!messagesContainer) {
            return;
        }
        
        const isNearBottom = messagesContainer.scrollHeight - messagesContainer.scrollTop - messagesContainer.clientHeight < 100;
        
        // Hide new messages indicator if near bottom
        if (isNearBottom) {
            hideNewMessagesIndicator();
        }
    }
    
    /**
     * Scroll to bottom
     */
    function scrollToBottom() {
        const messagesContainer = document.getElementById('lobby-chat-messages');
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
            hideNewMessagesIndicator();
        }
    }
    
    /**
     * Show new messages indicator
     */
    function showNewMessagesIndicator() {
        const indicator = document.getElementById('lobby-chat-new-messages-indicator');
        if (indicator) {
            indicator.style.display = 'block';
        }
    }
    
    /**
     * Hide new messages indicator
     */
    function hideNewMessagesIndicator() {
        const indicator = document.getElementById('lobby-chat-new-messages-indicator');
        if (indicator) {
            indicator.style.display = 'none';
        }
    }
    
    /**
     * Show toast notification
     */
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `lobby-chat-toast lobby-chat-toast-${type}`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
