<?php
/**
 * Template: İletişim
 */

// Post ID'yi doğru şekilde al - global $post objesinden
global $post;
$post_id = isset($post) && isset($post->ID) ? $post->ID : get_the_ID();

$is_front_page = is_front_page();
$contact_email = get_post_meta($post_id, '_mi_iletisim_email', true) ?: 'memleketisterimcom@gmail.com';
$response_time = get_post_meta($post_id, '_mi_iletisim_response_time', true) ?: '24-48 Saat';
$intro_title = get_post_meta($post_id, '_mi_iletisim_intro_title', true) ?: 'Yazılarınızı Paylaşın';
$intro_text = get_post_meta($post_id, '_mi_iletisim_intro_text', true) ?: 'Görüşlerinizi, önerilerinizi ve yazılarınızı bizimle paylaşın. Değerli katkılarınız yayınlanabilir ve toplumla paylaşılabilir.';

// Alt bölüm görünürlük ayarları
$show_info = get_post_meta($post_id, '_mi_iletisim_show_info', true) !== '0'; // Default: göster
$show_rules = get_post_meta($post_id, '_mi_iletisim_show_rules', true) === '1'; // Default: gizle
$show_quick = get_post_meta($post_id, '_mi_iletisim_show_quick', true) !== '0'; // Default: göster

// İletişim Bilgileri içerik ayarları
$info_title = get_post_meta($post_id, '_mi_iletisim_info_title', true) ?: 'İletişim Bilgileri';
$info_email_text = get_post_meta($post_id, '_mi_iletisim_info_email_text', true) ?: 'Yazılarınızı ve görüşlerinizi e-posta ile gönderebilirsiniz.';
$info_response_text = get_post_meta($post_id, '_mi_iletisim_info_response_text', true) ?: 'Mesajlarınıza en kısa sürede yanıt vermeye çalışıyoruz.';
$info_evaluation_text = get_post_meta($post_id, '_mi_iletisim_info_evaluation_text', true) ?: 'Gönderdiğiniz yazılar değerlendirilerek yayınlanabilir.';

// Yazı Gönderme Kuralları içerik ayarları
$rules_title = get_post_meta($post_id, '_mi_iletisim_rules_title', true) ?: 'Yazı Gönderme Kuralları';
$rules_content = get_post_meta($post_id, '_mi_iletisim_rules_content', true) ?: '✅ Orijinal İçerik: Gönderdiğiniz yazıların orijinal ve özgün olması gerekmektedir. Başka kaynaklardan alıntı yapıyorsanız kaynak belirtiniz.

📝 Uygun Dil: Yazılarınızda saygılı ve uygun bir dil kullanınız. Nefret söylemi, ayrımcılık veya saldırgan içerik içeren yazılar yayınlanmayacaktır.

📏 Uzunluk: Yazılarınızın en az 200 kelime olması önerilir. Ancak daha kısa veya uzun yazılar da değerlendirilebilir.

🔍 Değerlendirme: Gönderilen tüm yazılar editörlerimiz tarafından değerlendirilir. Yayınlanma kararı editörlerimize aittir.';

// Hızlı İletişim içerik ayarları
$quick_title = get_post_meta($post_id, '_mi_iletisim_quick_title', true) ?: 'Hızlı İletişim';
$quick_text = get_post_meta($post_id, '_mi_iletisim_quick_text', true) ?: 'Doğrudan e-posta göndermek isterseniz:';
$quick_button_text = get_post_meta($post_id, '_mi_iletisim_quick_button_text', true) ?: 'E-posta Gönder';
?>

<div class="iletisim-section <?php echo $is_front_page ? 'front-page-iletisim' : ''; ?>">
    <div class="container">
        <div class="iletisim-content">
            <?php if ($show_info) : ?>
            <div class="iletisim-info">
                <?php /* "İletişim Bilgileri" başlığı kaldırıldı */ ?>
                
                <div class="info-item">
                    <div class="info-icon">📧</div>
                    <div class="info-content">
                        <h3>E-posta</h3>
                        <a href="mailto:<?php echo esc_attr($contact_email); ?>"><?php echo esc_html($contact_email); ?></a>
                        <p><?php echo esc_html($info_email_text); ?></p>
                    </div>
                </div>
                
                <?php /* Yanıt Süresi ve Yazı Değerlendirme kaldırıldı */ ?>
            </div>
            <?php endif; ?>
            
            <?php if ($show_rules) : ?>
            <div class="iletisim-rules">
                <h2><?php echo esc_html($rules_title); ?></h2>
                
                <?php
                // Kuralları parse et ve göster
                $rules_lines = explode("\n", $rules_content);
                foreach ($rules_lines as $rule_line) {
                    $rule_line = trim($rule_line);
                    if (empty($rule_line)) continue;
                    
                    // İkon ve başlık/içerik ayır
                    if (preg_match('/^([^\s]+)\s+(.+)$/', $rule_line, $matches)) {
                        $icon = $matches[1];
                        $content = $matches[2];
                        
                        // Başlık ve açıklama ayır
                        if (preg_match('/^(.+?):\s*(.+)$/', $content, $content_matches)) {
                            $rule_title = trim($content_matches[1]);
                            $rule_desc = trim($content_matches[2]);
                        } else {
                            $rule_title = '';
                            $rule_desc = $content;
                        }
                        ?>
                        <div class="rule-item">
                            <div class="rule-icon"><?php echo esc_html($icon); ?></div>
                            <div class="rule-content">
                                <?php if ($rule_title) : ?>
                                    <h3><?php echo esc_html($rule_title); ?></h3>
                                <?php endif; ?>
                                <p><?php echo esc_html($rule_desc); ?></p>
                            </div>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
            <?php endif; ?>
            
            <?php /* Hızlı İletişim bölümü tamamen kaldırıldı */ ?>
        </div>
    </div>
</div>
