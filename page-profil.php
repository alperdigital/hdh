<?php
/**
 * Template Name: Profil
 * HDH: Profile page with clean login/register for logged-out users
 */
get_header();

// Check if user is logged in
$is_logged_in = is_user_logged_in();

if (!$is_logged_in) {
    // LOGGED OUT: Show clean login/register screen
    ?>
    <main class="profile-page-main">
        <div class="container">
            <div class="auth-screen">
                <div class="auth-header">
                    <h1 class="auth-title"><?php echo esc_html(hdh_get_content('auth', 'login_title', 'Hesabına Giriş Yap')); ?></h1>
                    <p class="auth-subtitle"><?php echo esc_html(hdh_get_content('auth', 'login_subtitle', 'Bilet biriktirmek ve hediyeleşmek için giriş yap.')); ?></p>
                </div>
                
                <!-- Tab Switcher -->
                <div class="auth-tabs">
                    <button type="button" class="auth-tab" data-tab="login">
                        Giriş Yap
                    </button>
                    <button type="button" class="auth-tab active" data-tab="register">
                        Üye Ol
                    </button>
                </div>
                
                <!-- Login Form -->
                <div id="login-form-container" class="auth-form-container">
                    <?php
                    $login_error = isset($_GET['login_error']) ? $_GET['login_error'] : '';
                    if ($login_error) {
                        echo '<div class="auth-message auth-error">';
                        switch ($login_error) {
                            case 'invalid_credentials':
                                echo esc_html(hdh_get_content('auth', 'error_invalid_credentials', 'Kullanıcı adı veya şifre hatalı.'));
                                break;
                            case 'empty_fields':
                                echo esc_html(hdh_get_content('auth', 'error_empty_fields', 'Lütfen tüm alanları doldurun.'));
                                break;
                            default:
                                echo esc_html(hdh_get_content('auth', 'error_generic', 'Giriş yapılırken bir hata oluştu.'));
                        }
                        echo '</div>';
                    }
                    ?>
                    
                    <form class="auth-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('hdh_custom_login', 'hdh_login_nonce'); ?>
                        <input type="hidden" name="action" value="hdh_custom_login">
                        <input type="hidden" name="redirect_to" value="<?php echo esc_url(home_url('/profil')); ?>">
                        
                        <div class="auth-field">
                            <label for="login_username" class="auth-label">Çiftlik Adı veya E-posta</label>
                            <input 
                                type="text" 
                                id="login_username" 
                                name="log" 
                                class="auth-input" 
                                required 
                                autocomplete="username"
                                placeholder="Çiftlik adınız veya e-posta"
                            >
                        </div>
                        
                        <div class="auth-field">
                            <label for="login_password" class="auth-label">Şifre</label>
                            <div class="auth-password-wrapper">
                                <input 
                                    type="password" 
                                    id="login_password" 
                                    name="pwd" 
                                    class="auth-input" 
                                    required 
                                    autocomplete="current-password"
                                    placeholder="<?php echo esc_attr(hdh_get_content('auth', 'password_placeholder', 'Şifreniz')); ?>"
                                >
                                <button type="button" class="auth-password-toggle" data-target="login_password">
                                    <span class="toggle-show">👁️</span>
                                    <span class="toggle-hide" style="display:none;">🙈</span>
                                </button>
                            </div>
                        </div>
                        
                        <div class="auth-field-checkbox">
                            <label class="auth-checkbox-label">
                                <input type="checkbox" name="rememberme" value="forever">
                                <span><?php echo esc_html(hdh_get_content('auth', 'remember_me_text', 'Beni hatırla')); ?></span>
                            </label>
                        </div>
                        
                        <button type="submit" class="auth-submit"><?php echo esc_html(hdh_get_content('auth', 'login_button_text', 'Giriş Yap')); ?></button>
                    </form>
                </div>
                
                <!-- Register Form -->
                <div id="register-form-container" class="auth-form-container active">
                    <?php
                    $register_error = isset($_GET['registration_error']) ? $_GET['registration_error'] : '';
                    if ($register_error) {
                        $error_messages = array(
                            'farm_name_required' => 'Çiftlik adı gereklidir',
                            'farm_name_too_short' => 'Çiftlik adı en az 3 karakter olmalıdır',
                            'farm_name_too_long' => 'Çiftlik adı en fazla 50 karakter olabilir',
                            'username_exists' => 'Bu çiftlik adı zaten kullanılıyor',
                            'email_required' => 'E-posta adresi gereklidir',
                            'email_invalid' => 'Geçerli bir e-posta adresi girin',
                            'email_exists' => 'Bu e-posta adresi zaten kayıtlı',
                            'farm_tag_required' => 'Çiftlik etiketi gereklidir',
                            'farm_tag_invalid_format' => 'Çiftlik etiketi #ABC123 formatında olmalıdır',
                            'farm_tag_exists' => 'Bu çiftlik etiketi zaten kullanılıyor',
                            'phone_invalid' => 'Geçerli bir telefon numarası girin (örnek: +90 5XX XXX XX XX)',
                            'password_required' => 'Şifre gereklidir',
                            'password_too_short' => 'Şifre en az 6 karakter olmalıdır',
                            'password_too_long' => 'Şifre en fazla 100 karakter olabilir',
                            'terms_not_accepted' => 'Üyelik sözleşmesini kabul etmelisiniz',
                            'empty_fields' => 'Lütfen tüm zorunlu alanları doldurun',
                        );
                        
                        // Handle multiple errors (comma-separated)
                        $errors = explode(',', $register_error);
                        echo '<div class="auth-message auth-error">';
                        echo '<ul class="error-list">';
                        foreach ($errors as $error_code) {
                            $message = isset($error_messages[$error_code]) ? $error_messages[$error_code] : 'Bir hata oluştu';
                            echo '<li>' . esc_html($message) . '</li>';
                        }
                        echo '</ul>';
                        echo '</div>';
                    }
                    ?>
                    
                    <form class="auth-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('hdh_custom_register', 'hdh_register_nonce'); ?>
                        <input type="hidden" name="action" value="hdh_custom_register">
                        <input type="hidden" name="redirect_to" value="<?php echo esc_url(home_url('/profil')); ?>">
                        
                        <div class="auth-field">
                            <label for="farm_name" class="auth-label"><?php echo esc_html(hdh_get_content('auth', 'farm_name_label', 'Çiftlik Adı')); ?> <span class="required">*</span></label>
                            <input 
                                type="text" 
                                id="farm_name" 
                                name="farm_name" 
                                class="auth-input" 
                                required 
                                autocomplete="username"
                                placeholder="<?php echo esc_attr(hdh_get_content('auth', 'farm_name_placeholder', 'Çiftlik adınız')); ?>"
                            >
                            <small class="auth-help">Bu ad kullanıcı adınız olarak kullanılacaktır</small>
                        </div>
                        
                        <div class="auth-field">
                            <label for="user_email" class="auth-label"><?php echo esc_html(hdh_get_content('auth', 'email_label', 'E-posta Adresi')); ?> <span class="required">*</span></label>
                            <input 
                                type="email" 
                                id="user_email" 
                                name="user_email" 
                                class="auth-input" 
                                required 
                                autocomplete="email"
                                placeholder="<?php echo esc_attr(hdh_get_content('auth', 'email_placeholder', 'ornek@email.com')); ?>"
                            >
                            <small class="auth-help"><?php echo esc_html(hdh_get_content('auth', 'email_verify_message', 'E-posta\'nı doğrula +1 bilet kazan')); ?> 🎟️</small>
                        </div>
                        
                        <div class="auth-field">
                            <label for="farm_tag" class="auth-label">Çiftlik Etiketi <span class="required">*</span></label>
                            <input 
                                type="text" 
                                id="farm_tag" 
                                name="farm_tag" 
                                class="auth-input" 
                                required 
                                placeholder="#ABC123"
                            >
                            <small class="auth-help">Çiftliğinizin benzersiz etiketi (örnek: #ABC123)</small>
                        </div>
                        
                        <div class="auth-field">
                            <label for="phone_number" class="auth-label">Telefon Numarası</label>
                            <input 
                                type="tel" 
                                id="phone_number" 
                                name="phone_number" 
                                class="auth-input" 
                                autocomplete="tel"
                                placeholder="+90 5XX XXX XX XX"
                            >
                            <small class="auth-help">Telefon numaranız isteğe bağlıdır</small>
                        </div>
                        
                        <div class="auth-field">
                            <label for="user_pass" class="auth-label">Şifre <span class="required">*</span></label>
                            <div class="auth-password-wrapper">
                                <input 
                                    type="password" 
                                    id="user_pass" 
                                    name="user_pass" 
                                    class="auth-input" 
                                    required 
                                    autocomplete="new-password"
                                    placeholder="Güçlü bir şifre seçin"
                                >
                                <button type="button" class="auth-password-toggle" data-target="user_pass">
                                    <span class="toggle-show">👁️</span>
                                    <span class="toggle-hide" style="display:none;">🙈</span>
                                </button>
                            </div>
                        </div>
                        
                        <div class="auth-field-checkbox">
                            <label class="auth-checkbox-label">
                                <input type="checkbox" name="accept_terms" id="accept_terms" required>
                                <span>
                                    <a href="<?php echo esc_url(home_url('/uyelik-sozlesmesi/')); ?>" target="_blank">Üyelik sözleşmesini</a> okudum ve onaylıyorum. <span class="required">*</span>
                                </span>
                            </label>
                        </div>
                        
                        <button type="submit" class="auth-submit" id="register-submit">Üye Ol</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <?php
} else {
    // LOGGED IN: Show profile settings
    $user_id = get_current_user_id();
    $user = wp_get_current_user();
    $farm_name = $user->display_name;
    $hayday_username = get_user_meta($user_id, 'hayday_username', true);
    $jeton_balance = function_exists('hdh_get_user_jeton_balance') ? hdh_get_user_jeton_balance($user_id) : 0;
    $completed_exchanges = function_exists('hdh_get_completed_gift_count') ? hdh_get_completed_gift_count($user_id) : 0;
    
    // Check if we should show edit form by default
    $show_edit = isset($_GET['edit']) && $_GET['edit'] === '1';
    ?>
    <main class="profile-page-main">
        <div class="container">
            <div class="profile-logged-in">
                <h1 class="profile-title">Profil Ayarları</h1>
                
                <!-- Profile Overview Card -->
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-name-section">
                            <h2 class="profile-farm-name"><?php echo esc_html($farm_name); ?></h2>
                            <?php if ($hayday_username) : ?>
                                <p class="profile-hayday-username">@<?php echo esc_html($hayday_username); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="profile-stats">
                        <div class="profile-stat-item">
                            <div class="stat-icon">⭐</div>
                            <div class="stat-content">
                                <div class="stat-value"><?php echo esc_html($completed_exchanges); ?></div>
                                <div class="stat-label">Başarılı Hediyeleşme</div>
                            </div>
                        </div>
                        
                        <div class="profile-stat-item">
                            <div class="stat-icon">🎟️</div>
                            <div class="stat-content">
                                <div class="stat-value"><?php echo esc_html(number_format_i18n($jeton_balance)); ?></div>
                                <div class="stat-label">Bilet</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="profile-actions">
                        <button class="btn-edit-profile" id="btn-edit-profile">
                            ✏️ Profili Düzenle
                        </button>
                        <a href="<?php echo esc_url(wp_logout_url(home_url('/profil'))); ?>" class="btn-logout">
                            🚪 Çıkış Yap
                        </a>
                    </div>
                </div>
                
                <!-- Email Verification Section -->
                <?php
                $user_email = $current_user->user_email;
                $email_verified = get_user_meta($user_id, 'hdh_email_verified', true);
                $firebase_enabled = function_exists('hdh_is_firebase_configured') && hdh_is_firebase_configured();
                ?>
                <div class="email-verification-card">
                    <div class="verification-header">
                        <h3 class="verification-title">📧 E-posta Doğrulama</h3>
                        <?php if ($email_verified) : ?>
                            <span class="verification-badge verified">✅ Doğrulandı</span>
                        <?php else : ?>
                            <span class="verification-badge not-verified">⏳ Doğrulanmadı</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="verification-content">
                        <p class="verification-email">
                            <strong>E-posta:</strong> <?php echo esc_html($user_email); ?>
                        </p>
                        
                        <?php if (!$email_verified) : ?>
                            <div class="verification-actions">
                                <?php if ($firebase_enabled) : ?>
                                    <!-- Firebase Email Verification -->
                                    <button type="button" class="btn-send-verification-code" id="btn-firebase-email-verify">
                                        📨 Doğrulama E-postası Gönder (Firebase)
                                    </button>
                                    <button type="button" class="btn-check-verification" id="btn-firebase-email-check" style="display: none; margin-top: 12px;">
                                        ✅ Doğrulamayı Kontrol Et
                                    </button>
                                    <div class="verification-message" id="firebase-email-message"></div>
                                <?php else : ?>
                                    <!-- Fallback: Code-based Email Verification -->
                                    <button type="button" class="btn-send-verification-code" id="btn-send-email-code">
                                        📨 Doğrulama Kodu Gönder
                                    </button>
                                    
                                    <div class="verification-code-form" id="email-code-form" style="display: none;">
                                        <label for="email-verification-code" class="verification-label">
                                            E-posta adresinize gönderilen 6 haneli kodu girin:
                                        </label>
                                        <div class="verification-input-group">
                                            <input 
                                                type="text" 
                                                id="email-verification-code" 
                                                class="verification-code-input" 
                                                placeholder="000000"
                                                maxlength="6"
                                                pattern="[0-9]{6}"
                                                autocomplete="off"
                                            >
                                            <button type="button" class="btn-verify-code" id="btn-verify-email-code">
                                                Doğrula
                                            </button>
                                        </div>
                                        <small class="verification-help">
                                            Kod 15 dakika süreyle geçerlidir. E-posta gelmediyse spam klasörünü kontrol edin.
                                        </small>
                                    </div>
                                    
                                    <div class="verification-message" id="email-verification-message"></div>
                                <?php endif; ?>
                            </div>
                        <?php else : ?>
                            <p class="verification-success">
                                ✅ E-posta adresiniz doğrulandı. <strong>+1 bilet</strong> kazandınız! 🎟️
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Profile Edit Form -->
                <div class="profile-edit-form" id="profile-edit-form" style="display: <?php echo $show_edit ? 'block' : 'none'; ?>;">
                    <h3 class="edit-form-title">Profili Düzenle</h3>
                    
                    <?php if (isset($_GET['updated']) && $_GET['updated'] === '1') : ?>
                        <div class="auth-message auth-success">
                            ✅ Profiliniz başarıyla güncellendi!
                        </div>
                    <?php endif; ?>
                    
                    <form id="profile-edit-form-element" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('hdh_update_profile', 'hdh_profile_nonce'); ?>
                        <input type="hidden" name="action" value="hdh_update_profile">
                        
                        <div class="form-field">
                            <label for="farm_name" class="form-label">Çiftlik Adı</label>
                            <input 
                                type="text" 
                                id="farm_name" 
                                name="farm_name" 
                                value="<?php echo esc_attr($farm_name); ?>" 
                                required 
                                class="form-input"
                            >
                        </div>
                        
                        <div class="form-field">
                            <label for="hayday_username" class="form-label">Hay Day Kullanıcı Adı</label>
                            <input 
                                type="text" 
                                id="hayday_username" 
                                name="hayday_username" 
                                value="<?php echo esc_attr($hayday_username); ?>" 
                                placeholder="Örn: HayDayPlayer123" 
                                class="form-input"
                            >
                            <small class="form-help">Hay Day oyunundaki kullanıcı adınız (isteğe bağlı)</small>
                        </div>
                        
                        <div class="form-field">
                            <label for="hayday_farm_number" class="form-label">🏡 Çiftlik Numarası</label>
                            <input 
                                type="text" 
                                id="hayday_farm_number" 
                                name="hayday_farm_number" 
                                value="<?php echo esc_attr(get_user_meta($user_id, 'hayday_farm_number', true)); ?>" 
                                placeholder="Örn: #P8QVJY9CL" 
                                class="form-input"
                            >
                            <small class="form-help">Hay Day çiftlik numaranız. Teklif kabul edildiğinde karşı tarafa gösterilir.</small>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-save-profile">💾 Kaydet</button>
                            <button type="button" class="btn-cancel-edit" id="btn-cancel-edit">❌ İptal</button>
                        </div>
                    </form>
                </div>
                
                <!-- My Listings Section -->
                <div class="my-listings-section">
                    <h3 class="my-listings-title">📋 İlanlarım</h3>
                    <?php
                    // Get user's listings
                    $user_listings = new WP_Query(array(
                        'post_type' => 'hayday_trade',
                        'author' => $user_id,
                        'posts_per_page' => 20,
                        'post_status' => array('publish', 'draft'),
                        'orderby' => 'date',
                        'order' => 'DESC'
                    ));
                    
                    if ($user_listings->have_posts()) : ?>
                        <div class="my-listings-list">
                            <?php while ($user_listings->have_posts()) : $user_listings->the_post(); 
                                $listing_id = get_the_ID();
                                $trade_status = get_post_meta($listing_id, '_hdh_trade_status', true);
                                $is_active = (get_post_status() === 'publish');
                                $wanted_item = get_post_meta($listing_id, '_hdh_wanted_item', true);
                                $wanted_qty = get_post_meta($listing_id, '_hdh_wanted_qty', true);
                                $wanted_label = function_exists('hdh_get_item_label') ? hdh_get_item_label($wanted_item) : $wanted_item;
                            ?>
                                <div class="my-listing-item <?php echo $is_active ? 'listing-active' : 'listing-inactive'; ?>">
                                    <div class="listing-info">
                                        <h4 class="listing-title">
                                            <a href="<?php echo esc_url(get_permalink()); ?>" target="_blank">
                                                <?php the_title(); ?>
                                            </a>
                                        </h4>
                                        <div class="listing-meta">
                                            <span class="listing-wanted">İstiyor: <?php echo esc_html($wanted_qty . 'x ' . $wanted_label); ?></span>
                                            <span class="listing-date">📅 <?php echo get_the_date(); ?></span>
                                        </div>
                                    </div>
                                    <div class="listing-actions">
                                        <span class="listing-status <?php echo $is_active ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $is_active ? '✅ Aktif' : '⏸️ Pasif'; ?>
                                        </span>
                                        <?php if ($is_active) : ?>
                                            <button 
                                                class="btn-deactivate-listing" 
                                                data-listing-id="<?php echo esc_attr($listing_id); ?>"
                                                title="İlanı pasife al"
                                            >
                                                ⏸️ Pasife Al
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else : ?>
                        <div class="no-listings-message">
                            <p>Henüz ilan oluşturmadınız.</p>
                            <a href="<?php echo esc_url(home_url('/ilan-ver')); ?>" class="btn-create-listing-profile">
                                ➕ İlk İlanını Oluştur
                            </a>
                        </div>
                    <?php endif; 
                    wp_reset_postdata();
                    ?>
                </div>
                
                <!-- Tasks Panel -->
            </div>
        </div>
    </main>
    <?php
}

get_footer();
?>
