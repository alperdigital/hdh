<?php
/**
 * HDH: Task Data Migration
 * Migrates existing user_meta task data to new database tables
 */

if (!defined('ABSPATH')) exit;

/**
 * Migrate task data from user_meta to new tables
 * 
 * @param bool $dry_run If true, only simulate migration without making changes
 * @return array Migration results
 */
function hdh_migrate_task_data_to_tables($dry_run = false) {
    global $wpdb;
    
    $progress_table = $wpdb->prefix . 'hdh_task_progress';
    $results = array(
        'users_processed' => 0,
        'tasks_migrated' => 0,
        'errors' => array(),
    );
    
    // Get all users
    $users = get_users(array('fields' => 'ID'));
    
    foreach ($users as $user_id) {
        $results['users_processed']++;
        
        // Get one-time tasks config
        if (!function_exists('hdh_get_one_time_tasks_config')) {
            $results['errors'][] = 'hdh_get_one_time_tasks_config function not available';
            continue;
        }
        $one_time_config = hdh_get_one_time_tasks_config();
        
        foreach ($one_time_config as $task_id => $task_config) {
            $progress_key = 'hdh_task_progress_' . $task_id;
            $claimed_key = 'hdh_task_claimed_' . $task_id;
            
            // Get old data
            $old_progress = (int) get_user_meta($user_id, $progress_key, true);
            $old_claimed = get_user_meta($user_id, $claimed_key, true);
            
            // Convert claimed to count (boolean to int)
            $claimed_count = 0;
            if ($old_claimed === true || $old_claimed === '1' || $old_claimed === 1) {
                $claimed_count = $old_progress > 0 ? 1 : 0; // If progress exists and claimed, count = 1
            }
            
            // Check if task is actually completed (for tasks that might not have progress meta)
            $is_completed = false;
            if ($old_progress > 0) {
                $is_completed = true;
            } else {
                // Check task-specific completion
                switch ($task_id) {
                    case 'verify_email':
                        $is_completed = (bool) get_user_meta($user_id, 'hdh_email_verified', true);
                        break;
                    case 'create_first_listing':
                        $listings = get_posts(array(
                            'post_type' => 'hayday_trade',
                            'author' => $user_id,
                            'posts_per_page' => 1,
                            'fields' => 'ids',
                        ));
                        $is_completed = !empty($listings);
                        break;
                    case 'complete_first_exchange':
                        $completed = (int) get_user_meta($user_id, 'hdh_completed_exchanges', true);
                        $is_completed = $completed > 0;
                        break;
                }
            }
            
            if ($is_completed && $old_progress === 0) {
                $old_progress = $task_config['max_progress'];
            }
            
            // Skip if no progress and not completed
            if ($old_progress === 0 && !$is_completed) {
                continue;
            }
            
            $period_key = 'lifetime';
            
            if (!$dry_run) {
                // Check if record already exists
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $progress_table 
                     WHERE user_id = %d AND task_id = %s AND period_key = %s",
                    $user_id,
                    $task_id,
                    $period_key
                ));
                
                if ($existing) {
                    // Update existing record
                    $wpdb->update(
                        $progress_table,
                        array(
                            'completed_count' => $old_progress,
                            'claimed_count' => $claimed_count,
                            'updated_at' => current_time('mysql')
                        ),
                        array(
                            'user_id' => $user_id,
                            'task_id' => $task_id,
                            'period_key' => $period_key
                        ),
                        array('%d', '%d', '%s'),
                        array('%d', '%s', '%s')
                    );
                } else {
                    // Insert new record
                    $wpdb->insert(
                        $progress_table,
                        array(
                            'user_id' => $user_id,
                            'task_id' => $task_id,
                            'period_key' => $period_key,
                            'completed_count' => $old_progress,
                            'claimed_count' => $claimed_count,
                            'updated_at' => current_time('mysql')
                        ),
                        array('%d', '%s', '%s', '%d', '%d', '%s')
                    );
                }
            }
            
            $results['tasks_migrated']++;
        }
        
        // Get daily tasks config
        if (!function_exists('hdh_get_daily_tasks_config')) {
            $results['errors'][] = 'hdh_get_daily_tasks_config function not available';
            continue;
        }
        $daily_config = hdh_get_daily_tasks_config();
        $today = date('Y-m-d');
        
        foreach ($daily_config as $task_id => $task_config) {
            $progress_key = 'hdh_daily_task_progress_' . $task_id;
            $claimed_key = 'hdh_daily_task_claimed_' . $task_id;
            
            // Get old data
            $old_progress = (int) get_user_meta($user_id, $progress_key, true);
            $old_claimed = get_user_meta($user_id, $claimed_key, true);
            
            // Convert claimed to count
            $claimed_count = 0;
            if (is_numeric($old_claimed)) {
                $claimed_count = (int) $old_claimed;
            } elseif ($old_claimed === true || $old_claimed === '1' || $old_claimed === 1) {
                // Old format: boolean true means all progress claimed
                $claimed_count = $old_progress;
            }
            
            // Skip if no progress
            if ($old_progress === 0) {
                continue;
            }
            
            $period_key = $today;
            
            if (!$dry_run) {
                // Check if record already exists
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $progress_table 
                     WHERE user_id = %d AND task_id = %s AND period_key = %s",
                    $user_id,
                    $task_id,
                    $period_key
                ));
                
                if ($existing) {
                    // Update existing record
                    $wpdb->update(
                        $progress_table,
                        array(
                            'completed_count' => $old_progress,
                            'claimed_count' => $claimed_count,
                            'updated_at' => current_time('mysql')
                        ),
                        array(
                            'user_id' => $user_id,
                            'task_id' => $task_id,
                            'period_key' => $period_key
                        ),
                        array('%d', '%d', '%s'),
                        array('%d', '%s', '%s')
                    );
                } else {
                    // Insert new record
                    $wpdb->insert(
                        $progress_table,
                        array(
                            'user_id' => $user_id,
                            'task_id' => $task_id,
                            'period_key' => $period_key,
                            'completed_count' => $old_progress,
                            'claimed_count' => $claimed_count,
                            'updated_at' => current_time('mysql')
                        ),
                        array('%d', '%s', '%s', '%d', '%d', '%s')
                    );
                }
            }
            
            $results['tasks_migrated']++;
        }
    }
    
    return $results;
}

