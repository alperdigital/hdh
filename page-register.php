<?php
/**
 * Template Name: Hay Day Üye Ol
 * Custom registration page for Hay Day users
 */

get_header();
?>

<main class="register-page">
    <div class="container">
        <div class="register-wrapper">
            <h1 class="page-title-cartoon">Hay Day Takas Merkezi'ne Katıl</h1>
            <p class="page-subtitle">Üye olarak takas ilanları oluşturabilir ve diğer oyuncularla takas yapabilirsiniz.</p>
            
            <?php
            // Show error messages
            if (isset($_GET['error'])) {
                $error_messages = array(
                    'empty_fields' => 'Lütfen tüm zorunlu alanları doldurun.',
                    'email_exists' => 'Bu e-posta adresi zaten kullanılıyor.',
                    'username_exists' => 'Bu kullanıcı adı zaten alınmış.',
                    'invalid_email' => 'Geçerli bir e-posta adresi girin.',
                    'password_mismatch' => 'Şifreler eşleşmiyor.',
                    'registration_failed' => 'Kayıt işlemi başarısız oldu. Lütfen tekrar deneyin.',
                );
                
                $error = sanitize_text_field($_GET['error']);
                if (isset($error_messages[$error])) {
                    echo '<div class="alert alert-error">' . esc_html($error_messages[$error]) . '</div>';
                }
            }
            
            // Show success message
            if (isset($_GET['registered'])) {
                echo '<div class="alert alert-success">Kayıt başarılı! Yönlendiriliyorsunuz...</div>';
            }
            ?>
            
            <form id="hdh-register-form" class="register-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('hdh_register', 'hdh_register_nonce'); ?>
                <input type="hidden" name="action" value="hdh_register">
                <input type="hidden" name="redirect" value="<?php echo isset($_GET['redirect']) ? esc_attr($_GET['redirect']) : ''; ?>">
                
                <div class="form-section">
                    <h3 class="form-section-title">
                        <span class="title-icon">🏠</span>
                        Çiftlik Bilgileri
                    </h3>
                    
                    <div class="form-field">
                        <label for="farm_code">Çiftlik Kodu <span class="required">*</span>:</label>
                        <input type="text" 
                               id="farm_code" 
                               name="farm_code" 
                               required
                               placeholder="Örn: #ABC123"
                               class="form-input"
                               pattern="[#]?[A-Z0-9]+"
                               title="Çiftlik kodunuzu girin (örn: #ABC123)">
                        <small class="form-help">Hay Day'deki çiftlik kodunuz</small>
                    </div>
                    
                    <div class="form-field">
                        <label for="farm_name">Çiftlik İsmi <span class="required">*</span>:</label>
                        <input type="text" 
                               id="farm_name" 
                               name="farm_name" 
                               required
                               placeholder="Çiftliğinizin adı"
                               class="form-input">
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="form-section-title">
                        <span class="title-icon">👤</span>
                        Hesap Bilgileri
                    </h3>
                    
                    <div class="form-field">
                        <label for="username">Kullanıcı Adı <span class="required">*</span>:</label>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               required
                               placeholder="Kullanıcı adınız"
                               class="form-input"
                               pattern="[a-zA-Z0-9_]+"
                               title="Sadece harf, rakam ve alt çizgi kullanabilirsiniz">
                    </div>
                    
                    <div class="form-field">
                        <label for="email">E-posta Adresi <span class="required">*</span>:</label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               required
                               placeholder="ornek@email.com"
                               class="form-input">
                    </div>
                    
                    <div class="form-field">
                        <label for="phone">Telefon Numarası (Opsiyonel):</label>
                        <input type="tel" 
                               id="phone" 
                               name="phone" 
                               placeholder="05XX XXX XX XX"
                               class="form-input">
                        <small class="form-help">E-posta veya telefon numarasından en az biri zorunludur</small>
                    </div>
                    
                    <div class="form-field">
                        <label for="password">Şifre <span class="required">*</span>:</label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               required
                               minlength="6"
                               placeholder="En az 6 karakter"
                               class="form-input">
                    </div>
                    
                    <div class="form-field">
                        <label for="password_confirm">Şifre Tekrar <span class="required">*</span>:</label>
                        <input type="password" 
                               id="password_confirm" 
                               name="password_confirm" 
                               required
                               placeholder="Şifrenizi tekrar girin"
                               class="form-input">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-submit-register btn-wooden-sign btn-primary">
                        <span class="btn-icon">✨</span>
                        Üye Ol
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php
get_footer();
?>

