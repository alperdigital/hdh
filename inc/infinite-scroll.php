<?php
/**
 * Infinite Scroll Feature
 */

function mi_infinite_scroll_script() {
    if (!is_home() && !is_archive() && !is_category() && !is_tag()) {
        return;
    }
    ?>
    <script>
    jQuery(document).ready(function($) {
        let loading = false;
        let page = 2;
        const maxPages = <?php echo $GLOBALS['wp_query']->max_num_pages; ?>;
        
        $(window).on('scroll', function() {
            if (loading || page > maxPages) return;
            
            const scrollTop = $(window).scrollTop();
            const windowHeight = $(window).height();
            const documentHeight = $(document).height();
            
            // Load more when 200px from bottom
            if (scrollTop + windowHeight >= documentHeight - 200) {
                loading = true;
                
                // Show loading indicator
                const loader = $('<div class="infinite-scroll-loader">Yükleniyor...</div>');
                $('.posts-container').after(loader);
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'mi_load_more_posts',
                        page: page,
                        query: '<?php echo json_encode($GLOBALS['wp_query']->query_vars); ?>'
                    },
                    success: function(response) {
                        if (response.success && response.data.html) {
                            $('.posts-container').append(response.data.html);
                            page++;
                            loading = false;
                            loader.remove();
                            
                            // Reinitialize any scripts if needed
                            if (typeof mi_reinit_scripts === 'function') {
                                mi_reinit_scripts();
                            }
                        } else {
                            loader.remove();
                            loading = true; // Prevent further loads
                        }
                    },
                    error: function() {
                        loader.remove();
                        loading = false;
                    }
                });
            }
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'mi_infinite_scroll_script');

// AJAX handler for infinite scroll
function mi_load_more_posts() {
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $query_vars = isset($_POST['query']) ? json_decode(stripslashes($_POST['query']), true) : array();
    
    $query_vars['paged'] = $page;
    $query_vars['post_status'] = 'publish';
    
    $query = new WP_Query($query_vars);
    
    ob_start();
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            // Use standard post item structure
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class('post-item'); ?>>
                <?php if (has_post_thumbnail()) : ?>
                    <div class="post-thumbnail">
                        <a href="<?php the_permalink(); ?>">
                            <?php the_post_thumbnail('medium'); ?>
                        </a>
                    </div>
                <?php endif; ?>
                
                <div class="post-content">
                    <h2 class="post-title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h2>
                    
                    <div class="post-meta">
                        <span class="post-date"><?php echo get_the_date(); ?></span>
                        <?php if (function_exists('mi_display_post_views')) : ?>
                            <?php mi_display_post_views(); ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="post-excerpt">
                        <?php the_excerpt(); ?>
                    </div>
                    
                    <?php if (function_exists('mi_render_social_share')) : ?>
                        <div class="post-share">
                            <?php mi_render_social_share(get_the_ID(), true); ?>
                        </div>
                    <?php endif; ?>
                    
                    <a href="<?php the_permalink(); ?>" class="read-more"><?php _e('Devamını Oku', 'mi-theme'); ?></a>
                </div>
            </article>
            <?php
        }
    }
    $html = ob_get_clean();
    
    wp_reset_postdata();
    
    wp_send_json_success(array('html' => $html));
}
add_action('wp_ajax_mi_load_more_posts', 'mi_load_more_posts');
add_action('wp_ajax_nopriv_mi_load_more_posts', 'mi_load_more_posts');

