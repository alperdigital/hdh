<?php
/**
 * HDH: Terms Page Settings
 * Admin settings for terms page content
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add terms page settings to admin menu
 */
function hdh_add_terms_settings_menu() {
    add_submenu_page(
        'options-general.php',
        'Üyelik Sözleşmesi',
        'Üyelik Sözleşmesi',
        'manage_options',
        'hdh-terms-settings',
        'hdh_terms_settings_page'
    );
}
add_action('admin_menu', 'hdh_add_terms_settings_menu');

/**
 * Render terms settings page
 */
function hdh_terms_settings_page() {
    if (isset($_POST['hdh_terms_settings_submit'])) {
        check_admin_referer('hdh_terms_settings');
        
        $terms_content = isset($_POST['hdh_terms_content']) ? wp_kses_post($_POST['hdh_terms_content']) : '';
        $terms_page_id = isset($_POST['hdh_terms_page_id']) ? absint($_POST['hdh_terms_page_id']) : 0;
        
        update_option('hdh_terms_content', $terms_content);
        update_option('hdh_terms_page_id', $terms_page_id);
        
        echo '<div class="notice notice-success"><p>Ayarlar kaydedildi!</p></div>';
    }
    
    $terms_content = get_option('hdh_terms_content', '');
    $terms_page_id = get_option('hdh_terms_page_id', 0);
    ?>
    <div class="wrap">
        <h1>📋 Üyelik Sözleşmesi Ayarları</h1>
        <form method="post" action="">
            <?php wp_nonce_field('hdh_terms_settings'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="hdh_terms_page_id">Üyelik Sözleşmesi Sayfası</label>
                    </th>
                    <td>
                        <?php
                        wp_dropdown_pages(array(
                            'name' => 'hdh_terms_page_id',
                            'selected' => $terms_page_id,
                            'show_option_none' => 'Sayfa Seçin',
                            'option_none_value' => 0
                        ));
                        ?>
                        <p class="description">
                            Üyelik sözleşmesi sayfasını seçin. Eğer sayfa seçilmezse, aşağıdaki içerik kullanılır.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="hdh_terms_content">Üyelik Sözleşmesi İçeriği</label>
                    </th>
                    <td>
                        <?php
                        wp_editor($terms_content, 'hdh_terms_content', array(
                            'textarea_name' => 'hdh_terms_content',
                            'textarea_rows' => 20,
                            'media_buttons' => false,
                        ));
                        ?>
                        <p class="description">
                            Üyelik sözleşmesi sayfası seçilmediğinde bu içerik gösterilir. HTML kullanabilirsiniz.
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Ayarları Kaydet', 'primary', 'hdh_terms_settings_submit'); ?>
        </form>
    </div>
    <?php
}

