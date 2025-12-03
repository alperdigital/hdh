<?php
/**
 * Social Media Functions
 */

function mi_render_social_links() {
    $social_networks = array(
        'facebook' => array('label' => 'Facebook', 'icon' => '📘'),
        'twitter' => array('label' => 'Twitter/X', 'icon' => '🐦'),
        'instagram' => array('label' => 'Instagram', 'icon' => '📷'),
        'linkedin' => array('label' => 'LinkedIn', 'icon' => '💼'),
        'youtube' => array('label' => 'YouTube', 'icon' => '📺'),
        'whatsapp' => array('label' => 'WhatsApp', 'icon' => '💬'),
        'telegram' => array('label' => 'Telegram', 'icon' => '✈️'),
    );
    
    $has_links = false;
    foreach ($social_networks as $key => $network) {
        $url = get_theme_mod('mi_social_' . $key, '');
        if ($url) {
            $has_links = true;
            break;
        }
    }
    
    if (!$has_links) {
        return;
    }
    
    echo '<div class="social-links">';
    foreach ($social_networks as $key => $network) {
        $url = get_theme_mod('mi_social_' . $key, '');
        if ($url) {
            echo '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer" class="social-link social-' . esc_attr($key) . '" title="' . esc_attr($network['label']) . '">';
            echo '<span class="social-icon">' . esc_html($network['icon']) . '</span>';
            echo '<span class="social-label">' . esc_html($network['label']) . '</span>';
            echo '</a>';
        }
    }
    echo '</div>';
}

