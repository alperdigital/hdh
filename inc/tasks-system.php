<?php
/**
 * HDH: Tasks System - One-time and Daily Tasks
 * Manages task progress, completion, and reward claiming
 */

if (!defined('ABSPATH')) exit;

/**
 * Get one-time tasks configuration
 */
function hdh_get_one_time_tasks_config() {
    return array(
        'verify_email' => array(
            'id' => 'verify_email',
            'title' => 'Mail Adresini Doğrula',
            'description' => 'E-posta adresinizi doğrulayın',
            'reward_bilet' => 1,
            'reward_level' => 1,
            'max_progress' => 1,
        ),
        'create_first_listing' => array(
            'id' => 'create_first_listing',
            'title' => 'İlk İlanını Oluştur',
            'description' => 'İlk ilanınızı oluşturun',
            'reward_bilet' => 2,
            'reward_level' => 1,
            'max_progress' => 1,
        ),
        'complete_first_exchange' => array(
            'id' => 'complete_first_exchange',
            'title' => 'İlk Hediyeleşmeni Tamamla',
            'description' => 'İlk hediyeleşmenizi tamamlayın',
            'reward_bilet' => 5,
            'reward_level' => 2,
            'max_progress' => 1,
        ),
        'invite_friend' => array(
            'id' => 'invite_friend',
            'title' => 'Arkadaşını Davet Et',
            'description' => 'Bir arkadaşınızı davet edin',
            'reward_bilet' => 2,
            'reward_level' => 1,
            'max_progress' => 1,
        ),
        'friend_exchange' => array(
            'id' => 'friend_exchange',
            'title' => 'Arkadaşın Hediyeleşsin',
            'description' => 'Davet ettiğiniz arkadaşınız hediyeleşme yapsın',
            'reward_bilet' => 5,
            'reward_level' => 2,
            'max_progress' => 1,
        ),
    );
}

/**
 * Get daily tasks configuration
 */
function hdh_get_daily_tasks_config() {
    return array(
        'create_listings' => array(
            'id' => 'create_listings',
            'title' => 'İlan Oluştur',
            'description' => 'Günde 3 ilan oluşturun',
            'reward_bilet' => 1,
            'reward_level' => 0,
            'max_progress' => 3,
        ),
        'complete_exchanges' => array(
            'id' => 'complete_exchanges',
            'title' => 'Hediyeleşme Tamamla',
            'description' => 'Günde 5 hediyeleşme tamamlayın',
            'reward_bilet' => 4,
            'reward_level' => 1,
            'max_progress' => 5,
        ),
        'invite_friends' => array(
            'id' => 'invite_friends',
            'title' => 'Arkadaşını Davet Et',
            'description' => 'Günde 5 arkadaş davet edin',
            'reward_bilet' => 2,
            'reward_level' => 0,
            'max_progress' => 5,
        ),
        'friend_exchanges' => array(
            'id' => 'friend_exchanges',
            'title' => 'Arkadaşın Hediyeleşsin',
            'description' => 'Davet ettiğiniz arkadaşlarınız hediyeleşme yapsın',
            'reward_bilet' => 5,
            'reward_level' => 2,
            'max_progress' => 1,
        ),
    );
}

/**
 * Get user's one-time tasks with progress
 */
function hdh_get_user_one_time_tasks($user_id) {
    if (!$user_id) return array();
    
    $config = hdh_get_one_time_tasks_config();
    $tasks = array();
    
    foreach ($config as $task_id => $task_config) {
        $progress_key = 'hdh_task_progress_' . $task_id;
        $claimed_key = 'hdh_task_claimed_' . $task_id;
        
        $progress = (int) get_user_meta($user_id, $progress_key, true);
        $claimed = (bool) get_user_meta($user_id, $claimed_key, true);
        
        // Check task-specific completion
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
                $completed = (int) get_user_meta($user_id, 'hdh_completed_exchanges', true);
                $is_completed = $completed > 0;
                break;
            case 'invite_friend':
            case 'friend_exchange':
                // Placeholder - will be implemented later
                $is_completed = false;
                break;
        }
        
        if ($is_completed) {
            $progress = $task_config['max_progress'];
        }
        
        $tasks[] = array(
            'id' => $task_id,
            'title' => $task_config['title'],
            'description' => $task_config['description'],
            'reward_bilet' => $task_config['reward_bilet'],
            'reward_level' => $task_config['reward_level'],
            'progress' => $progress,
            'max_progress' => $task_config['max_progress'],
            'completed' => $progress >= $task_config['max_progress'],
            'claimed' => $claimed,
            'can_claim' => ($progress >= $task_config['max_progress']) && !$claimed,
        );
    }
    
    return $tasks;
}

