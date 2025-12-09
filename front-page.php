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

<!-- HDH: Hero Section with Updated CTAs -->
<section class="farm-hero-world" id="farm-hero">
    <div class="floating-cloud" style="top: 10%; left: 5%; animation-delay: 0s;">☁️</div>
    <div class="floating-cloud" style="top: 20%; right: 10%; animation-delay: 2s;">☁️</div>
    <div class="floating-leaf" style="top: 30%; left: 20%; animation-delay: 1s;">🍃</div>
    
    <div class="container">
        <div class="hero-content-wrapper">
            <h1 class="hero-title-cartoon">Yılbaşı hediyeni seçtin mi?</h1>
        </div>
    </div>
    
    <div class="farm-hero-background">
        <div class="farm-sun">☀️</div>
        <div class="farm-hills"></div>
    </div>
</section>

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

<!-- HDH: Main Feature CTAs -->
<section class="main-features-section" id="main-features">
    <div class="container">
        <div class="main-features-grid">
            <div class="main-feature-card">
                <div class="feature-icon">🎨</div>
                <h3 class="feature-title">Ücretsiz Dekorasyonlar</h3>
                <p class="feature-description">Çiftliğinizi süsleyin</p>
                <a href="#" class="btn-wooden-sign btn-secondary">Keşfet →</a>
            </div>
            
            <div class="main-feature-card">
                <div class="feature-icon">🎁</div>
                <h3 class="feature-title">Çekilişe Katıl</h3>
                <p class="feature-description">Özel ödüller kazanın</p>
                <a href="#" class="btn-wooden-sign btn-secondary">Katıl →</a>
            </div>
            
            <div class="main-feature-card highlight">
                <div class="feature-icon">🔄</div>
                <h3 class="feature-title">Hediyeleşme</h3>
                <p class="feature-description">İhtiyacınız olan ürünleri bulun</p>
                <a href="#trade-feed" class="btn-wooden-sign btn-primary">Hediyeni Seç →</a>
            </div>
            
            <div class="main-feature-card">
                <div class="feature-icon">👥</div>
                <h3 class="feature-title">Mahalleye Katıl</h3>
                <p class="feature-description">Toplulukla bağlantıda kalın</p>
                <a href="#" class="btn-wooden-sign btn-secondary">Katıl →</a>
            </div>
        </div>
    </div>
</section>

<!-- HDH: Trade Offer Feed Section -->
<main class="trade-feed-main" id="trade-feed">
    <div class="container">
        <h2 class="section-title-cartoon">Hediyeni Seç</h2>
        
        <!-- HDH: Filter Bar -->
        <div class="trade-filter-bar">
            <form method="get" action="<?php echo esc_url(home_url('/')); ?>" class="trade-filters">
                <div class="filter-group">
                    <label for="filter_wanted">İstediği Ürün:</label>
                    <select name="wanted" id="filter_wanted">
                        <option value="">Hepsi</option>
                        <?php 
                        $hayday_items = hdh_get_hayday_items();
                        $selected_wanted = isset($_GET['wanted']) ? sanitize_text_field($_GET['wanted']) : '';
                        foreach ($hayday_items as $key => $label) : ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($selected_wanted, $key); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="filter_status">Durum:</label>
                    <select name="status" id="filter_status">
                        <?php 
                        $selected_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'open';
                        ?>
                        <option value="all" <?php selected($selected_status, 'all'); ?>>Hepsi</option>
                        <option value="open" <?php selected($selected_status, 'open'); ?>>Açık</option>
                        <option value="completed" <?php selected($selected_status, 'completed'); ?>>Tamamlandı</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="filter_sort">Sıralama:</label>
                    <select name="sort" id="filter_sort">
                        <?php 
                        $selected_sort = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'newest';
                        ?>
                        <option value="newest" <?php selected($selected_sort, 'newest'); ?>>En yeni ilanlar</option>
                        <option value="oldest" <?php selected($selected_sort, 'oldest'); ?>>En eski ilanlar</option>
                    </select>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn-filter btn-wooden-sign btn-primary">Filtrele</button>
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="btn-clear-filters">Filtreleri Temizle</a>
                </div>
            </form>
        </div>
        
        <!-- HDH: Trade Offers Feed -->
        <div class="trade-feed-container">
            <?php
            // Build query based on filters
            $args = array(
                'post_type' => 'hayday_trade',
                'posts_per_page' => 20,
                'post_status' => 'publish',
            );
            
            // Meta query for filters
            $meta_query = array('relation' => 'AND');
            
            // Filter by wanted item
            if (!empty($_GET['wanted'])) {
                $meta_query[] = array(
                    'key' => '_hdh_wanted_item',
                    'value' => sanitize_text_field($_GET['wanted']),
                    'compare' => '='
                );
            }
            
            // Filter by status
            $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'open';
            if ($status_filter !== 'all') {
                $meta_query[] = array(
                    'key' => '_hdh_trade_status',
                    'value' => $status_filter,
                    'compare' => '='
                );
            } else {
                // Show all, but default to open if no filter
                if (empty($_GET['status'])) {
                    $meta_query[] = array(
                        'key' => '_hdh_trade_status',
                        'value' => 'open',
                        'compare' => '='
                    );
                }
            }
            
            if (!empty($meta_query)) {
                $args['meta_query'] = $meta_query;
            }
            
            // Sorting
            $sort = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'newest';
            if ($sort === 'oldest') {
                $args['orderby'] = 'date';
                $args['order'] = 'ASC';
            } else {
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
            }
            
            $trade_query = new WP_Query($args);
            
            if ($trade_query->have_posts()) : ?>
                <div class="trade-cards-grid">
                    <?php while ($trade_query->have_posts()) : $trade_query->the_post(); ?>
                        <?php hdh_render_trade_card(get_the_ID()); ?>
                    <?php endwhile; ?>
                </div>
                
                <!-- Pagination -->
                <div class="trade-pagination">
                    <?php
                    echo paginate_links(array(
                        'total' => $trade_query->max_num_pages,
                        'prev_text' => '← Önceki',
                        'next_text' => 'Sonraki →',
                        'mid_size' => 2,
                    ));
                    ?>
                </div>
            <?php else : ?>
                <div class="no-trades-message">
                    <p>Henüz hediye ilanı bulunmamaktadır.</p>
                    <p>İlk ilanı siz oluşturun!</p>
                </div>
            <?php endif; 
            wp_reset_postdata();
            ?>
        </div>
    </div>
</main>

<?php
get_footer();
?>
