(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        // Quantity controls
        const qtyButtons = document.querySelectorAll('.qty-btn, .qty-btn-small');
        qtyButtons.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                if (!input) return;
                
                let currentValue = parseInt(input.value) || 1;
                const min = parseInt(input.getAttribute('min')) || 1;
                const max = parseInt(input.getAttribute('max')) || 999;
                
                if (this.classList.contains('qty-minus') || this.classList.contains('qty-minus-small')) {
                    if (currentValue > min) {
                        input.value = currentValue - 1;
                    }
                } else if (this.classList.contains('qty-plus') || this.classList.contains('qty-plus-small')) {
                    if (currentValue < max) {
                        input.value = currentValue + 1;
                    }
                }
            });
        });
        
        // Offer item selection - show/hide quantity controls
        const offerCheckboxes = document.querySelectorAll('.offer-item-check');
        offerCheckboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const parent = this.closest('.offer-item-select');
                const qtyControl = parent.querySelector('.offer-qty-control');
                if (qtyControl) {
                    qtyControl.style.display = this.checked ? 'flex' : 'none';
                }
            });
        });
        
        // Make offer form submission
        const makeOfferForm = document.getElementById('make-offer-form');
        if (makeOfferForm) {
            makeOfferForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Check if at least one item is selected
                const checkedItems = makeOfferForm.querySelectorAll('.offer-item-check:checked');
                if (checkedItems.length === 0) {
                    const msg = (hdhSingleTrade.messages && hdhSingleTrade.messages.ajax && hdhSingleTrade.messages.ajax.select_at_least_one_gift) 
                        ? hdhSingleTrade.messages.ajax.select_at_least_one_gift 
                        : 'En az bir hediye seçmelisiniz.';
                    showToast(msg, 'error');
                    return;
                }
                
                const formData = new FormData(makeOfferForm);
                formData.append('action', 'hdh_make_offer');
                formData.append('nonce', hdhSingleTrade.makeOfferNonce);
                
                const submitBtn = makeOfferForm.querySelector('.btn-submit-offer');
                submitBtn.disabled = true;
                const sendingMsg = (hdhSingleTrade.messages && hdhSingleTrade.messages.ui && hdhSingleTrade.messages.ui.sending) 
                    ? hdhSingleTrade.messages.ui.sending 
                    : 'Gönderiliyor...';
                submitBtn.textContent = '⏳ ' + sendingMsg;
                
                fetch(hdhSingleTrade.ajaxUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.data.message, 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        const errorMsg = data.data.message || (hdhSingleTrade.messages && hdhSingleTrade.messages.ajax && hdhSingleTrade.messages.ajax.generic_error) 
                            ? hdhSingleTrade.messages.ajax.generic_error 
                            : 'Bir hata oluştu.';
                        showToast(errorMsg, 'error');
                        submitBtn.disabled = false;
                        submitBtn.textContent = '📤 Teklif Gönder';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const errorMsg = (hdhSingleTrade.messages && hdhSingleTrade.messages.ajax && hdhSingleTrade.messages.ajax.generic_error_retry) 
                        ? hdhSingleTrade.messages.ajax.generic_error_retry 
                        : 'Bir hata oluştu. Lütfen tekrar deneyin.';
                    showToast(errorMsg, 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = '📤 Teklif Gönder';
                });
            });
        }
        
        // Accept offer buttons
        const acceptButtons = document.querySelectorAll('.btn-accept-offer');
        acceptButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const confirmMsg = (hdhSingleTrade.messages && hdhSingleTrade.messages.ui && hdhSingleTrade.messages.ui.confirm_accept_offer) 
                    ? hdhSingleTrade.messages.ui.confirm_accept_offer 
                    : 'Bu teklifi kabul etmek istediğinize emin misiniz? Diğer tüm teklifler reddedilecek.';
                if (!confirm(confirmMsg)) {
                    return;
                }
                
                const offerId = this.getAttribute('data-offer-id');
                btn.disabled = true;
                const processingMsg = (hdhSingleTrade.messages && hdhSingleTrade.messages.ui && hdhSingleTrade.messages.ui.processing) 
                    ? hdhSingleTrade.messages.ui.processing 
                    : 'İşleniyor...';
                btn.textContent = '⏳ ' + processingMsg;
                
                fetch(hdhSingleTrade.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'hdh_accept_offer',
                        offer_id: offerId,
                        nonce: hdhSingleTrade.offerResponseNonce
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.data.message, 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showToast(data.data.message || 'Bir hata oluştu.', 'error');
                        btn.disabled = false;
                        btn.textContent = '✅ Kabul Et';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Bir hata oluştu.', 'error');
                    btn.disabled = false;
                    btn.textContent = '✅ Kabul Et';
                });
            });
        });
        
        // Reject offer buttons
        const rejectButtons = document.querySelectorAll('.btn-reject-offer');
        rejectButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const confirmMsg = (hdhSingleTrade.messages && hdhSingleTrade.messages.ui && hdhSingleTrade.messages.ui.confirm_reject_offer) 
                    ? hdhSingleTrade.messages.ui.confirm_reject_offer 
                    : 'Bu teklifi reddetmek istediğinize emin misiniz?';
                if (!confirm(confirmMsg)) {
                    return;
                }
                
                const offerId = this.getAttribute('data-offer-id');
                btn.disabled = true;
                const processingMsg = (hdhSingleTrade.messages && hdhSingleTrade.messages.ui && hdhSingleTrade.messages.ui.processing) 
                    ? hdhSingleTrade.messages.ui.processing 
                    : 'İşleniyor...';
                btn.textContent = '⏳ ' + processingMsg;
                
                fetch(hdhSingleTrade.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'hdh_reject_offer',
                        offer_id: offerId,
                        nonce: hdhSingleTrade.offerResponseNonce
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.data.message, 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        const errorMsg = data.data.message || (hdhSingleTrade.messages && hdhSingleTrade.messages.ajax && hdhSingleTrade.messages.ajax.generic_error) 
                            ? hdhSingleTrade.messages.ajax.generic_error 
                            : 'Bir hata oluştu.';
                        showToast(errorMsg, 'error');
                        btn.disabled = false;
                        btn.textContent = '❌ Reddet';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const errorMsg = (hdhSingleTrade.messages && hdhSingleTrade.messages.ajax && hdhSingleTrade.messages.ajax.generic_error) 
                        ? hdhSingleTrade.messages.ajax.generic_error 
                        : 'Bir hata oluştu.';
                    showToast(errorMsg, 'error');
                    btn.disabled = false;
                    btn.textContent = '❌ Reddet';
                });
            });
        });
        
        // Load messages
        const messagesContainer = document.getElementById('messages-container');
        if (messagesContainer) {
            loadMessages();
            
            // Poll for new messages every 10 seconds
            setInterval(loadMessages, 10000);
        }
        
        function loadMessages() {
            const listingId = document.querySelector('input[name="listing_id"]').value;
            const offerId = document.querySelector('input[name="offer_id"]').value;
            
            fetch(hdhSingleTrade.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'hdh_load_messages',
                    listing_id: listingId,
                    offer_id: offerId,
                    nonce: hdhSingleTrade.messagingNonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderMessages(data.data.messages);
                }
            })
            .catch(error => {
                console.error('Error loading messages:', error);
            });
        }
        
        function renderMessages(messages) {
            if (messages.length === 0) {
                messagesContainer.innerHTML = '<div class="no-messages">Henüz mesaj yok. İlk mesajı siz gönderin!</div>';
                return;
            }
            
            let html = '';
            messages.forEach(function(msg) {
                const messageClass = msg.is_own ? 'message message-own' : 'message message-other';
                html += '<div class="' + messageClass + '">';
                html += '<div class="message-header">';
                html += '<span class="message-author">' + escapeHtml(msg.author_name) + '</span>';
                html += '<span class="message-date">' + escapeHtml(msg.date) + '</span>';
                html += '</div>';
                html += '<div class="message-content">' + escapeHtml(msg.content) + '</div>';
                html += '</div>';
            });
            
            messagesContainer.innerHTML = html;
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        // Send message form
        const sendMessageForm = document.getElementById('send-message-form');
        if (sendMessageForm) {
            sendMessageForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(sendMessageForm);
                formData.append('action', 'hdh_send_message');
                formData.append('nonce', hdhSingleTrade.messagingNonce);
                
                const submitBtn = sendMessageForm.querySelector('.btn-send-message');
                const messageInput = sendMessageForm.querySelector('#message-input');
                
                submitBtn.disabled = true;
                submitBtn.textContent = '⏳ Gönderiliyor...';
                
                fetch(hdhSingleTrade.ajaxUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        messageInput.value = '';
                        loadMessages();
                        submitBtn.disabled = false;
                        submitBtn.textContent = '📤 Gönder';
                    } else {
                        showToast(data.data.message || 'Mesaj gönderilemedi.', 'error');
                        submitBtn.disabled = false;
                        submitBtn.textContent = '📤 Gönder';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Bir hata oluştu.', 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = '📤 Gönder';
                });
            });
        }
        
        // Confirm exchange button
        const confirmExchangeBtn = document.getElementById('btn-confirm-exchange');
        if (confirmExchangeBtn) {
            confirmExchangeBtn.addEventListener('click', function() {
                if (!confirm('Hediyeleşmeyi tamamladığınızı onaylıyor musunuz?')) {
                    return;
                }
                
                const listingId = document.querySelector('input[name="listing_id"]').value;
                confirmExchangeBtn.disabled = true;
                confirmExchangeBtn.textContent = '⏳ İşleniyor...';
                
                fetch(hdhSingleTrade.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'hdh_confirm_exchange',
                        listing_id: listingId,
                        nonce: hdhSingleTrade.confirmExchangeNonce
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.data.message, 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showToast(data.data.message || 'Bir hata oluştu.', 'error');
                        confirmExchangeBtn.disabled = false;
                        confirmExchangeBtn.textContent = 'Hediyeleşmeyi Tamamladım';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Bir hata oluştu.', 'error');
                    confirmExchangeBtn.disabled = false;
                    confirmExchangeBtn.textContent = 'Hediyeleşmeyi Tamamladım';
                });
            });
        }
        
        // Toast notification function
        function showToast(message, type) {
            const toast = document.createElement('div');
            toast.className = 'trade-toast trade-toast-' + type;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(function() {
                toast.classList.add('show');
            }, 10);
            
            setTimeout(function() {
                toast.classList.remove('show');
                setTimeout(function() {
                    toast.remove();
                }, 300);
            }, 3000);
        }
        
        // Escape HTML helper
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    });
})();

