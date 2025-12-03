<?php
/**
 * WordPress Customizer Settings
 */

function mi_customize_register($wp_customize) {
    
    // ============================================
    // PANEL: Tema Ayarları
    // ============================================
    $wp_customize->add_panel('mi_theme_panel', array(
        'title' => __('MI Tema Ayarları', 'mi-theme'),
        'description' => __('Temanızın tüm ayarlarını buradan yönetebilirsiniz.', 'mi-theme'),
        'priority' => 30,
    ));
    
    // ============================================
    // SECTION: Genel Ayarlar
    // ============================================
    $wp_customize->add_section('mi_general_settings', array(
        'title' => __('Genel Ayarlar', 'mi-theme'),
        'panel' => 'mi_theme_panel',
        'priority' => 10,
    ));
    
    // Site Logo
    $wp_customize->add_setting('mi_site_logo', array(
        'default' => '',
        'sanitize_callback' => 'esc_url_raw',
    ));
    
    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'mi_site_logo', array(
        'label' => __('Site Logo', 'mi-theme'),
        'description' => __('Site başlığının yanında görünecek logoyu yükleyin. Önerilen boyut: 200x60px', 'mi-theme'),
        'section' => 'mi_general_settings',
        'settings' => 'mi_site_logo',
    )));
    
    // Site Açıklama Göster/Gizle
    $wp_customize->add_setting('mi_show_tagline', array(
        'default' => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    
    $wp_customize->add_control('mi_show_tagline', array(
        'label' => __('Site Açıklamasını Göster', 'mi-theme'),
        'description' => __('Site başlığının altında tagline (slogan) görünürlüğünü kontrol eder.', 'mi-theme'),
        'section' => 'mi_general_settings',
        'type' => 'checkbox',
    ));
    
    // Layout Genişliği
    $wp_customize->add_setting('mi_container_width', array(
        'default' => '1200',
        'sanitize_callback' => 'absint',
    ));
    
    $wp_customize->add_control('mi_container_width', array(
        'label' => __('Container Genişliği (px)', 'mi-theme'),
        'description' => __('İçerik alanının maksimum genişliğini belirler. Varsayılan: 1200px', 'mi-theme'),
        'section' => 'mi_general_settings',
        'type' => 'number',
        'input_attrs' => array(
            'min' => 800,
            'max' => 1920,
            'step' => 10,
        ),
    ));
    
    // Dark Mode
    $wp_customize->add_setting('mi_enable_dark_mode', array(
        'default' => false,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    
    $wp_customize->add_control('mi_enable_dark_mode', array(
        'label' => __('Dark Mode\'u Etkinleştir', 'mi-theme'),
        'description' => __('Kullanıcıların site üzerinden dark mode\'u açıp kapatabilmesini sağlar.', 'mi-theme'),
        'section' => 'mi_general_settings',
        'type' => 'checkbox',
    ));
    
    // ============================================
    // SECTION: Renk Ayarları
    // ============================================
    $wp_customize->add_section('mi_color_settings', array(
        'title' => __('Renk Ayarları', 'mi-theme'),
        'panel' => 'mi_theme_panel',
        'priority' => 20,
    ));
    
    // Ana Renk (Primary)
    $wp_customize->add_setting('mi_primary_color', array(
        'default' => '#C41E3A',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'mi_primary_color', array(
        'label' => __('Ana Renk', 'mi-theme'),
        'description' => __('Butonlar, linkler ve vurgu renkleri için kullanılır. Varsayılan: #C41E3A', 'mi-theme'),
        'section' => 'mi_color_settings',
        'settings' => 'mi_primary_color',
    )));
    
    // Koyu Renk (Primary Dark)
    $wp_customize->add_setting('mi_primary_dark', array(
        'default' => '#A01A2E',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'mi_primary_dark', array(
        'label' => __('Ana Renk (Koyu)', 'mi-theme'),
        'section' => 'mi_color_settings',
        'settings' => 'mi_primary_dark',
    )));
    
    // Metin Rengi
    $wp_customize->add_setting('mi_text_color', array(
        'default' => '#1A1A1A',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'mi_text_color', array(
        'label' => __('Metin Rengi', 'mi-theme'),
        'section' => 'mi_color_settings',
        'settings' => 'mi_text_color',
    )));
    
    // Arka Plan Rengi
    $wp_customize->add_setting('mi_bg_color', array(
        'default' => '#FFFFFF',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'mi_bg_color', array(
        'label' => __('Arka Plan Rengi', 'mi-theme'),
        'section' => 'mi_color_settings',
        'settings' => 'mi_bg_color',
    )));
    
    // ============================================
    // SECTION: Tipografi Ayarları
    // ============================================
    $wp_customize->add_section('mi_typography_settings', array(
        'title' => __('Tipografi Ayarları', 'mi-theme'),
        'panel' => 'mi_theme_panel',
        'priority' => 30,
    ));
    
    // Font Ailesi
    $wp_customize->add_setting('mi_font_family', array(
        'default' => 'system',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    
    $wp_customize->add_control('mi_font_family', array(
        'label' => __('Font Ailesi', 'mi-theme'),
        'section' => 'mi_typography_settings',
        'type' => 'select',
        'choices' => array(
            'system' => 'Sistem Varsayılanı',
            'roboto' => 'Roboto',
            'open-sans' => 'Open Sans',
            'lato' => 'Lato',
            'montserrat' => 'Montserrat',
            'playfair' => 'Playfair Display',
            'merriweather' => 'Merriweather',
        ),
    ));
    
    // Başlık Font Boyutu
    $wp_customize->add_setting('mi_heading_font_size', array(
        'default' => '32',
        'sanitize_callback' => 'absint',
    ));
    
    $wp_customize->add_control('mi_heading_font_size', array(
        'label' => __('Başlık Font Boyutu (px)', 'mi-theme'),
        'section' => 'mi_typography_settings',
        'type' => 'number',
        'input_attrs' => array(
            'min' => 16,
            'max' => 72,
            'step' => 2,
        ),
    ));
    
    // Metin Font Boyutu
    $wp_customize->add_setting('mi_body_font_size', array(
        'default' => '16',
        'sanitize_callback' => 'absint',
    ));
    
    $wp_customize->add_control('mi_body_font_size', array(
        'label' => __('Metin Font Boyutu (px)', 'mi-theme'),
        'section' => 'mi_typography_settings',
        'type' => 'number',
        'input_attrs' => array(
            'min' => 12,
            'max' => 24,
            'step' => 1,
        ),
    ));
    
    // ============================================
    // SECTION: Header Ayarları
    // ============================================
    $wp_customize->add_section('mi_header_settings', array(
        'title' => __('Header Ayarları', 'mi-theme'),
        'panel' => 'mi_theme_panel',
        'priority' => 40,
    ));
    
    // Header Sticky
    $wp_customize->add_setting('mi_header_sticky', array(
        'default' => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    
    $wp_customize->add_control('mi_header_sticky', array(
        'label' => __('Header Yapışkan (Sticky)', 'mi-theme'),
        'section' => 'mi_header_settings',
        'type' => 'checkbox',
    ));
    
    // Header Arka Plan Rengi
    $wp_customize->add_setting('mi_header_bg_color', array(
        'default' => '#FFFFFF',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'mi_header_bg_color', array(
        'label' => __('Header Arka Plan Rengi', 'mi-theme'),
        'section' => 'mi_header_settings',
        'settings' => 'mi_header_bg_color',
    )));
    
    // ============================================
    // SECTION: Footer Ayarları
    // ============================================
    $wp_customize->add_section('mi_footer_settings', array(
        'title' => __('Footer Ayarları', 'mi-theme'),
        'panel' => 'mi_theme_panel',
        'priority' => 50,
    ));
    
    // Footer Metni
    $wp_customize->add_setting('mi_footer_text', array(
        'default' => '© ' . date('Y') . ' ' . get_bloginfo('name') . '. Tüm hakları saklıdır.',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    
    $wp_customize->add_control('mi_footer_text', array(
        'label' => __('Footer Metni', 'mi-theme'),
        'section' => 'mi_footer_settings',
        'type' => 'text',
    ));
    
    // Footer Arka Plan Rengi
    $wp_customize->add_setting('mi_footer_bg_color', array(
        'default' => '#F5F5F5',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'mi_footer_bg_color', array(
        'label' => __('Footer Arka Plan Rengi', 'mi-theme'),
        'section' => 'mi_footer_settings',
        'settings' => 'mi_footer_bg_color',
    )));
    
    // ============================================
    // SECTION: Sosyal Medya Ayarları
    // ============================================
    $wp_customize->add_section('mi_social_settings', array(
        'title' => __('Sosyal Medya', 'mi-theme'),
        'panel' => 'mi_theme_panel',
        'priority' => 60,
    ));
    
    $social_networks = array(
        'facebook' => 'Facebook',
        'twitter' => 'Twitter/X',
        'instagram' => 'Instagram',
        'linkedin' => 'LinkedIn',
        'youtube' => 'YouTube',
        'whatsapp' => 'WhatsApp',
        'telegram' => 'Telegram',
    );
    
    foreach ($social_networks as $key => $label) {
        $wp_customize->add_setting('mi_social_' . $key, array(
            'default' => '',
            'sanitize_callback' => 'esc_url_raw',
        ));
        
        $wp_customize->add_control('mi_social_' . $key, array(
            'label' => $label . ' URL',
            'section' => 'mi_social_settings',
            'type' => 'url',
        ));
    }
    
    // Sosyal Medya Göster/Gizle
    $wp_customize->add_setting('mi_show_social_header', array(
        'default' => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    
    $wp_customize->add_control('mi_show_social_header', array(
        'label' => __('Header\'da Sosyal Medya Göster', 'mi-theme'),
        'section' => 'mi_social_settings',
        'type' => 'checkbox',
    ));
    
    $wp_customize->add_setting('mi_show_social_footer', array(
        'default' => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    
    $wp_customize->add_control('mi_show_social_footer', array(
        'label' => __('Footer\'da Sosyal Medya Göster', 'mi-theme'),
        'section' => 'mi_social_settings',
        'type' => 'checkbox',
    ));
    
    // ============================================
    // SECTION: Blog Ayarları
    // ============================================
    $wp_customize->add_section('mi_blog_settings', array(
        'title' => __('Blog Ayarları', 'mi-theme'),
        'panel' => 'mi_theme_panel',
        'priority' => 70,
    ));
    
    // Post Sayfa Başına
    $wp_customize->add_setting('mi_posts_per_page', array(
        'default' => '10',
        'sanitize_callback' => 'absint',
    ));
    
    $wp_customize->add_control('mi_posts_per_page', array(
        'label' => __('Sayfa Başına Post Sayısı', 'mi-theme'),
        'description' => __('Blog listesinde bir sayfada gösterilecek yazı sayısı. Varsayılan: 10', 'mi-theme'),
        'section' => 'mi_blog_settings',
        'type' => 'number',
        'input_attrs' => array(
            'min' => 1,
            'max' => 50,
        ),
    ));
    
    // Excerpt Uzunluğu
    $wp_customize->add_setting('mi_excerpt_length', array(
        'default' => '30',
        'sanitize_callback' => 'absint',
    ));
    
    $wp_customize->add_control('mi_excerpt_length', array(
        'label' => __('Özet Uzunluğu (Kelime)', 'mi-theme'),
        'description' => __('Yazı özetlerinde gösterilecek kelime sayısı. Varsayılan: 30', 'mi-theme'),
        'section' => 'mi_blog_settings',
        'type' => 'number',
        'input_attrs' => array(
            'min' => 10,
            'max' => 100,
        ),
    ));
    
    // Thumbnail Göster
    $wp_customize->add_setting('mi_show_thumbnails', array(
        'default' => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    
    $wp_customize->add_control('mi_show_thumbnails', array(
        'label' => __('Thumbnail Göster', 'mi-theme'),
        'section' => 'mi_blog_settings',
        'type' => 'checkbox',
    ));
    
    // ============================================
    // SECTION: Gelişmiş Ayarlar
    // ============================================
    $wp_customize->add_section('mi_advanced_settings', array(
        'title' => __('Gelişmiş Ayarlar', 'mi-theme'),
        'panel' => 'mi_theme_panel',
        'priority' => 80,
    ));
    
    // Özel CSS
    $wp_customize->add_setting('mi_custom_css', array(
        'default' => '',
        'sanitize_callback' => 'wp_strip_all_tags',
    ));
    
    $wp_customize->add_control('mi_custom_css', array(
        'label' => __('Özel CSS', 'mi-theme'),
        'section' => 'mi_advanced_settings',
        'type' => 'textarea',
        'description' => __('Temaya özel CSS kodlarınızı buraya ekleyebilirsiniz.', 'mi-theme'),
    ));
    
    // Özel JavaScript
    $wp_customize->add_setting('mi_custom_js', array(
        'default' => '',
        'sanitize_callback' => 'wp_strip_all_tags',
    ));
    
    $wp_customize->add_control('mi_custom_js', array(
        'label' => __('Özel JavaScript', 'mi-theme'),
        'section' => 'mi_advanced_settings',
        'type' => 'textarea',
        'description' => __('Temaya özel JavaScript kodlarınızı buraya ekleyebilirsiniz.', 'mi-theme'),
    ));
}
add_action('customize_register', 'mi_customize_register');

// Dynamic CSS Output
function mi_dynamic_css() {
    $primary_color = get_theme_mod('mi_primary_color', '#C41E3A');
    $primary_dark = get_theme_mod('mi_primary_dark', '#A01A2E');
    $text_color = get_theme_mod('mi_text_color', '#1A1A1A');
    $bg_color = get_theme_mod('mi_bg_color', '#FFFFFF');
    $header_bg = get_theme_mod('mi_header_bg_color', '#FFFFFF');
    $footer_bg = get_theme_mod('mi_footer_bg_color', '#F5F5F5');
    $container_width = get_theme_mod('mi_container_width', '1200');
    $heading_size = get_theme_mod('mi_heading_font_size', '32');
    $body_size = get_theme_mod('mi_body_font_size', '16');
    $custom_css = get_theme_mod('mi_custom_css', '');
    
    $font_family = get_theme_mod('mi_font_family', 'system');
    $fonts = array(
        'roboto' => "'Roboto', sans-serif",
        'open-sans' => "'Open Sans', sans-serif",
        'lato' => "'Lato', sans-serif",
        'montserrat' => "'Montserrat', sans-serif",
        'playfair' => "'Playfair Display', serif",
        'merriweather' => "'Merriweather', serif",
    );
    $font_family_css = isset($fonts[$font_family]) ? $fonts[$font_family] : '';
    
    ?>
    <style type="text/css" id="mi-dynamic-css">
        :root {
            --primary-red: <?php echo esc_attr($primary_color); ?>;
            --primary-red-dark: <?php echo esc_attr($primary_dark); ?>;
            --text-dark: <?php echo esc_attr($text_color); ?>;
            --bg-white: <?php echo esc_attr($bg_color); ?>;
        }
        
        body {
            background-color: <?php echo esc_attr($bg_color); ?>;
            color: <?php echo esc_attr($text_color); ?>;
            <?php if ($font_family_css) : ?>
            font-family: <?php echo esc_attr($font_family_css); ?>;
            <?php endif; ?>
            font-size: <?php echo esc_attr($body_size); ?>px;
        }
        
        .container {
            max-width: <?php echo esc_attr($container_width); ?>px;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-size: <?php echo esc_attr($heading_size); ?>px;
        }
        
        header {
            background-color: <?php echo esc_attr($header_bg); ?>;
        }
        
        footer {
            background-color: <?php echo esc_attr($footer_bg); ?>;
        }
        
        <?php echo wp_strip_all_tags($custom_css); ?>
    </style>
    <?php
}
add_action('wp_head', 'mi_dynamic_css');

// Custom JavaScript Output
function mi_custom_javascript() {
    $custom_js = get_theme_mod('mi_custom_js', '');
    if (!empty($custom_js)) {
        ?>
        <script type="text/javascript">
            <?php echo wp_strip_all_tags($custom_js); ?>
        </script>
        <?php
    }
}
add_action('wp_footer', 'mi_custom_javascript');

