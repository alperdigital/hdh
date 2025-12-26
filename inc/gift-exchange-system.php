<?php
/**
 * HDH: Gift Exchange System
 * Mesajlaşma tabanlı hediyeleşme sistemi
 */

if (!defined('ABSPATH')) exit;

/**
 * Create gift exchanges table
 */
function hdh_create_gift_exchanges_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_gift_exchanges';
    $charset_collate = $wpdb->get_charset_collate();
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    
    if ($table_exists) {
        return; // Table already exists
    }
    
    // Try to use dbDelta if available
    $use_dbdelta = false;
    if (!function_exists('dbDelta')) {
        if (file_exists(ABSPATH . 'wp-admin/includes/upgrade.php')) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        }
    }
    
    if (function_exists('dbDelta')) {
        $use_dbdelta = true;
    }
    
    $sql = "CREATE TABLE $table_name (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        listing_id bigint(20) UNSIGNED NOT NULL,
        owner_user_id bigint(20) UNSIGNED NOT NULL,
        offerer_user_id bigint(20) UNSIGNED NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'ACTIVE',
        completed_owner_at datetime DEFAULT NULL,
        completed_offerer_at datetime DEFAULT NULL,
        reported_at datetime DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY listing_id (listing_id),
        KEY owner_user_id (owner_user_id),
        KEY offerer_user_id (offerer_user_id),
        KEY status (status)
    ) $charset_collate;";
    
    if ($use_dbdelta) {
        dbDelta($sql);
    } else {
        // Fallback: Direct SQL query (only if table doesn't exist)
        // This is safer than dbDelta but less flexible
        $wpdb->query($sql);
        
        // Check for errors
        if ($wpdb->last_error) {
            // Silently fail - table might already exist or there's a permission issue
            return;
        }
    }
}

/**
 * Ensure gift tables exist (lazy loading)
 * This function is called before any table queries to ensure tables are created
 */
function hdh_ensure_gift_tables_exist() {
    static $tables_checked = false;
    
    if ($tables_checked) {
        return; // Already checked in this request
    }
    
    global $wpdb;
    $exchanges_table = $wpdb->prefix . 'hdh_gift_exchanges';
    $messages_table = $wpdb->prefix . 'hdh_gift_messages';
    
    $exchanges_exists = $wpdb->get_var("SHOW TABLES LIKE '$exchanges_table'") == $exchanges_table;
    $messages_exists = $wpdb->get_var("SHOW TABLES LIKE '$messages_table'") == $messages_table;
    
    if (!$exchanges_exists) {
        hdh_create_gift_exchanges_table();
    }
    if (!$messages_exists) {
        hdh_create_gift_messages_table();
    }
    
    $tables_checked = true;
}

// Don't create tables on init hook - use lazy loading only
// Tables will be created automatically when first needed via hdh_ensure_gift_tables_exist()

/**
 * Create gift messages table
 */
function hdh_create_gift_messages_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_gift_messages';
    $charset_collate = $wpdb->get_charset_collate();
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    
    if ($table_exists) {
        return; // Table already exists
    }
    
    // Try to use dbDelta if available
    $use_dbdelta = false;
    if (!function_exists('dbDelta')) {
        if (file_exists(ABSPATH . 'wp-admin/includes/upgrade.php')) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        }
    }
    
    if (function_exists('dbDelta')) {
        $use_dbdelta = true;
    }
    
    $sql = "CREATE TABLE $table_name (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        gift_exchange_id bigint(20) UNSIGNED NOT NULL,
        user_id bigint(20) UNSIGNED NOT NULL,
        message text NOT NULL,
        is_system_message tinyint(1) DEFAULT 0,
        is_read tinyint(1) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY gift_exchange_id (gift_exchange_id),
        KEY user_id (user_id),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    if ($use_dbdelta) {
        dbDelta($sql);
    } else {
        // Fallback: Direct SQL query (only if table doesn't exist)
        // This is safer than dbDelta but less flexible
        $wpdb->query($sql);
        
        // Check for errors
        if ($wpdb->last_error) {
            // Silently fail - table might already exist or there's a permission issue
            return;
        }
    }
}

/**
 * ============================================
 * GIFT EXCHANGE FUNCTIONS
 * ============================================
 */

