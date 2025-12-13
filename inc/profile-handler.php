<?php
if (!defined('ABSPATH')) exit;

function hdh_handle_update_profile() {
    if (!is_user_logged_in()) wp_die('Yetkisiz erişim.');
    if (!isset($_POST['hdh_profile_nonce']) || !wp_verify_nonce($_POST['hdh_profile_nonce'], 'hdh_update_profile')) {
        wp_die('Güvenlik kontrolü başarısız.');
    }
    $user_id = get_current_user_id();
    $farm_name = isset($_POST['farm_name']) ? sanitize_text_field($_POST['farm_name']) : '';
    $hayday_username = isset($_POST['hayday_username']) ? sanitize_text_field($_POST['hayday_username']) : '';
    $hayday_farm_number = isset($_POST['hayday_farm_number']) ? sanitize_text_field($_POST['hayday_farm_number']) : '';
    
    if (empty($farm_name)) {
        wp_redirect(home_url('/profil?error=empty_name'));
        exit;
    }
    
    wp_update_user(array('ID' => $user_id, 'display_name' => $farm_name));
    
    if (!empty($hayday_username)) {
        update_user_meta($user_id, 'hayday_username', $hayday_username);
    } else {
        delete_user_meta($user_id, 'hayday_username');
    }
    
    if (!empty($hayday_farm_number)) {
        update_user_meta($user_id, 'hayday_farm_number', $hayday_farm_number);
    } else {
        delete_user_meta($user_id, 'hayday_farm_number');
    }
    
    wp_redirect(home_url('/profil?updated=1'));
    exit;
}
add_action('admin_post_hdh_update_profile', 'hdh_handle_update_profile');
