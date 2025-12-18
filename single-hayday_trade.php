<?php
/**
 * Single Trade Listing Template - Roadmap/Stepper Version
 * Step-by-step gift exchange with roadmap
 */

get_header();

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
        
        // Get first offer item for summary
        $first_offer = !empty($offer_items) ? $offer_items[0] : null;
        $offer_label = $first_offer ? hdh_get_item_label($first_offer['item']) : '';
        $offer_qty = $first_offer ? $first_offer['qty'] : 0;
        
        // Get trade session if exists
        $session = null;
        if ($current_user_id && function_exists('hdh_get_trade_session')) {
            $session = hdh_get_trade_session(null, $post_id, $current_user_id);
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
                    <!-- Header: Level, Name, Time -->
                    <header class="trade-header">
                        <div class="trade-header-top">
                            <div class="trade-author-meta">
                                <div class="hdh-level-badge lvl-d<?php echo strlen((string)$user_level); ?>" 
                                     aria-label="Seviye <?php echo esc_attr($user_level); ?>">
                                    <?php echo esc_html($user_level); ?>
                                </div>
                                <div class="trade-author-info">
                                    <div class="trade-author-name"><?php echo esc_html($author_name); ?></div>
                                    <?php if ($completed_count > 0) : ?>
                                        <div class="trade-author-stats">
                                            <span class="trade-completed-count"><?php echo esc_html($completed_count); ?></span>
                                            <span class="trade-stats-label">başarılı hediyeleşme</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="trade-time"><?php echo esc_html($relative_time); ?></div>
                        </div>
                    </header>
                    
                    <!-- Trade Items: Split Layout -->
                    <div class="trade-items-section">
                        <!-- Left: Hediye Ediyor -->
                        <div class="trade-items-column trade-items-offering">
                            <div class="trade-items-header">
                                <span class="trade-items-icon">🎁</span>
                                <span class="trade-items-title">Hediye Ediyor</span>
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
                                <span class="trade-items-title">Hediye İstiyor</span>
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
                    
                    <!-- Simple Summary -->
                    <div class="trade-summary">
                        <p class="summary-text">
                            <strong><?php echo esc_html($author_name); ?></strong>, senden 
                            <strong><?php echo esc_html($wanted_label); ?> ×<?php echo esc_html($trade_data['wanted_qty']); ?></strong> istiyor. 
                            Karşılığında sana 
                            <?php 
                            $offer_labels = array();
                            foreach ($offer_items as $offer) {
                                $offer_labels[] = hdh_get_item_label($offer['item']) . ' ×' . $offer['qty'];
                            }
                            echo esc_html(implode(', ', $offer_labels));
                            ?> 
                            hediye edecek.
                        </p>
                    </div>
                    
                    <!-- Start Trade Button or Roadmap -->
                    <?php if ($session) : ?>
                        <!-- Roadmap Section -->
                        <?php hdh_render_trade_roadmap($session, $post_id, $current_user_id); ?>
                    <?php elseif (is_user_logged_in() && !$is_owner && $trade_status === 'open') : ?>
                        <!-- Start Trade Button -->
                        <div class="trade-start-section">
                            <button type="button" 
                                    id="btn-start-trade" 
                                    class="btn-primary btn-start-trade"
                                    data-listing-id="<?php echo esc_attr($post_id); ?>">
                                <span class="btn-icon">🚀</span>
                                <span class="btn-text">Hediyeleşmeyi Başlat</span>
                            </button>
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
