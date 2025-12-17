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

// Hide WordPress admin bar for non-admin users
add_action('after_setup_theme', function() {
    if (!current_user_can('administrator')) {
        show_admin_bar(false);
    }
});

// Core includes - Only essential files
require_once get_template_directory() . '/inc/items-config.php';
require_once get_template_directory() . '/components/item-card.php';
require_once get_template_directory() . '/components/trade-card.php';
require_once get_template_directory() . '/inc/trade-offers.php';
require_once get_template_directory() . '/inc/jeton-system.php';
require_once get_template_directory() . '/inc/create-trade-handler.php';
require_once get_template_directory() . '/inc/trade-settings.php';
require_once get_template_directory() . '/inc/registration-handler.php';
require_once get_template_directory() . '/inc/trust-system.php';
require_once get_template_directory() . '/inc/listing-actions-handler.php';
require_once get_template_directory() . '/inc/offers-cpt.php';
require_once get_template_directory() . '/inc/offers-handler.php';
require_once get_template_directory() . '/inc/widgets.php';
require_once get_template_directory() . '/inc/social-functions.php';
require_once get_template_directory() . '/inc/ajax-handlers.php';
require_once get_template_directory() . '/inc/lottery-config.php';
require_once get_template_directory() . '/inc/auth-redirect.php';
require_once get_template_directory() . '/inc/asset-loader.php';
require_once get_template_directory() . '/inc/trade-integrity.php';
require_once get_template_directory() . '/inc/event-system.php';
require_once get_template_directory() . '/inc/user-state-system.php';
require_once get_template_directory() . '/inc/kvkk-compliance.php';
require_once get_template_directory() . '/inc/moderation-system.php';
require_once get_template_directory() . '/inc/admin-moderation-ui.php';
require_once get_template_directory() . '/inc/trust-display.php';
require_once get_template_directory() . '/components/user-badge.php';
require_once get_template_directory() . '/components/quest-panel.php';
require_once get_template_directory() . '/components/tasks-panel.php';
require_once get_template_directory() . '/components/share-buttons.php';
require_once get_template_directory() . '/inc/seo-handler.php';
require_once get_template_directory() . '/inc/share-image-generator.php';
require_once get_template_directory() . '/inc/share-tracking-handler.php';
require_once get_template_directory() . '/inc/email-verification.php';
require_once get_template_directory() . '/inc/firebase-config.php';
require_once get_template_directory() . '/inc/firebase-verification.php';
require_once get_template_directory() . '/inc/quest-system.php';
require_once get_template_directory() . '/inc/tasks-system.php';
require_once get_template_directory() . '/inc/tasks-handler.php';
require_once get_template_directory() . '/inc/tasks-admin.php';
require_once get_template_directory() . '/inc/items-admin.php';
require_once get_template_directory() . '/inc/content-management.php';
require_once get_template_directory() . '/inc/content-admin.php';
require_once get_template_directory() . '/inc/messages-admin.php';
require_once get_template_directory() . '/inc/settings-admin.php';
require_once get_template_directory() . '/social-share.php';

