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
    <header>
        <div class="container">
            <div class="header-top">
                <h1><a href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a></h1>
                <?php if (get_bloginfo('description')) : ?>
                    <p class="site-description"><?php bloginfo('description'); ?></p>
                <?php endif; ?>
            </div>
            
            <?php if (is_active_sidebar('header-widget')) : ?>
                <div class="header-widget-area">
                    <?php dynamic_sidebar('header-widget'); ?>
                </div>
            <?php endif; ?>
            
            <?php mi_mobile_menu_toggle(); ?>
            
            <nav class="main-navigation">
                <?php
                $sections = mi_get_active_sections();
                $single_page_mode = get_option('mi_enable_single_page', 0) === 1;
                
                if (!empty($sections)) {
                    echo '<ul class="nav-menu">';
                    foreach ($sections as $section) {
                        $section_name = mi_get_section_name($section->ID);
                        $section_type = get_post_meta($section->ID, '_mi_section_type', true);
                        
                        // Tek sayfa modunda hash link, değilse normal permalink
                        if ($single_page_mode && is_front_page()) {
                            $section_url = '#' . 'section-' . $section->ID;
                        } else {
                            $section_url = get_permalink($section->ID);
                        }
                        
                        // Ana sayfada sadece Başyazı aktif görünsün
                        $current_class = '';
                        if (is_front_page()) {
                            // Sadece "Başyazı" bölümü aktif olsun (# içeren bölüm aktif olmasın)
                            $section_name_lower = mb_strtolower($section_name, 'UTF-8');
                            if ($section_type === 'aciklama' && (strpos($section_name_lower, 'başyazı') !== false || strpos($section_name_lower, 'basyazi') !== false)) {
                                $current_class = 'current-menu-item';
                            }
                        } elseif (is_singular('mi_section') && get_the_ID() == $section->ID) {
                            $current_class = 'current-menu-item';
                        }
                        
                        // # içeren menü item'ları için özel class ve formatlama
                        $has_hash = strpos($section_name, '#') !== false;
                        $menu_item_class = $has_hash ? 'menu-item-has-hash' : '';
                        
                        // # içeren menü item'ları için alt alta formatla
                        $display_name = $section_name;
                        if ($has_hash) {
                            $display_name = preg_replace('/\s+#/', "\n#", $section_name);
                        }
                        
                        echo '<li class="' . esc_attr($current_class . ' ' . $menu_item_class) . '"><a href="' . esc_url($section_url) . '" data-section-id="' . esc_attr($section->ID) . '">' . nl2br(esc_html($display_name)) . '</a></li>';
                    }
                    echo '</ul>';
                } else {
                    wp_nav_menu(array(
                        'theme_location' => 'primary',
                        'menu_class' => 'nav-menu',
                        'container' => false,
                        'fallback_cb' => 'default_nav_menu',
                    ));
                }
                ?>
            </nav>
            
            <?php if (get_theme_mod('mi_show_social_header', true)) : ?>
                <div class="header-social">
                    <?php mi_render_social_links(); ?>
                </div>
            <?php endif; ?>
        </div>
    </header>
    
    <?php
    // Fallback menu if no menu is assigned
    function default_nav_menu() {
        echo '<ul class="nav-menu">';
        echo '<li><a href="' . esc_url(home_url('/')) . '">Ana Sayfa</a></li>';
        echo '<li><a href="' . esc_url(home_url('/')) . '">Gündem</a></li>';
        echo '<li><a href="' . esc_url(home_url('/')) . '">Yazarlar</a></li>';
        echo '<li><a href="' . esc_url(home_url('/')) . '">Siyaset</a></li>';
        echo '<li><a href="' . esc_url(home_url('/')) . '">Ekonomi</a></li>';
        echo '<li><a href="' . esc_url(home_url('/')) . '">Dünya</a></li>';
        echo '<li><a href="' . esc_url(home_url('/')) . '">Spor</a></li>';
        echo '<li><a href="' . esc_url(home_url('/')) . '">Yaşam</a></li>';
        echo '</ul>';
    }
    ?>

