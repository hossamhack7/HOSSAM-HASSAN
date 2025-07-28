<?php
/**
 * System Health class.
 * Performs system health checks and diagnostics.
 *
 * @package GeminiCommandCenter
 */

class Gemini_CC_System_Health {

    /**
     * Run complete health check.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response
     */
    public function run_health_check($request) {
        $checks = array(
            'wordpress' => $this->check_wordpress_health(),
            'php' => $this->check_php_health(),
            'database' => $this->check_database_health(),
            'filesystem' => $this->check_filesystem_health(),
            'gemini_api' => $this->check_gemini_api_health(),
            'integrations' => $this->check_integrations_health(),
            'security' => $this->check_security_health(),
            'performance' => $this->check_performance_health(),
        );

        $overall_status = $this->calculate_overall_status($checks);

        return new WP_REST_Response(array(
            'overall_status' => $overall_status,
            'checks' => $checks,
            'timestamp' => current_time('c'),
        ), 200);
    }

    /**
     * Check WordPress health.
     *
     * @return array Health check results.
     */
    private function check_wordpress_health() {
        global $wp_version;
        
        $checks = array();

        // WordPress version
        $latest_version = $this->get_latest_wp_version();
        $version_status = version_compare($wp_version, $latest_version, '>=') ? 'good' : 'warning';
        
        $checks['version'] = array(
            'status' => $version_status,
            'message' => sprintf(
                __('WordPress %s (Latest: %s)', 'gemini-command-center'),
                $wp_version,
                $latest_version
            ),
            'current' => $wp_version,
            'latest' => $latest_version,
        );

        // Plugin compatibility
        $active_plugins = get_option('active_plugins', array());
        $checks['active_plugins'] = array(
            'status' => 'good',
            'message' => sprintf(
                __('%d active plugins', 'gemini-command-center'),
                count($active_plugins)
            ),
            'count' => count($active_plugins),
        );

        // Theme health
        $current_theme = wp_get_theme();
        $checks['theme'] = array(
            'status' => 'good',
            'message' => sprintf(
                __('Theme: %s v%s', 'gemini-command-center'),
                $current_theme->get('Name'),
                $current_theme->get('Version')
            ),
            'name' => $current_theme->get('Name'),
            'version' => $current_theme->get('Version'),
        );

        // WordPress constants
        $checks['debug_mode'] = array(
            'status' => defined('WP_DEBUG') && WP_DEBUG ? 'warning' : 'good',
            'message' => defined('WP_DEBUG') && WP_DEBUG ? 
                __('Debug mode is enabled', 'gemini-command-center') : 
                __('Debug mode is disabled', 'gemini-command-center'),
            'enabled' => defined('WP_DEBUG') && WP_DEBUG,
        );

        return $checks;
    }

    /**
     * Check PHP health.
     *
     * @return array Health check results.
     */
    private function check_php_health() {
        $checks = array();

        // PHP version
        $php_version = PHP_VERSION;
        $min_version = '7.4';
        $recommended_version = '8.1';
        
        $version_status = 'good';
        if (version_compare($php_version, $min_version, '<')) {
            $version_status = 'critical';
        } elseif (version_compare($php_version, $recommended_version, '<')) {
            $version_status = 'warning';
        }

        $checks['version'] = array(
            'status' => $version_status,
            'message' => sprintf(
                __('PHP %s (Recommended: %s+)', 'gemini-command-center'),
                $php_version,
                $recommended_version
            ),
            'current' => $php_version,
            'recommended' => $recommended_version,
        );

        // Memory limit
        $memory_limit = ini_get('memory_limit');
        $memory_bytes = $this->parse_memory_limit($memory_limit);
        $recommended_memory = 256 * 1024 * 1024; // 256MB

        $memory_status = $memory_bytes >= $recommended_memory ? 'good' : 'warning';
        
        $checks['memory_limit'] = array(
            'status' => $memory_status,
            'message' => sprintf(
                __('Memory limit: %s (Recommended: 256M+)', 'gemini-command-center'),
                $memory_limit
            ),
            'current' => $memory_limit,
            'bytes' => $memory_bytes,
        );

        // Max execution time
        $max_execution_time = ini_get('max_execution_time');
        $execution_status = $max_execution_time >= 30 ? 'good' : 'warning';
        
        $checks['max_execution_time'] = array(
            'status' => $execution_status,
            'message' => sprintf(
                __('Max execution time: %ds (Recommended: 30s+)', 'gemini-command-center'),
                $max_execution_time
            ),
            'current' => $max_execution_time,
        );

        // Required extensions
        $required_extensions = array('curl', 'json', 'mbstring', 'openssl', 'zip');
        $missing_extensions = array();
        
        foreach ($required_extensions as $extension) {
            if (!extension_loaded($extension)) {
                $missing_extensions[] = $extension;
            }
        }
        
        $checks['extensions'] = array(
            'status' => empty($missing_extensions) ? 'good' : 'critical',
            'message' => empty($missing_extensions) ? 
                __('All required extensions are loaded', 'gemini-command-center') :
                sprintf(
                    __('Missing extensions: %s', 'gemini-command-center'),
                    implode(', ', $missing_extensions)
                ),
            'missing' => $missing_extensions,
        );

        return $checks;
    }

