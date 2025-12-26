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
 * Create trade timeline events table
 */
function hdh_create_trade_timeline_events_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_timeline_events';
    $charset_collate = $wpdb->get_charset_collate();
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    
    if ($table_exists) {
        return; // Table already exists
    }
    
    $sql = "CREATE TABLE $table_name (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        trade_session_id bigint(20) UNSIGNED NOT NULL,
        event_type varchar(50) NOT NULL,
        event_data text DEFAULT NULL,
        user_id bigint(20) UNSIGNED DEFAULT NULL,
        side varchar(10) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY trade_session_id (trade_session_id),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('after_switch_theme', 'hdh_create_trade_timeline_events_table');
add_action('admin_init', 'hdh_create_trade_timeline_events_table');

/**
 * Migrate trade session table - Add new columns for 3-step system
 */
function hdh_migrate_trade_session_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_sessions';
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    if (!$table_exists) {
        return; // Table doesn't exist yet, will be created by hdh_create_trade_session_table
    }
    
    // Get existing columns
    $columns = $wpdb->get_col("DESC $table_name");
    
    // New columns to add
    $new_columns = array(
        'owner_farm_code' => "ALTER TABLE $table_name ADD COLUMN owner_farm_code varchar(20) DEFAULT NULL AFTER starter_user_id",
        'offerer_farm_code' => "ALTER TABLE $table_name ADD COLUMN offerer_farm_code varchar(20) DEFAULT NULL AFTER owner_farm_code",
        'friend_request_sent_at' => "ALTER TABLE $table_name ADD COLUMN friend_request_sent_at datetime DEFAULT NULL AFTER offerer_farm_code",
        'friend_request_accepted_at' => "ALTER TABLE $table_name ADD COLUMN friend_request_accepted_at datetime DEFAULT NULL AFTER friend_request_sent_at",
        'ready_owner_at' => "ALTER TABLE $table_name ADD COLUMN ready_owner_at datetime DEFAULT NULL AFTER friend_request_accepted_at",
        'ready_offerer_at' => "ALTER TABLE $table_name ADD COLUMN ready_offerer_at datetime DEFAULT NULL AFTER ready_owner_at",
        'collected_owner_at' => "ALTER TABLE $table_name ADD COLUMN collected_owner_at datetime DEFAULT NULL AFTER ready_offerer_at",
        'collected_offerer_at' => "ALTER TABLE $table_name ADD COLUMN collected_offerer_at datetime DEFAULT NULL AFTER collected_owner_at",
        'completed_owner_at' => "ALTER TABLE $table_name ADD COLUMN completed_owner_at datetime DEFAULT NULL AFTER collected_offerer_at",
        'completed_offerer_at' => "ALTER TABLE $table_name ADD COLUMN completed_offerer_at datetime DEFAULT NULL AFTER completed_owner_at",
        'report_unlock_at' => "ALTER TABLE $table_name ADD COLUMN report_unlock_at datetime DEFAULT NULL AFTER completed_offerer_at",
        'reported_at' => "ALTER TABLE $table_name ADD COLUMN reported_at datetime DEFAULT NULL AFTER report_unlock_at",
    );
    
    foreach ($new_columns as $col_name => $sql) {
        if (!in_array($col_name, $columns)) {
            $wpdb->query($sql);
        }
    }
    
    // Cancel existing active sessions (migrate to CANCELLED status)
    $wpdb->query(
        "UPDATE $table_name 
         SET status = 'CANCELLED' 
         WHERE status = 'ACTIVE' 
         AND (step1_starter_done_at IS NULL OR step5_starter_done_at IS NULL)"
    );
}
add_action('admin_init', 'hdh_migrate_trade_session_table');

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
    
    // Get owner farm code from user meta
    $owner_farm_code = get_user_meta($owner_user_id, 'farm_tag', true);
    if (empty($owner_farm_code)) {
        $owner_farm_code = get_user_meta($owner_user_id, 'hayday_farm_number', true);
    }
    // Format farm code with # prefix if not already
    if (!empty($owner_farm_code) && strpos($owner_farm_code, '#') !== 0) {
        $owner_farm_code = '#' . $owner_farm_code;
    }
    
    $result = $wpdb->insert(
        $table_name,
        array(
            'listing_id' => $listing_id,
            'owner_user_id' => $owner_user_id,
            'starter_user_id' => $starter_user_id,
            'status' => 'ACTIVE',
            'owner_farm_code' => $owner_farm_code,
        ),
        array('%d', '%d', '%d', '%s', '%s')
    );
    
    if ($result === false) {
        return new WP_Error('db_error', 'Veritabanı hatası');
    }
    
    $session_id = $wpdb->insert_id;
    
    // Create initial timeline event: Owner farm code shared
    if (!empty($owner_farm_code)) {
        hdh_create_timeline_event(
            $session_id,
            'farm_code_shared',
            array(
                'text' => 'Beni arkadaş olarak ekle. Çiftlik kodum: ' . $owner_farm_code,
                'farm_code' => $owner_farm_code,
                'role' => 'owner'
            ),
            $owner_user_id,
            null // Side calculated in frontend
        );
    }
    
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
    // Deprecated: Keep for backward compatibility but don't use in new system
    $session['current_step'] = hdh_get_trade_session_current_step($session);
    
    // Only set is_starter/is_owner if user_id provided
    if ($user_id) {
        $session['is_starter'] = ($session['starter_user_id'] == $user_id);
        $session['is_owner'] = ($session['owner_user_id'] == $user_id);
    } else {
        $session['is_starter'] = false;
        $session['is_owner'] = false;
    }
    
    // Add status label for new 3-step system
    if (function_exists('hdh_get_trade_status_label')) {
        $session['status_label'] = hdh_get_trade_status_label($session);
    }
    
    // Add timeline events if function exists
    if (function_exists('hdh_get_timeline_events')) {
        $session['timeline_events'] = hdh_get_timeline_events($session['id']);
    }
    
    // Add can_report flag
    if (function_exists('hdh_can_report')) {
        $session['can_report'] = hdh_can_report($session['id']);
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
        
        // Get current step (deprecated, kept for backward compatibility)
        $session['current_step'] = hdh_get_trade_session_current_step($session);
        
        // Get status label for new 3-step system
        if (function_exists('hdh_get_trade_status_label')) {
            $session['status_label'] = hdh_get_trade_status_label($session);
        }
        
        // Check if requires user action (new 3-step system logic)
        $requires_action = false;
        if (function_exists('hdh_can_user_perform_action')) {
            // Check if user can perform any action
            $actions = array('share_farm_code', 'send_friend_request', 'accept_friend_request', 
                           'mark_gift_ready', 'mark_gift_collected', 'complete_trade');
            foreach ($actions as $action) {
                if (hdh_can_user_perform_action($session, $user_id, $action)) {
                    $requires_action = true;
                    break;
                }
            }
        } else {
            // Fallback to old 5-step logic (deprecated)
            if ($session['current_step'] == 1 && $session['is_starter']) {
                $requires_action = true;
            } elseif ($session['current_step'] == 2 && $session['is_owner']) {
                $requires_action = true;
            } elseif ($session['current_step'] == 3 && $session['is_starter']) {
                $requires_action = true;
            } elseif ($session['current_step'] == 4 && $session['is_owner']) {
                $requires_action = true;
            } elseif ($session['current_step'] == 5 && $session['is_starter']) {
                $requires_action = true;
            }
        }
        
        $session['requires_action'] = $requires_action;
        
        // Add timeline events
        if (function_exists('hdh_get_timeline_events')) {
            $session['timeline_events'] = hdh_get_timeline_events($session['id']);
        }
        
        // Add can_report flag
        if (function_exists('hdh_can_report')) {
            $session['can_report'] = hdh_can_report($session['id']);
        }
        
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

/**
 * ============================================
 * NEW 3-STEP SYSTEM FUNCTIONS
 * ============================================
 */

/**
 * Create timeline event
 */
function hdh_create_timeline_event($session_id, $event_type, $event_data = array(), $user_id = null, $side = null) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_timeline_events';
    
    $result = $wpdb->insert(
        $table_name,
        array(
            'trade_session_id' => $session_id,
            'event_type' => $event_type,
            'event_data' => json_encode($event_data),
            'user_id' => $user_id,
            'side' => $side,
            'created_at' => current_time('mysql'),
        ),
        array('%d', '%s', '%s', '%d', '%s', '%s')
    );
    
    if ($result === false) {
        return false;
    }
    
    return $wpdb->insert_id;
}

