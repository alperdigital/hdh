/**
 * HDH: Mobile Menu Toggle
 * Handles hamburger menu open/close functionality
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.querySelector('.mobile-menu-toggle');
        const navigation = document.querySelector('.cartoon-navigation');
        
        if (!menuToggle || !navigation) {
            return;
        }
        
        // Toggle menu on button click
        menuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            const isExpanded = menuToggle.getAttribute('aria-expanded') === 'true';
            
            menuToggle.setAttribute('aria-expanded', !isExpanded);
            navigation.classList.toggle('menu-open');
            
            // Prevent body scroll when menu is open
            if (!isExpanded) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        });
        
        // Close menu when clicking on a link
        const navLinks = navigation.querySelectorAll('.cartoon-nav-link');
        navLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                menuToggle.setAttribute('aria-expanded', 'false');
                navigation.classList.remove('menu-open');
                document.body.style.overflow = '';
            });
        });
        
        // Close menu when clicking outside (on overlay)
        navigation.addEventListener('click', function(event) {
            // If clicking on the navigation container itself (not on links), close menu
            if (event.target === navigation) {
                menuToggle.setAttribute('aria-expanded', 'false');
                navigation.classList.remove('menu-open');
                document.body.style.overflow = '';
            }
        });
        
        // Close menu on window resize (if resizing to desktop)
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (window.innerWidth > 768) {
                    menuToggle.setAttribute('aria-expanded', 'false');
                    navigation.classList.remove('menu-open');
                    document.body.style.overflow = '';
                }
            }, 250);
        });
    });
})();

