<?php
/**
 * HDH: Tasks System - One-time and Daily Tasks
 * Manages task progress, completion, and reward claiming
 */

if (!defined('ABSPATH')) exit;

/**
 * Clean up removed tasks from WordPress options
 * Removes friend_exchange and friend_exchanges tasks
 */
function hdh_cleanup_removed_tasks() {
    // Clean one-time tasks
    $one_time_tasks = get_option('hdh_one_time_tasks', array());
    if (!empty($one_time_tasks) && isset($one_time_tasks['friend_exchange'])) {
        unset($one_time_tasks['friend_exchange']);
        update_option('hdh_one_time_tasks', $one_time_tasks);
    }
    
    // Clean daily tasks
    $daily_tasks = get_option('hdh_daily_tasks', array());
    if (!empty($daily_tasks) && isset($daily_tasks['friend_exchanges'])) {
        unset($daily_tasks['friend_exchanges']);
        update_option('hdh_daily_tasks', $daily_tasks);
    }
}
add_action('admin_init', 'hdh_cleanup_removed_tasks');

/**
 * Update daily task descriptions to new format
 * Migrates old descriptions to new ones
 */
function hdh_update_daily_task_descriptions() {
    $daily_tasks = get_option('hdh_daily_tasks', array());
    if (empty($daily_tasks)) {
        return;
    }
    
    $updated = false;
    
    // Update create_listings description
    if (isset($daily_tasks['create_listings']) && 
        isset($daily_tasks['create_listings']['description']) &&
        ($daily_tasks['create_listings']['description'] === 'Günde 3 ilan oluşturun' || 
         $daily_tasks['create_listings']['description'] === 'İlan oluşturun')) {
        $daily_tasks['create_listings']['description'] = 'Bir ilan oluşturun';
        $updated = true;
    }
    
    // Update complete_exchanges description
    if (isset($daily_tasks['complete_exchanges']) && 
        isset($daily_tasks['complete_exchanges']['description']) &&
        ($daily_tasks['complete_exchanges']['description'] === 'Günde 5 hediyeleşme tamamlayın' || 
         $daily_tasks['complete_exchanges']['description'] === 'Hediyeleşme tamamlayın')) {
        $daily_tasks['complete_exchanges']['description'] = 'Bir hediyeleşme tamamlayın';
        $updated = true;
    }
    
    // Update invite_friends description
    if (isset($daily_tasks['invite_friends']) && 
        isset($daily_tasks['invite_friends']['description']) &&
        ($daily_tasks['invite_friends']['description'] === 'Günde 5 arkadaş davet edin' || 
         $daily_tasks['invite_friends']['description'] === 'Arkadaşınızı davet edin')) {
        $daily_tasks['invite_friends']['description'] = 'Bir arkadaş davet edin';
        $updated = true;
    }
    
    if ($updated) {
        update_option('hdh_daily_tasks', $daily_tasks);
        // Clear cache
        wp_cache_delete('hdh_daily_tasks', 'options');
    }
}
add_action('admin_init', 'hdh_update_daily_task_descriptions');

/**
 * Get one-time tasks configuration
 * Now loads from WordPress options (admin-manageable)
 * Falls back to hardcoded config if options are empty (only for frontend, not admin)
 * 
 * @param bool $save_if_empty Whether to save defaults to options if empty (default: true for frontend, false for admin)
 */
