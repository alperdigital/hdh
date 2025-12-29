<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="<?php echo esc_url(get_template_directory_uri() . '/assets/favicon.svg'); ?>">
    <?php wp_head(); ?>
</head>
<body <?php body_class('theme-hayday-winter'); ?>>
    <!-- HDH: SVG Icons Sprite -->
    <?php include get_template_directory() . '/assets/svg/farm-icons.svg'; ?>
    
    <!-- HDH: Header Banner / User Info -->
    <?php if (is_user_logged_in()) : 
        // LOGIN SONRASI: Kullanıcı bilgileri göster
        $current_user_id = get_current_user_id();
        $current_user = wp_get_current_user();
        $farm_name = $current_user->display_name;
        
        // Get user level and bilet balance
        $user_state = function_exists('hdh_get_user_state') ? hdh_get_user_state($current_user_id) : null;
        $user_level = $user_state ? $user_state['level'] : 1;
        $bilet_balance = $user_state ? $user_state['bilet_balance'] : 0;
        
        // Get completed gift count
        $completed_gift_count = function_exists('hdh_get_completed_gift_count') ? hdh_get_completed_gift_count($current_user_id) : 0;
        
        // Get task count (incomplete/claimable tasks)
        $task_count = 0;
        if (function_exists('hdh_get_user_one_time_tasks') && function_exists('hdh_get_user_daily_tasks')) {
            try {
                $one_time_tasks = hdh_get_user_one_time_tasks($current_user_id);
                $daily_tasks = hdh_get_user_daily_tasks($current_user_id);
                
                if (is_array($one_time_tasks)) {
                    foreach ($one_time_tasks as $task) {
                        $claimable_count = isset($task['claimable_count']) ? (int) $task['claimable_count'] : 0;
                        if ($claimable_count > 0) {
                            $task_count++;
                        }
                    }
                }
                
                if (is_array($daily_tasks)) {
                    foreach ($daily_tasks as $task) {
                        $claimable_count = isset($task['claimable_count']) ? (int) $task['claimable_count'] : 0;
                        if ($claimable_count > 0) {
                            $task_count++;
                        }
                    }
                }
            } catch (Exception $e) {
                error_log('HDH Header: Error getting task count: ' . $e->getMessage());
                $task_count = 0;
            }
        }
        
        // Determine digit class based on level
        $level_int = (int) $user_level;
        $digits = strlen((string)$level_int);
        $digit_class = $digits === 1 ? 'lvl-d1' : ($digits === 2 ? 'lvl-d2' : 'lvl-d3');
        ?>
        <div class="hdh-header-user-info">
            <div class="hdh-header-user-content">
                <div class="hdh-level-badge <?php echo esc_attr($digit_class); ?>" 
                     aria-label="Seviye <?php echo esc_attr($user_level); ?>"
                     title="Seviye <?php echo esc_attr($user_level); ?>">
                    <?php echo esc_html($user_level); ?>
                </div>
                <div class="hdh-header-farm-info">
                    <span class="hdh-header-farm-name"><?php echo esc_html($farm_name); ?></span>
                    <div class="hdh-header-stats">
                        <span class="hdh-header-stat-item">
                            <span class="hdh-header-stat-emoji">🎁</span>
                            <span class="hdh-header-stat-value"><?php echo esc_html($completed_gift_count); ?></span>
                        </span>
                        <span class="hdh-header-stat-item">
                            <span class="hdh-header-stat-emoji">🎟️</span>
                            <span class="hdh-header-stat-value"><?php echo esc_html(number_format($bilet_balance, 0, ',', '.')); ?></span>
                        </span>
                    </div>
                </div>
                <div class="hdh-header-tasks-link">
                    <button type="button" class="hdh-header-tasks-button" id="hdh-header-tasks-button" aria-label="Görevler">
                        <span class="hdh-header-tasks-text">Görevler</span>
                        <span class="hdh-header-tasks-count" id="hdh-header-tasks-count">(<?php echo esc_html($task_count); ?>)</span>
                    </button>
                </div>
            </div>
        </div>
    <?php else : 
        // LOGIN ÖNCESİ: Banner göster
        if (get_theme_mod('hdh_show_announcement', true)) : ?>
            <div class="farm-announcement-banner">
                <div class="container">
                    <p class="farm-announcement-text">
                        <?php echo esc_html(hdh_get_content('homepage', 'announcement_text', '🎁 Hediyeleşme ve Çekiliş Merkezi!')); ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    

