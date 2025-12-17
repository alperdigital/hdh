<?php
/**
 * HDH: Premium Admin Panel
 * Refactored admin experience with improved IA, progressive disclosure, and safety features
 */

if (!defined('ABSPATH')) exit;

// Include registry, history, and handlers
require_once get_template_directory() . '/inc/admin-registry.php';
require_once get_template_directory() . '/inc/admin-history.php';
require_once get_template_directory() . '/inc/admin-panel-handlers.php';

/**
 * Add new admin menu structure
 */
function hdh_add_premium_admin_menu() {
    // Main Dashboard
    add_menu_page(
        'HDH Dashboard',
        'HDH',
        'manage_options',
        'hdh-dashboard',
        'hdh_render_dashboard_page',
        'dashicons-admin-generic',
        2
    );
    
    // Dashboard (submenu to match main)
    add_submenu_page(
        'hdh-dashboard',
        'Dashboard',
        'Dashboard',
        'manage_options',
        'hdh-dashboard',
        'hdh_render_dashboard_page'
    );
    
    // Pre-Login Experience
    add_submenu_page(
        'hdh-dashboard',
        'Pre-Login Experience',
        'Pre-Login',
        'manage_options',
        'hdh-pre-login',
        'hdh_render_experience_page'
    );
    
    // Post-Login Experience
    add_submenu_page(
        'hdh-dashboard',
        'Post-Login Experience',
        'Post-Login',
        'manage_options',
        'hdh-post-login',
        'hdh_render_experience_page'
    );
    
    // Global Design
    add_submenu_page(
        'hdh-dashboard',
        'Global Design',
        'Global Design',
        'manage_options',
        'hdh-global-design',
        'hdh_render_global_design_page'
    );
    
    // Content Library
    add_submenu_page(
        'hdh-dashboard',
        'Content Library',
        'Content',
        'manage_options',
        'hdh-content-library',
        'hdh_render_content_library_page'
    );
    
    // Components & Presets
    add_submenu_page(
        'hdh-dashboard',
        'Components & Presets',
        'Components',
        'manage_options',
        'hdh-components',
        'hdh_render_components_page'
    );
    
    // Advanced
    add_submenu_page(
        'hdh-dashboard',
        'Advanced Settings',
        'Advanced',
        'manage_options',
        'hdh-advanced',
        'hdh_render_advanced_page'
    );
    
    // Logs & History
    add_submenu_page(
        'hdh-dashboard',
        'Logs & History',
        'Logs',
        'manage_options',
        'hdh-logs',
        'hdh_render_logs_page'
    );
}
add_action('admin_menu', 'hdh_add_premium_admin_menu', 5); // Priority 5 to run before old menus

/**
 * Enqueue admin assets
 */
function hdh_enqueue_premium_admin_assets($hook) {
    // Only load on our pages
    if (strpos($hook, 'hdh-') === false) {
        return;
    }
    
    wp_enqueue_style(
        'hdh-premium-admin',
        get_template_directory_uri() . '/assets/css/admin-premium.css',
        array(),
        '1.0.0'
    );
    
    wp_enqueue_script(
        'hdh-premium-admin',
        get_template_directory_uri() . '/assets/js/admin-premium.js',
        array('jquery'),
        '1.0.0',
        true
    );
    
    // Localize script
    wp_localize_script('hdh-premium-admin', 'hdhAdmin', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('hdh_premium_admin'),
        'registry' => HDH_Settings_Registry::get_all(),
    ));
}
add_action('admin_enqueue_scripts', 'hdh_enqueue_premium_admin_assets');

/**
 * Render Dashboard Page
 */
