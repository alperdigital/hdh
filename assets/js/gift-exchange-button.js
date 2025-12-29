/**
 * HDH: Gift Exchange Button Handler
 * Handles "Hediyeleş" button clicks on listing cards
 */

(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        // Configuration
        const config = {
            ajaxUrl: (typeof hdhGiftExchange !== 'undefined' && hdhGiftExchange?.ajaxUrl) ? hdhGiftExchange.ajaxUrl : '/wp-admin/admin-ajax.php',
            nonce: (typeof hdhGiftExchange !== 'undefined' && hdhGiftExchange?.nonce) ? hdhGiftExchange.nonce : '',
        };
        
        // Attach click handlers to clickable listing cards
        function attachHandlers() {
            // Handle clickable listing cards
            document.querySelectorAll('.listing-clickable').forEach(card => {
                // Remove existing listeners by cloning
                const newCard = card.cloneNode(true);
                card.parentNode.replaceChild(newCard, card);
                
                newCard.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const listingId = parseInt(this.getAttribute('data-listing-id'));
                    if (!listingId) {
                        showToast('Geçersiz ilan', 'error');
                        return;
                    }
                    
                    // Check if login is required
                    const requiresLogin = this.getAttribute('data-requires-login') === 'true';
                    if (requiresLogin) {
                        // Redirect to registration page
                        const currentUrl = window.location.href;
                        const separator = currentUrl.includes('?') ? '&' : '?';
                        window.location.href = currentUrl + separator + 'action=register';
                        return;
                    }
                    
                    if (!config.nonce) {
                        showToast('Güvenlik hatası. Sayfayı yenileyin.', 'error');
                        return;
                    }
                    
                    // Disable card interaction
                    this.style.pointerEvents = 'none';
                    this.style.opacity = '0.7';
                    const originalHint = this.querySelector('.listing-click-hint');
                    const originalHintText = originalHint ? originalHint.textContent : '';
                    if (originalHint) {
                        originalHint.textContent = '⏳ İşleniyor...';
                    }
                    
                    fetch(config.ajaxUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'hdh_start_gift_exchange',
                            nonce: config.nonce,
                            listing_id: listingId,
                        }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.data?.message || 'Hediyeleşme başlatıldı!', 'success');
                            
                            // Get exchange ID from response
                            const exchangeId = data.data?.exchange?.id || data.data?.exchange_id;
                            
                            // Open gift exchange panel and chat view
                            const giftIcon = document.getElementById('gift-exchange-icon-toggle');
                            if (giftIcon) {
                                // Open panel first
                                if (!document.getElementById('gift-exchange-panel')?.classList.contains('active')) {
                                    giftIcon.click();
                                }
                                
                                // Wait for panel to open, then open chat view
                                setTimeout(() => {
                                    if (exchangeId && typeof window.openGiftExchangeChat === 'function') {
                                        // Use global function if available
                                        window.openGiftExchangeChat(exchangeId);
                                    } else if (exchangeId) {
                                        // Try to trigger chat view opening via custom event
                                        const event = new CustomEvent('hdh-open-gift-chat', {
                                            detail: { exchangeId: exchangeId }
                                        });
                                        window.dispatchEvent(event);
                                    }
                                }, 800);
                            }
                        } else {
                            showToast(data.data?.message || 'Hediyeleşme başlatılamadı', 'error');
                        }
                        
                        // Re-enable card
                        this.style.pointerEvents = '';
                        this.style.opacity = '';
                        if (originalHint) {
                            originalHint.textContent = originalHintText;
                        }
                    })
                    .catch(error => {
                        console.error('Error starting gift exchange:', error);
                        showToast('Bir hata oluştu', 'error');
                        this.style.pointerEvents = '';
                        this.style.opacity = '';
                        if (originalHint) {
                            originalHint.textContent = originalHintText;
                        }
                    });
                });
                
                // Handle keyboard (Enter/Space)
                newCard.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.click();
                    }
                });
            });
            
            // Also handle legacy buttons for backward compatibility
            document.querySelectorAll('.btn-start-gift-exchange, .btn-start-gift-exchange-inline').forEach(btn => {
                const newBtn = btn.cloneNode(true);
                btn.parentNode.replaceChild(newBtn, btn);
                
                newBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const listingId = parseInt(this.getAttribute('data-listing-id'));
                    if (!listingId) {
                        showToast('Geçersiz ilan', 'error');
                        return;
                    }
                    
                    // Check if login is required (for buttons, assume they're only shown when logged in)
                    // But check nonce to be sure
                    if (!config.nonce) {
                        // No nonce means not logged in, redirect to registration
                        const currentUrl = window.location.href;
                        const separator = currentUrl.includes('?') ? '&' : '?';
                        window.location.href = currentUrl + separator + 'action=register';
                        return;
                    }
                    
                    this.disabled = true;
                    const originalText = this.textContent;
                    this.textContent = '⏳ İşleniyor...';
                    
                    fetch(config.ajaxUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'hdh_start_gift_exchange',
                            nonce: config.nonce,
                            listing_id: listingId,
                        }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.data?.message || 'Hediyeleşme başlatıldı!', 'success');
                            
                            // Get exchange ID from response
                            const exchangeId = data.data?.exchange?.id || data.data?.exchange_id;
                            
                            // Open gift exchange panel and chat view
                            const giftIcon = document.getElementById('gift-exchange-icon-toggle');
                            if (giftIcon) {
                                // Open panel first
                                if (!document.getElementById('gift-exchange-panel')?.classList.contains('active')) {
                                    giftIcon.click();
                                }
                                
                                // Wait for panel to open, then open chat view
                                setTimeout(() => {
                                    if (exchangeId && typeof window.openGiftExchangeChat === 'function') {
                                        window.openGiftExchangeChat(exchangeId);
                                    } else if (exchangeId) {
                                        const event = new CustomEvent('hdh-open-gift-chat', {
                                            detail: { exchangeId: exchangeId }
                                        });
                                        window.dispatchEvent(event);
                                    }
                                }, 800);
                            }
                        } else {
                            showToast(data.data?.message || 'Hediyeleşme başlatılamadı', 'error');
                        }
                        
                        this.disabled = false;
                        this.textContent = originalText;
                    })
                    .catch(error => {
                        console.error('Error starting gift exchange:', error);
                        showToast('Bir hata oluştu', 'error');
                        this.disabled = false;
                        this.textContent = originalText;
                    });
                });
            });
        }
        
        // Initial attachment
        attachHandlers();
        
        // Re-attach when new cards are loaded (AJAX)
        if (window.MutationObserver) {
            const observer = new MutationObserver(function(mutations) {
                let shouldReattach = false;
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes.length > 0) {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1 && (node.classList.contains('listing-clickable') || node.classList.contains('listing-unified-block') || node.querySelector('.btn-start-gift-exchange'))) {
                                shouldReattach = true;
                            }
                        });
                    }
                });
                if (shouldReattach) {
                    setTimeout(attachHandlers, 100);
                }
            });
            
            const container = document.getElementById('trade-cards-grid');
            if (container) {
                observer.observe(container, {
                    childList: true,
                    subtree: true
                });
            }
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
    });
})();

