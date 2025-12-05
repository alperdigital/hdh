<?php
/**
 * Single Trade Offer Template
 * Template for displaying individual trade offers
 */

get_header();
?>

<main class="single-trade-main">
    <div class="container">
        <div class="content-wrapper">
            <div class="main-content">
                <?php while (have_posts()) : the_post(); 
                    $trade_data = hdh_get_trade_data();
                    $author_id = get_the_author_meta('ID');
                    $author_name = get_the_author_meta('display_name', $author_id);
                    
                    // Trust score
                    $trust_plus = (int) get_user_meta($author_id, 'hayday_trust_plus', true);
                    $trust_minus = (int) get_user_meta($author_id, 'hayday_trust_minus', true);
                    
                    // Filter out empty offer items
                    $offer_items = array_filter($trade_data['offer_items'], function($item) {
                        return !empty($item['item']) && !empty($item['qty']);
                    });
                    
                    // Status
                    $is_completed = $trade_data['trade_status'] === 'completed';
                ?>
                
                <!-- HDH: Trade Offer Header -->
                <div class="trade-offer-header farm-board-card">
                    <div class="trade-header-top">
                        <h1 class="trade-offer-title cartoon-title"><?php the_title(); ?></h1>
                        <span class="trade-status-badge <?php echo $is_completed ? 'status-completed' : 'status-open'; ?>">
                            <?php echo $is_completed ? 'Tamamlandı ✅' : 'Açık'; ?>
                        </span>
                    </div>
                    
                    <?php if ($is_completed) : ?>
                        <div class="trade-completed-banner">
                            <p>✅ Bu takas tamamlandı. Yeni teklifler kabul edilmiyor.</p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- HDH: Explanation Text -->
                    <div class="trade-explanation">
                        <h3>📖 Nasıl Çalışır?</h3>
                        <p>
                            <strong>İlan sahibi</strong> "İSTEDİĞİ" ürünü belirtir. 
                            Siz bu ürünü vererek karşılığında "VEREBİLECEKLERİ" listesindeki ürünlerden birini alabilirsiniz.
                        </p>
                        <p>
                            <strong>Örnek:</strong> İlan sahibi "7 Bant istiyorum, 7 Cıvata veya 7 Kalas verebilirim" diyor.
                            Siz "Ben 6 Bant veriyim, sen bana 6 Kalas ver" şeklinde teklif yapabilirsiniz.
                        </p>
                    </div>
                </div>
                
                <!-- HDH: Trade Details -->
                <div class="trade-details-section">
                    <!-- İSTEDİĞİ ÜRÜN -->
                    <div class="trade-detail-card trade-wanted-card">
                        <h2 class="trade-detail-title">
                            <span class="title-icon">🔍</span>
                            İSTEDİĞİ ÜRÜN
                        </h2>
                        <div class="trade-detail-content">
                            <div class="trade-item-large">
                                <span class="item-quantity-large"><?php echo esc_html($trade_data['wanted_qty']); ?>x</span>
                                <span class="item-name-large"><?php echo esc_html($trade_data['wanted_item']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- VEREBİLECEKLERİ -->
                    <?php if (!empty($offer_items)) : ?>
                        <div class="trade-detail-card trade-offer-card">
                            <h2 class="trade-detail-title">
                                <span class="title-icon">🎁</span>
                                VEREBİLECEKLERİ
                            </h2>
                            <div class="trade-detail-content">
                                <div class="trade-offer-items-large">
                                    <?php foreach ($offer_items as $offer) : ?>
                                        <div class="trade-offer-item-large">
                                            <span class="item-quantity-large"><?php echo esc_html($offer['qty']); ?>x</span>
                                            <span class="item-name-large"><?php echo esc_html($offer['item']); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- İlan Detayları -->
                    <div class="trade-detail-card trade-info-card">
                        <h2 class="trade-detail-title">
                            <span class="title-icon">📋</span>
                            İlan Detayları
                        </h2>
                        <div class="trade-detail-content">
                            <div class="trade-info-item">
                                <span class="info-label">İlan Sahibi:</span>
                                <span class="info-value">
                                    <?php echo esc_html($author_name); ?>
                                    <?php if ($trust_plus > 0 || $trust_minus > 0) : ?>
                                        <span class="trust-score-large">
                                            (Güven Skoru: +<?php echo esc_html($trust_plus); ?> / -<?php echo esc_html($trust_minus); ?>)
                                        </span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="trade-info-item">
                                <span class="info-label">Yayınlanma Tarihi:</span>
                                <span class="info-value"><?php echo mi_get_turkish_date('d F Y, H:i'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- İlan Açıklaması -->
                    <?php if (get_the_content()) : ?>
                        <div class="trade-detail-card trade-description-card">
                            <h2 class="trade-detail-title">
                                <span class="title-icon">📝</span>
                                Açıklama
                            </h2>
                            <div class="trade-detail-content trade-description">
                                <?php the_content(); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- HDH: Teklifler ve Yorumlar Section -->
                <div class="trade-offers-section">
                    <h2 class="section-title-cartoon">
                        <span class="title-icon">💬</span>
                        Teklifler ve Yorumlar
                    </h2>
                    
                    <?php
                    // Enable comments for trade offers (form is customized in inc/comments.php)
                    if (comments_open() || get_comments_number()) {
                        comments_template();
                    }
                    ?>
                </div>
                
                <!-- HDH: Post Navigation -->
                <nav class="post-navigation farm-navigation">
                    <?php
                    $prev_post = get_previous_post(false, '', 'hayday_trade');
                    $next_post = get_next_post(false, '', 'hayday_trade');
                    ?>
                    <?php if ($prev_post || $next_post) : ?>
                        <div class="nav-links">
                            <?php if ($prev_post) : ?>
                                <div class="nav-previous">
                                    <span class="nav-subtitle">← Önceki İlan</span>
                                    <a href="<?php echo esc_url(get_permalink($prev_post->ID)); ?>" class="nav-link">
                                        <?php echo esc_html(get_the_title($prev_post->ID)); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($next_post) : ?>
                                <div class="nav-next">
                                    <span class="nav-subtitle">Sonraki İlan →</span>
                                    <a href="<?php echo esc_url(get_permalink($next_post->ID)); ?>" class="nav-link">
                                        <?php echo esc_html(get_the_title($next_post->ID)); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </nav>
                
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</main>

<?php
get_footer();
?>

