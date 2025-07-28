<?php
/**
 * Rate Limiter class.
 * Handles API rate limiting to prevent abuse.
 *
 * @package GeminiCommandCenter
 */

class Gemini_CC_Rate_Limiter {

    /**
     * Default rate limit (requests per minute).
     */
    const DEFAULT_RATE_LIMIT = 15;

    /**
     * Rate limit window in seconds.
     */
    const RATE_WINDOW = 60;

    /**
     * Transient key prefix.
     */
    const TRANSIENT_PREFIX = 'gemini_cc_rate_limit_';

    /**
     * Check if a request is allowed.
     *
     * @param string $identifier Optional identifier (defaults to current user).
     * @return bool True if allowed, false if rate limited.
     */
    public function is_allowed($identifier = null) {
        if ($identifier === null) {
            $identifier = $this->get_default_identifier();
        }

        $key = self::TRANSIENT_PREFIX . md5($identifier);
        $requests = get_transient($key);

        if ($requests === false) {
            // No requests in current window
            return true;
        }

        $rate_limit = $this->get_rate_limit();
        
        return count($requests) < $rate_limit;
    }

    /**
     * Record a request.
     *
     * @param string $identifier Optional identifier (defaults to current user).
     */
    public function record_request($identifier = null) {
        if ($identifier === null) {
            $identifier = $this->get_default_identifier();
        }

        $key = self::TRANSIENT_PREFIX . md5($identifier);
        $requests = get_transient($key);

        if ($requests === false) {
            $requests = array();
        }

        // Clean old requests outside the window
        $current_time = time();
        $requests = array_filter($requests, function($timestamp) use ($current_time) {
            return ($current_time - $timestamp) < self::RATE_WINDOW;
        });

        // Add current request
        $requests[] = $current_time;

        // Store for the rate window duration
        set_transient($key, $requests, self::RATE_WINDOW);
    }

    /**
     * Get remaining requests for identifier.
     *
     * @param string $identifier Optional identifier.
     * @return int Number of requests remaining.
     */
    public function get_remaining_requests($identifier = null) {
        if ($identifier === null) {
            $identifier = $this->get_default_identifier();
        }

        $key = self::TRANSIENT_PREFIX . md5($identifier);
        $requests = get_transient($key);

        if ($requests === false) {
            return $this->get_rate_limit();
        }

        $rate_limit = $this->get_rate_limit();
        $used_requests = count($requests);

        return max(0, $rate_limit - $used_requests);
    }

    /**
     * Get time until next request is allowed.
     *
     * @param string $identifier Optional identifier.
     * @return int Seconds until next request allowed, 0 if allowed now.
     */
    public function get_retry_after($identifier = null) {
        if ($this->is_allowed($identifier)) {
            return 0;
        }

        if ($identifier === null) {
            $identifier = $this->get_default_identifier();
        }

        $key = self::TRANSIENT_PREFIX . md5($identifier);
        $requests = get_transient($key);

        if (empty($requests)) {
            return 0;
        }

        // Find the oldest request in the current window
        $oldest_request = min($requests);
        $window_end = $oldest_request + self::RATE_WINDOW;
        
        return max(0, $window_end - time());
    }

    /**
     * Reset rate limit for identifier.
     *
     * @param string $identifier Optional identifier.
     */
    public function reset($identifier = null) {
        if ($identifier === null) {
            $identifier = $this->get_default_identifier();
        }

        $key = self::TRANSIENT_PREFIX . md5($identifier);
        delete_transient($key);
    }

    /**
     * Get rate limit status for identifier.
     *
     * @param string $identifier Optional identifier.
     * @return array Status information.
     */
    public function get_status($identifier = null) {
        if ($identifier === null) {
            $identifier = $this->get_default_identifier();
        }

        $key = self::TRANSIENT_PREFIX . md5($identifier);
        $requests = get_transient($key);
        $rate_limit = $this->get_rate_limit();

        if ($requests === false) {
            $requests = array();
        }

        // Clean old requests
        $current_time = time();
        $requests = array_filter($requests, function($timestamp) use ($current_time) {
            return ($current_time - $timestamp) < self::RATE_WINDOW;
        });

        $used_requests = count($requests);
        $remaining = max(0, $rate_limit - $used_requests);
        $retry_after = $this->get_retry_after($identifier);

        return array(
            'limit' => $rate_limit,
            'used' => $used_requests,
            'remaining' => $remaining,
            'reset_time' => $current_time + self::RATE_WINDOW,
            'retry_after' => $retry_after,
            'allowed' => $remaining > 0,
        );
    }

    /**
     * Get the default identifier (current user + IP).
     *
     * @return string Default identifier.
     */
    private function get_default_identifier() {
        $user_id = get_current_user_id();
        $ip_address = $this->get_client_ip();
        
        return $user_id . '_' . $ip_address;
    }

    /**
     * Get the current rate limit setting.
     *
     * @return int Rate limit per minute.
     */
    private function get_rate_limit() {
        $settings = get_option('gemini_cc_settings', array());
        
        if (isset($settings['system']['rate_limiting']['requests_per_minute'])) {
            return (int) $settings['system']['rate_limiting']['requests_per_minute'];
        }

        return self::DEFAULT_RATE_LIMIT;
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
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }

    /**
     * Clean up old rate limit data (called by cron).
     */
    public static function cleanup_old_data() {
        global $wpdb;

        // Clean up old transients
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE %s 
                AND option_value < %d",
                $wpdb->esc_like('_transient_timeout_' . self::TRANSIENT_PREFIX) . '%',
                time()
            )
        );
    }

    /**
     * Get global rate limit statistics.
     *
     * @return array Global statistics.
     */
    public function get_global_stats() {
        global $wpdb;

        // Count active rate limit entries
        $active_limits = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options} 
                WHERE option_name LIKE %s",
                $wpdb->esc_like('_transient_' . self::TRANSIENT_PREFIX) . '%'
            )
        );

        return array(
            'active_limits' => (int) $active_limits,
            'rate_limit' => $this->get_rate_limit(),
            'window_seconds' => self::RATE_WINDOW,
        );
    }
}