<?php
/**
 * HDH: Chat Admin Panel
 * Admin controls for chat, moderation, and rate limits
 */

if (!defined('ABSPATH')) exit;

/**
 * Render chat admin page
 */
function hdh_render_chat_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Bu sayfaya erişim yetkiniz yok.');
    }
    
    // Handle form submission
    if (isset($_POST['hdh_save_chat_settings']) && check_admin_referer('hdh_save_chat_settings')) {
        hdh_save_chat_settings();
        echo '<div class="notice notice-success"><p>Ayarlar kaydedildi!</p></div>';
    }
    
    // Get current tab
    $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'settings';
    
    ?>
    <div class="wrap">
        <h1>Chat Yönetimi</h1>
        
        <!-- Tabs -->
        <nav class="nav-tab-wrapper">
            <a href="?page=hdh-chat&tab=settings" class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                Ayarlar
            </a>
            <a href="?page=hdh-chat&tab=moderation" class="nav-tab <?php echo $current_tab === 'moderation' ? 'nav-tab-active' : ''; ?>">
                Moderation Queue
            </a>
            <?php if (function_exists('hdh_get_trade_reports')) : ?>
            <a href="?page=hdh-chat&tab=trade-reports" class="nav-tab <?php echo $current_tab === 'trade-reports' ? 'nav-tab-active' : ''; ?>">
                Trade Reports
            </a>
            <?php endif; ?>
        </nav>
        
        <div class="hdh-chat-admin-content">
            <?php if ($current_tab === 'settings') : ?>
                <?php hdh_render_chat_settings_tab(); ?>
            <?php elseif ($current_tab === 'moderation') : ?>
                <?php hdh_render_chat_moderation_tab(); ?>
            <?php elseif ($current_tab === 'trade-reports' && function_exists('hdh_get_trade_reports')) : ?>
                <?php hdh_render_trade_reports_tab(); ?>
            <?php elseif ($current_tab === 'trade-reports') : ?>
                <p>Trade Reports sistemi şu anda mevcut değil.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Render chat settings tab
 */
