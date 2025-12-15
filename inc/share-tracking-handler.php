<?php
/**
 * HDH: Share Tracking Handler
 */

if (!defined('ABSPATH')) exit;

/**
 * Track share action for quest progress
 */
function hdh_track_share() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Giriş yapmalısınız.'));
        return;
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_share_tracking')) {
        wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız.'));
        return;
    }
    
    $listing_id = isset($_POST['listing_id']) ? absint($_POST['listing_id']) : 0;
    $user_id = get_current_user_id();
    
    if ($listing_id && function_exists('hdh_update_quest_progress')) {
        hdh_update_quest_progress($user_id, 'share_listing', 1);
    }
    
    wp_send_json_success();
}
add_action('wp_ajax_hdh_track_share', 'hdh_track_share');

