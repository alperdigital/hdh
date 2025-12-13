<?php
/**
 * Template Name: Çekiliş
 */
get_header();
$user_id = is_user_logged_in() ? get_current_user_id() : 0;
$jeton_balance = $user_id && function_exists('hdh_get_user_jeton_balance') ? hdh_get_user_jeton_balance($user_id) : 0;
$kurek_entries_today = $user_id && function_exists('hdh_get_lottery_entries_today') ? hdh_get_lottery_entries_today($user_id, 'kurek') : 0;
$genisletme_entries_today = $user_id && function_exists('hdh_get_lottery_entries_today') ? hdh_get_lottery_entries_today($user_id, 'genisletme') : 0;
?>
<main class="lottery-page-main"><div class="container">
    <h1 class="lottery-page-title">Çekiliş</h1>
    <?php if (is_user_logged_in()) : ?>
        <div class="lottery-balance-section"><div class="jeton-balance-display">
            <span class="jeton-icon-large">🪙</span>
            <div class="jeton-balance-info">
                <span class="jeton-balance-amount"><?php echo esc_html(number_format_i18n($jeton_balance)); ?></span>
                <span class="jeton-balance-label">Hediye Jetonu</span>
            </div>
        </div></div>
    <?php else : ?>
        <div class="lottery-login-prompt">
            <p>Çekilişe katılmak için giriş yapmanız gerekiyor.</p>
            <a href="<?php echo esc_url(home_url('/profil')); ?>" class="btn-login-for-lottery">Giriş Yap</a>
        </div>
    <?php endif; ?>
    <div class="lottery-countdown-section">
        <h2 class="countdown-title">Çekiliş Tarihi</h2>
        <div class="countdown-display" id="lottery-countdown">
            <div class="countdown-item"><span class="countdown-value" id="countdown-days">0</span><span class="countdown-label">Gün</span></div>
            <div class="countdown-item"><span class="countdown-value" id="countdown-hours">0</span><span class="countdown-label">Saat</span></div>
            <div class="countdown-item"><span class="countdown-value" id="countdown-minutes">0</span><span class="countdown-label">Dakika</span></div>
        </div>
        <p class="countdown-target">21 Aralık 2025, 20:00 (TSI)</p>
    </div>
    <?php if (is_user_logged_in()) : ?>
        <div class="lottery-card">
            <div class="lottery-header">
                <h3 class="lottery-name">89 Kürek Çekilişi</h3>
                <span class="lottery-cost">1 🪙 Jeton</span>
            </div>
            <div class="lottery-info">
                <p class="lottery-description">1 jeton ile katılabilirsiniz. Ödül: 89 Kürek</p>
                <p class="lottery-entries-info">Bugünkü katılımlarınız: <strong><?php echo esc_html($kurek_entries_today); ?>/3</strong></p>
            </div>
            <button class="btn-join-lottery <?php echo ($jeton_balance < 1 || $kurek_entries_today >= 3) ? 'disabled' : ''; ?>" data-lottery-type="kurek" data-jeton-cost="1" <?php echo ($jeton_balance < 1 || $kurek_entries_today >= 3) ? 'disabled' : ''; ?>>
                <?php if ($jeton_balance < 1) echo 'Yetersiz Jeton'; elseif ($kurek_entries_today >= 3) echo 'Günlük Limit Doldu'; else echo 'Çekilişe Katıl (1 🪙)'; ?>
            </button>
        </div>
        <div class="lottery-card">
            <div class="lottery-header">
                <h3 class="lottery-name">89 Genişletme/Ağıl Malzemesi Çekilişi</h3>
                <span class="lottery-cost">5 🪙 Jeton</span>
            </div>
            <div class="lottery-info">
                <p class="lottery-description">5 jeton ile katılabilirsiniz. Ödül: 89 Genişletme/Ağıl Malzemesi</p>
                <p class="lottery-entries-info">Bugünkü katılımlarınız: <strong><?php echo esc_html($genisletme_entries_today); ?>/3</strong></p>
            </div>
            <button class="btn-join-lottery <?php echo ($jeton_balance < 5 || $genisletme_entries_today >= 3) ? 'disabled' : ''; ?>" data-lottery-type="genisletme" data-jeton-cost="5" <?php echo ($jeton_balance < 5 || $genisletme_entries_today >= 3) ? 'disabled' : ''; ?>>
                <?php if ($jeton_balance < 5) echo 'Yetersiz Jeton'; elseif ($genisletme_entries_today >= 3) echo 'Günlük Limit Doldu'; else echo 'Çekilişe Katıl (5 🪙)'; ?>
            </button>
        </div>
    <?php endif; ?>
</div></main>
<?php get_footer(); ?>
