/**
 * HDH: Tasks Panel JavaScript
 * Handles toggle functionality for tasks panel
 */

(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        const tasksIcon = document.getElementById('tasks-icon-toggle');
        const tasksPanel = document.getElementById('tasks-panel');
        const tasksOverlay = document.getElementById('tasks-panel-overlay');
        const tasksClose = document.getElementById('tasks-panel-close');
        
        if (!tasksIcon || !tasksPanel) return;
        
        /**
         * Open tasks panel
         */
        function openTasksPanel() {
            tasksPanel.classList.add('active');
            if (tasksOverlay) {
                tasksOverlay.classList.add('active');
            }
            document.body.style.overflow = 'hidden'; // Prevent background scroll
        }
        
        /**
         * Close tasks panel
         */
        function closeTasksPanel() {
            tasksPanel.classList.remove('active');
            if (tasksOverlay) {
                tasksOverlay.classList.remove('active');
            }
            document.body.style.overflow = ''; // Restore scroll
        }
        
        /**
         * Toggle panel
         */
        tasksIcon.addEventListener('click', function(e) {
            e.stopPropagation();
            if (tasksPanel.classList.contains('active')) {
                closeTasksPanel();
            } else {
                openTasksPanel();
            }
        });
        
        /**
         * Close panel on close button click
         */
        if (tasksClose) {
            tasksClose.addEventListener('click', function(e) {
                e.stopPropagation();
                closeTasksPanel();
            });
        }
        
        /**
         * Close panel on overlay click
         */
        if (tasksOverlay) {
            tasksOverlay.addEventListener('click', function(e) {
                e.stopPropagation();
                closeTasksPanel();
            });
        }
        
        /**
         * Close panel on Escape key
         */
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && tasksPanel.classList.contains('active')) {
                closeTasksPanel();
            }
        });
        
        /**
         * Handle daily ticket claim
         */
        const claimBtn = document.querySelector('.btn-claim-daily');
        if (claimBtn) {
            claimBtn.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                if (!userId) return;
                
                const btn = this;
                btn.disabled = true;
                const originalText = btn.textContent;
                btn.textContent = 'İşleniyor...';
                
                const formData = new FormData();
                formData.append('action', 'hdh_claim_daily_jeton');
                formData.append('user_id', userId);
                formData.append('nonce', hdhTasks.nonce);
                
                fetch(hdhTasks.ajaxUrl, { 
                    method: 'POST', 
                    body: formData 
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('+1 Bilet kazandınız! 🎟️', 'success');
                        btn.parentNode.classList.add('task-completed');
                        btn.remove();
                        const status = document.createElement('span');
                        status.className = 'task-status';
                        status.textContent = '✅ Tamamlandı';
                        btn.parentNode.appendChild(status);
                        
                        // Update badge count
                        updateTasksBadge();
                        
                        // Update balance if element exists
                        if (data.data.new_balance !== undefined) {
                            const balanceEl = document.querySelector('.jeton-balance, .bilet-balance');
                            if (balanceEl) {
                                balanceEl.textContent = data.data.new_balance.toLocaleString('tr-TR');
                            }
                        }
                    } else {
                        showToast(data.data.message || 'Bir hata oluştu', 'error');
                        btn.disabled = false;
                        btn.textContent = originalText;
                    }
                })
                .catch(error => { 
                    console.error('Error:', error); 
                    showToast('Bir hata oluştu', 'error'); 
                    btn.disabled = false; 
                    btn.textContent = originalText; 
                });
            });
        }
        
        /**
         * Update tasks badge count
         */
        function updateTasksBadge() {
            const badge = document.getElementById('tasks-icon-badge');
            if (!badge) return;
            
            const incompleteTasks = document.querySelectorAll('.task-item:not(.task-completed)');
            const count = incompleteTasks.length;
            
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }
        
        // Initial badge update
        updateTasksBadge();
        
        // Update badge when tasks change
        if (window.MutationObserver && tasksPanel) {
            const observer = new MutationObserver(function() {
                updateTasksBadge();
            });
            observer.observe(tasksPanel, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['class']
            });
        }
        
        /**
         * Show toast notification
         */
        function showToast(message, type) {
            const toast = document.createElement('div');
            toast.className = 'toast toast-' + type;
            toast.textContent = message;
            toast.style.cssText = 'position: fixed; bottom: 100px; left: 50%; transform: translateX(-50%); background: ' + (type === 'success' ? 'var(--farm-green)' : '#dc3545') + '; color: #FFFFFF; padding: 14px 24px; border-radius: 10px; font-weight: 600; z-index: 10001; box-shadow: 0 4px 12px rgba(0,0,0,0.2); max-width: 90%; text-align: center; opacity: 0; transition: opacity 0.3s ease, transform 0.3s ease;';
            document.body.appendChild(toast);
            
            setTimeout(function() { 
                toast.style.opacity = '1'; 
                toast.style.transform = 'translateX(-50%) translateY(0)'; 
            }, 10);
            
            setTimeout(function() { 
                toast.style.opacity = '0'; 
                toast.style.transform = 'translateX(-50%) translateY(20px)'; 
                setTimeout(function() { 
                    toast.remove(); 
                }, 300); 
            }, 3000);
        }
    });
})();
