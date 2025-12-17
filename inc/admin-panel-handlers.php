<?php
/**
 * HDH: Admin Panel AJAX Handlers
 * Handles save, preview, draft, rollback operations
 */

if (!defined('ABSPATH')) exit;

/**
 * Handle save experience settings
 */
function hdh_ajax_save_experience() {
    check_ajax_referer('hdh_premium_admin', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }
    
    $section = isset($_POST['section']) ? sanitize_key($_POST['section']) : '';
    $group = isset($_POST['group']) ? sanitize_key($_POST['group']) : '';
    $settings = isset($_POST['settings']) ? $_POST['settings'] : array();
    
    $saved = 0;
    $errors = array();
    
    foreach ($settings as $full_key => $value) {
        $config = HDH_Settings_Registry::get($full_key);
        if (!$config) {
            continue;
        }
        
        $storage_key = !empty($config['storage_key']) ? $config['storage_key'] : 'hdh_setting_' . $full_key;
        $old_value = get_option($storage_key, $config['default']);
        
        // Sanitize based on type
        switch ($config['type']) {
            case 'number':
                $value = intval($value);
                break;
            case 'textarea':
                $value = sanitize_textarea_field($value);
                break;
            default:
                $value = sanitize_text_field($value);
        }
        
        // Validate
        if (isset($config['validation'])) {
            if (isset($config['validation']['min']) && $value < $config['validation']['min']) {
                $errors[] = sprintf('%s must be at least %d', $config['label'], $config['validation']['min']);
                continue;
            }
            if (isset($config['validation']['max']) && $value > $config['validation']['max']) {
                $errors[] = sprintf('%s must be at most %d', $config['label'], $config['validation']['max']);
                continue;
            }
        }
        
        update_option($storage_key, $value);
        hdh_log_setting_change($full_key, $old_value, $value);
        $saved++;
    }
    
    if (!empty($errors)) {
        wp_send_json_error(array('message' => 'Some settings failed validation', 'errors' => $errors, 'saved' => $saved));
    }
    
    wp_send_json_success(array('message' => sprintf('Saved %d settings', $saved), 'saved' => $saved));
}
add_action('wp_ajax_hdh_save_experience', 'hdh_ajax_save_experience');

/**
 * Handle save draft
 */
function hdh_ajax_save_draft() {
    check_ajax_referer('hdh_premium_admin', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }
    
    $settings = isset($_POST['settings']) ? $_POST['settings'] : array();
    
    if (!class_exists('HDH_Settings_Registry')) {
        wp_send_json_error(array('message' => 'Registry not available'));
    }
    
    $drafts = array();
    foreach ($settings as $full_key => $value) {
        $config = HDH_Settings_Registry::get($full_key);
        if (!$config) {
            continue;
        }
        
        // Sanitize
        switch ($config['type']) {
            case 'number':
                $value = intval($value);
                break;
            case 'textarea':
                $value = sanitize_textarea_field($value);
                break;
            default:
                $value = sanitize_text_field($value);
        }
        
        $drafts[$full_key] = $value;
    }
    
    hdh_save_draft_changes($drafts);
    
    wp_send_json_success(array('message' => 'Draft saved', 'count' => count($drafts)));
}
add_action('wp_ajax_hdh_save_draft', 'hdh_ajax_save_draft');

/**
 * Handle publish draft
 */
function hdh_ajax_publish_draft() {
    check_ajax_referer('hdh_premium_admin', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }
    
    hdh_publish_draft_changes();
    
    wp_send_json_success(array('message' => 'Draft published'));
}
add_action('wp_ajax_hdh_publish_draft', 'hdh_ajax_publish_draft');

/**
 * Handle rollback
 */
function hdh_ajax_rollback() {
    check_ajax_referer('hdh_premium_admin', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }
    
    $change_id = isset($_POST['change_id']) ? sanitize_text_field($_POST['change_id']) : '';
    
    if (empty($change_id)) {
        wp_send_json_error(array('message' => 'Change ID required'));
    }
    
    $result = hdh_rollback_change($change_id);
    
    if ($result) {
        wp_send_json_success(array('message' => 'Change rolled back'));
    } else {
        wp_send_json_error(array('message' => 'Rollback failed'));
    }
}
add_action('wp_ajax_hdh_rollback', 'hdh_ajax_rollback');

/**
 * Handle search
 */
function hdh_ajax_search_settings() {
    check_ajax_referer('hdh_premium_admin', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }
    
    $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
    
    if (empty($query)) {
        wp_send_json_success(array('results' => array()));
    }
    
    if (!class_exists('HDH_Settings_Registry')) {
        wp_send_json_success(array('results' => array()));
    }
    
    $results = HDH_Settings_Registry::search($query);
    
    wp_send_json_success(array('results' => $results, 'count' => count($results)));
}
add_action('wp_ajax_hdh_search_settings', 'hdh_ajax_search_settings');

/**
 * Handle pin/unpin section
 */
function hdh_ajax_toggle_pin() {
    check_ajax_referer('hdh_premium_admin', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }
    
    $section_key = isset($_POST['section_key']) ? sanitize_text_field($_POST['section_key']) : '';
    $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : 'pin';
    
    $pinned = get_option('hdh_pinned_sections', array());
    
    if ($action === 'pin') {
        if (!isset($pinned[$section_key])) {
            $pinned[$section_key] = array(
                'key' => $section_key,
                'label' => $section_key,
                'url' => admin_url('admin.php?page=' . $section_key),
            );
        }
    } else {
        unset($pinned[$section_key]);
    }
    
    update_option('hdh_pinned_sections', $pinned);
    
    wp_send_json_success(array('pinned' => $action === 'pin'));
}
add_action('wp_ajax_hdh_toggle_pin', 'hdh_ajax_toggle_pin');

