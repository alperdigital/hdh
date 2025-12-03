<?php
/**
 * Modular System - Plugin-like Structure
 */

// Module loader
class MI_Module_Loader {
    private static $modules = array();
    
    public static function register_module($module_name, $module_path) {
        self::$modules[$module_name] = $module_path;
    }
    
    public static function load_modules() {
        $modules_dir = get_template_directory() . '/modules/';
        
        if (is_dir($modules_dir)) {
            $modules = glob($modules_dir . '*/module.php');
            
            foreach ($modules as $module) {
                if (file_exists($module)) {
                    require_once $module;
                }
            }
        }
    }
    
    public static function get_active_modules() {
        return get_option('mi_active_modules', array());
    }
    
    public static function is_module_active($module_name) {
        $active = self::get_active_modules();
        return in_array($module_name, $active);
    }
}

// Load modules
add_action('after_setup_theme', array('MI_Module_Loader', 'load_modules'));

// Calculate reading time (global function)
if (!function_exists('mi_calculate_reading_time')) {
    function mi_calculate_reading_time($content) {
        $word_count = str_word_count(strip_tags($content));
        $reading_time = ceil($word_count / 200); // Average reading speed: 200 words per minute
        return $reading_time > 0 ? $reading_time : null;
    }
}

// Reading time module
function mi_reading_time_module() {
    if (!get_option('mi_enable_reading_time', 1)) {
        return;
    }
    
    function mi_display_reading_time() {
        // Okuma süresi kaldırıldı - kullanıcı isteği üzerine
        // Artık okuma süresi gösterilmiyor
    }
    // add_action('mi_post_meta', 'mi_display_reading_time', 5); // Devre dışı bırakıldı
}
add_action('init', 'mi_reading_time_module');

// Lazy loading module
function mi_lazy_load_module() {
    if (!get_option('mi_enable_lazy_load', 0)) {
        return;
    }
    
    function mi_add_lazy_loading($attr, $attachment, $size) {
        $attr['loading'] = 'lazy';
        $attr['decoding'] = 'async';
        return $attr;
    }
    add_filter('wp_get_attachment_image_attributes', 'mi_add_lazy_loading', 10, 3);
}
add_action('init', 'mi_lazy_load_module');

// Social media links widget
class MI_Social_Links_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'mi_social_links',
            __('MI Sosyal Medya', 'mi-theme'),
            array('description' => __('Sosyal medya linklerinizi gösterin', 'mi-theme'))
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        $social_networks = array(
            'facebook' => 'Facebook',
            'twitter' => 'Twitter/X',
            'instagram' => 'Instagram',
            'linkedin' => 'LinkedIn',
            'youtube' => 'YouTube',
            'whatsapp' => 'WhatsApp',
            'telegram' => 'Telegram',
        );
        
        echo '<div class="social-links-widget">';
        foreach ($social_networks as $key => $label) {
            $url = get_theme_mod('mi_social_' . $key, '');
            if ($url) {
                echo '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer" class="social-link social-' . esc_attr($key) . '">' . esc_html($label) . '</a>';
            }
        }
        echo '</div>';
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Sosyal Medya', 'mi-theme');
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Başlık:', 'mi-theme'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p><?php _e('Sosyal medya linklerinizi Customizer > Sosyal Medya bölümünden ayarlayabilirsiniz.', 'mi-theme'); ?></p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        return $instance;
    }
}

function mi_register_social_widget() {
    register_widget('MI_Social_Links_Widget');
}
add_action('widgets_init', 'mi_register_social_widget');

