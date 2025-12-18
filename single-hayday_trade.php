<?php
/**
 * Single Trade Listing Template - Redesigned
 * Modern, mobile-first, user-friendly design
 */

get_header();

if (!have_posts()) : 
    // Get alternative trades
    $alternatives = function_exists('hdh_get_alternative_trades') ? hdh_get_alternative_trades(0, 3) : array();
    ?>
    <main class="single-trade-main">
        <div class="container">
            <div class="trade-not-found-card">
                <div class="trade-not-found-icon">🔍</div>
                <h1 class="trade-not-found-title">İlan Bulunamadı</h1>
                <div class="trade-not-found-reasons">
                    <p class="trade-not-found-text">Bu ilan artık mevcut değil. Bunun nedeni:</p>
                    <ul class="trade-not-found-list">
                        <li>İlan sahibi tarafından kaldırılmış olabilir</li>
                        <li>Takas tamamlanmış ve ilan kapanmış olabilir</li>
                        <li>İlan süresi dolmuş olabilir</li>
                        <li>Yanlış bir bağlantı kullanmış olabilirsiniz</li>
                    </ul>
                </div>
                <div class="trade-not-found-actions">
                    <a href="<?php echo esc_url(home_url('/ara')); ?>" class="btn-primary-action">
                        <span class="btn-icon">🔍</span>
                        <span class="btn-text">Diğer İlanları İncele</span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/ilan-ver')); ?>" class="btn-secondary-action">
                        <span class="btn-icon">➕</span>
                        <span class="btn-text">Yeni İlan Oluştur</span>
                    </a>
                </div>
                
                <?php if (!empty($alternatives)) : ?>
                    <div class="alternative-trades-section">
                        <h3 class="alternative-trades-title">Benzer İlanlar</h3>
                        <div class="alternative-trades-grid">
                            <?php foreach ($alternatives as $alt_id) : ?>
                                <?php hdh_render_trade_card($alt_id); ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
