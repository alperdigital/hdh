<?php
/**
 * HDH: Admin Task Management
 * Allows admins to manage tasks via WordPress admin panel
 */

if (!defined('ABSPATH')) exit;

/**
 * Add admin menu for task management
 */
function hdh_add_tasks_admin_menu() {
    add_menu_page(
        'Görev Yönetimi',
        'Görevler',
        'manage_options',
        'hdh-tasks',
        'hdh_render_tasks_admin_page',
        'dashicons-list-view',
        30
    );
    
    add_submenu_page(
        'hdh-tasks',
        'XP Ayarları',
        'XP Ayarları',
        'manage_options',
        'hdh-xp-settings',
        'hdh_render_xp_settings_page'
    );
}
add_action('admin_menu', 'hdh_add_tasks_admin_menu');

/**
 * Render tasks admin page
 */
function hdh_render_tasks_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Bu sayfaya erişim yetkiniz yok.');
    }
    
    // Handle form submission
    if (isset($_POST['hdh_save_tasks']) && check_admin_referer('hdh_save_tasks')) {
        hdh_save_tasks_from_admin();
    }
    
    // Get current tasks
    $one_time_tasks = get_option('hdh_one_time_tasks', array());
    $daily_tasks = get_option('hdh_daily_tasks', array());
    
    // If empty, load from hardcoded config (migration)
    if (empty($one_time_tasks) && function_exists('hdh_get_one_time_tasks_config')) {
        $one_time_tasks = hdh_get_one_time_tasks_config();
        update_option('hdh_one_time_tasks', $one_time_tasks);
    }
    if (empty($daily_tasks) && function_exists('hdh_get_daily_tasks_config')) {
        $daily_tasks = hdh_get_daily_tasks_config();
        update_option('hdh_daily_tasks', $daily_tasks);
    }
    
    ?>
    <div class="wrap">
        <h1>Görev Yönetimi</h1>
        <p>Buradan görevleri ekleyebilir, düzenleyebilir ve silebilirsiniz.</p>
        
        <form method="post" action="">
            <?php wp_nonce_field('hdh_save_tasks'); ?>
            
            <h2>Tek Seferlik Görevler</h2>
            <div id="one-time-tasks-list">
                <?php if (!empty($one_time_tasks)) : ?>
                    <?php foreach ($one_time_tasks as $task_id => $task) : ?>
                        <div class="task-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; background: #fff;">
                            <table class="form-table">
                                <tr>
                                    <th>Görev ID</th>
                                    <td><input type="text" name="one_time_tasks[<?php echo esc_attr($task_id); ?>][id]" value="<?php echo esc_attr($task['id']); ?>" required /></td>
                                </tr>
                                <tr>
                                    <th>Başlık</th>
                                    <td><input type="text" name="one_time_tasks[<?php echo esc_attr($task_id); ?>][title]" value="<?php echo esc_attr($task['title']); ?>" required style="width: 100%;" /></td>
                                </tr>
                                <tr>
                                    <th>Açıklama</th>
                                    <td><textarea name="one_time_tasks[<?php echo esc_attr($task_id); ?>][description]" style="width: 100%;" rows="2"><?php echo esc_textarea($task['description']); ?></textarea></td>
                                </tr>
                                <tr>
                                    <th>Bilet Ödülü</th>
                                    <td><input type="number" name="one_time_tasks[<?php echo esc_attr($task_id); ?>][reward_bilet]" value="<?php echo esc_attr($task['reward_bilet']); ?>" min="0" required /></td>
                                </tr>
                                <tr>
                                    <th>Seviye Ödülü</th>
                                    <td><input type="number" name="one_time_tasks[<?php echo esc_attr($task_id); ?>][reward_level]" value="<?php echo esc_attr($task['reward_level']); ?>" min="0" required /></td>
                                </tr>
                                <tr>
                                    <th>Maksimum İlerleme</th>
                                    <td><input type="number" name="one_time_tasks[<?php echo esc_attr($task_id); ?>][max_progress]" value="<?php echo esc_attr($task['max_progress']); ?>" min="1" required /></td>
                                </tr>
                            </table>
                            <button type="button" class="button button-secondary remove-task" data-task-type="one_time" data-task-id="<?php echo esc_attr($task_id); ?>">Görevi Sil</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" class="button" id="add-one-time-task">+ Tek Seferlik Görev Ekle</button>
            
            <h2 style="margin-top: 30px;">Günlük Görevler</h2>
            <div id="daily-tasks-list">
                <?php if (!empty($daily_tasks)) : ?>
                    <?php foreach ($daily_tasks as $task_id => $task) : ?>
                        <div class="task-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; background: #fff;">
                            <table class="form-table">
                                <tr>
                                    <th>Görev ID</th>
                                    <td><input type="text" name="daily_tasks[<?php echo esc_attr($task_id); ?>][id]" value="<?php echo esc_attr($task['id']); ?>" required /></td>
                                </tr>
                                <tr>
                                    <th>Başlık</th>
                                    <td><input type="text" name="daily_tasks[<?php echo esc_attr($task_id); ?>][title]" value="<?php echo esc_attr($task['title']); ?>" required style="width: 100%;" /></td>
                                </tr>
                                <tr>
                                    <th>Açıklama</th>
                                    <td><textarea name="daily_tasks[<?php echo esc_attr($task_id); ?>][description]" style="width: 100%;" rows="2"><?php echo esc_textarea($task['description']); ?></textarea></td>
                                </tr>
                                <tr>
                                    <th>Bilet Ödülü</th>
                                    <td><input type="number" name="daily_tasks[<?php echo esc_attr($task_id); ?>][reward_bilet]" value="<?php echo esc_attr($task['reward_bilet']); ?>" min="0" required /></td>
                                </tr>
                                <tr>
                                    <th>Seviye Ödülü</th>
                                    <td><input type="number" name="daily_tasks[<?php echo esc_attr($task_id); ?>][reward_level]" value="<?php echo esc_attr($task['reward_level']); ?>" min="0" required /></td>
                                </tr>
                                <tr>
                                    <th>Maksimum İlerleme</th>
                                    <td><input type="number" name="daily_tasks[<?php echo esc_attr($task_id); ?>][max_progress]" value="<?php echo esc_attr($task['max_progress']); ?>" min="1" required /></td>
                                </tr>
                            </table>
                            <button type="button" class="button button-secondary remove-task" data-task-type="daily" data-task-id="<?php echo esc_attr($task_id); ?>">Görevi Sil</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" class="button" id="add-daily-task">+ Günlük Görev Ekle</button>
            
            <p class="submit">
                <input type="submit" name="hdh_save_tasks" class="button button-primary" value="Görevleri Kaydet" />
            </p>
        </form>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        var oneTimeTaskIndex = <?php echo count($one_time_tasks); ?>;
        var dailyTaskIndex = <?php echo count($daily_tasks); ?>;
        
        $('#add-one-time-task').on('click', function() {
            var taskId = 'new_task_' + oneTimeTaskIndex;
            var html = '<div class="task-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; background: #fff;">' +
                '<table class="form-table">' +
                '<tr><th>Görev ID</th><td><input type="text" name="one_time_tasks[' + taskId + '][id]" value="' + taskId + '" required /></td></tr>' +
                '<tr><th>Başlık</th><td><input type="text" name="one_time_tasks[' + taskId + '][title]" value="" required style="width: 100%;" /></td></tr>' +
                '<tr><th>Açıklama</th><td><textarea name="one_time_tasks[' + taskId + '][description]" style="width: 100%;" rows="2"></textarea></td></tr>' +
                '<tr><th>Bilet Ödülü</th><td><input type="number" name="one_time_tasks[' + taskId + '][reward_bilet]" value="1" min="0" required /></td></tr>' +
                '<tr><th>Seviye Ödülü</th><td><input type="number" name="one_time_tasks[' + taskId + '][reward_level]" value="1" min="0" required /></td></tr>' +
                '<tr><th>Maksimum İlerleme</th><td><input type="number" name="one_time_tasks[' + taskId + '][max_progress]" value="1" min="1" required /></td></tr>' +
                '</table>' +
                '<button type="button" class="button button-secondary remove-task">Görevi Sil</button>' +
                '</div>';
            $('#one-time-tasks-list').append(html);
            oneTimeTaskIndex++;
        });
        
        $('#add-daily-task').on('click', function() {
            var taskId = 'new_task_' + dailyTaskIndex;
            var html = '<div class="task-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; background: #fff;">' +
                '<table class="form-table">' +
                '<tr><th>Görev ID</th><td><input type="text" name="daily_tasks[' + taskId + '][id]" value="' + taskId + '" required /></td></tr>' +
                '<tr><th>Başlık</th><td><input type="text" name="daily_tasks[' + taskId + '][title]" value="" required style="width: 100%;" /></td></tr>' +
                '<tr><th>Açıklama</th><td><textarea name="daily_tasks[' + taskId + '][description]" style="width: 100%;" rows="2"></textarea></td></tr>' +
                '<tr><th>Bilet Ödülü</th><td><input type="number" name="daily_tasks[' + taskId + '][reward_bilet]" value="1" min="0" required /></td></tr>' +
                '<tr><th>Seviye Ödülü</th><td><input type="number" name="daily_tasks[' + taskId + '][reward_level]" value="0" min="0" required /></td></tr>' +
                '<tr><th>Maksimum İlerleme</th><td><input type="number" name="daily_tasks[' + taskId + '][max_progress]" value="3" min="1" required /></td></tr>' +
                '</table>' +
                '<button type="button" class="button button-secondary remove-task">Görevi Sil</button>' +
                '</div>';
            $('#daily-tasks-list').append(html);
            dailyTaskIndex++;
        });
        
        $(document).on('click', '.remove-task', function() {
            if (confirm('Bu görevi silmek istediğinize emin misiniz?')) {
                $(this).closest('.task-item').remove();
            }
        });
    });
    </script>
    <?php
}

