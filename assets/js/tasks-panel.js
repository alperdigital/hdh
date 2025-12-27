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
        const headerTasksButton = document.getElementById('hdh-header-tasks-button');
        
        if (!tasksPanel) {
            return; // Silently fail if panel not found
        }
        
        /**
         * Open tasks panel
         */
        function openTasksPanel() {
            if (!tasksPanel) {
                console.error('HDH Tasks: Panel not found!');
                return;
            }
            
            // Add active class (CSS will handle display)
            tasksPanel.classList.add('active');
            
            // Force display to ensure it's visible (CSS might have display: none)
            requestAnimationFrame(function() {
                if (tasksPanel.classList.contains('active')) {
                    tasksPanel.style.display = 'flex';
                }
            });
            
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
        
        // Header tasks button click handler
        if (headerTasksButton) {
            headerTasksButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (isToggling) return;
                isToggling = true;
                handleToggle(e);
                setTimeout(function() {
                    isToggling = false;
                }, 300);
            }, false);
            
            headerTasksButton.addEventListener('touchend', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (isToggling) return;
                isToggling = true;
                handleToggle(e);
                setTimeout(function() {
                    isToggling = false;
                }, 300);
            }, false);
        }
        
        // Original tasks icon (if exists)
        if (tasksIcon) {
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
        }
        
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
                const errorMsg = (hdhTasks.messages && hdhTasks.messages.tasks && hdhTasks.messages.tasks.task_id_not_found) 
                    ? hdhTasks.messages.tasks.task_id_not_found 
                    : 'Görev ID bulunamadı';
                showToast(errorMsg, 'error');
                return;
            }
            
            // Check if hdhTasks is defined
            if (typeof hdhTasks === 'undefined') {
                const errorMsg = (hdhTasks.messages && hdhTasks.messages.tasks && hdhTasks.messages.tasks.task_system_load_error) 
                    ? hdhTasks.messages.tasks.task_system_load_error 
                    : 'Görev sistemi yüklenemedi';
                showToast(errorMsg, 'error');
                return;
            }
            
            // Disable button to prevent double-click
            btn.disabled = true;
            const originalText = btn.textContent;
            const processingMsg = (hdhTasks.messages && hdhTasks.messages.ui && hdhTasks.messages.ui.processing) 
                ? hdhTasks.messages.ui.processing 
                : 'İşleniyor...';
            btn.textContent = processingMsg;
            
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
                    const new_bilet = data.data.new_bilet;
                    const new_level = data.data.new_level;
                    
                    // Build success message
                    let message = 'Ödül alındı!';
                    const parts = [];
                    if (bilet > 0) {
                        parts.push(`+${bilet} 🎟️ Bilet`);
                    }
                    if (level > 0) {
                        parts.push(`+${level} Seviye`);
                    }
                    if (parts.length > 0) {
                        message = parts.join(' + ') + ' kazandınız!';
                    }
                    
                    showToast(message, 'success');
                    
                    // Update UI: claimable_count and badge
                    const claimableRemaining = data.data.claimable_remaining !== undefined ? data.data.claimable_remaining : 0;
                    const taskContainer = btn.closest('.task-item');
                    if (taskContainer) {
                        // Update button state based on remaining claimable count
                        if (claimableRemaining > 0) {
                            // Still has claimable rewards, keep button enabled
                            btn.disabled = false;
                            btn.textContent = originalText;
                        } else {
                            // No more claimable rewards, show "Ödül Alındı" or disable button
                            const claimedMsg = (hdhTasks.messages && hdhTasks.messages.tasks && hdhTasks.messages.tasks.reward_claimed_text) 
                                ? hdhTasks.messages.tasks.reward_claimed_text 
                                : '✅ Ödül Alındı';
                            btn.textContent = claimedMsg;
                            btn.disabled = true;
                            btn.classList.add('claimed');
                        }
                    }
                    
                    // Update badge count
                    updateBadgeCount();
                    
                    // Log for debugging (only in development)
                    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                        console.log('HDH Tasks: Reward claimed', {
                            bilet: bilet,
                            level: level,
                            new_bilet: new_bilet,
                            new_level: new_level,
                            claimable_remaining: claimableRemaining
                        });
                    }
                    
                    // Update bilet balance in header widget with animation
                    if (data.data.new_bilet !== undefined && data.data.new_bilet !== null) {
                        // Find bilet stat value (second .hdh-stat-value in .hdh-farm-stats)
                        const farmStats = document.querySelector('.hdh-farm-stats');
                        if (farmStats) {
                            const statItems = farmStats.querySelectorAll('.hdh-stat-item');
                            if (statItems.length >= 2) {
                                // Second item is bilet (🎟️)
                                const biletValue = statItems[1].querySelector('.hdh-stat-value');
                                if (biletValue) {
                                    const oldValue = parseInt(biletValue.textContent.replace(/\./g, '')) || 0;
                                    const newValue = data.data.new_bilet;
                                    
                                    // Add animation class
                                    biletValue.classList.add('updating');
                                    
                                    // Update value
                                    biletValue.textContent = newValue.toLocaleString('tr-TR');
                                    
                                    // Remove animation class after animation completes
                                    setTimeout(function() {
                                        biletValue.classList.remove('updating');
                                    }, 600);
                                }
                            }
                        }
                        
                        // Also try old selectors for backward compatibility
                        const balanceEl = document.querySelector('.jeton-balance, .bilet-balance');
                        if (balanceEl) {
                            balanceEl.textContent = data.data.new_bilet.toLocaleString('tr-TR');
                        }
                    }
                    
                    // Update level in header widget with animation
                    if (data.data.new_level !== undefined && data.data.new_level !== null) {
                        const newLevel = parseInt(data.data.new_level);
                        if (!isNaN(newLevel)) {
                            // Update level badge (star with number)
                            const levelBadge = document.querySelector('.hdh-level-badge');
                            if (levelBadge) {
                                const oldLevel = parseInt(levelBadge.textContent) || 0;
                                
                                // Add animation class
                                levelBadge.classList.add('updating');
                                
                                // Update value
                                levelBadge.textContent = newLevel;
                                // Update aria-label and title
                                levelBadge.setAttribute('aria-label', 'Seviye ' + newLevel);
                                levelBadge.setAttribute('title', 'Seviye ' + newLevel);
                                
                                // Update digit class if needed
                                const digits = newLevel.toString().length;
                                levelBadge.className = 'hdh-level-badge ' + (digits === 1 ? 'lvl-d1' : (digits === 2 ? 'lvl-d2' : 'lvl-d3')) + ' updating';
                                
                                // Remove animation class after animation completes
                                setTimeout(function() {
                                    levelBadge.classList.remove('updating');
                                }, 600);
                            }
                            
                            // Update star stat value (first .hdh-stat-value in .hdh-farm-stats)
                            const farmStats = document.querySelector('.hdh-farm-stats');
                            if (farmStats) {
                                const statItems = farmStats.querySelectorAll('.hdh-stat-item');
                                if (statItems.length >= 1) {
                                    // First item is star (⭐)
                                    const starValue = statItems[0].querySelector('.hdh-stat-value');
                                    if (starValue) {
                                        // Add animation class
                                        starValue.classList.add('updating');
                                        
                                        // Update value
                                        starValue.textContent = newLevel;
                                        
                                        // Remove animation class after animation completes
                                        setTimeout(function() {
                                            starValue.classList.remove('updating');
                                        }, 600);
                                    }
                                }
                            }
                            
                            // Also try old selectors for backward compatibility
                            const levelEl = document.querySelector('.hdh-user-level, .user-level');
                            if (levelEl) {
                                levelEl.textContent = newLevel;
                            }
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
                        const taskActions = btn.closest('.task-actions');
                        if (taskActions) {
                            taskActions.innerHTML = '<span class="task-status">✅ Ödül Alındı</span>';
                        }
                        // Update badge count
                        updateTasksBadge();
                    }
                } else {
                    const errorMsg = data.data.message || (hdhTasks.messages && hdhTasks.messages.ajax && hdhTasks.messages.ajax.generic_error) 
                        ? hdhTasks.messages.ajax.generic_error 
                        : 'Bir hata oluştu';
                    showToast(errorMsg, 'error');
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            })
            .catch(error => { 
                console.error('Error:', error); 
                const errorMsg = (hdhTasks.messages && hdhTasks.messages.ajax && hdhTasks.messages.ajax.generic_error) 
                    ? hdhTasks.messages.ajax.generic_error 
                    : 'Bir hata oluştu';
                showToast(errorMsg, 'error'); 
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
                                    const claimRewardText = (hdhTasks.messages && hdhTasks.messages.tasks && hdhTasks.messages.tasks.claim_reward_button) 
                                        ? hdhTasks.messages.tasks.claim_reward_button 
                                        : 'Ödülünü Al';
                                    taskActions.innerHTML = '<button class="btn-claim-task" data-task-id="' + task.id + '" data-is-daily="true">' + claimRewardText + '</button>';
                                    } else if (task.id === 'create_listings') {
                                    const doTaskText = (hdhTasks.messages && hdhTasks.messages.tasks && hdhTasks.messages.tasks.do_task) 
                                        ? hdhTasks.messages.tasks.do_task 
                                        : 'Yap';
                                    taskActions.innerHTML = '<a href="' + hdhTasks.siteUrl + '/ilan-ver" class="btn-do-task">' + doTaskText + '</a>';
                                    } else if (task.id === 'invite_friends' || task.id === 'friend_exchanges') {
                                    const doTaskText = (hdhTasks.messages && hdhTasks.messages.tasks && hdhTasks.messages.tasks.do_task) 
                                        ? hdhTasks.messages.tasks.do_task 
                                        : 'Yap';
                                    taskActions.innerHTML = '<a href="' + hdhTasks.siteUrl + '/profil" class="btn-do-task">' + doTaskText + '</a>';
                                    } else {
                                    const pendingText = (hdhTasks.messages && hdhTasks.messages.tasks && hdhTasks.messages.tasks.pending_status) 
                                        ? hdhTasks.messages.tasks.pending_status 
                                        : 'Beklemede';
                                    taskActions.innerHTML = '<span class="task-status">' + pendingText + '</span>';
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
                    
                    // Event delegation handles button clicks automatically, no need to re-attach
                    // But ensure delegation is set up (it should already be, but just in case)
                    const tasksPanelContent = document.querySelector('.tasks-panel-content');
                    if (tasksPanelContent && !tasksPanelContent._claimHandlerAttached) {
                        attachClaimHandlers();
                    }
                    
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
         * Update badge count based on claimable_count
         */
        function updateBadgeCount() {
            // Reload tasks panel to get fresh claimable_count data
            // This is a simple approach - could be optimized with AJAX call to get only counts
            const taskItems = document.querySelectorAll('.task-item');
            let claimableCount = 0;
            
            taskItems.forEach(function(item) {
                const claimBtn = item.querySelector('.btn-claim-task');
                if (claimBtn && !claimBtn.disabled && !claimBtn.classList.contains('claimed')) {
                    claimableCount++;
                }
            });
            
            // Update icon badge (if exists)
            const badge = document.getElementById('tasks-icon-badge');
            if (badge) {
                if (claimableCount > 0) {
                    badge.textContent = claimableCount;
                    badge.style.display = 'block';
                } else {
                    badge.style.display = 'none';
                }
            }
            
            // Update header count
            const headerCount = document.getElementById('hdh-header-tasks-count');
            if (headerCount) {
                headerCount.textContent = '(' + claimableCount + ')';
            }
        }
        
        /**
         * Update tasks badge count (legacy function name, kept for compatibility)
         */
        function updateTasksBadge() {
            updateBadgeCount();
        }
        
        /**
         * Update tasks badge count (uses claimable_count from task data)
         */
        function updateTasksBadge() {
            updateBadgeCount();
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
