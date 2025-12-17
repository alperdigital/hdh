(function() {
    'use strict';
    document.addEventListener('DOMContentLoaded', function() {
        // Handle both .btn-create-offer and .btn-make-offer
        const createOfferBtns = document.querySelectorAll('.btn-create-offer, .btn-make-offer');
        createOfferBtns.forEach(function(createOfferBtn) {
            createOfferBtn.addEventListener('click', function() {
                const listingId = this.getAttribute('data-listing-id');
                if (!listingId) { 
                    const invalidMsg = (window.hdhMessages && window.hdhMessages.ajax && window.hdhMessages.ajax.invalid_listing) 
                        ? window.hdhMessages.ajax.invalid_listing 
                        : 'Geçersiz ilan';
                    alert(invalidMsg); 
                    return; 
                }
                const confirmMsg = (window.hdhMessages && window.hdhMessages.ui && window.hdhMessages.ui.confirm_action) 
                    ? window.hdhMessages.ui.confirm_action 
                    : 'Bu ilana teklif yapmak istediğinize emin misiniz?';
                if (!confirm(confirmMsg)) return;
                this.disabled = true;
                const sendingMsg = (window.hdhMessages && window.hdhMessages.ui && window.hdhMessages.ui.sending) 
                    ? window.hdhMessages.ui.sending 
                    : 'Gönderiliyor...';
                this.textContent = sendingMsg;
                const formData = new FormData();
                formData.append('action', 'hdh_create_offer');
                formData.append('listing_id', listingId);
                formData.append('nonce', hdhOffer.nonce);
                fetch(hdhOffer.ajaxUrl, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const message = document.createElement('div');
                        message.textContent = data.data.message;
                        message.style.cssText = 'background: var(--farm-green); color: #FFFFFF; padding: 12px; border-radius: 8px; margin-top: 16px; text-align: center; font-weight: 600;';
                        createOfferBtn.parentNode.insertBefore(message, createOfferBtn.nextSibling);
                        createOfferBtn.style.display = 'none';
                        setTimeout(function() { window.location.reload(); }, 2000);
                    } else {
                        const errorMsg = data.data.message || (window.hdhMessages && window.hdhMessages.ajax && window.hdhMessages.ajax.generic_error) 
                            ? window.hdhMessages.ajax.generic_error 
                            : 'Bir hata oluştu';
                        alert(errorMsg);
                        this.disabled = false;
                        this.textContent = '💬 Teklif Yap';
                    }
                }).catch(error => { 
                    console.error('Error:', error); 
                    const errorMsg = (window.hdhMessages && window.hdhMessages.ajax && window.hdhMessages.ajax.generic_error) 
                        ? window.hdhMessages.ajax.generic_error 
                        : 'Bir hata oluştu';
                    alert(errorMsg); 
                    this.disabled = false; 
                    this.textContent = '💬 Teklif Yap'; 
                });
            });
        });
        document.querySelectorAll('.btn-accept-offer').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const listingId = this.getAttribute('data-listing-id');
                const offerIndex = this.getAttribute('data-offer-index');
                if (!listingId || offerIndex === null) { 
                    const invalidMsg = (window.hdhMessages && window.hdhMessages.ajax && window.hdhMessages.ajax.invalid_parameters) 
                        ? window.hdhMessages.ajax.invalid_parameters 
                        : 'Geçersiz parametreler';
                    alert(invalidMsg); 
                    return; 
                }
                const confirmMsg = (window.hdhMessages && window.hdhMessages.ui && window.hdhMessages.ui.confirm_accept_offer) 
                    ? window.hdhMessages.ui.confirm_accept_offer 
                    : 'Bu teklifi kabul etmek istediğinize emin misiniz?';
                if (!confirm(confirmMsg)) return;
                this.disabled = true;
                const processingMsg = (window.hdhMessages && window.hdhMessages.ui && window.hdhMessages.ui.processing) 
                    ? window.hdhMessages.ui.processing 
                    : 'İşleniyor...';
                this.textContent = processingMsg;
                const formData = new FormData();
                formData.append('action', 'hdh_offer_response');
                formData.append('listing_id', listingId);
                formData.append('offer_index', offerIndex);
                formData.append('action_type', 'accept');
                formData.append('nonce', hdhOffer.nonce);
                fetch(hdhOffer.ajaxUrl, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => { 
                    if (data.success) { 
                        alert(data.data.message); 
                        window.location.reload(); 
                    } else { 
                        const errorMsg = data.data.message || (window.hdhMessages && window.hdhMessages.ajax && window.hdhMessages.ajax.generic_error) 
                            ? window.hdhMessages.ajax.generic_error 
                            : 'Bir hata oluştu';
                        alert(errorMsg); 
                        this.disabled = false; 
                        this.textContent = '✅ Kabul Et'; 
                    } 
                })
                .catch(error => { 
                    console.error('Error:', error); 
                    const errorMsg = (window.hdhMessages && window.hdhMessages.ajax && window.hdhMessages.ajax.generic_error) 
                        ? window.hdhMessages.ajax.generic_error 
                        : 'Bir hata oluştu';
                    alert(errorMsg); 
                    this.disabled = false; 
                    this.textContent = '✅ Kabul Et'; 
                });
            });
        });
        document.querySelectorAll('.btn-reject-offer').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const listingId = this.getAttribute('data-listing-id');
                const offerIndex = this.getAttribute('data-offer-index');
                if (!listingId || offerIndex === null) { 
                    const invalidMsg = (window.hdhMessages && window.hdhMessages.ajax && window.hdhMessages.ajax.invalid_parameters) 
                        ? window.hdhMessages.ajax.invalid_parameters 
                        : 'Geçersiz parametreler';
                    alert(invalidMsg); 
                    return; 
                }
                const confirmMsg = (window.hdhMessages && window.hdhMessages.ui && window.hdhMessages.ui.confirm_reject_offer) 
                    ? window.hdhMessages.ui.confirm_reject_offer 
                    : 'Bu teklifi reddetmek istediğinize emin misiniz?';
                if (!confirm(confirmMsg)) return;
                this.disabled = true;
                const processingMsg = (window.hdhMessages && window.hdhMessages.ui && window.hdhMessages.ui.processing) 
                    ? window.hdhMessages.ui.processing 
                    : 'İşleniyor...';
                this.textContent = processingMsg;
                const formData = new FormData();
                formData.append('action', 'hdh_offer_response');
                formData.append('listing_id', listingId);
                formData.append('offer_index', offerIndex);
                formData.append('action_type', 'reject');
                formData.append('nonce', hdhOffer.nonce);
                fetch(hdhOffer.ajaxUrl, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => { 
                    if (data.success) { 
                        alert(data.data.message); 
                        window.location.reload(); 
                    } else { 
                        const errorMsg = data.data.message || (window.hdhMessages && window.hdhMessages.ajax && window.hdhMessages.ajax.generic_error) 
                            ? window.hdhMessages.ajax.generic_error 
                            : 'Bir hata oluştu';
                        alert(errorMsg); 
                        this.disabled = false; 
                        this.textContent = '❌ Reddet'; 
                    } 
                })
                .catch(error => { 
                    console.error('Error:', error); 
                    const errorMsg = (window.hdhMessages && window.hdhMessages.ajax && window.hdhMessages.ajax.generic_error) 
                        ? window.hdhMessages.ajax.generic_error 
                        : 'Bir hata oluştu';
                    alert(errorMsg); 
                    this.disabled = false; 
                    this.textContent = '❌ Reddet'; 
                });
            });
        });
        // Handle both .btn-complete-exchange and .btn-confirm-exchange
        const completeExchangeBtns = document.querySelectorAll('.btn-complete-exchange, .btn-confirm-exchange');
        completeExchangeBtns.forEach(function(completeExchangeBtn) {
            completeExchangeBtn.addEventListener('click', function() {
                const listingId = this.getAttribute('data-listing-id');
                if (!listingId) { 
                    const invalidMsg = (window.hdhMessages && window.hdhMessages.ajax && window.hdhMessages.ajax.invalid_listing) 
                        ? window.hdhMessages.ajax.invalid_listing 
                        : 'Geçersiz ilan';
                    alert(invalidMsg); 
                    return; 
                }
                const confirmMsg = (window.hdhMessages && window.hdhMessages.ui && window.hdhMessages.ui.confirm_complete_exchange) 
                    ? window.hdhMessages.ui.confirm_complete_exchange 
                    : 'Hediyeleşmeyi tamamladığınızı onaylıyor musunuz? Bu işlem geri alınamaz.';
                if (!confirm(confirmMsg)) return;
                this.disabled = true;
                const processingMsg = (window.hdhMessages && window.hdhMessages.ui && window.hdhMessages.ui.processing) 
                    ? window.hdhMessages.ui.processing 
                    : 'İşleniyor...';
                this.textContent = processingMsg;
                const formData = new FormData();
                formData.append('action', 'hdh_complete_exchange');
                formData.append('listing_id', listingId);
                formData.append('nonce', hdhOffer.nonce);
                fetch(hdhOffer.ajaxUrl, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => { 
                    if (data.success) { 
                        alert(data.data.message); 
                        window.location.reload(); 
                    } else { 
                        const errorMsg = data.data.message || (window.hdhMessages && window.hdhMessages.ajax && window.hdhMessages.ajax.generic_error) 
                            ? window.hdhMessages.ajax.generic_error 
                            : 'Bir hata oluştu';
                        alert(errorMsg); 
                        this.disabled = false; 
                        this.textContent = '✅ Hediyeleşmeyi Onayla'; 
                    } 
                })
                .catch(error => { 
                    console.error('Error:', error); 
                    const errorMsg = (window.hdhMessages && window.hdhMessages.ajax && window.hdhMessages.ajax.generic_error) 
                        ? window.hdhMessages.ajax.generic_error 
                        : 'Bir hata oluştu';
                    alert(errorMsg); 
                    this.disabled = false; 
                    this.textContent = '✅ Hediyeleşmeyi Onayla'; 
                });
            });
        });
    });
})();
