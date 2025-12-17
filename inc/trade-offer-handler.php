<?php
if (!defined('ABSPATH')) exit;

function hdh_handle_create_offer() {
    if (!is_user_logged_in()) { wp_send_json_error(array('message' => hdh_get_message('ajax', 'login_required', 'Giriş yapmanız gerekiyor'))); return; }
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_create_offer')) { wp_send_json_error(array('message' => hdh_get_message('ajax', 'security_failed', 'Güvenlik kontrolü başarısız'))); return; }
    $listing_id = isset($_POST['listing_id']) ? absint($_POST['listing_id']) : 0;
    $offerer_id = get_current_user_id();
    if (!$listing_id) { wp_send_json_error(array('message' => hdh_get_message('ajax', 'invalid_listing', 'Geçersiz ilan'))); return; }
    $listing = get_post($listing_id);
    if (!$listing || $listing->post_type !== 'hayday_trade') { wp_send_json_error(array('message' => hdh_get_message('ajax', 'listing_not_found', 'İlan bulunamadı'))); return; }
    if ($offerer_id == $listing->post_author) { wp_send_json_error(array('message' => hdh_get_message('ajax', 'cannot_offer_own_listing', 'Kendi ilanınıza teklif yapamazsınız'))); return; }
    $listing_status = get_post_meta($listing_id, '_hdh_trade_status', true);
    if ($listing_status !== 'open') { wp_send_json_error(array('message' => hdh_get_message('ajax', 'listing_not_open', 'Bu ilan artık açık değil'))); return; }
    $existing_offers = get_post_meta($listing_id, '_hdh_offers', true);
    if (!is_array($existing_offers)) $existing_offers = array();
    foreach ($existing_offers as $offer) {
        if (isset($offer['offerer_id']) && $offer['offerer_id'] == $offerer_id) {
            wp_send_json_error(array('message' => hdh_get_message('ajax', 'already_offered', 'Bu ilana zaten teklif yaptınız'))); return;
        }
    }
    $offer = array('offerer_id' => $offerer_id, 'status' => 'pending', 'created_at' => current_time('mysql'));
    $existing_offers[] = $offer;
    update_post_meta($listing_id, '_hdh_offers', $existing_offers);
    wp_send_json_success(array('message' => hdh_get_message('ajax', 'offer_sent', 'Teklifiniz gönderildi'), 'offer_id' => count($existing_offers) - 1));
}
add_action('wp_ajax_hdh_create_offer', 'hdh_handle_create_offer');

function hdh_handle_offer_response() {
    if (!is_user_logged_in()) { wp_send_json_error(array('message' => hdh_get_message('ajax', 'login_required', 'Giriş yapmanız gerekiyor'))); return; }
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_create_offer')) { wp_send_json_error(array('message' => hdh_get_message('ajax', 'security_failed', 'Güvenlik kontrolü başarısız'))); return; }
    $listing_id = isset($_POST['listing_id']) ? absint($_POST['listing_id']) : 0;
    $offer_index = isset($_POST['offer_index']) ? absint($_POST['offer_index']) : -1;
    $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
    if (!$listing_id || $offer_index < 0 || !in_array($action, array('accept', 'reject'))) { wp_send_json_error(array('message' => hdh_get_message('ajax', 'invalid_parameters', 'Geçersiz parametreler'))); return; }
    $listing = get_post($listing_id);
    if (!$listing || $listing->post_type !== 'hayday_trade') { wp_send_json_error(array('message' => hdh_get_message('ajax', 'listing_not_found', 'İlan bulunamadı'))); return; }
    if (get_current_user_id() != $listing->post_author) { wp_send_json_error(array('message' => hdh_get_message('ajax', 'unauthorized_action', 'Yetkisiz işlem'))); return; }
    $offers = get_post_meta($listing_id, '_hdh_offers', true);
    if (!is_array($offers) || !isset($offers[$offer_index])) { wp_send_json_error(array('message' => hdh_get_message('ajax', 'offer_not_found', 'Teklif bulunamadı'))); return; }
    $offer = $offers[$offer_index];
    if ($action === 'accept') {
        $offers[$offer_index]['status'] = 'accepted';
        $offers[$offer_index]['accepted_at'] = current_time('mysql');
        update_post_meta($listing_id, '_hdh_trade_status', 'in_progress');
        update_post_meta($listing_id, '_hdh_accepted_offerer_id', $offer['offerer_id']);
    } else {
        $offers[$offer_index]['status'] = 'rejected';
        $offers[$offer_index]['rejected_at'] = current_time('mysql');
    }
    update_post_meta($listing_id, '_hdh_offers', $offers);
    $message = $action === 'accept' ? hdh_get_message('ajax', 'offer_accepted', 'Teklif kabul edildi') : hdh_get_message('ajax', 'offer_rejected', 'Teklif reddedildi');
    wp_send_json_success(array('message' => $message));
}
add_action('wp_ajax_hdh_offer_response', 'hdh_handle_offer_response');

function hdh_handle_complete_exchange() {
    if (!is_user_logged_in()) { wp_send_json_error(array('message' => hdh_get_message('ajax', 'login_required', 'Giriş yapmanız gerekiyor'))); return; }
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_create_offer')) { wp_send_json_error(array('message' => hdh_get_message('ajax', 'security_failed', 'Güvenlik kontrolü başarısız'))); return; }
    $listing_id = isset($_POST['listing_id']) ? absint($_POST['listing_id']) : 0;
    $user_id = get_current_user_id();
    if (!$listing_id) { wp_send_json_error(array('message' => hdh_get_message('ajax', 'invalid_listing', 'Geçersiz ilan'))); return; }
    $listing = get_post($listing_id);
    if (!$listing || $listing->post_type !== 'hayday_trade') { wp_send_json_error(array('message' => hdh_get_message('ajax', 'listing_not_found', 'İlan bulunamadı'))); return; }
    $listing_author_id = $listing->post_author;
    $accepted_offerer_id = get_post_meta($listing_id, '_hdh_accepted_offerer_id', true);
    if ($user_id != $listing_author_id && $user_id != $accepted_offerer_id) { wp_send_json_error(array('message' => hdh_get_message('ajax', 'unauthorized_action', 'Yetkisiz işlem'))); return; }
    $author_confirmed = get_post_meta($listing_id, '_hdh_author_confirmed', true);
    $offerer_confirmed = get_post_meta($listing_id, '_hdh_offerer_confirmed', true);
    if ($user_id == $listing_author_id) {
        update_post_meta($listing_id, '_hdh_author_confirmed', '1');
        $author_confirmed = '1';
    } else {
        update_post_meta($listing_id, '_hdh_offerer_confirmed', '1');
        $offerer_confirmed = '1';
    }
    if ($author_confirmed === '1' && $offerer_confirmed === '1') {
        update_post_meta($listing_id, '_hdh_trade_status', 'completed');
        if (function_exists('hdh_award_exchange_jetons')) hdh_award_exchange_jetons($listing_author_id, $accepted_offerer_id);
        wp_send_json_success(array('message' => hdh_get_message('ajax', 'exchange_completed', 'Hediyeleşme tamamlandı!')));
    } else {
        wp_send_json_success(array('message' => hdh_get_message('ajax', 'exchange_confirmation_saved', 'Onayınız kaydedildi. Diğer tarafın onayını bekliyoruz.')));
    }
}
add_action('wp_ajax_hdh_complete_exchange', 'hdh_handle_complete_exchange');

function hdh_get_listing_offers($listing_id) {
    $offers = get_post_meta($listing_id, '_hdh_offers', true);
    return is_array($offers) ? $offers : array();
}
