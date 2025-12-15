<?php
/**
 * HDH: Event Logging & Audit System
 * Track all user actions, rewards, and state changes
 */

if (!defined('ABSPATH')) exit;

/**
 * ============================================
 * EVENT LOGGING
 * ============================================
 */

/**
 * Log an event
 * 
 * @param int $user_id User ID
 * @param string $event_type Event type
 * @param array $data Event data
 * @param string $ip_address Optional IP address
 * @return int|false Event ID or false on failure
 */
function hdh_log_event($user_id, $event_type, $data = array(), $ip_address = null) {
    global $wpdb;
    
    // Get IP address if not provided
    if ($ip_address === null) {
        $ip_address = hdh_get_client_ip();
    }
    
    // Hash IP address before storage (KVKK compliance)
    $ip_hash = hdh_hash_ip($ip_address);
    
    // Prepare event data
    $event = array(
        'user_id' => (int) $user_id,
        'event_type' => sanitize_key($event_type),
        'event_data' => wp_json_encode($data),
        'ip_address' => $ip_hash,
        'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : '',
        'created_at' => current_time('mysql'),
    );
    
    // Insert into events table
    $table_name = $wpdb->prefix . 'hdh_events';
    $inserted = $wpdb->insert($table_name, $event);
    
    if ($inserted) {
        $event_id = $wpdb->insert_id;
        
        // Also log to user meta (recent events cache)
        hdh_cache_user_event($user_id, $event_type, $data, $event_id);
        
        // Trigger action hook for other systems
        do_action('hdh_event_logged', $event_id, $user_id, $event_type, $data);
        
        return $event_id;
    }
    
    return false;
}

/**
 * Cache recent event in user meta (for quick access)
 */
function hdh_cache_user_event($user_id, $event_type, $data, $event_id) {
    $recent_events = get_user_meta($user_id, 'hdh_recent_events', true);
    if (!is_array($recent_events)) {
        $recent_events = array();
    }
    
    // Add new event to beginning
    array_unshift($recent_events, array(
        'event_id' => $event_id,
        'event_type' => $event_type,
        'event_data' => $data,
        'timestamp' => current_time('mysql'),
    ));
    
    // Keep only last 50 events in cache
    if (count($recent_events) > 50) {
        $recent_events = array_slice($recent_events, 0, 50);
    }
    
    update_user_meta($user_id, 'hdh_recent_events', $recent_events);
}

/**
 * Get user events
 * 
 * @param int $user_id User ID
 * @param array $args Query arguments
 * @return array Events
 */
function hdh_get_user_events($user_id, $args = array()) {
    global $wpdb;
    
    $defaults = array(
        'event_type' => null,
        'limit' => 50,
        'offset' => 0,
        'order' => 'DESC',
        'date_from' => null,
        'date_to' => null,
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $table_name = $wpdb->prefix . 'hdh_events';
    
    $where = array('user_id = %d');
    $where_values = array($user_id);
    
    if ($args['event_type']) {
        $where[] = 'event_type = %s';
        $where_values[] = $args['event_type'];
    }
    
    if ($args['date_from']) {
        $where[] = 'created_at >= %s';
        $where_values[] = $args['date_from'];
    }
    
    if ($args['date_to']) {
        $where[] = 'created_at <= %s';
        $where_values[] = $args['date_to'];
    }
    
    $where_clause = implode(' AND ', $where);
    
    $query = $wpdb->prepare(
        "SELECT * FROM {$table_name} 
        WHERE {$where_clause} 
        ORDER BY created_at {$args['order']} 
        LIMIT %d OFFSET %d",
        array_merge($where_values, array($args['limit'], $args['offset']))
    );
    
    $events = $wpdb->get_results($query, ARRAY_A);
    
    // Decode JSON data
    foreach ($events as &$event) {
        $event['event_data'] = json_decode($event['event_data'], true);
    }
    
    return $events;
}

/**
 * Get event statistics
 * 
 * @param int $user_id User ID
 * @param string $event_type Optional event type filter
 * @param int $days Number of days to look back
 * @return array Statistics
 */
function hdh_get_event_stats($user_id, $event_type = null, $days = 30) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'hdh_events';
    $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    
    $where = array('user_id = %d', 'created_at >= %s');
    $where_values = array($user_id, $date_from);
    
    if ($event_type) {
        $where[] = 'event_type = %s';
        $where_values[] = $event_type;
    }
    
    $where_clause = implode(' AND ', $where);
    
    $query = $wpdb->prepare(
        "SELECT 
            event_type,
            COUNT(*) as count,
            MIN(created_at) as first_occurrence,
            MAX(created_at) as last_occurrence
        FROM {$table_name}
        WHERE {$where_clause}
        GROUP BY event_type
        ORDER BY count DESC",
        $where_values
    );
    
    return $wpdb->get_results($query, ARRAY_A);
}