<?php else :
    while (have_posts()) : the_post(); 
        $post_id = get_the_ID();
        $post_status = get_post_status($post_id);
        $author_id = get_post_field('post_author', $post_id);
        $current_user_id = get_current_user_id();
        $is_owner = ($current_user_id == $author_id);
        
        // Check if post is not published and user is not owner/admin
        if ($post_status !== 'publish' && !$is_owner && !current_user_can('administrator')) {
            $alternatives = function_exists('hdh_get_alternative_trades') ? hdh_get_alternative_trades($post_id, 3) : array();
            ?>
            <main class="single-trade-main">
                <div class="container">
                    <div class="trade-not-found-card">
                        <div class="trade-not-found-icon">🚫</div>
                        <h1 class="trade-not-found-title">İlan Pasif Durumda</h1>
                        <div class="trade-not-found-reasons">
                            <p class="trade-not-found-text">Bu ilan şu anda aktif değil.</p>
                        </div>
                        <div class="trade-not-found-actions">
                            <a href="<?php echo esc_url(home_url('/ara')); ?>" class="btn-primary-action">
                                <span class="btn-icon">🔍</span>
                                <span class="btn-text">Aktif İlanları İncele</span>
                            </a>
                        </div>
                        
                        <?php if (!empty($alternatives)) : ?>
                            <div class="alternative-trades-section">
                                <h3 class="alternative-trades-title">Benzer Aktif İlanlar</h3>
                                <div class="alternative-trades-grid">
                                    <?php foreach ($alternatives as $alt_id) : ?>
                                        <?php hdh_render_trade_card($alt_id); ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
            <?php
            get_footer();
            exit;
        }
        
        $trade_data = hdh_get_trade_data();
        
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
        
        // Get user level
        $user_level = 1;
        if (function_exists('hdh_get_user_state')) {
            $user_state = hdh_get_user_state($author_id);
            $user_level = $user_state['level'] ?? 1;
        }
        
        // Filter offer items
        $offer_items = array_filter($trade_data['offer_items'], function($item) {
            return !empty($item['item']) && !empty($item['qty']);
        });
        
        // Get wanted item info
        $wanted_slug = $trade_data['wanted_item'];
        $wanted_image = hdh_get_item_image($wanted_slug);
        $wanted_label = hdh_get_item_label($wanted_slug);
        
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
        
        <main class="single-trade-main">
            <div class="container">
                <!-- Back Button -->
                <a href="<?php echo esc_url(home_url('/ara')); ?>" class="btn-back-single">
                    <span class="btn-back-icon">←</span>
                    <span class="btn-back-text">İlanlara Dön</span>
                </a>
                
                <!-- Main Trade Card -->
                <article class="single-trade-card">
                    <!-- Header: Level, Name, Time, Status -->
                    <header class="trade-header">
                        <div class="trade-header-top">
                            <div class="trade-author-meta">
                                <div class="hdh-level-badge lvl-d<?php echo strlen((string)$user_level); ?>" 
                                     aria-label="Seviye <?php echo esc_attr($user_level); ?>">
                                    <?php echo esc_html($user_level); ?>
                                </div>
                                <div class="trade-author-info">
                                    <div class="trade-author-name"><?php echo esc_html($author_name); ?></div>
                                    <div class="trade-author-stats">
                                        <span class="trade-completed-count"><?php echo esc_html($completed_count); ?></span>
                                        <span class="trade-stats-label">başarılı hediyeleşme</span>
                                    </div>
                                </div>
                            </div>
                            <div class="trade-time"><?php echo esc_html($relative_time); ?></div>
                        </div>
                        
                        <div class="trade-title-row">
                            <h1 class="trade-title"><?php the_title(); ?></h1>
                            <div class="trade-status-badge status-<?php echo esc_attr($trade_status); ?>">
                                <?php if ($trade_status === 'completed') : ?>
                                    ✅ <?php echo esc_html(hdh_get_content('trade_single', 'completed_status_text', 'Tamamlandı')); ?>
                                <?php elseif ($trade_status === 'accepted') : ?>
                                    🤝 <?php echo esc_html(hdh_get_content('trade_single', 'accepted_status_text', 'Kabul Edildi')); ?>
                                <?php else : ?>
                                    🟢 <?php echo esc_html(hdh_get_content('trade_single', 'open_status_text', 'Açık')); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </header>
                    
                    <!-- Farm Number (if accepted) -->
                    <?php if (($is_owner || $is_accepted_offerer) && $trade_status === 'accepted' && $author_farm_number) : ?>
                        <div class="trade-farm-number">
                            <span class="farm-number-icon">🏡</span>
                            <span class="farm-number-label"><?php echo esc_html(hdh_get_content('trade_single', 'farm_number_label', 'Çiftlik No:')); ?></span>
                            <span class="farm-number-value"><?php echo esc_html($author_farm_number); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Trade Items: Split Layout -->
                    <div class="trade-items-section">
                        <!-- Left: Hediye Ediyor -->
                        <div class="trade-items-column trade-items-offering">
                            <div class="trade-items-header">
                                <span class="trade-items-icon">🎁</span>
                                <span class="trade-items-title"><?php echo esc_html(hdh_get_content('trade_single', 'offering_label', 'Hediye')); ?></span>
                            </div>
                            <div class="trade-items-list">
                                <?php if (!empty($offer_items)) : ?>
                                    <?php foreach ($offer_items as $offer) : 
                                        $offer_slug = $offer['item'];
                                        $offer_image = hdh_get_item_image($offer_slug);
                                        $offer_label = hdh_get_item_label($offer_slug);
                                    ?>
                                        <div class="trade-item-card">
                                            <?php if ($offer_image) : ?>
                                                <img src="<?php echo esc_url($offer_image); ?>" 
                                                     alt="<?php echo esc_attr($offer_label); ?>" 
                                                     class="trade-item-img"
                                                     loading="lazy"
                                                     decoding="async">
                                            <?php endif; ?>
                                            <div class="trade-item-details">
                                                <div class="trade-item-qty"><?php echo esc_html($offer['qty']); ?>x</div>
                                                <div class="trade-item-name"><?php echo esc_html($offer_label); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <div class="trade-item-empty">—</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Divider -->
                        <div class="trade-items-divider"></div>
                        
                        <!-- Right: İstek -->
                        <div class="trade-items-column trade-items-wanted">
                            <div class="trade-items-header">
                                <span class="trade-items-icon">🤍</span>
                                <span class="trade-items-title"><?php echo esc_html(hdh_get_content('trade_single', 'wanted_label', 'İstek')); ?></span>
                            </div>
                            <div class="trade-items-list">
                                <div class="trade-item-card trade-item-wanted">
                                    <?php if ($wanted_image) : ?>
                                        <img src="<?php echo esc_url($wanted_image); ?>" 
                                             alt="<?php echo esc_attr($wanted_label); ?>" 
                                             class="trade-item-img"
                                             loading="lazy"
                                             decoding="async">
                                    <?php endif; ?>
                                    <div class="trade-item-details">
                                        <div class="trade-item-qty"><?php echo esc_html($trade_data['wanted_qty']); ?>x</div>
                                        <div class="trade-item-name"><?php echo esc_html($wanted_label); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Owner View: Manage Offers -->
                    <?php if ($is_owner) : ?>
                        <?php if ($trade_status === 'open' && !empty($offers)) : ?>
                            <section class="trade-section offers-section">
                                <h2 class="section-title">
                                    <span class="section-icon">📬</span>
                                    <span class="section-text">Gelen Teklifler</span>
                                </h2>
                                <div class="offers-list">
                                    <?php foreach ($offers as $offer) : ?>
                                        <div class="offer-card offer-status-<?php echo esc_attr($offer['status']); ?>">
                                            <div class="offer-card-header">
                                                <div class="offer-user-info">
                                                    <div class="offer-user-name"><?php echo esc_html($offer['offerer_name']); ?></div>
                                                    <div class="offer-date"><?php echo esc_html($offer['date']); ?></div>
                                                </div>
                                                <div class="offer-status-badge offer-status-<?php echo esc_attr($offer['status']); ?>">
                                                    <?php if ($offer['status'] === 'accepted') : ?>
                                                        ✅ Kabul Edildi
                                                    <?php elseif ($offer['status'] === 'rejected') : ?>
                                                        ❌ Reddedildi
                                                    <?php else : ?>
                                                        ⏳ Bekliyor
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="offer-details">
                                                <div class="offer-detail-row">
                                                    <span class="offer-detail-label">Verecek:</span>
                                                    <span class="offer-detail-value"><?php echo esc_html($offer['wanted_qty']); ?>x <?php echo esc_html($wanted_label); ?></span>
                                                </div>
                                                <div class="offer-detail-row">
                                                    <span class="offer-detail-label">İsteyecek:</span>
                                                    <div class="offer-wants-items">
                                                        <?php if (!empty($offer['offer_items'])) : 
                                                            foreach ($offer['offer_items'] as $item) : 
                                                                $item_label = hdh_get_item_label($item['slug']);
                                                        ?>
                                                            <span class="offer-want-badge"><?php echo esc_html($item['qty']); ?>x <?php echo esc_html($item_label); ?></span>
                                                        <?php endforeach; endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <?php if ($offer['status'] === 'pending') : ?>
                                                <div class="offer-actions">
                                                    <button class="btn-action btn-accept" data-offer-id="<?php echo esc_attr($offer['id']); ?>">
                                                        ✅ <?php echo esc_html(hdh_get_content('trade_single', 'accept_button_text', 'Kabul Et')); ?>
                                                    </button>
                                                    <button class="btn-action btn-reject" data-offer-id="<?php echo esc_attr($offer['id']); ?>">
                                                        ❌ <?php echo esc_html(hdh_get_content('trade_single', 'reject_button_text', 'Reddet')); ?>
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php elseif ($trade_status === 'open') : ?>
                            <div class="trade-empty-state">
                                <span class="empty-state-icon">📭</span>
                                <p class="empty-state-text">Henüz teklif gelmedi. Teklif geldiğinde burada görünecek.</p>
                            </div>
                        <?php endif; ?>
                    
                    <!-- Non-owner View: Make Offer -->
                    <?php elseif (is_user_logged_in() && $trade_status === 'open') : ?>
                        <section class="trade-section make-offer-section">
                            <h2 class="section-title">
                                <span class="section-icon">💬</span>
                                <span class="section-text">Teklif Yap</span>
                            </h2>
                            <form id="make-offer-form" class="offer-form">
                                <input type="hidden" name="listing_id" value="<?php echo esc_attr($post_id); ?>">
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        <span class="label-icon">🤍</span>
                                        <span class="label-text">Vereceğiniz: <?php echo esc_html($wanted_label); ?></span>
                                    </label>
                                    <div class="quantity-stepper">
                                        <button type="button" class="qty-btn qty-minus" data-target="offer_wanted_qty">−</button>
                                        <input type="number" 
                                               name="offer_wanted_qty" 
                                               id="offer_wanted_qty" 
                                               value="<?php echo esc_attr($trade_data['wanted_qty']); ?>" 
                                               min="1" 
                                               max="999" 
                                               class="qty-input"
                                               readonly>
                                        <button type="button" class="qty-btn qty-plus" data-target="offer_wanted_qty">+</button>
                                    </div>
                                    <small class="form-hint">İlan sahibi <?php echo esc_html($trade_data['wanted_qty']); ?>x istiyor, siz farklı miktar teklif edebilirsiniz.</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        <span class="label-icon">🎁</span>
                                        <span class="label-text">Almak İstediğiniz Hediyeler</span>
                                    </label>
                                    <div class="offer-items-grid">
                                        <?php foreach ($offer_items as $offer) : 
                                            $offer_slug = $offer['item'];
                                            $offer_image = hdh_get_item_image($offer_slug);
                                            $offer_label = hdh_get_item_label($offer_slug);
                                        ?>
                                            <div class="offer-item-option">
                                                <label class="offer-item-checkbox-label">
                                                    <input type="checkbox" 
                                                           name="offer_items[]" 
                                                           value="<?php echo esc_attr($offer_slug); ?>" 
                                                           class="offer-item-checkbox-input"
                                                           data-default-qty="<?php echo esc_attr($offer['qty']); ?>">
                                                    <div class="offer-item-checkbox-content">
                                                        <?php if ($offer_image) : ?>
                                                            <img src="<?php echo esc_url($offer_image); ?>" 
                                                                 alt="<?php echo esc_attr($offer_label); ?>" 
                                                                 class="offer-item-checkbox-image"
                                                                 loading="lazy">
                                                        <?php endif; ?>
                                                        <span class="offer-item-checkbox-name"><?php echo esc_html($offer_label); ?></span>
                                                    </div>
                                                </label>
                                                <div class="offer-item-qty-control" style="display: none;">
                                                    <button type="button" class="qty-btn-small qty-minus-small" data-target="offer_qty_<?php echo esc_attr($offer_slug); ?>">−</button>
                                                    <input type="number" 
                                                           name="offer_qty[<?php echo esc_attr($offer_slug); ?>]" 
                                                           id="offer_qty_<?php echo esc_attr($offer_slug); ?>" 
                                                           value="<?php echo esc_attr($offer['qty']); ?>" 
                                                           min="1" 
                                                           max="999" 
                                                           class="qty-input-small"
                                                           readonly>
                                                    <button type="button" class="qty-btn-small qty-plus-small" data-target="offer_qty_<?php echo esc_attr($offer_slug); ?>">+</button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <small class="form-hint">En az 1 hediye seçmelisiniz. Miktarları değiştirebilirsiniz.</small>
                                </div>
                                
                                <button type="submit" class="btn-primary btn-submit-offer">
                                    <span class="btn-icon">📤</span>
                                    <span class="btn-text"><?php echo esc_html(hdh_get_content('trade_single', 'offer_submit_button_text', 'Teklif Gönder')); ?></span>
                                </button>
                            </form>
                        </section>
                    
                    <!-- Login Required -->
                    <?php elseif (!is_user_logged_in()) : 
                        $login_url = function_exists('hdh_get_login_url_with_return') 
                            ? hdh_get_login_url_with_return(get_permalink($post_id))
                            : home_url('/profil');
                    ?>
                        <section class="trade-section login-required-section">
                            <div class="login-required-content">
                                <span class="login-icon">🔐</span>
                                <h3 class="login-title">Teklif Yapmak İçin Giriş Yapın</h3>
                                <p class="login-description">Giriş yaptıktan sonra bu ilana geri döneceksiniz.</p>
                                <a href="<?php echo esc_url($login_url); ?>" class="btn-primary btn-login">
                                    <span class="btn-icon">🔐</span>
                                    <span class="btn-text"><?php echo esc_html(hdh_get_content('trade_single', 'login_button_text', 'Giriş Yap')); ?></span>
                                </a>
                            </div>
                        </section>
                    <?php endif; ?>
                    
                    <!-- Messaging Section (Only if offer accepted) -->
                    <?php if ($trade_status === 'accepted' && ($is_owner || $is_accepted_offerer)) : ?>
                        <section class="trade-section messaging-section">
                            <h2 class="section-title">
                                <span class="section-icon">💬</span>
                                <span class="section-text">Mesajlaşma</span>
                            </h2>
                            <div class="messaging-info-box">
                                <p class="messaging-info-text">✅ Hediyeleşme kabul edildi! Artık mesajlaşabilirsiniz.</p>
                                <?php if ($is_accepted_offerer && $author_farm_number) : ?>
                                    <p class="farm-number-info">
                                        <span class="farm-info-label">İlan sahibinin çiftlik numarası:</span>
                                        <strong class="farm-info-value"><?php echo esc_html($author_farm_number); ?></strong>
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                            <div id="messages-container" class="messages-container">
                                <div class="loading-messages">Mesajlar yükleniyor...</div>
                            </div>
                            
                            <form id="send-message-form" class="message-form">
                                <input type="hidden" name="listing_id" value="<?php echo esc_attr($post_id); ?>">
                                <input type="hidden" name="offer_id" value="<?php echo esc_attr($accepted_offer_id); ?>">
                                <div class="message-input-wrapper">
                                    <textarea name="message" 
                                              id="message-input" 
                                              placeholder="Mesajınızı yazın..." 
                                              rows="3" 
                                              required
                                              class="message-textarea"></textarea>
                                    <button type="submit" class="btn-send-message">
                                        <span class="btn-send-icon">📤</span>
                                    </button>
                                </div>
                            </form>
                        </section>
                        
                        <!-- Complete Exchange Section -->
                        <?php
                        $owner_confirmed = get_post_meta($post_id, '_hdh_owner_confirmed', true);
                        $offerer_confirmed = get_post_meta($post_id, '_hdh_offerer_confirmed', true);
                        ?>
                        <?php if ($trade_status !== 'completed') : ?>
                            <section class="trade-section complete-exchange-section">
                                <h2 class="section-title">
                                    <span class="section-icon">✅</span>
                                    <span class="section-text">Hediyeleşmeyi Tamamla</span>
                                </h2>
                                <?php if ($is_owner && !$owner_confirmed) : ?>
                                    <button id="btn-confirm-exchange" class="btn-primary btn-confirm-exchange">
                                        <span class="btn-icon">✅</span>
                                        <span class="btn-text">Hediyeleşmeyi Tamamladım</span>
                                    </button>
                                    <p class="confirm-hint">Her iki taraf da onayladığında ilan kapanacak.</p>
                                <?php elseif ($is_owner && $owner_confirmed) : ?>
                                    <div class="confirm-status-box">
                                        <span class="confirm-status-icon">✅</span>
                                        <p class="confirm-status-text">Siz onayladınız. Diğer tarafın onayı bekleniyor...</p>
                                    </div>
                                <?php elseif ($is_accepted_offerer && !$offerer_confirmed) : ?>
                                    <button id="btn-confirm-exchange" class="btn-primary btn-confirm-exchange">
                                        <span class="btn-icon">✅</span>
                                        <span class="btn-text">Hediyeleşmeyi Tamamladım</span>
                                    </button>
                                    <p class="confirm-hint">Her iki taraf da onayladığında ilan kapanacak.</p>
                                <?php elseif ($is_accepted_offerer && $offerer_confirmed) : ?>
                                    <div class="confirm-status-box">
                                        <span class="confirm-status-icon">✅</span>
                                        <p class="confirm-status-text">Siz onayladınız. Diğer tarafın onayı bekleniyor...</p>
                                    </div>
                                <?php endif; ?>
                            </section>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <!-- Share Section -->
                    <?php if (function_exists('hdh_render_share_buttons')) : ?>
                        <div class="share-section-wrapper">
                            <?php echo hdh_render_share_buttons(get_the_ID(), 'single-trade'); ?>
                        </div>
                    <?php endif; ?>
                </article>
            </div>
        </main>
        
        <?php 
            endwhile; 
        endif; 

get_footer();
?>
