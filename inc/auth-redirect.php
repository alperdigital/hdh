<?php
/**
 * HDH: Auth Redirect System
 * Handles secure return URLs after login/registration
 */

if (!defined('ABSPATH')) exit;

/**
 * Generate secure return URL with state token
 * 
 * @param string $return_url The URL to return to after auth
 * @return string Login URL with return parameter
 */
function hdh_get_login_url_with_return($return_url = '') {
    if (empty($return_url)) {
        $return_url = hdh_get_current_url();
    }
    
    // Validate return URL is on same site
    if (!hdh_is_valid_return_url($return_url)) {
        $return_url = home_url('/');
    }
    
    // Generate state token for security
    $state_token = hdh_generate_state_token($return_url);
    
    // Build login URL
    $login_url = home_url('/profil');
    $login_url = add_query_arg(array(
        'return' => urlencode($return_url),
        'state' => $state_token
    ), $login_url);
    
    return $login_url;
}

/**
 * Get current URL
 * 
 * @return string Current page URL
 */
function hdh_get_current_url() {
    $protocol = is_ssl() ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $request_uri = $_SERVER['REQUEST_URI'];
    
    return $protocol . $host . $request_uri;
}

/**
 * Validate return URL is safe
 * 
 * @param string $url URL to validate
 * @return bool True if URL is valid and safe
 */
function hdh_is_valid_return_url($url) {
    if (empty($url)) {
        return false;
    }
    
    // Parse URL
    $parsed = parse_url($url);
    if (!$parsed) {
        return false;
    }
    
    // Check if URL is relative (safe)
    if (!isset($parsed['host'])) {
        // Relative URL - check if it starts with /
        return strpos($url, '/') === 0 && strpos($url, '//') !== 0;
    }
    
    // Check if URL is on same domain
    $site_host = parse_url(home_url(), PHP_URL_HOST);
    if ($parsed['host'] !== $site_host) {
        return false;
    }
    
    // Whitelist allowed paths
    $allowed_patterns = array(
        '/^\/ilan-ver/',
        '/^\/ara/',
        '/^\/cekilis/',
        '/^\/hazine/',
        '/^\/profil/',
        '/^\/hayday_trade\//',
        '/^\/$/',
    );
    
    $path = isset($parsed['path']) ? $parsed['path'] : '/';
    foreach ($allowed_patterns as $pattern) {
        if (preg_match($pattern, $path)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Generate state token for CSRF protection
 * 
 * @param string $return_url Return URL to bind token to
 * @return string State token
 */
function hdh_generate_state_token($return_url) {
    $token = wp_generate_password(32, false);
    $expiry = time() + (15 * MINUTE_IN_SECONDS); // 15 minutes
    
    // Store token in transient
    $transient_key = 'hdh_auth_state_' . md5($token);
    set_transient($transient_key, array(
        'return_url' => $return_url,
        'created_at' => time(),
        'ip' => $_SERVER['REMOTE_ADDR']
    ), 15 * MINUTE_IN_SECONDS);
    
    return $token;
}

/**
 * Verify and consume state token
 * 
 * @param string $token State token to verify
 * @param string $return_url Expected return URL
 * @return bool True if token is valid
 */
function hdh_verify_state_token($token, $return_url) {
    if (empty($token)) {
        return false;
    }
    
    $transient_key = 'hdh_auth_state_' . md5($token);
    $state_data = get_transient($transient_key);
    
    if (!$state_data) {
        return false;
    }
    
    // Verify return URL matches
    if ($state_data['return_url'] !== $return_url) {
        return false;
    }
    
    // Verify IP matches (optional, can be disabled for mobile networks)
    // if ($state_data['ip'] !== $_SERVER['REMOTE_ADDR']) {
    //     return false;
    // }
    
    // Delete transient (one-time use)
    delete_transient($transient_key);
    
    return true;
}

/**
 * Get return URL from request
 * 
 * @return string|false Return URL if valid, false otherwise
 */
function hdh_get_return_url_from_request() {
    if (!isset($_GET['return']) || !isset($_GET['state'])) {
        return false;
    }
    
    $return_url = urldecode($_GET['return']);
    $state_token = sanitize_text_field($_GET['state']);
    
    // Validate return URL
    if (!hdh_is_valid_return_url($return_url)) {
        return false;
    }
    
    // Verify state token
    if (!hdh_verify_state_token($state_token, $return_url)) {
        return false;
    }
    
    return $return_url;
}

/**
 * Redirect after successful auth
 * 
 * @param int $user_id User ID (optional, for logging)
 */
function hdh_redirect_after_auth($user_id = 0) {
    // Try to get return URL
    $return_url = hdh_get_return_url_from_request();
    
    // If no valid return URL, check for pending trade
    if (!$return_url) {
        $redirect_to_trade = isset($_POST['redirect_to_trade']) && $_POST['redirect_to_trade'] === '1';
        
        if ($redirect_to_trade) {
            $transient_key = isset($_COOKIE['hdh_pending_trade_key']) ? $_COOKIE['hdh_pending_trade_key'] : '';
            
            if ($transient_key) {
                $pending_trade = get_transient($transient_key);
                
                if ($pending_trade && function_exists('hdh_create_trade_from_pending')) {
                    hdh_create_trade_from_pending($pending_trade, $transient_key);
                    exit;
                }
            }
        }
        
        // Default to homepage instead of /profil
        $return_url = home_url('/');
    }
    
    // Clear user cache to ensure fresh login state
    if ($user_id) {
        clean_user_cache($user_id);
        wp_cache_delete($user_id, 'users');
        wp_cache_flush(); // Clear all caches to prevent stale login state
    }
    
    // Add cache-busting parameter to prevent stale page cache
    $return_url = add_query_arg(array(
        'logged_in' => '1',
        '_t' => time()
    ), $return_url);
    
    // Log redirect for debugging (optional)
    if (WP_DEBUG && $user_id) {
        error_log(sprintf(
            '[HDH Auth] User %d redirected to: %s',
            $user_id,
            $return_url
        ));
    }
    
    wp_redirect($return_url);
    exit;
}

