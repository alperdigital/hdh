<?php
/**
 * HDH: Lottery Admin Management
 * Comprehensive admin interface for managing lotteries
 */

if (!defined('ABSPATH')) exit;

/**
 * Render lottery admin page
 */
function hdh_render_lottery_admin_page() {
    if (!current_user_can('administrator')) {
        wp_die('Bu sayfaya erişim yetkiniz yok.');
    }
    
    // Handle form submissions
    $message = '';
    $message_type = 'success';
    
    // Handle lottery actions
    if (isset($_POST['hdh_lottery_action']) && check_admin_referer('hdh_lottery_admin', 'hdh_lottery_admin_nonce')) {
        $action = sanitize_text_field($_POST['hdh_lottery_action']);
        $lottery_type = isset($_POST['lottery_type']) ? sanitize_text_field($_POST['lottery_type']) : '';
        
        if (in_array($lottery_type, array('kurek', 'genisletme'))) {
            switch ($action) {
                case 'start':
                    if (function_exists('hdh_start_lottery_management')) {
                        $result = hdh_start_lottery_management($lottery_type);
                        $message = $result ? 'Çekiliş başarıyla başlatıldı!' : 'Çekiliş başlatılırken bir hata oluştu.';
                        $message_type = $result ? 'success' : 'error';
                    }
                    break;
                    
                case 'end':
                    if (function_exists('hdh_end_lottery_management')) {
                        $result = hdh_end_lottery_management($lottery_type);
                        $message = $result ? 'Çekiliş başarıyla sonlandırıldı!' : 'Çekiliş sonlandırılırken bir hata oluştu.';
                        $message_type = $result ? 'success' : 'error';
                    }
                    break;
                    
                case 'pause':
                    if (function_exists('hdh_pause_lottery_management')) {
                        $result = hdh_pause_lottery_management($lottery_type);
                        $message = $result ? 'Çekiliş duraklatıldı!' : 'Çekiliş duraklatılırken bir hata oluştu.';
                        $message_type = $result ? 'success' : 'error';
                    }
                    break;
                    
                case 'reset':
                    if (function_exists('hdh_reset_lottery_management')) {
                        $result = hdh_reset_lottery_management($lottery_type);
                        $message = $result ? 'Çekiliş sıfırlandı!' : 'Çekiliş sıfırlanırken bir hata oluştu.';
                        $message_type = $result ? 'success' : 'error';
                    }
                    break;
                    
                case 'draw_winner':
                    if (function_exists('hdh_start_lottery')) {
                        $result = hdh_start_lottery($lottery_type);
                        if ($result) {
                            $winner = get_userdata($result);
                            $message = 'Kazanan seçildi: ' . ($winner ? $winner->display_name : 'Bilinmiyor');
                            $message_type = 'success';
                        } else {
                            $message = 'Kazanan seçilirken bir hata oluştu.';
                            $message_type = 'error';
                        }
                    }
                    break;
                    
                case 'save_config':
                    if (function_exists('hdh_save_lottery_config')) {
                        $config = array(
                            'name' => isset($_POST['lottery_name']) ? sanitize_text_field($_POST['lottery_name']) : '',
                            'description' => isset($_POST['lottery_description']) ? sanitize_textarea_field($_POST['lottery_description']) : '',
                            'cost' => isset($_POST['lottery_cost']) ? absint($_POST['lottery_cost']) : 1,
                            'prize' => isset($_POST['lottery_prize']) ? sanitize_text_field($_POST['lottery_prize']) : '',
                            'max_daily_entries' => isset($_POST['max_daily_entries']) ? absint($_POST['max_daily_entries']) : 3,
                            'start_date' => isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '',
                            'end_date' => isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '',
                        );
                        $result = hdh_save_lottery_config($lottery_type, $config);
                        $message = $result ? 'Çekiliş ayarları kaydedildi!' : 'Ayarlar kaydedilirken bir hata oluştu.';
                        $message_type = $result ? 'success' : 'error';
                    }
                    break;
            }
        }
    }
    
    // Get lottery configs
    $kurek_config = function_exists('hdh_get_lottery_config') ? hdh_get_lottery_config('kurek') : array();
    $genisletme_config = function_exists('hdh_get_lottery_config') ? hdh_get_lottery_config('genisletme') : array();
    
    // Get participants
    $kurek_participants = function_exists('hdh_get_lottery_participants') ? hdh_get_lottery_participants('kurek') : array();
    $genisletme_participants = function_exists('hdh_get_lottery_participants') ? hdh_get_lottery_participants('genisletme') : array();
    
    // Get stats
    $kurek_total = function_exists('hdh_get_lottery_total_entries') ? hdh_get_lottery_total_entries('kurek') : 0;
    $genisletme_total = function_exists('hdh_get_lottery_total_entries') ? hdh_get_lottery_total_entries('genisletme') : 0;
    
    // Get status
    $kurek_status = function_exists('hdh_get_lottery_status_text') ? hdh_get_lottery_status_text('kurek') : 'Bilinmiyor';
    $genisletme_status = function_exists('hdh_get_lottery_status_text') ? hdh_get_lottery_status_text('genisletme') : 'Bilinmiyor';
    ?>
    <div class="wrap">
        <h1>🎲 Çekiliş Yönetimi</h1>
        
        <?php if ($message) : ?>
            <div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible">
                <p><?php echo esc_html($message); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="hdh-lottery-admin">
            <!-- 89 Kürek Çekilişi -->
            <div class="hdh-lottery-admin-card">
                <div class="hdh-lottery-admin-header">
                    <div>
                        <h2><?php echo esc_html($kurek_config['name'] ?? '89 Kürek Çekilişi'); ?></h2>
                        <p class="hdh-lottery-status">Durum: <strong><?php echo esc_html($kurek_status); ?></strong></p>
                    </div>
                    <span class="hdh-lottery-stats">Toplam Katılım: <?php echo esc_html($kurek_total); ?></span>
                </div>
                
                <!-- Configuration Form -->
                <div class="hdh-lottery-config-section">
                    <h3>⚙️ Çekiliş Ayarları</h3>
                    <form method="post" action="" class="hdh-lottery-config-form">
                        <?php wp_nonce_field('hdh_lottery_admin', 'hdh_lottery_admin_nonce'); ?>
                        <input type="hidden" name="hdh_lottery_action" value="save_config">
                        <input type="hidden" name="lottery_type" value="kurek">
                        
                        <table class="form-table">
                            <tr>
                                <th><label for="kurek_name">Çekiliş Adı</label></th>
                                <td><input type="text" id="kurek_name" name="lottery_name" value="<?php echo esc_attr($kurek_config['name'] ?? '89 Kürek Çekilişi'); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="kurek_description">Açıklama</label></th>
                                <td><textarea id="kurek_description" name="lottery_description" rows="3" class="large-text"><?php echo esc_textarea($kurek_config['description'] ?? '1 bilet ile katılabilirsiniz. Ödül: 89 Kürek'); ?></textarea></td>
                            </tr>
                            <tr>
                                <th><label for="kurek_cost">Bilet Maliyeti</label></th>
                                <td><input type="number" id="kurek_cost" name="lottery_cost" value="<?php echo esc_attr($kurek_config['cost'] ?? 1); ?>" min="1" class="small-text"></td>
                            </tr>
                            <tr>
                                <th><label for="kurek_prize">Ödül</label></th>
                                <td><input type="text" id="kurek_prize" name="lottery_prize" value="<?php echo esc_attr($kurek_config['prize'] ?? '89 Kürek'); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="kurek_max_entries">Günlük Maksimum Katılım</label></th>
                                <td><input type="number" id="kurek_max_entries" name="max_daily_entries" value="<?php echo esc_attr($kurek_config['max_daily_entries'] ?? 3); ?>" min="1" class="small-text"></td>
                            </tr>
                            <tr>
                                <th><label for="kurek_start_date">Başlangıç Tarihi</label></th>
                                <td><input type="datetime-local" id="kurek_start_date" name="start_date" value="<?php echo esc_attr($kurek_config['start_date'] ?? ''); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="kurek_end_date">Bitiş Tarihi</label></th>
                                <td><input type="datetime-local" id="kurek_end_date" name="end_date" value="<?php echo esc_attr($kurek_config['end_date'] ?? ''); ?>" class="regular-text"></td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary">💾 Ayarları Kaydet</button>
                        </p>
                    </form>
                </div>
                
                <!-- Participants Section -->
                <div class="hdh-lottery-participants-section">
                    <h3>👥 Katılan Çiftlikler (<?php echo esc_html(count($kurek_participants)); ?>)</h3>
                    <?php if (!empty($kurek_participants)) : ?>
                        <div class="hdh-lottery-participants-list">
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th>Sıra</th>
                                        <th>Çiftlik Adı</th>
                                        <th>Katılım Sayısı</th>
                                        <th>İlk Katılım Tarihi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($kurek_participants as $index => $participant) : ?>
                                        <tr>
                                            <td><?php echo esc_html($index + 1); ?></td>
                                            <td><strong><?php echo esc_html($participant['display_name']); ?></strong></td>
                                            <td><?php echo esc_html($participant['entry_count']); ?></td>
                                            <td><?php echo esc_html($participant['first_entry_date']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <p>Henüz katılım yok.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Action Buttons -->
                <div class="hdh-lottery-actions">
                    <form method="post" action="" style="display: inline;">
                        <?php wp_nonce_field('hdh_lottery_admin', 'hdh_lottery_admin_nonce'); ?>
                        <input type="hidden" name="hdh_lottery_action" value="start">
                        <input type="hidden" name="lottery_type" value="kurek">
                        <button type="submit" class="button button-primary">▶️ Çekilişi Başlat</button>
                    </form>
                    
                    <form method="post" action="" style="display: inline;">
                        <?php wp_nonce_field('hdh_lottery_admin', 'hdh_lottery_admin_nonce'); ?>
                        <input type="hidden" name="hdh_lottery_action" value="pause">
                        <input type="hidden" name="lottery_type" value="kurek">
                        <button type="submit" class="button">⏸️ Duraklat</button>
                    </form>
                    
                    <form method="post" action="" style="display: inline;">
                        <?php wp_nonce_field('hdh_lottery_admin', 'hdh_lottery_admin_nonce'); ?>
                        <input type="hidden" name="hdh_lottery_action" value="end">
                        <input type="hidden" name="lottery_type" value="kurek">
                        <button type="submit" class="button" onclick="return confirm('Çekilişi sonlandırmak istediğinize emin misiniz?');">⏹️ Sonlandır</button>
                    </form>
                    
                    <form method="post" action="" style="display: inline;">
                        <?php wp_nonce_field('hdh_lottery_admin', 'hdh_lottery_admin_nonce'); ?>
                        <input type="hidden" name="hdh_lottery_action" value="draw_winner">
                        <input type="hidden" name="lottery_type" value="kurek">
                        <button type="submit" class="button button-secondary" onclick="return confirm('Kazananı seçmek istediğinize emin misiniz?');">🎲 Kazananı Seç</button>
                    </form>
                    
                    <form method="post" action="" style="display: inline;">
                        <?php wp_nonce_field('hdh_lottery_admin', 'hdh_lottery_admin_nonce'); ?>
                        <input type="hidden" name="hdh_lottery_action" value="reset">
                        <input type="hidden" name="lottery_type" value="kurek">
                        <button type="submit" class="button button-link-delete" onclick="return confirm('Çekilişi sıfırlamak istediğinize emin misiniz? Tüm katılımlar silinecek!');">🔄 Sıfırla</button>
                    </form>
                </div>
            </div>
            
            <!-- 89 Genişletme/Ağıl Malzemesi Çekilişi -->
            <div class="hdh-lottery-admin-card">
                <div class="hdh-lottery-admin-header">
                    <div>
                        <h2><?php echo esc_html($genisletme_config['name'] ?? '89 Genişletme/Ağıl Malzemesi Çekilişi'); ?></h2>
                        <p class="hdh-lottery-status">Durum: <strong><?php echo esc_html($genisletme_status); ?></strong></p>
                    </div>
                    <span class="hdh-lottery-stats">Toplam Katılım: <?php echo esc_html($genisletme_total); ?></span>
                </div>
                
                <!-- Configuration Form -->
                <div class="hdh-lottery-config-section">
                    <h3>⚙️ Çekiliş Ayarları</h3>
                    <form method="post" action="" class="hdh-lottery-config-form">
                        <?php wp_nonce_field('hdh_lottery_admin', 'hdh_lottery_admin_nonce'); ?>
                        <input type="hidden" name="hdh_lottery_action" value="save_config">
                        <input type="hidden" name="lottery_type" value="genisletme">
                        
                        <table class="form-table">
                            <tr>
                                <th><label for="genisletme_name">Çekiliş Adı</label></th>
                                <td><input type="text" id="genisletme_name" name="lottery_name" value="<?php echo esc_attr($genisletme_config['name'] ?? '89 Genişletme/Ağıl Malzemesi Çekilişi'); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="genisletme_description">Açıklama</label></th>
                                <td><textarea id="genisletme_description" name="lottery_description" rows="3" class="large-text"><?php echo esc_textarea($genisletme_config['description'] ?? '5 bilet ile katılabilirsiniz. Ödül: 89 Genişletme/Ağıl Malzemesi'); ?></textarea></td>
                            </tr>
                            <tr>
                                <th><label for="genisletme_cost">Bilet Maliyeti</label></th>
                                <td><input type="number" id="genisletme_cost" name="lottery_cost" value="<?php echo esc_attr($genisletme_config['cost'] ?? 5); ?>" min="1" class="small-text"></td>
                            </tr>
                            <tr>
                                <th><label for="genisletme_prize">Ödül</label></th>
                                <td><input type="text" id="genisletme_prize" name="lottery_prize" value="<?php echo esc_attr($genisletme_config['prize'] ?? '89 Genişletme/Ağıl Malzemesi'); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="genisletme_max_entries">Günlük Maksimum Katılım</label></th>
                                <td><input type="number" id="genisletme_max_entries" name="max_daily_entries" value="<?php echo esc_attr($genisletme_config['max_daily_entries'] ?? 3); ?>" min="1" class="small-text"></td>
                            </tr>
                            <tr>
                                <th><label for="genisletme_start_date">Başlangıç Tarihi</label></th>
                                <td><input type="datetime-local" id="genisletme_start_date" name="start_date" value="<?php echo esc_attr($genisletme_config['start_date'] ?? ''); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="genisletme_end_date">Bitiş Tarihi</label></th>
                                <td><input type="datetime-local" id="genisletme_end_date" name="end_date" value="<?php echo esc_attr($genisletme_config['end_date'] ?? ''); ?>" class="regular-text"></td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary">💾 Ayarları Kaydet</button>
                        </p>
                    </form>
                </div>
                
                <!-- Participants Section -->
                <div class="hdh-lottery-participants-section">
                    <h3>👥 Katılan Çiftlikler (<?php echo esc_html(count($genisletme_participants)); ?>)</h3>
                    <?php if (!empty($genisletme_participants)) : ?>
                        <div class="hdh-lottery-participants-list">
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th>Sıra</th>
                                        <th>Çiftlik Adı</th>
                                        <th>Katılım Sayısı</th>
                                        <th>İlk Katılım Tarihi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($genisletme_participants as $index => $participant) : ?>
                                        <tr>
                                            <td><?php echo esc_html($index + 1); ?></td>
                                            <td><strong><?php echo esc_html($participant['display_name']); ?></strong></td>
                                            <td><?php echo esc_html($participant['entry_count']); ?></td>
                                            <td><?php echo esc_html($participant['first_entry_date']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <p>Henüz katılım yok.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Action Buttons -->
                <div class="hdh-lottery-actions">
                    <form method="post" action="" style="display: inline;">
                        <?php wp_nonce_field('hdh_lottery_admin', 'hdh_lottery_admin_nonce'); ?>
                        <input type="hidden" name="hdh_lottery_action" value="start">
                        <input type="hidden" name="lottery_type" value="genisletme">
                        <button type="submit" class="button button-primary">▶️ Çekilişi Başlat</button>
                    </form>
                    
                    <form method="post" action="" style="display: inline;">
                        <?php wp_nonce_field('hdh_lottery_admin', 'hdh_lottery_admin_nonce'); ?>
                        <input type="hidden" name="hdh_lottery_action" value="pause">
                        <input type="hidden" name="lottery_type" value="genisletme">
                        <button type="submit" class="button">⏸️ Duraklat</button>
                    </form>
                    
                    <form method="post" action="" style="display: inline;">
                        <?php wp_nonce_field('hdh_lottery_admin', 'hdh_lottery_admin_nonce'); ?>
                        <input type="hidden" name="hdh_lottery_action" value="end">
                        <input type="hidden" name="lottery_type" value="genisletme">
                        <button type="submit" class="button" onclick="return confirm('Çekilişi sonlandırmak istediğinize emin misiniz?');">⏹️ Sonlandır</button>
                    </form>
                    
                    <form method="post" action="" style="display: inline;">
                        <?php wp_nonce_field('hdh_lottery_admin', 'hdh_lottery_admin_nonce'); ?>
                        <input type="hidden" name="hdh_lottery_action" value="draw_winner">
                        <input type="hidden" name="lottery_type" value="genisletme">
                        <button type="submit" class="button button-secondary" onclick="return confirm('Kazananı seçmek istediğinize emin misiniz?');">🎲 Kazananı Seç</button>
                    </form>
                    
                    <form method="post" action="" style="display: inline;">
                        <?php wp_nonce_field('hdh_lottery_admin', 'hdh_lottery_admin_nonce'); ?>
                        <input type="hidden" name="hdh_lottery_action" value="reset">
                        <input type="hidden" name="lottery_type" value="genisletme">
                        <button type="submit" class="button button-link-delete" onclick="return confirm('Çekilişi sıfırlamak istediğinize emin misiniz? Tüm katılımlar silinecek!');">🔄 Sıfırla</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .hdh-lottery-admin {
        max-width: 1200px;
    }
    
    .hdh-lottery-admin-card {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .hdh-lottery-admin-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .hdh-lottery-admin-header h2 {
        margin: 0 0 8px 0;
        font-size: 24px;
        color: #23282d;
    }
    
    .hdh-lottery-status {
        margin: 0;
        font-size: 14px;
        color: #666;
    }
    
    .hdh-lottery-stats {
        font-size: 16px;
        font-weight: 600;
        color: var(--farm-green);
    }
    
    .hdh-lottery-config-section {
        margin-bottom: 24px;
        padding: 20px;
        background: #f9f9f9;
        border-radius: 6px;
    }
    
    .hdh-lottery-config-section h3 {
        margin-top: 0;
        font-size: 18px;
        color: #23282d;
    }
    
    .hdh-lottery-participants-section {
        margin-bottom: 24px;
    }
    
    .hdh-lottery-participants-section h3 {
        margin: 0 0 16px 0;
        font-size: 18px;
        color: #23282d;
    }
    
    .hdh-lottery-participants-list table {
        margin-top: 12px;
    }
    
    .hdh-lottery-participants-list th {
        font-weight: 600;
        background: #f9f9f9;
    }
    
    .hdh-lottery-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        padding-top: 20px;
        border-top: 1px solid #f0f0f0;
    }
    
    .hdh-lottery-actions form {
        margin: 0;
    }
    </style>
    <?php
}

/**
 * Start lottery (select winner)
 * 
 * @param string $lottery_type 'kurek' or 'genisletme'
 * @return bool|int Winner user ID or false on failure
 */
function hdh_start_lottery($lottery_type) {
    if (!current_user_can('administrator')) {
        return false;
    }
    
    if (!in_array($lottery_type, array('kurek', 'genisletme'))) {
        return false;
    }
    
    // Get all participants
    $participants = hdh_get_lottery_participants($lottery_type);
    
    if (empty($participants)) {
        return false;
    }
    
    // Create weighted array (users with more entries have higher chance)
    $weighted_participants = array();
    foreach ($participants as $participant) {
        for ($i = 0; $i < $participant['entry_count']; $i++) {
            $weighted_participants[] = $participant['user_id'];
        }
    }
    
    // Select random winner
    $winner_index = array_rand($weighted_participants);
    $winner_id = $weighted_participants[$winner_index];
    
    // Save winner to lottery results
    $lottery_results = get_option('hdh_lottery_results', array());
    if (!is_array($lottery_results)) {
        $lottery_results = array();
    }
    
    $lottery_results[] = array(
        'lottery_type' => $lottery_type,
        'winner_id' => $winner_id,
        'winner_name' => get_userdata($winner_id)->display_name,
        'drawn_at' => current_time('mysql'),
        'total_participants' => count($participants),
        'total_entries' => count($weighted_participants),
    );
    
    update_option('hdh_lottery_results', $lottery_results);
    
    // Log event
    if (function_exists('hdh_log_event')) {
        hdh_log_event($winner_id, 'lottery_won', array(
            'lottery_type' => $lottery_type,
            'drawn_at' => current_time('mysql'),
        ));
    }
    
    return $winner_id;
}
