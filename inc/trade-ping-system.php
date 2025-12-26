<?php
/**
 * HDH: Trade Ping System
 * Ping/check-in system for trade sessions
 */

if (!defined('ABSPATH')) exit;

/**
 * ============================================
 * DATABASE TABLE CREATION
 * ============================================
 */

/**
 * Create trade pings table
 */
function hdh_create_trade_pings_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'hdh_trade_pings';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        trade_session_id bigint(20) unsigned NOT NULL,
        from_user_id bigint(20) unsigned NOT NULL,
        to_user_id bigint(20) unsigned NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'pending',
        response varchar(50) DEFAULT NULL,
        created_at datetime NOT NULL,
        responded_at datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY trade_session_id (trade_session_id),
        KEY from_user_id (from_user_id),
        KEY to_user_id (to_user_id),
        KEY status (status),
        KEY created_at (created_at)
    ) {$charset_collate};";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Initialize trade pings table on theme activation
 * DISABLED - System temporarily disabled
 */
// add_action('after_switch_theme', 'hdh_create_trade_pings_table');

/**
 * Also create on admin init (for existing sites)
 * DISABLED - System temporarily disabled
 */
// add_action('admin_init', function() {
//     if (current_user_can('manage_options')) {
//         hdh_create_trade_pings_table();
//     }
// }, 1);

/**
 * ============================================
 * PING FUNCTIONS
 * ============================================
 */

/**
 * Send trade ping
 * 
 * @param int $trade_session_id Trade session ID
 * @param int $from_user_id User ID sending ping
 * @return int|WP_Error Ping ID or error
 */
function hdh_send_trade_ping($trade_session_id, $from_user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_pings';
    
    // Get trade session
    if (!function_exists('hdh_get_trade_session')) {
        return new WP_Error('function_not_found', 'Trade session system not available');
    }
    
    $session = hdh_get_trade_session($trade_session_id);
    if (!$session) {
        return new WP_Error('session_not_found', 'Hediyeleşme oturumu bulunamadı');
    }
    
    // Determine to_user_id
    $to_user_id = null;
    if ($session['owner_user_id'] == $from_user_id) {
        $to_user_id = $session['starter_user_id'];
    } elseif ($session['starter_user_id'] == $from_user_id) {
        $to_user_id = $session['owner_user_id'];
    } else {
        return new WP_Error('invalid_user', 'Bu hediyeleşmede yer almıyorsunuz');
    }
    
    // Rate limiting: max 1 ping per 10 minutes per trade
    $ten_minutes_ago = date('Y-m-d H:i:s', current_time('timestamp') - 600);
    $recent_ping = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$table_name}
         WHERE trade_session_id = %d
         AND from_user_id = %d
         AND created_at >= %s
         LIMIT 1",
        $trade_session_id,
        $from_user_id,
        $ten_minutes_ago
    ));
    
    if ($recent_ping) {
        return new WP_Error('rate_limit', '10 dakika içinde zaten bir ping gönderdiniz');
    }
    
    // Rate limiting: max 5 pings per day per user
    $today_start = date('Y-m-d 00:00:00', current_time('timestamp'));
    $today_pings = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_name}
         WHERE from_user_id = %d
         AND created_at >= %s",
        $from_user_id,
        $today_start
    ));
    
    if ($today_pings >= 5) {
        return new WP_Error('daily_limit', 'Günlük ping limitine ulaştınız (5 ping/gün)');
    }
    
    // Create ping
    $result = $wpdb->insert(
        $table_name,
        array(
            'trade_session_id' => $trade_session_id,
            'from_user_id' => $from_user_id,
            'to_user_id' => $to_user_id,
            'status' => 'pending',
            'created_at' => current_time('mysql'),
        ),
        array('%d', '%d', '%d', '%s', '%s')
    );
    
    if ($result === false) {
        return new WP_Error('db_error', 'Veritabanı hatası');
    }
    
    $ping_id = $wpdb->insert_id;
    
    // Create notification for recipient
    if (function_exists('hdh_create_notification')) {
        $from_user = get_userdata($from_user_id);
        $from_name = $from_user ? $from_user->display_name : 'Bir kullanıcı';
        $listing = get_post($session['listing_id']);
        $listing_title = $listing ? $listing->post_title : 'Hediyeleşme';
        
        hdh_create_notification(
            $to_user_id,
            'trade_ping',
            'Ping / Kontrol İsteği',
            sprintf('%s, "%s" hediyeleşmesi için bir ping gönderdi.', $from_name, $listing_title),
            home_url('/?ping_id=' . $ping_id)
        );
    }
    
    return $ping_id;
}

