<?php
/**
 * HDH: Tasks Handler - AJAX endpoints for task operations
 */

if (!defined('ABSPATH')) exit;

/**
 * Handle claim task reward AJAX request
 */
function hdh_handle_claim_task_reward() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Giriş yapmanız gerekiyor'));
        return;
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_claim_task_reward')) {
        wp_send_json_error(array('message' => 'Güvenlik kontrolü başarısız'));
        return;
    }
    
    $user_id = get_current_user_id();
    $task_id = isset($_POST['task_id']) ? sanitize_text_field($_POST['task_id']) : '';
    $is_daily = isset($_POST['is_daily']) && $_POST['is_daily'] === 'true';
    
    if (empty($task_id)) {
        wp_send_json_error(array('message' => 'Görev ID gerekli'));
        return;
    }
    
    if (!function_exists('hdh_claim_task_reward')) {
        wp_send_json_error(array('message' => 'Görev sistemi aktif değil'));
        return;
    }
    
    $result = hdh_claim_task_reward($user_id, $task_id, $is_daily);
    
    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
        return;
    }
    
    // Get updated balances
    $new_bilet = function_exists('hdh_get_user_jeton_balance') ? hdh_get_user_jeton_balance($user_id) : 0;
    $user_state = function_exists('hdh_get_user_state') ? hdh_get_user_state($user_id) : null;
    $new_level = $user_state ? $user_state['level'] : 1;
    
    wp_send_json_success(array(
        'message' => 'Ödül başarıyla alındı!',
        'bilet' => $result['bilet'],
        'level' => $result['level'],
        'new_bilet' => $new_bilet,
        'new_level' => $new_level,
    ));
}
add_action('wp_ajax_hdh_claim_task_reward', 'hdh_handle_claim_task_reward');

/**
 * Handle get tasks AJAX request (for refreshing task list)
 */
function hdh_handle_get_tasks() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Giriş yapmanız gerekiyor'));
        return;
    }
    
    $user_id = get_current_user_id();
    
    if (!function_exists('hdh_get_user_one_time_tasks') || !function_exists('hdh_get_user_daily_tasks')) {
        wp_send_json_error(array('message' => 'Görev sistemi aktif değil'));
        return;
    }
    
    // Get tasks with error handling
    $one_time_tasks = array();
    $daily_tasks = array();
    
    try {
        $one_time_tasks = hdh_get_user_one_time_tasks($user_id);
        if (!is_array($one_time_tasks)) {
            $one_time_tasks = array();
        }
    } catch (Exception $e) {
        error_log('HDH Tasks AJAX: Error getting one-time tasks: ' . $e->getMessage());
        $one_time_tasks = array();
    }
    
    try {
        $daily_tasks = hdh_get_user_daily_tasks($user_id);
        if (!is_array($daily_tasks)) {
            $daily_tasks = array();
        }
    } catch (Exception $e) {
        error_log('HDH Tasks AJAX: Error getting daily tasks: ' . $e->getMessage());
        $daily_tasks = array();
    }
    
    wp_send_json_success(array(
        'one_time_tasks' => $one_time_tasks,
        'daily_tasks' => $daily_tasks,
    ));
}
add_action('wp_ajax_hdh_get_tasks', 'hdh_handle_get_tasks');
