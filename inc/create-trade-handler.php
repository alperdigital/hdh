<?php
/**
 * HDH: Create Trade Offer Handler
 * Processes form submission to create new trade offers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle trade creation form submission
 */
function hdh_handle_create_trade() {
    // Verify nonce
    if (!isset($_POST['hdh_trade_nonce']) || !wp_verify_nonce($_POST['hdh_trade_nonce'], 'hdh_create_trade')) {
        wp_die('Güvenlik kontrolü başarısız.');
    }
    
    // Check if user is logged in (optional, can be removed if allowing anonymous)
    if (!is_user_logged_in()) {
        wp_redirect(home_url('/?trade_error=login_required'));
        exit;
    }
    
    // Get form data
    $wanted_item = isset($_POST['wanted_item']) ? sanitize_text_field($_POST['wanted_item']) : '';
    $wanted_qty = isset($_POST['wanted_qty']) ? absint($_POST['wanted_qty']) : 0;
    $trade_title = isset($_POST['trade_title']) ? sanitize_text_field($_POST['trade_title']) : '';
    $trade_description = isset($_POST['trade_description']) ? wp_kses_post($_POST['trade_description']) : '';
    
    // Get offer items from new format: offer_item[slug] and offer_qty[slug]
    $offer_items_data = array();
    if (isset($_POST['offer_item']) && is_array($_POST['offer_item'])) {
        foreach ($_POST['offer_item'] as $slug => $item_slug) {
            $slug = sanitize_text_field($slug);
            $qty = isset($_POST['offer_qty'][$slug]) ? absint($_POST['offer_qty'][$slug]) : 0;
            if ($qty > 0) {
                $offer_items_data[] = array(
                    'slug' => sanitize_text_field($item_slug),
                    'qty' => $qty
                );
            }
        }
    }
    
    // Validation
    if (empty($wanted_item) || $wanted_qty <= 0) {
        wp_redirect(home_url('/?trade_error=invalid_wanted'));
        exit;
    }
    
    // Validate wanted_item exists in config
    $items_config = hdh_get_items_config();
    if (!isset($items_config[$wanted_item])) {
        wp_redirect(home_url('/?trade_error=invalid_wanted'));
        exit;
    }
    
    if (empty($offer_items_data) || count($offer_items_data) > 3) {
        wp_redirect(home_url('/?trade_error=invalid_offer'));
        exit;
    }
    
    // Validate all offer items exist in config
    foreach ($offer_items_data as $offer_item) {
        if (!isset($items_config[$offer_item['slug']])) {
            wp_redirect(home_url('/?trade_error=invalid_offer'));
            exit;
        }
    }
    
    if (empty($trade_title)) {
        wp_redirect(home_url('/?trade_error=no_title'));
        exit;
    }
    
    // Create post
    $post_data = array(
        'post_title' => $trade_title,
        'post_content' => $trade_description,
        'post_status' => 'publish',
        'post_type' => 'hayday_trade',
        'post_author' => get_current_user_id(),
    );
    
    $post_id = wp_insert_post($post_data);
    
    if (is_wp_error($post_id)) {
        wp_redirect(home_url('/?trade_error=creation_failed'));
        exit;
    }
    
    // Save meta fields
    update_post_meta($post_id, '_hdh_wanted_item', $wanted_item);
    update_post_meta($post_id, '_hdh_wanted_qty', $wanted_qty);
    update_post_meta($post_id, '_hdh_trade_status', 'open');
    
    // Save offer items (up to 3)
    for ($i = 0; $i < 3; $i++) {
        if (isset($offer_items_data[$i])) {
            update_post_meta($post_id, '_hdh_offer_item_' . ($i + 1), $offer_items_data[$i]['slug']);
            update_post_meta($post_id, '_hdh_offer_qty_' . ($i + 1), $offer_items_data[$i]['qty']);
        } else {
            // Clear any existing data
            delete_post_meta($post_id, '_hdh_offer_item_' . ($i + 1));
            delete_post_meta($post_id, '_hdh_offer_qty_' . ($i + 1));
        }
    }
    
    // Redirect to the new trade offer
    wp_redirect(get_permalink($post_id));
    exit;
}
add_action('admin_post_hdh_create_trade', 'hdh_handle_create_trade');
add_action('admin_post_nopriv_hdh_create_trade', 'hdh_handle_create_trade'); // For non-logged-in users if needed

