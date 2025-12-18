<?php
/**
 * Template Name: Çekiliş
 */
get_header();
$user_id = is_user_logged_in() ? get_current_user_id() : 0;
$jeton_balance = $user_id && function_exists('hdh_get_user_jeton_balance') ? hdh_get_user_jeton_balance($user_id) : 0;
$kurek_entries_today = $user_id && function_exists('hdh_get_lottery_entries_today') ? hdh_get_lottery_entries_today($user_id, 'kurek') : 0;
$genisletme_entries_today = $user_id && function_exists('hdh_get_lottery_entries_today') ? hdh_get_lottery_entries_today($user_id, 'genisletme') : 0;

// Get lottery date info
$next_lottery_date = function_exists('hdh_get_next_lottery_date') ? hdh_get_next_lottery_date() : '';
$server_time = function_exists('hdh_get_server_time_iso') ? hdh_get_server_time_iso() : '';

// Format display date (Turkey time) - Use Turkish month names
$display_date = '';
if (!empty($next_lottery_date)) {
    try {
        $dt = new DateTime($next_lottery_date);
        $dt->setTimezone(new DateTimeZone('Europe/Istanbul'));
        
        // Use Turkish date formatter if available
        if (function_exists('hdh_format_date_turkish')) {
            $display_date = hdh_format_date_turkish($dt) . ' (TSI)';
        } else {
            // Fallback with Turkish month names
            $months_tr = array(
                1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
                5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
                9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
            );
            $day = $dt->format('d');
            $month = (int) $dt->format('m');
            $year = $dt->format('Y');
            $time = $dt->format('H:i');
            $display_date = sprintf('%s %s %s, %s (TSI)', $day, $months_tr[$month], $year, $time);
        }
    } catch (Exception $e) {
        $display_date = '21 Aralık 2025, 20:00 (TSI)';
    }
}
?>
<main class="lottery-page-main"><div class="container">
    <h1 class="lottery-page-title"><?php echo esc_html(hdh_get_content('lottery', 'page_title', 'Çekiliş')); ?></h1>
    <?php if (is_user_logged_in()) : ?>
        <div class="lottery-balance-section"><div class="jeton-balance-display">
            <span class="jeton-icon-large">🎟️</span>
            <div class="jeton-balance-info">
                <span class="jeton-balance-amount"><?php echo esc_html(number_format_i18n($jeton_balance)); ?></span>
                <span class="jeton-balance-label">Bilet</span>
            </div>
        </div></div>
    <?php else : ?>
        <div class="lottery-login-prompt">
            <p><?php echo esc_html(hdh_get_content('lottery', 'lottery_description', 'Çekilişe katılmak için giriş yapmanız gerekiyor.')); ?></p>
            <a href="<?php echo esc_url(home_url('/profil')); ?>" class="btn-login-for-lottery"><?php echo esc_html(hdh_get_content('lottery', 'login_button_text', 'Giriş Yap')); ?></a>
        </div>
    <?php endif; ?>
    <div class="lottery-countdown-section">
        <h2 class="countdown-title">Çekiliş Tarihi</h2>
        <p class="countdown-target" id="countdown-target-date"><?php echo esc_html($display_date); ?></p>
        <div class="countdown-display" id="lottery-countdown" 
             data-lottery-date="<?php echo esc_attr($next_lottery_date); ?>"
             data-server-time="<?php echo esc_attr($server_time); ?>">
            <div class="countdown-item"><span class="countdown-value" id="countdown-days">...</span><span class="countdown-label">Gün</span></div>
            <div class="countdown-item"><span class="countdown-value" id="countdown-hours">...</span><span class="countdown-label">Saat</span></div>
            <div class="countdown-item"><span class="countdown-value" id="countdown-minutes">...</span><span class="countdown-label">Dakika</span></div>
        </div>
    </div>
    <?php if (is_user_logged_in()) : ?>
        <?php
        // Get lottery configs
        $kurek_config = function_exists('hdh_get_lottery_config') ? hdh_get_lottery_config('kurek') : array('name' => '89 Kürek Çekilişi', 'description' => '1 bilet ile katılabilirsiniz. Ödül: 89 Kürek', 'cost' => 1, 'prize' => '89 Kürek', 'max_daily_entries' => 3);
        $genisletme_config = function_exists('hdh_get_lottery_config') ? hdh_get_lottery_config('genisletme') : array('name' => '89 Genişletme/Ağıl Malzemesi Çekilişi', 'description' => '5 bilet ile katılabilirsiniz. Ödül: 89 Genişletme/Ağıl Malzemesi', 'cost' => 5, 'prize' => '89 Genişletme/Ağıl Malzemesi', 'max_daily_entries' => 3);
        
        // Get participants for both lotteries
        $kurek_participants = function_exists('hdh_get_lottery_participants') ? hdh_get_lottery_participants('kurek') : array();
        $genisletme_participants = function_exists('hdh_get_lottery_participants') ? hdh_get_lottery_participants('genisletme') : array();
        $is_admin = current_user_can('administrator');
        
        // Check if lotteries are active
        $kurek_active = function_exists('hdh_is_lottery_active') ? hdh_is_lottery_active('kurek') : true;
        $genisletme_active = function_exists('hdh_is_lottery_active') ? hdh_is_lottery_active('genisletme') : true;
        ?>
        <div class="lottery-card">
            <div class="lottery-header">
                <h3 class="lottery-name"><?php echo esc_html($kurek_config['name']); ?></h3>
                <span class="lottery-cost"><?php echo esc_html($kurek_config['cost']); ?> 🎟️ Bilet</span>
            </div>
            <div class="lottery-info">
                <p class="lottery-description"><?php echo esc_html($kurek_config['description']); ?></p>
                <p class="lottery-entries-info">Bugünkü katılımlarınız: <strong><?php echo esc_html($kurek_entries_today); ?>/<?php echo esc_html($kurek_config['max_daily_entries']); ?></strong></p>
                <?php if (!empty($kurek_participants)) : ?>
                    <div class="lottery-participants">
                        <p class="lottery-participants-title">Katılan Çiftlikler (<?php echo esc_html(count($kurek_participants)); ?>):</p>
                        <div class="lottery-participants-list">
                            <?php foreach ($kurek_participants as $participant) : ?>
                                <span class="lottery-participant-name"><?php echo esc_html($participant['display_name']); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <button class="btn-join-lottery <?php echo (!$kurek_active || $jeton_balance < $kurek_config['cost'] || $kurek_entries_today >= $kurek_config['max_daily_entries']) ? 'disabled' : ''; ?>" data-lottery-type="kurek" data-jeton-cost="<?php echo esc_attr($kurek_config['cost']); ?>" <?php echo (!$kurek_active || $jeton_balance < $kurek_config['cost'] || $kurek_entries_today >= $kurek_config['max_daily_entries']) ? 'disabled' : ''; ?>>
                <?php if (!$kurek_active) echo 'Çekiliş Aktif Değil'; elseif ($jeton_balance < $kurek_config['cost']) echo 'Yetersiz Bilet'; elseif ($kurek_entries_today >= $kurek_config['max_daily_entries']) echo 'Günlük Limit Doldu'; else echo 'Çekilişe Katıl (' . esc_html($kurek_config['cost']) . ' 🎟️)'; ?>
            </button>
            <?php if ($is_admin) : ?>
                <button class="btn-start-lottery" data-lottery-type="kurek">🎲 Çekilişi Başlat</button>
            <?php endif; ?>
        </div>
        <div class="lottery-card">
            <div class="lottery-header">
                <h3 class="lottery-name"><?php echo esc_html($genisletme_config['name']); ?></h3>
                <span class="lottery-cost"><?php echo esc_html($genisletme_config['cost']); ?> 🎟️ Bilet</span>
            </div>
            <div class="lottery-info">
                <p class="lottery-description"><?php echo esc_html($genisletme_config['description']); ?></p>
                <p class="lottery-entries-info">Bugünkü katılımlarınız: <strong><?php echo esc_html($genisletme_entries_today); ?>/<?php echo esc_html($genisletme_config['max_daily_entries']); ?></strong></p>
                <?php if (!empty($genisletme_participants)) : ?>
                    <div class="lottery-participants">
                        <p class="lottery-participants-title">Katılan Çiftlikler (<?php echo esc_html(count($genisletme_participants)); ?>):</p>
                        <div class="lottery-participants-list">
                            <?php foreach ($genisletme_participants as $participant) : ?>
                                <span class="lottery-participant-name"><?php echo esc_html($participant['display_name']); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <button class="btn-join-lottery <?php echo (!$genisletme_active || $jeton_balance < $genisletme_config['cost'] || $genisletme_entries_today >= $genisletme_config['max_daily_entries']) ? 'disabled' : ''; ?>" data-lottery-type="genisletme" data-jeton-cost="<?php echo esc_attr($genisletme_config['cost']); ?>" <?php echo (!$genisletme_active || $jeton_balance < $genisletme_config['cost'] || $genisletme_entries_today >= $genisletme_config['max_daily_entries']) ? 'disabled' : ''; ?>>
                <?php if (!$genisletme_active) echo 'Çekiliş Aktif Değil'; elseif ($jeton_balance < $genisletme_config['cost']) echo 'Yetersiz Bilet'; elseif ($genisletme_entries_today >= $genisletme_config['max_daily_entries']) echo 'Günlük Limit Doldu'; else echo 'Çekilişe Katıl (' . esc_html($genisletme_config['cost']) . ' 🎟️)'; ?>
            </button>
            <?php if ($is_admin) : ?>
                <button class="btn-start-lottery" data-lottery-type="genisletme">🎲 Çekilişi Başlat</button>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div></main>
<?php get_footer(); ?>
