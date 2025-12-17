<?php
/**
 * Template Name: İlan Ver
 * Page Template for Create Trade Offer
 * 
 * This page displays:
 * - Create trade offer form
 * - Item selection grids
 * - Quantity inputs
 */

get_header();
?>

<!-- HDH: Create Trade Offer Form -->
<section class="create-trade-form-section" id="create-trade">
    <div class="container">
        <div class="create-trade-wrapper">
            <h2 class="section-title-cartoon"><?php echo esc_html(hdh_get_content('trade_create', 'page_title', 'Hediyeleşme Başlasın')); ?></h2>
            
            <?php
            // Display error messages
            if (isset($_GET['trade_error'])) {
                $error_code = sanitize_text_field($_GET['trade_error']);
                $error_messages = array(
                    'no_wanted_item' => hdh_get_content('trade_create', 'error_no_wanted_item', 'Lütfen almak istediğiniz ürünü seçin.'),
                    'invalid_wanted_item' => hdh_get_content('trade_create', 'error_invalid_wanted_item', 'Seçtiğiniz ürün geçersiz.'),
                    'invalid_wanted_qty' => hdh_get_content('trade_create', 'error_invalid_wanted_qty', 'Miktar 1-999 arasında olmalıdır.'),
                    'no_offer_items' => hdh_get_content('trade_create', 'error_no_offer_items', 'Lütfen en az 1 ürün seçin (vermek istediğiniz).'),
                    'too_many_offer_items' => hdh_get_content('trade_create', 'error_too_many_offer_items', 'En fazla 3 ürün seçebilirsiniz.'),
                    'invalid_offer_item' => hdh_get_content('trade_create', 'error_invalid_offer_item', 'Seçtiğiniz ürünlerden biri geçersiz.'),
                    'invalid_offer_qty' => hdh_get_content('trade_create', 'error_invalid_offer_qty', 'Tüm miktarlar 1-999 arasında olmalıdır.'),
                    'rate_limit' => hdh_get_content('trade_create', 'error_rate_limit', 'Çok fazla ilan oluşturdunuz. Lütfen 1 saat sonra tekrar deneyin.'),
                );
                
                $error_message = isset($error_messages[$error_code]) ? $error_messages[$error_code] : 'Bir hata oluştu. Lütfen tekrar deneyin.';
                ?>
                <div class="form-error-message">
                    <span class="error-icon">⚠️</span>
                    <span class="error-text"><?php echo esc_html($error_message); ?></span>
                </div>
            <?php } ?>
            
            <form id="create-trade-form" class="trade-create-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('hdh_create_trade', 'hdh_trade_nonce'); ?>
                <input type="hidden" name="action" value="hdh_create_trade">
                
                <!-- Almak İstediğin Hediye -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <span class="title-icon">🔍</span>
                        Almak İstediğin Hediye
                        <span class="form-section-subtitle">Bir ürün seçin</span>
                    </h3>
                    <div class="items-grid" id="wanted-items-grid">
                        <?php 
                        $items = hdh_get_items_config();
                        foreach ($items as $slug => $item) {
                            echo hdh_render_item_card($slug, 'wanted_item', 'radio', '');
                        }
                        ?>
                    </div>
                    <div class="quantity-stepper-wrapper" id="wanted-quantity-wrapper" style="display: none;">
                        <label class="stepper-label">
                            <span class="stepper-label-text">Miktar</span>
                            <span class="stepper-hint">Kaç adet istiyorsunuz?</span>
                        </label>
                        <div class="quantity-stepper">
                            <button type="button" class="qty-btn qty-minus" data-target="wanted_qty" aria-label="Azalt">−</button>
                            <input type="number" 
                                   id="wanted_qty" 
                                   name="wanted_qty" 
                                   min="1" 
                                   max="999"
                                   value="1" 
                                   required
                                   class="qty-input"
                                   readonly>
                            <button type="button" class="qty-btn qty-plus" data-target="wanted_qty" aria-label="Artır">+</button>
                        </div>
                    </div>
                </div>
                
                <!-- Vermek İstediğin Hediye -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <span class="title-icon">🎁</span>
                        Vermek İstediğin Hediye
                        <span class="form-section-subtitle">
                            <span id="offer-selection-count">0/3 seçildi</span>
                            <span class="subtitle-hint">En fazla 3 ürün seçebilirsiniz</span>
                        </span>
                    </h3>
                    <div class="items-grid" id="offer-items-grid">
                        <?php 
                        foreach ($items as $slug => $item) {
                            echo hdh_render_item_card($slug, 'offer_item[' . esc_attr($slug) . ']', 'checkbox', '');
                        }
                        ?>
                    </div>
                    <div class="offer-quantities" id="offer-quantities">
                        <!-- Dynamic quantity steppers will be added here via JS -->
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="form-actions">
                    <button type="submit" class="btn-submit-trade btn-wooden-sign btn-primary">
                        <span class="btn-icon">✨</span>
                        İlanı Oluştur
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<?php
get_footer();
?>
