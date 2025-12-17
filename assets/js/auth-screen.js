/**
 * HDH: Auth Screen (Login/Register) - Enhanced with Validation
 * Handles tab switching, password visibility, and form validation
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        // Tab Switching
        initTabSwitching();
        
        // Password Toggle
        initPasswordToggle();
        
        // Form Validation
        initFormValidation();
        
        // Auto-switch tabs based on errors
        handleErrorTabSwitching();
        
        // Auto-dismiss success messages
        autoDismissMessages();
    });
    
    /**
     * Initialize tab switching
     */
    function initTabSwitching() {
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
    }
    
    /**
     * Initialize password toggle
     */
    function initPasswordToggle() {
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
                        toggle.setAttribute('aria-label', 'Şifreyi gizle');
                    } else {
                        passwordInput.type = 'password';
                        showIcon.style.display = 'inline';
                        hideIcon.style.display = 'none';
                        toggle.setAttribute('aria-label', 'Şifreyi göster');
                    }
                }
            });
        });
    }
    
    /**
     * Initialize form validation
     */
    function initFormValidation() {
        // Register form validation
        const registerForm = document.getElementById('register-form');
        if (registerForm) {
            // Real-time validation
            const farmNameInput = document.getElementById('farm_name');
            const emailInput = document.getElementById('user_email');
            const farmTagInput = document.getElementById('farm_tag');
            const phoneInput = document.getElementById('phone_number');
            const passwordInput = document.getElementById('user_pass');
            
            if (farmNameInput) {
                farmNameInput.addEventListener('blur', function() {
                    validateFarmName(this);
                });
                farmNameInput.addEventListener('input', function() {
                    clearFieldError(this);
                });
            }
            
            if (emailInput) {
                emailInput.addEventListener('blur', function() {
                    validateEmail(this);
                });
                emailInput.addEventListener('input', function() {
                    clearFieldError(this);
                });
            }
            
            if (farmTagInput) {
                farmTagInput.addEventListener('blur', function() {
                    validateFarmTag(this);
                });
                farmTagInput.addEventListener('input', function() {
                    clearFieldError(this);
                    // Auto-format farm tag
                    formatFarmTag(this);
                });
            }
            
            if (phoneInput) {
                phoneInput.addEventListener('blur', function() {
                    if (this.value) {
                        validatePhone(this);
                    }
                });
                phoneInput.addEventListener('input', function() {
                    clearFieldError(this);
                });
            }
            
            if (passwordInput) {
                passwordInput.addEventListener('blur', function() {
                    validatePassword(this);
                });
                passwordInput.addEventListener('input', function() {
                    clearFieldError(this);
                    updatePasswordStrength(this);
                });
            }
            
            // Form submit validation
            registerForm.addEventListener('submit', function(e) {
                let isValid = true;
                
                if (farmNameInput && !validateFarmName(farmNameInput)) isValid = false;
                if (emailInput && !validateEmail(emailInput)) isValid = false;
                if (farmTagInput && !validateFarmTag(farmTagInput)) isValid = false;
                if (phoneInput && phoneInput.value && !validatePhone(phoneInput)) isValid = false;
                if (passwordInput && !validatePassword(passwordInput)) isValid = false;
                
                if (!isValid) {
                    e.preventDefault();
                    // Scroll to first error
                    const firstError = registerForm.querySelector('.auth-field.has-error');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });
        }
        
        // Login form validation
        const loginForm = document.getElementById('login-form');
        if (loginForm) {
            loginForm.addEventListener('submit', function(e) {
                const usernameInput = document.getElementById('log');
                const passwordInput = document.getElementById('pwd');
                
                let isValid = true;
                
                if (usernameInput && !usernameInput.value.trim()) {
                    showFieldError(usernameInput, 'Lütfen kullanıcı adınızı girin');
                    isValid = false;
                }
                
                if (passwordInput && !passwordInput.value) {
                    showFieldError(passwordInput, 'Lütfen şifrenizi girin');
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                }
            });
        }
    }
    
    /**
     * Validate farm name
     */
    function validateFarmName(input) {
        const value = input.value.trim();
        
        if (!value) {
            showFieldError(input, 'Çiftlik adı gereklidir');
            return false;
        }
        
        if (value.length < 3) {
            showFieldError(input, 'Çiftlik adı en az 3 karakter olmalıdır');
            return false;
        }
        
        if (value.length > 50) {
            showFieldError(input, 'Çiftlik adı en fazla 50 karakter olabilir');
            return false;
        }
        
        clearFieldError(input);
        return true;
    }
    
    /**
     * Validate email
     */
    function validateEmail(input) {
        const value = input.value.trim();
        
        if (!value) {
            showFieldError(input, 'E-posta adresi gereklidir');
            return false;
        }
        
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            showFieldError(input, 'Geçerli bir e-posta adresi girin (örnek: ornek@email.com)');
            return false;
        }
        
        clearFieldError(input);
        return true;
    }
    
    /**
     * Validate farm tag
     */
    function validateFarmTag(input) {
        const value = input.value.trim().toUpperCase();
        
        if (!value) {
            showFieldError(input, 'Çiftlik etiketi gereklidir');
            return false;
        }
        
        // Farm tag format: #ABC123 or #ABCDEF (6-7 characters including #)
        const farmTagRegex = /^#[A-Z0-9]{5,6}$/;
        if (!farmTagRegex.test(value)) {
            showFieldError(input, 'Çiftlik etiketi #ABC123 formatında olmalıdır (# ile başlamalı, 5-6 harf/rakam)');
            return false;
        }
        
        clearFieldError(input);
        return true;
    }
    
    /**
     * Format farm tag as user types
     */
    function formatFarmTag(input) {
        let value = input.value.toUpperCase();
        
        // Add # if not present
        if (value && !value.startsWith('#')) {
            value = '#' + value;
        }
        
        // Remove invalid characters
        value = value.replace(/[^#A-Z0-9]/g, '');
        
        // Limit length
        if (value.length > 7) {
            value = value.substring(0, 7);
        }
        
        input.value = value;
    }
    
    /**
     * Validate phone number
     */
    function validatePhone(input) {
        const value = input.value.trim();
        
        if (!value) {
            return true; // Optional field
        }
        
        // Remove spaces and special characters for validation
        const cleanPhone = value.replace(/[\s\-\(\)]/g, '');
        
        // Turkish phone: +90 5XX XXX XX XX or 05XX XXX XX XX
        const phoneRegex = /^(\+90|0)?5\d{9}$/;
        if (!phoneRegex.test(cleanPhone)) {
            showFieldError(input, 'Geçerli bir telefon numarası girin (örnek: +90 5XX XXX XX XX)');
            return false;
        }
        
        clearFieldError(input);
        return true;
    }
    
    /**
     * Validate password
     */
    function validatePassword(input) {
        const value = input.value;
        
        if (!value) {
            showFieldError(input, 'Şifre gereklidir');
            return false;
        }
        
        if (value.length < 6) {
            showFieldError(input, 'Şifre en az 6 karakter olmalıdır');
            return false;
        }
        
        if (value.length > 100) {
            showFieldError(input, 'Şifre en fazla 100 karakter olabilir');
            return false;
        }
        
        clearFieldError(input);
        return true;
    }
    
    /**
     * Update password strength indicator
     */
    function updatePasswordStrength(input) {
        const value = input.value;
        let strength = 0;
        let strengthText = '';
        let strengthClass = '';
        
        if (value.length >= 6) strength++;
        if (value.length >= 10) strength++;
        if (/[a-z]/.test(value) && /[A-Z]/.test(value)) strength++;
        if (/\d/.test(value)) strength++;
        if (/[^a-zA-Z0-9]/.test(value)) strength++;
        
        if (strength <= 2) {
            strengthText = 'Zayıf';
            strengthClass = 'weak';
        } else if (strength <= 3) {
            strengthText = 'Orta';
            strengthClass = 'medium';
        } else {
            strengthText = 'Güçlü';
            strengthClass = 'strong';
        }
        
        // Show/update strength indicator
        let strengthIndicator = input.parentElement.parentElement.querySelector('.password-strength');
        if (!strengthIndicator && value.length > 0) {
            strengthIndicator = document.createElement('div');
            strengthIndicator.className = 'password-strength';
            input.parentElement.parentElement.appendChild(strengthIndicator);
        }
        
        if (strengthIndicator) {
            if (value.length === 0) {
                strengthIndicator.remove();
            } else {
                strengthIndicator.className = 'password-strength ' + strengthClass;
                const strengthLabel = (hdhAuth.messages && hdhAuth.messages.auth && hdhAuth.messages.auth.password_strength_label) 
                    ? hdhAuth.messages.auth.password_strength_label 
                    : 'Şifre gücü: ';
                strengthIndicator.textContent = strengthLabel + strengthText;
            }
        }
    }
    
    /**
     * Show field error
     */
    function showFieldError(input, message) {
        const field = input.closest('.auth-field');
        if (!field) return;
        
        field.classList.add('has-error');
        input.setAttribute('aria-invalid', 'true');
        
        // Remove existing error
        const existingError = field.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
        
        // Add new error
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.setAttribute('role', 'alert');
        errorDiv.textContent = message;
        
        // Insert after input or password wrapper
        const wrapper = input.closest('.auth-password-wrapper');
        if (wrapper) {
            wrapper.parentNode.insertBefore(errorDiv, wrapper.nextSibling);
        } else {
            input.parentNode.insertBefore(errorDiv, input.nextSibling);
        }
    }
    
    /**
     * Clear field error
     */
    function clearFieldError(input) {
        const field = input.closest('.auth-field');
        if (!field) return;
        
        field.classList.remove('has-error');
        input.removeAttribute('aria-invalid');
        
        const error = field.querySelector('.field-error');
        if (error) {
            error.remove();
        }
    }
    
    /**
     * Handle error tab switching
     */
    function handleErrorTabSwitching() {
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
    }
    
    /**
     * Auto-dismiss success messages
     */
    function autoDismissMessages() {
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
    }
})();
