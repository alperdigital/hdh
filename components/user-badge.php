<?php
/**
 * User Badge Component
 * Renders level badge and trust stars
 */

if (!defined('ABSPATH')) exit;

/**
 * Render user badge component
 */
function hdh_render_user_badge_component($user_id, $show_level = true, $show_trust = true, $size = 'medium') {
    if (!$user_id) return '';
    
    ob_start();
    ?>
    <div class="user-badge-component">
        <?php if ($show_level && function_exists('hdh_render_user_badge')) : ?>
            <?php echo hdh_render_user_badge($user_id, $size); ?>
        <?php endif; ?>
        <?php if ($show_trust && function_exists('hdh_render_trust_stars')) : ?>
            <?php echo hdh_render_trust_stars($user_id, $size); ?>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

