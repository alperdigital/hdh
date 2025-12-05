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
 * Add custom fields to registration form
 */
function hdh_add_registration_fields() {
    ?>
    <p class="form-row">
        <label for="farm_code">Çiftlik Kodu <span class="required">*</span></label>
        <input type="text" name="farm_code" id="farm_code" class="input" value="<?php echo isset($_POST['farm_code']) ? esc_attr($_POST['farm_code']) : ''; ?>" required />
    </p>
    
    <p class="form-row">
        <label for="farm_name">Çiftlik İsmi <span class="required">*</span></label>
        <input type="text" name="farm_name" id="farm_name" class="input" value="<?php echo isset($_POST['farm_name']) ? esc_attr($_POST['farm_name']) : ''; ?>" required />
    </p>
    
    <p class="form-row">
        <label for="contact_info">E-posta veya Telefon Numarası <span class="required">*</span></label>
        <input type="text" name="contact_info" id="contact_info" class="input" value="<?php echo isset($_POST['contact_info']) ? esc_attr($_POST['contact_info']) : ''; ?>" required />
        <small>E-posta adresi veya telefon numarası girebilirsiniz</small>
    </p>
    <?php
}
add_action('register_form', 'hdh_add_registration_fields');

/**
 * Validate custom registration fields
 */
function hdh_validate_registration_fields($errors, $sanitized_user_login, $user_email) {
    if (empty($_POST['farm_code'])) {
        $errors->add('farm_code_error', '<strong>Hata:</strong> Çiftlik kodu gereklidir.');
    }
    
    if (empty($_POST['farm_name'])) {
        $errors->add('farm_name_error', '<strong>Hata:</strong> Çiftlik ismi gereklidir.');
    }
    
    if (empty($_POST['contact_info'])) {
        $errors->add('contact_info_error', '<strong>Hata:</strong> E-posta veya telefon numarası gereklidir.');
    }
    
    // Check if farm code already exists
    if (!empty($_POST['farm_code'])) {
        $existing_user = get_users(array(
            'meta_key' => 'farm_code',
            'meta_value' => sanitize_text_field($_POST['farm_code']),
            'number' => 1
        ));
        
        if (!empty($existing_user)) {
            $errors->add('farm_code_exists', '<strong>Hata:</strong> Bu çiftlik kodu zaten kullanılıyor.');
        }
    }
    
    return $errors;
}
add_filter('registration_errors', 'hdh_validate_registration_fields', 10, 3);

/**
 * Save custom fields after registration
 */
function hdh_save_registration_fields($user_id) {
    if (isset($_POST['farm_code'])) {
        update_user_meta($user_id, 'farm_code', sanitize_text_field($_POST['farm_code']));
    }
    
    if (isset($_POST['farm_name'])) {
        update_user_meta($user_id, 'farm_name', sanitize_text_field($_POST['farm_name']));
    }
    
    if (isset($_POST['contact_info'])) {
        update_user_meta($user_id, 'contact_info', sanitize_text_field($_POST['contact_info']));
    }
}
add_action('user_register', 'hdh_save_registration_fields');

/**
 * Custom registration page handler
 */
function hdh_handle_custom_registration() {
    if (isset($_GET['action']) && $_GET['action'] === 'register' && !is_user_logged_in()) {
        // Show registration form
        add_action('wp_footer', 'hdh_render_registration_modal');
        // Enqueue modal styles
        add_action('wp_enqueue_scripts', 'hdh_enqueue_registration_modal_styles');
    }
}
add_action('template_redirect', 'hdh_handle_custom_registration');

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
            max-width: 500px;
            margin: 50px auto;
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
        }
        .button-primary:hover {
            background: var(--farm-green-dark);
            transform: translateY(-2px);
        }
    ';
}

/**
 * Render registration modal/form
 */
