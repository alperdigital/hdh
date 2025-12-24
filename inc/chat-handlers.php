<?php
/**
 * HDH: Chat AJAX Handlers
 * Handles AJAX requests for lobby chat
 */

if (!defined('ABSPATH')) exit;

/**
 * Send chat message
 */
function hdh_ajax_send_chat_message() {
    // Check authentication
    if (!is_user_logged_in()) {
        wp_send_json_error(array(
            'message' => 'Giriş yapmanız gerekiyor'
        ));
        return;
    }
    
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_chat_message')) {
        wp_send_json_error(array(
            'message' => 'Güvenlik kontrolü başarısız'
        ));
        return;
    }
    
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    $user_id = get_current_user_id();
    
    if (empty($message)) {
        wp_send_json_error(array(
            'message' => 'Mesaj boş olamaz'
        ));
        return;
    }
    
    // Create chat message
    $message_id = hdh_create_chat_message($user_id, $message);
    
    if (is_wp_error($message_id)) {
        wp_send_json_error(array(
            'message' => $message_id->get_error_message()
        ));
        return;
    }
    
    // Get the created message
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_chat_messages';
    $created_message = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table_name} WHERE id = %d",
        $message_id
    ), ARRAY_A);
    
    if ($created_message) {
        // Enrich with user data
        $user = get_userdata($user_id);
        $created_message['user_name'] = $user->display_name;
        $created_message['user_level'] = 1;
        if (function_exists('hdh_get_user_state')) {
            $user_state = hdh_get_user_state($user_id);
            $created_message['user_level'] = $user_state['level'] ?? 1;
        }
        
        // Parse moderation flags
        if (!empty($created_message['moderation_flags'])) {
            $created_message['moderation_flags'] = json_decode($created_message['moderation_flags'], true);
        } else {
            $created_message['moderation_flags'] = array();
        }
    }
    
    wp_send_json_success(array(
        'message' => $created_message,
        'message_text' => 'Mesaj gönderildi'
    ));
}
add_action('wp_ajax_hdh_send_chat_message', 'hdh_ajax_send_chat_message');

/**
 * Get chat messages
 */
function hdh_ajax_get_chat_messages() {
    // Verify nonce (optional for public read access, but recommended)
    $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
    if (!empty($nonce) && !wp_verify_nonce($nonce, 'hdh_chat_message')) {
        wp_send_json_error(array(
            'message' => 'Güvenlik kontrolü başarısız'
        ));
        return;
    }
    
    $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 50;
    $offset = isset($_POST['offset']) ? absint($_POST['offset']) : 0;
    
    // Limit max to prevent abuse
    if ($limit > 100) {
        $limit = 100;
    }
    
    // Rate limiting: prevent excessive requests
    $user_id = get_current_user_id();
    if ($user_id) {
        $cache_key = 'hdh_chat_get_messages_' . $user_id;
        $last_request = get_transient($cache_key);
        if ($last_request !== false && (time() - $last_request) < 1) {
            // Max 1 request per second per user
            wp_send_json_error(array(
                'message' => 'Çok fazla istek. Lütfen bekleyin.'
            ));
            return;
        }
        set_transient($cache_key, time(), 2);
    }
    
    $messages = hdh_get_chat_messages($limit, $offset);
    
    wp_send_json_success(array(
        'messages' => $messages,
        'has_more' => count($messages) === $limit
    ));
}
add_action('wp_ajax_hdh_get_chat_messages', 'hdh_ajax_get_chat_messages');
add_action('wp_ajax_nopriv_hdh_get_chat_messages', 'hdh_ajax_get_chat_messages');

/**
 * Get active users count
 */
function hdh_ajax_get_active_users_count() {
    // No nonce required for public read access
    $threshold = isset($_POST['threshold']) ? absint($_POST['threshold']) : 120;
    
    $count = 0;
    if (function_exists('hdh_get_chat_active_users_count')) {
        $count = hdh_get_chat_active_users_count($threshold);
    }
    
    wp_send_json_success(array(
        'count' => $count
    ));
}
add_action('wp_ajax_hdh_get_active_users_count', 'hdh_ajax_get_active_users_count');
add_action('wp_ajax_nopriv_hdh_get_active_users_count', 'hdh_ajax_get_active_users_count');
