<?php
/**
 * Demo Content Import
 */

// Add demo import page
function mi_add_demo_import_page() {
    add_theme_page(
        __('Demo İçerik İçe Aktar', 'mi-theme'),
        __('Demo İçerik', 'mi-theme'),
        'manage_options',
        'mi-demo-import',
        'mi_demo_import_page'
    );
}
add_action('admin_menu', 'mi_add_demo_import_page');

// Demo import page
function mi_demo_import_page() {
    if (isset($_POST['mi_import_demo'])) {
        check_admin_referer('mi_demo_import_nonce');
        
        mi_import_demo_content();
        echo '<div class="notice notice-success"><p>' . __('Demo içerik başarıyla içe aktarıldı!', 'mi-theme') . '</p></div>';
    }
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <div class="demo-import-info">
            <h2><?php _e('Demo İçerik İçe Aktarma', 'mi-theme'); ?></h2>
            <p><?php _e('Bu işlem örnek içerik, sayfalar ve bölümler oluşturacaktır.', 'mi-theme'); ?></p>
            <p><strong><?php _e('Uyarı:', 'mi-theme'); ?></strong> <?php _e('Mevcut içerikleriniz etkilenmeyecektir, ancak yedek almanız önerilir.', 'mi-theme'); ?></p>
        </div>
        
        <form method="post" action="">
            <?php wp_nonce_field('mi_demo_import_nonce'); ?>
            
            <h3><?php _e('İçe Aktarılacaklar:', 'mi-theme'); ?></h3>
            <ul style="list-style: disc; margin-left: 30px;">
                <li><?php _e('1. Bölüm: Başyazı (Tek metin içerik)', 'mi-theme'); ?></li>
                <li><?php _e('2. Bölüm: #EkremİmamoğlunaÖzgürlük #TümSiyasiTutsaklaraÖzgürlük (Tek metin içerik)', 'mi-theme'); ?></li>
                <li><?php _e('3. Bölüm: Yazılar (Haber listesi)', 'mi-theme'); ?></li>
                <li><?php _e('4. Bölüm: İletişim', 'mi-theme'); ?></li>
                <li><?php _e('Örnek Yazılar ve Kategoriler', 'mi-theme'); ?></li>
            </ul>
            
            <p class="submit">
                <input type="submit" name="mi_import_demo" class="button button-primary" value="<?php esc_attr_e('Demo İçeriği İçe Aktar', 'mi-theme'); ?>">
            </p>
        </form>
    </div>
    <?php
}

