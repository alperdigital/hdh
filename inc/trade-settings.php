<?php
/**
 * HDH: Trade Settings
 * Admin settings for trade approval requirement
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add trade settings to admin menu
 */
function hdh_add_trade_settings_menu() {
    add_options_page(
        'Takas Ayarları',
        'Takas Ayarları',
        'manage_options',
        'hdh-trade-settings',
        'hdh_trade_settings_page'
    );
}
add_action('admin_menu', 'hdh_add_trade_settings_menu');

/**
 * Render trade settings page
 */
function hdh_trade_settings_page() {
    if (isset($_POST['hdh_trade_settings_submit'])) {
        check_admin_referer('hdh_trade_settings');
        
        $require_approval = isset($_POST['hdh_trade_require_approval']) ? true : false;
        update_option('hdh_trade_require_approval', $require_approval);
        
        echo '<div class="notice notice-success"><p>Ayarlar kaydedildi!</p></div>';
    }
    
    $require_approval = get_option('hdh_trade_require_approval', false);
    ?>
    <div class="wrap">
        <h1>🌾 Takas Ayarları</h1>
        <form method="post" action="">
            <?php wp_nonce_field('hdh_trade_settings'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="hdh_trade_require_approval">İlan Onayı</label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="hdh_trade_require_approval" 
                                   id="hdh_trade_require_approval" 
                                   value="1" 
                                   <?php checked($require_approval, true); ?>>
                            İlanlar onay sonrası görünsün
                        </label>
                        <p class="description">
                            Bu seçeneği işaretlerseniz, oluşturulan ilanlar admin onayından sonra yayınlanır. 
                            İşaretlenmezse ilanlar anında yayınlanır (varsayılan).
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Ayarları Kaydet', 'primary', 'hdh_trade_settings_submit'); ?>
        </form>
    </div>
    <?php
}

