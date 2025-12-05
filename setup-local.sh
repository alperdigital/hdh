#!/bin/bash

# HDH Theme - Local WordPress Setup Script
# Bu script tema dosyalarını local WordPress kurulumuna kopyalar

echo "🌾 HDH Theme - Local WordPress Setup"
echo "======================================"
echo ""

# Mevcut tema kaynağı
THEME_SOURCE="/Users/abdullahalperbas/hdh"

# WordPress kurulum yolunu sor
echo "WordPress kurulumunuzun tam yolunu girin:"
echo "Örnekler:"
echo "  - MAMP: /Applications/MAMP/htdocs/wordpress"
echo "  - XAMPP: /Applications/XAMPP/htdocs/wordpress"
echo "  - Local by Flywheel: ~/Local Sites/wordpress/app/public"
echo "  - Custom: /path/to/your/wordpress"
echo ""
read -p "WordPress yolu: " WP_PATH

# Tilde expansion
WP_PATH="${WP_PATH/#\~/$HOME}"

# Yol kontrolü
if [ ! -d "$WP_PATH" ]; then
    echo "❌ Hata: Belirtilen yol bulunamadı: $WP_PATH"
    exit 1
fi

# wp-content kontrolü
if [ ! -d "$WP_PATH/wp-content" ]; then
    echo "❌ Hata: wp-content klasörü bulunamadı. Bu bir WordPress kurulumu gibi görünmüyor."
    exit 1
fi

# Tema klasörü yolu
THEME_DEST="$WP_PATH/wp-content/themes/hdh"

echo ""
echo "📁 Kaynak: $THEME_SOURCE"
echo "📁 Hedef:  $THEME_DEST"
echo ""

# Eski tema klasörünü yedekle (varsa)
if [ -d "$THEME_DEST" ]; then
    BACKUP_DIR="${THEME_DEST}_backup_$(date +%Y%m%d_%H%M%S)"
    echo "📦 Mevcut tema yedekleniyor: $BACKUP_DIR"
    mv "$THEME_DEST" "$BACKUP_DIR"
fi

# Tema klasörünü oluştur
echo "📂 Tema klasörü oluşturuluyor..."
mkdir -p "$THEME_DEST"

# Dosyaları kopyala (git hariç)
echo "📋 Dosyalar kopyalanıyor..."
rsync -av --exclude='.git' --exclude='node_modules' "$THEME_SOURCE/" "$THEME_DEST/"

# Dosya izinlerini düzelt
echo "🔐 Dosya izinleri düzeltiliyor..."
find "$THEME_DEST" -type f -exec chmod 644 {} \;
find "$THEME_DEST" -type d -exec chmod 755 {} \;

# WordPress config dosyası oluştur (geliştirme için)
CONFIG_FILE="$THEME_DEST/.wp-path"
echo "$WP_PATH" > "$CONFIG_FILE"
echo "💾 WordPress yolu kaydedildi: $CONFIG_FILE"

echo ""
echo "✅ Tema başarıyla kopyalandı!"
echo ""
echo "📝 Sonraki adımlar:"
echo "1. WordPress admin paneline giriş yapın"
echo "2. Görünüm > Temalar sayfasına gidin"
echo "3. 'HDH' temasını bulun ve etkinleştirin"
echo ""
echo "🌐 WordPress URL: $WP_PATH"
echo ""
echo "💡 Geliştirme için:"
echo "   Değişikliklerinizi yapın ve şu komutla güncelleyin:"
echo "   ./sync-to-wp.sh"
echo ""

