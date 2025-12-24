<?php
/**
 * HDH: Trade Session System
 * Manages step-by-step gift exchange roadmap/stepper
 */

if (!defined('ABSPATH')) exit;

/**
 * Create trade session table on activation
 */
function hdh_create_trade_session_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_sessions';
    $charset_collate = $wpdb->get_charset_collate();
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    
    if ($table_exists) {
        return; // Table already exists
    }
    
    $sql = "CREATE TABLE $table_name (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        listing_id bigint(20) UNSIGNED NOT NULL,
        owner_user_id bigint(20) UNSIGNED NOT NULL,
        starter_user_id bigint(20) UNSIGNED NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'ACTIVE',
        step1_starter_done_at datetime DEFAULT NULL,
        step2_owner_done_at datetime DEFAULT NULL,
        step3_starter_done_at datetime DEFAULT NULL,
        step4_owner_done_at datetime DEFAULT NULL,
        step5_starter_done_at datetime DEFAULT NULL,
        completed_at datetime DEFAULT NULL,
        dispute_reason varchar(100) DEFAULT NULL,
        dispute_text text DEFAULT NULL,
        dispute_created_at datetime DEFAULT NULL,
        dispute_resolved_at datetime DEFAULT NULL,
        dispute_resolution_note text DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY listing_id (listing_id),
        KEY owner_user_id (owner_user_id),
        KEY starter_user_id (starter_user_id),
        KEY status (status)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('after_switch_theme', 'hdh_create_trade_session_table');
add_action('admin_init', 'hdh_create_trade_session_table'); // Also create on admin init

/**
 * Create trade session
 */
function hdh_create_trade_session($listing_id, $starter_user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_sessions';
    
    $listing = get_post($listing_id);
    if (!$listing || $listing->post_type !== 'hayday_trade') {
        return new WP_Error('invalid_listing', 'Geçersiz ilan');
    }
    
    $owner_user_id = $listing->post_author;
    
    if ($owner_user_id == $starter_user_id) {
        return new WP_Error('cannot_start_own', 'Kendi ilanınız için hediyeleşme başlatamazsınız');
    }
    
    // Check if session already exists
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE listing_id = %d AND starter_user_id = %d AND status IN ('ACTIVE', 'COMPLETED')",
        $listing_id,
        $starter_user_id
    ));
    
    if ($existing) {
        return $existing;
    }
    
    $result = $wpdb->insert(
        $table_name,
        array(
            'listing_id' => $listing_id,
            'owner_user_id' => $owner_user_id,
            'starter_user_id' => $starter_user_id,
            'status' => 'ACTIVE',
        ),
        array('%d', '%d', '%d', '%s')
    );
    
    if ($result === false) {
        return new WP_Error('db_error', 'Veritabanı hatası');
    }
    
    $session_id = $wpdb->insert_id;
    
    // Log event
    if (function_exists('hdh_log_event')) {
        hdh_log_event($starter_user_id, 'trade_session_started', array(
            'session_id' => $session_id,
            'listing_id' => $listing_id,
            'owner_user_id' => $owner_user_id,
        ));
    }
    
    return hdh_get_trade_session($session_id);
}

/**
 * Get trade session
 */
function hdh_get_trade_session($session_id = null, $listing_id = null, $user_id = null) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_sessions';
    
    if ($session_id) {
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $session_id
        ), ARRAY_A);
    } elseif ($listing_id && $user_id) {
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE listing_id = %d AND (owner_user_id = %d OR starter_user_id = %d) AND status IN ('ACTIVE', 'COMPLETED', 'DISPUTED') ORDER BY created_at DESC LIMIT 1",
            $listing_id,
            $user_id,
            $user_id
        ), ARRAY_A);
    } else {
        return null;
    }
    
    if (!$session) {
        return null;
    }
    
    // Convert to object-like array with computed fields
    $session['current_step'] = hdh_get_trade_session_current_step($session);
    // Only set is_starter/is_owner if user_id provided
    if ($user_id) {
        $session['is_starter'] = ($session['starter_user_id'] == $user_id);
        $session['is_owner'] = ($session['owner_user_id'] == $user_id);
    } else {
        $session['is_starter'] = false;
        $session['is_owner'] = false;
    }
    
    return $session;
}

/**
 * Get current step number (1-5)
 */
function hdh_get_trade_session_current_step($session) {
    if ($session['status'] === 'COMPLETED') {
        return 5; // Completed = step 5 done
    }
    if ($session['status'] === 'DISPUTED') {
        return 0; // Disputed state
    }
    
    if ($session['step5_starter_done_at']) {
        return 5; // Step 5 done = completed
    }
    if ($session['step4_owner_done_at']) {
        return 5;
    }
    if ($session['step3_starter_done_at']) {
        return 4;
    }
    if ($session['step2_owner_done_at']) {
        return 3;
    }
    if ($session['step1_starter_done_at']) {
        return 2;
    }
    
    return 1;
}

/**
 * Complete a step
 */
