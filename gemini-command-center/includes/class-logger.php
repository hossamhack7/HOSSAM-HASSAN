<?php
/**
 * Logger class.
 * Handles system logging functionality.
 *
 * @package GeminiCommandCenter
 */

class Gemini_CC_Logger {

    /**
     * Log levels.
     */
    const LEVEL_ERROR = 'error';
    const LEVEL_WARNING = 'warning';
    const LEVEL_INFO = 'info';
    const LEVEL_DEBUG = 'debug';

    /**
     * Check if logging is enabled.
     *
     * @return bool True if logging is enabled.
     */
    public function is_enabled() {
        $settings = get_option('gemini_cc_settings', array());
        return !empty($settings['system']['logging']['enabled']);
    }

    /**
     * Log an error message.
     *
     * @param string $message Log message.
     * @param array  $context Additional context data.
     */
    public function error($message, $context = array()) {
        $this->log(self::LEVEL_ERROR, $message, $context);
    }

    /**
     * Log a warning message.
     *
     * @param string $message Log message.
     * @param array  $context Additional context data.
     */
    public function warning($message, $context = array()) {
        $this->log(self::LEVEL_WARNING, $message, $context);
    }

    /**
     * Log an info message.
     *
     * @param string $message Log message.
     * @param array  $context Additional context data.
     */
    public function info($message, $context = array()) {
        $this->log(self::LEVEL_INFO, $message, $context);
    }

