<?php
/**
 * Search Results Template
 */

get_header();
?>

<main>
    <div class="container">
        <?php mi_breadcrumbs(); ?>
        
        <div class="search-header">
            <h1 class="search-title">
                <?php
                if (have_posts()) {
                    printf(__('"%s" için arama sonuçları', 'mi-theme'), '<span>' . get_search_query() . '</span>');
                } else {
                    printf(__('"%s" için sonuç bulunamadı', 'mi-theme'), '<span>' . get_search_query() . '</span>');
                }
                ?>
            </h1>
            <p class="search-results-count">
                <?php
                global $wp_query;
                if ($wp_query->found_posts > 0) {
                    printf(_n('%d sonuç bulundu', '%d sonuç bulundu', $wp_query->found_posts, 'mi-theme'), $wp_query->found_posts);
                }
                ?>
            </p>
        </div>
        
        <?php if (have_posts()) : ?>
            <div class="search-results">
                <?php while (have_posts()) : the_post(); ?>
                    <article class="search-result-item">
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="search-result-thumbnail">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail('medium'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="search-result-content">
                            <h2 class="search-result-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h2>
                            
                            <div class="search-result-meta">
                                <span class="search-result-date"><?php echo get_the_date(); ?></span>
                                <?php if (get_the_category()) : ?>
                                    <span class="search-result-category">
                                        <?php
                                        $categories = get_the_category();
                                        echo esc_html($categories[0]->name);
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="search-result-excerpt">
                                <?php the_excerpt(); ?>
                            </div>
                            
                            <a href="<?php the_permalink(); ?>" class="search-result-link"><?php _e('Devamını Oku →', 'mi-theme'); ?></a>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
            
            <div class="pagination">
                <?php
                the_posts_pagination(array(
                    'prev_text' => '&laquo; Önceki',
                    'next_text' => 'Sonraki &raquo;',
                    'mid_size' => 2,
                ));
                ?>
            </div>
        <?php else : ?>
            <div class="no-search-results">
                <p><?php _e('Aradığınız kriterlere uygun sonuç bulunamadı. Lütfen farklı kelimeler deneyin.', 'mi-theme'); ?></p>
                
                <div class="search-suggestions">
                    <h3><?php _e('Öneriler:', 'mi-theme'); ?></h3>
                    <ul>
                        <li><?php _e('Yazım hatalarını kontrol edin', 'mi-theme'); ?></li>
                        <li><?php _e('Daha genel terimler kullanın', 'mi-theme'); ?></li>
                        <li><?php _e('Farklı anahtar kelimeler deneyin', 'mi-theme'); ?></li>
                    </ul>
                </div>
                
                <div class="search-again">
                    <?php get_search_form(); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();
?>

