<?php
/**
 * The main plugin class.
 *
 * @package GeminiCommandCenter
 */

class Gemini_Command_Center {

    /**
     * Plugin version.
     *
     * @var string
     */
    private $version;

    /**
     * The loader that's responsible for maintaining and registering all hooks.
     *
     * @var array
     */
    private $modules;

    /**
     * Initialize the plugin.
     */
    public function __construct() {
        $this->version = GEMINI_CC_VERSION;
        $this->modules = array();
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {
        // Core modules
        require_once GEMINI_CC_INCLUDES_DIR . 'class-assets-loader.php';
        require_once GEMINI_CC_INCLUDES_DIR . 'class-api-registrar.php';
        require_once GEMINI_CC_INCLUDES_DIR . 'class-settings-handler.php';
        require_once GEMINI_CC_INCLUDES_DIR . 'class-security-manager.php';
        
        // Feature modules
        require_once GEMINI_CC_INCLUDES_DIR . 'class-backup-manager.php';
        require_once GEMINI_CC_INCLUDES_DIR . 'class-seo-manager.php';
        require_once GEMINI_CC_INCLUDES_DIR . 'class-content-manager.php';
        require_once GEMINI_CC_INCLUDES_DIR . 'class-uiux-manager.php';
        require_once GEMINI_CC_INCLUDES_DIR . 'class-reporting-manager.php';
        require_once GEMINI_CC_INCLUDES_DIR . 'class-ai-agent.php';
        
        // Utility classes
        require_once GEMINI_CC_INCLUDES_DIR . 'class-gemini-api.php';
        require_once GEMINI_CC_INCLUDES_DIR . 'class-rate-limiter.php';
        require_once GEMINI_CC_INCLUDES_DIR . 'class-logger.php';
    }

    /**
     * Define the locale for this plugin for internationalization.
     */
    private function set_locale() {
        add_action('init', array($this, 'load_plugin_textdomain'));
    }

    /**
     * Load the plugin text domain for translation.
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'gemini-command-center',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }

    /**
     * Register all hooks related to the admin area functionality.
     */
    private function define_admin_hooks() {
        // Initialize modules
        $this->modules['assets_loader'] = new Gemini_CC_Assets_Loader();
        $this->modules['api_registrar'] = new Gemini_CC_API_Registrar();
        $this->modules['settings_handler'] = new Gemini_CC_Settings_Handler();
        $this->modules['security_manager'] = new Gemini_CC_Security_Manager();
        $this->modules['backup_manager'] = new Gemini_CC_Backup_Manager();
        $this->modules['seo_manager'] = new Gemini_CC_SEO_Manager();
        $this->modules['content_manager'] = new Gemini_CC_Content_Manager();
        $this->modules['uiux_manager'] = new Gemini_CC_UIUX_Manager();
        $this->modules['reporting_manager'] = new Gemini_CC_Reporting_Manager();
        $this->modules['ai_agent'] = new Gemini_CC_AI_Agent();

        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Admin notices
        add_action('admin_notices', array($this, 'display_admin_notices'));
        
        // AJAX hooks
        add_action('wp_ajax_gemini_cc_dismiss_notice', array($this, 'dismiss_admin_notice'));
    }

    /**
     * Register all hooks related to the public-facing functionality.
     */
    private function define_public_hooks() {
        // A/B testing title filter
        add_filter('the_title', array($this->modules['content_manager'], 'filter_ab_test_title'), 10, 2);
        
        // CSS injection for UI/UX changes
        add_action('wp_head', array($this->modules['uiux_manager'], 'inject_custom_styles'));
    }

    /**
     * Add admin menu items.
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('Gemini Command Center', 'gemini-command-center'),
            __('Gemini Command Center', 'gemini-command-center'),
            'manage_options',
            'gemini-command-center',
            array($this, 'display_main_page'),
            'dashicons-rocket',
            30
        );

        // AI Agent submenu
        add_submenu_page(
            'gemini-command-center',
            __('AI Agent', 'gemini-command-center'),
            __('AI Agent', 'gemini-command-center'),
            'manage_options',
            'gemini-ai-agent',
            array($this, 'display_ai_agent_page')
        );

        // Settings submenu
        add_submenu_page(
            'gemini-command-center',
            __('Settings', 'gemini-command-center'),
            __('Settings', 'gemini-command-center'),
            'manage_options',
            'gemini-settings',
            array($this, 'display_settings_page')
        );
    }

    /**
     * Display the main plugin page.
     */
    public function display_main_page() {
        require_once GEMINI_CC_PLUGIN_DIR . 'templates/admin-main.php';
    }

    /**
     * Display the AI Agent page.
     */
    public function display_ai_agent_page() {
        require_once GEMINI_CC_PLUGIN_DIR . 'templates/admin-ai-agent.php';
    }

    /**
     * Display the settings page.
     */
    public function display_settings_page() {
        require_once GEMINI_CC_PLUGIN_DIR . 'templates/admin-settings.php';
    }

    /**
     * Display admin notices.
     */
    public function display_admin_notices() {
        $notices = get_transient('gemini_cc_admin_notices');
        if (!empty($notices)) {
            foreach ($notices as $notice) {
                $class = isset($notice['type']) ? $notice['type'] : 'info';
                $dismissible = isset($notice['dismissible']) && $notice['dismissible'] ? 'is-dismissible' : '';
                echo '<div class="notice notice-' . esc_attr($class) . ' ' . esc_attr($dismissible) . '">';
                echo '<p>' . wp_kses_post($notice['message']) . '</p>';
                echo '</div>';
            }
            delete_transient('gemini_cc_admin_notices');
        }
    }

    /**
     * Dismiss admin notice via AJAX.
     */
    public function dismiss_admin_notice() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'gemini-command-center'));
        }

        if (!wp_verify_nonce($_POST['nonce'], 'gemini_cc_admin_nonce')) {
            wp_die(__('Security check failed.', 'gemini-command-center'));
        }

        $notice_id = sanitize_text_field($_POST['notice_id']);
        update_user_meta(get_current_user_id(), 'gemini_cc_dismissed_' . $notice_id, true);
        
        wp_send_json_success();
    }

    /**
     * Run the plugin.
     */
    public function run() {
        // The plugin is now running
        do_action('gemini_cc_loaded');
    }

    /**
     * Get plugin version.
     *
     * @return string
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Get a specific module instance.
     *
     * @param string $module_name The module name.
     * @return object|null The module instance or null if not found.
     */
    public function get_module($module_name) {
        return isset($this->modules[$module_name]) ? $this->modules[$module_name] : null;
    }
}