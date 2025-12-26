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
        
        // Attach click handlers to all gift exchange buttons
        function attachHandlers() {
            document.querySelectorAll('.btn-start-gift-exchange').forEach(btn => {
                // Remove existing listeners by cloning
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
                    
                    if (!config.nonce) {
                        showToast('Güvenlik hatası. Sayfayı yenileyin.', 'error');
                        return;
                    }
                    
                    // Disable button
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
                            // Optionally open gift exchange panel
                            const giftIcon = document.getElementById('gift-exchange-icon-toggle');
                            if (giftIcon) {
                                setTimeout(() => {
                                    giftIcon.click();
                                }, 500);
                            }
                        } else {
                            showToast(data.data?.message || 'Hediyeleşme başlatılamadı', 'error');
                        }
                        
                        // Re-enable button
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
                            if (node.nodeType === 1 && (node.classList.contains('listing-unified-block') || node.querySelector('.btn-start-gift-exchange'))) {
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

