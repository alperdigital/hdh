<?php
/**
 * Additional Widgets
 */

// Recent Posts Widget (Custom Styled)
class MI_Recent_Posts_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'mi_recent_posts',
            __('MI Son Yazılar', 'mi-theme'),
            array('description' => __('Özel stilli son yazılar widget', 'mi-theme'))
        );
    }
    
    public function widget($args, $instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Son Yazılar', 'mi-theme');
        $number = !empty($instance['number']) ? absint($instance['number']) : 5;
        $show_date = !empty($instance['show_date']) ? 1 : 0;
        $show_thumbnail = !empty($instance['show_thumbnail']) ? 1 : 0;
        
        echo $args['before_widget'];
        echo $args['before_title'] . esc_html($title) . $args['after_title'];
        
        $recent = new WP_Query(array(
            'post_type' => 'post',
            'posts_per_page' => $number,
            'ignore_sticky_posts' => true,
        ));
        
        if ($recent->have_posts()) {
            echo '<ul class="recent-posts-list">';
            while ($recent->have_posts()) {
                $recent->the_post();
                ?>
                <li class="recent-post-item">
                    <?php if ($show_thumbnail && has_post_thumbnail()) : ?>
                        <a href="<?php the_permalink(); ?>" class="recent-post-thumb">
                            <?php the_post_thumbnail('thumbnail'); ?>
                        </a>
                    <?php endif; ?>
                    <div class="recent-post-content">
                        <h4 class="recent-post-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h4>
                        <?php if ($show_date) : ?>
                            <span class="recent-post-date"><?php echo get_the_date(); ?></span>
                        <?php endif; ?>
                    </div>
                </li>
                <?php
            }
            echo '</ul>';
            wp_reset_postdata();
        }
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Son Yazılar', 'mi-theme');
        $number = !empty($instance['number']) ? absint($instance['number']) : 5;
        $show_date = !empty($instance['show_date']) ? 1 : 0;
        $show_thumbnail = !empty($instance['show_thumbnail']) ? 1 : 0;
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
            <input class="checkbox" type="checkbox" <?php checked($show_date); ?> 
                   id="<?php echo esc_attr($this->get_field_id('show_date')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('show_date')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('show_date')); ?>"><?php _e('Tarih göster', 'mi-theme'); ?></label>
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_thumbnail); ?> 
                   id="<?php echo esc_attr($this->get_field_id('show_thumbnail')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('show_thumbnail')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('show_thumbnail')); ?>"><?php _e('Thumbnail göster', 'mi-theme'); ?></label>
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['number'] = (!empty($new_instance['number'])) ? absint($new_instance['number']) : 5;
        $instance['show_date'] = !empty($new_instance['show_date']) ? 1 : 0;
        $instance['show_thumbnail'] = !empty($new_instance['show_thumbnail']) ? 1 : 0;
        return $instance;
    }
}

// Categories Widget (Custom Styled)
class MI_Categories_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'mi_categories',
            __('Kategoriler', 'mi-theme'),
            array('description' => __('Özel stilli kategoriler widget', 'mi-theme'))
        );
    }
    
    public function widget($args, $instance) {
        // Widget görünürlük kontrolü
        if (get_option('mi_show_categories_widget', 0) != 1) {
            return; // Widget kapalıysa gösterilmez
        }
        
        $title = !empty($instance['title']) ? $instance['title'] : __('Kategoriler', 'mi-theme');
        $count = !empty($instance['count']) ? 1 : 0;
        $hierarchical = !empty($instance['hierarchical']) ? 1 : 0;
        
        echo $args['before_widget'];
        echo $args['before_title'] . esc_html($title) . $args['after_title'];
        
        $cat_args = array(
            'orderby' => 'name',
            'show_count' => $count,
            'hierarchical' => $hierarchical,
            'title_li' => '',
        );
        
        echo '<ul class="categories-list">';
        wp_list_categories($cat_args);
        echo '</ul>';
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Kategoriler', 'mi-theme');
        $count = !empty($instance['count']) ? 1 : 0;
        $hierarchical = !empty($instance['hierarchical']) ? 1 : 0;
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Başlık:', 'mi-theme'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($count); ?> 
                   id="<?php echo esc_attr($this->get_field_id('count')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('count')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('count')); ?>"><?php _e('Yazı sayısını göster', 'mi-theme'); ?></label>
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($hierarchical); ?> 
                   id="<?php echo esc_attr($this->get_field_id('hierarchical')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('hierarchical')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('hierarchical')); ?>"><?php _e('Hiyerarşik göster', 'mi-theme'); ?></label>
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['count'] = !empty($new_instance['count']) ? 1 : 0;
        $instance['hierarchical'] = !empty($new_instance['hierarchical']) ? 1 : 0;
        return $instance;
    }
}

// Tags Widget (Tag Cloud)
class MI_Tags_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'mi_tags',
            __('MI Etiketler', 'mi-theme'),
            array('description' => __('Etiket bulutu widget', 'mi-theme'))
        );
    }
    
    public function widget($args, $instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Etiketler', 'mi-theme');
        $number = !empty($instance['number']) ? absint($instance['number']) : 45;
        
        echo $args['before_widget'];
        echo $args['before_title'] . esc_html($title) . $args['after_title'];
        
        wp_tag_cloud(array(
            'number' => $number,
            'smallest' => 12,
            'largest' => 18,
            'unit' => 'px',
        ));
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Etiketler', 'mi-theme');
        $number = !empty($instance['number']) ? absint($instance['number']) : 45;
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Başlık:', 'mi-theme'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('number')); ?>"><?php _e('Etiket Sayısı:', 'mi-theme'); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr($this->get_field_id('number')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('number')); ?>" 
                   type="number" step="1" min="1" value="<?php echo esc_attr($number); ?>">
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['number'] = (!empty($new_instance['number'])) ? absint($new_instance['number']) : 45;
        return $instance;
    }
}

