<?php
/**
 * Feature Integration - Ensure all features work together seamlessly
 */

// Ensure post views are tracked on single posts
function mi_integrate_post_views() {
    if (is_single() && function_exists('mi_set_post_views')) {
        mi_set_post_views(get_the_ID());
    }
}
add_action('wp', 'mi_integrate_post_views');

// Integrate reading time with post content
function mi_integrate_reading_time() {
    // Okuma süresi kaldırıldı - kullanıcı isteği üzerine
    // Artık okuma süresi gösterilmiyor
}

// Integrate related posts with theme options
function mi_integrate_related_posts() {
    if (get_option('mi_enable_related_posts', 1) && is_single()) {
        $count = get_option('mi_related_posts_count', 3);
        add_action('mi_after_post_content', function() use ($count) {
            $related = get_posts(array(
                'category__in' => wp_get_post_categories(get_the_ID()),
                'numberposts' => $count,
                'post__not_in' => array(get_the_ID())
            ));
            
            if ($related) {
                echo '<div class="related-posts">';
                echo '<h3 class="related-title">' . __('İlgili Haberler', 'mi-theme') . '</h3>';
                echo '<div class="related-posts-grid">';
                foreach ($related as $post) {
                    setup_postdata($post);
                    echo '<article class="related-post-item">';
                    if (has_post_thumbnail()) {
                        echo '<div class="related-thumbnail">';
                        echo '<a href="' . get_permalink() . '">';
                        the_post_thumbnail('medium');
                        echo '</a></div>';
                    }
                    echo '<h4><a href="' . get_permalink() . '">' . get_the_title() . '</a></h4>';
                    echo '</article>';
                }
                wp_reset_postdata();
                echo '</div></div>';
            }
        });
    }
}

// Ensure masonry doesn't conflict with infinite scroll
function mi_masonry_infinite_scroll_compatibility() {
    if (get_theme_mod('mi_enable_masonry', false) && get_option('mi_enable_infinite_scroll', false)) {
        // Disable infinite scroll if masonry is enabled (they can conflict)
        update_option('mi_enable_infinite_scroll', false);
    }
}
add_action('admin_init', 'mi_masonry_infinite_scroll_compatibility');

// Ensure dark mode styles are applied
function mi_dark_mode_integration() {
    if (get_theme_mod('mi_enable_dark_mode', false)) {
        add_filter('body_class', function($classes) {
            $classes[] = 'dark-mode-enabled';
            return $classes;
        });
    }
}
add_action('wp', 'mi_dark_mode_integration');

// Integrate SEO with post views
function mi_seo_post_views_integration() {
    if (is_single() && function_exists('mi_get_post_views')) {
        $views = mi_get_post_views(get_the_ID());
        add_filter('mi_schema_markup', function($schema) use ($views) {
            if (isset($schema['@type']) && $schema['@type'] === 'Article') {
                $schema['interactionStatistic'] = array(
                    '@type' => 'InteractionCounter',
                    'interactionType' => 'https://schema.org/ViewAction',
                    'userInteractionCount' => $views
                );
            }
            return $schema;
        });
    }
}
add_action('wp', 'mi_seo_post_views_integration');

// Initialize integrations
add_action('wp', 'mi_integrate_reading_time');
add_action('wp', 'mi_integrate_related_posts');

