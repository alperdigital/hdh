/**
 * HDH: Email Verification JavaScript
 */

(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        const sendBtn = document.getElementById('btn-send-email-code');
        const verifyBtn = document.getElementById('btn-verify-email-code');
        const codeForm = document.getElementById('email-code-form');
        const codeInput = document.getElementById('email-verification-code');
        const messageDiv = document.getElementById('email-verification-message');
        const verificationActions = document.querySelector('.verification-actions');
        
        // Check if email is already verified (from PHP)
        const verificationBadge = document.querySelector('.verification-badge.verified');
        if (verificationBadge) {
            // Email is verified - hide all verification buttons and forms
            if (sendBtn) {
                sendBtn.style.display = 'none';
            }
            if (verifyBtn) {
                verifyBtn.style.display = 'none';
            }
            if (codeForm) {
                codeForm.style.display = 'none';
            }
            if (verificationActions) {
                verificationActions.style.display = 'none';
            }
            return; // Exit early if already verified
        }
        
        if (!sendBtn || !verifyBtn) return;
        
        // Send verification code
        sendBtn.addEventListener('click', function() {
            const btn = this;
            const originalText = btn.textContent;
            
            btn.disabled = true;
            btn.textContent = 'Gönderiliyor...';
            
            fetch(hdhProfile.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'hdh_send_email_verification_code',
                    nonce: hdhProfile.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('success', data.data.message || 'Doğrulama kodu e-posta adresinize gönderildi.');
                    codeForm.style.display = 'block';
                    codeInput.focus();
                } else {
                    showMessage('error', data.data.message || 'Bir hata oluştu. Lütfen tekrar deneyin.');
                }
            })
            .catch(error => {
                showMessage('error', 'Bir hata oluştu. Lütfen tekrar deneyin.');
                console.error('Error:', error);
            })
            .finally(() => {
                btn.disabled = false;
                btn.textContent = originalText;
            });
        });
        
        // Verify code
        verifyBtn.addEventListener('click', function() {
            const code = codeInput.value.trim();
            
            if (!code || code.length !== 6) {
                showMessage('error', 'Lütfen 6 haneli doğrulama kodunu girin.');
                codeInput.focus();
                return;
            }
            
            const btn = this;
            const originalText = btn.textContent;
            
            btn.disabled = true;
            btn.textContent = 'Doğrulanıyor...';
            
            fetch(hdhProfile.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'hdh_verify_email_code',
                    nonce: hdhProfile.nonce,
                    code: code
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('success', data.data.message || 'E-posta adresiniz başarıyla doğrulandı!');
                    
                    // Hide verification buttons and form immediately
                    if (sendBtn) {
                        sendBtn.style.display = 'none';
                        sendBtn.disabled = true;
                    }
                    if (verifyBtn) {
                        verifyBtn.style.display = 'none';
                        verifyBtn.disabled = true;
                    }
                    if (codeForm) {
                        codeForm.style.display = 'none';
                    }
                    if (codeInput) {
                        codeInput.disabled = true;
                    }
                    if (verificationActions) {
                        verificationActions.style.display = 'none';
                    }
                    
                    // Reload page after 2 seconds to show updated status
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showMessage('error', data.data.message || 'Doğrulama kodu hatalı. Lütfen tekrar deneyin.');
                    codeInput.value = '';
                    codeInput.focus();
                }
            })
            .catch(error => {
                showMessage('error', 'Bir hata oluştu. Lütfen tekrar deneyin.');
                console.error('Error:', error);
            })
            .finally(() => {
                btn.disabled = false;
                btn.textContent = originalText;
            });
        });
        
        // Auto-format code input (numbers only)
        if (codeInput) {
            codeInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length === 6) {
                    verifyBtn.focus();
                }
            });
            
            // Enter key to verify
            codeInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && this.value.length === 6) {
                    verifyBtn.click();
                }
            });
        }
        
        function showMessage(type, message) {
            if (!messageDiv) return;
            
            messageDiv.className = 'verification-message verification-' + type;
            messageDiv.textContent = message;
            messageDiv.style.display = 'block';
            
            // Auto-hide after 5 seconds for success messages
            if (type === 'success') {
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 5000);
            }
        }
    });
})();