/**
 * Get timeline events for a session
 */
function hdh_get_timeline_events($session_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_timeline_events';
    
    $events = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE trade_session_id = %d ORDER BY created_at ASC",
        $session_id
    ), ARRAY_A);
    
    // Decode event_data JSON
    foreach ($events as &$event) {
        if (!empty($event['event_data'])) {
            $event['event_data'] = json_decode($event['event_data'], true);
        }
    }
    
    return $events;
}

/**
 * Share farm code (offerer only)
 */
function hdh_share_farm_code($session_id, $user_id, $farm_code) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_sessions';
    
    $session = hdh_get_trade_session($session_id, null, $user_id);
    if (!$session) {
        return new WP_Error('session_not_found', 'Oturum bulunamadı');
    }
    
    // Only offerer (starter) can share farm code
    if ($session['starter_user_id'] != $user_id) {
        return new WP_Error('unauthorized', 'Bu işlemi yapmaya yetkiniz yok');
    }
    
    // Format farm code with # prefix if not already
    if (!empty($farm_code) && strpos($farm_code, '#') !== 0) {
        $farm_code = '#' . $farm_code;
    }
    
    $result = $wpdb->update(
        $table_name,
        array('offerer_farm_code' => $farm_code),
        array('id' => $session_id),
        array('%s'),
        array('%d')
    );
    
    if ($result === false) {
        return new WP_Error('db_error', 'Veritabanı hatası');
    }
    
    // Create timeline event (side will be calculated in frontend based on viewing user)
    hdh_create_timeline_event(
        $session_id,
        'farm_code_shared',
        array(
            'text' => 'Ekliyorum. Çiftlik kodum: ' . $farm_code,
            'farm_code' => $farm_code,
            'role' => 'offerer'
        ),
        $user_id,
        null // Side calculated in frontend
    );
    
    return hdh_get_trade_session($session_id, null, $user_id);
}

