<?php
/**
 * Post Views Counter
 */

// Set post views
function mi_set_post_views($postID) {
    $count_key = 'mi_post_views_count';
    $count = get_post_meta($postID, $count_key, true);
    
    if ($count == '') {
        $count = 0;
        delete_post_meta($postID, $count_key);
        add_post_meta($postID, $count_key, '0');
    } else {
        $count++;
        update_post_meta($postID, $count_key, $count);
    }
}

// Get post views
function mi_get_post_views($postID) {
    $count_key = 'mi_post_views_count';
    $count = get_post_meta($postID, $count_key, true);
    
    if ($count == '') {
        delete_post_meta($postID, $count_key);
        add_post_meta($postID, $count_key, '0');
        return 0;
    }
    
    return $count;
}

// Track views on single posts
function mi_track_post_views($post_id) {
    if (!is_single()) return;
    if (empty($post_id)) {
        $post_id = get_the_ID();
    }
    mi_set_post_views($post_id);
}
add_action('wp_head', 'mi_track_post_views');

// Display post views
function mi_display_post_views($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    $views = mi_get_post_views($post_id);
    return '<span class="post-views">👁️ ' . number_format_i18n($views) . ' görüntülenme</span>';
}

