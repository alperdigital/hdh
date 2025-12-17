<?php
/**
 * HDH: Messages Localization Helper
 * Provides messages to JavaScript via wp_localize_script
 */

if (!defined('ABSPATH')) exit;

/**
 * Get all messages for JavaScript
 */
function hdh_get_js_messages() {
    return array(
        'ui' => array(
            'loading' => hdh_get_message('ui', 'loading', 'Yükleniyor...'),
            'saving' => hdh_get_message('ui', 'saving', 'Kaydediliyor...'),
            'processing' => hdh_get_message('ui', 'processing', 'İşleniyor...'),
            'sending' => hdh_get_message('ui', 'sending', 'Gönderiliyor...'),
            'confirm_accept_offer' => hdh_get_message('ui', 'confirm_accept_offer', 'Bu teklifi kabul etmek istediğinize emin misiniz? Diğer tüm teklifler reddedilecek.'),
            'confirm_reject_offer' => hdh_get_message('ui', 'confirm_reject_offer', 'Bu teklifi reddetmek istediğinize emin misiniz?'),
            'confirm_complete_exchange' => hdh_get_message('ui', 'confirm_complete_exchange', 'Hediyeleşmeyi tamamladığınızı onaylıyor musunuz? Bu işlem geri alınamaz.'),
        ),
        'ajax' => array(
            'generic_error' => hdh_get_message('ajax', 'generic_error', 'Bir hata oluştu'),
            'generic_error_retry' => hdh_get_message('ajax', 'generic_error_retry', 'Bir hata oluştu. Lütfen tekrar deneyin.'),
            'select_at_least_one_gift' => hdh_get_message('ajax', 'select_at_least_one_gift', 'En az bir hediye seçmelisiniz.'),
        ),
        'tasks' => array(
            'task_id_not_found' => 'Görev ID bulunamadı',
            'task_system_load_error' => 'Görev sistemi yüklenemedi',
            'reward_claimed' => hdh_get_content('tasks', 'reward_claimed_text', '✅ Ödül Alındı'),
            'do_task' => hdh_get_content('tasks', 'do_task_button', 'Yap'),
            'pending_status' => hdh_get_content('tasks', 'pending_status', 'Beklemede'),
            'claim_reward_button' => hdh_get_content('tasks', 'claim_reward_button', 'Ödülünü Al'),
        ),
        'verification' => array(
            'email_sent' => hdh_get_message('verification', 'email_sent', 'Doğrulama kodu e-posta adresinize gönderildi. Lütfen e-posta kutunuzu kontrol edin.'),
            'email_verified' => hdh_get_message('verification', 'email_verified', 'E-posta adresiniz başarıyla doğrulandı!'),
            'code_invalid' => hdh_get_message('verification', 'code_invalid', 'Doğrulama kodu geçersiz veya süresi dolmuş.'),
        ),
        'ajax' => array(
            'invalid_parameters' => hdh_get_message('ajax', 'invalid_parameters', 'Geçersiz parametreler'),
            'generic_error' => hdh_get_message('ajax', 'generic_error', 'Bir hata oluştu'),
        ),
    );
}

