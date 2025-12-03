<?php
/**
 * Popular Posts Widget
 */

class MI_Popular_Posts_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'mi_popular_posts',
            __('MI Popüler Yazılar', 'mi-theme'),
            array('description' => __('En çok görüntülenen yazıları gösterir', 'mi-theme'))
        );
    }
    
    public function widget($args, $instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Popüler Yazılar', 'mi-theme');
        $number = !empty($instance['number']) ? absint($instance['number']) : 5;
        $show_views = !empty($instance['show_views']) ? 1 : 0;
        $show_date = !empty($instance['show_date']) ? 1 : 0;
        
        echo $args['before_widget'];
        echo $args['before_title'] . esc_html($title) . $args['after_title'];
        
        $query_args = array(
            'post_type' => 'post',
            'posts_per_page' => $number,
            'meta_key' => 'mi_post_views_count',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'ignore_sticky_posts' => true,
        );
        
        $popular_posts = new WP_Query($query_args);
        
        if ($popular_posts->have_posts()) {
            echo '<ul class="popular-posts-list">';
            while ($popular_posts->have_posts()) {
                $popular_posts->the_post();
                ?>
                <li class="popular-post-item">
                    <?php if (has_post_thumbnail()) : ?>
                        <a href="<?php the_permalink(); ?>" class="popular-post-thumbnail">
                            <?php the_post_thumbnail('thumbnail'); ?>
                        </a>
                    <?php endif; ?>
                    <div class="popular-post-content">
                        <h4 class="popular-post-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h4>
                        <?php if ($show_date) : ?>
                            <span class="popular-post-date"><?php echo get_the_date(); ?></span>
                        <?php endif; ?>
                        <?php if ($show_views) : ?>
                            <span class="popular-post-views"><?php echo mi_display_post_views(); ?></span>
                        <?php endif; ?>
                    </div>
                </li>
                <?php
            }
            echo '</ul>';
            wp_reset_postdata();
        } else {
            echo '<p>' . __('Henüz popüler yazı bulunmamaktadır.', 'mi-theme') . '</p>';
        }
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Popüler Yazılar', 'mi-theme');
        $number = !empty($instance['number']) ? absint($instance['number']) : 5;
        $show_views = !empty($instance['show_views']) ? 1 : 0;
        $show_date = !empty($instance['show_date']) ? 1 : 0;
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Başlık:', 'mi-theme'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('number')); ?>"><?php _e('Yazı Sayısı:', 'mi-theme'); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr($this->get_field_id('number')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('number')); ?>" 
                   type="number" step="1" min="1" value="<?php echo esc_attr($number); ?>">
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_views); ?> 
                   id="<?php echo esc_attr($this->get_field_id('show_views')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('show_views')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('show_views')); ?>"><?php _e('Görüntülenme sayısını göster', 'mi-theme'); ?></label>
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_date); ?> 
                   id="<?php echo esc_attr($this->get_field_id('show_date')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('show_date')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('show_date')); ?>"><?php _e('Tarih göster', 'mi-theme'); ?></label>
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['number'] = (!empty($new_instance['number'])) ? absint($new_instance['number']) : 5;
        $instance['show_views'] = !empty($new_instance['show_views']) ? 1 : 0;
        $instance['show_date'] = !empty($new_instance['show_date']) ? 1 : 0;
        return $instance;
    }
}

function mi_register_popular_posts_widget() {
    register_widget('MI_Popular_Posts_Widget');
}
add_action('widgets_init', 'mi_register_popular_posts_widget');

