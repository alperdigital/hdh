<?php
/**
 * HDH: Custom Registration Handler
 * Handles user registration with Hay Day specific fields
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Custom registration page handler
 * Redirects to profile page instead of showing modal
 */
function hdh_handle_custom_registration() {
    if (isset($_GET['action']) && $_GET['action'] === 'register' && !is_user_logged_in()) {
        // Redirect to profile page instead of showing modal
        $referral_param = isset($_GET['ref']) ? '&ref=' . urlencode(sanitize_user($_GET['ref'])) : '';
        wp_redirect(home_url('/profil' . $referral_param));
        exit;
    }
}
add_action('template_redirect', 'hdh_handle_custom_registration', 1);

/**
 * Enqueue registration modal styles
 */
function hdh_enqueue_registration_modal_styles() {
    wp_add_inline_style('hdh-farm-style', hdh_get_registration_modal_css());
}

/**
 * Get registration modal CSS
 */
function hdh_get_registration_modal_css() {
    return '
        .hdh-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 10000;
            display: none;
        }
        .hdh-modal[style*="display: block"],
        .hdh-modal.show {
            display: block !important;
        }
        .hdh-modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
        }
        .hdh-modal-content {
            position: relative;
            max-width: 600px;
            margin: 30px auto;
            background: var(--clean-white);
            border-radius: var(--radius-large);
            border: 4px solid var(--wood-brown);
            box-shadow: var(--shadow-strong);
            z-index: 10001;
            max-height: 90vh;
            overflow-y: auto;
        }
        .hdh-modal-header {
            padding: 20px;
            border-bottom: 2px solid var(--wood-brown-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .hdh-modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }
        .hdh-modal-close {
            background: none;
            border: none;
            font-size: 2rem;
            cursor: pointer;
            color: var(--wood-brown);
            padding: 0;
            width: 30px;
            height: 30px;
            line-height: 1;
        }
        .hdh-modal-body {
            padding: 20px;
        }
        .hdh-tabs {
            display: flex;
            border-bottom: 2px solid var(--wood-brown-light);
            margin-bottom: 20px;
        }
        .hdh-tab {
            flex: 1;
            padding: 12px 20px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-weight: 600;
            color: var(--wood-brown-light);
            transition: all 0.3s ease;
        }
        .hdh-tab.active {
            color: var(--wood-brown);
            border-bottom-color: var(--farm-green);
        }
        .hdh-tab-content {
            display: none;
        }
        .hdh-tab-content.active {
            display: block;
        }
        .hdh-registration-notice {
            background: var(--farm-green-light);
            padding: 15px;
            border-radius: var(--radius-small);
            margin-bottom: 20px;
            border: 2px solid var(--farm-green);
        }
        .hdh-error-message {
            background: var(--warm-orange-light);
            color: var(--warm-orange-dark);
            padding: 15px;
            border-radius: var(--radius-small);
            margin-bottom: 20px;
            border: 2px solid var(--warm-orange);
        }
        .hdh-modal-body .form-row {
            margin-bottom: 20px;
        }
        .hdh-modal-body label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--wood-brown);
        }
        .hdh-modal-body .input {
            width: 100%;
            padding: 10px;
            border: 2px solid var(--wood-brown-light);
            border-radius: var(--radius-small);
            font-size: 15px;
            box-sizing: border-box;
        }
        .hdh-modal-body .input:focus {
            outline: none;
            border-color: var(--farm-green);
        }
        .hdh-modal-body small {
            display: block;
            margin-top: 5px;
            color: var(--wood-brown-light);
            font-size: 13px;
        }
        .hdh-phone-note {
            background: #FFF6D8;
            padding: 10px;
            border-radius: var(--radius-small);
            margin-top: 5px;
            border: 2px solid var(--wood-brown-light);
            font-size: 13px;
            color: var(--wood-brown);
        }
        .hdh-phone-note strong {
            color: var(--farm-green-dark);
        }
        .hdh-terms-checkbox {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin: 20px 0;
            padding: 15px;
            background: var(--soft-cream);
            border-radius: var(--radius-small);
            border: 2px solid var(--wood-brown-light);
        }
        .hdh-terms-checkbox input[type="checkbox"] {
            margin-top: 3px;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        .hdh-terms-checkbox label {
            font-weight: normal;
            cursor: pointer;
            line-height: 1.5;
        }
        .hdh-terms-checkbox a {
            color: var(--farm-green-dark);
            text-decoration: underline;
        }
        .hdh-terms-checkbox a:hover {
            color: var(--farm-green);
        }
        .form-submit {
            margin-top: 25px;
        }
        .button-primary {
            background: var(--farm-green);
            color: var(--clean-white);
            border: none;
            padding: 12px 30px;
            border-radius: var(--radius-medium);
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        .button-primary:hover:not(:disabled) {
            background: var(--farm-green-dark);
            transform: translateY(-2px);
        }
        .button-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        @media (max-width: 768px) {
            .hdh-modal-content {
                margin: 10px;
                max-width: calc(100% - 20px);
                max-height: 95vh;
            }
            .hdh-modal-header {
                padding: 15px;
            }
            .hdh-modal-header h2 {
                font-size: 1.2rem;
            }
            .hdh-modal-body {
                padding: 15px;
            }
            .hdh-tabs {
                flex-direction: row;
                overflow-x: auto;
            }
            .hdh-tab {
                min-width: 120px;
                padding: 10px 15px;
                font-size: 14px;
                white-space: nowrap;
            }
            .hdh-tab.active {
                border-bottom: 3px solid var(--farm-green);
                border-left: none;
            }
            .hdh-registration-notice,
            .hdh-error-message {
                font-size: 13px;
                padding: 12px;
            }
            .hdh-phone-note {
                font-size: 12px;
                padding: 8px;
            }
            .hdh-terms-checkbox {
                padding: 12px;
                font-size: 13px;
            }
            .hdh-terms-checkbox input[type="checkbox"] {
                width: 18px;
                height: 18px;
                flex-shrink: 0;
            }
            .form-row {
                margin-bottom: 15px;
            }
            .button-primary {
                padding: 14px 20px;
                font-size: 16px;
            }
        }
    ';
}

/**
 * Render registration modal/form
 */
function hdh_render_registration_modal() {
    $redirect_to_trade = isset($_GET['redirect']) && $_GET['redirect'] === 'trade';
    $terms_page_id = get_option('hdh_terms_page_id', 0);
    $terms_url = $terms_page_id ? get_permalink($terms_page_id) : home_url('/uyelik-sozlesmesi/');
    
    // Get referral username from query param
    $referral_username = isset($_GET['ref']) ? sanitize_user($_GET['ref']) : '';
    ?>
    <div id="hdh-registration-modal" class="hdh-modal" style="display: block;">
        <div class="hdh-modal-overlay"></div>
        <div class="hdh-modal-content">
            <div class="hdh-modal-header">
                <h2>🎁 Üye ol / Giriş yap</h2>
                <button class="hdh-modal-close" onclick="document.getElementById('hdh-registration-modal').style.display='none';">×</button>
            </div>
            <div class="hdh-modal-body">
                <!-- Tabs -->
                <div class="hdh-tabs">
                    <button type="button" class="hdh-tab active" data-tab="register">Üye Ol</button>
                    <button type="button" class="hdh-tab" data-tab="login">Giriş Yap</button>
                </div>
                
                <!-- Registration Tab -->
                <div id="register-tab" class="hdh-tab-content active">
                    <p class="hdh-registration-notice">
                        İlan oluşturmak için üye olmanız gerekiyor. Lütfen aşağıdaki bilgileri doldurun.
                    </p>
                    
                    <?php
                    $registration_errors = isset($_GET['registration_error']) ? $_GET['registration_error'] : '';
                    if ($registration_errors) {
                        echo '<div class="hdh-error-message">';
                        switch ($registration_errors) {
                            case 'empty_fields':
                                echo 'Lütfen tüm zorunlu alanları doldurun.';
                                break;
                            case 'farm_tag_exists':
                                echo 'Bu çiftlik etiketi zaten kullanılıyor.';
                                break;
                            case 'email_exists':
                                echo 'Bu e-posta adresi zaten kayıtlı.';
                                break;
                            case 'username_exists':
                                echo 'Bu çiftlik adı zaten kullanılıyor.';
                                break;
                            case 'terms_not_accepted':
                                echo 'Üyelik sözleşmesini kabul etmelisiniz.';
                                break;
                            default:
                                echo 'Kayıt sırasında bir hata oluştu. Lütfen tekrar deneyin.';
                        }
                        echo '</div>';
                    }
                    ?>
                    
                    <form name="hdh_registerform" id="hdh_registerform" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('hdh_custom_register', 'hdh_register_nonce'); ?>
                        <input type="hidden" name="action" value="hdh_custom_register">
                        <input type="hidden" name="redirect_to_trade" value="<?php echo $redirect_to_trade ? '1' : '0'; ?>">
                        
                        <p class="form-row">
                            <label for="farm_name">Çiftlik Adı <span class="required">*</span></label>
                            <input type="text" name="farm_name" id="farm_name" class="input" value="<?php echo isset($_POST['farm_name']) ? esc_attr($_POST['farm_name']) : ''; ?>" required />
                            <small>Bu ad kullanıcı adınız olarak kullanılacaktır</small>
                        </p>
                        
                        <p class="form-row">
                            <label for="user_email">E-posta Adresi <span class="required">*</span></label>
                            <input type="email" name="user_email" id="user_email" class="input" value="<?php echo isset($_POST['user_email']) ? esc_attr($_POST['user_email']) : ''; ?>" required />
                        </p>
                        
                        <p class="form-row">
                            <label for="farm_tag">Çiftlik Etiketi <span class="required">*</span></label>
                            <input type="text" name="farm_tag" id="farm_tag" class="input" value="<?php echo isset($_POST['farm_tag']) ? esc_attr($_POST['farm_tag']) : ''; ?>" required />
                            <small>Çiftliğinizin benzersiz etiketi (örnek: #ABC123)</small>
                        </p>
                        
                        <p class="form-row">
                            <label for="phone_number">Telefon Numarası</label>
                            <input type="tel" name="phone_number" id="phone_number" class="input" value="<?php echo isset($_POST['phone_number']) ? esc_attr($_POST['phone_number']) : ''; ?>" />
                            <div class="hdh-phone-note">
                                <strong>💡 İpucu:</strong> Telefon numaranızı belirtirseniz hesabınız <strong>mavi tikli</strong> olacaktır ve diğer kullanıcılar size daha çok güvenecektir.
                            </div>
                        </p>
                        
                        <p class="form-row">
                            <label for="referral_username">Referans (Opsiyonel)</label>
                            <input type="text" name="referral_username" id="referral_username" class="input" value="<?php echo isset($_POST['referral_username']) ? esc_attr($_POST['referral_username']) : esc_attr($referral_username); ?>" placeholder="Arkadaşınızın kullanıcı adı" />
                            <small>Eğer bir arkadaşınız tarafından davet edildiyseniz, onun kullanıcı adını girin</small>
                        </p>
                        
                        <p class="form-row">
                            <label for="user_pass">Şifre <span class="required">*</span></label>
                            <input type="password" name="user_pass" id="user_pass" class="input" value="" required />
                        </p>
                        
                        <div class="hdh-terms-checkbox">
                            <input type="checkbox" name="accept_terms" id="accept_terms" required />
                            <label for="accept_terms">
                                <a href="<?php echo esc_url($terms_url); ?>" target="_blank">Üyelik sözleşmesini</a> okudum ve onaylıyorum. <span class="required">*</span>
                            </label>
                        </div>
                        
                        <p class="form-row form-submit">
                            <input type="submit" name="hdh_submit" id="hdh_submit" class="button button-primary" value="Üye Ol" />
                        </p>
                    </form>
                </div>
                
                <!-- Login Tab -->
                <div id="login-tab" class="hdh-tab-content">
                    <p class="hdh-registration-notice">
                        Zaten üye misiniz? Giriş yaparak devam edin.
                    </p>
                    
                    <?php
                    $login_errors = isset($_GET['login_error']) ? $_GET['login_error'] : '';
                    if ($login_errors) {
                        echo '<div class="hdh-error-message">';
                        switch ($login_errors) {
                            case 'invalid_credentials':
                                echo 'Kullanıcı adı veya şifre hatalı.';
                                break;
                            default:
                                echo 'Giriş yapılırken bir hata oluştu.';
                        }
                        echo '</div>';
                    }
                    ?>
                    
                    <form name="hdh_loginform" id="hdh_loginform" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('hdh_custom_login', 'hdh_login_nonce'); ?>
                        <input type="hidden" name="action" value="hdh_custom_login">
                        <input type="hidden" name="redirect_to_trade" value="<?php echo $redirect_to_trade ? '1' : '0'; ?>">
                        
                        <p class="form-row">
                            <label for="login_username">Çiftlik Adı veya E-posta <span class="required">*</span></label>
                            <input type="text" name="log" id="login_username" class="input" value="<?php echo isset($_POST['log']) ? esc_attr($_POST['log']) : ''; ?>" required />
                        </p>
                        
                        <p class="form-row">
                            <label for="login_password">Şifre <span class="required">*</span></label>
                            <input type="password" name="pwd" id="login_password" class="input" value="" required />
                        </p>
                        
                        <p class="form-row">
                            <label>
                                <input type="checkbox" name="rememberme" value="forever" />
                                Beni hatırla
                            </label>
                        </p>
                        
                        <p class="form-row form-submit">
                            <input type="submit" name="hdh_login_submit" id="hdh_login_submit" class="button button-primary" value="Giriş Yap" />
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Tab switching
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('.hdh-tab');
        const tabContents = document.querySelectorAll('.hdh-tab-content');
        
        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                const targetTab = this.getAttribute('data-tab');
                
                // Remove active class from all tabs and contents
                tabs.forEach(function(t) { t.classList.remove('active'); });
                tabContents.forEach(function(c) { c.classList.remove('active'); });
                
                // Add active class to clicked tab and corresponding content
                this.classList.add('active');
                document.getElementById(targetTab + '-tab').classList.add('active');
            });
        });
        
        // Terms checkbox validation
        const termsCheckbox = document.getElementById('accept_terms');
        const submitButton = document.getElementById('hdh_submit');
        
        if (termsCheckbox && submitButton) {
            function toggleSubmitButton() {
                submitButton.disabled = !termsCheckbox.checked;
            }
            
            termsCheckbox.addEventListener('change', toggleSubmitButton);
            toggleSubmitButton(); // Initial check
        }
    });
    </script>
    <?php
}