function hdh_get_one_time_tasks_config($save_if_empty = true) {
    // Clean up removed tasks first
    hdh_cleanup_removed_tasks();
    
    // Try to load from options first (admin-managed)
    // Use get_option with false to check if option exists, then get actual value
    $tasks = get_option('hdh_one_time_tasks', false);
    if ($tasks === false) {
        $tasks = array();
    }
    
    // Filter out removed tasks
    if (isset($tasks['friend_exchange'])) {
        unset($tasks['friend_exchange']);
        if ($save_if_empty && !empty($tasks)) {
            update_option('hdh_one_time_tasks', $tasks);
        }
    }
    
    // If empty, use hardcoded default (for migration/fallback)
    // Only save to options if $save_if_empty is true (frontend usage)
    if (empty($tasks)) {
        $tasks = array(
            'verify_email' => array(
                'id' => 'verify_email',
                'title' => 'Doğrulama',
                'description' => 'E-posta adresinizi doğrulayın',
                'reward_bilet' => 1,
                'reward_level' => 1,
                'max_progress' => 1,
            ),
            'create_first_listing' => array(
                'id' => 'create_first_listing',
                'title' => 'İlk ilan',
                'description' => 'İlk ilanınızı oluşturun',
                'reward_bilet' => 2,
                'reward_level' => 1,
                'max_progress' => 1,
            ),
            'complete_first_exchange' => array(
                'id' => 'complete_first_exchange',
                'title' => 'İlk hediyeleşme',
                'description' => 'İlk hediyeleşmenizi tamamlayın',
                'reward_bilet' => 5,
                'reward_level' => 2,
                'max_progress' => 1,
            ),
            'invite_friend' => array(
                'id' => 'invite_friend',
                'title' => 'Davet et',
                'description' => 'Bir arkadaşınızı davet edin',
                'reward_bilet' => 2,
                'reward_level' => 1,
                'max_progress' => 1,
            ),
        );
        // Save defaults to options only if $save_if_empty is true (frontend usage)
        if ($save_if_empty) {
            update_option('hdh_one_time_tasks', $tasks);
        }
    }
    
    return $tasks;
}

/**
 * Get daily tasks configuration
 * Now loads from WordPress options (admin-manageable)
 * Falls back to hardcoded config if options are empty (only for frontend, not admin)
 * 
 * @param bool $save_if_empty Whether to save defaults to options if empty (default: true for frontend, false for admin)
 */
function hdh_get_daily_tasks_config($save_if_empty = true) {
    // Try to load from options first (admin-managed)
    // Use get_option with false to check if option exists, then get actual value
    $tasks = get_option('hdh_daily_tasks', false);
    if ($tasks === false) {
        $tasks = array();
    }
    
    // Filter out removed tasks
    if (isset($tasks['friend_exchanges'])) {
        unset($tasks['friend_exchanges']);
        if ($save_if_empty) {
            update_option('hdh_daily_tasks', $tasks);
        }
    }
    
    // If empty, use hardcoded default (for migration/fallback)
    // Only save to options if $save_if_empty is true (frontend usage)
    if (empty($tasks)) {
        $tasks = array(
            'create_listings' => array(
                'id' => 'create_listings',
                'title' => 'İlan Oluştur',
                'description' => 'Bir ilan oluşturun',
                'reward_bilet' => 1,
                'reward_level' => 0,
                'max_progress' => 3,
            ),
            'complete_exchanges' => array(
                'id' => 'complete_exchanges',
                'title' => 'Hediyeleşme yap',
                'description' => 'Bir hediyeleşme tamamlayın',
                'reward_bilet' => 4,
                'reward_level' => 1,
                'max_progress' => 5,
            ),
            'invite_friends' => array(
                'id' => 'invite_friends',
                'title' => 'Davet et',
                'description' => 'Bir arkadaş davet edin',
                'reward_bilet' => 2,
                'reward_level' => 0,
                'max_progress' => 5,
            ),
        );
        // Save defaults to options only if $save_if_empty is true (frontend usage)
        if ($save_if_empty) {
            update_option('hdh_daily_tasks', $tasks);
        }
    }
    
    return $tasks;
}

/**
 * Get user's one-time tasks with progress
 * Uses new progress table system
 */
