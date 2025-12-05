<?php
/**
 * Comments Template
 */

if (post_password_required()) {
    return;
}
?>

<div id="comments" class="comments-area">
    <?php if (have_comments()) : ?>
        <h3 class="comments-title">
            <?php
            // HDH: Custom title for trade offers
            if (get_post_type() === 'hayday_trade') {
                $comments_number = get_comments_number();
                if ($comments_number == 1) {
                    echo '1 Teklif / Yorum';
                } else {
                    printf('%s Teklif / Yorum', number_format_i18n($comments_number));
                }
            } else {
                $comments_number = get_comments_number();
                if ($comments_number == 1) {
                    echo __('1 Yorum', 'hdh');
                } else {
                    printf(__('%s Yorum', 'hdh'), number_format_i18n($comments_number));
                }
            }
            ?>
        </h3>
        
        <ol class="comment-list">
            <?php
            wp_list_comments(array(
                'style' => 'ol',
                'short_ping' => true,
                'callback' => 'mi_comment_callback',
            ));
            ?>
        </ol>
        
        <?php
        the_comments_pagination(array(
            'prev_text' => __('&laquo; Önceki Yorumlar', 'mi-theme'),
            'next_text' => __('Sonraki Yorumlar &raquo;', 'mi-theme'),
        ));
        ?>
    <?php endif; ?>
    
    <?php if (!comments_open() && get_comments_number() && post_type_supports(get_post_type(), 'comments')) : ?>
        <p class="no-comments"><?php _e('Yorumlar kapatılmıştır.', 'mi-theme'); ?></p>
    <?php endif; ?>
    
    <?php comment_form(); ?>
</div>

