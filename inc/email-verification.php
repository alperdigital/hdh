<?php
/**
 * HDH: Email Verification System
 * Sends verification codes and handles email verification
 */

if (!defined('ABSPATH')) exit;

/**
 * Generate and send verification code
 */
function hdh_send_email_verification_code($user_id) {
    if (!$user_id) {
        return new WP_Error('invalid_user', hdh_get_message('ajax', 'invalid_user', 'Geçersiz kullanıcı'));
    }
    
    $user = get_userdata($user_id);
    if (!$user || !$user->user_email) {
        return new WP_Error('no_email', hdh_get_message('ajax', 'no_email', 'Kullanıcının e-posta adresi bulunamadı'));
    }
    
    // Check if already verified
    if (get_user_meta($user_id, 'hdh_email_verified', true)) {
        return new WP_Error('already_verified', hdh_get_message('ajax', 'already_verified', 'E-posta zaten doğrulanmış'));
    }
    
    // Generate 6-digit code
    $code = sprintf('%06d', wp_rand(100000, 999999));
    
    // Store code in transient (expires in 15 minutes)
    $transient_key = 'hdh_email_verify_' . $user_id;
    set_transient($transient_key, $code, 15 * MINUTE_IN_SECONDS);
    
    // Store attempt count (max 5 attempts per hour)
    $attempt_key = 'hdh_email_verify_attempts_' . $user_id;
    $attempts = get_transient($attempt_key) ?: 0;
    if ($attempts >= 5) {
        return new WP_Error('rate_limit', hdh_get_message('ajax', 'rate_limit', 'Çok fazla deneme. Lütfen 1 saat sonra tekrar deneyin.'));
    }
    set_transient($attempt_key, $attempts + 1, HOUR_IN_SECONDS);
    
    // Prepare email
    $site_name = get_bloginfo('name');
    $site_url = home_url();
    $subject = sprintf(
        hdh_get_message('verification', 'email_subject', '[%s] E-posta Doğrulama Kodu'),
        $site_name
    );
    
    $greeting = sprintf(
        hdh_get_message('verification', 'email_body_greeting', 'Merhaba %s,'),
        $user->display_name
    );
    $code_text = sprintf(
        hdh_get_message('verification', 'email_body_code', 'Doğrulama Kodu: %s'),
        $code
    );
    $validity = hdh_get_message('verification', 'email_body_validity', 'Bu kod 15 dakika süreyle geçerlidir.');
    $warning = hdh_get_message('verification', 'email_body_warning', 'Eğer bu işlemi siz yapmadıysanız, bu e-postayı görmezden gelebilirsiniz.');
    $signature = sprintf(
        hdh_get_message('verification', 'email_body_signature', 'Saygılarımızla,') . "\n%s Ekibi\n%s",
        $site_name,
        $site_url
    );
    
    $message = $greeting . "\n\n" . "E-posta adresinizi doğrulamak için aşağıdaki kodu kullanın:\n\n" . $code_text . "\n\n" . $validity . "\n\n" . $warning . "\n\n" . $signature;
    
    // Send email
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . $site_name . ' <' . get_option('admin_email') . '>'
    );
    
    $sent = wp_mail($user->user_email, $subject, $message, $headers);
    
    if ($sent) {
        // Log event
        if (function_exists('hdh_log_event')) {
            hdh_log_event($user_id, 'email_verification_code_sent', array(
                'email' => $user->user_email,
                'sent_at' => current_time('mysql'),
            ));
        }
        
        return true;
    } else {
        return new WP_Error('send_failed', hdh_get_message('ajax', 'send_failed', 'E-posta gönderilemedi. Lütfen daha sonra tekrar deneyin.'));
    }
}

/**
 * Verify email code
 */
function hdh_verify_email_code($user_id, $code) {
    if (!$user_id || !$code) {
        return new WP_Error('invalid_params', hdh_get_message('ajax', 'invalid_parameters', 'Geçersiz parametreler'));
    }
    
    // Check if already verified
    if (get_user_meta($user_id, 'hdh_email_verified', true)) {
        return new WP_Error('already_verified', hdh_get_message('ajax', 'already_verified', 'E-posta zaten doğrulanmış'));
    }
    
    // Get stored code
    $transient_key = 'hdh_email_verify_' . $user_id;
    $stored_code = get_transient($transient_key);
    
    if (!$stored_code) {
        return new WP_Error('code_expired', hdh_get_message('ajax', 'code_expired', 'Doğrulama kodu süresi dolmuş. Lütfen yeni kod isteyin.'));
    }
    
    // Verify code
    if ($stored_code !== $code) {
        // Log failed attempt
        if (function_exists('hdh_log_event')) {
            hdh_log_event($user_id, 'email_verification_failed', array(
                'attempted_code' => $code,
                'attempted_at' => current_time('mysql'),
            ));
        }
        
        return new WP_Error('invalid_code', hdh_get_message('ajax', 'invalid_code', 'Doğrulama kodu hatalı'));
    }
    
    // Code is valid - verify email
    delete_transient($transient_key); // Remove code after successful verification
    
    if (function_exists('hdh_verify_email')) {
        hdh_verify_email($user_id);
    } else {
        update_user_meta($user_id, 'hdh_email_verified', true);
        update_user_meta($user_id, 'hdh_email_verified_at', current_time('mysql'));
    }
    
    // Log successful verification
    if (function_exists('hdh_log_event')) {
        hdh_log_event($user_id, 'email_verified', array(
            'verified_at' => current_time('mysql'),
            'method' => 'code_verification',
        ));
    }
    
    return true;
}

/**
 * AJAX: Send verification code
 */
function hdh_ajax_send_email_verification_code() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => hdh_get_message('ajax', 'login_required', 'Giriş yapmalısınız.')));
        return;
    }
    
    check_ajax_referer('hdh_email_verification', 'nonce');
    
    $user_id = get_current_user_id();
    $result = hdh_send_email_verification_code($user_id);
    
    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    } else {
        wp_send_json_success(array('message' => hdh_get_message('verification', 'email_sent', 'Doğrulama kodu e-posta adresinize gönderildi.')));
    }
}
add_action('wp_ajax_hdh_send_email_verification_code', 'hdh_ajax_send_email_verification_code');

/**
 * AJAX: Verify email code
 */
function hdh_ajax_verify_email_code() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => hdh_get_message('ajax', 'login_required', 'Giriş yapmalısınız.')));
        return;
    }
    
    check_ajax_referer('hdh_email_verification', 'nonce');
    
    $user_id = get_current_user_id();
    $code = isset($_POST['code']) ? sanitize_text_field($_POST['code']) : '';
    
    if (empty($code)) {
        wp_send_json_error(array('message' => hdh_get_message('verification', 'code_invalid', 'Doğrulama kodu gerekli.')));
        return;
    }
    
    $result = hdh_verify_email_code($user_id, $code);
    
    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    } else {
        $success_message = hdh_get_message('verification', 'email_verified', 'E-posta adresiniz başarıyla doğrulandı!') . ' +1 bilet kazandınız.';
        wp_send_json_success(array(
            'message' => $success_message,
            'bilet_balance' => function_exists('hdh_get_user_jeton_balance') 
                ? hdh_get_user_jeton_balance($user_id) 
                : 0
        ));
    }
}
add_action('wp_ajax_hdh_verify_email_code', 'hdh_ajax_verify_email_code');

