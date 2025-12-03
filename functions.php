<?php
/**
 * Theme Functions
 */

// Theme setup
function mi_theme_setup() {
    // Add theme support
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
    add_theme_support('automatic-feed-links');
    add_theme_support('custom-logo');
    
    // Gutenberg support
    add_theme_support('editor-styles');
    add_theme_support('align-wide');
    add_theme_support('align-full');
    add_theme_support('responsive-embeds');
    add_editor_style('editor-style.css');
    
    // Editor color palette
    add_theme_support('editor-color-palette', array(
        array(
            'name' => __('Kırmızı', 'hdh'),
            'slug' => 'primary-red',
            'color' => '#C41E3A',
        ),
        array(
            'name' => __('Koyu Kırmızı', 'hdh'),
            'slug' => 'primary-dark',
            'color' => '#A01A2E',
        ),
        array(
            'name' => __('Koyu Gri', 'hdh'),
            'slug' => 'text-dark',
            'color' => '#1A1A1A',
        ),
        array(
            'name' => __('Gri', 'hdh'),
            'slug' => 'text-gray',
            'color' => '#4D4D4D',
        ),
        array(
            'name' => __('Beyaz', 'hdh'),
            'slug' => 'white',
            'color' => '#FFFFFF',
        ),
    ));
    
    // Editor font sizes
    add_theme_support('editor-font-sizes', array(
        array(
            'name' => __('Küçük', 'hdh'),
            'size' => 14,
            'slug' => 'small',
        ),
        array(
            'name' => __('Normal', 'hdh'),
            'size' => 16,
            'slug' => 'normal',
        ),
        array(
            'name' => __('Büyük', 'hdh'),
            'size' => 20,
            'slug' => 'large',
        ),
        array(
            'name' => __('Çok Büyük', 'hdh'),
            'size' => 32,
            'slug' => 'huge',
        ),
    ));
    
    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'hdh'),
        'footer' => __('Footer Menu', 'hdh'),
    ));
    
    // Set content width
    if (!isset($content_width)) {
        $content_width = 1200;
    }
    
    // Add image sizes
    add_image_size('post-thumbnail', 300, 200, true);
    add_image_size('post-large', 800, 500, true);
}
add_action('after_setup_theme', 'mi_theme_setup');

