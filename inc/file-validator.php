<?php
/**
 * File and Function Validator - Ensures all files and functions exist
 */

// Validate all required files exist
function mi_validate_required_files() {
    $required_files = array(
        'admin-sections.php',
        'template-functions.php',
        'social-share.php',
        'inc/customizer.php',
        'inc/theme-options.php',
        'inc/widgets.php',
        'inc/modules.php',
        'inc/social-functions.php',
        'inc/breadcrumbs.php',
        'inc/comments.php',
        'inc/seo.php',
        'inc/dark-mode.php',
        'inc/mobile-menu.php',
        'inc/scroll-to-top.php',
        'inc/post-views.php',
        'inc/popular-posts-widget.php',
        'inc/lightbox.php',
        'inc/infinite-scroll.php',
        'inc/loading-skeleton.php',
        'inc/newsletter.php',
        'inc/cookie-consent.php',
        'inc/gutenberg-blocks.php',
        'inc/rtl-support.php',
        'inc/advanced-stats.php',
        'inc/amp.php',
        'inc/media-player.php',
        'inc/masonry-grid.php',
        'inc/additional-widgets.php',
        'inc/webp-support.php',
        'inc/recaptcha.php',
        'inc/table-of-contents.php',
        'inc/syntax-highlighting.php',
        'inc/parallax.php',
        'inc/demo-import.php',
        'inc/admin-ui.php',
        'inc/compatibility-check.php',
        'inc/feature-integration.php',
    );
    
    $template_dir = get_template_directory();
    $missing = array();
    
    foreach ($required_files as $file) {
        $file_path = $template_dir . '/' . $file;
        if (!file_exists($file_path)) {
            $missing[] = $file;
        }
    }
    
    if (!empty($missing) && current_user_can('manage_options')) {
        add_action('admin_notices', function() use ($missing) {
            echo '<div class="notice notice-error"><p>';
            echo __('MI Tema: Eksik dosyalar tespit edildi: ', 'mi-theme');
            echo implode(', ', $missing);
            echo '</p></div>';
        });
    }
}

// Validate template files
function mi_validate_template_files() {
    $template_files = array(
        'index.php',
        'single.php',
        'page.php',
        '404.php',
        'archive.php',
        'category.php',
        'tag.php',
        'author.php',
        'search.php',
        'attachment.php',
        'header.php',
        'footer.php',
        'sidebar.php',
        'comments.php',
        'single-mi_section.php',
    );
    
    $template_dir = get_template_directory();
    $missing = array();
    
    foreach ($template_files as $file) {
        $file_path = $template_dir . '/' . $file;
        if (!file_exists($file_path)) {
            $missing[] = $file;
        }
    }
    
    if (!empty($missing) && current_user_can('manage_options')) {
        add_action('admin_notices', function() use ($missing) {
            echo '<div class="notice notice-error"><p>';
            echo __('MI Tema: Eksik template dosyaları: ', 'mi-theme');
            echo implode(', ', $missing);
            echo '</p></div>';
        });
    }
}

// Validate section template files
function mi_validate_section_templates() {
    $section_templates = array(
        'templates/section-manset.php',
        'templates/section-kararlar.php',
        'templates/section-iletisim.php',
        'templates/section-custom.php',
        'templates/section-default.php',
    );
    
    $template_dir = get_template_directory();
    $missing = array();
    
    foreach ($section_templates as $file) {
        $file_path = $template_dir . '/' . $file;
        if (!file_exists($file_path)) {
            $missing[] = $file;
        }
    }
    
    if (!empty($missing) && current_user_can('manage_options')) {
        add_action('admin_notices', function() use ($missing) {
            echo '<div class="notice notice-error"><p>';
            echo __('MI Tema: Eksik section template dosyaları: ', 'mi-theme');
            echo implode(', ', $missing);
            echo '</p></div>';
        });
    }
}

// Validate asset files
function mi_validate_asset_files() {
    $asset_files = array(
        'style.css',
        'editor-style.css',
        'print.css',
        'assets/css/blocks.css',
        'assets/css/blocks-editor.css',
        'assets/css/admin.css',
        'assets/js/blocks.js',
    );
    
    $template_dir = get_template_directory();
    $missing = array();
    
    foreach ($asset_files as $file) {
        $file_path = $template_dir . '/' . $file;
        if (!file_exists($file_path)) {
            $missing[] = $file;
        }
    }
    
    if (!empty($missing) && current_user_can('manage_options')) {
        add_action('admin_notices', function() use ($missing) {
            echo '<div class="notice notice-warning"><p>';
            echo __('MI Tema: Eksik asset dosyaları (kritik değil): ', 'mi-theme');
            echo implode(', ', $missing);
            echo '</p></div>';
        });
    }
}

// Run validations
add_action('admin_init', 'mi_validate_required_files');
add_action('admin_init', 'mi_validate_template_files');
add_action('admin_init', 'mi_validate_section_templates');
add_action('admin_init', 'mi_validate_asset_files');