/**
 * Handle custom registration form submission
 */
function hdh_handle_custom_registration_submit() {
    // Verify nonce
    if (!isset($_POST['hdh_register_nonce']) || !wp_verify_nonce($_POST['hdh_register_nonce'], 'hdh_custom_register')) {
        wp_redirect(home_url('/profil?registration_error=security'));
        exit;
    }
    
    $farm_name = isset($_POST['farm_name']) ? sanitize_user($_POST['farm_name']) : '';
    $user_email = isset($_POST['user_email']) ? sanitize_email($_POST['user_email']) : '';
    $user_pass = isset($_POST['user_pass']) ? $_POST['user_pass'] : '';
    $farm_tag = isset($_POST['farm_tag']) ? sanitize_text_field($_POST['farm_tag']) : '';
    $phone_number = isset($_POST['phone_number']) ? sanitize_text_field($_POST['phone_number']) : '';
    $referral_username = isset($_POST['referral_username']) ? sanitize_user($_POST['referral_username']) : '';
    $accept_terms = isset($_POST['accept_terms']) ? true : false;
    $redirect_to = isset($_POST['redirect_to']) ? esc_url_raw($_POST['redirect_to']) : home_url('/');
    $redirect_to_trade = isset($_POST['redirect_to_trade']) && $_POST['redirect_to_trade'] === '1';
    
    // Enhanced validation with detailed error messages
    $errors = array();
    
    // Validate farm name
    if (empty($farm_name)) {
        $errors['farm_name'] = 'farm_name_required';
    } elseif (strlen($farm_name) < 3) {
        $errors['farm_name'] = 'farm_name_too_short';
    } elseif (strlen($farm_name) > 50) {
        $errors['farm_name'] = 'farm_name_too_long';
    } elseif (username_exists($farm_name)) {
        $errors['farm_name'] = 'username_exists';
    }
    
    // Validate email
    if (empty($user_email)) {
        $errors['user_email'] = 'email_required';
    } elseif (!is_email($user_email)) {
        $errors['user_email'] = 'email_invalid';
    } elseif (email_exists($user_email)) {
        $errors['user_email'] = 'email_exists';
    }
    
    // Validate farm tag
    if (empty($farm_tag)) {
        $errors['farm_tag'] = 'farm_tag_required';
    } else {
        // Normalize farm tag
        $farm_tag = strtoupper(trim($farm_tag));
        
        // Farm tag format: #ABC123 or #ABCDEF
        if (!preg_match('/^#[A-Z0-9]{5,6}$/', $farm_tag)) {
            $errors['farm_tag'] = 'farm_tag_invalid_format';
        } else {
            // Check if farm tag exists
            $existing_user = get_users(array(
                'meta_key' => 'farm_tag',
                'meta_value' => $farm_tag,
                'number' => 1
            ));
            
            if (!empty($existing_user)) {
                $errors['farm_tag'] = 'farm_tag_exists';
            }
        }
    }
    
    // Validate phone number (optional)
    if (!empty($phone_number)) {
        $clean_phone = preg_replace('/[\s\-\(\)]/', '', $phone_number);
        if (!preg_match('/^(\+90|0)?5\d{9}$/', $clean_phone)) {
            $errors['phone_number'] = 'phone_invalid';
        }
    }
    
    // Validate password
    if (empty($user_pass)) {
        $errors['user_pass'] = 'password_required';
    } elseif (strlen($user_pass) < 6) {
        $errors['user_pass'] = 'password_too_short';
    } elseif (strlen($user_pass) > 100) {
        $errors['user_pass'] = 'password_too_long';
    }
    
    // Validate terms acceptance
    if (!$accept_terms) {
        $errors['accept_terms'] = 'terms_not_accepted';
    }
    
    // If there are errors, redirect with error codes
    if (!empty($errors)) {
        $error_string = implode(',', array_values($errors));
        wp_redirect(add_query_arg('registration_error', $error_string, $redirect_to));
        exit;
    }
    
    // Create user (farm_name as username)
    $user_id = wp_create_user($farm_name, $user_pass, $user_email);
    
    if (is_wp_error($user_id)) {
        wp_redirect(add_query_arg('registration_error', 'creation_failed', $redirect_to));
        exit;
    }
    
    // Save custom fields
    update_user_meta($user_id, 'farm_tag', $farm_tag);
    update_user_meta($user_id, 'farm_name', $farm_name);
    
    if (!empty($phone_number)) {
        update_user_meta($user_id, 'phone_number', $phone_number);
        update_user_meta($user_id, 'verified_account', true); // Mavi tikli hesap
    }
    
    // Process referral if provided
    if (!empty($referral_username)) {
        if (function_exists('hdh_process_referral')) {
            $referral_result = hdh_process_referral($user_id, $referral_username);
            // Don't fail registration if referral processing fails, just log it
            if (is_wp_error($referral_result) && defined('WP_DEBUG') && WP_DEBUG) {
                error_log('HDH Referral: ' . $referral_result->get_error_message());
            }
        }
    }
    
    // Save terms acceptance record (KVKK compliance)
    $terms_acceptance = array(
        'accepted_at' => current_time('mysql'),
        'ip_hash' => hash('sha256', $_SERVER['REMOTE_ADDR']),
        'version' => '1.0',
        'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 200) : ''
    );
    update_user_meta($user_id, 'hdh_terms_acceptance', $terms_acceptance);
    
    // Auto login
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);
    
    // Use new redirect system
    if (function_exists('hdh_redirect_after_auth')) {
        hdh_redirect_after_auth($user_id);
    } else {
        // Fallback: If redirect to trade, create trade from pending data
        if ($redirect_to_trade) {
            $transient_key = isset($_COOKIE['hdh_pending_trade_key']) ? $_COOKIE['hdh_pending_trade_key'] : '';
            
            if ($transient_key) {
                $pending_trade = get_transient($transient_key);
                
                if ($pending_trade) {
                    hdh_create_trade_from_pending($pending_trade, $transient_key);
                    exit;
                }
            }
        }
        
        // Redirect to specified URL or homepage
        wp_redirect($redirect_to);
        exit;
    }
}
add_action('admin_post_hdh_custom_register', 'hdh_handle_custom_registration_submit');
add_action('admin_post_nopriv_hdh_custom_register', 'hdh_handle_custom_registration_submit');

