#!/bin/bash

# HDH Theme - Sync to WordPress Script
# Geliştirme sırasında değişiklikleri WordPress'e senkronize eder

echo "🔄 HDH Theme - WordPress'e Senkronize Ediliyor..."
echo ""

THEME_SOURCE="/Users/abdullahalperbas/hdh"
CONFIG_FILE="$THEME_SOURCE/.wp-path"

# WordPress yolunu oku
if [ ! -f "$CONFIG_FILE" ]; then
    echo "❌ WordPress yolu bulunamadı!"
    echo "   Önce ./setup-local.sh script'ini çalıştırın."
    exit 1
fi

WP_PATH=$(cat "$CONFIG_FILE")
THEME_DEST="$WP_PATH/wp-content/themes/hdh"

if [ ! -d "$WP_PATH/wp-content" ]; then
    echo "❌ WordPress kurulumu bulunamadı: $WP_PATH"
    echo "   Lütfen .wp-path dosyasını kontrol edin veya setup-local.sh'ı tekrar çalıştırın."
    exit 1
fi

echo "📁 Kaynak: $THEME_SOURCE"
echo "📁 Hedef:  $THEME_DEST"
echo ""

# Dosyaları senkronize et (git ve node_modules hariç)
echo "🔄 Senkronize ediliyor..."
rsync -av --exclude='.git' --exclude='node_modules' --exclude='.wp-path' --exclude='*.sh' "$THEME_SOURCE/" "$THEME_DEST/"

# Dosya izinlerini düzelt
find "$THEME_DEST" -type f -exec chmod 644 {} \;
find "$THEME_DEST" -type d -exec chmod 755 {} \;

echo ""
echo "✅ Senkronizasyon tamamlandı!"
echo "   WordPress'te sayfayı yenileyin (Ctrl+F5 veya Cmd+Shift+R)"
echo ""

