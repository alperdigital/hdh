# 🌾 HDH Theme - Local Development Guide

## 🚀 İlk Kurulum

### 1. WordPress Kurulumunu Hazırlayın

WordPress kurulumunuzun tam yolunu bilmeniz gerekiyor. Örnekler:

- **MAMP**: `/Applications/MAMP/htdocs/wordpress`
- **XAMPP**: `/Applications/XAMPP/htdocs/wordpress`
- **Local by Flywheel**: `~/Local Sites/wordpress/app/public`
- **Custom**: Kendi WordPress kurulumunuzun yolu

### 2. Tema Dosyalarını Kopyalayın

Terminal'de şu komutu çalıştırın:

```bash
cd /Users/abdullahalperbas/hdh
./setup-local.sh
```

Script size WordPress yolunu soracak. Yolu girdikten sonra tema dosyaları otomatik olarak kopyalanacak.

### 3. WordPress'te Temayı Etkinleştirin

1. WordPress admin paneline giriş yapın
2. **Görünüm > Temalar** sayfasına gidin
3. **HDH** temasını bulun ve **Etkinleştir** butonuna tıklayın

## 🔄 Geliştirme Workflow

### Günlük Geliştirme

1. **Değişiklikleri Yapın**
   - Tema dosyalarını `/Users/abdullahalperbas/hdh/` klasöründe düzenleyin
   - Bu klasör Git repository'nizdir

2. **WordPress'e Senkronize Edin**
   ```bash
   cd /Users/abdullahalperbas/hdh
   ./sync-to-wp.sh
   ```

3. **Tarayıcıda Kontrol Edin**
   - WordPress sitenizi yenileyin (Ctrl+F5 veya Cmd+Shift+R)
   - Değişiklikleri görün

### Git Workflow

```bash
# 1. Değişiklikleri yapın ve test edin
# 2. Git'e commit edin
git add .
git commit -m "Açıklayıcı commit mesajı"

# 3. İstediğiniz zaman GitHub'a push edin
git push origin main
```

**Önemli:** Sadece hazır olduğunuzda GitHub'a push edin. Local'de istediğiniz kadar commit yapabilirsiniz.

## 📁 Klasör Yapısı

```
/Users/abdullahalperbas/hdh/          # Git repository (geliştirme)
├── setup-local.sh                    # İlk kurulum script'i
├── sync-to-wp.sh                     # Senkronizasyon script'i
├── .wp-path                          # WordPress yolu (otomatik oluşturulur)
└── [tema dosyaları]

/path/to/wordpress/wp-content/themes/hdh/  # WordPress tema klasörü
└── [tema dosyaları - senkronize edilir]
```

## 🛠️ Hızlı Komutlar

### WordPress yolunu değiştirmek

```bash
# .wp-path dosyasını düzenleyin veya setup-local.sh'ı tekrar çalıştırın
./setup-local.sh
```

### Manuel senkronizasyon

```bash
# Eğer script çalışmazsa, manuel olarak:
WP_PATH="/path/to/wordpress"
rsync -av --exclude='.git' /Users/abdullahalperbas/hdh/ "$WP_PATH/wp-content/themes/hdh/"
```

### Cache temizleme

WordPress cache'ini temizlemek için:

1. WordPress admin → **Eklentiler** → Cache eklentilerini devre dışı bırakın
2. Tarayıcı cache'ini temizleyin (Ctrl+Shift+Delete)
3. Hard refresh yapın (Ctrl+F5 veya Cmd+Shift+R)

## ⚠️ Sorun Giderme

### Tema görünmüyor

1. WordPress yolunu kontrol edin: `cat .wp-path`
2. Tema klasörünün varlığını kontrol edin
3. `style.css` dosyasının olduğundan emin olun
4. WordPress admin'de **Görünüm > Temalar** sayfasını yenileyin

### Değişiklikler görünmüyor

1. `sync-to-wp.sh` script'ini çalıştırın
2. Tarayıcı cache'ini temizleyin
3. Hard refresh yapın (Ctrl+F5)

### Dosya izinleri hatası

```bash
WP_PATH=$(cat .wp-path)
chmod -R 755 "$WP_PATH/wp-content/themes/hdh"
find "$WP_PATH/wp-content/themes/hdh" -type f -exec chmod 644 {} \;
```

## 📝 Notlar

- `.wp-path` dosyası Git'e commit edilmez (`.gitignore`'da)
- Her zaman `/Users/abdullahalperbas/hdh/` klasöründe çalışın
- WordPress tema klasörü sadece senkronizasyon için kullanılır
- Git commit'leri sadece hazır olduğunuzda push edin

## 🎯 Best Practices

1. ✅ Her değişiklikten sonra `sync-to-wp.sh` çalıştırın
2. ✅ Local'de test edin
3. ✅ Anlamlı commit mesajları yazın
4. ✅ Sadece hazır kodları GitHub'a push edin
5. ✅ Feature branch'leri kullanın (opsiyonel)

