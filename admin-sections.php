<?php
/**
 * Admin Panel - Bölüm Yönetimi
 */

// Custom Post Type: Bölümler
function mi_register_sections_post_type() {
    $labels = array(
        'name' => 'Bölümler',
        'singular_name' => 'Bölüm',
        'menu_name' => 'Bölümler',
        'add_new' => 'Yeni Bölüm Ekle',
        'add_new_item' => 'Yeni Bölüm Ekle',
        'edit_item' => 'Bölümü Düzenle',
        'new_item' => 'Yeni Bölüm',
        'view_item' => 'Bölümü Görüntüle',
        'search_items' => 'Bölüm Ara',
        'not_found' => 'Bölüm bulunamadı',
        'not_found_in_trash' => 'Çöp kutusunda bölüm bulunamadı'
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'bolum'),
        'capability_type' => 'post',
        'has_archive' => false,
        'hierarchical' => false,
        'menu_position' => 20,
        'menu_icon' => 'dashicons-admin-page',
        'supports' => array('title', 'editor', 'thumbnail', 'page-attributes'),
        'show_in_rest' => true,
    );

    register_post_type('mi_section', $args);
}
add_action('init', 'mi_register_sections_post_type');

// Meta Box: Bölüm Ayarları
function mi_add_section_meta_boxes() {
    add_meta_box(
        'mi_section_settings',
        'Bölüm Ayarları',
        'mi_section_settings_callback',
        'mi_section',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'mi_add_section_meta_boxes');

// Meta Box Callback
function mi_section_settings_callback($post) {
    wp_nonce_field('mi_section_meta_box', 'mi_section_meta_box_nonce');
    
    $section_name = get_post_meta($post->ID, '_mi_section_name', true);
    $section_type = get_post_meta($post->ID, '_mi_section_type', true);
    $ui_template = get_post_meta($post->ID, '_mi_ui_template', true);
    $menu_order = $post->menu_order;
    $is_active = get_post_meta($post->ID, '_mi_section_active', true);
    
    ?>
    <div class="section-settings-notice" style="background: #f0f0f1; padding: 15px; border-left: 4px solid #2271b1; margin-bottom: 20px;">
        <p style="margin: 0;"><strong>💡 İpucu:</strong> Bu bölümü düzenlemek için üstteki <strong>Başlık</strong> ve <strong>İçerik</strong> alanlarını kullanabilirsiniz. Aşağıdaki ayarlar bölümün görünümünü ve davranışını kontrol eder.</p>
    </div>
    
    <table class="form-table">
        <tr>
            <th><label for="mi_section_name">📝 Bölüm İsmi</label></th>
            <td>
                <input type="text" id="mi_section_name" name="mi_section_name" 
                       value="<?php echo esc_attr($section_name ?: $post->post_title); ?>" 
                       class="regular-text" />
                <p class="description">Menüde görünecek bölüm ismi (boş bırakılırsa başlık kullanılır)</p>
            </td>
        </tr>
        <tr>
            <th><label for="mi_section_type">🎨 Sayfa Tipi</label></th>
            <td>
                <select id="mi_section_type" name="mi_section_type" class="regular-text">
                    <option value="default" <?php selected($section_type, 'default'); ?>>📄 Varsayılan</option>
                    <option value="aciklama" <?php selected($section_type, 'aciklama'); ?>>📝 Açıklama</option>
                    <option value="manset" <?php selected($section_type, 'manset'); ?>>📰 Manşet (Haber Listesi)</option>
                    <option value="kararlar" <?php selected($section_type, 'kararlar'); ?>>⚖️ Kararlar (Karar Listesi)</option>
                    <option value="iletisim" <?php selected($section_type, 'iletisim'); ?>>📧 İletişim</option>
                    <option value="custom" <?php selected($section_type, 'custom'); ?>>🎨 Özel İçerik</option>
                </select>
                <p class="description"><strong>Önemli:</strong> Sayfa tipini değiştirdiğinizde, aşağıda ilgili özel ayarlar görünecektir.</p>
            </td>
        </tr>
        <tr>
            <th><label for="mi_ui_template">📍 UI Tasarım Konumu</label></th>
            <td>
                <select id="mi_ui_template" name="mi_ui_template" class="regular-text">
                    <option value="default" <?php selected($ui_template, 'default'); ?>>Varsayılan</option>
                    <option value="top" <?php selected($ui_template, 'top'); ?>>Üstte</option>
                    <option value="bottom" <?php selected($ui_template, 'bottom'); ?>>Altta</option>
                    <option value="sidebar" <?php selected($ui_template, 'sidebar'); ?>>Yan Bar</option>
                    <option value="none" <?php selected($ui_template, 'none'); ?>>Gösterme</option>
                </select>
                <p class="description">UI tasarım bileşeninin sayfadaki konumu (Manşet, Kararlar gibi özel bölümler için)</p>
            </td>
        </tr>
        <tr>
            <th><label for="menu_order">🔢 Sıralama</label></th>
            <td>
                <input type="number" id="menu_order" name="menu_order" 
                       value="<?php echo esc_attr($menu_order); ?>" 
                       class="small-text" min="0" />
                <p class="description">Menüdeki sıralama (düşük sayı = üstte, örn: 0, 1, 2...)</p>
            </td>
        </tr>
        <tr>
            <th><label for="mi_section_active">✅ Aktif Durumu</label></th>
            <td>
                <label>
                    <input type="checkbox" id="mi_section_active" name="mi_section_active" 
                           value="1" <?php checked($is_active, '1'); ?> />
                    <strong>Bu bölümü menüde göster</strong>
                </label>
                <p class="description">İşaretlenirse bölüm menüde görünür, işaretlenmezse gizlenir.</p>
            </td>
        </tr>
    </table>
    
    <h3>📋 Sayfa Tipi Özel Ayarları</h3>
    <p class="description" style="margin-bottom: 20px;">Seçtiğiniz sayfa tipine göre özel ayarlar burada görünecektir.</p>
    
    <div id="manset-settings" class="section-type-settings" style="display:none;">
        <div class="notice notice-info inline" style="margin: 15px 0;">
            <p><strong>💡 Manşet Bölümü:</strong> Bu bölüm haber listesini gösterir. Kategori, yazar ve sıralama filtreleri ile haberleri filtreleyebilirsiniz.</p>
        </div>
        <table class="form-table">
            <tr>
                <th><label>🔍 Filtreleme Seçenekleri</label></th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="manset_filter_category" value="1" 
                                   <?php checked(get_post_meta($post->ID, '_mi_manset_filter_category', true), '0', false); ?> />
                            <strong>Kategori Filtresi</strong> (Varsayılan: Kapalı)
                        </label><br>
                        <label>
                            <input type="checkbox" name="manset_filter_author" value="1" 
                                   <?php checked(get_post_meta($post->ID, '_mi_manset_filter_author', true), '1'); ?> />
                            <strong>Yazar Filtresi</strong> (Varsayılan: Açık, "Tümü" seçili)
                        </label><br>
                        <label>
                            <input type="checkbox" name="manset_filter_sort" value="1" 
                                   <?php checked(get_post_meta($post->ID, '_mi_manset_filter_sort', true), '1'); ?> />
                            <strong>Sıralama Filtresi</strong> (Varsayılan: Açık)
                        </label>
                    </fieldset>
                    <p class="description">Manşet sayfasında hangi filtrelerin gösterileceğini seçin.</p>
                </td>
            </tr>
            <tr>
                <th><label for="manset_posts_per_page">📄 Sayfa Başına Haber Sayısı</label></th>
                <td>
                    <input type="number" id="manset_posts_per_page" name="manset_posts_per_page" 
                           value="<?php echo esc_attr(get_post_meta($post->ID, '_mi_manset_posts_per_page', true) ?: '12'); ?>" 
                           class="small-text" min="1" max="50" />
                    <p class="description">Bir sayfada gösterilecek haber sayısı (varsayılan: 12)</p>
                </td>
            </tr>
            <tr>
                <th><label for="manset_default_sort">🔀 Varsayılan Sıralama</label></th>
                <td>
                    <select id="manset_default_sort" name="manset_default_sort" class="regular-text">
                        <option value="date-desc" <?php selected(get_post_meta($post->ID, '_mi_manset_default_sort', true), 'date-desc'); ?>>Tarih (Yeniden Eskiye)</option>
                        <option value="date-asc" <?php selected(get_post_meta($post->ID, '_mi_manset_default_sort', true), 'date-asc'); ?>>Tarih (Eskiden Yeniye)</option>
                        <option value="editor-choice" <?php selected(get_post_meta($post->ID, '_mi_manset_default_sort', true), 'editor-choice'); ?>>Editörün Seçimi</option>
                    </select>
                    <p class="description">Sayfa ilk yüklendiğinde hangi sıralamanın aktif olacağını seçin</p>
                </td>
            </tr>
            <tr>
                <th><label>📊 Haber Detayları</label></th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="manset_show_views" value="1" 
                                   <?php checked(get_post_meta($post->ID, '_mi_manset_show_views', true), '0', false); ?> />
                            <strong>Görüntülenme Sayısı</strong> (Varsayılan: Kapalı)
                        </label><br>
                        <label>
                            <input type="checkbox" name="manset_show_reading_time" value="1" 
                                   <?php checked(get_post_meta($post->ID, '_mi_manset_show_reading_time', true), '0', false); ?> />
                            <strong>Okuma Süresi</strong> (Varsayılan: Kapalı)
                        </label><br>
                        <label>
                            <input type="checkbox" name="manset_show_category" value="1" 
                                   <?php checked(get_post_meta($post->ID, '_mi_manset_show_category', true), '0', false); ?> />
                            <strong>Kategori Badge</strong> (Varsayılan: Kapalı)
                        </label>
                    </fieldset>
                    <p class="description">Haber kartlarında hangi detayların gösterileceğini seçin</p>
                </td>
            </tr>
            <tr>
                <th><label>💬 Yorumlar</label></th>
                <td>
                    <label>
                        <input type="checkbox" name="manset_enable_comments" value="1" 
                               <?php checked(get_post_meta($post->ID, '_mi_manset_enable_comments', true), '1'); ?> />
                        <strong>Yazılara yorum yapılmasına izin ver</strong> (Varsayılan: Kapalı)
                    </label>
                    <p class="description">İşaretlenirse yazılara yorum yapılabilecek, işaretlenmezse yorumlar kapalı olacak</p>
                </td>
            </tr>
        </table>
    </div>
    
    <div id="kararlar-settings" class="section-type-settings" style="display:none;">
        <div class="notice notice-info inline" style="margin: 15px 0;">
            <p><strong>💡 Kararlar Bölümü:</strong> Bu bölüm karar listesini gösterir. Dava türü, mahkeme ve sıralama filtreleri ile kararları filtreleyebilirsiniz.</p>
        </div>
        <table class="form-table">
            <tr>
                <th><label>🔍 Filtreleme Seçenekleri</label></th>
                <td>
                    <fieldset>
                        <label><input type="checkbox" name="kararlar_filter_dava" value="1" checked /> Dava Türü Filtresi</label><br>
                        <label><input type="checkbox" name="kararlar_filter_mahkeme" value="1" checked /> Mahkeme Filtresi</label><br>
                        <label><input type="checkbox" name="kararlar_filter_sort" value="1" checked /> Sıralama Filtresi</label>
                    </fieldset>
                    <p class="description">Kararlar sayfasında hangi filtrelerin gösterileceğini seçin.</p>
                </td>
            </tr>
            <tr>
                <th><label for="kararlar_posts_per_page">📄 Sayfa Başına Karar Sayısı</label></th>
                <td>
                    <input type="number" id="kararlar_posts_per_page" name="kararlar_posts_per_page" 
                           value="<?php echo esc_attr(get_post_meta($post->ID, '_mi_kararlar_posts_per_page', true) ?: '12'); ?>" 
                           class="small-text" min="1" max="50" />
                    <p class="description">Bir sayfada gösterilecek karar sayısı (varsayılan: 12)</p>
                </td>
            </tr>
        </table>
    </div>
    
    <div id="iletisim-settings" class="section-type-settings" style="display:none;">
        <div class="notice notice-info inline" style="margin: 15px 0;">
            <p><strong>💡 İletişim Bölümü:</strong> Bu bölüm iletişim bilgilerini ve yazı gönderme kurallarını gösterir. Alt bölümleri opsiyonel olarak ekleyebilir veya çıkarabilirsiniz.</p>
        </div>
        <table class="form-table">
            <tr>
                <th><label for="iletisim_email">📧 E-posta Adresi</label></th>
                <td>
                    <input type="email" id="iletisim_email" name="iletisim_email" 
                           value="<?php echo esc_attr(get_post_meta($post->ID, '_mi_iletisim_email', true) ?: get_option('admin_email')); ?>" 
                           class="regular-text" />
                    <p class="description">İletişim sayfasında gösterilecek e-posta adresi (Tüm bölümlerde kullanılır)</p>
                </td>
            </tr>
            <tr>
                <th><label for="iletisim_response_time">⏰ Yanıt Süresi</label></th>
                <td>
                    <input type="text" id="iletisim_response_time" name="iletisim_response_time" 
                           value="<?php echo esc_attr(get_post_meta($post->ID, '_mi_iletisim_response_time', true) ?: '24-48 Saat'); ?>" 
                           class="regular-text" placeholder="24-48 Saat" />
                    <p class="description">Mesajlara yanıt verme süresi bilgisi (örn: "24-48 Saat", "1-2 İş Günü")</p>
                </td>
            </tr>
            <tr>
                <th><label for="iletisim_intro_title">📝 Giriş Başlığı</label></th>
                <td>
                    <input type="text" id="iletisim_intro_title" name="iletisim_intro_title" 
                           value="<?php echo esc_attr(get_post_meta($post->ID, '_mi_iletisim_intro_title', true) ?: 'Yazılarınızı Paylaşın'); ?>" 
                           class="regular-text" />
                    <p class="description">İletişim bölümünün üst kısmında gösterilecek ana başlık</p>
                </td>
            </tr>
            <tr>
                <th><label for="iletisim_intro_text">📄 Giriş Metni</label></th>
                <td>
                    <textarea id="iletisim_intro_text" name="iletisim_intro_text" 
                              class="large-text" rows="3"><?php echo esc_textarea(get_post_meta($post->ID, '_mi_iletisim_intro_text', true) ?: 'Görüşlerinizi, önerilerinizi ve yazılarınızı bizimle paylaşın. Değerli katkılarınız yayınlanabilir ve toplumla paylaşılabilir.'); ?></textarea>
                    <p class="description">İletişim bölümünün üst kısmında gösterilecek açıklama metni</p>
                </td>
            </tr>
            <tr>
                <th><label>📋 Alt Bölümler</label></th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="iletisim_show_info" value="1" 
                                   <?php checked(get_post_meta($post->ID, '_mi_iletisim_show_info', true), '1'); ?> />
                            <strong>İletişim Bilgileri</strong> bölümünü göster
                        </label><br>
                        <label>
                            <input type="checkbox" name="iletisim_show_rules" value="1" 
                                   <?php checked(get_post_meta($post->ID, '_mi_iletisim_show_rules', true), '0'); ?> />
                            <strong>Yazı Gönderme Kuralları</strong> bölümünü göster (Opsiyonel)
                        </label><br>
                        <label>
                            <input type="checkbox" name="iletisim_show_quick" value="1" 
                                   <?php checked(get_post_meta($post->ID, '_mi_iletisim_show_quick', true), '1'); ?> />
                            <strong>Hızlı İletişim</strong> bölümünü göster
                        </label>
                    </fieldset>
                    <p class="description">Hangi alt bölümlerin gösterileceğini seçin</p>
                </td>
            </tr>
            <tr>
                <th><label for="iletisim_info_title">📞 İletişim Bilgileri Başlığı</label></th>
                <td>
                    <input type="text" id="iletisim_info_title" name="iletisim_info_title" 
                           value="<?php echo esc_attr(get_post_meta($post->ID, '_mi_iletisim_info_title', true) ?: 'İletişim Bilgileri'); ?>" 
                           class="regular-text" />
                    <p class="description">İletişim Bilgileri bölümünün başlığı</p>
                </td>
            </tr>
            <tr>
                <th><label for="iletisim_info_email_text">📧 E-posta Açıklama Metni</label></th>
                <td>
                    <textarea id="iletisim_info_email_text" name="iletisim_info_email_text" 
                              class="large-text" rows="2"><?php echo esc_textarea(get_post_meta($post->ID, '_mi_iletisim_info_email_text', true) ?: 'Yazılarınızı ve görüşlerinizi e-posta ile gönderebilirsiniz.'); ?></textarea>
                    <p class="description">E-posta bilgisinin altında gösterilecek açıklama metni</p>
                </td>
            </tr>
            <tr>
                <th><label for="iletisim_info_response_text">⏰ Yanıt Süresi Açıklama Metni</label></th>
                <td>
                    <textarea id="iletisim_info_response_text" name="iletisim_info_response_text" 
                              class="large-text" rows="2"><?php echo esc_textarea(get_post_meta($post->ID, '_mi_iletisim_info_response_text', true) ?: 'Mesajlarınıza en kısa sürede yanıt vermeye çalışıyoruz.'); ?></textarea>
                    <p class="description">Yanıt süresi bilgisinin altında gösterilecek açıklama metni</p>
                </td>
            </tr>
            <tr>
                <th><label for="iletisim_info_evaluation_text">📝 Yazı Değerlendirme Açıklama Metni</label></th>
                <td>
                    <textarea id="iletisim_info_evaluation_text" name="iletisim_info_evaluation_text" 
                              class="large-text" rows="2"><?php echo esc_textarea(get_post_meta($post->ID, '_mi_iletisim_info_evaluation_text', true) ?: 'Gönderdiğiniz yazılar değerlendirilerek yayınlanabilir.'); ?></textarea>
                    <p class="description">Yazı değerlendirme bilgisinin altında gösterilecek açıklama metni</p>
                </td>
            </tr>
            <tr>
                <th><label for="iletisim_rules_title">📋 Yazı Gönderme Kuralları Başlığı</label></th>
                <td>
                    <input type="text" id="iletisim_rules_title" name="iletisim_rules_title" 
                           value="<?php echo esc_attr(get_post_meta($post->ID, '_mi_iletisim_rules_title', true) ?: 'Yazı Gönderme Kuralları'); ?>" 
                           class="regular-text" />
                    <p class="description">Yazı Gönderme Kuralları bölümünün başlığı</p>
                </td>
            </tr>
            <tr>
                <th><label for="iletisim_rules_content">📋 Yazı Gönderme Kuralları İçeriği</label></th>
                <td>
                    <textarea id="iletisim_rules_content" name="iletisim_rules_content" 
                              class="large-text" rows="8"><?php echo esc_textarea(get_post_meta($post->ID, '_mi_iletisim_rules_content', true) ?: '✅ Orijinal İçerik: Gönderdiğiniz yazıların orijinal ve özgün olması gerekmektedir. Başka kaynaklardan alıntı yapıyorsanız kaynak belirtiniz.

📝 Uygun Dil: Yazılarınızda saygılı ve uygun bir dil kullanınız. Nefret söylemi, ayrımcılık veya saldırgan içerik içeren yazılar yayınlanmayacaktır.

📏 Uzunluk: Yazılarınızın en az 200 kelime olması önerilir. Ancak daha kısa veya uzun yazılar da değerlendirilebilir.

🔍 Değerlendirme: Gönderilen tüm yazılar editörlerimiz tarafından değerlendirilir. Yayınlanma kararı editörlerimize aittir.'); ?></textarea>
                    <p class="description">Yazı Gönderme Kuralları bölümünün içeriği (Her kuralı ayrı satırda yazın)</p>
                </td>
            </tr>
            <tr>
                <th><label for="iletisim_quick_title">🚀 Hızlı İletişim Başlığı</label></th>
                <td>
                    <input type="text" id="iletisim_quick_title" name="iletisim_quick_title" 
                           value="<?php echo esc_attr(get_post_meta($post->ID, '_mi_iletisim_quick_title', true) ?: 'Hızlı İletişim'); ?>" 
                           class="regular-text" />
                    <p class="description">Hızlı İletişim bölümünün başlığı</p>
                </td>
            </tr>
            <tr>
                <th><label for="iletisim_quick_text">🚀 Hızlı İletişim Açıklama Metni</label></th>
                <td>
                    <textarea id="iletisim_quick_text" name="iletisim_quick_text" 
                              class="large-text" rows="2"><?php echo esc_textarea(get_post_meta($post->ID, '_mi_iletisim_quick_text', true) ?: 'Doğrudan e-posta göndermek isterseniz:'); ?></textarea>
                    <p class="description">Hızlı İletişim bölümünün açıklama metni</p>
                </td>
            </tr>
            <tr>
                <th><label for="iletisim_quick_button_text">🚀 Hızlı İletişim Buton Metni</label></th>
                <td>
                    <input type="text" id="iletisim_quick_button_text" name="iletisim_quick_button_text" 
                           value="<?php echo esc_attr(get_post_meta($post->ID, '_mi_iletisim_quick_button_text', true) ?: 'E-posta Gönder'); ?>" 
                           class="regular-text" />
                    <p class="description">Hızlı İletişim butonunun üzerinde gösterilecek metin</p>
                </td>
            </tr>
        </table>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        function toggleSectionSettings() {
            $('.section-type-settings').hide();
            var type = $('#mi_section_type').val();
            if (type) {
                $('#' + type + '-settings').show();
            }
        }
        
        $('#mi_section_type').on('change', toggleSectionSettings);
        toggleSectionSettings();
    });
    </script>
    <?php
}