function hdh_get_user_one_time_tasks($user_id) {
    if (!$user_id) return array();
    
    // Get config without auto-saving (to prevent overwriting admin changes)
    $config = hdh_get_one_time_tasks_config(false);
    $tasks = array();
    
    foreach ($config as $task_id => $task_config) {
        $period_key = 'lifetime';
        
        // Get progress from new table
        $progress_data = hdh_get_task_progress($user_id, $task_id, $period_key);
        
        $completed_count = $progress_data ? (int) $progress_data['completed_count'] : 0;
        $claimed_count = $progress_data ? (int) $progress_data['claimed_count'] : 0;
        
        // Fallback: Check task-specific completion if no progress record exists
        if ($completed_count === 0) {
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
                    $existing_progress = hdh_get_task_progress($user_id, 'complete_first_exchange', 'lifetime');
                    $is_completed = $existing_progress && (int) $existing_progress['completed_count'] > 0;
                    break;
                case 'invite_friend':
                    // Placeholder - will be implemented later
                    $is_completed = false;
                    break;
            }
            
            if ($is_completed) {
                $completed_count = $task_config['max_progress'];
            }
        }
        
        // Calculate claimable count
        $claimable_count = max(0, $completed_count - $claimed_count);
        
        // Determine CTA state
        $cta_state = 'locked';
        if ($completed_count >= $task_config['max_progress']) {
            if ($claimable_count > 0) {
                $cta_state = 'claim';
            } elseif ($claimed_count > 0) {
                $cta_state = 'done';
            } else {
                $cta_state = 'claim'; // Should not happen, but safety check
            }
        } elseif ($completed_count > 0) {
            $cta_state = 'in_progress';
        }
        
        // For one-time tasks: if reward is fully claimed, don't show in UI (task disappears)
        // Show task only if:
        // 1. Task is not yet completed, OR
        // 2. Task is completed but reward is not yet fully claimed (claimable_count > 0)
        $is_fully_claimed = ($completed_count >= $task_config['max_progress']) && ($claimed_count >= $completed_count);
        
        if (!$is_fully_claimed) {
            $task_data = array(
                'id' => $task_id,
                'title' => $task_config['title'],
                'description' => $task_config['description'],
                'reward_bilet' => $task_config['reward_bilet'],
                'reward_level' => $task_config['reward_level'],
                'progress' => $completed_count,
                'max_progress' => $task_config['max_progress'],
                'completed' => $completed_count >= $task_config['max_progress'],
                'claimed' => $claimed_count > 0,
                'claimed_count' => $claimed_count,
                'claimable_count' => $claimable_count,
                'can_claim' => $claimable_count > 0,
                'cta_state' => $cta_state,
            );
            
            // Add referral link for invite_friend task (one-time)
            if ($task_id === 'invite_friend' && function_exists('hdh_get_referral_link')) {
                $task_data['referral_link'] = hdh_get_referral_link($user_id);
            }
            
            $tasks[] = $task_data;
        }
    }
    
    return $tasks;
}

/**
 * Get user's daily tasks with progress
 * Uses new progress table system
 */
