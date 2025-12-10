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
            <!-- Back Button -->
            <div class="trade-back-button">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="btn-back-link">
                    ← İlanlara Dön
                </a>
            </div>
            
            <!-- Trade Header -->
            <header class="trade-header-single">
                <h1 class="trade-title-single"><?php the_title(); ?></h1>
                <div class="trade-meta-header-single">
                    <span class="trade-status-badge <?php echo esc_attr($status_class); ?>">
                        <?php echo esc_html($status_text); ?>
                    </span>
                    <span class="trade-date-single">
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
            
            <!-- Trade Details with Gift Box Design -->
            <div class="trade-details-single">
                <!-- İSTEDİĞİ HEDİYE - Green Gift Box -->
                <div class="trade-wanted-wrapper-single">
                    <h2 class="trade-section-label trade-wanted-label">
                        <span class="label-icon">🔍</span>
                        İstediği hediye:
                    </h2>
                    <div class="gift-box gift-box-wanted gift-box-single">
                        <div class="gift-box-content">
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
                                         width="80"
                                         height="80">
                                    <div class="trade-item-info">
                                        <span class="item-quantity"><?php echo esc_html($trade_data['wanted_qty']); ?>x</span>
                                        <span class="item-name"><?php echo esc_html($wanted_label); ?></span>
                                    </div>
                                </div>
                            <?php else : ?>
                                <div class="trade-item-info">
                                    <span class="item-quantity"><?php echo esc_html($trade_data['wanted_qty']); ?>x</span>
                                    <span class="item-name"><?php echo esc_html($wanted_label ?: $trade_data['wanted_item']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- VEREBİLECEKLERİ HEDİYE - Red Gift Box -->
                <?php if (!empty($offer_items)) : ?>
                    <div class="trade-offer-wrapper-single">
                        <h2 class="trade-section-label trade-offer-label">
                            <span class="label-icon">🎁</span>
                            Vereceği hediye:
                        </h2>
                        <div class="gift-box gift-box-offer gift-box-single">
                            <div class="gift-box-content">
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
                                                     width="50"
                                                     height="50">
                                            <?php endif; ?>
                                            <div class="trade-offer-item-info">
                                                <span class="item-quantity"><?php echo esc_html($offer['qty']); ?>x</span>
                                                <span class="item-name"><?php echo esc_html($offer_label ?: $offer['item']); ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
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
            <div class="trade-explanation-single">
                <div class="explanation-box">
                    <h3 class="explanation-title">💡 Nasıl Çalışır?</h3>
                    <div class="explanation-content">
                        <p><strong>Yeşil hediye paketi:</strong> İlan sahibinin istediği hediyedir. Bu hediyeyi siz vereceksiniz.</p>
                        <p><strong>Kırmızı hediye paketi:</strong> İlan sahibinin verebileceği hediyelerdir. Bunlardan birini siz alacaksınız.</p>
                        <p class="example-text"><strong>Örnek:</strong> İlan sahibi "7 Bant istiyorum, 7 Cıvata verebilirim" diyor. Siz 7 Bant verip (yeşil paket), 7 Cıvata alabilirsiniz (kırmızı paket).</p>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="trade-actions-single">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="btn-back-to-list btn-wooden-sign">
                    ← İlanlara Dön
                </a>
                <?php if ($trade_data['trade_status'] === 'open' && is_user_logged_in() && get_current_user_id() != $author_id) : ?>
                    <button class="btn-contact-seller btn-wooden-sign btn-primary">
                        💬 İlan Sahibiyle İletişime Geç
                    </button>
                <?php endif; ?>
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
