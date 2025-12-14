<?php
/**
 * HDH: User State & Event System
 * Foundation for user status, rewards, verification, and audit trail
 */

if (!defined('ABSPATH')) exit;

/**
 * ============================================
 * USER STATE MANAGEMENT
 * ============================================
 */

/**
 * Get complete user state
 * 
 * @param int $user_id User ID
 * @return array User state data
 */
function hdh_get_user_state($user_id) {
    if (!$user_id) {
        return hdh_get_default_user_state();
    }
    
    $state = array(
        'user_id' => $user_id,
        
        // Core Stats
        'level' => (int) get_user_meta($user_id, 'hdh_level', true) ?: 1,
        'xp' => (int) get_user_meta($user_id, 'hdh_xp', true) ?: 0,
        'bilet_balance' => (int) get_user_meta($user_id, 'hdh_jeton_balance', true) ?: 0,
        
        // Trust & Risk
        'trust_score' => (int) get_user_meta($user_id, 'hdh_trust_score', true) ?: 0,
        'trust_plus' => (int) get_user_meta($user_id, 'hayday_trust_plus', true) ?: 0,
        'trust_minus' => (int) get_user_meta($user_id, 'hayday_trust_minus', true) ?: 0,
        'risk_score' => (int) get_user_meta($user_id, 'hdh_risk_score', true) ?: 0,
        
        // Verification Status
        'email_verified' => (bool) get_user_meta($user_id, 'hdh_email_verified', true),
        'phone_verified' => (bool) get_user_meta($user_id, 'hdh_phone_verified', true),
        'email_verified_at' => get_user_meta($user_id, 'hdh_email_verified_at', true),
        'phone_verified_at' => get_user_meta($user_id, 'hdh_phone_verified_at', true),
        
        // Activity Stats
        'total_trades' => (int) get_user_meta($user_id, 'hdh_total_trades', true) ?: 0,
        'completed_exchanges' => (int) get_user_meta($user_id, 'hdh_completed_exchanges', true) ?: 0,
        'active_listings' => (int) get_user_meta($user_id, 'hdh_active_listings', true) ?: 0,
        
        // Timestamps
        'last_active' => get_user_meta($user_id, 'hdh_last_active', true),
        'last_trade' => get_user_meta($user_id, 'hdh_last_trade', true),
        'member_since' => get_user_meta($user_id, 'hdh_member_since', true) ?: get_userdata($user_id)->user_registered,
        
        // Badges & Achievements
        'badges' => get_user_meta($user_id, 'hdh_badges', true) ?: array(),
        'achievements' => get_user_meta($user_id, 'hdh_achievements', true) ?: array(),
        
        // Restrictions
        'is_banned' => (bool) get_user_meta($user_id, 'hdh_is_banned', true),
        'ban_reason' => get_user_meta($user_id, 'hdh_ban_reason', true),
        'ban_until' => get_user_meta($user_id, 'hdh_ban_until', true),
    );
    
    // Calculate derived values
    $state['xp_to_next_level'] = hdh_calculate_xp_for_level($state['level'] + 1);
    $state['xp_progress'] = hdh_calculate_xp_for_level($state['level']);
    $state['level_progress_percent'] = $state['xp_to_next_level'] > 0 
        ? round((($state['xp'] - $state['xp_progress']) / ($state['xp_to_next_level'] - $state['xp_progress'])) * 100, 1)
        : 0;
    
    $state['trust_rating'] = hdh_calculate_trust_rating($state['trust_plus'], $state['trust_minus']);
    $state['is_verified'] = $state['email_verified'] || $state['phone_verified'];
    $state['is_fully_verified'] = $state['email_verified'] && $state['phone_verified'];
    $state['verification_level'] = hdh_calculate_verification_level($state);
    
    return apply_filters('hdh_user_state', $state, $user_id);
}

/**
 * Get default user state (for non-logged-in users)
 */