/**
 * Get user's daily tasks with progress
 */
function hdh_get_user_daily_tasks($user_id) {
    if (!$user_id) return array();
    
    // Reset daily tasks if needed
    $today = date('Y-m-d');
    $last_reset = get_user_meta($user_id, 'hdh_daily_tasks_reset_date', true);
    if ($last_reset !== $today) {
        hdh_reset_daily_tasks($user_id);
    }
    
    $config = hdh_get_daily_tasks_config();
    $tasks = array();
    
    foreach ($config as $task_id => $task_config) {
        $progress_key = 'hdh_daily_task_progress_' . $task_id;
        $claimed_key = 'hdh_daily_task_claimed_' . $task_id;
        
        $progress = (int) get_user_meta($user_id, $progress_key, true);
        $claimed = (bool) get_user_meta($user_id, $claimed_key, true);
        
        // Check task-specific progress
        switch ($task_id) {
            case 'create_listings':
                $today_start = strtotime('today');
                $today_end = strtotime('tomorrow') - 1;
                $today_listings = new WP_Query(array(
                    'post_type' => 'hayday_trade',
                    'author' => $user_id,
                    'post_status' => 'publish',
                    'date_query' => array(array(
                        'after' => date('Y-m-d H:i:s', $today_start),
                        'before' => date('Y-m-d H:i:s', $today_end),
                    )),
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                ));
                $progress = min($task_config['max_progress'], $today_listings->found_posts);
                wp_reset_postdata();
                update_user_meta($user_id, $progress_key, $progress);
                break;
            case 'complete_exchanges':
                $today_start = strtotime('today');
                $today_end = strtotime('tomorrow') - 1;
                $transactions = function_exists('hdh_get_jeton_transactions') ? hdh_get_jeton_transactions($user_id, 100) : array();
                $count = 0;
                foreach ($transactions as $transaction) {
                    if (isset($transaction['reason']) && $transaction['reason'] === 'completed_exchange') {
                        $timestamp = strtotime($transaction['timestamp']);
                        if ($timestamp >= $today_start && $timestamp <= $today_end) {
                            $count++;
                        }
                    }
                }
                $progress = min($task_config['max_progress'], $count);
                update_user_meta($user_id, $progress_key, $progress);
                break;
            case 'invite_friends':
            case 'friend_exchanges':
                // Placeholder - will be implemented later
                $progress = 0;
                break;
        }
        
        $tasks[] = array(
            'id' => $task_id,
            'title' => $task_config['title'],
            'description' => $task_config['description'],
            'reward_bilet' => $task_config['reward_bilet'],
            'reward_level' => $task_config['reward_level'],
            'progress' => $progress,
            'max_progress' => $task_config['max_progress'],
            'completed' => $progress >= $task_config['max_progress'],
            'claimed' => $claimed,
            'can_claim' => ($progress >= $task_config['max_progress']) && !$claimed,
        );
    }
    
    return $tasks;
}

/**
 * Reset daily tasks (called at midnight)
 */
function hdh_reset_daily_tasks($user_id) {
    if (!$user_id) return;
    
    $config = hdh_get_daily_tasks_config();
    
    foreach ($config as $task_id => $task_config) {
        delete_user_meta($user_id, 'hdh_daily_task_progress_' . $task_id);
        delete_user_meta($user_id, 'hdh_daily_task_claimed_' . $task_id);
    }
    
    update_user_meta($user_id, 'hdh_daily_tasks_reset_date', date('Y-m-d'));
    
    if (function_exists('hdh_log_event')) {
        hdh_log_event($user_id, 'daily_tasks_reset', array());
    }
}

/**
 * Claim task reward
 */
