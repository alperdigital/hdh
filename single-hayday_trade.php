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
                
                <!-- Author Info with Blue Star Trust Indicator -->
                <div class="trade-author-info-header-newspaper">
                    <div class="author-info-newspaper">
                        <div class="author-star-section">
                            <span class="trust-star-large">
                                <?php if ($completed_trades_count > 0) : ?>
                                    ★<?php echo esc_html($completed_trades_count); ?>
                                <?php else : ?>
                                    ★
                                <?php endif; ?>
                            </span>
                            <div class="author-name-newspaper"><?php echo esc_html($author_name); ?></div>
                        </div>
                        <div class="star-explanation">
                            <?php if ($completed_trades_count > 0) : ?>
                                <p class="star-explanation-text">
                                    Bu kullanıcı <?php echo esc_html($completed_trades_count); ?> başarılı hediyeleşme yapmıştır.
                                </p>
                            <?php else : ?>
                                <p class="star-explanation-text">
                                    Bu kullanıcı henüz hediyeleşme yapmamış olabilir.
                                </p>
                            <?php endif; ?>
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
                        <span class="label-icon">🤍</span>
                        Hediye İstiyor:
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
                            Hediye Ediyor:
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
            
            <!-- Offer Flow Section -->
            <?php
            $current_user_id = get_current_user_id();
            $is_listing_owner = $current_user_id == $author_id;
            $listing_status = get_post_meta(get_the_ID(), '_hdh_trade_status', true);
            $accepted_offerer_id = get_post_meta(get_the_ID(), '_hdh_accepted_offerer_id', true);
            $author_confirmed = get_post_meta(get_the_ID(), '_hdh_author_confirmed', true);
            $offerer_confirmed = get_post_meta(get_the_ID(), '_hdh_offerer_confirmed', true);
            
            // Get all offers for this listing
            $offers = function_exists('hdh_get_listing_offers') ? hdh_get_listing_offers(get_the_ID()) : array();
            ?>
            
            <?php if (is_user_logged_in() && $trade_data['trade_status'] !== 'completed') : ?>
                
                <!-- If user is listing owner, show offers -->
                <?php if ($is_listing_owner) : ?>
                    <div class="offers-section">
                        <h2 class="offers-section-title">📬 Gelen Teklifler</h2>
                        <?php if (!empty($offers)) : ?>
                            <div class="offers-list">
                                <?php foreach ($offers as $index => $offer) : 
                                    $offerer_id = $offer['offerer_id'];
                                    $offerer_name = get_the_author_meta('display_name', $offerer_id) ?: 'Bilinmeyen';
                                    $offerer_completed_count = function_exists('hdh_get_completed_gift_count') ? hdh_get_completed_gift_count($offerer_id) : 0;
                                    $offer_status = isset($offer['status']) ? $offer['status'] : 'pending';
                                    $offer_date = isset($offer['created_at']) ? $offer['created_at'] : '';
                                ?>
                                    <div class="offer-card offer-status-<?php echo esc_attr($offer_status); ?>">
                                        <div class="offer-header">
                                            <div class="offer-user-info">
                                                <span class="offer-user-avatar">👤</span>
                                                <div class="offer-user-details">
                                                    <span class="offer-user-name"><?php echo esc_html($offerer_name); ?></span>
                                                    <span class="offer-user-trust">
                                                        <?php if ($offerer_completed_count > 0) : ?>
                                                            ★<?php echo esc_html($offerer_completed_count); ?> başarılı hediyeleşme
                                                        <?php else : ?>
                                                            ★ Yeni kullanıcı
                                                        <?php endif; ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <span class="offer-status-badge offer-badge-<?php echo esc_attr($offer_status); ?>">
                                                <?php 
                                                if ($offer_status === 'accepted') echo '✅ Kabul Edildi';
                                                elseif ($offer_status === 'rejected') echo '❌ Reddedildi';
                                                else echo '⏳ Bekliyor';
                                                ?>
                                            </span>
                                        </div>
                                        <?php if ($offer_date) : ?>
                                            <div class="offer-date">
                                                📅 <?php echo esc_html(human_time_diff(strtotime($offer_date), current_time('timestamp'))); ?> önce
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($offer_status === 'pending' && $listing_status === 'open') : ?>
                                            <div class="offer-actions">
                                                <button class="btn-accept-offer" data-listing-id="<?php echo esc_attr(get_the_ID()); ?>" data-offer-index="<?php echo esc_attr($index); ?>">
                                                    ✅ Kabul Et
                                                </button>
                                                <button class="btn-reject-offer" data-listing-id="<?php echo esc_attr(get_the_ID()); ?>" data-offer-index="<?php echo esc_attr($index); ?>">
                                                    ❌ Reddet
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($offer_status === 'accepted' && $listing_status === 'in_progress' && $accepted_offerer_id == $offerer_id) : ?>
                                            <div class="exchange-completion-section">
                                                <h3 class="completion-title">🎁 Hediyeleşmeyi Tamamla</h3>
                                                <p class="completion-explanation">
                                                    Hediyeleşme gerçekleştikten sonra, her iki tarafın da onaylaması gerekiyor.
                                                </p>
                                                <div class="completion-status">
                                                    <div class="completion-party">
                                                        <span class="completion-icon"><?php echo $author_confirmed === '1' ? '✅' : '⏳'; ?></span>
                                                        <span class="completion-label">
                                                            Sen <?php echo $author_confirmed === '1' ? '(Onayladın)' : '(Onay Bekleniyor)'; ?>
                                                        </span>
                                                    </div>
                                                    <div class="completion-party">
                                                        <span class="completion-icon"><?php echo $offerer_confirmed === '1' ? '✅' : '⏳'; ?></span>
                                                        <span class="completion-label">
                                                            <?php echo esc_html($offerer_name); ?> <?php echo $offerer_confirmed === '1' ? '(Onayladı)' : '(Onay Bekleniyor)'; ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <?php if ($author_confirmed !== '1') : ?>
                                                    <button class="btn-confirm-exchange" data-listing-id="<?php echo esc_attr(get_the_ID()); ?>">
                                                        ✅ Hediyeleşmeyi Onayla
                                                    </button>
                                                <?php else : ?>
                                                    <p class="waiting-confirmation">⏳ Diğer tarafın onayını bekliyorsunuz...</p>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else : ?>
                            <div class="no-offers-message">
                                <p>Henüz teklif gelmedi. İlanınız aktif, teklifler geldiğinde burada görünecek.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                
                <!-- If user is not listing owner and listing is open, show offer button -->
                <?php elseif ($listing_status === 'open') : ?>
                    <?php
                    // Check if user already made an offer
                    $user_already_offered = false;
                    foreach ($offers as $offer) {
                        if (isset($offer['offerer_id']) && $offer['offerer_id'] == $current_user_id) {
                            $user_already_offered = true;
                            break;
                        }
                    }
                    ?>
                    
                    <div class="make-offer-section">
                        <h2 class="make-offer-title">💬 Teklif Yap</h2>
                        <?php if ($user_already_offered) : ?>
                            <div class="offer-already-made">
                                <p>✅ Bu ilana zaten teklif yaptınız. İlan sahibinin yanıtını bekliyorsunuz.</p>
                            </div>
                        <?php else : ?>
                            <div class="offer-explanation">
                                <p><strong>Nasıl çalışır?</strong></p>
                                <p>1. Teklif yap butonuna tıklayın</p>
                                <p>2. İlan sahibi teklifinizi değerlendirecek</p>
                                <p>3. Kabul edilirse, hediyeleşme detayları için yorumlar bölümünden iletişime geçin</p>
                            </div>
                            <button class="btn-make-offer" data-listing-id="<?php echo esc_attr(get_the_ID()); ?>">
                                💬 Teklif Yap
                            </button>
                        <?php endif; ?>
                    </div>
                
                <!-- If user is the accepted offerer and exchange is in progress -->
                <?php elseif ($listing_status === 'in_progress' && $accepted_offerer_id == $current_user_id) : ?>
                    <div class="exchange-completion-section">
                        <h2 class="completion-title">🎁 Hediyeleşmeyi Tamamla</h2>
                        <p class="completion-explanation">
                            Teklifiniz kabul edildi! Hediyeleşme gerçekleştikten sonra, her iki tarafın da onaylaması gerekiyor.
                        </p>
                        <div class="completion-status">
                            <div class="completion-party">
                                <span class="completion-icon"><?php echo $author_confirmed === '1' ? '✅' : '⏳'; ?></span>
                                <span class="completion-label">
                                    <?php echo esc_html($author_name); ?> <?php echo $author_confirmed === '1' ? '(Onayladı)' : '(Onay Bekleniyor)'; ?>
                                </span>
                            </div>
                            <div class="completion-party">
                                <span class="completion-icon"><?php echo $offerer_confirmed === '1' ? '✅' : '⏳'; ?></span>
                                <span class="completion-label">
                                    Sen <?php echo $offerer_confirmed === '1' ? '(Onayladın)' : '(Onay Bekleniyor)'; ?>
                                </span>
                            </div>
                        </div>
                        <?php if ($offerer_confirmed !== '1') : ?>
                            <button class="btn-confirm-exchange" data-listing-id="<?php echo esc_attr(get_the_ID()); ?>">
                                ✅ Hediyeleşmeyi Onayla
                            </button>
                        <?php else : ?>
                            <p class="waiting-confirmation">⏳ İlan sahibinin onayını bekliyorsunuz...</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
            <?php elseif (!is_user_logged_in() && $trade_data['trade_status'] !== 'completed') : ?>
                <div class="login-prompt-section">
                    <h2 class="login-prompt-title">💬 Teklif Yapmak İster misiniz?</h2>
                    <p class="login-prompt-text">Teklif yapmak için giriş yapmanız gerekiyor.</p>
                    <button class="btn-open-login" onclick="document.getElementById('hdh-registration-modal').style.display='block';">
                        Giriş Yap / Üye Ol
                    </button>
                </div>
                <?php
                add_action('wp_footer', 'hdh_render_registration_modal', 999);
                add_action('wp_enqueue_scripts', 'hdh_enqueue_registration_modal_styles', 999);
                ?>
            <?php endif; ?>
            
            <!-- Action Buttons -->
            <div class="trade-actions-single">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="btn-back-to-list btn-wooden-sign">
                    ← İlanlara Dön
                </a>
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
