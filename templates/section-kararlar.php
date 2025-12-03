<?php
/**
 * Template: Kararlar (Karar Listesi) - AJAX ile İşlevsel
 */

$post_id = get_the_ID();
?>

<div class="kararlar-section">
    <div class="kararlar-filters">
        <div class="filter-group">
            <label for="filter-dava">⚖️ Dava Türü:</label>
            <select id="filter-dava" class="filter-select">
                <option value="">Tümü</option>
                <option value="ticaret">Ticaret</option>
                <option value="ceza">Ceza</option>
                <option value="is">İş</option>
                <option value="aile">Aile</option>
                <option value="gayrimenkul">Gayrimenkul</option>
                <option value="tuketici">Tüketici</option>
                <option value="icra">İcra</option>
                <option value="idare">İdare</option>
                <option value="genel">Genel</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="filter-mahkeme">🏛️ Mahkeme:</label>
            <select id="filter-mahkeme" class="filter-select">
                <option value="">Tümü</option>
                <option value="11-hukuk">11. Hukuk Dairesi</option>
                <option value="15-hukuk">15. Hukuk Dairesi</option>
                <option value="2-hukuk">2. Hukuk Dairesi</option>
                <option value="3-ceza">3. Ceza Dairesi</option>
                <option value="6-hukuk">6. Hukuk Dairesi</option>
                <option value="9-hukuk">9. Hukuk Dairesi</option>
                <option value="genel-mahkeme">Genel Mahkeme</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="filter-sort">🔀 Sırala:</label>
            <select id="filter-sort" class="filter-select">
                <option value="date-desc">Tarih (Yeni → Eski)</option>
                <option value="date-asc">Tarih (Eski → Yeni)</option>
                <option value="importance-desc">Önem Derecesi (Yüksek → Düşük)</option>
                <option value="importance-asc">Önem Derecesi (Düşük → Yüksek)</option>
                <option value="number-asc">Karar No (A → Z)</option>
                <option value="number-desc">Karar No (Z → A)</option>
            </select>
        </div>
    </div>
    
    <div class="kararlar-loading" id="kararlar-loading" style="display: none;">
        <div class="loading-spinner"></div>
        <p>Yükleniyor...</p>
    </div>
    
    <div class="kararlar-list" id="kararlar-list">
        <?php
        // Kararları çek (meta field ile işaretlenmiş postlar veya özel kategori)
        $posts_per_page = get_post_meta($post_id, '_mi_kararlar_posts_per_page', true) ?: 12;
        $args = array(
            'post_type' => 'post',
            'posts_per_page' => intval($posts_per_page),
            'meta_query' => array(
                array(
                    'key' => '_is_karar',
                    'value' => '1',
                    'compare' => '='
                )
            ),
            'orderby' => 'date',
            'order' => 'DESC',
            'post_status' => 'publish'
        );
        
        // Eğer karar kategorisi varsa onu kullan
        $karar_category = get_category_by_slug('kararlar');
        if ($karar_category) {
            $args['cat'] = $karar_category->term_id;
            unset($args['meta_query']); // Meta query yerine kategori kullan
        }
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) :
            while ($query->have_posts()) : $query->the_post();
                $dava_turu = get_post_meta(get_the_ID(), '_karar_dava_turu', true) ?: 'Genel';
                $mahkeme = get_post_meta(get_the_ID(), '_karar_mahkeme', true) ?: 'Genel Mahkeme';
                $karar_no = get_post_meta(get_the_ID(), '_karar_no', true) ?: '2024/' . rand(10000, 99999);
                $importance = get_post_meta(get_the_ID(), '_karar_importance', true) ?: rand(70, 100);
                ?>
                <article class="karar-article" 
                         data-dava="<?php echo esc_attr(strtolower($dava_turu)); ?>"
                         data-mahkeme="<?php echo esc_attr(strtolower(str_replace(' ', '-', $mahkeme))); ?>"
                         data-importance="<?php echo esc_attr($importance); ?>"
                         data-number="<?php echo esc_attr(strtolower($karar_no)); ?>"
                         data-date="<?php echo esc_attr(get_the_date('Y-m-d')); ?>">
                    <div class="karar-header">
                        <span class="karar-dava-turu"><?php echo esc_html($dava_turu); ?></span>
                        <span class="karar-importance">⭐ <?php echo esc_html($importance); ?></span>
                    </div>
                    <div class="karar-no">📋 Karar No: <?php echo esc_html($karar_no); ?></div>
                    <h2 class="karar-title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h2>
                    <div class="karar-excerpt">
                        <?php 
                        if (has_excerpt()) {
                            echo wp_kses_post(get_the_excerpt());
                        } else {
                            $content = get_the_content();
                            $content = strip_tags($content);
                            echo esc_html(wp_trim_words($content, 30, '...'));
                        }
                        ?>
                    </div>
                    <div class="karar-meta">
                        <span class="karar-mahkeme">🏛️ <?php echo esc_html($mahkeme); ?></span>
                        <span class="karar-date">📅 <?php echo mi_get_turkish_date('d F Y'); ?></span>
                    </div>
                    <div class="karar-share">
                        <?php if (function_exists('mi_render_social_share')) : ?>
                            <?php mi_render_social_share(get_the_ID(), true); ?>
                        <?php endif; ?>
                    </div>
                    <a href="<?php the_permalink(); ?>" class="karar-read-more">Detayları Gör →</a>
                </article>
            <?php
            endwhile;
            wp_reset_postdata();
        else :
            ?>
            <p class="no-kararlar">Henüz karar bulunmamaktadır. Karar eklemek için post'lara "_is_karar" meta field'ı ekleyin veya "kararlar" kategorisini kullanın.</p>
        <?php endif; ?>
    </div>
    
    <div class="kararlar-pagination" id="kararlar-pagination"></div>