/**
 * Send friend request (offerer only)
 */
function hdh_send_friend_request($session_id, $user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_sessions';
    
    $session = hdh_get_trade_session($session_id, null, $user_id);
    if (!$session) {
        return new WP_Error('session_not_found', 'Oturum bulunamadı');
    }
    
    // Only offerer (starter) can send friend request
    if ($session['starter_user_id'] != $user_id) {
        return new WP_Error('unauthorized', 'Bu işlemi yapmaya yetkiniz yok');
    }
    
    // Check if already sent
    if ($session['friend_request_sent_at']) {
        return new WP_Error('already_sent', 'İstek zaten gönderilmiş');
    }
    
    $result = $wpdb->update(
        $table_name,
        array('friend_request_sent_at' => current_time('mysql')),
        array('id' => $session_id),
        array('%s'),
        array('%d')
    );
    
    if ($result === false) {
        return new WP_Error('db_error', 'Veritabanı hatası');
    }
    
    // Create timeline event (side will be calculated in frontend based on viewing user)
    hdh_create_timeline_event(
        $session_id,
        'friend_request_sent',
        array('text' => 'İstek gönderdim'),
        $user_id,
        null // Side calculated in frontend
    );
    
    return hdh_get_trade_session($session_id, null, $user_id);
}

/**
 * Accept friend request (owner only)
 */
function hdh_accept_friend_request($session_id, $user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_sessions';
    
    $session = hdh_get_trade_session($session_id, null, $user_id);
    if (!$session) {
        return new WP_Error('session_not_found', 'Oturum bulunamadı');
    }
    
    // Only owner can accept friend request
    if ($session['owner_user_id'] != $user_id) {
        return new WP_Error('unauthorized', 'Bu işlemi yapmaya yetkiniz yok');
    }
    
    // Check if request was sent
    if (!$session['friend_request_sent_at']) {
        return new WP_Error('no_request', 'İstek gönderilmemiş');
    }
    
    // Check if already accepted
    if ($session['friend_request_accepted_at']) {
        return new WP_Error('already_accepted', 'İstek zaten kabul edilmiş');
    }
    
    $result = $wpdb->update(
        $table_name,
        array('friend_request_accepted_at' => current_time('mysql')),
        array('id' => $session_id),
        array('%s'),
        array('%d')
    );
    
    if ($result === false) {
        return new WP_Error('db_error', 'Veritabanı hatası');
    }
    
    // Create timeline events (side will be calculated in frontend based on viewing user)
    hdh_create_timeline_event(
        $session_id,
        'friend_request_accepted',
        array('text' => 'Kabul ettim'),
        $user_id,
        null // Side calculated in frontend
    );
    
    // System message: Step 2 unlocked
    hdh_create_timeline_event(
        $session_id,
        'system',
        array('text' => 'Adım 2 açıldı'),
        null,
        'system'
    );
    
    return hdh_get_trade_session($session_id, null, $user_id);
}

/**
 * Mark gift as ready (both sides)
 */
