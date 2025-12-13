<?php
/**
 * Template Name: Profil
 */
get_header();
if (!is_user_logged_in()) {
    add_action('wp_footer', 'hdh_render_registration_modal', 999);
    add_action('wp_enqueue_scripts', 'hdh_enqueue_registration_modal_styles', 999);
    ?>
    <main class="profile-page-main"><div class="container"><div class="profile-logged-out">
        <h1 class="profile-title">Profil</h1>
        <p class="profile-subtitle">Hediyeleşmeye başlamak için giriş yapın veya üye olun</p>
        <button class="btn-open-login" onclick="document.getElementById('hdh-registration-modal').style.display='block';">Giriş Yap / Üye Ol</button>
    </div></div></main>
    <?php
} else {
    $user_id = get_current_user_id();
    $user = wp_get_current_user();
    $farm_name = $user->display_name;
    $hayday_username = get_user_meta($user_id, 'hayday_username', true);
    $jeton_balance = function_exists('hdh_get_user_jeton_balance') ? hdh_get_user_jeton_balance($user_id) : 0;
    $completed_exchanges = function_exists('hdh_get_completed_gift_count') ? hdh_get_completed_gift_count($user_id) : 0;
    ?>
    <main class="profile-page-main"><div class="container"><div class="profile-logged-in">
        <h1 class="profile-title">Profil</h1>
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar"><span class="avatar-icon">👤</span></div>
                <div class="profile-name-section">
                    <h2 class="profile-farm-name"><?php echo esc_html($farm_name); ?></h2>
                    <?php if ($hayday_username) : ?><p class="profile-hayday-username">@<?php echo esc_html($hayday_username); ?></p><?php endif; ?>
                </div>
            </div>
            <div class="profile-trust-section">
                <div class="trust-star-large"><?php echo $completed_exchanges > 0 ? '★' . esc_html($completed_exchanges) : '★'; ?></div>
                <p class="trust-explanation"><?php echo $completed_exchanges > 0 ? esc_html($completed_exchanges) . ' başarılı hediyeleşme' : 'Henüz hediyeleşme yapmamış'; ?></p>
            </div>
            <div class="profile-jeton-section"><div class="jeton-display">
                <span class="jeton-icon">🪙</span>
                <span class="jeton-balance"><?php echo esc_html(number_format_i18n($jeton_balance)); ?></span>
                <span class="jeton-label">Hediye Jetonu</span>
            </div></div>
            <div class="profile-actions"><button class="btn-edit-profile" id="btn-edit-profile">Profili Düzenle</button></div>
        </div>
        <div class="profile-edit-form" id="profile-edit-form" style="display: none;">
            <h3 class="edit-form-title">Profili Düzenle</h3>
            <form id="profile-edit-form-element" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('hdh_update_profile', 'hdh_profile_nonce'); ?>
                <input type="hidden" name="action" value="hdh_update_profile">
                <div class="form-field"><label for="farm_name">Çiftlik Adı:</label><input type="text" id="farm_name" name="farm_name" value="<?php echo esc_attr($farm_name); ?>" required class="form-input"></div>
                <div class="form-field"><label for="hayday_username">Hay Day Kullanıcı Adı:</label><input type="text" id="hayday_username" name="hayday_username" value="<?php echo esc_attr($hayday_username); ?>" placeholder="Örn: HayDayPlayer123" class="form-input"></div>
                <div class="form-actions">
                    <button type="submit" class="btn-save-profile">Kaydet</button>
                    <button type="button" class="btn-cancel-edit" id="btn-cancel-edit">İptal</button>
                </div>
            </form>
        </div>
        <?php if (function_exists('hdh_render_tasks_panel')) hdh_render_tasks_panel($user_id); ?>
    </div></div></main>
    <?php
}
get_footer();
?>