/**
 * Handle custom login form submission
 */
function hdh_handle_custom_login_submit() {
    // Verify nonce
    if (!isset($_POST['hdh_login_nonce']) || !wp_verify_nonce($_POST['hdh_login_nonce'], 'hdh_custom_login')) {
        wp_redirect(home_url('/profil?login_error=security'));
        exit;
    }
    
    $username = isset($_POST['log']) ? sanitize_user($_POST['log']) : '';
    $password = isset($_POST['pwd']) ? $_POST['pwd'] : '';
    $remember = isset($_POST['rememberme']);
    $redirect_to = isset($_POST['redirect_to']) ? esc_url_raw($_POST['redirect_to']) : home_url('/');
    $redirect_to_trade = isset($_POST['redirect_to_trade']) && $_POST['redirect_to_trade'] === '1';
    
    if (empty($username) || empty($password)) {
        wp_redirect(add_query_arg('login_error', 'empty_fields', $redirect_to));
        exit;
    }
    
    // Try to login
    $user = wp_authenticate($username, $password);
    
    if (is_wp_error($user)) {
        wp_redirect(add_query_arg('login_error', 'invalid_credentials', $redirect_to));
        exit;
    }
    
    // Login successful
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID, $remember);
    
    // Use new redirect system
    if (function_exists('hdh_redirect_after_auth')) {
        hdh_redirect_after_auth($user->ID);
    } else {
        // Fallback: If redirect to trade, create trade from pending data
        if ($redirect_to_trade) {
            $transient_key = isset($_COOKIE['hdh_pending_trade_key']) ? $_COOKIE['hdh_pending_trade_key'] : '';
            
            if ($transient_key) {
                $pending_trade = get_transient($transient_key);
                
                if ($pending_trade) {
                    hdh_create_trade_from_pending($pending_trade, $transient_key);
                    exit;
                }
            }
        }
        
        // Redirect to specified URL or homepage
        wp_redirect($redirect_to);
        exit;
    }
}
add_action('admin_post_hdh_custom_login', 'hdh_handle_custom_login_submit');
add_action('admin_post_nopriv_hdh_custom_login', 'hdh_handle_custom_login_submit');