function hdh_render_chat_settings_tab() {
    $chat_enabled = get_option('hdh_chat_enabled', true);
    $pre_login_behavior = get_option('hdh_chat_pre_login_behavior', 'show_blurred');
    $post_login_behavior = get_option('hdh_chat_post_login_behavior', 'allow_posting');
    
    // Moderation settings
    $filter_profanity = get_option('hdh_chat_filter_profanity', true);
    $filter_insults = get_option('hdh_chat_filter_insults', true);
    $filter_links = get_option('hdh_chat_filter_links', true);
    $filter_phone = get_option('hdh_chat_filter_phone', true);
    $filter_email = get_option('hdh_chat_filter_email', true);
    $action_on_violation = get_option('hdh_chat_action_on_violation', 'censor');
    
    // Warning thresholds
    $third_strike_mute = get_option('hdh_chat_3rd_strike_mute_minutes', 10);
    $fifth_strike_mute = get_option('hdh_chat_5th_strike_mute_hours', 24);
    
    // Rate limits
    $messages_per_minute = get_option('hdh_chat_messages_per_minute', 3);
    $cooldown_seconds = get_option('hdh_chat_cooldown_seconds', 20);
    $max_message_length = get_option('hdh_chat_max_message_length', 200);
    
    // Slow mode
    $slow_mode_level_5 = get_option('hdh_chat_slow_mode_level_5', 5);
    $slow_mode_level_10 = get_option('hdh_chat_slow_mode_level_10', 10);
    $slow_mode_cooldown_5 = get_option('hdh_chat_slow_mode_cooldown_5', 60);
    $slow_mode_cooldown_10 = get_option('hdh_chat_slow_mode_cooldown_10', 30);
    
    ?>
    <form method="post" action="">
        <?php wp_nonce_field('hdh_save_chat_settings'); ?>
        
        <h2>Core Toggles</h2>
        <table class="form-table">
            <tr>
                <th scope="row">Chat'i Etkinleştir</th>
                <td>
                    <label>
                        <input type="checkbox" name="hdh_chat_enabled" value="1" <?php checked($chat_enabled, true); ?>>
                        Lobby chat'i etkinleştir
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">Pre-login Davranışı</th>
                <td>
                    <select name="hdh_chat_pre_login_behavior">
                        <option value="show_blurred" <?php selected($pre_login_behavior, 'show_blurred'); ?>>Blurred mesajlar göster</option>
                        <option value="show_locked" <?php selected($pre_login_behavior, 'show_locked'); ?>>Kilitli görünüm göster</option>
                        <option value="hide" <?php selected($pre_login_behavior, 'hide'); ?>>Gizle</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">Post-login Davranışı</th>
                <td>
                    <select name="hdh_chat_post_login_behavior">
                        <option value="allow_posting" <?php selected($post_login_behavior, 'allow_posting'); ?>>Mesaj göndermeye izin ver</option>
                        <option value="read_only" <?php selected($post_login_behavior, 'read_only'); ?>>Sadece okuma modu</option>
                    </select>
                </td>
            </tr>
        </table>
        
        <h2>Moderation Settings</h2>
        <table class="form-table">
            <tr>
                <th scope="row">Filtreleri Etkinleştir</th>
                <td>
                    <label><input type="checkbox" name="hdh_chat_filter_profanity" value="1" <?php checked($filter_profanity, true); ?>> Küfür</label><br>
                    <label><input type="checkbox" name="hdh_chat_filter_insults" value="1" <?php checked($filter_insults, true); ?>> Hakaret</label><br>
                    <label><input type="checkbox" name="hdh_chat_filter_links" value="1" <?php checked($filter_links, true); ?>> Linkler</label><br>
                    <label><input type="checkbox" name="hdh_chat_filter_phone" value="1" <?php checked($filter_phone, true); ?>> Telefon numaraları</label><br>
                    <label><input type="checkbox" name="hdh_chat_filter_email" value="1" <?php checked($filter_email, true); ?>> E-posta adresleri</label>
                </td>
            </tr>
            <tr>
                <th scope="row">İhlal Durumunda Aksiyon</th>
                <td>
                    <select name="hdh_chat_action_on_violation">
                        <option value="censor" <?php selected($action_on_violation, 'censor'); ?>>Censor (*** ile değiştir)</option>
                        <option value="block" <?php selected($action_on_violation, 'block'); ?>>Block (mesajı engelle)</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">Warning Thresholds</th>
                <td>
                    <label>
                        3. strike: <input type="number" name="hdh_chat_3rd_strike_mute_minutes" value="<?php echo esc_attr($third_strike_mute); ?>" min="1" max="1440"> dakika mute
                    </label><br>
                    <label>
                        5. strike: <input type="number" name="hdh_chat_5th_strike_mute_hours" value="<?php echo esc_attr($fifth_strike_mute); ?>" min="1" max="168"> saat mute
                    </label>
                </td>
            </tr>
        </table>
        
        <h2>Rate Limits</h2>
        <table class="form-table">
            <tr>
                <th scope="row">Dakikada Mesaj Sayısı</th>
                <td>
                    <input type="number" name="hdh_chat_messages_per_minute" value="<?php echo esc_attr($messages_per_minute); ?>" min="1" max="10">
                </td>
            </tr>
            <tr>
                <th scope="row">Mesajlar Arası Cooldown (saniye)</th>
                <td>
                    <input type="number" name="hdh_chat_cooldown_seconds" value="<?php echo esc_attr($cooldown_seconds); ?>" min="1" max="300">
                </td>
            </tr>
            <tr>
                <th scope="row">Maksimum Mesaj Uzunluğu</th>
                <td>
                    <input type="number" name="hdh_chat_max_message_length" value="<?php echo esc_attr($max_message_length); ?>" min="50" max="1000">
                </td>
            </tr>
        </table>
        
        <h2>Slow Mode (Yeni/Düşük Seviye Kullanıcılar)</h2>
        <table class="form-table">
            <tr>
                <th scope="row">Seviye < 5 Cooldown (saniye)</th>
                <td>
                    <input type="number" name="hdh_chat_slow_mode_cooldown_5" value="<?php echo esc_attr($slow_mode_cooldown_5); ?>" min="1" max="300">
                </td>
            </tr>
            <tr>
                <th scope="row">Seviye 5-10 Cooldown (saniye)</th>
                <td>
                    <input type="number" name="hdh_chat_slow_mode_cooldown_10" value="<?php echo esc_attr($slow_mode_cooldown_10); ?>" min="1" max="300">
                </td>
            </tr>
        </table>
        
        <?php submit_button('Ayarları Kaydet', 'primary', 'hdh_save_chat_settings'); ?>
    </form>
    <?php
}

/**
 * Save chat settings
 */
