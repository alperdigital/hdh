<?php
/**
 * HDH: Trust & Level Display
 */

if (!defined('ABSPATH')) exit;

/**
 * Get user tooltip data
 */
function hdh_get_user_tooltip_data($user_id) {
    if (!$user_id) return null;
    
    $state = function_exists('hdh_get_user_state') ? hdh_get_user_state($user_id) : null;
    if (!$state) {
        $state = array(
            'level' => 1,
            'completed_exchanges' => 0,
            'trust_rating' => 0,
            'trust_plus' => 0,
            'trust_minus' => 0,
            'member_since' => get_userdata($user_id)->user_registered ?? '',
        );
    }
    
    return array(
        'level' => $state['level'],
        'completed_exchanges' => $state['completed_exchanges'] ?? 0,
        'trust_rating' => $state['trust_rating'] ?? 0,
        'trust_plus' => $state['trust_plus'] ?? 0,
        'trust_minus' => $state['trust_minus'] ?? 0,
        'member_since' => $state['member_since'] ?? '',
    );
}

/**
 * Render user badge with tooltip
 */
function hdh_render_user_badge($user_id, $size = 'medium') {
    if (!$user_id) return '';
    
    $tooltip_data = hdh_get_user_tooltip_data($user_id);
    if (!$tooltip_data) return '';
    
    $level = $tooltip_data['level'];
    $exchanges = $tooltip_data['completed_exchanges'];
    $trust_rating = $tooltip_data['trust_rating'];
    $member_since = $tooltip_data['member_since'];
    $formatted_date = $member_since ? date_i18n('d F Y', strtotime($member_since)) : '';
    
    // Determine digit class based on level
    $level_int = (int) $level;
    $digits = strlen((string)$level_int);
    $digit_class = $digits === 1 ? 'lvl-d1' : ($digits === 2 ? 'lvl-d2' : 'lvl-d3');
    
    $tooltip_text = sprintf(
        'Seviye %d • %d başarılı takas • %.1f%% güven puanı • Üye: %s',
        $level,
        $exchanges,
        $trust_rating * 20, // Convert 0-5 to 0-100%
        $formatted_date
    );
    
    ob_start();
    ?>
    <div class="hdh-level-badge <?php echo esc_attr($digit_class); ?>" 
          data-user-id="<?php echo esc_attr($user_id); ?>"
          title="<?php echo esc_attr($tooltip_text); ?>"
          aria-label="<?php echo esc_attr($tooltip_text); ?>">
        <?php echo esc_html($level); ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render trust stars with tooltip
 */
function hdh_render_trust_stars($user_id, $size = 'medium') {
    if (!$user_id) return '';
    
    $tooltip_data = hdh_get_user_tooltip_data($user_id);
    if (!$tooltip_data) return '';
    
    $rating = $tooltip_data['trust_rating'];
    $plus = $tooltip_data['trust_plus'];
    $minus = $tooltip_data['trust_minus'];
    $exchanges = $tooltip_data['completed_exchanges'];
    
    $total = $plus + $minus;
    $tooltip_text = sprintf(
        '%d pozitif • %d negatif • %d tamamlanan takas bazında',
        $plus,
        $minus,
        $exchanges
    );
    
    $full_stars = floor($rating);
    $has_half = ($rating - $full_stars) >= 0.5;
    $empty_stars = 5 - $full_stars - ($has_half ? 1 : 0);
    
    ob_start();
    ?>
    <span class="trust-stars <?php echo esc_attr('stars-' . $size); ?>" 
          data-user-id="<?php echo esc_attr($user_id); ?>"
          title="<?php echo esc_attr($tooltip_text); ?>"
          aria-label="<?php echo esc_attr($tooltip_text); ?>">
        <?php for ($i = 0; $i < $full_stars; $i++) : ?>
            <span class="star star-full">⭐</span>
        <?php endfor; ?>
        <?php if ($has_half) : ?>
            <span class="star star-half">⭐</span>
        <?php endif; ?>
        <?php for ($i = 0; $i < $empty_stars; $i++) : ?>
            <span class="star star-empty">☆</span>
        <?php endfor; ?>
        <?php if ($total > 0) : ?>
            <span class="trust-rating-text">(<?php echo esc_html(number_format($rating, 1)); ?>)</span>
        <?php endif; ?>
    </span>
    <?php
    return ob_get_clean();
}