function hdh_get_user_daily_tasks($user_id) {
    if (!$user_id) return array();
    
    // Reset daily tasks if needed (for old user_meta system compatibility)
    $today = date('Y-m-d');
    $last_reset = get_user_meta($user_id, 'hdh_daily_tasks_reset_date', true);
    if ($last_reset !== $today) {
        hdh_reset_daily_tasks($user_id);
    }
    
    // Get config without auto-saving (to prevent overwriting admin changes)
    $config = hdh_get_daily_tasks_config(false);
    $tasks = array();
    
    foreach ($config as $task_id => $task_config) {
        $period_key = $today;
        
        // Get progress from new table
        $progress_data = hdh_get_task_progress($user_id, $task_id, $period_key);
        
        $completed_count = $progress_data ? (int) $progress_data['completed_count'] : 0;
        $claimed_count = $progress_data ? (int) $progress_data['claimed_count'] : 0;
        
        // Fallback: Check task-specific progress if no progress record exists
        // This is for backward compatibility during migration
        if ($completed_count === 0) {
            $old_progress_key = 'hdh_daily_task_progress_' . $task_id;
            $old_progress = (int) get_user_meta($user_id, $old_progress_key, true);
            if ($old_progress > 0) {
                $completed_count = $old_progress;
            }
        }
        
        // Calculate claimable count
        $claimable_count = max(0, $completed_count - $claimed_count);
        
        // Check if daily task is unlocked (one-time task must be completed first)
        $is_locked = false;
        $unlock_task_id = null;
        
        switch ($task_id) {
            case 'create_listings':
                $unlock_task_id = 'create_first_listing';
                break;
            case 'complete_exchanges':
                $unlock_task_id = 'complete_first_exchange';
                break;
            case 'invite_friends':
                $unlock_task_id = 'invite_friend';
                break;
        }
        
        if ($unlock_task_id) {
            $one_time_progress = hdh_get_task_progress($user_id, $unlock_task_id, 'lifetime');
            $one_time_completed = $one_time_progress && (int) $one_time_progress['completed_count'] > 0;
            $is_locked = !$one_time_completed;
        }
        
        // Determine CTA state
        $cta_state = 'locked';
        if ($is_locked) {
            // Task is locked because one-time task is not completed
            $cta_state = 'locked';
        } elseif ($completed_count > 0) {
            if ($claimable_count > 0) {
                $cta_state = 'claim';
            } elseif ($completed_count >= $task_config['max_progress']) {
                $cta_state = 'done';
            } else {
                $cta_state = 'in_progress';
            }
        }
        
        $task_data = array(
            'id' => $task_id,
            'title' => $task_config['title'],
            'description' => $task_config['description'],
            'reward_bilet' => $task_config['reward_bilet'],
            'reward_level' => $task_config['reward_level'],
            'progress' => $is_locked ? 0 : $completed_count, // Show 0 progress if locked
            'max_progress' => $task_config['max_progress'],
            'completed' => $completed_count >= $task_config['max_progress'],
            'can_claim' => !$is_locked && $claimable_count > 0, // Cannot claim if locked
            'claimed_count' => $claimed_count,
            'claimable_count' => $is_locked ? 0 : $claimable_count, // No claimable if locked
            'cta_state' => $cta_state,
            'is_locked' => $is_locked,
            'unlock_task_id' => $unlock_task_id,
        );
        
        // Add referral link for invite_friends task (daily)
        if ($task_id === 'invite_friends' && function_exists('hdh_get_referral_link')) {
            $task_data['referral_link'] = hdh_get_referral_link($user_id);
        }
        
        $tasks[] = $task_data;
    }
    
    return $tasks;
}

/**
 * Reset daily tasks (called at midnight)
 */
function hdh_reset_daily_tasks($user_id) {
    if (!$user_id) return;
    
    $config = hdh_get_daily_tasks_config(false);
    
    foreach ($config as $task_id => $task_config) {
        delete_user_meta($user_id, 'hdh_daily_task_progress_' . $task_id);
        delete_user_meta($user_id, 'hdh_daily_task_claimed_' . $task_id); // This stores claimed_progress (number, not boolean)
    }
    
    update_user_meta($user_id, 'hdh_daily_tasks_reset_date', date('Y-m-d'));
    
    if (function_exists('hdh_log_event')) {
        hdh_log_event($user_id, 'daily_tasks_reset', array());
    }
}

/**
 * Claim task reward
 * Wrapper function that calls the atomic claim engine
 * 
 * @deprecated The actual implementation is in hdh_claim_task_reward_atomic()
 * This function is kept for backward compatibility and redirects to the atomic version
 */
