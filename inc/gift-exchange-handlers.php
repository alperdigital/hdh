<?php
/**
 * HDH: Gift Exchange AJAX Handlers
 * Handles AJAX requests for gift exchange system
 */

if (!defined('ABSPATH')) exit;

/**
 * Start gift exchange
 */
function hdh_ajax_start_gift_exchange() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Giriş yapmanız gerekiyor'));
        return;
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_gift_exchange')) {
        wp_send_json_error(array('message' => 'Güvenlik kontrolü başarısız'));
        return;
    }
    
    $listing_id = isset($_POST['listing_id']) ? absint($_POST['listing_id']) : 0;
    $user_id = get_current_user_id();
    
    if (!$listing_id) {
        wp_send_json_error(array('message' => 'Geçersiz parametreler'));
        return;
    }
    
    if (!function_exists('hdh_create_gift_exchange')) {
        wp_send_json_error(array('message' => 'Fonksiyon bulunamadı'));
        return;
    }
    
    $exchange = hdh_create_gift_exchange($listing_id, $user_id);
    
    if (is_wp_error($exchange)) {
        wp_send_json_error(array('message' => $exchange->get_error_message()));
        return;
    }
    
    wp_send_json_success(array(
        'exchange' => $exchange,
        'message' => 'Hediyeleşme başlatıldı!'
    ));
}
add_action('wp_ajax_hdh_start_gift_exchange', 'hdh_ajax_start_gift_exchange');

/**
 * Send gift message
 */
function hdh_ajax_send_gift_message() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Giriş yapmanız gerekiyor'));
        return;
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_gift_exchange')) {
        wp_send_json_error(array('message' => 'Güvenlik kontrolü başarısız'));
        return;
    }
    
    $exchange_id = isset($_POST['exchange_id']) ? absint($_POST['exchange_id']) : 0;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    $user_id = get_current_user_id();
    
    if (!$exchange_id || !$message) {
        wp_send_json_error(array('message' => 'Geçersiz parametreler'));
        return;
    }
    
    if (!function_exists('hdh_send_gift_message')) {
        wp_send_json_error(array('message' => 'Fonksiyon bulunamadı'));
        return;
    }
    
    $message_id = hdh_send_gift_message($exchange_id, $user_id, $message);
    
    if (is_wp_error($message_id)) {
        wp_send_json_error(array('message' => $message_id->get_error_message()));
        return;
    }
    
    // Get updated messages
    $messages = array();
    if (function_exists('hdh_get_gift_messages')) {
        $messages = hdh_get_gift_messages($exchange_id, $user_id);
    }
    
    wp_send_json_success(array(
        'message_id' => $message_id,
        'messages' => $messages,
        'message' => 'Mesaj gönderildi'
    ));
}
add_action('wp_ajax_hdh_send_gift_message', 'hdh_ajax_send_gift_message');

/**
 * Get gift messages
 */
function hdh_ajax_get_gift_messages() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Giriş yapmanız gerekiyor'));
        return;
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_gift_exchange')) {
        wp_send_json_error(array('message' => 'Güvenlik kontrolü başarısız'));
        return;
    }
    
    $exchange_id = isset($_POST['exchange_id']) ? absint($_POST['exchange_id']) : 0;
    $user_id = get_current_user_id();
    
    if (!$exchange_id) {
        wp_send_json_error(array('message' => 'Geçersiz parametreler'));
        return;
    }
    
    if (!function_exists('hdh_get_gift_messages')) {
        wp_send_json_error(array('message' => 'Fonksiyon bulunamadı'));
        return;
    }
    
    $messages = hdh_get_gift_messages($exchange_id, $user_id);
    
    // Mark messages as read
    if (function_exists('hdh_mark_messages_read')) {
        hdh_mark_messages_read($exchange_id, $user_id);
    }
    
    wp_send_json_success(array(
        'messages' => $messages
    ));
}
add_action('wp_ajax_hdh_get_gift_messages', 'hdh_ajax_get_gift_messages');

/**
 * Get gift exchanges
 */
