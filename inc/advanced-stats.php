<?php
/**
 * Advanced Statistics System
 */

// Track post views with IP (prevent duplicate counts)
function mi_track_post_views_advanced($post_id) {
    if (!is_single()) return;
    if (empty($post_id)) {
        $post_id = get_the_ID();
    }
    
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $view_key = 'mi_view_' . md5($ip_address . $post_id . date('Y-m-d'));
    
    // Check if already viewed today
    if (get_transient($view_key)) {
        return;
    }
    
    // Set transient for 24 hours
    set_transient($view_key, true, DAY_IN_SECONDS);
    
    // Increment view count
    $count = get_post_meta($post_id, 'mi_post_views_count', true);
    $count = $count ? intval($count) + 1 : 1;
    update_post_meta($post_id, 'mi_post_views_count', $count);
    
    // Track daily views
    $today = date('Y-m-d');
    $daily_key = 'mi_daily_views_' . $post_id . '_' . $today;
    $daily_count = get_transient($daily_key);
    $daily_count = $daily_count ? intval($daily_count) + 1 : 1;
    set_transient($daily_key, $daily_count, DAY_IN_SECONDS);
}

// Get daily views
function mi_get_daily_views($post_id) {
    $today = date('Y-m-d');
    $daily_key = 'mi_daily_views_' . $post_id . '_' . $today;
    return get_transient($daily_key) ?: 0;
}

// Get weekly views
function mi_get_weekly_views($post_id) {
    $total = 0;
    for ($i = 0; $i < 7; $i++) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $daily_key = 'mi_daily_views_' . $post_id . '_' . $date;
        $total += get_transient($daily_key) ?: 0;
    }
    return $total;
}

// Statistics widget
class MI_Statistics_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'mi_statistics',
            __('MI İstatistikler', 'mi-theme'),
            array('description' => __('Site istatistiklerini gösterir', 'mi-theme'))
        );
    }
    
    public function widget($args, $instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('İstatistikler', 'mi-theme');
        
        echo $args['before_widget'];
        echo $args['before_title'] . esc_html($title) . $args['after_title'];
        
        $total_posts = wp_count_posts()->publish;
        $total_categories = wp_count_terms('category');
        $total_tags = wp_count_terms('post_tag');
        
        ?>
        <ul class="statistics-list">
            <li class="stat-item">
                <span class="stat-label"><?php _e('Toplam Yazı', 'mi-theme'); ?>:</span>
                <span class="stat-value"><?php echo number_format_i18n($total_posts); ?></span>
            </li>
            <li class="stat-item">
                <span class="stat-label"><?php _e('Kategoriler', 'mi-theme'); ?>:</span>
                <span class="stat-value"><?php echo number_format_i18n($total_categories); ?></span>
            </li>
            <li class="stat-item">
                <span class="stat-label"><?php _e('Etiketler', 'mi-theme'); ?>:</span>
                <span class="stat-value"><?php echo number_format_i18n($total_tags); ?></span>
            </li>
        </ul>
        <?php
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('İstatistikler', 'mi-theme');
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Başlık:', 'mi-theme'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        return $instance;
    }
}

function mi_register_statistics_widget() {
    register_widget('MI_Statistics_Widget');
}
add_action('widgets_init', 'mi_register_statistics_widget');

// Replace basic tracking with advanced
remove_action('wp_head', 'mi_track_post_views');
add_action('wp_head', 'mi_track_post_views_advanced');

