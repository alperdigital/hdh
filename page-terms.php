<?php
/**
 * Template Name: Üyelik Sözleşmesi
 * 
 * Üyelik sözleşmesi sayfası - Admin panelinden güncellenebilir
 */

get_header();
?>

<main>
    <div class="container">
        <article class="terms-page">
            <header class="terms-header">
                <h1 class="terms-title">Üyelik Sözleşmesi</h1>
                <p class="terms-updated">Son güncelleme: <?php echo get_the_modified_date('d F Y'); ?></p>
            </header>
            
            <div class="terms-content">
                <?php
                // Admin panelinden güncellenebilir içerik
                $terms_content = get_option('hdh_terms_content', '');
                
                if (!empty($terms_content)) {
                    echo wp_kses_post(wpautop($terms_content));
                } else {
                    // Varsayılan içerik
                    ?>
                    <h2>1. Genel Hükümler</h2>
                    <p>Bu sözleşme, hayday.help platformuna üye olan kullanıcıların hak ve yükümlülüklerini belirler.</p>
                    
                    <h2>2. Üyelik Koşulları</h2>
                    <p>Platforma üye olmak için:</p>
                    <ul>
                        <li>18 yaşını doldurmuş olmak veya veli/vasi izni almak</li>
                        <li>Doğru ve güncel bilgiler vermek</li>
                        <li>Üyelik sözleşmesini kabul etmek</li>
                    </ul>
                    
                    <h2>3. Kullanıcı Yükümlülükleri</h2>
                    <p>Kullanıcılar:</p>
                    <ul>
                        <li>Doğru bilgiler paylaşmalıdır</li>
                        <li>Diğer kullanıcılara saygılı olmalıdır</li>
                        <li>Yasa dışı faaliyetlerde bulunmamalıdır</li>
                        <li>Hesap güvenliğini sağlamalıdır</li>
                    </ul>
                    
                    <h2>4. Hediyeleşme Kuralları</h2>
                    <p>Hediyeleşme işlemlerinde:</p>
                    <ul>
                        <li>Anlaşmalar kullanıcılar arasında yapılır</li>
                        <li>Platform sadece aracılık eder</li>
                        <li>Anlaşmazlıklarda platform sorumlu değildir</li>
                        <li>Güven skoru sistemi kullanılır</li>
                    </ul>
                    
                    <h2>5. Gizlilik</h2>
                    <p>Kişisel bilgileriniz gizlilik politikamıza uygun olarak korunur.</p>
                    
                    <h2>6. Değişiklikler</h2>
                    <p>Bu sözleşme gerektiğinde güncellenebilir. Güncellemeler platformda duyurulur.</p>
                    <?php
                }
                ?>
            </div>
            
            <div class="terms-footer">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="btn-wooden-sign btn-primary">Ana Sayfaya Dön</a>
            </div>
        </article>
    </div>
</main>

<?php
get_footer();
?>

