<?php
/**
 * Enhanced Admin UI - User-Friendly Interface
 */

// Add admin dashboard widget
function mi_add_dashboard_widget() {
    wp_add_dashboard_widget(
        'mi_theme_dashboard',
        __('MI Tema Hızlı Erişim', 'mi-theme'),
        'mi_dashboard_widget_content'
    );
}
add_action('wp_dashboard_setup', 'mi_add_dashboard_widget');

function mi_dashboard_widget_content() {
    ?>
    <div class="mi-dashboard-widget">
        <div class="mi-quick-links">
            <h3><?php _e('Hızlı Erişim', 'mi-theme'); ?></h3>
            <div class="mi-links-grid">
                <a href="<?php echo admin_url('customize.php'); ?>" class="mi-quick-link">
                    <span class="dashicons dashicons-admin-customizer"></span>
                    <strong><?php _e('Tema Özelleştir', 'mi-theme'); ?></strong>
                    <span><?php _e('Renkler, logo, layout', 'mi-theme'); ?></span>
                </a>
                <a href="<?php echo admin_url('themes.php?page=mi-theme-options'); ?>" class="mi-quick-link">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <strong><?php _e('Tema Ayarları', 'mi-theme'); ?></strong>
                    <span><?php _e('Analytics, özellikler', 'mi-theme'); ?></span>
                </a>
                <a href="<?php echo admin_url('edit.php?post_type=mi_section'); ?>" class="mi-quick-link">
                    <span class="dashicons dashicons-admin-page"></span>
                    <strong><?php _e('Bölümler', 'mi-theme'); ?></strong>
                    <span><?php _e('MANŞET, KARARLAR, İLETİŞİM', 'mi-theme'); ?></span>
                </a>
                <a href="<?php echo admin_url('themes.php?page=mi-demo-import'); ?>" class="mi-quick-link">
                    <span class="dashicons dashicons-download"></span>
                    <strong><?php _e('Demo İçerik', 'mi-theme'); ?></strong>
                    <span><?php _e('Örnek içerik yükle', 'mi-theme'); ?></span>
                </a>
            </div>
        </div>
        
        <div class="mi-features-status">
            <h3><?php _e('Özellik Durumu', 'mi-theme'); ?></h3>
            <ul class="mi-features-list">
                <li>
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php _e('Dark Mode', 'mi-theme'); ?>
                    <?php if (get_theme_mod('mi_enable_dark_mode', false)) : ?>
                        <span class="mi-status-active"><?php _e('Aktif', 'mi-theme'); ?></span>
                    <?php else : ?>
                        <span class="mi-status-inactive"><?php _e('Pasif', 'mi-theme'); ?></span>
                    <?php endif; ?>
                </li>
                <li>
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php _e('Masonry Grid', 'mi-theme'); ?>
                    <?php if (get_theme_mod('mi_enable_masonry', false)) : ?>
                        <span class="mi-status-active"><?php _e('Aktif', 'mi-theme'); ?></span>
                    <?php else : ?>
                        <span class="mi-status-inactive"><?php _e('Pasif', 'mi-theme'); ?></span>
                    <?php endif; ?>
                </li>
                <li>
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php _e('reCAPTCHA', 'mi-theme'); ?>
                    <?php if (get_theme_mod('mi_recaptcha_site_key', '')) : ?>
                        <span class="mi-status-active"><?php _e('Yapılandırıldı', 'mi-theme'); ?></span>
                    <?php else : ?>
                        <span class="mi-status-inactive"><?php _e('Yapılandırılmadı', 'mi-theme'); ?></span>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
        
        <div class="mi-help-section">
            <h3><?php _e('Yardım', 'mi-theme'); ?></h3>
            <p><?php _e('Tema hakkında sorularınız mı var?', 'mi-theme'); ?></p>
            <a href="<?php echo admin_url('themes.php?page=mi-theme-help'); ?>" class="button button-secondary">
                <?php _e('Yardım Sayfası', 'mi-theme'); ?>
            </a>
        </div>
    </div>
    
    <style>
    .mi-dashboard-widget {
        padding: 10px 0;
    }
    .mi-links-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        margin: 15px 0;
    }
    .mi-quick-link {
        display: flex;
        flex-direction: column;
        padding: 15px;
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 4px;
        text-decoration: none;
        transition: all 0.3s;
    }
    .mi-quick-link:hover {
        background: #fff;
        border-color: #C41E3A;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .mi-quick-link .dashicons {
        font-size: 32px;
        width: 32px;
        height: 32px;
        color: #C41E3A;
        margin-bottom: 10px;
    }
    .mi-quick-link strong {
        color: #1a1a1a;
        margin-bottom: 5px;
    }
    .mi-quick-link span:last-child {
        color: #666;
        font-size: 12px;
    }
    .mi-features-list {
        list-style: none;
        padding: 0;
        margin: 15px 0;
    }
    .mi-features-list li {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #eee;
    }
    .mi-features-list li:last-child {
        border-bottom: none;
    }
    .mi-features-list .dashicons {
        color: #46b450;
        margin-right: 10px;
    }
    .mi-status-active {
        color: #46b450;
        font-weight: 600;
    }
    .mi-status-inactive {
        color: #dc3232;
    }
    .mi-help-section {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }
    </style>
    <?php
}

