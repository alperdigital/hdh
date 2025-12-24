<?php
/**
 * HDH: Chat Moderation Engine
 * Filters profanity, links, phone numbers, emails, and insults
 */

if (!defined('ABSPATH')) exit;

/**
 * ============================================
 * MODERATION WORD LISTS
 * ============================================
 */

/**
 * Get profanity word list
 * 
 * @return array Array of profanity words (Turkish)
 */
function hdh_get_profanity_words() {
    // Basic Turkish profanity list (can be extended via admin)
    $default_words = array(
        // Add basic Turkish profanity words here (keeping minimal for now)
        // This can be extended via admin panel
    );
    
    // Get from options (admin can add more)
    $custom_words = get_option('hdh_chat_profanity_words', array());
    
    return array_merge($default_words, $custom_words);
}

/**
 * Get insult word list
 * 
 * @return array Array of insult words (Turkish)
 */
function hdh_get_insult_words() {
    // Basic Turkish insult list
    $default_words = array(
        // Add basic Turkish insult words here (keeping minimal for now)
    );
    
    // Get from options (admin can add more)
    $custom_words = get_option('hdh_chat_insult_words', array());
    
    return array_merge($default_words, $custom_words);
}

/**
 * ============================================
 * MODERATION RULE FUNCTIONS
 * ============================================
 */

/**
 * Check for profanity
 * 
 * @param string $message Message text
 * @return bool True if profanity found
 */
