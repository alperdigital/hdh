<?php
/**
 * HDH: Admin System Settings
 * Allows admins to manage system settings like rate limits, max values, etc.
 */

if (!defined('ABSPATH')) exit;

/**
 * Add admin menu for system settings
 */
function hdh_add_settings_admin_menu() {
    add_submenu_page(
        'hdh-tasks',
        'Sistem Ayarları',
        'Sistem Ayarları',
        'manage_options',
        'hdh-settings',
        'hdh_render_settings_admin_page'
    );
}
add_action('admin_menu', 'hdh_add_settings_admin_menu');

/**
 * Enqueue admin styles and scripts for settings
 */
function hdh_enqueue_settings_admin_assets($hook) {
    if ($hook !== 'gorevler_page_hdh-settings') {
        return;
    }
    
    wp_enqueue_style('hdh-settings-admin', get_template_directory_uri() . '/assets/css/admin-settings.css', array(), '1.0.0');
    wp_enqueue_script('hdh-settings-admin', get_template_directory_uri() . '/assets/js/admin-settings.js', array('jquery'), '1.0.0', true);
}
add_action('admin_enqueue_scripts', 'hdh_enqueue_settings_admin_assets');

/**
 * Get setting value with fallback
 */
function hdh_get_setting($key, $default = '') {
    $setting_key = 'hdh_setting_' . $key;
    $value = get_option($setting_key, $default);
    return $value !== '' ? $value : $default;
}

/**
 * Get default settings
 */
function hdh_get_default_settings() {
    return array(
        'rate_limiting' => array(
            'listing_create_per_hour' => 5,
            'offer_create_per_hour' => 10,
            'message_send_per_hour' => 20,
        ),
        'limits' => array(
            'max_offer_items' => 3,
            'max_item_quantity' => 999,
            'min_item_quantity' => 1,
            'max_listing_title_length' => 200,
            'max_listing_description_length' => 1000,
        ),
        'level_requirements' => array(
            'decorations_page_level' => 10,
            'lottery_join_level' => 1,
        ),
        'redirects' => array(
            'default_after_login' => '/profil',
            'default_after_register' => '/profil',
            'default_after_logout' => '/',
        ),
    );
}

/**
 * Initialize default settings
 */
function hdh_init_default_settings() {
    $defaults = hdh_get_default_settings();
    
    foreach ($defaults as $category => $settings) {
        foreach ($settings as $key => $value) {
            $setting_key = 'hdh_setting_' . $key;
            if (get_option($setting_key) === false) {
                update_option($setting_key, $value);
            }
        }
    }
}
add_action('admin_init', 'hdh_init_default_settings');

/**
 * Render settings admin page
 */