function hdh_mark_gift_ready($session_id, $user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_sessions';
    
    $session = hdh_get_trade_session($session_id, null, $user_id);
    if (!$session) {
        return new WP_Error('session_not_found', 'Oturum bulunamadı');
    }
    
    // Check authorization
    if ($session['owner_user_id'] != $user_id && $session['starter_user_id'] != $user_id) {
        return new WP_Error('unauthorized', 'Bu işlemi yapmaya yetkiniz yok');
    }
    
    // Check if step 1 is completed
    if (!$session['friend_request_accepted_at']) {
        return new WP_Error('step_not_completed', 'Önce arkadaşlık isteğini kabul etmelisiniz');
    }
    
    // Determine which field to update
    $field = ($session['owner_user_id'] == $user_id) ? 'ready_owner_at' : 'ready_offerer_at';
    
    // Check if already marked
    if ($session[$field]) {
        return new WP_Error('already_marked', 'Zaten işaretlenmiş');
    }
    
    $result = $wpdb->update(
        $table_name,
        array($field => current_time('mysql')),
        array('id' => $session_id),
        array('%s'),
        array('%d')
    );
    
    if ($result === false) {
        return new WP_Error('db_error', 'Veritabanı hatası');
    }
    
    // Create timeline event (side will be calculated in frontend based on viewing user)
    hdh_create_timeline_event(
        $session_id,
        'gift_ready',
        array('text' => 'Hediyen hazır'),
        $user_id,
        null // Side calculated in frontend
    );
    
    // Check if both sides marked ready - unlock step 3
    $updated_session = hdh_get_trade_session($session_id, null, $user_id);
    if ($updated_session['ready_owner_at'] && $updated_session['ready_offerer_at']) {
        // System message: Step 3 unlocked
        hdh_create_timeline_event(
            $session_id,
            'system',
            array('text' => 'Adım 3 açıldı'),
            null,
            'system'
        );
    }
    
    return hdh_get_trade_session($session_id, null, $user_id);
}

/**
 * Mark gift as collected (both sides)
 */
function hdh_mark_gift_collected($session_id, $user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_sessions';
    
    $session = hdh_get_trade_session($session_id, null, $user_id);
    if (!$session) {
        return new WP_Error('session_not_found', 'Oturum bulunamadı');
    }
    
    // Check authorization
    if ($session['owner_user_id'] != $user_id && $session['starter_user_id'] != $user_id) {
        return new WP_Error('unauthorized', 'Bu işlemi yapmaya yetkiniz yok');
    }
    
    // Check if step 2 is completed (both sides ready)
    if (!$session['ready_owner_at'] || !$session['ready_offerer_at']) {
        return new WP_Error('step_not_completed', 'Önce her iki taraf da hediyeyi hazırlamalı');
    }
    
    // Determine which field to update
    $field = ($session['owner_user_id'] == $user_id) ? 'collected_owner_at' : 'collected_offerer_at';
    
    // Check if already marked
    if ($session[$field]) {
        return new WP_Error('already_marked', 'Zaten işaretlenmiş');
    }
    
    $result = $wpdb->update(
        $table_name,
        array($field => current_time('mysql')),
        array('id' => $session_id),
        array('%s'),
        array('%d')
    );
    
    if ($result === false) {
        return new WP_Error('db_error', 'Veritabanı hatası');
    }
    
    // Create timeline event (side will be calculated in frontend based on viewing user)
    hdh_create_timeline_event(
        $session_id,
        'gift_collected',
        array('text' => 'Aldım'),
        $user_id,
        null // Side calculated in frontend
    );
    
    return hdh_get_trade_session($session_id, null, $user_id);
}

/**
 * Complete trade (both sides) - New 3-step version
 */
function hdh_complete_trade_new($session_id, $user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_sessions';
    
    $session = hdh_get_trade_session($session_id, null, $user_id);
    if (!$session) {
        return new WP_Error('session_not_found', 'Oturum bulunamadı');
    }
    
    // Check authorization
    if ($session['owner_user_id'] != $user_id && $session['starter_user_id'] != $user_id) {
        return new WP_Error('unauthorized', 'Bu işlemi yapmaya yetkiniz yok');
    }
    
    // Determine which field to update
    $field = ($session['owner_user_id'] == $user_id) ? 'completed_owner_at' : 'completed_offerer_at';
    
    // Check if already marked
    if ($session[$field]) {
        return new WP_Error('already_marked', 'Zaten tamamlanmış');
    }
    
    $result = $wpdb->update(
        $table_name,
        array($field => current_time('mysql')),
        array('id' => $session_id),
        array('%s'),
        array('%d')
    );
    
    if ($result === false) {
        return new WP_Error('db_error', 'Veritabanı hatası');
    }
    
    // Create timeline event (side will be calculated in frontend based on viewing user)
    hdh_create_timeline_event(
        $session_id,
        'trade_completed',
        array('text' => 'Hediyeleşme tamamlandı'),
        $user_id,
        null // Side calculated in frontend
    );
    
    // Check if both sides completed
    $updated_session = hdh_get_trade_session($session_id, null, $user_id);
    if ($updated_session['completed_owner_at'] && $updated_session['completed_offerer_at']) {
        // Update status to COMPLETED
        $wpdb->update(
            $table_name,
            array(
                'status' => 'COMPLETED',
                'completed_at' => current_time('mysql'),
            ),
            array('id' => $session_id),
            array('%s', '%s'),
            array('%d')
        );
        
        // System message
        hdh_create_timeline_event(
            $session_id,
            'system',
            array('text' => '✅ Hediyeleşme tamamlandı'),
            null,
            'system'
        );
        
        // Update completed gift counts
        if (function_exists('hdh_increment_completed_gift_count')) {
            hdh_increment_completed_gift_count($session['owner_user_id']);
            hdh_increment_completed_gift_count($session['starter_user_id']);
        }
    } else {
        // First completion - unlock report button (10 minutes)
        hdh_unlock_report_button($session_id);
    }
    
    return hdh_get_trade_session($session_id, null, $user_id);
}