// Save Meta Box Data
function mi_save_section_meta_box($post_id) {
    if (!isset($_POST['mi_section_meta_box_nonce']) || 
        !wp_verify_nonce($_POST['mi_section_meta_box_nonce'], 'mi_section_meta_box')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['mi_section_name'])) {
        $section_name = sanitize_text_field($_POST['mi_section_name']);
        // İLETIŞIM -> İLETİŞİM düzeltmesi (kaydetmeden önce)
        $section_name = str_replace('İLETIŞIM', 'İLETİŞİM', $section_name);
        $section_name = preg_replace('/İLET[Iİ]ŞIM/i', 'İLETİŞİM', $section_name);
        update_post_meta($post_id, '_mi_section_name', $section_name);
    }

    if (isset($_POST['mi_section_type'])) {
        update_post_meta($post_id, '_mi_section_type', sanitize_text_field($_POST['mi_section_type']));
    }

    if (isset($_POST['mi_ui_template'])) {
        update_post_meta($post_id, '_mi_ui_template', sanitize_text_field($_POST['mi_ui_template']));
    }

    if (isset($_POST['mi_section_active'])) {
        update_post_meta($post_id, '_mi_section_active', '1');
    } else {
        update_post_meta($post_id, '_mi_section_active', '0');
    }

    // Manşet özel ayarları
    if (isset($_POST['manset_posts_per_page'])) {
        update_post_meta($post_id, '_mi_manset_posts_per_page', intval($_POST['manset_posts_per_page']));
    }
    
    // Filtreleme seçenekleri
    update_post_meta($post_id, '_mi_manset_filter_category', isset($_POST['manset_filter_category']) ? '1' : '0');
    update_post_meta($post_id, '_mi_manset_filter_author', isset($_POST['manset_filter_author']) ? '1' : '0');
    update_post_meta($post_id, '_mi_manset_filter_sort', isset($_POST['manset_filter_sort']) ? '1' : '0');
    
    // Varsayılan sıralama
    if (isset($_POST['manset_default_sort'])) {
        update_post_meta($post_id, '_mi_manset_default_sort', sanitize_text_field($_POST['manset_default_sort']));
    }
    
    // Görüntüleme seçenekleri
    update_post_meta($post_id, '_mi_manset_show_category', isset($_POST['manset_show_category']) ? '1' : '0');
    update_post_meta($post_id, '_mi_manset_show_views', isset($_POST['manset_show_views']) ? '1' : '0');
    update_post_meta($post_id, '_mi_manset_show_reading_time', isset($_POST['manset_show_reading_time']) ? '1' : '0');
    update_post_meta($post_id, '_mi_manset_enable_comments', isset($_POST['manset_enable_comments']) ? '1' : '0');
    
    // Kararlar özel ayarları
    if (isset($_POST['kararlar_posts_per_page'])) {
        update_post_meta($post_id, '_mi_kararlar_posts_per_page', intval($_POST['kararlar_posts_per_page']));
    }
    
    // İletişim özel ayarları
    if (isset($_POST['iletisim_email'])) {
        update_post_meta($post_id, '_mi_iletisim_email', sanitize_email($_POST['iletisim_email']));
    }

    if (isset($_POST['iletisim_response_time'])) {
        update_post_meta($post_id, '_mi_iletisim_response_time', sanitize_text_field($_POST['iletisim_response_time']));
    }
    
    if (isset($_POST['iletisim_intro_title'])) {
        update_post_meta($post_id, '_mi_iletisim_intro_title', sanitize_text_field($_POST['iletisim_intro_title']));
    }
    
    if (isset($_POST['iletisim_intro_text'])) {
        update_post_meta($post_id, '_mi_iletisim_intro_text', sanitize_textarea_field($_POST['iletisim_intro_text']));
    }
    
    // Alt bölüm görünürlük ayarları
    update_post_meta($post_id, '_mi_iletisim_show_info', isset($_POST['iletisim_show_info']) ? '1' : '0');
    update_post_meta($post_id, '_mi_iletisim_show_rules', isset($_POST['iletisim_show_rules']) ? '1' : '0');
    update_post_meta($post_id, '_mi_iletisim_show_quick', isset($_POST['iletisim_show_quick']) ? '1' : '0');
    
    // İletişim Bilgileri içerik ayarları
    if (isset($_POST['iletisim_info_title'])) {
        update_post_meta($post_id, '_mi_iletisim_info_title', sanitize_text_field($_POST['iletisim_info_title']));
    }
    if (isset($_POST['iletisim_info_email_text'])) {
        update_post_meta($post_id, '_mi_iletisim_info_email_text', sanitize_textarea_field($_POST['iletisim_info_email_text']));
    }
    if (isset($_POST['iletisim_info_response_text'])) {
        update_post_meta($post_id, '_mi_iletisim_info_response_text', sanitize_textarea_field($_POST['iletisim_info_response_text']));
    }
    if (isset($_POST['iletisim_info_evaluation_text'])) {
        update_post_meta($post_id, '_mi_iletisim_info_evaluation_text', sanitize_textarea_field($_POST['iletisim_info_evaluation_text']));
    }
    
    // Yazı Gönderme Kuralları içerik ayarları
    if (isset($_POST['iletisim_rules_title'])) {
        update_post_meta($post_id, '_mi_iletisim_rules_title', sanitize_text_field($_POST['iletisim_rules_title']));
    }
    if (isset($_POST['iletisim_rules_content'])) {
        update_post_meta($post_id, '_mi_iletisim_rules_content', sanitize_textarea_field($_POST['iletisim_rules_content']));
    }
    
    // Hızlı İletişim içerik ayarları
    if (isset($_POST['iletisim_quick_title'])) {
        update_post_meta($post_id, '_mi_iletisim_quick_title', sanitize_text_field($_POST['iletisim_quick_title']));
    }
    if (isset($_POST['iletisim_quick_text'])) {
        update_post_meta($post_id, '_mi_iletisim_quick_text', sanitize_textarea_field($_POST['iletisim_quick_text']));
    }
    if (isset($_POST['iletisim_quick_button_text'])) {
        update_post_meta($post_id, '_mi_iletisim_quick_button_text', sanitize_text_field($_POST['iletisim_quick_button_text']));
    }
}
add_action('save_post', 'mi_save_section_meta_box');

