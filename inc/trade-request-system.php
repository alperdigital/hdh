<?php
/**
 * HDH: Trade Request System
 * Manages trade requests with 120-second accept window
 */

if (!defined('ABSPATH')) exit;

/**
 * ============================================
 * DATABASE TABLE CREATION
 * ============================================
 */

/**
 * Create trade request table
 */
function hdh_create_trade_request_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'hdh_trade_requests';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        listing_id bigint(20) unsigned NOT NULL,
        requester_user_id bigint(20) unsigned NOT NULL,
        owner_user_id bigint(20) unsigned NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'pending',
        requested_at datetime NOT NULL,
        expires_at datetime NOT NULL,
        accepted_at datetime DEFAULT NULL,
        rejected_at datetime DEFAULT NULL,
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY (id),
        KEY listing_id (listing_id),
        KEY requester_user_id (requester_user_id),
        KEY owner_user_id (owner_user_id),
        KEY status (status),
        KEY expires_at (expires_at)
    ) {$charset_collate};";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Initialize trade request table on theme activation
 */
add_action('after_switch_theme', 'hdh_create_trade_request_table');

/**
 * Also create on admin init (for existing sites)
 */
add_action('admin_init', function() {
    if (current_user_can('manage_options')) {
        hdh_create_trade_request_table();
    }
}, 1);

/**
 * ============================================
 * TRADE REQUEST FUNCTIONS
 * ============================================
 */

/**
 * Create trade request
 * 
 * @param int $listing_id Listing ID
 * @param int $requester_user_id Requester user ID
 * @return array|WP_Error Request data or error
 */
function hdh_create_trade_request($listing_id, $requester_user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_requests';
    
    // Validate listing
    $listing = get_post($listing_id);
    if (!$listing || $listing->post_type !== 'hayday_trade') {
        return new WP_Error('invalid_listing', 'Geçersiz ilan');
    }
    
    $owner_user_id = $listing->post_author;
    
    // Check if user is owner
    if ($owner_user_id == $requester_user_id) {
        return new WP_Error('cannot_request_own', 'Kendi ilanınız için teklif gönderemezsiniz');
    }
    
    // Check if listing is open
    $trade_status = get_post_meta($listing_id, '_hdh_trade_status', true);
    if ($trade_status !== 'open') {
        return new WP_Error('listing_not_open', 'Bu ilan açık değil');
    }
    
    // Check rate limiting
    $rate_limit_check = hdh_check_trade_request_rate_limit($listing_id, $requester_user_id, $owner_user_id);
    if (is_wp_error($rate_limit_check)) {
        return $rate_limit_check;
    }
    
    // Check if there's already a pending request from this user for this listing
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table_name} 
         WHERE listing_id = %d 
         AND requester_user_id = %d 
         AND status = 'pending'",
        $listing_id,
        $requester_user_id
    ), ARRAY_A);
    
    if ($existing) {
        return new WP_Error('request_exists', 'Bu ilan için zaten bekleyen bir teklifiniz var');
    }
    
    // Auto-reject older pending requests from same user for same listing
    $wpdb->update(
        $table_name,
        array(
            'status' => 'rejected',
            'rejected_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ),
        array(
            'listing_id' => $listing_id,
            'requester_user_id' => $requester_user_id,
            'status' => 'pending',
        ),
        array('%s', '%s', '%s'),
        array('%d', '%d', '%s')
    );
    
    // Create new request
    $now = current_time('mysql');
    $expires_at = date('Y-m-d H:i:s', current_time('timestamp') + 120); // 120 seconds
    
    $result = $wpdb->insert(
        $table_name,
        array(
            'listing_id' => $listing_id,
            'requester_user_id' => $requester_user_id,
            'owner_user_id' => $owner_user_id,
            'status' => 'pending',
            'requested_at' => $now,
            'expires_at' => $expires_at,
            'created_at' => $now,
            'updated_at' => $now,
        ),
        array('%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s')
    );
    
    if ($result === false) {
        return new WP_Error('db_error', 'Veritabanı hatası');
    }
    
    $request_id = $wpdb->insert_id;
    
    // Log event
    if (function_exists('hdh_log_event')) {
        hdh_log_event($requester_user_id, 'trade_request_created', array(
            'request_id' => $request_id,
            'listing_id' => $listing_id,
            'owner_user_id' => $owner_user_id,
        ));
    }
    
    // Create notification for owner
    if (function_exists('hdh_create_notification')) {
        $requester_name = get_user_meta($requester_user_id, 'display_name', true) ?: get_userdata($requester_user_id)->display_name;
        hdh_create_notification(
            $owner_user_id,
            'trade_request',
            'Yeni Teklif',
            $requester_name . ' size teklif gönderdi',
            get_permalink($listing_id)
        );
    }
    
    return hdh_get_trade_request($request_id);
}

