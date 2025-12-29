<?php
/**
 * Template Name: Hediye Ara
 * Page Template for Gift Search/Listing Feed
 * 
 * This page displays:
 * - Visual filter grid (9 items)
 * - Trade listing feed with sticky header
 * - AJAX filtering functionality
 */

get_header();
?>

<!-- HDH: Trade Offer Feed Section -->
<main class="trade-feed-main" id="trade-feed">
    <div class="container">
        <h2 class="section-title-cartoon"><?php echo esc_html(hdh_get_content('trade_search', 'page_title', 'Hediyeni Seç')); ?></h2>
        
        <!-- HDH: Visual Filter Grid (9 Items) -->
        <div class="trade-filter-visual-grid">
            <div class="filter-items-grid" id="filter-items-grid">
                <?php 
                $items = hdh_get_items_config();
                $filter_items = array_slice($items, 0, 9, true); // Get first 9 items
                foreach ($filter_items as $slug => $item) : 
                    $item_image = hdh_get_item_image($slug);
                    $item_label = hdh_get_item_label($slug);
                ?>
                    <button type="button" 
                            class="filter-item-btn" 
                            data-item-slug="<?php echo esc_attr($slug); ?>"
                            aria-label="Filtrele: <?php echo esc_attr($item_label); ?>">
                        <img src="<?php echo esc_url($item_image); ?>" 
                             alt="<?php echo esc_attr($item_label); ?>" 
                             class="filter-item-image">
                        <span class="filter-item-label"><?php echo esc_html($item_label); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn-clear-filter-visual" id="btn-clear-filter-visual" style="display: none;">
                <span class="btn-icon">🔄</span>
                Filtreyi Temizle
            </button>
        </div>
        
        <!-- HDH: Trade Offers Feed -->
        <div class="trade-feed-container" id="trade-feed-container">
            <!-- Skeleton Loading State -->
            <div class="trade-skeleton-container" id="trade-skeleton" style="display: none;">
                <div class="trade-cards-grid">
                    <?php for ($i = 0; $i < 6; $i++) : ?>
                        <div class="trade-card-skeleton">
                            <div class="skeleton-header">
                                <div class="skeleton-avatar"></div>
                                <div class="skeleton-text skeleton-text-short"></div>
                            </div>
                            <div class="skeleton-body">
                                <div class="skeleton-items">
                                    <div class="skeleton-item"></div>
                                    <div class="skeleton-item"></div>
                                </div>
                                <div class="skeleton-divider"></div>
                                <div class="skeleton-items">
                                    <div class="skeleton-item"></div>
                                </div>
                            </div>
                            <div class="skeleton-footer">
                                <div class="skeleton-text skeleton-text-long"></div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
            
            <!-- Error State -->
            <div class="trade-error-container" id="trade-error" style="display: none;">
                <div class="trade-error-card">
                    <div class="trade-error-icon" id="error-icon">⚠️</div>
                    <h3 class="trade-error-title" id="error-title">Bir Sorun Oluştu</h3>
                    <p class="trade-error-message" id="error-message">İlanlar yüklenirken bir hata oluştu.</p>
                    <div class="trade-error-actions">
                        <button class="btn-retry" id="btn-retry">
                            <span class="btn-icon">🔄</span>
                            <span class="btn-text">Tekrar Dene</span>
                        </button>
                        <button class="btn-reload" id="btn-reload">
                            <span class="btn-icon">↻</span>
                            <span class="btn-text">Sayfayı Yenile</span>
                        </button>
                    </div>
                    <p class="trade-error-help">Sorun devam ediyorsa, internet bağlantınızı kontrol edin.</p>
                </div>
            </div>
            
            <!-- Trade Cards Grid -->
            <div class="trade-cards-grid" id="trade-cards-grid">
                <?php
                // Build query - default: show only open trades
                $args = array(
                    'post_type' => 'hayday_trade',
                    'posts_per_page' => 20,
                    'post_status' => 'publish',
                    'meta_query' => array(
                        array(
                            'key' => '_hdh_trade_status',
                            'value' => 'open',
                            'compare' => '='
                        )
                    ),
                );
                
                // Exclude blocked users (server-side filtering)
                $current_user_id = get_current_user_id();
                if ($current_user_id && function_exists('hdh_get_blocked_users')) {
                    $blocked_users = hdh_get_blocked_users($current_user_id);
                    if (!empty($blocked_users)) {
                        $args['author__not_in'] = $blocked_users;
                    }
                }
                
                // Get user sorting preference (default: newest for newest listings first)
                $sort_by = 'newest';
                if (is_user_logged_in()) {
                    $user_sort_pref = get_user_meta($current_user_id, 'hdh_listing_sort_preference', true);
                    if ($user_sort_pref === 'presence') {
                        $sort_by = 'presence';
                    }
                }
                
                // Use presence-based sorting if available
                if (function_exists('hdh_get_listings_with_presence')) {
                    $trade_query = hdh_get_listings_with_presence($args, $sort_by);
                } else {
                    // Fallback to standard query
                    $args['orderby'] = 'date';
                    $args['order'] = 'DESC';
                    $trade_query = new WP_Query($args);
                }
                
                if ($trade_query->have_posts()) : ?>
                    <?php while ($trade_query->have_posts()) : $trade_query->the_post(); ?>
                        <?php hdh_render_trade_card(get_the_ID()); ?>
                    <?php endwhile; ?>
                <?php else : ?>
                    <div class="no-trades-message">
                        <div class="no-trades-message-icon">🔍</div>
                        <h3 class="no-trades-message-title"><?php echo esc_html(hdh_get_content('trade_search', 'empty_state_message', 'Henüz İlan Yok')); ?></h3>
                        <p><?php echo esc_html(hdh_get_content('trade_search', 'no_results_message', 'Şu anda aktif hediye ilanı bulunmamaktadır.')); ?></p>
                        <div class="no-trades-message-actions">
                            <a href="<?php echo esc_url(home_url('/ilan-ver')); ?>" class="btn-create-listing">
                                <span>➕</span>
                                <span><?php echo esc_html(hdh_get_content('profile', 'create_listing_button', 'İlan Oluştur')); ?></span>
                            </a>
                        </div>
                    </div>
                <?php endif; 
                wp_reset_postdata();
                ?>
            </div>
            
            <!-- Pagination (will be updated via AJAX) -->
            <div class="trade-pagination" id="trade-pagination"></div>
        </div>
    </div>
</main>

<?php
get_footer();
?>
