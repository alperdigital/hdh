<?php
/**
 * Theme Options Page
 */

// Add theme options page
function mi_add_theme_options_page() {
    add_theme_page(
        __('Tema Ayarları', 'mi-theme'),
        __('Tema Ayarları', 'mi-theme'),
        'manage_options',
        'mi-theme-options',
        'mi_theme_options_page'
    );
}
add_action('admin_menu', 'mi_add_theme_options_page');

// Theme options page content
function mi_theme_options_page() {
    if (isset($_POST['mi_save_options'])) {
        check_admin_referer('mi_theme_options_nonce');
        
        // Save options
        update_option('mi_analytics_code', wp_kses_post($_POST['mi_analytics_code']));
        update_option('mi_facebook_pixel', sanitize_text_field($_POST['mi_facebook_pixel']));
        update_option('mi_google_tag_manager', sanitize_text_field($_POST['mi_google_tag_manager']));
        update_option('mi_custom_head_code', wp_kses_post($_POST['mi_custom_head_code']));
        update_option('mi_custom_footer_code', wp_kses_post($_POST['mi_custom_footer_code']));
        update_option('mi_enable_lazy_load', isset($_POST['mi_enable_lazy_load']) ? 1 : 0);
        update_option('mi_enable_reading_time', isset($_POST['mi_enable_reading_time']) ? 1 : 0);
        update_option('mi_enable_comments', isset($_POST['mi_enable_comments']) ? 1 : 0);
        update_option('mi_show_categories_widget', isset($_POST['mi_show_categories_widget']) ? 1 : 0);
        update_option('mi_show_archives_widget', isset($_POST['mi_show_archives_widget']) ? 1 : 0);
        update_option('mi_enable_related_posts', isset($_POST['mi_enable_related_posts']) ? 1 : 0);
        update_option('mi_related_posts_count', absint($_POST['mi_related_posts_count']));
        update_option('mi_enable_single_page', isset($_POST['mi_enable_single_page']) ? 1 : 0);
        update_option('mi_front_page_section', isset($_POST['mi_front_page_section']) ? intval($_POST['mi_front_page_section']) : 0);
        
        echo '<div class="notice notice-success"><p>' . __('Ayarlar kaydedildi!', 'mi-theme') . '</p></div>';
    }
    
    $analytics_code = get_option('mi_analytics_code', '');
    $facebook_pixel = get_option('mi_facebook_pixel', '');
    $google_tag_manager = get_option('mi_google_tag_manager', '');
    $custom_head = get_option('mi_custom_head_code', '');
    $custom_footer = get_option('mi_custom_footer_code', '');
    $lazy_load = get_option('mi_enable_lazy_load', 0);
    $reading_time = get_option('mi_enable_reading_time', 1);
    $enable_comments = get_option('mi_enable_comments', 0);
    $show_categories = get_option('mi_show_categories_widget', 0);
    $show_archives = get_option('mi_show_archives_widget', 1);
    $related_posts = get_option('mi_enable_related_posts', 1);
    $related_count = get_option('mi_related_posts_count', 3);
    $single_page = get_option('mi_enable_single_page', 0);
    
    // Ana sayfa section seçimi için aktif section'ları al
    $active_sections = get_posts(array(
        'post_type' => 'mi_section',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_mi_section_active',
                'value' => '1',
                'compare' => '='
            )
        ),
        'orderby' => 'menu_order',
        'order' => 'ASC'
    ));
    
    // Default: Başyazı (aciklama tipinde, menu_order=0)
    $default_section_id = 0;
    foreach ($active_sections as $section) {
        $section_type = get_post_meta($section->ID, '_mi_section_type', true);
        $section_name = mi_get_section_name($section->ID);
        $section_name_lower = mb_strtolower($section_name, 'UTF-8');
        if ($section_type === 'aciklama' && (strpos($section_name_lower, 'başyazı') !== false || strpos($section_name_lower, 'basyazi') !== false)) {
            $default_section_id = $section->ID;
            break;
        }
    }
    
    $front_page_section = get_option('mi_front_page_section', $default_section_id);
    ?>
    <div class="wrap mi-theme-options">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <p class="description"><?php _e('Temanızın gelişmiş ayarlarını buradan yönetebilirsiniz. Daha fazla özelleştirme için <a href="' . admin_url('customize.php') . '">Tema Özelleştir</a> sayfasını kullanın.', 'mi-theme'); ?></p>
        
        <form method="post" action="">
            <?php wp_nonce_field('mi_theme_options_nonce'); ?>
            
            <h2 class="nav-tab-wrapper">
                <a href="#general" class="nav-tab nav-tab-active">
                    <span class="dashicons dashicons-admin-generic"></span> <?php _e('Genel', 'mi-theme'); ?>
                </a>
                <a href="#analytics" class="nav-tab">
                    <span class="dashicons dashicons-chart-line"></span> <?php _e('Analitik', 'mi-theme'); ?>
                </a>
                <a href="#features" class="nav-tab">
                    <span class="dashicons dashicons-admin-plugins"></span> <?php _e('Özellikler', 'mi-theme'); ?>
                </a>
                <a href="#homepage" class="nav-tab">
                    <span class="dashicons dashicons-admin-home"></span> <?php _e('Ana Sayfa', 'mi-theme'); ?>
                </a>
                <a href="#advanced" class="nav-tab">
                    <span class="dashicons dashicons-admin-tools"></span> <?php _e('Gelişmiş', 'mi-theme'); ?>
                </a>
            </h2>
            
            <div id="general" class="tab-content">
                <div class="mi-tab-header">
                    <h3><?php _e('Genel Ayarlar', 'mi-theme'); ?></h3>
                    <p><?php _e('Site genelinde kullanılacak özel kodları buradan ekleyebilirsiniz.', 'mi-theme'); ?></p>
                </div>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="mi_custom_head_code"><?php _e('Özel Head Kodu', 'mi-theme'); ?></label>
                        </th>
                        <td>
                            <textarea name="mi_custom_head_code" id="mi_custom_head_code" rows="5" class="large-text code" placeholder="<?php esc_attr_e('<!-- Özel meta tags, CSS, vb. -->', 'mi-theme'); ?>"><?php echo esc_textarea($custom_head); ?></textarea>
                            <p class="description">
                                <?php _e('&lt;head&gt; bölümüne eklenecek kodlar. Örnek: Meta tags, CSS, preconnect linkler, vb.', 'mi-theme'); ?>
                                <br>
                                <strong><?php _e('Örnek:', 'mi-theme'); ?></strong> 
                                <code>&lt;link rel="preconnect" href="https://fonts.googleapis.com"&gt;</code>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="mi_custom_footer_code"><?php _e('Özel Footer Kodu', 'mi-theme'); ?></label>
                        </th>
                        <td>
                            <textarea name="mi_custom_footer_code" id="mi_custom_footer_code" rows="5" class="large-text code" placeholder="<?php esc_attr_e('<!-- JavaScript, tracking codes, vb. -->', 'mi-theme'); ?>"><?php echo esc_textarea($custom_footer); ?></textarea>
                            <p class="description">
                                <?php _e('Footer\'a eklenecek kodlar. Genellikle JavaScript kodları ve tracking script\'leri buraya eklenir.', 'mi-theme'); ?>
                                <br>
                                <strong><?php _e('Örnek:', 'mi-theme'); ?></strong> 
                                <code>&lt;script&gt;console.log('Hello');&lt;/script&gt;</code>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div id="analytics" class="tab-content" style="display:none;">
                <div class="mi-tab-header">
                    <h3><?php _e('Analitik ve Takip Kodları', 'mi-theme'); ?></h3>
                    <p><?php _e('Site trafiğinizi ve kullanıcı davranışlarınızı analiz etmek için tracking kodlarınızı ekleyin.', 'mi-theme'); ?></p>
                </div>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="mi_analytics_code"><?php _e('Google Analytics', 'mi-theme'); ?></label>
                        </th>
                        <td>
                            <textarea name="mi_analytics_code" id="mi_analytics_code" rows="5" class="large-text code" placeholder="<?php esc_attr_e('<!-- Google Analytics tracking code -->', 'mi-theme'); ?>"><?php echo esc_textarea($analytics_code); ?></textarea>
                            <p class="description">
                                <?php _e('Google Analytics 4 (GA4) veya Universal Analytics tracking kodunu buraya yapıştırın.', 'mi-theme'); ?>
                                <br>
                                <a href="https://analytics.google.com/" target="_blank"><?php _e('Google Analytics\'e git →', 'mi-theme'); ?></a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="mi_facebook_pixel"><?php _e('Facebook Pixel ID', 'mi-theme'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="mi_facebook_pixel" id="mi_facebook_pixel" value="<?php echo esc_attr($facebook_pixel); ?>" class="regular-text" placeholder="123456789012345" />
                            <p class="description">
                                <?php _e('Facebook Pixel ID\'nizi girin. Sadece ID numarasını girin (örn: 123456789012345)', 'mi-theme'); ?>
                                <br>
                                <a href="https://business.facebook.com/events_manager2" target="_blank"><?php _e('Facebook Events Manager\'a git →', 'mi-theme'); ?></a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="mi_google_tag_manager"><?php _e('Google Tag Manager ID', 'mi-theme'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="mi_google_tag_manager" id="mi_google_tag_manager" value="<?php echo esc_attr($google_tag_manager); ?>" class="regular-text" placeholder="GTM-XXXXXXX" />
                            <p class="description">
                                <?php _e('Google Tag Manager Container ID\'nizi girin. Format: GTM-XXXXXXX', 'mi-theme'); ?>
                                <br>
                                <a href="https://tagmanager.google.com/" target="_blank"><?php _e('Google Tag Manager\'a git →', 'mi-theme'); ?></a>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div id="features" class="tab-content" style="display:none;">
                <div class="mi-tab-header">
                    <h3><?php _e('Tema Özellikleri', 'mi-theme'); ?></h3>
                    <p><?php _e('Temanın sunduğu ek özellikleri buradan etkinleştirebilir veya devre dışı bırakabilirsiniz.', 'mi-theme'); ?></p>
                </div>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Özellikler', 'mi-theme'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="mi_enable_lazy_load" value="1" <?php checked($lazy_load, 1); ?> />
                                    <strong><?php _e('Lazy Loading (Görseller için)', 'mi-theme'); ?></strong>
                                    <p class="description" style="margin-left: 25px; margin-top: 5px;">
                                        <?php _e('Görsellerin sayfa yüklenirken değil, görünür olduklarında yüklenmesini sağlar. Sayfa hızını artırır.', 'mi-theme'); ?>
                                    </p>
                                </label><br><br>
                                <label>
                                    <input type="checkbox" name="mi_enable_reading_time" value="1" <?php checked($reading_time, 1); ?> />
                                    <strong><?php _e('Okuma Süresi Göster', 'mi-theme'); ?></strong>
                                    <p class="description" style="margin-left: 25px; margin-top: 5px;">
                                        <?php _e('Yazıların başında tahmini okuma süresini gösterir. Kullanıcı deneyimini iyileştirir.', 'mi-theme'); ?>
                                    </p>
                                </label><br><br>
                                <label>
                                    <input type="checkbox" name="mi_enable_related_posts" value="1" <?php checked($related_posts, 1); ?> />
                                    <strong><?php _e('İlgili Yazılar Göster', 'mi-theme'); ?></strong>
                                    <p class="description" style="margin-left: 25px; margin-top: 5px;">
                                        <?php _e('Yazıların sonunda benzer içerikleri gösterir. Kullanıcıların sitede daha fazla kalmasını sağlar.', 'mi-theme'); ?>
                                    </p>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="mi_related_posts_count"><?php _e('İlgili Yazı Sayısı', 'mi-theme'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="mi_related_posts_count" id="mi_related_posts_count" value="<?php echo esc_attr($related_count); ?>" min="1" max="10" class="small-text" />
                            <p class="description"><?php _e('Yazıların sonunda gösterilecek ilgili yazı sayısı. Önerilen: 3-6 arası', 'mi-theme'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Widget Görünürlüğü', 'mi-theme'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="mi_show_categories_widget" value="1" <?php checked($show_categories, 1); ?> />
                                    <strong><?php _e('Kategoriler Widget\'ını Göster', 'mi-theme'); ?></strong>
                                    <p class="description" style="margin-left: 25px; margin-top: 5px;">
                                        <?php _e('Sidebar\'da Kategoriler widget\'ının görünmesini sağlar. (Varsayılan: Kapalı)', 'mi-theme'); ?>
                                    </p>
                                </label><br><br>
                                <label>
                                    <input type="checkbox" name="mi_show_archives_widget" value="1" <?php checked($show_archives, 1); ?> />
                                    <strong><?php _e('Arşiv Widget\'ını Göster', 'mi-theme'); ?></strong>
                                    <p class="description" style="margin-left: 25px; margin-top: 5px;">
                                        <?php _e('Sidebar\'da Arşiv widget\'ının görünmesini sağlar. Ay isimleri Türkçe olarak gösterilir. (Varsayılan: Açık)', 'mi-theme'); ?>
                                    </p>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div id="homepage" class="tab-content" style="display:none;">
                <div class="mi-tab-header">
                    <h3><?php _e('Ana Sayfa Ayarları', 'mi-theme'); ?></h3>
                    <p><?php _e('Ana sayfanızın hangi bölümü göstereceğini seçebilirsiniz. Ana sayfa, seçtiğiniz bölümün içeriği ve görünümüyle bire bir aynı olacaktır.', 'mi-theme'); ?></p>
                </div>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="mi_front_page_section"><?php _e('Ana Sayfa Bölümü', 'mi-theme'); ?></label>
                        </th>
                        <td>
                            <select name="mi_front_page_section" id="mi_front_page_section" class="regular-text">
                                <option value="0" <?php selected($front_page_section, 0); ?>><?php _e('-- Seçiniz (Varsayılan: Başyazı) --', 'mi-theme'); ?></option>
                                <?php
                                if (!empty($active_sections)) {
                                    foreach ($active_sections as $section) {
                                        $section_name = mi_get_section_name($section->ID);
                                        $section_type = get_post_meta($section->ID, '_mi_section_type', true);
                                        $type_labels = array(
                                            'aciklama' => 'Açıklama',
                                            'manset' => 'Manşet',
                                            'kararlar' => 'Kararlar',
                                            'iletisim' => 'İletişim',
                                            'custom' => 'Özel',
                                            'default' => 'Varsayılan'
                                        );
                                        $type_label = isset($type_labels[$section_type]) ? $type_labels[$section_type] : 'Varsayılan';
                                        $selected = selected($front_page_section, $section->ID, false);
                                        echo '<option value="' . esc_attr($section->ID) . '" ' . $selected . '>' . esc_html($section_name) . ' (' . esc_html($type_label) . ')</option>';
                                    }
                                } else {
                                    echo '<option value="0">' . __('Aktif bölüm bulunamadı', 'mi-theme') . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description">
                                <?php _e('Ana sayfanızın hangi bölümü göstereceğini seçin. Varsayılan olarak "Başyazı" bölümü seçilidir.', 'mi-theme'); ?>
                                <br>
                                <strong><?php _e('Not:', 'mi-theme'); ?></strong> <?php _e('Ana sayfa, seçtiğiniz bölümün içeriği ve görünümüyle bire bir aynı olacaktır.', 'mi-theme'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div id="advanced" class="tab-content" style="display:none;">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Gelişmiş Ayarlar', 'mi-theme'); ?></th>
                        <td>
                            <p><?php _e('Gelişmiş ayarlar için WordPress Customizer\'ı kullanın:', 'mi-theme'); ?></p>
                            <a href="<?php echo admin_url('customize.php'); ?>" class="button button-primary"><?php _e('Customizer\'ı Aç', 'mi-theme'); ?></a>
                        </td>
                    </tr>
                </table>
            </div>
            
            <?php submit_button(__('Ayarları Kaydet', 'mi-theme'), 'primary', 'mi_save_options'); ?>
        </form>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            var target = $(this).attr('href');
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            $('.tab-content').hide();
            $(target).show();
        });
    });
    </script>
    
    <style>
    .mi-theme-options .description {
        margin-top: 10px;
        color: #666;
    }
    .mi-tab-header {
        background: #f9f9f9;
        padding: 20px;
        border-left: 4px solid #C41E3A;
        margin-bottom: 20px;
        border-radius: 4px;
    }
    .mi-tab-header h3 {
        margin-top: 0;
        color: #1a1a1a;
    }
    .mi-tab-header p {
        margin-bottom: 0;
        color: #666;
    }
    .tab-content {
        margin-top: 20px;
    }
    .tab-content table {
        margin-top: 0;
    }
    .nav-tab .dashicons {
        margin-right: 5px;
        vertical-align: middle;
    }
    .form-table th {
        width: 200px;
    }
    .form-table fieldset label {
        display: block;
        margin-bottom: 15px;
    }
    </style>
    <?php
}

// Output custom head code
function mi_output_custom_head() {
    $custom_head = get_option('mi_custom_head_code', '');
    $analytics = get_option('mi_analytics_code', '');
    $facebook_pixel = get_option('mi_facebook_pixel', '');
    $gtm = get_option('mi_google_tag_manager', '');
    
    if ($analytics) {
        echo $analytics;
    }
    
    if ($facebook_pixel) {
        ?>
        <!-- Facebook Pixel Code -->
        <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '<?php echo esc_js($facebook_pixel); ?>');
        fbq('track', 'PageView');
        </script>
        <noscript><img height="1" width="1" style="display:none"
        src="https://www.facebook.com/tr?id=<?php echo esc_attr($facebook_pixel); ?>&ev=PageView&noscript=1"
        /></noscript>
        <?php
    }
    
    if ($gtm) {
        ?>
        <!-- Google Tag Manager -->
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','<?php echo esc_js($gtm); ?>');</script>
        <!-- End Google Tag Manager -->
        <?php
    }
    
    if ($custom_head) {
        echo $custom_head;
    }
}
add_action('wp_head', 'mi_output_custom_head', 99);

// Output custom footer code
function mi_output_custom_footer() {
    $custom_footer = get_option('mi_custom_footer_code', '');
    if ($custom_footer) {
        echo $custom_footer;
    }
}
add_action('wp_footer', 'mi_output_custom_footer', 99);