// Add help page
function mi_add_help_page() {
    add_theme_page(
        __('Tema Yardımı', 'mi-theme'),
        __('Tema Yardımı', 'mi-theme'),
        'read',
        'mi-theme-help',
        'mi_help_page_content'
    );
}
add_action('admin_menu', 'mi_add_help_page');

function mi_help_page_content() {
    ?>
    <div class="wrap mi-help-page">
        <h1><?php _e('MI Tema Yardım Sayfası', 'mi-theme'); ?></h1>
        
        <div class="mi-help-sections">
            <div class="mi-help-section">
                <h2><?php _e('🚀 Hızlı Başlangıç', 'mi-theme'); ?></h2>
                <ol>
                    <li><?php _e('Tema Özelleştir\'e giderek logo ve renkleri ayarlayın', 'mi-theme'); ?></li>
                    <li><?php _e('Bölümler\'den MANŞET, KARARLAR, İLETİŞİM bölümlerini oluşturun', 'mi-theme'); ?></li>
                    <li><?php _e('Tema Ayarları\'ndan analytics kodlarınızı ekleyin', 'mi-theme'); ?></li>
                    <li><?php _e('Demo İçerik ile örnek içerik yükleyin', 'mi-theme'); ?></li>
                </ol>
            </div>
            
            <div class="mi-help-section">
                <h2><?php _e('⚙️ Özellikler', 'mi-theme'); ?></h2>
                <div class="mi-feature-grid">
                    <div class="mi-feature-card">
                        <h3><?php _e('Dark Mode', 'mi-theme'); ?></h3>
                        <p><?php _e('Customizer > Genel Ayarlar\'dan Dark Mode\'u etkinleştirin. Kullanıcılar site üzerinden açıp kapatabilir.', 'mi-theme'); ?></p>
                    </div>
                    <div class="mi-feature-card">
                        <h3><?php _e('Masonry Grid', 'mi-theme'); ?></h3>
                        <p><?php _e('Customizer > Blog Ayarları\'ndan Masonry Grid\'i etkinleştirin ve kolon sayısını seçin.', 'mi-theme'); ?></p>
                    </div>
                    <div class="mi-feature-card">
                        <h3><?php _e('İçindekiler Tablosu', 'mi-theme'); ?></h3>
                        <p><?php _e('Yazı düzenleme ekranında sağ tarafta "İçindekiler Tablosu" meta box\'ından etkinleştirin.', 'mi-theme'); ?></p>
                    </div>
                    <div class="mi-feature-card">
                        <h3><?php _e('reCAPTCHA', 'mi-theme'); ?></h3>
                        <p><?php _e('Customizer > reCAPTCHA Ayarları\'ndan Site Key ve Secret Key\'inizi girin.', 'mi-theme'); ?></p>
                    </div>
                    <div class="mi-feature-card">
                        <h3><?php _e('Parallax', 'mi-theme'); ?></h3>
                        <p><?php _e('Shortcode kullanın: [parallax speed="0.5" image="url"] İçerik [/parallax]', 'mi-theme'); ?></p>
                    </div>
                    <div class="mi-feature-card">
                        <h3><?php _e('Kod Highlighting', 'mi-theme'); ?></h3>
                        <p><?php _e('Gutenberg\'de code block kullanın, otomatik olarak syntax highlighting uygulanır.', 'mi-theme'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="mi-help-section">
                <h2><?php _e('📝 Bölüm Yönetimi', 'mi-theme'); ?></h2>
                <p><?php _e('Bölümler > Yeni Ekle\'den yeni bölüm oluşturabilirsiniz:', 'mi-theme'); ?></p>
                <ul>
                    <li><strong>MANŞET:</strong> <?php _e('Haber listesi ve filtreleme', 'mi-theme'); ?></li>
                    <li><strong>KARARLAR:</strong> <?php _e('Yargıtay kararları listesi', 'mi-theme'); ?></li>
                    <li><strong>İLETİŞİM:</strong> <?php _e('İletişim formu ve bilgileri', 'mi-theme'); ?></li>
                    <li><strong>CUSTOM:</strong> <?php _e('Özel içerik', 'mi-theme'); ?></li>
                </ul>
            </div>
            
            <div class="mi-help-section">
                <h2><?php _e('🎨 Customizer Kullanımı', 'mi-theme'); ?></h2>
                <p><?php _e('Görünüm > Özelleştir\'den tüm tema ayarlarına erişebilirsiniz:', 'mi-theme'); ?></p>
                <ul>
                    <li><strong>Genel Ayarlar:</strong> <?php _e('Logo, tagline, container genişliği', 'mi-theme'); ?></li>
                    <li><strong>Renk Ayarları:</strong> <?php _e('Ana renkler, metin renkleri', 'mi-theme'); ?></li>
                    <li><strong>Header Ayarları:</strong> <?php _e('Sticky header, menü', 'mi-theme'); ?></li>
                    <li><strong>Footer Ayarları:</strong> <?php _e('Footer metni, widget alanları', 'mi-theme'); ?></li>
                    <li><strong>Sosyal Medya:</strong> <?php _e('Sosyal medya linkleri', 'mi-theme'); ?></li>
                    <li><strong>Blog Ayarları:</strong> <?php _e('Yazı sayısı, excerpt uzunluğu, masonry', 'mi-theme'); ?></li>
                </ul>
            </div>
            
            <div class="mi-help-section">
                <h2><?php _e('❓ Sık Sorulan Sorular', 'mi-theme'); ?></h2>
                <div class="mi-faq">
                    <h3><?php _e('Bölümler nasıl görünür?', 'mi-theme'); ?></h3>
                    <p><?php _e('Bölümler, oluşturduğunuz bölüm sayfalarında görünür. Menüye eklemek için Görünüm > Menüler\'den ekleyebilirsiniz.', 'mi-theme'); ?></p>
                    
                    <h3><?php _e('Dark Mode nasıl çalışır?', 'mi-theme'); ?></h3>
                    <p><?php _e('Customizer\'dan etkinleştirdikten sonra, kullanıcılar site üzerindeki toggle butonu ile açıp kapatabilir.', 'mi-theme'); ?></p>
                    
                    <h3><?php _e('reCAPTCHA nereden alınır?', 'mi-theme'); ?></h3>
                    <p><?php _e('https://www.google.com/recaptcha/admin adresinden ücretsiz olarak alabilirsiniz.', 'mi-theme'); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .mi-help-page {
        max-width: 1200px;
    }
    .mi-help-sections {
        margin-top: 20px;
    }
    .mi-help-section {
        background: #fff;
        padding: 25px;
        margin-bottom: 20px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    .mi-help-section h2 {
        margin-top: 0;
        color: #C41E3A;
    }
    .mi-feature-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-top: 20px;
    }
    .mi-feature-card {
        padding: 20px;
        background: #f9f9f9;
        border-left: 4px solid #C41E3A;
        border-radius: 4px;
    }
    .mi-feature-card h3 {
        margin-top: 0;
        color: #1a1a1a;
    }
    .mi-faq h3 {
        color: #C41E3A;
        margin-top: 20px;
    }
    .mi-faq p {
        margin-bottom: 15px;
    }
    </style>
    <?php
}

// Add admin bar menu
function mi_add_admin_bar_menu($wp_admin_bar) {
    $wp_admin_bar->add_node(array(
        'id' => 'mi-theme',
        'title' => __('MI Tema', 'mi-theme'),
        'href' => admin_url('customize.php'),
    ));
    
    $wp_admin_bar->add_node(array(
        'id' => 'mi-customize',
        'parent' => 'mi-theme',
        'title' => __('Tema Özelleştir', 'mi-theme'),
        'href' => admin_url('customize.php'),
    ));
    
    $wp_admin_bar->add_node(array(
        'id' => 'mi-settings',
        'parent' => 'mi-theme',
        'title' => __('Tema Ayarları', 'mi-theme'),
        'href' => admin_url('themes.php?page=mi-theme-options'),
    ));
    
    $wp_admin_bar->add_node(array(
        'id' => 'mi-sections',
        'parent' => 'mi-theme',
        'title' => __('Bölümler', 'mi-theme'),
        'href' => admin_url('edit.php?post_type=mi_section'),
    ));
}
add_action('admin_bar_menu', 'mi_add_admin_bar_menu', 100);

// Enqueue admin styles
function mi_admin_styles() {
    wp_enqueue_style('mi-admin-style', get_template_directory_uri() . '/assets/css/admin.css', array(), '1.0.0');
}
add_action('admin_enqueue_scripts', 'mi_admin_styles');

