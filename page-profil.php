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
                    <h1 class="auth-title">Hesabına Giriş Yap</h1>
                    <p class="auth-subtitle">Hediye Jetonu biriktirmek ve hediyeleşmek için giriş yap.</p>
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
                                echo 'Kullanıcı adı veya şifre hatalı.';
                                break;
                            case 'empty_fields':
                                echo 'Lütfen tüm alanları doldurun.';
                                break;
                            default:
                                echo 'Giriş yapılırken bir hata oluştu.';
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
                                    placeholder="Şifreniz"
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
                                <span>Beni hatırla</span>
                            </label>
                        </div>
                        
                        <button type="submit" class="auth-submit">Giriş Yap</button>
                    </form>
                </div>
                
                <!-- Register Form -->
                <div id="register-form-container" class="auth-form-container active">
                    <?php
                    $register_error = isset($_GET['registration_error']) ? $_GET['registration_error'] : '';
                    if ($register_error) {
                        echo '<div class="auth-message auth-error">';
                        switch ($register_error) {
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
                    
                    <form class="auth-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('hdh_custom_register', 'hdh_register_nonce'); ?>
                        <input type="hidden" name="action" value="hdh_custom_register">
                        <input type="hidden" name="redirect_to" value="<?php echo esc_url(home_url('/profil')); ?>">
                        
                        <div class="auth-field">
                            <label for="farm_name" class="auth-label">Çiftlik Adı <span class="required">*</span></label>
                            <input 
                                type="text" 
                                id="farm_name" 
                                name="farm_name" 
                                class="auth-input" 
                                required 
                                autocomplete="username"
                                placeholder="Çiftlik adınız"
                            >
                            <small class="auth-help">Bu ad kullanıcı adınız olarak kullanılacaktır</small>
                        </div>
                        
                        <div class="auth-field">
                            <label for="user_email" class="auth-label">E-posta Adresi <span class="required">*</span></label>
                            <input 
                                type="email" 
                                id="user_email" 
                                name="user_email" 
                                class="auth-input" 
                                required 
                                autocomplete="email"
                                placeholder="ornek@email.com"
                            >
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
                            <div class="auth-tip">
                                <strong>💡 İpucu:</strong> Telefon numaranızı belirtirseniz hesabınız <strong>mavi tikli</strong> olacaktır ve diğer kullanıcılar size daha çok güvenecektir.
                            </div>
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
                            <div class="stat-icon">🪙</div>
                            <div class="stat-content">
                                <div class="stat-value"><?php echo esc_html(number_format_i18n($jeton_balance)); ?></div>
                                <div class="stat-label">Hediye Jetonu</div>
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
                            <a href="<?php echo esc_url(home_url('/ilan-ver')); ?>" class="btn-create-listing">
                                ➕ İlk İlanını Oluştur
                            </a>
                        </div>
                    <?php endif; 
                    wp_reset_postdata();
                    ?>
                </div>
                
                <!-- Tasks Panel -->
                <?php if (function_exists('hdh_render_tasks_panel')) hdh_render_tasks_panel($user_id); ?>
            </div>
        </div>
    </main>
    <?php
}

get_footer();
?>
