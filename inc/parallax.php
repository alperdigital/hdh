<?php
/**
 * Parallax Scrolling Effects
 */

// Enqueue parallax script
function mi_enqueue_parallax() {
    wp_enqueue_script('jquery');
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Parallax effect for elements with data-parallax attribute
        $(window).on('scroll', function() {
            var scrolled = $(window).scrollTop();
            
            $('[data-parallax]').each(function() {
                var $this = $(this);
                var speed = $this.data('parallax') || 0.5;
                var yPos = -(scrolled * speed);
                $this.css('transform', 'translateY(' + yPos + 'px)');
            });
            
            // Parallax for background images
            $('[data-parallax-bg]').each(function() {
                var $this = $(this);
                var speed = $this.data('parallax-bg') || 0.5;
                var scrolled = $(window).scrollTop();
                var yPos = -(scrolled * speed);
                $this.css('background-position', 'center ' + yPos + 'px');
            });
        });
        
        // Smooth scroll for anchor links
        $('a[href^="#"]').on('click', function(e) {
            var target = $(this.getAttribute('href'));
            if (target.length) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: target.offset().top - 80
                }, 800);
            }
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'mi_enqueue_parallax');

// Parallax shortcode
function mi_parallax_shortcode($atts, $content = '') {
    $atts = shortcode_atts(array(
        'speed' => '0.5',
        'image' => '',
        'height' => '400px',
        'overlay' => 'rgba(0,0,0,0.3)',
    ), $atts);
    
    $style = 'min-height: ' . esc_attr($atts['height']) . ';';
    if ($atts['image']) {
        $style .= 'background-image: url(' . esc_url($atts['image']) . ');';
    }
    if ($atts['overlay']) {
        $style .= 'position: relative;';
    }
    
    ob_start();
    ?>
    <div class="parallax-section" data-parallax-bg="<?php echo esc_attr($atts['speed']); ?>" style="<?php echo $style; ?>">
        <?php if ($atts['overlay']) : ?>
            <div class="parallax-overlay" style="background-color: <?php echo esc_attr($atts['overlay']); ?>;"></div>
        <?php endif; ?>
        <div class="parallax-content">
            <?php echo do_shortcode($content); ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('parallax', 'mi_parallax_shortcode');

// Add parallax option to customizer
function mi_parallax_customizer($wp_customize) {
    $wp_customize->add_setting('mi_enable_parallax', array(
        'default' => false,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    
    $wp_customize->add_control('mi_enable_parallax', array(
        'label' => __('Parallax Efektlerini Etkinleştir', 'mi-theme'),
        'section' => 'mi_general_settings',
        'type' => 'checkbox',
        'description' => __('Parallax scrolling efektlerini aktif eder', 'mi-theme'),
    ));
}
add_action('customize_register', 'mi_parallax_customizer');

