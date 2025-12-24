<?php
/**
 * HDH: User Presence Tracking System
 * Lightweight presence tracking for "Online/Last seen" labels and active user counts
 */

if (!defined('ABSPATH')) exit;

/**
 * ============================================
 * DATABASE TABLE CREATION
 * ============================================
 */

/**
 * Create presence tracking table
 */
function hdh_create_presence_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'hdh_user_presence';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        last_seen_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY user_id (user_id),
        KEY last_seen_at (last_seen_at)
    ) {$charset_collate};";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Initialize presence table on theme activation
 */
add_action('after_switch_theme', 'hdh_create_presence_table');

/**
 * Also create on admin init (for existing sites)
 */
add_action('admin_init', function() {
    if (current_user_can('manage_options')) {
        hdh_create_presence_table();
    }
}, 1);

/**
 * ============================================
 * PRESENCE UPDATE FUNCTIONS
 * ============================================
 */

/**
 * Update user presence
 * Throttled to max once per 30 seconds per user
 * 
 * @param int $user_id User ID
 * @return bool Success
 */
function hdh_update_user_presence($user_id) {
    if (!$user_id) {
        return false;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_user_presence';
    
    // Check if record exists
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$table_name} WHERE user_id = %d",
        $user_id
    ));
    
    $now = current_time('mysql');
    
    if ($existing) {
        // Check last update time (throttle to max once per 30 seconds)
        $last_update = $wpdb->get_var($wpdb->prepare(
            "SELECT updated_at FROM {$table_name} WHERE user_id = %d",
            $user_id
        ));
        
        if ($last_update) {
            $last_update_timestamp = strtotime($last_update);
            $current_timestamp = current_time('timestamp');
            $time_diff = $current_timestamp - $last_update_timestamp;
            
            // Throttle: only update if more than 30 seconds have passed
            if ($time_diff < 30) {
                return false; // Too soon, skip update
            }
        }
        
        // Update existing record
        $result = $wpdb->update(
            $table_name,
            array(
                'last_seen_at' => $now,
                'updated_at' => $now,
            ),
            array('user_id' => $user_id),
            array('%s', '%s'),
            array('%d')
        );
    } else {
        // Insert new record
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'last_seen_at' => $now,
                'updated_at' => $now,
            ),
            array('%d', '%s', '%s')
        );
    }
    
    return $result !== false;
}

/**
 * Get user presence data
 * 
 * @param int $user_id User ID
 * @return array|false Presence data or false
 */
function hdh_get_user_presence($user_id) {
    if (!$user_id) {
        return false;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_user_presence';
    
    $presence = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table_name} WHERE user_id = %d",
        $user_id
    ), ARRAY_A);
    
    if (!$presence) {
        // Fallback: check user registration date
        $user = get_userdata($user_id);
        if ($user) {
            return array(
                'user_id' => $user_id,
                'last_seen_at' => $user->user_registered,
                'updated_at' => $user->user_registered,
            );
        }
        return false;
    }
    
    return $presence;
}

/**
 * Get active users count
 * 
 * @param int $threshold_seconds Threshold in seconds (default 120)
 * @return int Active users count
 */
function hdh_get_active_users_count($threshold_seconds = 120) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_user_presence';
    
    $threshold_time = date('Y-m-d H:i:s', current_time('timestamp') - $threshold_seconds);
    
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT user_id) 
         FROM {$table_name} 
         WHERE last_seen_at >= %s",
        $threshold_time
    ));
    
    return (int) $count;
}

/**
 * Check if user is online
 * 
 * @param int $user_id User ID
 * @param int $threshold_seconds Threshold in seconds (default 120)
 * @return bool Is online
 */
function hdh_is_user_online($user_id, $threshold_seconds = 120) {
    $presence = hdh_get_user_presence($user_id);
    if (!$presence) {
        return false;
    }
    
    $last_seen_timestamp = strtotime($presence['last_seen_at']);
    $current_timestamp = current_time('timestamp');
    $time_diff = $current_timestamp - $last_seen_timestamp;
    
    return $time_diff <= $threshold_seconds;
}