function hdh_save_chat_settings() {
    // Core toggles
    update_option('hdh_chat_enabled', isset($_POST['hdh_chat_enabled']));
    update_option('hdh_chat_pre_login_behavior', sanitize_text_field($_POST['hdh_chat_pre_login_behavior'] ?? 'show_blurred'));
    update_option('hdh_chat_post_login_behavior', sanitize_text_field($_POST['hdh_chat_post_login_behavior'] ?? 'allow_posting'));
    
    // Moderation filters
    update_option('hdh_chat_filter_profanity', isset($_POST['hdh_chat_filter_profanity']));
    update_option('hdh_chat_filter_insults', isset($_POST['hdh_chat_filter_insults']));
    update_option('hdh_chat_filter_links', isset($_POST['hdh_chat_filter_links']));
    update_option('hdh_chat_filter_phone', isset($_POST['hdh_chat_filter_phone']));
    update_option('hdh_chat_filter_email', isset($_POST['hdh_chat_filter_email']));
    update_option('hdh_chat_action_on_violation', sanitize_text_field($_POST['hdh_chat_action_on_violation'] ?? 'censor'));
    
    // Warning thresholds
    update_option('hdh_chat_3rd_strike_mute_minutes', absint($_POST['hdh_chat_3rd_strike_mute_minutes'] ?? 10));
    update_option('hdh_chat_5th_strike_mute_hours', absint($_POST['hdh_chat_5th_strike_mute_hours'] ?? 24));
    
    // Rate limits
    update_option('hdh_chat_messages_per_minute', absint($_POST['hdh_chat_messages_per_minute'] ?? 3));
    update_option('hdh_chat_cooldown_seconds', absint($_POST['hdh_chat_cooldown_seconds'] ?? 20));
    update_option('hdh_chat_max_message_length', absint($_POST['hdh_chat_max_message_length'] ?? 200));
    
    // Slow mode
    update_option('hdh_chat_slow_mode_cooldown_5', absint($_POST['hdh_chat_slow_mode_cooldown_5'] ?? 60));
    update_option('hdh_chat_slow_mode_cooldown_10', absint($_POST['hdh_chat_slow_mode_cooldown_10'] ?? 30));
}

/**
 * Render moderation queue tab
 */
function hdh_render_chat_moderation_tab() {
    global $wpdb;
    $messages_table = $wpdb->prefix . 'hdh_chat_messages';
    $warnings_table = $wpdb->prefix . 'hdh_chat_warnings';
    
    // Get reported messages (if report system exists)
    // For now, show messages with warnings
    $warned_messages = $wpdb->get_results(
        "SELECT m.*, w.strike_count, w.warning_type
         FROM {$messages_table} m
         INNER JOIN {$warnings_table} w ON m.id = w.message_id
         WHERE m.status IN ('censored', 'blocked')
         ORDER BY m.created_at DESC
         LIMIT 50",
        ARRAY_A
    );
    
    // Get users with warnings
    $users_with_warnings = $wpdb->get_results(
        "SELECT user_id, SUM(strike_count) as total_strikes, COUNT(*) as warning_count
         FROM {$warnings_table}
         GROUP BY user_id
         HAVING total_strikes >= 3
         ORDER BY total_strikes DESC
         LIMIT 50",
        ARRAY_A
    );
    
    ?>
    <div class="hdh-moderation-queue">
        <h2>Users with Warnings</h2>
        <?php if (empty($users_with_warnings)) : ?>
            <p>Henüz uyarı alan kullanıcı yok.</p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Kullanıcı</th>
                        <th>Toplam Strike</th>
                        <th>Uyarı Sayısı</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users_with_warnings as $user_warning) : 
                        $user = get_userdata($user_warning['user_id']);
                        $user_name = $user ? $user->display_name : 'Silinmiş Kullanıcı';
                    ?>
                        <tr>
                            <td><?php echo esc_html($user_name); ?></td>
                            <td><?php echo esc_html($user_warning['total_strikes']); ?></td>
                            <td><?php echo esc_html($user_warning['warning_count']); ?></td>
                            <td>
                                <a href="?page=hdh-chat&tab=moderation&action=mute&user_id=<?php echo esc_attr($user_warning['user_id']); ?>" class="button">Mute</a>
                                <a href="?page=hdh-chat&tab=moderation&action=ban&user_id=<?php echo esc_attr($user_warning['user_id']); ?>" class="button">Ban</a>
                                <a href="?page=hdh-chat&tab=moderation&action=reset_warnings&user_id=<?php echo esc_attr($user_warning['user_id']); ?>" class="button" onclick="return confirm('Uyarıları sıfırlamak istediğinize emin misiniz?');">Uyarıları Sıfırla</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <h2>Filter-Triggered Messages</h2>
        <?php if (empty($warned_messages)) : ?>
            <p>Henüz filtrelenmiş mesaj yok.</p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Kullanıcı</th>
                        <th>Mesaj</th>
                        <th>Durum</th>
                        <th>Uyarı Tipi</th>
                        <th>Tarih</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($warned_messages as $msg) : 
                        $user = get_userdata($msg['user_id']);
                        $user_name = $user ? $user->display_name : 'Silinmiş Kullanıcı';
                        $flags = !empty($msg['moderation_flags']) ? json_decode($msg['moderation_flags'], true) : array();
                    ?>
                        <tr>
                            <td><?php echo esc_html($user_name); ?></td>
                            <td><?php echo esc_html(mb_substr($msg['message'], 0, 50)); ?>...</td>
                            <td><?php echo esc_html($msg['status']); ?></td>
                            <td><?php echo esc_html(implode(', ', $flags ?: array())); ?></td>
                            <td><?php echo esc_html($msg['created_at']); ?></td>
                            <td>
                                <a href="?page=hdh-chat&tab=moderation&action=delete_message&message_id=<?php echo esc_attr($msg['id']); ?>" class="button" onclick="return confirm('Mesajı silmek istediğinize emin misiniz?');">Sil</a>
                                <a href="?page=hdh-chat&tab=moderation&action=approve_message&message_id=<?php echo esc_attr($msg['id']); ?>" class="button">Onayla</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <?php
    // Handle actions
    if (isset($_GET['action'])) {
        hdh_handle_chat_moderation_action();
    }
}

