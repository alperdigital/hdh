<?php
/**
 * HDH: Listing Card Component (Newspaper Style)
 * Hay Day newspaper-inspired design for gift listings
 * 
 * @param int $post_id Post ID of the listing
 */
if (!function_exists('hdh_render_trade_card')) {
    function hdh_render_trade_card($post_id = null) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        $trade_data = hdh_get_trade_data($post_id);
        $author_id = get_post_field('post_author', $post_id);
        $author_name = get_the_author_meta('display_name', $author_id);
        $post_url = get_permalink($post_id);
        
        // Get completed gift count
        $completed_gift_count = function_exists('hdh_get_completed_gift_count') 
            ? hdh_get_completed_gift_count($author_id) 
            : 0;
        
        // Get main item name (from wanted_item) for header
        $wanted_slug = $trade_data['wanted_item'];
        $main_item_label = hdh_get_item_label($wanted_slug);
        $main_item_name = strtoupper($main_item_label ?: $wanted_slug);
        
        // Filter out empty offer items (Hediye Ediyor)
        $offer_items = array_filter($trade_data['offer_items'], function($item) {
            return !empty($item['item']) && !empty($item['qty']);
        });
        
        // Wanted item (Hediye İstiyor)
        $wanted_item = array(
            'item' => $wanted_slug,
            'qty' => $trade_data['wanted_qty']
        );
        
        // Calculate relative time
        $post_time = get_post_time('U', false, $post_id);
        $current_time = current_time('timestamp');
        $time_diff = $current_time - $post_time;
        
        if ($time_diff < 60) {
            $relative_time = $time_diff . 's';
        } elseif ($time_diff < 3600) {
            $relative_time = floor($time_diff / 60) . 'dk';
        } elseif ($time_diff < 86400) {
            $relative_time = floor($time_diff / 3600) . 's';
        } else {
            $relative_time = floor($time_diff / 86400) . 'g';
        }
        ?>
        <article class="listing-card-newspaper trade-card-clickable" data-post-id="<?php echo esc_attr($post_id); ?>" data-post-url="<?php echo esc_url($post_url); ?>">
            <!-- Header: Main Item Name (Uppercase) -->
            <header class="listing-card-header">
                <h3 class="listing-card-title"><?php echo esc_html($main_item_name); ?></h3>
            </header>
            
            <div class="listing-card-divider"></div>
            
            <!-- 🎁 Hediye Ediyor (Gifting Items) -->
            <div class="listing-card-section listing-gifting-section">
                <div class="listing-section-label">
                    <span class="section-icon">🎁</span>
                    <span class="section-text">Hediye Ediyor</span>
                </div>
                <div class="listing-items-list">
                    <?php 
                    $offer_count = 0;
                    $max_visible_offers = 3;
                    foreach ($offer_items as $offer) : 
                        if ($offer_count >= $max_visible_offers) break;
                        $offer_slug = $offer['item'];
                        $offer_image = hdh_get_item_image($offer_slug);
                        $offer_label = hdh_get_item_label($offer_slug);
                        $offer_count++;
                    ?>
                        <div class="listing-item-row">
                            <?php if ($offer_image) : ?>
                                <img src="<?php echo esc_url($offer_image); ?>" 
                                     alt="<?php echo esc_attr($offer_label); ?>" 
                                     class="listing-item-icon"
                                     loading="lazy"
                                     decoding="async"
                                     width="24"
                                     height="24">
                            <?php endif; ?>
                            <span class="listing-item-name"><?php echo esc_html($offer_label ?: $offer_slug); ?></span>
                            <span class="listing-item-quantity">x<?php echo esc_html($offer['qty']); ?></span>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (count($offer_items) > $max_visible_offers) : 
                        $remaining = count($offer_items) - $max_visible_offers;
                    ?>
                        <div class="listing-item-more">+<?php echo esc_html($remaining); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="listing-card-divider"></div>
            
            <!-- 🤍 Hediye İstiyor (Requested Items) -->
            <div class="listing-card-section listing-requesting-section">
                <div class="listing-section-label">
                    <span class="section-icon">🤍</span>
                    <span class="section-text">Hediye İstiyor</span>
                </div>
                <div class="listing-items-list">
                    <?php 
                    $wanted_image = hdh_get_item_image($wanted_slug);
                    $wanted_label = hdh_get_item_label($wanted_slug);
                    ?>
                    <div class="listing-item-row">
                        <?php if ($wanted_image) : ?>
                            <img src="<?php echo esc_url($wanted_image); ?>" 
                                 alt="<?php echo esc_attr($wanted_label); ?>" 
                                 class="listing-item-icon"
                                 loading="lazy"
                                 decoding="async"
                                 width="24"
                                 height="24">
                        <?php endif; ?>
                        <span class="listing-item-name"><?php echo esc_html($wanted_label ?: $wanted_slug); ?></span>
                        <span class="listing-item-quantity">x<?php echo esc_html($wanted_item['qty']); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Blue Star Trust Indicator (Bottom-left) -->
            <div class="listing-trust-star">
                <?php if ($completed_gift_count > 0) : ?>
                    <span class="trust-star-filled">★<?php echo esc_html($completed_gift_count); ?></span>
                <?php else : ?>
                    <span class="trust-star-empty">★</span>
                <?php endif; ?>
            </div>
            
            <!-- Footer: Star, Username, Time -->
            <footer class="listing-card-footer">
                <span class="listing-footer-star">
                    <?php if ($completed_gift_count > 0) : ?>
                        ★<?php echo esc_html($completed_gift_count); ?>
                    <?php else : ?>
                        ★
                    <?php endif; ?>
                </span>
                <span class="listing-footer-username"><?php echo esc_html($author_name); ?></span>
                <span class="listing-footer-separator">·</span>
                <span class="listing-footer-time"><?php echo esc_html($relative_time); ?></span>
            </footer>
        </article>
        <?php
    }
}
?>
