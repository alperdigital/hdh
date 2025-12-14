<?php
/**
 * HDH: AJAX Handlers
 * Handles AJAX requests for trade filtering
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX Handler: Filter trades by offer item
 */
function hdh_filter_trades_by_offer_item() {
    // Generate request ID for debugging
    $request_id = uniqid('hdh_filter_', true);
    
    // Verify nonce
    if (!check_ajax_referer('hdh_filter_trades', 'nonce', false)) {
        wp_send_json_error(array(
            'message' => 'Güvenlik doğrulaması başarısız. Lütfen sayfayı yenileyin.',
            'request_id' => $request_id
        ), 403);
        return;
    }
    
    $item_slug = isset($_POST['item_slug']) ? sanitize_text_field($_POST['item_slug']) : '';
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    
    // Validate page number
    if ($page < 1) {
        $page = 1;
    }
    
    // Build query
    $args = array(
        'post_type' => 'hayday_trade',
        'posts_per_page' => 20,
        'paged' => $page,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC',
    );
    
    // Build meta query: always filter by status (open trades only)
    $meta_query = array(
        array(
            'key' => '_hdh_trade_status',
            'value' => 'open',
            'compare' => '='
        )
    );
    
    // If item_slug is provided, also filter by offer item (check all 3 offer_item slots)
    if (!empty($item_slug)) {
        $meta_query[] = array(
            'relation' => 'OR',
            array(
                'key' => '_hdh_offer_item_1',
                'value' => $item_slug,
                'compare' => '='
            ),
            array(
                'key' => '_hdh_offer_item_2',
                'value' => $item_slug,
                'compare' => '='
            ),
            array(
                'key' => '_hdh_offer_item_3',
                'value' => $item_slug,
                'compare' => '='
            )
        );
        
        // Set relation to AND when we have both status and item filters
        $args['meta_query'] = array(
            'relation' => 'AND',
            $meta_query[0], // status filter
            $meta_query[1]  // item filter
        );
    } else {
        // Only status filter
        $args['meta_query'] = $meta_query;
    }
    
    $trade_query = new WP_Query($args);
    
    ob_start();
    
    if ($trade_query->have_posts()) {
        echo '<div class="trade-cards-grid">';
        while ($trade_query->have_posts()) {
            $trade_query->the_post();
            hdh_render_trade_card(get_the_ID());
        }
        echo '</div>';
    } else {
        echo '<div class="no-trades-message">';
        echo '<div class="no-trades-message-icon">🎁</div>';
        echo '<h3 class="no-trades-message-title">İlan Bulunamadı</h3>';
        if (!empty($item_slug)) {
            $item_label = function_exists('hdh_get_item_label') ? hdh_get_item_label($item_slug) : 'Bu ürün';
            echo '<p>' . esc_html($item_label) . ' verebilecek aktif ilan bulunmamaktadır.</p>';
            echo '<p>Farklı bir ürün seçerek arama yapabilir veya kendi ilanınızı oluşturabilirsiniz.</p>';
            echo '<div class="no-trades-message-actions">';
            echo '<button type="button" class="btn-clear-filter-inline" onclick="document.getElementById(\'btn-clear-filter-visual\').click();">';
            echo '<span>🔄</span><span>Filtreyi Temizle</span>';
            echo '</button>';
            echo '<a href="' . esc_url(home_url('/ilan-ver')) . '" class="btn-create-listing">';
            echo '<span>➕</span><span>İlan Oluştur</span>';
            echo '</a>';
            echo '</div>';
        } else {
            echo '<p>Şu anda aktif hediye ilanı bulunmamaktadır.</p>';
            echo '<p>İlk ilanı siz oluşturarak topluluğa katkıda bulunun!</p>';
            echo '<div class="no-trades-message-actions">';
            echo '<a href="' . esc_url(home_url('/ilan-ver')) . '" class="btn-create-listing">';
            echo '<span>➕</span><span>İlan Oluştur</span>';
            echo '</a>';
            echo '</div>';
        }
        echo '</div>';
    }
    
    wp_reset_postdata();
    
    $html = ob_get_clean();
    
    // Pagination HTML (separate from cards)
    $pagination_html = '';
    if ($trade_query->max_num_pages > 1) {
        $pagination = paginate_links(array(
            'total' => $trade_query->max_num_pages,
            'current' => $page,
            'prev_text' => '← Önceki',
            'next_text' => 'Sonraki →',
            'mid_size' => 2,
            'type' => 'array',
        ));
        
        if (!empty($pagination)) {
            $pagination_html = '<div class="trade-pagination">' . implode(' ', $pagination) . '</div>';
        }
    }
    
    wp_send_json_success(array(
        'html' => $html,
        'pagination' => $pagination_html,
        'found_posts' => $trade_query->found_posts,
        'max_pages' => $trade_query->max_num_pages,
        'request_id' => $request_id,
        'query_time' => timer_stop(0, 3) // Query execution time
    ));
}

/**
 * Log AJAX errors for debugging
 */
function hdh_log_ajax_error($request_id, $error_message, $context = array()) {
    if (!WP_DEBUG) {
        return;
    }
    
    error_log(sprintf(
        '[HDH AJAX Error] Request ID: %s | Message: %s | Context: %s',
        $request_id,
        $error_message,
        json_encode($context)
    ));
}
add_action('wp_ajax_hdh_filter_trades', 'hdh_filter_trades_by_offer_item');
add_action('wp_ajax_nopriv_hdh_filter_trades', 'hdh_filter_trades_by_offer_item');