/**
 * Handle moderation actions
 */
function hdh_handle_chat_moderation_action() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $action = sanitize_text_field($_GET['action'] ?? '');
    $user_id = isset($_GET['user_id']) ? absint($_GET['user_id']) : 0;
    $message_id = isset($_GET['message_id']) ? absint($_GET['message_id']) : 0;
    
    switch ($action) {
        case 'mute':
            if ($user_id) {
                $minutes = 60; // Default 1 hour
                $mute_until = date('Y-m-d H:i:s', current_time('timestamp') + ($minutes * 60));
                update_user_meta($user_id, 'hdh_chat_muted_until', $mute_until);
                echo '<div class="notice notice-success"><p>Kullanıcı mute edildi.</p></div>';
            }
            break;
            
        case 'ban':
            if ($user_id) {
                update_user_meta($user_id, 'hdh_chat_banned', true);
                echo '<div class="notice notice-success"><p>Kullanıcı banlandı.</p></div>';
            }
            break;
            
        case 'reset_warnings':
            if ($user_id) {
                global $wpdb;
                $warnings_table = $wpdb->prefix . 'hdh_chat_warnings';
                $wpdb->delete($warnings_table, array('user_id' => $user_id), array('%d'));
                delete_user_meta($user_id, 'hdh_chat_muted_until');
                echo '<div class="notice notice-success"><p>Uyarılar sıfırlandı.</p></div>';
            }
            break;
            
        case 'delete_message':
            if ($message_id) {
                hdh_delete_chat_message($message_id, null, true);
                echo '<div class="notice notice-success"><p>Mesaj silindi.</p></div>';
            }
            break;
            
        case 'approve_message':
            if ($message_id) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'hdh_chat_messages';
                $wpdb->update(
                    $table_name,
                    array('status' => 'published'),
                    array('id' => $message_id),
                    array('%s'),
                    array('%d')
                );
                echo '<div class="notice notice-success"><p>Mesaj onaylandı.</p></div>';
            }
            break;
    }
}

/**
 * Render trade reports tab
 */
