#!/bin/bash

# HDH Theme - Auto Setup Script
# WordPress yolunu otomatik bulmaya çalışır veya kullanıcıdan alır

echo "🌾 HDH Theme - Local WordPress Setup (Auto)"
echo "============================================"
echo ""

THEME_SOURCE="/Users/abdullahalperbas/hdh"

# WordPress kurulumunu otomatik bul
echo "🔍 WordPress kurulumu aranıyor..."
WP_PATHS=(
    "$HOME/Sites"
    "$HOME/Local Sites"
    "/Applications/MAMP/htdocs"
    "/Applications/XAMPP/htdocs"
    "/Users/abdullahalperbas/wordpress"
    "/Users/abdullahalperbas/Sites/wordpress"
)

FOUND_PATHS=()

for base_path in "${WP_PATHS[@]}"; do
    if [ -d "$base_path" ]; then
        # wp-content klasörünü ara
        while IFS= read -r wp_path; do
            if [ -d "$wp_path/wp-content" ]; then
                FOUND_PATHS+=("$wp_path")
            fi
        done < <(find "$base_path" -type d -name "wp-content" -maxdepth 3 2>/dev/null | sed 's|/wp-content||')
    fi
done

# Bulunan yolları göster
if [ ${#FOUND_PATHS[@]} -gt 0 ]; then
    echo ""
    echo "✅ WordPress kurulumları bulundu:"
    echo ""
    for i in "${!FOUND_PATHS[@]}"; do
        echo "  [$((i+1))] ${FOUND_PATHS[$i]}"
    done
    echo "  [0] Manuel yol girin"
    echo ""
    read -p "Seçiminiz (numara): " choice
    
    if [ "$choice" = "0" ] || [ -z "$choice" ]; then
        read -p "WordPress tam yolu: " WP_PATH
    else
        WP_PATH="${FOUND_PATHS[$((choice-1))]}"
    fi
else
    echo "❌ Otomatik WordPress kurulumu bulunamadı."
    echo ""
    read -p "WordPress kurulumunuzun tam yolunu girin: " WP_PATH
fi

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
read -p "Devam etmek istiyor musunuz? (y/n): " confirm

if [ "$confirm" != "y" ] && [ "$confirm" != "Y" ]; then
    echo "İptal edildi."
    exit 0
fi

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
rsync -av --exclude='.git' --exclude='node_modules' --exclude='*.sh' --exclude='.gitignore' --exclude='DEVELOPMENT.md' "$THEME_SOURCE/" "$THEME_DEST/"

# Dosya izinlerini düzelt
echo "🔐 Dosya izinleri düzeltiliyor..."
find "$THEME_DEST" -type f -exec chmod 644 {} \;
find "$THEME_DEST" -type d -exec chmod 755 {} \;

# WordPress config dosyası oluştur (geliştirme için)
CONFIG_FILE="$THEME_SOURCE/.wp-path"
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

