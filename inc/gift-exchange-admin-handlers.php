<?php
/**
 * HDH: Gift Exchange Admin Handlers
 * Handlers for penalty application in gift exchange disputes
 */

if (!defined('ABSPATH')) exit;

/**
 * Apply penalty to users involved in dispute
 */
function hdh_apply_gift_exchange_penalty($exchange_id, $reporter_penalty, $reporter_penalty_amount, $reported_penalty, $reported_penalty_amount, $admin_note = '') {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_gift_exchanges';
    
    $exchange = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $exchange_id
    ), ARRAY_A);
    
    if (!$exchange) {
        return new WP_Error('exchange_not_found', 'Hediyeleşme bulunamadı');
    }
    
    $reporter_id = $exchange['reported_by_user_id'];
    $reported_id = ($exchange['owner_user_id'] == $reporter_id) ? $exchange['offerer_user_id'] : $exchange['owner_user_id'];
    
    // Apply penalties
    if ($reporter_penalty && $reporter_penalty !== 'none') {
        hdh_apply_user_penalty($reporter_id, $reporter_penalty, $reporter_penalty_amount, $admin_note, $exchange_id);
    }
    
    if ($reported_penalty && $reported_penalty !== 'none') {
        hdh_apply_user_penalty($reported_id, $reported_penalty, $reported_penalty_amount, $admin_note, $exchange_id);
    }
    
    // Mark dispute as resolved
    $wpdb->update(
        $table_name,
        array('status' => 'RESOLVED'),
        array('id' => $exchange_id),
        array('%s'),
        array('%d')
    );
    
    return true;
}

/**
 * Apply penalty to a user
 */
function hdh_apply_user_penalty($user_id, $penalty_type, $penalty_amount, $admin_note, $exchange_id) {
    $admin_id = get_current_user_id();
    
    switch ($penalty_type) {
        case 'warning':
            // Just log a warning, no action needed
            if (function_exists('hdh_log_event')) {
                hdh_log_event($user_id, 'admin_warning', array(
                    'reason' => $admin_note,
                    'exchange_id' => $exchange_id,
                    'admin_id' => $admin_id,
                ));
            }
            break;
            
        case 'ban_1day':
            if (function_exists('hdh_ban_user')) {
                hdh_ban_user($user_id, $admin_note ?: 'Hediyeleşme şikayeti', 1, $admin_id, $exchange_id);
            }
            break;
            
        case 'ban_3days':
            if (function_exists('hdh_ban_user')) {
                hdh_ban_user($user_id, $admin_note ?: 'Hediyeleşme şikayeti', 3, $admin_id, $exchange_id);
            }
            break;
            
        case 'ban_7days':
            if (function_exists('hdh_ban_user')) {
                hdh_ban_user($user_id, $admin_note ?: 'Hediyeleşme şikayeti', 7, $admin_id, $exchange_id);
            }
            break;
            
        case 'ban_30days':
            if (function_exists('hdh_ban_user')) {
                hdh_ban_user($user_id, $admin_note ?: 'Hediyeleşme şikayeti', 30, $admin_id, $exchange_id);
            }
            break;
            
        case 'ban_permanent':
            if (function_exists('hdh_ban_user')) {
                hdh_ban_user($user_id, $admin_note ?: 'Hediyeleşme şikayeti', 0, $admin_id, $exchange_id); // 0 = permanent
            }
            break;
            
        case 'decrease_trust':
            if (function_exists('hdh_decrease_trust_rating')) {
                hdh_decrease_trust_rating($user_id, $penalty_amount, $admin_note ?: 'Hediyeleşme şikayeti', $exchange_id);
            }
            break;
    }
}

