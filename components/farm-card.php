<?php
/**
 * HDH: Farm Board Card Component
 * Reusable card component with cartoon farm styling
 * 
 * @param string $title Card title
 * @param string $content Card content
 * @param string $icon Icon emoji or SVG
 * @param string $class Additional CSS classes
 */
if (!function_exists('hdh_farm_card')) {
    function hdh_farm_card($title = '', $content = '', $icon = '🌾', $class = '') {
        ?>
        <div class="farm-board-card <?php echo esc_attr($class); ?>">
            <?php if ($title) : ?>
                <h3 class="farm-board-card-title">
                    <?php if ($icon) : ?>
                        <span class="farm-board-card-icon"><?php echo esc_html($icon); ?></span>
                    <?php endif; ?>
                    <?php echo esc_html($title); ?>
                </h3>
            <?php endif; ?>
            <?php if ($content) : ?>
                <div class="farm-board-card-content">
                    <?php echo wp_kses_post($content); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
?>

