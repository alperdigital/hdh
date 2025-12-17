<?php
/**
 * HDH: Settings Registry System
 * Central registry for all configurable settings with metadata
 * 
 * This provides a unified schema for settings discovery, validation, and UI rendering.
 */

if (!defined('ABSPATH')) exit;

/**
 * Settings Registry
 * 
 * Structure:
 * - section: Top-level grouping (e.g., 'pre_login', 'post_login', 'global')
 * - group: Sub-grouping within section (e.g., 'landing', 'auth', 'profile')
 * - key: Unique identifier for the setting
 * - label: Human-readable label
 * - description: Help text
 * - type: Input type (text, textarea, number, select, checkbox, etc.)
 * - default: Default value
 * - validation: Validation rules
 * - visibility: 'quick' (default visible) or 'advanced' (collapsed)
 * - category: 'content', 'behavior', 'appearance', 'integration', 'debug'
 * - location: Where this setting appears (for context hints)
 * - storage: How it's stored ('option', 'meta', 'config')
 * - storage_key: The actual storage key
 */
class HDH_Settings_Registry {
    
    private static $registry = array();
    private static $initialized = false;
    
    /**
     * Initialize registry with all settings
     */
    public static function init() {
        if (self::$initialized) {
            return;
        }
        
        self::register_all_settings();
        self::$initialized = true;
    }
    
    /**
     * Register a setting
     */
    public static function register($section, $group, $key, $config) {
        $full_key = $section . '.' . $group . '.' . $key;
        
        self::$registry[$full_key] = array_merge(array(
            'section' => $section,
            'group' => $group,
            'key' => $key,
            'full_key' => $full_key,
            'label' => $key,
            'description' => '',
            'type' => 'text',
            'default' => '',
            'validation' => array(),
            'visibility' => 'quick',
            'category' => 'content',
            'location' => '',
            'storage' => 'option',
            'storage_key' => '',
        ), $config);
    }
    
    /**
     * Get a setting config
     */
    public static function get($full_key) {
        return isset(self::$registry[$full_key]) ? self::$registry[$full_key] : null;
    }
    
    /**
     * Get all settings for a section/group
     */
    public static function get_by_section($section, $group = null) {
        $filtered = array();
        foreach (self::$registry as $full_key => $config) {
            if ($config['section'] === $section) {
                if ($group === null || $config['group'] === $group) {
                    $filtered[$full_key] = $config;
                }
            }
        }
        return $filtered;
    }
    
    /**
     * Get settings by visibility
     */
    public static function get_by_visibility($visibility, $section = null, $group = null) {
        $filtered = array();
        foreach (self::$registry as $full_key => $config) {
            if ($config['visibility'] === $visibility) {
                if ($section === null || $config['section'] === $section) {
                    if ($group === null || $config['group'] === $group) {
                        $filtered[$full_key] = $config;
                    }
                }
            }
        }
        return $filtered;
    }
    
    /**
     * Search settings
     */
    public static function search($query) {
        $query = strtolower($query);
        $results = array();
        
        foreach (self::$registry as $full_key => $config) {
            $searchable = strtolower($config['label'] . ' ' . $config['description'] . ' ' . $config['key'] . ' ' . $full_key);
            if (strpos($searchable, $query) !== false) {
                $results[$full_key] = $config;
            }
        }
        
        return $results;
    }
    
    /**
     * Get all registered settings
     */
    public static function get_all() {
        return self::$registry;
    }
    
    /**
     * Register all settings from existing systems
     */
    private static function register_all_settings() {
        // Only register if functions are available
        if (!function_exists('hdh_get_default_content')) {
            return;
        }
        
        // Content Management Settings
        self::register_content_settings();
        
        // Message Settings
        if (function_exists('hdh_get_default_messages')) {
            self::register_message_settings();
        }
        
        // System Settings
        if (function_exists('hdh_get_default_settings')) {
            self::register_system_settings();
        }
        
        // Task Settings
        self::register_task_settings();
        
        // Item Settings
        self::register_item_settings();
    }
    
