<?php
/**
 * HDH: Firebase Configuration
 * Firebase Authentication settings and helper functions
 */

if (!defined('ABSPATH')) exit;

/**
 * Get Firebase configuration
 * Store these in wp_options or environment variables
 */
function hdh_get_firebase_config() {
    return array(
        'apiKey' => get_option('hdh_firebase_api_key', ''),
        'authDomain' => get_option('hdh_firebase_auth_domain', ''),
        'projectId' => get_option('hdh_firebase_project_id', ''),
        'storageBucket' => get_option('hdh_firebase_storage_bucket', ''),
        'messagingSenderId' => get_option('hdh_firebase_messaging_sender_id', ''),
        'appId' => get_option('hdh_firebase_app_id', ''),
    );
}

/**
 * Check if Firebase is configured
 */
function hdh_is_firebase_configured() {
    $config = hdh_get_firebase_config();
    return !empty($config['apiKey']) && !empty($config['authDomain']) && !empty($config['projectId']);
}

/**
 * Get Firebase Admin SDK credentials path
 * For server-side verification (optional, for advanced use)
 */
function hdh_get_firebase_admin_credentials_path() {
    $path = get_option('hdh_firebase_admin_credentials_path', '');
    if ($path && file_exists($path)) {
        return $path;
    }
    return false;
}