function hdh_claim_task_reward($user_id, $task_id, $is_daily = false) {
    // Use atomic claim engine if available
    if (function_exists('hdh_claim_task_reward_atomic')) {
        return hdh_claim_task_reward_atomic($user_id, $task_id, $is_daily);
    }
    
    // Fallback to old implementation (should not happen)
    global $wpdb;
    if (!$user_id || !$task_id) {
        return new WP_Error('invalid_params', hdh_get_message('ajax', 'invalid_parameters', 'Geçersiz parametreler'));
    }
    
    $config = $is_daily ? hdh_get_daily_tasks_config(false) : hdh_get_one_time_tasks_config(false);
    
    if (!isset($config[$task_id])) {
        return new WP_Error('invalid_task', hdh_get_message('ajax', 'invalid_task', 'Geçersiz görev'));
    }
    
    $task_config = $config[$task_id];
    $claimed_key = $is_daily ? 'hdh_daily_task_claimed_' . $task_id : 'hdh_task_claimed_' . $task_id;
    $progress_key = $is_daily ? 'hdh_daily_task_progress_' . $task_id : 'hdh_task_progress_' . $task_id;
    
    // Get current progress - recalculate from actual data to ensure accuracy
    if ($is_daily) {
        // For daily tasks, get fresh progress from hdh_get_user_daily_tasks
        // This ensures we have the latest progress before claiming
        $daily_tasks = hdh_get_user_daily_tasks($user_id);
        $progress = 0;
        foreach ($daily_tasks as $task) {
            if ($task['id'] === $task_id) {
                $progress = $task['progress'];
                break;
            }
        }
        // Fallback to meta if not found in tasks
        if ($progress === 0) {
            $progress = (int) get_user_meta($user_id, $progress_key, true);
        }
    } else {
        // For one-time tasks, get from meta or check completion status
        $progress = (int) get_user_meta($user_id, $progress_key, true);
        
        // Check task-specific completion
        switch ($task_id) {
            case 'verify_email':
                if ((bool) get_user_meta($user_id, 'hdh_email_verified', true)) {
                    $progress = $task_config['max_progress'];
                }
                break;
            case 'create_first_listing':
                $listings = get_posts(array(
                    'post_type' => 'hayday_trade',
                    'author' => $user_id,
                    'posts_per_page' => 1,
                    'fields' => 'ids',
                ));
                if (!empty($listings)) {
                    $progress = $task_config['max_progress'];
                }
                break;
            case 'complete_first_exchange':
                $completed = (int) get_user_meta($user_id, 'hdh_completed_exchanges', true);
                if ($completed > 0) {
                    $progress = $task_config['max_progress'];
                }
                break;
        }
    }
    
    // For daily tasks: allow claiming rewards for each progress milestone
    if ($is_daily) {
        // Get current progress and claimed progress
        $claimed_progress = (int) get_user_meta($user_id, $claimed_key, true);
        
        // Check if there are unclaimed progress milestones
        if ($progress <= $claimed_progress) {
            return new WP_Error('already_claimed', hdh_get_message('ajax', 'already_claimed', 'Bu görevin ödülü zaten alınmış'));
        }
        
        // Award reward for only ONE milestone at a time
        // Each click on "Ödülünü Al" awards only the next milestone
        // Example: progress = 3, claimed_progress = 1 → award for milestone 2 only (not 2 and 3)
        $claimable_milestones = 1; // Always award for 1 milestone only
        $new_claimed_progress = $claimed_progress + 1; // Increment by 1
        
        // Update claimed progress (increment by 1, not set to current progress)
        update_user_meta($user_id, $claimed_key, $new_claimed_progress);
        
        // Award bilet (for 1 milestone only)
        $total_bilet = 0;
        if ($task_config['reward_bilet'] > 0) {
            $total_bilet = $task_config['reward_bilet']; // No multiplication, just the base reward
            if (function_exists('hdh_add_bilet')) {
                // Generate unique transaction ID for this specific reward claim
                $transaction_id = 'task_' . $task_id . '_' . ($is_daily ? 'daily' : 'onetime') . '_' . $user_id . '_' . $new_claimed_progress . '_' . current_time('timestamp');
                
                $result = hdh_add_bilet($user_id, $total_bilet, 'task_reward', array(
                    'task_id' => $task_id,
                    'is_daily' => $is_daily,
                    'milestones' => $claimable_milestones,
                    'progress' => $progress,
                    'claimed_progress' => $claimed_progress,
                    'new_claimed_progress' => $new_claimed_progress,
                    'transaction_id' => $transaction_id,
                ));
                
                // If bilet addition failed (duplicate transaction), revert claimed progress
                if (is_wp_error($result)) {
                    // Revert claimed progress update
                    update_user_meta($user_id, $claimed_key, $claimed_progress);
                    return $result;
                }
            }
        }
        
        // Award level (XP) (for 1 milestone only)
        $total_level = 0;
        $old_level = 0;
        if ($task_config['reward_level'] > 0) {
            // Get current level before adding XP
            $user_state_before = function_exists('hdh_get_user_state') ? hdh_get_user_state($user_id) : null;
            $old_level = $user_state_before ? $user_state_before['level'] : 1;
            
            // Award XP for 1 milestone only
            if (function_exists('hdh_add_xp')) {
                // Convert level to XP using configurable XP per level
                // 1 level reward = xp_per_level XP (default: 100 XP)
                $xp_per_level = function_exists('hdh_get_xp_per_level') ? hdh_get_xp_per_level() : 100;
                $xp_amount = $task_config['reward_level'] * $xp_per_level;
                hdh_add_xp($user_id, $xp_amount, 'task_reward', array(
                    'task_id' => $task_id,
                    'is_daily' => $is_daily,
                    'milestones' => $claimable_milestones,
                    'progress' => $progress,
                    'claimed_progress' => $claimed_progress,
                    'new_claimed_progress' => $new_claimed_progress,
                ));
            }
            
            // Get new level after adding XP
            $user_state_after = function_exists('hdh_get_user_state') ? hdh_get_user_state($user_id) : null;
            $new_level = $user_state_after ? $user_state_after['level'] : $old_level;
            $actual_level_gain = $new_level - $old_level;
            
            // Return actual level gain (not the XP reward amount)
            $total_level = $actual_level_gain;
        }
        
        // Log event
        if (function_exists('hdh_log_event')) {
            hdh_log_event($user_id, 'task_reward_claimed', array(
                'task_id' => $task_id,
                'is_daily' => $is_daily,
                'rewards' => array(
                    'bilet' => $total_bilet,
                    'level' => $total_level,
                ),
                'milestones' => $claimable_milestones,
                'progress' => $progress,
                'claimed_progress' => $claimed_progress,
                'new_claimed_progress' => $new_claimed_progress,
            ));
        }
        
        return array(
            'success' => true,
            'bilet' => $total_bilet,
            'level' => $total_level,
            'milestones' => $claimable_milestones,
        );
    } else {
        // For one-time tasks: check if task is completed
        if ($progress < $task_config['max_progress']) {
            return new WP_Error('not_completed', hdh_get_message('ajax', 'not_completed', 'Görev henüz tamamlanmamış'));
        }
        
        // Check if already claimed
        if (get_user_meta($user_id, $claimed_key, true)) {
            return new WP_Error('already_claimed', hdh_get_message('ajax', 'already_claimed', 'Bu görevin ödülü zaten alınmış'));
        }
        
        // Mark as claimed
        update_user_meta($user_id, $claimed_key, true);
        
        // Award bilet
        if ($task_config['reward_bilet'] > 0) {
            if (function_exists('hdh_add_bilet')) {
                // Generate unique transaction ID for this specific reward claim
                $transaction_id = 'task_' . $task_id . '_onetime_' . $user_id . '_' . current_time('timestamp');
                
                $result = hdh_add_bilet($user_id, $task_config['reward_bilet'], 'task_reward', array(
                    'task_id' => $task_id,
                    'is_daily' => $is_daily,
                    'transaction_id' => $transaction_id,
                ));
                
                // If bilet addition failed (duplicate transaction), revert claimed status
                if (is_wp_error($result)) {
                    // Revert claimed status
                    delete_user_meta($user_id, $claimed_key);
                    return $result;
                }
            }
        }
        
        // Award level (XP)
        $old_level = 0;
        $actual_level_gain = 0;
        if ($task_config['reward_level'] > 0) {
            // Get current level before adding XP
            $user_state_before = function_exists('hdh_get_user_state') ? hdh_get_user_state($user_id) : null;
            $old_level = $user_state_before ? $user_state_before['level'] : 1;
            
            if (function_exists('hdh_add_xp')) {
                // Convert level to XP using configurable XP per level
                // 1 level reward = xp_per_level XP (default: 100 XP)
                $xp_per_level = function_exists('hdh_get_xp_per_level') ? hdh_get_xp_per_level() : 100;
                $xp_amount = $task_config['reward_level'] * $xp_per_level;
                hdh_add_xp($user_id, $xp_amount, 'task_reward', array(
                    'task_id' => $task_id,
                    'is_daily' => $is_daily,
                ));
            }
            
            // Get new level after adding XP
            $user_state_after = function_exists('hdh_get_user_state') ? hdh_get_user_state($user_id) : null;
            $new_level = $user_state_after ? $user_state_after['level'] : $old_level;
            $actual_level_gain = $new_level - $old_level;
        }
        
        // Log event
        if (function_exists('hdh_log_event')) {
            hdh_log_event($user_id, 'task_reward_claimed', array(
                'task_id' => $task_id,
                'is_daily' => $is_daily,
                'rewards' => array(
                    'bilet' => $task_config['reward_bilet'],
                    'level' => $actual_level_gain,
                    'xp_reward' => (function_exists('hdh_get_xp_per_level') ? hdh_get_xp_per_level() : 100) * $task_config['reward_level'],
                ),
            ));
        }
        
        return array(
            'success' => true,
            'bilet' => $task_config['reward_bilet'],
            'level' => $actual_level_gain, // Return actual level gain, not XP reward
        );
    }
}