function hdh_complete_trade_step($session_id, $step, $user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_sessions';
    
    $session = hdh_get_trade_session($session_id);
    if (!$session) {
        return new WP_Error('session_not_found', 'Oturum bulunamadı');
    }
    
    // Validate user can complete this step
    $can_complete = false;
    $step_field = '';
    
    if ($step == 1 && $session['starter_user_id'] == $user_id && !$session['step1_starter_done_at']) {
        $can_complete = true;
        $step_field = 'step1_starter_done_at';
    } elseif ($step == 2 && $session['owner_user_id'] == $user_id && !$session['step2_owner_done_at']) {
        $can_complete = true;
        $step_field = 'step2_owner_done_at';
    } elseif ($step == 3 && $session['starter_user_id'] == $user_id && !$session['step3_starter_done_at']) {
        $can_complete = true;
        $step_field = 'step3_starter_done_at';
    } elseif ($step == 4 && $session['owner_user_id'] == $user_id && !$session['step4_owner_done_at']) {
        $can_complete = true;
        $step_field = 'step4_owner_done_at';
    } elseif ($step == 5 && $session['starter_user_id'] == $user_id && !$session['step5_starter_done_at']) {
        $can_complete = true;
        $step_field = 'step5_starter_done_at';
    }
    
    if (!$can_complete) {
        return new WP_Error('invalid_step', 'Bu adımı tamamlayamazsınız');
    }
    
    // Validate step order
    $current_step = hdh_get_trade_session_current_step($session);
    if ($step != $current_step) {
        return new WP_Error('wrong_step_order', 'Adımlar sırayla tamamlanmalı');
    }
    
    // Update step
    $result = $wpdb->update(
        $table_name,
        array($step_field => current_time('mysql')),
        array('id' => $session_id),
        array('%s'),
        array('%d')
    );
    
    if ($result === false) {
        return new WP_Error('db_error', 'Veritabanı hatası');
    }
    
    // Check if completed (step 5 done = auto complete)
    $updated_session = hdh_get_trade_session($session_id);
    if ($updated_session['step5_starter_done_at']) {
        hdh_complete_trade_session($session_id);
    }
    
    // Log event
    if (function_exists('hdh_log_event')) {
        hdh_log_event($user_id, 'trade_step_completed', array(
            'session_id' => $session_id,
            'step' => $step,
        ));
    }
    
    return hdh_get_trade_session($session_id);
}

/**
 * Complete trade session
 */
function hdh_complete_trade_session($session_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_sessions';
    
    $session = hdh_get_trade_session($session_id);
    if (!$session) {
        return new WP_Error('session_not_found', 'Oturum bulunamadı');
    }
    
    $result = $wpdb->update(
        $table_name,
        array(
            'status' => 'COMPLETED',
            'completed_at' => current_time('mysql'),
        ),
        array('id' => $session_id),
        array('%s', '%s'),
        array('%d')
    );
    
    if ($result === false) {
        return new WP_Error('db_error', 'Veritabanı hatası');
    }
    
    // Update completed gift counts
    if (function_exists('hdh_increment_completed_gift_count')) {
        hdh_increment_completed_gift_count($session['owner_user_id']);
        hdh_increment_completed_gift_count($session['starter_user_id']);
    }
    
    // Log event
    if (function_exists('hdh_log_event')) {
        hdh_log_event($session['owner_user_id'], 'trade_completed', array(
            'session_id' => $session_id,
            'listing_id' => $session['listing_id'],
        ));
        hdh_log_event($session['starter_user_id'], 'trade_completed', array(
            'session_id' => $session_id,
            'listing_id' => $session['listing_id'],
        ));
    }
    
    return hdh_get_trade_session($session_id);
}

/**
 * Create dispute
 */
function hdh_create_trade_dispute($session_id, $user_id, $reason, $text) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_sessions';
    
    $session = hdh_get_trade_session($session_id);
    if (!$session) {
        return new WP_Error('session_not_found', 'Oturum bulunamadı');
    }
    
    if ($session['owner_user_id'] != $user_id && $session['starter_user_id'] != $user_id) {
        return new WP_Error('unauthorized', 'Yetkiniz yok');
    }
    
    if ($session['status'] === 'COMPLETED') {
        return new WP_Error('already_completed', 'Tamamlanmış hediyeleşme için anlaşmazlık açılamaz');
    }
    
    $result = $wpdb->update(
        $table_name,
        array(
            'status' => 'DISPUTED',
            'dispute_reason' => sanitize_text_field($reason),
            'dispute_text' => sanitize_textarea_field($text),
            'dispute_created_at' => current_time('mysql'),
        ),
        array('id' => $session_id),
        array('%s', '%s', '%s', '%s'),
        array('%d')
    );
    
    if ($result === false) {
        return new WP_Error('db_error', 'Veritabanı hatası');
    }
    
    // Log event
    if (function_exists('hdh_log_event')) {
        hdh_log_event($user_id, 'trade_dispute_created', array(
            'session_id' => $session_id,
            'reason' => $reason,
        ));
    }
    
    return hdh_get_trade_session($session_id);
}

