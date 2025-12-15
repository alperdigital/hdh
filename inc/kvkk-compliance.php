<?php
/**
 * HDH: KVKK Compliance - Cookie Consent & GDPR
 */

if (!defined('ABSPATH')) exit;

/**
 * Show cookie consent banner
 */
function hdh_show_cookie_banner() {
    if (is_admin()) return;
    
    $user_id = get_current_user_id();
    $consent = hdh_get_cookie_consent($user_id);
    $policy_version = get_option('hdh_cookie_policy_version', '1.0');
    
    // Check if user needs to consent (no consent or version changed)
    if ($consent && isset($consent['version']) && $consent['version'] === $policy_version) {
        return; // Already consented to current version
    }
    
    // Check localStorage (client-side check)
    ?>
    <div id="hdh-cookie-banner" class="hdh-cookie-banner" style="display: none;">
        <div class="cookie-banner-content">
            <p class="cookie-banner-text">
                Bu site, deneyiminizi iyileştirmek için çerezler kullanır. 
                <a href="<?php echo esc_url(home_url('/gizlilik-politikasi')); ?>">Gizlilik Politikası</a>
            </p>
            <div class="cookie-banner-options">
                <label class="cookie-option">
                    <input type="checkbox" id="cookie-analytics" checked disabled>
                    <span>Gerekli Çerezler (Her zaman aktif)</span>
                </label>
                <label class="cookie-option">
                    <input type="checkbox" id="cookie-analytics-opt">
                    <span>Analitik Çerezler</span>
                </label>
                <label class="cookie-option">
                    <input type="checkbox" id="cookie-marketing-opt">
                    <span>Pazarlama Çerezleri</span>
                </label>
            </div>
            <div class="cookie-banner-actions">
                <button type="button" class="btn-cookie-accept-all" id="btn-cookie-accept-all">Tümünü Kabul Et</button>
                <button type="button" class="btn-cookie-save" id="btn-cookie-save">Seçimleri Kaydet</button>
            </div>
        </div>
    </div>
    <script>
    (function() {
        const banner = document.getElementById('hdh-cookie-banner');
        const saved = localStorage.getItem('hdh_cookie_consent');
        const policyVersion = '<?php echo esc_js($policy_version); ?>';
        
        if (saved) {
            const consent = JSON.parse(saved);
            if (consent.version === policyVersion) {
                return; // Already consented
            }
        }
        
        banner.style.display = 'block';
        
        document.getElementById('btn-cookie-accept-all').addEventListener('click', function() {
            const consent = {
                essential: true,
                analytics: true,
                marketing: true,
                timestamp: new Date().toISOString(),
                version: policyVersion
            };
            localStorage.setItem('hdh_cookie_consent', JSON.stringify(consent));
            hdhSaveCookieConsent(consent);
            banner.style.display = 'none';
        });
        
        document.getElementById('btn-cookie-save').addEventListener('click', function() {
            const consent = {
                essential: true,
                analytics: document.getElementById('cookie-analytics-opt').checked,
                marketing: document.getElementById('cookie-marketing-opt').checked,
                timestamp: new Date().toISOString(),
                version: policyVersion
            };
            localStorage.setItem('hdh_cookie_consent', JSON.stringify(consent));
            hdhSaveCookieConsent(consent);
            banner.style.display = 'none';
        });
        
        function hdhSaveCookieConsent(consent) {
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'hdh_save_cookie_consent',
                    consent: JSON.stringify(consent),
                    nonce: '<?php echo wp_create_nonce('hdh_cookie_consent'); ?>'
                })
            });
        }
    })();
    </script>
    <?php
}
add_action('wp_footer', 'hdh_show_cookie_banner');

/**
 * Get cookie consent
 */
function hdh_get_cookie_consent($user_id) {
    if (!$user_id) {
        return null;
    }
    $consent = get_user_meta($user_id, 'hdh_cookie_consent', true);
    return is_array($consent) ? $consent : null;
}

/**
 * Save cookie consent
 */
function hdh_save_cookie_consent($user_id, $consent_data) {
    if (!$user_id) return false;
    
    $consent = array(
        'essential' => true, // Always true
        'analytics' => isset($consent_data['analytics']) ? (bool) $consent_data['analytics'] : false,
        'marketing' => isset($consent_data['marketing']) ? (bool) $consent_data['marketing'] : false,
        'timestamp' => isset($consent_data['timestamp']) ? $consent_data['timestamp'] : current_time('mysql'),
        'version' => isset($consent_data['version']) ? $consent_data['version'] : get_option('hdh_cookie_policy_version', '1.0'),
    );
    
    update_user_meta($user_id, 'hdh_cookie_consent', $consent);
    
    // Log consent event
    if (function_exists('hdh_log_event')) {
        hdh_log_event($user_id, 'cookie_consent_given', $consent);
    }
    
    return true;
}

