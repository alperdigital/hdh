<?php
/**
 * HDH: CTA Buttons Component
 * Wooden sign style call-to-action buttons
 * 
 * @param array $buttons Array of button configs: ['text' => '', 'url' => '', 'type' => 'primary|secondary']
 */
if (!function_exists('hdh_cta_buttons')) {
    function hdh_cta_buttons($buttons = array()) {
        if (empty($buttons)) return;
        ?>
        <div class="cta-buttons-wrapper">
            <?php foreach ($buttons as $button) : 
                $text = isset($button['text']) ? $button['text'] : '';
                $url = isset($button['url']) ? $button['url'] : '#';
                $type = isset($button['type']) ? $button['type'] : 'primary';
                $icon = isset($button['icon']) ? $button['icon'] : '';
            ?>
                <a href="<?php echo esc_url($url); ?>" class="btn-wooden-sign btn-<?php echo esc_attr($type); ?>">
                    <?php if ($icon) : ?>
                        <span><?php echo esc_html($icon); ?></span>
                    <?php endif; ?>
                    <?php echo esc_html($text); ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
    }
}
?>

