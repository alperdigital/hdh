<?php
/**
 * AJAX Handlers for Theme
 */

// AJAX Handler for Manşet Articles Filter
add_action('wp_ajax_mi_filter_manset_articles', 'mi_filter_manset_articles');
add_action('wp_ajax_nopriv_mi_filter_manset_articles', 'mi_filter_manset_articles');

if (!function_exists('mi_filter_manset_articles')) {
    function mi_filter_manset_articles() {
        // Nonce kontrolü - daha esnek hale getirildi
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mi_manset_filter')) {
            wp_send_json_error(array('message' => 'Güvenlik kontrolü başarısız.'));
            return;
        }
        
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $category = isset($_POST['category']) ? intval($_POST['category']) : 0;
        $author = isset($_POST['author']) ? intval($_POST['author']) : 0;
        $sort = isset($_POST['sort']) ? sanitize_text_field($_POST['sort']) : 'date-desc';
        $section_id = isset($_POST['section_id']) ? intval($_POST['section_id']) : 0;
        
        $posts_per_page = 12;
        $show_category_badge = false;
        $show_views = false;
        $show_reading_time = false;
        if ($section_id > 0) {
            $posts_per_page = get_post_meta($section_id, '_mi_manset_posts_per_page', true) ?: 12;
            $show_category_badge = get_post_meta($section_id, '_mi_manset_show_category', true) === '1';
            $show_views = get_post_meta($section_id, '_mi_manset_show_views', true) === '1';
            $show_reading_time = get_post_meta($section_id, '_mi_manset_show_reading_time', true) === '1';
        }
        
        $args = array(
            'post_type' => 'post',
            'posts_per_page' => intval($posts_per_page),
            'paged' => $page,
            'post_status' => 'publish'
        );
        
        // Category filter
        if ($category > 0) {
            $args['cat'] = $category;
        }
        
        // Author filter
        if ($author > 0) {
            $args['author'] = $author;
        }
        
        // Sort
        switch ($sort) {
            case 'date-desc':
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;
            case 'date-asc':
                $args['orderby'] = 'date';
                $args['order'] = 'ASC';
                break;
            case 'editor-choice':
                // Editörün seçimi: menu_order veya featured post'lar
                // Önce featured post'ları, sonra menu_order'a göre sırala
                $args['orderby'] = 'menu_order';
                $args['order'] = 'ASC';
                // Featured post'ları önceliklendirmek için meta query ekle
                $args['meta_query'] = array(
                    'relation' => 'OR',
                    array(
                        'key' => '_mi_featured_post',
                        'value' => '1',
                        'compare' => '='
                    ),
                    array(
                        'key' => '_mi_featured_post',
                        'compare' => 'NOT EXISTS'
                    )
                );
                break;
            default:
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
        }
        
        $query = new WP_Query($args);
        
        ob_start();
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $category = get_the_category();
                $category_name = !empty($category) ? $category[0]->name : 'Genel';
                $views = function_exists('mi_get_post_views') ? mi_get_post_views(get_the_ID()) : 0;
                ?>
                <article class="manset-article">
                    <?php if ($show_category_badge) : ?>
                        <div class="article-category-badge">
                            <span><?php echo esc_html($category_name); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <h2 class="article-title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h2>
                    
                    <div class="article-excerpt">
                        <?php 
                        $excerpt_text = '';
                        if (has_excerpt()) {
                            $excerpt_text = get_the_excerpt();
                        } else {
                            $content = get_the_content();
                            $content = strip_tags($content);
                            $excerpt_text = wp_trim_words($content, 40, '');
                        }
                        echo esc_html($excerpt_text);
                        ?>
                        <a href="<?php the_permalink(); ?>" class="article-read-more-inline">Devamını Oku →</a>
                    </div>
                    
                    <div class="article-footer">
                        <div class="article-author">✍️ <?php the_author(); ?></div>
                        <div class="article-footer-right">
                            <?php if ($show_views && $views > 0) : ?>
                                <span class="article-views">👁️ <?php echo number_format($views); ?></span>
                            <?php endif; ?>
                            <?php if ($show_reading_time && function_exists('mi_calculate_reading_time')) : ?>
                                <?php 
                                $content = get_the_content();
                                $reading_time = mi_calculate_reading_time($content);
                                if ($reading_time) :
                                    ?>
                                    <span class="article-reading-time">⏱️ <?php echo $reading_time; ?> dakika okuma süresi</span>
                                <?php endif; ?>
                            <?php endif; ?>
                            <span class="article-date">📅 <?php echo mi_get_turkish_date('d F Y'); ?></span>
                        </div>
                    </div>
                </article>
                <?php
            }
        } else {
            echo '<p class="no-articles">Bu kriterlere uygun haber bulunamadı.</p>';
        }
        wp_reset_postdata();
        $html = ob_get_clean();
        
        // Pagination
        $pagination = '';
        if ($query->max_num_pages > 1) {
            $pagination = '<div class="manset-pagination-links">';
            if ($page > 1) {
                $pagination .= '<a href="#" data-page="' . ($page - 1) . '" class="pagination-link prev">← Önceki</a>';
            }
            for ($i = 1; $i <= $query->max_num_pages; $i++) {
                if ($i == $page) {
                    $pagination .= '<span class="pagination-current">' . $i . '</span>';
                } elseif ($i == 1 || $i == $query->max_num_pages || ($i >= $page - 2 && $i <= $page + 2)) {
                    $pagination .= '<a href="#" data-page="' . $i . '" class="pagination-link">' . $i . '</a>';
                } elseif ($i == $page - 3 || $i == $page + 3) {
                    $pagination .= '<span class="pagination-dots">...</span>';
                }
            }
            if ($page < $query->max_num_pages) {
                $pagination .= '<a href="#" data-page="' . ($page + 1) . '" class="pagination-link next">Sonraki →</a>';
            }
            $pagination .= '</div>';
        }
        
        wp_send_json_success(array(
            'html' => $html,
            'pagination' => $pagination
        ));
    }
}

// AJAX Handler for Signature
add_action('wp_ajax_mi_add_signature', 'mi_add_signature');
add_action('wp_ajax_nopriv_mi_add_signature', 'mi_add_signature');

if (!function_exists('mi_add_signature')) {
    function mi_add_signature() {
        // Nonce kontrolü
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mi_signature_nonce')) {
            wp_send_json_error(array('message' => 'Güvenlik kontrolü başarısız.'));
            return;
        }
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if ($post_id <= 0) {
            wp_send_json_error(array('message' => ''));
            return;
        }
        
        // Post'un aciklama (Başyazı) tipinde olduğunu kontrol et
        $section_type = get_post_meta($post_id, '_mi_section_type', true);
        if ($section_type !== 'aciklama') {
            // Sessizce başarısız dön (kullanıcıya hata gösterme)
            wp_send_json_error(array('message' => ''));
            return;
        }
        
        // Cookie kontrolü - sayfa her açıldığında 1 kere tıklanabilir
        $cookie_name = 'mi_signed_' . $post_id;
        if (isset($_COOKIE[$cookie_name]) && $_COOKIE[$cookie_name] === '1') {
            // Sessizce başarısız dön (kullanıcıya hata gösterme)
            wp_send_json_error(array('message' => ''));
            return;
        }
        
        // İmza sayısını artır
        $current_count = intval(get_post_meta($post_id, '_mi_aciklama_signatures', true));
        $new_count = $current_count + 1;
        update_post_meta($post_id, '_mi_aciklama_signatures', $new_count);
        
        wp_send_json_success(array(
            'count' => number_format_i18n($new_count)
        ));
    }
}

