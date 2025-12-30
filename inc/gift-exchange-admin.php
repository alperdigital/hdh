<?php
/**
 * HDH: Gift Exchange Admin Panel
 * Admin controls for gift exchange disputes
 */

if (!defined('ABSPATH')) exit;

/**
 * Add admin menu for gift exchange disputes
 */
function hdh_add_gift_exchange_admin_menu() {
    add_submenu_page(
        'hdh-dashboard',
        'Hediyeleşme Şikayetleri',
        'Hediyeleşme Şikayetleri',
        'manage_options',
        'hdh-gift-disputes',
        'hdh_render_gift_disputes_page'
    );
}
add_action('admin_menu', 'hdh_add_gift_exchange_admin_menu', 20);

/**
 * Render disputes admin page
 */
function hdh_render_gift_disputes_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Bu sayfaya erişim yetkiniz yok.');
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_gift_exchanges';
    
    // Handle penalty application
    if (isset($_POST['apply_penalty']) && check_admin_referer('hdh_apply_penalty', 'hdh_apply_penalty_nonce')) {
        $exchange_id = isset($_POST['exchange_id']) ? absint($_POST['exchange_id']) : 0;
        $reporter_penalty = isset($_POST['reporter_penalty']) ? sanitize_text_field($_POST['reporter_penalty']) : '';
        $reporter_penalty_amount = isset($_POST['reporter_penalty_amount']) ? absint($_POST['reporter_penalty_amount']) : 0;
        $reported_penalty = isset($_POST['reported_penalty']) ? sanitize_text_field($_POST['reported_penalty']) : '';
        $reported_penalty_amount = isset($_POST['reported_penalty_amount']) ? absint($_POST['reported_penalty_amount']) : 0;
        $admin_note = isset($_POST['admin_note']) ? sanitize_textarea_field($_POST['admin_note']) : '';
        
        if ($exchange_id) {
            if (function_exists('hdh_apply_gift_exchange_penalty')) {
                $result = hdh_apply_gift_exchange_penalty(
                    $exchange_id,
                    $reporter_penalty,
                    $reporter_penalty_amount,
                    $reported_penalty,
                    $reported_penalty_amount,
                    $admin_note
                );
                if (!is_wp_error($result)) {
                    echo '<div class="notice notice-success"><p>Cezalar uygulandı.</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
                }
            } else {
                echo '<div class="notice notice-error"><p>Ceza verme fonksiyonu bulunamadı.</p></div>';
            }
        }
    }
    
    // Get disputed exchanges
    $disputes = $wpdb->get_results(
        "SELECT * FROM $table_name WHERE status = 'DISPUTED' ORDER BY reported_at DESC",
        ARRAY_A
    );
    
    ?>
    <div class="wrap">
        <h1>Hediyeleşme Şikayetleri</h1>
        
        <?php if (empty($disputes)) : ?>
            <p>Henüz şikayet bulunmamaktadır.</p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Şikayet Eden</th>
                        <th>Şikayet Edilen</th>
                        <th>İlan</th>
                        <th>Şikayet Nedeni</th>
                        <th>Şikayet Tarihi</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($disputes as $dispute) : 
                        $reporter = get_userdata($dispute['reported_by_user_id']);
                        $owner = get_userdata($dispute['owner_user_id']);
                        $offerer = get_userdata($dispute['offerer_user_id']);
                        $reported_user = ($dispute['reported_by_user_id'] == $dispute['owner_user_id']) ? $offerer : $owner;
                        $listing = get_post($dispute['listing_id']);
                    ?>
                        <tr>
                            <td><?php echo esc_html($dispute['id']); ?></td>
                            <td>
                                <?php if ($reporter) : ?>
                                    <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $reporter->ID)); ?>">
                                        <?php echo esc_html($reporter->display_name); ?>
                                    </a>
                                    (ID: <?php echo esc_html($reporter->ID); ?>)
                                <?php else : ?>
                                    Kullanıcı bulunamadı
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($reported_user) : ?>
                                    <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $reported_user->ID)); ?>">
                                        <?php echo esc_html($reported_user->display_name); ?>
                                    </a>
                                    (ID: <?php echo esc_html($reported_user->ID); ?>)
                                <?php else : ?>
                                    Kullanıcı bulunamadı
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($listing) : ?>
                                    <a href="<?php echo esc_url(get_permalink($listing->ID)); ?>" target="_blank">
                                        <?php echo esc_html($listing->post_title); ?>
                                    </a>
                                <?php else : ?>
                                    İlan bulunamadı
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($dispute['report_reason'] ?: 'Belirtilmemiş'); ?></td>
                            <td><?php echo esc_html($dispute['reported_at'] ? date_i18n('d.m.Y H:i', strtotime($dispute['reported_at'])) : '-'); ?></td>
                            <td>
                                <button class="button button-primary hdh-apply-penalty-btn" data-exchange-id="<?php echo esc_attr($dispute['id']); ?>" data-reporter-id="<?php echo esc_attr($dispute['reported_by_user_id']); ?>" data-reported-id="<?php echo esc_attr($reported_user ? $reported_user->ID : 0); ?>">
                                    Ceza Ver
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <!-- Penalty Modal -->
    <div id="hdh-penalty-modal" style="display: none;">
        <div class="hdh-penalty-modal-overlay">
            <div class="hdh-penalty-modal-content">
                <form method="post" action="">
                    <?php wp_nonce_field('hdh_apply_penalty', 'hdh_apply_penalty_nonce'); ?>
                    <input type="hidden" name="exchange_id" id="penalty-exchange-id" value="">
                    <input type="hidden" name="apply_penalty" value="1">
                    
                    <h2>Ceza Ver</h2>
                    
                    <div class="hdh-penalty-section">
                        <h3>Şikayet Eden Kullanıcı</h3>
                        <label>
                            <select name="reporter_penalty" id="reporter-penalty">
                                <option value="none">Ceza Yok</option>
                                <option value="warning">Uyarı</option>
                                <option value="ban_1day">1 Gün Ban</option>
                                <option value="ban_3days">3 Gün Ban</option>
                                <option value="ban_7days">7 Gün Ban</option>
                                <option value="ban_30days">30 Gün Ban</option>
                                <option value="ban_permanent">Kalıcı Ban</option>
                                <option value="decrease_trust">Güven Puanı Düşür</option>
                            </select>
                        </label>
                        <label id="reporter-penalty-amount-label" style="display: none;">
                            Miktar:
                            <select name="reporter_penalty_amount" id="reporter-penalty-amount">
                                <option value="10">-10</option>
                                <option value="20">-20</option>
                                <option value="50">-50</option>
                            </select>
                        </label>
                    </div>
                    
                    <div class="hdh-penalty-section">
                        <h3>Şikayet Edilen Kullanıcı</h3>
                        <label>
                            <select name="reported_penalty" id="reported-penalty">
                                <option value="none">Ceza Yok</option>
                                <option value="warning">Uyarı</option>
                                <option value="ban_1day">1 Gün Ban</option>
                                <option value="ban_3days">3 Gün Ban</option>
                                <option value="ban_7days">7 Gün Ban</option>
                                <option value="ban_30days">30 Gün Ban</option>
                                <option value="ban_permanent">Kalıcı Ban</option>
                                <option value="decrease_trust">Güven Puanı Düşür</option>
                            </select>
                        </label>
                        <label id="reported-penalty-amount-label" style="display: none;">
                            Miktar:
                            <select name="reported_penalty_amount" id="reported-penalty-amount">
                                <option value="10">-10</option>
                                <option value="20">-20</option>
                                <option value="50">-50</option>
                            </select>
                        </label>
                    </div>
                    
                    <div class="hdh-penalty-section">
                        <label>
                            Admin Notu (Opsiyonel):
                            <textarea name="admin_note" rows="3" style="width: 100%;"></textarea>
                        </label>
                    </div>
                    
                    <div class="hdh-penalty-modal-footer">
                        <button type="button" class="button hdh-cancel-penalty">İptal</button>
                        <button type="submit" class="button button-primary">Ceza Ver</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('.hdh-apply-penalty-btn').on('click', function() {
            const exchangeId = $(this).data('exchange-id');
            $('#penalty-exchange-id').val(exchangeId);
            $('#hdh-penalty-modal').show();
        });
        
        $('.hdh-cancel-penalty').on('click', function() {
            $('#hdh-penalty-modal').hide();
        });
        
        $('#reporter-penalty, #reported-penalty').on('change', function() {
            const isReporter = $(this).attr('id') === 'reporter-penalty';
            const amountLabel = isReporter ? $('#reporter-penalty-amount-label') : $('#reported-penalty-amount-label');
            
            if ($(this).val() === 'decrease_trust') {
                amountLabel.show();
            } else {
                amountLabel.hide();
            }
        });
    });
    </script>
    
    <style>
    .hdh-penalty-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 100000;
    }
    
    .hdh-penalty-modal-content {
        background: #fff;
        padding: 30px;
        border-radius: 8px;
        max-width: 600px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
    }
    
    .hdh-penalty-section {
        margin-bottom: 20px;
    }
    
    .hdh-penalty-section h3 {
        margin-top: 0;
        margin-bottom: 10px;
    }
    
    .hdh-penalty-section label {
        display: block;
        margin-bottom: 10px;
    }
    
    .hdh-penalty-section select,
    .hdh-penalty-section textarea {
        width: 100%;
        padding: 8px;
        margin-top: 5px;
    }
    
    .hdh-penalty-modal-footer {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 20px;
    }
    </style>
    <?php
}

