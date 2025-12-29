<?php
/**
 * HDH: Referral System
 * Handles user referrals and tracking
 */

if (!defined('ABSPATH')) exit;

/**
 * Process referral after user registration
 * 
 * @param int $new_user_id The newly registered user ID
 * @param string $referral_username The username of the referrer
 * @return bool|WP_Error Success or error
 */
function hdh_process_referral($new_user_id, $referral_username) {
    global $wpdb;
    
    if (!$new_user_id || empty($referral_username)) {
        return false; // No referral, but not an error
    }
    
    // Ensure referrals table exists
    if (function_exists('hdh_create_referrals_table')) {
        hdh_create_referrals_table();
    }
    
    $referrals_table = $wpdb->prefix . 'hdh_referrals';
    
    // Get referrer user by username
    $referrer = get_user_by('login', $referral_username);
    if (!$referrer) {
        return new WP_Error('referrer_not_found', 'Referans kullanıcı bulunamadı');
    }
    
    $referrer_id = $referrer->ID;
    
    // Security check: User cannot refer themselves
    if ($referrer_id == $new_user_id) {
        return new WP_Error('self_referral', 'Kendinizi referans gösteremezsiniz');
    }
    
    // Check if this referral already exists (prevent duplicates)
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $referrals_table 
         WHERE referrer_id = %d AND referred_id = %d",
        $referrer_id,
        $new_user_id
    ));
    
    if ($existing) {
        return new WP_Error('duplicate_referral', 'Bu referans zaten kayıtlı');
    }
    
    // Insert referral record
    $result = $wpdb->insert(
        $referrals_table,
        array(
            'referrer_id' => $referrer_id,
            'referred_id' => $new_user_id,
            'created_at' => current_time('mysql')
        ),
        array('%d', '%d', '%s')
    );
    
    if ($result === false) {
        return new WP_Error('db_error', 'Referans kaydı oluşturulamadı');
    }
    
    // Increment invite_friend task progress for referrer
    if (function_exists('hdh_increment_task_progress')) {
        // Check if this is the first referral (one-time task)
        $progress = hdh_get_task_progress($referrer_id, 'invite_friend', 'lifetime');
        $completed_count = $progress ? (int) $progress['completed_count'] : 0;
        
        if ($completed_count === 0) {
            // This is the first referral - only increment one-time task
            hdh_increment_task_progress($referrer_id, 'invite_friend', 'lifetime');
            
            // Log task completion
            if (function_exists('hdh_log_event')) {
                hdh_log_event($referrer_id, 'task_completed', array(
                    'task_id' => 'invite_friend',
                    'reason' => 'first_referral_completed',
                    'note' => 'First referral - only one-time task progress incremented',
                ));
            }
        } else {
            // This is NOT the first referral - increment daily task progress only
            // But first check if daily task is unlocked (one-time task must be completed first)
            $one_time_progress = hdh_get_task_progress($referrer_id, 'invite_friend', 'lifetime');
            $one_time_completed = $one_time_progress && (int) $one_time_progress['completed_count'] > 0;
            
            if ($one_time_completed) {
                // One-time task is completed, so daily task is unlocked - increment daily progress
                $today = date('Y-m-d');
                hdh_increment_task_progress($referrer_id, 'invite_friends', $today);
                
                // Log task completion
                if (function_exists('hdh_log_event')) {
                    hdh_log_event($referrer_id, 'task_completed', array(
                        'task_id' => 'invite_friends',
                        'reason' => 'referral_completed',
                        'note' => 'Daily task progress incremented, reward must be claimed manually',
                    ));
                }
            }
        }
    }
    
    return true;
}

/**
 * Get referral link for a user
 * 
 * @param int $user_id User ID
 * @return string Referral link
 */
function hdh_get_referral_link($user_id) {
    if (!$user_id) {
        return '';
    }
    
    $user = get_userdata($user_id);
    if (!$user) {
        return '';
    }
    
    $username = $user->user_login;
    $register_url = home_url('/profil');
    
    return add_query_arg('ref', $username, $register_url);
}

/**
 * Get user's referral count
 * 
 * @param int $user_id User ID
 * @return int Number of referrals
 */
function hdh_get_referral_count($user_id) {
    global $wpdb;
    
    if (!$user_id) {
        return 0;
    }
    
    $referrals_table = $wpdb->prefix . 'hdh_referrals';
    
    // Ensure table exists
    if (function_exists('hdh_create_referrals_table')) {
        hdh_create_referrals_table();
    }
    
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $referrals_table WHERE referrer_id = %d",
        $user_id
    ));
    
    return (int) $count;
}