    /**
     * Check database health.
     *
     * @return array Health check results.
     */
    private function check_database_health() {
        global $wpdb;
        
        $checks = array();

        // Database connection
        $db_connection = $wpdb->check_connection();
        $checks['connection'] = array(
            'status' => $db_connection ? 'good' : 'critical',
            'message' => $db_connection ? 
                __('Database connection is working', 'gemini-command-center') :
                __('Database connection failed', 'gemini-command-center'),
        );

        // Database version
        $db_version = $wpdb->get_var('SELECT VERSION()');
        $checks['version'] = array(
            'status' => 'good',
            'message' => sprintf(
                __('Database version: %s', 'gemini-command-center'),
                $db_version
            ),
            'version' => $db_version,
        );

        // Table status
        $tables = $wpdb->get_results('SHOW TABLE STATUS', ARRAY_A);
        $total_size = 0;
        $table_count = 0;
        
        foreach ($tables as $table) {
            if (strpos($table['Name'], $wpdb->prefix) === 0) {
                $total_size += $table['Data_length'] + $table['Index_length'];
                $table_count++;
            }
        }
        
        $checks['tables'] = array(
            'status' => 'good',
            'message' => sprintf(
                __('%d tables, %s total size', 'gemini-command-center'),
                $table_count,
                size_format($total_size)
            ),
            'count' => $table_count,
            'size' => $total_size,
        );

        return $checks;
    }

    /**
     * Check filesystem health.
     *
     * @return array Health check results.
     */
    private function check_filesystem_health() {
        $checks = array();

        // WordPress uploads directory
        $upload_dir = wp_upload_dir();
        $uploads_writable = wp_is_writable($upload_dir['basedir']);
        
        $checks['uploads_writable'] = array(
            'status' => $uploads_writable ? 'good' : 'critical',
            'message' => $uploads_writable ? 
                __('Uploads directory is writable', 'gemini-command-center') :
                __('Uploads directory is not writable', 'gemini-command-center'),
            'path' => $upload_dir['basedir'],
        );

        // Backup directory
        $backup_dir = $upload_dir['basedir'] . '/gemini-cc-backups';
        $backup_writable = wp_is_writable($backup_dir);
        
        $checks['backup_directory'] = array(
            'status' => $backup_writable ? 'good' : 'warning',
            'message' => $backup_writable ? 
                __('Backup directory is writable', 'gemini-command-center') :
                __('Backup directory is not writable', 'gemini-command-center'),
            'path' => $backup_dir,
        );

        // Disk space
        $free_space = disk_free_space(ABSPATH);
        $total_space = disk_total_space(ABSPATH);
        $used_percentage = (($total_space - $free_space) / $total_space) * 100;
        
        $space_status = 'good';
        if ($used_percentage > 90) {
            $space_status = 'critical';
        } elseif ($used_percentage > 80) {
            $space_status = 'warning';
        }
        
        $checks['disk_space'] = array(
            'status' => $space_status,
            'message' => sprintf(
                __('Disk usage: %.1f%% (%s free)', 'gemini-command-center'),
                $used_percentage,
                size_format($free_space)
            ),
            'free_space' => $free_space,
            'total_space' => $total_space,
            'used_percentage' => $used_percentage,
        );

        return $checks;
    }

