/**
 * HDH: Firebase Email & Phone Verification
 * Handles Firebase Authentication for email and phone verification
 */

(function() {
    'use strict';
    
    // Firebase will be initialized globally
    let firebaseApp = null;
    let firebaseAuth = null;
    let firebaseConfig = null;
    
    /**
     * Initialize Firebase
     */
    function initFirebase() {
        if (typeof firebase === 'undefined') {
            console.error('Firebase SDK not loaded');
            return false;
        }
        
        // Get config from localized script
        if (typeof hdhFirebase === 'undefined' || !hdhFirebase.config) {
            console.error('Firebase config not found');
            return false;
        }
        
        firebaseConfig = hdhFirebase.config;
        
        // Initialize Firebase App
        if (!firebase.apps.length) {
            firebaseApp = firebase.initializeApp(firebaseConfig);
        } else {
            firebaseApp = firebase.app();
        }
        
        firebaseAuth = firebase.auth();
        
        return true;
    }
    
    /**
     * Send email verification link
     */
    async function sendEmailVerification(user) {
        try {
            await user.sendEmailVerification();
            return { success: true };
        } catch (error) {
            console.error('Email verification error:', error);
            return { 
                success: false, 
                error: error.message || 'E-posta doğrulama linki gönderilemedi' 
            };
        }
    }
    
    /**
     * Reload user to check email verification status
     */
    async function reloadUser(user) {
        try {
            await user.reload();
            return user.emailVerified;
        } catch (error) {
            console.error('Reload user error:', error);
            return false;
        }
    }
    
    /**
     * Send phone verification code
     */
    async function sendPhoneVerificationCode(phoneNumber, recaptchaVerifier) {
        try {
            const confirmationResult = await firebaseAuth.signInWithPhoneNumber(phoneNumber, recaptchaVerifier);
            return { success: true, confirmationResult };
        } catch (error) {
            console.error('Phone verification error:', error);
            return { 
                success: false, 
                error: error.message || 'SMS kodu gönderilemedi' 
            };
        }
    }
    
    /**
     * Verify phone code
     */
    async function verifyPhoneCode(confirmationResult, code) {
        try {
            const result = await confirmationResult.confirm(code);
            return { success: true, user: result.user };
        } catch (error) {
            console.error('Phone code verification error:', error);
            return { 
                success: false, 
                error: error.message || 'Doğrulama kodu hatalı' 
            };
        }
    }
    
    /**
     * Get Firebase ID token
     */
    async function getIdToken(user) {
        try {
            const token = await user.getIdToken();
            return { success: true, token };
        } catch (error) {
            console.error('Get ID token error:', error);
            return { 
                success: false, 
                error: error.message || 'Token alınamadı' 
            };
        }
    }
    
    /**
     * Email Verification Handler
     */
    document.addEventListener('DOMContentLoaded', function() {
        const emailVerifyBtn = document.getElementById('btn-firebase-email-verify');
        const emailCheckBtn = document.getElementById('btn-firebase-email-check');
        const emailMessage = document.getElementById('firebase-email-message');
        
        if (!emailVerifyBtn) return;
        
        // Initialize Firebase
        if (!initFirebase()) {
            if (emailMessage) {
                emailMessage.textContent = 'Firebase yapılandırması bulunamadı.';
                emailMessage.className = 'verification-message verification-error';
                emailMessage.style.display = 'block';
            }
            return;
        }
        
        // Send email verification
        emailVerifyBtn.addEventListener('click', async function() {
            const btn = this;
            const originalText = btn.textContent;
            
            btn.disabled = true;
            btn.textContent = 'Gönderiliyor...';
            
            try {
                // Get current user's email
                const userEmail = hdhFirebase.userEmail;
                if (!userEmail) {
                    throw new Error('E-posta adresi bulunamadı');
                }
                
                // Create user with email (or sign in if exists)
                let user;
                try {
                    // Try to sign in with email (passwordless)
                    // Note: For passwordless email, we need to use email link authentication
                    // For now, we'll use a different approach: send verification to existing email
                    
                    // Alternative: Use Firebase Admin to send verification email
                    // Or use Firebase's sendEmailVerification after creating a temporary account
                    
                    // For WordPress integration, we'll send verification via Firebase Admin SDK
                    // But for client-side, we can use Firebase's email link auth
                    
                    // Create a temporary auth state
                    const actionCodeSettings = {
                        url: window.location.origin + '/profil?email_verified=true',
                        handleCodeInApp: true,
                    };
                    
                    // Send verification email via backend
                    const response = await fetch(hdhFirebase.ajaxUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'hdh_send_firebase_email_verification',
                            nonce: hdhFirebase.nonce,
                            email: userEmail
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showMessage(emailMessage, 'success', 'Doğrulama e-postası gönderildi. E-posta kutunuzu kontrol edin.');
                    } else {
                        throw new Error(data.data.message || 'E-posta gönderilemedi');
                    }
                } catch (error) {
                    showMessage(emailMessage, 'error', error.message || 'Bir hata oluştu');
                }
            } catch (error) {
                showMessage(emailMessage, 'error', error.message || 'Bir hata oluştu');
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        });
        
        // Check email verification status
        if (emailCheckBtn) {
            emailCheckBtn.addEventListener('click', async function() {
                const btn = this;
                const originalText = btn.textContent;
                
                btn.disabled = true;
                btn.textContent = 'Kontrol ediliyor...';
                
                try {
                    // Get current Firebase user
                    const currentUser = firebaseAuth.currentUser;
                    
                    if (!currentUser) {
                        throw new Error('Firebase kullanıcısı bulunamadı');
                    }
                    
                    // Reload user to check verification status
                    const isVerified = await reloadUser(currentUser);
                    
                    if (isVerified) {
                        // Get ID token and verify on backend
                        const tokenResult = await getIdToken(currentUser);
                        
                        if (tokenResult.success) {
                            // Send to backend
                            const response = await fetch(hdhFirebase.ajaxUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: new URLSearchParams({
                                    action: 'hdh_verify_email_firebase',
                                    nonce: hdhFirebase.nonce,
                                    id_token: tokenResult.token,
                                    firebase_uid: currentUser.uid
                                })
                            });
                            
                            const data = await response.json();
                            
                            if (data.success) {
                                showMessage(emailMessage, 'success', data.data.message || 'E-posta doğrulandı!');
                                setTimeout(() => {
                                    window.location.reload();
                                }, 2000);
                            } else {
                                throw new Error(data.data.message || 'Doğrulama başarısız');
                            }
                        } else {
                            throw new Error('Token alınamadı');
                        }
                    } else {
                        showMessage(emailMessage, 'error', 'E-posta henüz doğrulanmamış. Lütfen e-posta kutunuzu kontrol edin.');
                    }
                } catch (error) {
                    showMessage(emailMessage, 'error', error.message || 'Bir hata oluştu');
                } finally {
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            });
        }
    });
    
    /**
     * Phone Verification Handler
     */
    document.addEventListener('DOMContentLoaded', function() {
        const phoneVerifyBtn = document.getElementById('btn-firebase-phone-verify');
        const phoneCodeInput = document.getElementById('firebase-phone-code');
        const phoneNumberInput = document.getElementById('firebase-phone-number');
        const phoneVerifyCodeBtn = document.getElementById('btn-firebase-phone-verify-code');
        const phoneMessage = document.getElementById('firebase-phone-message');
        const recaptchaContainer = document.getElementById('firebase-recaptcha-container');
        
        if (!phoneVerifyBtn) return;
        
        // Initialize Firebase
        if (!initFirebase()) {
            if (phoneMessage) {
                phoneMessage.textContent = 'Firebase yapılandırması bulunamadı.';
                phoneMessage.className = 'verification-message verification-error';
                phoneMessage.style.display = 'block';
            }
            return;
        }
        
        let recaptchaVerifier = null;
        let confirmationResult = null;
        
        // Initialize reCAPTCHA
        if (recaptchaContainer) {
            recaptchaVerifier = new firebase.auth.RecaptchaVerifier(recaptchaContainer, {
                'size': 'normal',
                'callback': function(response) {
                    // reCAPTCHA solved
                },
                'expired-callback': function() {
                    // reCAPTCHA expired
                }
            });
        }
        
        // Send phone verification code
        phoneVerifyBtn.addEventListener('click', async function() {
            const btn = this;
            const originalText = btn.textContent;
            const phoneNumber = phoneNumberInput ? phoneNumberInput.value.trim() : '';
            
            if (!phoneNumber) {
                showMessage(phoneMessage, 'error', 'Lütfen telefon numaranızı girin.');
                return;
            }
            
            // Format phone number (add country code if needed)
            const formattedPhone = phoneNumber.startsWith('+') ? phoneNumber : '+90' + phoneNumber.replace(/^0/, '');
            
            btn.disabled = true;
            btn.textContent = 'Gönderiliyor...';
            
            try {
                const result = await sendPhoneVerificationCode(formattedPhone, recaptchaVerifier);
                
                if (result.success) {
                    confirmationResult = result.confirmationResult;
                    showMessage(phoneMessage, 'success', 'SMS kodu gönderildi. Lütfen telefonunuzu kontrol edin.');
                    
                    // Show code input
                    if (phoneCodeInput) {
                        phoneCodeInput.parentElement.style.display = 'block';
                        phoneCodeInput.focus();
                    }
                    if (phoneVerifyCodeBtn) {
                        phoneVerifyCodeBtn.style.display = 'block';
                    }
                } else {
                    throw new Error(result.error);
                }
            } catch (error) {
                showMessage(phoneMessage, 'error', error.message || 'SMS kodu gönderilemedi');
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        });
        
        // Verify phone code
        if (phoneVerifyCodeBtn) {
            phoneVerifyCodeBtn.addEventListener('click', async function() {
                const btn = this;
                const originalText = btn.textContent;
                const code = phoneCodeInput ? phoneCodeInput.value.trim() : '';
                
                if (!code) {
                    showMessage(phoneMessage, 'error', 'Lütfen doğrulama kodunu girin.');
                    return;
                }
                
                if (!confirmationResult) {
                    showMessage(phoneMessage, 'error', 'Önce SMS kodu gönderin.');
                    return;
                }
                
                btn.disabled = true;
                btn.textContent = 'Doğrulanıyor...';
                
                try {
                    const result = await verifyPhoneCode(confirmationResult, code);
                    
                    if (result.success) {
                        // Get ID token
                        const tokenResult = await getIdToken(result.user);
                        
                        if (tokenResult.success) {
                            // Send to backend
                            const response = await fetch(hdhFirebase.ajaxUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: new URLSearchParams({
                                    action: 'hdh_verify_phone_firebase',
                                    nonce: hdhFirebase.nonce,
                                    id_token: tokenResult.token,
                                    phone_number: phoneNumberInput.value.trim(),
                                    firebase_uid: result.user.uid
                                })
                            });
                            
                            const data = await response.json();
                            
                            if (data.success) {
                                showMessage(phoneMessage, 'success', data.data.message || 'Telefon doğrulandı!');
                                setTimeout(() => {
                                    window.location.reload();
                                }, 2000);
                            } else {
                                throw new Error(data.data.message || 'Doğrulama başarısız');
                            }
                        } else {
                            throw new Error('Token alınamadı');
                        }
                    } else {
                        throw new Error(result.error);
                    }
                } catch (error) {
                    showMessage(phoneMessage, 'error', error.message || 'Doğrulama kodu hatalı');
                    if (phoneCodeInput) {
                        phoneCodeInput.value = '';
                        phoneCodeInput.focus();
                    }
                } finally {
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            });
        }
    });
    
    function showMessage(element, type, message) {
        if (!element) return;
        
        element.className = 'verification-message verification-' + type;
        element.textContent = message;
        element.style.display = 'block';
        
        if (type === 'success') {
            setTimeout(() => {
                element.style.display = 'none';
            }, 5000);
        }
    }
    
    // Export functions for global use
    window.hdhFirebaseAuth = {
        initFirebase,
        sendEmailVerification,
        sendPhoneVerificationCode,
        verifyPhoneCode,
        getIdToken,
    };
})();