/**
 * ============================================
 * WORDPRESS HEARTBEAT API INTEGRATION
 * ============================================
 */

/**
 * Hook into Heartbeat API to update presence
 */
add_filter('heartbeat_send', 'hdh_heartbeat_update_presence', 10, 2);

function hdh_heartbeat_update_presence($response, $data) {
    if (!is_user_logged_in()) {
        return $response;
    }
    
    $user_id = get_current_user_id();
    
    // Update presence via Heartbeat (throttled internally)
    hdh_update_user_presence($user_id);
    
    return $response;
}

/**
 * ============================================
 * EVENT SYSTEM INTEGRATION
 * ============================================
 */

/**
 * Auto-update presence when user action is tracked
 */
add_action('hdh_event_logged', 'hdh_auto_update_presence_on_action', 10, 2);

function hdh_auto_update_presence_on_action($event_id, $user_id) {
    if ($user_id) {
        hdh_update_user_presence($user_id);
    }
}

/**
 * Also update presence when hdh_track_action is called
 * (since it updates hdh_last_active, we should sync with presence table)
 */
add_action('init', function() {
    // Hook into hdh_track_action if it exists
    if (function_exists('hdh_track_action')) {
        // We'll update presence in the action hook above
        // This ensures presence is updated whenever an action is tracked
    }
}, 20);

/**
 * ============================================
 * PRESENCE BUCKET CALCULATION
 * ============================================
 */

/**
 * Get presence bucket for user
 * 
 * @param int $user_id User ID
 * @param bool $privacy_enabled If true, returns coarse buckets only
 * @return string Bucket name
 */
function hdh_get_presence_bucket($user_id, $privacy_enabled = false) {
    if (!$user_id) {
        return '3+ days';
    }
    
    // Check privacy setting
    if ($privacy_enabled || get_user_meta($user_id, 'hdh_presence_privacy_enabled', true)) {
        return hdh_get_coarse_presence_bucket($user_id);
    }
    
    $presence = hdh_get_user_presence($user_id);
    if (!$presence) {
        return '3+ days';
    }
    
    $last_seen_timestamp = strtotime($presence['last_seen_at']);
    $current_timestamp = current_time('timestamp');
    $time_diff = $current_timestamp - $last_seen_timestamp;
    
    // Get admin-configurable thresholds
    $online_threshold = (int) get_option('hdh_presence_online_threshold', 120); // 2 minutes
    $five_min_threshold = (int) get_option('hdh_presence_5min_threshold', 300); // 5 minutes
    $one_hour_threshold = (int) get_option('hdh_presence_1hour_threshold', 3600); // 1 hour
    
    // Calculate buckets
    if ($time_diff <= $online_threshold) {
        return 'online';
    } elseif ($time_diff <= $five_min_threshold) {
        return '5min';
    } elseif ($time_diff <= $one_hour_threshold) {
        return '1hour';
    } else {
        // Check if today, yesterday, or older
        $last_seen_date = date('Y-m-d', $last_seen_timestamp);
        $today = date('Y-m-d', $current_timestamp);
        $yesterday = date('Y-m-d', $current_timestamp - 86400);
        
        if ($last_seen_date === $today) {
            return 'today';
        } elseif ($last_seen_date === $yesterday) {
            return 'yesterday';
        } else {
            return '3+ days';
        }
    }
}

/**
 * Get coarse presence bucket (for privacy mode)
 * 
 * @param int $user_id User ID
 * @return string Coarse bucket name
 */
