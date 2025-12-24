<?php
/**
 * HDH: Presence Admin Settings
 * Admin controls for presence tracking thresholds
 */

if (!defined('ABSPATH')) exit;

/**
 * Render presence admin page
 */
function hdh_render_presence_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Bu sayfaya erişim yetkiniz yok.');
    }
    
    // Handle form submission
    if (isset($_POST['hdh_save_presence_settings']) && check_admin_referer('hdh_save_presence_settings')) {
        hdh_save_presence_settings();
        echo '<div class="notice notice-success"><p>Ayarlar kaydedildi!</p></div>';
    }
    
    // Get current settings
    $online_threshold = get_option('hdh_presence_online_threshold', 120);
    $five_min_threshold = get_option('hdh_presence_5min_threshold', 300);
    $one_hour_threshold = get_option('hdh_presence_1hour_threshold', 3600);
    $privacy_default = get_option('hdh_presence_privacy_default', false);
    
    ?>
    <div class="wrap">
        <h1>Presence Ayarları</h1>
        <p class="description">Kullanıcı varlık takibi için eşik değerlerini ayarlayın.</p>
        
        <form method="post" action="">
            <?php wp_nonce_field('hdh_save_presence_settings'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="hdh_presence_online_threshold">Online Eşiği (saniye)</label>
                    </th>
                    <td>
                        <input type="number" 
                               id="hdh_presence_online_threshold" 
                               name="hdh_presence_online_threshold" 
                               value="<?php echo esc_attr($online_threshold); ?>" 
                               min="30" 
                               max="600" 
                               step="1" 
                               class="regular-text">
                        <p class="description">
                            Kullanıcının "Online" olarak görünmesi için son aktiviteden bu kadar saniye geçmeli. 
                            Varsayılan: 120 saniye (2 dakika)
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="hdh_presence_5min_threshold">5 Dakika Eşiği (saniye)</label>
                    </th>
                    <td>
                        <input type="number" 
                               id="hdh_presence_5min_threshold" 
                               name="hdh_presence_5min_threshold" 
                               value="<?php echo esc_attr($five_min_threshold); ?>" 
                               min="300" 
                               max="3600" 
                               step="1" 
                               class="regular-text">
                        <p class="description">
                            "5 dakika önce" bucket'ı için üst sınır. Varsayılan: 300 saniye (5 dakika)
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="hdh_presence_1hour_threshold">1 Saat Eşiği (saniye)</label>
                    </th>
                    <td>
                        <input type="number" 
                               id="hdh_presence_1hour_threshold" 
                               name="hdh_presence_1hour_threshold" 
                               value="<?php echo esc_attr($one_hour_threshold); ?>" 
                               min="3600" 
                               max="86400" 
                               step="1" 
                               class="regular-text">
                        <p class="description">
                            "1 saat önce" bucket'ı için üst sınır. Varsayılan: 3600 saniye (1 saat)
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="hdh_presence_privacy_default">Varsayılan Gizlilik Modu</label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="hdh_presence_privacy_default" 
                                   name="hdh_presence_privacy_default" 
                                   value="1" 
                                   <?php checked($privacy_default, true); ?>>
                            Yeni kullanıcılar için varsayılan olarak gizlilik modunu etkinleştir
                        </label>
                        <p class="description">
                            Gizlilik modu etkinleştirildiğinde, kullanıcılar sadece kaba bucket'lar görür 
                            (Online, Bugün, Dün, 3+ gün)
                        </p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" 
                       name="hdh_save_presence_settings" 
                       class="button button-primary" 
                       value="Ayarları Kaydet">
            </p>
        </form>
        
        <div class="hdh-presence-info">
            <h2>Bucket Sistemi</h2>
            <p>Kullanıcı varlığı şu bucket'lara ayrılır:</p>
            <ul>
                <li><strong>Online:</strong> Son <?php echo esc_html($online_threshold); ?> saniye içinde aktif</li>
                <li><strong>5 dakika önce:</strong> <?php echo esc_html($online_threshold + 1); ?> - <?php echo esc_html($five_min_threshold); ?> saniye arası</li>
                <li><strong>1 saat önce:</strong> <?php echo esc_html($five_min_threshold + 1); ?> - <?php echo esc_html($one_hour_threshold); ?> saniye arası</li>
                <li><strong>Bugün:</strong> Bugün aktif olmuş (1 saatten fazla)</li>
                <li><strong>Dün:</strong> Dün aktif olmuş</li>
                <li><strong>3+ gün:</strong> 3 günden fazla süre önce aktif olmuş</li>
            </ul>
        </div>
    </div>
    <?php
}

/**
 * Save presence settings
 */
function hdh_save_presence_settings() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Get and validate thresholds
    $online_threshold = isset($_POST['hdh_presence_online_threshold']) ? absint($_POST['hdh_presence_online_threshold']) : 120;
    $five_min_threshold = isset($_POST['hdh_presence_5min_threshold']) ? absint($_POST['hdh_presence_5min_threshold']) : 300;
    $one_hour_threshold = isset($_POST['hdh_presence_1hour_threshold']) ? absint($_POST['hdh_presence_1hour_threshold']) : 3600;
    
    // Validation: thresholds must be positive and in order
    if ($online_threshold < 30 || $online_threshold > 600) {
        $online_threshold = 120; // Reset to default if invalid
    }
    
    if ($five_min_threshold < $online_threshold || $five_min_threshold > 3600) {
        $five_min_threshold = 300; // Reset to default if invalid
    }
    
    if ($one_hour_threshold < $five_min_threshold || $one_hour_threshold > 86400) {
        $one_hour_threshold = 3600; // Reset to default if invalid
    }
    
    // Save settings
    update_option('hdh_presence_online_threshold', $online_threshold);
    update_option('hdh_presence_5min_threshold', $five_min_threshold);
    update_option('hdh_presence_1hour_threshold', $one_hour_threshold);
    update_option('hdh_presence_privacy_default', isset($_POST['hdh_presence_privacy_default']));
}

