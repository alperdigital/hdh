<?php
/**
 * Template Name: Dekorlar
 */
get_header();
$decorations = function_exists('hdh_get_decorations_config') ? hdh_get_decorations_config() : array();
?>
<main class="decorations-page-main"><div class="container">
    <h1 class="decorations-page-title">Ücretsiz Dekorasyonlar</h1>
    <p class="decorations-page-subtitle">Hay Day'de ücretsiz kullanabileceğiniz dekorasyonlar</p>
    <?php if (!empty($decorations)) : ?>
        <div class="decorations-grid">
            <?php foreach ($decorations as $decoration) : ?>
                <div class="decoration-card">
                    <div class="decoration-image-wrapper">
                        <?php if (!empty($decoration['image'])) : ?>
                            <img src="<?php echo esc_url($decoration['image']); ?>" alt="<?php echo esc_attr($decoration['name']); ?>" class="decoration-image" loading="lazy" decoding="async">
                        <?php else : ?>
                            <div class="decoration-placeholder"><span class="placeholder-icon">🎨</span></div>
                        <?php endif; ?>
                    </div>
                    <div class="decoration-content">
                        <h3 class="decoration-name"><?php echo esc_html($decoration['name']); ?></h3>
                        <?php if (!empty($decoration['description'])) : ?><p class="decoration-description"><?php echo esc_html($decoration['description']); ?></p><?php endif; ?>
                        <a href="<?php echo esc_url($decoration['source_url']); ?>" target="_blank" rel="noopener noreferrer" class="btn-get-decoration">Dekorasyonu Al</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <div class="no-decorations-message"><p>Henüz dekorasyon eklenmemiş.</p></div>
    <?php endif; ?>
</div></main>
<?php get_footer(); ?>
