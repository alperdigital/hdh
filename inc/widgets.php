<?php
/**
 * Widget Areas and Sidebar Management
 */

// Register widget areas
function mi_register_widget_areas() {
    // Sidebar
    register_sidebar(array(
        'name' => __('Ana Sidebar', 'mi-theme'),
        'id' => 'sidebar-1',
        'description' => __('Ana sidebar widget alanı', 'mi-theme'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget' => '</div>',
        'before_title' => '<h3 class="widget-title">',
        'after_title' => '</h3>',
    ));
    
    // Footer Widget Areas
    register_sidebar(array(
        'name' => __('Footer 1', 'mi-theme'),
        'id' => 'footer-1',
        'description' => __('Footer birinci kolon', 'mi-theme'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget' => '</div>',
        'before_title' => '<h4 class="widget-title">',
        'after_title' => '</h4>',
    ));
    
    register_sidebar(array(
        'name' => __('Footer 2', 'mi-theme'),
        'id' => 'footer-2',
        'description' => __('Footer ikinci kolon', 'mi-theme'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget' => '</div>',
        'before_title' => '<h4 class="widget-title">',
        'after_title' => '</h4>',
    ));
    
    register_sidebar(array(
        'name' => __('Footer 3', 'mi-theme'),
        'id' => 'footer-3',
        'description' => __('Footer üçüncü kolon', 'mi-theme'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget' => '</div>',
        'before_title' => '<h4 class="widget-title">',
        'after_title' => '</h4>',
    ));
    
    register_sidebar(array(
        'name' => __('Footer 4', 'mi-theme'),
        'id' => 'footer-4',
        'description' => __('Footer dördüncü kolon', 'mi-theme'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget' => '</div>',
        'before_title' => '<h4 class="widget-title">',
        'after_title' => '</h4>',
    ));
    
    // Header Widget Area
    register_sidebar(array(
        'name' => __('Header Widget', 'mi-theme'),
        'id' => 'header-widget',
        'description' => __('Header widget alanı', 'mi-theme'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget' => '</div>',
        'before_title' => '<h3 class="widget-title">',
        'after_title' => '</h3>',
    ));
}
add_action('widgets_init', 'mi_register_widget_areas');

// Check if sidebar should be displayed
function mi_has_sidebar() {
    return is_active_sidebar('sidebar-1');
}

// Get sidebar layout
function mi_get_sidebar_layout() {
    $layout = get_theme_mod('mi_sidebar_layout', 'right');
    return $layout;
}

