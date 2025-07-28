<?php
/**
 * Plugin Name: Hossam Intelligent Agent
 * Plugin URI: https://github.com/hossamhack7/HOSSAM-HASSAN
 * Description: WordPress plugin to integrate with Hossam's Intelligent Agent backend system. Provides AI-powered features for your WordPress site.
 * Version: 1.0.0
 * Author: Hossam Hassan
 * Author URI: https://github.com/hossamhack7
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hossam-intelligent-agent
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('HIA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HIA_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('HIA_PLUGIN_VERSION', '1.0.0');

/**
 * Main plugin class
 */
class HossamIntelligentAgent {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('hossam-intelligent-agent', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize admin interface
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_init', array($this, 'admin_init'));
        }
        
        // Add shortcode support
        add_shortcode('hia_chat', array($this, 'chat_shortcode'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_hia_send_message', array($this, 'ajax_send_message'));
        add_action('wp_ajax_nopriv_hia_send_message', array($this, 'ajax_send_message'));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create default options
        add_option('hia_backend_url', 'http://localhost:5000');
        add_option('hia_api_key', '');
        add_option('hia_enable_chat', 1);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Cleanup if needed
        flush_rewrite_rules();
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('Hossam Intelligent Agent Settings', 'hossam-intelligent-agent'),
            __('Intelligent Agent', 'hossam-intelligent-agent'),
            'manage_options',
            'hossam-intelligent-agent',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Initialize admin settings
     */
    public function admin_init() {
        register_setting('hia_settings', 'hia_backend_url');
        register_setting('hia_settings', 'hia_api_key');
        register_setting('hia_settings', 'hia_enable_chat');
        
        add_settings_section(
            'hia_main_section',
            __('Main Settings', 'hossam-intelligent-agent'),
            array($this, 'settings_section_callback'),
            'hia_settings'
        );
        
        add_settings_field(
            'hia_backend_url',
            __('Backend URL', 'hossam-intelligent-agent'),
            array($this, 'backend_url_callback'),
            'hia_settings',
            'hia_main_section'
        );
        
        add_settings_field(
            'hia_api_key',
            __('API Key', 'hossam-intelligent-agent'),
            array($this, 'api_key_callback'),
            'hia_settings',
            'hia_main_section'
        );
        
        add_settings_field(
            'hia_enable_chat',
            __('Enable Chat Widget', 'hossam-intelligent-agent'),
            array($this, 'enable_chat_callback'),
            'hia_settings',
            'hia_main_section'
        );
    }
    
    /**
     * Admin page HTML
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('hia_settings');
                do_settings_sections('hia_settings');
                submit_button();
                ?>
            </form>
            <div class="hia-admin-info">
                <h2><?php _e('About This Plugin', 'hossam-intelligent-agent'); ?></h2>
                <p><?php _e('This plugin connects your WordPress site to Hossam\'s Intelligent Agent backend system, providing AI-powered features.', 'hossam-intelligent-agent'); ?></p>
                <h3><?php _e('Usage', 'hossam-intelligent-agent'); ?></h3>
                <ul>
                    <li><?php _e('Use the shortcode [hia_chat] to display a chat interface on any page or post.', 'hossam-intelligent-agent'); ?></li>
                    <li><?php _e('Configure the backend URL to point to your running intelligent agent server.', 'hossam-intelligent-agent'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    /**
     * Settings section callback
     */
    public function settings_section_callback() {
        echo '<p>' . __('Configure the connection to your Intelligent Agent backend.', 'hossam-intelligent-agent') . '</p>';
    }
    
    /**
     * Backend URL field callback
     */
    public function backend_url_callback() {
        $backend_url = get_option('hia_backend_url', 'http://localhost:5000');
        echo '<input type="url" id="hia_backend_url" name="hia_backend_url" value="' . esc_attr($backend_url) . '" class="regular-text" />';
        echo '<p class="description">' . __('The URL of your Intelligent Agent backend server (e.g., http://localhost:5000)', 'hossam-intelligent-agent') . '</p>';
    }
    
    /**
     * API Key field callback
     */
    public function api_key_callback() {
        $api_key = get_option('hia_api_key', '');
        echo '<input type="password" id="hia_api_key" name="hia_api_key" value="' . esc_attr($api_key) . '" class="regular-text" />';
        echo '<p class="description">' . __('API key for authenticating with the backend (if required)', 'hossam-intelligent-agent') . '</p>';
    }
    
    /**
     * Enable chat field callback
     */
    public function enable_chat_callback() {
        $enable_chat = get_option('hia_enable_chat', 1);
        echo '<input type="checkbox" id="hia_enable_chat" name="hia_enable_chat" value="1" ' . checked(1, $enable_chat, false) . ' />';
        echo '<label for="hia_enable_chat">' . __('Enable the chat widget functionality', 'hossam-intelligent-agent') . '</label>';
    }
    
    /**
     * Chat shortcode
     */
    public function chat_shortcode($atts) {
        $atts = shortcode_atts(array(
            'height' => '400px',
            'width' => '100%',
            'title' => __('Chat with AI Assistant', 'hossam-intelligent-agent')
        ), $atts);
        
        if (!get_option('hia_enable_chat', 1)) {
            return '<p>' . __('Chat is currently disabled.', 'hossam-intelligent-agent') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="hia-chat-widget" style="width: <?php echo esc_attr($atts['width']); ?>; height: <?php echo esc_attr($atts['height']); ?>;">
            <div class="hia-chat-header">
                <h3><?php echo esc_html($atts['title']); ?></h3>
            </div>
            <div class="hia-chat-messages" id="hia-chat-messages"></div>
            <div class="hia-chat-input">
                <input type="text" id="hia-message-input" placeholder="<?php _e('Type your message...', 'hossam-intelligent-agent'); ?>" />
                <button id="hia-send-button" type="button"><?php _e('Send', 'hossam-intelligent-agent'); ?></button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_style('hia-frontend', HIA_PLUGIN_URL . 'assets/css/frontend.css', array(), HIA_PLUGIN_VERSION);
        wp_enqueue_script('hia-frontend', HIA_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), HIA_PLUGIN_VERSION, true);
        
        wp_localize_script('hia-frontend', 'hia_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hia_nonce'),
            'backend_url' => get_option('hia_backend_url', 'http://localhost:5000')
        ));
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook != 'settings_page_hossam-intelligent-agent') {
            return;
        }
        
