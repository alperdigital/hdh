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
                
                <!-- Legal Links -->
                <div class="footer-legal-links">
                    <a href="<?php echo esc_url(home_url('/uyelik-sozlesmesi')); ?>" class="footer-legal-link"><?php echo esc_html(hdh_get_content('footer', 'terms_link_text', 'Üyelik Sözleşmesi')); ?></a>
                    <span class="footer-legal-separator">•</span>
                    <a href="<?php echo esc_url(home_url('/gizlilik-politikasi')); ?>" class="footer-legal-link"><?php echo esc_html(hdh_get_content('footer', 'privacy_link_text', 'Gizlilik Politikası & KVKK')); ?></a>
                </div>
                
                <?php if (get_theme_mod('mi_show_social_footer', true)) : ?>
                    <div class="footer-social">
                        <?php mi_render_social_links(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </footer>
    
    <!-- HDH: Bottom Navigation (Mobile & Desktop) -->
    <nav class="bottom-navigation" id="bottom-navigation" role="navigation" aria-label="Ana Navigasyon">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="bottom-nav-item" data-nav="home">
            <span class="bottom-nav-icon">🏠</span>
            <span class="bottom-nav-label"><?php echo esc_html(hdh_get_content('navigation', 'home_label', 'Anasayfa')); ?></span>
        </a>
        <a href="<?php echo esc_url(home_url('/ara')); ?>" class="bottom-nav-item" data-nav="search">
            <span class="bottom-nav-icon">🔍</span>
            <span class="bottom-nav-label"><?php echo esc_html(hdh_get_content('navigation', 'search_label', 'Ara')); ?></span>
        </a>
        <a href="<?php echo esc_url(home_url('/ilan-ver')); ?>" class="bottom-nav-item bottom-nav-center" data-nav="create">
            <span class="bottom-nav-center-icon">+</span>
            <span class="bottom-nav-center-label"><?php echo esc_html(hdh_get_content('navigation', 'create_label', 'İlan Ver')); ?></span>
        </a>
        <a href="<?php echo esc_url(home_url('/cekilis')); ?>" class="bottom-nav-item" data-nav="raffle">
            <span class="bottom-nav-icon">🎫</span>
            <span class="bottom-nav-label"><?php echo esc_html(hdh_get_content('navigation', 'raffle_label', 'Çekiliş')); ?></span>
        </a>
        <a href="<?php echo esc_url(home_url('/profil')); ?>" class="bottom-nav-item" data-nav="profile">
            <span class="bottom-nav-icon">👤</span>
            <span class="bottom-nav-label"><?php echo esc_html(hdh_get_content('navigation', 'profile_label', 'Profil')); ?></span>
        </a>
    </nav>
    
    <!-- HDH: Tasks Panel & Gift Exchange Panel (Visible on all pages for logged-in users) -->
    <?php if (is_user_logged_in()) : 
        $current_user_id = get_current_user_id();
        if (function_exists('hdh_render_tasks_panel')) {
            hdh_render_tasks_panel($current_user_id);
        }
        if (function_exists('hdh_render_gift_exchange_panel')) {
            hdh_render_gift_exchange_panel($current_user_id);
        }
    endif; ?>
    
    <?php wp_footer(); ?>
</body>
</html>