/**
 * Update task progress (for daily tasks that can be claimed multiple times)
 */
function hdh_update_daily_task_progress($user_id, $task_id, $increment = 1) {
    if (!$user_id || !$task_id) return false;
    
    $today = date('Y-m-d');
    $last_reset = get_user_meta($user_id, 'hdh_daily_tasks_reset_date', true);
    if ($last_reset !== $today) {
        hdh_reset_daily_tasks($user_id);
    }
    
    $progress_key = 'hdh_daily_task_progress_' . $task_id;
    $current = (int) get_user_meta($user_id, $progress_key, true);
    $config = hdh_get_daily_tasks_config(false);
    
    if (!isset($config[$task_id])) return false;
    
    $max_progress = $config[$task_id]['max_progress'];
    $new = min($max_progress, $current + $increment);
    
    update_user_meta($user_id, $progress_key, $new);
    
    // If task is completed, reset claimed status so user can claim again
    if ($new >= $max_progress) {
        $claimed_key = 'hdh_daily_task_claimed_' . $task_id;
        // For repeatable daily tasks, allow claiming again after progress resets
        // But we need to track partial claims - this is complex, so we'll handle it differently
        // For now, we'll allow claiming when max_progress is reached
    }
    
    return true;
}

/**
 * Track listing creation for daily task and one-time task
 * Uses new progress table system (NO AUTOMATIC REWARDS)
 */