/**
 * Check trade request rate limiting
 * 
 * @param int $listing_id Listing ID
 * @param int $requester_user_id Requester user ID
 * @param int $owner_user_id Owner user ID
 * @return bool|WP_Error True if allowed, WP_Error if rate limited
 */
function hdh_check_trade_request_rate_limit($listing_id, $requester_user_id, $owner_user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_requests';
    
    // Max 3 requests per listing per user per day
    $today_start = date('Y-m-d 00:00:00', current_time('timestamp'));
    $today_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_name}
         WHERE listing_id = %d
         AND requester_user_id = %d
         AND created_at >= %s",
        $listing_id,
        $requester_user_id,
        $today_start
    ));
    
    if ($today_count >= 3) {
        return new WP_Error('rate_limit_daily', 'Bu ilan için günlük teklif limitine ulaştınız (3 teklif/gün)');
    }
    
    // Cooldown: 10 minutes between requests to same owner
    $cooldown_time = date('Y-m-d H:i:s', current_time('timestamp') - 600); // 10 minutes ago
    $recent_request = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_name}
         WHERE owner_user_id = %d
         AND requester_user_id = %d
         AND created_at >= %s",
        $owner_user_id,
        $requester_user_id,
        $cooldown_time
    ));
    
    if ($recent_request > 0) {
        return new WP_Error('rate_limit_cooldown', 'Aynı kullanıcıya 10 dakika içinde tekrar teklif gönderemezsiniz');
    }
    
    return true;
}

/**
 * Accept trade request
 * 
 * @param int $request_id Request ID
 * @param int $owner_user_id Owner user ID (for verification)
 * @return array|WP_Error Request data or error
 */
function hdh_accept_trade_request($request_id, $owner_user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_requests';
    
    $request = hdh_get_trade_request($request_id);
    if (!$request) {
        return new WP_Error('request_not_found', 'Teklif bulunamadı');
    }
    
    // Verify owner
    if ($request['owner_user_id'] != $owner_user_id) {
        return new WP_Error('unauthorized', 'Bu teklifi kabul etme yetkiniz yok');
    }
    
    // Check if expired
    if ($request['status'] === 'expired' || strtotime($request['expires_at']) < current_time('timestamp')) {
        // Mark as expired if not already
        if ($request['status'] !== 'expired') {
            $wpdb->update(
                $table_name,
                array(
                    'status' => 'expired',
                    'updated_at' => current_time('mysql'),
                ),
                array('id' => $request_id),
                array('%s', '%s'),
                array('%d')
            );
        }
        return new WP_Error('request_expired', 'Teklif süresi dolmuş');
    }
    
    // Check if already accepted/rejected
    if ($request['status'] !== 'pending') {
        return new WP_Error('request_already_processed', 'Bu teklif zaten işlenmiş');
    }
    
    // Accept request
    $result = $wpdb->update(
        $table_name,
        array(
            'status' => 'accepted',
            'accepted_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ),
        array('id' => $request_id),
        array('%s', '%s', '%s'),
        array('%d')
    );
    
    if ($result === false) {
        return new WP_Error('db_error', 'Veritabanı hatası');
    }
    
    // Reject other pending requests for same listing
    $wpdb->query($wpdb->prepare(
        "UPDATE {$table_name}
         SET status = 'rejected', rejected_at = %s, updated_at = %s
         WHERE listing_id = %d
         AND status = 'pending'
         AND id != %d",
        current_time('mysql'),
        current_time('mysql'),
        $request['listing_id'],
        $request_id
    ));
    
    // Log event
    if (function_exists('hdh_log_event')) {
        hdh_log_event($owner_user_id, 'trade_request_accepted', array(
            'request_id' => $request_id,
            'listing_id' => $request['listing_id'],
            'requester_user_id' => $request['requester_user_id'],
        ));
    }
    
    // Create notification for requester
    if (function_exists('hdh_create_notification')) {
        $owner_name = get_user_meta($owner_user_id, 'display_name', true) ?: get_userdata($owner_user_id)->display_name;
        hdh_create_notification(
            $request['requester_user_id'],
            'trade_accepted',
            'Teklif Kabul Edildi',
            $owner_name . ' teklifinizi kabul etti',
            get_permalink($request['listing_id'])
        );
    }
    
    return hdh_get_trade_request($request_id);
}

/**
 * Reject trade request
 * 
 * @param int $request_id Request ID
 * @param int $owner_user_id Owner user ID (for verification)
 * @return array|WP_Error Request data or error
 */
function hdh_reject_trade_request($request_id, $owner_user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_requests';
    
    $request = hdh_get_trade_request($request_id);
    if (!$request) {
        return new WP_Error('request_not_found', 'Teklif bulunamadı');
    }
    
    // Verify owner
    if ($request['owner_user_id'] != $owner_user_id) {
        return new WP_Error('unauthorized', 'Bu teklifi reddetme yetkiniz yok');
    }
    
    // Check if already processed
    if ($request['status'] !== 'pending') {
        return new WP_Error('request_already_processed', 'Bu teklif zaten işlenmiş');
    }
    
    // Reject request
    $result = $wpdb->update(
        $table_name,
        array(
            'status' => 'rejected',
            'rejected_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ),
        array('id' => $request_id),
        array('%s', '%s', '%s'),
        array('%d')
    );
    
    if ($result === false) {
        return new WP_Error('db_error', 'Veritabanı hatası');
    }
    
    // Log event
    if (function_exists('hdh_log_event')) {
        hdh_log_event($owner_user_id, 'trade_request_rejected', array(
            'request_id' => $request_id,
            'listing_id' => $request['listing_id'],
            'requester_user_id' => $request['requester_user_id'],
        ));
    }
    
    // Create notification for requester
    if (function_exists('hdh_create_notification')) {
        $owner_name = get_user_meta($owner_user_id, 'display_name', true) ?: get_userdata($owner_user_id)->display_name;
        hdh_create_notification(
            $request['requester_user_id'],
            'trade_rejected',
            'Teklif Reddedildi',
            $owner_name . ' teklifinizi reddetti',
            get_permalink($request['listing_id'])
        );
    }
    
    return hdh_get_trade_request($request_id);
}

/**
 * Get trade request
 * 
 * @param int $request_id Request ID
 * @return array|false Request data or false
 */
function hdh_get_trade_request($request_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_requests';
    
    $request = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table_name} WHERE id = %d",
        $request_id
    ), ARRAY_A);
    
    if (!$request) {
        return false;
    }
    
    // Check if expired
    if ($request['status'] === 'pending' && strtotime($request['expires_at']) < current_time('timestamp')) {
        // Auto-expire
        $wpdb->update(
            $table_name,
            array(
                'status' => 'expired',
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $request_id),
            array('%s', '%s'),
            array('%d')
        );
        $request['status'] = 'expired';
        
        // Create notification for requester
        if (function_exists('hdh_create_notification')) {
            hdh_create_notification(
                $request['requester_user_id'],
                'trade_expired',
                'Teklif Süresi Doldu',
                'Teklifiniz süresi doldu',
                get_permalink($request['listing_id'])
            );
        }
    }
    
    return $request;
}

