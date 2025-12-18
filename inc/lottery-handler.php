<?php
if (!defined('ABSPATH')) exit;

function hdh_handle_join_lottery() {
    if (!is_user_logged_in()) { wp_send_json_error(array('message' => hdh_get_message('ajax', 'login_required', 'Giriş yapmanız gerekiyor'))); return; }
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_join_lottery')) { wp_send_json_error(array('message' => hdh_get_message('ajax', 'security_failed', 'Güvenlik kontrolü başarısız'))); return; }
    $user_id = get_current_user_id();
    $lottery_type = isset($_POST['lottery_type']) ? sanitize_text_field($_POST['lottery_type']) : '';
    $jeton_cost = isset($_POST['jeton_cost']) ? absint($_POST['jeton_cost']) : 0;
    if (!in_array($lottery_type, array('kurek', 'genisletme')) || $jeton_cost <= 0) { wp_send_json_error(array('message' => hdh_get_message('ajax', 'invalid_parameters', 'Geçersiz parametreler'))); return; }
    $balance = function_exists('hdh_get_user_jeton_balance') ? hdh_get_user_jeton_balance($user_id) : 0;
    if ($balance < $jeton_cost) { wp_send_json_error(array('message' => hdh_get_message('ajax', 'insufficient_tickets', 'Yetersiz bilet'))); return; }
    // Check if lottery is active
    if (function_exists('hdh_is_lottery_active')) {
        if (!hdh_is_lottery_active($lottery_type)) {
            wp_send_json_error(array('message' => hdh_get_message('ajax', 'lottery_not_active', 'Bu çekiliş şu anda aktif değil')));
            return;
        }
    } else {
        // Fallback: check status directly
        $lottery_status = get_option('hdh_lottery_status_' . $lottery_type, 'active');
        if ($lottery_status === 'ended' || $lottery_status === 'paused') {
            wp_send_json_error(array('message' => hdh_get_message('ajax', 'lottery_not_active', 'Bu çekiliş şu anda aktif değil')));
            return;
        }
    }
    
    $today = date('Y-m-d');
    
    // Get max daily entries from config
    $max_daily_entries = 3;
    if (function_exists('hdh_get_lottery_config')) {
        $config = hdh_get_lottery_config($lottery_type);
        $max_daily_entries = isset($config['max_daily_entries']) ? (int) $config['max_daily_entries'] : 3;
    }
    
    $entries_today = hdh_get_lottery_entries_today($user_id, $lottery_type, $today);
    if ($entries_today >= $max_daily_entries) {
        wp_send_json_error(array('message' => sprintf(hdh_get_message('ajax', 'lottery_max_entries', 'Bugün bu çekilişe maksimum %d kez katılabilirsiniz'), $max_daily_entries)));
        return;
    }
    
    // Spend jeton - ensure function exists
    if (!function_exists('hdh_spend_jeton')) {
        wp_send_json_error(array('message' => hdh_get_message('ajax', 'system_error', 'Sistem hatası. Lütfen daha sonra tekrar deneyin.')));
        return;
    }
    
    $spend_result = hdh_spend_jeton($user_id, $jeton_cost, 'lottery_entry', array('lottery_type' => $lottery_type, 'timestamp' => current_time('mysql')));
    if (is_wp_error($spend_result)) {
        wp_send_json_error(array('message' => $spend_result->get_error_message()));
        return;
    }
    
    // Verify spend was successful (should return true, not false)
    if ($spend_result === false) {
        wp_send_json_error(array('message' => hdh_get_message('ajax', 'system_error', 'Bilet harcanırken bir hata oluştu. Lütfen tekrar deneyin.')));
        return;
    }
    
    // Double-check balance after spending (safety check)
    $new_balance_check = function_exists('hdh_get_user_jeton_balance') ? hdh_get_user_jeton_balance($user_id) : 0;
    if ($new_balance_check < 0) {
        // Rollback: add back the jeton
        if (function_exists('hdh_add_bilet')) {
            hdh_add_bilet($user_id, $jeton_cost, 'lottery_entry_rollback', array('lottery_type' => $lottery_type));
        }
        wp_send_json_error(array('message' => hdh_get_message('ajax', 'system_error', 'Sistem hatası. Lütfen daha sonra tekrar deneyin.')));
        return;
    }
    $entries = get_user_meta($user_id, 'hdh_lottery_entries', true);
    if (!is_array($entries)) $entries = array();
    $entries[] = array('lottery_type' => $lottery_type, 'jeton_cost' => $jeton_cost, 'date' => $today, 'timestamp' => current_time('mysql'));
    update_user_meta($user_id, 'hdh_lottery_entries', $entries);
    wp_send_json_success(array('message' => hdh_get_message('ajax', 'lottery_join_success', 'Çekilişe başarıyla katıldınız!'), 'new_balance' => $balance - $jeton_cost));
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

/**
 * Get lottery participants (users who joined)
 * 
 * @param string $lottery_type 'kurek' or 'genisletme'
 * @return array Array of participants with user_id, display_name, entry_count, first_entry_date
 */
function hdh_get_lottery_participants($lottery_type) {
    if (!in_array($lottery_type, array('kurek', 'genisletme'))) {
        return array();
    }
    
    $users = get_users(array('fields' => 'all'));
    $participants = array();
    
    foreach ($users as $user) {
        $entries = get_user_meta($user->ID, 'hdh_lottery_entries', true);
        if (!is_array($entries)) {
            continue;
        }
        
        $user_entries = array();
        foreach ($entries as $entry) {
            if (isset($entry['lottery_type']) && $entry['lottery_type'] === $lottery_type) {
                $user_entries[] = $entry;
            }
        }
        
        if (!empty($user_entries)) {
            // Sort by timestamp to get first entry
            usort($user_entries, function($a, $b) {
                $time_a = isset($a['timestamp']) ? strtotime($a['timestamp']) : 0;
                $time_b = isset($b['timestamp']) ? strtotime($b['timestamp']) : 0;
                return $time_a - $time_b;
            });
            
            $participants[] = array(
                'user_id' => $user->ID,
                'display_name' => $user->display_name,
                'entry_count' => count($user_entries),
                'first_entry_date' => isset($user_entries[0]['date']) ? $user_entries[0]['date'] : '',
                'first_entry_timestamp' => isset($user_entries[0]['timestamp']) ? $user_entries[0]['timestamp'] : '',
            );
        }
    }
    
    // Sort by first entry timestamp (earliest first)
    usort($participants, function($a, $b) {
        $time_a = !empty($a['first_entry_timestamp']) ? strtotime($a['first_entry_timestamp']) : 0;
        $time_b = !empty($b['first_entry_timestamp']) ? strtotime($b['first_entry_timestamp']) : 0;
        return $time_a - $time_b;
    });
    
    return $participants;
}

/**
 * AJAX handler: Start lottery (admin only)
 */
function hdh_ajax_start_lottery() {
    if (!is_user_logged_in() || !current_user_can('administrator')) {
        wp_send_json_error(array('message' => 'Yetkiniz yok'));
        return;
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_join_lottery')) {
        wp_send_json_error(array('message' => 'Güvenlik kontrolü başarısız'));
        return;
    }
    
    $lottery_type = isset($_POST['lottery_type']) ? sanitize_text_field($_POST['lottery_type']) : '';
    if (!in_array($lottery_type, array('kurek', 'genisletme'))) {
        wp_send_json_error(array('message' => 'Geçersiz çekiliş tipi'));
        return;
    }
    
    $winner_id = hdh_start_lottery($lottery_type);
    
    if ($winner_id) {
        $winner = get_userdata($winner_id);
        wp_send_json_success(array(
            'message' => 'Çekiliş başarıyla başlatıldı!',
            'winner_id' => $winner_id,
            'winner_name' => $winner ? $winner->display_name : 'Bilinmiyor',
        ));
    } else {
        wp_send_json_error(array('message' => 'Çekiliş başlatılamadı. Katılım yok.'));
    }
}
add_action('wp_ajax_hdh_start_lottery', 'hdh_ajax_start_lottery');
