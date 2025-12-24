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
);

// Use presence-based sorting if available (default: presence)
if (function_exists('hdh_get_listings_with_presence')) {
    $recent_listings = hdh_get_listings_with_presence($recent_listings_args, 'presence');
} else {
    // Fallback to standard query
    $recent_listings_args['orderby'] = 'date';
    $recent_listings_args['order'] = 'DESC';
    $recent_listings = new WP_Query($recent_listings_args);
}
?>

<!-- Homepage: Action Starter -->
<main class="homepage-action-starter">
    
    <!-- 1. TOP SECTION - PRIMARY ACTION -->
    <section class="homepage-hero">
        <div class="container">
            <h1 class="homepage-headline"><?php echo esc_html(hdh_get_content('homepage', 'headline', 'Diğer çiftliklerle hediyeleşmeye başla')); ?></h1>
            <p class="homepage-subtitle"><?php echo esc_html(hdh_get_content('homepage', 'subtitle', 'Diğer çiftliklerle güvenle hediyeleş')); ?></p>
            <div class="homepage-cta-buttons">
                <a href="<?php echo esc_url(home_url('/ara')); ?>" class="homepage-primary-cta homepage-cta-search">
                    <?php echo esc_html(hdh_get_content('homepage', 'cta_search_text', 'İlan Ara')); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/ilan-ver')); ?>" class="homepage-primary-cta homepage-cta-create">
                    <?php echo esc_html(hdh_get_content('homepage', 'cta_create_text', 'İlan Ver')); ?>
                </a>
            </div>
        </div>
    </section>
    
    <!-- 2. MIDDLE SECTION - SON İLANLAR -->
    <?php if ($recent_listings->have_posts()) : ?>
    <section class="homepage-recent-listings">
        <div class="container">
            <h2 class="homepage-section-title"><?php echo esc_html(hdh_get_content('homepage', 'recent_listings_title', 'Son İlanlar')); ?></h2>
            
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
    
    <!-- 2.5. LOBBY CHAT SECTION -->
    <?php hdh_render_lobby_chat(); ?>
    
    <!-- 3. TRUST INDICATOR (MINIMAL) -->
    <?php if ($total_exchanges > 0) : ?>
    <section class="homepage-trust-indicator">
        <div class="container">
            <p class="trust-indicator-text">
                <?php 
                $trust_text = hdh_get_content('homepage', 'trust_indicator_text', '⭐ {count} başarılı hediyeleşme');
                echo esc_html(str_replace('{count}', number_format_i18n($total_exchanges), $trust_text));
                ?>
            </p>
        </div>
    </section>
    <?php endif; ?>
    
</main>

<?php
get_footer();
?>