/**
 * Create gift exchange
 */
function hdh_create_gift_exchange($listing_id, $offerer_user_id) {
    // Ensure tables exist before querying
    hdh_ensure_gift_tables_exist();
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_gift_exchanges';
    
    // Check for database errors
    if ($wpdb->last_error) {
        return new WP_Error('db_error', 'Veritabanı hatası');
    }
    
    $listing = get_post($listing_id);
    if (!$listing || $listing->post_type !== 'hayday_trade') {
        return new WP_Error('invalid_listing', 'Geçersiz ilan');
    }
    
    $owner_user_id = $listing->post_author;
    
    if ($owner_user_id == $offerer_user_id) {
        return new WP_Error('cannot_start_own', 'Kendi ilanınız için hediyeleşme başlatamazsınız');
    }
    
    // Check if exchange already exists
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE listing_id = %d AND offerer_user_id = %d AND status = 'ACTIVE'",
        $listing_id,
        $offerer_user_id
    ), ARRAY_A);
    
    if ($existing) {
        return $existing; // Return existing exchange
    }
    
    $result = $wpdb->insert(
        $table_name,
        array(
            'listing_id' => $listing_id,
            'owner_user_id' => $owner_user_id,
            'offerer_user_id' => $offerer_user_id,
            'status' => 'ACTIVE',
        ),
        array('%d', '%d', '%d', '%s')
    );
    
    if ($result === false || $wpdb->last_error) {
        return new WP_Error('db_error', 'Veritabanı hatası: ' . ($wpdb->last_error ?: 'Bilinmeyen hata'));
    }
    
    $exchange_id = $wpdb->insert_id;
    
    // Get exchange data first
    $exchange = hdh_get_gift_exchange($exchange_id, $offerer_user_id);
    
    // Auto-send first message (only if function exists and exchange is valid)
    if ($exchange && function_exists('hdh_send_gift_message')) {
        $offerer_farm_code = get_user_meta($offerer_user_id, 'farm_tag', true);
        if (empty($offerer_farm_code)) {
            $offerer_farm_code = get_user_meta($offerer_user_id, 'hayday_farm_number', true);
        }
        
        // Format farm code with # prefix if not already
        if (!empty($offerer_farm_code) && strpos($offerer_farm_code, '#') !== 0) {
            $offerer_farm_code = '#' . $offerer_farm_code;
        }
        
        $first_message = 'Ekle beni Çiftlik kodum:' . $offerer_farm_code;
        hdh_send_gift_message($exchange_id, $offerer_user_id, $first_message, true);
        
        // Refresh exchange data after sending message
        $exchange = hdh_get_gift_exchange($exchange_id, $offerer_user_id);
    }
    
    return $exchange;
}

/**
 * Get gift exchange
 */
function hdh_get_gift_exchange($exchange_id, $user_id = null) {
    // Ensure tables exist before querying
    hdh_ensure_gift_tables_exist();
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_gift_exchanges';
    
    // Check for database errors
    if ($wpdb->last_error) {
        return null;
    }
    
    $exchange = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $exchange_id
    ), ARRAY_A);
    
    // Check for database errors after query
    if ($wpdb->last_error) {
        return null;
    }
    
    if (!$exchange) {
        return null;
    }
    
    // Check authorization if user_id provided
    if ($user_id && $exchange['owner_user_id'] != $user_id && $exchange['offerer_user_id'] != $user_id) {
        return null;
    }
    
    // Add computed fields
    $exchange['is_owner'] = ($user_id && $exchange['owner_user_id'] == $user_id);
    $exchange['is_offerer'] = ($user_id && $exchange['offerer_user_id'] == $user_id);
    
    // Get counterpart info (only if user_id is provided)
    if ($user_id) {
        $counterpart_id = ($exchange['owner_user_id'] == $user_id) ? $exchange['offerer_user_id'] : $exchange['owner_user_id'];
        $counterpart = get_userdata($counterpart_id);
        if ($counterpart) {
            $exchange['counterpart_id'] = $counterpart_id;
            $exchange['counterpart_name'] = $counterpart->display_name;
        }
    }
    
    // Get listing info
    $listing = get_post($exchange['listing_id']);
    if ($listing) {
        $exchange['listing_title'] = $listing->post_title;
    }
    
    return $exchange;
}

