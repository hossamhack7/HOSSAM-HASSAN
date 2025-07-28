<?php
/**
 * Plugin Deactivator class.
 * Handles plugin deactivation tasks.
 *
 * @package GeminiCommandCenter
 */

class Gemini_CC_Deactivator {

    /**
     * Deactivate the plugin.
     */
    public static function deactivate() {
        // Clear scheduled cron jobs
        self::clear_cron_jobs();
        
        // Clear transients
        self::clear_transients();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Log deactivation
        self::log_deactivation();
    }

    /**
     * Clear scheduled cron jobs.
     */
    private static function clear_cron_jobs() {
        // Clear daily cleanup job
        $timestamp = wp_next_scheduled('gemini_cc_daily_cleanup');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'gemini_cc_daily_cleanup');
        }
        
        // Clear weekly backup job
        $timestamp = wp_next_scheduled('gemini_cc_weekly_backup');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'gemini_cc_weekly_backup');
        }
    }

    /**
     * Clear plugin transients.
     */
    private static function clear_transients() {
        global $wpdb;
        
        // Delete all transients that start with 'gemini_cc_'
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE %s 
                OR option_name LIKE %s",
                $wpdb->esc_like('_transient_gemini_cc_') . '%',
                $wpdb->esc_like('_transient_timeout_gemini_cc_') . '%'
            )
        );
    }

    /**
     * Log plugin deactivation.
     */
    private static function log_deactivation() {
        // Only log if logging is enabled
        $settings = get_option('gemini_cc_settings', array());
        
        if (!empty($settings['system']['logging']['enabled'])) {
            if (class_exists('Gemini_CC_Logger')) {
                $logger = new Gemini_CC_Logger();
                $logger->info('Plugin deactivated', array(
                    'user_id' => get_current_user_id(),
                    'timestamp' => current_time('c'),
                ));
            }
        }
    }
}