// Import demo content
function mi_import_demo_content() {
    // Create categories
    $categories = array(
        'Gündem' => 'Güncel haberler ve gelişmeler',
        'Spor' => 'Spor haberleri',
        'Ekonomi' => 'Ekonomi haberleri',
        'Teknoloji' => 'Teknoloji haberleri',
        'Kültür' => 'Kültür ve sanat',
    );
    
    $category_ids = array();
    foreach ($categories as $name => $description) {
        $term = wp_insert_term($name, 'category', array('description' => $description));
        if (!is_wp_error($term)) {
            $category_ids[$name] = $term['term_id'];
        }
    }
    
    // Create sample posts
    $sample_posts = array(
        array(
            'title' => 'Teknoloji Sektöründe Yeni Gelişmeler',
            'content' => 'Yapay zeka ve makine öğrenmesi alanında son dönemde yaşanan önemli gelişmeler ve bunların sektöre etkileri. Bu gelişmeler iş dünyasını ve günlük hayatımızı nasıl şekillendiriyor?',
            'category' => 'Teknoloji',
            'excerpt' => 'Yapay zeka ve makine öğrenmesi alanında son dönemde yaşanan önemli gelişmeler.',
        ),
        array(
            'title' => 'Ekonomide Büyüme Beklentileri',
            'content' => 'Ekonomi uzmanları, önümüzdeki dönem için olumlu büyüme beklentileri açıkladı. İhracat ve yatırımların artması bekleniyor. Bu gelişmelerin topluma etkileri neler olacak?',
            'category' => 'Ekonomi',
            'excerpt' => 'Ekonomi uzmanları, önümüzdeki dönem için olumlu büyüme beklentileri açıkladı.',
        ),
        array(
            'title' => 'Spor Dünyasında Transfer Dönemi',
            'content' => 'Transfer döneminde gerçekleşen önemli transferler ve takımların yeni sezon hazırlıkları hakkında detaylar. Hangi oyuncular hangi takımlara transfer oldu?',
            'category' => 'Spor',
            'excerpt' => 'Transfer döneminde gerçekleşen önemli transferler ve takımların yeni sezon hazırlıkları.',
        ),
    );
    
    foreach ($sample_posts as $post_data) {
        $post_id = wp_insert_post(array(
            'post_title' => $post_data['title'],
            'post_content' => $post_data['content'],
            'post_excerpt' => $post_data['excerpt'],
            'post_status' => 'publish',
            'post_type' => 'post',
            'post_category' => array($category_ids[$post_data['category']]),
        ));
        
        if ($post_id) {
            // Set random view count
            update_post_meta($post_id, 'mi_post_views_count', rand(50, 500));
        }
    }
    
    // Create sample sections
    // Sıralama: Başyazı (0), # içeren bölüm (1), Yazılar (2), İletişim (3)
    // Not: Yazılar 3. bölüm olacak şekilde order değerleri: Başyazı=0, # içeren=1, Yazılar=2, İletişim=3
    $sections = array(
        array(
            'title' => 'Başyazı',
            'name' => 'Başyazı',
            'type' => 'aciklama',
            'content' => '<p>Demokrasi, özgürlük ve adalet için mücadele eden tüm insanların sesi olmak, hak ve özgürlüklerin korunması için çalışmak temel görevimizdir. Toplumun her kesiminden gelen görüşlere açığız ve farklılıklarımızı zenginlik olarak görüyoruz.</p>
<p>Güncel gelişmeleri takip ederken, objektif ve tarafsız bir bakış açısıyla haberleri sunmayı hedefliyoruz. Toplumsal sorunlara duyarlı, çözüm odaklı bir yaklaşım benimsiyoruz.</p>
<p>Yazılarınızı, görüşlerinizi ve önerilerinizi bizimle paylaşabilirsiniz. Birlikte daha iyi bir gelecek inşa edebiliriz.</p>',
            'active' => '1',
            'order' => 0,
        ),
        array(
            'title' => '#EkremİmamoğlunaÖzgürlük #TümSiyasiTutsaklaraÖzgürlük',
            'name' => '#EkremİmamoğlunaÖzgürlük #TümSiyasiTutsaklaraÖzgürlük',
            'type' => 'aciklama',
            'content' => '<p>Demokrasinin temel değerleri olan ifade özgürlüğü, düşünce özgürlüğü ve siyasi katılım hakkı, herkes için eşit ve koşulsuz olmalıdır. Siyasi görüşleri nedeniyle tutuklanan, yargılanan veya cezalandırılan hiç kimse olmamalıdır.</p>
<p>Ekrem İmamoğlu ve tüm siyasi tutsakların derhal serbest bırakılmasını talep ediyoruz. Adil yargılanma hakkı, savunma hakkı ve masumiyet karinesi herkes için geçerli olmalıdır.</p>
<p>Özgürlük, demokrasinin olmazsa olmazıdır. Siyasi görüşlerin cezalandırılması, demokrasinin temel ilkeleriyle bağdaşmaz. Tüm siyasi tutsakların özgürlüğüne kavuşması için mücadele etmeye devam edeceğiz.</p>',
            'active' => '1',
            'order' => 1,
        ),
        array(
            'title' => 'Yazılar',
            'name' => 'Yazılar',
            'type' => 'manset',
            'content' => 'Güncel yazılar ve haberler',
            'active' => '1',
            'order' => 2, // 3. bölüm (0, 1, 2, 3 sıralamasında)
            'posts_per_page' => 12,
        ),
        array(
            'title' => 'İletişim',
            'name' => 'İletişim',
            'type' => 'iletisim',
            'content' => 'Bizimle iletişime geçin',
            'active' => '1',
            'order' => 3, // 4. bölüm (son)
            'email' => 'memleketisterimcom@gmail.com',
            'response_time' => '24-48 Saat',
            'intro_title' => 'Yazılarınızı Paylaşın',
            'intro_text' => 'Görüşlerinizi, önerilerinizi ve yazılarınızı bizimle paylaşın. Değerli katkılarınız yayınlanabilir ve toplumla paylaşılabilir.',
            'show_info' => '1',
            'show_rules' => '0', // Default: kapalı
            'show_quick' => '1',
        ),
    );
    
    foreach ($sections as $section_data) {
        // İLETIŞIM -> İLETİŞİM düzeltmesi (post title için)
        $post_title = $section_data['title'];
        $post_title = str_replace('İLETIŞIM', 'İLETİŞİM', $post_title);
        $post_title = preg_replace('/İLET[Iİ]ŞIM/i', 'İLETİŞİM', $post_title);
        
        $section_id = wp_insert_post(array(
            'post_title' => $post_title,
            'post_content' => $section_data['content'],
            'post_status' => 'publish',
            'post_type' => 'mi_section',
            'menu_order' => $section_data['order'],
        ));
        
        if ($section_id) {
            // İLETIŞIM -> İLETİŞİM düzeltmesi (kaydetmeden önce)
            $section_name = $section_data['name'];
            $section_name = str_replace('İLETIŞIM', 'İLETİŞİM', $section_name);
            $section_name = preg_replace('/İLET[Iİ]ŞIM/i', 'İLETİŞİM', $section_name);
            update_post_meta($section_id, '_mi_section_name', $section_name);
            update_post_meta($section_id, '_mi_section_type', $section_data['type']);
            update_post_meta($section_id, '_mi_section_active', $section_data['active']);
            update_post_meta($section_id, '_mi_ui_template', 'top');
            
            // Manşet özel ayarları
            if ($section_data['type'] === 'manset' && isset($section_data['posts_per_page'])) {
                update_post_meta($section_id, '_mi_manset_posts_per_page', intval($section_data['posts_per_page']));
            }
            
            // İletişim özel ayarları
            if ($section_data['type'] === 'iletisim') {
                update_post_meta($section_id, '_mi_iletisim_email', isset($section_data['email']) ? $section_data['email'] : 'memleketisterimcom@gmail.com');
                update_post_meta($section_id, '_mi_iletisim_response_time', isset($section_data['response_time']) ? $section_data['response_time'] : '24-48 Saat');
                if (isset($section_data['intro_title'])) {
                    update_post_meta($section_id, '_mi_iletisim_intro_title', $section_data['intro_title']);
                }
                if (isset($section_data['intro_text'])) {
                    update_post_meta($section_id, '_mi_iletisim_intro_text', $section_data['intro_text']);
                }
                // Alt bölüm görünürlük ayarları
                update_post_meta($section_id, '_mi_iletisim_show_info', isset($section_data['show_info']) ? $section_data['show_info'] : '1');
                update_post_meta($section_id, '_mi_iletisim_show_rules', isset($section_data['show_rules']) ? $section_data['show_rules'] : '0');
                update_post_meta($section_id, '_mi_iletisim_show_quick', isset($section_data['show_quick']) ? $section_data['show_quick'] : '1');
            }
        }
    }
    
    // Create sample pages
    $pages = array(
        array(
            'title' => 'Hakkımızda',
            'content' => 'Sitemiz hakkında bilgiler...',
        ),
        array(
            'title' => 'Gizlilik Politikası',
            'content' => 'Gizlilik politikamız...',
        ),
    );
    
    foreach ($pages as $page_data) {
        wp_insert_post(array(
            'post_title' => $page_data['title'],
            'post_content' => $page_data['content'],
            'post_status' => 'publish',
            'post_type' => 'page',
        ));
    }
}