/**
 * Get user gift exchanges
 */
function hdh_get_user_gift_exchanges($user_id) {
    if (!$user_id) {
        return array();
    }
    
    // Ensure tables exist before querying
    hdh_ensure_gift_tables_exist();
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_gift_exchanges';
    
    // Check for database errors
    if ($wpdb->last_error) {
        return array();
    }
    
    $exchanges = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name
         WHERE (owner_user_id = %d OR offerer_user_id = %d)
         AND status = 'ACTIVE'
         ORDER BY updated_at DESC",
        $user_id,
        $user_id
    ), ARRAY_A);
    
    if (empty($exchanges)) {
        return array();
    }
    
    $enriched_exchanges = array();
    
    foreach ($exchanges as $exchange) {
        // Get counterpart info
        $counterpart_id = ($exchange['owner_user_id'] == $user_id) ? $exchange['offerer_user_id'] : $exchange['owner_user_id'];
        $counterpart = get_userdata($counterpart_id);
        if (!$counterpart) {
            continue; // Skip if user deleted
        }
        
        $exchange['counterpart_id'] = $counterpart_id;
        $exchange['counterpart_name'] = $counterpart->display_name;
        
        // Get listing info
        $listing = get_post($exchange['listing_id']);
        if (!$listing) {
            continue; // Skip if listing deleted
        }
        
        $exchange['listing_title'] = $listing->post_title;
        
        // Get unread count (only if function exists)
        if (function_exists('hdh_get_unread_count')) {
            $exchange['unread_count'] = hdh_get_unread_count($exchange['id'], $user_id);
        } else {
            $exchange['unread_count'] = 0;
        }
        
        $enriched_exchanges[] = $exchange;
    }
    
    return $enriched_exchanges;
}

/**
 * Complete gift exchange
 */
function hdh_complete_gift_exchange($exchange_id, $user_id) {
    // Ensure tables exist before querying
    hdh_ensure_gift_tables_exist();
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_gift_exchanges';
    
    // Check for database errors
    if ($wpdb->last_error) {
        return new WP_Error('db_error', 'Veritabanı hatası');
    }
    
    $exchange = hdh_get_gift_exchange($exchange_id, $user_id);
    if (!$exchange) {
        return new WP_Error('exchange_not_found', 'Hediyeleşme bulunamadı');
    }
    
    // Check authorization
    if ($exchange['owner_user_id'] != $user_id && $exchange['offerer_user_id'] != $user_id) {
        return new WP_Error('unauthorized', 'Bu işlemi yapmaya yetkiniz yok');
    }
    
    // Determine which field to update
    $field = ($exchange['owner_user_id'] == $user_id) ? 'completed_owner_at' : 'completed_offerer_at';
    
    // Check if already marked
    if ($exchange[$field]) {
        return new WP_Error('already_marked', 'Zaten tamamlanmış');
    }
    
    // Update field
    $result = $wpdb->update(
        $table_name,
        array($field => current_time('mysql')),
        array('id' => $exchange_id),
        array('%s'),
        array('%d')
    );
    
    if ($result === false || $wpdb->last_error) {
        return new WP_Error('db_error', 'Veritabanı hatası: ' . ($wpdb->last_error ?: 'Bilinmeyen hata'));
    }
    
    // Check if both sides completed
    $updated_exchange = hdh_get_gift_exchange($exchange_id, $user_id);
    if ($updated_exchange['completed_owner_at'] && $updated_exchange['completed_offerer_at']) {
        // Mark as completed
        $update_result = $wpdb->update(
            $table_name,
            array('status' => 'COMPLETED'),
            array('id' => $exchange_id),
            array('%s'),
            array('%d')
        );
        
        // Check for errors but don't fail if update fails
        if ($update_result === false && $wpdb->last_error) {
            // Log error but continue
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('HDH Gift Exchange: Failed to update status to COMPLETED: ' . $wpdb->last_error);
            }
        }
        
        // Increment gift counts
        if (function_exists('hdh_increment_completed_gift_count')) {
            hdh_increment_completed_gift_count($exchange['owner_user_id']);
            hdh_increment_completed_gift_count($exchange['offerer_user_id']);
        }
    }
    
    return hdh_get_gift_exchange($exchange_id, $user_id);
}

