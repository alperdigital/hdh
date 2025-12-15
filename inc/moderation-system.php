<?php
/**
 * HDH: Moderation System - Reports & Disputes
 */

if (!defined('ABSPATH')) exit;

/**
 * Register hayday_report CPT
 */
function hdh_register_report_cpt() {
    register_post_type('hayday_report', array(
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-flag',
        'menu_position' => 25,
        'capability_type' => 'post',
        'capabilities' => array(
            'create_posts' => 'manage_options',
        ),
        'map_meta_cap' => true,
        'supports' => array('title', 'editor'),
        'labels' => array(
            'name' => 'Reports',
            'singular_name' => 'Report',
            'add_new' => 'Add Report',
            'add_new_item' => 'Add New Report',
            'edit_item' => 'Edit Report',
            'new_item' => 'New Report',
            'view_item' => 'View Report',
            'search_items' => 'Search Reports',
            'not_found' => 'No reports found',
            'not_found_in_trash' => 'No reports found in trash',
        ),
    ));
}
add_action('init', 'hdh_register_report_cpt');

/**
 * Register hayday_dispute CPT
 */
function hdh_register_dispute_cpt() {
    register_post_type('hayday_dispute', array(
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-warning',
        'menu_position' => 26,
        'capability_type' => 'post',
        'capabilities' => array(
            'create_posts' => 'edit_posts',
        ),
        'map_meta_cap' => true,
        'supports' => array('title', 'editor', 'comments'),
        'labels' => array(
            'name' => 'Disputes',
            'singular_name' => 'Dispute',
            'add_new' => 'Add Dispute',
            'add_new_item' => 'Add New Dispute',
            'edit_item' => 'Edit Dispute',
            'new_item' => 'New Dispute',
            'view_item' => 'View Dispute',
            'search_items' => 'Search Disputes',
            'not_found' => 'No disputes found',
            'not_found_in_trash' => 'No disputes found in trash',
        ),
    ));
}
add_action('init', 'hdh_register_dispute_cpt');

/**
 * Create report
 */
function hdh_create_report($reporter_id, $target_id, $type, $reason) {
    if (!$reporter_id || !$target_id || $reporter_id == $target_id) {
        return new WP_Error('invalid_params', 'Invalid report parameters');
    }
    
    $post_id = wp_insert_post(array(
        'post_type' => 'hayday_report',
        'post_status' => 'publish',
        'post_title' => sprintf('Report: User #%d reports User #%d', $reporter_id, $target_id),
        'post_content' => $reason,
    ));
    
    if (is_wp_error($post_id)) {
        return $post_id;
    }
    
    update_post_meta($post_id, '_hdh_reporter_id', $reporter_id);
    update_post_meta($post_id, '_hdh_target_id', $target_id);
    update_post_meta($post_id, '_hdh_report_type', sanitize_text_field($type));
    update_post_meta($post_id, '_hdh_report_reason', sanitize_textarea_field($reason));
    update_post_meta($post_id, '_hdh_report_status', 'pending');
    
    // Log event
    if (function_exists('hdh_log_event')) {
        hdh_log_event($reporter_id, 'report_created', array(
            'target_id' => $target_id,
            'type' => $type,
            'report_id' => $post_id,
        ));
    }
    
    // Check if target has 3+ reports → auto-flag
    global $wpdb;
    $report_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = 'hayday_report'
        AND p.post_status = 'publish'
        AND pm.meta_key = '_hdh_target_id'
        AND pm.meta_value = %d",
        $target_id
    ));
    
    if ($report_count >= 3) {
        if (function_exists('hdh_update_risk_score')) {
            hdh_update_risk_score($target_id, 20, 'multiple_reports');
        }
    }
    
    return $post_id;
}

/**
 * Create dispute
 */
