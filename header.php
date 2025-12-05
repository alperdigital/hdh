<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="<?php echo esc_url(get_template_directory_uri() . '/assets/favicon.svg'); ?>">
    <?php wp_head(); ?>
</head>
<body <?php 
    body_class(); 
    $single_page_mode = get_option('mi_enable_single_page', 0) === 1;
    if ($single_page_mode) {
        echo ' data-single-page-mode="1"';
    }
?>>
    <!-- HDH: SVG Icons Sprite -->
    <?php include get_template_directory() . '/assets/svg/farm-icons.svg'; ?>
    
    <!-- HDH: Cartoon Farm Announcement Banner -->
    <?php if (get_theme_mod('hdh_show_announcement', true)) : ?>
        <div class="farm-announcement-banner">
            <div class="container">
                <p class="farm-announcement-text">
                    <?php 
                    $announcement = get_theme_mod('hdh_announcement_text', '🌾 Hay Day Rehber, Etkinlik ve Çekiliş Merkezi!');
                    echo esc_html($announcement);
                    ?>
                </p>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- HDH: Cartoon Wooden Board Header -->
    <header class="cartoon-header" id="cartoon-header">
        <div class="header-wooden-board">
            <div class="container">
                <!-- Logo Section -->
                <div class="header-top">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="site-logo">
                        <div class="site-logo-icon">🌾</div>
                        <h1 class="site-title">
                            <?php 
                            $site_name = get_bloginfo('name');
                            if (empty($site_name) || $site_name === 'HDH') {
                                echo 'hayday.help';
                            } else {
                                echo esc_html($site_name);
                            }
                            ?>
                        </h1>
                    </a>
                    
                    <!-- Mobile Menu Toggle Button -->
                    <button class="mobile-menu-toggle" aria-label="Menüyü Aç/Kapat" aria-expanded="false">
                        <span class="hamburger-line"></span>
                        <span class="hamburger-line"></span>
                        <span class="hamburger-line"></span>
                    </button>
                </div>
                
                <!-- HDH: Cartoon Navigation Menu -->
                <nav class="cartoon-navigation" id="main-navigation">
                    <?php
                    $sections = mi_get_active_sections();
                    $single_page_mode = get_option('mi_enable_single_page', 0) === 1;
                    
                    if (!empty($sections)) {
                        echo '<ul class="cartoon-nav">';
                        foreach ($sections as $section) {
                            $section_name = mi_get_section_name($section->ID);
                            $section_type = get_post_meta($section->ID, '_mi_section_type', true);
                            
                            if ($single_page_mode && is_front_page()) {
                                $section_url = '#' . 'section-' . $section->ID;
                            } else {
                                $section_url = get_permalink($section->ID);
                            }
                            
                            $current_class = '';
                            if (is_singular('mi_section') && get_the_ID() == $section->ID) {
                                $current_class = 'current-menu-item';
                            }
                            
                            // HDH: Icon mapping for sections
                            $icons = array(
                                'aciklama' => '📝',
                                'manset' => '📰',
                                'kararlar' => '📋',
                                'iletisim' => '📞'
                            );
                            $icon = isset($icons[$section_type]) ? $icons[$section_type] : '🌾';
                            
                            echo '<li class="cartoon-nav-item ' . esc_attr($current_class) . '">';
                            echo '<a href="' . esc_url($section_url) . '" class="cartoon-nav-link" data-section-id="' . esc_attr($section->ID) . '">';
                            echo '<span class="nav-icon">' . esc_html($icon) . '</span>';
                            echo '<span>' . esc_html($section_name) . '</span>';
                            echo '</a></li>';
                        }
                        echo '</ul>';
                    } else {
                        // HDH: Default cartoon menu - Updated for trading platform
                        wp_nav_menu(array(
                            'theme_location' => 'primary',
                            'menu_class' => 'cartoon-nav',
                            'container' => false,
                            'fallback_cb' => 'hdh_default_cartoon_menu',
                        ));
                    }
                    ?>
                </nav>
            </div>
        </div>
    </header>
    
    <?php
    // HDH: Default cartoon menu fallback - Updated for trading platform
    function hdh_default_cartoon_menu() {
        $menu_items = array(
            array('icon' => '🏠', 'text' => 'Ana Sayfa', 'url' => home_url('/')),
            array('icon' => '🎨', 'text' => 'Ücretsiz Dekorasyonlar', 'url' => home_url('/')),
            array('icon' => '🎁', 'text' => 'Çekilişe Katıl', 'url' => home_url('/')),
            array('icon' => '🔄', 'text' => 'Takas Yap', 'url' => home_url('/#trade-feed')),
            array('icon' => '👥', 'text' => 'Mahalleye Katıl', 'url' => home_url('/')),
        );
        
        echo '<ul class="cartoon-nav">';
        foreach ($menu_items as $item) {
            echo '<li class="cartoon-nav-item">';
            echo '<a href="' . esc_url($item['url']) . '" class="cartoon-nav-link">';
            echo '<span class="nav-icon">' . esc_html($item['icon']) . '</span>';
            echo '<span>' . esc_html($item['text']) . '</span>';
            echo '</a></li>';
        }
        echo '</ul>';
    }
    ?>

