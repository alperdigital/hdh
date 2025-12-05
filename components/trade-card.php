<?php
/**
 * HDH: Trade Offer Card Component
 * Farm-themed card for displaying trade offers
 * 
 * @param int $post_id Post ID of the trade offer
 */
if (!function_exists('hdh_render_trade_card')) {
    function hdh_render_trade_card($post_id = null) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        $trade_data = hdh_get_trade_data($post_id);
        $author_id = get_post_field('post_author', $post_id);
        $author_name = get_the_author_meta('display_name', $author_id);
        $post_date = get_the_date('d F Y', $post_id);
        $post_url = get_permalink($post_id);
        
        // Trust score
        $trust_plus = (int) get_user_meta($author_id, 'hayday_trust_plus', true);
        $trust_minus = (int) get_user_meta($author_id, 'hayday_trust_minus', true);
        
        // Status badge
        $status_class = $trade_data['trade_status'] === 'completed' ? 'status-completed' : 'status-open';
        $status_text = $trade_data['trade_status'] === 'completed' ? 'Tamamlandı ✅' : 'Açık';
        
        // Filter out empty offer items
        $offer_items = array_filter($trade_data['offer_items'], function($item) {
            return !empty($item['item']) && !empty($item['qty']);
        });
        ?>
        <article class="trade-card farm-board-card" data-post-id="<?php echo esc_attr($post_id); ?>">
            <div class="trade-card-header">
                <h3 class="trade-card-title">
                    <a href="<?php echo esc_url($post_url); ?>">
                        <?php echo esc_html(get_the_title($post_id)); ?>
                    </a>
                </h3>
                <span class="trade-status-badge <?php echo esc_attr($status_class); ?>">
                    <?php echo esc_html($status_text); ?>
                </span>
            </div>
            
            <div class="trade-card-content">
                <!-- İSTEDİĞİ ÜRÜN - Big and Clear -->
                <div class="trade-wanted-section">
                    <h4 class="trade-section-label trade-wanted-label">
                        <span class="label-icon">🔍</span>
                        İSTEDİĞİ:
                    </h4>
                    <div class="trade-item-display trade-wanted-item">
                        <?php 
                        $wanted_slug = $trade_data['wanted_item'];
                        $wanted_image = hdh_get_item_image($wanted_slug);
                        $wanted_label = hdh_get_item_label($wanted_slug);
                        if ($wanted_image) : ?>
                            <div class="trade-item-with-image">
                                <img src="<?php echo esc_url($wanted_image); ?>" 
                                     alt="<?php echo esc_attr($wanted_label); ?>" 
                                     class="trade-item-icon"
                                     loading="lazy"
                                     decoding="async"
                                     width="50"
                                     height="50">
                                <div class="trade-item-info">
                                    <span class="item-quantity"><?php echo esc_html($trade_data['wanted_qty']); ?>x</span>
                                    <span class="item-name"><?php echo esc_html($wanted_label); ?></span>
                                </div>
                            </div>
                        <?php else : ?>
                            <span class="item-quantity"><?php echo esc_html($trade_data['wanted_qty']); ?>x</span>
                            <span class="item-name"><?php echo esc_html($wanted_label ?: $trade_data['wanted_item']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- VEREBİLECEKLERİ - Subsection -->
                <?php if (!empty($offer_items)) : ?>
                    <div class="trade-offer-section">
                        <h4 class="trade-section-label trade-offer-label">
                            <span class="label-icon">🎁</span>
                            VEREBİLECEKLERİ:
                        </h4>
                        <div class="trade-offer-items">
                            <?php foreach ($offer_items as $offer) : 
                                $offer_slug = $offer['item'];
                                $offer_image = hdh_get_item_image($offer_slug);
                                $offer_label = hdh_get_item_label($offer_slug);
                            ?>
                                <div class="trade-offer-item">
                                    <?php if ($offer_image) : ?>
                                        <img src="<?php echo esc_url($offer_image); ?>" 
                                             alt="<?php echo esc_attr($offer_label); ?>" 
                                             class="trade-offer-item-icon"
                                             loading="lazy"
                                             decoding="async"
                                             width="35"
                                             height="35">
                                    <?php endif; ?>
                                    <div class="trade-offer-item-info">
                                        <span class="item-quantity"><?php echo esc_html($offer['qty']); ?>x</span>
                                        <span class="item-name"><?php echo esc_html($offer_label ?: $offer['item']); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="trade-card-footer">
                <div class="trade-card-meta">
                    <span class="trade-author">
                        <span class="author-icon">👤</span>
                        <?php echo esc_html($author_name); ?>
                        <?php if ($trust_plus > 0 || $trust_minus > 0) : ?>
                            <span class="trust-score">
                                (Güven: +<?php echo esc_html($trust_plus); ?> / -<?php echo esc_html($trust_minus); ?>)
                            </span>
                        <?php endif; ?>
                    </span>
                    <span class="trade-date">
                        <span class="date-icon">📅</span>
                        <?php echo esc_html($post_date); ?>
                    </span>
                </div>
                <a href="<?php echo esc_url($post_url); ?>" class="btn-trade-detail btn-wooden-sign btn-primary">
                    Takas Detayına Git →
                </a>
            </div>
        </article>
        <?php
    }
}
?>

