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
            console.warn('HDH Tasks: tasks-icon-toggle not found');
            return;
        }
        
        if (!tasksPanel) {
            console.warn('HDH Tasks: tasks-panel not found');
            return;
        }
        
        console.log('HDH Tasks: Initialized', { tasksIcon, tasksPanel, tasksOverlay });
        
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
            console.log('HDH Tasks: Toggle clicked', tasksPanel.classList.contains('active'));
            if (tasksPanel.classList.contains('active')) {
                closeTasksPanel();
            } else {
                openTasksPanel();
            }
        }
        
        // Support both click and touch events for better mobile compatibility
        tasksIcon.addEventListener('click', function(e) {
            console.log('HDH Tasks: Click event fired');
            handleToggle(e);
        }, { passive: false });
        
        tasksIcon.addEventListener('touchend', function(e) {
            console.log('HDH Tasks: Touch event fired');
            e.preventDefault();
            handleToggle(e);
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
                console.error('HDH Tasks: Task ID not found');
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
            .then(response => response.json())
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
                    
                    // Update balance if element exists
                    if (data.data.new_bilet !== undefined) {
                        const balanceEl = document.querySelector('.jeton-balance, .bilet-balance');
                        if (balanceEl) {
                            balanceEl.textContent = data.data.new_bilet.toLocaleString('tr-TR');
                        }
                    }
                    
                    // Update level if element exists
                    if (data.data.new_level !== undefined) {
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
         */
        function attachClaimHandlers() {
            const claimButtons = document.querySelectorAll('.btn-claim-task');
            claimButtons.forEach(function(btn) {
                // Remove existing listeners by cloning
                const newBtn = btn.cloneNode(true);
                btn.parentNode.replaceChild(newBtn, btn);
                
                // Add new listener
                newBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    handleClaimTask(newBtn);
                });
            });
        }
        
        // Attach handlers on initial load
        attachClaimHandlers();
        
        // Re-attach handlers when panel opens (in case tasks were updated)
        tasksIcon.addEventListener('click', function() {
            setTimeout(attachClaimHandlers, 100);
        });
        
        /**
         * Refresh tasks list from server and update UI
         */
        function refreshTasksListAndUpdateUI() {
            const formData = new FormData();
            formData.append('action', 'hdh_get_tasks');
            formData.append('nonce', hdhTasks.nonce);
            
            fetch(hdhTasks.ajaxUrl, { 
                method: 'POST', 
                body: formData 
            })
            .then(response => response.json())
            .then(data => {
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
                }
            })
            .catch(error => {
                console.error('Error refreshing tasks:', error);
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
        
        // Update badge when tasks change
        if (window.MutationObserver && tasksPanel) {
            const observer = new MutationObserver(function() {
                updateTasksBadge();
                attachClaimHandlers(); // Re-attach handlers when DOM changes
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
