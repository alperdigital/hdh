<?php
/**
 * HDH: Trade Report AJAX Handlers
 */

if (!defined('ABSPATH')) exit;

/**
 * AJAX: Create trade report
 */
function hdh_ajax_create_trade_report() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Giriş yapmanız gerekiyor'));
        return;
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_trade_session')) {
        wp_send_json_error(array('message' => 'Güvenlik kontrolü başarısız'));
        return;
    }
    
    $session_id = isset($_POST['session_id']) ? absint($_POST['session_id']) : 0;
    $issue_type = isset($_POST['issue_type']) ? sanitize_text_field($_POST['issue_type']) : '';
    $description = isset($_POST['description']) ? sanitize_text_field($_POST['description']) : '';
    $user_id = get_current_user_id();
    
    if (!$session_id) {
        wp_send_json_error(array('message' => 'Geçersiz oturum ID'));
        return;
    }
    
    if (!$issue_type) {
        wp_send_json_error(array('message' => 'Lütfen bir sorun tipi seçin'));
        return;
    }
    
    // Rate limiting: max 3 reports per day per user
    if (function_exists('hdh_get_user_report_count')) {
        $report_count = hdh_get_user_report_count($user_id, 24);
        if ($report_count >= 3) {
            wp_send_json_error(array('message' => 'Günlük rapor limitine ulaştınız (3 rapor/gün)'));
            return;
        }
    }
    
    if (!function_exists('hdh_create_trade_report')) {
        wp_send_json_error(array('message' => 'Rapor sistemi mevcut değil'));
        return;
    }
    
    $report_id = hdh_create_trade_report($session_id, $user_id, $issue_type, $description);
    
    if (is_wp_error($report_id)) {
        wp_send_json_error(array('message' => $report_id->get_error_message()));
        return;
    }
    
    wp_send_json_success(array(
        'report_id' => $report_id,
        'message' => 'Rapor gönderildi!'
    ));
}
add_action('wp_ajax_hdh_create_trade_report', 'hdh_ajax_create_trade_report');

/**
 * AJAX: Get trade reports (admin only)
 */
function hdh_ajax_get_trade_reports() {
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Yetkiniz yok'));
        return;
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_admin_moderation')) {
        wp_send_json_error(array('message' => 'Güvenlik kontrolü başarısız'));
        return;
    }
    
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'pending';
    $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 50;
    
    if (!function_exists('hdh_get_trade_reports')) {
        wp_send_json_error(array('message' => 'Rapor sistemi mevcut değil'));
        return;
    }
    
    $reports = hdh_get_trade_reports($status, $limit);
    
    wp_send_json_success(array(
        'reports' => $reports,
        'count' => count($reports),
    ));
}
add_action('wp_ajax_hdh_get_trade_reports', 'hdh_ajax_get_trade_reports');

/**
 * AJAX: Update trade report status (admin only)
 */
function hdh_ajax_update_trade_report_status() {
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Yetkiniz yok'));
        return;
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_admin_moderation')) {
        wp_send_json_error(array('message' => 'Güvenlik kontrolü başarısız'));
        return;
    }
    
    $report_id = isset($_POST['report_id']) ? absint($_POST['report_id']) : 0;
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
    $admin_note = isset($_POST['admin_note']) ? sanitize_textarea_field($_POST['admin_note']) : '';
    
    if (!$report_id || !$status) {
        wp_send_json_error(array('message' => 'Geçersiz parametreler'));
        return;
    }
    
    if (!function_exists('hdh_update_trade_report_status')) {
        wp_send_json_error(array('message' => 'Rapor sistemi mevcut değil'));
        return;
    }
    
    $result = hdh_update_trade_report_status($report_id, $status, $admin_note);
    
    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
        return;
    }
    
    wp_send_json_success(array('message' => 'Rapor durumu güncellendi'));
}
add_action('wp_ajax_hdh_update_trade_report_status', 'hdh_ajax_update_trade_report_status');

