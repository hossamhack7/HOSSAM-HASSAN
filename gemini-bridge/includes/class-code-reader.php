<?php
/**
 * Gemini Bridge Advanced Code & Content Intelligence Module
 *
 * Safely reads theme files for AI analysis with strict security constraints.
 *
 * @package GeminiBridge
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Advanced Code & Content Intelligence Class
 *
 * @since 1.0.0
 */
class Gemini_Bridge_Code_Reader {

	/**
	 * The namespace for REST routes.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $namespace = 'gemini-bridge/v1';

	/**
	 * Maximum file size to read (in bytes).
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private $max_file_size = 1048576; // 1MB

	/**
	 * Allowed file extensions.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $allowed_extensions = array(
		'php', 'css', 'js', 'json', 'txt', 'md', 'html', 'htm', 'xml', 'scss', 'sass', 'less'
	);

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
		// Read file content endpoint
		register_rest_route(
			$this->namespace,
			'/read-file-content',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'read_file_content' ),
				'permission_callback' => array( $this, 'check_read_permissions' ),
				'args'                => array(
					'file_path' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => array( $this, 'sanitize_file_path' ),
						'validate_callback' => array( $this, 'validate_file_path' ),
					),
				),
			)
		);

		// List theme files endpoint
		register_rest_route(
			$this->namespace,
			'/list-theme-files',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'list_theme_files' ),
				'permission_callback' => array( $this, 'check_read_permissions' ),
				'args'                => array(
					'path' => array(
						'required'          => false,
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => array( $this, 'sanitize_file_path' ),
						'validate_callback' => array( $this, 'validate_directory_path' ),
					),
				),
			)
		);
	}

	/**
	 * Check HMAC signature and read permissions.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request The REST request object.
	 * @return bool|WP_Error True if authorized, WP_Error otherwise.
	 */
	public function check_read_permissions( $request ) {
		// First check HMAC signature
		$security = gemini_bridge()->security;
		$hmac_check = $security->check_hmac_signature( $request );
		
		if ( is_wp_error( $hmac_check ) ) {
			return $hmac_check;
		}

		// Check if user can edit themes (indicates they can view theme files)
		if ( ! current_user_can( 'edit_themes' ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'You do not have permission to read theme files.', 'gemini-bridge' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Sanitize file path to prevent directory traversal.
	 *
	 * @since 1.0.0
	 * @param string $path The file path to sanitize.
	 * @return string Sanitized file path.
	 */
	public function sanitize_file_path( $path ) {
		// Remove any directory traversal attempts
		$path = str_replace( array( '../', '..\\', './', '.\\' ), '', $path );
		
		// Remove null bytes
		$path = str_replace( "\0", '', $path );
		
		// Normalize slashes
		$path = str_replace( '\\', '/', $path );
		
		// Remove leading slash
		$path = ltrim( $path, '/' );
		
		// Sanitize the path
		return sanitize_text_field( $path );
	}

	/**
	 * Validate file path is within theme directory and has allowed extension.
	 *
	 * @since 1.0.0
	 * @param string $path The file path to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validate_file_path( $path ) {
		// Check for directory traversal attempts
		if ( strpos( $path, '..' ) !== false ) {
			return false;
		}

		// Check file extension
		$extension = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
		if ( ! in_array( $extension, $this->allowed_extensions, true ) ) {
			return false;
		}

		// Build full path and verify it's within theme directory
		$theme_dir = get_stylesheet_directory();
		$full_path = $theme_dir . '/' . $path;
		$real_path = realpath( $full_path );

		// Ensure the resolved path is within the theme directory
		if ( ! $real_path || strpos( $real_path, $theme_dir ) !== 0 ) {
			return false;
		}

		return true;
	}

	/**
	 * Validate directory path is within theme directory.
	 *
	 * @since 1.0.0
	 * @param string $path The directory path to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validate_directory_path( $path ) {
		// Allow empty path (root theme directory)
		if ( empty( $path ) ) {
			return true;
		}

		// Check for directory traversal attempts
		if ( strpos( $path, '..' ) !== false ) {
			return false;
		}

		// Build full path and verify it's within theme directory
		$theme_dir = get_stylesheet_directory();
		$full_path = $theme_dir . '/' . $path;
		$real_path = realpath( $full_path );

		// Ensure the resolved path is within the theme directory
		if ( ! $real_path || strpos( $real_path, $theme_dir ) !== 0 ) {
			return false;
		}

		// Ensure it's actually a directory
		return is_dir( $real_path );
	}

	/**
	 * Read file content from theme directory.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request The REST request object.
	 * @return WP_REST_Response|WP_Error The response object or WP_Error on failure.
	 */
	public function read_file_content( $request ) {
		$file_path = $request->get_param( 'file_path' );
		$theme_dir = get_stylesheet_directory();
		$full_path = $theme_dir . '/' . $file_path;

		try {
			// Final security check - ensure file exists and is readable
			if ( ! file_exists( $full_path ) ) {
				return new WP_Error(
					'file_not_found',
					esc_html__( 'File not found.', 'gemini-bridge' ),
					array( 'status' => 404 )
				);
			}

			if ( ! is_readable( $full_path ) ) {
				return new WP_Error(
					'file_not_readable',
					esc_html__( 'File is not readable.', 'gemini-bridge' ),
					array( 'status' => 403 )
				);
			}

			// Check file size
			$file_size = filesize( $full_path );
			if ( $file_size > $this->max_file_size ) {
				return new WP_Error(
					'file_too_large',
					sprintf(
						/* translators: %s: maximum file size */
						esc_html__( 'File is too large. Maximum size allowed: %s', 'gemini-bridge' ),
						size_format( $this->max_file_size )
					),
					array( 'status' => 413 )
				);
			}

			// Read file content
			$content = file_get_contents( $full_path );
			
			if ( $content === false ) {
				return new WP_Error(
					'file_read_error',
					esc_html__( 'Failed to read file content.', 'gemini-bridge' ),
					array( 'status' => 500 )
				);
			}

			// Get file information
			$file_info = array(
				'path'          => $file_path,
				'full_path'     => $full_path,
				'size'          => $file_size,
				'size_formatted' => size_format( $file_size ),
				'extension'     => strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) ),
				'mime_type'     => $this->get_mime_type( $full_path ),
				'last_modified' => filemtime( $full_path ),
				'last_modified_formatted' => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), filemtime( $full_path ) ),
				'content'       => $content,
				'line_count'    => substr_count( $content, "\n" ) + 1,
				'encoding'      => $this->detect_encoding( $content ),
			);

			// Log the file access
			$this->log_file_access( $file_path, 'read', true, get_current_user_id() );

			return new WP_REST_Response( $file_info, 200 );

		} catch ( Exception $e ) {
			$this->log_file_access( $file_path, 'read', false, get_current_user_id(), $e->getMessage() );

			return new WP_Error(
				'file_read_failed',
				sprintf(
					/* translators: %s: error message */
					esc_html__( 'Failed to read file: %s', 'gemini-bridge' ),
					$e->getMessage()
				),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * List files in theme directory.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request The REST request object.
	 * @return WP_REST_Response|WP_Error The response object or WP_Error on failure.
	 */
	public function list_theme_files( $request ) {
		$sub_path = $request->get_param( 'path' );
		$theme_dir = get_stylesheet_directory();
		$scan_dir = empty( $sub_path ) ? $theme_dir : $theme_dir . '/' . $sub_path;

		try {
			if ( ! is_dir( $scan_dir ) ) {
				return new WP_Error(
					'directory_not_found',
					esc_html__( 'Directory not found.', 'gemini-bridge' ),
					array( 'status' => 404 )
				);
			}

			$files = array();
			$directories = array();

			$items = scandir( $scan_dir );
			if ( $items === false ) {
				return new WP_Error(
					'directory_read_error',
					esc_html__( 'Failed to read directory.', 'gemini-bridge' ),
					array( 'status' => 500 )
				);
			}

			foreach ( $items as $item ) {
				// Skip hidden files and parent directory references
				if ( $item === '.' || $item === '..' || strpos( $item, '.' ) === 0 ) {
					continue;
				}

				$item_path = $scan_dir . '/' . $item;
				$relative_path = empty( $sub_path ) ? $item : $sub_path . '/' . $item;

				if ( is_dir( $item_path ) ) {
					$directories[] = array(
						'name' => $item,
						'path' => $relative_path,
						'type' => 'directory',
						'last_modified' => filemtime( $item_path ),
						'last_modified_formatted' => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), filemtime( $item_path ) ),
					);
				} elseif ( is_file( $item_path ) ) {
					$extension = strtolower( pathinfo( $item, PATHINFO_EXTENSION ) );
					$file_size = filesize( $item_path );
					
					$files[] = array(
						'name' => $item,
						'path' => $relative_path,
						'type' => 'file',
						'extension' => $extension,
						'size' => $file_size,
						'size_formatted' => size_format( $file_size ),
						'mime_type' => $this->get_mime_type( $item_path ),
						'readable' => in_array( $extension, $this->allowed_extensions, true ) && $file_size <= $this->max_file_size,
						'last_modified' => filemtime( $item_path ),
						'last_modified_formatted' => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), filemtime( $item_path ) ),
					);
				}
			}

