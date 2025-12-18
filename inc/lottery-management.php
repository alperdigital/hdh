<?php
/**
 * HDH: Lottery Management System
 * Comprehensive lottery configuration and management
 */

if (!defined('ABSPATH')) exit;

/**
 * Get lottery configuration
 */
function hdh_get_lottery_config($lottery_type) {
    $defaults = array(
        'kurek' => array(
            'id' => 'kurek',
            'name' => '89 Kürek Çekilişi',
            'description' => '1 bilet ile katılabilirsiniz. Ödül: 89 Kürek',
            'cost' => 1,
            'prize' => '89 Kürek',
            'max_daily_entries' => 3,
            'status' => 'active', // active, paused, ended
            'start_date' => '',
            'end_date' => '',
        ),
        'genisletme' => array(
            'id' => 'genisletme',
            'name' => '89 Genişletme/Ağıl Malzemesi Çekilişi',
            'description' => '5 bilet ile katılabilirsiniz. Ödül: 89 Genişletme/Ağıl Malzemesi',
            'cost' => 5,
            'prize' => '89 Genişletme/Ağıl Malzemesi',
            'max_daily_entries' => 3,
            'status' => 'active',
            'start_date' => '',
            'end_date' => '',
        ),
    );
    
    $saved = get_option('hdh_lottery_config_' . $lottery_type, array());
    return wp_parse_args($saved, isset($defaults[$lottery_type]) ? $defaults[$lottery_type] : array());
}

/**
 * Save lottery configuration
 */
function hdh_save_lottery_config($lottery_type, $config) {
    if (!current_user_can('administrator')) {
        return false;
    }
    
    if (!in_array($lottery_type, array('kurek', 'genisletme'))) {
        return false;
    }
    
    // Sanitize config
    $sanitized = array(
        'id' => sanitize_text_field($config['id'] ?? $lottery_type),
        'name' => sanitize_text_field($config['name'] ?? ''),
        'description' => sanitize_textarea_field($config['description'] ?? ''),
        'cost' => absint($config['cost'] ?? 1),
        'prize' => sanitize_text_field($config['prize'] ?? ''),
        'max_daily_entries' => absint($config['max_daily_entries'] ?? 3),
        'status' => in_array($config['status'] ?? 'active', array('active', 'paused', 'ended')) ? $config['status'] : 'active',
        'start_date' => sanitize_text_field($config['start_date'] ?? ''),
        'end_date' => sanitize_text_field($config['end_date'] ?? ''),
    );
    
    update_option('hdh_lottery_config_' . $lottery_type, $sanitized);
    update_option('hdh_lottery_status_' . $lottery_type, $sanitized['status']);
    
    return true;
}

/**
 * Start lottery (set status to active)
 */
function hdh_start_lottery_management($lottery_type) {
    if (!current_user_can('administrator')) {
        return false;
    }
    
    $config = hdh_get_lottery_config($lottery_type);
    $config['status'] = 'active';
    if (empty($config['start_date'])) {
        $config['start_date'] = current_time('mysql');
    }
    
    return hdh_save_lottery_config($lottery_type, $config);
}

/**
 * End lottery (set status to ended)
 */
function hdh_end_lottery_management($lottery_type) {
    if (!current_user_can('administrator')) {
        return false;
    }
    
    $config = hdh_get_lottery_config($lottery_type);
    $config['status'] = 'ended';
    $config['end_date'] = current_time('mysql');
    
    return hdh_save_lottery_config($lottery_type, $config);
}

/**
 * Pause lottery (set status to paused)
 */
function hdh_pause_lottery_management($lottery_type) {
    if (!current_user_can('administrator')) {
        return false;
    }
    
    $config = hdh_get_lottery_config($lottery_type);
    $config['status'] = 'paused';
    
    return hdh_save_lottery_config($lottery_type, $config);
}

/**
 * Reset lottery (clear entries and reset status)
 */
function hdh_reset_lottery_management($lottery_type) {
    if (!current_user_can('administrator')) {
        return false;
    }
    
    // Clear all entries for this lottery type
    $users = get_users(array('fields' => 'ID'));
    foreach ($users as $user_id) {
        $entries = get_user_meta($user_id, 'hdh_lottery_entries', true);
        if (is_array($entries)) {
            $filtered_entries = array_filter($entries, function($entry) use ($lottery_type) {
                return !isset($entry['lottery_type']) || $entry['lottery_type'] !== $lottery_type;
            });
            update_user_meta($user_id, 'hdh_lottery_entries', array_values($filtered_entries));
        }
    }
    
    // Reset config
    $config = hdh_get_lottery_config($lottery_type);
    $config['status'] = 'active';
    $config['start_date'] = '';
    $config['end_date'] = '';
    
    return hdh_save_lottery_config($lottery_type, $config);
}

/**
 * Check if lottery is active
 */
function hdh_is_lottery_active($lottery_type) {
    $config = hdh_get_lottery_config($lottery_type);
    return $config['status'] === 'active';
}

/**
 * Get lottery status text
 */
function hdh_get_lottery_status_text($lottery_type) {
    $config = hdh_get_lottery_config($lottery_type);
    $status = $config['status'];
    
    $status_texts = array(
        'active' => 'Aktif',
        'paused' => 'Duraklatıldı',
        'ended' => 'Sona Erdi',
    );
    
    return isset($status_texts[$status]) ? $status_texts[$status] : 'Bilinmiyor';
}

