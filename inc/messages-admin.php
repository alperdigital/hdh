<?php
/**
 * HDH: Admin Messages Management
 * Allows admins to manage all error, success, and UI messages
 */

if (!defined('ABSPATH')) exit;

/**
 * Add admin menu for messages management
 */
function hdh_add_messages_admin_menu() {
    add_submenu_page(
        'hdh-tasks',
        'Mesaj Yönetimi',
        'Mesajlar',
        'manage_options',
        'hdh-messages',
        'hdh_render_messages_admin_page'
    );
}
add_action('admin_menu', 'hdh_add_messages_admin_menu');

/**
 * Enqueue admin styles and scripts for messages management
 */
function hdh_enqueue_messages_admin_assets($hook) {
    if ($hook !== 'gorevler_page_hdh-messages') {
        return;
    }
    
    wp_enqueue_style('hdh-messages-admin', get_template_directory_uri() . '/assets/css/admin-messages.css', array(), '1.0.0');
    wp_enqueue_script('hdh-messages-admin', get_template_directory_uri() . '/assets/js/admin-messages.js', array('jquery'), '1.0.0', true);
}
add_action('admin_enqueue_scripts', 'hdh_enqueue_messages_admin_assets');

/**
 * Get message by key with fallback
 */
function hdh_get_message($category, $key, $default = '') {
    $message_key = 'hdh_message_' . $category . '_' . $key;
    $message = get_option($message_key, '');
    
    if (empty($message)) {
        return $default;
    }
    
    return $message;
}

/**
 * Get default messages
 */
function hdh_get_default_messages() {
    return array(
        'error' => array(
            'generic' => 'Bir hata oluştu. Lütfen tekrar deneyin.',
            'network' => 'Ağ hatası. Lütfen internet bağlantınızı kontrol edin.',
            'timeout' => 'İstek zaman aşımına uğradı. Lütfen tekrar deneyin.',
            'unauthorized' => 'Bu işlem için yetkiniz yok.',
            'not_found' => 'Aradığınız içerik bulunamadı.',
            'validation_failed' => 'Form doğrulaması başarısız. Lütfen tüm alanları kontrol edin.',
        ),
        'success' => array(
            'saved' => 'Başarıyla kaydedildi!',
            'updated' => 'Başarıyla güncellendi!',
            'deleted' => 'Başarıyla silindi!',
            'created' => 'Başarıyla oluşturuldu!',
            'sent' => 'Başarıyla gönderildi!',
        ),
        'verification' => array(
            'email_sent' => 'Doğrulama e-postası gönderildi. Lütfen e-posta kutunuzu kontrol edin.',
            'email_verified' => 'E-posta adresiniz başarıyla doğrulandı!',
            'phone_code_sent' => 'Doğrulama kodu gönderildi. Lütfen telefonunuzu kontrol edin.',
            'phone_verified' => 'Telefon numaranız başarıyla doğrulandı!',
            'code_invalid' => 'Doğrulama kodu geçersiz veya süresi dolmuş.',
            'code_expired' => 'Doğrulama kodu süresi dolmuş. Lütfen yeni kod isteyin.',
        ),
        'ui' => array(
            'loading' => 'Yükleniyor...',
            'saving' => 'Kaydediliyor...',
            'processing' => 'İşleniyor...',
            'sending' => 'Gönderiliyor...',
            'no_results' => 'Sonuç bulunamadı.',
            'empty_state' => 'Henüz içerik yok.',
            'confirm_delete' => 'Bu işlemi geri alamazsınız. Emin misiniz?',
            'confirm_action' => 'Bu işlemi yapmak istediğinize emin misiniz?',
            'confirm_accept_offer' => 'Bu teklifi kabul etmek istediğinize emin misiniz? Diğer tüm teklifler reddedilecek.',
            'confirm_reject_offer' => 'Bu teklifi reddetmek istediğinize emin misiniz?',
            'confirm_complete_exchange' => 'Hediyeleşmeyi tamamladığınızı onaylıyor musunuz? Bu işlem geri alınamaz.',
            'confirm_join_lottery' => 'Çekilişe katılmak için {cost} bilet harcanacak. Devam etmek istiyor musunuz?',
        ),
        'ajax' => array(
            'login_required' => 'Giriş yapmanız gerekiyor',
            'security_failed' => 'Güvenlik kontrolü başarısız',
            'security_verification_failed' => 'Güvenlik doğrulaması başarısız.',
            'invalid_parameters' => 'Geçersiz parametreler',
            'invalid_listing' => 'Geçersiz ilan',
            'listing_not_found' => 'İlan bulunamadı',
            'offer_not_found' => 'Teklif bulunamadı',
            'unauthorized_action' => 'Yetkisiz işlem',
            'fill_all_fields' => 'Lütfen tüm alanları doldurun.',
            'select_at_least_one_gift' => 'En az bir hediye seçmelisiniz.',
            'cannot_offer_own_listing' => 'Kendi ilanınıza teklif yapamazsınız.',
            'listing_not_open' => 'Bu ilan artık açık değil',
            'already_offered' => 'Bu ilana zaten teklif yaptınız',
            'offer_created_success' => 'Teklifiniz başarıyla gönderildi!',
            'offer_sent' => 'Teklifiniz gönderildi',
            'offer_accepted' => 'Teklif kabul edildi',
            'offer_rejected' => 'Teklif reddedildi',
            'exchange_completed' => 'Hediyeleşme tamamlandı!',
            'exchange_confirmation_saved' => 'Onayınız kaydedildi. Diğer tarafın onayını bekliyoruz.',
            'task_id_required' => 'Görev ID gerekli',
            'task_system_inactive' => 'Görev sistemi aktif değil',
            'reward_claimed_success' => 'Ödül başarıyla alındı!',
            'insufficient_tickets' => 'Yetersiz bilet',
            'lottery_max_entries' => 'Bugün bu çekilişe maksimum 3 kez katılabilirsiniz',
            'lottery_join_success' => 'Çekilişe başarıyla katıldınız!',
            'offer_create_error' => 'Teklif oluşturulurken bir hata oluştu.',
            'generic_error' => 'Bir hata oluştu',
            'generic_error_retry' => 'Bir hata oluştu. Lütfen tekrar deneyin.',
        ),
    );
}

