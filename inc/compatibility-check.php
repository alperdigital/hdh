<?php
/**
 * Compatibility Check - Ensure all features work together
 */

// Check if all required functions exist
function mi_check_function_exists() {
    $required_functions = array(
        'mi_breadcrumbs',
        'mi_has_sidebar',
        'mi_display_post_views',
        'mi_render_social_share',
        'mi_render_social_links',
        'mi_mobile_menu_toggle',
        'mi_dark_mode_toggle',
        'mi_scroll_to_top',
        'mi_cookie_consent_banner',
        'mi_render_masonry_grid',
        'mi_get_active_sections',
        'mi_get_section_name',
    );
    
    $missing = array();
    foreach ($required_functions as $func) {
        if (!function_exists($func)) {
            $missing[] = $func;
        }
    }
    
    if (!empty($missing) && current_user_can('manage_options')) {
        add_action('admin_notices', function() use ($missing) {
            echo '<div class="notice notice-error"><p>';
            echo __('MI Tema: Eksik fonksiyonlar tespit edildi: ', 'mi-theme');
            echo implode(', ', $missing);
            echo '</p></div>';
        });
    }
}

// Ensure jQuery is loaded before our scripts
function mi_ensure_jquery() {
    if (!wp_script_is('jquery', 'enqueued')) {
        wp_enqueue_script('jquery');
    }
}
add_action('wp_enqueue_scripts', 'mi_ensure_jquery', 1);

// Prevent conflicts with masonry
function mi_masonry_conflict_check() {
    if (get_theme_mod('mi_enable_masonry', false)) {
        // Ensure masonry is loaded only once
        if (!wp_script_is('masonry', 'enqueued')) {
            wp_enqueue_script('masonry');
        }
        if (!wp_script_is('imagesloaded', 'enqueued')) {
            wp_enqueue_script('imagesloaded');
        }
    }
}
add_action('wp_enqueue_scripts', 'mi_masonry_conflict_check', 20);

// Ensure excerpt length uses customizer setting
function mi_dynamic_excerpt_length($length) {
    $custom_length = get_theme_mod('mi_excerpt_length', 30);
    return $custom_length ?: 30;
}
add_filter('excerpt_length', 'mi_dynamic_excerpt_length', 20);

// Ensure posts per page uses customizer setting
function mi_custom_posts_per_page($query) {
    if (!is_admin() && $query->is_main_query()) {
        $posts_per_page = get_theme_mod('mi_posts_per_page', 10);
        if ($posts_per_page) {
            $query->set('posts_per_page', $posts_per_page);
        }
    }
}
add_action('pre_get_posts', 'mi_custom_posts_per_page');

// Run compatibility check on admin init
add_action('admin_init', 'mi_check_function_exists');