function hdh_get_default_user_state() {
    return array(
        'user_id' => 0,
        'level' => 0,
        'xp' => 0,
        'bilet_balance' => 0,
        'trust_score' => 0,
        'trust_plus' => 0,
        'trust_minus' => 0,
        'risk_score' => 0,
        'email_verified' => false,
        'phone_verified' => false,
        'is_verified' => false,
        'is_fully_verified' => false,
        'verification_level' => 0,
        'total_trades' => 0,
        'completed_exchanges' => 0,
        'active_listings' => 0,
        'badges' => array(),
        'achievements' => array(),
        'is_banned' => false,
    );
}

/**
 * Update user state field
 * 
 * @param int $user_id User ID
 * @param string $field Field name
 * @param mixed $value New value
 * @param string $reason Reason for change (for audit)
 * @return bool Success
 */
function hdh_update_user_state($user_id, $field, $value, $reason = '') {
    if (!$user_id) return false;
    
    $meta_key_map = array(
        'level' => 'hdh_level',
        'xp' => 'hdh_xp',
        'bilet_balance' => 'hdh_jeton_balance',
        'trust_score' => 'hdh_trust_score',
        'risk_score' => 'hdh_risk_score',
        'email_verified' => 'hdh_email_verified',
        'phone_verified' => 'hdh_phone_verified',
        'total_trades' => 'hdh_total_trades',
        'completed_exchanges' => 'hdh_completed_exchanges',
        'active_listings' => 'hdh_active_listings',
        'last_active' => 'hdh_last_active',
        'last_trade' => 'hdh_last_trade',
        'is_banned' => 'hdh_is_banned',
        'ban_reason' => 'hdh_ban_reason',
        'ban_until' => 'hdh_ban_until',
    );
    
    if (!isset($meta_key_map[$field])) {
        return false;
    }
    
    $old_value = get_user_meta($user_id, $meta_key_map[$field], true);
    $updated = update_user_meta($user_id, $meta_key_map[$field], $value);
    
    // Log state change event
    if ($updated) {
        hdh_log_event($user_id, 'state_change', array(
            'field' => $field,
            'old_value' => $old_value,
            'new_value' => $value,
            'reason' => $reason,
        ));
    }
    
    return $updated;
}

/**
 * ============================================
 * XP & LEVELING SYSTEM
 * ============================================
 */

/**
 * Calculate XP required for a level
 * Formula: 100 * level^1.5
 */
function hdh_calculate_xp_for_level($level) {
    if ($level <= 1) return 0;
    return (int) (100 * pow($level, 1.5));
}

/**
 * Add XP to user
 * 
 * @param int $user_id User ID
 * @param int $amount XP amount
 * @param string $reason Reason/action
 * @param array $metadata Additional data
 * @return bool|WP_Error Success or error
 */
function hdh_add_xp($user_id, $amount, $reason = '', $metadata = array()) {
    if (!$user_id || $amount <= 0) {
        return new WP_Error('invalid_params', 'Invalid parameters');
    }
    
    $state = hdh_get_user_state($user_id);
    $old_level = $state['level'];
    $old_xp = $state['xp'];
    $new_xp = $old_xp + $amount;
    
    // Update XP
    update_user_meta($user_id, 'hdh_xp', $new_xp);
    
    // Check for level up
    $new_level = hdh_calculate_level_from_xp($new_xp);
    if ($new_level > $old_level) {
        update_user_meta($user_id, 'hdh_level', $new_level);
        
        // Log level up event
        hdh_log_event($user_id, 'level_up', array(
            'old_level' => $old_level,
            'new_level' => $new_level,
            'xp' => $new_xp,
        ));
        
        // Award level up rewards
        hdh_award_level_up_rewards($user_id, $new_level);
    }
    
    // Log XP gain event
    hdh_log_event($user_id, 'xp_gain', array(
        'amount' => $amount,
        'reason' => $reason,
        'old_xp' => $old_xp,
        'new_xp' => $new_xp,
        'level' => $new_level,
        'metadata' => $metadata,
    ));
    
    return true;
}

/**
 * Calculate level from total XP
 */
