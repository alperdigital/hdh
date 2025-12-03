<?php
/**
 * Tag Archive Template
 */

get_header();
?>

<main>
    <div class="container">
        <?php mi_breadcrumbs(); ?>
        
        <div class="content-wrapper <?php echo mi_has_sidebar() ? 'has-sidebar' : 'no-sidebar'; ?>">
            <div class="main-content">
                <header class="tag-header">
                    <?php
                    $tag = get_queried_object();
                    ?>
                    <h1 class="tag-title">
                        <span class="tag-icon">🏷️</span>
                        <?php echo esc_html($tag->name); ?>
                    </h1>
                    <?php if ($tag->description) : ?>
                        <div class="tag-description">
                            <?php echo wp_kses_post($tag->description); ?>
                        </div>
                    <?php endif; ?>
                    <div class="tag-meta">
                        <span class="tag-count">
                            <?php
                            printf(
                                _n('%d yazı', '%d yazı', $tag->count, 'mi-theme'),
                                number_format_i18n($tag->count)
                            );
                            ?>
                        </span>
                    </div>
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
                                        <span class="post-author">
                                            <span class="author-icon">✍️</span>
                                            <span><?php the_author(); ?></span>
                                        </span>
                                        <span class="post-date">
                                            <span class="date-icon">📅</span>
                                            <time datetime="<?php echo get_the_date('c'); ?>">
                                                <?php echo get_the_date(); ?>
                                            </time>
                                        </span>
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
                        <p><?php _e('Bu etiketle ilgili henüz yazı bulunmamaktadır.', 'mi-theme'); ?></p>
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

