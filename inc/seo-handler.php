<?php
/**
 * HDH: SEO Handler - Meta Tags & Structured Data
 */

if (!defined('ABSPATH')) exit;

/**
 * Output all SEO tags for listing
 */
function hdh_output_listing_seo($listing_id) {
    if (!$listing_id) return;
    
    $listing = get_post($listing_id);
    if (!$listing || $listing->post_type !== 'hayday_trade') return;
    
    $trade_data = hdh_get_trade_data($listing_id);
    $author_id = $listing->post_author;
    $author_name = get_the_author_meta('display_name', $author_id);
    
    // Get offered items
    $offer_items = array_filter($trade_data['offer_items'], function($item) {
        return !empty($item['item']) && !empty($item['qty']);
    });
    $offered_labels = array();
    foreach ($offer_items as $item) {
        $label = hdh_get_item_label($item['item']);
        if ($label) {
            $offered_labels[] = $label . ' ×' . $item['qty'];
        }
    }
    $offered_text = implode(', ', $offered_labels);
    
    // Get wanted item
    $wanted_label = hdh_get_item_label($trade_data['wanted_item']);
    $wanted_text = $wanted_label . ' ×' . $trade_data['wanted_qty'];
    
    // Title
    $title = sprintf('%s ↔ %s | hayday.help', $offered_text, $wanted_text);
    
    // Description (first 150 chars)
    $description = sprintf('Takas teklifi: %s karşılığında %s arıyorum.', $offered_text, $wanted_text);
    $description = mb_substr($description, 0, 150);
    
    // URL
    $url = get_permalink($listing_id);
    if (substr($url, -1) !== '/') {
        $url .= '/';
    }
    
    // Image
    $image_url = hdh_get_listing_og_image_url($listing_id);
    
    // Trust rating
    $state = function_exists('hdh_get_user_state') ? hdh_get_user_state($author_id) : null;
    $trust_rating = $state && isset($state['trust_rating']) ? $state['trust_rating'] : 0;
    
    // Trade status
    $trade_status = get_post_meta($listing_id, '_hdh_trade_status', true) ?: 'open';
    $availability = ($trade_status === 'open') ? 'InStock' : 'OutOfStock';
    
    // Canonical
    echo '<link rel="canonical" href="' . esc_url($url) . '">' . "\n";
    
    // OG Tags
    echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
    echo '<meta property="og:type" content="website">' . "\n";
    if ($image_url) {
        echo '<meta property="og:image" content="' . esc_url($image_url) . '">' . "\n";
        echo '<meta property="og:image:width" content="1200">' . "\n";
        echo '<meta property="og:image:height" content="630">' . "\n";
    }
    
    // Twitter Card
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
    if ($image_url) {
        echo '<meta name="twitter:image" content="' . esc_url($image_url) . '">' . "\n";
    }
    
    // Schema.org JSON-LD
    $schema = hdh_generate_schema_offer($listing_id, $title, $description, $url, $image_url, $author_name, $trust_rating, $availability);
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}

/**
 * Generate Schema.org Offer structured data
 */
function hdh_generate_schema_offer($listing_id, $title, $description, $url, $image_url, $seller_name, $trust_rating, $availability) {
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'Offer',
        'name' => $title,
        'description' => $description,
        'url' => $url,
        'price' => '0',
        'priceCurrency' => 'TRY',
        'availability' => 'https://schema.org/' . $availability,
        'seller' => array(
            '@type' => 'Person',
            'name' => $seller_name,
        ),
    );
    
    if ($image_url) {
        $schema['image'] = $image_url;
    }
    
    if ($trust_rating > 0) {
        $schema['seller']['aggregateRating'] = array(
            '@type' => 'AggregateRating',
            'ratingValue' => $trust_rating,
            'bestRating' => '5',
            'worstRating' => '0',
        );
    }
    
    return $schema;
}

/**
 * Get OG image URL (delegates to share-image-generator.php)
 */
function hdh_get_listing_og_image_url($listing_id) {
    if (function_exists('hdh_get_listing_og_image_url_generated')) {
        return hdh_get_listing_og_image_url_generated($listing_id);
    }
    
    // Fallback
    $upload_dir = wp_upload_dir();
    $image_path = $upload_dir['basedir'] . '/hdh-shares/' . $listing_id . '-og.jpg';
    $image_url = $upload_dir['baseurl'] . '/hdh-shares/' . $listing_id . '-og.jpg';
    
    if (file_exists($image_path)) {
        return $image_url;
    }
    
    return get_site_icon_url();
}

add_action('wp_head', function() {
    if (is_singular('hayday_trade')) {
        hdh_output_listing_seo(get_the_ID());
    }
}, 1);

