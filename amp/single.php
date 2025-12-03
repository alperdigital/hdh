<!doctype html>
<html amp <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="canonical" href="<?php echo esc_url(get_permalink()); ?>">
    <?php wp_head(); ?>
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a></h1>
        </div>
    </header>
    
    <main>
        <div class="container">
            <?php while (have_posts()) : the_post(); ?>
                <article>
                    <h1><?php the_title(); ?></h1>
                    <div class="post-meta">
                        <span><?php echo get_the_date(); ?></span>
                        <span><?php the_author(); ?></span>
                    </div>
                    <?php if (has_post_thumbnail()) : ?>
                        <amp-img src="<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'large')); ?>" 
                                 width="800" height="500" layout="responsive" alt="<?php the_title_attribute(); ?>"></amp-img>
                    <?php endif; ?>
                    <div class="post-content">
                        <?php the_content(); ?>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
    </main>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?></p>
        </div>
    </footer>
    <?php wp_footer(); ?>
</body>
</html>