function hdh_render_settings_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Bu sayfaya erişim yetkiniz yok.');
    }
    
    // Handle form submission
    if (isset($_POST['hdh_save_settings']) && check_admin_referer('hdh_save_settings')) {
        hdh_save_settings_from_admin();
        settings_errors('hdh_settings');
    }
    
    // Get current tab
    $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'rate_limiting';
    
    // Define tabs
    $tabs = array(
        'rate_limiting' => array(
            'title' => 'Rate Limiting',
            'icon' => '⏱️',
            'description' => 'Sistem hız limitleri ve kısıtlamaları',
        ),
        'limits' => array(
            'title' => 'Maksimum/Minimum Değerler',
            'icon' => '📊',
            'description' => 'Form ve içerik limitleri',
        ),
        'level_requirements' => array(
            'title' => 'Seviye Gereksinimleri',
            'icon' => '⭐',
            'description' => 'Sayfa ve özellik erişim seviye gereksinimleri',
        ),
        'redirects' => array(
            'title' => 'Yönlendirme Ayarları',
            'icon' => '🔄',
            'description' => 'Giriş/çıkış sonrası yönlendirme URL\'leri',
        ),
    );
    
    // Get current settings
    $defaults = hdh_get_default_settings();
    $current_settings = isset($defaults[$current_tab]) ? $defaults[$current_tab] : array();
    
    // Load saved settings
    foreach ($current_settings as $key => $default_value) {
        $current_settings[$key] = hdh_get_setting($key, $default_value);
    }
    
    ?>
    <div class="wrap hdh-settings-admin">
        <h1>⚙️ Sistem Ayarları</h1>
        <p class="description">Sistem limitleri, rate limiting ve diğer teknik ayarları buradan yönetebilirsiniz.</p>
        
        <!-- Settings Tabs -->
        <div class="hdh-settings-tabs">
            <?php foreach ($tabs as $tab_key => $tab_info) : ?>
                <a href="<?php echo esc_url(add_query_arg('tab', $tab_key)); ?>" 
                   class="hdh-settings-tab <?php echo $current_tab === $tab_key ? 'active' : ''; ?>">
                    <span class="tab-icon"><?php echo esc_html($tab_info['icon']); ?></span>
                    <span class="tab-title"><?php echo esc_html($tab_info['title']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Settings Form -->
        <form method="post" action="" id="hdh-settings-form">
            <?php wp_nonce_field('hdh_save_settings'); ?>
            <input type="hidden" name="tab" value="<?php echo esc_attr($current_tab); ?>">
            
            <div class="hdh-settings-section">
                <div class="hdh-settings-header">
                    <h2>
                        <span class="section-icon"><?php echo esc_html($tabs[$current_tab]['icon']); ?></span>
                        <?php echo esc_html($tabs[$current_tab]['title']); ?>
                    </h2>
                    <p class="description"><?php echo esc_html($tabs[$current_tab]['description']); ?></p>
                </div>
                
                <div class="hdh-settings-fields">
                    <?php foreach ($current_settings as $key => $value) : 
                        $field_id = 'hdh_setting_' . $key;
                        $field_name = 'settings[' . $key . ']';
                        $field_label = hdh_format_setting_label($key);
                        $field_description = hdh_get_setting_description($current_tab, $key);
                        ?>
                        <div class="hdh-setting-field-group">
                            <label for="<?php echo esc_attr($field_id); ?>">
                                <strong><?php echo esc_html($field_label); ?></strong>
                                <input 
                                    type="number"
                                    id="<?php echo esc_attr($field_id); ?>"
                                    name="<?php echo esc_attr($field_name); ?>"
                                    class="hdh-setting-field regular-text"
                                    value="<?php echo esc_attr($value); ?>"
                                    min="<?php echo hdh_get_setting_min($key); ?>"
                                    max="<?php echo hdh_get_setting_max($key); ?>"
                                    step="<?php echo hdh_get_setting_step($key); ?>" />
                                <span class="field-description">
                                    <?php echo esc_html($field_description); ?>
                                </span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="hdh-settings-footer">
                    <p class="submit">
                        <input type="submit" name="hdh_save_settings" class="button button-primary button-large" value="💾 Ayarları Kaydet" />
                        <button type="button" class="button button-secondary hdh-reset-settings" data-tab="<?php echo esc_attr($current_tab); ?>">
                            🔄 Varsayılanlara Dön
                        </button>
                    </p>
                </div>
            </div>
        </form>
    </div>
    <?php
}

/**
 * Format setting label
 */
function hdh_format_setting_label($key) {
    $labels = array(
        'listing_create_per_hour' => 'Saatte Maksimum İlan Oluşturma',
        'offer_create_per_hour' => 'Saatte Maksimum Teklif Verme',
        'message_send_per_hour' => 'Saatte Maksimum Mesaj Gönderme',
        'max_offer_items' => 'Maksimum Ürün Sayısı (Vermek)',
        'max_item_quantity' => 'Maksimum Miktar',
        'min_item_quantity' => 'Minimum Miktar',
        'max_listing_title_length' => 'Maksimum Başlık Uzunluğu',
        'max_listing_description_length' => 'Maksimum Açıklama Uzunluğu',
        'decorations_page_level' => 'Dekorlar Sayfası Gerekli Seviye',
        'lottery_join_level' => 'Çekilişe Katılım Gerekli Seviye',
        'default_after_login' => 'Giriş Sonrası Varsayılan URL',
        'default_after_register' => 'Kayıt Sonrası Varsayılan URL',
        'default_after_logout' => 'Çıkış Sonrası Varsayılan URL',
    );
    
    return isset($labels[$key]) ? $labels[$key] : ucwords(str_replace('_', ' ', $key));
}

/**
 * Get setting description
 */