/**
 * ============================================
 * REWARD EVENT TRACKING
 * ============================================
 */

/**
 * Track reward event (bilet, XP, etc.)
 * 
 * @param int $user_id User ID
 * @param string $reward_type Type (bilet, xp, badge)
 * @param mixed $amount Amount or identifier
 * @param string $reason Reason/action
 * @param array $metadata Additional data
 */
function hdh_track_reward($user_id, $reward_type, $amount, $reason, $metadata = array()) {
    $event_data = array(
        'reward_type' => $reward_type,
        'amount' => $amount,
        'reason' => $reason,
        'metadata' => $metadata,
    );
    
    hdh_log_event($user_id, 'reward_earned', $event_data);
    
    // Update reward totals
    $total_key = 'hdh_total_' . $reward_type;
    $current_total = (int) get_user_meta($user_id, $total_key, true);
    update_user_meta($user_id, $total_key, $current_total + (is_numeric($amount) ? $amount : 1));
}

/**
 * Get reward history
 * 
 * @param int $user_id User ID
 * @param string $reward_type Optional filter
 * @param int $limit Limit
 * @return array Rewards
 */
function hdh_get_reward_history($user_id, $reward_type = null, $limit = 50) {
    $events = hdh_get_user_events($user_id, array(
        'event_type' => 'reward_earned',
        'limit' => $limit,
    ));
    
    if ($reward_type) {
        $events = array_filter($events, function($event) use ($reward_type) {
            return isset($event['event_data']['reward_type']) 
                && $event['event_data']['reward_type'] === $reward_type;
        });
    }
    
    return array_values($events);
}

/**
 * ============================================
 * ACTION TRACKING
 * ============================================
 */

/**
 * Track user action
 * 
 * @param int $user_id User ID
 * @param string $action Action name
 * @param array $context Action context
 */
function hdh_track_action($user_id, $action, $context = array()) {
    $event_data = array(
        'action' => $action,
        'context' => $context,
    );
    
    hdh_log_event($user_id, 'user_action', $event_data);
    
    // Update last active timestamp
    update_user_meta($user_id, 'hdh_last_active', current_time('mysql'));
    
    // Track specific action counts
    $action_count_key = 'hdh_action_count_' . sanitize_key($action);
    $current_count = (int) get_user_meta($user_id, $action_count_key, true);
    update_user_meta($user_id, $action_count_key, $current_count + 1);
}

/**
 * ============================================
 * AUDIT TRAIL
 * ============================================
 */

/**
 * Get audit trail for user
 * 
 * @param int $user_id User ID
 * @param int $days Days to look back
 * @return array Audit entries
 */
function hdh_get_audit_trail($user_id, $days = 30) {
    $events = hdh_get_user_events($user_id, array(
        'limit' => 100,
        'date_from' => date('Y-m-d H:i:s', strtotime("-{$days} days")),
    ));
    
    // Format for audit display
    $audit_trail = array();
    foreach ($events as $event) {
        $audit_trail[] = array(
            'timestamp' => $event['created_at'],
            'event_type' => $event['event_type'],
            'description' => hdh_format_event_description($event),
            'data' => $event['event_data'],
            'ip_address' => $event['ip_address'],
        );
    }
    
    return $audit_trail;
}

