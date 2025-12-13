/**
 * HDH: Auth Screen (Login/Register)
 * Handles tab switching and password visibility toggle
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        // Tab Switching
        const authTabs = document.querySelectorAll('.auth-tab');
        const authContainers = document.querySelectorAll('.auth-form-container');
        
        authTabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                const targetTab = this.getAttribute('data-tab');
                
                // Remove active class from all tabs and containers
                authTabs.forEach(function(t) {
                    t.classList.remove('active');
                });
                authContainers.forEach(function(c) {
                    c.classList.remove('active');
                });
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Show corresponding container
                const targetContainer = document.getElementById(targetTab + '-form-container');
                if (targetContainer) {
                    targetContainer.classList.add('active');
                }
            });
        });
        
        // Password Toggle
        const passwordToggles = document.querySelectorAll('.auth-password-toggle');
        
        passwordToggles.forEach(function(toggle) {
            toggle.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const passwordInput = document.getElementById(targetId);
                const showIcon = this.querySelector('.toggle-show');
                const hideIcon = this.querySelector('.toggle-hide');
                
                if (passwordInput) {
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        showIcon.style.display = 'none';
                        hideIcon.style.display = 'inline';
                    } else {
                        passwordInput.type = 'password';
                        showIcon.style.display = 'inline';
                        hideIcon.style.display = 'none';
                    }
                }
            });
        });
        
        // Terms Checkbox Validation (Register Form)
        const termsCheckbox = document.getElementById('accept_terms');
        const registerSubmit = document.getElementById('register-submit');
        
        if (termsCheckbox && registerSubmit) {
            function toggleRegisterButton() {
                registerSubmit.disabled = !termsCheckbox.checked;
            }
            
            termsCheckbox.addEventListener('change', toggleRegisterButton);
            toggleRegisterButton(); // Initial check
        }
        
        // Auto-switch tabs based on errors
        const urlParams = new URLSearchParams(window.location.search);
        
        // Switch to register tab if registration error exists
        if (urlParams.get('registration_error')) {
            const registerTab = document.querySelector('.auth-tab[data-tab="register"]');
            if (registerTab) {
                registerTab.click();
            }
        }
        
        // Switch to login tab if login error exists
        if (urlParams.get('login_error')) {
            const loginTab = document.querySelector('.auth-tab[data-tab="login"]');
            if (loginTab) {
                loginTab.click();
            }
        }
        
        // Auto-dismiss success messages after 5 seconds
        const successMessages = document.querySelectorAll('.auth-message.auth-success');
        successMessages.forEach(function(message) {
            setTimeout(function() {
                message.style.transition = 'opacity 0.3s ease';
                message.style.opacity = '0';
                setTimeout(function() {
                    message.remove();
                }, 300);
            }, 5000);
        });
    });
})();