function hdh_track_listing_creation($user_id, $listing_id) {
    if (!$user_id || !function_exists('hdh_increment_task_progress')) {
        return;
    }
    
    // Check if this is the first listing (one-time task)
    $listings = get_posts(array(
        'post_type' => 'hayday_trade',
        'author' => $user_id,
        'posts_per_page' => -1,
        'fields' => 'ids',
    ));
    
    // If this is the first listing, only increment one-time task progress
    // Daily task progress should NOT be incremented for the first listing
    if (count($listings) === 1) {
        // This is the first listing - only increment one-time task
        hdh_increment_task_progress($user_id, 'create_first_listing', 'lifetime');
        
        // Log task completion (progress only, no reward)
        if (function_exists('hdh_log_event')) {
            hdh_log_event($user_id, 'task_completed', array(
                'task_id' => 'create_first_listing',
                'reason' => 'first_listing_created',
                'note' => 'First listing - only one-time task progress incremented, daily task not incremented',
            ));
        }
    } else {
        // This is NOT the first listing - increment daily task progress only
        // But first check if daily task is unlocked (one-time task must be completed first)
        $one_time_progress = hdh_get_task_progress($user_id, 'create_first_listing', 'lifetime');
        $one_time_completed = $one_time_progress && (int) $one_time_progress['completed_count'] > 0;
        
        if ($one_time_completed) {
            // One-time task is completed, so daily task is unlocked - increment daily progress
            $today = date('Y-m-d');
            hdh_increment_task_progress($user_id, 'create_listings', $today);
            
            // Log task completion (progress only, no reward)
            if (function_exists('hdh_log_event')) {
                hdh_log_event($user_id, 'task_completed', array(
                    'task_id' => 'create_listings',
                    'reason' => 'listing_created',
                    'note' => 'Daily task progress incremented, reward must be claimed manually',
                ));
            }
        }
    }
}
add_action('hdh_listing_created', 'hdh_track_listing_creation', 10, 2);

