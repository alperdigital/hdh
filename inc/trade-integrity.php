<?php
/**
 * HDH: Trade Data Integrity & Soft Delete
 * Handles trade status, soft delete, and alternative suggestions
 */

if (!defined('ABSPATH')) exit;

/**
 * Check if a trade is accessible
 * 
 * @param int $post_id Trade post ID
 * @param int $user_id Current user ID (0 if not logged in)
 * @return array Status array with 'accessible', 'reason', 'message'
 */
function hdh_check_trade_accessibility($post_id, $user_id = 0) {
    $post = get_post($post_id);
    
    // Post doesn't exist
    if (!$post || $post->post_type !== 'hayday_trade') {
        return array(
            'accessible' => false,
            'reason' => 'not_found',
            'message' => 'İlan bulunamadı'
        );
    }
    
    $post_status = $post->post_status;
    $author_id = $post->post_author;
    $is_owner = ($user_id == $author_id);
    $is_admin = user_can($user_id, 'administrator');
    
    // Trash/deleted
    if ($post_status === 'trash') {
        return array(
            'accessible' => false,
            'reason' => 'deleted',
            'message' => 'İlan kaldırılmış'
        );
    }
    
    // Draft (not published yet)
    if ($post_status === 'draft') {
        if ($is_owner || $is_admin) {
            return array(
                'accessible' => true,
                'reason' => 'draft',
                'message' => 'İlan taslak durumda (sadece siz görebilirsiniz)'
            );
        }
        return array(
            'accessible' => false,
            'reason' => 'draft',
            'message' => 'İlan henüz yayınlanmamış'
        );
    }
    
    // Pending review
    if ($post_status === 'pending') {
        if ($is_owner || $is_admin) {
            return array(
                'accessible' => true,
                'reason' => 'pending',
                'message' => 'İlan onay bekliyor'
            );
        }
        return array(
            'accessible' => false,
            'reason' => 'pending',
            'message' => 'İlan onay bekliyor'
        );
    }
    
    // Check trade status (completed, closed, etc.)
    $trade_status = get_post_meta($post_id, '_hdh_trade_status', true) ?: 'open';
    
    if ($trade_status === 'completed') {
        // Only owner and accepted offerer can see completed trades
        $accepted_offerer_id = get_post_meta($post_id, '_hdh_accepted_offerer_id', true);
        if ($is_owner || $user_id == $accepted_offerer_id || $is_admin) {
            return array(
                'accessible' => true,
                'reason' => 'completed',
                'message' => 'İlan tamamlanmış'
            );
        }
        return array(
            'accessible' => false,
            'reason' => 'completed',
            'message' => 'İlan tamamlanmış ve kapanmış'
        );
    }
    
    if ($trade_status === 'closed') {
        if ($is_owner || $is_admin) {
            return array(
                'accessible' => true,
                'reason' => 'closed',
                'message' => 'İlan kapatılmış (sadece siz görebilirsiniz)'
            );
        }
        return array(
            'accessible' => false,
            'reason' => 'closed',
            'message' => 'İlan sahibi tarafından kapatılmış'
        );
    }
    
    // Check expiry date (if implemented)
    $expiry_date = get_post_meta($post_id, '_hdh_expiry_date', true);
    if ($expiry_date && strtotime($expiry_date) < current_time('timestamp')) {
        if ($is_owner || $is_admin) {
            return array(
                'accessible' => true,
                'reason' => 'expired',
                'message' => 'İlan süresi dolmuş (sadece siz görebilirsiniz)'
            );
        }
        return array(
            'accessible' => false,
            'reason' => 'expired',
            'message' => 'İlan süresi dolmuş'
        );
    }
    
    // Published and open
    if ($post_status === 'publish' && $trade_status === 'open') {
        return array(
            'accessible' => true,
            'reason' => 'open',
            'message' => 'İlan aktif'
        );
    }
    
    // Default: accessible if published
    return array(
        'accessible' => ($post_status === 'publish'),
        'reason' => $post_status,
        'message' => 'İlan durumu: ' . $post_status
    );
}

/**
 * Get similar/alternative trades
 * 
 * @param int $post_id Current trade ID (to exclude)
 * @param int $limit Number of alternatives to return
 * @return array Array of post IDs
 */
