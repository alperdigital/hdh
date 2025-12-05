<?php
/**
 * HDH: Item Card Component
 * Renders a selectable item card with image and label
 * 
 * @param string $slug Item slug/key
 * @param string $name Input name attribute
 * @param string $type Input type ('radio' or 'checkbox')
 * @param string $value Current value (for checked state)
 * @return string HTML output
 */
if (!function_exists('hdh_render_item_card')) {
    function hdh_render_item_card($slug, $name, $type = 'radio', $value = '') {
        $items = hdh_get_items_config();
        
        if (!isset($items[$slug])) {
            return '';
        }
        
        $item = $items[$slug];
        $item_label = $item['label'];
        $item_image = $item['image'];
        $input_id = sanitize_html_class($name . '_' . $slug);
        $is_checked = ($value === $slug || (is_array($value) && in_array($slug, $value)));
        
        ob_start();
        ?>
        <div class="item-card-wrapper">
            <input type="<?php echo esc_attr($type); ?>" 
                   id="<?php echo esc_attr($input_id); ?>" 
                   name="<?php echo esc_attr($name); ?>" 
                   value="<?php echo esc_attr($slug); ?>" 
                   class="item-card-input"
                   <?php echo $is_checked ? 'checked' : ''; ?>>
            <label for="<?php echo esc_attr($input_id); ?>" class="item-card">
                <div class="item-card-image-wrapper">
                    <img src="<?php echo esc_url($item_image); ?>" 
                         alt="<?php echo esc_attr($item_label); ?>" 
                         class="item-card-image"
                         loading="lazy"
                         decoding="async"
                         width="80"
                         height="80">
                    <div class="item-card-overlay">
                        <span class="item-card-check">✓</span>
                    </div>
                </div>
                <div class="item-card-label">
                    <?php echo esc_html($item_label); ?>
                </div>
            </label>
        </div>
        <?php
        return ob_get_clean();
    }
}