</div>

<script>
jQuery(document).ready(function($) {
    var currentPage = 1;
    var loading = false;
    
    function loadKararlar(page, dava, mahkeme, sort) {
        if (loading) return;
        loading = true;
        
        $('#kararlar-loading').show();
        $('#kararlar-list').fadeOut(200);
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
                    data: {
                        action: 'mi_filter_kararlar',
                        page: page,
                        dava: dava,
                        mahkeme: mahkeme,
                        sort: sort,
                        section_id: <?php echo $post_id; ?>,
                        nonce: '<?php echo wp_create_nonce('mi_kararlar_filter'); ?>'
                    },
            success: function(response) {
                if (response.success) {
                    $('#kararlar-list').html(response.data.html).fadeIn(300);
                    if (response.data.pagination) {
                        $('#kararlar-pagination').html(response.data.pagination);
                    }
                } else {
                    $('#kararlar-list').html('<p class="no-kararlar">' + response.data.message + '</p>').fadeIn(300);
                }
                loading = false;
                $('#kararlar-loading').hide();
            },
            error: function() {
                $('#kararlar-list').html('<p class="no-kararlar">Bir hata oluştu. Lütfen tekrar deneyin.</p>').fadeIn(300);
                loading = false;
                $('#kararlar-loading').hide();
            }
        });
    }
    
    $('#filter-dava, #filter-mahkeme, #filter-sort').on('change', function() {
        currentPage = 1;
        var dava = $('#filter-dava').val();
        var mahkeme = $('#filter-mahkeme').val();
        var sort = $('#filter-sort').val();
        loadKararlar(currentPage, dava, mahkeme, sort);
    });
    
    // Sıfırla butonu kaldırıldı
    
    $(document).on('click', '.kararlar-pagination a', function(e) {
        e.preventDefault();
        var page = $(this).data('page');
        if (page) {
            currentPage = page;
            var dava = $('#filter-dava').val();
            var mahkeme = $('#filter-mahkeme').val();
            var sort = $('#filter-sort').val();
            loadKararlar(page, dava, mahkeme, sort);
            $('html, body').animate({ scrollTop: $('.kararlar-section').offset().top - 100 }, 500);
        }
    });
});
</script>

