<?php
/**
 * Page Template
 */

get_header();
?>

<main>
    <div class="container">
        <?php mi_breadcrumbs(); ?>
        
        <div class="content-wrapper <?php echo mi_has_sidebar() ? 'has-sidebar' : 'no-sidebar'; ?>">
            <div class="main-content">
                <?php while (have_posts()) : the_post(); ?>
                    <article id="page-<?php the_ID(); ?>" <?php post_class('page-content'); ?>>
                        <header class="page-header">
                            <h1 class="page-title"><?php the_title(); ?></h1>
                            <?php if (get_the_excerpt()) : ?>
                                <p class="page-excerpt"><?php echo get_the_excerpt(); ?></p>
                            <?php endif; ?>
                        </header>
                        
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="page-featured-image">
                                <?php the_post_thumbnail('large', array('alt' => get_the_title())); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="page-content-wrapper">
                            <?php the_content(); ?>
                            
                            <?php
                            wp_link_pages(array(
                                'before' => '<div class="page-links">' . __('Sayfalar:', 'mi-theme'),
                                'after' => '</div>',
                            ));
                            ?>
                        </div>
                        
                        <?php if (comments_open() || get_comments_number()) : ?>
                            <div class="page-comments">
                                <?php comments_template(); ?>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endwhile; ?>
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