/**
 * Track exchange completion for daily task and one-time task
 */
function hdh_track_exchange_completion($user_id, $trade_id) {
    if (!$user_id || !function_exists('hdh_increment_task_progress')) {
        return;
    }
    
    // Check if this is the first exchange (one-time task)
    // Get completed exchanges count from progress table
    $progress = hdh_get_task_progress($user_id, 'complete_first_exchange', 'lifetime');
    $completed_count = $progress ? (int) $progress['completed_count'] : 0;
    
    // If this is the first exchange, only increment one-time task progress
    // Daily task progress should NOT be incremented for the first exchange
    if ($completed_count === 0) {
        // This is the first exchange - only increment one-time task
        hdh_increment_task_progress($user_id, 'complete_first_exchange', 'lifetime');
        
        // Log task completion (progress only, no reward)
        if (function_exists('hdh_log_event')) {
            hdh_log_event($user_id, 'task_completed', array(
                'task_id' => 'complete_first_exchange',
                'reason' => 'first_exchange_completed',
                'note' => 'First exchange - only one-time task progress incremented, daily task not incremented',
            ));
        }
    } else {
        // This is NOT the first exchange - increment daily task progress only
        // But first check if daily task is unlocked (one-time task must be completed first)
        $one_time_progress = hdh_get_task_progress($user_id, 'complete_first_exchange', 'lifetime');
        $one_time_completed = $one_time_progress && (int) $one_time_progress['completed_count'] > 0;
        
        if ($one_time_completed) {
            // One-time task is completed, so daily task is unlocked - increment daily progress
            $today = date('Y-m-d');
            hdh_increment_task_progress($user_id, 'complete_exchanges', $today);
            
            // Log task completion (progress only, no reward)
            if (function_exists('hdh_log_event')) {
                hdh_log_event($user_id, 'task_completed', array(
                    'task_id' => 'complete_exchanges',
                    'reason' => 'exchange_completed',
                    'note' => 'Daily task progress incremented, reward must be claimed manually',
                ));
            }
        }
    }
}
add_action('hdh_exchange_completed', 'hdh_track_exchange_completion', 10, 2);

/**
 * Schedule daily task reset (midnight Turkey time)
 */
function hdh_schedule_daily_tasks_reset() {
    if (!wp_next_scheduled('hdh_reset_all_daily_tasks')) {
        $turkey_tz = new DateTimeZone('Europe/Istanbul');
        $midnight = new DateTime('tomorrow midnight', $turkey_tz);
        wp_schedule_event($midnight->getTimestamp(), 'daily', 'hdh_reset_all_daily_tasks');
    }
}
add_action('init', 'hdh_schedule_daily_tasks_reset');

/**
 * Reset all users' daily tasks
 */
function hdh_reset_all_daily_tasks() {
    $users = get_users(array('fields' => 'ID'));
    foreach ($users as $user_id) {
        hdh_reset_daily_tasks($user_id);
    }
}
add_action('hdh_reset_all_daily_tasks', 'hdh_reset_all_daily_tasks');

