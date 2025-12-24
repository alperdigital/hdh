<?php
/**
 * HDH: Lobby Chat System
 * Public lobby chat with moderation and rate limiting
 */

if (!defined('ABSPATH')) exit;

/**
 * ============================================
 * DATABASE TABLE CREATION
 * ============================================
 */

/**
 * Create chat messages table
 */
function hdh_create_chat_messages_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'hdh_chat_messages';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        message text NOT NULL,
        message_raw text DEFAULT NULL,
        status varchar(20) NOT NULL DEFAULT 'published',
        moderation_flags longtext DEFAULT NULL,
        warning_strikes int NOT NULL DEFAULT 0,
        created_at datetime NOT NULL,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY status (status),
        KEY created_at (created_at)
    ) {$charset_collate};";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Create chat warnings table
 */
function hdh_create_chat_warnings_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'hdh_chat_warnings';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        message_id bigint(20) unsigned DEFAULT NULL,
        warning_type varchar(50) NOT NULL,
        strike_count int NOT NULL DEFAULT 1,
        created_at datetime NOT NULL,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY message_id (message_id),
        KEY warning_type (warning_type),
        KEY created_at (created_at)
    ) {$charset_collate};";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Initialize chat tables on theme activation
 */
add_action('after_switch_theme', function() {
    hdh_create_chat_messages_table();
    hdh_create_chat_warnings_table();
});

/**
 * Also create on admin init (for existing sites)
 */
add_action('admin_init', function() {
    if (current_user_can('manage_options')) {
        hdh_create_chat_messages_table();
        hdh_create_chat_warnings_table();
    }
}, 1);

/**
 * ============================================
 * CHAT MESSAGE FUNCTIONS
 * ============================================
 */

/**
 * Create chat message
 * 
 * @param int $user_id User ID
 * @param string $message Message text
 * @return int|WP_Error Message ID or error
 */
function hdh_create_chat_message($user_id, $message) {
    if (!$user_id) {
        return new WP_Error('invalid_user', 'Geçersiz kullanıcı');
    }
    
    // Check if chat is enabled
    $chat_enabled = get_option('hdh_chat_enabled', true);
    if (!$chat_enabled) {
        return new WP_Error('chat_disabled', 'Chat şu anda kapalı');
    }
    
    // Check if user is banned/muted
    if (function_exists('hdh_is_user_chat_banned') && hdh_is_user_chat_banned($user_id)) {
        return new WP_Error('user_banned', 'Chat kullanımınız yasaklandı');
    }
    
    if (function_exists('hdh_is_user_chat_muted') && hdh_is_user_chat_muted($user_id)) {
        return new WP_Error('user_muted', 'Chat kullanımınız geçici olarak kısıtlandı');
    }
    
    // Check rate limiting
    $rate_limit_check = hdh_check_chat_rate_limit($user_id);
    if (is_wp_error($rate_limit_check)) {
        return $rate_limit_check;
    }
    
    // Check duplicate message
    $duplicate_check = hdh_check_duplicate_message($user_id, $message);
    if (is_wp_error($duplicate_check)) {
        return $duplicate_check;
    }
    
    // Validate message length
    $max_length = (int) get_option('hdh_chat_max_message_length', 200);
    if (mb_strlen($message) > $max_length) {
        return new WP_Error('message_too_long', sprintf('Mesaj en fazla %d karakter olabilir', $max_length));
    }
    
    if (mb_strlen(trim($message)) === 0) {
        return new WP_Error('message_empty', 'Mesaj boş olamaz');
    }
    
    // Store original message for moderation
    $message_raw = $message;
    
    // Moderate message (moderation system should be loaded)
    $moderation_result = array(
        'status' => 'published',
        'flags' => array(),
        'censored_message' => $message,
    );
    
    // Moderation will be available after chat-moderation.php is loaded
    if (function_exists('hdh_moderate_message')) {
        $moderation_result = hdh_moderate_message($message, $user_id);
    }
    
    // If blocked, return error
    if ($moderation_result['status'] === 'blocked') {
        // Increment warning strikes
        if (function_exists('hdh_increment_chat_warning')) {
            hdh_increment_chat_warning($user_id, null, 'blocked_message');
        }
        
        return new WP_Error('message_blocked', 'Mesajınız moderasyon kurallarına uymadığı için gönderilemedi');
    }
    
    // Use censored message if available
    $final_message = $moderation_result['censored_message'] ?? $message;
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_chat_messages';
    
    // Sanitize message
    $sanitized_message = wp_kses_post($final_message);
    
    // Store moderation flags as JSON
    $moderation_flags_json = !empty($moderation_result['flags']) ? wp_json_encode($moderation_result['flags']) : null;
    
    $result = $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'message' => $sanitized_message,
            'message_raw' => $message_raw,
            'status' => $moderation_result['status'],
            'moderation_flags' => $moderation_flags_json,
            'warning_strikes' => $moderation_result['flags'] ? count($moderation_result['flags']) : 0,
            'created_at' => current_time('mysql'),
        ),
        array('%d', '%s', '%s', '%s', '%s', '%d', '%s')
    );
    
    if ($result === false) {
        return new WP_Error('db_error', 'Veritabanı hatası');
    }
    
    $message_id = $wpdb->insert_id;
    
    // Log event
    if (function_exists('hdh_log_event')) {
        hdh_log_event($user_id, 'chat_message_sent', array(
            'message_id' => $message_id,
            'status' => $moderation_result['status'],
            'flags' => $moderation_result['flags'],
        ));
    }
    
    // If message was censored, increment warning
    if ($moderation_result['status'] === 'censored' && !empty($moderation_result['flags'])) {
        if (function_exists('hdh_increment_chat_warning')) {
            hdh_increment_chat_warning($user_id, $message_id, 'censored_message');
        }
    }
    
    return $message_id;
}