// Admin Columns
function mi_section_columns($columns) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = 'Bölüm Adı';
    $new_columns['section_type'] = 'Sayfa Tipi';
    $new_columns['menu_order'] = 'Sıralama';
    $new_columns['active'] = 'Aktif';
    $new_columns['quick_edit'] = 'Hızlı İşlemler';
    $new_columns['date'] = $columns['date'];
    return $new_columns;
}
add_filter('manage_mi_section_posts_columns', 'mi_section_columns');

function mi_section_column_content($column, $post_id) {
    switch ($column) {
        case 'section_type':
            $type = get_post_meta($post_id, '_mi_section_type', true);
            $types = array(
                'default' => 'Varsayılan',
                'aciklama' => 'Açıklama',
                'manset' => 'Manşet',
                'kararlar' => 'Kararlar',
                'iletisim' => 'İletişim',
                'custom' => 'Özel'
            );
            $type_label = isset($types[$type]) ? $types[$type] : 'Varsayılan';
            $type_icons = array(
                'default' => '📄',
                'aciklama' => '📝',
                'manset' => '📰',
                'kararlar' => '⚖️',
                'iletisim' => '📧',
                'custom' => '🎨'
            );
            $icon = isset($type_icons[$type]) ? $type_icons[$type] : '📄';
            echo '<span style="font-size: 18px;">' . $icon . '</span> ' . $type_label;
            break;
        case 'menu_order':
            $current_order = get_post($post_id)->menu_order;
            $all_sections = get_posts(array(
                'post_type' => 'mi_section',
                'posts_per_page' => -1,
                'orderby' => 'menu_order',
                'order' => 'ASC',
                'post_status' => 'any'
            ));
            
            $current_index = 0;
            $total = count($all_sections);
            foreach ($all_sections as $index => $section) {
                if ($section->ID == $post_id) {
                    $current_index = $index;
                    break;
                }
            }
            
            echo '<div style="display: flex; align-items: center; gap: 8px;">';
            echo '<span style="font-weight: bold; min-width: 30px; text-align: center;">' . $current_order . '</span>';
            
            // Yukarı butonu
            if ($current_index > 0) {
                $prev_id = $all_sections[$current_index - 1]->ID;
                echo '<button type="button" class="button button-small mi-order-up" data-post-id="' . $post_id . '" data-target-id="' . $prev_id . '" title="Yukarı Taşı">⬆️</button>';
            } else {
                echo '<button type="button" class="button button-small" disabled style="opacity: 0.3;">⬆️</button>';
            }
            
            // Aşağı butonu
            if ($current_index < $total - 1) {
                $next_id = $all_sections[$current_index + 1]->ID;
                echo '<button type="button" class="button button-small mi-order-down" data-post-id="' . $post_id . '" data-target-id="' . $next_id . '" title="Aşağı Taşı">⬇️</button>';
            } else {
                echo '<button type="button" class="button button-small" disabled style="opacity: 0.3;">⬇️</button>';
            }
            
            echo '</div>';
            break;
        case 'active':
            $active = get_post_meta($post_id, '_mi_section_active', true);
            echo $active == '1' ? '<span style="color:green; font-weight: bold;">✓ Aktif</span>' : '<span style="color:red; font-weight: bold;">✗ Pasif</span>';
            break;
        case 'quick_edit':
            $edit_url = get_edit_post_link($post_id);
            $view_url = get_permalink($post_id);
            echo '<div style="display: flex; gap: 5px; flex-wrap: wrap;">';
            echo '<a href="' . esc_url($edit_url) . '" class="button button-small" title="Düzenle">✏️ Düzenle</a>';
            echo '<a href="' . esc_url($view_url) . '" class="button button-small" target="_blank" title="Görüntüle">👁️ Görüntüle</a>';
            echo '</div>';
            break;
    }
}
add_action('manage_mi_section_posts_custom_column', 'mi_section_column_content', 10, 2);

