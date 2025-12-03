<?php
/**
 * Template Functions - Bölüm Yönetimi ve Görüntüleme
 */

// Get all active sections for menu
function mi_get_active_sections() {
    $args = array(
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
    );
    
    return get_posts($args);
}

// Get section by slug
function mi_get_section_by_slug($slug) {
    $args = array(
        'post_type' => 'mi_section',
        'name' => $slug,
        'posts_per_page' => 1,
        'post_status' => 'publish'
    );
    
    $posts = get_posts($args);
    return !empty($posts) ? $posts[0] : null;
}

// Get section type
function mi_get_section_type($post_id) {
    return get_post_meta($post_id, '_mi_section_type', true) ?: 'default';
}

// Get section name
function mi_get_section_name($post_id) {
    // Önce meta field'dan al
    $name = get_post_meta($post_id, '_mi_section_name', true);
    
    // Meta field yoksa post title'dan al (get_the_title yerine get_post kullan)
    if (empty($name)) {
        $post = get_post($post_id);
        if ($post && isset($post->post_title)) {
            $name = $post->post_title;
        } else {
            // Fallback: get_the_title kullan (global post setup edilmişse)
            $name = get_the_title($post_id);
        }
    }
    
    // Boş değilse düzeltmeleri yap
    if (!empty($name)) {
        // İLETIŞIM -> İLETİŞİM düzeltmesi (tüm varyasyonlar)
        // Sorun: Türkçe karakterlerde büyük I (İngilizce) yerine İ (Türkçe) kullanılmalı
        
        // Önce tüm büyük harf versiyonlarını düzelt
        // İLETIŞIM (I = İngilizce büyük I) -> İLETİŞİM (İ = Türkçe büyük İ)
        $name = str_replace('İLETIŞIM', 'İLETİŞİM', $name);
        $name = str_replace('İLETIŞIM', 'İLETİŞİM', $name); // Tekrar kontrol
        
        // Küçük harf versiyonları
        $name = str_replace('İletişim', 'İletişim', $name);
        $name = str_replace('iletışim', 'İletişim', $name);
        
        // Regex ile daha kapsamlı düzeltme (I yerine İ karakteri)
        // İLET[Iİ]ŞIM pattern'i: İLET + (I veya İ) + ŞIM
        $name = preg_replace('/İLET[Iİ]ŞIM/i', 'İLETİŞİM', $name);
        
        // Tüm büyük harf versiyonları için (her karakter için kontrol)
        $name = preg_replace('/İLET[Iİ]Ş[Iİ]M/i', 'İLETİŞİM', $name);
        
        // Özel durum: Sadece "İLETIŞIM" string'i varsa (I karakteri İngilizce)
        // Bu durumda tüm I karakterlerini İ ile değiştir (sadece bu kelime için)
        if (preg_match('/İLETIŞIM/i', $name)) {
            $name = preg_replace('/İLETIŞIM/i', 'İLETİŞİM', $name);
        }
        
        // Son kontrol: Eğer hala yanlış karakter varsa
        // Unicode karakter kodları ile kontrol
        // İ (U+0130) ve I (U+0049) farklı karakterler
        // İLETIŞIM'deki I (U+0049) -> İ (U+0130) olmalı
        $name = mb_convert_encoding($name, 'UTF-8', 'UTF-8');
        
        // Son bir düzeltme daha: Karakter karakter kontrol
        if (mb_strpos($name, 'İLETIŞIM') !== false) {
            $name = str_replace('İLETIŞIM', 'İLETİŞİM', $name);
        }
        
        // CSS text-transform: uppercase sorunu için ek düzeltme
        // Eğer string'de "İLETIŞIM" (I karakteri İngilizce) varsa, tüm I'ları İ ile değiştir
        // Ancak sadece Türkçe kelimelerde (İletişim, İçerik, İlgili, vb.)
        $turkish_words = array(
            'İLETIŞIM' => 'İLETİŞİM',
            'İLETIŞIM' => 'İLETİŞİM',
            'IÇERIK' => 'İÇERİK',
            'ILGILI' => 'İLGİLİ',
            'IPUCU' => 'İPUCU',
            'IŞ' => 'İŞ',
            'ICRA' => 'İCRA',
            'IDARE' => 'İDARE',
        );
        
        foreach ($turkish_words as $wrong => $correct) {
            $name = str_replace($wrong, $correct, $name);
        }
    }
    
    return $name ?: 'Bölüm';
}

// Get UI template position
function mi_get_ui_template_position($post_id) {
    return get_post_meta($post_id, '_mi_ui_template', true) ?: 'default';
}

// Render section template
function mi_render_section_template($section_post) {
    // front-page.php'de zaten setup_postdata($section) yapılmış
    // Bu yüzden global $post zaten doğru section'ı gösteriyor
    // Sadece section type'ı alıp template'i yüklüyoruz
    $section_type = mi_get_section_type($section_post->ID);
    
    switch ($section_type) {
        case 'aciklama':
            get_template_part('templates/section', 'aciklama');
            break;
        case 'manset':
            get_template_part('templates/section', 'manset');
            break;
        case 'kararlar':
            get_template_part('templates/section', 'kararlar');
            break;
        case 'iletisim':
            get_template_part('templates/section', 'iletisim');
            break;
        case 'custom':
            get_template_part('templates/section', 'custom');
            break;
        default:
            get_template_part('templates/section', 'default');
            break;
    }
}

// Render UI components for sections
function mi_render_ui_components($section_type, $post_id) {
    // This function can be used to render additional UI components
    // Currently, section templates handle their own UI
    do_action('mi_render_section_ui', $section_type, $post_id);
}

// Update navigation menu with sections
function mi_update_navigation_menu() {
    $sections = mi_get_active_sections();
    
    if (empty($sections)) {
        return;
    }
    
    // Create menu items from sections
    $menu_items = array();
    foreach ($sections as $section) {
        $menu_items[] = array(
            'title' => mi_get_section_name($section->ID),
            'url' => get_permalink($section->ID),
            'classes' => array('section-menu-item')
        );
    }
    
    return $menu_items;
}

