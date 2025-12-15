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
     * NOTE: Phone verification is disabled - phone is optional, no rewards
     */
    // Phone verification functions removed
    
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
        const verificationActions = document.querySelector('.verification-actions');
        
        // Check if email is already verified (from PHP)
        const verificationBadge = document.querySelector('.verification-badge.verified');
        if (verificationBadge) {
            // Email is verified - hide all verification buttons
            if (emailVerifyBtn) {
                emailVerifyBtn.style.display = 'none';
            }
            if (emailCheckBtn) {
                emailCheckBtn.style.display = 'none';
            }
            if (verificationActions) {
                verificationActions.style.display = 'none';
            }
            return; // Exit early if already verified
        }
        
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
                
                // Create a temporary Firebase user account for email verification
                // Note: We'll use Firebase's email link authentication
                // First, create a user account with email (passwordless)
                
                try {
                    // Create user with email (if not exists) or sign in
                    let userCredential;
                    
                    // Try to sign in anonymously first, then link email
                    // Or create user with email/password (temporary password)
                    // For WordPress integration, we'll use a simpler approach:
                    // Create a temporary account, send verification, then link to WordPress user
                    
                    // Generate a temporary password
                    const tempPassword = Math.random().toString(36).slice(-12) + Math.random().toString(36).slice(-12);
                    
                    try {
                        // Try to sign in with email (if account exists)
                        userCredential = await firebaseAuth.signInWithEmailAndPassword(userEmail, tempPassword);
                    } catch (error) {
                        // If account doesn't exist, create it
                        if (error.code === 'auth/user-not-found' || error.code === 'auth/wrong-password') {
                            userCredential = await firebaseAuth.createUserWithEmailAndPassword(userEmail, tempPassword);
                        } else {
                            throw error;
                        }
                    }
                    
                    const user = userCredential.user;
                    
                    // Send email verification
                    await user.sendEmailVerification({
                        url: window.location.origin + '/profil?email_verified=true',
                        handleCodeInApp: true,
                    });
                    
                    // Log to backend
                    await fetch(hdhFirebase.ajaxUrl, {
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
                    
                    showMessage(emailMessage, 'success', 'Doğrulama e-postası gönderildi. E-posta kutunuzu kontrol edin.');
                    
                    // Show check button
                    if (emailCheckBtn) {
                        emailCheckBtn.style.display = 'block';
                    }
                } catch (error) {
                    console.error('Firebase email verification error:', error);
                    showMessage(emailMessage, 'error', error.message || 'E-posta gönderilemedi. Lütfen tekrar deneyin.');
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
                                
                                // Hide verification buttons and actions immediately
                                if (emailVerifyBtn) {
                                    emailVerifyBtn.style.display = 'none';
                                    emailVerifyBtn.disabled = true;
                                }
                                if (emailCheckBtn) {
                                    emailCheckBtn.style.display = 'none';
                                    emailCheckBtn.disabled = true;
                                }
                                const verificationActions = document.querySelector('.verification-actions');
                                if (verificationActions) {
                                    verificationActions.style.display = 'none';
                                }
                                
                                // Reload page after 2 seconds to show updated status
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
     * NOTE: Phone verification is disabled - phone is optional, no rewards
     */
    // Phone verification code removed - phone verification is disabled
    
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
        getIdToken,
    };
})();

