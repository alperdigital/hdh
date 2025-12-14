<?php
if (!defined('ABSPATH')) exit;

function hdh_handle_join_lottery() {
    if (!is_user_logged_in()) { wp_send_json_error(array('message' => 'Giriş yapmanız gerekiyor')); return; }
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_join_lottery')) { wp_send_json_error(array('message' => 'Güvenlik kontrolü başarısız')); return; }
    $user_id = get_current_user_id();
    $lottery_type = isset($_POST['lottery_type']) ? sanitize_text_field($_POST['lottery_type']) : '';
    $jeton_cost = isset($_POST['jeton_cost']) ? absint($_POST['jeton_cost']) : 0;
    if (!in_array($lottery_type, array('kurek', 'genisletme')) || $jeton_cost <= 0) { wp_send_json_error(array('message' => 'Geçersiz parametreler')); return; }
    $balance = function_exists('hdh_get_user_jeton_balance') ? hdh_get_user_jeton_balance($user_id) : 0;
    if ($balance < $jeton_cost) { wp_send_json_error(array('message' => 'Yetersiz bilet')); return; }
    $today = date('Y-m-d');
    $entries_today = hdh_get_lottery_entries_today($user_id, $lottery_type, $today);
    if ($entries_today >= 3) { wp_send_json_error(array('message' => 'Bugün bu çekilişe maksimum 3 kez katılabilirsiniz')); return; }
    $spend_result = function_exists('hdh_spend_jeton') ? hdh_spend_jeton($user_id, $jeton_cost, 'lottery_entry', array('lottery_type' => $lottery_type, 'timestamp' => current_time('mysql'))) : false;
    if (is_wp_error($spend_result)) { wp_send_json_error(array('message' => $spend_result->get_error_message())); return; }
    $entries = get_user_meta($user_id, 'hdh_lottery_entries', true);
    if (!is_array($entries)) $entries = array();
    $entries[] = array('lottery_type' => $lottery_type, 'jeton_cost' => $jeton_cost, 'date' => $today, 'timestamp' => current_time('mysql'));
    update_user_meta($user_id, 'hdh_lottery_entries', $entries);
    wp_send_json_success(array('message' => 'Çekilişe başarıyla katıldınız!', 'new_balance' => $balance - $jeton_cost));
}
add_action('wp_ajax_hdh_join_lottery', 'hdh_handle_join_lottery');

function hdh_get_lottery_entries_today($user_id, $lottery_type, $date = null) {
    if (!$date) $date = date('Y-m-d');
    $entries = get_user_meta($user_id, 'hdh_lottery_entries', true);
    if (!is_array($entries)) return 0;
    $count = 0;
    foreach ($entries as $entry) {
        if (isset($entry['lottery_type']) && $entry['lottery_type'] === $lottery_type && isset($entry['date']) && $entry['date'] === $date) $count++;
    }
    return $count;
}

function hdh_get_lottery_total_entries($lottery_type) {
    $users = get_users(array('fields' => 'ID'));
    $total = 0;
    foreach ($users as $user_id) {
        $entries = get_user_meta($user_id, 'hdh_lottery_entries', true);
        if (is_array($entries)) {
            foreach ($entries as $entry) {
                if (isset($entry['lottery_type']) && $entry['lottery_type'] === $lottery_type) $total++;
            }
        }
    }
    return $total;
}