/**
 * Format event description for audit trail
 */
function hdh_format_event_description($event) {
    $type = $event['event_type'];
    $data = $event['event_data'];
    
    $descriptions = array(
        'reward_earned' => sprintf(
            'Ödül kazanıldı: %s %s (%s)',
            $data['amount'] ?? '',
            $data['reward_type'] ?? '',
            $data['reason'] ?? ''
        ),
        'xp_gain' => sprintf(
            '%d XP kazanıldı (%s)',
            $data['amount'] ?? 0,
            $data['reason'] ?? ''
        ),
        'level_up' => sprintf(
            'Seviye atlandı: %d → %d',
            $data['old_level'] ?? 0,
            $data['new_level'] ?? 0
        ),
        'trust_plus' => 'Pozitif güven puanı alındı',
        'trust_minus' => 'Negatif güven puanı alındı',
        'email_verified' => 'E-posta doğrulandı',
        'phone_verified' => 'Telefon doğrulandı',
        'badge_awarded' => sprintf(
            'Rozet kazanıldı: %s',
            $data['badge_id'] ?? ''
        ),
        'user_banned' => sprintf(
            'Kullanıcı banlandı: %s',
            $data['reason'] ?? ''
        ),
        'user_unbanned' => 'Kullanıcı ban kaldırıldı',
        'state_change' => sprintf(
            'Durum değişti: %s (%s → %s)',
            $data['field'] ?? '',
            $data['old_value'] ?? '',
            $data['new_value'] ?? ''
        ),
    );
    
    return $descriptions[$type] ?? ucfirst(str_replace('_', ' ', $type));
}

/**
 * ============================================
 * UTILITY FUNCTIONS
 * ============================================
 */

/**
 * Get client IP address
 */
function hdh_get_client_ip() {
    $ip_keys = array(
        'HTTP_CF_CONNECTING_IP', // Cloudflare
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'REMOTE_ADDR'
    );
    
    foreach ($ip_keys as $key) {
        if (isset($_SERVER[$key]) && filter_var($_SERVER[$key], FILTER_VALIDATE_IP)) {
            return $_SERVER[$key];
        }
    }
    
    return '0.0.0.0';
}

/**
 * Hash IP address for privacy
 */
function hdh_hash_ip($ip) {
    return hash('sha256', $ip . wp_salt());
}

/**
 * ============================================
 * DATABASE TABLE CREATION
 * ============================================
 */

/**
 * Create events table
 */
function hdh_create_events_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'hdh_events';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        event_type varchar(50) NOT NULL,
        event_data longtext,
        ip_address varchar(45),
        user_agent varchar(255),
        created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY event_type (event_type),
        KEY created_at (created_at),
        KEY user_event (user_id, event_type)
    ) {$charset_collate};";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Create table on theme activation
add_action('after_switch_theme', 'hdh_create_events_table');

/**
 * ============================================
 * CLEANUP & MAINTENANCE
 * ============================================
 */

/**
 * Clean up old events (keep last 90 days)
 */
function hdh_cleanup_old_events() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'hdh_events';
    $cutoff_date = date('Y-m-d H:i:s', strtotime('-90 days'));
    
    $deleted = $wpdb->query($wpdb->prepare(
        "DELETE FROM {$table_name} WHERE created_at < %s",
        $cutoff_date
    ));
    
    if ($deleted && WP_DEBUG && WP_DEBUG_LOG) {
        error_log(sprintf('[HDH Events] Cleaned up %d old events', $deleted));
    }
}

// Schedule cleanup (weekly)
if (!wp_next_scheduled('hdh_cleanup_events')) {
    wp_schedule_event(time(), 'weekly', 'hdh_cleanup_events');
}
add_action('hdh_cleanup_events', 'hdh_cleanup_old_events');

