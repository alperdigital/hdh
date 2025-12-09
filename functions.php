<?php
/**
 * Theme Functions
 * HDH: Hay Day Help Theme
 */

// Theme setup
function hdh_theme_setup() {
    // Add theme support
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
    add_theme_support('automatic-feed-links');
    add_theme_support('custom-logo');
    add_theme_support('editor-styles');
    add_theme_support('align-wide');
    add_theme_support('align-full');
    add_theme_support('responsive-embeds');
    
    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('Ana Menü', 'hdh'),
        'footer' => __('Footer Menü', 'hdh'),
    ));
    
    // Set content width
    global $content_width;
    if (!isset($content_width)) {
        $content_width = 1200;
    }
}
add_action('after_setup_theme', 'hdh_theme_setup');

// HDH: Include Items Configuration
require_once get_template_directory() . '/inc/items-config.php';

// HDH: Include Components
require_once get_template_directory() . '/components/item-card.php';
require_once get_template_directory() . '/components/trade-card.php';

// HDH: Include Trade System
require_once get_template_directory() . '/inc/trade-offers.php';
require_once get_template_directory() . '/inc/create-trade-handler.php';
require_once get_template_directory() . '/inc/trade-settings.php';

// HDH: Include User System
require_once get_template_directory() . '/inc/registration-handler.php';
require_once get_template_directory() . '/inc/trust-system.php';

// HDH: Include Widgets and Social Share
require_once get_template_directory() . '/inc/widgets.php';
require_once get_template_directory() . '/social-share.php';

// Enqueue styles and scripts
function hdh_enqueue_scripts() {
    // Main Farm Styles
    wp_enqueue_style('hdh-farm-style', get_template_directory_uri() . '/assets/css/farm-style.css', array(), '3.2.0');
    
    // Core Scripts
    wp_enqueue_script('jquery');
    
    // Farm Interactions
    wp_enqueue_script(
        'hdh-cartoon-interactions',
        get_template_directory_uri() . '/assets/js/cartoon-interactions.js',
        array('jquery'),
        '2.0.0',
        true
    );
    
    // Mobile Menu
    wp_enqueue_script(
        'hdh-mobile-menu',
        get_template_directory_uri() . '/assets/js/mobile-menu.js',
        array(),
        '1.0.0',
        true
    );
    
    // Trade Form Script (Only on Front Page)
    if (is_front_page()) {
        wp_enqueue_script(
            'hdh-trade-form',
            get_template_directory_uri() . '/assets/js/trade-form.js',
            array(), // Remove jQuery dependency - using vanilla JS
            '1.1.0', // Version bump to bypass cache
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'hdh_enqueue_scripts');

// Preload Critical Assets
function hdh_preload_assets() {
    if (is_front_page()) {
        $items = hdh_get_items_config();
        foreach ($items as $item) {
            echo '<link rel="preload" as="image" href="' . esc_url($item['image']) . '" type="image/svg+xml">' . "\n";
        }
    }
}
add_action('wp_head', 'hdh_preload_assets', 1);

// Add Body Classes
function hdh_body_classes($classes) {
    if (is_front_page()) {
        $classes[] = 'hdh-home';
    }
    
    return $classes;
}
add_filter('body_class', 'hdh_body_classes');

// Disable Auto-P for Shortcodes/Content where needed
remove_filter('the_content', 'wpautop');
add_filter('the_content', 'wpautop', 12);
