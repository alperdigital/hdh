<?php
/**
 * HDH: Share Image Generator
 * Generates OG images (1200x630) and Story images (1080x1920) for listings
 */

if (!defined('ABSPATH')) exit;

/**
 * Generate OG image for listing
 */
function hdh_generate_listing_og_image($listing_id) {
    if (!function_exists('imagecreatetruecolor')) {
        return false; // GD library not available
    }
    
    $listing = get_post($listing_id);
    if (!$listing || $listing->post_type !== 'hayday_trade') {
        return false;
    }
    
    $trade_data = hdh_get_trade_data($listing_id);
    $author_id = $listing->post_author;
    $author_name = get_the_author_meta('display_name', $author_id);
    
    // Get offered items
    $offer_items = array_filter($trade_data['offer_items'], function($item) {
        return !empty($item['item']) && !empty($item['qty']);
    });
    
    // Get wanted item
    $wanted_slug = $trade_data['wanted_item'];
    $wanted_label = hdh_get_item_label($wanted_slug);
    $wanted_qty = $trade_data['wanted_qty'];
    
    // Create image
    $width = 1200;
    $height = 630;
    $image = imagecreatetruecolor($width, $height);
    
    // Colors
    $bg_color = imagecolorallocate($image, 255, 246, 216); // #FFF6D8
    $text_color = imagecolorallocate($image, 74, 59, 44); // #4A3B2C
    $border_color = imagecolorallocate($image, 201, 184, 138); // #C9B88A
    
    // Fill background
    imagefill($image, 0, 0, $bg_color);
    
    // Draw border
    imagerectangle($image, 10, 10, $width - 11, $height - 11, $border_color);
    imagerectangle($image, 12, 12, $width - 13, $height - 13, $border_color);
    
    // Title text
    $title = 'Takas Teklifi';
    $font_size = 48;
    $title_x = 60;
    $title_y = 80;
    imagestring($image, 5, $title_x, $title_y, $title, $text_color);
    
    // Offered items (left side)
    $y_offset = 180;
    $item_count = 0;
    foreach (array_slice($offer_items, 0, 3) as $item) {
        $label = hdh_get_item_label($item['item']);
        $text = $label . ' ×' . $item['qty'];
        imagestring($image, 3, 60, $y_offset + ($item_count * 50), $text, $text_color);
        $item_count++;
    }
    
    // Arrow
    imagestring($image, 5, 550, 300, '↔', $text_color);
    
    // Wanted item (right side)
    $wanted_text = $wanted_label . ' ×' . $wanted_qty;
    imagestring($image, 3, 700, 280, $wanted_text, $text_color);
    
    // Author
    $author_text = 'Çiftlik: ' . $author_name;
    imagestring($image, 2, 60, $height - 80, $author_text, $text_color);
    
    // Save image
    $upload_dir = wp_upload_dir();
    $hdh_dir = $upload_dir['basedir'] . '/hdh-shares';
    if (!file_exists($hdh_dir)) {
        wp_mkdir_p($hdh_dir);
    }
    
    $filename = $listing_id . '-og.jpg';
    $filepath = $hdh_dir . '/' . $filename;
    
    imagejpeg($image, $filepath, 85);
    imagedestroy($image);
    
    return $upload_dir['baseurl'] . '/hdh-shares/' . $filename;
}

/**
 * Generate Story image for listing
 */
