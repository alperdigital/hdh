<?php
/**
 * HDH: Trade Session AJAX Handlers
 */

if (!defined('ABSPATH')) exit;

/**
 * Start trade session
 */
function hdh_ajax_start_trade_session() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => hdh_get_message('ajax', 'login_required', 'Giriş yapmanız gerekiyor')));
        return;
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_trade_session')) {
        wp_send_json_error(array('message' => hdh_get_message('ajax', 'security_failed', 'Güvenlik kontrolü başarısız')));
        return;
    }
    
    $listing_id = isset($_POST['listing_id']) ? absint($_POST['listing_id']) : 0;
    $user_id = get_current_user_id();
    
    if (!$listing_id) {
        wp_send_json_error(array('message' => hdh_get_message('ajax', 'invalid_parameters', 'Geçersiz parametreler')));
        return;
    }
    
    $session = hdh_create_trade_session($listing_id, $user_id);
    
    if (is_wp_error($session)) {
        wp_send_json_error(array('message' => $session->get_error_message()));
        return;
    }
    
    wp_send_json_success(array(
        'session' => $session,
        'message' => 'Hediyeleşme başlatıldı!'
    ));
}
add_action('wp_ajax_hdh_start_trade_session', 'hdh_ajax_start_trade_session');

/**
 * Get trade session
 */
function hdh_ajax_get_trade_session() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => hdh_get_message('ajax', 'login_required', 'Giriş yapmanız gerekiyor')));
        return;
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_trade_session')) {
        wp_send_json_error(array('message' => hdh_get_message('ajax', 'security_failed', 'Güvenlik kontrolü başarısız')));
        return;
    }
    
    $listing_id = isset($_POST['listing_id']) ? absint($_POST['listing_id']) : 0;
    $session_id = isset($_POST['session_id']) ? absint($_POST['session_id']) : 0;
    $user_id = get_current_user_id();
    
    if ($session_id) {
        $session = hdh_get_trade_session($session_id, null, $user_id);
    } elseif ($listing_id) {
        $session = hdh_get_trade_session(null, $listing_id, $user_id);
    } else {
        wp_send_json_error(array('message' => hdh_get_message('ajax', 'invalid_parameters', 'Geçersiz parametreler')));
        return;
    }
    
    if (!$session) {
        wp_send_json_error(array('message' => 'Oturum bulunamadı', 'code' => 'not_found'));
        return;
    }
    
    wp_send_json_success(array('session' => $session));
}
add_action('wp_ajax_hdh_get_trade_session', 'hdh_ajax_get_trade_session');

/**
 * Complete trade step
 */
function hdh_ajax_complete_trade_step() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => hdh_get_message('ajax', 'login_required', 'Giriş yapmanız gerekiyor')));
        return;
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_trade_session')) {
        wp_send_json_error(array('message' => hdh_get_message('ajax', 'security_failed', 'Güvenlik kontrolü başarısız')));
        return;
    }
    
    $session_id = isset($_POST['session_id']) ? absint($_POST['session_id']) : 0;
    $step = isset($_POST['step']) ? absint($_POST['step']) : 0;
    $user_id = get_current_user_id();
    
    if (!$session_id || !$step || $step < 1 || $step > 5) {
        wp_send_json_error(array('message' => hdh_get_message('ajax', 'invalid_parameters', 'Geçersiz parametreler')));
        return;
    }
    
    $session = hdh_complete_trade_step($session_id, $step, $user_id);
    
    if (is_wp_error($session)) {
        wp_send_json_error(array('message' => $session->get_error_message()));
        return;
    }
    
    wp_send_json_success(array(
        'session' => $session,
        'message' => 'Adım tamamlandı!'
    ));
}
add_action('wp_ajax_hdh_complete_trade_step', 'hdh_ajax_complete_trade_step');

/**
 * Create dispute
 */