function hdh_calculate_level_from_xp($xp) {
    $level = 1;
    while (hdh_calculate_xp_for_level($level + 1) <= $xp) {
        $level++;
        if ($level >= 100) break; // Max level cap
    }
    return $level;
}

/**
 * Award level up rewards
 */
function hdh_award_level_up_rewards($user_id, $level) {
    // Award bilets based on level
    $bilet_reward = min($level, 10); // Max 10 bilets per level
    hdh_add_bilet($user_id, $bilet_reward, 'level_up', array('level' => $level));
    
    // Award badges at milestone levels
    $milestones = array(5, 10, 25, 50, 100);
    if (in_array($level, $milestones)) {
        hdh_award_badge($user_id, 'level_' . $level);
    }
}

/**
 * ============================================
 * TRUST & RISK SCORING
 * ============================================
 */

/**
 * Calculate trust rating (0-5 stars)
 */
function hdh_calculate_trust_rating($plus, $minus) {
    $total = $plus + $minus;
    if ($total === 0) return 0;
    
    $ratio = $plus / $total;
    return round($ratio * 5, 1);
}

/**
 * Calculate verification level (0-3)
 * 0: No verification
 * 1: Email verified
 * 2: Phone verified
 * 3: Both verified
 */
function hdh_calculate_verification_level($state) {
    $level = 0;
    if ($state['email_verified']) $level += 1;
    if ($state['phone_verified']) $level += 2;
    return $level;
}

/**
 * Update trust score
 */
function hdh_update_trust_score($user_id, $is_positive, $reason = '') {
    if (!$user_id) return false;
    
    $meta_key = $is_positive ? 'hayday_trust_plus' : 'hayday_trust_minus';
    $current = (int) get_user_meta($user_id, $meta_key, true);
    $new = $current + 1;
    
    update_user_meta($user_id, $meta_key, $new);
    
    // Recalculate overall trust score (0-100)
    $plus = (int) get_user_meta($user_id, 'hayday_trust_plus', true);
    $minus = (int) get_user_meta($user_id, 'hayday_trust_minus', true);
    $total = $plus + $minus;
    $trust_score = $total > 0 ? (int) (($plus / $total) * 100) : 50;
    
    update_user_meta($user_id, 'hdh_trust_score', $trust_score);
    
    // Log trust event
    hdh_log_event($user_id, $is_positive ? 'trust_plus' : 'trust_minus', array(
        'reason' => $reason,
        'new_plus' => $plus,
        'new_minus' => $minus,
        'trust_score' => $trust_score,
    ));
    
    return true;
}

/**
 * Update risk score (0-100, higher = riskier)
 */
function hdh_update_risk_score($user_id, $change, $reason = '') {
    if (!$user_id) return false;
    
    $current = (int) get_user_meta($user_id, 'hdh_risk_score', true);
    $new = max(0, min(100, $current + $change)); // Clamp 0-100
    
    update_user_meta($user_id, 'hdh_risk_score', $new);
    
    // Log risk event
    hdh_log_event($user_id, 'risk_change', array(
        'change' => $change,
        'reason' => $reason,
        'old_score' => $current,
        'new_score' => $new,
    ));
    
    // Auto-ban if risk score too high
    if ($new >= 80 && !get_user_meta($user_id, 'hdh_is_banned', true)) {
        hdh_ban_user($user_id, 'High risk score: ' . $new, 7); // 7 day ban
    }
    
    return true;
}

/**
 * ============================================
 * VERIFICATION SYSTEM
 * ============================================
 */

/**
 * Mark email as verified
 */
function hdh_verify_email($user_id) {
    if (!$user_id) return false;
    
    $already_verified = get_user_meta($user_id, 'hdh_email_verified', true);
    
    if (!$already_verified) {
        update_user_meta($user_id, 'hdh_email_verified', true);
        update_user_meta($user_id, 'hdh_email_verified_at', current_time('mysql'));
        
        // Award bilet for verification
        hdh_add_bilet($user_id, 1, 'email_verification');
        
        // Log verification event
        hdh_log_event($user_id, 'email_verified', array(
            'verified_at' => current_time('mysql'),
        ));
    }
    
    return true;
}

