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
 * Enqueue admin styles and scripts
 */
function hdh_enqueue_tasks_admin_assets($hook) {
    if ($hook !== 'toplevel_page_hdh-tasks' && $hook !== 'gorevler_page_hdh-xp-settings') {
        return;
    }
    
    wp_enqueue_style('hdh-tasks-admin', get_template_directory_uri() . '/assets/css/admin-tasks.css', array(), '1.0.0');
    wp_enqueue_script('hdh-tasks-admin', get_template_directory_uri() . '/assets/js/admin-tasks.js', array('jquery'), '1.0.0', true);
}
add_action('admin_enqueue_scripts', 'hdh_enqueue_tasks_admin_assets');

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
        settings_errors('hdh_tasks');
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
    <div class="wrap hdh-tasks-admin">
        <h1>📋 Görev Yönetimi</h1>
        <p class="description">Buradan görevleri ekleyebilir, düzenleyebilir ve silebilirsiniz. Görev türünü (Tek Seferlik/Günlük), ödülleri ve diğer ayarları belirleyebilirsiniz.</p>
        
        <!-- Migration Section -->
        <div class="hdh-migration-section" style="background: #fff; border: 1px solid #ccd0d4; border-left: 4px solid #2271b1; padding: 15px; margin: 20px 0;">
            <h2 style="margin-top: 0;">🔄 Veri Migrasyonu</h2>
            <p>Eski user_meta verilerini yeni veritabanı tablolarına taşıyın. Bu işlem sadece bir kez çalıştırılmalıdır.</p>
            <?php
            // Handle migration request
            if (isset($_POST['hdh_run_migration']) && check_admin_referer('hdh_run_migration')) {
                $dry_run = isset($_POST['hdh_migration_dry_run']) && $_POST['hdh_migration_dry_run'] === '1';
                $results = hdh_migrate_task_data_to_tables($dry_run);
                
                if ($dry_run) {
                    echo '<div class="notice notice-info"><p><strong>Test Modu:</strong> ' . $results['users_processed'] . ' kullanıcı işlendi, ' . $results['tasks_migrated'] . ' görev migrate edilecek.</p></div>';
                } else {
                    echo '<div class="notice notice-success"><p><strong>Migrasyon Tamamlandı:</strong> ' . $results['users_processed'] . ' kullanıcı işlendi, ' . $results['tasks_migrated'] . ' görev migrate edildi.</p></div>';
                }
            }
            ?>
            <form method="post" action="" style="margin-top: 15px;">
                <?php wp_nonce_field('hdh_run_migration'); ?>
                <label>
                    <input type="checkbox" name="hdh_migration_dry_run" value="1" checked>
                    Test modu (değişiklik yapmadan simüle et)
                </label>
                <br><br>
                <button type="submit" name="hdh_run_migration" class="button button-secondary" onclick="return confirm('Migrasyonu çalıştırmak istediğinizden emin misiniz?');">
                    Migrasyonu Çalıştır
                </button>
            </form>
        </div>
        
        <form method="post" action="" id="hdh-tasks-form">
            <?php wp_nonce_field('hdh_save_tasks'); ?>
            
            <!-- Tabs for task types -->
            <div class="hdh-tasks-tabs">
                <button type="button" class="hdh-tab-button active" data-tab="one-time">
                    <span class="dashicons dashicons-yes-alt"></span> Tek Seferlik Görevler
                    <span class="task-count">(<?php echo count($one_time_tasks); ?>)</span>
                </button>
                <button type="button" class="hdh-tab-button" data-tab="daily">
                    <span class="dashicons dashicons-update"></span> Günlük Görevler
                    <span class="task-count">(<?php echo count($daily_tasks); ?>)</span>
                </button>
            </div>
            
            <!-- One-Time Tasks Tab -->
            <div class="hdh-tab-content active" id="tab-one-time">
                <div class="hdh-tasks-header">
                    <h2>Tek Seferlik Görevler</h2>
                    <p class="description">Kullanıcılar bu görevleri sadece bir kez tamamlayabilir.</p>
                    <button type="button" class="button button-primary" id="add-one-time-task">
                        <span class="dashicons dashicons-plus-alt"></span> Yeni Tek Seferlik Görev Ekle
                    </button>
                </div>
                
                <div id="one-time-tasks-list" class="hdh-tasks-list">
                    <?php if (!empty($one_time_tasks)) : ?>
                        <?php foreach ($one_time_tasks as $task_id => $task) : ?>
                            <div class="hdh-task-item" data-task-id="<?php echo esc_attr($task_id); ?>">
                                <div class="hdh-task-header">
                                    <span class="hdh-task-number">#<?php echo esc_html($loop_index = array_search($task_id, array_keys($one_time_tasks)) + 1); ?></span>
                                    <h3 class="hdh-task-title-preview"><?php echo esc_html($task['title']); ?></h3>
                                    <button type="button" class="button button-link hdh-toggle-task" aria-label="Görevi Genişlet/Daralt">
                                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                                    </button>
                                </div>
                                
                                <div class="hdh-task-content">
                                    <div class="hdh-task-fields">
                                        <div class="hdh-field-group">
                                            <label>
                                                <strong>Görev ID <span class="required">*</span></strong>
                                                <input type="text" name="one_time_tasks[<?php echo esc_attr($task_id); ?>][id]" 
                                                       value="<?php echo esc_attr($task['id']); ?>" 
                                                       required 
                                                       class="regular-text"
                                                       placeholder="ornek_gorev_id" />
                                                <span class="description">Benzersiz görev kimliği (örn: verify_email, create_first_listing)</span>
                                            </label>
                                        </div>
                                        
                                        <div class="hdh-field-group">
                                            <label>
                                                <strong>Görev Başlığı <span class="required">*</span></strong>
                                                <input type="text" name="one_time_tasks[<?php echo esc_attr($task_id); ?>][title]" 
                                                       value="<?php echo esc_attr($task['title']); ?>" 
                                                       required 
                                                       class="large-text"
                                                       placeholder="Örn: Mail Adresini Doğrula" />
                                            </label>
                                        </div>
                                        
                                        <div class="hdh-field-group">
                                            <label>
                                                <strong>Açıklama</strong>
                                                <textarea name="one_time_tasks[<?php echo esc_attr($task_id); ?>][description]" 
                                                          rows="3" 
                                                          class="large-text"
                                                          placeholder="Görevin açıklaması"><?php echo esc_textarea($task['description']); ?></textarea>
                                            </label>
                                        </div>
                                        
                                        <div class="hdh-rewards-row">
                                            <div class="hdh-field-group hdh-reward-field">
                                                <label>
                                                    <strong>🎟️ Bilet Ödülü <span class="required">*</span></strong>
                                                    <input type="number" name="one_time_tasks[<?php echo esc_attr($task_id); ?>][reward_bilet]" 
                                                           value="<?php echo esc_attr($task['reward_bilet']); ?>" 
                                                           min="0" 
                                                           max="999"
                                                           required 
                                                           class="small-text" />
                                                    <span class="description">Tamamlandığında verilecek bilet miktarı</span>
                                                </label>
                                            </div>
                                            
                                            <div class="hdh-field-group hdh-reward-field">
                                                <label>
                                                    <strong>Seviye Ödülü <span class="required">*</span></strong>
                                                    <input type="number" name="one_time_tasks[<?php echo esc_attr($task_id); ?>][reward_level]" 
                                                           value="<?php echo esc_attr($task['reward_level']); ?>" 
                                                           min="0" 
                                                           max="10"
                                                           required 
                                                           class="small-text" />
                                                    <span class="description">Tamamlandığında verilecek seviye miktarı (1 seviye = <?php echo esc_html(hdh_get_xp_per_level()); ?> XP)</span>
                                                </label>
                                            </div>
                                            
                                            <div class="hdh-field-group hdh-reward-field">
                                                <label>
                                                    <strong>Maksimum İlerleme <span class="required">*</span></strong>
                                                    <input type="number" name="one_time_tasks[<?php echo esc_attr($task_id); ?>][max_progress]" 
                                                           value="<?php echo esc_attr($task['max_progress']); ?>" 
                                                           min="1" 
                                                           max="999"
                                                           required 
                                                           class="small-text" />
                                                    <span class="description">Görevin tamamlanması için gereken ilerleme (genellikle 1)</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="hdh-task-actions">
                                        <button type="button" class="button button-secondary hdh-remove-task" data-task-type="one_time" data-task-id="<?php echo esc_attr($task_id); ?>">
                                            <span class="dashicons dashicons-trash"></span> Görevi Sil
                                        </button>
                                        <button type="button" class="button button-link hdh-move-task-up" data-task-type="one_time" data-task-id="<?php echo esc_attr($task_id); ?>">
                                            <span class="dashicons dashicons-arrow-up-alt"></span> Yukarı Taşı
                                        </button>
                                        <button type="button" class="button button-link hdh-move-task-down" data-task-type="one_time" data-task-id="<?php echo esc_attr($task_id); ?>">
                                            <span class="dashicons dashicons-arrow-down-alt"></span> Aşağı Taşı
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="hdh-empty-state">
                            <p>Henüz tek seferlik görev eklenmemiş.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Daily Tasks Tab -->
            <div class="hdh-tab-content" id="tab-daily">
                <div class="hdh-tasks-header">
                    <h2>Günlük Görevler</h2>
                    <p class="description">Kullanıcılar bu görevleri her gün tamamlayabilir. Her gün sıfırlanır.</p>
                    <button type="button" class="button button-primary" id="add-daily-task">
                        <span class="dashicons dashicons-plus-alt"></span> Yeni Günlük Görev Ekle
                    </button>
                </div>
                
                <div id="daily-tasks-list" class="hdh-tasks-list">
                    <?php if (!empty($daily_tasks)) : ?>
                        <?php foreach ($daily_tasks as $task_id => $task) : ?>
                            <div class="hdh-task-item" data-task-id="<?php echo esc_attr($task_id); ?>">
                                <div class="hdh-task-header">
                                    <span class="hdh-task-number">#<?php echo esc_html($loop_index = array_search($task_id, array_keys($daily_tasks)) + 1); ?></span>
                                    <h3 class="hdh-task-title-preview"><?php echo esc_html($task['title']); ?></h3>
                                    <button type="button" class="button button-link hdh-toggle-task" aria-label="Görevi Genişlet/Daralt">
                                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                                    </button>
                                </div>
                                
                                <div class="hdh-task-content">
                                    <div class="hdh-task-fields">
                                        <div class="hdh-field-group">
                                            <label>
                                                <strong>Görev ID <span class="required">*</span></strong>
                                                <input type="text" name="daily_tasks[<?php echo esc_attr($task_id); ?>][id]" 
                                                       value="<?php echo esc_attr($task['id']); ?>" 
                                                       required 
                                                       class="regular-text"
                                                       placeholder="ornek_gorev_id" />
                                                <span class="description">Benzersiz görev kimliği (örn: create_listings, complete_exchanges)</span>
                                            </label>
                                        </div>
                                        
                                        <div class="hdh-field-group">
                                            <label>
                                                <strong>Görev Başlığı <span class="required">*</span></strong>
                                                <input type="text" name="daily_tasks[<?php echo esc_attr($task_id); ?>][title]" 
                                                       value="<?php echo esc_attr($task['title']); ?>" 
                                                       required 
                                                       class="large-text"
                                                       placeholder="Örn: İlan Oluştur" />
                                            </label>
                                        </div>
                                        
                                        <div class="hdh-field-group">
                                            <label>
                                                <strong>Açıklama</strong>
                                                <textarea name="daily_tasks[<?php echo esc_attr($task_id); ?>][description]" 
                                                          rows="3" 
                                                          class="large-text"
                                                          placeholder="Görevin açıklaması"><?php echo esc_textarea($task['description']); ?></textarea>
                                            </label>
                                        </div>
                                        
                                        <div class="hdh-rewards-row">
                                            <div class="hdh-field-group hdh-reward-field">
                                                <label>
                                                    <strong>🎟️ Bilet Ödülü <span class="required">*</span></strong>
                                                    <input type="number" name="daily_tasks[<?php echo esc_attr($task_id); ?>][reward_bilet]" 
                                                           value="<?php echo esc_attr($task['reward_bilet']); ?>" 
                                                           min="0" 
                                                           max="999"
                                                           required 
                                                           class="small-text" />
                                                    <span class="description">Her milestone için verilecek bilet miktarı</span>
                                                </label>
                                            </div>
                                            
                                            <div class="hdh-field-group hdh-reward-field">
                                                <label>
                                                    <strong>Seviye Ödülü <span class="required">*</span></strong>
                                                    <input type="number" name="daily_tasks[<?php echo esc_attr($task_id); ?>][reward_level]" 
                                                           value="<?php echo esc_attr($task['reward_level']); ?>" 
                                                           min="0" 
                                                           max="10"
                                                           required 
                                                           class="small-text" />
                                                    <span class="description">Her milestone için verilecek seviye miktarı (1 seviye = <?php echo esc_html(hdh_get_xp_per_level()); ?> XP)</span>
                                                </label>
                                            </div>
                                            
                                            <div class="hdh-field-group hdh-reward-field">
                                                <label>
                                                    <strong>Maksimum İlerleme <span class="required">*</span></strong>
                                                    <input type="number" name="daily_tasks[<?php echo esc_attr($task_id); ?>][max_progress]" 
                                                           value="<?php echo esc_attr($task['max_progress']); ?>" 
                                                           min="1" 
                                                           max="999"
                                                           required 
                                                           class="small-text" />
                                                    <span class="description">Günlük maksimum ilerleme (örn: 3 ilan, 5 hediyeleşme)</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="hdh-task-actions">
                                        <button type="button" class="button button-secondary hdh-remove-task" data-task-type="daily" data-task-id="<?php echo esc_attr($task_id); ?>">
                                            <span class="dashicons dashicons-trash"></span> Görevi Sil
                                        </button>
                                        <button type="button" class="button button-link hdh-move-task-up" data-task-type="daily" data-task-id="<?php echo esc_attr($task_id); ?>">
                                            <span class="dashicons dashicons-arrow-up-alt"></span> Yukarı Taşı
                                        </button>
                                        <button type="button" class="button button-link hdh-move-task-down" data-task-type="daily" data-task-id="<?php echo esc_attr($task_id); ?>">
                                            <span class="dashicons dashicons-arrow-down-alt"></span> Aşağı Taşı
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="hdh-empty-state">
                            <p>Henüz günlük görev eklenmemiş.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="hdh-tasks-footer">
                <p class="submit">
                    <input type="submit" name="hdh_save_tasks" class="button button-primary button-large" value="💾 Tüm Görevleri Kaydet" />
                    <span class="description" style="margin-left: 15px;">Değişiklikleriniz kaydedildikten sonra kullanıcılar yeni görevleri görebilecek.</span>
                </p>
            </div>
        </form>
    </div>
    
    <script type="text/template" id="hdh-one-time-task-template">
        <div class="hdh-task-item" data-task-id="{{taskId}}">
            <div class="hdh-task-header">
                <span class="hdh-task-number">#{{taskNumber}}</span>
                <h3 class="hdh-task-title-preview">Yeni Görev</h3>
                <button type="button" class="button button-link hdh-toggle-task" aria-label="Görevi Genişlet/Daralt">
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </button>
            </div>
            <div class="hdh-task-content">
                <div class="hdh-task-fields">
                    <div class="hdh-field-group">
                        <label>
                            <strong>Görev ID <span class="required">*</span></strong>
                            <input type="text" name="one_time_tasks[{{taskId}}][id]" value="{{taskId}}" required class="regular-text" placeholder="ornek_gorev_id" />
                            <span class="description">Benzersiz görev kimliği (örn: verify_email, create_first_listing)</span>
                        </label>
                    </div>
                    <div class="hdh-field-group">
                        <label>
                            <strong>Görev Başlığı <span class="required">*</span></strong>
                            <input type="text" name="one_time_tasks[{{taskId}}][title]" value="" required class="large-text" placeholder="Örn: Mail Adresini Doğrula" />
                        </label>
                    </div>
                    <div class="hdh-field-group">
                        <label>
                            <strong>Açıklama</strong>
                            <textarea name="one_time_tasks[{{taskId}}][description]" rows="3" class="large-text" placeholder="Görevin açıklaması"></textarea>
                        </label>
                    </div>
                    <div class="hdh-rewards-row">
                        <div class="hdh-field-group hdh-reward-field">
                            <label>
                                <strong>🎟️ Bilet Ödülü <span class="required">*</span></strong>
                                <input type="number" name="one_time_tasks[{{taskId}}][reward_bilet]" value="1" min="0" max="999" required class="small-text" />
                                <span class="description">Tamamlandığında verilecek bilet miktarı</span>
                            </label>
                        </div>
                        <div class="hdh-field-group hdh-reward-field">
                            <label>
                                <strong>Seviye Ödülü <span class="required">*</span></strong>
                                <input type="number" name="one_time_tasks[{{taskId}}][reward_level]" value="1" min="0" max="10" required class="small-text" />
                                <span class="description">Tamamlandığında verilecek seviye miktarı</span>
                            </label>
                        </div>
                        <div class="hdh-field-group hdh-reward-field">
                            <label>
                                <strong>Maksimum İlerleme <span class="required">*</span></strong>
                                <input type="number" name="one_time_tasks[{{taskId}}][max_progress]" value="1" min="1" max="999" required class="small-text" />
                                <span class="description">Görevin tamamlanması için gereken ilerleme (genellikle 1)</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="hdh-task-actions">
                    <button type="button" class="button button-secondary hdh-remove-task" data-task-type="one_time" data-task-id="{{taskId}}">
                        <span class="dashicons dashicons-trash"></span> Görevi Sil
                    </button>
                </div>
            </div>
        </div>
    </script>
    
    <script type="text/template" id="hdh-daily-task-template">
        <div class="hdh-task-item" data-task-id="{{taskId}}">
            <div class="hdh-task-header">
                <span class="hdh-task-number">#{{taskNumber}}</span>
                <h3 class="hdh-task-title-preview">Yeni Görev</h3>
                <button type="button" class="button button-link hdh-toggle-task" aria-label="Görevi Genişlet/Daralt">
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </button>
            </div>
            <div class="hdh-task-content">
                <div class="hdh-task-fields">
                    <div class="hdh-field-group">
                        <label>
                            <strong>Görev ID <span class="required">*</span></strong>
                            <input type="text" name="daily_tasks[{{taskId}}][id]" value="{{taskId}}" required class="regular-text" placeholder="ornek_gorev_id" />
                            <span class="description">Benzersiz görev kimliği (örn: create_listings, complete_exchanges)</span>
                        </label>
                    </div>
                    <div class="hdh-field-group">
                        <label>
                            <strong>Görev Başlığı <span class="required">*</span></strong>
                            <input type="text" name="daily_tasks[{{taskId}}][title]" value="" required class="large-text" placeholder="Örn: İlan Oluştur" />
                        </label>
                    </div>
                    <div class="hdh-field-group">
                        <label>
                            <strong>Açıklama</strong>
                            <textarea name="daily_tasks[{{taskId}}][description]" rows="3" class="large-text" placeholder="Görevin açıklaması"></textarea>
                        </label>
                    </div>
                    <div class="hdh-rewards-row">
                        <div class="hdh-field-group hdh-reward-field">
                            <label>
                                <strong>🎟️ Bilet Ödülü <span class="required">*</span></strong>
                                <input type="number" name="daily_tasks[{{taskId}}][reward_bilet]" value="1" min="0" max="999" required class="small-text" />
                                <span class="description">Her milestone için verilecek bilet miktarı</span>
                            </label>
                        </div>
                        <div class="hdh-field-group hdh-reward-field">
                            <label>
                                <strong>Seviye Ödülü <span class="required">*</span></strong>
                                <input type="number" name="daily_tasks[{{taskId}}][reward_level]" value="0" min="0" max="10" required class="small-text" />
                                <span class="description">Her milestone için verilecek seviye miktarı</span>
                            </label>
                        </div>
                        <div class="hdh-field-group hdh-reward-field">
                            <label>
                                <strong>Maksimum İlerleme <span class="required">*</span></strong>
                                <input type="number" name="daily_tasks[{{taskId}}][max_progress]" value="3" min="1" max="999" required class="small-text" />
                                <span class="description">Günlük maksimum ilerleme (örn: 3 ilan, 5 hediyeleşme)</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="hdh-task-actions">
                    <button type="button" class="button button-secondary hdh-remove-task" data-task-type="daily" data-task-id="{{taskId}}">
                        <span class="dashicons dashicons-trash"></span> Görevi Sil
                    </button>
                </div>
            </div>
        </div>
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
        if (empty($task['id']) || empty($task['title'])) {
            continue; // Skip invalid tasks
        }
        
        $task_id_clean = sanitize_key($task['id']);
        $sanitized_one_time[$task_id_clean] = array(
            'id' => $task_id_clean,
            'title' => sanitize_text_field($task['title']),
            'description' => sanitize_textarea_field($task['description'] ?? ''),
            'reward_bilet' => absint($task['reward_bilet'] ?? 0),
            'reward_level' => absint($task['reward_level'] ?? 0),
            'max_progress' => absint($task['max_progress'] ?? 1),
        );
    }
    
    $sanitized_daily = array();
    foreach ($daily_tasks as $task_id => $task) {
        if (empty($task['id']) || empty($task['title'])) {
            continue; // Skip invalid tasks
        }
        
        $task_id_clean = sanitize_key($task['id']);
        $sanitized_daily[$task_id_clean] = array(
            'id' => $task_id_clean,
            'title' => sanitize_text_field($task['title']),
            'description' => sanitize_textarea_field($task['description'] ?? ''),
            'reward_bilet' => absint($task['reward_bilet'] ?? 0),
            'reward_level' => absint($task['reward_level'] ?? 0),
            'max_progress' => absint($task['max_progress'] ?? 1),
        );
    }
    
    update_option('hdh_one_time_tasks', $sanitized_one_time);
    update_option('hdh_daily_tasks', $sanitized_daily);
    
    add_settings_error('hdh_tasks', 'tasks_saved', 'Görevler başarıyla kaydedildi! (' . count($sanitized_one_time) . ' tek seferlik, ' . count($sanitized_daily) . ' günlük)', 'updated');
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
    
    settings_errors('hdh_xp_settings');
    
    $current_xp_per_level = hdh_get_xp_per_level();
    
    ?>
    <div class="wrap">
        <h1>⚙️ XP Ayarları</h1>
        <p class="description">Seviye başına gereken XP miktarını ayarlayabilirsiniz. Bu değer tüm seviyeler için sabittir (linear sistem).</p>
        
        <form method="post" action="">
            <?php wp_nonce_field('hdh_save_xp_settings'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="hdh_xp_per_level">Seviye Başına XP</label>
                    </th>
                    <td>
                        <input type="number" id="hdh_xp_per_level" name="hdh_xp_per_level" value="<?php echo esc_attr($current_xp_per_level); ?>" min="1" max="1000" required style="width: 100px;" />
                        <span style="margin-left: 10px; font-size: 18px;">XP = 1 Seviye</span>
                        <p class="description" style="margin-top: 10px;">
                            Her seviye için gereken XP miktarı. Örnek: 100 XP = 1 seviye, 200 XP = 2 seviye, 1000 XP = 10 seviye.
                            <br><strong>Mevcut değer:</strong> <?php echo esc_html($current_xp_per_level); ?> XP = 1 seviye
                        </p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="hdh_save_xp_settings" class="button button-primary button-large" value="💾 Ayarları Kaydet" />
            </p>
        </form>
        
        <div style="margin-top: 30px; padding: 20px; background: #f0f6fc; border-left: 4px solid #2271b1; border-radius: 4px;">
            <h3>📊 Mevcut XP Sistemi</h3>
            <p><strong>Formül:</strong> <code>Level = floor(XP / <?php echo esc_html($current_xp_per_level); ?>) + 1</code></p>
            <p><strong>Örnekler:</strong></p>
            <ul style="list-style: disc; margin-left: 20px;">
                <li>0-<?php echo esc_html($current_xp_per_level - 1); ?> XP = Level 1</li>
                <li><?php echo esc_html($current_xp_per_level); ?>-<?php echo esc_html($current_xp_per_level * 2 - 1); ?> XP = Level 2</li>
                <li><?php echo esc_html($current_xp_per_level * 2); ?>-<?php echo esc_html($current_xp_per_level * 3 - 1); ?> XP = Level 3</li>
                <li><?php echo esc_html($current_xp_per_level * 9); ?>-<?php echo esc_html($current_xp_per_level * 10 - 1); ?> XP = Level 10</li>
            </ul>
            <p style="margin-top: 15px;"><strong>Not:</strong> Bu değer değiştirildiğinde, mevcut kullanıcıların seviyeleri yeni formüle göre yeniden hesaplanır.</p>
        </div>
    </div>
    <?php
}
