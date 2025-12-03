<?php
/**
 * Comments Template Functions
 */

// Custom comment form
function mi_comment_form_defaults($defaults) {
    $defaults['title_reply'] = __('Yorum Yap', 'mi-theme');
    $defaults['label_submit'] = __('Yorumu Gönder', 'mi-theme');
    $defaults['comment_notes_before'] = '<p class="comment-notes">' . __('E-posta adresiniz yayınlanmayacaktır. Gerekli alanlar * ile işaretlenmiştir.', 'mi-theme') . '</p>';
    $defaults['comment_field'] = '<p class="comment-form-comment"><label for="comment">' . __('Yorumunuz', 'mi-theme') . ' <span class="required">*</span></label><textarea id="comment" name="comment" cols="45" rows="8" required></textarea></p>';
    return $defaults;
}
add_filter('comment_form_defaults', 'mi_comment_form_defaults');

// Custom comment form fields
function mi_comment_form_fields($fields) {
    $commenter = wp_get_current_commenter();
    $req = get_option('require_name_email');
    $aria_req = ($req ? " aria-required='true'" : '');
    
    $fields['author'] = '<p class="comment-form-author"><label for="author">' . __('Ad Soyad', 'mi-theme') . ($req ? ' <span class="required">*</span>' : '') . '</label><input id="author" name="author" type="text" value="' . esc_attr($commenter['comment_author']) . '" size="30"' . $aria_req . ' /></p>';
    
    $fields['email'] = '<p class="comment-form-email"><label for="email">' . __('E-posta', 'mi-theme') . ($req ? ' <span class="required">*</span>' : '') . '</label><input id="email" name="email" type="email" value="' . esc_attr($commenter['comment_author_email']) . '" size="30"' . $aria_req . ' /></p>';
    
    $fields['url'] = '<p class="comment-form-url"><label for="url">' . __('Web Sitesi', 'mi-theme') . '</label><input id="url" name="url" type="url" value="' . esc_attr($commenter['comment_author_url']) . '" size="30" /></p>';
    
    return $fields;
}
add_filter('comment_form_default_fields', 'mi_comment_form_fields');

// Custom comment callback
function mi_comment_callback($comment, $args, $depth) {
    $GLOBALS['comment'] = $comment;
    ?>
    <li <?php comment_class(); ?> id="comment-<?php comment_ID(); ?>">
        <article class="comment-body">
            <div class="comment-author-avatar">
                <?php echo get_avatar($comment, 60); ?>
            </div>
            <div class="comment-content">
                <div class="comment-meta">
                    <span class="comment-author"><?php comment_author_link(); ?></span>
                    <span class="comment-date">
                        <time datetime="<?php comment_time('c'); ?>">
                            <?php printf(__('%1$s at %2$s', 'mi-theme'), get_comment_date(), get_comment_time()); ?>
                        </time>
                    </span>
                    <?php if ($comment->comment_approved == '0') : ?>
                        <span class="comment-awaiting-moderation"><?php _e('Yorumunuz onay bekliyor.', 'mi-theme'); ?></span>
                    <?php endif; ?>
                </div>
                <div class="comment-text">
                    <?php comment_text(); ?>
                </div>
                <div class="comment-actions">
                    <?php
                    comment_reply_link(array_merge($args, array(
                        'depth' => $depth,
                        'max_depth' => $args['max_depth'],
                        'reply_text' => __('Yanıtla', 'mi-theme')
                    )));
                    edit_comment_link(__('Düzenle', 'mi-theme'), ' | ');
                    ?>
                </div>
            </div>
        </article>
    <?php
}