function hdh_generate_listing_story_image($listing_id) {
    if (!function_exists('imagecreatetruecolor')) {
        return false;
    }
    
    $listing = get_post($listing_id);
    if (!$listing || $listing->post_type !== 'hayday_trade') {
        return false;
    }
    
    $trade_data = hdh_get_trade_data($listing_id);
    $author_id = $listing->post_author;
    $author_name = get_the_author_meta('display_name', $author_id);
    
    // Create image
    $width = 1080;
    $height = 1920;
    $image = imagecreatetruecolor($width, $height);
    
    // Colors
    $bg_color = imagecolorallocate($image, 255, 246, 216);
    $text_color = imagecolorallocate($image, 74, 59, 44);
    $border_color = imagecolorallocate($image, 201, 184, 138);
    
    // Fill background
    imagefill($image, 0, 0, $bg_color);
    
    // Draw border
    imagerectangle($image, 20, 20, $width - 21, $height - 21, $border_color);
    
    // Title
    $title = 'Takas Teklifi';
    imagestring($image, 5, 60, 100, $title, $text_color);
    
    // Items list (centered)
    $y_start = 400;
    $y_offset = 0;
    
    // Offered items
    $offer_items = array_filter($trade_data['offer_items'], function($item) {
        return !empty($item['item']) && !empty($item['qty']);
    });
    foreach (array_slice($offer_items, 0, 3) as $item) {
        $label = hdh_get_item_label($item['item']);
        $text = '🎁 ' . $label . ' ×' . $item['qty'];
        imagestring($image, 3, 100, $y_start + $y_offset, $text, $text_color);
        $y_offset += 80;
    }
    
    // Arrow
    imagestring($image, 5, 500, $y_start + $y_offset, '↔', $text_color);
    $y_offset += 100;
    
    // Wanted item
    $wanted_slug = $trade_data['wanted_item'];
    $wanted_label = hdh_get_item_label($wanted_slug);
    $wanted_qty = $trade_data['wanted_qty'];
    $wanted_text = '🤍 ' . $wanted_label . ' ×' . $wanted_qty;
    imagestring($image, 3, 100, $y_start + $y_offset, $wanted_text, $text_color);
    
    // Author (bottom)
    $author_text = 'Çiftlik: ' . $author_name;
    imagestring($image, 2, 60, $height - 150, $author_text, $text_color);
    
    // Save image
    $upload_dir = wp_upload_dir();
    $hdh_dir = $upload_dir['basedir'] . '/hdh-shares';
    if (!file_exists($hdh_dir)) {
        wp_mkdir_p($hdh_dir);
    }
    
    $filename = $listing_id . '-story.jpg';
    $filepath = $hdh_dir . '/' . $filename;
    
    imagejpeg($image, $filepath, 85);
    imagedestroy($image);
    
    return $upload_dir['baseurl'] . '/hdh-shares/' . $filename;
}

/**
 * Get or generate OG image URL
 */
function hdh_get_listing_og_image_url_generated($listing_id) {
    $upload_dir = wp_upload_dir();
    $filepath = $upload_dir['basedir'] . '/hdh-shares/' . $listing_id . '-og.jpg';
    $fileurl = $upload_dir['baseurl'] . '/hdh-shares/' . $listing_id . '-og.jpg';
    
    // If exists, return it
    if (file_exists($filepath)) {
        return $fileurl;
    }
    
    // Generate new
    $generated = hdh_generate_listing_og_image($listing_id);
    return $generated ?: get_site_icon_url();
}

/**
 * Get or generate Story image URL
 */
function hdh_get_listing_story_image_url($listing_id) {
    $upload_dir = wp_upload_dir();
    $filepath = $upload_dir['basedir'] . '/hdh-shares/' . $listing_id . '-story.jpg';
    $fileurl = $upload_dir['baseurl'] . '/hdh-shares/' . $listing_id . '-story.jpg';
    
    // If exists, return it
    if (file_exists($filepath)) {
        return $fileurl;
    }
    
    // Generate new
    $generated = hdh_generate_listing_story_image($listing_id);
    return $generated ?: '';
}

/**
 * Hook: Generate images when listing is published
 */
function hdh_generate_listing_images_on_publish($post_id, $post) {
    if ($post->post_type !== 'hayday_trade' || $post->post_status !== 'publish') {
        return;
    }
    
    // Generate images in background (non-blocking)
    if (function_exists('hdh_generate_listing_og_image')) {
        hdh_generate_listing_og_image($post_id);
    }
    if (function_exists('hdh_generate_listing_story_image')) {
        hdh_generate_listing_story_image($post_id);
    }
}
add_action('save_post_hayday_trade', 'hdh_generate_listing_images_on_publish', 20, 2);