    /**
     * Check Gemini API health.
     *
     * @return array Health check results.
     */
    private function check_gemini_api_health() {
        $checks = array();
        
        $settings = get_option('gemini_cc_settings', array());
        $api_key = isset($settings['general']['api_key']) ? $settings['general']['api_key'] : '';
        
        if (empty($api_key)) {
            $checks['api_key'] = array(
                'status' => 'warning',
                'message' => __('Gemini API key not configured', 'gemini-command-center'),
            );
            
            $checks['connection'] = array(
                'status' => 'warning',
                'message' => __('Cannot test connection without API key', 'gemini-command-center'),
            );
        } else {
            $checks['api_key'] = array(
                'status' => 'good',
                'message' => __('Gemini API key is configured', 'gemini-command-center'),
            );
            
            // Test API connection
            require_once GEMINI_CC_INCLUDES_DIR . 'class-gemini-api.php';
            $gemini_api = new Gemini_CC_API($api_key);
            $connection_test = $gemini_api->test_connection();
            
            $checks['connection'] = array(
                'status' => $connection_test['success'] ? 'good' : 'critical',
                'message' => $connection_test['success'] ? 
                    __('Gemini API connection is working', 'gemini-command-center') :
                    sprintf(
                        __('Gemini API connection failed: %s', 'gemini-command-center'),
                        $connection_test['message']
                    ),
            );
        }

        // Rate limiting status
        require_once GEMINI_CC_INCLUDES_DIR . 'class-rate-limiter.php';
        $rate_limiter = new Gemini_CC_Rate_Limiter();
        $rate_status = $rate_limiter->get_status();
        
        $rate_limit_status = 'good';
        if ($rate_status['remaining'] < 3) {
            $rate_limit_status = 'warning';
        }
        
        $checks['rate_limiting'] = array(
            'status' => $rate_limit_status,
            'message' => sprintf(
                __('API rate limit: %d/%d requests remaining', 'gemini-command-center'),
                $rate_status['remaining'],
                $rate_status['limit']
            ),
            'remaining' => $rate_status['remaining'],
            'limit' => $rate_status['limit'],
        );

        return $checks;
    }

    /**
     * Check integrations health.
     *
     * @return array Health check results.
     */
    private function check_integrations_health() {
        $checks = array();

        // Site Kit integration
        $site_kit_active = is_plugin_active('google-site-kit/google-site-kit.php');
        $checks['site_kit'] = array(
            'status' => $site_kit_active ? 'good' : 'info',
            'message' => $site_kit_active ? 
                __('Google Site Kit is active', 'gemini-command-center') :
                __('Google Site Kit is not installed', 'gemini-command-center'),
            'active' => $site_kit_active,
        );

        // SEO plugins
        $seo_plugins = array(
            'yoast' => 'wordpress-seo/wp-seo.php',
            'rankmath' => 'seo-by-rankmath/rank-math.php',
            'aioseo' => 'all-in-one-seo-pack/all_in_one_seo_pack.php',
        );
        
        $active_seo_plugins = array();
        foreach ($seo_plugins as $name => $plugin_file) {
            if (is_plugin_active($plugin_file)) {
                $active_seo_plugins[] = $name;
            }
        }
        
        $checks['seo_plugins'] = array(
            'status' => 'info',
            'message' => empty($active_seo_plugins) ? 
                __('No SEO plugins detected', 'gemini-command-center') :
                sprintf(
                    __('SEO plugins: %s', 'gemini-command-center'),
                    implode(', ', $active_seo_plugins)
                ),
            'active' => $active_seo_plugins,
        );

        return $checks;
    }