<?php
// AJAX handler for kararlar filtering
function mi_filter_kararlar() {
    check_ajax_referer('mi_kararlar_filter', 'nonce');
    
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $dava = isset($_POST['dava']) ? sanitize_text_field($_POST['dava']) : '';
    $mahkeme = isset($_POST['mahkeme']) ? sanitize_text_field($_POST['mahkeme']) : '';
    $sort = isset($_POST['sort']) ? sanitize_text_field($_POST['sort']) : 'date-desc';
    $section_id = isset($_POST['section_id']) ? intval($_POST['section_id']) : 0;
    
    $posts_per_page = 12;
    if ($section_id > 0) {
        $posts_per_page = get_post_meta($section_id, '_mi_kararlar_posts_per_page', true) ?: 12;
    }
    
    $args = array(
        'post_type' => 'post',
        'posts_per_page' => intval($posts_per_page),
        'paged' => $page,
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => '_is_karar',
                'value' => '1',
                'compare' => '='
            )
        )
    );
    
    // Dava türü filter
    if (!empty($dava)) {
        $args['meta_query'][] = array(
            'key' => '_karar_dava_turu',
            'value' => $dava,
            'compare' => 'LIKE'
        );
    }
    
    // Mahkeme filter
    if (!empty($mahkeme)) {
        $args['meta_query'][] = array(
            'key' => '_karar_mahkeme',
            'value' => str_replace('-', ' ', $mahkeme),
            'compare' => 'LIKE'
        );
    }
    
    // Karar kategorisi varsa kullan
    $karar_category = get_category_by_slug('kararlar');
    if ($karar_category) {
        $args['cat'] = $karar_category->term_id;
        unset($args['meta_query']); // Meta query yerine kategori kullan
    }
    
    // Sort
    switch ($sort) {
        case 'date-asc':
            $args['orderby'] = 'date';
            $args['order'] = 'ASC';
            break;
        case 'importance-desc':
            $args['meta_key'] = '_karar_importance';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
            break;
        case 'importance-asc':
            $args['meta_key'] = '_karar_importance';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'ASC';
            break;
        case 'number-asc':
            $args['meta_key'] = '_karar_no';
            $args['orderby'] = 'meta_value';
            $args['order'] = 'ASC';
            break;
        case 'number-desc':
            $args['meta_key'] = '_karar_no';
            $args['orderby'] = 'meta_value';
            $args['order'] = 'DESC';
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
            $dava_turu = get_post_meta(get_the_ID(), '_karar_dava_turu', true) ?: 'Genel';
            $mahkeme = get_post_meta(get_the_ID(), '_karar_mahkeme', true) ?: 'Genel Mahkeme';
            $karar_no = get_post_meta(get_the_ID(), '_karar_no', true) ?: '2024/' . rand(10000, 99999);
            $importance = get_post_meta(get_the_ID(), '_karar_importance', true) ?: rand(70, 100);
            ?>
            <article class="karar-article">
                <div class="karar-header">
                    <span class="karar-dava-turu"><?php echo esc_html($dava_turu); ?></span>
                    <span class="karar-importance">⭐ <?php echo esc_html($importance); ?></span>
                </div>
                <div class="karar-no">📋 Karar No: <?php echo esc_html($karar_no); ?></div>
                <h2 class="karar-title">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h2>
                <div class="karar-excerpt">
                    <?php 
                    if (has_excerpt()) {
                        echo wp_kses_post(get_the_excerpt());
                    } else {
                        $content = get_the_content();
                        $content = strip_tags($content);
                        echo esc_html(wp_trim_words($content, 30, '...'));
                    }
                    ?>
                </div>
                <div class="karar-meta">
                    <span class="karar-mahkeme">🏛️ <?php echo esc_html($mahkeme); ?></span>
                    <span class="karar-date">📅 <?php echo get_the_date('d F Y'); ?></span>
                </div>
                <div class="karar-share">
                    <?php if (function_exists('mi_render_social_share')) : ?>
                        <?php mi_render_social_share(get_the_ID(), true); ?>
                    <?php endif; ?>
                </div>
                <a href="<?php the_permalink(); ?>" class="karar-read-more">Detayları Gör →</a>
            </article>
            <?php
        }
    } else {
        echo '<p class="no-kararlar">Bu kriterlere uygun karar bulunamadı.</p>';
    }
    wp_reset_postdata();
    $html = ob_get_clean();
    
    // Pagination
    $pagination = '';
    if ($query->max_num_pages > 1) {
        $pagination = '<div class="kararlar-pagination-links">';
        if ($page > 1) {
            $pagination .= '<a href="#" data-page="' . ($page - 1) . '" class="pagination-link prev">← Önceki</a>';
        }
        for ($i = 1; $i <= $query->max_num_pages; $i++) {
            if ($i == $page) {
                $pagination .= '<span class="pagination-current">' . $i . '</span>';
            } else {
                $pagination .= '<a href="#" data-page="' . $i . '" class="pagination-link">' . $i . '</a>';
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
add_action('wp_ajax_mi_filter_kararlar', 'mi_filter_kararlar');
add_action('wp_ajax_nopriv_mi_filter_kararlar', 'mi_filter_kararlar');
?>
