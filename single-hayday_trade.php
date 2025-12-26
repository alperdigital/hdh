<?php
/**
 * Single Trade Listing Template - Redirected
 * This page redirects to listing search page
 */

// 301 redirect to listing search page
wp_redirect(home_url('/ara'), 301);
exit;

if (!have_posts()) : 
    $alternatives = function_exists('hdh_get_alternative_trades') ? hdh_get_alternative_trades(0, 3) : array();
    ?>
    <main class="single-trade-main">
        <div class="container">
            <div class="trade-not-found-card">
                <div class="trade-not-found-icon">🔍</div>
                <h1 class="trade-not-found-title">İlan Bulunamadı</h1>
                <div class="trade-not-found-reasons">
                    <p class="trade-not-found-text">Bu ilan artık mevcut değil.</p>
                </div>
                <div class="trade-not-found-actions">
                    <a href="<?php echo esc_url(home_url('/ara')); ?>" class="btn-primary-action">
                        <span class="btn-icon">🔍</span>
                        <span class="btn-text">Diğer İlanları İncele</span>
                    </a>
                </div>
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
        
        if ($post_status !== 'publish' && !$is_owner && !current_user_can('administrator')) {
            ?>
            <main class="single-trade-main">
                <div class="container">
                    <div class="trade-not-found-card">
                        <div class="trade-not-found-icon">🚫</div>
                        <h1 class="trade-not-found-title">İlan Pasif Durumda</h1>
                        <div class="trade-not-found-actions">
                            <a href="<?php echo esc_url(home_url('/ara')); ?>" class="btn-primary-action">
                                <span class="btn-icon">🔍</span>
                                <span class="btn-text">Aktif İlanları İncele</span>
                            </a>
                        </div>
                    </div>
                </div>
            </main>
            <?php
            get_footer();
            exit;
        }
        
        $trade_data = hdh_get_trade_data();
        $trade_status = get_post_meta($post_id, '_hdh_trade_status', true) ?: 'open';
        
        // Get author info
        $author_name = get_the_author_meta('display_name', $author_id);
        // Get farm tag (Çiftlik Etiketi) from registration, fallback to hayday_farm_number
        $author_farm_number = get_user_meta($author_id, 'farm_tag', true);
        if (empty($author_farm_number)) {
            $author_farm_number = get_user_meta($author_id, 'hayday_farm_number', true);
        }
        $completed_count = function_exists('hdh_get_completed_gift_count') ? hdh_get_completed_gift_count($author_id) : 0;
        
        // Get current user farm number (for starter)
        $current_user_farm_number = '';
        if ($current_user_id) {
            $current_user_farm_number = get_user_meta($current_user_id, 'farm_tag', true);
            if (empty($current_user_farm_number)) {
                $current_user_farm_number = get_user_meta($current_user_id, 'hayday_farm_number', true);
            }
        }
        
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
        
        // Get first offer item for summary
        $first_offer = !empty($offer_items) ? $offer_items[0] : null;
        $offer_label = $first_offer ? hdh_get_item_label($first_offer['item']) : '';
        $offer_qty = $first_offer ? $first_offer['qty'] : 0;
        
        // Get trade session if exists
        $session = null;
        if ($current_user_id && function_exists('hdh_get_trade_session')) {
            $session = hdh_get_trade_session(null, $post_id, $current_user_id);
        }
        
        // Get trade request if exists (for non-owner users)
        $trade_request = null;
        if ($current_user_id && !$is_owner && function_exists('hdh_get_trade_request_for_listing')) {
            $trade_request = hdh_get_trade_request_for_listing($post_id, $current_user_id);
        }
        
        // Get pending requests for owner
        $pending_requests = array();
        if ($is_owner && function_exists('hdh_get_pending_requests_for_owner')) {
            $pending_requests = hdh_get_pending_requests_for_owner($author_id);
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
                    <!-- Trade Description (Text Only) with Meta Info -->
                    <div class="trade-description-simplified">
                        <p class="trade-description-text">
                            <strong><?php echo esc_html($author_name); ?></strong> senden 
                            <strong><?php echo esc_html($trade_data['wanted_qty']); ?> <?php echo esc_html($wanted_label); ?></strong> istiyor. 
                            Sana 
                            <?php 
                            $offer_labels = array();
                            foreach ($offer_items as $offer) {
                                $offer_label = hdh_get_item_label($offer['item']);
                                $offer_labels[] = $offer['qty'] . ' ' . $offer_label;
                            }
                            echo esc_html(implode(', ', $offer_labels));
                            ?> 
                            verecek.
                        </p>
                        <!-- Meta Info Row: Level, Farm Name, Time, Completed Count -->
                        <div class="trade-description-meta">
                            <div class="hdh-level-badge lvl-d<?php echo strlen((string)$user_level); ?>" 
                                 aria-label="Seviye <?php echo esc_attr($user_level); ?>">
                                <?php echo esc_html($user_level); ?>
                            </div>
                            <span class="trade-meta-farm-name"><?php echo esc_html($author_name); ?></span>
                            <span class="trade-meta-time"><?php echo esc_html($relative_time); ?></span>
                            <?php if ($completed_count > 0) : ?>
                                <span class="trade-meta-completed">
                                    <span class="completed-icon">✅</span>
                                    <span class="completed-count"><?php echo esc_html($completed_count); ?></span>
                                    <span class="completed-label">hediyeleşme</span>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Trade Request Status or Roadmap -->
                    <?php if ($session) : ?>
                        <!-- Roadmap Section -->
                        <?php hdh_render_trade_roadmap($session, $post_id, $current_user_id); ?>
                    <?php elseif ($is_owner && !empty($pending_requests)) : ?>
                        <!-- Owner: Pending Requests Section -->
                        <div class="trade-pending-requests-section">
                            <h3 class="pending-requests-title">Bekleyen Teklifler</h3>
                            <div class="pending-requests-list">
                                <?php foreach ($pending_requests as $request) : 
                                    $requester_id = $request['requester_user_id'];
                                    $requester_name = get_user_meta($requester_id, 'display_name', true) ?: get_userdata($requester_id)->display_name;
                                    $expires_timestamp = strtotime($request['expires_at']);
                                    $current_timestamp = current_time('timestamp');
                                    $time_remaining = max(0, $expires_timestamp - $current_timestamp);
                                ?>
                                    <div class="pending-request-item" data-request-id="<?php echo esc_attr($request['id']); ?>">
                                        <div class="request-info">
                                            <span class="request-requester"><?php echo esc_html($requester_name); ?></span>
                                            <span class="request-time-remaining" data-expires-at="<?php echo esc_attr($request['expires_at']); ?>">
                                                <?php echo esc_html(sprintf('%d saniye kaldı', $time_remaining)); ?>
                                            </span>
                                        </div>
                                        <div class="request-actions">
                                            <button type="button" 
                                                    class="btn-accept-request" 
                                                    data-request-id="<?php echo esc_attr($request['id']); ?>">
                                                ✅ Kabul Et
                                            </button>
                                            <button type="button" 
                                                    class="btn-reject-request" 
                                                    data-request-id="<?php echo esc_attr($request['id']); ?>">
                                                ❌ Reddet
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php elseif (is_user_logged_in() && !$is_owner && $trade_status === 'open') : ?>
                        <!-- Non-Owner: Send Trade Request Section -->
                        <div class="trade-request-section">
                            <?php if ($trade_request) : 
                                $request_status = $trade_request['status'];
                                $expires_timestamp = strtotime($trade_request['expires_at']);
                                $current_timestamp = current_time('timestamp');
                                $time_remaining = max(0, $expires_timestamp - $current_timestamp);
                            ?>
                                <!-- Request Status -->
                                <div class="trade-request-status" data-request-id="<?php echo esc_attr($trade_request['id']); ?>">
                                    <?php if ($request_status === 'pending') : ?>
                                        <div class="request-status-pending">
                                            <span class="status-icon">⏳</span>
                                            <span class="status-text">Teklif Gönderildi - Bekleniyor...</span>
                                            <div class="request-countdown" data-expires-at="<?php echo esc_attr($trade_request['expires_at']); ?>">
                                                <span class="countdown-text"><?php echo esc_html(sprintf('%d saniye kaldı', $time_remaining)); ?></span>
                                            </div>
                                        </div>
                                    <?php elseif ($request_status === 'accepted') : ?>
                                        <div class="request-status-accepted">
                                            <span class="status-icon">✅</span>
                                            <span class="status-text">Teklif Kabul Edildi</span>
                                            <p class="status-message">İlan sahibi teklifinizi kabul etti. Hediyeleşme başlatılıyor...</p>
                                        </div>
                                    <?php elseif ($request_status === 'rejected') : ?>
                                        <div class="request-status-rejected">
                                            <span class="status-icon">❌</span>
                                            <span class="status-text">Teklif Reddedildi</span>
                                            <button type="button" class="btn-send-new-request" data-listing-id="<?php echo esc_attr($post_id); ?>">
                                                Yeni Teklif Gönder
                                            </button>
                                        </div>
                                    <?php elseif ($request_status === 'expired') : ?>
                                        <div class="request-status-expired">
                                            <span class="status-icon">⏰</span>
                                            <span class="status-text">Teklif Süresi Doldu</span>
                                            <button type="button" class="btn-send-new-request" data-listing-id="<?php echo esc_attr($post_id); ?>">
                                                Yeni Teklif Gönder
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else : ?>
                                <!-- Send Request Button -->
                                <div class="trade-send-request-section">
                                    <button type="button" 
                                            id="btn-send-trade-request" 
                                            class="btn-primary btn-send-trade-request"
                                            data-listing-id="<?php echo esc_attr($post_id); ?>">
                                        <span class="btn-icon">📨</span>
                                        <span class="btn-text">Teklif Gönder</span>
                                    </button>
                                    <p class="request-help-text">İlan sahibi teklifinizi 120 saniye içinde kabul etmeli.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php elseif (!is_user_logged_in()) : 
                        $login_url = function_exists('hdh_get_login_url_with_return') 
                            ? hdh_get_login_url_with_return(get_permalink($post_id))
                            : home_url('/profil');
                    ?>
                        <!-- Login Required -->
                        <section class="trade-section login-required-section">
                            <div class="login-required-content">
                                <span class="login-icon">🔐</span>
                                <h3 class="login-title">Hediyeleşmeyi Başlatmak İçin Giriş Yapın</h3>
                                <p class="login-description">Giriş yaptıktan sonra bu ilana geri döneceksiniz.</p>
                                <a href="<?php echo esc_url($login_url); ?>" class="btn-primary btn-login">
                                    <span class="btn-icon">🔐</span>
                                    <span class="btn-text">Giriş Yap</span>
                                </a>
                            </div>
                        </section>
                    <?php elseif ($is_owner) : ?>
                        <div class="trade-owner-message">
                            <p>Bu ilanın sahibisiniz. Başka bir kullanıcı hediyeleşmeyi başlattığında burada yol haritası görünecek.</p>
                        </div>
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