/**
 * Save tasks from admin form
 */
function hdh_save_tasks_from_admin() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $one_time_tasks = isset($_POST['one_time_tasks']) ? $_POST['one_time_tasks'] : array();
    $daily_tasks = isset($_POST['daily_tasks']) ? $_POST['daily_tasks'] : array();
    
    // Sanitize and validate
    $sanitized_one_time = array();
    foreach ($one_time_tasks as $task_id => $task) {
        $sanitized_one_time[sanitize_key($task['id'])] = array(
            'id' => sanitize_key($task['id']),
            'title' => sanitize_text_field($task['title']),
            'description' => sanitize_textarea_field($task['description']),
            'reward_bilet' => absint($task['reward_bilet']),
            'reward_level' => absint($task['reward_level']),
            'max_progress' => absint($task['max_progress']),
        );
    }
    
    $sanitized_daily = array();
    foreach ($daily_tasks as $task_id => $task) {
        $sanitized_daily[sanitize_key($task['id'])] = array(
            'id' => sanitize_key($task['id']),
            'title' => sanitize_text_field($task['title']),
            'description' => sanitize_textarea_field($task['description']),
            'reward_bilet' => absint($task['reward_bilet']),
            'reward_level' => absint($task['reward_level']),
            'max_progress' => absint($task['max_progress']),
        );
    }
    
    update_option('hdh_one_time_tasks', $sanitized_one_time);
    update_option('hdh_daily_tasks', $sanitized_daily);
    
    add_settings_error('hdh_tasks', 'tasks_saved', 'Görevler başarıyla kaydedildi!', 'updated');
}

