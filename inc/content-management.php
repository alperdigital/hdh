<?php
/**
 * HDH: Content Management System
 * Centralized content management for all user-facing text
 */

if (!defined('ABSPATH')) exit;

/**
 * Get content by key with fallback
 * 
 * @param string $page Page identifier (e.g., 'homepage', 'auth', 'trade')
 * @param string $key Content key (e.g., 'headline', 'subtitle')
 * @param string $default Default value if not set
 * @return string Content value
 */
function hdh_get_content($page, $key, $default = '') {
    $content_key = 'hdh_content_' . $page . '_' . $key;
    $content = get_option($content_key, '');
    
    // If empty, return default
    if (empty($content)) {
        return $default;
    }
    
    return $content;
}

/**
 * Get all content for a page
 * 
 * @param string $page Page identifier
 * @return array All content for the page
 */
function hdh_get_page_content($page) {
    $prefix = 'hdh_content_' . $page . '_';
    $all_options = wp_load_alloptions();
    $page_content = array();
    
    foreach ($all_options as $option_key => $option_value) {
        if (strpos($option_key, $prefix) === 0) {
            $key = str_replace($prefix, '', $option_key);
            $page_content[$key] = $option_value;
        }
    }
    
    return $page_content;
}

/**
 * Save content
 * 
 * @param string $page Page identifier
 * @param string $key Content key
 * @param string $value Content value
 * @return bool Success
 */
function hdh_save_content($page, $key, $value) {
    $content_key = 'hdh_content_' . $page . '_' . $key;
    return update_option($content_key, sanitize_textarea_field($value));
}

/**
 * Get default content for a page
 * 
 * @param string $page Page identifier
 * @return array Default content array
 */
function hdh_get_default_content($page) {
    $defaults = array(
        'homepage' => array(
            'headline' => 'Diğer çiftliklerle hediyeleşmeye başla',
            'subtitle' => 'Diğer çiftliklerle güvenle hediyeleş',
            'cta_search_text' => 'İlan Ara',
            'cta_create_text' => 'İlan Ver',
            'recent_listings_title' => 'Son İlanlar',
            'trust_indicator_text' => '⭐ {count} başarılı hediyeleşme',
        ),
        'auth' => array(
            'login_title' => 'Hesabına Giriş Yap',
            'login_subtitle' => 'Bilet biriktirmek ve hediyeleşmek için giriş yap.',
            'register_title' => 'Yeni Hesap Oluştur',
            'register_subtitle' => 'Hediyeleşmeye başlamak için üye ol.',
            'username_label' => 'Çiftlik Adı veya E-posta',
            'username_placeholder' => 'Çiftlik adınız veya e-posta',
            'email_label' => 'E-posta',
            'email_placeholder' => 'ornek@email.com',
            'password_label' => 'Şifre',
            'password_placeholder' => 'Şifreniz',
            'farm_name_label' => 'Çiftlik Adı',
            'farm_name_placeholder' => 'Çiftlik adınız',
            'remember_me_text' => 'Beni hatırla',
            'login_button_text' => 'Giriş Yap',
            'register_button_text' => 'Üye Ol',
            'error_invalid_credentials' => 'Kullanıcı adı veya şifre hatalı.',
            'error_empty_fields' => 'Lütfen tüm alanları doldurun.',
            'error_generic' => 'Giriş yapılırken bir hata oluştu.',
            'email_verify_message' => 'E-posta\'nı doğrula +1 bilet kazan',
            'phone_verify_message' => 'Telefon numaranı doğrula +4 bilet kazan',
        ),
        'trade_create' => array(
            'page_title' => 'Hediyeleşme Başlasın',
            'wanted_item_label' => 'Hediye İstiyorum',
            'offer_item_label' => 'Vermek İstediğin Hediye',
            'quantity_label' => 'Miktar',
            'submit_button_text' => 'İlanı Oluştur',
            'error_no_wanted_item' => 'Lütfen almak istediğiniz ürünü seçin.',
            'error_invalid_wanted_item' => 'Seçtiğiniz ürün geçersiz.',
            'error_invalid_wanted_qty' => 'Miktar 1-999 arasında olmalıdır.',
            'error_no_offer_items' => 'Lütfen en az 1 ürün seçin (vermek istediğiniz).',
            'error_too_many_offer_items' => 'En fazla 3 ürün seçebilirsiniz.',
            'error_invalid_offer_item' => 'Seçtiğiniz ürünlerden biri geçersiz.',
            'error_invalid_offer_qty' => 'Tüm miktarlar 1-999 arasında olmalıdır.',
            'error_rate_limit' => 'Çok fazla ilan oluşturdunuz. Lütfen 1 saat sonra tekrar deneyin.',
            'success_message' => 'İlanınız başarıyla oluşturuldu!',
        ),
        'trade_search' => array(
            'page_title' => 'İlan Ara',
            'empty_state_message' => 'Henüz ilan bulunmuyor.',
            'loading_message' => 'İlanlar yükleniyor...',
            'no_results_message' => 'Aradığınız kriterlere uygun ilan bulunamadı.',
        ),
        'trade_single' => array(
            'login_button_text' => 'Giriş Yap',
            'offer_button_text' => 'Teklif Ver',
            'message_button_text' => 'Mesaj Gönder',
            'accept_button_text' => 'Kabul Et',
            'reject_button_text' => 'Reddet',
            'completed_status_text' => 'Tamamlandı',
            'closed_status_text' => 'İlan Kapandı',
            'farm_number_label' => '🏡 Çiftlik No:',
            'wanted_label' => 'Hediye İstiyor',
            'offering_label' => 'Hediye Ediyor',
        ),
        'lottery' => array(
            'page_title' => 'Çekiliş',
            'join_button_text' => 'Katıl',
            'login_button_text' => 'Giriş Yap',
            'countdown_ended_text' => 'Çekiliş Tamamlandı! 🎉',
            'lottery_description' => 'Çekilişe katılarak ödüller kazanabilirsiniz.',
        ),
        'decorations' => array(
            'page_title' => 'Hazine Odası',
            'login_required_message' => 'Bu özel hazine odasına erişmek için giriş yapmanız gerekiyor.',
            'level_required_message' => 'Bu hazine odasına erişmek için en az seviye {level} gerekiyor.',
            'level_progress_text' => '🎯 Sadece {levels} seviye daha!',
            'login_button_text' => '🔐 Giriş Yap',
            'search_action_text' => '📋 İlan Ara ve Seviye Atla',
            'create_action_text' => '✨ İlan Ver ve XP Kazan',
            'hint_text' => '💡 İpucu: İlan oluşturmak, takas tamamlamak ve görevleri yapmak size XP kazandırır!',
        ),
        'profile' => array(
            'my_listings_title' => 'İlanlarım',
            'settings_title' => 'Ayarlar',
            'create_listing_button' => 'İlan Oluştur',
            'edit_button_text' => 'Düzenle',
            'delete_button_text' => 'Sil',
            'close_button_text' => 'Kapat',
        ),
    );
    
    return isset($defaults[$page]) ? $defaults[$page] : array();
}

/**
 * Initialize default content (migration)
 */
function hdh_init_default_content() {
    $pages = array('homepage', 'auth', 'trade_create', 'trade_search', 'trade_single', 'lottery', 'decorations', 'profile');
    
    foreach ($pages as $page) {
        $defaults = hdh_get_default_content($page);
        foreach ($defaults as $key => $value) {
            $content_key = 'hdh_content_' . $page . '_' . $key;
            if (get_option($content_key) === false) {
                update_option($content_key, $value);
            }
        }
    }
}
add_action('admin_init', 'hdh_init_default_content');

