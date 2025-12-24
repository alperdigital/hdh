<?php
/**
 * HDH: Trade Request AJAX Handlers
 * Handles AJAX requests for trade request system
 */

if (!defined('ABSPATH')) exit;

/**
 * Send trade request
 */
function hdh_ajax_send_trade_request() {
    // Check authentication
    if (!is_user_logged_in()) {
        wp_send_json_error(array(
            'message' => 'Giriş yapmanız gerekiyor'
        ));
        return;
    }
    
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_trade_request')) {
        wp_send_json_error(array(
            'message' => 'Güvenlik kontrolü başarısız'
        ));
        return;
    }
    
    $listing_id = isset($_POST['listing_id']) ? absint($_POST['listing_id']) : 0;
    $user_id = get_current_user_id();
    
    if (!$listing_id) {
        wp_send_json_error(array(
            'message' => 'Geçersiz ilan ID'
        ));
        return;
    }
    
    // Create trade request
    $request = hdh_create_trade_request($listing_id, $user_id);
    
    if (is_wp_error($request)) {
        wp_send_json_error(array(
            'message' => $request->get_error_message()
        ));
        return;
    }
    
    // Calculate time remaining
    $expires_timestamp = strtotime($request['expires_at']);
    $current_timestamp = current_time('timestamp');
    $time_remaining = max(0, $expires_timestamp - $current_timestamp);
    
    wp_send_json_success(array(
        'request' => $request,
        'time_remaining' => $time_remaining,
        'expires_at' => $request['expires_at'],
        'message' => 'Teklif başarıyla gönderildi'
    ));
}
add_action('wp_ajax_hdh_send_trade_request', 'hdh_ajax_send_trade_request');

/**
 * Accept trade request
 */
function hdh_ajax_accept_trade_request() {
    // Check authentication
    if (!is_user_logged_in()) {
        wp_send_json_error(array(
            'message' => 'Giriş yapmanız gerekiyor'
        ));
        return;
    }
    
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_trade_request')) {
        wp_send_json_error(array(
            'message' => 'Güvenlik kontrolü başarısız'
        ));
        return;
    }
    
    $request_id = isset($_POST['request_id']) ? absint($_POST['request_id']) : 0;
    $user_id = get_current_user_id();
    
    if (!$request_id) {
        wp_send_json_error(array(
            'message' => 'Geçersiz teklif ID'
        ));
        return;
    }
    
    // Accept trade request
    $request = hdh_accept_trade_request($request_id, $user_id);
    
    if (is_wp_error($request)) {
        wp_send_json_error(array(
            'message' => $request->get_error_message()
        ));
        return;
    }
    
    // Create trade session automatically
    $session = null;
    if (function_exists('hdh_create_trade_session')) {
        $session = hdh_create_trade_session($request['listing_id'], $request['requester_user_id']);
    }
    
    wp_send_json_success(array(
        'request' => $request,
        'session' => $session,
        'message' => 'Teklif kabul edildi'
    ));
}
add_action('wp_ajax_hdh_accept_trade_request', 'hdh_ajax_accept_trade_request');

/**
 * Reject trade request
 */
function hdh_ajax_reject_trade_request() {
    // Check authentication
    if (!is_user_logged_in()) {
        wp_send_json_error(array(
            'message' => 'Giriş yapmanız gerekiyor'
        ));
        return;
    }
    
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_trade_request')) {
        wp_send_json_error(array(
            'message' => 'Güvenlik kontrolü başarısız'
        ));
        return;
    }
    
    $request_id = isset($_POST['request_id']) ? absint($_POST['request_id']) : 0;
    $user_id = get_current_user_id();
    
    if (!$request_id) {
        wp_send_json_error(array(
            'message' => 'Geçersiz teklif ID'
        ));
        return;
    }
    
    // Reject trade request
    $request = hdh_reject_trade_request($request_id, $user_id);
    
    if (is_wp_error($request)) {
        wp_send_json_error(array(
            'message' => $request->get_error_message()
        ));
        return;
    }
    
    wp_send_json_success(array(
        'request' => $request,
        'message' => 'Teklif reddedildi'
    ));
}
add_action('wp_ajax_hdh_reject_trade_request', 'hdh_ajax_reject_trade_request');

/**
 * Get trade request status (for polling)
 */
function hdh_ajax_get_trade_request_status() {
    // Check authentication
    if (!is_user_logged_in()) {
        wp_send_json_error(array(
            'message' => 'Giriş yapmanız gerekiyor'
        ));
        return;
    }
    
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_trade_request')) {
        wp_send_json_error(array(
            'message' => 'Güvenlik kontrolü başarısız'
        ));
        return;
    }
    
    $request_id = isset($_POST['request_id']) ? absint($_POST['request_id']) : 0;
    $listing_id = isset($_POST['listing_id']) ? absint($_POST['listing_id']) : 0;
    $user_id = get_current_user_id();
    
    if ($request_id) {
        $request = hdh_get_trade_request($request_id);
    } elseif ($listing_id) {
        $request = hdh_get_trade_request_for_listing($listing_id, $user_id);
    } else {
        wp_send_json_error(array(
            'message' => 'Geçersiz parametreler'
        ));
        return;
    }
    
    if (!$request) {
        wp_send_json_success(array(
            'request' => null,
            'status' => 'none'
        ));
        return;
    }
    
    // Calculate time remaining
    $time_remaining = 0;
    if ($request['status'] === 'pending') {
        $expires_timestamp = strtotime($request['expires_at']);
        $current_timestamp = current_time('timestamp');
        $time_remaining = max(0, $expires_timestamp - $current_timestamp);
    }
    
    wp_send_json_success(array(
        'request' => $request,
        'status' => $request['status'],
        'time_remaining' => $time_remaining,
        'expires_at' => $request['expires_at']
    ));
}
add_action('wp_ajax_hdh_get_trade_request_status', 'hdh_ajax_get_trade_request_status');