function hdh_get_coarse_presence_bucket($user_id) {
    $presence = hdh_get_user_presence($user_id);
    if (!$presence) {
        return '3+ days';
    }
    
    $last_seen_timestamp = strtotime($presence['last_seen_at']);
    $current_timestamp = current_time('timestamp');
    $time_diff = $current_timestamp - $last_seen_timestamp;
    
    // Coarse buckets: Online, Today, Yesterday, 3+ days
    $online_threshold = (int) get_option('hdh_presence_online_threshold', 120);
    
    if ($time_diff <= $online_threshold) {
        return 'online';
    } else {
        $last_seen_date = date('Y-m-d', $last_seen_timestamp);
        $today = date('Y-m-d', $current_timestamp);
        $yesterday = date('Y-m-d', $current_timestamp - 86400);
        
        if ($last_seen_date === $today) {
            return 'today';
        } elseif ($last_seen_date === $yesterday) {
            return 'yesterday';
        } else {
            return '3+ days';
        }
    }
}

/**
 * Format presence label in Turkish
 * 
 * @param string $bucket Bucket name
 * @param int|null $timestamp Optional timestamp for more precise formatting
 * @return string Formatted label
 */
function hdh_format_presence_label($bucket, $timestamp = null) {
    switch ($bucket) {
        case 'online':
            return 'Online';
        
        case '5min':
            if ($timestamp) {
                $diff = current_time('timestamp') - $timestamp;
                $minutes = floor($diff / 60);
                if ($minutes <= 0) {
                    return 'Az önce';
                }
                return $minutes . ' dakika önce';
            }
            return '5 dakika önce';
        
        case '1hour':
            if ($timestamp) {
                $diff = current_time('timestamp') - $timestamp;
                $hours = floor($diff / 3600);
                if ($hours <= 0) {
                    $minutes = floor($diff / 60);
                    return $minutes . ' dakika önce';
                }
                return $hours . ' saat önce';
            }
            return '1 saat önce';
        
        case 'today':
            return 'Bugün';
        
        case 'yesterday':
            return 'Dün';
        
        case '3+ days':
        default:
            if ($timestamp) {
                $diff = current_time('timestamp') - $timestamp;
                $days = floor($diff / 86400);
                if ($days <= 0) {
                    return 'Bugün';
                } elseif ($days == 1) {
                    return 'Dün';
                } elseif ($days < 7) {
                    return $days . ' gün önce';
                } else {
                    return date('d.m.Y', $timestamp);
                }
            }
            return '3+ gün önce';
    }
}

/**
 * Get presence bucket order value for sorting
 * 
 * @param string $bucket Bucket name
 * @return int Order value (lower = higher priority)
 */
function hdh_get_presence_bucket_order($bucket) {
    $order_map = array(
        'online' => 1,
        '5min' => 2,
        '1hour' => 3,
        'today' => 4,
        'yesterday' => 5,
        '3+ days' => 6,
    );
    
    return isset($order_map[$bucket]) ? $order_map[$bucket] : 99;
}

/**
 * ============================================
 * LISTING QUERIES WITH PRESENCE
 * ============================================
 */

/**
 * Get listings with presence-based sorting
 * 
 * @param array $args WP_Query arguments
 * @param string $sort_by 'presence' or 'newest'
 * @return WP_Query Query object
 */
