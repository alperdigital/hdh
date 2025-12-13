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
    });
})();
