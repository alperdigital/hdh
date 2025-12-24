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
        
        // Get presence bucket and label
        $presence_bucket = '3+ days';
        $presence_label = '3+ gün önce';
        $presence_timestamp = null;
        
        if (function_exists('hdh_get_presence_bucket')) {
            $presence_bucket = hdh_get_presence_bucket($author_id);
            $presence_data = hdh_get_user_presence($author_id);
            if ($presence_data && isset($presence_data['last_seen_at'])) {
                $presence_timestamp = strtotime($presence_data['last_seen_at']);
            }
            
            if (function_exists('hdh_format_presence_label')) {
                $presence_label = hdh_format_presence_label($presence_bucket, $presence_timestamp);
            }
        } else {
            // Fallback to relative time if presence system not available
            $post_time = get_post_time('U', false, $post_id);
            $current_time = current_time('timestamp');
            $time_diff = $current_time - $post_time;
            
            if ($time_diff < 60) {
                $presence_label = $time_diff . 's';
            } elseif ($time_diff < 3600) {
                $presence_label = floor($time_diff / 60) . 'dk';
            } elseif ($time_diff < 86400) {
                $presence_label = floor($time_diff / 3600) . 's';
            } else {
                $presence_label = floor($time_diff / 86400) . 'g';
            }
        }
        
        // Get listing creation time for secondary display (optional)
        $post_time = get_post_time('U', false, $post_id);
        $listing_creation_time = get_the_date('d.m.Y H:i', $post_id);
        
        // Build title: "X istiyorum Y verebilirim"
        $wanted_label = hdh_get_item_label($wanted_slug);
        $wanted_text = $trade_data['wanted_qty'] . ' ' . $wanted_label . ' istiyorum';
        
        $offer_labels = array();
        foreach ($offer_items as $offer) {
            $offer_label = hdh_get_item_label($offer['item']);
            $offer_labels[] = $offer['qty'] . ' ' . $offer_label;
        }
        $offer_text = !empty($offer_labels) ? implode(', ', $offer_labels) . ' verebilirim' : '';
        
        $listing_title = $wanted_text . ($offer_text ? ', ' . $offer_text : '');
        
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
        <a href="<?php echo esc_url($post_url); ?>" class="listing-unified-block listing-simplified">
            <!-- Title -->
            <h3 class="listing-title"><?php echo esc_html($listing_title); ?></h3>
            
            <!-- Meta Row: Level + Farm Name + Time -->
            <div class="listing-meta-row-unified">
                <div class="hdh-level-badge <?php echo esc_attr($digit_class); ?>" 
                     aria-label="Seviye <?php echo esc_attr($user_level); ?>"
                     title="Seviye <?php echo esc_attr($user_level); ?>">
                    <?php echo esc_html($user_level); ?>
                </div>
                <span class="listing-meta-farm-name">
                    <?php echo esc_html($author_name); ?>
                </span>
                <span class="listing-meta-time listing-presence-<?php echo esc_attr(str_replace(array('+', ' '), array('plus', '-'), $presence_bucket)); ?>">
                    <?php echo esc_html($presence_label); ?>
                </span>
            </div>
        </a>
        <?php
    }
}
?>