function hdh_render_registration_modal() {
    $redirect_to_trade = isset($_GET['redirect']) && $_GET['redirect'] === 'trade';
    ?>
    <div id="hdh-registration-modal" class="hdh-modal" style="display: block;">
        <div class="hdh-modal-overlay"></div>
        <div class="hdh-modal-content">
            <div class="hdh-modal-header">
                <h2>🌾 Hay Day Takas Merkezi - Üye Ol</h2>
                <button class="hdh-modal-close" onclick="document.getElementById('hdh-registration-modal').style.display='none';">×</button>
            </div>
            <div class="hdh-modal-body">
                <p class="hdh-registration-notice">
                    İlan oluşturmak için üye olmanız gerekiyor. Lütfen aşağıdaki bilgileri doldurun.
                </p>
                
                <?php
                $registration_errors = isset($_GET['registration_error']) ? $_GET['registration_error'] : '';
                if ($registration_errors) {
                    echo '<div class="hdh-error-message">';
                    switch ($registration_errors) {
                        case 'empty_fields':
                            echo 'Lütfen tüm alanları doldurun.';
                            break;
                        case 'farm_code_exists':
                            echo 'Bu çiftlik kodu zaten kullanılıyor.';
                            break;
                        case 'email_exists':
                            echo 'Bu e-posta adresi zaten kayıtlı.';
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
                        <label for="user_login">Kullanıcı Adı <span class="required">*</span></label>
                        <input type="text" name="user_login" id="user_login" class="input" value="<?php echo isset($_POST['user_login']) ? esc_attr($_POST['user_login']) : ''; ?>" size="20" required />
                    </p>
                    
                    <p class="form-row">
                        <label for="user_email">E-posta <span class="required">*</span></label>
                        <input type="email" name="user_email" id="user_email" class="input" value="<?php echo isset($_POST['user_email']) ? esc_attr($_POST['user_email']) : ''; ?>" size="25" required />
                    </p>
                    
                    <p class="form-row">
                        <label for="farm_code">Çiftlik Kodu <span class="required">*</span></label>
                        <input type="text" name="farm_code" id="farm_code" class="input" value="<?php echo isset($_POST['farm_code']) ? esc_attr($_POST['farm_code']) : ''; ?>" required />
                    </p>
                    
                    <p class="form-row">
                        <label for="farm_name">Çiftlik İsmi <span class="required">*</span></label>
                        <input type="text" name="farm_name" id="farm_name" class="input" value="<?php echo isset($_POST['farm_name']) ? esc_attr($_POST['farm_name']) : ''; ?>" required />
                    </p>
                    
                    <p class="form-row">
                        <label for="contact_info">E-posta veya Telefon Numarası <span class="required">*</span></label>
                        <input type="text" name="contact_info" id="contact_info" class="input" value="<?php echo isset($_POST['contact_info']) ? esc_attr($_POST['contact_info']) : ''; ?>" required />
                        <small>E-posta adresi veya telefon numarası girebilirsiniz</small>
                    </p>
                    
                    <p class="form-row">
                        <label for="user_pass">Şifre <span class="required">*</span></label>
                        <input type="password" name="user_pass" id="user_pass" class="input" value="" size="20" required />
                    </p>
                    
                    <p class="form-row form-submit">
                        <input type="submit" name="hdh_submit" id="hdh_submit" class="button button-primary" value="Üye Ol" />
                    </p>
                </form>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Handle custom registration form submission
 */
function hdh_handle_custom_registration_submit() {
    // Verify nonce
    if (!isset($_POST['hdh_register_nonce']) || !wp_verify_nonce($_POST['hdh_register_nonce'], 'hdh_custom_register')) {
        wp_redirect(home_url('/?action=register&registration_error=security'));
        exit;
    }
    
    $user_login = isset($_POST['user_login']) ? sanitize_user($_POST['user_login']) : '';
    $user_email = isset($_POST['user_email']) ? sanitize_email($_POST['user_email']) : '';
    $user_pass = isset($_POST['user_pass']) ? $_POST['user_pass'] : '';
    $farm_code = isset($_POST['farm_code']) ? sanitize_text_field($_POST['farm_code']) : '';
    $farm_name = isset($_POST['farm_name']) ? sanitize_text_field($_POST['farm_name']) : '';
    $contact_info = isset($_POST['contact_info']) ? sanitize_text_field($_POST['contact_info']) : '';
    $redirect_to_trade = isset($_POST['redirect_to_trade']) && $_POST['redirect_to_trade'] === '1';
    
    // Validation
    if (empty($user_login) || empty($user_email) || empty($user_pass) || empty($farm_code) || empty($farm_name) || empty($contact_info)) {
        wp_redirect(home_url('/?action=register&redirect=' . ($redirect_to_trade ? 'trade' : '') . '&registration_error=empty_fields'));
        exit;
    }
    
    // Check if farm code exists
    $existing_user = get_users(array(
        'meta_key' => 'farm_code',
        'meta_value' => $farm_code,
        'number' => 1
    ));
    
    if (!empty($existing_user)) {
        wp_redirect(home_url('/?action=register&redirect=' . ($redirect_to_trade ? 'trade' : '') . '&registration_error=farm_code_exists'));
        exit;
    }
    
    // Check if email exists
    if (email_exists($user_email)) {
        wp_redirect(home_url('/?action=register&redirect=' . ($redirect_to_trade ? 'trade' : '') . '&registration_error=email_exists'));
        exit;
    }
    
    // Check if username exists
    if (username_exists($user_login)) {
        wp_redirect(home_url('/?action=register&redirect=' . ($redirect_to_trade ? 'trade' : '') . '&registration_error=username_exists'));
        exit;
    }
    
    // Create user
    $user_id = wp_create_user($user_login, $user_pass, $user_email);
    
    if (is_wp_error($user_id)) {
        wp_redirect(home_url('/?action=register&redirect=' . ($redirect_to_trade ? 'trade' : '') . '&registration_error=creation_failed'));
        exit;
    }
    
    // Save custom fields
    update_user_meta($user_id, 'farm_code', $farm_code);
    update_user_meta($user_id, 'farm_name', $farm_name);
    update_user_meta($user_id, 'contact_info', $contact_info);
    
    // Auto login
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);
    
    // If redirect to trade, create trade from pending data
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
    
    // Redirect to homepage
    wp_redirect(home_url('/'));
    exit;
}
add_action('admin_post_hdh_custom_register', 'hdh_handle_custom_registration_submit');
add_action('admin_post_nopriv_hdh_custom_register', 'hdh_handle_custom_registration_submit');

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