    /**
     * Register content management settings
     */
    private static function register_content_settings() {
        if (!function_exists('hdh_get_default_content')) {
            return;
        }
        
        $pages = array('homepage', 'auth', 'trade_create', 'trade_search', 'trade_single', 'lottery', 'decorations', 'profile', 'navigation', 'footer', 'error_404');
        
        foreach ($pages as $page) {
            $defaults = hdh_get_default_content($page);
            if (empty($defaults)) continue;
            
            // Determine section and group
            $section = 'pre_login';
            $group = $page;
            
            if (in_array($page, array('profile', 'trade_create', 'trade_search', 'trade_single'))) {
                $section = 'post_login';
            }
            
            if ($page === 'homepage') {
                $section = 'pre_login';
                $group = 'landing';
            }
            
            if ($page === 'auth') {
                $section = 'pre_login';
                $group = 'authentication';
            }
            
            if (in_array($page, array('trade_create', 'trade_search', 'trade_single'))) {
                $group = 'listings';
            }
            
            // Register each field
            foreach ($defaults as $key => $default_value) {
                $visibility = 'quick';
                $category = 'content';
                
                // Advanced settings
                if (strpos($key, 'error_') === 0 || strpos($key, 'hint_') === 0) {
                    $visibility = 'advanced';
                    $category = 'behavior';
                }
                
                self::register($section, $group, $key, array(
                    'label' => ucwords(str_replace('_', ' ', $key)),
                    'description' => sprintf('Content for %s page: %s', $page, $key),
                    'type' => (strpos($key, 'text') !== false || strpos($key, 'message') !== false || strpos($key, 'description') !== false) ? 'textarea' : 'text',
                    'default' => $default_value,
                    'visibility' => $visibility,
                    'category' => $category,
                    'location' => $page . ' page',
                    'storage' => 'option',
                    'storage_key' => 'hdh_content_' . $page . '_' . $key,
                ));
            }
        }
    }
    
    /**
     * Register message settings
     */
    private static function register_message_settings() {
        if (!function_exists('hdh_get_default_messages')) {
            return;
        }
        
        $defaults = hdh_get_default_messages();
        
        foreach ($defaults as $category => $messages) {
            $section = 'global';
            $group = 'messages';
            
            foreach ($messages as $key => $default_value) {
                $visibility = 'advanced';
                $cat = 'behavior';
                
                // Quick settings for common messages
                if (in_array($key, array('generic_error', 'login_required', 'security_failed'))) {
                    $visibility = 'quick';
                }
                
                self::register($section, $group, $category . '.' . $key, array(
                    'label' => ucwords(str_replace('_', ' ', $key)),
                    'description' => sprintf('Message: %s (%s)', $key, $category),
                    'type' => 'textarea',
                    'default' => $default_value,
                    'visibility' => $visibility,
                    'category' => $cat,
                    'location' => 'System-wide messages',
                    'storage' => 'option',
                    'storage_key' => 'hdh_message_' . $category . '_' . $key,
                ));
            }
        }
    }
    
    /**
     * Register system settings
     */
    private static function register_system_settings() {
        if (!function_exists('hdh_get_default_settings')) {
            return;
        }
        
        $defaults = hdh_get_default_settings();
        
        foreach ($defaults as $tab => $settings) {
            $section = 'global';
            $group = 'system';
            
            foreach ($settings as $key => $default_value) {
                $visibility = 'advanced';
                $category = 'behavior';
                
                // Quick settings for common limits
                if (in_array($key, array('max_offer_items', 'max_item_quantity', 'min_item_quantity'))) {
                    $visibility = 'quick';
                }
                
                self::register($section, $group, $key, array(
                    'label' => ucwords(str_replace('_', ' ', $key)),
                    'description' => sprintf('System setting: %s', $key),
                    'type' => 'number',
                    'default' => $default_value,
                    'validation' => array('min' => 0),
                    'visibility' => $visibility,
                    'category' => $category,
                    'location' => 'System configuration',
                    'storage' => 'option',
                    'storage_key' => 'hdh_setting_' . $key,
                ));
            }
        }
    }
    
    /**
     * Register task settings
     */
    private static function register_task_settings() {
        $section = 'global';
        $group = 'tasks';
        
        // XP per level
        self::register($section, $group, 'xp_per_level', array(
            'label' => 'XP Per Level',
            'description' => 'Amount of XP required for each level',
            'type' => 'number',
            'default' => 100,
            'validation' => array('min' => 1),
            'visibility' => 'quick',
            'category' => 'behavior',
            'location' => 'XP Settings',
            'storage' => 'option',
            'storage_key' => 'hdh_xp_per_level',
        ));
    }
    
    /**
     * Register item settings
     */
    private static function register_item_settings() {
        // Items are managed dynamically, so we register a placeholder
        $section = 'global';
        $group = 'components';
        
        self::register($section, $group, 'tradeable_items', array(
            'label' => 'Tradeable Items',
            'description' => 'Manage items that can be traded',
            'type' => 'custom',
            'default' => array(),
            'visibility' => 'quick',
            'category' => 'content',
            'location' => 'Items Management',
            'storage' => 'option',
            'storage_key' => 'hdh_items_config',
        ));
    }
}

// Initialize on admin init
add_action('admin_init', array('HDH_Settings_Registry', 'init'));

