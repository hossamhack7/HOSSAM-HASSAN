<?php
/**
 * Plugin Name: Gemini Bridge
 * Plugin URI: https://github.com/hossamhack7/HOSSAM-HASSAN
 * Description: A comprehensive and highly secure companion plugin that serves as a backend bridge for sophisticated React-based frontend applications, enabling safe management, analysis, and execution of advanced operations on WordPress sites.
 * Version: 1.0.0
 * Author: Hossam Hassan
 * Author URI: https://github.com/hossamhack7
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gemini-bridge
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 *
 * @package GeminiBridge
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'GEMINI_BRIDGE_VERSION', '1.0.0' );
define( 'GEMINI_BRIDGE_PLUGIN_FILE', __FILE__ );
define( 'GEMINI_BRIDGE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GEMINI_BRIDGE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GEMINI_BRIDGE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main Gemini Bridge Plugin Class
 *
 * @since 1.0.0
 */
class Gemini_Bridge {

	/**
	 * Plugin instance.
	 *
	 * @since 1.0.0
	 * @var Gemini_Bridge
	 */
	private static $instance;

	/**
	 * Security module instance.
	 *
	 * @since 1.0.0
	 * @var Gemini_Bridge_Security
	 */
	public $security;

	/**
	 * Diagnostics module instance.
	 *
	 * @since 1.0.0
	 * @var Gemini_Bridge_Diagnostics
	 */
	public $diagnostics;

	/**
	 * Executor module instance.
	 *
	 * @since 1.0.0
	 * @var Gemini_Bridge_Executor
	 */
	public $executor;

	/**
	 * Code reader module instance.
	 *
	 * @since 1.0.0
	 * @var Gemini_Bridge_Code_Reader
	 */
	public $code_reader;

	/**
	 * Get plugin instance.
	 *
	 * @since 1.0.0
	 * @return Gemini_Bridge
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
	}

	/**
	 * Initialize the plugin.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		// Load text domain for internationalization
		load_plugin_textdomain( 'gemini-bridge', false, dirname( GEMINI_BRIDGE_PLUGIN_BASENAME ) . '/languages' );

		// Include required files
		$this->includes();

		// Initialize modules
		$this->init_modules();

		// Register REST API routes
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Include required files.
	 *
	 * @since 1.0.0
	 */
	private function includes() {
		require_once GEMINI_BRIDGE_PLUGIN_DIR . 'includes/class-security.php';
		require_once GEMINI_BRIDGE_PLUGIN_DIR . 'includes/class-diagnostics.php';
		require_once GEMINI_BRIDGE_PLUGIN_DIR . 'includes/class-executor.php';
		require_once GEMINI_BRIDGE_PLUGIN_DIR . 'includes/class-code-reader.php';
	}

	/**
	 * Initialize plugin modules.
	 *
	 * @since 1.0.0
	 */
	private function init_modules() {
		$this->security    = new Gemini_Bridge_Security();
		$this->diagnostics = new Gemini_Bridge_Diagnostics();
		$this->executor    = new Gemini_Bridge_Executor();
		$this->code_reader = new Gemini_Bridge_Code_Reader();
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 1.0.0
	 */
	public function register_rest_routes() {
		// Security & Authentication routes
		$this->security->register_routes();

		// Site Health & Diagnostics routes
		$this->diagnostics->register_routes();

		// Command Executor routes
		$this->executor->register_routes();

		// Code Reader routes
		$this->code_reader->register_routes();
	}

	/**
	 * Plugin activation.
	 *
	 * @since 1.0.0
	 */
	public function activate() {
		// Verify user capabilities
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		// Flush rewrite rules to ensure REST routes are available
		flush_rewrite_rules();

		// Add plugin activation timestamp
		update_option( 'gemini_bridge_activation_time', time() );
	}

	/**
	 * Plugin deactivation.
	 *
	 * @since 1.0.0
	 */
	public function deactivate() {
		// Verify user capabilities
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		// Flush rewrite rules
		flush_rewrite_rules();

		// Optional: Clean up temporary data (keep secret keys for reactivation)
		delete_transient( 'gemini_bridge_site_snapshot' );
	}
}

/**
 * Initialize the plugin.
 *
 * @since 1.0.0
 * @return Gemini_Bridge
 */
function gemini_bridge() {
	return Gemini_Bridge::get_instance();
}

// Initialize the plugin
gemini_bridge();