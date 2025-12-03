<?php
/**
 * Masonry Grid Layout System
 */

// Enqueue masonry script
function mi_masonry_scripts() {
    if (!get_theme_mod('mi_enable_masonry', false)) {
        return;
    }
    
    wp_enqueue_script('masonry');
    wp_enqueue_script('imagesloaded');
    
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Initialize masonry if element exists
        if ($('.masonry-grid').length) {
            var $grid = $('.masonry-grid').masonry({
                itemSelector: '.masonry-item',
                columnWidth: '.masonry-sizer',
                percentPosition: true,
                gutter: 20
            });
            
            // Layout after images load
            if (typeof $.fn.imagesLoaded !== 'undefined') {
                $grid.imagesLoaded().progress(function() {
                    $grid.masonry('layout');
                });
            } else {
                // Fallback if imagesLoaded not available
                $grid.on('layoutComplete', function() {
                    $grid.masonry('layout');
                });
            }
        }
    });
    </script>
    <?php
}
add_action('wp_footer', 'mi_masonry_scripts');

// Masonry grid function
function mi_render_masonry_grid($posts = null, $columns = 3) {
    if (!$posts) {
        global $wp_query;
        $posts = $wp_query->posts;
    }
    
    if (empty($posts)) {
        return;
    }
    
    $column_class = 'masonry-col-' . $columns;
    ?>
    <div class="masonry-grid <?php echo esc_attr($column_class); ?>">
        <div class="masonry-sizer"></div>
        <?php foreach ($posts as $post) : setup_postdata($post); ?>
            <div class="masonry-item">
                <article class="masonry-post">
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="masonry-thumbnail">
                            <a href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail('medium', array('alt' => get_the_title())); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="masonry-content">
                        <h3 class="masonry-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>
                        <div class="masonry-meta">
                            <span><?php echo get_the_date(); ?></span>
                        </div>
                        <div class="masonry-excerpt">
                            <?php echo wp_trim_words(get_the_excerpt(), 15); ?>
                        </div>
                    </div>
                </article>
            </div>
        <?php endforeach; wp_reset_postdata(); ?>
    </div>
    <?php
}

// Add masonry option to customizer
function mi_masonry_customizer($wp_customize) {
    $wp_customize->add_setting('mi_enable_masonry', array(
        'default' => false,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    
    $wp_customize->add_control('mi_enable_masonry', array(
        'label' => __('Masonry Grid Layout Kullan', 'mi-theme'),
        'section' => 'mi_blog_settings',
        'type' => 'checkbox',
        'description' => __('Blog listesinde masonry grid layout kullanılır', 'mi-theme'),
    ));
    
    $wp_customize->add_setting('mi_masonry_columns', array(
        'default' => '3',
        'sanitize_callback' => 'absint',
    ));
    
    $wp_customize->add_control('mi_masonry_columns', array(
        'label' => __('Masonry Kolon Sayısı', 'mi-theme'),
        'description' => __('Masonry grid\'de gösterilecek kolon sayısını seçin. Mobilde otomatik olarak 1 kolona düşer.', 'mi-theme'),
        'section' => 'mi_blog_settings',
        'type' => 'select',
        'choices' => array(
            '2' => '2 Kolon',
            '3' => '3 Kolon',
            '4' => '4 Kolon',
        ),
    ));
}
add_action('customize_register', 'mi_masonry_customizer');