/**
 * Get chat messages (optimized with user data caching)
 * 
 * @param int $limit Limit number of messages
 * @param int $offset Offset for pagination
 * @param bool $include_deleted Include deleted messages (admin only)
 * @return array Array of message data
 */
function hdh_get_chat_messages($limit = 50, $offset = 0, $include_deleted = false) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_chat_messages';
    
    // Limit max to prevent abuse
    if ($limit > 100) {
        $limit = 100;
    }
    
    $where = "status != 'deleted'";
    if ($include_deleted && current_user_can('manage_options')) {
        $where = "1=1";
    }
    
    $messages = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table_name}
         WHERE {$where}
         ORDER BY created_at DESC
         LIMIT %d OFFSET %d",
        $limit,
        $offset
    ), ARRAY_A);
    
    if (!$messages) {
        return array();
    }
    
    // Collect all user IDs to fetch in batch (avoid N+1 query problem)
    $user_ids = array();
    foreach ($messages as $message) {
        $user_ids[] = (int) $message['user_id'];
    }
    $user_ids = array_unique($user_ids);
    
    // Batch fetch user data
    $users_data = array();
    if (!empty($user_ids)) {
        $users = get_users(array(
            'include' => $user_ids,
            'fields' => array('ID', 'display_name')
        ));
        
        foreach ($users as $user) {
            $users_data[$user->ID] = array(
                'name' => $user->display_name,
                'level' => 1,
            );
            
            // Get user level if function exists
            if (function_exists('hdh_get_user_state')) {
                $user_state = hdh_get_user_state($user->ID);
                $users_data[$user->ID]['level'] = $user_state['level'] ?? 1;
            }
        }
    }
    
    // Enrich messages with user data
    foreach ($messages as &$message) {
        $user_id = (int) $message['user_id'];
        
        if (isset($users_data[$user_id])) {
            $message['user_name'] = $users_data[$user_id]['name'];
            $message['user_level'] = $users_data[$user_id]['level'];
        } else {
            $message['user_name'] = 'Silinmiş Kullanıcı';
            $message['user_level'] = 1;
        }
        
        // Parse moderation flags
        if (!empty($message['moderation_flags'])) {
            $message['moderation_flags'] = json_decode($message['moderation_flags'], true);
        } else {
            $message['moderation_flags'] = array();
        }
    }
    
    return array_reverse($messages); // Reverse to show oldest first
}

/**
 * Get active users count for chat (with fallback and caching)
 * Uses Parça 1 presence system if available, otherwise falls back to user_meta
 * 
 * @param int $threshold_seconds Threshold in seconds (default 120)
 * @return int Active users count
 */
function hdh_get_chat_active_users_count($threshold_seconds = 120) {
    // Cache for 30 seconds to reduce database load
    $cache_key = 'hdh_chat_active_users_' . $threshold_seconds;
    $cached = get_transient($cache_key);
    
    if ($cached !== false) {
        return (int) $cached;
    }
    
    // Try to use Parça 1 presence system if available
    // Check if presence system function exists (from presence-system.php)
    $presence_function_exists = false;
    if (function_exists('hdh_get_active_users_count')) {
        try {
            $reflection = new ReflectionFunction('hdh_get_active_users_count');
            $file = $reflection->getFileName();
            if (strpos($file, 'presence-system.php') !== false) {
                $presence_function_exists = true;
            }
        } catch (Exception $e) {
            // Reflection failed, try calling anyway
        }
    }
    
    $count = 0;
    if ($presence_function_exists) {
        $count = hdh_get_active_users_count($threshold_seconds);
    } else {
        // Fallback: use user_meta
        global $wpdb;
        
        $threshold_time = date('Y-m-d H:i:s', current_time('timestamp') - $threshold_seconds);
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id)
             FROM {$wpdb->usermeta}
             WHERE meta_key = 'hdh_last_active'
             AND meta_value >= %s",
            $threshold_time
        ));
        
        $count = (int) $count;
    }
    
    // Cache for 30 seconds
    set_transient($cache_key, $count, 30);
    
    return $count;
}