function hdh_render_trade_reports_tab() {
    // Check if trade report system is available first
    if (!function_exists('hdh_get_trade_reports')) {
        echo '<p>Rapor sistemi mevcut değil.</p>';
        return;
    }
    
    // Handle actions
    if (isset($_GET['action']) && isset($_GET['report_id']) && check_admin_referer('hdh_trade_report_action')) {
        if (function_exists('hdh_handle_trade_report_action')) {
            hdh_handle_trade_report_action();
        }
    }
    
    $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'pending';
    
    $reports = hdh_get_trade_reports($status, 50);
    
    ?>
    <div class="hdh-trade-reports-queue">
        <h2>Trade Reports</h2>
        
        <!-- Status Filter -->
        <div class="hdh-reports-filter" style="margin-bottom: 20px;">
            <a href="?page=hdh-chat&tab=trade-reports&status=pending" class="button <?php echo $status === 'pending' ? 'button-primary' : ''; ?>">
                Bekleyen (<?php echo count(array_filter($reports, function($r) { return $r['status'] === 'pending'; })); ?>)
            </a>
            <a href="?page=hdh-chat&tab=trade-reports&status=reviewed" class="button <?php echo $status === 'reviewed' ? 'button-primary' : ''; ?>">
                İncelenen
            </a>
            <a href="?page=hdh-chat&tab=trade-reports&status=resolved" class="button <?php echo $status === 'resolved' ? 'button-primary' : ''; ?>">
                Çözülen
            </a>
        </div>
        
        <?php if (empty($reports)) : ?>
            <p>Bu kategoride rapor yok.</p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Hediyeleşme</th>
                        <th>Raporlayan</th>
                        <th>Raporlanan</th>
                        <th>Sorun Tipi</th>
                        <th>Açıklama</th>
                        <th>Tarih</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $report) : ?>
                        <tr>
                            <td>
                                <?php if (isset($report['listing_id']) && $report['listing_id']) : ?>
                                    <a href="<?php echo esc_url(get_permalink($report['listing_id'])); ?>" target="_blank">
                                        <?php echo esc_html($report['listing_title'] ?? 'İlan'); ?>
                                    </a>
                                <?php else : ?>
                                    <?php echo esc_html($report['listing_title'] ?? 'İlan'); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($report['reporter_name'] ?? 'Bilinmeyen'); ?></td>
                            <td><?php echo esc_html($report['reported_name'] ?? 'Bilinmeyen'); ?></td>
                            <td><?php echo esc_html($report['issue_type_label'] ?? $report['issue_type'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if (!empty($report['description'])) : ?>
                                    <span title="<?php echo esc_attr($report['description']); ?>">
                                        <?php echo esc_html(mb_substr($report['description'], 0, 50)); ?>
                                        <?php echo mb_strlen($report['description']) > 50 ? '...' : ''; ?>
                                    </span>
                                <?php else : ?>
                                    <em>Yok</em>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($report['created_at']))); ?></td>
                            <td>
                                <?php if ($report['status'] === 'pending') : ?>
                                    <a href="?page=hdh-chat&tab=trade-reports&action=review&report_id=<?php echo esc_attr($report['id']); ?>&_wpnonce=<?php echo wp_create_nonce('hdh_trade_report_action'); ?>" class="button button-small">
                                        İncele
                                    </a>
                                    <a href="?page=hdh-chat&tab=trade-reports&action=resolve&report_id=<?php echo esc_attr($report['id']); ?>&_wpnonce=<?php echo wp_create_nonce('hdh_trade_report_action'); ?>" class="button button-small button-primary">
                                        Çöz
                                    </a>
                                    <a href="?page=hdh-chat&tab=trade-reports&action=dismiss&report_id=<?php echo esc_attr($report['id']); ?>&_wpnonce=<?php echo wp_create_nonce('hdh_trade_report_action'); ?>" class="button button-small" onclick="return confirm('Bu raporu reddetmek istediğinize emin misiniz?');">
                                        Reddet
                                    </a>
                                <?php else : ?>
                                    <span class="description">
                                        <?php echo $report['status'] === 'reviewed' ? 'İncelendi' : 'Çözüldü'; ?>
                                        <?php if (!empty($report['reviewed_at'])) : ?>
                                            <br><?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($report['reviewed_at']))); ?>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Handle trade report action
 */
function hdh_handle_trade_report_action() {
    if (!current_user_can('manage_options')) {
        wp_die('Yetkiniz yok.');
    }
    
    // Check if trade report system is available
    if (!function_exists('hdh_update_trade_report_status')) {
        wp_die('Trade report sistemi mevcut değil.');
    }
    
    $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
    $report_id = isset($_GET['report_id']) ? absint($_GET['report_id']) : 0;
    
    if (!$report_id || !$action) {
        return;
    }
    
    $status = '';
    $admin_note = '';
    
    switch ($action) {
        case 'review':
            $status = 'reviewed';
            $admin_note = 'İncelendi';
            break;
        case 'resolve':
            $status = 'resolved';
            $admin_note = 'Çözüldü';
            break;
        case 'dismiss':
            $status = 'reviewed';
            $admin_note = 'Reddedildi (Yanlış rapor)';
            break;
        default:
            return;
    }
    
    $result = hdh_update_trade_report_status($report_id, $status, $admin_note);
    
    if (!is_wp_error($result)) {
        wp_redirect(add_query_arg(array(
            'page' => 'hdh-chat',
            'tab' => 'trade-reports',
            'status' => $status,
            'updated' => '1',
        ), admin_url('admin.php')));
        exit;
    }
}
