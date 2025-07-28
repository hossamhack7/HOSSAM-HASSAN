<?php
/**
 * Gemini Bridge Site Health & Diagnostics Module
 *
 * Provides comprehensive site analysis and health information.
 *
 * @package GeminiBridge
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Site Health & Diagnostics Class
 *
 * @since 1.0.0
 */
class Gemini_Bridge_Diagnostics {

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
		// Site snapshot endpoint
		register_rest_route(
			$this->namespace,
			'/site/snapshot',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_site_snapshot' ),
				'permission_callback' => array( $this, 'check_hmac_permissions' ),
				'args'                => array(),
			)
		);
	}

	/**
	 * Check HMAC signature for authentication.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request The REST request object.
	 * @return bool|WP_Error True if authenticated, WP_Error otherwise.
	 */
	public function check_hmac_permissions( $request ) {
		$security = gemini_bridge()->security;
		return $security->check_hmac_signature( $request );
	}

	/**
	 * Get comprehensive site snapshot.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request The REST request object.
	 * @return WP_REST_Response|WP_Error The response object or WP_Error on failure.
	 */
	public function get_site_snapshot( $request ) {
		// Check for cached snapshot (valid for 5 minutes)
		$cache_key = 'gemini_bridge_site_snapshot';
		$cached_snapshot = get_transient( $cache_key );

		if ( false !== $cached_snapshot ) {
			return new WP_REST_Response( $cached_snapshot, 200 );
		}

		try {
			$snapshot = array(
				'timestamp'     => time(),
				'wordpress'     => $this->get_wordpress_info(),
				'php'           => $this->get_php_info(),
				'theme'         => $this->get_theme_info(),
				'plugins'       => $this->get_plugins_info(),
				'site_health'   => $this->get_site_health_info(),
				'database'      => $this->get_database_info(),
				'server'        => $this->get_server_info(),
				'cache'         => $this->get_cache_info(),
				'security'      => $this->get_security_info(),
			);

			// Cache the snapshot for 5 minutes
			set_transient( $cache_key, $snapshot, 5 * MINUTE_IN_SECONDS );

			return new WP_REST_Response( $snapshot, 200 );

		} catch ( Exception $e ) {
			return new WP_Error(
				'snapshot_failed',
				esc_html__( 'Failed to generate site snapshot.', 'gemini-bridge' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Get WordPress information.
	 *
	 * @since 1.0.0
	 * @return array WordPress information.
	 */
	private function get_wordpress_info() {
		global $wp_version;

		return array(
			'version'           => $wp_version,
			'multisite'         => is_multisite(),
			'site_url'          => site_url(),
			'home_url'          => home_url(),
			'admin_url'         => admin_url(),
			'language'          => get_locale(),
			'timezone'          => wp_timezone_string(),
			'date_format'       => get_option( 'date_format' ),
			'time_format'       => get_option( 'time_format' ),
			'start_of_week'     => get_option( 'start_of_week' ),
			'permalink_structure' => get_option( 'permalink_structure' ) ?: esc_html__( 'Plain', 'gemini-bridge' ),
			'users_can_register' => get_option( 'users_can_register' ),
			'default_role'      => get_option( 'default_role' ),
			'blog_public'       => get_option( 'blog_public' ),
			'comments_open'     => get_option( 'default_comment_status' ) === 'open',
			'pings_open'        => get_option( 'default_ping_status' ) === 'open',
		);
	}

	/**
	 * Get PHP information.
	 *
	 * @since 1.0.0
	 * @return array PHP information.
	 */
	private function get_php_info() {
		return array(
			'version'           => PHP_VERSION,
			'memory_limit'      => ini_get( 'memory_limit' ),
			'max_execution_time' => ini_get( 'max_execution_time' ),
			'max_input_vars'    => ini_get( 'max_input_vars' ),
			'post_max_size'     => ini_get( 'post_max_size' ),
			'upload_max_filesize' => ini_get( 'upload_max_filesize' ),
			'max_file_uploads'  => ini_get( 'max_file_uploads' ),
			'extensions'        => $this->get_php_extensions(),
			'disabled_functions' => $this->get_disabled_functions(),
		);
	}

	/**
	 * Get theme information.
	 *
	 * @since 1.0.0
	 * @return array Theme information.
	 */
	private function get_theme_info() {
		$theme = wp_get_theme();
		$parent_theme = $theme->parent();

		$theme_info = array(
			'name'          => $theme->get( 'Name' ),
			'version'       => $theme->get( 'Version' ),
			'description'   => $theme->get( 'Description' ),
			'author'        => $theme->get( 'Author' ),
			'author_uri'    => $theme->get( 'AuthorURI' ),
			'theme_uri'     => $theme->get( 'ThemeURI' ),
			'template'      => $theme->get_template(),
			'stylesheet'    => $theme->get_stylesheet(),
			'status'        => $theme->get( 'Status' ),
			'tags'          => $theme->get( 'Tags' ),
			'is_child_theme' => is_child_theme(),
		);

		if ( $parent_theme ) {
			$theme_info['parent_theme'] = array(
				'name'       => $parent_theme->get( 'Name' ),
				'version'    => $parent_theme->get( 'Version' ),
				'template'   => $parent_theme->get_template(),
			);
		}

		return $theme_info;
	}

	/**
	 * Get plugins information.
	 *
	 * @since 1.0.0
	 * @return array Plugins information.
	 */
	private function get_plugins_info() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins = get_plugins();
		$active_plugins = get_option( 'active_plugins', array() );
		$network_active_plugins = is_multisite() ? get_site_option( 'active_sitewide_plugins', array() ) : array();

		$plugins_data = array();

		foreach ( $all_plugins as $plugin_file => $plugin_data ) {
			$is_active = in_array( $plugin_file, $active_plugins ) || array_key_exists( $plugin_file, $network_active_plugins );
			
			$plugins_data[] = array(
				'name'          => $plugin_data['Name'],
				'version'       => $plugin_data['Version'],
				'description'   => $plugin_data['Description'],
				'author'        => $plugin_data['Author'],
				'author_uri'    => $plugin_data['AuthorURI'],
				'plugin_uri'    => $plugin_data['PluginURI'],
				'file'          => $plugin_file,
				'active'        => $is_active,
				'network_active' => array_key_exists( $plugin_file, $network_active_plugins ),
				'must_use'      => false, // We'll add MU plugins separately if needed
			);
		}

		return array(
			'total_plugins'    => count( $all_plugins ),
			'active_plugins'   => count( $active_plugins ) + count( $network_active_plugins ),
			'inactive_plugins' => count( $all_plugins ) - count( $active_plugins ) - count( $network_active_plugins ),
			'plugins'          => $plugins_data,
		);
	}

	/**
	 * Get site health information.
	 *
	 * @since 1.0.0
	 * @return array Site health information.
	 */
	private function get_site_health_info() {
		if ( ! class_exists( 'WP_Site_Health' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php';
		}

		$site_health = WP_Site_Health::get_instance();
		$tests = $site_health->get_tests();

		$health_data = array(
			'critical_issues' => 0,
			'recommended_improvements' => 0,
			'good_results' => 0,
			'tests' => array(),
		);

		// Run direct tests
		foreach ( $tests['direct'] as $test_name => $test_data ) {
			if ( is_callable( $test_data['test'] ) ) {
				try {
					$result = call_user_func( $test_data['test'] );
					$health_data['tests'][ $test_name ] = array(
						'label'       => $test_data['label'],
						'status'      => $result['status'],
						'badge_color' => $result['badge']['color'] ?? 'gray',
						'badge_label' => $result['badge']['label'] ?? esc_html__( 'Unknown', 'gemini-bridge' ),
						'description' => wp_strip_all_tags( $result['description'] ?? '' ),
					);

					// Count issues
					switch ( $result['status'] ) {
						case 'critical':
							$health_data['critical_issues']++;
							break;
						case 'recommended':
							$health_data['recommended_improvements']++;
							break;
						case 'good':
							$health_data['good_results']++;
							break;
					}
				} catch ( Exception $e ) {
					// Skip failed tests
					continue;
				}
			}
		}

		return $health_data;
	}

	/**
	 * Get database information.
	 *
	 * @since 1.0.0
	 * @return array Database information.
	 */
	private function get_database_info() {
		global $wpdb;

		$db_info = array(
			'version'    => $wpdb->db_version(),
			'charset'    => $wpdb->charset,
			'collate'    => $wpdb->collate,
			'prefix'     => $wpdb->prefix,
		);

		// Get database size (if possible)
		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'db_size_mb' 
				FROM information_schema.tables 
				WHERE table_schema = %s",
				DB_NAME
			)
		);

		if ( $result && isset( $result->db_size_mb ) ) {
			$db_info['size_mb'] = $result->db_size_mb;
		}

		return $db_info;
	}

	/**
	 * Get server information.
	 *
	 * @since 1.0.0
	 * @return array Server information.
	 */
	private function get_server_info() {
		return array(
			'software'       => sanitize_text_field( $_SERVER['SERVER_SOFTWARE'] ?? esc_html__( 'Unknown', 'gemini-bridge' ) ),
			'os'             => PHP_OS,
			'architecture'   => php_uname( 'm' ),
			'document_root'  => sanitize_text_field( $_SERVER['DOCUMENT_ROOT'] ?? '' ),
			'https'          => is_ssl(),
			'user_agent'     => sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ),
			'request_method' => sanitize_text_field( $_SERVER['REQUEST_METHOD'] ?? '' ),
		);
	}

	/**
	 * Get cache information.
	 *
	 * @since 1.0.0
	 * @return array Cache information.
	 */
	private function get_cache_info() {
		$cache_info = array(
			'object_cache'    => $this->detect_object_cache(),
			'page_cache'      => $this->detect_page_cache(),
			'opcache'         => $this->detect_opcache(),
			'cdn'             => $this->detect_cdn(),
		);

		return $cache_info;
	}

	/**
	 * Get security information.
	 *
	 * @since 1.0.0
	 * @return array Security information.
	 */
	private function get_security_info() {
		return array(
			'ssl_enabled'        => is_ssl(),
			'wp_debug'           => defined( 'WP_DEBUG' ) && WP_DEBUG,
			'wp_debug_log'       => defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG,
			'wp_debug_display'   => defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY,
			'disallow_file_edit' => defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT,
			'force_ssl_admin'    => defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN,
			'wp_auto_update_core' => get_option( 'auto_update_core' ),
		);
	}

	/**
	 * Get important PHP extensions.
	 *
	 * @since 1.0.0
	 * @return array PHP extensions status.
	 */
	private function get_php_extensions() {
		$important_extensions = array(
			'curl', 'gd', 'json', 'mbstring', 'mysql', 'mysqli', 'openssl',
			'pcre', 'xml', 'zip', 'imagick', 'exif', 'fileinfo', 'hash',
			'sodium', 'filter', 'intl'
		);

		$extensions = array();
		foreach ( $important_extensions as $ext ) {
			$extensions[ $ext ] = extension_loaded( $ext );
		}

		return $extensions;
	}

	/**
	 * Get disabled PHP functions.
	 *
	 * @since 1.0.0
	 * @return array Disabled PHP functions.
	 */
	private function get_disabled_functions() {
		$disabled = ini_get( 'disable_functions' );
		return $disabled ? array_map( 'trim', explode( ',', $disabled ) ) : array();
	}

	/**
	 * Detect object cache.
	 *
	 * @since 1.0.0
	 * @return array Object cache information.
	 */
	private function detect_object_cache() {
		global $wp_object_cache;

		$info = array(
			'enabled' => wp_using_ext_object_cache(),
			'type'    => 'default',
		);

		if ( wp_using_ext_object_cache() && is_object( $wp_object_cache ) ) {
			$cache_class = get_class( $wp_object_cache );
			
			if ( strpos( $cache_class, 'Redis' ) !== false ) {
				$info['type'] = 'Redis';
			} elseif ( strpos( $cache_class, 'Memcache' ) !== false ) {
				$info['type'] = 'Memcached';
			} else {
				$info['type'] = $cache_class;
			}
		}

		return $info;
	}

	/**
	 * Detect page cache plugins.
	 *
	 * @since 1.0.0
	 * @return array Page cache information.
	 */
	private function detect_page_cache() {
		$cache_plugins = array(
			'w3-total-cache/w3-total-cache.php'     => 'W3 Total Cache',
			'wp-super-cache/wp-cache.php'           => 'WP Super Cache',
			'litespeed-cache/litespeed-cache.php'   => 'LiteSpeed Cache',
			'wp-rocket/wp-rocket.php'               => 'WP Rocket',
			'wp-fastest-cache/wpFastestCache.php'   => 'WP Fastest Cache',
			'cache-enabler/cache-enabler.php'      => 'Cache Enabler',
			'wp-optimize/wp-optimize.php'           => 'WP-Optimize',
			'hummingbird-performance/wp-hummingbird.php' => 'Hummingbird',
		);

		$detected = array();
		$active_plugins = get_option( 'active_plugins', array() );

		foreach ( $cache_plugins as $plugin_file => $plugin_name ) {
			if ( in_array( $plugin_file, $active_plugins ) ) {
				$detected[] = $plugin_name;
			}
		}

		return array(
			'detected' => $detected,
			'count'    => count( $detected ),
		);
	}

	/**
	 * Detect OPcache.
	 *
	 * @since 1.0.0
	 * @return array OPcache information.
	 */
	private function detect_opcache() {
		$info = array(
			'enabled' => false,
			'version' => null,
		);

		if ( function_exists( 'opcache_get_status' ) ) {
			$status = opcache_get_status( false );
			$info['enabled'] = ! empty( $status['opcache_enabled'] );
			
			if ( function_exists( 'phpversion' ) ) {
				$info['version'] = phpversion( 'Zend OPcache' );
			}
		}

		return $info;
	}

	/**
	 * Detect CDN.
	 *
	 * @since 1.0.0
	 * @return array CDN information.
	 */
	private function detect_cdn() {
		$cdn_headers = array(
			'cf-ray'           => 'Cloudflare',
			'x-amz-cf-id'      => 'Amazon CloudFront',
			'x-cache'          => 'Generic CDN',
			'x-fastly-request-id' => 'Fastly',
		);

		$detected = array();

		foreach ( $cdn_headers as $header => $cdn_name ) {
			if ( ! empty( $_SERVER[ 'HTTP_' . strtoupper( str_replace( '-', '_', $header ) ) ] ) ) {
				$detected[] = $cdn_name;
			}
		}

		return array(
			'detected' => array_unique( $detected ),
			'count'    => count( array_unique( $detected ) ),
		);
	}
}