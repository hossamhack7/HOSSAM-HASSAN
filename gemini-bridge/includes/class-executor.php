<?php
/**
 * Gemini Bridge Command Executor Module
 *
 * Safely executes high-privilege commands with proper security checks.
 *
 * @package GeminiBridge
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Command Executor Class
 *
 * @since 1.0.0
 */
class Gemini_Bridge_Executor {

	/**
	 * The namespace for REST routes.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $namespace = 'gemini-bridge/v1';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// No hooks needed in constructor for this module
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 1.0.0
	 */
	public function register_routes() {
		// Plugin action endpoint
		register_rest_route(
			$this->namespace,
			'/execute/plugin-action',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'execute_plugin_action' ),
				'permission_callback' => array( $this, 'check_admin_permissions' ),
				'args'                => array(
					'action' => array(
						'required'          => true,
						'type'              => 'string',
						'enum'              => array( 'activate', 'deactivate', 'delete' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
					'slug' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => array( $this, 'validate_plugin_slug' ),
					),
				),
			)
		);

		// Install endpoint
		register_rest_route(
			$this->namespace,
			'/execute/install',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'execute_install' ),
				'permission_callback' => array( $this, 'check_admin_permissions' ),
				'args'                => array(
					'type' => array(
						'required'          => true,
						'type'              => 'string',
						'enum'              => array( 'plugin', 'theme' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
					'slug' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => array( $this, 'validate_install_slug' ),
					),
				),
			)
		);

		// Clear caches endpoint
		register_rest_route(
			$this->namespace,
			'/execute/clear-caches',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'execute_clear_caches' ),
				'permission_callback' => array( $this, 'check_admin_permissions' ),
				'args'                => array(),
			)
		);
	}

	/**
	 * Check if current user has admin permissions and valid HMAC signature.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request The REST request object.
	 * @return bool|WP_Error True if authorized, WP_Error otherwise.
	 */
	public function check_admin_permissions( $request ) {
		// First check HMAC signature
		$security = gemini_bridge()->security;
		$hmac_check = $security->check_hmac_signature( $request );
		
		if ( is_wp_error( $hmac_check ) ) {
			return $hmac_check;
		}

		// Then check admin capabilities
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'install_plugins' ) ) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'You do not have permission to execute this action.', 'gemini-bridge' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Validate plugin slug format.
	 *
	 * @since 1.0.0
	 * @param string $slug The plugin slug to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validate_plugin_slug( $slug ) {
		// Plugin slug should be in format: plugin-folder/plugin-file.php
		return preg_match( '/^[a-z0-9\-_]+\/[a-z0-9\-_]+\.php$/i', $slug );
	}

	/**
	 * Validate installation slug format.
	 *
	 * @since 1.0.0
	 * @param string $slug The installation slug to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validate_install_slug( $slug ) {
		// Installation slug should be alphanumeric with hyphens and underscores only
		return preg_match( '/^[a-z0-9\-_]+$/i', $slug );
	}

	/**
	 * Execute plugin actions (activate, deactivate, delete).
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request The REST request object.
	 * @return WP_REST_Response|WP_Error The response object or WP_Error on failure.
	 */
	public function execute_plugin_action( $request ) {
		$action = $request->get_param( 'action' );
		$slug   = $request->get_param( 'slug' );

		// Include necessary WordPress files
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Verify plugin exists
		$all_plugins = get_plugins();
		if ( ! array_key_exists( $slug, $all_plugins ) ) {
			return new WP_Error(
				'plugin_not_found',
				esc_html__( 'Plugin not found.', 'gemini-bridge' ),
				array( 'status' => 404 )
			);
		}

		$plugin_data = $all_plugins[ $slug ];
		$result = array(
			'action'      => $action,
			'plugin_slug' => $slug,
			'plugin_name' => $plugin_data['Name'],
			'success'     => false,
			'message'     => '',
		);

		try {
			switch ( $action ) {
				case 'activate':
					$result = $this->activate_plugin( $slug, $plugin_data );
					break;

				case 'deactivate':
					$result = $this->deactivate_plugin( $slug, $plugin_data );
					break;

				case 'delete':
					$result = $this->delete_plugin( $slug, $plugin_data );
					break;

				default:
					return new WP_Error(
						'invalid_action',
						esc_html__( 'Invalid action specified.', 'gemini-bridge' ),
						array( 'status' => 400 )
					);
			}

			// Log the action
			$this->log_executor_action( $action, $slug, $result['success'], get_current_user_id() );

			return new WP_REST_Response( $result, $result['success'] ? 200 : 500 );

		} catch ( Exception $e ) {
			$this->log_executor_action( $action, $slug, false, get_current_user_id(), $e->getMessage() );

			return new WP_Error(
				'execution_failed',
				sprintf(
					/* translators: %s: error message */
					esc_html__( 'Failed to execute action: %s', 'gemini-bridge' ),
					$e->getMessage()
				),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Activate a plugin.
	 *
	 * @since 1.0.0
	 * @param string $slug Plugin slug.
	 * @param array  $plugin_data Plugin data.
	 * @return array Result array.
	 */
	private function activate_plugin( $slug, $plugin_data ) {
		$result = array(
			'action'      => 'activate',
			'plugin_slug' => $slug,
			'plugin_name' => $plugin_data['Name'],
			'success'     => false,
			'message'     => '',
		);

		// Check if plugin is already active
		if ( is_plugin_active( $slug ) ) {
			$result['success'] = true;
			$result['message'] = esc_html__( 'Plugin is already active.', 'gemini-bridge' );
			return $result;
		}

		// Activate the plugin
		$activation_result = activate_plugin( $slug );

		if ( is_wp_error( $activation_result ) ) {
			$result['message'] = $activation_result->get_error_message();
		} else {
			$result['success'] = true;
			$result['message'] = esc_html__( 'Plugin activated successfully.', 'gemini-bridge' );
		}

		return $result;
	}

	/**
	 * Deactivate a plugin.
	 *
	 * @since 1.0.0
	 * @param string $slug Plugin slug.
	 * @param array  $plugin_data Plugin data.
	 * @return array Result array.
	 */
	private function deactivate_plugin( $slug, $plugin_data ) {
		$result = array(
			'action'      => 'deactivate',
			'plugin_slug' => $slug,
			'plugin_name' => $plugin_data['Name'],
			'success'     => false,
			'message'     => '',
		);

		// Check if plugin is active
		if ( ! is_plugin_active( $slug ) ) {
			$result['success'] = true;
			$result['message'] = esc_html__( 'Plugin is already inactive.', 'gemini-bridge' );
			return $result;
		}

		// Prevent deactivating this plugin itself
		if ( $slug === GEMINI_BRIDGE_PLUGIN_BASENAME ) {
			$result['message'] = esc_html__( 'Cannot deactivate the Gemini Bridge plugin itself.', 'gemini-bridge' );
			return $result;
		}

		// Deactivate the plugin
		deactivate_plugins( $slug );

		// Verify deactivation
		if ( ! is_plugin_active( $slug ) ) {
			$result['success'] = true;
			$result['message'] = esc_html__( 'Plugin deactivated successfully.', 'gemini-bridge' );
		} else {
			$result['message'] = esc_html__( 'Failed to deactivate plugin.', 'gemini-bridge' );
		}

		return $result;
	}

	/**
	 * Delete a plugin.
	 *
	 * @since 1.0.0
	 * @param string $slug Plugin slug.
	 * @param array  $plugin_data Plugin data.
	 * @return array Result array.
	 */
	private function delete_plugin( $slug, $plugin_data ) {
		$result = array(
			'action'      => 'delete',
			'plugin_slug' => $slug,
			'plugin_name' => $plugin_data['Name'],
			'success'     => false,
			'message'     => '',
		);

		// Prevent deleting this plugin itself
		if ( $slug === GEMINI_BRIDGE_PLUGIN_BASENAME ) {
			$result['message'] = esc_html__( 'Cannot delete the Gemini Bridge plugin itself.', 'gemini-bridge' );
			return $result;
		}

		// Deactivate plugin first if it's active
		if ( is_plugin_active( $slug ) ) {
			deactivate_plugins( $slug );
		}

		// Include necessary file for deletion
		if ( ! function_exists( 'delete_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		// Delete the plugin
		$deletion_result = delete_plugins( array( $slug ) );

		if ( is_wp_error( $deletion_result ) ) {
			$result['message'] = $deletion_result->get_error_message();
		} elseif ( $deletion_result === true ) {
			$result['success'] = true;
			$result['message'] = esc_html__( 'Plugin deleted successfully.', 'gemini-bridge' );
		} else {
			$result['message'] = esc_html__( 'Failed to delete plugin.', 'gemini-bridge' );
		}

		return $result;
	}

	/**
	 * Execute installation of plugins or themes.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request The REST request object.
	 * @return WP_REST_Response|WP_Error The response object or WP_Error on failure.
	 */
	public function execute_install( $request ) {
		$type = $request->get_param( 'type' );
		$slug = $request->get_param( 'slug' );

		// Include necessary WordPress files
		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}
		if ( ! function_exists( 'themes_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/theme-install.php';
		}
		if ( ! class_exists( 'Plugin_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		$result = array(
			'type'    => $type,
			'slug'    => $slug,
			'success' => false,
			'message' => '',
		);

		try {
			if ( $type === 'plugin' ) {
				$result = $this->install_plugin( $slug );
			} elseif ( $type === 'theme' ) {
				$result = $this->install_theme( $slug );
			}

			// Log the action
			$this->log_executor_action( 'install_' . $type, $slug, $result['success'], get_current_user_id() );

			return new WP_REST_Response( $result, $result['success'] ? 200 : 500 );

		} catch ( Exception $e ) {
			$this->log_executor_action( 'install_' . $type, $slug, false, get_current_user_id(), $e->getMessage() );

			return new WP_Error(
				'installation_failed',
				sprintf(
					/* translators: %s: error message */
					esc_html__( 'Installation failed: %s', 'gemini-bridge' ),
					$e->getMessage()
				),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Install a plugin from WordPress.org.
	 *
	 * @since 1.0.0
	 * @param string $slug Plugin slug.
	 * @return array Result array.
	 */
	private function install_plugin( $slug ) {
		$result = array(
			'type'    => 'plugin',
			'slug'    => $slug,
			'success' => false,
			'message' => '',
		);

		// Get plugin information from WordPress.org
		$api = plugins_api( 'plugin_information', array( 'slug' => $slug ) );

		if ( is_wp_error( $api ) ) {
			$result['message'] = $api->get_error_message();
			return $result;
		}

		// Create upgrader instance
		$upgrader = new Plugin_Upgrader( new WP_Ajax_Upgrader_Skin() );

		// Install the plugin
		$install_result = $upgrader->install( $api->download_link );

		if ( is_wp_error( $install_result ) ) {
			$result['message'] = $install_result->get_error_message();
		} elseif ( $install_result === true ) {
			$result['success'] = true;
			$result['message'] = esc_html__( 'Plugin installed successfully.', 'gemini-bridge' );
			$result['plugin_name'] = $api->name;
			$result['version'] = $api->version;
		} else {
			$result['message'] = esc_html__( 'Plugin installation failed.', 'gemini-bridge' );
		}

		return $result;
	}

	/**
	 * Install a theme from WordPress.org.
	 *
	 * @since 1.0.0
	 * @param string $slug Theme slug.
	 * @return array Result array.
	 */
	private function install_theme( $slug ) {
		$result = array(
			'type'    => 'theme',
			'slug'    => $slug,
			'success' => false,
			'message' => '',
		);

		// Get theme information from WordPress.org
		$api = themes_api( 'theme_information', array( 'slug' => $slug ) );

		if ( is_wp_error( $api ) ) {
			$result['message'] = $api->get_error_message();
			return $result;
		}

		// Create upgrader instance
		$upgrader = new Theme_Upgrader( new WP_Ajax_Upgrader_Skin() );

		// Install the theme
		$install_result = $upgrader->install( $api->download_link );

		if ( is_wp_error( $install_result ) ) {
			$result['message'] = $install_result->get_error_message();
		} elseif ( $install_result === true ) {
			$result['success'] = true;
			$result['message'] = esc_html__( 'Theme installed successfully.', 'gemini-bridge' );
			$result['theme_name'] = $api->name;
			$result['version'] = $api->version;
		} else {
			$result['message'] = esc_html__( 'Theme installation failed.', 'gemini-bridge' );
		}

		return $result;
	}

	/**
	 * Execute cache clearing for popular caching plugins.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request The REST request object.
	 * @return WP_REST_Response|WP_Error The response object or WP_Error on failure.
	 */
	public function execute_clear_caches( $request ) {
		$result = array(
			'success'      => false,
			'message'      => '',
			'caches_cleared' => array(),
		);

		$cleared_count = 0;

		try {
			// Clear WordPress object cache
			if ( function_exists( 'wp_cache_flush' ) ) {
				wp_cache_flush();
				$result['caches_cleared'][] = 'WordPress Object Cache';
				$cleared_count++;
			}

			// Clear W3 Total Cache
			if ( function_exists( 'w3tc_flush_all' ) ) {
				w3tc_flush_all();
				$result['caches_cleared'][] = 'W3 Total Cache';
				$cleared_count++;
			}

			// Clear WP Super Cache
			if ( function_exists( 'wp_cache_clear_cache' ) ) {
				wp_cache_clear_cache();
				$result['caches_cleared'][] = 'WP Super Cache';
				$cleared_count++;
			}

			// Clear LiteSpeed Cache
			if ( class_exists( 'LiteSpeed_Cache_API' ) && method_exists( 'LiteSpeed_Cache_API', 'purge_all' ) ) {
				LiteSpeed_Cache_API::purge_all();
				$result['caches_cleared'][] = 'LiteSpeed Cache';
				$cleared_count++;
			}

			// Clear WP Rocket Cache
			if ( function_exists( 'rocket_clean_domain' ) ) {
				rocket_clean_domain();
				$result['caches_cleared'][] = 'WP Rocket';
				$cleared_count++;
			}

			// Clear WP Fastest Cache
			if ( class_exists( 'WpFastestCache' ) ) {
				$wp_fastest_cache = new WpFastestCache();
				if ( method_exists( $wp_fastest_cache, 'deleteCache' ) ) {
					$wp_fastest_cache->deleteCache( true );
					$result['caches_cleared'][] = 'WP Fastest Cache';
					$cleared_count++;
				}
			}

			// Clear Cache Enabler
			if ( class_exists( 'Cache_Enabler' ) && method_exists( 'Cache_Enabler', 'clear_complete_cache' ) ) {
				Cache_Enabler::clear_complete_cache();
				$result['caches_cleared'][] = 'Cache Enabler';
				$cleared_count++;
			}

			// Clear Hummingbird Cache
			if ( class_exists( 'Hummingbird\WP_Hummingbird' ) ) {
				$hummingbird = Hummingbird\WP_Hummingbird::get_instance();
				if ( method_exists( $hummingbird, 'flush_cache' ) ) {
					$hummingbird->flush_cache( true, false );
					$result['caches_cleared'][] = 'Hummingbird';
					$cleared_count++;
				}
			}

			// Clear WordPress transients
			$this->clear_transients();
			$result['caches_cleared'][] = 'WordPress Transients';
			$cleared_count++;

			// Set success status and message
			if ( $cleared_count > 0 ) {
				$result['success'] = true;
				$result['message'] = sprintf(
					/* translators: %d: number of caches cleared */
					esc_html( _n(
						'Successfully cleared %d cache.',
						'Successfully cleared %d caches.',
						$cleared_count,
						'gemini-bridge'
					) ),
					$cleared_count
				);
			} else {
				$result['message'] = esc_html__( 'No caches found to clear.', 'gemini-bridge' );
			}

			// Log the action
			$this->log_executor_action( 'clear_caches', '', $result['success'], get_current_user_id() );

			return new WP_REST_Response( $result, 200 );

		} catch ( Exception $e ) {
			$this->log_executor_action( 'clear_caches', '', false, get_current_user_id(), $e->getMessage() );

			return new WP_Error(
				'cache_clear_failed',
				sprintf(
					/* translators: %s: error message */
					esc_html__( 'Failed to clear caches: %s', 'gemini-bridge' ),
					$e->getMessage()
				),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Clear WordPress transients.
	 *
	 * @since 1.0.0
	 */
	private function clear_transients() {
		global $wpdb;

		// Delete expired transients
		$wpdb->query(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_timeout_%' 
			AND option_value < " . time()
		);

		// Delete the corresponding transient
		$wpdb->query(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_%' 
			AND option_name NOT LIKE '_transient_timeout_%'"
		);

		// For multisite
		if ( is_multisite() ) {
			$wpdb->query(
				"DELETE FROM {$wpdb->sitemeta} 
				WHERE meta_key LIKE '_site_transient_timeout_%' 
				AND meta_value < " . time()
			);

			$wpdb->query(
				"DELETE FROM {$wpdb->sitemeta} 
				WHERE meta_key LIKE '_site_transient_%' 
				AND meta_key NOT LIKE '_site_transient_timeout_%'"
			);
		}
	}

	/**
	 * Log executor actions.
	 *
	 * @since 1.0.0
	 * @param string $action The action performed.
	 * @param string $target The target of the action.
	 * @param bool   $success Whether the action was successful.
	 * @param int    $user_id The user ID who performed the action.
	 * @param string $error_message Optional error message.
	 */
	private function log_executor_action( $action, $target, $success, $user_id, $error_message = '' ) {
		$log_entry = array(
			'action'       => sanitize_text_field( $action ),
			'target'       => sanitize_text_field( $target ),
			'success'      => (bool) $success,
			'user_id'      => intval( $user_id ),
			'timestamp'    => time(),
			'ip'           => $this->get_client_ip(),
			'error_message' => sanitize_text_field( $error_message ),
		);

		// Store in transient for recent actions (24 hours)
		$recent_actions = get_transient( 'gemini_bridge_executor_log' ) ?: array();
		$recent_actions[] = $log_entry;

		// Keep only last 50 actions
		if ( count( $recent_actions ) > 50 ) {
			$recent_actions = array_slice( $recent_actions, -50 );
		}

		set_transient( 'gemini_bridge_executor_log', $recent_actions, DAY_IN_SECONDS );
	}

	/**
	 * Get client IP address.
	 *
	 * @since 1.0.0
	 * @return string The client IP address.
	 */
	private function get_client_ip() {
		$ip_keys = array(
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR'
		);

		foreach ( $ip_keys as $key ) {
			if ( array_key_exists( $key, $_SERVER ) === true ) {
				$ip_list = explode( ',', $_SERVER[ $key ] );
				$ip = trim( $ip_list[0] );
				
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					return $ip;
				}
			}
		}

		return sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' );
	}
}