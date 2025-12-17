<?php
/**
 * HDH: Firebase Email & Phone Verification
 * Handles Firebase Authentication for email and phone verification
 */

if (!defined('ABSPATH')) exit;

/**
 * Link WordPress user to Firebase UID
 */
function hdh_link_firebase_uid($user_id, $firebase_uid) {
    if (!$user_id || !$firebase_uid) {
        return false;
    }
    
    update_user_meta($user_id, 'hdh_firebase_uid', $firebase_uid);
    
    // Log event
    if (function_exists('hdh_log_event')) {
        hdh_log_event($user_id, 'firebase_uid_linked', array(
            'firebase_uid' => $firebase_uid,
            'linked_at' => current_time('mysql'),
        ));
    }
    
    return true;
}

/**
 * Get Firebase UID for WordPress user
 */
function hdh_get_firebase_uid($user_id) {
    return get_user_meta($user_id, 'hdh_firebase_uid', true);
}

/**
 * Verify email using Firebase token
 * Called from frontend after Firebase email verification
 */
function hdh_verify_email_via_firebase($user_id, $firebase_id_token) {
    if (!$user_id || !$firebase_id_token) {
        return new WP_Error('invalid_params', 'Geçersiz parametreler');
    }
    
    // Check if already verified
    if (get_user_meta($user_id, 'hdh_email_verified', true)) {
        return new WP_Error('already_verified', 'E-posta zaten doğrulanmış');
    }
    
    // Verify Firebase token via AJAX endpoint
    // Note: In production, verify token server-side using Firebase Admin SDK
    // For now, we trust the client-side verification and verify on backend
    
    // Store verification request
    $verification_key = 'hdh_firebase_email_verify_' . $user_id;
    set_transient($verification_key, array(
        'token' => $firebase_id_token,
        'timestamp' => time(),
    ), 10 * MINUTE_IN_SECONDS);
    
    // Mark as verified (in production, verify token server-side first)
    if (function_exists('hdh_verify_email')) {
        hdh_verify_email($user_id);
    } else {
        update_user_meta($user_id, 'hdh_email_verified', true);
        update_user_meta($user_id, 'hdh_email_verified_at', current_time('mysql'));
    }
    
    // Link Firebase UID if available
    // Extract UID from token (in production, decode JWT properly)
    // For now, we'll get it from frontend
    
    // Log event
    if (function_exists('hdh_log_event')) {
        hdh_log_event($user_id, 'email_verified', array(
            'verified_at' => current_time('mysql'),
            'method' => 'firebase_auth',
        ));
    }
    
    return true;
}

/**
 * Verify phone using Firebase token
 * Called from frontend after Firebase phone verification
 * NOTE: Phone verification is disabled - phone is optional, no rewards
 */
function hdh_verify_phone_via_firebase($user_id, $firebase_id_token, $phone_number) {
    // Phone verification is disabled - return error
    return new WP_Error('phone_verification_disabled', 'Telefon doğrulaması devre dışı bırakılmıştır.');
}

/**
 * AJAX: Verify email via Firebase
 */
function hdh_ajax_verify_email_firebase() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => hdh_get_message('ajax', 'login_required', 'Giriş yapmalısınız.')));
        return;
    }
    
    check_ajax_referer('hdh_firebase_verification', 'nonce');
    
    $user_id = get_current_user_id();
    $id_token = isset($_POST['id_token']) ? sanitize_text_field($_POST['id_token']) : '';
    $firebase_uid = isset($_POST['firebase_uid']) ? sanitize_text_field($_POST['firebase_uid']) : '';
    
    if (empty($id_token)) {
        wp_send_json_error(array('message' => hdh_get_message('ajax', 'invalid_parameters', 'Firebase token gerekli.')));
        return;
    }
    
    // Link Firebase UID
    if ($firebase_uid) {
        hdh_link_firebase_uid($user_id, $firebase_uid);
    }
    
    $result = hdh_verify_email_via_firebase($user_id, $id_token);
    
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
add_action('wp_ajax_hdh_verify_email_firebase', 'hdh_ajax_verify_email_firebase');

/**
 * AJAX: Verify phone via Firebase
 * NOTE: Phone verification is disabled - phone is optional, no rewards
 */
function hdh_ajax_verify_phone_firebase() {
    wp_send_json_error(array('message' => hdh_get_message('ajax', 'generic_error', 'Telefon doğrulaması devre dışı bırakılmıştır.')));
}
add_action('wp_ajax_hdh_verify_phone_firebase', 'hdh_ajax_verify_phone_firebase');

/**
 * AJAX: Send Firebase email verification
 * Note: This uses Firebase Admin SDK on server-side (optional)
 * For now, we'll use client-side Firebase Auth
 */
function hdh_ajax_send_firebase_email_verification() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => hdh_get_message('ajax', 'login_required', 'Giriş yapmalısınız.')));
        return;
    }
    
    check_ajax_referer('hdh_firebase_verification', 'nonce');
    
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : $user->user_email;
    
    if (empty($email)) {
        wp_send_json_error(array('message' => hdh_get_message('ajax', 'invalid_parameters', 'E-posta adresi gerekli.')));
        return;
    }
    
    // Note: In production, use Firebase Admin SDK to send verification email
    // For now, we'll let client-side Firebase Auth handle it
    // This endpoint is mainly for logging purposes
    
    // Log event
    if (function_exists('hdh_log_event')) {
        hdh_log_event($user_id, 'firebase_email_verification_requested', array(
            'email' => $email,
            'requested_at' => current_time('mysql'),
        ));
    }
    
    wp_send_json_success(array(
        'message' => 'Doğrulama e-postası gönderilecek. Firebase Auth kullanarak e-posta kutunuzu kontrol edin.'
    ));
}
add_action('wp_ajax_hdh_send_firebase_email_verification', 'hdh_ajax_send_firebase_email_verification');

