# Firebase Authentication Kurulum Rehberi

## Firebase ile Email ve Telefon Doğrulama

Bu rehber, WordPress sitenize Firebase Authentication entegrasyonunu adım adım anlatır.

## 1. Firebase Projesi Oluşturma

1. [Firebase Console](https://console.firebase.google.com/) adresine gidin
2. "Proje Ekle" butonuna tıklayın
3. Proje adını girin (örn: `hayday-help`)
4. Google Analytics'i etkinleştirin (isteğe bağlı)
5. "Proje Oluştur" butonuna tıklayın

## 2. Web Uygulaması Ekleme

1. Firebase Console → Proje Ayarları → Genel
2. "Web uygulamanızı ekleyin" bölümünde `</>` ikonuna tıklayın
3. Uygulama takma adı girin (örn: `hayday-help-web`)
4. "Firebase Hosting" seçeneğini atlayabilirsiniz
5. "Uygulamayı kaydet" butonuna tıklayın
6. **Firebase yapılandırma bilgilerini kopyalayın:**
   ```javascript
   const firebaseConfig = {
     apiKey: "AIza...",
     authDomain: "your-project.firebaseapp.com",
     projectId: "your-project-id",
     storageBucket: "your-project.appspot.com",
     messagingSenderId: "123456789",
     appId: "1:123456789:web:abc123"
   };
   ```

## 3. Authentication Yöntemlerini Etkinleştirme

### Email/Password Authentication

1. Firebase Console → Authentication → Sign-in method
2. "Email/Password" seçeneğini tıklayın
3. "Etkinleştir" toggle'ını açın
4. "Kaydet" butonuna tıklayın

### Phone Authentication

1. Firebase Console → Authentication → Sign-in method
2. "Telefon" seçeneğini tıklayın
3. "Etkinleştir" toggle'ını açın
4. **Test telefon numaraları** ekleyebilirsiniz (geliştirme için)
5. "Kaydet" butonuna tıklayın

**Önemli:** Phone Authentication için Firebase Blaze (ücretli) planı gereklidir. Test modunda sınırlı SMS gönderimi yapılabilir.

## 4. WordPress'e Firebase Yapılandırması Ekleme

### Yöntem 1: WordPress Admin Panel (Önerilen)

1. WordPress Admin → Ayarlar → Firebase (eğer eklenti varsa)
2. Firebase yapılandırma bilgilerini girin

### Yöntem 2: wp-config.php (Manuel)

`wp-config.php` dosyasına ekleyin:

```php
define('HDH_FIREBASE_API_KEY', 'AIza...');
define('HDH_FIREBASE_AUTH_DOMAIN', 'your-project.firebaseapp.com');
define('HDH_FIREBASE_PROJECT_ID', 'your-project-id');
define('HDH_FIREBASE_STORAGE_BUCKET', 'your-project.appspot.com');
define('HDH_FIREBASE_MESSAGING_SENDER_ID', '123456789');
define('HDH_FIREBASE_APP_ID', '1:123456789:web:abc123');
```

### Yöntem 3: WordPress Options (Database)

`functions.php` dosyasına ekleyin veya bir admin panel oluşturun:

```php
update_option('hdh_firebase_api_key', 'AIza...');
update_option('hdh_firebase_auth_domain', 'your-project.firebaseapp.com');
update_option('hdh_firebase_project_id', 'your-project-id');
update_option('hdh_firebase_storage_bucket', 'your-project.appspot.com');
update_option('hdh_firebase_messaging_sender_id', '123456789');
update_option('hdh_firebase_app_id', '1:123456789:web:abc123');
```

## 5. reCAPTCHA Yapılandırması (Phone Auth için)

Firebase Phone Authentication, reCAPTCHA kullanır. reCAPTCHA otomatik olarak yüklenir, ancak özel bir yapılandırma gerekiyorsa:

1. Firebase Console → Authentication → Settings → reCAPTCHA
2. reCAPTCHA v3 veya v2 (checkbox) seçin
3. Domain'i ekleyin (örn: `www.hayday.help`)

## 6. Test Etme

### Email Doğrulama

1. Profil sayfasına gidin (`/profil`)
2. "E-posta Doğrulama" bölümünde "Doğrulama E-postası Gönder (Firebase)" butonuna tıklayın
3. E-posta kutunuzu kontrol edin
4. E-postadaki doğrulama linkine tıklayın
5. "Doğrulamayı Kontrol Et" butonuna tıklayın
6. Başarılı doğrulama sonrası +1 bilet kazanırsınız

### Telefon Doğrulama

1. Profil sayfasına gidin (`/profil`)
2. "Telefon Doğrulama" bölümünde telefon numaranızı girin (ülke kodu ile, örn: `+90 5XX XXX XX XX`)
3. "SMS Kodu Gönder" butonuna tıklayın
4. reCAPTCHA'yı tamamlayın
5. SMS ile gelen 6 haneli kodu girin
6. "Doğrula" butonuna tıklayın
7. Başarılı doğrulama sonrası +4 bilet kazanırsınız

## 7. Güvenlik Notları

### Production Ortamı

1. **Firebase Security Rules:** Firebase Console → Authentication → Settings → Authorized domains
   - Sadece kendi domain'inizi ekleyin
   - `localhost` sadece geliştirme için

2. **API Key Kısıtlamaları:**
   - Google Cloud Console → API & Services → Credentials
   - API Key'e HTTP referrer kısıtlaması ekleyin
   - Sadece kendi domain'inizden erişilebilir yapın

3. **Server-Side Token Verification:**
   - Production'da Firebase Admin SDK kullanarak token'ları server-side doğrulayın
   - `inc/firebase-verification.php` dosyasındaki `hdh_verify_email_via_firebase` ve `hdh_verify_phone_via_firebase` fonksiyonlarını güncelleyin

### Firebase Admin SDK (İsteğe Bağlı - Production için)

1. Firebase Console → Proje Ayarları → Service accounts
2. "Yeni özel anahtar oluştur" butonuna tıklayın
3. JSON dosyasını indirin
4. WordPress sunucusuna yükleyin (güvenli bir konuma)
5. `wp-config.php` veya `functions.php`'de path'i ayarlayın:

```php
define('HDH_FIREBASE_ADMIN_CREDENTIALS', '/path/to/serviceAccountKey.json');
```

## 8. Maliyetler

### Email Authentication
- **Ücretsiz:** Sınırsız email gönderimi

### Phone Authentication
- **Spark Plan (Ücretsiz):** Test modunda sınırlı SMS
- **Blaze Plan (Ücretli):** Production SMS gönderimi
  - Fiyatlandırma: Ülkeye göre değişir (Türkiye için yaklaşık $0.05-0.10/SMS)
  - İlk 10 SMS/ay ücretsiz (test için)

## 9. Sorun Giderme

### Email gönderilmiyor
- Firebase Console → Authentication → Users → Kullanıcıyı kontrol edin
- Email template'lerini kontrol edin
- Spam klasörünü kontrol edin

### SMS kodu gelmiyor
- Firebase Blaze planına geçtiğinizden emin olun
- Telefon numarası formatını kontrol edin (`+90` ile başlamalı)
- reCAPTCHA'yı tamamladığınızdan emin olun
- Firebase Console → Authentication → Users → Phone numbers'ı kontrol edin

### "Firebase yapılandırması bulunamadı" hatası
- `hdh_is_firebase_configured()` fonksiyonunu kontrol edin
- WordPress options'da Firebase config'in doğru kaydedildiğinden emin olun
- Browser console'da `hdhFirebase` objesini kontrol edin

### Token doğrulama hatası
- Server-side token verification için Firebase Admin SDK kullanın
- Token'ın expire olmadığından emin olun (1 saat geçerlilik)

## 10. Alternatif: Hybrid Yaklaşım

Firebase yapılandırılmamışsa, sistem otomatik olarak eski kod tabanlı doğrulamaya geri döner:

- **Email:** Kod tabanlı doğrulama (SMTP gerekli)
- **Telefon:** Firebase yapılandırması gereklidir

## 11. Geliştirme Notları

- Firebase SDK versiyonu: `10.7.1` (CDN'den yüklenir)
- Test telefon numaraları: Firebase Console → Authentication → Sign-in method → Phone → Test phone numbers
- Rate limiting: Firebase tarafından otomatik yönetilir

## Destek

Sorun yaşarsanız:
1. Browser console'u kontrol edin
2. Firebase Console → Authentication → Users'ı kontrol edin
3. WordPress debug log'larını kontrol edin
4. `inc/firebase-verification.php` dosyasındaki error handling'i kontrol edin

