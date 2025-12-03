<?php
/**
 * Mobile Menu (Hamburger)
 */

// Mobile menu toggle button
function mi_mobile_menu_toggle() {
    ?>
    <button class="mobile-menu-toggle" id="mobile-menu-toggle" aria-label="Menü" aria-expanded="false">
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
    </button>
    <?php
}

// Mobile menu script
function mi_mobile_menu_script() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        const toggle = $('#mobile-menu-toggle');
        const menu = $('.main-navigation');
        
        if (toggle.length && menu.length) {
            toggle.on('click', function() {
                const isExpanded = $(this).attr('aria-expanded') === 'true';
                $(this).attr('aria-expanded', !isExpanded);
                $(this).toggleClass('active');
                menu.toggleClass('mobile-menu-open');
                $('body').toggleClass('menu-open');
            });
            
            // Close menu when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.main-navigation, #mobile-menu-toggle').length) {
                    toggle.attr('aria-expanded', 'false').removeClass('active');
                    menu.removeClass('mobile-menu-open');
                    $('body').removeClass('menu-open');
                }
            });
            
            // Close menu on window resize
            $(window).on('resize', function() {
                if ($(window).width() > 768) {
                    toggle.attr('aria-expanded', 'false').removeClass('active');
                    menu.removeClass('mobile-menu-open');
                    $('body').removeClass('menu-open');
                }
            });
        }
    });
    </script>
    <?php
}
add_action('wp_footer', 'mi_mobile_menu_script');

