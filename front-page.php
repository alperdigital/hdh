<?php
/**
 * Front Page Template - Hay Day Trading Platform
 * 
 * DEV NOTES:
 * - This homepage displays a feed of trade offers (hayday_trade CPT)
 * - Includes filtering by wanted_item, status, and sorting
 * - Shows 4 main CTA sections at top
 * - Each trade card clearly shows "İSTEDİĞİ" vs "VEREBİLECEKLERİ"
 * 
 * TODO:
 * - [x] Custom Post Type registered
 * - [x] Trade card component created
 * - [ ] Filtering system implemented
 * - [ ] Single trade page template
 * - [ ] Trust/rating system
 */

get_header();
?>

<!-- HDH: Create Trade Offer Form -->
<section class="create-trade-form-section" id="create-trade">
    <div class="container">
        <div class="create-trade-wrapper">
            <h2 class="section-title-cartoon">Hediyeleşme Başlasın</h2>
            
            <form id="create-trade-form" class="trade-create-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('hdh_create_trade', 'hdh_trade_nonce'); ?>
                <input type="hidden" name="action" value="hdh_create_trade">
                
                <!-- Almak İstediğin Hediye -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <span class="title-icon">🔍</span>
                        Almak İstediğin Hediye
                    </h3>
                    <div class="items-grid" id="wanted-items-grid">
                        <?php 
                        $items = hdh_get_items_config();
                        foreach ($items as $slug => $item) {
                            echo hdh_render_item_card($slug, 'wanted_item', 'radio', '');
                        }
                        ?>
                    </div>
                    <div class="quantity-input-wrapper">
                        <label for="wanted_qty">Miktar:</label>
                        <input type="number" 
                               id="wanted_qty" 
                               name="wanted_qty" 
                               min="1" 
                               value="1" 
                               required
                               class="quantity-input">
                    </div>
                </div>
                
                <!-- Vermek İstediğin Hediye -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <span class="title-icon">🎁</span>
                        Vermek İstediğin Hediye (En fazla 3 ürün seçebilirsiniz)
                    </h3>
                    <div class="items-grid" id="offer-items-grid">
                        <?php 
                        foreach ($items as $slug => $item) {
                            echo hdh_render_item_card($slug, 'offer_item[' . esc_attr($slug) . ']', 'checkbox', '');
                        }
                        ?>
                    </div>
                    <div class="offer-quantities" id="offer-quantities">
                        <!-- Dynamic quantity inputs will be added here via JS -->
                    </div>
                </div>
                
                <!-- İlan Başlığı -->
                <div class="form-section">
                    <div class="form-field">
                        <label for="trade_title">İlan Başlığı:</label>
                        <input type="text" 
                               id="trade_title" 
                               name="trade_title" 
                               required
                               placeholder="Örn: 7 Bant arıyorum, 7 Cıvata verebilirim"
                               class="form-input">
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="form-actions">
                    <button type="submit" class="btn-submit-trade btn-wooden-sign btn-primary">
                        <span class="btn-icon">✨</span>
                        İlanı Oluştur
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- HDH: Trade Offer Feed Section -->
<main class="trade-feed-main" id="trade-feed">
    <div class="container">
        <h2 class="section-title-cartoon">Hediyeni Seç</h2>
        
        <!-- HDH: Visual Filter Grid (9 Items) -->
        <div class="trade-filter-visual-grid">
            <h3 class="filter-grid-title">Hediye Edilecek Hediyeye Göre Filtrele</h3>
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
            <!-- Sticky Header: Hediye Ediyor / Hediye İstiyor Labels (Centered with Divider) -->
            <div class="listing-feed-sticky-header">
                <div class="sticky-header-column sticky-header-left">
                    <span class="sticky-header-icon">🎁</span>
                    <span class="sticky-header-label">Hediye Ediyor</span>
                </div>
                <div class="sticky-header-divider"></div>
                <div class="sticky-header-column sticky-header-right">
                    <span class="sticky-header-icon">🤍</span>
                    <span class="sticky-header-label">Hediye İstiyor</span>
                </div>
            </div>
            
            <div class="trade-loading" id="trade-loading" style="display: none;">
                <div class="loading-spinner">⏳</div>
                <p>İlanlar yükleniyor...</p>
            </div>
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
                    'orderby' => 'date',
                    'order' => 'DESC',
                );
                
                $trade_query = new WP_Query($args);
                
                if ($trade_query->have_posts()) : ?>
                    <?php while ($trade_query->have_posts()) : $trade_query->the_post(); ?>
                        <?php hdh_render_trade_card(get_the_ID()); ?>
                    <?php endwhile; ?>
                <?php else : ?>
                    <div class="no-trades-message">
                        <p>Henüz hediye ilanı bulunmamaktadır.</p>
                        <p>İlk ilanı siz oluşturun!</p>
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
