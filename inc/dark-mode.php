<?php
/**
 * Dark Mode Feature
 */

// Add dark mode toggle to customizer
function mi_dark_mode_customizer($wp_customize) {
    $wp_customize->add_setting('mi_enable_dark_mode', array(
        'default' => false,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    
    $wp_customize->add_control('mi_enable_dark_mode', array(
        'label' => __('Dark Mode Özelliğini Etkinleştir', 'mi-theme'),
        'section' => 'mi_general_settings',
        'type' => 'checkbox',
    ));
    
    $wp_customize->add_setting('mi_dark_mode_default', array(
        'default' => false,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    
    $wp_customize->add_control('mi_dark_mode_default', array(
        'label' => __('Varsayılan Dark Mode', 'mi-theme'),
        'section' => 'mi_general_settings',
        'type' => 'checkbox',
        'description' => __('Site varsayılan olarak dark mode ile açılsın', 'mi-theme'),
    ));
}
add_action('customize_register', 'mi_dark_mode_customizer');

// Dark mode toggle button
function mi_dark_mode_toggle() {
    if (!get_theme_mod('mi_enable_dark_mode', false)) {
        return;
    }
    ?>
    <button id="dark-mode-toggle" class="dark-mode-toggle" aria-label="Dark Mode">
        <span class="dark-mode-icon">🌙</span>
        <span class="light-mode-icon">☀️</span>
    </button>
    <?php
}
add_action('wp_footer', 'mi_dark_mode_toggle');

// Dark mode script
function mi_dark_mode_script() {
    if (!get_theme_mod('mi_enable_dark_mode', false)) {
        return;
    }
    
    $default_dark = get_theme_mod('mi_dark_mode_default', false) ? 'true' : 'false';
    ?>
    <script>
    (function() {
        const darkModeToggle = document.getElementById('dark-mode-toggle');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const defaultDark = <?php echo $default_dark; ?>;
        
        // Get saved preference or use default
        let isDark = localStorage.getItem('darkMode');
        if (isDark === null) {
            isDark = defaultDark || prefersDark;
        } else {
            isDark = isDark === 'true';
        }
        
        function setDarkMode(dark) {
            document.documentElement.classList.toggle('dark-mode', dark);
            localStorage.setItem('darkMode', dark);
            if (darkModeToggle) {
                darkModeToggle.setAttribute('aria-pressed', dark);
            }
        }
        
        // Set initial state
        setDarkMode(isDark);
        
        // Toggle on button click
        if (darkModeToggle) {
            darkModeToggle.addEventListener('click', function() {
                isDark = !isDark;
                setDarkMode(isDark);
            });
        }
        
        // Listen for system preference changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
            if (localStorage.getItem('darkMode') === null) {
                setDarkMode(e.matches);
            }
        });
    })();
    </script>
    <?php
}
add_action('wp_footer', 'mi_dark_mode_script');