function hdh_create_dispute($trade_id, $offer_id, $initiator_id) {
    if (!$trade_id || !$offer_id || !$initiator_id) {
        return new WP_Error('invalid_params', 'Invalid dispute parameters');
    }
    
    $trade = get_post($trade_id);
    $offer = get_post($offer_id);
    
    if (!$trade || $trade->post_type !== 'hayday_trade') {
        return new WP_Error('invalid_trade', 'Invalid trade');
    }
    
    $other_party_id = ($initiator_id == $trade->post_author) 
        ? get_post_meta($offer_id, '_hdh_offered_by', true)
        : $trade->post_author;
    
    $post_id = wp_insert_post(array(
        'post_type' => 'hayday_dispute',
        'post_status' => 'publish',
        'post_title' => sprintf('Dispute: Trade #%d', $trade_id),
        'post_content' => 'Dispute opened',
    ));
    
    if (is_wp_error($post_id)) {
        return $post_id;
    }
    
    update_post_meta($post_id, '_hdh_trade_id', $trade_id);
    update_post_meta($post_id, '_hdh_offer_id', $offer_id);
    update_post_meta($post_id, '_hdh_initiator_id', $initiator_id);
    update_post_meta($post_id, '_hdh_other_party_id', $other_party_id);
    update_post_meta($post_id, '_hdh_dispute_status', 'open');
    
    // Log event
    if (function_exists('hdh_log_event')) {
        hdh_log_event($initiator_id, 'dispute_created', array(
            'trade_id' => $trade_id,
            'offer_id' => $offer_id,
            'dispute_id' => $post_id,
        ));
    }
    
    return $post_id;
}

/**
 * Resolve dispute (admin only)
 */
function hdh_resolve_dispute($dispute_id, $resolution, $winner_user_id = null) {
    if (!current_user_can('administrator')) {
        return new WP_Error('insufficient_permissions', 'Admin only');
    }
    
    $dispute = get_post($dispute_id);
    if (!$dispute || $dispute->post_type !== 'hayday_dispute') {
        return new WP_Error('invalid_dispute', 'Invalid dispute');
    }
    
    update_post_meta($dispute_id, '_hdh_dispute_status', 'resolved');
    update_post_meta($dispute_id, '_hdh_resolution', sanitize_textarea_field($resolution));
    
    if ($winner_user_id) {
        $initiator_id = get_post_meta($dispute_id, '_hdh_initiator_id', true);
        $other_party_id = get_post_meta($dispute_id, '_hdh_other_party_id', true);
        
        $loser_id = ($winner_user_id == $initiator_id) ? $other_party_id : $initiator_id;
        
        // Update trust scores
        if (function_exists('hdh_update_trust_score')) {
            hdh_update_trust_score($winner_user_id, true, 'dispute_resolved');
            hdh_update_trust_score($loser_id, false, 'dispute_resolved');
        }
        
        // Refund/keep tickets per resolution
        $trade_id = get_post_meta($dispute_id, '_hdh_trade_id', true);
        // TODO: Implement ticket refund logic if needed
    }
    
    // Log event
    if (function_exists('hdh_log_event')) {
        hdh_log_event(get_current_user_id(), 'dispute_resolved', array(
            'dispute_id' => $dispute_id,
            'resolution' => $resolution,
            'winner_id' => $winner_user_id,
        ));
    }
    
    return true;
}

/**
 * Block user
 */
function hdh_block_user($user_id, $blocked_user_id) {
    if (!$user_id || !$blocked_user_id || $user_id == $blocked_user_id) {
        return false;
    }
    
    $blocked = get_user_meta($user_id, 'hdh_blocked_users', true);
    if (!is_array($blocked)) {
        $blocked = array();
    }
    
    if (!in_array($blocked_user_id, $blocked)) {
        $blocked[] = $blocked_user_id;
        update_user_meta($user_id, 'hdh_blocked_users', $blocked);
        
        if (function_exists('hdh_log_event')) {
            hdh_log_event($user_id, 'user_blocked', array(
                'blocked_user_id' => $blocked_user_id,
            ));
        }
    }
    
    return true;
}

/**
 * Unblock user
 */
function hdh_unblock_user($user_id, $blocked_user_id) {
    if (!$user_id || !$blocked_user_id) {
        return false;
    }
    
    $blocked = get_user_meta($user_id, 'hdh_blocked_users', true);
    if (!is_array($blocked)) {
        return false;
    }
    
    $blocked = array_diff($blocked, array($blocked_user_id));
    update_user_meta($user_id, 'hdh_blocked_users', array_values($blocked));
    
    if (function_exists('hdh_log_event')) {
        hdh_log_event($user_id, 'user_unblocked', array(
            'unblocked_user_id' => $blocked_user_id,
        ));
    }
    
    return true;
}

/**
 * Get blocked users
 */
function hdh_get_blocked_users($user_id) {
    $blocked = get_user_meta($user_id, 'hdh_blocked_users', true);
    return is_array($blocked) ? $blocked : array();
}

