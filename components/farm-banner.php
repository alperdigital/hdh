<?php
/**
 * HDH: Farm Banner Component
 * Decorative banner with farm-themed styling
 * 
 * @param string $text Banner text
 * @param string $type Banner type (info, success, warning)
 */
if (!function_exists('hdh_farm_banner')) {
    function hdh_farm_banner($text = '', $type = 'info') {
        $classes = 'farm-banner farm-banner-' . esc_attr($type);
        $icons = array(
            'info' => 'ℹ️',
            'success' => '✅',
            'warning' => '⚠️',
            'event' => '🎉'
        );
        $icon = isset($icons[$type]) ? $icons[$type] : '🌾';
        ?>
        <div class="<?php echo esc_attr($classes); ?>">
            <span class="farm-banner-icon"><?php echo esc_html($icon); ?></span>
            <span class="farm-banner-text"><?php echo esc_html($text); ?></span>
        </div>
        <?php
    }
}
?>

