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
    
    <!-- HDH: User Info Widget (Fixed Top Left) -->
    <?php if (is_user_logged_in()) : 
        $current_user_id = get_current_user_id();
        $current_user = wp_get_current_user();
        $farm_name = $current_user->display_name;
        
        // Get user level and bilet balance
        $user_state = function_exists('hdh_get_user_state') ? hdh_get_user_state($current_user_id) : null;
        $user_level = $user_state ? $user_state['level'] : 1;
        $bilet_balance = $user_state ? $user_state['bilet_balance'] : 0;
        
        // Get completed gift count
        $completed_gift_count = function_exists('hdh_get_completed_gift_count') ? hdh_get_completed_gift_count($current_user_id) : 0;
        
        // Determine digit class based on level
        $level_int = (int) $user_level;
        $digits = strlen((string)$level_int);
        $digit_class = $digits === 1 ? 'lvl-d1' : ($digits === 2 ? 'lvl-d2' : 'lvl-d3');
        ?>
        <div class="hdh-user-info-widget">
            <div class="hdh-level-badge <?php echo esc_attr($digit_class); ?>" 
                 aria-label="Seviye <?php echo esc_attr($user_level); ?>"
                 title="Seviye <?php echo esc_attr($user_level); ?>">
                <?php echo esc_html($user_level); ?>
            </div>
            <div class="hdh-farm-info">
                <span class="hdh-farm-name"><?php echo esc_html($farm_name); ?></span>
                <div class="hdh-farm-stats">
                    <span class="hdh-stat-item">
                        <span class="hdh-stat-emoji">🎁</span>
                        <span class="hdh-stat-value"><?php echo esc_html($completed_gift_count); ?></span>
                    </span>
                    <span class="hdh-stat-item">
                        <span class="hdh-stat-emoji">🎟️</span>
                        <span class="hdh-stat-value"><?php echo esc_html(number_format($bilet_balance, 0, ',', '.')); ?></span>
                    </span>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- HDH: Cartoon Farm Announcement Banner -->
    <?php if (get_theme_mod('hdh_show_announcement', true)) : ?>
        <div class="farm-announcement-banner">
            <div class="container">
                <p class="farm-announcement-text">
                    <?php echo esc_html(hdh_get_content('homepage', 'announcement_text', '🎁 Hediyeleşme ve Çekiliş Merkezi!')); ?>
                </p>
            </div>
        </div>
    <?php endif; ?>
    

