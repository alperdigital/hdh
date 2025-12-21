<?php
/**
 * HDH: Atomic Task Claim Engine
 * This replaces the old hdh_claim_task_reward function with atomic transaction-based claiming
 */

if (!defined('ABSPATH')) exit;

/**
 * Claim task reward (ATOMIC - with transaction and row locking)
 * This is the ONLY function that awards rewards. All automatic rewards have been removed.
 * 
 * @param int $user_id User ID
 * @param string $task_id Task ID
 * @param bool $is_daily Whether this is a daily task
 * @return array|WP_Error Success with rewards or error
 */
function hdh_claim_task_reward_atomic($user_id, $task_id, $is_daily = false) {
    global $wpdb;
    
    // 1. Auth & validation
    if (!$user_id || !$task_id) {
        $error_msg = function_exists('hdh_get_message') 
            ? hdh_get_message('ajax', 'invalid_parameters', 'Geçersiz parametreler')
            : 'Geçersiz parametreler';
        return new WP_Error('invalid_params', $error_msg);
    }
    
    // Check if config functions are available
    if (!function_exists('hdh_get_one_time_tasks_config') || !function_exists('hdh_get_daily_tasks_config')) {
        return new WP_Error('config_not_loaded', 'Task configuration functions not available');
    }
    
    $config = $is_daily ? hdh_get_daily_tasks_config() : hdh_get_one_time_tasks_config();
    
    if (!isset($config[$task_id])) {
        $error_msg = function_exists('hdh_get_message') 
            ? hdh_get_message('ajax', 'invalid_task', 'Geçersiz görev')
            : 'Geçersiz görev';
        return new WP_Error('invalid_task', $error_msg);
    }
    
    $task_config = $config[$task_id];
    
    // Determine period_key
    $period_key = $is_daily ? date('Y-m-d') : 'lifetime';
    
    $progress_table = $wpdb->prefix . 'hdh_task_progress';
    $ledger_table = $wpdb->prefix . 'hdh_reward_ledger';
    
    // Ensure tables exist (safety check)
    if (function_exists('hdh_create_task_progress_table')) {
        hdh_create_task_progress_table(); // This function checks if table exists before creating
    }
    if (function_exists('hdh_create_reward_ledger_table')) {
        hdh_create_reward_ledger_table(); // This function checks if table exists before creating
    }
    
    // 2. START TRANSACTION
    $wpdb->query('START TRANSACTION');
    
    try {
        // 3. SELECT ... FOR UPDATE ile progress kaydını kilitle
        $progress = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $progress_table 
             WHERE user_id = %d AND task_id = %s AND period_key = %s 
             FOR UPDATE",
            $user_id,
            $task_id,
            $period_key
        ), ARRAY_A);
        
        // If no progress record exists, check if task is completed and create one
        if (!$progress) {
            // For one-time tasks, check completion status
            if (!$is_daily) {
                $is_completed = false;
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
                        if (function_exists('hdh_get_task_progress')) {
                            $existing_progress = hdh_get_task_progress($user_id, 'complete_first_exchange', 'lifetime');
                            $is_completed = $existing_progress && (int) $existing_progress['completed_count'] > 0;
                        } else {
                            $is_completed = false;
                        }
                        break;
                    default:
                        $is_completed = false;
                }
                
                if (!$is_completed) {
                    $wpdb->query('ROLLBACK');
                    $error_msg = function_exists('hdh_get_message') 
                        ? hdh_get_message('ajax', 'not_completed', 'Görev henüz tamamlanmamış')
                        : 'Görev henüz tamamlanmamış';
                    return new WP_Error('not_completed', $error_msg);
                }
                
                // Create progress record with completed_count = 1
                $wpdb->insert(
                    $progress_table,
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
                
                $progress = array(
                    'user_id' => $user_id,
                    'task_id' => $task_id,
                    'period_key' => $period_key,
                    'completed_count' => 1,
                    'claimed_count' => 0
                );
            } else {
                // For daily tasks, if no progress record, there's nothing to claim
                $wpdb->query('ROLLBACK');
                $error_msg = function_exists('hdh_get_message') 
                    ? hdh_get_message('ajax', 'not_completed', 'Görev henüz tamamlanmamış')
                    : 'Görev henüz tamamlanmamış';
                return new WP_Error('not_completed', $error_msg);
            }
        }
        
        // 4. Calculate claimable count
        $completed_count = (int) $progress['completed_count'];
        $claimed_count = (int) $progress['claimed_count'];
        $claimable = $completed_count - $claimed_count;
        
        // 5. Check if claimable <= 0
        if ($claimable <= 0) {
            $wpdb->query('ROLLBACK');
            $error_msg = function_exists('hdh_get_message') 
                ? hdh_get_message('ajax', 'already_claimed', 'Bu görevin ödülü zaten alınmış')
                : 'Bu görevin ödülü zaten alınmış';
            return new WP_Error('already_claimed', $error_msg);
        }
        
        // 6. Increment claimed_count
        $new_claimed_count = $claimed_count + 1;
        $updated = $wpdb->update(
            $progress_table,
            array(
                'claimed_count' => $new_claimed_count,
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
            $wpdb->query('ROLLBACK');
            return new WP_Error('update_failed', 'Failed to update claimed count');
        }
        
        // 7. Insert into ledger (claim_index = new_claimed_count)
        $claim_index = $new_claimed_count;
        
        // Check if ledger entry already exists (shouldn't happen due to unique constraint, but double-check)
        $existing_ledger = $wpdb->get_var($wpdb->prepare(
            "SELECT ledger_id FROM $ledger_table 
             WHERE user_id = %d AND task_id = %s AND period_key = %s AND claim_index = %d",
            $user_id,
            $task_id,
            $period_key,
            $claim_index
        ));
        
        if ($existing_ledger) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('duplicate_claim', 'This reward has already been claimed');
        }
        
        // Award bilet if configured
        $bilet_awarded = 0;
        if ($task_config['reward_bilet'] > 0) {
            $bilet_awarded = (int) $task_config['reward_bilet'];
            
            // Insert bilet reward into ledger
            $wpdb->insert(
                $ledger_table,
                array(
                    'user_id' => $user_id,
                    'task_id' => $task_id,
                    'period_key' => $period_key,
                    'claim_index' => $claim_index,
                    'reward_type' => 'bilet',
                    'reward_amount' => $bilet_awarded,
                    'status' => 'applied',
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%s', '%s', '%d', '%s', '%d', '%s', '%s')
            );
            
            // Apply bilet reward
            if (function_exists('hdh_add_bilet')) {
                $transaction_id = 'task_' . $task_id . '_' . ($is_daily ? 'daily' : 'onetime') . '_' . $user_id . '_' . $claim_index . '_' . current_time('timestamp');
                $result = hdh_add_bilet($user_id, $bilet_awarded, 'task_reward', array(
                    'task_id' => $task_id,
                    'is_daily' => $is_daily,
                    'claim_index' => $claim_index,
                    'transaction_id' => $transaction_id,
                ));
                
                if (is_wp_error($result)) {
                    $wpdb->query('ROLLBACK');
                    return $result;
                }
            }
        }
        
        // Award XP/Level if configured
        $level_gain = 0;
        if ($task_config['reward_level'] > 0) {
            $xp_per_level = function_exists('hdh_get_xp_per_level') ? hdh_get_xp_per_level() : 100;
            $xp_amount = $task_config['reward_level'] * $xp_per_level;
            
            // Insert XP reward into ledger
            $wpdb->insert(
                $ledger_table,
                array(
                    'user_id' => $user_id,
                    'task_id' => $task_id,
                    'period_key' => $period_key,
                    'claim_index' => $claim_index,
                    'reward_type' => 'xp',
                    'reward_amount' => $xp_amount,
                    'status' => 'applied',
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%s', '%s', '%d', '%s', '%d', '%s', '%s')
            );
            
            // Get level before adding XP
            $user_state_before = function_exists('hdh_get_user_state') ? hdh_get_user_state($user_id) : null;
            $old_level = $user_state_before ? $user_state_before['level'] : 1;
            
            // Apply XP reward
            if (function_exists('hdh_add_xp')) {
                hdh_add_xp($user_id, $xp_amount, 'task_reward', array(
                    'task_id' => $task_id,
                    'is_daily' => $is_daily,
                    'claim_index' => $claim_index,
                ));
            }
            
            // Get level after adding XP
            $user_state_after = function_exists('hdh_get_user_state') ? hdh_get_user_state($user_id) : null;
            $new_level = $user_state_after ? $user_state_after['level'] : $old_level;
            $level_gain = $new_level - $old_level;
        }
        
        // 9. COMMIT transaction
        $wpdb->query('COMMIT');
        
        // Log event
        if (function_exists('hdh_log_event')) {
            hdh_log_event($user_id, 'task_reward_claimed', array(
                'task_id' => $task_id,
                'is_daily' => $is_daily,
                'period_key' => $period_key,
                'claim_index' => $claim_index,
                'rewards' => array(
                    'bilet' => $bilet_awarded,
                    'level' => $level_gain,
                ),
                'completed_count' => $completed_count,
                'claimed_count_before' => $claimed_count,
                'claimed_count_after' => $new_claimed_count,
            ));
        }
        
        // 10. Return success
        return array(
            'success' => true,
            'bilet' => $bilet_awarded,
            'level' => $level_gain,
            'claim_index' => $claim_index,
            'claimable_remaining' => $completed_count - $new_claimed_count,
        );
        
    } catch (Exception $e) {
        // Rollback on any error
        $wpdb->query('ROLLBACK');
        return new WP_Error('claim_failed', 'Failed to claim reward: ' . $e->getMessage());
    }
}

