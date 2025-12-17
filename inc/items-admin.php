<?php
/**
 * HDH: Admin Items Management
 * Allows admins to manage tradeable items via WordPress admin panel
 */

if (!defined('ABSPATH')) exit;

/**
 * Add admin menu for items management
 */
function hdh_add_items_admin_menu() {
    add_submenu_page(
        'hdh-tasks',
        'Ürün Yönetimi',
        'Ürünler',
        'manage_options',
        'hdh-items',
        'hdh_render_items_admin_page'
    );
}
add_action('admin_menu', 'hdh_add_items_admin_menu');

/**
 * Enqueue admin styles and scripts for items management
 */
function hdh_enqueue_items_admin_assets($hook) {
    if ($hook !== 'gorevler_page_hdh-items') {
        return;
    }
    
    wp_enqueue_media(); // WordPress media library
    wp_enqueue_style('hdh-items-admin', get_template_directory_uri() . '/assets/css/admin-items.css', array(), '1.0.0');
    wp_enqueue_script('hdh-items-admin', get_template_directory_uri() . '/assets/js/admin-items.js', array('jquery'), '1.0.0', true);
    
    // Localize script for media uploader
    wp_localize_script('hdh-items-admin', 'hdhItemsAdmin', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('hdh_items_admin'),
        'uploadTitle' => 'Ürün Görseli Seç',
        'uploadButton' => 'Görseli Kullan',
    ));
}
add_action('admin_enqueue_scripts', 'hdh_enqueue_items_admin_assets');

/**
 * Render items admin page
 */
