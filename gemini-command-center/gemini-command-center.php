<?php
/**
 * Plugin Name: Gemini Command Center
 * Plugin URI: https://github.com/hossamhack7/HOSSAM-HASSAN
 * Description: A comprehensive, AI-powered management suite for WordPress powered by Google's Gemini AI.
 * Version: 1.0.0
 * Author: Hossam Hassan
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gemini-command-center
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.5
 * Requires PHP: 7.4
 * Network: false
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Plugin version.
 */
define('GEMINI_CC_VERSION', '1.0.0');

/**
 * Plugin paths and URLs.
 */
define('GEMINI_CC_PLUGIN_FILE', __FILE__);
define('GEMINI_CC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GEMINI_CC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GEMINI_CC_INCLUDES_DIR', GEMINI_CC_PLUGIN_DIR . 'includes/');
define('GEMINI_CC_ASSETS_URL', GEMINI_CC_PLUGIN_URL . 'assets/');
define('GEMINI_CC_BUILD_URL', GEMINI_CC_PLUGIN_URL . 'build/');

/**
 * Database table names.
 */
global $wpdb;
define('GEMINI_CC_MEMORY_TABLE', $wpdb->prefix . 'gemini_cc_memory');
define('GEMINI_CC_LOGS_TABLE', $wpdb->prefix . 'gemini_cc_logs');
define('GEMINI_CC_AB_TESTS_TABLE', $wpdb->prefix . 'gemini_cc_ab_tests');

/**
 * Include the main plugin class.
 */
require_once GEMINI_CC_INCLUDES_DIR . 'class-gemini-command-center.php';

/**
 * Initialize the plugin.
 */
function gemini_cc_init() {
    $plugin = new Gemini_Command_Center();
    $plugin->run();
}

/**
 * Plugin activation hook.
 */
function gemini_cc_activate() {
    require_once GEMINI_CC_INCLUDES_DIR . 'class-gemini-cc-activator.php';
    Gemini_CC_Activator::activate();
}

/**
 * Plugin deactivation hook.
 */
function gemini_cc_deactivate() {
    require_once GEMINI_CC_INCLUDES_DIR . 'class-gemini-cc-deactivator.php';
    Gemini_CC_Deactivator::deactivate();
}

/**
 * Plugin uninstall hook.
 */
function gemini_cc_uninstall() {
    require_once GEMINI_CC_INCLUDES_DIR . 'class-gemini-cc-uninstaller.php';
    Gemini_CC_Uninstaller::uninstall();
}

// Hook into WordPress
add_action('plugins_loaded', 'gemini_cc_init');
register_activation_hook(__FILE__, 'gemini_cc_activate');
register_deactivation_hook(__FILE__, 'gemini_cc_deactivate');
register_uninstall_hook(__FILE__, 'gemini_cc_uninstall');