(function() {
    'use strict';
    document.addEventListener('DOMContentLoaded', function() {
        const editBtn = document.getElementById('btn-edit-profile');
        const cancelBtn = document.getElementById('btn-cancel-edit');
        const editForm = document.getElementById('profile-edit-form');
        if (!editBtn || !editForm) return;
        editBtn.addEventListener('click', function() {
            editForm.style.display = editForm.style.display === 'none' ? 'block' : 'none';
            if (editForm.style.display === 'block') editForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
        if (cancelBtn) cancelBtn.addEventListener('click', function() { editForm.style.display = 'none'; });
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('updated') === '1') {
            const form = document.getElementById('profile-edit-form-element');
            if (form) {
                const successMsg = document.createElement('div');
                successMsg.textContent = 'Profil başarıyla güncellendi!';
                successMsg.style.cssText = 'background: var(--farm-green); color: #FFFFFF; padding: 12px; border-radius: 8px; margin-bottom: 16px; text-align: center; font-weight: 600;';
                form.parentNode.insertBefore(successMsg, form);
                setTimeout(function() { successMsg.remove(); }, 3000);
            }
        }

        // Handle deactivate listing buttons
        const deactivateButtons = document.querySelectorAll('.btn-deactivate-listing');
        deactivateButtons.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const listingId = this.getAttribute('data-listing-id');
                const listingItem = this.closest('.my-listing-item');
                
                if (!confirm('Bu ilanı pasife almak istediğinize emin misiniz? Pasife alınan ilanlar tekrar aktif edilemez.')) {
                    return;
                }
                
                // Disable button and show loading
                btn.disabled = true;
                btn.textContent = '⏳ İşleniyor...';
                
                // AJAX request
                fetch(hdhProfile.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'hdh_deactivate_listing',
                        listing_id: listingId,
                        nonce: hdhProfile.nonce
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update UI
                        listingItem.classList.remove('listing-active');
                        listingItem.classList.add('listing-inactive');
                        
                        const statusSpan = listingItem.querySelector('.listing-status');
                        if (statusSpan) {
                            statusSpan.classList.remove('status-active');
                            statusSpan.classList.add('status-inactive');
                            statusSpan.textContent = '⏸️ Pasif';
                        }
                        
                        // Remove button
                        btn.remove();
                        
                        // Show success toast
                        showToast('İlan başarıyla pasife alındı.', 'success');
                    } else {
                        showToast(data.data.message || 'Bir hata oluştu.', 'error');
                        btn.disabled = false;
                        btn.textContent = '⏸️ Pasife Al';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Bir hata oluştu. Lütfen tekrar deneyin.', 'error');
                    btn.disabled = false;
                    btn.textContent = '⏸️ Pasife Al';
                });
            });
        });
        
        // Toast notification function
        function showToast(message, type) {
            const toast = document.createElement('div');
            toast.className = 'profile-toast profile-toast-' + type;
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
    });
})();