/**
 * Unlock report button (set 10 minute timer)
 */
function hdh_unlock_report_button($session_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_sessions';
    
    // Set report_unlock_at to now + 10 minutes
    $unlock_time = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    $result = $wpdb->update(
        $table_name,
        array('report_unlock_at' => $unlock_time),
        array('id' => $session_id),
        array('%s'),
        array('%d')
    );
    
    return $result !== false;
}

/**
 * Check if report button should be enabled
 */
function hdh_can_report($session_id) {
    $session = hdh_get_trade_session($session_id);
    if (!$session) {
        return false;
    }
    
    // If already reported, can't report again
    if ($session['reported_at']) {
        return false;
    }
    
    // If no unlock time set, can't report yet
    if (!$session['report_unlock_at']) {
        return false;
    }
    
    // Check if unlock time has passed
    $unlock_time = strtotime($session['report_unlock_at']);
    $current_time = current_time('timestamp');
    
    return $current_time >= $unlock_time;
}

/**
 * Get trade status label for UI
 */
function hdh_get_trade_status_label($session) {
    // Check if reported
    if ($session['reported_at'] || $session['status'] === 'DISPUTED') {
        return 'Şikayet açık';
    }
    
    // Check if completed
    if ($session['completed_owner_at'] && $session['completed_offerer_at']) {
        return 'Tamamlandı';
    }
    
    // Check if one side completed
    if ($session['completed_owner_at'] || $session['completed_offerer_at']) {
        return 'Karşı tarafın onayı bekleniyor';
    }
    
    // Check step 3 (collect)
    if ($session['collected_owner_at'] || $session['collected_offerer_at']) {
        return 'Hediyeni al';
    }
    
    // Check step 2 (ready)
    if ($session['ready_owner_at'] || $session['ready_offerer_at']) {
        return 'Hediye hazırla';
    }
    
    // Check step 1 (friend request)
    if ($session['friend_request_accepted_at']) {
        return 'Hediye hazırla';
    }
    
    // Default: waiting for friend request
    return 'Arkadaşlık bekleniyor';
}

/**
 * Check if user can perform action
 */
function hdh_can_user_perform_action($session, $user_id, $action) {
    $is_owner = ($session['owner_user_id'] == $user_id);
    $is_offerer = ($session['starter_user_id'] == $user_id);
    
    if (!$is_owner && !$is_offerer) {
        return false;
    }
    
    switch ($action) {
        case 'share_farm_code':
            return $is_offerer && empty($session['offerer_farm_code']);
        
        case 'send_friend_request':
            return $is_offerer && !$session['friend_request_sent_at'];
        
        case 'accept_friend_request':
            return $is_owner && $session['friend_request_sent_at'] && !$session['friend_request_accepted_at'];
        
        case 'mark_gift_ready':
            return ($is_owner || $is_offerer) && $session['friend_request_accepted_at'] && 
                   (($is_owner && !$session['ready_owner_at']) || ($is_offerer && !$session['ready_offerer_at']));
        
        case 'mark_gift_collected':
            return ($is_owner || $is_offerer) && 
                   $session['ready_owner_at'] && $session['ready_offerer_at'] &&
                   (($is_owner && !$session['collected_owner_at']) || ($is_offerer && !$session['collected_offerer_at']));
        
        case 'complete_trade':
            return ($is_owner || $is_offerer) &&
                   (($is_owner && !$session['completed_owner_at']) || ($is_offerer && !$session['completed_offerer_at']));
        
        default:
            return false;
    }
}