// Archive Widget
class MI_Archive_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'mi_archive',
            __('MI Arşiv', 'mi-theme'),
            array('description' => __('Arşiv widget', 'mi-theme'))
        );
    }
    
    public function widget($args, $instance) {
        // Widget görünürlük kontrolü
        if (get_option('mi_show_archives_widget', 1) != 1) {
            return; // Widget kapalıysa gösterilmez
        }
        
        $title = !empty($instance['title']) ? $instance['title'] : __('Arşiv', 'mi-theme');
        $count = !empty($instance['count']) ? 1 : 0;
        $dropdown = !empty($instance['dropdown']) ? 1 : 0;
        
        echo $args['before_widget'];
        echo $args['before_title'] . esc_html($title) . $args['after_title'];
        
        // Türkçe ay isimleri
        $turkish_months = array(
            'January' => 'Ocak', 'February' => 'Şubat', 'March' => 'Mart',
            'April' => 'Nisan', 'May' => 'Mayıs', 'June' => 'Haziran',
            'July' => 'Temmuz', 'August' => 'Ağustos', 'September' => 'Eylül',
            'October' => 'Ekim', 'November' => 'Kasım', 'December' => 'Aralık'
        );
        
        if ($dropdown) {
            ?>
            <select name="archive-dropdown" onchange="document.location.href=this.options[this.selectedIndex].value;">
                <option value=""><?php _e('Ay Seçin', 'mi-theme'); ?></option>
                <?php 
                $archives_html = wp_get_archives(array('type' => 'monthly', 'format' => 'option', 'echo' => 0, 'show_post_count' => $count));
                foreach ($turkish_months as $en => $tr) {
                    $archives_html = str_replace($en, $tr, $archives_html);
                }
                echo $archives_html;
                ?>
            </select>
            <?php
        } else {
            echo '<ul class="archive-list">';
            $archives_html = wp_get_archives(array('type' => 'monthly', 'echo' => 0, 'show_post_count' => $count));
            foreach ($turkish_months as $en => $tr) {
                $archives_html = str_replace($en, $tr, $archives_html);
            }
            echo $archives_html;
            echo '</ul>';
        }
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Arşiv', 'mi-theme');
        $count = !empty($instance['count']) ? 1 : 0;
        $dropdown = !empty($instance['dropdown']) ? 1 : 0;
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Başlık:', 'mi-theme'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($count); ?> 
                   id="<?php echo esc_attr($this->get_field_id('count')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('count')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('count')); ?>"><?php _e('Yazı sayısını göster', 'mi-theme'); ?></label>
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($dropdown); ?> 
                   id="<?php echo esc_attr($this->get_field_id('dropdown')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('dropdown')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('dropdown')); ?>"><?php _e('Dropdown olarak göster', 'mi-theme'); ?></label>
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['count'] = !empty($new_instance['count']) ? 1 : 0;
        $instance['dropdown'] = !empty($new_instance['dropdown']) ? 1 : 0;
        return $instance;
    }
}

// Recent Comments Widget
class MI_Recent_Comments_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'mi_recent_comments',
            __('MI Son Yorumlar', 'mi-theme'),
            array('description' => __('Son yorumları gösterir', 'mi-theme'))
        );
    }
    
    public function widget($args, $instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Son Yorumlar', 'mi-theme');
        $number = !empty($instance['number']) ? absint($instance['number']) : 5;
        
        echo $args['before_widget'];
        echo $args['before_title'] . esc_html($title) . $args['after_title'];
        
        $comments = get_comments(array(
            'number' => $number,
            'status' => 'approve',
            'post_status' => 'publish',
        ));
        
        if ($comments) {
            echo '<ul class="recent-comments-list">';
            foreach ($comments as $comment) {
                ?>
                <li class="recent-comment-item">
                    <div class="comment-author-avatar">
                        <?php echo get_avatar($comment, 40); ?>
                    </div>
                    <div class="comment-content">
                        <span class="comment-author"><?php echo esc_html($comment->comment_author); ?></span>
                        <span class="comment-text"><?php echo wp_trim_words($comment->comment_content, 15); ?></span>
                        <a href="<?php echo esc_url(get_comment_link($comment)); ?>" class="comment-link">
                            <?php echo esc_html(get_the_title($comment->comment_post_ID)); ?>
                        </a>
                    </div>
                </li>
                <?php
            }
            echo '</ul>';
        }
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Son Yorumlar', 'mi-theme');
        $number = !empty($instance['number']) ? absint($instance['number']) : 5;
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Başlık:', 'mi-theme'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('number')); ?>"><?php _e('Yorum Sayısı:', 'mi-theme'); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr($this->get_field_id('number')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('number')); ?>" 
                   type="number" step="1" min="1" value="<?php echo esc_attr($number); ?>">
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['number'] = (!empty($new_instance['number'])) ? absint($new_instance['number']) : 5;
        return $instance;
    }
}

// Register all widgets
function mi_register_additional_widgets() {
    register_widget('MI_Recent_Posts_Widget');
    register_widget('MI_Categories_Widget');
    register_widget('MI_Tags_Widget');
    register_widget('MI_Archive_Widget');
    register_widget('MI_Recent_Comments_Widget');
}
add_action('widgets_init', 'mi_register_additional_widgets');

