<?php
/**
 * HDH: Trust/Rating System
 * Basic trust scoring for trade offers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add rating buttons to comments
 * Called from comment callback, not as action hook
 */
function hdh_add_comment_rating_buttons($comment_id) {
    $comment = get_comment($comment_id);
    if (!$comment) return '';
    
    $comment_author_id = $comment->user_id;
    $current_user_id = get_current_user_id();
    $post = get_post($comment->comment_post_ID);
    
    // Only show rating buttons if:
    // 1. User is logged in
    // 2. Comment author is not the current user
    // 3. Post is trade offer
    if (!$current_user_id || $comment_author_id == $current_user_id || get_post_type($post->ID) !== 'hayday_trade') {
        return '';
    }
    
    // Check if already rated
    $already_rated = get_comment_meta($comment_id, '_hdh_rated_by_' . $current_user_id, true);
    
    if ($already_rated) {
        return '<div class="comment-rating-buttons"><span class="rating-success">✅ Değerlendirdiniz</span></div>';
    }
    
    ob_start();
    ?>
    <div class="comment-rating-buttons" data-comment-id="<?php echo esc_attr($comment_id); ?>">
        <button type="button" class="btn-rate btn-rate-plus" data-rating="plus" data-comment-id="<?php echo esc_attr($comment_id); ?>">
            👍 Güvenilir (+)
        </button>
        <button type="button" class="btn-rate btn-rate-minus" data-rating="minus" data-comment-id="<?php echo esc_attr($comment_id); ?>">
            👎 Güvenilmez (-)
        </button>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * AJAX handler for rating comments
 */
function hdh_rate_comment() {
    check_ajax_referer('hdh_rate_comment', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Giriş yapmanız gerekiyor.'));
        return;
    }
    
    $comment_id = absint($_POST['comment_id']);
    $rating = sanitize_text_field($_POST['rating']); // 'plus' or 'minus'
    $current_user_id = get_current_user_id();
    
    if (!in_array($rating, array('plus', 'minus'))) {
        wp_send_json_error(array('message' => 'Geçersiz değerlendirme.'));
        return;
    }
    
    // Check if already rated
    $already_rated = get_comment_meta($comment_id, '_hdh_rated_by_' . $current_user_id, true);
    if ($already_rated) {
        wp_send_json_error(array('message' => 'Bu yorumu zaten değerlendirdiniz.'));
        return;
    }
    
    $comment = get_comment($comment_id);
    if (!$comment) {
        wp_send_json_error(array('message' => 'Yorum bulunamadı.'));
        return;
    }
    
    $comment_author_id = $comment->user_id;
    if (!$comment_author_id) {
        wp_send_json_error(array('message' => 'Yorum sahibi bulunamadı.'));
        return;
    }
    
    // Mark as rated
    update_comment_meta($comment_id, '_hdh_rated_by_' . $current_user_id, $rating);
    
    // Update comment rating
    $current_rating = (int) get_comment_meta($comment_id, '_hdh_comment_rating', true);
    $new_rating = $rating === 'plus' ? $current_rating + 1 : $current_rating - 1;
    update_comment_meta($comment_id, '_hdh_comment_rating', $new_rating);
    
    // Update user trust score
    if ($rating === 'plus') {
        $trust_plus = (int) get_user_meta($comment_author_id, 'hayday_trust_plus', true);
        update_user_meta($comment_author_id, 'hayday_trust_plus', $trust_plus + 1);
    } else {
        $trust_minus = (int) get_user_meta($comment_author_id, 'hayday_trust_minus', true);
        update_user_meta($comment_author_id, 'hayday_trust_minus', $trust_minus + 1);
    }
    
    wp_send_json_success(array(
        'message' => 'Değerlendirme kaydedildi.',
        'new_rating' => $new_rating
    ));
}
add_action('wp_ajax_hdh_rate_comment', 'hdh_rate_comment');

/**
 * Enqueue trust system scripts
 */
function hdh_enqueue_trust_scripts() {
    if (is_singular('hayday_trade')) {
        wp_enqueue_script(
            'hdh-trust-system',
            get_template_directory_uri() . '/assets/js/trust-system.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_localize_script('hdh-trust-system', 'hdhTrust', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hdh_rate_comment')
        ));
    }
}
add_action('wp_enqueue_scripts', 'hdh_enqueue_trust_scripts');

/**
 * Get user trust score
 */
function hdh_get_user_trust_score($user_id) {
    $plus = (int) get_user_meta($user_id, 'hayday_trust_plus', true);
    $minus = (int) get_user_meta($user_id, 'hayday_trust_minus', true);
    return array(
        'plus' => $plus,
        'minus' => $minus,
        'total' => $plus - $minus
    );
}

