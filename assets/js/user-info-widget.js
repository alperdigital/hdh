/**
 * HDH: User Info Widget - Dynamic Positioning
 * Adjusts widget position based on header height
 */

(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        const widget = document.querySelector('.hdh-user-info-widget');
        if (!widget) return;
        
        const announcementBanner = document.querySelector('.farm-announcement-banner');
        
        function updateWidgetPosition() {
            if (announcementBanner) {
                const bannerHeight = announcementBanner.offsetHeight;
                widget.style.top = (bannerHeight + 8) + 'px'; // 8px gap
            } else {
                widget.style.top = '16px';
            }
        }
        
        // Initial positioning
        updateWidgetPosition();
        
        // Update on window resize
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(updateWidgetPosition, 100);
        });
        
        // Update when announcement banner visibility changes
        if (announcementBanner) {
            const observer = new MutationObserver(function() {
                updateWidgetPosition();
            });
            
            observer.observe(announcementBanner, {
                attributes: true,
                attributeFilter: ['style', 'class'],
                childList: false,
                subtree: false
            });
        }
    });
})();

