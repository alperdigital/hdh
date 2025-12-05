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
 * 
 * @return array Associative array of items with 'label' and 'image' keys
 */
function hdh_get_items_config() {
    $template_uri = get_template_directory_uri();
    
    return array(
        'civata' => array(
            'label' => 'Cıvata',
            'image' => $template_uri . '/assets/items/civata.PNG',
        ),
        'kalas' => array(
            'label' => 'Kalas',
            'image' => $template_uri . '/assets/items/kalas.PNG',
        ),
        'bant' => array(
            'label' => 'Bant',
            'image' => $template_uri . '/assets/items/bant.PNG',
        ),
        'civi' => array(
            'label' => 'Çivi',
            'image' => $template_uri . '/assets/items/civi.PNG',
        ),
        'vida' => array(
            'label' => 'Vida',
            'image' => $template_uri . '/assets/items/vida.PNG',
        ),
        'tahta' => array(
            'label' => 'Ahşap Levha',
            'image' => $template_uri . '/assets/items/tahta.PNG',
        ),
        'kazik' => array(
            'label' => 'İşaret Kazığı',
            'image' => $template_uri . '/assets/items/kazik.PNG',
        ),
        'tokmak' => array(
            'label' => 'Tokmak',
            'image' => $template_uri . '/assets/items/tokmak.PNG',
        ),
        'tapu' => array(
            'label' => 'Tapu',
            'image' => $template_uri . '/assets/items/tapu.PNG',
        ),
    );
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
