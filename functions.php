<?php
/**
 * Theme Functions
 * HDH: Hay Day Help Theme - Optimized
 */

if (!defined('ABSPATH')) {
    exit;
}

// Theme setup
function hdh_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
    add_theme_support('automatic-feed-links');
    add_theme_support('custom-logo');
    add_theme_support('editor-styles');
    add_theme_support('align-wide');
    add_theme_support('align-full');
    add_theme_support('responsive-embeds');
    
    register_nav_menus(array(
        'primary' => __('Ana Menü', 'hdh'),
        'footer' => __('Footer Menü', 'hdh'),
    ));
    
    global $content_width;
    if (!isset($content_width)) {
        $content_width = 1200;
    }
}
add_action('after_setup_theme', 'hdh_theme_setup');

// Core includes - Only essential files
require_once get_template_directory() . '/inc/items-config.php';
require_once get_template_directory() . '/components/item-card.php';
require_once get_template_directory() . '/components/trade-card.php';
require_once get_template_directory() . '/inc/trade-offers.php';
require_once get_template_directory() . '/inc/create-trade-handler.php';
require_once get_template_directory() . '/inc/trade-settings.php';
require_once get_template_directory() . '/inc/registration-handler.php';
require_once get_template_directory() . '/inc/trust-system.php';
require_once get_template_directory() . '/inc/widgets.php';
require_once get_template_directory() . '/inc/social-functions.php';
require_once get_template_directory() . '/social-share.php';

// Enqueue styles and scripts
function hdh_enqueue_scripts() {
    wp_enqueue_style('hdh-farm-style', get_template_directory_uri() . '/assets/css/farm-style.css', array(), '3.3.0');
    
    wp_enqueue_script('jquery');
    
    wp_enqueue_script(
        'hdh-cartoon-interactions',
        get_template_directory_uri() . '/assets/js/cartoon-interactions.js',
        array('jquery'),
        '2.0.0',
        true
    );
    
    wp_enqueue_script(
        'hdh-mobile-menu',
        get_template_directory_uri() . '/assets/js/mobile-menu.js',
        array(),
        '1.0.0',
        true
    );
    
    if (is_front_page()) {
        wp_enqueue_script(
            'hdh-trade-form',
            get_template_directory_uri() . '/assets/js/trade-form.js',
            array(),
            '1.1.0',
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'hdh_enqueue_scripts');

// Preload critical assets
function hdh_preload_assets() {
    if (is_front_page()) {
        $items = hdh_get_items_config();
        foreach ($items as $item) {
            echo '<link rel="preload" as="image" href="' . esc_url($item['image']) . '" type="image/svg+xml">' . "\n";
        }
    }
}
add_action('wp_head', 'hdh_preload_assets', 1);

// Add body classes
function hdh_body_classes($classes) {
    if (is_front_page()) {
        $classes[] = 'hdh-home';
    }
    $classes[] = 'theme-hayday-winter';
    return $classes;
}
add_filter('body_class', 'hdh_body_classes');
