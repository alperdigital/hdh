(function() {
    'use strict';
    document.addEventListener('DOMContentLoaded', function() {
        const joinButtons = document.querySelectorAll('.btn-join-lottery');
        joinButtons.forEach(function(btn) {
            if (btn.disabled) return;
            btn.addEventListener('click', function() {
                const lotteryType = this.getAttribute('data-lottery-type');
                const jetonCost = parseInt(this.getAttribute('data-jeton-cost'), 10);
                if (!lotteryType || !jetonCost) { alert('Geçersiz parametreler'); return; }
                if (!confirm('Çekilişe katılmak için ' + jetonCost + ' bilet harcanacak. Devam etmek istiyor musunuz?')) return;
                this.disabled = true;
                const originalText = this.textContent;
                this.textContent = 'İşleniyor...';
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
    });
})();
