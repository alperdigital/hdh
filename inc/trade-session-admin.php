<?php
/**
 * HDH: Trade Session Admin Panel
 * Dispute management for administrators
 */

if (!defined('ABSPATH')) exit;

/**
 * Add admin menu for trade session disputes
 */
function hdh_add_trade_session_admin_menu() {
    add_submenu_page(
        'hdh-dashboard',
        'Hediyeleşme Anlaşmazlıkları',
        'Anlaşmazlıklar',
        'manage_options',
        'hdh-trade-disputes',
        'hdh_render_trade_disputes_page'
    );
}
add_action('admin_menu', 'hdh_add_trade_session_admin_menu', 20);

/**
 * Render disputes admin page
 */
function hdh_render_trade_disputes_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Bu sayfaya erişim yetkiniz yok.');
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_sessions';
    
    // Handle dispute resolution
    if (isset($_POST['resolve_dispute']) && check_admin_referer('hdh_resolve_dispute', 'hdh_resolve_dispute_nonce')) {
        $session_id = isset($_POST['session_id']) ? absint($_POST['session_id']) : 0;
        $action = isset($_POST['resolution_action']) ? sanitize_text_field($_POST['resolution_action']) : 'resolved';
        $note = isset($_POST['resolution_note']) ? sanitize_textarea_field($_POST['resolution_note']) : '';
        
        if ($session_id) {
            $result = hdh_resolve_trade_dispute($session_id, $note, $action);
            if (!is_wp_error($result)) {
                echo '<div class="notice notice-success"><p>Anlaşmazlık çözüldü.</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
            }
        }
    }
    
    // Get disputed sessions
    $disputes = $wpdb->get_results(
        "SELECT * FROM $table_name WHERE status = 'DISPUTED' ORDER BY dispute_created_at DESC",
        ARRAY_A
    );
    
    ?>
    <div class="wrap">
        <h1>Hediyeleşme Anlaşmazlıkları</h1>
        
        <?php if (empty($disputes)) : ?>
            <div class="notice notice-info">
                <p>Şu anda bekleyen anlaşmazlık yok.</p>
            </div>
        <?php else : ?>
            <div class="hdh-disputes-list">
                <?php foreach ($disputes as $dispute) : 
                    $owner = get_userdata($dispute['owner_user_id']);
                    $starter = get_userdata($dispute['starter_user_id']);
                    $listing = get_post($dispute['listing_id']);
                    
                    $dispute_reasons = array(
                        'friend_request_not_accepted' => 'Arkadaşlık isteği kabul edilmedi',
                        'gift_not_received' => 'Hediye gönderildi ama alınmadı',
                        'wrong_gift' => 'Hediye yanlış/eksik geldi',
                        'other_not_completing' => 'Karşı taraf adımı tamamlamıyor',
                        'other' => 'Diğer',
                    );
                ?>
                    <div class="hdh-dispute-card">
                        <div class="dispute-header">
                            <div class="dispute-info">
                                <h3>Anlaşmazlık #<?php echo esc_html($dispute['id']); ?></h3>
                                <p class="dispute-meta">
                                    <strong>İlan:</strong> <?php echo esc_html($listing ? $listing->post_title : 'Silinmiş'); ?> (ID: <?php echo esc_html($dispute['listing_id']); ?>)<br>
                                    <strong>İlan Sahibi:</strong> <?php echo esc_html($owner ? $owner->display_name : 'Bilinmiyor'); ?> (ID: <?php echo esc_html($dispute['owner_user_id']); ?>)<br>
                                    <strong>Başlatan:</strong> <?php echo esc_html($starter ? $starter->display_name : 'Bilinmiyor'); ?> (ID: <?php echo esc_html($dispute['starter_user_id']); ?>)<br>
                                    <strong>Tarih:</strong> <?php echo esc_html($dispute['dispute_created_at']); ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="dispute-details">
                            <div class="dispute-detail-row">
                                <strong>Sebep:</strong>
                                <span><?php echo esc_html($dispute_reasons[$dispute['dispute_reason']] ?? $dispute['dispute_reason']); ?></span>
                            </div>
                            <div class="dispute-detail-row">
                                <strong>Açıklama:</strong>
                                <p><?php echo esc_html($dispute['dispute_text']); ?></p>
                            </div>
                            
                            <div class="dispute-progress">
                                <strong>İlerleme:</strong>
                                <ul class="dispute-steps-list">
                                    <li><?php echo $dispute['step1_starter_done_at'] ? '✅' : '⏳'; ?> Adım 1: Arkadaş olarak ekle</li>
                                    <li><?php echo $dispute['step2_owner_done_at'] ? '✅' : '⏳'; ?> Adım 2: İstek kabul edildi</li>
                                    <li><?php echo $dispute['step3_starter_done_at'] ? '✅' : '⏳'; ?> Adım 3: Sen hediyeni hazırla</li>
                                    <li><?php echo $dispute['step4_owner_done_at'] ? '✅' : '⏳'; ?> Adım 4: İlan sahibi hediyeni aldı</li>
                                    <li><?php echo $dispute['step5_starter_done_at'] ? '✅' : '⏳'; ?> Adım 5: Sen hediyeni aldın</li>
                                </ul>
                            </div>
                        </div>
                        
                        <form method="post" action="" class="dispute-resolution-form">
                            <?php wp_nonce_field('hdh_resolve_dispute', 'hdh_resolve_dispute_nonce'); ?>
                            <input type="hidden" name="session_id" value="<?php echo esc_attr($dispute['id']); ?>">
                            
                            <div class="form-group">
                                <label for="resolution_action_<?php echo esc_attr($dispute['id']); ?>">Çözüm:</label>
                                <select name="resolution_action" id="resolution_action_<?php echo esc_attr($dispute['id']); ?>" required>
                                    <option value="resolved">Çözüldü (Tamamlandı olarak işaretle)</option>
                                    <option value="cancelled">İptal Edildi</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="resolution_note_<?php echo esc_attr($dispute['id']); ?>">Not:</label>
                                <textarea name="resolution_note" id="resolution_note_<?php echo esc_attr($dispute['id']); ?>" rows="3" class="large-text"></textarea>
                            </div>
                            
                            <button type="submit" name="resolve_dispute" class="button button-primary">Çöz</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <style>
    .hdh-disputes-list {
        display: flex;
        flex-direction: column;
        gap: 20px;
        margin-top: 20px;
    }
    
    .hdh-dispute-card {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .dispute-header {
        margin-bottom: 16px;
        padding-bottom: 16px;
        border-bottom: 1px solid #eee;
    }
    
    .dispute-header h3 {
        margin: 0 0 8px 0;
        font-size: 1.2rem;
    }
    
    .dispute-meta {
        margin: 0;
        font-size: 0.9rem;
        color: #666;
        line-height: 1.6;
    }
    
    .dispute-details {
        margin-bottom: 20px;
    }
    
    .dispute-detail-row {
        margin-bottom: 12px;
    }
    
    .dispute-detail-row strong {
        display: block;
        margin-bottom: 4px;
        color: #333;
    }
    
    .dispute-detail-row p {
        margin: 4px 0 0 0;
        color: #666;
        line-height: 1.5;
    }
    
    .dispute-progress {
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid #eee;
    }
    
    .dispute-steps-list {
        margin: 8px 0 0 0;
        padding-left: 20px;
    }
    
    .dispute-steps-list li {
        margin-bottom: 4px;
        color: #666;
    }
    
    .dispute-resolution-form {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 2px solid #eee;
    }
    
    .dispute-resolution-form .form-group {
        margin-bottom: 16px;
    }
    
    .dispute-resolution-form label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
    }
    
    .dispute-resolution-form select,
    .dispute-resolution-form textarea {
        width: 100%;
        max-width: 600px;
    }
    </style>
    <?php
}

