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
    
    // Check if user is logged in - redirect to registration if not
    if (!is_user_logged_in()) {
        // Start session if not started
        if (!session_id()) {
            session_start();
        }
        
        // Store form data in session/transient for after registration
        $form_data = array(
            'wanted_item' => isset($_POST['wanted_item']) ? sanitize_text_field($_POST['wanted_item']) : '',
            'wanted_qty' => isset($_POST['wanted_qty']) ? absint($_POST['wanted_qty']) : 0,
            'offer_item' => isset($_POST['offer_item']) ? $_POST['offer_item'] : array(),
            'offer_qty' => isset($_POST['offer_qty']) ? $_POST['offer_qty'] : array(),
        );
        
        // Store in transient using unique identifier
        $transient_key = 'hdh_pending_trade_' . md5($_SERVER['REMOTE_ADDR'] . time() . wp_generate_password(10, false));
        set_transient($transient_key, $form_data, HOUR_IN_SECONDS);
        
        // Store transient key in cookie (will be read after registration)
        if (!headers_sent()) {
            setcookie('hdh_pending_trade_key', $transient_key, time() + HOUR_IN_SECONDS, '/', '', is_ssl(), true);
        }
        
        // Redirect to registration page
        wp_redirect(home_url('/?action=register&redirect=trade'));
        exit;
    }
    
    // Get form data from POST
    $wanted_item = isset($_POST['wanted_item']) ? sanitize_text_field($_POST['wanted_item']) : '';
    $wanted_qty = isset($_POST['wanted_qty']) ? absint($_POST['wanted_qty']) : 0;
    $offer_item_data = isset($_POST['offer_item']) ? $_POST['offer_item'] : array();
    $offer_qty_data = isset($_POST['offer_qty']) ? $_POST['offer_qty'] : array();
    
    // Get offer items from new format: offer_item[slug] and offer_qty[slug]
    $offer_items_data = array();
    if (!empty($offer_item_data) && is_array($offer_item_data)) {
        foreach ($offer_item_data as $slug => $item_slug) {
            $slug = sanitize_text_field($slug);
            $qty = isset($offer_qty_data[$slug]) ? absint($offer_qty_data[$slug]) : 0;
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
    
    // Auto-generate trade title from items
    $wanted_label = isset($items_config[$wanted_item]['label']) ? $items_config[$wanted_item]['label'] : $wanted_item;
    $offer_labels = array();
    foreach ($offer_items_data as $offer_item) {
        $offer_label = isset($items_config[$offer_item['slug']]['label']) ? $items_config[$offer_item['slug']]['label'] : $offer_item['slug'];
        $offer_labels[] = $offer_item['qty'] . ' ' . $offer_label;
    }
    $offer_text = implode(', ', $offer_labels);
    $trade_title = $wanted_qty . ' ' . $wanted_label . ' arıyorum, ' . $offer_text . ' verebilirim';
    
    // Check if admin requires approval (default: auto-publish)
    $require_approval = get_option('hdh_trade_require_approval', false);
    $post_status = $require_approval ? 'pending' : 'publish';
    
    // Generate post_name from current date/time: YYYYMMDD-HHMMSS
    $date_slug = current_time('Ymd-His');
    
    // Ensure uniqueness by checking if slug exists
    $original_slug = $date_slug;
    $counter = 1;
    while (get_page_by_path($date_slug, OBJECT, 'hayday_trade')) {
        $date_slug = $original_slug . '-' . $counter;
        $counter++;
    }
    
    // Create post
    $post_data = array(
        'post_title' => $trade_title,
        'post_content' => '', // No description field
        'post_status' => $post_status,
        'post_type' => 'hayday_trade',
        'post_author' => get_current_user_id(),
        'post_name' => $date_slug, // Set custom slug with date/time
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
    
    // Award +2 jetons for creating a listing (only if published)
    if ($post_status === 'publish' && function_exists('hdh_add_jeton')) {
        hdh_add_jeton(get_current_user_id(), 2, 'listing_created', array('post_id' => $post_id));
    }
    
    // Redirect based on approval status
    if ($post_status === 'pending') {
        wp_redirect(home_url('/?trade_success=pending'));
    } else {
        wp_redirect(get_permalink($post_id));
    }
    exit;
}
add_action('admin_post_hdh_create_trade', 'hdh_handle_create_trade');
add_action('admin_post_nopriv_hdh_create_trade', 'hdh_handle_create_trade'); // For non-logged-in users if needed
