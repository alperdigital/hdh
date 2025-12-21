<?php
/**
 * HDH: Tasks Database Tables
 * Creates and manages task progress and reward ledger tables
 */

if (!defined('ABSPATH')) exit;

/**
 * Create task progress table
 */
function hdh_create_task_progress_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_task_progress';
    $charset_collate = $wpdb->get_charset_collate();
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    
    if ($table_exists) {
        return; // Table already exists
    }
    
    $sql = "CREATE TABLE $table_name (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id bigint(20) UNSIGNED NOT NULL,
        task_id varchar(100) NOT NULL,
        period_key varchar(50) NOT NULL DEFAULT 'lifetime',
        completed_count int(11) NOT NULL DEFAULT 0,
        claimed_count int(11) NOT NULL DEFAULT 0,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY user_task_period (user_id, task_id, period_key),
        KEY user_id (user_id),
        KEY task_id (task_id),
        KEY period_key (period_key)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('after_switch_theme', 'hdh_create_task_progress_table');
add_action('admin_init', 'hdh_create_task_progress_table');

/**
 * Create reward ledger table
 */
function hdh_create_reward_ledger_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_reward_ledger';
    $charset_collate = $wpdb->get_charset_collate();
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    
    if ($table_exists) {
        return; // Table already exists
    }
    
    $sql = "CREATE TABLE $table_name (
        ledger_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id bigint(20) UNSIGNED NOT NULL,
        task_id varchar(100) NOT NULL,
        period_key varchar(50) NOT NULL DEFAULT 'lifetime',
        claim_index int(11) NOT NULL,
        reward_type enum('bilet','xp') NOT NULL,
        reward_amount int(11) NOT NULL,
        status enum('applied','reverted') NOT NULL DEFAULT 'applied',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (ledger_id),
        UNIQUE KEY user_task_period_claim (user_id, task_id, period_key, claim_index),
        KEY user_id (user_id),
        KEY task_id (task_id),
        KEY period_key (period_key),
        KEY status (status)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('after_switch_theme', 'hdh_create_reward_ledger_table');
add_action('admin_init', 'hdh_create_reward_ledger_table');

