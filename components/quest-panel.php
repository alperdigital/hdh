<?php
/**
 * Quest Panel Component
 */

if (!defined('ABSPATH')) exit;

/**
 * Render quest panel
 */
function hdh_render_quest_panel($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) return '';
    
    $main_quests = function_exists('hdh_get_main_quests') ? hdh_get_main_quests($user_id) : array();
    $side_quests = function_exists('hdh_get_side_quests') ? hdh_get_side_quests($user_id) : array();
    
    ob_start();
    ?>
    <div class="quest-panel" id="quest-panel">
        <div class="quest-panel-header">
            <h3 class="quest-panel-title">Görevler</h3>
            <button class="quest-panel-close" id="quest-panel-close">×</button>
        </div>
        
        <div class="quest-panel-content">
            <?php if (!empty($main_quests)) : ?>
                <div class="quest-section">
                    <h4 class="quest-section-title">Ana Görevler</h4>
                    <div class="quest-list">
                        <?php foreach ($main_quests as $quest) : ?>
                            <div class="quest-item <?php echo $quest['completed'] ? 'quest-completed' : ''; ?>">
                                <div class="quest-info">
                                    <h5 class="quest-title"><?php echo esc_html($quest['title']); ?></h5>
                                    <p class="quest-description"><?php echo esc_html($quest['description']); ?></p>
                                    <div class="quest-progress">
                                        <div class="quest-progress-bar">
                                            <div class="quest-progress-fill" style="width: <?php echo min(100, ($quest['progress'] / $quest['max_progress']) * 100); ?>%"></div>
                                        </div>
                                        <span class="quest-progress-text"><?php echo esc_html($quest['progress']); ?>/<?php echo esc_html($quest['max_progress']); ?></span>
                                    </div>
                                </div>
                                <div class="quest-rewards">
                                    <?php if ($quest['reward_tickets'] > 0) : ?>
                                        <span class="quest-reward">+<?php echo esc_html($quest['reward_tickets']); ?> 🎟️</span>
                                    <?php endif; ?>
                                    <?php if ($quest['reward_xp'] > 0) : ?>
                                        <span class="quest-reward">+<?php echo esc_html($quest['reward_xp']); ?> XP</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($side_quests)) : ?>
                <div class="quest-section">
                    <h4 class="quest-section-title">Günlük Görevler</h4>
                    <div class="quest-list">
                        <?php foreach ($side_quests as $quest) : ?>
                            <div class="quest-item <?php echo $quest['completed'] ? 'quest-completed' : ''; ?>">
                                <div class="quest-info">
                                    <h5 class="quest-title"><?php echo esc_html($quest['title']); ?></h5>
                                    <p class="quest-description"><?php echo esc_html($quest['description']); ?></p>
                                    <div class="quest-progress">
                                        <div class="quest-progress-bar">
                                            <div class="quest-progress-fill" style="width: <?php echo min(100, ($quest['progress'] / $quest['max_progress']) * 100); ?>%"></div>
                                        </div>
                                        <span class="quest-progress-text"><?php echo esc_html($quest['progress']); ?>/<?php echo esc_html($quest['max_progress']); ?></span>
                                    </div>
                                </div>
                                <div class="quest-rewards">
                                    <?php if ($quest['reward_tickets'] > 0) : ?>
                                        <span class="quest-reward">+<?php echo esc_html($quest['reward_tickets']); ?> 🎟️</span>
                                    <?php endif; ?>
                                    <?php if ($quest['reward_xp'] > 0) : ?>
                                        <span class="quest-reward">+<?php echo esc_html($quest['reward_xp']); ?> XP</span>
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
    return ob_get_clean();
}