// Enqueue styles and scripts
function mi_enqueue_scripts() {
    wp_enqueue_style('mi-style', get_stylesheet_uri(), array(), '1.0.0');
    
    // Print stylesheet
    wp_enqueue_style('mi-print', get_template_directory_uri() . '/print.css', array(), '1.0.0', 'print');
    
    // Enqueue jQuery (WordPress includes it by default)
    wp_enqueue_script('jquery');
    
    // Enqueue masonry and imagesloaded for masonry grid
    if (get_theme_mod('mi_enable_masonry', false)) {
        wp_enqueue_script('masonry');
        wp_enqueue_script('imagesloaded');
    }
    
    // Enqueue single page mode script
    if (get_option('mi_enable_single_page', 0) === 1) {
        wp_enqueue_script(
            'mi-single-page',
            get_template_directory_uri() . '/assets/js/single-page.js',
            array('jquery'),
            '1.0.0',
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'mi_enqueue_scripts');

// Custom excerpt length (will be overridden by compatibility-check.php if customizer setting exists)
function mi_excerpt_length($length) {
    $custom_length = get_theme_mod('mi_excerpt_length', 30);
    return $custom_length ?: 30;
}
add_filter('excerpt_length', 'mi_excerpt_length', 10);

// Custom excerpt more
function mi_excerpt_more($more) {
    return '...';
}
add_filter('excerpt_more', 'mi_excerpt_more');

// Add custom body classes
function mi_body_classes($classes) {
    if (is_home() || is_front_page()) {
        $classes[] = 'home-page';
    }
    return $classes;
}
add_filter('body_class', 'mi_body_classes');

// Include admin sections
require_once get_template_directory() . '/admin-sections.php';

// Include template functions
require_once get_template_directory() . '/template-functions.php';

// Include social share functions
require_once get_template_directory() . '/social-share.php';

// Include Customizer
require_once get_template_directory() . '/inc/customizer.php';

// Include Theme Options
require_once get_template_directory() . '/inc/theme-options.php';

// Include Widgets
require_once get_template_directory() . '/inc/widgets.php';

// Include Modules
require_once get_template_directory() . '/inc/modules.php';

// Include Social Functions
require_once get_template_directory() . '/inc/social-functions.php';

// Include Breadcrumbs
require_once get_template_directory() . '/inc/breadcrumbs.php';

// Include Comments
require_once get_template_directory() . '/inc/comments.php';

// Include SEO
require_once get_template_directory() . '/inc/seo.php';

// Include Dark Mode
require_once get_template_directory() . '/inc/dark-mode.php';

// Include Mobile Menu
require_once get_template_directory() . '/inc/mobile-menu.php';

// Include Scroll to Top
require_once get_template_directory() . '/inc/scroll-to-top.php';

// Include Post Views
require_once get_template_directory() . '/inc/post-views.php';

// Include Popular Posts Widget
require_once get_template_directory() . '/inc/popular-posts-widget.php';

// Include Lightbox
require_once get_template_directory() . '/inc/lightbox.php';

// Include Infinite Scroll
require_once get_template_directory() . '/inc/infinite-scroll.php';

// Include Loading Skeleton
require_once get_template_directory() . '/inc/loading-skeleton.php';

// Include Newsletter
require_once get_template_directory() . '/inc/newsletter.php';

// Include Cookie Consent
require_once get_template_directory() . '/inc/cookie-consent.php';

// Include Gutenberg Blocks
require_once get_template_directory() . '/inc/gutenberg-blocks.php';

// Include RTL Support
require_once get_template_directory() . '/inc/rtl-support.php';

// Include Advanced Statistics
require_once get_template_directory() . '/inc/advanced-stats.php';

// Include AMP Support
require_once get_template_directory() . '/inc/amp.php';

// Include Media Player
require_once get_template_directory() . '/inc/media-player.php';

// Include Masonry Grid
require_once get_template_directory() . '/inc/masonry-grid.php';

// Include Additional Widgets
require_once get_template_directory() . '/inc/additional-widgets.php';

// Include WebP Support
require_once get_template_directory() . '/inc/webp-support.php';

// Include reCAPTCHA
require_once get_template_directory() . '/inc/recaptcha.php';

// Include Table of Contents
require_once get_template_directory() . '/inc/table-of-contents.php';

// Include Syntax Highlighting
require_once get_template_directory() . '/inc/syntax-highlighting.php';

// Include Parallax
require_once get_template_directory() . '/inc/parallax.php';

// Include Demo Import
require_once get_template_directory() . '/inc/demo-import.php';

// Include AJAX Handlers
require_once get_template_directory() . '/inc/ajax-handlers.php';

// Include Turkish Archives Support
require_once get_template_directory() . '/inc/turkish-archives.php';

// Include Admin UI
require_once get_template_directory() . '/inc/admin-ui.php';

// Include Compatibility Check
require_once get_template_directory() . '/inc/compatibility-check.php';

// Include Feature Integration
require_once get_template_directory() . '/inc/feature-integration.php';

// Include File Validator
require_once get_template_directory() . '/inc/file-validator.php';

// Türkçe tarih formatı
function mi_get_turkish_date($format = 'd F Y', $post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    $date = get_the_date($format, $post_id);
    
    // İngilizce ay isimlerini Türkçe'ye çevir
    $english_months = array(
        'January' => 'Ocak',
        'February' => 'Şubat',
        'March' => 'Mart',
        'April' => 'Nisan',
        'May' => 'Mayıs',
        'June' => 'Haziran',
        'July' => 'Temmuz',
        'August' => 'Ağustos',
        'September' => 'Eylül',
        'October' => 'Ekim',
        'November' => 'Kasım',
        'December' => 'Aralık'
    );
    
    foreach ($english_months as $en => $tr) {
        $date = str_replace($en, $tr, $date);
    }
    
    return $date;
}