function hdh_ajax_get_gift_exchanges() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Giriş yapmanız gerekiyor'));
        return;
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_gift_exchange')) {
        wp_send_json_error(array('message' => 'Güvenlik kontrolü başarısız'));
        return;
    }
    
    $user_id = get_current_user_id();
    
    if (!function_exists('hdh_get_user_gift_exchanges')) {
        wp_send_json_error(array('message' => 'Fonksiyon bulunamadı'));
        return;
    }
    
    $exchanges = hdh_get_user_gift_exchanges($user_id);
    $total_unread = 0;
    
    if (function_exists('hdh_get_total_unread_count')) {
        $total_unread = hdh_get_total_unread_count($user_id);
    }
    
    wp_send_json_success(array(
        'exchanges' => $exchanges,
        'total_unread' => $total_unread
    ));
}
add_action('wp_ajax_hdh_get_gift_exchanges', 'hdh_ajax_get_gift_exchanges');

/**
 * Complete gift exchange
 */
function hdh_ajax_complete_gift_exchange() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Giriş yapmanız gerekiyor'));
        return;
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_gift_exchange')) {
        wp_send_json_error(array('message' => 'Güvenlik kontrolü başarısız'));
        return;
    }
    
    $exchange_id = isset($_POST['exchange_id']) ? absint($_POST['exchange_id']) : 0;
    $user_id = get_current_user_id();
    
    if (!$exchange_id) {
        wp_send_json_error(array('message' => 'Geçersiz parametreler'));
        return;
    }
    
    if (!function_exists('hdh_complete_gift_exchange')) {
        wp_send_json_error(array('message' => 'Fonksiyon bulunamadı'));
        return;
    }
    
    $exchange = hdh_complete_gift_exchange($exchange_id, $user_id);
    
    if (is_wp_error($exchange)) {
        wp_send_json_error(array('message' => $exchange->get_error_message()));
        return;
    }
    
    $message = 'Hediyeleşme tamamlandı';
    if ($exchange['completed_owner_at'] && $exchange['completed_offerer_at']) {
        $message = '✅ Hediyeleşme tamamlandı!';
    } else {
        $message = 'Karşı tarafın onayı bekleniyor';
    }
    
    wp_send_json_success(array(
        'exchange' => $exchange,
        'message' => $message
    ));
}
add_action('wp_ajax_hdh_complete_gift_exchange', 'hdh_ajax_complete_gift_exchange');

/**
 * Report gift exchange
 */
function hdh_ajax_report_gift_exchange() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Giriş yapmanız gerekiyor'));
        return;
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_gift_exchange')) {
        wp_send_json_error(array('message' => 'Güvenlik kontrolü başarısız'));
        return;
    }
    
    $exchange_id = isset($_POST['exchange_id']) ? absint($_POST['exchange_id']) : 0;
    $reason = isset($_POST['reason']) ? sanitize_text_field($_POST['reason']) : '';
    $user_id = get_current_user_id();
    
    if (!$exchange_id) {
        wp_send_json_error(array('message' => 'Geçersiz parametreler'));
        return;
    }
    
    if (!function_exists('hdh_report_gift_exchange')) {
        wp_send_json_error(array('message' => 'Fonksiyon bulunamadı'));
        return;
    }
    
    $exchange = hdh_report_gift_exchange($exchange_id, $user_id, $reason);
    
    if (is_wp_error($exchange)) {
        wp_send_json_error(array('message' => $exchange->get_error_message()));
        return;
    }
    
    wp_send_json_success(array(
        'exchange' => $exchange,
        'message' => 'Şikayet bildirildi'
    ));
}
add_action('wp_ajax_hdh_report_gift_exchange', 'hdh_ajax_report_gift_exchange');

/**
 * Mark messages as read
 */
function hdh_ajax_mark_messages_read() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Giriş yapmanız gerekiyor'));
        return;
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_gift_exchange')) {
        wp_send_json_error(array('message' => 'Güvenlik kontrolü başarısız'));
        return;
    }
    
    $exchange_id = isset($_POST['exchange_id']) ? absint($_POST['exchange_id']) : 0;
    $user_id = get_current_user_id();
    
    if (!$exchange_id) {
        wp_send_json_error(array('message' => 'Geçersiz parametreler'));
        return;
    }
    
    if (!function_exists('hdh_mark_messages_read')) {
        wp_send_json_error(array('message' => 'Fonksiyon bulunamadı'));
        return;
    }
    
    $result = hdh_mark_messages_read($exchange_id, $user_id);
    
    if (!$result) {
        wp_send_json_error(array('message' => 'Mesajlar okundu olarak işaretlenemedi'));
        return;
    }
    
    wp_send_json_success(array(
        'message' => 'Mesajlar okundu olarak işaretlendi'
    ));
}
add_action('wp_ajax_hdh_mark_messages_read', 'hdh_ajax_mark_messages_read');

