<?php
/**
 * HDH: Hay Day Trade Offers System
 * Custom Post Type and Meta Fields for Trade Offers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hay Day Items List
 * Fixed list of key items for filtering
 * Uses items-config.php for consistency
 */
function hdh_get_hayday_items() {
    $items_config = hdh_get_items_config();
    $items = array();
    foreach ($items_config as $key => $item) {
        $items[$key] = $item['label'];
    }
    return $items;
}

/**
 * Register Custom Post Type: hayday_trade
 */
function hdh_register_trade_offers_cpt() {
    $labels = array(
        'name' => __('Takas İlanları', 'hdh'),
        'singular_name' => __('Takas İlanı', 'hdh'),
        'menu_name' => __('Takas İlanları', 'hdh'),
        'add_new' => __('Yeni İlan', 'hdh'),
        'add_new_item' => __('Yeni Takas İlanı Ekle', 'hdh'),
        'edit_item' => __('Takas İlanını Düzenle', 'hdh'),
        'new_item' => __('Yeni Takas İlanı', 'hdh'),
        'view_item' => __('Takas İlanını Görüntüle', 'hdh'),
        'search_items' => __('Takas İlanlarında Ara', 'hdh'),
        'not_found' => __('Takas ilanı bulunamadı', 'hdh'),
        'not_found_in_trash' => __('Çöp kutusunda takas ilanı bulunamadı', 'hdh'),
        'all_items' => __('Tüm Takas İlanları', 'hdh'),
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'takas'),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-cart',
        'supports' => array('title', 'editor', 'thumbnail', 'comments', 'author'),
        'show_in_rest' => true, // Gutenberg support
    );

    register_post_type('hayday_trade', $args);
}
add_action('init', 'hdh_register_trade_offers_cpt');

/**
 * Add Meta Box for Trade Offer Fields
 */
