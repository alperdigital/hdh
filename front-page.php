<?php
/**
 * Front Page Template
 * Ana sayfa template'i - Seçilen bölümün içeriği ve görünümüyle bire bir aynı
 */

get_header();

// Admin panelinden seçilen section'ı al
$front_page_section_id = get_option('mi_front_page_section', 0);

// Eğer section seçilmemişse, default olarak Başyazı'yı bul
if ($front_page_section_id == 0) {
    $basyazi_section = get_posts(array(
        'post_type' => 'mi_section',
        'posts_per_page' => 1,
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => '_mi_section_active',
                'value' => '1',
                'compare' => '='
            ),
            array(
                'key' => '_mi_section_type',
                'value' => 'aciklama',
                'compare' => '='
            )
        ),
        'orderby' => 'menu_order',
        'order' => 'ASC'
    ));
    
    if (!empty($basyazi_section)) {
        $front_page_section_id = $basyazi_section[0]->ID;
    }
}

// Seçilen section'ı al
$section = null;
if ($front_page_section_id > 0) {
    $section = get_post($front_page_section_id);
}

// Ana sayfa görünümü - single-mi_section.php ile bire bir aynı
?>

<!-- HDH: Immersive Farm World Hero Section -->
<section class="farm-hero-world" id="farm-hero">
    <!-- Floating Decorative Elements -->
    <div class="floating-cloud" style="top: 10%; left: 5%; animation-delay: 0s;">☁️</div>
    <div class="floating-cloud" style="top: 20%; right: 10%; animation-delay: 2s;">☁️</div>
    <div class="floating-cloud" style="top: 15%; left: 50%; animation-delay: 4s;">☁️</div>
    <div class="floating-leaf" style="top: 30%; left: 20%; animation-delay: 1s;">🍃</div>
    <div class="floating-leaf" style="top: 25%; right: 25%; animation-delay: 3s;">🍃</div>
    <div class="sparkle" style="top: 40%; left: 15%; animation-delay: 0.5s;"></div>
    <div class="sparkle" style="top: 35%; right: 20%; animation-delay: 1.5s;"></div>
    <div class="sparkle" style="top: 50%; left: 60%; animation-delay: 2.5s;"></div>
    
    <div class="container">
        <div class="hero-content-wrapper">
            <h1 class="hero-title-cartoon">Hay Day Yardım, Rehber ve Etkinlik Merkezi</h1>
            <p class="hero-subtitle-cartoon">Çiftliğinizi geliştirmek, etkinlikleri kaçırmamak ve toplulukla bağlantıda kalmak için ihtiyacınız olan her şey burada!</p>
            
            <!-- HDH: Wooden Sign CTA Buttons -->
            <div class="hero-buttons">
                <?php
                $cta_buttons = array(
                    array(
                        'text' => 'Güncel Çekilişler',
                        'url' => '#events',
                        'type' => 'primary',
                        'icon' => '🎁'
                    ),
                    array(
                        'text' => 'Başlangıç Rehberi',
                        'url' => '#guides',
                        'type' => 'secondary',
                        'icon' => '📚'
                    )
                );
                if (function_exists('hdh_cta_buttons')) {
                    hdh_cta_buttons($cta_buttons);
                } else {
                    // Fallback
                    echo '<a href="#events" class="btn-wooden-sign btn-primary">🎁 Güncel Çekilişler</a>';
                    echo '<a href="#guides" class="btn-wooden-sign btn-secondary">📚 Başlangıç Rehberi</a>';
                }
                ?>
            </div>
        </div>
    </div>
    
    <!-- HDH: Animated Farm Background Elements -->
    <div class="farm-hero-background">
        <div class="farm-sun">☀️</div>
        <div class="farm-hills"></div>
    </div>
</section>

<!-- HDH: Feature Sections with Farm Board Cards -->
<section class="farm-features-section" id="features">
    <div class="container">
        <h2 class="section-title-cartoon">Neler Sunuyoruz?</h2>
        <div class="farm-features-grid">
            <?php
            $features = array(
                array(
                    'icon' => '📚',
                    'title' => 'Detaylı Rehberler',
                    'content' => 'Hay Day\'de başarılı olmak için ihtiyacınız olan tüm ipuçları ve stratejiler.'
                ),
                array(
                    'icon' => '🎁',
                    'title' => 'Güncel Etkinlikler',
                    'content' => 'Hiçbir özel etkinliği kaçırmayın! Tüm güncel etkinlikler ve çekilişler burada.'
                ),
                array(
                    'icon' => '👥',
                    'title' => 'Aktif Topluluk',
                    'content' => 'Binlerce oyuncu ile bilgi paylaşın, sorularınızı sorun ve deneyimlerinizi paylaşın.'
                ),
                array(
                    'icon' => '🎉',
                    'title' => 'Özel Çekilişler',
                    'content' => 'Düzenli olarak düzenlenen özel çekilişler ve ödüller sizi bekliyor!'
                )
            );
            
            foreach ($features as $feature) {
                if (function_exists('hdh_farm_card')) {
                    hdh_farm_card(
                        $feature['title'],
                        '<p>' . esc_html($feature['content']) . '</p>',
                        $feature['icon']
                    );
                } else {
                    // Fallback
                    echo '<div class="farm-board-card">';
                    echo '<h3 class="farm-board-card-title">';
                    echo '<span class="farm-board-card-icon">' . esc_html($feature['icon']) . '</span>';
                    echo esc_html($feature['title']);
                    echo '</h3>';
                    echo '<div class="farm-board-card-content"><p>' . esc_html($feature['content']) . '</p></div>';
                    echo '</div>';
                }
            }
            ?>
        </div>
    </div>
</section>

<main>
    <div class="container">
        <?php if ($section && $section->post_type === 'mi_section') : ?>
            <?php 
            setup_postdata($section);
            $section_type = mi_get_section_type($section->ID);
            $section_name = mi_get_section_name($section->ID);
            $ui_position = mi_get_ui_template_position($section->ID);
            ?>
            
            <?php if ($section_type !== 'aciklama' && $section_type !== 'manset' && $section_type !== 'iletisim') : ?>
                <div class="section-header">
                    <h1 class="section-title"><?php echo esc_html($section_name); ?></h1>
                    <?php if (get_the_excerpt()) : ?>
                        <p class="section-description"><?php echo esc_html(get_the_excerpt()); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($ui_position === 'top' || $ui_position === 'default') : ?>
                <?php if (function_exists('mi_render_ui_components')) : ?>
                    <?php mi_render_ui_components($section_type, $section->ID); ?>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if ($section_type === 'aciklama') : ?>
                <div class="section-content aciklama-content">
                    <?php the_content(); ?>
                </div>
            <?php elseif ($section_type !== 'default') : ?>
                <?php if (function_exists('mi_render_section_template')) : ?>
                    <?php mi_render_section_template($section); ?>
                <?php endif; ?>
            <?php else : ?>
                <div class="section-content">
                    <?php 
                    $content = get_the_content();
                    remove_filter('the_content', 'wpautop');
                    $content = apply_filters('the_content', $content);
                    add_filter('the_content', 'wpautop');
                    echo $content;
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if ($ui_position === 'bottom') : ?>
                <?php if (function_exists('mi_render_ui_components')) : ?>
                    <?php mi_render_ui_components($section_type, $section->ID); ?>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php wp_reset_postdata(); ?>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();
?>

