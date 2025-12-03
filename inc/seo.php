<?php
/**
 * SEO Functions - Schema.org, Open Graph, Twitter Cards
 */

// Schema.org JSON-LD
function mi_schema_markup() {
    if (is_single()) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => get_the_title(),
            'description' => wp_trim_words(get_the_excerpt(), 30),
            'image' => get_the_post_thumbnail_url(get_the_ID(), 'large') ?: get_site_icon_url(),
            'datePublished' => get_the_date('c'),
            'dateModified' => get_the_modified_date('c'),
            'author' => array(
                '@type' => 'Person',
                'name' => get_the_author(),
                'url' => get_author_posts_url(get_the_author_meta('ID')),
            ),
            'publisher' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'logo' => array(
                    '@type' => 'ImageObject',
                    'url' => get_site_icon_url() ?: get_template_directory_uri() . '/assets/logo.png',
                ),
            ),
        );
        
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    } elseif (is_home() || is_front_page()) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'url' => home_url('/'),
        );
        
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    }
}
add_action('wp_head', 'mi_schema_markup');

// Open Graph Meta Tags
function mi_open_graph_tags() {
    if (is_single()) {
        $title = get_the_title();
        $description = wp_trim_words(get_the_excerpt(), 30);
        $image = get_the_post_thumbnail_url(get_the_ID(), 'large') ?: get_site_icon_url();
        $url = get_permalink();
    } else {
        $title = get_bloginfo('name');
        $description = get_bloginfo('description');
        $image = get_site_icon_url();
        $url = home_url('/');
    }
    
    ?>
    <meta property="og:type" content="<?php echo is_single() ? 'article' : 'website'; ?>" />
    <meta property="og:title" content="<?php echo esc_attr($title); ?>" />
    <meta property="og:description" content="<?php echo esc_attr($description); ?>" />
    <meta property="og:url" content="<?php echo esc_url($url); ?>" />
    <meta property="og:image" content="<?php echo esc_url($image); ?>" />
    <meta property="og:site_name" content="<?php echo esc_attr(get_bloginfo('name')); ?>" />
    <?php
}
add_action('wp_head', 'mi_open_graph_tags');

// Twitter Cards
function mi_twitter_cards() {
    if (is_single()) {
        $title = get_the_title();
        $description = wp_trim_words(get_the_excerpt(), 30);
        $image = get_the_post_thumbnail_url(get_the_ID(), 'large') ?: get_site_icon_url();
    } else {
        $title = get_bloginfo('name');
        $description = get_bloginfo('description');
        $image = get_site_icon_url();
    }
    
    ?>
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="<?php echo esc_attr($title); ?>" />
    <meta name="twitter:description" content="<?php echo esc_attr($description); ?>" />
    <meta name="twitter:image" content="<?php echo esc_url($image); ?>" />
    <?php
}
add_action('wp_head', 'mi_twitter_cards');

