<?php
/**
 * HDH: Notification Bell Component
 * Notification bell icon with dropdown for site notifications
 */

if (!defined('ABSPATH')) exit;

/**
 * Render notification bell
 */
function hdh_render_notification_bell() {
    if (!is_user_logged_in()) {
        return;
    }
    
    $user_id = get_current_user_id();
    $unread_count = 0;
    
    if (function_exists('hdh_get_unread_count')) {
        $unread_count = hdh_get_unread_count($user_id);
    }
    
    ?>
    <div class="hdh-notification-bell-container">
        <button type="button" 
                class="hdh-notification-bell" 
                id="hdh-notification-bell"
                aria-label="Bildirimler"
                aria-expanded="false">
            <span class="bell-icon">🔔</span>
            <?php if ($unread_count > 0) : ?>
                <span class="bell-badge" id="hdh-notification-badge"><?php echo esc_html($unread_count > 99 ? '99+' : $unread_count); ?></span>
            <?php endif; ?>
        </button>
        
        <div class="hdh-notification-dropdown" id="hdh-notification-dropdown" style="display: none;">
            <div class="notification-dropdown-header">
                <h3 class="notification-dropdown-title">Bildirimler</h3>
                <?php if ($unread_count > 0) : ?>
                    <button type="button" 
                            class="btn-mark-all-read" 
                            id="btn-mark-all-read">
                        Tümünü Okundu İşaretle
                    </button>
                <?php endif; ?>
            </div>
            
            <div class="notification-dropdown-content" id="hdh-notification-list">
                <div class="notification-loading">
                    <span>Yükleniyor...</span>
                </div>
            </div>
            
            <div class="notification-dropdown-footer">
                <a href="<?php echo esc_url(home_url('/bildirimler')); ?>" class="notification-view-all">
                    Tümünü Gör
                </a>
            </div>
        </div>
    </div>
    <?php
}



