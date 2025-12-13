<?php
/**
 * Single Trade Offer Template - Modern Design
 * HDH: Detailed view for a single trade offer with messaging
 */

get_header();

if (!have_posts()) : ?>
    <main class="single-trade-main">
        <div class="container">
            <div class="trade-not-found">
                <h1>İlan Bulunamadı</h1>
                <p>Bu ilan mevcut değil veya silinmiş olabilir.</p>
                <a href="<?php echo esc_url(home_url('/ara')); ?>" class="btn-back">← İlanlara Dön</a>
            </div>
        </div>
    </main>
<?php else :
    while (have_posts()) : the_post();
        $post_id = get_the_ID();
        $trade_data = hdh_get_trade_data();
        $author_id = get_post_field('post_author', $post_id);
        $current_user_id = get_current_user_id();
        $is_owner = ($current_user_id == $author_id);
        
        // Get trade status and accepted offer
        $trade_status = get_post_meta($post_id, '_hdh_trade_status', true) ?: 'open';
        $accepted_offer_id = get_post_meta($post_id, '_hdh_accepted_offer_id', true);
        $accepted_offerer_id = get_post_meta($post_id, '_hdh_accepted_offerer_id', true);
        
        // Check if current user is the accepted offerer
        $is_accepted_offerer = ($current_user_id == $accepted_offerer_id);
        
        // Get author info
        $author_name = get_the_author_meta('display_name', $author_id);
        $author_farm_number = get_user_meta($author_id, 'hayday_farm_number', true);
        $completed_count = function_exists('hdh_get_completed_gift_count') ? hdh_get_completed_gift_count($author_id) : 0;
        
        // Filter offer items
        $offer_items = array_filter($trade_data['offer_items'], function($item) {
            return !empty($item['item']) && !empty($item['qty']);
        });
        
        // Get all offers for this listing (if owner)
        $offers = array();
        if ($is_owner && $trade_status !== 'completed') {
            $offers_query = new WP_Query(array(
                'post_type' => 'hayday_offer',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => '_hdh_listing_id',
                        'value' => $post_id,
                        'compare' => '='
                    )
                ),
                'orderby' => 'date',
                'order' => 'DESC'
            ));
            
            if ($offers_query->have_posts()) {
                while ($offers_query->have_posts()) {
                    $offers_query->the_post();
                    $offer_id = get_the_ID();
                    $offers[] = array(
                        'id' => $offer_id,
                        'offerer_id' => get_post_field('post_author', $offer_id),
                        'offerer_name' => get_the_author_meta('display_name', get_post_field('post_author', $offer_id)),
                        'status' => get_post_meta($offer_id, '_hdh_offer_status', true) ?: 'pending',
                        'wanted_qty' => get_post_meta($offer_id, '_hdh_offer_wanted_qty', true),
                        'offer_items' => get_post_meta($offer_id, '_hdh_offer_items', true),
                        'date' => get_the_date('d M Y, H:i')
                    );
                }
                wp_reset_postdata();
            }
        }
        ?>
        
        <main class="single-trade-main">
            <div class="container">
                <!-- Back Button -->
                <a href="<?php echo esc_url(home_url('/ara')); ?>" class="btn-back-single">
                    ← İlanlara Dön
                </a>
                
                <!-- Trade Card -->
                <div class="single-trade-card">
                    <!-- Header -->
                    <div class="single-trade-header">
                        <h1 class="single-trade-title"><?php the_title(); ?></h1>
                        <div class="single-trade-status">
                            <?php if ($trade_status === 'completed') : ?>
                                <span class="status-badge status-completed">✅ Tamamlandı</span>
                            <?php elseif ($trade_status === 'accepted') : ?>
                                <span class="status-badge status-accepted">🤝 Kabul Edildi</span>
                            <?php else : ?>
                                <span class="status-badge status-open">🟢 Açık</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Author Info -->
                    <div class="single-trade-author">
                        <div class="author-info">
                            <div class="author-star">
                                ★<?php echo esc_html($completed_count); ?>
                            </div>
                            <div class="author-details">
                                <div class="author-name"><?php echo esc_html($author_name); ?></div>
                                <div class="author-stats">
                                    <?php echo esc_html($completed_count); ?> başarılı hediyeleşme
                                </div>
                            </div>
                        </div>
                        
                        <?php if (($is_owner || $is_accepted_offerer) && $trade_status === 'accepted' && $author_farm_number) : ?>
                            <div class="farm-number-display">
                                <span class="farm-label">🏡 Çiftlik No:</span>
                                <span class="farm-number"><?php echo esc_html($author_farm_number); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Trade Items -->
                    <div class="single-trade-items">
                        <!-- Wanted Item -->
                        <div class="trade-item-section">
                            <div class="trade-item-header">
                                <span class="trade-item-icon">🤍</span>
                                <span class="trade-item-label">Hediye İstiyor</span>
                            </div>
                            <div class="trade-item-box wanted-box">
                                <?php 
                                $wanted_slug = $trade_data['wanted_item'];
                                $wanted_image = hdh_get_item_image($wanted_slug);
                                $wanted_label = hdh_get_item_label($wanted_slug);
                                ?>
                                <img src="<?php echo esc_url($wanted_image); ?>" 
                                     alt="<?php echo esc_attr($wanted_label); ?>" 
                                     class="item-image">
                                <div class="item-info">
                                    <div class="item-qty"><?php echo esc_html($trade_data['wanted_qty']); ?>x</div>
                                    <div class="item-name"><?php echo esc_html($wanted_label); ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Offer Items -->
                        <div class="trade-item-section">
                            <div class="trade-item-header">
                                <span class="trade-item-icon">🎁</span>
                                <span class="trade-item-label">Hediye Ediyor</span>
                            </div>
                            <div class="trade-item-box offer-box">
                                <?php foreach ($offer_items as $offer) : 
                                    $offer_slug = $offer['item'];
                                    $offer_image = hdh_get_item_image($offer_slug);
                                    $offer_label = hdh_get_item_label($offer_slug);
                                ?>
                                    <div class="offer-item">
                                        <img src="<?php echo esc_url($offer_image); ?>" 
                                             alt="<?php echo esc_attr($offer_label); ?>" 
                                             class="offer-item-image">
                                        <div class="offer-item-info">
                                            <div class="offer-item-qty"><?php echo esc_html($offer['qty']); ?>x</div>
                                            <div class="offer-item-name"><?php echo esc_html($offer_label); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($is_owner) : ?>
                        <!-- Owner View: Manage Offers -->
                        <?php if ($trade_status === 'open' && !empty($offers)) : ?>
                            <div class="offers-section">
                                <h3 class="section-title">📬 Gelen Teklifler</h3>
                                <div class="offers-list">
                                    <?php foreach ($offers as $offer) : ?>
                                        <div class="offer-card offer-status-<?php echo esc_attr($offer['status']); ?>">
                                            <div class="offer-header">
                                                <div class="offer-user">
                                                    <span class="offer-user-name"><?php echo esc_html($offer['offerer_name']); ?></span>
                                                    <span class="offer-date"><?php echo esc_html($offer['date']); ?></span>
                                                </div>
                                                <div class="offer-status-badge">
                                                    <?php if ($offer['status'] === 'accepted') : ?>
                                                        ✅ Kabul Edildi
                                                    <?php elseif ($offer['status'] === 'rejected') : ?>
                                                        ❌ Reddedildi
                                                    <?php else : ?>
                                                        ⏳ Bekliyor
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="offer-items-display">
                                                <div class="offer-gives">
                                                    <span class="offer-label">Verecek:</span>
                                                    <span class="offer-value"><?php echo esc_html($offer['wanted_qty']); ?>x <?php echo esc_html($wanted_label); ?></span>
                                                </div>
                                                <div class="offer-wants">
                                                    <span class="offer-label">İsteyecek:</span>
                                                    <div class="offer-wants-list">
                                                        <?php if (!empty($offer['offer_items'])) : 
                                                            foreach ($offer['offer_items'] as $item) : 
                                                                $item_label = hdh_get_item_label($item['slug']);
                                                        ?>
                                                            <span class="offer-want-item"><?php echo esc_html($item['qty']); ?>x <?php echo esc_html($item_label); ?></span>
                                                        <?php endforeach; endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php if ($offer['status'] === 'pending') : ?>
                                                <div class="offer-actions">
                                                    <button class="btn-accept-offer" data-offer-id="<?php echo esc_attr($offer['id']); ?>">
                                                        ✅ Kabul Et
                                                    </button>
                                                    <button class="btn-reject-offer" data-offer-id="<?php echo esc_attr($offer['id']); ?>">
                                                        ❌ Reddet
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php elseif ($trade_status === 'open') : ?>
                            <div class="no-offers-message">
                                <p>Henüz teklif gelmedi. Teklif geldiğinde burada görünecek.</p>
                            </div>
                        <?php endif; ?>
                        
                    <?php elseif (is_user_logged_in() && $trade_status === 'open') : ?>
                        <!-- Non-owner View: Make Offer -->
                        <div class="make-offer-section">
                            <h3 class="section-title">💬 Teklif Yap</h3>
                            <form id="make-offer-form" class="offer-form">
                                <input type="hidden" name="listing_id" value="<?php echo esc_attr($post_id); ?>">
                                
                                <div class="offer-form-group">
                                    <label class="offer-form-label">
                                        <span class="label-icon">🤍</span>
                                        Vereceğiniz: <?php echo esc_html($wanted_label); ?>
                                    </label>
                                    <div class="quantity-control">
                                        <button type="button" class="qty-btn qty-minus" data-target="offer_wanted_qty">−</button>
                                        <input type="number" 
                                               name="offer_wanted_qty" 
                                               id="offer_wanted_qty" 
                                               value="<?php echo esc_attr($trade_data['wanted_qty']); ?>" 
                                               min="1" 
                                               max="999" 
                                               class="qty-input">
                                        <button type="button" class="qty-btn qty-plus" data-target="offer_wanted_qty">+</button>
                                    </div>
                                    <small class="form-help">İlan sahibi <?php echo esc_html($trade_data['wanted_qty']); ?>x istiyor, siz farklı miktar teklif edebilirsiniz.</small>
                                </div>
                                
                                <div class="offer-form-group">
                                    <label class="offer-form-label">
                                        <span class="label-icon">🎁</span>
                                        Almak İstediğiniz Hediyeler
                                    </label>
                                    <div class="offer-items-selection">
                                        <?php foreach ($offer_items as $offer) : 
                                            $offer_slug = $offer['item'];
                                            $offer_image = hdh_get_item_image($offer_slug);
                                            $offer_label = hdh_get_item_label($offer_slug);
                                        ?>
                                            <div class="offer-item-select">
                                                <label class="offer-item-checkbox">
                                                    <input type="checkbox" 
                                                           name="offer_items[]" 
                                                           value="<?php echo esc_attr($offer_slug); ?>" 
                                                           class="offer-item-check"
                                                           data-default-qty="<?php echo esc_attr($offer['qty']); ?>">
                                                    <img src="<?php echo esc_url($offer_image); ?>" 
                                                         alt="<?php echo esc_attr($offer_label); ?>" 
                                                         class="offer-select-image">
                                                    <span class="offer-select-name"><?php echo esc_html($offer_label); ?></span>
                                                </label>
                                                <div class="offer-qty-control" style="display: none;">
                                                    <button type="button" class="qty-btn-small qty-minus-small" data-target="offer_qty_<?php echo esc_attr($offer_slug); ?>">−</button>
                                                    <input type="number" 
                                                           name="offer_qty[<?php echo esc_attr($offer_slug); ?>]" 
                                                           id="offer_qty_<?php echo esc_attr($offer_slug); ?>" 
                                                           value="<?php echo esc_attr($offer['qty']); ?>" 
                                                           min="1" 
                                                           max="999" 
                                                           class="qty-input-small">
                                                    <button type="button" class="qty-btn-small qty-plus-small" data-target="offer_qty_<?php echo esc_attr($offer_slug); ?>">+</button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <small class="form-help">En az 1 hediye seçmelisiniz. Miktarları değiştirebilirsiniz.</small>
                                </div>
                                
                                <button type="submit" class="btn-submit-offer">
                                    📤 Teklif Gönder
                                </button>
                            </form>
                        </div>
                        
                    <?php elseif (!is_user_logged_in()) : ?>
                        <div class="login-required-message">
                            <p>Teklif yapmak için giriş yapmalısınız.</p>
                            <a href="<?php echo esc_url(home_url('/profil')); ?>" class="btn-login">Giriş Yap</a>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Messaging Section (Only if offer accepted) -->
                    <?php if ($trade_status === 'accepted' && ($is_owner || $is_accepted_offerer)) : ?>
                        <div class="messaging-section">
                            <h3 class="section-title">💬 Mesajlaşma</h3>
                            <div class="messaging-info">
                                <p>✅ Hediyeleşme kabul edildi! Artık mesajlaşabilirsiniz.</p>
                                <?php if ($is_accepted_offerer && $author_farm_number) : ?>
                                    <p class="farm-reminder">İlan sahibinin çiftlik numarası: <strong><?php echo esc_html($author_farm_number); ?></strong></p>
                                <?php endif; ?>
                            </div>
                            
                            <div id="messages-container" class="messages-container">
                                <!-- Messages will be loaded here via AJAX -->
                                <div class="loading-messages">Mesajlar yükleniyor...</div>
                            </div>
                            
                            <form id="send-message-form" class="message-form">
                                <input type="hidden" name="listing_id" value="<?php echo esc_attr($post_id); ?>">
                                <input type="hidden" name="offer_id" value="<?php echo esc_attr($accepted_offer_id); ?>">
                                <div class="message-input-group">
                                    <textarea name="message" 
                                              id="message-input" 
                                              placeholder="Mesajınızı yazın..." 
                                              rows="3" 
                                              required
                                              class="message-textarea"></textarea>
                                    <button type="submit" class="btn-send-message">📤 Gönder</button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Complete Exchange Button -->
                        <?php 
                        $owner_confirmed = get_post_meta($post_id, '_hdh_owner_confirmed', true);
                        $offerer_confirmed = get_post_meta($post_id, '_hdh_offerer_confirmed', true);
                        ?>
                        <?php if ($trade_status !== 'completed') : ?>
                            <div class="complete-exchange-section">
                                <h3 class="section-title">✅ Hediyeleşmeyi Tamamla</h3>
                                <?php if ($is_owner && !$owner_confirmed) : ?>
                                    <button id="btn-confirm-exchange" class="btn-confirm-exchange">
                                        Hediyeleşmeyi Tamamladım
                                    </button>
                                    <p class="confirm-help">Hediyeleşme tamamlandıysa onaylayın. Her iki taraf da onayladığında ilan kapanacak.</p>
                                <?php elseif ($is_owner && $owner_confirmed) : ?>
                                    <p class="confirmed-message">✅ Siz onayladınız. Diğer tarafın onayı bekleniyor...</p>
                                <?php elseif ($is_accepted_offerer && !$offerer_confirmed) : ?>
                                    <button id="btn-confirm-exchange" class="btn-confirm-exchange">
                                        Hediyeleşmeyi Tamamladım
                                    </button>
                                    <p class="confirm-help">Hediyeleşme tamamlandıysa onaylayın. Her iki taraf da onayladığında ilan kapanacak.</p>
                                <?php elseif ($is_accepted_offerer && $offerer_confirmed) : ?>
                                    <p class="confirmed-message">✅ Siz onayladınız. Diğer tarafın onayı bekleniyor...</p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
        
        <?php
    endwhile;
endif;

get_footer();
?>