function hdh_render_dashboard_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Bu sayfaya erişim yetkiniz yok.');
    }
    
    // Get recent changes
    $recent_changes = hdh_get_recent_changes(10);
    
    // Get pinned sections
    $pinned = hdh_get_pinned_sections();
    
    // Get quick stats
    $stats = hdh_get_admin_stats();
    
    ?>
    <div class="wrap hdh-admin-dashboard">
        <div class="hdh-admin-header">
            <h1>HDH Dashboard</h1>
            <div class="hdh-admin-search">
                <input type="search" id="hdh-global-search" placeholder="Search settings..." class="hdh-search-input" />
                <span class="hdh-search-icon dashicons dashicons-search"></span>
            </div>
        </div>
        
        <div class="hdh-dashboard-grid">
            <!-- Quick Actions -->
            <div class="hdh-dashboard-card hdh-quick-actions">
                <h2>Quick Actions</h2>
                <div class="hdh-quick-actions-grid">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=hdh-pre-login&group=landing')); ?>" class="hdh-quick-action">
                        <span class="dashicons dashicons-admin-home"></span>
                        <span>Edit Landing Page</span>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=hdh-pre-login&group=authentication')); ?>" class="hdh-quick-action">
                        <span class="dashicons dashicons-lock"></span>
                        <span>Edit Login/Register</span>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=hdh-post-login&group=listings')); ?>" class="hdh-quick-action">
                        <span class="dashicons dashicons-list-view"></span>
                        <span>Edit Listings</span>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=hdh-components')); ?>" class="hdh-quick-action">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <span>Manage Items</span>
                    </a>
                </div>
            </div>
            
            <!-- Pinned Sections -->
            <?php if (!empty($pinned)) : ?>
            <div class="hdh-dashboard-card hdh-pinned-sections">
                <h2>Pinned Sections</h2>
                <ul class="hdh-pinned-list">
                    <?php foreach ($pinned as $pin) : ?>
                    <li>
                        <a href="<?php echo esc_url($pin['url']); ?>">
                            <?php echo esc_html($pin['label']); ?>
                        </a>
                        <button class="hdh-unpin" data-section="<?php echo esc_attr($pin['key']); ?>">
                            <span class="dashicons dashicons-star-filled"></span>
                        </button>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <!-- Recent Changes -->
            <div class="hdh-dashboard-card hdh-recent-changes">
                <h2>Recent Changes</h2>
                <?php if (!empty($recent_changes)) : ?>
                <ul class="hdh-recent-list">
                    <?php foreach ($recent_changes as $change) : ?>
                    <li>
                        <span class="hdh-change-time"><?php echo esc_html(human_time_diff($change['timestamp'], current_time('timestamp'))); ?> ago</span>
                        <span class="hdh-change-desc"><?php echo esc_html($change['description']); ?></span>
                        <?php if (!empty($change['rollback_id'])) : ?>
                        <button class="hdh-rollback-btn" data-rollback-id="<?php echo esc_attr($change['rollback_id']); ?>">
                            Rollback
                        </button>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else : ?>
                <p class="hdh-empty-state">No recent changes</p>
                <?php endif; ?>
            </div>
            
            <!-- Stats -->
            <div class="hdh-dashboard-card hdh-stats">
                <h2>Overview</h2>
                <div class="hdh-stats-grid">
                    <div class="hdh-stat">
                        <span class="hdh-stat-value"><?php echo esc_html($stats['total_settings']); ?></span>
                        <span class="hdh-stat-label">Total Settings</span>
                    </div>
                    <div class="hdh-stat">
                        <span class="hdh-stat-value"><?php echo esc_html($stats['modified_settings']); ?></span>
                        <span class="hdh-stat-label">Modified</span>
                    </div>
                    <div class="hdh-stat">
                        <span class="hdh-stat-value"><?php echo esc_html($stats['draft_changes']); ?></span>
                        <span class="hdh-stat-label">Draft Changes</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render Experience Page (Pre/Post Login)
 */
function hdh_render_experience_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Bu sayfaya erişim yetkiniz yok.');
    }
    
    $current_page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
    $section = ($current_page === 'hdh-pre-login') ? 'pre_login' : 'post_login';
    $current_group = isset($_GET['group']) ? sanitize_key($_GET['group']) : '';
    
    // Get groups for this section
    $groups = hdh_get_experience_groups($section);
    
    // If no group selected, show group selector
    if (empty($current_group) && !empty($groups)) {
        $current_group = array_key_first($groups);
    }
    
    // Get settings for current group
    $settings = HDH_Settings_Registry::get_by_section($section, $current_group);
    $quick_settings = HDH_Settings_Registry::get_by_visibility('quick', $section, $current_group);
    $advanced_settings = HDH_Settings_Registry::get_by_visibility('advanced', $section, $current_group);
    
    ?>
    <div class="wrap hdh-admin-experience">
        <div class="hdh-admin-header">
            <h1><?php echo $section === 'pre_login' ? 'Pre-Login Experience' : 'Post-Login Experience'; ?></h1>
        </div>
        
        <!-- Group Navigation -->
        <div class="hdh-group-nav">
            <?php foreach ($groups as $group_key => $group_info) : ?>
            <a href="<?php echo esc_url(add_query_arg('group', $group_key)); ?>" 
               class="hdh-group-tab <?php echo $current_group === $group_key ? 'active' : ''; ?>">
                <span class="hdh-group-icon"><?php echo esc_html($group_info['icon']); ?></span>
                <span class="hdh-group-title"><?php echo esc_html($group_info['title']); ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Settings Form -->
        <form method="post" action="" id="hdh-experience-form" class="hdh-settings-form">
            <?php wp_nonce_field('hdh_save_experience'); ?>
            <input type="hidden" name="section" value="<?php echo esc_attr($section); ?>" />
            <input type="hidden" name="group" value="<?php echo esc_attr($current_group); ?>" />
            
            <!-- Quick Settings -->
            <div class="hdh-settings-section hdh-quick-settings">
                <div class="hdh-section-header">
                    <h2>Quick Edits</h2>
                    <p class="description">Most commonly changed settings</p>
                </div>
                <div class="hdh-settings-grid">
                    <?php foreach ($quick_settings as $full_key => $config) : ?>
                    <?php hdh_render_setting_field($full_key, $config); ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Advanced Settings -->
            <div class="hdh-settings-section hdh-advanced-settings">
                <div class="hdh-section-header">
                    <button type="button" class="hdh-toggle-advanced">
                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                        Advanced Settings
                    </button>
                </div>
                <div class="hdh-advanced-content" style="display: none;">
                    <div class="hdh-settings-grid">
                        <?php foreach ($advanced_settings as $full_key => $config) : ?>
                        <?php hdh_render_setting_field($full_key, $config); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="hdh-form-actions">
                <button type="button" class="button hdh-preview-btn">Preview</button>
                <button type="button" class="button hdh-save-draft-btn">Save Draft</button>
                <button type="submit" class="button button-primary hdh-publish-btn">Publish Changes</button>
            </div>
        </form>
    </div>
    <?php
}

