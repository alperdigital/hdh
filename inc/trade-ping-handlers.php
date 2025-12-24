<?php
/**
 * HDH: Trade Ping AJAX Handlers
 */

if (!defined('ABSPATH')) exit;

/**
 * AJAX: Send trade ping
 */
function hdh_ajax_send_trade_ping() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Giriş yapmanız gerekiyor'));
        return;
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_trade_session')) {
        wp_send_json_error(array('message' => 'Güvenlik kontrolü başarısız'));
        return;
    }
    
    $session_id = isset($_POST['session_id']) ? absint($_POST['session_id']) : 0;
    $user_id = get_current_user_id();
    
    if (!$session_id) {
        wp_send_json_error(array('message' => 'Geçersiz oturum ID'));
        return;
    }
    
    if (!function_exists('hdh_send_trade_ping')) {
        wp_send_json_error(array('message' => 'Ping sistemi mevcut değil'));
        return;
    }
    
    $ping_id = hdh_send_trade_ping($session_id, $user_id);
    
    if (is_wp_error($ping_id)) {
        wp_send_json_error(array('message' => $ping_id->get_error_message()));
        return;
    }
    
    wp_send_json_success(array(
        'ping_id' => $ping_id,
        'message' => 'Ping gönderildi!'
    ));
}
add_action('wp_ajax_hdh_send_trade_ping', 'hdh_ajax_send_trade_ping');

/**
 * AJAX: Respond to ping
 */
function hdh_ajax_respond_to_ping() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Giriş yapmanız gerekiyor'));
        return;
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_trade_session')) {
        wp_send_json_error(array('message' => 'Güvenlik kontrolü başarısız'));
        return;
    }
    
    $ping_id = isset($_POST['ping_id']) ? absint($_POST['ping_id']) : 0;
    $response = isset($_POST['response']) ? sanitize_text_field($_POST['response']) : '';
    $user_id = get_current_user_id();
    
    if (!$ping_id || !$response) {
        wp_send_json_error(array('message' => 'Geçersiz parametreler'));
        return;
    }
    
    if (!function_exists('hdh_respond_to_ping')) {
        wp_send_json_error(array('message' => 'Ping sistemi mevcut değil'));
        return;
    }
    
    $result = hdh_respond_to_ping($ping_id, $user_id, $response);
    
    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
        return;
    }
    
    wp_send_json_success(array('message' => 'Yanıt gönderildi!'));
}
add_action('wp_ajax_hdh_respond_to_ping', 'hdh_ajax_respond_to_ping');

/**
 * AJAX: Get pending pings
 */
function hdh_ajax_get_pending_pings() {
    if (!is_user_logged_in()) {
        wp_send_json_success(array('pings' => array()));
        return;
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_trade_session')) {
        wp_send_json_error(array('message' => 'Güvenlik kontrolü başarısız'));
        return;
    }
    
    $user_id = get_current_user_id();
    
    if (!function_exists('hdh_get_pending_pings')) {
        wp_send_json_success(array('pings' => array()));
        return;
    }
    
    $pings = hdh_get_pending_pings($user_id);
    
    wp_send_json_success(array('pings' => $pings));
}
add_action('wp_ajax_hdh_get_pending_pings', 'hdh_ajax_get_pending_pings');

