<?php
/**
 * HDH: Admin Content Management
 * Allows admins to manage all user-facing content via WordPress admin panel
 */

if (!defined('ABSPATH')) exit;

/**
 * Add admin menu for content management
 */
function hdh_add_content_admin_menu() {
    add_submenu_page(
        'hdh-tasks',
        'İçerik Yönetimi',
        'İçerik Yönetimi',
        'manage_options',
        'hdh-content',
        'hdh_render_content_admin_page'
    );
}
add_action('admin_menu', 'hdh_add_content_admin_menu');

/**
 * Enqueue admin styles and scripts for content management
 */
function hdh_enqueue_content_admin_assets($hook) {
    if ($hook !== 'gorevler_page_hdh-content') {
        return;
    }
    
    wp_enqueue_style('hdh-content-admin', get_template_directory_uri() . '/assets/css/admin-content.css', array(), '1.0.0');
    wp_enqueue_script('hdh-content-admin', get_template_directory_uri() . '/assets/js/admin-content.js', array('jquery'), '1.0.0', true);
}
add_action('admin_enqueue_scripts', 'hdh_enqueue_content_admin_assets');

/**
 * Render content admin page
 */
function hdh_render_content_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Bu sayfaya erişim yetkiniz yok.');
    }
    
    // Handle form submission
    if (isset($_POST['hdh_save_content']) && check_admin_referer('hdh_save_content')) {
        hdh_save_content_from_admin();
        settings_errors('hdh_content');
    }
    
    // Get current page
    $current_page = isset($_GET['page_tab']) ? sanitize_key($_GET['page_tab']) : 'homepage';
    
    // Define pages
    $pages = array(
        'homepage' => array(
            'title' => 'Ana Sayfa',
            'icon' => '🏠',
            'description' => 'Ana sayfa başlıkları, butonlar ve metinler',
        ),
        'auth' => array(
            'title' => 'Giriş/Kayıt',
            'icon' => '🔐',
            'description' => 'Giriş ve kayıt sayfası metinleri',
        ),
        'trade_create' => array(
            'title' => 'İlan Ver',
            'icon' => '📝',
            'description' => 'İlan oluşturma sayfası metinleri',
        ),
        'trade_search' => array(
            'title' => 'İlan Ara',
            'icon' => '🔍',
            'description' => 'İlan arama sayfası metinleri',
        ),
        'trade_single' => array(
            'title' => 'Tek İlan',
            'icon' => '📄',
            'description' => 'Tek ilan detay sayfası metinleri',
        ),
        'lottery' => array(
            'title' => 'Çekiliş',
            'icon' => '🎟️',
            'description' => 'Çekiliş sayfası metinleri',
        ),
        'decorations' => array(
            'title' => 'Dekorlar',
            'icon' => '💎',
            'description' => 'Dekorlar sayfası metinleri',
        ),
        'profile' => array(
            'title' => 'Profil',
            'icon' => '👤',
            'description' => 'Profil sayfası metinleri',
        ),
        'navigation' => array(
            'title' => 'Navigasyon',
            'icon' => '🧭',
            'description' => 'Alt menü ve navigasyon metinleri',
        ),
        'footer' => array(
            'title' => 'Footer',
            'icon' => '📄',
            'description' => 'Footer link metinleri',
        ),
        'error_404' => array(
            'title' => '404 Sayfası',
            'icon' => '⚠️',
            'description' => '404 hata sayfası metinleri',
        ),
    );
    
    // Get current page content
    $current_content = hdh_get_page_content($current_page);
    $defaults = hdh_get_default_content($current_page);
    
    // Merge with defaults
    $all_fields = array_merge($defaults, $current_content);
    
    ?>
    <div class="wrap hdh-content-admin">
        <h1>📝 İçerik Yönetimi</h1>
        <p class="description">Sitedeki tüm kullanıcıya yönelik metinleri buradan yönetebilirsiniz. Değişiklikler anında sitede görünecektir.</p>
        
        <!-- Page Tabs -->
        <div class="hdh-content-tabs">
            <?php foreach ($pages as $page_key => $page_info) : ?>
                <a href="<?php echo esc_url(add_query_arg('page_tab', $page_key)); ?>" 
                   class="hdh-content-tab <?php echo $current_page === $page_key ? 'active' : ''; ?>"
                   data-page="<?php echo esc_attr($page_key); ?>">
                    <span class="tab-icon"><?php echo esc_html($page_info['icon']); ?></span>
                    <span class="tab-title"><?php echo esc_html($page_info['title']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Content Form -->
        <form method="post" action="" id="hdh-content-form">
            <?php wp_nonce_field('hdh_save_content'); ?>
            <input type="hidden" name="page" value="<?php echo esc_attr($current_page); ?>">
            
            <div class="hdh-content-section">
                <div class="hdh-content-header">
                    <h2>
                        <span class="section-icon"><?php echo esc_html($pages[$current_page]['icon']); ?></span>
                        <?php echo esc_html($pages[$current_page]['title']); ?>
                    </h2>
                    <p class="description"><?php echo esc_html($pages[$current_page]['description']); ?></p>
                </div>
                
                <div class="hdh-content-fields">
                    <?php foreach ($all_fields as $key => $value) : 
                        $field_id = 'hdh_content_' . $current_page . '_' . $key;
                        $field_name = 'content[' . $key . ']';
                        $field_label = hdh_format_field_label($key);
                        $field_type = hdh_get_field_type($key);
                        ?>
                        <div class="hdh-content-field-group">
                            <label for="<?php echo esc_attr($field_id); ?>">
                                <strong><?php echo esc_html($field_label); ?></strong>
                                <?php if ($field_type === 'textarea') : ?>
                                    <textarea 
                                        id="<?php echo esc_attr($field_id); ?>"
                                        name="<?php echo esc_attr($field_name); ?>"
                                        class="hdh-content-field large-text"
                                        rows="3"
                                        placeholder="<?php echo esc_attr($value); ?>"><?php echo esc_textarea($value); ?></textarea>
                                <?php else : ?>
                                    <input 
                                        type="text"
                                        id="<?php echo esc_attr($field_id); ?>"
                                        name="<?php echo esc_attr($field_name); ?>"
                                        class="hdh-content-field regular-text"
                                        value="<?php echo esc_attr($value); ?>"
                                        placeholder="<?php echo esc_attr($value); ?>" />
                                <?php endif; ?>
                                <span class="field-description">
                                    <?php echo esc_html(hdh_get_field_description($current_page, $key)); ?>
                                </span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="hdh-content-footer">
                    <p class="submit">
                        <input type="submit" name="hdh_save_content" class="button button-primary button-large" value="💾 İçeriği Kaydet" />
                        <button type="button" class="button button-secondary hdh-reset-content" data-page="<?php echo esc_attr($current_page); ?>">
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
 * Format field label from key
 */
function hdh_format_field_label($key) {
    $key = str_replace('_', ' ', $key);
    $key = ucwords($key);
    return $key;
}

/**
 * Get field type
 */
function hdh_get_field_type($key) {
    $textarea_keys = array('subtitle', 'description', 'message', 'text', 'hint');
    foreach ($textarea_keys as $textarea_key) {
        if (strpos($key, $textarea_key) !== false) {
            return 'textarea';
        }
    }
    return 'text';
}

/**
 * Get field description
 */
function hdh_get_field_description($page, $key) {
    $descriptions = array(
        'homepage' => array(
            'headline' => 'Ana sayfanın ana başlığı',
            'subtitle' => 'Ana başlığın altındaki açıklama metni',
            'cta_search_text' => 'İlan Ara butonunun metni',
            'cta_create_text' => 'İlan Ver butonunun metni',
            'trust_indicator_text' => 'Güven göstergesi metni. {count} yerine sayı gelecek',
        ),
        'auth' => array(
            'login_title' => 'Giriş formunun başlığı',
            'login_subtitle' => 'Giriş formunun alt başlığı',
            'error_invalid_credentials' => 'Hatalı kullanıcı adı/şifre mesajı',
        ),
    );
    
    if (isset($descriptions[$page][$key])) {
        return $descriptions[$page][$key];
    }
    
    return 'Bu alan ' . $page . ' sayfasında kullanılır';
}

/**
 * Save content from admin form
 */
function hdh_save_content_from_admin() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $page = isset($_POST['page']) ? sanitize_key($_POST['page']) : '';
    $content = isset($_POST['content']) ? $_POST['content'] : array();
    
    if (empty($page) || empty($content)) {
        add_settings_error('hdh_content', 'save_error', 'Geçersiz form verisi.', 'error');
        return;
    }
    
    $saved_count = 0;
    foreach ($content as $key => $value) {
        $key = sanitize_key($key);
        $value = sanitize_textarea_field($value);
        
        if (hdh_save_content($page, $key, $value)) {
            $saved_count++;
        }
    }
    
    add_settings_error('hdh_content', 'content_saved', sprintf('%s sayfası için %d içerik kaydedildi!', ucfirst($page), $saved_count), 'updated');
}

