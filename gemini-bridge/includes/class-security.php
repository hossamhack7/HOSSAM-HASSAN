<?php
/**
 * Gemini Bridge Security & Authentication Module
 *
 * Handles HMAC-based request signing for secure API authentication.
 *
 * @package GeminiBridge
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Security & Authentication Class
 *
 * @since 1.0.0
 */
class Gemini_Bridge_Security {

	/**
	 * The namespace for REST routes.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $namespace = 'gemini-bridge/v1';

	/**
	 * Option name for storing the secret key.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $secret_key_option = 'gemini_bridge_secret_key';

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
		// Generate secret key endpoint
		register_rest_route(
			$this->namespace,
			'/auth/generate-key',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'generate_secret_key' ),
				'permission_callback' => array( $this, 'check_admin_permissions' ),
				'args'                => array(),
			)
		);

		// Verify request signature endpoint
		register_rest_route(
			$this->namespace,
			'/auth/verify',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'verify_signature' ),
				'permission_callback' => array( $this, 'check_hmac_signature' ),
				'args'                => array(),
			)
		);
	}

	/**
	 * Check if current user has administrator privileges.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request The REST request object.
	 * @return bool|WP_Error True if user has admin capabilities, WP_Error otherwise.
	 */
	public function check_admin_permissions( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'You do not have permission to access this endpoint.', 'gemini-bridge' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * Check HMAC signature for request authentication.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request The REST request object.
	 * @return bool|WP_Error True if signature is valid, WP_Error otherwise.
	 */
	public function check_hmac_signature( $request ) {
		$signature = $request->get_header( 'X-Gemini-Signature' );
		$timestamp = $request->get_header( 'X-Gemini-Timestamp' );

		if ( empty( $signature ) || empty( $timestamp ) ) {
			return new WP_Error(
				'missing_signature',
				esc_html__( 'Missing required authentication headers.', 'gemini-bridge' ),
				array( 'status' => 401 )
			);
		}

		// Check timestamp to prevent replay attacks (5-minute window)
		$current_time = time();
		if ( abs( $current_time - intval( $timestamp ) ) > 300 ) {
			return new WP_Error(
				'invalid_timestamp',
				esc_html__( 'Request timestamp is too old or in the future.', 'gemini-bridge' ),
				array( 'status' => 401 )
			);
		}

		// Get stored secret key
		$secret_key = get_option( $this->secret_key_option );
		if ( empty( $secret_key ) ) {
			return new WP_Error(
				'no_secret_key',
				esc_html__( 'Secret key not generated. Please generate a key first.', 'gemini-bridge' ),
				array( 'status' => 401 )
			);
		}

		// Create signature string
		$method      = $request->get_method();
		$uri         = $request->get_route();
		$body        = $request->get_body();
		$string_to_sign = $method . "\n" . $uri . "\n" . $body . "\n" . $timestamp;

		// Calculate expected signature
		$expected_signature = hash_hmac( 'sha256', $string_to_sign, $secret_key );

		// Compare signatures (timing-safe comparison)
		if ( ! hash_equals( $expected_signature, $signature ) ) {
			return new WP_Error(
				'invalid_signature',
				esc_html__( 'Invalid request signature.', 'gemini-bridge' ),
				array( 'status' => 401 )
			);
		}

		return true;
	}

	/**
	 * Generate a new secret key.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request The REST request object.
	 * @return WP_REST_Response|WP_Error The response object or WP_Error on failure.
	 */
	public function generate_secret_key( $request ) {
		try {
			// Generate a cryptographically secure random key
			$secret_key = $this->generate_secure_key();

			// Store the key securely
			$updated = update_option( $this->secret_key_option, $secret_key );

			if ( ! $updated ) {
				return new WP_Error(
					'key_storage_failed',
					esc_html__( 'Failed to store the secret key.', 'gemini-bridge' ),
					array( 'status' => 500 )
				);
			}

			// Log the key generation for security audit
			$this->log_security_event( 'secret_key_generated', get_current_user_id() );

			return new WP_REST_Response(
				array(
					'success'    => true,
					'message'    => esc_html__( 'Secret key generated successfully.', 'gemini-bridge' ),
					'secret_key' => $secret_key,
					'timestamp'  => time(),
				),
				200
			);

		} catch ( Exception $e ) {
			return new WP_Error(
				'key_generation_failed',
				esc_html__( 'Failed to generate secret key.', 'gemini-bridge' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Verify request signature.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request The REST request object.
	 * @return WP_REST_Response The response object.
	 */
	public function verify_signature( $request ) {
		return new WP_REST_Response(
			array(
				'success'   => true,
				'message'   => esc_html__( 'Signature verified successfully.', 'gemini-bridge' ),
				'timestamp' => time(),
			),
			200
		);
	}

	/**
	 * Generate a cryptographically secure random key.
	 *
	 * @since 1.0.0
	 * @return string The generated key.
	 * @throws Exception If secure key generation fails.
	 */
	private function generate_secure_key() {
		// Use WordPress wp_generate_password for compatibility
		if ( function_exists( 'wp_generate_password' ) ) {
			return wp_generate_password( 64, true, true );
		}

		// Fallback to random_bytes if available
		if ( function_exists( 'random_bytes' ) ) {
			return bin2hex( random_bytes( 32 ) );
		}

		// Final fallback using openssl
		if ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
			$bytes = openssl_random_pseudo_bytes( 32, $strong );
			if ( $strong ) {
				return bin2hex( $bytes );
			}
		}

		throw new Exception( 'Unable to generate secure random key' );
	}

	/**
	 * Log security events.
	 *
	 * @since 1.0.0
	 * @param string $event The event type.
	 * @param int    $user_id The user ID who triggered the event.
	 */
	private function log_security_event( $event, $user_id ) {
		$log_entry = array(
			'event'     => sanitize_text_field( $event ),
			'user_id'   => intval( $user_id ),
			'timestamp' => time(),
			'ip'        => $this->get_client_ip(),
			'user_agent' => sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ),
		);

		// Store in transient for recent events (24 hours)
		$recent_events = get_transient( 'gemini_bridge_security_log' ) ?: array();
		$recent_events[] = $log_entry;

		// Keep only last 100 events
		if ( count( $recent_events ) > 100 ) {
			$recent_events = array_slice( $recent_events, -100 );
		}

		set_transient( 'gemini_bridge_security_log', $recent_events, DAY_IN_SECONDS );
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

	/**
	 * Get the stored secret key.
	 *
	 * @since 1.0.0
	 * @return string|false The secret key or false if not set.
	 */
	public function get_secret_key() {
		return get_option( $this->secret_key_option );
	}

	/**
	 * Delete the stored secret key.
	 *
	 * @since 1.0.0
	 * @return bool True on success, false on failure.
	 */
	public function delete_secret_key() {
		$this->log_security_event( 'secret_key_deleted', get_current_user_id() );
		return delete_option( $this->secret_key_option );
	}
}