        wp_enqueue_style('hia-admin', HIA_PLUGIN_URL . 'assets/css/admin.css', array(), HIA_PLUGIN_VERSION);
    }
    
    /**
     * AJAX handler for sending messages
     */
    public function ajax_send_message() {
        check_ajax_referer('hia_nonce', 'nonce');
        
        $message = sanitize_text_field($_POST['message']);
        $backend_url = get_option('hia_backend_url', 'http://localhost:5000');
        
        if (empty($message)) {
            wp_die(__('Message cannot be empty', 'hossam-intelligent-agent'));
        }
        
        // Send message to backend
        $response = $this->send_to_backend($message, $backend_url);
        
        wp_send_json($response);
    }
    
    /**
     * Send message to backend
     */
    private function send_to_backend($message, $backend_url) {
        $api_key = get_option('hia_api_key', '');
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'message' => $message,
                'api_key' => $api_key
            )),
            'timeout' => 30
        );
        
        $response = wp_remote_post($backend_url . '/v1/chat', $args);
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => __('Error connecting to backend: ', 'hossam-intelligent-agent') . $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (wp_remote_retrieve_response_code($response) == 200) {
            return array(
                'success' => true,
                'message' => isset($data['response']) ? $data['response'] : __('Response received', 'hossam-intelligent-agent')
            );
        } else {
            return array(
                'success' => false,
                'message' => __('Backend error: ', 'hossam-intelligent-agent') . (isset($data['error']) ? $data['error'] : __('Unknown error', 'hossam-intelligent-agent'))
            );
        }
    }
}

// Initialize the plugin
new HossamIntelligentAgent();

// Include additional files
require_once HIA_PLUGIN_PATH . 'includes/uninstall.php';