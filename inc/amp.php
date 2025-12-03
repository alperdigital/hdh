<?php
/**
 * AMP (Accelerated Mobile Pages) Support
 */

// Add AMP theme support
function mi_amp_support() {
    add_theme_support('amp', array(
        'paired' => true,
    ));
}
add_action('after_setup_theme', 'mi_amp_support');

// AMP template redirect
function mi_amp_template_redirect() {
    if (function_exists('is_amp_endpoint') && is_amp_endpoint()) {
        // Use AMP template
        $template = locate_template(array('amp/single.php', 'amp/index.php'));
        if ($template) {
            include $template;
            exit;
        }
    }
}
add_action('template_redirect', 'mi_amp_template_redirect');

// AMP styles
function mi_amp_styles() {
    if (function_exists('is_amp_endpoint') && is_amp_endpoint()) {
        ?>
        <style amp-custom>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1A1A1A;
            background: #FFFFFF;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1, h2, h3 {
            color: #C41E3A;
            font-weight: 700;
        }
        a {
            color: #C41E3A;
            text-decoration: none;
        }
        img {
            max-width: 100%;
            height: auto;
        }
        </style>
        <?php
    }
}
add_action('wp_head', 'mi_amp_styles');

// Remove non-AMP scripts in AMP mode
function mi_amp_remove_scripts() {
    if (function_exists('is_amp_endpoint') && is_amp_endpoint()) {
        remove_action('wp_head', 'wp_print_scripts');
        remove_action('wp_head', 'wp_print_head_scripts', 9);
        remove_action('wp_footer', 'wp_print_footer_scripts', 20);
    }
}
add_action('wp_enqueue_scripts', 'mi_amp_remove_scripts', 100);

// AMP analytics
function mi_amp_analytics() {
    if (function_exists('is_amp_endpoint') && is_amp_endpoint()) {
        $ga_id = get_option('mi_analytics_code');
        if ($ga_id && preg_match('/UA-\d{4,10}-\d{1,4}/', $ga_id, $matches)) {
            ?>
            <amp-analytics type="googleanalytics">
                <script type="application/json">
                {
                    "vars": {
                        "account": "<?php echo esc_js($matches[0]); ?>"
                    },
                    "triggers": {
                        "trackPageview": {
                            "on": "visible",
                            "request": "pageview"
                        }
                    }
                }
                </script>
            </amp-analytics>
            <?php
        }
    }
}
add_action('wp_footer', 'mi_amp_analytics');

