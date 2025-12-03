<?php
/**
 * WebP Image Support
 */

// Add WebP support to allowed mime types
function mi_add_webp_mime_type($mimes) {
    $mimes['webp'] = 'image/webp';
    return $mimes;
}
add_filter('mime_types', 'mi_add_webp_mime_type');

// Enable WebP upload
function mi_webp_upload($file) {
    $file['type'] = 'image/webp';
    return $file;
}

// Convert images to WebP on upload (optional - requires image processing library)
function mi_convert_to_webp($metadata, $attachment_id) {
    if (!function_exists('imagewebp')) {
        return $metadata;
    }
    
    $file = get_attached_file($attachment_id);
    $mime_type = get_post_mime_type($attachment_id);
    
    // Only convert JPEG and PNG
    if ($mime_type === 'image/jpeg' || $mime_type === 'image/png') {
        $image = null;
        
        if ($mime_type === 'image/jpeg') {
            $image = imagecreatefromjpeg($file);
        } elseif ($mime_type === 'image/png') {
            $image = imagecreatefrompng($file);
        }
        
        if ($image) {
            $webp_file = str_replace(array('.jpg', '.jpeg', '.png'), '.webp', $file);
            imagewebp($image, $webp_file, 85);
            imagedestroy($image);
        }
    }
    
    return $metadata;
}
// Uncomment to enable automatic WebP conversion
// add_filter('wp_generate_attachment_metadata', 'mi_convert_to_webp', 10, 2);

// Serve WebP when available
function mi_serve_webp($sources, $size_array, $image_src, $image_meta, $attachment_id) {
    if (strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false) {
        $file = get_attached_file($attachment_id);
        $webp_file = str_replace(array('.jpg', '.jpeg', '.png'), '.webp', $file);
        
        if (file_exists($webp_file)) {
            $webp_url = str_replace(array('.jpg', '.jpeg', '.png'), '.webp', $image_src);
            $sources['webp'] = array(
                'url' => $webp_url,
                'descriptor' => 'w',
                'value' => $size_array[0],
            );
        }
    }
    
    return $sources;
}
add_filter('wp_calculate_image_srcset', 'mi_serve_webp', 10, 5);

// Picture element with WebP fallback
function mi_picture_element($html, $id, $size, $permalink) {
    $image = wp_get_attachment_image_src($id, $size);
    if (!$image) {
        return $html;
    }
    
    $mime_type = get_post_mime_type($id);
    $file = get_attached_file($id);
    $webp_file = str_replace(array('.jpg', '.jpeg', '.png'), '.webp', $file);
    
    if (file_exists($webp_file) && strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false) {
        $webp_url = str_replace(array('.jpg', '.jpeg', '.png'), '.webp', $image[0]);
        $html = '<picture>
            <source srcset="' . esc_url($webp_url) . '" type="image/webp">
            <img src="' . esc_url($image[0]) . '" alt="' . esc_attr(get_the_title($id)) . '" width="' . $image[1] . '" height="' . $image[2] . '">
        </picture>';
    }
    
    return $html;
}
add_filter('wp_get_attachment_image', 'mi_picture_element', 10, 4);

