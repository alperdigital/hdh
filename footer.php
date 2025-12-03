    <footer>
        <div class="container">
            <?php if (is_active_sidebar('footer-1') || is_active_sidebar('footer-2') || is_active_sidebar('footer-3') || is_active_sidebar('footer-4')) : ?>
                <div class="footer-widgets">
                    <div class="footer-widget-column">
                        <?php dynamic_sidebar('footer-1'); ?>
                    </div>
                    <div class="footer-widget-column">
                        <?php dynamic_sidebar('footer-2'); ?>
                    </div>
                    <div class="footer-widget-column">
                        <?php dynamic_sidebar('footer-3'); ?>
                    </div>
                    <div class="footer-widget-column">
                        <?php dynamic_sidebar('footer-4'); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="footer-content">
                <div class="footer-info">
                    <p><?php echo esc_html(get_theme_mod('mi_footer_text', '© ' . date('Y') . ' ' . get_bloginfo('name') . '. Tüm hakları saklıdır.')); ?></p>
                </div>
                <div class="footer-links">
                    <?php
                    wp_nav_menu(array(
                        'theme_location' => 'footer',
                        'menu_class' => 'footer-menu',
                        'container' => false,
                        'fallback_cb' => false,
                    ));
                    ?>
                </div>
                <?php if (get_theme_mod('mi_show_social_footer', true)) : ?>
                    <div class="footer-social">
                        <?php mi_render_social_links(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </footer>
    
    <?php wp_footer(); ?>
</body>
</html>