// Enqueue styles and scripts
function hdh_enqueue_scripts() {
    wp_enqueue_style('hdh-farm-style', get_template_directory_uri() . '/assets/css/farm-style.css', array(), '3.23.0');
    
    // Enqueue single trade CSS on single trade pages
    if (is_singular('hayday_trade')) {
        wp_enqueue_style('hdh-single-trade', get_template_directory_uri() . '/assets/css/single-trade.css', array('hdh-farm-style'), '1.0.0');
    }
    
    // Enqueue legal pages CSS
    if (is_page_template('page-uyelik-sozlesmesi.php') || is_page_template('page-gizlilik-politikasi.php')) {
        wp_enqueue_style('hdh-legal-pages', get_template_directory_uri() . '/assets/css/legal-pages.css', array('hdh-farm-style'), '1.0.0');
    }
    
    // Enqueue 404 page CSS
    if (is_404()) {
        wp_enqueue_style('hdh-404-page', get_template_directory_uri() . '/assets/css/404-page.css', array('hdh-farm-style'), '1.0.0');
    }
    
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
    
    // Enqueue user info widget script (for logged-in users)
    if (is_user_logged_in()) {
        wp_enqueue_script(
            'hdh-user-info-widget',
            get_template_directory_uri() . '/assets/js/user-info-widget.js',
            array(),
            '1.0.0',
            true
        );
    }
    
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
        wp_enqueue_style(
            'hdh-loading-states',
            get_template_directory_uri() . '/assets/css/loading-states.css',
            array('hdh-farm-style'),
            '1.0.0'
        );
        
        wp_enqueue_script(
            'hdh-trade-filter',
            get_template_directory_uri() . '/assets/js/trade-filter.js',
            array('jquery'),
            '2.0.0',
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
        // Auth screen (for logged-out users)
        wp_enqueue_script(
            'hdh-auth-screen',
            get_template_directory_uri() . '/assets/js/auth-screen.js',
            array(),
            '1.0.0',
            true
        );
        
        // Profile page interactions (for logged-in users)
        wp_enqueue_script(
            'hdh-profile-page',
            get_template_directory_uri() . '/assets/js/profile-page.js',
            array(),
            '1.0.0',
            true
        );
        
        // Email verification script (for logged-in users on profile page)
        wp_enqueue_script(
            'hdh-email-verification',
            get_template_directory_uri() . '/assets/js/email-verification.js',
            array(),
            '1.0.0',
            true
        );
        
        // Firebase SDK (if configured)
        if (function_exists('hdh_is_firebase_configured') && hdh_is_firebase_configured()) {
            // Firebase App SDK
            wp_enqueue_script(
                'firebase-app',
                'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js',
                array(),
                '10.7.1',
                true
            );
            
            // Firebase Auth SDK
            wp_enqueue_script(
                'firebase-auth',
                'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js',
                array('firebase-app'),
                '10.7.1',
                true
            );
            
            // Firebase verification script
            wp_enqueue_script(
                'hdh-firebase-verification',
                get_template_directory_uri() . '/assets/js/firebase-verification.js',
                array('firebase-auth'),
                '1.0.0',
                true
            );
        }
    }
    
    // Tasks panel (for logged-in users on ALL pages)
    if (is_user_logged_in()) {
        wp_enqueue_script(
            'hdh-tasks-panel',
            get_template_directory_uri() . '/assets/js/tasks-panel.js',
            array(),
            '1.0.0',
            true
        );
        wp_localize_script('hdh-tasks-panel', 'hdhTasks', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hdh_claim_task_reward'),
            'siteUrl' => home_url(),
        ));
    }
    
        // Enqueue quest panel script on homepage
        if (is_front_page() && is_user_logged_in()) {
            wp_enqueue_script(
                'hdh-quest-panel',
                get_template_directory_uri() . '/assets/js/quest-panel.js',
                array(),
                '1.0.0',
                true
            );
        }
        
        // Enqueue cookie consent script on all pages
        wp_enqueue_script(
            'hdh-cookie-consent',
            get_template_directory_uri() . '/assets/js/cookie-consent.js',
            array(),
            '1.0.0',
            true
        );
    
    // Enqueue single trade page script
    if (is_singular('hayday_trade')) {
        wp_enqueue_script(
            'hdh-single-trade',
            get_template_directory_uri() . '/assets/js/single-trade.js',
            array(),
            '2.0.0',
            true
        );
        wp_localize_script('hdh-single-trade', 'hdhSingleTrade', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'makeOfferNonce' => wp_create_nonce('hdh_make_offer'),
            'offerResponseNonce' => wp_create_nonce('hdh_offer_response'),
            'messagingNonce' => wp_create_nonce('hdh_messaging'),
            'confirmExchangeNonce' => wp_create_nonce('hdh_confirm_exchange'),
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
    
    // Enqueue profile page script with localized data
    if (is_page_template('page-profil.php') && is_user_logged_in()) {
            wp_localize_script('hdh-profile-page', 'hdhProfile', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('hdh_listing_actions'),
            ));
            
            // Localize email verification script
            wp_localize_script('hdh-email-verification', 'hdhProfile', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('hdh_email_verification'),
            ));
            
            // Localize Firebase verification script (if Firebase is configured)
            if (function_exists('hdh_is_firebase_configured') && hdh_is_firebase_configured()) {
                $current_user = wp_get_current_user();
                $firebase_config = hdh_get_firebase_config();
                
                wp_localize_script('hdh-firebase-verification', 'hdhFirebase', array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('hdh_firebase_verification'),
                    'config' => $firebase_config,
                    'userEmail' => $current_user->user_email,
                ));
            }
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
    // Define all required pages
    $required_pages = array(
        array(
            'title' => 'Hediye Ara',
            'slug' => 'ara',
            'template' => 'page-ara.php'
        ),
        array(
            'title' => 'İlan Ver',
            'slug' => 'ilan-ver',
            'template' => 'page-ilan-ver.php'
        ),
        array(
            'title' => 'Profil',
            'slug' => 'profil',
            'template' => 'page-profil.php'
        ),
        array(
            'title' => 'Çekiliş',
            'slug' => 'cekilis',
            'template' => 'page-cekilis.php'
        ),
        array(
            'title' => 'Hazine',
            'slug' => 'hazine',
            'template' => 'page-dekorlar.php'
        ),
        array(
            'title' => 'Üyelik Sözleşmesi',
            'slug' => 'uyelik-sozlesmesi',
            'template' => 'page-uyelik-sozlesmesi.php'
        ),
        array(
            'title' => 'Gizlilik Politikası',
            'slug' => 'gizlilik-politikasi',
            'template' => 'page-gizlilik-politikasi.php'
        )
    );
    
    // Create or update each page
    foreach ($required_pages as $page_data) {
        $existing_page = get_page_by_path($page_data['slug']);
        
        if (!$existing_page) {
            // Create new page
            $page_id = wp_insert_post(array(
                'post_title' => $page_data['title'],
                'post_name' => $page_data['slug'],
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_content' => ''
            ));
            
            if ($page_id && !is_wp_error($page_id)) {
                update_post_meta($page_id, '_wp_page_template', $page_data['template']);
            }
        } else {
            // Update existing page template if needed
            update_post_meta($existing_page->ID, '_wp_page_template', $page_data['template']);
        }
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
    
    // Check if all required pages exist
        $required_slugs = array('ara', 'ilan-ver', 'profil', 'cekilis', 'hazine', 'uyelik-sozlesmesi', 'gizlilik-politikasi');
    $missing_pages = false;
    
    foreach ($required_slugs as $slug) {
        if (!get_page_by_path($slug)) {
            $missing_pages = true;
            break;
        }
    }
    
    if ($missing_pages) {
        hdh_create_required_pages();
        flush_rewrite_rules(false);
    }
}
add_action('init', 'hdh_ensure_required_pages', 1);