			// Sort directories and files alphabetically
			usort( $directories, function( $a, $b ) {
				return strcmp( $a['name'], $b['name'] );
			});

			usort( $files, function( $a, $b ) {
				return strcmp( $a['name'], $b['name'] );
			});

			$result = array(
				'current_path' => $sub_path,
				'theme_name' => wp_get_theme()->get( 'Name' ),
				'theme_version' => wp_get_theme()->get( 'Version' ),
				'directories' => $directories,
				'files' => $files,
				'total_directories' => count( $directories ),
				'total_files' => count( $files ),
				'allowed_extensions' => $this->allowed_extensions,
				'max_file_size' => $this->max_file_size,
				'max_file_size_formatted' => size_format( $this->max_file_size ),
			);

			// Log the directory access
			$this->log_file_access( $sub_path ?: '/', 'list', true, get_current_user_id() );

			return new WP_REST_Response( $result, 200 );

		} catch ( Exception $e ) {
			$this->log_file_access( $sub_path ?: '/', 'list', false, get_current_user_id(), $e->getMessage() );

			return new WP_Error(
				'directory_list_failed',
				sprintf(
					/* translators: %s: error message */
					esc_html__( 'Failed to list directory: %s', 'gemini-bridge' ),
					$e->getMessage()
				),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Get MIME type for a file.
	 *
	 * @since 1.0.0
	 * @param string $file_path The file path.
	 * @return string The MIME type.
	 */
	private function get_mime_type( $file_path ) {
		$extension = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );
		
		$mime_types = array(
			'php'  => 'application/x-php',
			'css'  => 'text/css',
			'js'   => 'application/javascript',
			'json' => 'application/json',
			'txt'  => 'text/plain',
			'md'   => 'text/markdown',
			'html' => 'text/html',
			'htm'  => 'text/html',
			'xml'  => 'application/xml',
			'scss' => 'text/x-scss',
			'sass' => 'text/x-sass',
			'less' => 'text/x-less',
		);

		if ( isset( $mime_types[ $extension ] ) ) {
			return $mime_types[ $extension ];
		}

		// Fallback to WordPress function if available
		if ( function_exists( 'mime_content_type' ) && file_exists( $file_path ) ) {
			$mime = mime_content_type( $file_path );
			if ( $mime ) {
				return $mime;
			}
		}

		return 'application/octet-stream';
	}

	/**
	 * Detect file encoding.
	 *
	 * @since 1.0.0
	 * @param string $content The file content.
	 * @return string The detected encoding.
	 */
	private function detect_encoding( $content ) {
		if ( function_exists( 'mb_detect_encoding' ) ) {
			$encoding = mb_detect_encoding( $content, array( 'UTF-8', 'ISO-8859-1', 'ASCII' ), true );
			return $encoding ?: 'unknown';
		}

		// Simple UTF-8 detection
		if ( mb_check_encoding( $content, 'UTF-8' ) ) {
			return 'UTF-8';
		}

		return 'unknown';
	}

	/**
	 * Log file access operations.
	 *
	 * @since 1.0.0
	 * @param string $file_path The file path accessed.
	 * @param string $operation The operation performed (read, list).
	 * @param bool   $success Whether the operation was successful.
	 * @param int    $user_id The user ID who performed the operation.
	 * @param string $error_message Optional error message.
	 */
	private function log_file_access( $file_path, $operation, $success, $user_id, $error_message = '' ) {
		$log_entry = array(
			'file_path'    => sanitize_text_field( $file_path ),
			'operation'    => sanitize_text_field( $operation ),
			'success'      => (bool) $success,
			'user_id'      => intval( $user_id ),
			'timestamp'    => time(),
			'ip'           => $this->get_client_ip(),
			'user_agent'   => sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ),
			'error_message' => sanitize_text_field( $error_message ),
		);

		// Store in transient for recent file accesses (24 hours)
		$recent_accesses = get_transient( 'gemini_bridge_file_access_log' ) ?: array();
		$recent_accesses[] = $log_entry;

		// Keep only last 100 accesses
		if ( count( $recent_accesses ) > 100 ) {
			$recent_accesses = array_slice( $recent_accesses, -100 );
		}

		set_transient( 'gemini_bridge_file_access_log', $recent_accesses, DAY_IN_SECONDS );
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