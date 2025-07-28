<?php
/**
 * Security Manager class.
 * Handles security-related functionality.
 *
 * @package GeminiCommandCenter
 */

class Gemini_CC_Security_Manager {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action('init', array($this, 'init_security_features'));
        add_action('wp_ajax_gemini_cc_verify_nonce', array($this, 'verify_nonce_ajax'));
    }

    /**
     * Initialize security features.
     */
    public function init_security_features() {
        // Add security headers
        add_action('send_headers', array($this, 'add_security_headers'));
        
        // Validate all AJAX requests
        add_action('wp_ajax_nopriv_gemini_cc_*', array($this, 'block_non_authenticated_requests'), 1);
    }

    /**
     * Add security headers.
     */
    public function add_security_headers() {
        // Only on plugin pages
        if (!$this->is_plugin_page()) {
            return;
        }

        // Content Security Policy
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    /**
     * Check if current page is a plugin page.
     *
     * @return bool True if plugin page.
     */
    private function is_plugin_page() {
        global $pagenow;
        
        if ($pagenow !== 'admin.php') {
            return false;
        }
        
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        $plugin_pages = array(
            'gemini-command-center',
            'gemini-ai-agent',
            'gemini-settings',
        );
        
        return in_array($page, $plugin_pages, true);
    }

    /**
     * Block non-authenticated requests to plugin AJAX endpoints.
     */
    public function block_non_authenticated_requests() {
        wp_die(
            __('Authentication required.', 'gemini-command-center'),
            __('Access Denied', 'gemini-command-center'),
            array('response' => 403)
        );
    }

    /**
     * Create a secure nonce for specific action.
     *
     * @param string $action Action name.
     * @return string Nonce.
     */
    public function create_nonce($action) {
        return wp_create_nonce('gemini_cc_' . $action);
    }

    /**
     * Verify nonce for specific action.
     *
     * @param string $nonce Nonce to verify.
     * @param string $action Action name.
     * @return bool True if valid.
     */
    public function verify_nonce($nonce, $action) {
        return wp_verify_nonce($nonce, 'gemini_cc_' . $action);
    }

    /**
     * Verify REST API nonce.
     *
     * @param WP_REST_Request $request Request object.
     * @return bool True if valid.
     */
    public function verify_rest_nonce($request) {
        $nonce = $request->get_header('X-WP-Nonce');
        
        if (empty($nonce)) {
            $nonce = $request->get_param('_wpnonce');
        }
        
        return wp_verify_nonce($nonce, 'wp_rest');
    }

    /**
     * Check if user has required capability.
     *
     * @param string $capability Required capability.
     * @return bool True if user has capability.
     */
    public function check_capability($capability = 'manage_options') {
        return current_user_can($capability);
    }

    /**
     * Sanitize API key.
     *
     * @param string $api_key Raw API key.
     * @return string Sanitized API key.
     */
    public function sanitize_api_key($api_key) {
        // Remove any whitespace and special characters except alphanumeric, hyphens, and underscores
        $sanitized = preg_replace('/[^a-zA-Z0-9\-_]/', '', trim($api_key));
        
        // Validate length (Gemini API keys are typically 39 characters)
        if (strlen($sanitized) < 20 || strlen($sanitized) > 100) {
            return '';
        }
        
        return $sanitized;
    }

    /**
     * Mask API key for display.
     *
     * @param string $api_key API key to mask.
     * @return string Masked API key.
     */
    public function mask_api_key($api_key) {
        if (empty($api_key)) {
            return '';
        }
        
        $length = strlen($api_key);
        if ($length <= 8) {
            return str_repeat('*', $length);
        }
        
        $visible_start = 4;
        $visible_end = 4;
        $masked_length = $length - $visible_start - $visible_end;
        
        return substr($api_key, 0, $visible_start) . 
               str_repeat('*', $masked_length) . 
               substr($api_key, -$visible_end);
    }

    /**
     * Validate and sanitize URL.
     *
     * @param string $url URL to validate.
     * @return string|false Sanitized URL or false if invalid.
     */
    public function validate_url($url) {
        $sanitized_url = esc_url_raw($url);
        
        // Check if URL is valid
        if (!filter_var($sanitized_url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        // Check if URL uses HTTPS (recommended)
        $scheme = parse_url($sanitized_url, PHP_URL_SCHEME);
        if ($scheme !== 'https' && $scheme !== 'http') {
            return false;
        }
        
        return $sanitized_url;
    }

    /**
     * Sanitize and validate email address.
     *
     * @param string $email Email to validate.
     * @return string|false Sanitized email or false if invalid.
     */
    public function validate_email($email) {
        $sanitized_email = sanitize_email($email);
        
        if (!is_email($sanitized_email)) {
            return false;
        }
        
        return $sanitized_email;
    }

    /**
     * Generate a secure random token.
     *
     * @param int $length Token length.
     * @return string Random token.
     */
    public function generate_token($length = 32) {
        if (function_exists('wp_generate_password')) {
            return wp_generate_password($length, false);
        }
        
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Hash sensitive data.
     *
     * @param string $data Data to hash.
     * @param string $salt Optional salt.
     * @return string Hashed data.
     */
    public function hash_data($data, $salt = '') {
        if (empty($salt)) {
            $salt = wp_salt('auth');
        }
        
        return wp_hash($data . $salt);
    }

    /**
     * Encrypt sensitive data.
     *
     * @param string $data Data to encrypt.
     * @return string Encrypted data.
     */
    public function encrypt_data($data) {
        if (!function_exists('openssl_encrypt')) {
            // Fallback to base64 encoding (not secure, but better than nothing)
            return base64_encode($data);
        }
        
        $method = 'AES-256-CBC';
        $key = $this->get_encryption_key();
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
        
        $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt sensitive data.
     *
     * @param string $encrypted_data Encrypted data.
     * @return string|false Decrypted data or false on failure.
     */
    public function decrypt_data($encrypted_data) {
        if (!function_exists('openssl_decrypt')) {
            // Fallback from base64 encoding
            return base64_decode($encrypted_data);
        }
        
        $data = base64_decode($encrypted_data);
        $method = 'AES-256-CBC';
        $key = $this->get_encryption_key();
        $iv_length = openssl_cipher_iv_length($method);
        
        if (strlen($data) < $iv_length) {
            return false;
        }
        
        $iv = substr($data, 0, $iv_length);
        $encrypted = substr($data, $iv_length);
        
        return openssl_decrypt($encrypted, $method, $key, 0, $iv);
    }

    /**
     * Get encryption key.
     *
     * @return string Encryption key.
     */
    private function get_encryption_key() {
        $key = get_option('gemini_cc_encryption_key');
        
        if (empty($key)) {
            $key = $this->generate_token(64);
            update_option('gemini_cc_encryption_key', $key);
        }
        
        // Combine with WordPress salts for additional security
        return hash('sha256', $key . wp_salt('secure_auth'));
    }

    /**
     * Validate file upload.
     *
     * @param array $file File upload array.
     * @param array $allowed_types Allowed MIME types.
     * @param int   $max_size Maximum file size in bytes.
     * @return array Validation result.
     */
    public function validate_file_upload($file, $allowed_types = array(), $max_size = 2097152) {
        $result = array(
            'valid' => false,
            'message' => '',
            'file' => null,
        );
        
        // Check for upload errors
        if (!isset($file['error']) || is_array($file['error'])) {
            $result['message'] = __('Invalid file upload.', 'gemini-command-center');
            return $result;
        }
        
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                $result['message'] = __('No file was uploaded.', 'gemini-command-center');
                return $result;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $result['message'] = __('File is too large.', 'gemini-command-center');
                return $result;
            default:
                $result['message'] = __('Unknown file upload error.', 'gemini-command-center');
                return $result;
        }
        
        // Check file size
        if ($file['size'] > $max_size) {
            $result['message'] = sprintf(
                __('File size (%s) exceeds maximum allowed size (%s).', 'gemini-command-center'),
                size_format($file['size']),
                size_format($max_size)
            );
            return $result;
        }
        
        // Check MIME type
        if (!empty($allowed_types)) {
            $file_type = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
            
            if (!in_array($file_type['type'], $allowed_types, true)) {
                $result['message'] = __('File type not allowed.', 'gemini-command-center');
                return $result;
            }
        }
        
        // Additional security checks
        if (!is_uploaded_file($file['tmp_name'])) {
            $result['message'] = __('Security check failed.', 'gemini-command-center');
            return $result;
        }
        
        $result['valid'] = true;
        $result['file'] = $file;
        
        return $result;
    }

    /**
     * Log security event.
     *
     * @param string $event Event type.
     * @param array  $data Event data.
     */
    public function log_security_event($event, $data = array()) {
        if (class_exists('Gemini_CC_Logger')) {
            $logger = new Gemini_CC_Logger();
            
            $log_data = array_merge($data, array(
                'event_type' => $event,
                'user_id' => get_current_user_id(),
                'ip_address' => $this->get_client_ip(),
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
                'timestamp' => current_time('c'),
            ));
            
            $logger->warning('Security Event: ' . $event, $log_data);
        }
    }

    /**
     * Get client IP address.
     *
     * @return string IP address.
     */
    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );

        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                
                $ip = trim($ip);
                
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }

    /**
     * Check for suspicious activity.
     *
     * @param string $action Action being performed.
     * @return bool True if suspicious.
     */
    public function check_suspicious_activity($action) {
        $user_id = get_current_user_id();
        $ip = $this->get_client_ip();
        $key = 'gemini_cc_activity_' . md5($user_id . '_' . $ip . '_' . $action);
        
        $attempts = get_transient($key);
        $max_attempts = 10; // Max attempts per hour
        
        if ($attempts === false) {
            set_transient($key, 1, HOUR_IN_SECONDS);
            return false;
        }
        
        if ($attempts >= $max_attempts) {
            $this->log_security_event('suspicious_activity', array(
                'action' => $action,
                'attempts' => $attempts,
                'max_attempts' => $max_attempts,
            ));
            return true;
        }
        
        set_transient($key, $attempts + 1, HOUR_IN_SECONDS);
        return false;
    }

    /**
     * AJAX handler for nonce verification.
     */
    public function verify_nonce_ajax() {
        $action = isset($_POST['action_name']) ? sanitize_text_field($_POST['action_name']) : '';
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        
        if (empty($action) || empty($nonce)) {
            wp_send_json_error(array(
                'message' => __('Invalid request.', 'gemini-command-center'),
            ));
        }
        
        if (!$this->verify_nonce($nonce, $action)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'gemini-command-center'),
            ));
        }
        
        wp_send_json_success(array(
            'message' => __('Nonce verified.', 'gemini-command-center'),
        ));
    }

    /**
     * Clean up expired security data.
     */
    public function cleanup_security_data() {
        global $wpdb;
        
        // Clean up old activity tracking transients
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE %s 
                AND option_value < %d",
                $wpdb->esc_like('_transient_timeout_gemini_cc_activity_') . '%',
                time()
            )
        );
        
        // Clean up old nonce data
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE %s 
                AND option_value < %d",
                $wpdb->esc_like('_transient_timeout_gemini_cc_nonce_') . '%',
                time()
            )
        );
    }
}