<?php
/**
 * HDH: Trade Report System
 * Structured report system for trade issues (separate from dispute system)
 */

if (!defined('ABSPATH')) exit;

/**
 * ============================================
 * DATABASE TABLE CREATION
 * ============================================
 */

/**
 * Create trade reports table
 */
function hdh_create_trade_reports_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'hdh_trade_reports';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        trade_session_id bigint(20) unsigned NOT NULL,
        reporter_user_id bigint(20) unsigned NOT NULL,
        reported_user_id bigint(20) unsigned NOT NULL,
        issue_type varchar(50) NOT NULL,
        description varchar(200) DEFAULT NULL,
        status varchar(20) NOT NULL DEFAULT 'pending',
        admin_note text DEFAULT NULL,
        created_at datetime NOT NULL,
        reviewed_at datetime DEFAULT NULL,
        updated_at datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY trade_session_id (trade_session_id),
        KEY reporter_user_id (reporter_user_id),
        KEY reported_user_id (reported_user_id),
        KEY status (status),
        KEY created_at (created_at)
    ) {$charset_collate};";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Initialize trade reports table on theme activation
 * DISABLED - System temporarily disabled
 */
// add_action('after_switch_theme', 'hdh_create_trade_reports_table');

/**
 * Also create on admin init (for existing sites)
 * DISABLED - System temporarily disabled
 */
// add_action('admin_init', function() {
//     if (current_user_can('manage_options')) {
//         hdh_create_trade_reports_table();
//     }
// }, 1);

/**
 * ============================================
 * REPORT FUNCTIONS
 * ============================================
 */

/**
 * Create trade report
 * 
 * @param int $trade_session_id Trade session ID
 * @param int $reporter_user_id User ID reporting
 * @param string $issue_type Issue type (no_response/scam/other)
 * @param string $description Description (max 200 chars)
 * @return int|WP_Error Report ID or error
 */
function hdh_create_trade_report($trade_session_id, $reporter_user_id, $issue_type, $description = '') {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_reports';
    
    // Validate issue type
    $valid_types = array('no_response', 'scam', 'other');
    if (!in_array($issue_type, $valid_types)) {
        return new WP_Error('invalid_issue_type', 'Geçersiz sorun tipi');
    }
    
    // Validate description length
    if (mb_strlen($description) > 200) {
        return new WP_Error('description_too_long', 'Açıklama 200 karakterden uzun olamaz');
    }
    
    // Get trade session
    if (!function_exists('hdh_get_trade_session')) {
        return new WP_Error('function_not_found', 'Trade session sistemi mevcut değil');
    }
    
    $session = hdh_get_trade_session($trade_session_id);
    if (!$session) {
        return new WP_Error('session_not_found', 'Hediyeleşme oturumu bulunamadı');
    }
    
    // Determine reported_user_id
    $reported_user_id = null;
    if ($session['owner_user_id'] == $reporter_user_id) {
        $reported_user_id = $session['starter_user_id'];
    } elseif ($session['starter_user_id'] == $reporter_user_id) {
        $reported_user_id = $session['owner_user_id'];
    } else {
        return new WP_Error('invalid_user', 'Bu hediyeleşmede yer almıyorsunuz');
    }
    
    // Prevent self-reporting
    if ($reporter_user_id == $reported_user_id) {
        return new WP_Error('self_report', 'Kendinizi rapor edemezsiniz');
    }
    
    // Prevent duplicate reports (same session, same reporter, within 24 hours)
    $twenty_four_hours_ago = date('Y-m-d H:i:s', current_time('timestamp') - 86400);
    $duplicate = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$table_name}
         WHERE trade_session_id = %d
         AND reporter_user_id = %d
         AND created_at >= %s
         LIMIT 1",
        $trade_session_id,
        $reporter_user_id,
        $twenty_four_hours_ago
    ));
    
    if ($duplicate) {
        return new WP_Error('duplicate_report', 'Bu hediyeleşme için zaten bir rapor gönderdiniz (24 saat içinde)');
    }
    
    // Create report
    $result = $wpdb->insert(
        $table_name,
        array(
            'trade_session_id' => $trade_session_id,
            'reporter_user_id' => $reporter_user_id,
            'reported_user_id' => $reported_user_id,
            'issue_type' => sanitize_key($issue_type),
            'description' => sanitize_text_field($description),
            'status' => 'pending',
            'created_at' => current_time('mysql'),
        ),
        array('%d', '%d', '%d', '%s', '%s', '%s', '%s')
    );
    
    if ($result === false) {
        return new WP_Error('db_error', 'Veritabanı hatası');
    }
    
    $report_id = $wpdb->insert_id;
    
    // Log event
    if (function_exists('hdh_log_event')) {
        hdh_log_event($reporter_user_id, 'trade_report_created', array(
            'report_id' => $report_id,
            'trade_session_id' => $trade_session_id,
            'reported_user_id' => $reported_user_id,
            'issue_type' => $issue_type,
        ));
    }
    
    return $report_id;
}

