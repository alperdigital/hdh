# Kod Temizleme ve Optimizasyon Planı

## Kullanılan Dosyalar (KORUNACAK)

### PHP Core
- functions.php
- header.php
- footer.php
- front-page.php
- single.php
- single-hayday_trade.php
- index.php
- archive.php
- page.php
- search.php
- 404.php

### Inc/ Dosyaları (Kullanılan)
- inc/items-config.php
- inc/trade-offers.php
- inc/create-trade-handler.php
- inc/trade-settings.php
- inc/registration-handler.php
- inc/trust-system.php
- inc/widgets.php (mi_has_sidebar için)
- inc/social-functions.php (mi_render_social_links için)
- inc/breadcrumbs.php (archive.php'de kullanılıyor)
- inc/post-views.php (archive.php'de kullanılıyor)

### Components (Kullanılan)
- components/item-card.php
- components/trade-card.php

### JavaScript (Kullanılan)
- assets/js/cartoon-interactions.js
- assets/js/mobile-menu.js
- assets/js/trade-form.js
- assets/js/trust-system.js (trust-system.php'de enqueue ediliyor)

### CSS
- assets/css/farm-style.css
- style.css (WordPress theme header için gerekli)

## Silinecek Dosyalar

### Kullanılmayan Components
- components/cta-buttons.php
- components/farm-banner.php
- components/farm-card.php

### Kullanılmayan JavaScript
- assets/js/blocks.js (gutenberg-blocks.php include edilmiyor)
- assets/js/farm-effects.js (enqueue edilmiyor)
- assets/js/single-page.js (enqueue edilmiyor)

### Kullanılmayan Inc/ Dosyaları
- inc/additional-widgets.php
- inc/admin-ui.php
- inc/advanced-stats.php
- inc/ajax-handlers.php
- inc/amp.php
- inc/comments.php
- inc/compatibility-check.php
- inc/cookie-consent.php
- inc/customizer.php
- inc/dark-mode.php
- inc/demo-import.php
- inc/feature-integration.php
- inc/file-validator.php
- inc/gutenberg-blocks.php
- inc/infinite-scroll.php
- inc/lightbox.php
- inc/loading-skeleton.php
- inc/masonry-grid.php
- inc/media-player.php
- inc/mobile-menu.php
- inc/modules.php
- inc/newsletter.php
- inc/parallax.php
- inc/popular-posts-widget.php
- inc/recaptcha.php
- inc/rtl-support.php
- inc/scroll-to-top.php
- inc/seo.php
- inc/syntax-highlighting.php
- inc/table-of-contents.php
- inc/theme-options.php
- inc/turkish-archives.php
- inc/webp-support.php

### Kullanılmayan Template Dosyaları
- single-mi_section.php
- templates/ klasörü (tüm dosyalar)
- page-register.php (registration-handler.php modal kullanıyor)
- author.php, category.php, tag.php, attachment.php (basit versiyonlar oluşturulacak)

### Kullanılmayan Diğer Dosyalar
- admin-sections.php
- template-functions.php
- social-share.php (inc/social-functions.php kullanılıyor, ama mi_render_social_share için gerekli)

## Optimizasyon Planı

1. functions.php'yi sadeleştir - sadece gerekli include'lar
2. Template dosyalarındaki gereksiz function_exists kontrollerini kaldır
3. CSS'teki kullanılmayan stilleri temizle
4. JavaScript dosyalarını optimize et (jQuery bağımlılıklarını azalt)
5. Gereksiz dosyaları sil

