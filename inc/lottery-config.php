<?php
/**
 * HDH: Lottery Configuration & Time Management
 * Manages lottery dates and provides server time endpoint
 */

if (!defined('ABSPATH')) exit;

/**
 * Get next lottery date (UTC)
 * Returns ISO 8601 formatted string
 */
function hdh_get_next_lottery_date() {
    // Get saved lottery date from options
    $saved_date = get_option('hdh_next_lottery_date', '');
    
    // If no date saved or date is in the past, calculate next Saturday 20:00 Turkey Time (17:00 UTC)
    if (empty($saved_date) || strtotime($saved_date) <= current_time('timestamp', true)) {
        $next_date = hdh_calculate_next_lottery_date();
        update_option('hdh_next_lottery_date', $next_date);
        return $next_date;
    }
    
    return $saved_date;
}

/**
 * Calculate next lottery date
 * Always Saturday at 20:00 Turkey Time (17:00 UTC)
 */
function hdh_calculate_next_lottery_date() {
    // Get current time in UTC
    $now = new DateTime('now', new DateTimeZone('UTC'));
    
    // Turkey is UTC+3
    $turkey_time = new DateTime('now', new DateTimeZone('Europe/Istanbul'));
    
    // Find next Saturday
    $day_of_week = (int) $turkey_time->format('N'); // 1 (Monday) to 7 (Sunday)
    
    if ($day_of_week == 6) {
        // Today is Saturday
        $hour = (int) $turkey_time->format('H');
        if ($hour >= 20) {
            // Past 20:00, go to next Saturday
            $days_until_saturday = 7;
        } else {
            // Before 20:00, use today
            $days_until_saturday = 0;
        }
    } else {
        // Calculate days until next Saturday
        $days_until_saturday = (6 - $day_of_week + 7) % 7;
        if ($days_until_saturday == 0) {
            $days_until_saturday = 7;
        }
    }
    
    // Create next lottery date in Turkey timezone
    $next_lottery = clone $turkey_time;
    $next_lottery->modify("+{$days_until_saturday} days");
    $next_lottery->setTime(20, 0, 0); // 20:00 Turkey time
    
    // Convert to UTC for storage
    $next_lottery->setTimezone(new DateTimeZone('UTC'));
    
    // Return ISO 8601 format
    return $next_lottery->format('c'); // e.g., 2025-12-27T17:00:00+00:00
}

/**
 * Get server time in ISO 8601 UTC format
 */
function hdh_get_server_time_iso() {
    $now = new DateTime('now', new DateTimeZone('UTC'));
    return $now->format('c');
}

/**
 * AJAX endpoint: Get server time
 * Prevents client timezone issues
 */
function hdh_ajax_get_server_time() {
    wp_send_json_success(array(
        'serverTime' => hdh_get_server_time_iso(),
        'timestamp' => time()
    ));
}
add_action('wp_ajax_hdh_get_server_time', 'hdh_ajax_get_server_time');
add_action('wp_ajax_nopriv_hdh_get_server_time', 'hdh_ajax_get_server_time');

/**
 * Format date in Turkish locale
 * 
 * @param DateTime $dt DateTime object
 * @return string Formatted date in Turkish
 */
function hdh_format_date_turkish($dt) {
    $months_tr = array(
        1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
        5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
        9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
    );
    
    $day = $dt->format('d');
    $month = (int) $dt->format('m');
    $year = $dt->format('Y');
    $time = $dt->format('H:i');
    
    return sprintf('%s %s %s, %s', $day, $months_tr[$month], $year, $time);
}

/**
 * AJAX endpoint: Get lottery info
 */
function hdh_ajax_get_lottery_info() {
    $next_date = hdh_get_next_lottery_date();
    $server_time = hdh_get_server_time_iso();
    
    // Parse dates
    $next_dt = new DateTime($next_date);
    $now_dt = new DateTime($server_time);
    
    // Calculate time remaining
    $diff = $next_dt->getTimestamp() - $now_dt->getTimestamp();
    
    // Convert to Turkey timezone for display
    $next_dt_turkey = clone $next_dt;
    $next_dt_turkey->setTimezone(new DateTimeZone('Europe/Istanbul'));
    
    wp_send_json_success(array(
        'nextLotteryDate' => $next_date,
        'serverTime' => $server_time,
        'timeRemaining' => max(0, $diff),
        'lotteryDateFormatted' => hdh_format_date_turkish($next_dt_turkey) . ' (TSI)'
    ));
}
add_action('wp_ajax_hdh_get_lottery_info', 'hdh_ajax_get_lottery_info');
add_action('wp_ajax_nopriv_hdh_get_lottery_info', 'hdh_ajax_get_lottery_info');

/**
 * Admin function: Manually set lottery date
 * Usage: hdh_set_lottery_date('2025-12-28T17:00:00+00:00');
 */
function hdh_set_lottery_date($iso_date) {
    if (!current_user_can('administrator')) {
        return false;
    }
    
    // Validate ISO date
    $dt = DateTime::createFromFormat(DateTime::ISO8601, $iso_date);
    if (!$dt) {
        return false;
    }
    
    update_option('hdh_next_lottery_date', $iso_date);
    return true;
}

/**
 * Reset lottery date (calculate next Saturday)
 */
function hdh_reset_lottery_date() {
    if (!current_user_can('administrator')) {
        return false;
    }
    
    delete_option('hdh_next_lottery_date');
    return hdh_get_next_lottery_date();
}