/**
 * Respond to ping
 * 
 * @param int $ping_id Ping ID
 * @param int $to_user_id User ID responding (for verification)
 * @param string $response Response type (here/10min_later/not_available_today)
 * @return bool|WP_Error Success or error
 */
function hdh_respond_to_ping($ping_id, $to_user_id, $response) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_pings';
    
    // Get ping
    $ping = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table_name} WHERE id = %d",
        $ping_id
    ), ARRAY_A);
    
    if (!$ping) {
        return new WP_Error('ping_not_found', 'Ping bulunamadı');
    }
    
    // Verify user
    if ((int) $ping['to_user_id'] !== $to_user_id) {
        return new WP_Error('unauthorized', 'Bu ping size ait değil');
    }
    
    // Validate response
    $valid_responses = array('here', '10min_later', 'not_available_today');
    if (!in_array($response, $valid_responses)) {
        return new WP_Error('invalid_response', 'Geçersiz yanıt');
    }
    
    // Update ping
    $result = $wpdb->update(
        $table_name,
        array(
            'status' => 'responded',
            'response' => $response,
            'responded_at' => current_time('mysql'),
        ),
        array('id' => $ping_id),
        array('%s', '%s', '%s'),
        array('%d')
    );
    
    if ($result === false) {
        return new WP_Error('db_error', 'Veritabanı hatası');
    }
    
    // Create notification for sender
    if (function_exists('hdh_create_notification')) {
        $to_user = get_userdata($to_user_id);
        $to_name = $to_user ? $to_user->display_name : 'Kullanıcı';
        
        $response_texts = array(
            'here' => 'Buradayım',
            '10min_later' => '10 dakika sonra',
            'not_available_today' => 'Bugün müsait değilim',
        );
        
        $response_text = $response_texts[$response] ?? $response;
        
        hdh_create_notification(
            $ping['from_user_id'],
            'ping_response',
            'Ping Yanıtı',
            sprintf('%s: %s', $to_name, $response_text),
            home_url('/?ping_id=' . $ping_id)
        );
    }
    
    return true;
}

/**
 * Get pending pings for user
 * 
 * @param int $user_id User ID
 * @return array Array of pending pings
 */
function hdh_get_pending_pings($user_id) {
    if (!$user_id) {
        return array();
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_pings';
    
    $pings = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table_name}
         WHERE to_user_id = %d
         AND status = 'pending'
         ORDER BY created_at DESC",
        $user_id
    ), ARRAY_A);
    
    if (!$pings) {
        return array();
    }
    
    // Enrich with user and session data
    foreach ($pings as &$ping) {
        $from_user = get_userdata($ping['from_user_id']);
        $ping['from_user_name'] = $from_user ? $from_user->display_name : 'Bilinmeyen';
        
        if (function_exists('hdh_get_trade_session')) {
            $session = hdh_get_trade_session($ping['trade_session_id']);
            if ($session) {
                $listing = get_post($session['listing_id']);
                $ping['listing_title'] = $listing ? $listing->post_title : 'İlan';
            }
        }
    }
    
    return $pings;
}

/**
 * Get ping by ID
 * 
 * @param int $ping_id Ping ID
 * @return array|null Ping data or null
 */
function hdh_get_ping($ping_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_pings';
    
    $ping = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table_name} WHERE id = %d",
        $ping_id
    ), ARRAY_A);
    
    return $ping;
}



