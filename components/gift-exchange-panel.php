<?php
/**
 * HDH: Gift Exchange Panel Component
 * Toggleable gift exchange panel with emoji button (exact copy of tasks panel structure)
 */
if (!function_exists('hdh_render_gift_exchange_panel')) {
    function hdh_render_gift_exchange_panel($user_id) {
        if (!$user_id) return;
        
        // Ensure tables exist before rendering
        if (function_exists('hdh_ensure_gift_tables_exist')) {
            hdh_ensure_gift_tables_exist();
        }
        
        // Get total unread count
        $total_unread = 0;
        if (function_exists('hdh_get_total_unread_count')) {
            $total_unread = hdh_get_total_unread_count($user_id);
        }
        ?>
        
        <!-- Gift Exchange Panel Toggle Button (Fixed Position) -->
        <button class="gift-exchange-icon-fixed" id="gift-exchange-icon-toggle" aria-label="Hediyeleşmelerim">
            <span class="gift-exchange-icon-emoji">🎁</span>
            <?php if ($total_unread > 0) : ?>
                <span class="gift-exchange-icon-badge" id="gift-exchange-icon-badge"><?php echo esc_html($total_unread > 99 ? '99+' : $total_unread); ?></span>
            <?php else : ?>
                <span class="gift-exchange-icon-badge" id="gift-exchange-icon-badge" style="display: none;">0</span>
            <?php endif; ?>
        </button>
        
        <!-- Gift Exchange Panel Overlay -->
        <div class="gift-exchange-panel-overlay" id="gift-exchange-panel-overlay"></div>
        
        <!-- Gift Exchange Panel -->
        <div class="gift-exchange-panel" id="gift-exchange-panel">
            <div class="gift-exchange-panel-header">
                <button class="gift-exchange-panel-back" id="gift-exchange-panel-back" aria-label="Geri Dön" style="display: none;">← Geri Dön</button>
                <h3 class="gift-exchange-panel-title">🎁 Hediyeleşmelerim</h3>
                <button class="gift-exchange-panel-close" id="gift-exchange-panel-close" aria-label="Kapat">×</button>
            </div>
            
            <div class="gift-exchange-panel-content" id="gift-exchange-panel-content">
                <!-- Exchanges list will be loaded via JavaScript -->
                <div class="gift-exchange-loading" id="gift-exchange-loading">
                    <p>Yükleniyor...</p>
                </div>
                
                <div class="gift-exchange-empty" id="gift-exchange-empty" style="display: none;">
                    <p>Aktif hediyeleşme yok</p>
                </div>
                
                <div class="gift-exchanges-list" id="gift-exchanges-list" style="display: none;">
                    <!-- Exchange cards will be inserted here -->
                </div>
            </div>
        </div>
        <?php
    }
}
?>