/**
 * Delete chat message (soft delete)
 * 
 * @param int $message_id Message ID
 * @param int $user_id User ID (for verification if user's own message)
 * @param bool $admin_delete If true, admin can delete any message
 * @return bool Success
 */
function hdh_delete_chat_message($message_id, $user_id = null, $admin_delete = false) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_chat_messages';
    
    $message = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table_name} WHERE id = %d",
        $message_id
    ), ARRAY_A);
    
    if (!$message) {
        return false;
    }
    
    // Check permissions
    if ($admin_delete && current_user_can('manage_options')) {
        // Admin can delete any message
    } elseif ($user_id && (int) $message['user_id'] === $user_id) {
        // User can delete own message
    } else {
        return false; // No permission
    }
    
    // Soft delete
    $result = $wpdb->update(
        $table_name,
        array('status' => 'deleted'),
        array('id' => $message_id),
        array('%s'),
        array('%d')
    );
    
    return $result !== false;
}

/**
 * ============================================
 * RATE LIMITING & ANTI-SPAM
 * ============================================
 */

/**
 * Check chat rate limit
 * 
 * @param int $user_id User ID
 * @return bool|WP_Error True if allowed, WP_Error if rate limited
 */
function hdh_check_chat_rate_limit($user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_chat_messages';
    
    // Get user level for slow mode
    $user_level = 1;
    if (function_exists('hdh_get_user_state')) {
        $user_state = hdh_get_user_state($user_id);
        $user_level = $user_state['level'] ?? 1;
    }
    
    // Get rate limit settings
    $messages_per_minute = (int) get_option('hdh_chat_messages_per_minute', 3);
    $cooldown_seconds = (int) get_option('hdh_chat_cooldown_seconds', 20);
    
    // Slow mode for low-level users
    $slow_mode_level_5 = (int) get_option('hdh_chat_slow_mode_level_5', 5);
    $slow_mode_level_10 = (int) get_option('hdh_chat_slow_mode_level_10', 10);
    $slow_mode_cooldown_5 = (int) get_option('hdh_chat_slow_mode_cooldown_5', 60);
    $slow_mode_cooldown_10 = (int) get_option('hdh_chat_slow_mode_cooldown_10', 30);
    
    // Apply slow mode cooldown if user level is low
    if ($user_level < $slow_mode_level_5) {
        $cooldown_seconds = $slow_mode_cooldown_5;
    } elseif ($user_level < $slow_mode_level_10) {
        $cooldown_seconds = $slow_mode_cooldown_10;
    }
    
    // Check cooldown (time since last message)
    $cooldown_time = date('Y-m-d H:i:s', current_time('timestamp') - $cooldown_seconds);
    $recent_message = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$table_name}
         WHERE user_id = %d
         AND created_at >= %s
         LIMIT 1",
        $user_id,
        $cooldown_time
    ));
    
    if ($recent_message) {
        return new WP_Error('rate_limit_cooldown', sprintf('Lütfen %d saniye bekleyin', $cooldown_seconds));
    }
    
    // Check messages per minute
    $one_minute_ago = date('Y-m-d H:i:s', current_time('timestamp') - 60);
    $messages_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_name}
         WHERE user_id = %d
         AND created_at >= %s",
        $user_id,
        $one_minute_ago
    ));
    
    if ($messages_count >= $messages_per_minute) {
        return new WP_Error('rate_limit_flood', sprintf('Dakikada en fazla %d mesaj gönderebilirsiniz', $messages_per_minute));
    }
    
    return true;
}

/**
 * Check for duplicate message
 * 
 * @param int $user_id User ID
 * @param string $message Message text
 * @return bool|WP_Error True if allowed, WP_Error if duplicate
 */
function hdh_check_duplicate_message($user_id, $message) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_chat_messages';
    
    // Check if same message sent within last 5 minutes
    $five_minutes_ago = date('Y-m-d H:i:s', current_time('timestamp') - 300);
    $duplicate = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$table_name}
         WHERE user_id = %d
         AND message = %s
         AND created_at >= %s
         LIMIT 1",
        $user_id,
        $message,
        $five_minutes_ago
    ));
    
    if ($duplicate) {
        return new WP_Error('duplicate_message', 'Aynı mesajı kısa süre içinde tekrar gönderemezsiniz');
    }
    
    return true;
}
