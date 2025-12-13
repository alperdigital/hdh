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
            <h2 class="section-title-cartoon">Hediyeleşme Başlasın</h2>
            
            <form id="create-trade-form" class="trade-create-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('hdh_create_trade', 'hdh_trade_nonce'); ?>
                <input type="hidden" name="action" value="hdh_create_trade">
                
                <!-- Almak İstediğin Hediye -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <span class="title-icon">🔍</span>
                        Almak İstediğin Hediye
                    </h3>
                    <div class="items-grid" id="wanted-items-grid">
                        <?php 
                        $items = hdh_get_items_config();
                        foreach ($items as $slug => $item) {
                            echo hdh_render_item_card($slug, 'wanted_item', 'radio', '');
                        }
                        ?>
                    </div>
                    <div class="quantity-input-wrapper">
                        <label for="wanted_qty">Miktar:</label>
                        <input type="number" 
                               id="wanted_qty" 
                               name="wanted_qty" 
                               min="1" 
                               value="1" 
                               required
                               class="quantity-input">
                    </div>
                </div>
                
                <!-- Vermek İstediğin Hediye -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <span class="title-icon">🎁</span>
                        Vermek İstediğin Hediye (En fazla 3 ürün seçebilirsiniz)
                    </h3>
                    <div class="items-grid" id="offer-items-grid">
                        <?php 
                        foreach ($items as $slug => $item) {
                            echo hdh_render_item_card($slug, 'offer_item[' . esc_attr($slug) . ']', 'checkbox', '');
                        }
                        ?>
                    </div>
                    <div class="offer-quantities" id="offer-quantities">
                        <!-- Dynamic quantity inputs will be added here via JS -->
                    </div>
                </div>
                
                <!-- İlan Başlığı -->
                <div class="form-section">
                    <div class="form-field">
                        <label for="trade_title">İlan Başlığı:</label>
                        <input type="text" 
                               id="trade_title" 
                               name="trade_title" 
                               required
                               placeholder="Örn: 7 Bant arıyorum, 7 Cıvata verebilirim"
                               class="form-input">
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
