# Email Ayarları - WordPress SMTP Yapılandırması

## Önemli Notlar

WordPress'in varsayılan `wp_mail()` fonksiyonu çoğu hosting ortamında çalışmaz. Email doğrulama kodlarının gönderilebilmesi için SMTP yapılandırması gereklidir.

## Seçenek 1: WP Mail SMTP Plugin (Önerilen)

### Kurulum
1. WordPress Admin → Eklentiler → Yeni Ekle
2. "WP Mail SMTP" araması yapın
3. "WP Mail SMTP by WPForms" eklentisini kurun ve etkinleştirin

### Yapılandırma
1. WordPress Admin → Ayarlar → WP Mail SMTP
2. **Mailer** seçeneğini seçin:

#### Gmail (Önerilen - Test için)
- **Mailer:** Gmail / Google Workspace
- **Client ID** ve **Client Secret:** Google Cloud Console'dan alın
- **Authorized Redirect URI:** `https://www.hayday.help/wp-admin/admin.php?page=wp-mail-smtp`
- **From Email:** Gmail adresiniz
- **From Name:** Hay Day Help

#### SMTP (Production için)
- **Mailer:** Other SMTP
- **SMTP Host:** `smtp.gmail.com` (Gmail) veya hosting sağlayıcınızın SMTP sunucusu
- **SMTP Port:** `587` (TLS) veya `465` (SSL)
- **Encryption:** TLS veya SSL
- **SMTP Username:** Email adresiniz
- **SMTP Password:** Email şifreniz veya uygulama şifresi
- **From Email:** Gönderen email adresi
- **From Name:** Hay Day Help

### Test Email Gönderme
1. WP Mail SMTP → Ayarlar → Test Email
2. Test email gönderin
3. Email gelirse yapılandırma başarılıdır

## Seçenek 2: Manuel SMTP Yapılandırması (Kod ile)

Eğer plugin kullanmak istemiyorsanız, `functions.php` dosyasına SMTP ayarlarını ekleyebilirsiniz:

```php
// SMTP Configuration
add_action('phpmailer_init', 'hdh_configure_smtp');
function hdh_configure_smtp($phpmailer) {
    $phpmailer->isSMTP();
    $phpmailer->Host = 'smtp.gmail.com'; // SMTP sunucu
    $phpmailer->SMTPAuth = true;
    $phpmailer->Port = 587;
    $phpmailer->SMTPSecure = 'tls';
    $phpmailer->Username = 'your-email@gmail.com'; // SMTP kullanıcı adı
    $phpmailer->Password = 'your-app-password'; // SMTP şifresi (Gmail için uygulama şifresi)
    $phpmailer->From = 'your-email@gmail.com';
    $phpmailer->FromName = 'Hay Day Help';
}
```

**Not:** Gmail kullanıyorsanız, normal şifre yerine "Uygulama Şifresi" oluşturmanız gerekir:
1. Google Hesabım → Güvenlik
2. 2 Adımlı Doğrulama'yı etkinleştirin
3. "Uygulama şifreleri" → Yeni uygulama şifresi oluşturun

## Seçenek 3: Hosting Sağlayıcısı SMTP

Çoğu hosting sağlayıcısı kendi SMTP sunucularını sağlar:

- **cPanel:** Email Accounts → SMTP ayarları
- **Cloudflare:** Email Routing
- **SendGrid / Mailgun:** API key ile entegrasyon

## Email Doğrulama Sistemi

Sistem şu özelliklere sahiptir:

1. **6 haneli doğrulama kodu** gönderilir
2. **15 dakika** süreyle geçerlidir
3. **Saatte maksimum 5 kod** gönderilebilir (rate limiting)
4. **Başarılı doğrulama:** +1 bilet ödülü
5. **Event logging:** Tüm doğrulama işlemleri kaydedilir

## Test Etme

1. Profil sayfasına gidin (`/profil`)
2. "E-posta Doğrulama" bölümünde "Doğrulama Kodu Gönder" butonuna tıklayın
3. Email'inizi kontrol edin (spam klasörünü de kontrol edin)
4. 6 haneli kodu girin ve "Doğrula" butonuna tıklayın
5. Başarılı doğrulama sonrası +1 bilet kazanırsınız

## Sorun Giderme

### Email gelmiyor
- SMTP ayarlarını kontrol edin
- Spam klasörünü kontrol edin
- Email adresinin doğru olduğundan emin olun
- WP Mail SMTP plugin loglarını kontrol edin

### "E-posta gönderilemedi" hatası
- SMTP sunucu ayarlarını kontrol edin
- Port ve encryption ayarlarını doğrulayın
- Gmail kullanıyorsanız uygulama şifresi kullandığınızdan emin olun

### Rate limit hatası
- Saatte maksimum 5 kod gönderilebilir
- 1 saat bekleyip tekrar deneyin

## Güvenlik Notları

- SMTP şifrelerini asla kod içine yazmayın
- WordPress environment variables veya plugin ayarlarını kullanın
- Production'da Gmail yerine profesyonel email servisleri (SendGrid, Mailgun) kullanın

