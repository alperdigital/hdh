/**
 * HDH: Quest Panel JavaScript
 */

(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        const questIcon = document.getElementById('quest-icon-toggle');
        const questPanel = document.getElementById('quest-panel');
        const questClose = document.getElementById('quest-panel-close');
        
        if (!questIcon || !questPanel) return;
        
        // Toggle panel
        questIcon.addEventListener('click', function() {
            questPanel.classList.toggle('active');
        });
        
        // Close panel
        if (questClose) {
            questClose.addEventListener('click', function() {
                questPanel.classList.remove('active');
            });
        }
        
        // Update badge count
        function updateQuestBadge() {
            const badge = document.getElementById('quest-icon-badge');
            if (!badge) return;
            
            const incompleteQuests = document.querySelectorAll('.quest-item:not(.quest-completed)');
            const count = incompleteQuests.length;
            
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }
        
        // Initial update
        updateQuestBadge();
        
        // Update on quest completion (if using MutationObserver)
        if (window.MutationObserver) {
            const observer = new MutationObserver(updateQuestBadge);
            if (questPanel) {
                observer.observe(questPanel, {
                    childList: true,
                    subtree: true,
                    attributes: true,
                    attributeFilter: ['class']
                });
            }
        }
    });
})();

