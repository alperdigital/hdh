<?php
if (!defined('ABSPATH')) exit;

function hdh_handle_claim_daily_jeton() {
    if (!is_user_logged_in()) { wp_send_json_error(array('message' => 'Giriş yapmanız gerekiyor')); return; }
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_claim_daily_jeton')) { wp_send_json_error(array('message' => 'Güvenlik kontrolü başarısız')); return; }
    $user_id = get_current_user_id();
    if (function_exists('hdh_claim_daily_jeton')) {
        $result = hdh_claim_daily_jeton($user_id);
        if (is_wp_error($result)) { wp_send_json_error(array('message' => $result->get_error_message())); return; }
        $new_balance = function_exists('hdh_get_user_jeton_balance') ? hdh_get_user_jeton_balance($user_id) : 0;
        wp_send_json_success(array('message' => 'Günlük jetonunuz alındı!', 'new_balance' => $new_balance));
    } else {
        wp_send_json_error(array('message' => 'Jeton sistemi aktif değil'));
    }
}
add_action('wp_ajax_hdh_claim_daily_jeton', 'hdh_handle_claim_daily_jeton');
