<?php
/**
 * Offers Custom Post Type
 * Manages trade offers
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register hayday_offer CPT
 */
function hdh_register_offers_cpt() {
    register_post_type('hayday_offer', array(
        'labels' => array(
            'name' => 'Teklifler',
            'singular_name' => 'Teklif',
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'capability_type' => 'post',
        'supports' => array('author'),
        'has_archive' => false,
        'rewrite' => false,
    ));
}
add_action('init', 'hdh_register_offers_cpt');

/**
 * Register hayday_message CPT for messaging
 */
function hdh_register_messages_cpt() {
    register_post_type('hayday_message', array(
        'labels' => array(
            'name' => 'Mesajlar',
            'singular_name' => 'Mesaj',
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'capability_type' => 'post',
        'supports' => array('author', 'editor'),
        'has_archive' => false,
        'rewrite' => false,
    ));
}
add_action('init', 'hdh_register_messages_cpt');