/**
 * Render setting field
 */
function hdh_render_setting_field($full_key, $config) {
    $value = hdh_get_setting_value($config);
    $field_id = 'hdh_setting_' . str_replace('.', '_', $full_key);
    
    ?>
    <div class="hdh-setting-field" data-setting-key="<?php echo esc_attr($full_key); ?>">
        <label for="<?php echo esc_attr($field_id); ?>" class="hdh-setting-label">
            <?php echo esc_html($config['label']); ?>
            <?php if (!empty($config['location'])) : ?>
            <span class="hdh-setting-location">(<?php echo esc_html($config['location']); ?>)</span>
            <?php endif; ?>
        </label>
        <?php if (!empty($config['description'])) : ?>
        <p class="description"><?php echo esc_html($config['description']); ?></p>
        <?php endif; ?>
        
        <?php
        switch ($config['type']) {
            case 'textarea':
                ?>
                <textarea 
                    id="<?php echo esc_attr($field_id); ?>"
                    name="settings[<?php echo esc_attr($full_key); ?>]"
                    class="hdh-setting-input"
                    rows="3"
                ><?php echo esc_textarea($value); ?></textarea>
                <?php
                break;
            case 'number':
                ?>
                <input 
                    type="number"
                    id="<?php echo esc_attr($field_id); ?>"
                    name="settings[<?php echo esc_attr($full_key); ?>]"
                    class="hdh-setting-input"
                    value="<?php echo esc_attr($value); ?>"
                    <?php if (isset($config['validation']['min'])) : ?>min="<?php echo esc_attr($config['validation']['min']); ?>"<?php endif; ?>
                    <?php if (isset($config['validation']['max'])) : ?>max="<?php echo esc_attr($config['validation']['max']); ?>"<?php endif; ?>
                />
                <?php
                break;
            default:
                ?>
                <input 
                    type="text"
                    id="<?php echo esc_attr($field_id); ?>"
                    name="settings[<?php echo esc_attr($full_key); ?>]"
                    class="hdh-setting-input"
                    value="<?php echo esc_attr($value); ?>"
                />
                <?php
        }
        ?>
    </div>
    <?php
}

/**
 * Get setting value
 */
function hdh_get_setting_value($config) {
    $storage_key = !empty($config['storage_key']) ? $config['storage_key'] : 'hdh_setting_' . $config['full_key'];
    
    if ($config['storage'] === 'option') {
        return get_option($storage_key, $config['default']);
    }
    
    return $config['default'];
}

/**
 * Get experience groups
 */
function hdh_get_experience_groups($section) {
    $groups = array();
    
    if (!class_exists('HDH_Settings_Registry')) {
        return $groups;
    }
    
    $all_settings = HDH_Settings_Registry::get_by_section($section);
    
    foreach ($all_settings as $full_key => $config) {
        $group = $config['group'];
        if (!isset($groups[$group])) {
            $groups[$group] = array(
                'title' => ucwords(str_replace('_', ' ', $group)),
                'icon' => '📄',
            );
        }
    }
    
    // Add specific icons
    $icons = array(
        'landing' => '🏠',
        'authentication' => '🔐',
        'listings' => '📋',
        'profile' => '👤',
    );
    
    foreach ($groups as $key => &$group) {
        if (isset($icons[$key])) {
            $group['icon'] = $icons[$key];
        }
    }
    
    return $groups;
}

/**
 * Get recent changes
 */
function hdh_get_recent_changes($limit = 10) {
    $changes = get_option('hdh_change_history', array());
    return array_slice($changes, 0, $limit);
}

/**
 * Get pinned sections
 */
function hdh_get_pinned_sections() {
    return get_option('hdh_pinned_sections', array());
}

/**
 * Get admin stats
 */
function hdh_get_admin_stats() {
    $all_settings = array();
    $modified = 0;
    
    if (class_exists('HDH_Settings_Registry')) {
        $all_settings = HDH_Settings_Registry::get_all();
    }
    
    foreach ($all_settings as $config) {
        $value = hdh_get_setting_value($config);
        if ($value !== $config['default']) {
            $modified++;
        }
    }
    
    return array(
        'total_settings' => count($all_settings),
        'modified_settings' => $modified,
        'draft_changes' => count(get_option('hdh_draft_changes', array())),
    );
}

