<?php
/**
 * Sidebar Template
 */

// Single post sayfalarında özel sidebar içeriği
if (is_single() && get_post_type() === 'post') {
    ?>
    <aside id="secondary" class="widget-area sidebar">
        <?php
        // Editörün Seçtiği Haberler
        $featured_posts = get_posts(array(
            'post_type' => 'post',
            'posts_per_page' => 5,
            'post_status' => 'publish',
            'orderby' => array(
                'menu_order' => 'ASC',
                'date' => 'DESC'
            ),
            'meta_query' => array(
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
            ),
            'post__not_in' => array(get_the_ID())
        ));
        
        if (!empty($featured_posts)) :
            ?>
            <div class="widget widget-featured-posts">
                <h3 class="widget-title">Editörün Seçtikleri</h3>
                <ul class="featured-posts-list">
                    <?php foreach ($featured_posts as $post) : setup_postdata($post); ?>
                        <li class="featured-post-item">
                            <h4 class="featured-post-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h4>
                            <div class="featured-post-excerpt">
                                <?php 
                                if (has_excerpt()) {
                                    echo wp_trim_words(get_the_excerpt(), 15, '...');
                                } else {
                                    $content = get_the_content();
                                    $content = strip_tags($content);
                                    echo wp_trim_words($content, 15, '...');
                                }
                                ?>
                            </div>
                            <a href="<?php the_permalink(); ?>" class="featured-post-read-more">Devamını Oku →</a>
                        </li>
                    <?php endforeach; wp_reset_postdata(); ?>
                </ul>
            </div>
            <?php
        endif;
        
        // Arşiv Widget
        if (get_option('mi_show_archives_widget', 1) == 1) {
            $turkish_months = array(
                'January' => 'Ocak', 'February' => 'Şubat', 'March' => 'Mart',
                'April' => 'Nisan', 'May' => 'Mayıs', 'June' => 'Haziran',
                'July' => 'Temmuz', 'August' => 'Ağustos', 'September' => 'Eylül',
                'October' => 'Ekim', 'November' => 'Kasım', 'December' => 'Aralık'
            );
            ?>
            <div class="widget widget-archive">
                <h3 class="widget-title">Arşiv</h3>
                <ul class="archive-list">
                    <?php 
                    $archives_html = wp_get_archives(array('type' => 'monthly', 'echo' => 0, 'show_post_count' => 0));
                    foreach ($turkish_months as $en => $tr) {
                        $archives_html = str_replace($en, $tr, $archives_html);
                    }
                    echo $archives_html;
                    ?>
                </ul>
            </div>
            <?php
        }
        ?>
    </aside>
    <?php
} else {
    // Diğer sayfalarda normal sidebar
    if (!is_active_sidebar('sidebar-1')) {
        return;
    }
    ?>
    <aside id="secondary" class="widget-area sidebar">
        <?php dynamic_sidebar('sidebar-1'); ?>
    </aside>
    <?php
}
?>

