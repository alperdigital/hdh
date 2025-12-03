<?php
/**
 * Author Archive Template
 */

get_header();
?>

<main>
    <div class="container">
        <?php mi_breadcrumbs(); ?>
        
        <?php if (have_posts()) : the_post(); ?>
            <?php
            $author_id = get_the_author_meta('ID');
            $author_name = get_the_author();
            $author_description = get_the_author_meta('description');
            $author_url = get_author_posts_url($author_id);
            ?>
            
            <div class="author-header">
                <div class="author-avatar">
                    <?php echo get_avatar($author_id, 120); ?>
                </div>
                <div class="author-info">
                    <h1 class="author-name"><?php echo esc_html($author_name); ?></h1>
                    <?php if ($author_description) : ?>
                        <p class="author-description"><?php echo esc_html($author_description); ?></p>
                    <?php endif; ?>
                    <div class="author-meta">
                        <span class="author-posts-count">
                            <?php
                            $post_count = count_user_posts($author_id);
                            printf(_n('%d yazı', '%d yazı', $post_count, 'mi-theme'), $post_count);
                            ?>
                        </span>
                        <?php if (get_the_author_meta('user_url')) : ?>
                            <a href="<?php echo esc_url(get_the_author_meta('user_url')); ?>" 
                               class="author-website" target="_blank" rel="noopener">
                                <?php _e('Web Sitesi', 'mi-theme'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php rewind_posts(); ?>
            
            <div class="author-posts">
                <h2 class="author-posts-title"><?php _e('Yazılar', 'mi-theme'); ?></h2>
                
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
                                    <?php the_excerpt(); ?>
                                </div>
                                
                                <div class="post-read-more">
                                    <a href="<?php the_permalink(); ?>" class="read-more-link"><?php _e('Devamını Oku →', 'mi-theme'); ?></a>
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
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();
?>

