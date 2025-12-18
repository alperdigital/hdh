(function() {
    'use strict';
    document.addEventListener('DOMContentLoaded', function() {
        const joinButtons = document.querySelectorAll('.btn-join-lottery');
        joinButtons.forEach(function(btn) {
            if (btn.disabled) return;
            btn.addEventListener('click', function() {
                const lotteryType = this.getAttribute('data-lottery-type');
                const jetonCost = parseInt(this.getAttribute('data-jeton-cost'), 10);
                if (!lotteryType || !jetonCost) { 
                const invalidMsg = (hdhLottery.messages && hdhLottery.messages.ajax && hdhLottery.messages.ajax.invalid_parameters) 
                    ? hdhLottery.messages.ajax.invalid_parameters 
                    : 'Geçersiz parametreler';
                alert(invalidMsg); 
                return; 
            }
            // Confirm dialog removed - direct join
            this.disabled = true;
            const originalText = this.textContent;
            const processingMsg = (hdhLottery.messages && hdhLottery.messages.ui && hdhLottery.messages.ui.processing) 
                ? hdhLottery.messages.ui.processing 
                : 'İşleniyor...';
            this.textContent = processingMsg;
                const formData = new FormData();
                formData.append('action', 'hdh_join_lottery');
                formData.append('lottery_type', lotteryType);
                formData.append('jeton_cost', jetonCost);
                formData.append('nonce', hdhLottery.nonce);
                fetch(hdhLottery.ajaxUrl, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const successMsg = document.createElement('div');
                        successMsg.textContent = data.data.message;
                        successMsg.style.cssText = 'background: var(--farm-green); color: #FFFFFF; padding: 12px; border-radius: 8px; margin-top: 12px; text-align: center; font-weight: 600;';
                        btn.parentNode.insertBefore(successMsg, btn.nextSibling);
                        if (data.data.new_balance !== undefined) {
                            const balanceEl = document.querySelector('.jeton-balance-amount');
                            if (balanceEl) balanceEl.textContent = data.data.new_balance.toLocaleString('tr-TR');
                        }
                        setTimeout(function() { window.location.reload(); }, 2000);
                    } else {
                        alert(data.data.message || 'Bir hata oluştu');
                        this.disabled = false;
                        this.textContent = originalText;
                    }
                }).catch(error => { console.error('Error:', error); alert('Bir hata oluştu'); this.disabled = false; this.textContent = originalText; });
            });
        });
        
        // Handle lottery start button (admin only)
        const startButtons = document.querySelectorAll('.btn-start-lottery');
        startButtons.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const lotteryType = this.getAttribute('data-lottery-type');
                if (!lotteryType) {
                    alert('Geçersiz çekiliş tipi');
                    return;
                }
                
                if (!confirm('Çekilişi başlatmak istediğinize emin misiniz? Bu işlem geri alınamaz.')) {
                    return;
                }
                
                this.disabled = true;
                const originalText = this.textContent;
                this.textContent = 'Başlatılıyor...';
                
                const formData = new FormData();
                formData.append('action', 'hdh_start_lottery');
                formData.append('lottery_type', lotteryType);
                formData.append('nonce', hdhLottery.nonce);
                
                fetch(hdhLottery.ajaxUrl, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Çekiliş başarıyla başlatıldı! Kazanan: ' + (data.data.winner_name || 'Bilinmiyor'));
                        window.location.reload();
                    } else {
                        alert(data.data.message || 'Bir hata oluştu');
                        this.disabled = false;
                        this.textContent = originalText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Bir hata oluştu');
                    this.disabled = false;
                    this.textContent = originalText;
                });
            });
        });
    });
})();