/**
 * Report gift exchange
 */
function hdh_report_gift_exchange($exchange_id, $user_id, $reason = '') {
    // Ensure tables exist before querying
    hdh_ensure_gift_tables_exist();
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_gift_exchanges';
    
    // Check for database errors
    if ($wpdb->last_error) {
        return new WP_Error('db_error', 'Veritabanı hatası');
    }
    
    $exchange = hdh_get_gift_exchange($exchange_id, $user_id);
    if (!$exchange) {
        return new WP_Error('exchange_not_found', 'Hediyeleşme bulunamadı');
    }
    
    // Check authorization
    if ($exchange['owner_user_id'] != $user_id && $exchange['offerer_user_id'] != $user_id) {
        return new WP_Error('unauthorized', 'Bu işlemi yapmaya yetkiniz yok');
    }
    
    // Check if already reported
    if ($exchange['reported_at']) {
        return new WP_Error('already_reported', 'Zaten şikayet edilmiş');
    }
    
    $result = $wpdb->update(
        $table_name,
        array(
            'reported_at' => current_time('mysql'),
            'status' => 'DISPUTED',
        ),
        array('id' => $exchange_id),
        array('%s', '%s'),
        array('%d')
    );
    
    if ($result === false || $wpdb->last_error) {
        return new WP_Error('db_error', 'Veritabanı hatası: ' . ($wpdb->last_error ?: 'Bilinmeyen hata'));
    }
    
    return hdh_get_gift_exchange($exchange_id, $user_id);
}

/**
 * ============================================
 * MESSAGE FUNCTIONS
 * ============================================
 */

/**
 * Send gift message
 */
function hdh_send_gift_message($exchange_id, $user_id, $message, $is_system = false) {
    // Ensure tables exist before querying
    hdh_ensure_gift_tables_exist();
    
    global $wpdb;
    $messages_table = $wpdb->prefix . 'hdh_gift_messages';
    $exchanges_table = $wpdb->prefix . 'hdh_gift_exchanges';
    
    // Check for database errors
    if ($wpdb->last_error) {
        return new WP_Error('db_error', 'Veritabanı hatası');
    }
    
    // Verify exchange exists and user has access
    $exchange = hdh_get_gift_exchange($exchange_id, $user_id);
    if (!$exchange) {
        return new WP_Error('exchange_not_found', 'Hediyeleşme bulunamadı');
    }
    
    // Check if exchange is locked (completed or disputed)
    if ($exchange['status'] !== 'ACTIVE') {
        return new WP_Error('exchange_locked', 'Bu hediyeleşme tamamlanmış veya şikayet edilmiş');
    }
    
    // Validate message
    $message = trim($message);
    if (empty($message)) {
        return new WP_Error('empty_message', 'Mesaj boş olamaz');
    }
    
    if (mb_strlen($message) > 1000) {
        return new WP_Error('message_too_long', 'Mesaj en fazla 1000 karakter olabilir');
    }
    
    // Sanitize message
    $sanitized_message = wp_kses_post($message);
    
    // Insert message
    $result = $wpdb->insert(
        $messages_table,
        array(
            'gift_exchange_id' => $exchange_id,
            'user_id' => $user_id,
            'message' => $sanitized_message,
            'is_system_message' => $is_system ? 1 : 0,
            'is_read' => 0,
            'created_at' => current_time('mysql'),
        ),
        array('%d', '%d', '%s', '%d', '%d', '%s')
    );
    
    if ($result === false || $wpdb->last_error) {
        return new WP_Error('db_error', 'Veritabanı hatası: ' . ($wpdb->last_error ?: 'Bilinmeyen hata'));
    }
    
    $message_id = $wpdb->insert_id;
    
    // Update exchange updated_at (non-critical, so don't fail if it errors)
    $update_result = $wpdb->update(
        $exchanges_table,
        array('updated_at' => current_time('mysql')),
        array('id' => $exchange_id),
        array('%s'),
        array('%d')
    );
    
    // Log error but don't fail
    if ($update_result === false && $wpdb->last_error) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('HDH Gift Exchange: Failed to update updated_at: ' . $wpdb->last_error);
        }
    }
    
    return $message_id;
}

