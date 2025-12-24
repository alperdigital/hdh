<?php
/**
 * HDH: Notification System
 * Site notifications for trade requests, acceptances, and trade step completions
 */

if (!defined('ABSPATH')) exit;

/**
 * ============================================
 * DATABASE TABLE CREATION
 * ============================================
 */

/**
 * Create notifications table
 */
function hdh_create_notifications_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'hdh_notifications';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        type varchar(50) NOT NULL,
        title varchar(255) NOT NULL,
        message text NOT NULL,
        link_url varchar(500) DEFAULT NULL,
        is_read tinyint(1) NOT NULL DEFAULT 0,
        created_at datetime NOT NULL,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY is_read (is_read),
        KEY created_at (created_at),
        KEY type (type)
    ) {$charset_collate};";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Initialize notifications table on theme activation
 */
add_action('after_switch_theme', 'hdh_create_notifications_table');

/**
 * Also create on admin init (for existing sites)
 */
add_action('admin_init', function() {
    if (current_user_can('manage_options')) {
        hdh_create_notifications_table();
    }
}, 1);

/**
 * ============================================
 * NOTIFICATION FUNCTIONS
 * ============================================
 */

/**
 * Create notification
 * 
 * @param int $user_id User ID
 * @param string $type Notification type
 * @param string $title Notification title
 * @param string $message Notification message
 * @param string $link_url Optional link URL
 * @return int|false Notification ID or false
 */
function hdh_create_notification($user_id, $type, $title, $message, $link_url = '') {
    if (!$user_id) {
        return false;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_notifications';
    
    // Prevent duplicate notifications (same type, same user, within 1 minute)
    $one_minute_ago = date('Y-m-d H:i:s', current_time('timestamp') - 60);
    $duplicate = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$table_name}
         WHERE user_id = %d
         AND type = %s
         AND message = %s
         AND created_at >= %s
         LIMIT 1",
        $user_id,
        $type,
        $message,
        $one_minute_ago
    ));
    
    if ($duplicate) {
        return false; // Skip duplicate
    }
    
    $result = $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'type' => sanitize_key($type),
            'title' => sanitize_text_field($title),
            'message' => sanitize_text_field($message),
            'link_url' => esc_url_raw($link_url),
            'is_read' => 0,
            'created_at' => current_time('mysql'),
        ),
        array('%d', '%s', '%s', '%s', '%s', '%d', '%s')
    );
    
    if ($result === false) {
        return false;
    }
    
    return $wpdb->insert_id;
}

/**
 * Get user notifications
 * 
 * @param int $user_id User ID
 * @param bool $unread_only If true, only return unread notifications
 * @param int $limit Limit number of notifications
 * @return array Array of notifications
 */
function hdh_get_user_notifications($user_id, $unread_only = false, $limit = 50) {
    if (!$user_id) {
        return array();
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_notifications';
    
    $where = $wpdb->prepare('user_id = %d', $user_id);
    if ($unread_only) {
        $where .= ' AND is_read = 0';
    }
    
    $notifications = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table_name}
         WHERE {$where}
         ORDER BY created_at DESC
         LIMIT %d",
        $limit
    ), ARRAY_A);
    
    return $notifications ? $notifications : array();
}

/**
 * Mark notification as read
 * 
 * @param int $notification_id Notification ID
 * @param int $user_id User ID (for verification)
 * @return bool Success
 */
function hdh_mark_notification_read($notification_id, $user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_notifications';
    
    $result = $wpdb->update(
        $table_name,
        array('is_read' => 1),
        array(
            'id' => $notification_id,
            'user_id' => $user_id,
        ),
        array('%d'),
        array('%d', '%d')
    );
    
    return $result !== false;
}

/**
 * Mark all notifications as read for user
 * 
 * @param int $user_id User ID
 * @return bool Success
 */
function hdh_mark_all_read($user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_notifications';
    
    $result = $wpdb->update(
        $table_name,
        array('is_read' => 1),
        array('user_id' => $user_id),
        array('%d'),
        array('%d')
    );
    
    return $result !== false;
}

/**
 * Get unread notification count
 * 
 * @param int $user_id User ID
 * @return int Unread count
 */
function hdh_get_unread_count($user_id) {
    if (!$user_id) {
        return 0;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_notifications';
    
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_name}
         WHERE user_id = %d
         AND is_read = 0",
        $user_id
    ));
    
    return (int) $count;
}

/**
 * Delete old notifications (cleanup)
 * 
 * @param int $days_old Delete notifications older than this many days
 * @return int Number of deleted notifications
 */
function hdh_cleanup_old_notifications($days_old = 30) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_notifications';
    
    $cutoff_date = date('Y-m-d H:i:s', current_time('timestamp') - ($days_old * 86400));
    
    $deleted = $wpdb->query($wpdb->prepare(
        "DELETE FROM {$table_name}
         WHERE created_at < %s
         AND is_read = 1",
        $cutoff_date
    ));
    
    return $deleted;
}

/**
 * ============================================
 * AJAX HANDLERS
 * ============================================
 */

/**
 * AJAX: Get user notifications
 */
function hdh_ajax_get_notifications() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Giriş yapmanız gerekiyor'));
        return;
    }
    
    $user_id = get_current_user_id();
    $notifications = hdh_get_user_notifications($user_id, false, 50);
    
    wp_send_json_success(array(
        'notifications' => $notifications
    ));
}
add_action('wp_ajax_hdh_get_notifications', 'hdh_ajax_get_notifications');

/**
 * AJAX: Mark notification as read
 */
function hdh_ajax_mark_notification_read() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Giriş yapmanız gerekiyor'));
        return;
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_notifications')) {
        wp_send_json_error(array('message' => 'Güvenlik kontrolü başarısız'));
        return;
    }
    
    $notification_id = isset($_POST['notification_id']) ? absint($_POST['notification_id']) : 0;
    $user_id = get_current_user_id();
    
    if (!$notification_id) {
        wp_send_json_error(array('message' => 'Geçersiz bildirim ID'));
        return;
    }
    
    $result = hdh_mark_notification_read($notification_id, $user_id);
    
    if ($result) {
        wp_send_json_success(array('message' => 'Bildirim okundu olarak işaretlendi'));
    } else {
        wp_send_json_error(array('message' => 'Bildirim işaretlenemedi'));
    }
}
add_action('wp_ajax_hdh_mark_notification_read', 'hdh_ajax_mark_notification_read');

/**
 * AJAX: Mark all notifications as read
 */
function hdh_ajax_mark_all_notifications_read() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Giriş yapmanız gerekiyor'));
        return;
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_notifications')) {
        wp_send_json_error(array('message' => 'Güvenlik kontrolü başarısız'));
        return;
    }
    
    $user_id = get_current_user_id();
    $result = hdh_mark_all_read($user_id);
    
    if ($result) {
        wp_send_json_success(array('message' => 'Tüm bildirimler okundu olarak işaretlendi'));
    } else {
        wp_send_json_error(array('message' => 'Bildirimler işaretlenemedi'));
    }
}
add_action('wp_ajax_hdh_mark_all_notifications_read', 'hdh_ajax_mark_all_notifications_read');

/**
 * AJAX: Get unread count
 */
function hdh_ajax_get_unread_count() {
    if (!is_user_logged_in()) {
        wp_send_json_success(array('count' => 0));
        return;
    }
    
    $user_id = get_current_user_id();
    $count = hdh_get_unread_count($user_id);
    
    wp_send_json_success(array('count' => $count));
}
add_action('wp_ajax_hdh_get_unread_count', 'hdh_ajax_get_unread_count');

