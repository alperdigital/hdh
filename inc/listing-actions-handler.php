<?php
/**
 * Listing Actions Handler
 * Handles deactivating user listings
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle deactivate listing AJAX request
 */
function hdh_handle_deactivate_listing() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_listing_actions')) {
        wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız.'));
        return;
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Bu işlem için giriş yapmalısınız.'));
        return;
    }
    
    $listing_id = isset($_POST['listing_id']) ? absint($_POST['listing_id']) : 0;
    $user_id = get_current_user_id();
    
    // Validate listing ID
    if (!$listing_id) {
        wp_send_json_error(array('message' => 'Geçersiz ilan ID.'));
        return;
    }
    
    // Check if listing exists and belongs to user
    $listing = get_post($listing_id);
    if (!$listing || $listing->post_type !== 'hayday_trade') {
        wp_send_json_error(array('message' => 'İlan bulunamadı.'));
        return;
    }
    
    if ($listing->post_author != $user_id) {
        wp_send_json_error(array('message' => 'Bu ilanı düzenleme yetkiniz yok.'));
        return;
    }
    
    // Check if listing is already inactive
    if ($listing->post_status === 'draft') {
        wp_send_json_error(array('message' => 'İlan zaten pasif durumda.'));
        return;
    }
    
    // Deactivate listing (set to draft)
    $updated = wp_update_post(array(
        'ID' => $listing_id,
        'post_status' => 'draft'
    ));
    
    if (is_wp_error($updated)) {
        wp_send_json_error(array('message' => 'İlan güncellenirken bir hata oluştu.'));
        return;
    }
    
    wp_send_json_success(array(
        'message' => 'İlan başarıyla pasife alındı.',
        'listing_id' => $listing_id
    ));
}
add_action('wp_ajax_hdh_deactivate_listing', 'hdh_handle_deactivate_listing');