    /**
     * Check security health.
     *
     * @return array Health check results.
     */
    private function check_security_health() {
        $checks = array();

        // HTTPS
        $is_ssl = is_ssl();
        $checks['https'] = array(
            'status' => $is_ssl ? 'good' : 'warning',
            'message' => $is_ssl ? 
                __('Site is using HTTPS', 'gemini-command-center') :
                __('Site is not using HTTPS', 'gemini-command-center'),
        );

        // WordPress salts
        $salts = array('AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY');
        $empty_salts = 0;
        
        foreach ($salts as $salt) {
            if (!defined($salt) || constant($salt) === 'put your unique phrase here') {
                $empty_salts++;
            }
        }
        
        $checks['salts'] = array(
            'status' => $empty_salts === 0 ? 'good' : 'warning',
            'message' => $empty_salts === 0 ? 
                __('WordPress security salts are configured', 'gemini-command-center') :
                sprintf(
                    __('%d security salts need to be configured', 'gemini-command-center'),
                    $empty_salts
                ),
        );

        // File permissions
        $critical_files = array(
            'wp-config.php' => ABSPATH . 'wp-config.php',
            '.htaccess' => ABSPATH . '.htaccess',
        );
        
        $permission_issues = array();
        foreach ($critical_files as $name => $file) {
            if (file_exists($file)) {
                $perms = fileperms($file) & 0777;
                if ($perms > 0644) {
                    $permission_issues[] = $name;
                }
            }
        }
        
        $checks['file_permissions'] = array(
            'status' => empty($permission_issues) ? 'good' : 'warning',
            'message' => empty($permission_issues) ? 
                __('File permissions are secure', 'gemini-command-center') :
                sprintf(
                    __('File permission issues: %s', 'gemini-command-center'),
                    implode(', ', $permission_issues)
                ),
        );

        return $checks;
    }

    /**
     * Check performance health.
     *
     * @return array Health check results.
     */
    private function check_performance_health() {
        $checks = array();

        // Object cache
        $object_cache_active = wp_using_ext_object_cache();
        $checks['object_cache'] = array(
            'status' => $object_cache_active ? 'good' : 'info',
            'message' => $object_cache_active ? 
                __('Object cache is active', 'gemini-command-center') :
                __('Object cache is not active', 'gemini-command-center'),
        );

        // Database queries
        $query_count = get_num_queries();
        $query_status = 'good';
        if ($query_count > 100) {
            $query_status = 'warning';
        } elseif ($query_count > 200) {
            $query_status = 'critical';
        }
        
        $checks['database_queries'] = array(
            'status' => $query_status,
            'message' => sprintf(
                __('%d database queries', 'gemini-command-center'),
                $query_count
            ),
            'count' => $query_count,
        );

        // Plugin count
        $active_plugins = get_option('active_plugins', array());
        $plugin_count = count($active_plugins);
        
        $plugin_status = 'good';
        if ($plugin_count > 30) {
            $plugin_status = 'warning';
        } elseif ($plugin_count > 50) {
            $plugin_status = 'critical';
        }
        
        $checks['plugin_count'] = array(
            'status' => $plugin_status,
            'message' => sprintf(
                __('%d active plugins', 'gemini-command-center'),
                $plugin_count
            ),
            'count' => $plugin_count,
        );

        return $checks;
    }

    /**
     * Calculate overall status from individual checks.
     *
     * @param array $checks All health check results.
     * @return string Overall status.
     */
    private function calculate_overall_status($checks) {
        $has_critical = false;
        $has_warning = false;
        
        foreach ($checks as $category => $category_checks) {
            foreach ($category_checks as $check) {
                if ($check['status'] === 'critical') {
                    $has_critical = true;
                }
                if ($check['status'] === 'warning') {
                    $has_warning = true;
                }
            }
        }
        
        if ($has_critical) {
            return 'critical';
        }
        
        if ($has_warning) {
            return 'warning';
        }
        
        return 'good';
    }

    /**
     * Get latest WordPress version.
     *
     * @return string Latest WordPress version.
     */
    private function get_latest_wp_version() {
        $version_check = get_site_transient('update_core');
        
        if ($version_check && isset($version_check->updates[0]->version)) {
            return $version_check->updates[0]->version;
        }
        
        // Fallback to current version if update check not available
        global $wp_version;
        return $wp_version;
    }

    /**
     * Parse memory limit string to bytes.
     *
     * @param string $limit Memory limit string.
     * @return int Memory limit in bytes.
     */
    private function parse_memory_limit($limit) {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $number = intval($limit);
        
        switch ($last) {
            case 'g':
                $number *= 1024;
            case 'm':
                $number *= 1024;
            case 'k':
                $number *= 1024;
        }
        
        return $number;
    }
}