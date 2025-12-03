<?php
/**
 * Archive Template
 */

get_header();
?>

<main>
    <div class="container">
        <?php mi_breadcrumbs(); ?>
        
        <div class="content-wrapper <?php echo mi_has_sidebar() ? 'has-sidebar' : 'no-sidebar'; ?>">
            <div class="main-content">
                <header class="archive-header">
                    <?php
                    // Türkçe ay isimleri için özel başlık
                    if (is_month()) {
                        $month = get_query_var('monthnum');
                        $year = get_query_var('year');
                        $turkish_months = array(
                            1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
                            5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
                            9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
                        );
                        $month_name = isset($turkish_months[$month]) ? $turkish_months[$month] : '';
                        echo '<h1 class="archive-title">' . esc_html($month_name . ' ' . $year) . '</h1>';
                    } else {
                        the_archive_title('<h1 class="archive-title">', '</h1>');
                    }
                    the_archive_description('<div class="archive-description">', '</div>');
                    ?>
                </header>
                
                <?php if (have_posts()) : ?>
                    <div class="posts-container">
                        <?php while (have_posts()) : the_post(); ?>
                            <article id="post-<?php the_ID(); ?>" <?php post_class('post-item'); ?>>
                                <?php if (has_post_thumbnail()) : ?>
                                    <div class="post-thumbnail">
                                        <a href="<?php the_permalink(); ?>">
                                            <?php the_post_thumbnail('medium_large', array('alt' => get_the_title())); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="post-content-wrapper">
                                    <h2 class="post-title">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h2>
                                    
                                    <div class="post-meta">
                                        <span class="post-date">
                                            <time datetime="<?php echo get_the_date('c'); ?>">
                                                <?php echo get_the_date(); ?>
                                            </time>
                                        </span>
                                        <?php if (get_the_category()) : ?>
                                            <span class="post-category">
                                                <?php
                                                $categories = get_the_category();
                                                if (!empty($categories)) {
                                                    echo '<a href="' . esc_url(get_category_link($categories[0]->term_id)) . '">' . esc_html($categories[0]->name) . '</a>';
                                                }
                                                ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="post-views-meta">
                                            <?php echo mi_display_post_views(); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="post-excerpt">
                                        <?php the_excerpt(); ?>
                                    </div>
                                    
                                    <div class="post-actions">
                                        <div class="post-read-more">
                                            <a href="<?php the_permalink(); ?>" class="read-more-link"><?php _e('Devamını Oku →', 'mi-theme'); ?></a>
                                        </div>
                                        <div class="post-share-inline">
                                            <?php mi_render_social_share(get_the_ID(), true); ?>
                                        </div>
                                    </div>
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
                    <div class="no-posts">
                        <p><?php _e('Bu arşivde içerik bulunmamaktadır.', 'mi-theme'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (mi_has_sidebar()) : ?>
                <?php get_sidebar(); ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
get_footer();
?>