function hdh_get_listings_with_presence($args = array(), $sort_by = 'presence') {
    global $wpdb;
    
    // Default args
    $defaults = array(
        'post_type' => 'hayday_trade',
        'posts_per_page' => 20,
        'post_status' => 'publish',
    );
    
    $args = wp_parse_args($args, $defaults);
    
    // If sorting by newest, use standard WP_Query
    if ($sort_by === 'newest') {
        $args['orderby'] = 'date';
        $args['order'] = 'DESC';
        return new WP_Query($args);
    }
    
    // Presence-first sorting requires custom query
    $presence_table = $wpdb->prefix . 'hdh_user_presence';
    
    // Build base query
    $meta_query = isset($args['meta_query']) ? $args['meta_query'] : array();
    $author__not_in = isset($args['author__not_in']) ? $args['author__not_in'] : array();
    
    // Start with standard query to get post IDs
    $query_args = $args;
    $query_args['fields'] = 'ids';
    $query_args['posts_per_page'] = -1; // Get all matching IDs first
    $query_args['orderby'] = 'date';
    $query_args['order'] = 'DESC';
    
    $temp_query = new WP_Query($query_args);
    $post_ids = $temp_query->posts;
    
    if (empty($post_ids)) {
        // No posts found, return empty query
        $args['post__in'] = array(0); // Force no results
        return new WP_Query($args);
    }
    
    // Get presence data for all authors
    $post_ids_str = implode(',', array_map('intval', $post_ids));
    $authors_query = $wpdb->get_results(
        "SELECT DISTINCT post_author FROM {$wpdb->posts} WHERE ID IN ({$post_ids_str})",
        ARRAY_A
    );
    
    $author_ids = array();
    foreach ($authors_query as $row) {
        $author_ids[] = (int) $row['post_author'];
    }
    
    if (empty($author_ids)) {
        $args['post__in'] = array(0);
        return new WP_Query($args);
    }
    
    // Get presence buckets for all authors
    $author_buckets = array();
    foreach ($author_ids as $author_id) {
        $bucket = hdh_get_presence_bucket($author_id);
        $bucket_order = hdh_get_presence_bucket_order($bucket);
        $author_buckets[$author_id] = array(
            'bucket' => $bucket,
            'order' => $bucket_order,
        );
    }
    
    // Get posts with their authors and dates
    $posts_data = $wpdb->get_results(
        "SELECT ID, post_author, post_date FROM {$wpdb->posts} WHERE ID IN ({$post_ids_str})",
        ARRAY_A
    );
    
    // Sort posts: first by presence bucket order, then by post_date DESC
    usort($posts_data, function($a, $b) use ($author_buckets) {
        $author_a = (int) $a['post_author'];
        $author_b = (int) $b['post_author'];
        
        $bucket_order_a = isset($author_buckets[$author_a]) ? $author_buckets[$author_a]['order'] : 99;
        $bucket_order_b = isset($author_buckets[$author_b]) ? $author_buckets[$author_b]['order'] : 99;
        
        // First sort by bucket order
        if ($bucket_order_a !== $bucket_order_b) {
            return $bucket_order_a - $bucket_order_b;
        }
        
        // Within same bucket, sort by date DESC
        return strtotime($b['post_date']) - strtotime($a['post_date']);
    });
    
    // Apply anti-manipulation: limit max listings per user in "Online" bucket
    $max_online_per_user = (int) get_option('hdh_presence_max_online_per_user', 3);
    $user_online_count = array();
    $filtered_post_ids = array();
    
    foreach ($posts_data as $post_data) {
        $post_id = (int) $post_data['ID'];
        $author_id = (int) $post_data['post_author'];
        $bucket = isset($author_buckets[$author_id]) ? $author_buckets[$author_id]['bucket'] : '3+ days';
        
        // Apply limit for online bucket
        if ($bucket === 'online') {
            if (!isset($user_online_count[$author_id])) {
                $user_online_count[$author_id] = 0;
            }
            if ($user_online_count[$author_id] >= $max_online_per_user) {
                continue; // Skip this listing
            }
            $user_online_count[$author_id]++;
        }
        
        $filtered_post_ids[] = $post_id;
    }
    
    // Limit to requested posts_per_page
    $posts_per_page = isset($args['posts_per_page']) ? (int) $args['posts_per_page'] : 20;
    $filtered_post_ids = array_slice($filtered_post_ids, 0, $posts_per_page);
    
    if (empty($filtered_post_ids)) {
        $args['post__in'] = array(0);
        return new WP_Query($args);
    }
    
    // Create final query with sorted post IDs
    $final_args = $args;
    $final_args['post__in'] = $filtered_post_ids;
    $final_args['orderby'] = 'post__in'; // Maintain our custom order
    $final_args['order'] = 'ASC';
    
    return new WP_Query($final_args);
}

