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
            <h1 class="error-404-title"><?php echo esc_html(hdh_get_content('error_404', 'page_title', 'Sayfa Bulunamadı')); ?></h1>
            <p class="error-404-code"><?php echo esc_html(hdh_get_content('error_404', 'error_code', '404')); ?></p>
            <div class="error-404-content">
                <p class="error-404-text"><?php echo esc_html(hdh_get_content('error_404', 'main_message', 'Aradığınız sayfa bulunamadı. Bu sayfaya ulaşmaya çalışırken bir sorun oluşmuş olabilir.')); ?></p>
                <p class="error-404-subtext"><?php echo esc_html(hdh_get_content('error_404', 'sub_message', 'Muhtemelen aradığınız sayfa taşınmış, silinmiş veya hiç var olmamış olabilir.')); ?></p>
            </div>
            
            <div class="error-404-search">
                <h2 class="error-404-search-title"><?php echo esc_html(hdh_get_content('error_404', 'search_title', 'Ne Arıyordunuz?')); ?></h2>
                <div class="error-404-suggestions">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="suggestion-link">
                        <span class="suggestion-icon">🏠</span>
                        <span class="suggestion-text"><?php echo esc_html(hdh_get_content('error_404', 'home_link_text', 'Ana Sayfa')); ?></span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/ara')); ?>" class="suggestion-link">
                        <span class="suggestion-icon">🔍</span>
                        <span class="suggestion-text"><?php echo esc_html(hdh_get_content('error_404', 'search_link_text', 'İlan Ara')); ?></span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/ilan-ver')); ?>" class="suggestion-link">
                        <span class="suggestion-icon">➕</span>
                        <span class="suggestion-text"><?php echo esc_html(hdh_get_content('error_404', 'create_link_text', 'İlan Ver')); ?></span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/cekilis')); ?>" class="suggestion-link">
                        <span class="suggestion-icon">🎟️</span>
                        <span class="suggestion-text"><?php echo esc_html(hdh_get_content('error_404', 'raffle_link_text', 'Çekiliş')); ?></span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/hazine')); ?>" class="suggestion-link">
                        <span class="suggestion-icon">💎</span>
                        <span class="suggestion-text"><?php echo esc_html(hdh_get_content('error_404', 'treasure_link_text', 'Hazine')); ?></span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/profil')); ?>" class="suggestion-link">
                        <span class="suggestion-icon">👤</span>
                        <span class="suggestion-text"><?php echo esc_html(hdh_get_content('error_404', 'profile_link_text', 'Profil')); ?></span>
                    </a>
                </div>
            </div>
            
            <div class="error-404-help">
                <p><?php 
                $help_text = hdh_get_content('error_404', 'help_text', 'Sorun devam ediyorsa, lütfen <a href="{support_url}">destek</a> ile iletişime geçin.');
                echo wp_kses(str_replace('{support_url}', esc_url(home_url('/profil')), $help_text), array('a' => array('href' => array())));
                ?></p>
            </div>
        </div>
    </div>
</main>

<?php
get_footer();
?>
