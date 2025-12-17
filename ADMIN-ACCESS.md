# Admin Paneline Erişim Rehberi

## WordPress Admin Paneline Giriş

### 1. Admin Paneline Erişim URL'leri

**Ana Admin Panel:**
- `https://www.hayday.help/wp-admin`
- `https://www.hayday.help/wp-login.php`

### 2. Giriş Bilgileri

- **Kullanıcı Adı:** WordPress kullanıcı adınız veya e-posta adresiniz
- **Şifre:** WordPress şifreniz
- **Gerekli Yetki:** `manage_options` capability (genellikle Administrator rolü)

### 3. Premium Admin Panel (Yeni)

Giriş yaptıktan sonra WordPress admin panelinde:

**Sol Menüde "HDH" Menüsü:**
- **Dashboard** - Genel bakış, hızlı aksiyonlar, pinned sections, recent changes
- **Pre-Login** - Login öncesi sayfalar (Landing, Authentication)
- **Post-Login** - Login sonrası sayfalar (Listings, Profile)
- **Global Design** - Global tasarım ayarları
- **Content** - İçerik yönetimi (eski sayfaya yönlendirme)
- **Components** - Ürünler ve presetler (eski sayfaya yönlendirme)
- **Advanced** - Teknik ayarlar
- **Logs** - Değişiklik geçmişi ve rollback

**Direkt URL'ler:**
- Dashboard: `/wp-admin/admin.php?page=hdh-dashboard`
- Pre-Login: `/wp-admin/admin.php?page=hdh-pre-login`
- Post-Login: `/wp-admin/admin.php?page=hdh-post-login`
- Global Design: `/wp-admin/admin.php?page=hdh-global-design`
- Advanced: `/wp-admin/admin.php?page=hdh-advanced`
- Logs: `/wp-admin/admin.php?page=hdh-logs`

### 4. Eski Admin Sayfaları (Hâlâ Aktif)

**"Görevler" Menüsü Altında:**
- Görevler: `/wp-admin/admin.php?page=hdh-tasks`
- XP Ayarları: `/wp-admin/admin.php?page=hdh-xp-settings`
- İçerik Yönetimi: `/wp-admin/admin.php?page=hdh-content`
- Mesajlar: `/wp-admin/admin.php?page=hdh-messages`
- Sistem Ayarları: `/wp-admin/admin.php?page=hdh-settings`
- Ürünler: `/wp-admin/admin.php?page=hdh-items`

### 5. Özellikler

**Premium Admin Panel Özellikleri:**
- ✅ Global Search - Tüm ayarlarda arama
- ✅ Pinned Sections - Sık kullanılan bölümleri sabitleme
- ✅ Recent Changes - Son değişiklikler listesi
- ✅ Draft/Publish - Güvenli yayınlama akışı
- ✅ Change History - Tüm değişikliklerin kaydı
- ✅ Rollback - Herhangi bir değişikliği geri alma
- ✅ Progressive Disclosure - Quick/Advanced settings

### 6. Güvenlik

- Tüm admin sayfaları `manage_options` capability kontrolü yapar
- Nonce verification tüm form işlemlerinde kullanılır
- Input sanitization ve output escaping uygulanır

