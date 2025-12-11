<?php
/**
 * Single Trade Offer Template
 * HDH: Detailed view for a single trade offer
 */

get_header();
?>

<main id="primary" class="site-main">
    <div class="container">
        <?php 
        // Debug: Check if we have posts
        if (!have_posts()) : ?>
            <div class="no-trade-found">
                <h1>İlan Bulunamadı</h1>
                <p>Bu ilan mevcut değil veya silinmiş olabilir.</p>
                <a href="<?php echo esc_url(home_url('/')); ?>" class="btn-back-to-list">← İlanlara Dön</a>
            </div>
        <?php else :
            while (have_posts()) : the_post(); 
                // Check if functions exist
                if (!function_exists('hdh_get_trade_data')) {
                    ?>
                    <div class="error-message">
                        <h2>Hata: Fonksiyon Bulunamadı</h2>
                        <p>hdh_get_trade_data fonksiyonu yüklenmemiş. Lütfen tema dosyalarını kontrol edin.</p>
                        <p>Post ID: <?php echo get_the_ID(); ?></p>
                        <p>Post Type: <?php echo get_post_type(); ?></p>
                    </div>
                    <?php
                    break;
                }
                
                $trade_data = hdh_get_trade_data();
                
                // Validate trade data
                if (empty($trade_data) || empty($trade_data['wanted_item'])) {
                    ?>
                    <article id="trade-<?php the_ID(); ?>" <?php post_class('single-trade-offer'); ?>>
                        <div class="trade-back-button">
                            <a href="<?php echo esc_url(home_url('/')); ?>" class="btn-back-link">← İlanlara Dön</a>
                        </div>
                        <header class="trade-header-single">
                            <h1 class="trade-title-single"><?php the_title(); ?></h1>
                        </header>
                        <div class="error-message">
                            <h2>Veri Eksik</h2>
                            <p>Bu ilan için veri bulunamadı. İlan eksik bilgiler içeriyor olabilir.</p>
                            <p><strong>Post ID:</strong> <?php echo get_the_ID(); ?></p>
                            <p><strong>Wanted Item:</strong> <?php echo esc_html(get_post_meta(get_the_ID(), '_hdh_wanted_item', true) ?: 'Boş'); ?></p>
                            <p><strong>Wanted Qty:</strong> <?php echo esc_html(get_post_meta(get_the_ID(), '_hdh_wanted_qty', true) ?: 'Boş'); ?></p>
                        </div>
                    </article>
                    <?php
                    break;
                }
                
                $author_id = get_post_field('post_author', get_the_ID());
                $author_name = get_the_author_meta('display_name', $author_id) ?: 'Bilinmeyen Kullanıcı';
                
                // Trust score
                $trust_plus = (int) get_user_meta($author_id, 'hayday_trust_plus', true);
                $trust_minus = (int) get_user_meta($author_id, 'hayday_trust_minus', true);
                
                // Get completed trades count
                $completed_trades_count = 0;
                $completed_trades_query = new WP_Query(array(
                    'post_type' => 'hayday_trade',
                    'author' => $author_id,
                    'post_status' => 'publish',
                    'meta_query' => array(
                        array(
                            'key' => '_hdh_trade_status',
                            'value' => 'completed',
                            'compare' => '='
                        )
                    ),
                    'posts_per_page' => -1,
                    'fields' => 'ids'
                ));
                if ($completed_trades_query->have_posts()) {
                    $completed_trades_count = $completed_trades_query->found_posts;
                }
                wp_reset_postdata();
                
                // Calculate average stars (based on trust_plus and completed trades)
                // If no completed trades, show 0 stars
                $average_stars = 0;
                if ($completed_trades_count > 0) {
                    // Calculate average: trust_plus represents total positive ratings
                    // Average = (trust_plus / completed_trades_count) normalized to 5 stars
                    // If trust_plus equals completed_trades_count, that means all trades got +1 (average 5 stars)
                    // If trust_plus is less, calculate proportion
                    $average_rating = $trust_plus / $completed_trades_count;
                    $average_stars = min(5, max(0, round($average_rating * 5, 1)));
                }
                
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
                
                <!-- Author Info with Farm Name, Trade Count, and Stars -->
                <div class="trade-author-info-header">
                    <div class="author-farm-name">
                        <span class="farm-icon">🌾</span>
                        <span class="farm-name-text"><?php echo esc_html($author_name); ?></span>
                    </div>
                    <div class="author-stats-header">
                        <span class="trade-count-badge">
                            (<?php echo esc_html($completed_trades_count); ?>)
                        </span>
                        <div class="stars-rating">
                            <?php 
                            // Display 5 stars - filled stars are bright, empty stars are dim
                            for ($i = 1; $i <= 5; $i++) {
                                $star_class = $i <= $average_stars ? 'star-filled' : 'star-empty';
                                echo '<span class="star ' . esc_attr($star_class) . '">⭐</span>';
                            }
                            ?>
                            <span class="stars-average"><?php echo number_format($average_stars, 1); ?>/5.0</span>
                        </div>
                    </div>
                </div>
                
                <div class="trade-meta-header-single">
                    <span class="trade-status-badge <?php echo esc_attr($status_class); ?>">
                        <?php echo esc_html($status_text); ?>
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
        
        <?php 
            endwhile; 
        endif; 
        ?>
    </div>
</main>

<?php
get_footer();
?>