/**
 * Initialize default messages
 */
function hdh_init_default_messages() {
    $defaults = hdh_get_default_messages();
    
    foreach ($defaults as $category => $messages) {
        foreach ($messages as $key => $value) {
            $message_key = 'hdh_message_' . $category . '_' . $key;
            if (get_option($message_key) === false) {
                update_option($message_key, $value);
            }
        }
    }
}
add_action('admin_init', 'hdh_init_default_messages');

/**
 * Render messages admin page
 */
function hdh_render_messages_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Bu sayfaya erişim yetkiniz yok.');
    }
    
    // Handle form submission
    if (isset($_POST['hdh_save_messages']) && check_admin_referer('hdh_save_messages')) {
        hdh_save_messages_from_admin();
        settings_errors('hdh_messages');
    }
    
    // Get current category
    $current_category = isset($_GET['category']) ? sanitize_key($_GET['category']) : 'error';
    
    // Define categories
    $categories = array(
        'error' => array(
            'title' => 'Hata Mesajları',
            'icon' => '❌',
            'description' => 'Kullanıcılara gösterilecek hata mesajları',
        ),
        'success' => array(
            'title' => 'Başarı Mesajları',
            'icon' => '✅',
            'description' => 'Kullanıcılara gösterilecek başarı mesajları',
        ),
        'verification' => array(
            'title' => 'Doğrulama Mesajları',
            'icon' => '🔐',
            'description' => 'E-posta ve telefon doğrulama mesajları',
        ),
        'ui' => array(
            'title' => 'UI Mesajları',
            'icon' => '💬',
            'description' => 'Yükleme, onay ve diğer UI mesajları',
        ),
        'ajax' => array(
            'title' => 'AJAX Mesajları',
            'icon' => '🔄',
            'description' => 'Backend AJAX handler\'larından dönen mesajlar',
        ),
    );
    
    // Get current messages
    $defaults = hdh_get_default_messages();
    $current_messages = isset($defaults[$current_category]) ? $defaults[$current_category] : array();
    
    // Load saved messages
    $all_options = wp_load_alloptions();
    $prefix = 'hdh_message_' . $current_category . '_';
    foreach ($all_options as $option_key => $option_value) {
        if (strpos($option_key, $prefix) === 0) {
            $key = str_replace($prefix, '', $option_key);
            $current_messages[$key] = $option_value;
        }
    }
    
    ?>
    <div class="wrap hdh-messages-admin">
        <h1>💬 Mesaj Yönetimi</h1>
        <p class="description">Sitedeki tüm hata, başarı ve UI mesajlarını buradan yönetebilirsiniz.</p>
        
        <!-- Category Tabs -->
        <div class="hdh-messages-tabs">
            <?php foreach ($categories as $cat_key => $cat_info) : ?>
                <a href="<?php echo esc_url(add_query_arg('category', $cat_key)); ?>" 
                   class="hdh-messages-tab <?php echo $current_category === $cat_key ? 'active' : ''; ?>">
                    <span class="tab-icon"><?php echo esc_html($cat_info['icon']); ?></span>
                    <span class="tab-title"><?php echo esc_html($cat_info['title']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Messages Form -->
        <form method="post" action="" id="hdh-messages-form">
            <?php wp_nonce_field('hdh_save_messages'); ?>
            <input type="hidden" name="category" value="<?php echo esc_attr($current_category); ?>">
            
            <div class="hdh-messages-section">
                <div class="hdh-messages-header">
                    <h2>
                        <span class="section-icon"><?php echo esc_html($categories[$current_category]['icon']); ?></span>
                        <?php echo esc_html($categories[$current_category]['title']); ?>
                    </h2>
                    <p class="description"><?php echo esc_html($categories[$current_category]['description']); ?></p>
                </div>
                
                <div class="hdh-messages-fields">
                    <?php foreach ($current_messages as $key => $value) : 
                        $field_id = 'hdh_message_' . $current_category . '_' . $key;
                        $field_name = 'messages[' . $key . ']';
                        $field_label = hdh_format_message_label($key);
                        ?>
                        <div class="hdh-message-field-group">
                            <label for="<?php echo esc_attr($field_id); ?>">
                                <strong><?php echo esc_html($field_label); ?></strong>
                                <input 
                                    type="text"
                                    id="<?php echo esc_attr($field_id); ?>"
                                    name="<?php echo esc_attr($field_name); ?>"
                                    class="hdh-message-field regular-text"
                                    value="<?php echo esc_attr($value); ?>"
                                    placeholder="<?php echo esc_attr($value); ?>" />
                                <span class="field-description">
                                    Mesaj kodu: <code><?php echo esc_html($key); ?></code>
                                </span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="hdh-messages-footer">
                    <p class="submit">
                        <input type="submit" name="hdh_save_messages" class="button button-primary button-large" value="💾 Mesajları Kaydet" />
                        <button type="button" class="button button-secondary hdh-reset-messages" data-category="<?php echo esc_attr($current_category); ?>">
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
 * Format message label
 */
function hdh_format_message_label($key) {
    $key = str_replace('_', ' ', $key);
    $key = ucwords($key);
    return $key;
}

/**
 * Save messages from admin form
 */
function hdh_save_messages_from_admin() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Handle reset
    if (isset($_POST['hdh_reset_messages']) && check_admin_referer('hdh_save_messages')) {
        $category = isset($_POST['category']) ? sanitize_key($_POST['category']) : '';
        if (!empty($category)) {
            $defaults = hdh_get_default_messages();
            if (isset($defaults[$category])) {
                $reset_count = 0;
                foreach ($defaults[$category] as $key => $value) {
                    $message_key = 'hdh_message_' . $category . '_' . $key;
                    if (update_option($message_key, $value)) {
                        $reset_count++;
                    }
                }
                add_settings_error('hdh_messages', 'messages_reset', sprintf('%s kategorisi için %d mesaj varsayılan değerlere döndürüldü!', ucfirst($category), $reset_count), 'updated');
            }
        }
        return;
    }
    
    $category = isset($_POST['category']) ? sanitize_key($_POST['category']) : '';
    $messages = isset($_POST['messages']) ? $_POST['messages'] : array();
    
    if (empty($category) || empty($messages)) {
        add_settings_error('hdh_messages', 'save_error', 'Geçersiz form verisi.', 'error');
        return;
    }
    
    $saved_count = 0;
    foreach ($messages as $key => $value) {
        $key = sanitize_key($key);
        $value = sanitize_textarea_field($value);
        $message_key = 'hdh_message_' . $category . '_' . $key;
        
        if (update_option($message_key, $value)) {
            $saved_count++;
        }
    }
    
    add_settings_error('hdh_messages', 'messages_saved', sprintf('%s kategorisi için %d mesaj kaydedildi!', ucfirst($category), $saved_count), 'updated');
}

