<?php
/**
 * reCAPTCHA Integration
 */

// Add reCAPTCHA settings to customizer
function mi_recaptcha_customizer($wp_customize) {
    $wp_customize->add_section('mi_recaptcha_settings', array(
        'title' => __('reCAPTCHA Ayarları', 'mi-theme'),
        'panel' => 'mi_theme_panel',
        'priority' => 90,
    ));
    
    $wp_customize->add_setting('mi_recaptcha_site_key', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    
    $wp_customize->add_control('mi_recaptcha_site_key', array(
        'label' => __('reCAPTCHA Site Key', 'mi-theme'),
        'section' => 'mi_recaptcha_settings',
        'type' => 'text',
        'description' => __('Google reCAPTCHA Site Key\'inizi girin', 'mi-theme'),
    ));
    
    $wp_customize->add_setting('mi_recaptcha_secret_key', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    
    $wp_customize->add_control('mi_recaptcha_secret_key', array(
        'label' => __('reCAPTCHA Secret Key', 'mi-theme'),
        'section' => 'mi_recaptcha_settings',
        'type' => 'text',
        'description' => __('Google reCAPTCHA Secret Key\'inizi girin', 'mi-theme'),
    ));
    
    $wp_customize->add_setting('mi_recaptcha_version', array(
        'default' => 'v3',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    
    $wp_customize->add_control('mi_recaptcha_version', array(
        'label' => __('reCAPTCHA Versiyonu', 'mi-theme'),
        'section' => 'mi_recaptcha_settings',
        'type' => 'select',
        'choices' => array(
            'v2' => 'reCAPTCHA v2',
            'v3' => 'reCAPTCHA v3',
        ),
    ));
}
add_action('customize_register', 'mi_recaptcha_customizer');

// Enqueue reCAPTCHA script
function mi_enqueue_recaptcha() {
    $site_key = get_theme_mod('mi_recaptcha_site_key', '');
    $version = get_theme_mod('mi_recaptcha_version', 'v3');
    
    if (empty($site_key)) {
        return;
    }
    
    if ($version === 'v3') {
        wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js?render=' . esc_attr($site_key), array(), null, true);
    } else {
        wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js', array(), null, true);
    }
}
add_action('wp_enqueue_scripts', 'mi_enqueue_recaptcha');

// Add reCAPTCHA to comment form
function mi_recaptcha_comment_form() {
    $site_key = get_theme_mod('mi_recaptcha_site_key', '');
    $version = get_theme_mod('mi_recaptcha_version', 'v3');
    
    if (empty($site_key)) {
        return;
    }
    
    if ($version === 'v3') {
        ?>
        <input type="hidden" name="recaptcha_response" id="recaptcha_response">
        <script>
        grecaptcha.ready(function() {
            grecaptcha.execute('<?php echo esc_js($site_key); ?>', {action: 'comment'}).then(function(token) {
                document.getElementById('recaptcha_response').value = token;
            });
        });
        </script>
        <?php
    } else {
        ?>
        <div class="g-recaptcha" data-sitekey="<?php echo esc_attr($site_key); ?>"></div>
        <?php
    }
}
add_action('comment_form_after_fields', 'mi_recaptcha_comment_form');
add_action('comment_form_logged_in_after', 'mi_recaptcha_comment_form');

// Verify reCAPTCHA on comment submit
function mi_verify_recaptcha_comment($approved, $commentdata) {
    $secret_key = get_theme_mod('mi_recaptcha_secret_key', '');
    $version = get_theme_mod('mi_recaptcha_version', 'v3');
    
    if (empty($secret_key)) {
        return $approved;
    }
    
    $recaptcha_response = isset($_POST['recaptcha_response']) ? $_POST['recaptcha_response'] : '';
    
    if (empty($recaptcha_response)) {
        wp_die(__('reCAPTCHA doğrulaması başarısız. Lütfen tekrar deneyin.', 'mi-theme'));
        return false;
    }
    
    $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
    $response = wp_remote_post($verify_url, array(
        'body' => array(
            'secret' => $secret_key,
            'response' => $recaptcha_response,
            'remoteip' => $_SERVER['REMOTE_ADDR'],
        ),
    ));
    
    $result = json_decode(wp_remote_retrieve_body($response));
    
    if (!$result->success) {
        wp_die(__('reCAPTCHA doğrulaması başarısız. Lütfen tekrar deneyin.', 'mi-theme'));
        return false;
    }
    
    if ($version === 'v3' && $result->score < 0.5) {
        wp_die(__('Güvenlik kontrolü başarısız. Lütfen tekrar deneyin.', 'mi-theme'));
        return false;
    }
    
    return $approved;
}
add_filter('pre_comment_approved', 'mi_verify_recaptcha_comment', 10, 2);

// Add reCAPTCHA to contact form
function mi_recaptcha_contact_form() {
    $site_key = get_theme_mod('mi_recaptcha_site_key', '');
    $version = get_theme_mod('mi_recaptcha_version', 'v3');
    
    if (empty($site_key)) {
        return;
    }
    
    if ($version === 'v3') {
        ?>
        <input type="hidden" name="recaptcha_response" id="recaptcha_response_contact">
        <script>
        grecaptcha.ready(function() {
            grecaptcha.execute('<?php echo esc_js($site_key); ?>', {action: 'contact'}).then(function(token) {
                document.getElementById('recaptcha_response_contact').value = token;
            });
        });
        </script>
        <?php
    } else {
        ?>
        <div class="g-recaptcha" data-sitekey="<?php echo esc_attr($site_key); ?>"></div>
        <?php
    }
}
add_action('mi_contact_form_before_submit', 'mi_recaptcha_contact_form');

