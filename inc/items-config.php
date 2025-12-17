<?php
/**
 * HDH: Hay Day Items Configuration
 * Central configuration for all tradeable items with images
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get all Hay Day items with their labels and images
 * Now loads from WordPress options (admin-manageable)
 * Falls back to hardcoded config if options are empty
 * 
 * @return array Associative array of items with 'label' and 'image' keys
 */
function hdh_get_items_config() {
    // Try to load from options first (admin-managed)
    $items = get_option('hdh_items_config', array());
    
    // If empty, use hardcoded default (for migration/fallback)
    if (empty($items)) {
        // Use versioned asset URLs for cache busting
        $get_asset = function_exists('hdh_get_asset_url') ? 'hdh_get_asset_url' : function($path) {
            return get_template_directory_uri() . '/' . ltrim($path, '/');
        };
        
        $items = array(
            'civata' => array(
                'label' => 'Cıvata',
                'image' => $get_asset('assets/items/civata.svg'),
            ),
            'kalas' => array(
                'label' => 'Kalas',
                'image' => $get_asset('assets/items/kalas.svg'),
            ),
            'bant' => array(
                'label' => 'Bant',
                'image' => $get_asset('assets/items/bant.svg'),
            ),
            'civi' => array(
                'label' => 'Çivi',
                'image' => $get_asset('assets/items/civi.svg'),
            ),
            'vida' => array(
                'label' => 'Vida',
                'image' => $get_asset('assets/items/vida.svg'),
            ),
            'tahta' => array(
                'label' => 'Ahşap Levha',
                'image' => $get_asset('assets/items/tahta.svg'),
            ),
            'kazik' => array(
                'label' => 'İşaret Kazığı',
                'image' => $get_asset('assets/items/kazik.svg'),
            ),
            'tokmak' => array(
                'label' => 'Tokmak',
                'image' => $get_asset('assets/items/tokmak.svg'),
            ),
            'tapu' => array(
                'label' => 'Tapu',
                'image' => $get_asset('assets/items/tapu.svg'),
            ),
        );
        // Save defaults to options for first time
        update_option('hdh_items_config', $items);
    }
    
    return $items;
}

/**
 * Get item label by key
 * 
 * @param string $key Item key (e.g., 'civata')
 * @return string Item label or empty string if not found
 */
function hdh_get_item_label($key) {
    $items = hdh_get_items_config();
    return isset($items[$key]) ? $items[$key]['label'] : '';
}

/**
 * Get item image URL by key
 * 
 * @param string $key Item key (e.g., 'civata')
 * @return string Image URL or empty string if not found
 */
function hdh_get_item_image($key) {
    $items = hdh_get_items_config();
    return isset($items[$key]) ? $items[$key]['image'] : '';
}

/**
 * Get all item keys
 * 
 * @return array Array of item keys
 */
function hdh_get_item_keys() {
    return array_keys(hdh_get_items_config());
}
