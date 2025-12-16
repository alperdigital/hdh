<?php
/**
 * HDH: Tasks Panel Component
 * Toggleable tasks panel with emoji button
 */
if (!function_exists('hdh_render_tasks_panel')) {
    function hdh_render_tasks_panel($user_id) {
        if (!$user_id) return;
        
        // Get one-time and daily tasks with error handling
        $one_time_tasks = array();
        $daily_tasks = array();
        $incomplete_count = 0;
        
        try {
            if (function_exists('hdh_get_user_one_time_tasks')) {
                $one_time_tasks = hdh_get_user_one_time_tasks($user_id);
                if (!is_array($one_time_tasks)) {
                    $one_time_tasks = array();
                }
            }
        } catch (Exception $e) {
            error_log('HDH Tasks: Error getting one-time tasks: ' . $e->getMessage());
            $one_time_tasks = array();
        }
        
        try {
            if (function_exists('hdh_get_user_daily_tasks')) {
                $daily_tasks = hdh_get_user_daily_tasks($user_id);
                if (!is_array($daily_tasks)) {
                    $daily_tasks = array();
                }
            }
        } catch (Exception $e) {
            error_log('HDH Tasks: Error getting daily tasks: ' . $e->getMessage());
            $daily_tasks = array();
        }
        
        // Count incomplete tasks for badge (tasks that can be claimed)
        foreach ($one_time_tasks as $task) {
            if (isset($task['can_claim']) && $task['can_claim']) {
                $incomplete_count++;
            }
        }
        foreach ($daily_tasks as $task) {
            if (isset($task['can_claim']) && $task['can_claim']) {
                $incomplete_count++;
            }
        }
        ?>
        
        <!-- Tasks Panel Toggle Button (Fixed Position) -->
        <button class="tasks-icon-fixed" id="tasks-icon-toggle" aria-label="Görevler">
            <span class="tasks-icon-emoji">📋</span>
            <?php if ($incomplete_count > 0) : ?>
                <span class="tasks-icon-badge" id="tasks-icon-badge"><?php echo esc_html($incomplete_count); ?></span>
            <?php else : ?>
                <span class="tasks-icon-badge" id="tasks-icon-badge" style="display: none;">0</span>
            <?php endif; ?>
        </button>
        
        <!-- Tasks Panel Overlay -->
        <div class="tasks-panel-overlay" id="tasks-panel-overlay"></div>
        
        <!-- Tasks Panel -->
        <div class="tasks-panel" id="tasks-panel">
            <div class="tasks-panel-header">
                <h3 class="tasks-panel-title">📋 Görevler</h3>
                <button class="tasks-panel-close" id="tasks-panel-close" aria-label="Kapat">×</button>
            </div>
            
            <div class="tasks-panel-content">
                <!-- One-Time Tasks Section -->
                <?php if (!empty($one_time_tasks)) : ?>
                <div class="tasks-section">
                    <h4 class="tasks-section-title">Tek Seferlik Görevler</h4>
                    <div class="tasks-list">
                        <?php foreach ($one_time_tasks as $task) : ?>
                            <div class="task-item <?php echo $task['completed'] ? 'task-completed' : ''; ?>" data-task-container-id="<?php echo esc_attr($task['id']); ?>">
                                <div class="task-info">
                                    <span class="task-icon">
                                        <?php
                                        $icons = array(
                                            'verify_email' => '📧',
                                            'create_first_listing' => '📝',
                                            'complete_first_exchange' => '🎁',
                                            'invite_friend' => '👥',
                                            'friend_exchange' => '🤝',
                                        );
                                        echo isset($icons[$task['id']]) ? $icons[$task['id']] : '📋';
                                        ?>
                                    </span>
                                    <div class="task-details">
                                        <span class="task-name" data-task-id="<?php echo esc_attr($task['id']); ?>"><?php echo esc_html($task['title']); ?></span>
                                        <span class="task-description"><?php echo esc_html($task['description']); ?></span>
                                        <span class="task-reward">
                                            <?php if ($task['reward_bilet'] > 0) : ?>
                                                +<?php echo esc_html($task['reward_bilet']); ?> 🎟️
                                            <?php endif; ?>
                                            <?php if ($task['reward_level'] > 0) : ?>
                                                <?php echo $task['reward_bilet'] > 0 ? ' + ' : ''; ?>
                                                +<?php echo esc_html($task['reward_level']); ?> ⭐ Seviye
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="task-actions">
                                    <?php if ($task['completed'] && $task['claimed']) : ?>
                                        <span class="task-status">✅ Ödül Alındı</span>
                                    <?php elseif ($task['completed'] && $task['can_claim']) : ?>
                                        <button class="btn-claim-task" 
                                                data-task-id="<?php echo esc_attr($task['id']); ?>" 
                                                data-is-daily="false">
                                            Ödülünü Al
                                        </button>
                                    <?php elseif ($task['id'] === 'verify_email') : ?>
                                        <a href="<?php echo esc_url(home_url('/profil')); ?>" class="btn-do-task">Yap</a>
                                    <?php elseif ($task['id'] === 'create_first_listing') : ?>
                                        <a href="<?php echo esc_url(home_url('/ilan-ver')); ?>" class="btn-do-task">Yap</a>
                                    <?php elseif ($task['id'] === 'invite_friend' || $task['id'] === 'friend_exchange') : ?>
                                        <a href="<?php echo esc_url(home_url('/profil')); ?>" class="btn-do-task">Yap</a>
                                    <?php else : ?>
                                        <span class="task-status">Beklemede</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Daily Tasks Section -->
                <?php if (!empty($daily_tasks)) : ?>
                <div class="tasks-section">
                    <h4 class="tasks-section-title">Günlük Görevler</h4>
                    <div class="tasks-list">
                        <?php foreach ($daily_tasks as $task) : ?>
                            <div class="task-item <?php echo $task['completed'] ? 'task-completed' : ''; ?>" data-task-container-id="<?php echo esc_attr($task['id']); ?>">
                                <div class="task-info">
                                    <span class="task-icon">
                                        <?php
                                        $icons = array(
                                            'create_listings' => '📝',
                                            'complete_exchanges' => '🎁',
                                            'invite_friends' => '👥',
                                            'friend_exchanges' => '🤝',
                                        );
                                        echo isset($icons[$task['id']]) ? $icons[$task['id']] : '📋';
                                        ?>
                                    </span>
                                    <div class="task-details">
                                        <span class="task-name" data-task-id="<?php echo esc_attr($task['id']); ?>">
                                            <?php echo esc_html($task['title']); ?>
                                            <?php if ($task['max_progress'] > 1) : ?>
                                                <span class="task-progress">(<?php echo esc_html($task['progress']); ?>/<?php echo esc_html($task['max_progress']); ?>)</span>
                                            <?php endif; ?>
                                        </span>
                                        <span class="task-description"><?php echo esc_html($task['description']); ?></span>
                                        <span class="task-reward">
                                            <?php if ($task['reward_bilet'] > 0) : ?>
                                                +<?php echo esc_html($task['reward_bilet']); ?> 🎟️
                                            <?php endif; ?>
                                            <?php if ($task['reward_level'] > 0) : ?>
                                                <?php echo $task['reward_bilet'] > 0 ? ' + ' : ''; ?>
                                                +<?php echo esc_html($task['reward_level']); ?> ⭐ Seviye
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="task-actions">
                                    <?php if ($task['can_claim']) : ?>
                                        <button class="btn-claim-task" 
                                                data-task-id="<?php echo esc_attr($task['id']); ?>" 
                                                data-is-daily="true">
                                            Ödülünü Al
                                        </button>
                                    <?php elseif ($task['id'] === 'create_listings') : ?>
                                        <a href="<?php echo esc_url(home_url('/ilan-ver')); ?>" class="btn-do-task">Yap</a>
                                    <?php elseif ($task['id'] === 'invite_friends' || $task['id'] === 'friend_exchanges') : ?>
                                        <a href="<?php echo esc_url(home_url('/profil')); ?>" class="btn-do-task">Yap</a>
                                    <?php else : ?>
                                        <span class="task-status">Beklemede</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
?>
