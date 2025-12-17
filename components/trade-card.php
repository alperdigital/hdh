<?php
/**
 * HDH: Listing Card Component (Newspaper Style - Updated)
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
        
        // Filter out empty offer items (Hediye Ediyor)
        $offer_items = array_filter($trade_data['offer_items'], function($item) {
            return !empty($item['item']) && !empty($item['qty']);
        });
        
        // Wanted item (Hediye İstiyor)
        $wanted_slug = $trade_data['wanted_item'];
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
        <a href="<?php echo esc_url($post_url); ?>" class="listing-unified-block">
            <!-- Meta Row: Blue Star (Level) + Farm Name + Time (Top, Left Aligned) -->
            <header class="listing-meta-row-unified">
                <?php 
                // Get user level
                $user_level = 1;
                if (function_exists('hdh_get_user_state')) {
                    $user_state = hdh_get_user_state($author_id);
                    $user_level = $user_state['level'] ?? 1;
                }
                // Determine digit class based on level
                $level_int = (int) $user_level;
                $digits = strlen((string)$level_int);
                $digit_class = $digits === 1 ? 'lvl-d1' : ($digits === 2 ? 'lvl-d2' : 'lvl-d3');
                ?>
                <div class="hdh-level-badge <?php echo esc_attr($digit_class); ?>" 
                     aria-label="Seviye <?php echo esc_attr($user_level); ?>"
                     title="Seviye <?php echo esc_attr($user_level); ?>">
                    <?php echo esc_html($user_level); ?>
                </div>
                <span class="listing-meta-farm-name">
                    <?php echo esc_html($author_name); ?>
                </span>
                <span class="listing-meta-time"><?php echo esc_html($relative_time); ?></span>
            </header>
            
            <!-- Two-Column Layout: Gifted (Left) | Requested (Right) -->
            <div class="listing-content-columns-unified">
                <!-- Left Column: Hediye Ediyor Items -->
                <div class="listing-column listing-column-gifting">
                    <div class="listing-items-list">
                        <?php 
                        foreach ($offer_items as $offer) : 
                            $offer_slug = $offer['item'];
                            $offer_image = hdh_get_item_image($offer_slug);
                            $offer_label = hdh_get_item_label($offer_slug);
                        ?>
                            <div class="listing-item-row">
                                <?php if ($offer_image) : ?>
                                    <img src="<?php echo esc_url($offer_image); ?>" 
                                         alt="<?php echo esc_attr($offer_label); ?>" 
                                         class="listing-item-icon"
                                         loading="lazy"
                                         decoding="async"
                                         width="20"
                                         height="20">
                                <?php endif; ?>
                                <span class="listing-item-name"><?php echo esc_html($offer_label ?: $offer_slug); ?></span>
                                <span class="listing-item-quantity">×<?php echo esc_html($offer['qty']); ?></span>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($offer_items)) : ?>
                            <div class="listing-item-empty">—</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Vertical Divider -->
                <div class="listing-column-divider"></div>
                
                <!-- Right Column: Hediye İstiyor Items -->
                <div class="listing-column listing-column-requesting">
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
                                     width="20"
                                     height="20">
                            <?php endif; ?>
                            <span class="listing-item-name"><?php echo esc_html($wanted_label ?: $wanted_slug); ?></span>
                            <span class="listing-item-quantity">×<?php echo esc_html($wanted_item['qty']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer: Hediye (Left) | İstek (Right) with Divider -->
            <footer class="listing-header-split">
                <div class="listing-header-left">
                    <span class="listing-header-icon">🎁</span>
                    <span class="listing-header-label"><?php echo esc_html(hdh_get_content('trade_single', 'offering_label', 'Hediye')); ?></span>
                </div>
                <div class="listing-header-divider"></div>
                <div class="listing-header-right">
                    <span class="listing-header-icon">🤍</span>
                    <span class="listing-header-label"><?php echo esc_html(hdh_get_content('trade_single', 'wanted_label', 'İstek')); ?></span>
                </div>
            </footer>
        </a>
        <?php
    }
}
?>