/**
 * Check if consent given
 */
function hdh_has_cookie_consent($user_id, $category = 'all') {
    $consent = hdh_get_cookie_consent($user_id);
    if (!$consent) return false;
    
    if ($category === 'all') {
        return true;
    }
    
    return isset($consent[$category]) && $consent[$category] === true;
}

/**
 * AJAX: Save cookie consent
 */
function hdh_ajax_save_cookie_consent() {
    check_ajax_referer('hdh_cookie_consent', 'nonce');
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(array('message' => 'Not logged in'));
    }
    
    $consent_data = json_decode(stripslashes($_POST['consent']), true);
    if (!$consent_data) {
        wp_send_json_error(array('message' => 'Invalid consent data'));
    }
    
    $saved = hdh_save_cookie_consent($user_id, $consent_data);
    
    if ($saved) {
        wp_send_json_success(array('message' => 'Consent saved'));
    } else {
        wp_send_json_error(array('message' => 'Failed to save'));
    }
}
add_action('wp_ajax_hdh_save_cookie_consent', 'hdh_ajax_save_cookie_consent');
add_action('wp_ajax_nopriv_hdh_save_cookie_consent', 'hdh_ajax_save_cookie_consent');

/**
 * GDPR Export
 */
function hdh_gdpr_export($user_id) {
    if (!$user_id) return false;
    
    $data = array(
        'user_id' => $user_id,
        'exported_at' => current_time('mysql'),
        'profile' => array(),
        'events' => array(),
        'trades' => array(),
        'reports' => array(),
        'disputes' => array(),
    );
    
    // Profile data
    $user = get_userdata($user_id);
    $data['profile'] = array(
        'username' => $user->user_login,
        'email' => $user->user_email,
        'display_name' => $user->display_name,
        'registered' => $user->user_registered,
    );
    
    // User meta
    $meta = get_user_meta($user_id);
    $data['profile']['meta'] = $meta;
    
    // Events
    if (function_exists('hdh_get_audit_trail')) {
        $data['events'] = hdh_get_audit_trail($user_id, 365); // Last year
    }
    
    // Trades
    $trades = get_posts(array(
        'post_type' => 'hayday_trade',
        'author' => $user_id,
        'posts_per_page' => -1,
        'post_status' => 'any',
    ));
    foreach ($trades as $trade) {
        $data['trades'][] = array(
            'id' => $trade->ID,
            'title' => $trade->post_title,
            'status' => $trade->post_status,
            'created' => $trade->post_date,
            'meta' => get_post_meta($trade->ID),
        );
    }
    
    return $data;
}

/**
 * GDPR Delete/Anonymize
 */
function hdh_gdpr_delete($user_id) {
    if (!$user_id) return false;
    
    $user = get_userdata($user_id);
    if (!$user) return false;
    
    // Anonymize name
    $hash = substr(md5($user_id . wp_salt()), 0, 8);
    $anonymized_name = 'Deleted User ' . $hash;
    
    // Anonymize email
    $anonymized_email = 'deleted_' . $hash . '@deleted.local';
    
    // Update user
    wp_update_user(array(
        'ID' => $user_id,
        'display_name' => $anonymized_name,
        'user_email' => $anonymized_email,
        'user_nicename' => 'deleted-' . $hash,
    ));
    
    // Set deletion flag
    update_user_meta($user_id, 'hdh_deleted_user', true);
    update_user_meta($user_id, 'hdh_deleted_at', current_time('mysql'));
    update_user_meta($user_id, 'hdh_deleted_original_email', $user->user_email);
    
    // Anonymize listings
    $trades = get_posts(array(
        'post_type' => 'hayday_trade',
        'author' => $user_id,
        'posts_per_page' => -1,
        'post_status' => 'any',
    ));
    foreach ($trades as $trade) {
        update_post_meta($trade->ID, '_hdh_author_anonymized', true);
    }
    
    // Schedule hard purge (90 days)
    wp_schedule_single_event(time() + (90 * DAY_IN_SECONDS), 'hdh_hard_purge_user', array($user_id));
    
    // Log deletion event
    if (function_exists('hdh_log_event')) {
        hdh_log_event($user_id, 'user_deleted', array(
            'deleted_at' => current_time('mysql'),
        ));
    }
    
    return true;
}

/**
 * Hard purge user data (after 90 days)
 */
function hdh_hard_purge_user($user_id) {
    // Delete all user events
    global $wpdb;
    $table = $wpdb->prefix . 'hdh_events';
    $wpdb->delete($table, array('user_id' => $user_id));
    
    // Delete user meta
    $wpdb->delete($wpdb->usermeta, array('user_id' => $user_id));
    
    // Delete user
    wp_delete_user($user_id);
}
add_action('hdh_hard_purge_user', 'hdh_hard_purge_user');

