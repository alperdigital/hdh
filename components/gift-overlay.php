<?php
/**
 * HDH: Gift Overlay Component
 * Floating gift emoji button showing active trades
 */

if (!defined('ABSPATH')) exit;

/**
 * Render gift overlay
 */
function hdh_render_gift_overlay() {
    if (!is_user_logged_in()) {
        return;
    }
    
    $user_id = get_current_user_id();
    $active_trades = array();
    $action_required_count = 0;
    
    if (function_exists('hdh_get_user_active_trades')) {
        $active_trades = hdh_get_user_active_trades($user_id, false);
        $action_required_trades = hdh_get_user_active_trades($user_id, true);
        $action_required_count = count($action_required_trades);
    }
    
    $total_count = count($active_trades);
    
    // Get badge count preference (admin configurable, default: action required)
    $badge_show_action_only = get_option('hdh_gift_overlay_badge_action_only', true);
    $badge_count = $badge_show_action_only ? $action_required_count : $total_count;
    
    // Hide icon if no active trades (or show with 0 badge based on preference)
    $show_always = get_option('hdh_gift_overlay_show_always', false);
    if (!$show_always && $total_count === 0) {
        return;
    }
    
    ?>
    <div class="hdh-gift-overlay-container">
        <button type="button" 
                class="hdh-gift-overlay-button" 
                id="hdh-gift-overlay-button"
                aria-label="Aktif Hediyeleşmeler"
                aria-expanded="false">
            <span class="gift-icon">🎁</span>
            <?php if ($badge_count > 0) : ?>
                <span class="gift-badge" id="hdh-gift-badge"><?php echo esc_html($badge_count > 99 ? '99+' : $badge_count); ?></span>
            <?php endif; ?>
        </button>
        
        <div class="hdh-gift-overlay-panel" id="hdh-gift-overlay-panel" style="display: none;">
            <div class="gift-overlay-header">
                <h3 class="gift-overlay-title">Aktif Hediyeleşmeler</h3>
                <button type="button" 
                        class="gift-overlay-close" 
                        id="hdh-gift-overlay-close"
                        aria-label="Kapat">
                    ✕
                </button>
            </div>
            
            <div class="gift-overlay-content" id="hdh-gift-overlay-content">
                <?php if (empty($active_trades)) : ?>
                    <div class="gift-overlay-empty">
                        <span class="empty-icon">🎁</span>
                        <p class="empty-text">Aktif hediyeleşme yok</p>
                    </div>
                <?php else : ?>
                    <div class="gift-overlay-trades-list" id="hdh-gift-trades-list">
                        <?php foreach ($active_trades as $trade) : 
                            $level_digits = strlen((string)$trade['counterpart_level']);
                            $level_class = 'lvl-d' . $level_digits;
                        ?>
                            <div class="gift-trade-item" 
                                 data-session-id="<?php echo esc_attr($trade['id']); ?>"
                                 data-listing-id="<?php echo esc_attr($trade['listing_id']); ?>">
                                <div class="gift-trade-header">
                                    <h4 class="gift-trade-title"><?php echo esc_html($trade['listing_title']); ?></h4>
                                    <?php if ($trade['requires_action']) : ?>
                                        <span class="gift-trade-action-badge">Aksiyon Gerekli</span>
                                    <?php endif; ?>
                                </div>
                                <div class="gift-trade-counterpart">
                                    <a href="<?php echo esc_url(home_url('/profil?user=' . $trade['counterpart_id'])); ?>" class="gift-trade-user">
                                        <div class="hdh-level-badge <?php echo esc_attr($level_class); ?>" 
                                             aria-label="Seviye <?php echo esc_attr($trade['counterpart_level']); ?>">
                                            <?php echo esc_html($trade['counterpart_level']); ?>
                                        </div>
                                        <span class="gift-trade-farm-name"><?php echo esc_html($trade['counterpart_name']); ?></span>
                                        <span class="gift-trade-presence"><?php echo esc_html($trade['counterpart_presence']); ?></span>
                                    </a>
                                </div>
                                <div class="gift-trade-progress">
                                    <span class="progress-label">İlerleme:</span>
                                    <span class="progress-value"><?php echo esc_html($trade['current_step']); ?> / 5</span>
                                </div>
                                <!-- Note: Trade details will be loaded directly via JS, no "Aç" button needed -->
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Trade Report Modal -->
    <?php if (function_exists('hdh_render_trade_report_modal')) : ?>
        <?php hdh_render_trade_report_modal(); ?>
    <?php endif; ?>
    <?php
}