/**
 * Get pending requests for owner
 * 
 * @param int $owner_user_id Owner user ID
 * @return array Array of request data
 */
function hdh_get_pending_requests_for_owner($owner_user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_requests';
    
    $requests = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table_name}
         WHERE owner_user_id = %d
         AND status = 'pending'
         AND expires_at > %s
         ORDER BY requested_at DESC",
        $owner_user_id,
        current_time('mysql')
    ), ARRAY_A);
    
    return $requests;
}

/**
 * Get trade request for listing and user
 * 
 * @param int $listing_id Listing ID
 * @param int $user_id User ID (requester)
 * @return array|false Request data or false
 */
function hdh_get_trade_request_for_listing($listing_id, $user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_requests';
    
    $request = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table_name}
         WHERE listing_id = %d
         AND requester_user_id = %d
         ORDER BY created_at DESC
         LIMIT 1",
        $listing_id,
        $user_id
    ), ARRAY_A);
    
    if ($request) {
        return hdh_get_trade_request($request['id']); // This will auto-expire if needed
    }
    
    return false;
}

/**
 * Expire old requests (cron job)
 */
function hdh_expire_old_requests() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_requests';
    
    $expired = $wpdb->query(
        "UPDATE {$table_name}
         SET status = 'expired', updated_at = NOW()
         WHERE status = 'pending'
         AND expires_at < NOW()"
    );
    
    return $expired;
}

// Schedule cron job to expire old requests (every 5 minutes)
if (!wp_next_scheduled('hdh_expire_trade_requests')) {
    wp_schedule_event(time(), 'hdh_5min', 'hdh_expire_trade_requests');
}

// Add custom cron interval
add_filter('cron_schedules', function($schedules) {
    $schedules['hdh_5min'] = array(
        'interval' => 300, // 5 minutes
        'display' => 'Her 5 dakika'
    );
    return $schedules;
});

// Hook cron job
add_action('hdh_expire_trade_requests', 'hdh_expire_old_requests');

