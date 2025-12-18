/**
 * HDH: Trade Roadmap JavaScript
 * Handles step completion, dispute creation, and session management
 */

(function() {
    'use strict';
    
    const hdhTradeRoadmap = {
        ajaxUrl: '',
        nonce: '',
        session: null,
        
        init: function() {
            if (typeof hdhTradeRoadmapData === 'undefined') {
                return;
            }
            
            this.ajaxUrl = hdhTradeRoadmapData.ajaxUrl;
            this.nonce = hdhTradeRoadmapData.nonce;
            
            // Start trade button
            const startBtn = document.getElementById('btn-start-trade');
            if (startBtn) {
                startBtn.addEventListener('click', this.handleStartTrade.bind(this));
            }
            
            // Step complete buttons
            document.querySelectorAll('.btn-step-complete').forEach(btn => {
                btn.addEventListener('click', this.handleCompleteStep.bind(this));
            });
            
            // Dispute button
            document.querySelectorAll('.btn-dispute').forEach(btn => {
                btn.addEventListener('click', this.handleOpenDispute.bind(this));
            });
            
            // Dispute modal
            const disputeModal = document.getElementById('dispute-modal');
            if (disputeModal) {
                const closeBtn = disputeModal.querySelector('.dispute-modal-close');
                const cancelBtn = disputeModal.querySelector('.btn-cancel-dispute');
                const overlay = disputeModal.querySelector('.dispute-modal-overlay');
                
                if (closeBtn) closeBtn.addEventListener('click', this.handleCloseDispute.bind(this));
                if (cancelBtn) cancelBtn.addEventListener('click', this.handleCloseDispute.bind(this));
                if (overlay) overlay.addEventListener('click', this.handleCloseDispute.bind(this));
                
                // Dispute form
                const disputeForm = document.getElementById('dispute-form');
                if (disputeForm) {
                    disputeForm.addEventListener('submit', this.handleSubmitDispute.bind(this));
                }
                
                // Character counter
                const disputeText = document.getElementById('dispute-text');
                const charCount = document.getElementById('dispute-char-count');
                if (disputeText && charCount) {
                    disputeText.addEventListener('input', function() {
                        charCount.textContent = this.value.length;
                    });
                }
            }
            
            // Copy farm code button
            document.querySelectorAll('.btn-copy-farm-code').forEach(btn => {
                btn.addEventListener('click', this.handleCopyFarmCode.bind(this));
            });
        },
        
        handleStartTrade: function(e) {
            const btn = e.currentTarget;
            const listingId = btn.getAttribute('data-listing-id');
            
            if (!listingId) {
                this.showToast('Hata: İlan ID bulunamadı', 'error');
                return;
            }
            
            btn.disabled = true;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="btn-icon">⏳</span><span class="btn-text">Başlatılıyor...</span>';
            
            const formData = new FormData();
            formData.append('action', 'hdh_start_trade_session');
            formData.append('listing_id', listingId);
            formData.append('nonce', this.nonce);
            
            fetch(this.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.showToast('Hediyeleşme başlatıldı!', 'success');
                    // Reload page with roadmap param to trigger scroll
                    const url = new URL(window.location.href);
                    url.searchParams.set('roadmap', 'true');
                    setTimeout(() => {
                        window.location.href = url.toString();
                    }, 1000);
                } else {
                    this.showToast(data.data.message || 'Bir hata oluştu', 'error');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.showToast('Bir hata oluştu. Lütfen tekrar deneyin.', 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        },
        
        handleCompleteStep: function(e) {
            const btn = e.currentTarget;
            const sessionId = btn.getAttribute('data-session-id');
            const step = btn.getAttribute('data-step');
            
            if (!sessionId || !step) {
                this.showToast('Hata: Oturum veya adım bilgisi bulunamadı', 'error');
                return;
            }
            
            if (!confirm('Bu adımı tamamladığınızı onaylıyor musunuz?')) {
                return;
            }
            
            btn.disabled = true;
            const originalText = btn.innerHTML;
            btn.innerHTML = '⏳ İşleniyor...';
            
            const formData = new FormData();
            formData.append('action', 'hdh_complete_trade_step');
            formData.append('session_id', sessionId);
            formData.append('step', step);
            formData.append('nonce', this.nonce);
            
            fetch(this.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.showToast('Adım tamamlandı!', 'success');
                    // Reload to update UI
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    this.showToast(data.data.message || 'Bir hata oluştu', 'error');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.showToast('Bir hata oluştu. Lütfen tekrar deneyin.', 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        },
        
        handleOpenDispute: function(e) {
            const btn = e.currentTarget;
            const sessionId = btn.getAttribute('data-session-id');
            
            const modal = document.getElementById('dispute-modal');
            const sessionInput = document.getElementById('dispute-session-id');
            
            if (modal && sessionInput) {
                sessionInput.value = sessionId;
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        },
        
        handleCloseDispute: function() {
            const modal = document.getElementById('dispute-modal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
                
                // Reset form
                const form = document.getElementById('dispute-form');
                if (form) {
                    form.reset();
                    const charCount = document.getElementById('dispute-char-count');
                    if (charCount) charCount.textContent = '0';
                }
            }
        },
        
        handleSubmitDispute: function(e) {
            e.preventDefault();
            
            const form = e.currentTarget;
            const formData = new FormData(form);
            formData.append('action', 'hdh_create_trade_dispute');
            formData.append('nonce', this.nonce);
            
            const submitBtn = form.querySelector('.btn-submit-dispute');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Gönderiliyor...';
            }
            
            fetch(this.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.showToast('Anlaşmazlık bildirildi. İnceleme altına alındı.', 'success');
                    this.handleCloseDispute();
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    this.showToast(data.data.message || 'Bir hata oluştu', 'error');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Gönder';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.showToast('Bir hata oluştu. Lütfen tekrar deneyin.', 'error');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Gönder';
                }
            });
        },
        
        handleCopyFarmCode: function(e) {
            const btn = e.currentTarget;
            const farmCode = btn.getAttribute('data-farm-code');
            
            if (!farmCode) return;
            
            // Copy to clipboard
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(farmCode).then(() => {
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '✅ Kopyalandı!';
                    this.showToast('Çiftlik kodu kopyalandı!', 'success');
                    setTimeout(() => {
                        btn.innerHTML = originalText;
                    }, 2000);
                }).catch(err => {
                    console.error('Copy failed:', err);
                    this.showToast('Kopyalama başarısız', 'error');
                });
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = farmCode;
                textArea.style.position = 'fixed';
                textArea.style.opacity = '0';
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '✅ Kopyalandı!';
                    this.showToast('Çiftlik kodu kopyalandı!', 'success');
                    setTimeout(() => {
                        btn.innerHTML = originalText;
                    }, 2000);
                } catch (err) {
                    this.showToast('Kopyalama başarısız', 'error');
                }
                document.body.removeChild(textArea);
            }
        },
        
        showToast: function(message, type) {
            // Remove existing toast
            const existing = document.querySelector('.trade-toast');
            if (existing) {
                existing.remove();
            }
            
            const toast = document.createElement('div');
            toast.className = 'trade-toast trade-toast-' + (type || 'success');
            toast.textContent = message;
            document.body.appendChild(toast);
            
            // Trigger animation
            setTimeout(() => {
                toast.classList.add('show');
            }, 10);
            
            // Remove after 3 seconds
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 3000);
        }
    };
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            hdhTradeRoadmap.init();
            
            // Check if we should scroll to roadmap (after page reload from start)
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('roadmap') === 'true') {
                setTimeout(() => {
                    const roadmap = document.getElementById('trade-roadmap');
                    if (roadmap) {
                        roadmap.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        // Remove param from URL
                        urlParams.delete('roadmap');
                        window.history.replaceState({}, '', window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : ''));
                    }
                }, 300);
            }
        });
    } else {
        hdhTradeRoadmap.init();
        
        // Check if we should scroll to roadmap
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('roadmap') === 'true') {
            setTimeout(() => {
                const roadmap = document.getElementById('trade-roadmap');
                if (roadmap) {
                    roadmap.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    urlParams.delete('roadmap');
                    window.history.replaceState({}, '', window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : ''));
                }
            }, 300);
        }
    }
})();

