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
            // Check if we're on create trade page (ilan-ver)
            if (currentPath.includes('ilan-ver') || currentHash === '#create-trade') {
                const createItem = bottomNav.querySelector('[data-nav="create"]');
                if (createItem) {
                    setActiveItem(createItem);
                    return;
                }
            }
            
            // Check if we're on search page (ara)
            if (currentPath.includes('/ara') || currentPath.endsWith('/ara')) {
                const searchItem = bottomNav.querySelector('[data-nav="search"]');
                if (searchItem) {
                    setActiveItem(searchItem);
                    return;
                }
            }
            
            // Check if we're on homepage
            if (currentPath === '/' || currentPath === '') {
                // Homepage - no active item (or could set to first item)
                return;
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
                
                // For center button, navigate to ilan-ver page
                if (item.classList.contains('bottom-nav-center')) {
                    // Normal navigation - no special handling needed
                    // The href will handle navigation to /ilan-ver
                }
                
                // For other items, allow normal navigation
                // Active state is already set above
            });
        });
        
        /**
         * Handle hash changes and pathname changes
         */
        window.addEventListener('hashchange', function() {
            determineActiveItem();
        });
        
        // Also listen for popstate (back/forward navigation)
        window.addEventListener('popstate', function() {
            determineActiveItem();
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