/**
 * Get gift messages
 */
function hdh_get_gift_messages($exchange_id, $user_id, $limit = 100) {
    // Ensure tables exist before querying
    hdh_ensure_gift_tables_exist();
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_gift_messages';
    
    // Check for database errors
    if ($wpdb->last_error) {
        return array();
    }
    
    // Verify user has access
    $exchange = hdh_get_gift_exchange($exchange_id, $user_id);
    if (!$exchange) {
        return array();
    }
    
    $messages = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name 
         WHERE gift_exchange_id = %d 
         ORDER BY created_at ASC 
         LIMIT %d",
        $exchange_id,
        $limit
    ), ARRAY_A);
    
    // Check for database errors
    if ($wpdb->last_error) {
        return array(); // Return empty array on error
    }
    
    if (!is_array($messages)) {
        return array(); // Ensure we return an array
    }
    
    // Enrich with user data
    foreach ($messages as &$message) {
        $message_user = get_userdata($message['user_id']);
        if ($message_user) {
            $message['user_name'] = $message_user->display_name;
        }
        
        // Determine side (left/right) based on current user
        if ($message['user_id'] == $user_id) {
            $message['side'] = 'right';
        } else {
            $message['side'] = 'left';
        }
        
        // System messages are centered
        if ($message['is_system_message']) {
            $message['side'] = 'system';
        }
    }
    
    return $messages;
}

/**
 * Mark messages as read
 */
function hdh_mark_messages_read($exchange_id, $user_id) {
    // Ensure tables exist before querying
    hdh_ensure_gift_tables_exist();
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_gift_messages';
    
    // Check for database errors
    if ($wpdb->last_error) {
        return false;
    }
    
    // Verify user has access
    $exchange = hdh_get_gift_exchange($exchange_id, $user_id);
    if (!$exchange) {
        return false;
    }
    
    // Mark all messages from counterpart as read
    $counterpart_id = ($exchange['owner_user_id'] == $user_id) ? $exchange['offerer_user_id'] : $exchange['owner_user_id'];
    
    $result = $wpdb->update(
        $table_name,
        array('is_read' => 1),
        array(
            'gift_exchange_id' => $exchange_id,
            'user_id' => $counterpart_id,
            'is_read' => 0,
        ),
        array('%d'),
        array('%d', '%d', '%d')
    );
    
    // Check for errors but return true if no error (even if no rows updated)
    if ($wpdb->last_error) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('HDH Gift Exchange: Failed to mark messages as read: ' . $wpdb->last_error);
        }
        return false;
    }
    
    return true; // Success (even if no rows were updated)
}

/**
 * Get unread count for an exchange
 */
function hdh_get_unread_count($exchange_id, $user_id) {
    // Ensure tables exist before querying
    hdh_ensure_gift_tables_exist();
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_gift_messages';
    
    // Check for database errors
    if ($wpdb->last_error) {
        return 0;
    }
    
    // Verify user has access
    $exchange = hdh_get_gift_exchange($exchange_id, $user_id);
    if (!$exchange) {
        return 0;
    }
    
    // Get counterpart ID
    $counterpart_id = ($exchange['owner_user_id'] == $user_id) ? $exchange['offerer_user_id'] : $exchange['owner_user_id'];
    
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name
         WHERE gift_exchange_id = %d
         AND user_id = %d
         AND is_read = 0
         AND is_system_message = 0",
        $exchange_id,
        $counterpart_id
    ));
    
    // Check for database errors
    if ($wpdb->last_error) {
        return 0; // Return 0 on error
    }
    
    return (int) $count;
}

/**
 * Get total unread count across all exchanges
 */
function hdh_get_total_unread_count($user_id) {
    if (!$user_id) {
        return 0;
    }
    
    $exchanges = hdh_get_user_gift_exchanges($user_id);
    $total = 0;
    
    foreach ($exchanges as $exchange) {
        $total += $exchange['unread_count'];
    }
    
    return $total;
}

/**
 * Note: hdh_increment_completed_gift_count and hdh_get_completed_gift_count
 * are already defined in inc/trade-session.php and inc/trade-offers.php
 * We don't redefine them here to avoid duplicate function errors.
 */

