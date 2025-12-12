/**
 * HDH: Bottom Navigation
 * Handles bottom navigation interactions and active state management
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        const bottomNav = document.getElementById('bottom-navigation');
        if (!bottomNav) {
            return;
        }

        const navItems = bottomNav.querySelectorAll('.bottom-nav-item');
        
        // Get current page URL to determine active item
        const currentPath = window.location.pathname;
        const currentHash = window.location.hash;
        
        /**
         * Set active state for navigation item
         */
        function setActiveItem(item) {
            // Remove active class from all items
            navItems.forEach(function(navItem) {
                navItem.classList.remove('active');
            });
            
            // Add active class to clicked item
            if (item) {
                item.classList.add('active');
            }
        }
        
        /**
         * Determine which nav item should be active based on current page
         */
        function determineActiveItem() {
            // Check if we're on create trade page
            if (currentHash === '#create-trade' || currentPath.includes('create')) {
                const createItem = bottomNav.querySelector('[data-nav="create"]');
                if (createItem) {
                    setActiveItem(createItem);
                    return;
                }
            }
            
            // Check if we're on search page
            if (currentPath.includes('search') || currentPath === '/' && !currentHash) {
                const searchItem = bottomNav.querySelector('[data-nav="search"]');
                if (searchItem) {
                    setActiveItem(searchItem);
                    return;
                }
            }
            
            // Default: set first item (Ara) as active
            if (navItems.length > 0) {
                setActiveItem(navItems[0]);
            }
        }
        
        /**
         * Handle navigation item clicks
         */
        navItems.forEach(function(item) {
            item.addEventListener('click', function(e) {
                // Set active state immediately for better UX
                setActiveItem(item);
                
                // For center button, scroll to create trade section if on homepage
                if (item.classList.contains('bottom-nav-center')) {
                    const href = item.getAttribute('href');
                    if (href && href.includes('#create-trade')) {
                        e.preventDefault();
                        const targetElement = document.getElementById('create-trade');
                        if (targetElement) {
                            // Smooth scroll to create trade section
                            targetElement.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                            // Update URL without reload
                            if (history.pushState) {
                                history.pushState(null, null, '#create-trade');
                            }
                        } else {
                            // If element not found, navigate normally
                            window.location.href = href;
                        }
                        return;
                    }
                }
                
                // For other items, allow normal navigation
                // Active state is already set above
            });
        });
        
        /**
         * Handle hash changes (e.g., when scrolling to #create-trade)
         */
        window.addEventListener('hashchange', function() {
            const hash = window.location.hash;
            if (hash === '#create-trade') {
                const createItem = bottomNav.querySelector('[data-nav="create"]');
                if (createItem) {
                    setActiveItem(createItem);
                }
            }
        });
        
        /**
         * Handle scroll to update active state when scrolling to sections
         */
        let scrollTimeout;
        window.addEventListener('scroll', function() {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(function() {
                const createSection = document.getElementById('create-trade');
                if (createSection) {
                    const rect = createSection.getBoundingClientRect();
                    const isVisible = rect.top < window.innerHeight && rect.bottom > 0;
                    if (isVisible) {
                        const createItem = bottomNav.querySelector('[data-nav="create"]');
                        if (createItem && window.location.hash !== '#create-trade') {
                            // Don't change if user manually selected another item
                            // Only auto-update if scrolling naturally
                        }
                    }
                }
            }, 100);
        });
        
        // Initialize active item on page load
        determineActiveItem();
    });
})();
