<?php
/**
 * Gutenberg Custom Blocks
 */

// Register custom block category
function mi_block_category($categories, $post) {
    return array_merge(
        array(
            array(
                'slug' => 'mi-blocks',
                'title' => __('MI Blokları', 'mi-theme'),
            ),
        ),
        $categories
    );
}
add_filter('block_categories_all', 'mi_block_category', 10, 2);

// Enqueue block editor assets
function mi_block_editor_assets() {
    $blocks_js = get_template_directory() . '/assets/js/blocks.js';
    if (file_exists($blocks_js)) {
        wp_enqueue_script(
            'mi-blocks',
            get_template_directory_uri() . '/assets/js/blocks.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
            filemtime($blocks_js),
            true
        );
    }
    
    $blocks_editor_css = get_template_directory() . '/assets/css/blocks-editor.css';
    if (file_exists($blocks_editor_css)) {
        wp_enqueue_style(
            'mi-blocks-editor',
            get_template_directory_uri() . '/assets/css/blocks-editor.css',
            array('wp-edit-blocks'),
            filemtime($blocks_editor_css)
        );
    }
}
add_action('enqueue_block_editor_assets', 'mi_block_editor_assets');

// Enqueue frontend block styles
function mi_block_assets() {
    $blocks_css = get_template_directory() . '/assets/css/blocks.css';
    if (file_exists($blocks_css)) {
        wp_enqueue_style(
            'mi-blocks',
            get_template_directory_uri() . '/assets/css/blocks.css',
            array(),
            filemtime($blocks_css)
        );
    }
}
add_action('enqueue_block_assets', 'mi_block_assets');

// Register custom block styles
function mi_register_block_styles() {
    // Button block styles
    register_block_style('core/button', array(
        'name' => 'mi-primary',
        'label' => __('MI Primary', 'mi-theme'),
    ));
    
    register_block_style('core/button', array(
        'name' => 'mi-outline',
        'label' => __('MI Outline', 'mi-theme'),
    ));
    
    // Quote block styles
    register_block_style('core/quote', array(
        'name' => 'mi-highlight',
        'label' => __('MI Highlight', 'mi-theme'),
    ));
}
add_action('init', 'mi_register_block_styles');