/**
 * Get trade reports
 * 
 * @param string $status Report status (pending/reviewed/resolved)
 * @param int $limit Limit number of reports
 * @return array Array of reports
 */
function hdh_get_trade_reports($status = 'pending', $limit = 50) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_reports';
    
    $where = $wpdb->prepare('status = %s', $status);
    
    $reports = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table_name}
         WHERE {$where}
         ORDER BY created_at DESC
         LIMIT %d",
        $limit
    ), ARRAY_A);
    
    if (!$reports) {
        return array();
    }
    
    // Enrich with user and session data
    foreach ($reports as &$report) {
        $reporter = get_userdata($report['reporter_user_id']);
        $reported = get_userdata($report['reported_user_id']);
        
        $report['reporter_name'] = $reporter ? $reporter->display_name : 'Bilinmeyen';
        $report['reported_name'] = $reported ? $reported->display_name : 'Bilinmeyen';
        
        if (function_exists('hdh_get_trade_session')) {
            $session = hdh_get_trade_session($report['trade_session_id']);
            if ($session) {
                $listing = get_post($session['listing_id']);
                $report['listing_title'] = $listing ? $listing->post_title : 'İlan';
                $report['listing_id'] = $session['listing_id'];
            }
        }
        
        // Format issue type
        $issue_types = array(
            'no_response' => 'Yanıt vermiyor',
            'scam' => 'Dolandırıcılık şüphesi',
            'other' => 'Diğer',
        );
        $report['issue_type_label'] = $issue_types[$report['issue_type']] ?? $report['issue_type'];
    }
    
    return $reports;
}

/**
 * Update trade report status
 * 
 * @param int $report_id Report ID
 * @param string $status New status (reviewed/resolved)
 * @param string $admin_note Admin note
 * @return bool|WP_Error Success or error
 */
function hdh_update_trade_report_status($report_id, $status, $admin_note = '') {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_reports';
    
    // Validate status
    $valid_statuses = array('reviewed', 'resolved');
    if (!in_array($status, $valid_statuses)) {
        return new WP_Error('invalid_status', 'Geçersiz durum');
    }
    
    // Get report
    $report = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table_name} WHERE id = %d",
        $report_id
    ), ARRAY_A);
    
    if (!$report) {
        return new WP_Error('report_not_found', 'Rapor bulunamadı');
    }
    
    // Update report
    $update_data = array(
        'status' => $status,
        'updated_at' => current_time('mysql'),
    );
    
    if ($status === 'reviewed' || $status === 'resolved') {
        $update_data['reviewed_at'] = current_time('mysql');
    }
    
    if (!empty($admin_note)) {
        $update_data['admin_note'] = sanitize_textarea_field($admin_note);
    }
    
    $result = $wpdb->update(
        $table_name,
        $update_data,
        array('id' => $report_id),
        array('%s', '%s', '%s', '%s'),
        array('%d')
    );
    
    if ($result === false) {
        return new WP_Error('db_error', 'Veritabanı hatası');
    }
    
    // Log event
    if (function_exists('hdh_log_event')) {
        hdh_log_event(get_current_user_id(), 'trade_report_updated', array(
            'report_id' => $report_id,
            'status' => $status,
        ));
    }
    
    return true;
}

/**
 * Get report by ID
 * 
 * @param int $report_id Report ID
 * @return array|null Report data or null
 */
function hdh_get_trade_report($report_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_reports';
    
    $report = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table_name} WHERE id = %d",
        $report_id
    ), ARRAY_A);
    
    return $report;
}

/**
 * Get user's report count for rate limiting
 * 
 * @param int $user_id User ID
 * @param int $hours Hours to check (default 24)
 * @return int Report count
 */
function hdh_get_user_report_count($user_id, $hours = 24) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hdh_trade_reports';
    
    $cutoff = date('Y-m-d H:i:s', current_time('timestamp') - ($hours * 3600));
    
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_name}
         WHERE reporter_user_id = %d
         AND created_at >= %s",
        $user_id,
        $cutoff
    ));
    
    return (int) $count;
}

