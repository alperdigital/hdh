# HDH Theme - Hay Day Help WordPress Theme

**Version:** 3.5.0  
**WordPress:** 6.8+  
**PHP:** 7.4+  
**License:** GPL v2 or later

## 📋 İçindekiler

- [Genel Bakış](#genel-bakış)
- [Kod Yapısı ve Hiyerarşi](#kod-yapısı-ve-hiyerarşi)
- [Clean Code Prensipleri](#clean-code-prensipleri)
- [Kurulum](#kurulum)
- [Geliştirme](#geliştirme)
- [Dosya Açıklamaları](#dosya-açıklamaları)

---

## 🎯 Genel Bakış

HDH (Hay Day Help) teması, Hay Day oyuncuları için özel olarak tasarlanmış bir WordPress temasıdır. Temanın ana özellikleri:

- **Takas Sistemi**: Kullanıcılar Hay Day eşyalarını takas edebilir
- **Güven Sistemi**: Kullanıcılar birbirlerini değerlendirebilir
- **Özel Kayıt Sistemi**: Hay Day'e özel kayıt formu
- **Yılbaşı Teması**: Özel tasarım ve renk paleti
- **Mobil Uyumlu**: Responsive tasarım

---

## 📁 Kod Yapısı ve Hiyerarşi

### Ana Dizin Yapısı

```
hdh/
├── 📄 style.css                    # WordPress tema header (gerekli)
├── 📄 functions.php                # Ana tema dosyası (setup ve includes)
│
├── 🎨 Templates/                   # WordPress template dosyaları
│   ├── header.php                  # Site başlığı ve navigasyon
│   ├── footer.php                  # Site alt bilgisi
│   ├── front-page.php              # Ana sayfa (takas ilanları feed)
│   ├── index.php                   # Varsayılan blog template
│   ├── single.php                  # Tekil yazı template
│   ├── single-hayday_trade.php      # Takas ilanı detay sayfası
│   ├── archive.php                 # Arşiv sayfası
│   ├── page.php                    # Sayfa template
│   ├── search.php                  # Arama sonuçları
│   ├── 404.php                     # 404 hata sayfası
│   ├── sidebar.php                 # Sidebar widget alanı
│   └── comments.php                # Yorumlar template
│
├── 📦 inc/                         # Core functionality (PHP)
│   ├── items-config.php            # Hay Day eşya konfigürasyonu
│   ├── trade-offers.php            # Custom Post Type (hayday_trade)
│   ├── create-trade-handler.php    # Form işleme ve validasyon
│   ├── trade-settings.php          # Admin ayarları
│   ├── registration-handler.php    # Özel kayıt sistemi
│   ├── trust-system.php            # Güven/değerlendirme sistemi
│   ├── widgets.php                 # Widget alanları
│   ├── social-functions.php        # Sosyal medya fonksiyonları
│   ├── breadcrumbs.php             # Breadcrumb navigasyon
│   └── post-views.php              # Yazı görüntülenme sayacı
│
├── 🧩 components/                  # Yeniden kullanılabilir bileşenler
│   ├── item-card.php               # Eşya seçim kartı
│   └── trade-card.php              # Takas ilanı kartı
│
├── 🎨 assets/                       # Statik dosyalar
│   ├── css/
│   │   └── farm-style.css          # Ana stil dosyası
│   ├── js/
│   │   ├── cartoon-interactions.js # Header scroll, animasyonlar
│   │   ├── mobile-menu.js          # Mobil menü toggle
│   │   ├── trade-form.js           # Form dinamik davranışları
│   │   └── trust-system.js         # Güven sistemi AJAX
│   ├── items/                      # Hay Day eşya ikonları (SVG)
│   │   ├── bant.svg
│   │   ├── civata.svg
│   │   ├── kalas.svg
│   │   └── ...
│   └── svg/
│       └── farm-icons.svg          # SVG sprite
│
└── 📄 social-share.php              # Sosyal medya paylaşım fonksiyonları
```

### Kod Yükleme Sırası (functions.php)

```php
1. inc/items-config.php              # Eşya konfigürasyonu (diğerleri tarafından kullanılır)
2. components/item-card.php          # Bileşen fonksiyonları
3. components/trade-card.php         # Bileşen fonksiyonları
4. inc/trade-offers.php              # CPT kayıt
5. inc/create-trade-handler.php      # Form handler
6. inc/trade-settings.php            # Admin ayarları
7. inc/registration-handler.php      # Kayıt sistemi
8. inc/trust-system.php              # Güven sistemi
9. inc/widgets.php                   # Widget alanları
10. inc/social-functions.php         # Sosyal medya
11. social-share.php                  # Paylaşım fonksiyonları
12. inc/breadcrumbs.php              # Breadcrumb
13. inc/post-views.php               # Görüntülenme sayacı
```

### Fonksiyon Prefix'leri

- **`hdh_`**: Tema özel fonksiyonlar (Hay Day Help)
- **`mi_`**: Eski tema fonksiyonları (backward compatibility için)

---

## 🏗️ Clean Code Prensipleri

### ✅ Uygulanan Prensipler

#### 1. **Single Responsibility Principle (SRP)**
Her dosya ve fonksiyon tek bir sorumluluğa sahiptir:

- `items-config.php` → Sadece eşya konfigürasyonu
- `create-trade-handler.php` → Sadece form işleme
- `trust-system.php` → Sadece güven sistemi
- `item-card.php` → Sadece eşya kartı render

**Örnek:**
```php
// ✅ İyi: Tek sorumluluk
function hdh_get_items_config() {
    return array(/* items */);
}

// ❌ Kötü: Çoklu sorumluluk
function hdh_do_everything() {
    // items config
    // form handling
    // trust system
    // ...
}
```

#### 2. **DRY (Don't Repeat Yourself)**
Kod tekrarları kaldırılmış, ortak fonksiyonlar merkezi konumda:

- `hdh_render_item_card()` → Tüm eşya kartları için tek fonksiyon
- `hdh_render_trade_card()` → Tüm takas kartları için tek fonksiyon
- `hdh_get_items_config()` → Eşya listesi tek kaynak

**Örnek:**
```php
// ✅ İyi: Merkezi konfigürasyon
$items = hdh_get_items_config();
foreach ($items as $slug => $item) {
    echo hdh_render_item_card($slug, 'wanted_item', 'radio', '');
}

// ❌ Kötü: Tekrar eden kod
echo '<div class="item-card">Cıvata</div>';
echo '<div class="item-card">Kalas</div>';
// ...
```

#### 3. **Separation of Concerns**
Farklı katmanlar birbirinden ayrılmış:

- **Templates** → Görüntüleme (HTML/PHP)
- **inc/** → İş mantığı (PHP)
- **components/** → Yeniden kullanılabilir bileşenler
- **assets/** → Stil ve script (CSS/JS)

#### 4. **Meaningful Names**
Fonksiyon ve değişken isimleri açıklayıcı:

```php
// ✅ İyi
function hdh_get_items_config() { }
function hdh_render_item_card($slug, $name, $type) { }
$wanted_item = get_post_meta($post_id, '_hdh_wanted_item', true);

// ❌ Kötü
function get_data() { }
function render($a, $b, $c) { }
$item = get_meta($id, 'x', true);
```

#### 5. **Small Functions**
Fonksiyonlar küçük ve odaklı:

```php
// ✅ İyi: Her fonksiyon tek bir iş yapıyor
function hdh_get_items_config() {
    return array(/* ... */);
}

function hdh_render_item_card($slug, $name, $type) {
    // Sadece kart render
}

// ❌ Kötü: Çok büyük fonksiyon
function hdh_handle_everything() {
    // 200+ satır kod
}
```

#### 6. **Error Handling**
Güvenlik kontrolleri ve hata yönetimi:

```php
// ✅ İyi: ABSPATH kontrolü
if (!defined('ABSPATH')) {
    exit;
}

// ✅ İyi: Nonce kontrolü
if (!wp_verify_nonce($_POST['hdh_trade_nonce'], 'hdh_create_trade')) {
    wp_die('Security check failed');
}

// ✅ İyi: Input sanitization
$wanted_item = sanitize_text_field($_POST['wanted_item']);
```

#### 7. **Comments When Necessary**
Kod kendi kendini açıklıyorsa yorum yok, karmaşık mantık için yorum var:

```php
// ✅ İyi: Karmaşık mantık için yorum
// Filter by wanted_item using meta_query
$meta_query[] = array(
    'key' => '_hdh_wanted_item',
    'value' => $wanted_filter,
    'compare' => '='
);

// ❌ Kötü: Gereksiz yorum
// Get items config
$items = hdh_get_items_config();
```

### ⚠️ İyileştirilebilir Alanlar

#### 1. **Dependency Injection**
Şu anda dosyalar doğrudan `require_once` ile yükleniyor. Gelecekte autoloader pattern kullanılabilir:

```php
// Mevcut
require_once get_template_directory() . '/inc/items-config.php';

// İyileştirilebilir (gelecek)
HDH_Autoloader::load('items-config');
```

#### 2. **Class-based Structure**
Bazı fonksiyonlar class'lara dönüştürülebilir:

```php
// Mevcut
function hdh_get_items_config() { }

// İyileştirilebilir
class HDH_Items_Config {
    public static function get() { }
}
```

#### 3. **Interface Segregation**
Büyük fonksiyonlar daha küçük interface'lere bölünebilir.

---

## 🚀 Kurulum

### Gereksinimler

- WordPress 6.8+
- PHP 7.4+
- MySQL 5.6+

### Adımlar

1. **Temayı İndir**
   ```bash
   git clone https://github.com/alperdigital/hdh.git
   ```

2. **WordPress'e Yükle**
   ```bash
   cp -r hdh /path/to/wordpress/wp-content/themes/
   ```

3. **WordPress Admin'de Aktif Et**
   - Görünüm → Temalar → HDH → Etkinleştir

4. **Menü Oluştur**
   - Görünüm → Menüler → Yeni menü oluştur
   - "Ana Menü" konumuna ata

---

## 💻 Geliştirme

### Yerel Geliştirme

```bash
# Tema klasörüne git
cd /path/to/wordpress/wp-content/themes/hdh

# Değişiklikleri yap
# ...

# Git'e commit et
git add .
git commit -m "feat: Yeni özellik eklendi"
git push origin main
```

### Yeni Özellik Ekleme

1. **Yeni Fonksiyon Ekle**
   ```php
   // inc/new-feature.php
   if (!defined('ABSPATH')) {
       exit;
   }
   
   function hdh_new_feature() {
       // Kod buraya
   }
   ```

2. **functions.php'ye Ekle**
   ```php
   require_once get_template_directory() . '/inc/new-feature.php';
   ```

3. **Template'de Kullan**
   ```php
   <?php hdh_new_feature(); ?>
   ```

### CSS/JS Güncelleme

CSS veya JS dosyalarını güncellediğinizde, `functions.php`'deki version numarasını artırın:

```php
wp_enqueue_style('hdh-farm-style', ..., array(), '3.6.0'); // Version artır
```

---

## 📚 Dosya Açıklamaları

### Core Files

#### `functions.php`
Ana tema dosyası. Tema setup, include'lar ve enqueue işlemleri burada.

**Sorumluluklar:**
- Tema desteği ekleme
- Menü kayıtları
- Dosya include'ları
- CSS/JS enqueue

#### `inc/items-config.php`
Hay Day eşyalarının merkezi konfigürasyonu. Tüm eşya bilgileri burada.

**Fonksiyonlar:**
- `hdh_get_items_config()` → Tüm eşyaları döndürür

#### `inc/trade-offers.php`
Custom Post Type (`hayday_trade`) kaydı ve meta box'ları.

**Fonksiyonlar:**
- `hdh_register_trade_offers_cpt()` → CPT kaydı
- `hdh_get_hayday_items()` → Eşya listesi

#### `inc/create-trade-handler.php`
Takas ilanı oluşturma formunun işlenmesi.

**Sorumluluklar:**
- Form validasyonu
- Post oluşturma
- Meta field kaydetme
- Redirect yönetimi

#### `inc/registration-handler.php`
Özel kullanıcı kayıt sistemi.

**Sorumluluklar:**
- Kayıt formu render
- Kullanıcı oluşturma
- Login entegrasyonu
- Modal yönetimi

#### `inc/trust-system.php`
Kullanıcı güven/değerlendirme sistemi.

**Fonksiyonlar:**
- `hdh_rate_comment()` → Yorum değerlendirme
- `hdh_get_user_trust_score()` → Güven skoru

### Components

#### `components/item-card.php`
Eşya seçim kartı bileşeni.

**Fonksiyon:**
- `hdh_render_item_card($slug, $name, $type, $value)`

#### `components/trade-card.php`
Takas ilanı kartı bileşeni.

**Fonksiyon:**
- `hdh_render_trade_card($post_id)`

### Templates

#### `front-page.php`
Ana sayfa. Takas ilanları feed'i ve form.

**Özellikler:**
- Takas ilanı oluşturma formu
- Filtreleme ve sıralama
- Takas ilanları listesi

#### `single-hayday_trade.php`
Takas ilanı detay sayfası.

**Özellikler:**
- İlan detayları
- Yorumlar/teklifler
- Güven skoru gösterimi

### Assets

#### `assets/css/farm-style.css`
Ana stil dosyası. Tüm CSS burada.

**Bölümler:**
- CSS Variables (renkler, spacing)
- Typography
- Layout (header, footer, container)
- Components (cards, buttons, forms)
- Responsive (media queries)

#### `assets/js/trade-form.js`
Form dinamik davranışları (vanilla JS).

**Özellikler:**
- Eşya seçimi
- Miktar input'ları
- Form validasyonu

---

## 🔒 Güvenlik

### Uygulanan Güvenlik Önlemleri

1. **ABSPATH Kontrolü**
   ```php
   if (!defined('ABSPATH')) {
       exit;
   }
   ```

2. **Nonce Verification**
   ```php
   wp_nonce_field('hdh_create_trade', 'hdh_trade_nonce');
   wp_verify_nonce($_POST['hdh_trade_nonce'], 'hdh_create_trade');
   ```

3. **Input Sanitization**
   ```php
   sanitize_text_field($_POST['wanted_item']);
   sanitize_email($_POST['email']);
   intval($_POST['quantity']);
   ```

4. **Output Escaping**
   ```php
   esc_html($title);
   esc_url($link);
   esc_attr($class);
   ```

---

## 📊 Performans

### Optimizasyonlar

1. **Conditional Loading**
   - JS dosyaları sadece gerektiğinde yüklenir
   - `is_front_page()` kontrolü ile

2. **Preload Critical Assets**
   - SVG ikonlar preload edilir
   - `hdh_preload_assets()` fonksiyonu

3. **Lazy Loading**
   - Görseller `loading="lazy"` ile yüklenir

4. **CSS/JS Versioning**
   - Cache bypass için version numaraları

---

## 🧪 Test

### Manuel Test Checklist

- [ ] Takas ilanı oluşturma
- [ ] Form validasyonu
- [ ] Filtreleme ve sıralama
- [ ] Kullanıcı kaydı
- [ ] Güven sistemi
- [ ] Mobil uyumluluk
- [ ] Cross-browser uyumluluk

---

## 📝 Changelog

### v3.5.0 (Current)
- Kod temizleme ve optimizasyon
- 40+ gereksiz dosya silindi
- Clean code prensipleri uygulandı

### v3.4.0
- Footer arkaplan düzeltmesi
- Yılbaşı renk paleti uygulandı

### v3.3.0
- Yılbaşı teması eklendi
- CSS optimizasyonları

---

## 🤝 Katkıda Bulunma

1. Fork yapın
2. Feature branch oluşturun (`git checkout -b feature/amazing-feature`)
3. Commit yapın (`git commit -m 'feat: Add amazing feature'`)
4. Push yapın (`git push origin feature/amazing-feature`)
5. Pull Request açın

---

## 📄 Lisans

GPL v2 or later

---

## 👤 Yazar

**Alper Digital**
- GitHub: [@alperdigital](https://github.com/alperdigital)

---

## 🙏 Teşekkürler

- WordPress Community
- Hay Day Players

---

**Son Güncelleme:** 2025-01-XX
