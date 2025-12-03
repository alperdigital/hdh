<?php
/**
 * The main template file
 */

get_header();
?>

<main>
    <div class="container">
        <div class="content-wrapper <?php echo mi_has_sidebar() ? 'has-sidebar' : 'no-sidebar'; ?>">
            <div class="main-content">
        <?php if (have_posts()) : ?>
            <?php if (get_theme_mod('mi_enable_masonry', false)) : ?>
                <?php 
                $columns = get_theme_mod('mi_masonry_columns', '3');
                mi_render_masonry_grid(null, $columns);
                ?>
            <?php else : ?>
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
                            </div>
                            
                            <div class="post-excerpt">
                                <?php 
                                // Manuel excerpt varsa onu kullan, yoksa içerikten oluştur (sadece bir kere)
                                if (has_excerpt()) {
                                    echo wp_kses_post(get_the_excerpt());
                                } else {
                                    // İçerikten excerpt oluştur (sadece metin, HTML yok)
                                    $content = get_the_content();
                                    $content = strip_tags($content);
                                    $excerpt = wp_trim_words($content, 30, '...');
                                    echo esc_html($excerpt);
                                }
                                ?>
                            </div>
                            
                            <div class="post-actions">
                                <div class="post-read-more">
                                    <a href="<?php the_permalink(); ?>" class="read-more-link">Devamını Oku →</a>
                                </div>
                                <div class="post-share-inline">
                                    <?php mi_render_social_share(get_the_ID(), true); ?>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
            <?php endif; ?>
            
            <?php if (have_posts()) : ?>
                <div class="pagination">
                    <?php
                    the_posts_pagination(array(
                        'prev_text' => '&laquo; Önceki',
                        'next_text' => 'Sonraki &raquo;',
                        'mid_size' => 2,
                    ));
                    ?>
                </div>
            <?php endif; ?>
        <?php else : ?>
            <div class="no-posts">
                <p>Henüz içerik bulunmamaktadır.</p>
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

