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
    <div class="share-section-minimal">
        <p class="share-section-label">Burada paylaş:</p>
        <div class="share-buttons-minimal">
            <button class="share-btn-minimal share-whatsapp" 
                    data-url="<?php echo esc_url($url); ?>"
                    data-text="<?php echo esc_attr($share_text); ?>"
                    aria-label="WhatsApp'ta Paylaş"
                    title="WhatsApp">
                <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/icons/whatsapp.svg'); ?>" alt="WhatsApp" class="share-logo">
            </button>
            
            <button class="share-btn-minimal share-twitter" 
                    data-url="<?php echo esc_url($url); ?>"
                    data-text="<?php echo esc_attr($share_text); ?>"
                    aria-label="Twitter'da Paylaş"
                    title="Twitter">
                <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/icons/twitter.svg'); ?>" alt="Twitter" class="share-logo">
            </button>
            
            <button class="share-btn-minimal share-facebook" 
                    data-url="<?php echo esc_url($url); ?>"
                    aria-label="Facebook'ta Paylaş"
                    title="Facebook">
                <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/icons/facebook.svg'); ?>" alt="Facebook" class="share-logo">
            </button>
            
            <button class="share-btn-minimal share-copy" 
                    data-url="<?php echo esc_url($url); ?>"
                    aria-label="Linki Kopyala"
                    title="Linki Kopyala">
                <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/icons/copy-link.svg'); ?>" alt="Kopyala" class="share-logo">
            </button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

