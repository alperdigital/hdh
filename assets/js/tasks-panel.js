/**
 * HDH: Tasks Panel JavaScript
 * Handles toggle functionality and task reward claiming
 */

(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        const tasksIcon = document.getElementById('tasks-icon-toggle');
        const tasksPanel = document.getElementById('tasks-panel');
        const tasksOverlay = document.getElementById('tasks-panel-overlay');
        const tasksClose = document.getElementById('tasks-panel-close');
        
        if (!tasksIcon) {
            return; // Silently fail if icon not found
        }
        
        if (!tasksPanel) {
            return; // Silently fail if panel not found
        }
        
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
        function handleToggle(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            if (tasksPanel.classList.contains('active')) {
                closeTasksPanel();
            } else {
                openTasksPanel();
            }
        }
        
        // Support both click and touch events for better mobile compatibility
        let isToggling = false; // Prevent multiple rapid toggles
        
        tasksIcon.addEventListener('click', function(e) {
            if (isToggling) return;
            isToggling = true;
            handleToggle(e);
            setTimeout(function() {
                isToggling = false;
            }, 300);
        }, { passive: false });
        
        tasksIcon.addEventListener('touchend', function(e) {
            if (isToggling) return;
            isToggling = true;
            e.preventDefault();
            handleToggle(e);
            setTimeout(function() {
                isToggling = false;
            }, 300);
        }, { passive: false });
        
        // Also add mousedown for desktop
        tasksIcon.addEventListener('mousedown', function(e) {
            e.preventDefault();
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
         * Handle task reward claim
         */
        function handleClaimTask(btn) {
            const taskId = btn.getAttribute('data-task-id');
            const isDaily = btn.getAttribute('data-is-daily') === 'true';
            
            if (!taskId) {
                showToast('Görev ID bulunamadı', 'error');
                return;
            }
            
            // Check if hdhTasks is defined
            if (typeof hdhTasks === 'undefined') {
                showToast('Görev sistemi yüklenemedi', 'error');
                return;
            }
            
            // Disable button to prevent double-click
            btn.disabled = true;
            const originalText = btn.textContent;
            btn.textContent = 'İşleniyor...';
            
            const formData = new FormData();
            formData.append('action', 'hdh_claim_task_reward');
            formData.append('task_id', taskId);
            formData.append('is_daily', isDaily ? 'true' : 'false');
            formData.append('nonce', hdhTasks.nonce);
            
            fetch(hdhTasks.ajaxUrl, { 
                method: 'POST', 
                body: formData 
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const bilet = data.data.bilet || 0;
                    const level = data.data.level || 0;
                    
                    let message = 'Ödül alındı!';
                    if (bilet > 0 && level > 0) {
                        message = `+${bilet} 🎟️ Bilet +${level} ⭐ Seviye kazandınız!`;
                    } else if (bilet > 0) {
                        message = `+${bilet} 🎟️ Bilet kazandınız!`;
                    } else if (level > 0) {
                        message = `+${level} ⭐ Seviye kazandınız!`;
                    }
                    
                    showToast(message, 'success');
                    
                    // Update bilet balance in header widget
                    if (data.data.new_bilet !== undefined) {
                        // Find bilet stat value (second .hdh-stat-value in .hdh-farm-stats)
                        const farmStats = document.querySelector('.hdh-farm-stats');
                        if (farmStats) {
                            const statItems = farmStats.querySelectorAll('.hdh-stat-item');
                            if (statItems.length >= 2) {
                                // Second item is bilet (🎟️)
                                const biletValue = statItems[1].querySelector('.hdh-stat-value');
                                if (biletValue) {
                                    biletValue.textContent = data.data.new_bilet.toLocaleString('tr-TR');
                                }
                            }
                        }
                        
                        // Also try old selectors for backward compatibility
                        const balanceEl = document.querySelector('.jeton-balance, .bilet-balance');
                        if (balanceEl) {
                            balanceEl.textContent = data.data.new_bilet.toLocaleString('tr-TR');
                        }
                    }
                    
                    // Update level in header widget
                    if (data.data.new_level !== undefined) {
                        // Update level badge (star with number)
                        const levelBadge = document.querySelector('.hdh-level-badge');
                        if (levelBadge) {
                            levelBadge.textContent = data.data.new_level;
                            // Update aria-label and title
                            levelBadge.setAttribute('aria-label', 'Seviye ' + data.data.new_level);
                            levelBadge.setAttribute('title', 'Seviye ' + data.data.new_level);
                            
                            // Update digit class if needed
                            const levelInt = parseInt(data.data.new_level);
                            const digits = levelInt.toString().length;
                            levelBadge.className = 'hdh-level-badge ' + (digits === 1 ? 'lvl-d1' : (digits === 2 ? 'lvl-d2' : 'lvl-d3'));
                        }
                        
                        // Update star stat value (first .hdh-stat-value in .hdh-farm-stats)
                        const farmStats = document.querySelector('.hdh-farm-stats');
                        if (farmStats) {
                            const statItems = farmStats.querySelectorAll('.hdh-stat-item');
                            if (statItems.length >= 1) {
                                // First item is star (⭐)
                                const starValue = statItems[0].querySelector('.hdh-stat-value');
                                if (starValue) {
                                    starValue.textContent = data.data.new_level;
                                }
                            }
                        }
                        
                        // Also try old selectors for backward compatibility
                        const levelEl = document.querySelector('.hdh-user-level, .user-level');
                        if (levelEl) {
                            levelEl.textContent = data.data.new_level;
                        }
                    }
                    
                    // For daily tasks, refresh the tasks list to update progress
                    // For one-time tasks, update button to show claimed status
                    if (isDaily) {
                        // Refresh tasks list to update progress and button state
                        setTimeout(function() {
                            refreshTasksListAndUpdateUI();
                        }, 500);
                    } else {
                        // Update button to show claimed status
                        btn.parentNode.innerHTML = '<span class="task-status">✅ Ödül Alındı</span>';
                        // Update badge count
                        updateTasksBadge();
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
        }
        
        /**
         * Attach claim task handlers to all claim buttons
         * Uses event delegation for better reliability
         */
        function attachClaimHandlers() {
            // Remove all existing listeners by using event delegation
            // This is more reliable than cloning nodes
            const tasksPanelContent = document.querySelector('.tasks-panel-content');
            if (!tasksPanelContent) return;
            
            // Remove old delegation listener if exists
            if (tasksPanelContent._claimHandlerAttached) {
                tasksPanelContent.removeEventListener('click', tasksPanelContent._claimHandler);
            }
            
            // Create new delegation handler
            tasksPanelContent._claimHandler = function(e) {
                const btn = e.target.closest('.btn-claim-task');
                if (btn && !btn.disabled) {
                    e.preventDefault();
                    e.stopPropagation();
                    handleClaimTask(btn);
                }
            };
            
            // Attach delegation listener
            tasksPanelContent.addEventListener('click', tasksPanelContent._claimHandler);
            tasksPanelContent._claimHandlerAttached = true;
        }
        
        // Attach handlers on initial load
        attachClaimHandlers();
        
        // Re-attach handlers when panel opens (in case tasks were updated)
        let panelOpenHandler = function() {
            if (!tasksPanel.classList.contains('active')) {
                // Panel is opening, wait a bit for content to be ready
                setTimeout(attachClaimHandlers, 150);
            }
        };
        
        tasksIcon.addEventListener('click', panelOpenHandler);
        
        // Also attach when panel becomes visible (for dynamic content)
        if (window.MutationObserver) {
            const panelObserver = new MutationObserver(function(mutations) {
                let shouldReattach = false;
                for (let mutation of mutations) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        if (tasksPanel.classList.contains('active')) {
                            shouldReattach = true;
                        }
                    }
                    if (mutation.type === 'childList' && tasksPanel.classList.contains('active')) {
                        shouldReattach = true;
                    }
                }
                if (shouldReattach) {
                    setTimeout(attachClaimHandlers, 100);
                }
            });
            panelObserver.observe(tasksPanel, {
                attributes: true,
                attributeFilter: ['class'],
                childList: true,
                subtree: true
            });
        }
        
        /**
         * Refresh tasks list from server and update UI
         */
        function refreshTasksListAndUpdateUI() {
            // Prevent multiple simultaneous requests
            if (refreshTasksListAndUpdateUI.isLoading) {
                return;
            }
            refreshTasksListAndUpdateUI.isLoading = true;
            
            // Check if hdhTasks is defined
            if (typeof hdhTasks === 'undefined') {
                refreshTasksListAndUpdateUI.isLoading = false;
                console.error('HDH Tasks: hdhTasks object not defined');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'hdh_get_tasks');
            formData.append('nonce', hdhTasks.nonce);
            
            fetch(hdhTasks.ajaxUrl, { 
                method: 'POST', 
                body: formData 
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                refreshTasksListAndUpdateUI.isLoading = false;
                if (data.success) {
                    // Update daily tasks UI
                    const dailyTasks = data.data.daily_tasks || [];
                    dailyTasks.forEach(function(task) {
                        // Find task item by container data attribute
                        const taskItemContainer = document.querySelector('.task-item[data-task-container-id="' + task.id + '"]');
                        if (taskItemContainer) {
                            const taskActions = taskItemContainer.querySelector('.task-actions');
                            if (taskActions) {
                                if (task.can_claim) {
                                    taskActions.innerHTML = '<button class="btn-claim-task" data-task-id="' + task.id + '" data-is-daily="true">Ödülünü Al</button>';
                                } else if (task.id === 'create_listings') {
                                    taskActions.innerHTML = '<a href="' + hdhTasks.siteUrl + '/ilan-ver" class="btn-do-task">Yap</a>';
                                } else if (task.id === 'invite_friends' || task.id === 'friend_exchanges') {
                                    taskActions.innerHTML = '<a href="' + hdhTasks.siteUrl + '/profil" class="btn-do-task">Yap</a>';
                                } else {
                                    taskActions.innerHTML = '<span class="task-status">Beklemede</span>';
                                }
                            }
                            
                            // Update progress display
                            const taskName = taskItemContainer.querySelector('.task-name');
                            if (taskName && task.max_progress > 1) {
                                const taskProgress = taskName.querySelector('.task-progress');
                                if (taskProgress) {
                                    taskProgress.textContent = '(' + task.progress + '/' + task.max_progress + ')';
                                } else {
                                    // Create progress element if it doesn't exist
                                    const progressEl = document.createElement('span');
                                    progressEl.className = 'task-progress';
                                    progressEl.textContent = '(' + task.progress + '/' + task.max_progress + ')';
                                    taskName.appendChild(progressEl);
                                }
                            }
                        }
                    });
                    
                    // Update one-time tasks UI
                    const oneTimeTasks = data.data.one_time_tasks || [];
                    oneTimeTasks.forEach(function(task) {
                        const taskItemContainer = document.querySelector('.task-item[data-task-container-id="' + task.id + '"]');
                        if (taskItemContainer) {
                            const taskActions = taskItemContainer.querySelector('.task-actions');
                            if (taskActions && !task.can_claim && task.claimed) {
                                taskActions.innerHTML = '<span class="task-status">✅ Ödül Alındı</span>';
                            }
                        }
                    });
                    
                    // Re-attach claim handlers
                    attachClaimHandlers();
                    
                    // Update badge count
                    updateTasksBadge();
                } else {
                    console.error('HDH Tasks: Failed to refresh tasks', data);
                }
            })
            .catch(error => {
                refreshTasksListAndUpdateUI.isLoading = false;
                console.error('Error refreshing tasks:', error);
                // Don't show error to user, just log it
            });
        }
        
        /**
         * Refresh tasks list from server (legacy function)
         */
        function refreshTasksList() {
            refreshTasksListAndUpdateUI();
        }
        
        /**
         * Update tasks badge count
         */
        function updateTasksBadge() {
            const badge = document.getElementById('tasks-icon-badge');
            if (!badge) return;
            
            // Count tasks that are completed but not claimed
            const claimButtons = document.querySelectorAll('.btn-claim-task');
            const count = claimButtons.length;
            
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }
        
        // Initial badge update
        updateTasksBadge();
        
        // Update badge when tasks change (throttled to prevent infinite loops)
        // Note: Event delegation handles button clicks automatically, so we don't need to re-attach handlers
        if (window.MutationObserver && tasksPanel) {
            let observerTimeout;
            const observer = new MutationObserver(function() {
                clearTimeout(observerTimeout);
                observerTimeout = setTimeout(function() {
                    updateTasksBadge();
                    // Event delegation is already in place, no need to re-attach
                }, 200); // Throttle to prevent excessive calls
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
