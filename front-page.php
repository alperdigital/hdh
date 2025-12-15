<?php
/**
 * Front Page Template - Hay Day Help
 * Mobile-first action starter homepage
 */

get_header();

// Get total completed exchanges for trust indicator
$total_exchanges = function_exists('hdh_get_total_completed_exchanges') 
    ? hdh_get_total_completed_exchanges() 
    : 0;

// Get recent listings for social proof (4-6 items)
$recent_listings_args = array(
    'post_type' => 'hayday_trade',
    'posts_per_page' => 6,
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
$recent_listings = new WP_Query($recent_listings_args);
?>

<!-- Homepage: Action Starter -->
<main class="homepage-action-starter">
    
    <?php if (is_user_logged_in() && function_exists('hdh_render_quest_panel')) : ?>
        <?php echo hdh_render_quest_panel(); ?>
        <button class="quest-icon-fixed" id="quest-icon-toggle" aria-label="Görevler">
            <span class="quest-icon-emoji">📋</span>
            <span class="quest-icon-badge" id="quest-icon-badge" style="display: none;">0</span>
        </button>
    <?php endif; ?>
    
    <!-- 1. TOP SECTION - PRIMARY ACTION -->
    <section class="homepage-hero">
        <div class="container">
            <h1 class="homepage-headline">Diğer çiftliklerle hediyeleşmeye başla</h1>
            <p class="homepage-subtitle">Diğer çiftliklerle güvenle hediyeleş</p>
            <div class="homepage-cta-buttons">
                <a href="<?php echo esc_url(home_url('/ara')); ?>" class="homepage-primary-cta homepage-cta-search">
                    İlan Ara
                </a>
                <a href="<?php echo esc_url(home_url('/ilan-ver')); ?>" class="homepage-primary-cta homepage-cta-create">
                    İlan Ver
                </a>
            </div>
        </div>
    </section>
    
    <!-- 2. MIDDLE SECTION - SON İLANLAR -->
    <?php if ($recent_listings->have_posts()) : ?>
    <section class="homepage-recent-listings">
        <div class="container">
            <h2 class="homepage-section-title">Son İlanlar</h2>
            
            <!-- Listing Cards -->
            <div class="trade-cards-grid">
                <?php while ($recent_listings->have_posts()) : $recent_listings->the_post(); ?>
                    <?php hdh_render_trade_card(get_the_ID()); ?>
                <?php endwhile; ?>
            </div>
        </div>
    </section>
    <?php endif; 
    wp_reset_postdata();
    ?>
    
    <!-- 3. TRUST INDICATOR (MINIMAL) -->
    <?php if ($total_exchanges > 0) : ?>
    <section class="homepage-trust-indicator">
        <div class="container">
            <p class="trust-indicator-text">
                ⭐ <?php echo esc_html(number_format_i18n($total_exchanges)); ?> başarılı hediyeleşme
            </p>
        </div>
    </section>
    <?php endif; ?>
    
</main>

<?php
get_footer();
?>
