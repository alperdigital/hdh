<?php
/**
 * RTL (Right-to-Left) Language Support
 */

// Add RTL support
function mi_rtl_support() {
    // Check if current language is RTL
    if (is_rtl()) {
        // Enqueue RTL stylesheet
        wp_enqueue_style('mi-rtl', get_template_directory_uri() . '/rtl.css', array('mi-style'), '1.0.0');
        
        // Add RTL body class
        add_filter('body_class', function($classes) {
            $classes[] = 'rtl';
            return $classes;
        });
    }
}
add_action('wp_enqueue_scripts', 'mi_rtl_support');

// RTL CSS
function mi_rtl_css() {
    if (!is_rtl()) {
        return;
    }
    ?>
    <style>
    /* RTL overrides */
    body.rtl {
        direction: rtl;
        text-align: right;
    }
    
    .rtl .container,
    .rtl .main-content,
    .rtl .sidebar {
        direction: rtl;
    }
    
    .rtl .nav-menu {
        flex-direction: row-reverse;
    }
    
    .rtl .post-meta,
    .rtl .article-meta {
        flex-direction: row-reverse;
    }
    
    .rtl .social-links {
        flex-direction: row-reverse;
    }
    
    .rtl .breadcrumb-list {
        flex-direction: row-reverse;
    }
    </style>
    <?php
}
add_action('wp_head', 'mi_rtl_css');