function hdh_check_profanity($message) {
    $profanity_enabled = get_option('hdh_chat_filter_profanity', true);
    if (!$profanity_enabled) {
        return false;
    }
    
    $words = hdh_get_profanity_words();
    if (empty($words)) {
        return false;
    }
    
    $message_lower = mb_strtolower($message, 'UTF-8');
    
    foreach ($words as $word) {
        if (mb_strpos($message_lower, mb_strtolower($word, 'UTF-8')) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Check for links
 * 
 * @param string $message Message text
 * @return bool True if links found
 */
function hdh_check_links($message) {
    $links_enabled = get_option('hdh_chat_filter_links', true);
    if (!$links_enabled) {
        return false;
    }
    
    // Patterns for URLs
    $patterns = array(
        '/https?:\/\/[^\s]+/i',           // http:// or https://
        '/www\.[^\s]+/i',                 // www.
        '/[a-z0-9-]+\.(com|net|org|io|co|tr|gov|edu)[^\s]*/i', // domain.com
        '/bit\.ly\/[^\s]+/i',             // bit.ly shortener
        '/t\.co\/[^\s]+/i',               // t.co shortener
        '/tinyurl\.com\/[^\s]+/i',        // tinyurl.com
    );
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $message)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Check for phone numbers
 * 
 * @param string $message Message text
 * @return bool True if phone number found
 */
function hdh_check_phone($message) {
    $phone_enabled = get_option('hdh_chat_filter_phone', true);
    if (!$phone_enabled) {
        return false;
    }
    
    // Patterns for phone numbers (Turkish and international formats)
    $patterns = array(
        '/\+?90\s?[0-9]{3}\s?[0-9]{3}\s?[0-9]{2}\s?[0-9]{2}/', // +90 5XX XXX XX XX
        '/0?5[0-9]{2}\s?[0-9]{3}\s?[0-9]{2}\s?[0-9]{2}/',     // 05XX XXX XX XX
        '/\+?[0-9]{1,3}\s?[0-9]{3,4}\s?[0-9]{3,4}\s?[0-9]{3,4}/', // International
        '/\([0-9]{3}\)\s?[0-9]{3}-[0-9]{4}/',                 // (XXX) XXX-XXXX
    );
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $message)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Check for email addresses
 * 
 * @param string $message Message text
 * @return bool True if email found
 */
function hdh_check_email($message) {
    $email_enabled = get_option('hdh_chat_filter_email', true);
    if (!$email_enabled) {
        return false;
    }
    
    // Email pattern
    $pattern = '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/';
    
    return preg_match($pattern, $message) === 1;
}

/**
 * Check for insults
 * 
 * @param string $message Message text
 * @return bool True if insult found
 */
function hdh_check_insults($message) {
    $insults_enabled = get_option('hdh_chat_filter_insults', true);
    if (!$insults_enabled) {
        return false;
    }
    
    $words = hdh_get_insult_words();
    if (empty($words)) {
        return false;
    }
    
    $message_lower = mb_strtolower($message, 'UTF-8');
    
    foreach ($words as $word) {
        if (mb_strpos($message_lower, mb_strtolower($word, 'UTF-8')) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * ============================================
 * MAIN MODERATION FUNCTION
 * ============================================
 */

/**
 * Moderate message
 * 
 * @param string $message Message text
 * @param int $user_id User ID
 * @return array Moderation result with status, flags, and censored_message
 */
function hdh_moderate_message($message, $user_id) {
    $flags = array();
    $censored_message = $message;
    
    // Run all checks
    if (hdh_check_profanity($message)) {
        $flags[] = 'profanity';
    }
    
    if (hdh_check_links($message)) {
        $flags[] = 'links';
    }
    
    if (hdh_check_phone($message)) {
        $flags[] = 'phone';
    }
    
    if (hdh_check_email($message)) {
        $flags[] = 'email';
    }
    
    if (hdh_check_insults($message)) {
        $flags[] = 'insults';
    }
    
    // If no violations, return published
    if (empty($flags)) {
        return array(
            'status' => 'published',
            'flags' => array(),
            'censored_message' => $message,
        );
    }
    
    // Get action on violation (censor or block)
    $action_on_violation = get_option('hdh_chat_action_on_violation', 'censor');
    
    if ($action_on_violation === 'block') {
        return array(
            'status' => 'blocked',
            'flags' => $flags,
            'censored_message' => $message,
        );
    }
    
    // Censor: replace violating segments with ***
    $censored_message = hdh_censor_message($message, $flags);
    
    return array(
        'status' => 'censored',
        'flags' => $flags,
        'censored_message' => $censored_message,
    );
}

/**
 * Censor message by replacing violating segments
 * 
 * @param string $message Original message
 * @param array $flags Array of violation flags
 * @return string Censored message
 */
function hdh_censor_message($message, $flags) {
    $censored = $message;
    
    // Censor links
    if (in_array('links', $flags)) {
        $patterns = array(
            '/https?:\/\/[^\s]+/i',
            '/www\.[^\s]+/i',
            '/[a-z0-9-]+\.(com|net|org|io|co|tr|gov|edu)[^\s]*/i',
            '/bit\.ly\/[^\s]+/i',
            '/t\.co\/[^\s]+/i',
            '/tinyurl\.com\/[^\s]+/i',
        );
        foreach ($patterns as $pattern) {
            $censored = preg_replace($pattern, '***', $censored);
        }
    }
    
    // Censor phone numbers
    if (in_array('phone', $flags)) {
        $patterns = array(
            '/\+?90\s?[0-9]{3}\s?[0-9]{3}\s?[0-9]{2}\s?[0-9]{2}/',
            '/0?5[0-9]{2}\s?[0-9]{3}\s?[0-9]{2}\s?[0-9]{2}/',
            '/\+?[0-9]{1,3}\s?[0-9]{3,4}\s?[0-9]{3,4}\s?[0-9]{3,4}/',
            '/\([0-9]{3}\)\s?[0-9]{3}-[0-9]{4}/',
        );
        foreach ($patterns as $pattern) {
            $censored = preg_replace($pattern, '***', $censored);
        }
    }
    
    // Censor emails
    if (in_array('email', $flags)) {
        $pattern = '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/';
        $censored = preg_replace($pattern, '***', $censored);
    }
    
    // Censor profanity and insults (replace words with ***)
    if (in_array('profanity', $flags) || in_array('insults', $flags)) {
        $words = array();
        if (in_array('profanity', $flags)) {
            $words = array_merge($words, hdh_get_profanity_words());
        }
        if (in_array('insults', $flags)) {
            $words = array_merge($words, hdh_get_insult_words());
        }
        
        foreach ($words as $word) {
            $pattern = '/\b' . preg_quote($word, '/') . '\b/i';
            $censored = preg_replace($pattern, '***', $censored);
        }
    }
    
    return $censored;
}

/**
 * ============================================
 * WARNING SYSTEM
 * ============================================
 */

/**
 * Increment chat warning for user
 * 
 * @param int $user_id User ID
 * @param int|null $message_id Message ID (if related to a message)
 * @param string $warning_type Warning type
 * @return int New strike count
 */
function hdh_increment_chat_warning($user_id, $message_id = null, $warning_type = 'violation') {
    global $wpdb;
    $warnings_table = $wpdb->prefix . 'hdh_chat_warnings';
    
    // Get current strike count for user
    $current_strikes = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(strike_count) FROM {$warnings_table}
         WHERE user_id = %d",
        $user_id
    ));
    
    $current_strikes = (int) $current_strikes;
    $new_strikes = $current_strikes + 1;
    
    // Create warning record
    $wpdb->insert(
        $warnings_table,
        array(
            'user_id' => $user_id,
            'message_id' => $message_id,
            'warning_type' => sanitize_key($warning_type),
            'strike_count' => 1,
            'created_at' => current_time('mysql'),
        ),
        array('%d', '%d', '%s', '%d', '%s')
    );
    
    // Check thresholds and apply mute/ban
    $third_strike_threshold = (int) get_option('hdh_chat_3rd_strike_mute_minutes', 10);
    $fifth_strike_threshold = (int) get_option('hdh_chat_5th_strike_mute_hours', 24);
    
    if ($new_strikes >= 3 && $new_strikes < 5) {
        // 3rd strike: mute for configured minutes
        $mute_until = date('Y-m-d H:i:s', current_time('timestamp') + ($third_strike_threshold * 60));
        update_user_meta($user_id, 'hdh_chat_muted_until', $mute_until);
    } elseif ($new_strikes >= 5) {
        // 5th strike: mute for configured hours
        $mute_until = date('Y-m-d H:i:s', current_time('timestamp') + ($fifth_strike_threshold * 3600));
        update_user_meta($user_id, 'hdh_chat_muted_until', $mute_until);
    }
    
    return $new_strikes;
}

/**
 * Check if user is chat banned
 * 
 * @param int $user_id User ID
 * @return bool True if banned
 */
function hdh_is_user_chat_banned($user_id) {
    $banned = get_user_meta($user_id, 'hdh_chat_banned', true);
    if ($banned) {
        $banned_until = get_user_meta($user_id, 'hdh_chat_banned_until', true);
        if ($banned_until && strtotime($banned_until) < current_time('timestamp')) {
            // Ban expired, remove it
            delete_user_meta($user_id, 'hdh_chat_banned');
            delete_user_meta($user_id, 'hdh_chat_banned_until');
            return false;
        }
        return true;
    }
    return false;
}

/**
 * Check if user is chat muted
 * 
 * @param int $user_id User ID
 * @return bool True if muted
 */
function hdh_is_user_chat_muted($user_id) {
    $muted_until = get_user_meta($user_id, 'hdh_chat_muted_until', true);
    if ($muted_until) {
        if (strtotime($muted_until) > current_time('timestamp')) {
            return true; // Still muted
        } else {
            // Mute expired, remove it
            delete_user_meta($user_id, 'hdh_chat_muted_until');
            return false;
        }
    }
    return false;
}

/**
 * Get user warning count
 * 
 * @param int $user_id User ID
 * @return int Total strike count
 */
function hdh_get_user_chat_warning_count($user_id) {
    global $wpdb;
    $warnings_table = $wpdb->prefix . 'hdh_chat_warnings';
    
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(strike_count) FROM {$warnings_table}
         WHERE user_id = %d",
        $user_id
    ));
    
    return (int) $count;
}
