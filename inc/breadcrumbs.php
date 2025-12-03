<?php
/**
 * Breadcrumb Navigation System
 */

function mi_breadcrumbs() {
    if (is_front_page()) {
        return;
    }
    
    $home_text = __('Ana Sayfa', 'mi-theme');
    $separator = ' / ';
    
    echo '<nav class="breadcrumbs" aria-label="Breadcrumb">';
    echo '<ol class="breadcrumb-list">';
    
    // Home
    echo '<li class="breadcrumb-item"><a href="' . esc_url(home_url('/')) . '">' . esc_html($home_text) . '</a></li>';
    
    if (is_category()) {
        $category = get_queried_object();
        echo '<li class="breadcrumb-item active">' . esc_html($category->name) . '</li>';
    } elseif (is_single()) {
        // Category
        $categories = get_the_category();
        if (!empty($categories)) {
            $category = $categories[0];
            echo '<li class="breadcrumb-item"><a href="' . esc_url(get_category_link($category->term_id)) . '">' . esc_html($category->name) . '</a></li>';
        }
        
        // Post title
        echo '<li class="breadcrumb-item active">' . esc_html(get_the_title()) . '</li>';
    } elseif (is_page()) {
        $ancestors = get_post_ancestors(get_the_ID());
        if ($ancestors) {
            $ancestors = array_reverse($ancestors);
            foreach ($ancestors as $ancestor) {
                echo '<li class="breadcrumb-item"><a href="' . esc_url(get_permalink($ancestor)) . '">' . esc_html(get_the_title($ancestor)) . '</a></li>';
            }
        }
        echo '<li class="breadcrumb-item active">' . esc_html(get_the_title()) . '</li>';
    } elseif (is_tag()) {
        $tag = get_queried_object();
        echo '<li class="breadcrumb-item active">' . sprintf(__('Etiket: %s', 'mi-theme'), esc_html($tag->name)) . '</li>';
    } elseif (is_author()) {
        echo '<li class="breadcrumb-item active">' . sprintf(__('Yazar: %s', 'mi-theme'), esc_html(get_the_author())) . '</li>';
    } elseif (is_search()) {
        echo '<li class="breadcrumb-item active">' . sprintf(__('Arama: %s', 'mi-theme'), esc_html(get_search_query())) . '</li>';
    } elseif (is_404()) {
        echo '<li class="breadcrumb-item active">' . __('404 - Sayfa Bulunamadı', 'mi-theme') . '</li>';
    } elseif (is_archive()) {
        if (is_post_type_archive('mi_section')) {
            echo '<li class="breadcrumb-item active">' . __('Bölümler', 'mi-theme') . '</li>';
        } else {
            echo '<li class="breadcrumb-item active">' . __('Arşiv', 'mi-theme') . '</li>';
        }
    }
    
    echo '</ol>';
    echo '</nav>';
}