// Make menu_order sortable
function mi_section_sortable_columns($columns) {
    $columns['menu_order'] = 'menu_order';
    return $columns;
}
add_filter('manage_edit-mi_section_sortable_columns', 'mi_section_sortable_columns');

// Enqueue admin scripts for order management
function mi_enqueue_section_order_scripts($hook) {
    if ('edit.php' !== $hook || get_current_screen()->post_type !== 'mi_section') {
        return;
    }
    
    wp_enqueue_script('jquery');
    wp_add_inline_script('jquery', '
        jQuery(document).ready(function($) {
            $(".mi-order-up, .mi-order-down").on("click", function(e) {
                e.preventDefault();
                var $btn = $(this);
                var postId = $btn.data("post-id");
                var targetId = $btn.data("target-id");
                var direction = $btn.hasClass("mi-order-up") ? "up" : "down";
                
                if ($btn.prop("disabled")) {
                    return;
                }
                
                $btn.prop("disabled", true).text("⏳");
                
                $.ajax({
                    url: ajaxurl,
                    type: "POST",
                    data: {
                        action: "mi_update_section_order",
                        post_id: postId,
                        target_id: targetId,
                        direction: direction,
                        nonce: "' . wp_create_nonce('mi_section_order_nonce') . '"
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert("Hata: " + (response.data || "Sıralama güncellenemedi"));
                            $btn.prop("disabled", false).text(direction === "up" ? "⬆️" : "⬇️");
                        }
                    },
                    error: function() {
                        alert("Bir hata oluştu. Lütfen tekrar deneyin.");
                        $btn.prop("disabled", false).text(direction === "up" ? "⬆️" : "⬇️");
                    }
                });
            });
        });
    ');
}
add_action('admin_enqueue_scripts', 'mi_enqueue_section_order_scripts');

// AJAX handler for updating section order
function mi_update_section_order() {
    check_ajax_referer('mi_section_order_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Yetkiniz yok'));
        return;
    }
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $target_id = isset($_POST['target_id']) ? intval($_POST['target_id']) : 0;
    $direction = isset($_POST['direction']) ? sanitize_text_field($_POST['direction']) : '';
    
    if ($post_id <= 0 || $target_id <= 0) {
        wp_send_json_error(array('message' => 'Geçersiz bölüm ID'));
        return;
    }
    
    $post = get_post($post_id);
    $target = get_post($target_id);
    
    if (!$post || !$target || $post->post_type !== 'mi_section' || $target->post_type !== 'mi_section') {
        wp_send_json_error(array('message' => 'Geçersiz bölüm'));
        return;
    }
    
    // Swap menu_order values
    $post_order = $post->menu_order;
    $target_order = $target->menu_order;
    
    wp_update_post(array(
        'ID' => $post_id,
        'menu_order' => $target_order
    ));
    
    wp_update_post(array(
        'ID' => $target_id,
        'menu_order' => $post_order
    ));
    
    wp_send_json_success(array('message' => 'Sıralama güncellendi'));
}
add_action('wp_ajax_mi_update_section_order', 'mi_update_section_order');

