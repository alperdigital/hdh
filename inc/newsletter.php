<?php
/**
 * Newsletter Subscription Form
 */

// Newsletter widget
class MI_Newsletter_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'mi_newsletter',
            __('MI E-posta Abonelik', 'mi-theme'),
            array('description' => __('E-posta abonelik formu', 'mi-theme'))
        );
    }
    
    public function widget($args, $instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('E-posta Aboneliği', 'mi-theme');
        $description = !empty($instance['description']) ? $instance['description'] : '';
        
        echo $args['before_widget'];
        echo $args['before_title'] . esc_html($title) . $args['after_title'];
        
        if ($description) {
            echo '<p class="newsletter-description">' . esc_html($description) . '</p>';
        }
        ?>
        <form class="newsletter-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('mi_newsletter_subscribe', 'mi_newsletter_nonce'); ?>
            <input type="hidden" name="action" value="mi_newsletter_subscribe">
            
            <div class="newsletter-input-group">
                <input type="email" name="newsletter_email" placeholder="<?php esc_attr_e('E-posta adresiniz', 'mi-theme'); ?>" required class="newsletter-email">
                <button type="submit" class="newsletter-submit"><?php _e('Abone Ol', 'mi-theme'); ?></button>
            </div>
            
            <p class="newsletter-privacy">
                <label>
                    <input type="checkbox" name="newsletter_privacy" required>
                    <?php _e('Gizlilik politikasını kabul ediyorum', 'mi-theme'); ?>
                </label>
            </p>
        </form>
        <?php
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('E-posta Aboneliği', 'mi-theme');
        $description = !empty($instance['description']) ? $instance['description'] : '';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Başlık:', 'mi-theme'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('description')); ?>"><?php _e('Açıklama:', 'mi-theme'); ?></label>
            <textarea class="widefat" id="<?php echo esc_attr($this->get_field_id('description')); ?>" 
                      name="<?php echo esc_attr($this->get_field_name('description')); ?>" 
                      rows="3"><?php echo esc_textarea($description); ?></textarea>
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['description'] = (!empty($new_instance['description'])) ? strip_tags($new_instance['description']) : '';
        return $instance;
    }
}

function mi_register_newsletter_widget() {
    register_widget('MI_Newsletter_Widget');
}
add_action('widgets_init', 'mi_register_newsletter_widget');

// Handle newsletter subscription
function mi_newsletter_subscribe() {
    if (!isset($_POST['mi_newsletter_nonce']) || !wp_verify_nonce($_POST['mi_newsletter_nonce'], 'mi_newsletter_subscribe')) {
        wp_die('Güvenlik kontrolü başarısız.');
    }
    
    $email = sanitize_email($_POST['newsletter_email']);
    
    if (!is_email($email)) {
        wp_redirect(add_query_arg('newsletter', 'invalid', wp_get_referer()));
        exit;
    }
    
    // Save to options (in production, use a proper email service)
    $subscribers = get_option('mi_newsletter_subscribers', array());
    if (!in_array($email, $subscribers)) {
        $subscribers[] = $email;
        update_option('mi_newsletter_subscribers', $subscribers);
        
        // Send confirmation email
        $subject = __('E-posta Aboneliği Onayı', 'mi-theme');
        $message = __('E-posta aboneliğiniz başarıyla kaydedildi. Teşekkürler!', 'mi-theme');
        wp_mail($email, $subject, $message);
        
        wp_redirect(add_query_arg('newsletter', 'success', wp_get_referer()));
    } else {
        wp_redirect(add_query_arg('newsletter', 'exists', wp_get_referer()));
    }
    exit;
}
add_action('admin_post_mi_newsletter_subscribe', 'mi_newsletter_subscribe');
add_action('admin_post_nopriv_mi_newsletter_subscribe', 'mi_newsletter_subscribe');

