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
    
    <!-- HDH: Cartoon Farm Announcement Banner -->
    <?php if (get_theme_mod('hdh_show_announcement', true)) : ?>
        <div class="farm-announcement-banner">
            <div class="container">
                <p class="farm-announcement-text">
                    <?php 
                    $announcement = get_theme_mod('hdh_announcement_text', '🎁 Rehber, Hediyeleşme ve Çekiliş Merkezi!');
                    echo esc_html($announcement);
                    ?>
                </p>
            </div>
        </div>
    <?php endif; ?>
    

