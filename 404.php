<?php
/**
 * 404 Error Page Template
 * HDH: User-friendly 404 page
 */

get_header();
?>

<main class="error-404-main">
    <div class="container">
        <div class="error-404-card">
            <div class="error-404-icon">🌾</div>
            <h1 class="error-404-title">Sayfa Bulunamadı</h1>
            <p class="error-404-code">404</p>
            <div class="error-404-content">
                <p class="error-404-text">Aradığınız sayfa bulunamadı. Bu sayfaya ulaşmaya çalışırken bir sorun oluşmuş olabilir.</p>
                <p class="error-404-subtext">Muhtemelen aradığınız sayfa taşınmış, silinmiş veya hiç var olmamış olabilir.</p>
            </div>
            
            <div class="error-404-search">
                <h2 class="error-404-search-title">Ne Arıyordunuz?</h2>
                <div class="error-404-suggestions">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="suggestion-link">
                        <span class="suggestion-icon">🏠</span>
                        <span class="suggestion-text">Ana Sayfa</span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/ara')); ?>" class="suggestion-link">
                        <span class="suggestion-icon">🔍</span>
                        <span class="suggestion-text">İlan Ara</span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/ilan-ver')); ?>" class="suggestion-link">
                        <span class="suggestion-icon">➕</span>
                        <span class="suggestion-text">İlan Ver</span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/cekilis')); ?>" class="suggestion-link">
                        <span class="suggestion-icon">🎟️</span>
                        <span class="suggestion-text">Çekiliş</span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/hazine')); ?>" class="suggestion-link">
                        <span class="suggestion-icon">💎</span>
                        <span class="suggestion-text">Hazine</span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/profil')); ?>" class="suggestion-link">
                        <span class="suggestion-icon">👤</span>
                        <span class="suggestion-text">Profil</span>
                    </a>
                </div>
            </div>
            
            <div class="error-404-help">
                <p>Sorun devam ediyorsa, lütfen <a href="<?php echo esc_url(home_url('/profil')); ?>">destek</a> ile iletişime geçin.</p>
            </div>
        </div>
    </div>
</main>

<?php
get_footer();
?>