/**
 * Resolve dispute (admin only)
 */
function hdh_resolve_trade_dispute($session_id, $resolution_note, $action = 'resolved') {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_sessions';
    
    if (!current_user_can('administrator')) {
        return new WP_Error('unauthorized', 'Yetkiniz yok');
    }
    
    $session = hdh_get_trade_session($session_id);
    if (!$session || $session['status'] !== 'DISPUTED') {
        return new WP_Error('invalid_session', 'Geçersiz oturum veya durum');
    }
    
    $new_status = ($action === 'resolved') ? 'COMPLETED' : 'CANCELLED';
    
    $result = $wpdb->update(
        $table_name,
        array(
            'status' => $new_status,
            'dispute_resolved_at' => current_time('mysql'),
            'dispute_resolution_note' => sanitize_textarea_field($resolution_note),
        ),
        array('id' => $session_id),
        array('%s', '%s', '%s'),
        array('%d')
    );
    
    if ($result === false) {
        return new WP_Error('db_error', 'Veritabanı hatası');
    }
    
    // Log event
    if (function_exists('hdh_log_event')) {
        hdh_log_event(get_current_user_id(), 'trade_dispute_resolved', array(
            'session_id' => $session_id,
            'action' => $action,
        ));
    }
    
    return hdh_get_trade_session($session_id);
}

/**
 * Get active trade sessions for a user
 * 
 * @param int $user_id User ID
 * @param bool $require_action_only If true, only return trades requiring user action
 * @return array Array of trade sessions
 */
function hdh_get_user_active_trades($user_id, $require_action_only = false) {
    if (!$user_id) {
        return array();
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_sessions';
    
    // Get all active trades for user
    $sessions = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table_name}
         WHERE (owner_user_id = %d OR starter_user_id = %d)
         AND status = 'ACTIVE'
         ORDER BY updated_at DESC",
        $user_id,
        $user_id
    ), ARRAY_A);
    
    if (empty($sessions)) {
        return array();
    }
    
    $active_trades = array();
    
    foreach ($sessions as $session) {
        // Enrich with listing and user data
        $listing = get_post($session['listing_id']);
        if (!$listing) {
            continue; // Skip if listing deleted
        }
        
        $session['listing_title'] = $listing->post_title;
        $session['listing_id'] = $session['listing_id'];
        
        // Determine counterpart
        if ($session['owner_user_id'] == $user_id) {
            $counterpart_id = $session['starter_user_id'];
            $session['is_owner'] = true;
            $session['is_starter'] = false;
        } else {
            $counterpart_id = $session['owner_user_id'];
            $session['is_owner'] = false;
            $session['is_starter'] = true;
        }
        
        $counterpart = get_userdata($counterpart_id);
        if (!$counterpart) {
            continue; // Skip if user deleted
        }
        
        $session['counterpart_id'] = $counterpart_id;
        $session['counterpart_name'] = $counterpart->display_name;
        $session['counterpart_level'] = 1;
        if (function_exists('hdh_get_user_state')) {
            $counterpart_state = hdh_get_user_state($counterpart_id);
            $session['counterpart_level'] = $counterpart_state['level'] ?? 1;
        }
        
        // Get presence label
        $session['counterpart_presence'] = '3+ gün önce';
        if (function_exists('hdh_get_presence_bucket') && function_exists('hdh_format_presence_label')) {
            $presence_bucket = hdh_get_presence_bucket($counterpart_id);
            $session['counterpart_presence'] = hdh_format_presence_label($presence_bucket, null);
        }
        
        // Get current step
        $session['current_step'] = hdh_get_trade_session_current_step($session);
        
        // Check if requires user action
        $requires_action = false;
        if ($session['current_step'] == 1 && $session['is_starter']) {
            $requires_action = true; // Starter needs to do step 1
        } elseif ($session['current_step'] == 2 && $session['is_owner']) {
            $requires_action = true; // Owner needs to do step 2
        } elseif ($session['current_step'] == 3 && $session['is_starter']) {
            $requires_action = true; // Starter needs to do step 3
        } elseif ($session['current_step'] == 4 && $session['is_owner']) {
            $requires_action = true; // Owner needs to do step 4
        } elseif ($session['current_step'] == 5 && $session['is_starter']) {
            $requires_action = true; // Starter needs to do step 5
        }
        
        $session['requires_action'] = $requires_action;
        
        // Filter if only requiring action
        if ($require_action_only && !$requires_action) {
            continue;
        }
        
        $active_trades[] = $session;
    }
    
    return $active_trades;
}

/**
 * Increment completed gift count
 */
function hdh_increment_completed_gift_count($user_id) {
    $current = (int) get_user_meta($user_id, 'hdh_completed_gifts', true);
    update_user_meta($user_id, 'hdh_completed_gifts', $current + 1);
}