function hdh_get_setting_description($tab, $key) {
    $descriptions = array(
        'rate_limiting' => array(
            'listing_create_per_hour' => 'Kullanıcılar saatte kaç ilan oluşturabilir',
            'offer_create_per_hour' => 'Kullanıcılar saatte kaç teklif verebilir',
            'message_send_per_hour' => 'Kullanıcılar saatte kaç mesaj gönderebilir',
        ),
        'limits' => array(
            'max_offer_items' => 'Bir ilanda en fazla kaç ürün verilebilir',
            'max_item_quantity' => 'Bir ürün için maksimum miktar',
            'min_item_quantity' => 'Bir ürün için minimum miktar',
        ),
        'level_requirements' => array(
            'decorations_page_level' => 'Dekorlar sayfasına erişmek için gereken minimum seviye',
            'lottery_join_level' => 'Çekilişe katılmak için gereken minimum seviye',
        ),
        'redirects' => array(
            'default_after_login' => 'Giriş yaptıktan sonra yönlendirilecek varsayılan sayfa (örn: /profil)',
            'default_after_register' => 'Kayıt olduktan sonra yönlendirilecek varsayılan sayfa',
            'default_after_logout' => 'Çıkış yaptıktan sonra yönlendirilecek varsayılan sayfa',
        ),
    );
    
    if (isset($descriptions[$tab][$key])) {
        return $descriptions[$tab][$key];
    }
    
    return 'Bu ayar ' . $tab . ' kategorisinde kullanılır';
}

/**
 * Get setting min value
 */
function hdh_get_setting_min($key) {
    $mins = array(
        'listing_create_per_hour' => 1,
        'offer_create_per_hour' => 1,
        'message_send_per_hour' => 1,
        'max_offer_items' => 1,
        'max_item_quantity' => 1,
        'min_item_quantity' => 1,
        'decorations_page_level' => 1,
        'lottery_join_level' => 1,
    );
    
    return isset($mins[$key]) ? $mins[$key] : 0;
}

/**
 * Get setting max value
 */
function hdh_get_setting_max($key) {
    $maxs = array(
        'listing_create_per_hour' => 100,
        'offer_create_per_hour' => 100,
        'message_send_per_hour' => 100,
        'max_offer_items' => 10,
        'max_item_quantity' => 9999,
        'min_item_quantity' => 999,
        'decorations_page_level' => 100,
        'lottery_join_level' => 100,
    );
    
    return isset($maxs[$key]) ? $maxs[$key] : 9999;
}

/**
 * Get setting step value
 */
function hdh_get_setting_step($key) {
    return 1;
}

/**
 * Save settings from admin form
 */
function hdh_save_settings_from_admin() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Handle reset
    if (isset($_POST['hdh_reset_settings']) && check_admin_referer('hdh_save_settings')) {
        $tab = isset($_POST['tab']) ? sanitize_key($_POST['tab']) : '';
        if (!empty($tab)) {
            $defaults = hdh_get_default_settings();
            if (isset($defaults[$tab])) {
                $reset_count = 0;
                foreach ($defaults[$tab] as $key => $value) {
                    $setting_key = 'hdh_setting_' . $key;
                    if (update_option($setting_key, $value)) {
                        $reset_count++;
                    }
                }
                add_settings_error('hdh_settings', 'settings_reset', sprintf('%s kategorisi için %d ayar varsayılan değerlere döndürüldü!', ucfirst($tab), $reset_count), 'updated');
            }
        }
        return;
    }
    
    $tab = isset($_POST['tab']) ? sanitize_key($_POST['tab']) : '';
    $settings = isset($_POST['settings']) ? $_POST['settings'] : array();
    
    if (empty($tab) || empty($settings)) {
        add_settings_error('hdh_settings', 'save_error', 'Geçersiz form verisi.', 'error');
        return;
    }
    
    $saved_count = 0;
    foreach ($settings as $key => $value) {
        $key = sanitize_key($key);
        $value = absint($value);
        $setting_key = 'hdh_setting_' . $key;
        
        // Validate min/max
        $min = hdh_get_setting_min($key);
        $max = hdh_get_setting_max($key);
        $value = max($min, min($max, $value));
        
        if (update_option($setting_key, $value)) {
            $saved_count++;
        }
    }
    
    add_settings_error('hdh_settings', 'settings_saved', sprintf('%s kategorisi için %d ayar kaydedildi!', ucfirst($tab), $saved_count), 'updated');
}

