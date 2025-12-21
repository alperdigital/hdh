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
            // Normalize path - remove trailing slash
            const normalizedPath = currentPath.replace(/\/$/, '');
            
            // Check specific pages first (most specific to least specific)
            // Order matters! Check longer/more specific paths first
            
            // Check if we're on create trade page (ilan-ver)
            if (normalizedPath === '/ilan-ver' || currentHash === '#create-trade') {
                const createItem = bottomNav.querySelector('[data-nav="create"]');
                if (createItem) {
                    setActiveItem(createItem);
                    return;
                }
            }
            
            // Check if we're on profile page (profil)
            if (normalizedPath === '/profil') {
                const profileItem = bottomNav.querySelector('[data-nav="profile"]');
                if (profileItem) {
                    setActiveItem(profileItem);
                    return;
                }
            }
            
            // Check if we're on lottery page (cekilis)
            if (normalizedPath === '/cekilis') {
                const raffleItem = bottomNav.querySelector('[data-nav="raffle"]');
                if (raffleItem) {
                    setActiveItem(raffleItem);
                    return;
                }
            }
            
            // Check if we're on search page (ara) - check this before homepage
            // because "ara" might appear in other URLs
            if (normalizedPath === '/ara') {
                const searchItem = bottomNav.querySelector('[data-nav="search"]');
                if (searchItem) {
                    setActiveItem(searchItem);
                    return;
                }
            }
            
            // Check if we're on homepage - set home as active
            if (normalizedPath === '' || normalizedPath === '/index.php' || normalizedPath === '/') {
                const homeItem = bottomNav.querySelector('[data-nav="home"]');
                if (homeItem) {
                    setActiveItem(homeItem);
                    return;
                }
            }
            
            // Check if we're on treasure page (hazine) - now accessed from profile
            // Menu item is now "Anasayfa" (home), so we don't activate it from /hazine
            if (normalizedPath === '/hazine') {
                // Don't activate any menu item for treasure room (it's accessed from profile)
                setActiveItem(null);
                return;
            }
            
            // For other pages (like single trade posts), no active item
            setActiveItem(null);
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
