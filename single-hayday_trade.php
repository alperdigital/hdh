<?php
/**
 * Single Trade Offer Template
 * HDH: Detailed view for a single trade offer
 */

get_header();
?>

<main id="primary" class="site-main">
    <div class="container">
        <?php while (have_posts()) : the_post(); 
            $trade_data = hdh_get_trade_data();
            $author_id = get_post_field('post_author', get_the_ID());
            $author_name = get_the_author_meta('display_name', $author_id);
            
            // Trust score
            $trust_plus = (int) get_user_meta($author_id, 'hayday_trust_plus', true);
            $trust_minus = (int) get_user_meta($author_id, 'hayday_trust_minus', true);
            
            // Status
            $status_class = $trade_data['trade_status'] === 'completed' ? 'status-completed' : 'status-open';
            $status_text = $trade_data['trade_status'] === 'completed' ? 'Tamamlandı ✅' : 'Açık';
            
            // Filter out empty offer items
            $offer_items = array_filter($trade_data['offer_items'], function($item) {
                return !empty($item['item']) && !empty($item['qty']);
            });
        ?>
        
        <article id="trade-<?php the_ID(); ?>" <?php post_class('single-trade-offer'); ?>>
            <!-- Trade Header -->
            <header class="trade-header">
                <h1 class="trade-title"><?php the_title(); ?></h1>
                <div class="trade-meta-header">
                    <span class="trade-status-badge <?php echo esc_attr($status_class); ?>">
                        <?php echo esc_html($status_text); ?>
                    </span>
                    <span class="trade-date">
                        <span class="date-icon">📅</span>
                        <?php echo get_the_date('d F Y, H:i'); ?>
                    </span>
                </div>
            </header>
            
            <?php if ($trade_data['trade_status'] === 'completed') : ?>
                <div class="trade-completed-banner">
                    <p>✅ Bu hediyeleşme tamamlandı</p>
                </div>
            <?php endif; ?>
            
            <!-- Trade Details -->
            <div class="trade-details">
                <!-- İSTEDİĞİ ÜRÜN -->
                <div class="trade-wanted-section detailed">
                    <h2 class="trade-section-title">
                        <span class="title-icon">🔍</span>
                        İSTEDİĞİ ÜRÜN
                    </h2>
                    <div class="trade-item-display-large">
                        <?php 
                        $wanted_slug = $trade_data['wanted_item'];
                        $wanted_image = hdh_get_item_image($wanted_slug);
                        $wanted_label = hdh_get_item_label($wanted_slug);
                        if ($wanted_image) : ?>
                            <div class="trade-item-with-image-large">
                                <img src="<?php echo esc_url($wanted_image); ?>" 
                                     alt="<?php echo esc_attr($wanted_label); ?>" 
                                     class="trade-item-icon-large"
                                     loading="lazy"
                                     decoding="async"
                                     width="80"
                                     height="80">
                                <div class="trade-item-info-large">
                                    <span class="item-quantity-large"><?php echo esc_html($trade_data['wanted_qty']); ?>x</span>
                                    <span class="item-name-large"><?php echo esc_html($wanted_label); ?></span>
                                </div>
                            </div>
                        <?php else : ?>
                            <div class="trade-item-info-large">
                                <span class="item-quantity-large"><?php echo esc_html($trade_data['wanted_qty']); ?>x</span>
                                <span class="item-name-large"><?php echo esc_html($wanted_label ?: $trade_data['wanted_item']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- VEREBİLECEKLERİ -->
                <?php if (!empty($offer_items)) : ?>
                    <div class="trade-offer-section detailed">
                        <h2 class="trade-section-title">
                            <span class="title-icon">🎁</span>
                            VEREBİLECEKLERİ
                        </h2>
                        <div class="trade-offer-items-detailed">
                            <?php foreach ($offer_items as $offer) : 
                                $offer_slug = $offer['item'];
                                $offer_image = hdh_get_item_image($offer_slug);
                                $offer_label = hdh_get_item_label($offer_slug);
                            ?>
                                <div class="trade-offer-item-detailed">
                                    <?php if ($offer_image) : ?>
                                        <img src="<?php echo esc_url($offer_image); ?>" 
                                             alt="<?php echo esc_attr($offer_label); ?>" 
                                             class="trade-offer-item-icon-large"
                                             loading="lazy"
                                             decoding="async"
                                             width="60"
                                             height="60">
                                    <?php endif; ?>
                                    <div class="trade-offer-item-info-large">
                                        <span class="item-quantity-large"><?php echo esc_html($offer['qty']); ?>x</span>
                                        <span class="item-name-large"><?php echo esc_html($offer_label ?: $offer['item']); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Author Info -->
            <div class="trade-author-section">
                <h3 class="author-section-title">İlan Sahibi</h3>
                <div class="author-info">
                    <span class="author-name">👤 <?php echo esc_html($author_name); ?></span>
                    <?php if ($trust_plus > 0 || $trust_minus > 0) : ?>
                        <span class="trust-score-detailed">
                            Güven Skoru: +<?php echo esc_html($trust_plus); ?> / -<?php echo esc_html($trust_minus); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Explanation Text -->
            <div class="trade-explanation">
                <p><strong>Nasıl Çalışır?</strong></p>
                <p>İlan sahibi yukarıda "İSTEDİĞİ" ürünü belirtmiştir. Siz bu ürünü vererek, karşılığında "VEREBİLECEKLERİ" listesindeki ürünlerden birini alabilirsiniz.</p>
                <p>Örnek: İlan sahibi "7 Bant istiyorum, 7 Cıvata verebilirim" diyor. Siz 7 Bant verip, 7 Cıvata alabilirsiniz.</p>
            </div>
            
            <!-- Comments Section: Teklifler ve Yorumlar -->
            <div class="trade-comments-section">
                <h2 class="comments-title">Teklifler ve Yorumlar</h2>
                <?php
                // Rename comment form labels
                add_filter('comment_form_defaults', function($defaults) {
                    $defaults['title_reply'] = 'Teklif yap / Mesaj bırak';
                    $defaults['label_submit'] = 'Teklif Gönder';
                    return $defaults;
                });
                
                comments_template();
                ?>
            </div>
            
        </article>
        
        <?php endwhile; ?>
    </div>
</main>

<?php
get_footer();
?>
