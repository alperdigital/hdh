<?php
/**
 * HDH: Admin Change History & Rollback System
 * Tracks changes and enables rollback functionality
 */

if (!defined('ABSPATH')) exit;

/**
 * Log a setting change
 */
function hdh_log_setting_change($setting_key, $old_value, $new_value, $user_id = null) {
    if ($user_id === null) {
        $user_id = get_current_user_id();
    }
    
    $history = get_option('hdh_change_history', array());
    
    $change = array(
        'id' => uniqid('ch_'),
        'timestamp' => current_time('timestamp'),
        'user_id' => $user_id,
        'setting_key' => $setting_key,
        'old_value' => $old_value,
        'new_value' => $new_value,
        'description' => sprintf('Changed %s', $setting_key),
    );
    
    array_unshift($history, $change);
    
    // Keep only last 100 changes
    $history = array_slice($history, 0, 100);
    
    update_option('hdh_change_history', $history);
    
    return $change['id'];
}

/**
 * Get change history
 */
function hdh_get_change_history($limit = 50) {
    $history = get_option('hdh_change_history', array());
    return array_slice($history, 0, $limit);
}

/**
 * Rollback a change
 */
function hdh_rollback_change($change_id) {
    $history = get_option('hdh_change_history', array());
    
    foreach ($history as $change) {
        if ($change['id'] === $change_id) {
                // Restore old value
                if (class_exists('HDH_Settings_Registry')) {
                    $registry = HDH_Settings_Registry::get($change['setting_key']);
                    if ($registry) {
                        $storage_key = !empty($registry['storage_key']) ? $registry['storage_key'] : 'hdh_setting_' . $change['setting_key'];
                        update_option($storage_key, $change['old_value']);
                        
                        // Log the rollback
                        hdh_log_setting_change(
                            $change['setting_key'],
                            $change['new_value'],
                            $change['old_value']
                        );
                        
                        return true;
                    }
                } else {
                    // Fallback: try to restore directly
                    $storage_key = 'hdh_setting_' . $change['setting_key'];
                    update_option($storage_key, $change['old_value']);
                    hdh_log_setting_change(
                        $change['setting_key'],
                        $change['new_value'],
                        $change['old_value']
                    );
                    return true;
                }
        }
    }
    
    return false;
}

/**
 * Save draft changes
 */
function hdh_save_draft_changes($changes) {
    $drafts = get_option('hdh_draft_changes', array());
    $drafts = array_merge($drafts, $changes);
    update_option('hdh_draft_changes', $drafts);
}

/**
 * Get draft changes
 */
function hdh_get_draft_changes() {
    return get_option('hdh_draft_changes', array());
}

/**
 * Publish draft changes
 */
function hdh_publish_draft_changes() {
    $drafts = hdh_get_draft_changes();
    
    foreach ($drafts as $setting_key => $new_value) {
        $registry = HDH_Settings_Registry::get($setting_key);
        if ($registry) {
            $storage_key = !empty($registry['storage_key']) ? $registry['storage_key'] : 'hdh_setting_' . $setting_key;
            $old_value = get_option($storage_key, $registry['default']);
            
            update_option($storage_key, $new_value);
            hdh_log_setting_change($setting_key, $old_value, $new_value);
        }
    }
    
    // Clear drafts
    delete_option('hdh_draft_changes');
}

/**
 * Clear draft changes
 */
function hdh_clear_draft_changes() {
    delete_option('hdh_draft_changes');
}

