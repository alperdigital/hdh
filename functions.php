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
require_once get_template_directory() . '/inc/ajax-handlers.php';
require_once get_template_directory() . '/social-share.php';

// Enqueue styles and scripts
function hdh_enqueue_scripts() {
    wp_enqueue_style('hdh-farm-style', get_template_directory_uri() . '/assets/css/farm-style.css', array(), '3.23.0');
    
    wp_enqueue_script('jquery');
    
    wp_enqueue_script(
        'hdh-cartoon-interactions',
        get_template_directory_uri() . '/assets/js/cartoon-interactions.js',
        array('jquery'),
        '2.1.0',
        true
    );
    
    wp_enqueue_script(
        'hdh-bottom-navigation',
        get_template_directory_uri() . '/assets/js/bottom-navigation.js',
        array(),
        '1.0.0',
        true
    );
    
    // Enqueue trade form script on ilan-ver page
    if (is_page_template('page-ilan-ver.php')) {
        wp_enqueue_script(
            'hdh-trade-form',
            get_template_directory_uri() . '/assets/js/trade-form.js',
            array(),
            '1.1.0',
            true
        );
    }
    
    // Enqueue trade filter script on ara page
    if (is_page_template('page-ara.php')) {
        wp_enqueue_script(
            'hdh-trade-filter',
            get_template_directory_uri() . '/assets/js/trade-filter.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('hdh-trade-filter', 'hdhFilter', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hdh_filter_trades'),
        ));
    }
    
    // Enqueue profile page script on profil page
    if (is_page_template('page-profil.php')) {
        wp_enqueue_script(
            'hdh-profile-page',
            get_template_directory_uri() . '/assets/js/profile-page.js',
            array(),
            '1.0.0',
            true
        );
        wp_enqueue_script(
            'hdh-tasks-panel',
            get_template_directory_uri() . '/assets/js/tasks-panel.js',
            array(),
            '1.0.0',
            true
        );
        wp_localize_script('hdh-tasks-panel', 'hdhTasks', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hdh_claim_daily_jeton'),
        ));
    }
    
    // Enqueue trade offer script on single trade pages
    if (is_singular('hayday_trade')) {
        wp_enqueue_script(
            'hdh-trade-offer',
            get_template_directory_uri() . '/assets/js/trade-offer.js',
            array(),
            '1.1.0',
            true
        );
        wp_localize_script('hdh-trade-offer', 'hdhOffer', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hdh_create_offer'),
        ));
    }
    
    // Enqueue lottery scripts on lottery page
    if (is_page_template('page-cekilis.php')) {
        wp_enqueue_script(
            'hdh-lottery-countdown',
            get_template_directory_uri() . '/assets/js/lottery-countdown.js',
            array(),
            '1.0.0',
            true
        );
        wp_enqueue_script(
            'hdh-lottery-page',
            get_template_directory_uri() . '/assets/js/lottery-page.js',
            array(),
            '1.0.0',
            true
        );
        wp_localize_script('hdh-lottery-page', 'hdhLottery', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hdh_join_lottery'),
        ));
    }
}
add_action('wp_enqueue_scripts', 'hdh_enqueue_scripts');

// Preload critical assets
function hdh_preload_assets() {
    if (is_front_page() || is_page_template('page-ara.php') || is_page_template('page-ilan-ver.php')) {
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

// Auto-create required pages on theme activation
function hdh_create_required_pages() {
    // Check if pages already exist
    $ara_page = get_page_by_path('ara');
    $ilan_ver_page = get_page_by_path('ilan-ver');
    
    // Create "Ara" page if it doesn't exist
    if (!$ara_page) {
        $ara_page_id = wp_insert_post(array(
            'post_title' => 'Hediye Ara',
            'post_name' => 'ara',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_content' => ''
        ));
        
        if ($ara_page_id && !is_wp_error($ara_page_id)) {
            // Set template using template name (not file name)
            update_post_meta($ara_page_id, '_wp_page_template', 'page-ara.php');
        }
    } else {
        // Update existing page template if needed
        update_post_meta($ara_page->ID, '_wp_page_template', 'page-ara.php');
    }
    
    // Create "İlan Ver" page if it doesn't exist
    if (!$ilan_ver_page) {
        $ilan_ver_page_id = wp_insert_post(array(
            'post_title' => 'İlan Ver',
            'post_name' => 'ilan-ver',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_content' => ''
        ));
        
        if ($ilan_ver_page_id && !is_wp_error($ilan_ver_page_id)) {
            // Set template using template name (not file name)
            update_post_meta($ilan_ver_page_id, '_wp_page_template', 'page-ilan-ver.php');
        }
    } else {
        // Update existing page template if needed
        update_post_meta($ilan_ver_page->ID, '_wp_page_template', 'page-ilan-ver.php');
    }
}
add_action('after_switch_theme', 'hdh_create_required_pages');

// Also run on admin init to ensure pages exist
function hdh_check_required_pages() {
    if (is_admin() && current_user_can('manage_options')) {
        hdh_create_required_pages();
        // Flush rewrite rules to ensure permalinks work
        flush_rewrite_rules(false);
    }
}
add_action('admin_init', 'hdh_check_required_pages');

// Force create pages on any page load (one-time check)
function hdh_ensure_required_pages() {
    static $pages_checked = false;
    if ($pages_checked) {
        return;
    }
    $pages_checked = true;
    
    $ara_page = get_page_by_path('ara');
    $ilan_ver_page = get_page_by_path('ilan-ver');
    
    if (!$ara_page || !$ilan_ver_page) {
        hdh_create_required_pages();
        flush_rewrite_rules(false);
    }
}
add_action('init', 'hdh_ensure_required_pages', 1);
