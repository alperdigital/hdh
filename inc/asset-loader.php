<?php
/**
 * HDH: Asset Loader
 * Optimized asset loading with caching and fallbacks
 */

if (!defined('ABSPATH')) exit;

/**
 * Get versioned asset URL
 * Adds version parameter for cache busting
 * 
 * @param string $path Relative path to asset (e.g., 'assets/items/civata.svg')
 * @return string Full URL with version
 */
function hdh_get_asset_url($path) {
    $template_uri = get_template_directory_uri();
    $template_dir = get_template_directory();
    $full_path = $template_dir . '/' . ltrim($path, '/');
    
    // Get file modification time for cache busting
    if (file_exists($full_path)) {
        $version = filemtime($full_path);
    } else {
        $version = wp_get_theme()->get('Version');
    }
    
    return $template_uri . '/' . ltrim($path, '/') . '?v=' . $version;
}

/**
 * Get optimized image attributes
 * 
 * @param string $url Image URL
 * @param string $alt Alt text
 * @param array $args Optional arguments (width, height, loading, decoding)
 * @return string HTML img tag
 */
function hdh_get_optimized_image($url, $alt, $args = array()) {
    $defaults = array(
        'width' => 80,
        'height' => 80,
        'loading' => 'lazy',
        'decoding' => 'async',
        'class' => '',
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $attributes = array(
        'src="' . esc_url($url) . '"',
        'alt="' . esc_attr($alt) . '"',
        'width="' . absint($args['width']) . '"',
        'height="' . absint($args['height']) . '"',
        'loading="' . esc_attr($args['loading']) . '"',
        'decoding="' . esc_attr($args['decoding']) . '"',
    );
    
    if (!empty($args['class'])) {
        $attributes[] = 'class="' . esc_attr($args['class']) . '"';
    }
    
    return '<img ' . implode(' ', $attributes) . '>';
}

/**
 * Preload critical assets
 * Adds preload links to header
 */
function hdh_preload_critical_assets() {
    // Preload critical CSS
    $css_url = hdh_get_asset_url('assets/css/farm-style.css');
    echo '<link rel="preload" href="' . esc_url($css_url) . '" as="style">' . "\n";
    
    // Preload critical fonts (if any)
    // echo '<link rel="preload" href="' . esc_url($font_url) . '" as="font" type="font/woff2" crossorigin>' . "\n";
}
add_action('wp_head', 'hdh_preload_critical_assets', 1);

/**
 * Add resource hints for external domains
 */
function hdh_add_resource_hints() {
    // Preconnect to Google Fonts
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
    
    // DNS prefetch for other domains (if any)
    // echo '<link rel="dns-prefetch" href="//cdn.example.com">' . "\n";
}
add_action('wp_head', 'hdh_add_resource_hints', 0);

/**
 * Set proper cache headers for static assets
 */
function hdh_set_asset_cache_headers() {
    // Only for static asset requests
    if (!is_admin() && !is_user_logged_in()) {
        $request_uri = $_SERVER['REQUEST_URI'];
        
        // Check if request is for static asset
        if (preg_match('/\.(svg|png|jpg|jpeg|gif|webp|css|js|woff|woff2|ttf|eot)$/i', $request_uri)) {
            // Set cache headers
            header('Cache-Control: public, max-age=31536000, immutable');
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
        }
    }
}
add_action('send_headers', 'hdh_set_asset_cache_headers');

/**
 * Add proper MIME types for assets
 */
function hdh_add_mime_types($mimes) {
    // Ensure SVG is allowed
    $mimes['svg'] = 'image/svg+xml';
    $mimes['svgz'] = 'image/svg+xml';
    
    // WebP support
    $mimes['webp'] = 'image/webp';
    
    return $mimes;
}
add_filter('upload_mimes', 'hdh_add_mime_types');

/**
 * Fix SVG display in media library
 */
function hdh_fix_svg_display($response, $attachment, $meta) {
    if ($response['type'] === 'image' && $response['subtype'] === 'svg+xml') {
        $response['image'] = array(
            'src' => $response['url'],
            'width' => 150,
            'height' => 150,
        );
    }
    return $response;
}
add_filter('wp_prepare_attachment_for_js', 'hdh_fix_svg_display', 10, 3);

/**
 * Inline critical SVG sprite
 * Reduces HTTP requests for icons
 */
function hdh_inline_svg_sprite() {
    $sprite_path = get_template_directory() . '/assets/svg/farm-icons.svg';
    
    if (file_exists($sprite_path)) {
        echo '<div style="display:none;">';
        include $sprite_path;
        echo '</div>';
    }
}
add_action('wp_body_open', 'hdh_inline_svg_sprite', 1);

/**
 * Get SVG icon from sprite
 * 
 * @param string $icon Icon ID
 * @param array $args Optional arguments (class, width, height)
 * @return string SVG use tag
 */
function hdh_get_svg_icon($icon, $args = array()) {
    $defaults = array(
        'class' => '',
        'width' => 24,
        'height' => 24,
        'aria_hidden' => true,
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $attributes = array(
        'width="' . absint($args['width']) . '"',
        'height="' . absint($args['height']) . '"',
    );
    
    if (!empty($args['class'])) {
        $attributes[] = 'class="' . esc_attr($args['class']) . '"';
    }
    
    if ($args['aria_hidden']) {
        $attributes[] = 'aria-hidden="true"';
    }
    
    return '<svg ' . implode(' ', $attributes) . '><use xlink:href="#icon-' . esc_attr($icon) . '"></use></svg>';
}

/**
 * Lazy load images via Intersection Observer
 * Adds data-src instead of src for lazy loading
 */
function hdh_enable_lazy_loading() {
    ?>
    <script>
    // Lazy load images with Intersection Observer
    (function() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                            observer.unobserve(img);
                        }
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.01
            });
            
            // Observe all images with data-src
            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        } else {
            // Fallback for browsers without Intersection Observer
            document.querySelectorAll('img[data-src]').forEach(img => {
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
            });
        }
    })();
    </script>
    <?php
}
add_action('wp_footer', 'hdh_enable_lazy_loading', 999);

/**
 * Add fetchpriority attribute to hero images
 * Prioritizes above-the-fold images
 */
function hdh_add_fetchpriority($html, $attachment_id, $size, $icon, $attr) {
    // Check if this is a featured image or hero image
    if (is_singular() && get_post_thumbnail_id() === $attachment_id) {
        $html = str_replace('<img', '<img fetchpriority="high"', $html);
    }
    
    return $html;
}
add_filter('wp_get_attachment_image', 'hdh_add_fetchpriority', 10, 5);

/**
 * Log asset loading errors (debug mode only)
 */
function hdh_log_asset_error($asset_path) {
    if (WP_DEBUG && WP_DEBUG_LOG) {
        error_log(sprintf(
            '[HDH Asset Error] Missing asset: %s',
            $asset_path
        ));
    }
}

