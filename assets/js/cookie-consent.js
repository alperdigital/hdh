/**
 * HDH: Simple Cookie Consent Handler
 */
(function() {
    'use strict';
    
    const STORAGE_KEY = 'hdh_cookie_consent';
    const COOKIE_NAME = 'hdh_cookie_consent';
    const COOKIE_EXPIRY_DAYS = 180;
    
    function setCookie(name, value, days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        const expires = 'expires=' + date.toUTCString();
        document.cookie = name + '=' + value + ';' + expires + ';path=/';
    }
    
    function getCookie(name) {
        const nameEQ = name + '=';
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }
    
    function shouldShowBanner() {
        // Check localStorage
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored === 'accepted' || stored === 'rejected') {
            return false;
        }
        
        // Check cookie
        const cookie = getCookie(COOKIE_NAME);
        if (cookie === 'accepted' || cookie === 'rejected') {
            return false;
        }
        
        return true;
    }
    
    function saveConsent(choice) {
        // Save to localStorage
        localStorage.setItem(STORAGE_KEY, choice);
        
        // Save to cookie
        setCookie(COOKIE_NAME, choice, COOKIE_EXPIRY_DAYS);
        
        // Hide banner
        const banner = document.getElementById('hdh-cookie-consent');
        if (banner) {
            banner.style.display = 'none';
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        if (!shouldShowBanner()) {
            return;
        }
        
        const banner = document.getElementById('hdh-cookie-consent');
        if (!banner) return;
        
        // Show banner
        banner.style.display = 'block';
        
        // Accept button
        const acceptBtn = document.getElementById('hdh-cookie-accept');
        if (acceptBtn) {
            acceptBtn.addEventListener('click', function() {
                saveConsent('accepted');
            });
        }
        
        // Reject button
        const rejectBtn = document.getElementById('hdh-cookie-reject');
        if (rejectBtn) {
            rejectBtn.addEventListener('click', function() {
                saveConsent('rejected');
            });
        }
    });
})();

