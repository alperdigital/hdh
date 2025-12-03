<?php
/**
 * Template: Açıklama (Sadece İçerik) - Başyazı Bölümü
 * Yazının iki kere geçmemesi için sadece içeriği gösterir
 * İmza atma özelliği sadece bu bölüm için geçerlidir
 */

// Post ID'yi doğru şekilde al - global $post objesinden
global $post;
$post_id = isset($post) && isset($post->ID) ? $post->ID : get_the_ID();

// Sadece Başyazı (aciklama) bölümü için imza özelliğini göster
$section_type = get_post_meta($post_id, '_mi_section_type', true);
$is_aciklama = ($section_type === 'aciklama');

// İmza sayısı ve durumu (sadece Başyazı için)
$signature_count = $is_aciklama ? intval(get_post_meta($post_id, '_mi_aciklama_signatures', true)) : 0;
$user_signed = $is_aciklama && isset($_COOKIE['mi_signed_' . $post_id]) && $_COOKIE['mi_signed_' . $post_id] === '1';
?>

<div class="aciklama-section">
    <div class="container">
        <div class="aciklama-content-wrapper">
            <?php 
            // Sadece içeriği göster, excerpt gösterme
            // the_content() kullanarak otomatik excerpt oluşturulmasını engelle
            $content = get_the_content();
            // Excerpt'i temizle
            remove_filter('the_content', 'wpautop');
            $content = apply_filters('the_content', $content);
            add_filter('the_content', 'wpautop');
            echo $content;
            ?>
            
            <?php if ($is_aciklama) : ?>
            <!-- İmza At Butonu - Sadece Başyazı Bölümü için -->
            <div class="aciklama-signature-inline">
                <button type="button" 
                        class="signature-btn <?php echo $user_signed ? 'signed' : ''; ?>" 
                        data-post-id="<?php echo esc_attr($post_id); ?>"
                        <?php echo $user_signed ? 'disabled' : ''; ?>>
                    <span class="signature-icon">✍️</span>
                    <span class="signature-text"><?php echo $user_signed ? 'İmzanız Atıldı' : 'İmza At'; ?></span>
                    <span class="signature-count">(<span class="count-number"><?php echo number_format_i18n($signature_count); ?></span>)</span>
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('.signature-btn').on('click', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var postId = $btn.data('post-id');
        
        if ($btn.hasClass('signed') || $btn.prop('disabled')) {
            return;
        }
        
        // AJAX request
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'mi_add_signature',
                post_id: postId,
                nonce: '<?php echo wp_create_nonce('mi_signature_nonce'); ?>'
            },
            beforeSend: function() {
                $btn.prop('disabled', true);
                $btn.addClass('loading');
            },
            success: function(response) {
                if (response.success) {
                    $btn.addClass('signed');
                    $btn.find('.signature-text').text('İmzanız Atıldı');
                    $btn.find('.count-number').text(response.data.count);
                    
                    // Cookie set et - sayfa her açıldığında 1 kere tıklanabilir
                    // Session cookie kullan (tarayıcı kapanınca silinir, sayfa yenilendiğinde tekrar tıklanabilir)
                    // Aynı sayfa açıkken tekrar tıklanamaz
                    document.cookie = 'mi_signed_' + postId + '=1; path=/';
                } else {
                    // Hata durumunda sessizce butonu geri aktif et (mesaj gösterme)
                    $btn.prop('disabled', false);
                    $btn.removeClass('loading');
                }
            },
            error: function() {
                // Hata durumunda sessizce butonu geri aktif et (mesaj gösterme)
                $btn.prop('disabled', false);
                $btn.removeClass('loading');
            }
        });
    });
});
</script>