    /**
     * Log a debug message.
     *
     * @param string $message Log message.
     * @param array  $context Additional context data.
     */
    public function debug($message, $context = array()) {
        // Only log debug messages in development mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->log(self::LEVEL_DEBUG, $message, $context);
        }
    }

    /**
     * Log a message to the database.
     *
     * @param string $level Log level.
     * @param string $message Log message.
     * @param array  $context Additional context data.
     */
    public function log($level, $message, $context = array()) {
        if (!$this->is_enabled()) {
            return;
        }

        global $wpdb;

        $table_name = GEMINI_CC_LOGS_TABLE;

        $data = array(
            'level' => sanitize_text_field($level),
            'message' => sanitize_textarea_field($message),
            'context' => wp_json_encode($context),
            'user_id' => get_current_user_id(),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
            'created_at' => current_time('mysql'),
        );

        $wpdb->insert($table_name, $data);

        // Also log to WordPress error log if it's an error
        if ($level === self::LEVEL_ERROR && defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Gemini CC Error: ' . $message . ' | Context: ' . wp_json_encode($context));
        }
    }

    /**
     * Get logs from the database.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response
     */
    public function get_logs($request) {
        global $wpdb;

        $table_name = GEMINI_CC_LOGS_TABLE;
        
        // Get parameters
        $page = max(1, (int) $request->get_param('page'));
        $per_page = min(100, max(10, (int) $request->get_param('per_page')));
        $level = $request->get_param('level');
        
        $offset = ($page - 1) * $per_page;

        // Build query
        $where_clause = '1=1';
        $where_values = array();

        if (!empty($level)) {
            $where_clause .= ' AND level = %s';
            $where_values[] = sanitize_text_field($level);
        }

        // Get total count
        $total_query = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_clause}";
        if (!empty($where_values)) {
            $total = $wpdb->get_var($wpdb->prepare($total_query, $where_values));
        } else {
            $total = $wpdb->get_var($total_query);
        }

        // Get logs
        $logs_query = "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $query_values = array_merge($where_values, array($per_page, $offset));
        
        $logs = $wpdb->get_results($wpdb->prepare($logs_query, $query_values));

        // Process logs
        $processed_logs = array();
        foreach ($logs as $log) {
            $processed_logs[] = array(
                'id' => (int) $log->id,
                'level' => $log->level,
                'message' => $log->message,
                'context' => json_decode($log->context, true),
                'user_id' => (int) $log->user_id,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'created_at' => $log->created_at,
                'formatted_date' => wp_date('Y-m-d H:i:s', strtotime($log->created_at)),
            );
        }

        return new WP_REST_Response(array(
            'logs' => $processed_logs,
            'pagination' => array(
                'page' => $page,
                'per_page' => $per_page,
                'total' => (int) $total,
                'total_pages' => ceil($total / $per_page),
            ),
        ), 200);
    }

    /**
     * Clear all logs.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response
     */
    public function clear_logs($request) {
        global $wpdb;

        $table_name = GEMINI_CC_LOGS_TABLE;
        
        $deleted = $wpdb->query("DELETE FROM {$table_name}");

        // Log the clearing action
        $this->info('System logs cleared', array(
            'deleted_count' => $deleted,
            'cleared_by' => get_current_user_id(),
        ));

        return new WP_REST_Response(array(
            'success' => true,
            'message' => sprintf(
                __('%d log entries were deleted.', 'gemini-command-center'),
                $deleted
            ),
            'deleted_count' => $deleted,
        ), 200);
    }

    /**
     * Get log statistics.
     *
     * @return array Log statistics.
     */
    public function get_stats() {
        global $wpdb;

        $table_name = GEMINI_CC_LOGS_TABLE;

        // Get counts by level
        $level_counts = $wpdb->get_results(
            "SELECT level, COUNT(*) as count FROM {$table_name} GROUP BY level ORDER BY count DESC"
        );

        // Get recent log count (last 24 hours)
        $recent_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE created_at >= %s",
                date('Y-m-d H:i:s', strtotime('-24 hours'))
            )
        );

        // Get total count
        $total_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");

        // Process level counts
        $levels = array();
        foreach ($level_counts as $level_count) {
            $levels[$level_count->level] = (int) $level_count->count;
        }

        return array(
            'total' => (int) $total_count,
            'recent_24h' => (int) $recent_count,
            'by_level' => $levels,
            'enabled' => $this->is_enabled(),
        );
    }

    /**
     * Clean up old logs (called by cron).
     *
     * @param int $days_to_keep Number of days to keep logs.
     */
    public function cleanup_old_logs($days_to_keep = 30) {
        global $wpdb;

        $table_name = GEMINI_CC_LOGS_TABLE;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_to_keep} days"));
        
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE created_at < %s",
                $cutoff_date
            )
        );

        if ($deleted > 0) {
            $this->info('Old logs cleaned up', array(
                'deleted_count' => $deleted,
                'cutoff_date' => $cutoff_date,
                'days_kept' => $days_to_keep,
            ));
        }

        return $deleted;
    }

    /**
     * Export logs to file.
     *
     * @param array $filters Optional filters.
     * @return string|WP_Error File path or error.
     */
    public function export_logs($filters = array()) {
        global $wpdb;

        $table_name = GEMINI_CC_LOGS_TABLE;

        // Build query based on filters
        $where_clause = '1=1';
        $where_values = array();

        if (!empty($filters['level'])) {
            $where_clause .= ' AND level = %s';
            $where_values[] = sanitize_text_field($filters['level']);
        }

        if (!empty($filters['start_date'])) {
            $where_clause .= ' AND created_at >= %s';
            $where_values[] = sanitize_text_field($filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $where_clause .= ' AND created_at <= %s';
            $where_values[] = sanitize_text_field($filters['end_date']);
        }

        // Get logs
        $query = "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY created_at DESC";
        
        if (!empty($where_values)) {
            $logs = $wpdb->get_results($wpdb->prepare($query, $where_values));
        } else {
            $logs = $wpdb->get_results($query);
        }

        // Create export file
        $upload_dir = wp_upload_dir();
        $filename = 'gemini-cc-logs-' . date('Y-m-d-H-i-s') . '.csv';
        $filepath = $upload_dir['path'] . '/' . $filename;

        $file = fopen($filepath, 'w');
        if (!$file) {
            return new WP_Error('file_error', __('Could not create export file.', 'gemini-command-center'));
        }

        // Write CSV header
        fputcsv($file, array(
            'ID',
            'Level',
            'Message',
            'Context',
            'User ID',
            'IP Address',
            'User Agent',
            'Created At'
        ));

        // Write log data
        foreach ($logs as $log) {
            fputcsv($file, array(
                $log->id,
                $log->level,
                $log->message,
                $log->context,
                $log->user_id,
                $log->ip_address,
                $log->user_agent,
                $log->created_at
            ));
        }

        fclose($file);

        return array(
            'filepath' => $filepath,
            'filename' => $filename,
            'url' => $upload_dir['url'] . '/' . $filename,
            'count' => count($logs),
        );
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
}