function hdh_ajax_create_trade_dispute() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => hdh_get_message('ajax', 'login_required', 'Giriş yapmanız gerekiyor')));
        return;
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_trade_session')) {
        wp_send_json_error(array('message' => hdh_get_message('ajax', 'security_failed', 'Güvenlik kontrolü başarısız')));
        return;
    }
    
    $session_id = isset($_POST['session_id']) ? absint($_POST['session_id']) : 0;
    $reason = isset($_POST['reason']) ? sanitize_text_field($_POST['reason']) : '';
    $text = isset($_POST['text']) ? sanitize_textarea_field($_POST['text']) : '';
    $user_id = get_current_user_id();
    
    if (!$session_id || !$reason || !$text) {
        wp_send_json_error(array('message' => hdh_get_message('ajax', 'invalid_parameters', 'Lütfen tüm alanları doldurun')));
        return;
    }
    
    if (strlen($text) > 500) {
        wp_send_json_error(array('message' => 'Açıklama en fazla 500 karakter olabilir'));
        return;
    }
    
    $session = hdh_create_trade_dispute($session_id, $user_id, $reason, $text);
    
    if (is_wp_error($session)) {
        wp_send_json_error(array('message' => $session->get_error_message()));
        return;
    }
    
    wp_send_json_success(array(
        'session' => $session,
        'message' => 'Anlaşmazlık bildirildi. İnceleme altına alındı.'
    ));
}
add_action('wp_ajax_hdh_create_trade_dispute', 'hdh_ajax_create_trade_dispute');

/**
 * Resolve dispute (admin)
 */
function hdh_ajax_resolve_trade_dispute() {
    if (!is_user_logged_in() || !current_user_can('administrator')) {
        wp_send_json_error(array('message' => 'Yetkiniz yok'));
        return;
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_trade_session_admin')) {
        wp_send_json_error(array('message' => hdh_get_message('ajax', 'security_failed', 'Güvenlik kontrolü başarısız')));
        return;
    }
    
    $session_id = isset($_POST['session_id']) ? absint($_POST['session_id']) : 0;
    $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : 'resolved';
    $note = isset($_POST['note']) ? sanitize_textarea_field($_POST['note']) : '';
    
    if (!$session_id) {
        wp_send_json_error(array('message' => hdh_get_message('ajax', 'invalid_parameters', 'Geçersiz parametreler')));
        return;
    }
    
    $session = hdh_resolve_trade_dispute($session_id, $note, $action);
    
    if (is_wp_error($session)) {
        wp_send_json_error(array('message' => $session->get_error_message()));
        return;
    }
    
    wp_send_json_success(array(
        'session' => $session,
        'message' => 'Anlaşmazlık çözüldü'
    ));
}
add_action('wp_ajax_hdh_resolve_trade_dispute', 'hdh_ajax_resolve_trade_dispute');

/**
 * Get active trades for user
 */
function hdh_ajax_get_active_trades() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Giriş yapmanız gerekiyor'));
        return;
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_trade_session')) {
        wp_send_json_error(array('message' => 'Güvenlik kontrolü başarısız'));
        return;
    }
    
    $user_id = get_current_user_id();
    
    if (!function_exists('hdh_get_user_active_trades')) {
        wp_send_json_error(array('message' => 'Fonksiyon bulunamadı'));
        return;
    }
    
    $all_trades = hdh_get_user_active_trades($user_id, false);
    $action_required_trades = hdh_get_user_active_trades($user_id, true);
    
    wp_send_json_success(array(
        'trades' => $all_trades,
        'action_required_count' => count($action_required_trades),
    ));
}
add_action('wp_ajax_hdh_get_active_trades', 'hdh_ajax_get_active_trades');

/**
 * Get listing data for gift overlay
 */
function hdh_ajax_get_listing_data() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Giriş yapmanız gerekiyor'));
        return;
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_trade_session')) {
        wp_send_json_error(array('message' => 'Güvenlik kontrolü başarısız'));
        return;
    }
    
    $listing_id = isset($_POST['listing_id']) ? absint($_POST['listing_id']) : 0;
    
    if (!$listing_id) {
        wp_send_json_error(array('message' => 'Geçersiz ilan ID'));
        return;
    }
    
    $listing = get_post($listing_id);
    if (!$listing || $listing->post_type !== 'hayday_trade') {
        wp_send_json_error(array('message' => 'İlan bulunamadı'));
        return;
    }
    
    $trade_data = hdh_get_trade_data($listing_id);
    
    wp_send_json_success(array(
        'listing' => array(
            'id' => $listing_id,
            'title' => $listing->post_title,
            'wanted_item' => hdh_get_item_label($trade_data['wanted_item']),
            'offer_items' => implode(', ', array_map(function($item) {
                return hdh_get_item_label($item['item']) . ' x' . $item['qty'];
            }, array_filter($trade_data['offer_items'], function($item) {
                return !empty($item['item']) && !empty($item['qty']);
            }))),
        )
    ));
}
add_action('wp_ajax_hdh_get_listing_data', 'hdh_ajax_get_listing_data');