function hdh_add_trade_meta_box() {
    add_meta_box(
        'hdh_trade_meta',
        __('Takas Detayları', 'hdh'),
        'hdh_trade_meta_box_callback',
        'hayday_trade',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'hdh_add_trade_meta_box');

/**
 * Meta Box Callback
 */
function hdh_trade_meta_box_callback($post) {
    wp_nonce_field('hdh_trade_meta_box', 'hdh_trade_meta_box_nonce');
    
    $hayday_items = hdh_get_hayday_items();
    
    // Get existing values
    $wanted_item = get_post_meta($post->ID, '_hdh_wanted_item', true);
    $wanted_qty = get_post_meta($post->ID, '_hdh_wanted_qty', true);
    $trade_status = get_post_meta($post->ID, '_hdh_trade_status', true);
    
    // Offer items (up to 3)
    $offer_item_1 = get_post_meta($post->ID, '_hdh_offer_item_1', true);
    $offer_qty_1 = get_post_meta($post->ID, '_hdh_offer_qty_1', true);
    $offer_item_2 = get_post_meta($post->ID, '_hdh_offer_item_2', true);
    $offer_qty_2 = get_post_meta($post->ID, '_hdh_offer_qty_2', true);
    $offer_item_3 = get_post_meta($post->ID, '_hdh_offer_item_3', true);
    $offer_qty_3 = get_post_meta($post->ID, '_hdh_offer_qty_3', true);
    
    // Default values
    if (empty($trade_status)) {
        $trade_status = 'open';
    }
    ?>
    <div class="hdh-trade-meta-box">
        <style>
            .hdh-trade-meta-box .form-field {
                margin: 15px 0;
            }
            .hdh-trade-meta-box label {
                display: block;
                font-weight: 600;
                margin-bottom: 5px;
            }
            .hdh-trade-meta-box input[type="text"],
            .hdh-trade-meta-box input[type="number"],
            .hdh-trade-meta-box select {
                width: 100%;
                max-width: 400px;
                padding: 8px;
            }
            .hdh-trade-meta-box .offer-row {
                background: #f9f9f9;
                padding: 15px;
                margin: 10px 0;
                border-left: 3px solid #74C365;
            }
        </style>
        
        <h3 style="color: #74C365; border-bottom: 2px solid #74C365; padding-bottom: 10px;">
            🌾 İSTEDİĞİ ÜRÜN
        </h3>
        
        <div class="form-field">
            <label for="hdh_wanted_item">Ürün:</label>
            <select name="hdh_wanted_item" id="hdh_wanted_item" required>
                <option value="">-- Seçiniz --</option>
                <?php foreach ($hayday_items as $key => $label) : ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($wanted_item, $key); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-field">
            <label for="hdh_wanted_qty">Miktar:</label>
            <input type="number" name="hdh_wanted_qty" id="hdh_wanted_qty" 
                   value="<?php echo esc_attr($wanted_qty); ?>" min="1" required>
        </div>
        
        <h3 style="color: #FFC107; border-bottom: 2px solid #FFC107; padding-bottom: 10px; margin-top: 30px;">
            🎁 VEREBİLECEKLERİ (En fazla 3 ürün)
        </h3>
        
        <?php for ($i = 1; $i <= 3; $i++) : 
            $item_key = "offer_item_{$i}";
            $qty_key = "offer_qty_{$i}";
            $item_value = ${$item_key};
            $qty_value = ${$qty_key};
        ?>
            <div class="offer-row">
                <strong>Ürün <?php echo $i; ?>:</strong>
                <div class="form-field" style="display: inline-block; width: 48%; margin-right: 2%;">
                    <select name="hdh_<?php echo esc_attr($item_key); ?>" id="hdh_<?php echo esc_attr($item_key); ?>">
                        <option value="">-- Seçiniz (Opsiyonel) --</option>
                        <?php foreach ($hayday_items as $key => $label) : ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($item_value, $key); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field" style="display: inline-block; width: 48%;">
                    <input type="number" name="hdh_<?php echo esc_attr($qty_key); ?>" 
                           id="hdh_<?php echo esc_attr($qty_key); ?>" 
                           value="<?php echo esc_attr($qty_value); ?>" min="1" 
                           placeholder="Miktar">
                </div>
            </div>
        <?php endfor; ?>
        
        <h3 style="color: #E86F33; border-bottom: 2px solid #E86F33; padding-bottom: 10px; margin-top: 30px;">
            📋 DURUM
        </h3>
        
        <div class="form-field">
            <label>
                <input type="radio" name="hdh_trade_status" value="open" 
                       <?php checked($trade_status, 'open'); ?>>
                Açık
            </label>
            <br>
            <label>
                <input type="radio" name="hdh_trade_status" value="completed" 
                       <?php checked($trade_status, 'completed'); ?>>
                Tamamlandı
            </label>
        </div>
        
        <p class="description">
            <strong>Not:</strong> İlan sahibi "İSTEDİĞİ" ürünü belirtir. Diğer kullanıcılar bu ürünü 
            vererek karşılığında "VEREBİLECEKLERİ" listesindeki ürünlerden birini alabilir.
        </p>
    </div>
    <?php
}

/**
 * Save Meta Box Data
 */
function hdh_save_trade_meta_box($post_id) {
    // Security checks
    if (!isset($_POST['hdh_trade_meta_box_nonce'])) {
        return;
    }
    
    if (!wp_verify_nonce($_POST['hdh_trade_meta_box_nonce'], 'hdh_trade_meta_box')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Save wanted item
    if (isset($_POST['hdh_wanted_item'])) {
        update_post_meta($post_id, '_hdh_wanted_item', sanitize_text_field($_POST['hdh_wanted_item']));
    }
    
    // Save wanted quantity
    if (isset($_POST['hdh_wanted_qty'])) {
        update_post_meta($post_id, '_hdh_wanted_qty', absint($_POST['hdh_wanted_qty']));
    }
    
    // Save offer items (1-3)
    for ($i = 1; $i <= 3; $i++) {
        $item_key = "hdh_offer_item_{$i}";
        $qty_key = "hdh_offer_qty_{$i}";
        
        if (isset($_POST[$item_key])) {
            $item_value = sanitize_text_field($_POST[$item_key]);
            update_post_meta($post_id, "_hdh_offer_item_{$i}", $item_value);
        }
        
        if (isset($_POST[$qty_key])) {
            $qty_value = absint($_POST[$qty_key]);
            update_post_meta($post_id, "_hdh_offer_qty_{$i}", $qty_value);
        }
    }
    
    // Save trade status
    if (isset($_POST['hdh_trade_status'])) {
        $status = sanitize_text_field($_POST['hdh_trade_status']);
        if (in_array($status, array('open', 'completed'))) {
            update_post_meta($post_id, '_hdh_trade_status', $status);
        }
    }
}
add_action('save_post', 'hdh_save_trade_meta_box');

/**
 * Helper: Get Trade Offer Data
 */
function hdh_get_trade_data($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    return array(
        'wanted_item' => get_post_meta($post_id, '_hdh_wanted_item', true),
        'wanted_qty' => get_post_meta($post_id, '_hdh_wanted_qty', true),
        'offer_items' => array(
            array(
                'item' => get_post_meta($post_id, '_hdh_offer_item_1', true),
                'qty' => get_post_meta($post_id, '_hdh_offer_qty_1', true),
            ),
            array(
                'item' => get_post_meta($post_id, '_hdh_offer_item_2', true),
                'qty' => get_post_meta($post_id, '_hdh_offer_qty_2', true),
            ),
            array(
                'item' => get_post_meta($post_id, '_hdh_offer_item_3', true),
                'qty' => get_post_meta($post_id, '_hdh_offer_qty_3', true),
            ),
        ),
        'trade_status' => get_post_meta($post_id, '_hdh_trade_status', true) ?: 'open',
    );
}

