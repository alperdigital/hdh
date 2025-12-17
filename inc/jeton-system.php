<?php
/**
 * HDH: Bilet Economy System (formerly Jeton)
 */

if (!defined('ABSPATH')) exit;

function hdh_get_user_jeton_balance($user_id) {
    if (!$user_id) return 0;
    return (int) get_user_meta($user_id, 'hdh_jeton_balance', true);
}

function hdh_add_jeton($user_id, $amount, $reason = '', $metadata = array()) {
    if (!$user_id || $amount <= 0) return new WP_Error('invalid_params', 'Invalid');
    $current = hdh_get_user_jeton_balance($user_id);
    $new = $current + $amount;
    update_user_meta($user_id, 'hdh_jeton_balance', $new);
    hdh_log_jeton_transaction($user_id, $amount, 'add', $reason, $metadata);
    
    // Track reward event (if event system is loaded)
    if (function_exists('hdh_track_reward')) {
        hdh_track_reward($user_id, 'bilet', $amount, $reason, $metadata);
    }
    
    return true;
}

function hdh_spend_jeton($user_id, $amount, $reason = '', $metadata = array()) {
    if (!$user_id || $amount <= 0) return new WP_Error('invalid_params', 'Invalid');
    $current = hdh_get_user_jeton_balance($user_id);
    if ($current < $amount) return new WP_Error('insufficient_balance', hdh_get_message('ajax', 'insufficient_balance', 'Yetersiz jeton'));
    $new = $current - $amount;
    update_user_meta($user_id, 'hdh_jeton_balance', $new);
    hdh_log_jeton_transaction($user_id, $amount, 'spend', $reason, $metadata);
    
    // Track action event (if event system is loaded)
    if (function_exists('hdh_track_action')) {
        hdh_track_action($user_id, 'bilet_spent', array(
            'amount' => $amount,
            'reason' => $reason,
            'metadata' => $metadata,
        ));
    }
    
    return true;
}

function hdh_log_jeton_transaction($user_id, $amount, $type, $reason, $metadata) {
    if (!$user_id) return;
    $transactions = get_user_meta($user_id, 'hdh_jeton_transactions', true);
    if (!is_array($transactions)) $transactions = array();
    array_unshift($transactions, array(
        'timestamp' => current_time('mysql'),
        'amount' => $amount,
        'type' => $type,
        'reason' => $reason,
        'metadata' => $metadata,
    ));
    if (count($transactions) > 100) $transactions = array_slice($transactions, 0, 100);
    update_user_meta($user_id, 'hdh_jeton_transactions', $transactions);
}

function hdh_get_jeton_transactions($user_id, $limit = 20) {
    if (!$user_id) return array();
    $transactions = get_user_meta($user_id, 'hdh_jeton_transactions', true);
    if (!is_array($transactions)) return array();
    return array_slice($transactions, 0, $limit);
}

function hdh_can_claim_daily_jeton($user_id) {
    if (!$user_id) return false;
    $last_claim = get_user_meta($user_id, 'hdh_last_daily_claim', true);
    if (empty($last_claim)) return true;
    return date('Y-m-d', strtotime($last_claim)) !== date('Y-m-d');
}

function hdh_claim_daily_jeton($user_id) {
    if (!hdh_can_claim_daily_jeton($user_id)) {
        return new WP_Error('already_claimed', hdh_get_message('ajax', 'daily_claim_limit', 'Bugün zaten jeton aldınız'));
    }
    $result = hdh_add_jeton($user_id, 1, 'daily_claim');
    if (is_wp_error($result)) return $result;
    update_user_meta($user_id, 'hdh_last_daily_claim', current_time('mysql'));
    return true;
}

function hdh_can_earn_exchange_reward($user1_id, $user2_id) {
    if (!$user1_id || !$user2_id || $user1_id === $user2_id) return false;
    $today = date('Y-m-d');
    $transactions = hdh_get_jeton_transactions($user1_id, 50);
    foreach ($transactions as $t) {
        if ($t['type'] === 'add' && $t['reason'] === 'completed_exchange' &&
            isset($t['metadata']['other_user_id']) && $t['metadata']['other_user_id'] == $user2_id) {
            if (date('Y-m-d', strtotime($t['timestamp'])) === $today) return false;
        }
    }
    return true;
}

function hdh_award_exchange_jetons($user1_id, $user2_id) {
    if (!$user1_id || !$user2_id || $user1_id === $user2_id) {
        return new WP_Error('invalid_users', 'Invalid');
    }
    if (!hdh_can_earn_exchange_reward($user1_id, $user2_id)) {
        return new WP_Error('abuse_prevention', hdh_get_message('ajax', 'abuse_prevention', 'Bu kullanıcıyla bugün zaten ödül aldınız'));
    }
    hdh_add_jeton($user1_id, 5, 'completed_exchange', array('other_user_id' => $user2_id));
    hdh_add_jeton($user2_id, 5, 'completed_exchange', array('other_user_id' => $user1_id));
    return true;
}
