# 🚀 HDH Theme - Hızlı Başlangıç

## Yöntem 1: Otomatik Setup (Önerilen)

```bash
cd /Users/abdullahalperbas/hdh
./setup-local-auto.sh
```

Script size WordPress yolunu soracak veya bulunan kurulumları listeleyecek.

## Yöntem 2: Manuel Kurulum

WordPress kurulumunuzun yolunu biliyorsanız:

```bash
# WordPress yolunuzu değişken olarak ayarlayın
WP_PATH="/path/to/your/wordpress"

# Tema dosyalarını kopyalayın
cp -r /Users/abdullahalperbas/hdh "$WP_PATH/wp-content/themes/hdh"

# .git klasörünü kaldırın (opsiyonel)
rm -rf "$WP_PATH/wp-content/themes/hdh/.git"

# WordPress yolunu kaydedin (senkronizasyon için)
echo "$WP_PATH" > /Users/abdullahalperbas/hdh/.wp-path

# Dosya izinlerini düzeltin
chmod -R 755 "$WP_PATH/wp-content/themes/hdh"
find "$WP_PATH/wp-content/themes/hdh" -type f -exec chmod 644 {} \;
```

## Yöntem 3: Tek Komut (WordPress yolunu biliyorsanız)

```bash
# WP_PATH değişkenini kendi yolunuzla değiştirin
WP_PATH="/Applications/MAMP/htdocs/wordpress" && \
rsync -av --exclude='.git' /Users/abdullahalperbas/hdh/ "$WP_PATH/wp-content/themes/hdh/" && \
echo "$WP_PATH" > /Users/abdullahalperbas/hdh/.wp-path && \
chmod -R 755 "$WP_PATH/wp-content/themes/hdh" && \
find "$WP_PATH/wp-content/themes/hdh" -type f -exec chmod 644 {} \; && \
echo "✅ Tema kopyalandı! WordPress admin'de etkinleştirin."
```

## Sonraki Adımlar

1. WordPress admin paneline giriş yapın
2. **Görünüm > Temalar** sayfasına gidin
3. **HDH** temasını bulun ve **Etkinleştir** butonuna tıklayın

## Geliştirme

Değişikliklerinizi yaptıktan sonra:

```bash
./sync-to-wp.sh
```

Bu komut değişiklikleri WordPress'e senkronize eder.

