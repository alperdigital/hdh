<?php
/**
 * Template Name: Hazine
 * Treasure page - requires level 10 to access
 */
get_header();

// Check if user is logged in
if (!is_user_logged_in()) {
    // Show login required message
    ?>
    <main class="treasure-page-main">
        <div class="container">
            <div class="treasure-locked-screen">
                <div class="treasure-locked-icon">💎</div>
                <h1 class="treasure-locked-title">Hazine Odası</h1>
                <p class="treasure-locked-description">
                    Bu özel hazine odasına erişmek için giriş yapmanız gerekiyor.
                </p>
                <a href="<?php echo esc_url(home_url('/profil')); ?>" class="btn-treasure-login">
                    🔐 Giriş Yap
                </a>
            </div>
        </div>
    </main>
    <?php
    get_footer();
    return;
}

// User is logged in - check level
$user_id = get_current_user_id();
$user_state = function_exists('hdh_get_user_state') ? hdh_get_user_state($user_id) : null;
$user_level = $user_state ? $user_state['level'] : 1;
$required_level = 10;

if ($user_level < $required_level) {
    // Show level requirement message
    $levels_needed = $required_level - $user_level;
    ?>
    <main class="treasure-page-main">
        <div class="container">
            <div class="treasure-locked-screen treasure-level-locked">
                <div class="treasure-locked-icon treasure-sparkle">💎✨</div>
                <h1 class="treasure-locked-title">Hazine Odası Kilitli</h1>
                <div class="treasure-level-info">
                    <div class="treasure-current-level">
                        <span class="treasure-level-label">Mevcut Seviyeniz:</span>
                        <span class="treasure-level-value"><?php echo esc_html($user_level); ?></span>
                    </div>
                    <div class="treasure-required-level">
                        <span class="treasure-level-label">Gerekli Seviye:</span>
                        <span class="treasure-level-value treasure-required"><?php echo esc_html($required_level); ?></span>
                    </div>
                </div>
                <p class="treasure-locked-description">
                    Bu hazine odasına erişmek için <strong>Seviye <?php echo esc_html($required_level); ?></strong> olmanız gerekiyor.
                    <?php if ($levels_needed > 0) : ?>
                        <br><br>
                        <span class="treasure-progress-text">
                            🎯 Sadece <strong><?php echo esc_html($levels_needed); ?> seviye</strong> daha!
                        </span>
                    <?php endif; ?>
                </p>
                <div class="treasure-actions">
                    <a href="<?php echo esc_url(home_url('/ara')); ?>" class="btn-treasure-action">
                        📋 İlan Ara ve Seviye Atla
                    </a>
                    <a href="<?php echo esc_url(home_url('/ilan-ver')); ?>" class="btn-treasure-action">
                        ✨ İlan Ver ve XP Kazan
                    </a>
                </div>
                <div class="treasure-hint">
                    <p class="treasure-hint-text">
                        💡 <strong>İpucu:</strong> İlan oluşturmak, takas tamamlamak ve görevleri yapmak size XP kazandırır!
                    </p>
                </div>
            </div>
        </div>
    </main>
    <?php
    get_footer();
    return;
}

// User has level 10+ - show treasure content (placeholder for now)
?>
<main class="treasure-page-main">
    <div class="container">
        <div class="treasure-unlocked-screen">
            <div class="treasure-header">
                <div class="treasure-icon-large">💎</div>
                <h1 class="treasure-title">Hazine Odası</h1>
                <p class="treasure-subtitle">Hoş geldiniz, Seviye <?php echo esc_html($user_level); ?> oyuncusu!</p>
            </div>
            <div class="treasure-content-placeholder">
                <p class="treasure-placeholder-text">
                    🎉 Tebrikler! Hazine odasına erişim hakkı kazandınız.
                    <br><br>
                    İçerik yakında eklenecek...
                </p>
            </div>
        </div>
    </div>
</main>
<?php get_footer(); ?>
