<?php
/**
 * Share Buttons Component
 */

if (!defined('ABSPATH')) exit;

/**
 * Render share buttons for listing
 */
function hdh_render_share_buttons($listing_id, $context = 'single-trade') {
    if (!$listing_id) return '';
    
    $listing = get_post($listing_id);
    if (!$listing || $listing->post_type !== 'hayday_trade') return '';
    
    $url = get_permalink($listing_id);
    $title = get_the_title($listing_id);
    $trade_data = hdh_get_trade_data($listing_id);
    
    // Build share text
    $offer_items = array_filter($trade_data['offer_items'], function($item) {
        return !empty($item['item']) && !empty($item['qty']);
    });
    $offered_labels = array();
    foreach (array_slice($offer_items, 0, 2) as $item) {
        $label = hdh_get_item_label($item['item']);
        if ($label) {
            $offered_labels[] = $label . ' ×' . $item['qty'];
        }
    }
    $wanted_label = hdh_get_item_label($trade_data['wanted_item']);
    $share_text = sprintf('Takas teklifi: %s ↔ %s', 
        implode(', ', $offered_labels),
        $wanted_label . ' ×' . $trade_data['wanted_qty']
    );
    
    // Get images
    $og_image = function_exists('hdh_get_listing_og_image_url') 
        ? hdh_get_listing_og_image_url($listing_id) 
        : '';
    $story_image = function_exists('hdh_get_listing_story_image_url') 
        ? hdh_get_listing_story_image_url($listing_id) 
        : '';
    
    ob_start();
    ?>
    <div class="share-buttons <?php echo esc_attr($context); ?>">
        <button class="share-btn share-whatsapp" 
                data-url="<?php echo esc_url($url); ?>"
                data-text="<?php echo esc_attr($share_text); ?>"
                aria-label="WhatsApp'ta Paylaş">
            <span class="share-icon">📱</span>
            <span class="share-label">WhatsApp</span>
        </button>
        
        <button class="share-btn share-twitter" 
                data-url="<?php echo esc_url($url); ?>"
                data-text="<?php echo esc_attr($share_text); ?>"
                aria-label="Twitter'da Paylaş">
            <span class="share-icon">🐦</span>
            <span class="share-label">Twitter</span>
        </button>
        
        <button class="share-btn share-facebook" 
                data-url="<?php echo esc_url($url); ?>"
                aria-label="Facebook'ta Paylaş">
            <span class="share-icon">👤</span>
            <span class="share-label">Facebook</span>
        </button>
        
        <button class="share-btn share-copy" 
                data-url="<?php echo esc_url($url); ?>"
                aria-label="Linki Kopyala">
            <span class="share-icon">🔗</span>
            <span class="share-label">Kopyala</span>
        </button>
        
        <?php if ($story_image) : ?>
        <button class="share-btn share-story" 
                data-story-image="<?php echo esc_url($story_image); ?>"
                aria-label="Hikaye Görseli İndir">
            <span class="share-icon">📸</span>
            <span class="share-label">Hikaye</span>
        </button>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

