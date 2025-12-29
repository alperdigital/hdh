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
        
        // Count tasks with claimable rewards for badge (claimable_count > 0)
        foreach ($one_time_tasks as $task) {
            $claimable_count = isset($task['claimable_count']) ? (int) $task['claimable_count'] : 0;
            if ($claimable_count > 0) {
                $incomplete_count++;
            }
        }
        foreach ($daily_tasks as $task) {
            $claimable_count = isset($task['claimable_count']) ? (int) $task['claimable_count'] : 0;
            if ($claimable_count > 0) {
                $incomplete_count++;
            }
        }
        ?>
        
        <!-- Tasks Panel Toggle Button (REMOVED - Now using header button) -->
        
        <!-- Tasks Panel Overlay -->
        <div class="tasks-panel-overlay" id="tasks-panel-overlay"></div>
        
        <!-- Tasks Panel -->
        <div class="tasks-panel" id="tasks-panel">
            <div class="tasks-panel-header">
                <h3 class="tasks-panel-title">📋 <?php echo esc_html(hdh_get_content('tasks', 'panel_title', 'Görevler')); ?></h3>
                <button class="tasks-panel-close" id="tasks-panel-close" aria-label="<?php echo esc_attr(hdh_get_content('tasks', 'close_button_text', 'Kapat')); ?>">×</button>
            </div>
            
            <div class="tasks-panel-content">
                <!-- One-Time Tasks Section -->
                <?php if (!empty($one_time_tasks)) : ?>
                <div class="tasks-section">
                    <h4 class="tasks-section-title"><?php echo esc_html(hdh_get_content('tasks', 'one_time_section_title', 'Tek Seferlik Görevler')); ?></h4>
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
                                                <?php echo $task['reward_bilet'] > 0 ? '  ' : ''; ?>
                                                +<?php echo esc_html($task['reward_level']); ?> Seviye
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="task-actions">
                                    <?php if ($task['completed'] && $task['claimed']) : ?>
                                        <span class="task-status"><?php echo esc_html(hdh_get_content('tasks', 'reward_claimed_text', '✅ Ödül Alındı')); ?></span>
                                    <?php elseif ($task['completed'] && $task['can_claim']) : ?>
                                        <button class="btn-claim-task" 
                                                data-task-id="<?php echo esc_attr($task['id']); ?>" 
                                                data-is-daily="false">
                                            <?php echo esc_html(hdh_get_content('tasks', 'claim_reward_button', 'Ödülünü Al')); ?>
                                        </button>
                                    <?php elseif ($task['id'] === 'verify_email') : ?>
                                        <a href="<?php echo esc_url(home_url('/profil')); ?>" class="btn-do-task"><?php echo esc_html(hdh_get_content('tasks', 'do_task_button', 'Yap')); ?></a>
                                    <?php elseif ($task['id'] === 'create_first_listing') : ?>
                                        <a href="<?php echo esc_url(home_url('/ilan-ver')); ?>" class="btn-do-task"><?php echo esc_html(hdh_get_content('tasks', 'do_task_button', 'Yap')); ?></a>
                                    <?php elseif ($task['id'] === 'invite_friend') : ?>
                                        <?php
                                        $referral_link = function_exists('hdh_get_referral_link') ? hdh_get_referral_link($user_id) : '';
                                        if ($referral_link) : ?>
                                            <button type="button" class="btn-share-referral" data-referral-link="<?php echo esc_attr($referral_link); ?>">
                                                <?php echo esc_html(hdh_get_content('tasks', 'share_referral_button', 'Linki Paylaş')); ?>
                                            </button>
                                        <?php else : ?>
                                            <a href="<?php echo esc_url(home_url('/profil')); ?>" class="btn-do-task"><?php echo esc_html(hdh_get_content('tasks', 'do_task_button', 'Yap')); ?></a>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        <span class="task-status"><?php echo esc_html(hdh_get_content('tasks', 'pending_status', 'Beklemede')); ?></span>
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
                    <h4 class="tasks-section-title"><?php echo esc_html(hdh_get_content('tasks', 'daily_section_title', 'Günlük Görevler')); ?></h4>
                    <div class="tasks-list">
                        <?php foreach ($daily_tasks as $task) : ?>
                            <div class="task-item <?php echo $task['completed'] ? 'task-completed' : ''; ?> <?php echo (isset($task['is_locked']) && $task['is_locked']) ? 'task-locked-item' : ''; ?>" data-task-container-id="<?php echo esc_attr($task['id']); ?>">
                                <div class="task-info">
                                    <span class="task-icon">
                                        <?php
                                        $icons = array(
                                            'create_listings' => '📝',
                                            'complete_exchanges' => '🎁',
                                            'invite_friends' => '👥',
                                        );
                                        echo isset($icons[$task['id']]) ? $icons[$task['id']] : '📋';
                                        ?>
                                    </span>
                                    <div class="task-details">
                                        <span class="task-name" data-task-id="<?php echo esc_attr($task['id']); ?>">
                                            <?php echo esc_html($task['title']); ?>
                                            <?php if ($task['max_progress'] > 1 && !(isset($task['is_locked']) && $task['is_locked'])) : ?>
                                                <span class="task-progress">(<?php echo esc_html($task['progress']); ?>/<?php echo esc_html($task['max_progress']); ?>)</span>
                                            <?php endif; ?>
                                        </span>
                                        <span class="task-description"><?php echo esc_html($task['description']); ?></span>
                                        <span class="task-reward">
                                            <?php if ($task['reward_bilet'] > 0) : ?>
                                                +<?php echo esc_html($task['reward_bilet']); ?> 🎟️
                                            <?php endif; ?>
                                            <?php if ($task['reward_level'] > 0) : ?>
                                                <?php echo $task['reward_bilet'] > 0 ? '  ' : ''; ?>
                                                +<?php echo esc_html($task['reward_level']); ?> Seviye
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="task-actions <?php echo (isset($task['is_locked']) && $task['is_locked']) ? 'task-actions-locked' : ''; ?>">
                                    <?php if (isset($task['is_locked']) && $task['is_locked']) : ?>
                                        <div class="task-locked-wrapper">
                                            <span class="task-status task-locked">🔒 Kilitli</span>
                                            <small class="task-lock-hint">Bu görevi açmak için önce "<?php 
                                                $unlock_task_names = array(
                                                    'create_listings' => 'İlk ilan',
                                                    'complete_exchanges' => 'İlk hediyeleşme',
                                                    'invite_friends' => 'Davet et'
                                                );
                                                echo isset($unlock_task_names[$task['id']]) ? esc_html($unlock_task_names[$task['id']]) : 'Tek seferlik görev';
                                            ?>" görevini tamamlamalısınız</small>
                                        </div>
                                    <?php elseif ($task['can_claim']) : ?>
                                        <button class="btn-claim-task" 
                                                data-task-id="<?php echo esc_attr($task['id']); ?>" 
                                                data-is-daily="true">
                                            <?php echo esc_html(hdh_get_content('tasks', 'claim_reward_button', 'Ödülünü Al')); ?>
                                        </button>
                                    <?php elseif ($task['id'] === 'create_listings') : ?>
                                        <a href="<?php echo esc_url(home_url('/ilan-ver')); ?>" class="btn-do-task"><?php echo esc_html(hdh_get_content('tasks', 'do_task_button', 'Yap')); ?></a>
                                    <?php elseif ($task['id'] === 'invite_friends') : ?>
                                        <a href="<?php echo esc_url(home_url('/profil')); ?>" class="btn-do-task"><?php echo esc_html(hdh_get_content('tasks', 'do_task_button', 'Yap')); ?></a>
                                    <?php else : ?>
                                        <span class="task-status"><?php echo esc_html(hdh_get_content('tasks', 'pending_status', 'Beklemede')); ?></span>
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
