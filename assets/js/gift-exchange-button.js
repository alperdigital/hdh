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
                        console.error('Geçersiz ilan');
                        return;
                    }
                    
                    // Check if login is required
                    const requiresLogin = this.getAttribute('data-requires-login') === 'true';
                    if (requiresLogin) {
                        // Redirect to profile page (registration form)
                        window.location.href = '/profil';
                        return;
                    }
                    
                    if (!config.nonce) {
                        console.error('Güvenlik hatası. Sayfayı yenileyin.');
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
                            // Get exchange ID from response - handle different response formats
                            const exchangeId = data.data?.exchange?.id || 
                                             data.data?.exchange_id || 
                                             (data.data?.exchange && parseInt(data.data.exchange.id)) ||
                                             null;
                            
                            console.log('Gift exchange started, exchangeId:', exchangeId, 'Response:', data);
                            
                            // Open gift exchange panel and chat view
                            const giftIcon = document.getElementById('gift-exchange-icon-toggle');
                            if (giftIcon && exchangeId) {
                                // Open panel first
                                const giftPanel = document.getElementById('gift-exchange-panel');
                                if (!giftPanel || !giftPanel.classList.contains('active')) {
                                    giftIcon.click();
                                }
                                
                                // Wait for panel to open, then open chat view
                                setTimeout(() => {
                                    if (typeof window.openGiftExchangeChat === 'function') {
                                        // Use global function if available
                                        window.openGiftExchangeChat(exchangeId);
                                    } else {
                                        // Try to trigger chat view opening via custom event
                                        const event = new CustomEvent('hdh-open-gift-chat', {
                                            detail: { exchangeId: exchangeId }
                                        });
                                        window.dispatchEvent(event);
                                    }
                                }, 800);
                            } else if (!exchangeId) {
                                console.warn('Exchange ID not found in response, opening panel only');
                                // If no exchange ID, just open panel
                                if (giftIcon) {
                                    giftIcon.click();
                                }
                            }
                        } else {
                            console.error('Hediyeleşme başlatılamadı:', data.data?.message || 'Bilinmeyen hata');
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
                        console.error('Geçersiz ilan');
                        return;
                    }
                    
                    // Check if login is required (for buttons, assume they're only shown when logged in)
                    // But check nonce to be sure
                    if (!config.nonce) {
                        // No nonce means not logged in, redirect to profile page (registration form)
                        window.location.href = '/profil';
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
                            // Get exchange ID from response - handle different response formats
                            const exchangeId = data.data?.exchange?.id || 
                                             data.data?.exchange_id || 
                                             (data.data?.exchange && parseInt(data.data.exchange.id)) ||
                                             null;
                            
                            console.log('Gift exchange started (legacy button), exchangeId:', exchangeId, 'Response:', data);
                            
                            // Open gift exchange panel and chat view
                            const giftIcon = document.getElementById('gift-exchange-icon-toggle');
                            if (giftIcon && exchangeId) {
                                // Open panel first
                                const giftPanel = document.getElementById('gift-exchange-panel');
                                if (!giftPanel || !giftPanel.classList.contains('active')) {
                                    giftIcon.click();
                                }
                                
                                // Wait for panel to open, then open chat view
                                setTimeout(() => {
                                    if (typeof window.openGiftExchangeChat === 'function') {
                                        window.openGiftExchangeChat(exchangeId);
                                    } else {
                                        const event = new CustomEvent('hdh-open-gift-chat', {
                                            detail: { exchangeId: exchangeId }
                                        });
                                        window.dispatchEvent(event);
                                    }
                                }, 800);
                            } else if (!exchangeId) {
                                console.warn('Exchange ID not found in response, opening panel only');
                                // If no exchange ID, just open panel
                                if (giftIcon) {
                                    giftIcon.click();
                                }
                            }
                        } else {
                            console.error('Hediyeleşme başlatılamadı:', data.data?.message || 'Bilinmeyen hata');
                        }
                        
                        this.disabled = false;
                        this.textContent = originalText;
                    })
                    .catch(error => {
                        console.error('Error starting gift exchange:', error);
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
         * Show toast notification - Disabled (no visual feedback, only console logging)
         */
        function showToast(message, type) {
            // Toast notifications removed - only log to console for debugging
            if (type === 'error') {
                console.error('Toast (disabled):', message);
            } else {
                console.log('Toast (disabled):', message);
            }
        }
    });
})();