function hdh_render_items_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Bu sayfaya erişim yetkiniz yok.');
    }
    
    // Handle form submission
    if (isset($_POST['hdh_save_items']) && check_admin_referer('hdh_save_items')) {
        hdh_save_items_from_admin();
        settings_errors('hdh_items');
    }
    
    // Get current items
    $items = get_option('hdh_items_config', array());
    
    // If empty, load from hardcoded config (migration)
    if (empty($items) && function_exists('hdh_get_items_config')) {
        $items = hdh_get_items_config();
        update_option('hdh_items_config', $items);
    }
    
    ?>
    <div class="wrap hdh-items-admin">
        <h1>📦 Ürün Yönetimi</h1>
        <p class="description">Buradan ilan ara ve ilan ver sayfalarında kullanılan ürünleri ekleyebilir, düzenleyebilir ve silebilirsiniz. Her ürün için görsel, isim ve benzersiz ID belirleyebilirsiniz.</p>
        
        <form method="post" action="" id="hdh-items-form">
            <?php wp_nonce_field('hdh_save_items'); ?>
            
            <div class="hdh-items-header">
                <h2>Mevcut Ürünler</h2>
                <button type="button" class="button button-primary" id="add-new-item">
                    <span class="dashicons dashicons-plus-alt"></span> Yeni Ürün Ekle
                </button>
            </div>
            
            <div id="items-list" class="hdh-items-list">
                <?php if (!empty($items)) : ?>
                    <?php foreach ($items as $item_id => $item) : ?>
                        <div class="hdh-item-item" data-item-id="<?php echo esc_attr($item_id); ?>">
                            <div class="hdh-item-header">
                                <span class="hdh-item-number">#<?php echo esc_html($loop_index = array_search($item_id, array_keys($items)) + 1); ?></span>
                                <div class="hdh-item-preview">
                                    <?php if (!empty($item['image'])) : ?>
                                        <img src="<?php echo esc_url($item['image']); ?>" alt="<?php echo esc_attr($item['label']); ?>" class="hdh-item-preview-image" />
                                    <?php else : ?>
                                        <div class="hdh-item-preview-placeholder">
                                            <span class="dashicons dashicons-format-image"></span>
                                        </div>
                                    <?php endif; ?>
                                    <h3 class="hdh-item-title-preview"><?php echo esc_html($item['label']); ?></h3>
                                </div>
                                <button type="button" class="button button-link hdh-toggle-item" aria-label="Ürünü Genişlet/Daralt">
                                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                                </button>
                            </div>
                            
                            <div class="hdh-item-content">
                                <div class="hdh-item-fields">
                                    <div class="hdh-field-group">
                                        <label>
                                            <strong>Ürün ID (Slug) <span class="required">*</span></strong>
                                            <input type="text" name="items[<?php echo esc_attr($item_id); ?>][id]" 
                                                   value="<?php echo esc_attr($item_id); ?>" 
                                                   required 
                                                   class="regular-text"
                                                   placeholder="ornek_urun_id"
                                                   pattern="[a-z0-9_]+"
                                                   title="Sadece küçük harf, rakam ve alt çizgi kullanılabilir" />
                                            <span class="description">Benzersiz ürün kimliği (örn: civata, kalas, bant). Sadece küçük harf, rakam ve alt çizgi kullanılabilir.</span>
                                        </label>
                                    </div>
                                    
                                    <div class="hdh-field-group">
                                        <label>
                                            <strong>Ürün Adı <span class="required">*</span></strong>
                                            <input type="text" name="items[<?php echo esc_attr($item_id); ?>][label]" 
                                                   value="<?php echo esc_attr($item['label']); ?>" 
                                                   required 
                                                   class="large-text"
                                                   placeholder="Örn: Cıvata, Kalas, Bant" />
                                            <span class="description">Kullanıcılara gösterilecek ürün adı</span>
                                        </label>
                                    </div>
                                    
                                    <div class="hdh-field-group">
                                        <label>
                                            <strong>Ürün Görseli <span class="required">*</span></strong>
                                            <div class="hdh-image-upload-wrapper">
                                                <div class="hdh-image-preview-container">
                                                    <?php if (!empty($item['image'])) : ?>
                                                        <img src="<?php echo esc_url($item['image']); ?>" alt="Preview" class="hdh-image-preview" />
                                                        <button type="button" class="button hdh-remove-image" style="display: none;">
                                                            <span class="dashicons dashicons-dismiss"></span> Görseli Kaldır
                                                        </button>
                                                    <?php else : ?>
                                                        <div class="hdh-image-placeholder">
                                                            <span class="dashicons dashicons-format-image"></span>
                                                            <p>Görsel seçilmedi</p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="hdh-image-upload-buttons">
                                                    <button type="button" class="button button-secondary hdh-upload-image" data-item-id="<?php echo esc_attr($item_id); ?>">
                                                        <span class="dashicons dashicons-upload"></span> Görsel Seç veya Yükle
                                                    </button>
                                                    <?php if (!empty($item['image'])) : ?>
                                                        <button type="button" class="button button-link hdh-remove-image-btn" data-item-id="<?php echo esc_attr($item_id); ?>">
                                                            <span class="dashicons dashicons-trash"></span> Görseli Kaldır
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                                <input type="hidden" name="items[<?php echo esc_attr($item_id); ?>][image]" 
                                                       value="<?php echo esc_url($item['image'] ?? ''); ?>" 
                                                       class="hdh-image-url" />
                                                <span class="description">Ürün görseli (SVG, PNG, JPG). Önerilen boyut: 80x80px veya daha büyük kare görsel.</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="hdh-item-actions">
                                    <button type="button" class="button button-secondary hdh-remove-item" data-item-id="<?php echo esc_attr($item_id); ?>">
                                        <span class="dashicons dashicons-trash"></span> Ürünü Sil
                                    </button>
                                    <button type="button" class="button button-link hdh-move-item-up" data-item-id="<?php echo esc_attr($item_id); ?>">
                                        <span class="dashicons dashicons-arrow-up-alt"></span> Yukarı Taşı
                                    </button>
                                    <button type="button" class="button button-link hdh-move-item-down" data-item-id="<?php echo esc_attr($item_id); ?>">
                                        <span class="dashicons dashicons-arrow-down-alt"></span> Aşağı Taşı
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="hdh-empty-state">
                        <p>Henüz ürün eklenmemiş. İlk ürününüzü eklemek için "Yeni Ürün Ekle" butonuna tıklayın.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="hdh-items-footer">
                <p class="submit">
                    <input type="submit" name="hdh_save_items" class="button button-primary button-large" value="💾 Tüm Ürünleri Kaydet" />
                    <span class="description" style="margin-left: 15px;">Değişiklikleriniz kaydedildikten sonra ilan ara ve ilan ver sayfalarında yeni ürünler görünecek.</span>
                </p>
            </div>
        </form>
    </div>
    
    <!-- Template for new item -->
    <script type="text/template" id="hdh-item-template">
        <div class="hdh-item-item" data-item-id="{{itemId}}">
            <div class="hdh-item-header">
                <span class="hdh-item-number">#{{itemNumber}}</span>
                <div class="hdh-item-preview">
                    <div class="hdh-item-preview-placeholder">
                        <span class="dashicons dashicons-format-image"></span>
                    </div>
                    <h3 class="hdh-item-title-preview">Yeni Ürün</h3>
                </div>
                <button type="button" class="button button-link hdh-toggle-item" aria-label="Ürünü Genişlet/Daralt">
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </button>
            </div>
            <div class="hdh-item-content">
                <div class="hdh-item-fields">
                    <div class="hdh-field-group">
                        <label>
                            <strong>Ürün ID (Slug) <span class="required">*</span></strong>
                            <input type="text" name="items[{{itemId}}][id]" value="{{itemId}}" required class="regular-text" placeholder="ornek_urun_id" pattern="[a-z0-9_]+" title="Sadece küçük harf, rakam ve alt çizgi kullanılabilir" />
                            <span class="description">Benzersiz ürün kimliği (örn: civata, kalas, bant). Sadece küçük harf, rakam ve alt çizgi kullanılabilir.</span>
                        </label>
                    </div>
                    <div class="hdh-field-group">
                        <label>
                            <strong>Ürün Adı <span class="required">*</span></strong>
                            <input type="text" name="items[{{itemId}}][label]" value="" required class="large-text" placeholder="Örn: Cıvata, Kalas, Bant" />
                            <span class="description">Kullanıcılara gösterilecek ürün adı</span>
                        </label>
                    </div>
                    <div class="hdh-field-group">
                        <label>
                            <strong>Ürün Görseli <span class="required">*</span></strong>
                            <div class="hdh-image-upload-wrapper">
                                <div class="hdh-image-preview-container">
                                    <div class="hdh-image-placeholder">
                                        <span class="dashicons dashicons-format-image"></span>
                                        <p>Görsel seçilmedi</p>
                                    </div>
                                </div>
                                <div class="hdh-image-upload-buttons">
                                    <button type="button" class="button button-secondary hdh-upload-image" data-item-id="{{itemId}}">
                                        <span class="dashicons dashicons-upload"></span> Görsel Seç veya Yükle
                                    </button>
                                </div>
                                <input type="hidden" name="items[{{itemId}}][image]" value="" class="hdh-image-url" />
                                <span class="description">Ürün görseli (SVG, PNG, JPG). Önerilen boyut: 80x80px veya daha büyük kare görsel.</span>
                            </div>
                        </label>
                    </div>
                </div>
                <div class="hdh-item-actions">
                    <button type="button" class="button button-secondary hdh-remove-item" data-item-id="{{itemId}}">
                        <span class="dashicons dashicons-trash"></span> Ürünü Sil
                    </button>
                </div>
            </div>
        </div>
    </script>
    <?php
}

/**
 * Save items from admin form
 */
function hdh_save_items_from_admin() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $items = isset($_POST['items']) ? $_POST['items'] : array();
    
    // Sanitize and validate
    $sanitized_items = array();
    foreach ($items as $item_id => $item) {
        if (empty($item['id']) || empty($item['label'])) {
            continue; // Skip invalid items
        }
        
        // Sanitize item ID (slug): only lowercase letters, numbers, and underscores
        $item_id_clean = sanitize_key($item['id']);
        if (empty($item_id_clean)) {
            continue; // Skip if sanitization results in empty string
        }
        
        // Validate image URL
        $image_url = isset($item['image']) ? esc_url_raw($item['image']) : '';
        if (!empty($image_url) && !filter_var($image_url, FILTER_VALIDATE_URL)) {
            $image_url = ''; // Invalid URL, set to empty
        }
        
        $sanitized_items[$item_id_clean] = array(
            'label' => sanitize_text_field($item['label']),
            'image' => $image_url,
        );
    }
    
    update_option('hdh_items_config', $sanitized_items);
    
    add_settings_error('hdh_items', 'items_saved', 'Ürünler başarıyla kaydedildi! (' . count($sanitized_items) . ' ürün)', 'updated');
}