/**
 * Create trade from pending data
 */
function hdh_create_trade_from_pending($pending_trade, $transient_key = '') {
    $wanted_item = isset($pending_trade['wanted_item']) ? $pending_trade['wanted_item'] : '';
    $wanted_qty = isset($pending_trade['wanted_qty']) ? $pending_trade['wanted_qty'] : 0;
    $trade_title = isset($pending_trade['trade_title']) ? $pending_trade['trade_title'] : '';
    $offer_item_data = isset($pending_trade['offer_item']) ? $pending_trade['offer_item'] : array();
    $offer_qty_data = isset($pending_trade['offer_qty']) ? $pending_trade['offer_qty'] : array();
    
    // Get offer items
    $offer_items_data = array();
    if (!empty($offer_item_data) && is_array($offer_item_data)) {
        foreach ($offer_item_data as $slug => $item_slug) {
            $slug = sanitize_text_field($slug);
            $qty = isset($offer_qty_data[$slug]) ? absint($offer_qty_data[$slug]) : 0;
            if ($qty > 0) {
                $offer_items_data[] = array(
                    'slug' => sanitize_text_field($item_slug),
                    'qty' => $qty
                );
            }
        }
    }
    
    // Validation
    if (empty($wanted_item) || $wanted_qty <= 0 || empty($trade_title) || empty($offer_items_data)) {
        wp_redirect(home_url('/?trade_error=invalid_data'));
        exit;
    }
    
    // Check if admin requires approval
    $require_approval = get_option('hdh_trade_require_approval', false);
    $post_status = $require_approval ? 'pending' : 'publish';
    
    // Create post
    $post_data = array(
        'post_title' => $trade_title,
        'post_content' => '',
        'post_status' => $post_status,
        'post_type' => 'hayday_trade',
        'post_author' => get_current_user_id(),
    );
    
    $post_id = wp_insert_post($post_data);
    
    if (is_wp_error($post_id)) {
        wp_redirect(home_url('/?trade_error=creation_failed'));
        exit;
    }
    
    // Save meta fields
    update_post_meta($post_id, '_hdh_wanted_item', $wanted_item);
    update_post_meta($post_id, '_hdh_wanted_qty', $wanted_qty);
    update_post_meta($post_id, '_hdh_trade_status', 'open');
    
    // Save offer items
    for ($i = 0; $i < 3; $i++) {
        if (isset($offer_items_data[$i])) {
            update_post_meta($post_id, '_hdh_offer_item_' . ($i + 1), $offer_items_data[$i]['slug']);
            update_post_meta($post_id, '_hdh_offer_qty_' . ($i + 1), $offer_items_data[$i]['qty']);
        }
    }
    
    // Clear transient and cookie
    if ($transient_key) {
        delete_transient($transient_key);
        setcookie('hdh_pending_trade_key', '', time() - 3600, '/');
    }
    
    // Redirect
    if ($post_status === 'pending') {
        wp_redirect(home_url('/?trade_success=pending'));
    } else {
        wp_redirect(get_permalink($post_id));
    }
    exit;
}