/**
 * Render XP settings page
 */
function hdh_render_xp_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Bu sayfaya erişim yetkiniz yok.');
    }
    
    // Handle form submission
    if (isset($_POST['hdh_save_xp_settings']) && check_admin_referer('hdh_save_xp_settings')) {
        $xp_per_level = absint($_POST['hdh_xp_per_level']);
        if ($xp_per_level >= 1) {
            update_option('hdh_xp_per_level', $xp_per_level);
            add_settings_error('hdh_xp_settings', 'xp_saved', 'XP ayarları başarıyla kaydedildi!', 'updated');
        }
    }
    
    $current_xp_per_level = hdh_get_xp_per_level();
    
    ?>
    <div class="wrap">
        <h1>XP Ayarları</h1>
        <p>Seviye başına gereken XP miktarını ayarlayabilirsiniz.</p>
        
        <form method="post" action="">
            <?php wp_nonce_field('hdh_save_xp_settings'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="hdh_xp_per_level">Seviye Başına XP</label>
                    </th>
                    <td>
                        <input type="number" id="hdh_xp_per_level" name="hdh_xp_per_level" value="<?php echo esc_attr($current_xp_per_level); ?>" min="1" required />
                        <p class="description">
                            Her seviye için gereken XP miktarı. Örnek: 100 XP = 1 seviye, 200 XP = 2 seviye, 1000 XP = 10 seviye.
                            <br><strong>Mevcut değer:</strong> <?php echo esc_html($current_xp_per_level); ?> XP = 1 seviye
                        </p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="hdh_save_xp_settings" class="button button-primary" value="Ayarları Kaydet" />
            </p>
        </form>
        
        <div style="margin-top: 30px; padding: 15px; background: #f0f0f0; border-left: 4px solid #2271b1;">
            <h3>Mevcut XP Sistemi</h3>
            <p><strong>Formül:</strong> Level = floor(XP / <?php echo esc_html($current_xp_per_level); ?>) + 1</p>
            <p><strong>Örnekler:</strong></p>
            <ul>
                <li>0-<?php echo esc_html($current_xp_per_level - 1); ?> XP = Level 1</li>
                <li><?php echo esc_html($current_xp_per_level); ?>-<?php echo esc_html($current_xp_per_level * 2 - 1); ?> XP = Level 2</li>
                <li><?php echo esc_html($current_xp_per_level * 9); ?>-<?php echo esc_html($current_xp_per_level * 10 - 1); ?> XP = Level 10</li>
            </ul>
        </div>
    </div>
    <?php
}