function hdh_get_alternative_trades($post_id, $limit = 3) {
    // Get wanted item from current trade
    $wanted_item = get_post_meta($post_id, '_hdh_wanted_item', true);
    
    $args = array(
        'post_type' => 'hayday_trade',
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'post__not_in' => array($post_id),
        'meta_query' => array(
            array(
                'key' => '_hdh_trade_status',
                'value' => 'open',
                'compare' => '='
            )
        ),
        'orderby' => 'date',
        'order' => 'DESC'
    );
    
    // If we have a wanted item, prioritize trades offering that item
    if (!empty($wanted_item)) {
        $args['meta_query'][] = array(
            'relation' => 'OR',
            array(
                'key' => '_hdh_offer_item_1',
                'value' => $wanted_item,
                'compare' => '='
            ),
            array(
                'key' => '_hdh_offer_item_2',
                'value' => $wanted_item,
                'compare' => '='
            ),
            array(
                'key' => '_hdh_offer_item_3',
                'value' => $wanted_item,
                'compare' => '='
            )
        );
    }
    
    $query = new WP_Query($args);
    $alternatives = array();
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $alternatives[] = get_the_ID();
        }
        wp_reset_postdata();
    }
    
    // If we didn't find enough with same item, fill with recent trades
    if (count($alternatives) < $limit) {
        $remaining = $limit - count($alternatives);
        $exclude_ids = array_merge(array($post_id), $alternatives);
        
        $fallback_args = array(
            'post_type' => 'hayday_trade',
            'post_status' => 'publish',
            'posts_per_page' => $remaining,
            'post__not_in' => $exclude_ids,
            'meta_query' => array(
                array(
                    'key' => '_hdh_trade_status',
                    'value' => 'open',
                    'compare' => '='
                )
            ),
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $fallback_query = new WP_Query($fallback_args);
        
        if ($fallback_query->have_posts()) {
            while ($fallback_query->have_posts()) {
                $fallback_query->the_post();
                $alternatives[] = get_the_ID();
            }
            wp_reset_postdata();
        }
    }
    
    return $alternatives;
}

/**
 * Soft delete a trade (set status to closed)
 * 
 * @param int $post_id Trade post ID
 * @param int $user_id User performing the action
 * @return bool Success
 */
function hdh_soft_delete_trade($post_id, $user_id) {
    $post = get_post($post_id);
    
    if (!$post || $post->post_type !== 'hayday_trade') {
        return false;
    }
    
    // Check permissions
    if ($post->post_author != $user_id && !user_can($user_id, 'administrator')) {
        return false;
    }
    
    // Set trade status to closed
    update_post_meta($post_id, '_hdh_trade_status', 'closed');
    update_post_meta($post_id, '_hdh_closed_date', current_time('mysql'));
    update_post_meta($post_id, '_hdh_closed_by', $user_id);
    
    // Log action
    if (WP_DEBUG && WP_DEBUG_LOG) {
        error_log(sprintf(
            '[HDH Trade] Soft deleted: Post #%d by User #%d at %s',
            $post_id,
            $user_id,
            current_time('mysql')
        ));
    }
    
    return true;
}

/**
 * Reactivate a soft-deleted trade
 * 
 * @param int $post_id Trade post ID
 * @param int $user_id User performing the action
 * @return bool Success
 */
function hdh_reactivate_trade($post_id, $user_id) {
    $post = get_post($post_id);
    
    if (!$post || $post->post_type !== 'hayday_trade') {
        return false;
    }
    
    // Check permissions
    if ($post->post_author != $user_id && !user_can($user_id, 'administrator')) {
        return false;
    }
    
    // Set trade status back to open
    update_post_meta($post_id, '_hdh_trade_status', 'open');
    delete_post_meta($post_id, '_hdh_closed_date');
    delete_post_meta($post_id, '_hdh_closed_by');
    
    // Log action
    if (WP_DEBUG && WP_DEBUG_LOG) {
        error_log(sprintf(
            '[HDH Trade] Reactivated: Post #%d by User #%d at %s',
            $post_id,
            $user_id,
            current_time('mysql')
        ));
    }
    
    return true;
}

/**
 * Clean up expired trades (cron job)
 * Sets trades older than X days to expired status
 */
function hdh_cleanup_expired_trades() {
    $expiry_days = apply_filters('hdh_trade_expiry_days', 30); // Default 30 days
    
    $args = array(
        'post_type' => 'hayday_trade',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'date_query' => array(
            array(
                'before' => date('Y-m-d', strtotime("-{$expiry_days} days")),
                'inclusive' => false
            )
        ),
        'meta_query' => array(
            array(
                'key' => '_hdh_trade_status',
                'value' => 'open',
                'compare' => '='
            )
        )
    );
    
    $query = new WP_Query($args);
    $expired_count = 0;
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            
            // Mark as expired
            update_post_meta($post_id, '_hdh_trade_status', 'expired');
            update_post_meta($post_id, '_hdh_expired_date', current_time('mysql'));
            
            $expired_count++;
        }
        wp_reset_postdata();
    }
    
    if ($expired_count > 0 && WP_DEBUG && WP_DEBUG_LOG) {
        error_log(sprintf(
            '[HDH Trade Cleanup] Expired %d trades older than %d days',
            $expired_count,
            $expiry_days
        ));
    }
    
    return $expired_count;
}

// Schedule cleanup cron job (daily)
if (!wp_next_scheduled('hdh_cleanup_expired_trades')) {
    wp_schedule_event(time(), 'daily', 'hdh_cleanup_expired_trades');
}
add_action('hdh_cleanup_expired_trades', 'hdh_cleanup_expired_trades');

