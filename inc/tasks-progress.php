<?php
/**
 * HDH: Task Progress Increment System
 * Handles progress tracking without awarding rewards
 */

if (!defined('ABSPATH')) exit;

/**
 * Increment task progress (NO REWARD GIVEN)
 * 
 * @param int $user_id User ID
 * @param string $task_id Task ID
 * @param string|null $period_key Period key (date string for daily, 'lifetime' for one-time, null for auto-detect)
 * @return bool|WP_Error Success or error
 */
function hdh_increment_task_progress($user_id, $task_id, $period_key = null) {
    global $wpdb;
    
    if (!$user_id || !$task_id) {
        return new WP_Error('invalid_params', 'Invalid user_id or task_id');
    }
    
    // Determine period_key if not provided
    if ($period_key === null) {
        // Check if task is one-time or daily
        if (!function_exists('hdh_get_one_time_tasks_config') || !function_exists('hdh_get_daily_tasks_config')) {
            return new WP_Error('config_not_loaded', 'Task configuration functions not available');
        }
        
        $one_time_config = hdh_get_one_time_tasks_config();
        $daily_config = hdh_get_daily_tasks_config();
        
        if (isset($one_time_config[$task_id])) {
            $period_key = 'lifetime';
        } elseif (isset($daily_config[$task_id])) {
            $period_key = date('Y-m-d'); // Today's date for daily tasks
        } else {
            return new WP_Error('task_not_found', 'Task not found in configuration');
        }
    }
    
    $table_name = $wpdb->prefix . 'hdh_task_progress';
    
    // Ensure table exists (safety check)
    if (!function_exists('hdh_create_task_progress_table')) {
        // Table creation function not available, return error
        return new WP_Error('table_not_available', 'Task progress table not available');
    }
    hdh_create_task_progress_table(); // This function checks if table exists before creating
    
    // Check if record exists
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT id, completed_count FROM $table_name 
         WHERE user_id = %d AND task_id = %s AND period_key = %s",
        $user_id,
        $task_id,
        $period_key
    ));
    
    if ($existing) {
        // Update existing record
        $new_count = $existing->completed_count + 1;
        
        // Get max_progress from config to cap the count
        if (!function_exists('hdh_get_one_time_tasks_config') || !function_exists('hdh_get_daily_tasks_config')) {
            // If config not available, just increment without capping
            $max_progress = PHP_INT_MAX;
        } else {
            $one_time_config = hdh_get_one_time_tasks_config();
            $daily_config = hdh_get_daily_tasks_config();
            
            $max_progress = 1; // Default
            if (isset($one_time_config[$task_id])) {
                $max_progress = isset($one_time_config[$task_id]['max_progress']) 
                    ? (int) $one_time_config[$task_id]['max_progress'] 
                    : 1;
            } elseif (isset($daily_config[$task_id])) {
                $max_progress = isset($daily_config[$task_id]['max_progress']) 
                    ? (int) $daily_config[$task_id]['max_progress'] 
                    : 1;
            }
        }
        
        // Cap at max_progress (for daily tasks that can repeat)
        // For one-time tasks, max_progress is usually 1, so this won't matter
        // For daily tasks, we allow going over max_progress (e.g., 5/5 can become 6/5, user can claim 5 times)
        // Actually, let's not cap - allow unlimited completions, user can claim up to completed_count
        
        $updated = $wpdb->update(
            $table_name,
            array(
                'completed_count' => $new_count,
                'updated_at' => current_time('mysql')
            ),
            array(
                'user_id' => $user_id,
                'task_id' => $task_id,
                'period_key' => $period_key
            ),
            array('%d', '%s'),
            array('%d', '%s', '%s')
        );
        
        if ($updated === false) {
            return new WP_Error('update_failed', 'Failed to update task progress');
        }
    } else {
        // Insert new record
        $inserted = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'task_id' => $task_id,
                'period_key' => $period_key,
                'completed_count' => 1,
                'claimed_count' => 0,
                'updated_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%d', '%d', '%s')
        );
        
        if ($inserted === false) {
            return new WP_Error('insert_failed', 'Failed to insert task progress');
        }
    }
    
    // Log event (optional, for audit trail)
    if (function_exists('hdh_log_event')) {
        hdh_log_event($user_id, 'task_progress_incremented', array(
            'task_id' => $task_id,
            'period_key' => $period_key,
            'note' => 'Progress incremented, no reward given'
        ));
    }
    
    return true;
}

/**
 * Get task progress for a user
 * 
 * @param int $user_id User ID
 * @param string $task_id Task ID
 * @param string|null $period_key Period key (null for auto-detect)
 * @return array|null Progress data or null if not found
 */
function hdh_get_task_progress($user_id, $task_id, $period_key = null) {
    global $wpdb;
    
    if (!$user_id || !$task_id) {
        return null;
    }
    
    // Determine period_key if not provided
    if ($period_key === null) {
        if (!function_exists('hdh_get_one_time_tasks_config') || !function_exists('hdh_get_daily_tasks_config')) {
            return null;
        }
        
        $one_time_config = hdh_get_one_time_tasks_config();
        $daily_config = hdh_get_daily_tasks_config();
        
        if (isset($one_time_config[$task_id])) {
            $period_key = 'lifetime';
        } elseif (isset($daily_config[$task_id])) {
            $period_key = date('Y-m-d');
        } else {
            return null;
        }
    }
    
    $table_name = $wpdb->prefix . 'hdh_task_progress';
    
    // Ensure table exists (safety check)
    if (function_exists('hdh_create_task_progress_table')) {
        hdh_create_task_progress_table(); // This function checks if table exists before creating
    }
    
    $progress = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name 
         WHERE user_id = %d AND task_id = %s AND period_key = %s",
        $user_id,
        $task_id,
        $period_key
    ), ARRAY_A);
    
    return $progress;
}

/**
 * Calculate claimable count for a task
 * 
 * @param int $user_id User ID
 * @param string $task_id Task ID
 * @param string|null $period_key Period key (null for auto-detect)
 * @return int Claimable count (completed_count - claimed_count)
 */
function hdh_get_claimable_count($user_id, $task_id, $period_key = null) {
    $progress = hdh_get_task_progress($user_id, $task_id, $period_key);
    
    if (!$progress) {
        return 0;
    }
    
    $claimable = (int) $progress['completed_count'] - (int) $progress['claimed_count'];
    return max(0, $claimable); // Never return negative
}