/**
 * Mark phone as verified
 */
function hdh_verify_phone($user_id) {
    if (!$user_id) return false;
    
    $already_verified = get_user_meta($user_id, 'hdh_phone_verified', true);
    
    if (!$already_verified) {
        update_user_meta($user_id, 'hdh_phone_verified', true);
        update_user_meta($user_id, 'hdh_phone_verified_at', current_time('mysql'));
        
        // Award bilet for verification
        hdh_add_bilet($user_id, 4, 'phone_verification');
        
        // Log verification event
        hdh_log_event($user_id, 'phone_verified', array(
            'verified_at' => current_time('mysql'),
        ));
    }
    
    return true;
}

/**
 * ============================================
 * BADGES & ACHIEVEMENTS
 * ============================================
 */

/**
 * Award badge to user
 */
function hdh_award_badge($user_id, $badge_id, $metadata = array()) {
    if (!$user_id) return false;
    
    $badges = get_user_meta($user_id, 'hdh_badges', true) ?: array();
    
    if (!in_array($badge_id, $badges)) {
        $badges[] = $badge_id;
        update_user_meta($user_id, 'hdh_badges', $badges);
        
        // Log badge event
        hdh_log_event($user_id, 'badge_awarded', array(
            'badge_id' => $badge_id,
            'metadata' => $metadata,
        ));
    }
    
    return true;
}

/**
 * ============================================
 * BAN SYSTEM
 * ============================================
 */

/**
 * Ban user
 */
function hdh_ban_user($user_id, $reason, $days = 0) {
    if (!$user_id) return false;
    
    update_user_meta($user_id, 'hdh_is_banned', true);
    update_user_meta($user_id, 'hdh_ban_reason', $reason);
    
    if ($days > 0) {
        $ban_until = date('Y-m-d H:i:s', strtotime("+{$days} days"));
        update_user_meta($user_id, 'hdh_ban_until', $ban_until);
    } else {
        update_user_meta($user_id, 'hdh_ban_until', 'permanent');
    }
    
    // Log ban event
    hdh_log_event($user_id, 'user_banned', array(
        'reason' => $reason,
        'days' => $days,
        'ban_until' => $days > 0 ? $ban_until : 'permanent',
    ));
    
    return true;
}

/**
 * Unban user
 */
function hdh_unban_user($user_id, $reason = '') {
    if (!$user_id) return false;
    
    delete_user_meta($user_id, 'hdh_is_banned');
    delete_user_meta($user_id, 'hdh_ban_reason');
    delete_user_meta($user_id, 'hdh_ban_until');
    
    // Log unban event
    hdh_log_event($user_id, 'user_unbanned', array(
        'reason' => $reason,
    ));
    
    return true;
}

/**
 * Check if user is banned
 */
function hdh_is_user_banned($user_id) {
    if (!$user_id) return false;
    
    $is_banned = get_user_meta($user_id, 'hdh_is_banned', true);
    if (!$is_banned) return false;
    
    // Check if temporary ban expired
    $ban_until = get_user_meta($user_id, 'hdh_ban_until', true);
    if ($ban_until && $ban_until !== 'permanent') {
        if (strtotime($ban_until) < current_time('timestamp')) {
            // Ban expired, unban user
            hdh_unban_user($user_id, 'Ban period expired');
            return false;
        }
    }
    
    return true;
}

/**
 * ============================================
 * BILET WRAPPER (Maintains compatibility)
 * ============================================
 */

/**
 * Add bilet (wrapper for hdh_add_jeton)
 */
function hdh_add_bilet($user_id, $amount, $reason = '', $metadata = array()) {
    return hdh_add_jeton($user_id, $amount, $reason, $metadata);
}

/**
 * Spend bilet (wrapper for hdh_spend_jeton)
 */
function hdh_spend_bilet($user_id, $amount, $reason = '', $metadata = array()) {
    return hdh_spend_jeton($user_id, $amount, $reason, $metadata);
}

