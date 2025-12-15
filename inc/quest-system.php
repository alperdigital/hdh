<?php
/**
 * HDH: Quest System - Main & Side Quests
 */

if (!defined('ABSPATH')) exit;

/**
 * Get main quests (always visible, auto-tracked)
 */
function hdh_get_main_quests($user_id) {
    $quests = array(
        array(
            'id' => 'create_listing',
            'title' => 'İlan Oluştur',
            'description' => 'İlk ilanınızı oluşturun',
            'reward_tickets' => 2,
            'reward_xp' => 10,
            'progress' => 0,
            'max_progress' => 1,
            'completed' => false,
        ),
        array(
            'id' => 'complete_exchange',
            'title' => 'Takas Tamamla',
            'description' => 'İlk takasınızı tamamlayın',
            'reward_tickets' => 5,
            'reward_xp' => 50,
            'progress' => 0,
            'max_progress' => 1,
            'completed' => false,
        ),
    );
    
    // Load progress from user meta
    foreach ($quests as &$quest) {
        $progress_key = 'hdh_quest_progress_' . $quest['id'];
        $completed_key = 'hdh_quest_completed_' . $quest['id'];
        
        $quest['progress'] = (int) get_user_meta($user_id, $progress_key, true);
        $quest['completed'] = (bool) get_user_meta($user_id, $completed_key, true);
    }
    
    return $quests;
}

/**
 * Get side quests (daily, optional)
 */
function hdh_get_side_quests($user_id) {
    $today = date('Y-m-d');
    $last_reset = get_user_meta($user_id, 'hdh_quest_reset_date', true);
    
    if ($last_reset !== $today) {
        hdh_reset_daily_quests($user_id);
    }
    
    $quests = array(
        array(
            'id' => 'daily_ticket',
            'title' => 'Günlük Bilet',
            'description' => 'Günlük biletinizi alın',
            'reward_tickets' => 1,
            'reward_xp' => 5,
            'progress' => 0,
            'max_progress' => 1,
            'completed' => false,
        ),
        array(
            'id' => 'rate_exchanges',
            'title' => 'Değerlendirme Yap',
            'description' => '3 takası değerlendirin',
            'reward_tickets' => 2,
            'reward_xp' => 15,
            'progress' => 0,
            'max_progress' => 3,
            'completed' => false,
        ),
        array(
            'id' => 'share_listing',
            'title' => 'İlan Paylaş',
            'description' => 'Bir ilanı paylaşın',
            'reward_tickets' => 1,
            'reward_xp' => 10,
            'progress' => 0,
            'max_progress' => 1,
            'completed' => false,
        ),
    );
    
    // Load progress from user meta
    foreach ($quests as &$quest) {
        $progress_key = 'hdh_quest_progress_' . $quest['id'];
        $completed_key = 'hdh_quest_completed_' . $quest['id'];
        
        $quest['progress'] = (int) get_user_meta($user_id, $progress_key, true);
        $quest['completed'] = (bool) get_user_meta($user_id, $completed_key, true);
    }
    
    return $quests;
}

/**
 * Update quest progress
 */
function hdh_update_quest_progress($user_id, $quest_id, $increment = 1) {
    if (!$user_id || !$quest_id) return false;
    
    $progress_key = 'hdh_quest_progress_' . $quest_id;
    $current = (int) get_user_meta($user_id, $progress_key, true);
    $new = $current + $increment;
    
    update_user_meta($user_id, $progress_key, $new);
    
    // Check completion
    $main_quests = hdh_get_main_quests($user_id);
    $side_quests = hdh_get_side_quests($user_id);
    $all_quests = array_merge($main_quests, $side_quests);
    
    foreach ($all_quests as $quest) {
        if ($quest['id'] === $quest_id && $new >= $quest['max_progress']) {
            hdh_complete_quest($user_id, $quest_id, $quest);
            break;
        }
    }
    
    return true;
}

/**
 * Complete quest and award rewards
 */
function hdh_complete_quest($user_id, $quest_id, $quest_data) {
    $completed_key = 'hdh_quest_completed_' . $quest_id;
    
    // Check if already completed
    if (get_user_meta($user_id, $completed_key, true)) {
        return false;
    }
    
    // Mark as completed
    update_user_meta($user_id, $completed_key, true);
    
    // Award rewards
    if (isset($quest_data['reward_tickets']) && $quest_data['reward_tickets'] > 0) {
        if (function_exists('hdh_add_bilet')) {
            hdh_add_bilet($user_id, $quest_data['reward_tickets'], 'quest_completed', array('quest_id' => $quest_id));
        }
    }
    
    if (isset($quest_data['reward_xp']) && $quest_data['reward_xp'] > 0) {
        if (function_exists('hdh_add_xp')) {
            hdh_add_xp($user_id, $quest_data['reward_xp'], 'quest_completed', array('quest_id' => $quest_id));
        }
    }
    
    // Log event
    if (function_exists('hdh_log_event')) {
        hdh_log_event($user_id, 'quest_completed', array(
            'quest_id' => $quest_id,
            'rewards' => array(
                'tickets' => $quest_data['reward_tickets'] ?? 0,
                'xp' => $quest_data['reward_xp'] ?? 0,
            ),
        ));
    }
    
    return true;
}

/**
 * Reset daily quests (cron: midnight Turkey time)
 */
function hdh_reset_daily_quests($user_id) {
    $side_quests = array('daily_ticket', 'rate_exchanges', 'share_listing');
    
    foreach ($side_quests as $quest_id) {
        delete_user_meta($user_id, 'hdh_quest_progress_' . $quest_id);
        delete_user_meta($user_id, 'hdh_quest_completed_' . $quest_id);
    }
    
    update_user_meta($user_id, 'hdh_quest_reset_date', date('Y-m-d'));
    
    if (function_exists('hdh_log_event')) {
        hdh_log_event($user_id, 'daily_quests_reset', array());
    }
}

/**
 * Hook: Auto-track create listing quest
 */
function hdh_track_create_listing_quest($user_id, $listing_id) {
    hdh_update_quest_progress($user_id, 'create_listing', 1);
}
add_action('hdh_listing_created', 'hdh_track_create_listing_quest', 10, 2);

/**
 * Hook: Auto-track complete exchange quest
 */
function hdh_track_complete_exchange_quest($user_id, $trade_id) {
    hdh_update_quest_progress($user_id, 'complete_exchange', 1);
}
add_action('hdh_exchange_completed', 'hdh_track_complete_exchange_quest', 10, 2);

/**
 * Schedule daily quest reset (midnight Turkey time)
 */
function hdh_schedule_quest_reset() {
    if (!wp_next_scheduled('hdh_reset_all_daily_quests')) {
        $turkey_tz = new DateTimeZone('Europe/Istanbul');
        $midnight = new DateTime('tomorrow midnight', $turkey_tz);
        wp_schedule_event($midnight->getTimestamp(), 'daily', 'hdh_reset_all_daily_quests');
    }
}
add_action('init', 'hdh_schedule_quest_reset');

/**
 * Reset all users' daily quests
 */
function hdh_reset_all_daily_quests() {
    $users = get_users(array('fields' => 'ID'));
    foreach ($users as $user_id) {
        hdh_reset_daily_quests($user_id);
    }
}
add_action('hdh_reset_all_daily_quests', 'hdh_reset_all_daily_quests');

