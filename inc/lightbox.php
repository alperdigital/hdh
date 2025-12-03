<?php
/**
 * Lightbox Gallery System
 */

function mi_enqueue_lightbox() {
    wp_enqueue_script('jquery');
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Lightbox for gallery images
        $('.gallery, .wp-block-gallery').each(function() {
            $(this).find('a').attr('data-lightbox', 'gallery');
        });
        
        // Simple lightbox implementation
        $('a[data-lightbox]').on('click', function(e) {
            e.preventDefault();
            const imgSrc = $(this).attr('href');
            const imgAlt = $(this).find('img').attr('alt') || '';
            
            // Create lightbox overlay
            const lightbox = $('<div class="lightbox-overlay"><div class="lightbox-content"><img src="' + imgSrc + '" alt="' + imgAlt + '"><button class="lightbox-close">&times;</button></div></div>');
            
            $('body').append(lightbox);
            $('body').css('overflow', 'hidden');
            
            // Close on click
            lightbox.on('click', function(e) {
                if ($(e.target).hasClass('lightbox-overlay') || $(e.target).hasClass('lightbox-close')) {
                    lightbox.remove();
                    $('body').css('overflow', '');
                }
            });
            
            // Close on ESC key
            $(document).on('keydown.lightbox', function(e) {
                if (e.keyCode === 27) {
                    lightbox.remove();
                    $('body').css('overflow', '');
                    $(document).off('keydown.lightbox');
                }
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'mi_enqueue_lightbox');