function hdh_claim_task_reward($user_id, $task_id, $is_daily = false) {
    if (!$user_id || !$task_id) {
        return new WP_Error('invalid_params', 'Geçersiz parametreler');
    }
    
    $config = $is_daily ? hdh_get_daily_tasks_config() : hdh_get_one_time_tasks_config();
    
    if (!isset($config[$task_id])) {
        return new WP_Error('invalid_task', 'Geçersiz görev');
    }
    
    $task_config = $config[$task_id];
    $claimed_key = $is_daily ? 'hdh_daily_task_claimed_' . $task_id : 'hdh_task_claimed_' . $task_id;
    $progress_key = $is_daily ? 'hdh_daily_task_progress_' . $task_id : 'hdh_task_progress_' . $task_id;
    
    // Check if task is completed
    $progress = (int) get_user_meta($user_id, $progress_key, true);
    if ($progress < $task_config['max_progress']) {
        return new WP_Error('not_completed', 'Görev henüz tamamlanmamış');
    }
    
    // For daily tasks, allow multiple claims (every max_progress)
    if ($is_daily) {
        // Calculate how many times the task has been completed
        $completed_times = floor($progress / $task_config['max_progress']);
        
        // Get last claimed progress
        $last_claimed_progress = (int) get_user_meta($user_id, $claimed_key, true);
        $last_claimed_times = $last_claimed_progress > 0 ? floor($last_claimed_progress / $task_config['max_progress']) : 0;
        
        // Check if there are new completions to claim
        if ($completed_times <= $last_claimed_times) {
            return new WP_Error('already_claimed', 'Bu görevin ödülü zaten alınmış');
        }
        
        // Calculate reward multiplier (how many times to award)
        $reward_multiplier = $completed_times - $last_claimed_times;
        
        // Update last claimed progress
        update_user_meta($user_id, $claimed_key, $completed_times * $task_config['max_progress']);
        
        // Award bilet (multiplied by reward_multiplier)
        if ($task_config['reward_bilet'] > 0) {
            if (function_exists('hdh_add_bilet')) {
                hdh_add_bilet($user_id, $task_config['reward_bilet'] * $reward_multiplier, 'task_reward', array(
                    'task_id' => $task_id,
                    'is_daily' => $is_daily,
                    'multiplier' => $reward_multiplier,
                ));
            }
        }
        
        // Award level (XP) (multiplied by reward_multiplier)
        if ($task_config['reward_level'] > 0) {
            if (function_exists('hdh_add_xp')) {
                // Convert level to XP (1 level = 100 XP)
                $xp_amount = $task_config['reward_level'] * 100 * $reward_multiplier;
                hdh_add_xp($user_id, $xp_amount, 'task_reward', array(
                    'task_id' => $task_id,
                    'is_daily' => $is_daily,
                    'multiplier' => $reward_multiplier,
                ));
            }
        }
        
        // Log event
        if (function_exists('hdh_log_event')) {
            hdh_log_event($user_id, 'task_reward_claimed', array(
                'task_id' => $task_id,
                'is_daily' => $is_daily,
                'rewards' => array(
                    'bilet' => $task_config['reward_bilet'] * $reward_multiplier,
                    'level' => $task_config['reward_level'] * $reward_multiplier,
                ),
                'multiplier' => $reward_multiplier,
            ));
        }
        
        return array(
            'success' => true,
            'bilet' => $task_config['reward_bilet'] * $reward_multiplier,
            'level' => $task_config['reward_level'] * $reward_multiplier,
        );
    } else {
        // For one-time tasks, check if already claimed
        if (get_user_meta($user_id, $claimed_key, true)) {
            return new WP_Error('already_claimed', 'Bu görevin ödülü zaten alınmış');
        }
        
        // Mark as claimed
        update_user_meta($user_id, $claimed_key, true);
        
        // Award bilet
        if ($task_config['reward_bilet'] > 0) {
            if (function_exists('hdh_add_bilet')) {
                hdh_add_bilet($user_id, $task_config['reward_bilet'], 'task_reward', array(
                    'task_id' => $task_id,
                    'is_daily' => $is_daily,
                ));
            }
        }
        
        // Award level (XP)
        if ($task_config['reward_level'] > 0) {
            if (function_exists('hdh_add_xp')) {
                // Convert level to XP (1 level = 100 XP)
                $xp_amount = $task_config['reward_level'] * 100;
                hdh_add_xp($user_id, $xp_amount, 'task_reward', array(
                    'task_id' => $task_id,
                    'is_daily' => $is_daily,
                ));
            }
        }
        
        // Log event
        if (function_exists('hdh_log_event')) {
            hdh_log_event($user_id, 'task_reward_claimed', array(
                'task_id' => $task_id,
                'is_daily' => $is_daily,
                'rewards' => array(
                    'bilet' => $task_config['reward_bilet'],
                    'level' => $task_config['reward_level'],
                ),
            ));
        }
        
        return array(
            'success' => true,
            'bilet' => $task_config['reward_bilet'],
            'level' => $task_config['reward_level'],
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
    $config = hdh_get_daily_tasks_config();
    
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
 * Track listing creation for daily task
 */
function hdh_track_listing_creation($user_id, $listing_id) {
    // Update daily task progress
    hdh_update_daily_task_progress($user_id, 'create_listings', 1);
}
add_action('hdh_listing_created', 'hdh_track_listing_creation', 10, 2);

/**
 * Track exchange completion for daily task
 */
function hdh_track_exchange_completion($user_id, $trade_id) {
    // Update daily task progress
    hdh_update_daily_task_progress($user_id, 'complete_exchanges', 1);
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

