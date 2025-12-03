<?php
/**
 * Table of Contents (TOC) Feature
 */

// Generate TOC from headings
function mi_generate_toc($content) {
    if (!is_single() && !is_page()) {
        return $content;
    }
    
    // Check if TOC should be displayed
    $show_toc = get_post_meta(get_the_ID(), '_mi_show_toc', true);
    if ($show_toc !== '1') {
        return $content;
    }
    
    // Extract headings
    preg_match_all('/<h([2-4])[^>]*>(.*?)<\/h\1>/i', $content, $matches);
    
    if (empty($matches[0])) {
        return $content;
    }
    
    $toc = '<div class="table-of-contents"><h3 class="toc-title">' . __('İçindekiler', 'mi-theme') . '</h3><ul class="toc-list">';
    $counter = 0;
    
    foreach ($matches[0] as $index => $heading) {
        $level = $matches[1][$index];
        $text = strip_tags($matches[2][$index]);
        $id = 'toc-' . sanitize_title($text) . '-' . $counter;
        
        // Add ID to heading
        $content = str_replace($heading, '<h' . $level . ' id="' . $id . '">' . $matches[2][$index] . '</h' . $level . '>', $content);
        
        $toc .= '<li class="toc-item toc-level-' . $level . '"><a href="#' . $id . '">' . esc_html($text) . '</a></li>';
        $counter++;
    }
    
    $toc .= '</ul></div>';
    
    // Insert TOC after first paragraph or at the beginning
    $position = get_post_meta(get_the_ID(), '_mi_toc_position', true) ?: 'after-first';
    
    if ($position === 'before-content') {
        $content = $toc . $content;
    } else {
        // After first paragraph
        $content = preg_replace('/(<p[^>]*>.*?<\/p>)/i', '$1' . $toc, $content, 1);
    }
    
    return $content;
}
add_filter('the_content', 'mi_generate_toc', 10, 1);

// Add TOC meta box
function mi_add_toc_meta_box() {
    add_meta_box(
        'mi_toc_settings',
        __('İçindekiler Tablosu', 'mi-theme'),
        'mi_toc_meta_box_callback',
        array('post', 'page'),
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'mi_add_toc_meta_box');

function mi_toc_meta_box_callback($post) {
    wp_nonce_field('mi_toc_meta_box', 'mi_toc_meta_box_nonce');
    
    $show_toc = get_post_meta($post->ID, '_mi_show_toc', true);
    $toc_position = get_post_meta($post->ID, '_mi_toc_position', true) ?: 'after-first';
    ?>
    <p>
        <label>
            <input type="checkbox" name="mi_show_toc" value="1" <?php checked($show_toc, '1'); ?>>
            <?php _e('İçindekiler tablosunu göster', 'mi-theme'); ?>
        </label>
    </p>
    <p>
        <label for="mi_toc_position"><?php _e('Konum:', 'mi-theme'); ?></label>
        <select name="mi_toc_position" id="mi_toc_position">
            <option value="before-content" <?php selected($toc_position, 'before-content'); ?>><?php _e('İçeriğin Başında', 'mi-theme'); ?></option>
            <option value="after-first" <?php selected($toc_position, 'after-first'); ?>><?php _e('İlk Paragraftan Sonra', 'mi-theme'); ?></option>
        </select>
    </p>
    <?php
}

function mi_save_toc_meta_box($post_id) {
    if (!isset($_POST['mi_toc_meta_box_nonce']) || !wp_verify_nonce($_POST['mi_toc_meta_box_nonce'], 'mi_toc_meta_box')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    update_post_meta($post_id, '_mi_show_toc', isset($_POST['mi_show_toc']) ? '1' : '0');
    update_post_meta($post_id, '_mi_toc_position', sanitize_text_field($_POST['mi_toc_position']));
}
add_action('save_post', 'mi_save_toc_meta_box');

