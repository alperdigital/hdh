(function() {
    'use strict';
    document.addEventListener('DOMContentLoaded', function() {
        const claimBtn = document.querySelector('.btn-claim-daily');
        if (claimBtn) {
            claimBtn.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                if (!userId) return;
                this.disabled = true;
                this.textContent = 'İşleniyor...';
                const formData = new FormData();
                formData.append('action', 'hdh_claim_daily_jeton');
                formData.append('user_id', userId);
                formData.append('nonce', hdhTasks.nonce);
                fetch(hdhTasks.ajaxUrl, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('+1 Bilet kazandınız! 🎟️', 'success');
                        this.parentNode.classList.add('task-completed');
                        this.remove();
                        const status = document.createElement('span');
                        status.className = 'task-status';
                        status.textContent = '✅ Tamamlandı';
                        this.parentNode.appendChild(status);
                        if (data.data.new_balance !== undefined) {
                            const balanceEl = document.querySelector('.jeton-balance');
                            if (balanceEl) balanceEl.textContent = data.data.new_balance.toLocaleString('tr-TR');
                        }
                    } else {
                        showToast(data.data.message || 'Bir hata oluştu', 'error');
                        this.disabled = false;
                        this.textContent = 'Al';
                    }
                }).catch(error => { console.error('Error:', error); showToast('Bir hata oluştu', 'error'); this.disabled = false; this.textContent = 'Al'; });
            });
        }
    });
    function showToast(message, type) {
        const toast = document.createElement('div');
        toast.textContent = message;
        toast.style.cssText = 'position: fixed; bottom: 100px; left: 50%; transform: translateX(-50%); background: ' + (type === 'success' ? 'var(--farm-green)' : '#dc3545') + '; color: #FFFFFF; padding: 14px 24px; border-radius: 10px; font-weight: 600; z-index: 10000; box-shadow: 0 4px 12px rgba(0,0,0,0.2); max-width: 90%; text-align: center;';
        document.body.appendChild(toast);
        setTimeout(function() { toast.style.opacity = '1'; toast.style.transform = 'translateX(-50%) translateY(0)'; }, 10);
        setTimeout(function() { toast.style.opacity = '0'; toast.style.transform = 'translateX(-50%) translateY(20px)'; setTimeout(function() { toast.remove(); }, 300); }, 3000);
    }
})();
