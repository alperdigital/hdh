/**
 * HDH: Trade Request JavaScript
 * Handles trade request UI interactions and polling
 */

(function() {
    'use strict';
    
    // Configuration
    const config = {
        ajaxUrl: hdhTradeRequest?.ajaxUrl || '/wp-admin/admin-ajax.php',
        nonce: hdhTradeRequest?.nonce || '',
        pollInterval: 5000, // 5 seconds
        countdownInterval: 1000, // 1 second
    };
    
    // State
    let pollTimer = null;
    let countdownTimer = null;
    
    /**
     * Initialize trade request system
     */
    function init() {
        // Send trade request button
        const sendRequestBtn = document.getElementById('btn-send-trade-request');
        if (sendRequestBtn) {
            sendRequestBtn.addEventListener('click', handleSendRequest);
        }
        
        // Accept/reject buttons (for owners)
        document.querySelectorAll('.btn-accept-request').forEach(btn => {
            btn.addEventListener('click', handleAcceptRequest);
        });
        
        document.querySelectorAll('.btn-reject-request').forEach(btn => {
            btn.addEventListener('click', handleRejectRequest);
        });
        
        // New request button (after rejection/expiry)
        document.querySelectorAll('.btn-send-new-request').forEach(btn => {
            btn.addEventListener('click', handleSendRequest);
        });
        
        // Start countdown if pending request exists
        const countdownEl = document.querySelector('.request-countdown, .request-time-remaining');
        if (countdownEl) {
            startCountdown(countdownEl);
        }
        
        // Start polling if pending request exists
        const requestStatusEl = document.querySelector('.trade-request-status[data-request-id]');
        if (requestStatusEl) {
            const requestId = requestStatusEl.getAttribute('data-request-id');
            startPolling(requestId);
        }
    }
    
    /**
     * Handle send trade request
     */
    function handleSendRequest(e) {
        e.preventDefault();
        const btn = e.currentTarget;
        const listingId = btn.getAttribute('data-listing-id');
        
        if (!listingId) {
            console.error('Hata: İlan ID bulunamadı');
            return;
        }
        
        // Disable button
        btn.disabled = true;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="btn-icon">⏳</span><span class="btn-text">Gönderiliyor...</span>';
        
        // Send request
        fetch(config.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'hdh_send_trade_request',
                nonce: config.nonce,
                listing_id: listingId,
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Teklif başarıyla gönderildi - toast kaldırıldı
                // Reload page to show status
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                console.error('Teklif gönderilemedi:', data.data?.message || 'Bilinmeyen hata');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error sending trade request:', error);
            console.error('Bir hata oluştu. Lütfen tekrar deneyin.');
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }
    
    /**
     * Handle accept request
     */
    function handleAcceptRequest(e) {
        e.preventDefault();
        const btn = e.currentTarget;
        const requestId = btn.getAttribute('data-request-id');
        
        if (!requestId) {
            console.error('Hata: Teklif ID bulunamadı');
            return;
        }
        
        if (!confirm('Bu teklifi kabul etmek istediğinize emin misiniz?')) {
            return;
        }
        
        // Disable button
        btn.disabled = true;
        const originalText = btn.innerHTML;
        btn.innerHTML = 'Kabul ediliyor...';
        
        // Accept request
        fetch(config.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'hdh_accept_trade_request',
                nonce: config.nonce,
                request_id: requestId,
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Teklif kabul edildi - toast kaldırıldı
                // Reload page to show trade session
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                console.error('Teklif kabul edilemedi:', data.data?.message || 'Bilinmeyen hata');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error accepting trade request:', error);
            console.error('Bir hata oluştu. Lütfen tekrar deneyin.');
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }
    
    /**
     * Handle reject request
     */
    function handleRejectRequest(e) {
        e.preventDefault();
        const btn = e.currentTarget;
        const requestId = btn.getAttribute('data-request-id');
        
        if (!requestId) {
            console.error('Hata: Teklif ID bulunamadı');
            return;
        }
        
        if (!confirm('Bu teklifi reddetmek istediğinize emin misiniz?')) {
            return;
        }
        
        // Disable button
        btn.disabled = true;
        const originalText = btn.innerHTML;
        btn.innerHTML = 'Reddediliyor...';
        
        // Reject request
        fetch(config.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'hdh_reject_trade_request',
                nonce: config.nonce,
                request_id: requestId,
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Teklif reddedildi - toast kaldırıldı
                // Remove request item from DOM
                const requestItem = btn.closest('.pending-request-item');
                if (requestItem) {
                    requestItem.remove();
                }
            } else {
                console.error('Teklif reddedilemedi:', data.data?.message || 'Bilinmeyen hata');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error rejecting trade request:', error);
            console.error('Bir hata oluştu. Lütfen tekrar deneyin.');
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }
    
    /**
     * Start polling for request status
     */
    function startPolling(requestId) {
        if (pollTimer) {
            clearInterval(pollTimer);
        }
        
        pollTimer = setInterval(() => {
            fetch(config.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'hdh_get_trade_request_status',
                    nonce: config.nonce,
                    request_id: requestId,
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.request) {
                    const request = data.data.request;
                    
                    // If status changed, reload page
                    const currentStatus = document.querySelector('.trade-request-status')?.getAttribute('data-status');
                    if (currentStatus !== request.status) {
                        window.location.reload();
                    }
                    
                    // If expired, stop polling
                    if (request.status === 'expired' || request.status === 'rejected' || request.status === 'accepted') {
                        stopPolling();
                    }
                } else {
                    // Request not found, stop polling
                    stopPolling();
                }
            })
            .catch(error => {
                console.error('Error polling trade request status:', error);
            });
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
     * Start countdown timer
     */
    function startCountdown(element) {
        const expiresAt = element.getAttribute('data-expires-at');
        if (!expiresAt) {
            return;
        }
        
        if (countdownTimer) {
            clearInterval(countdownTimer);
        }
        
        countdownTimer = setInterval(() => {
            const expiresTimestamp = new Date(expiresAt).getTime();
            const now = Date.now();
            const remaining = Math.max(0, Math.floor((expiresTimestamp - now) / 1000));
            
            if (remaining <= 0) {
                clearInterval(countdownTimer);
                element.textContent = 'Süre doldu';
                // Reload page to show expired status
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                const minutes = Math.floor(remaining / 60);
                const seconds = remaining % 60;
                element.textContent = `${minutes}:${seconds.toString().padStart(2, '0')} kaldı`;
            }
        }, config.countdownInterval);
    }
    
    /**
     * Show toast notification - Disabled (no visual feedback, only console logging)
     */
    function showToast(message, type = 'info') {
        // Toast notifications removed - only log to console for debugging
        if (type === 'error') {
            console.error('Toast (disabled):', message);
        } else {
            console.log('Toast (disabled):', message);
        }
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();



