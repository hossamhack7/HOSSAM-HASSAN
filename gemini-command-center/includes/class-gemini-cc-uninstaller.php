<?php
/**
 * Plugin Uninstaller class.
 * Handles plugin uninstallation tasks.
 *
 * @package GeminiCommandCenter
 */

class Gemini_CC_Uninstaller {

    /**
     * Uninstall the plugin.
     */
    public static function uninstall() {
        // Check if we should keep data
        $settings = get_option('gemini_cc_settings', array());
        $keep_data = !empty($settings['system']['keep_data_on_uninstall']);
        
        if (!$keep_data) {
            // Drop database tables
            self::drop_database_tables();
            
            // Delete plugin options
            self::delete_plugin_options();
            
            // Delete user meta
            self::delete_user_meta();
            
            // Remove backup directory
            self::remove_backup_directory();
            
            // Clear all cron jobs
            self::clear_all_cron_jobs();
        }
        
        // Clear transients regardless
        self::clear_all_transients();
    }

    /**
     * Drop database tables.
     */
    private static function drop_database_tables() {
        global $wpdb;

        $tables = array(
            GEMINI_CC_MEMORY_TABLE,
            GEMINI_CC_LOGS_TABLE,
            GEMINI_CC_AB_TESTS_TABLE,
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
    }

    /**
     * Delete plugin options.
     */
    private static function delete_plugin_options() {
        global $wpdb;

        // Delete all options that start with 'gemini_cc_'
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE %s",
                $wpdb->esc_like('gemini_cc_') . '%'
            )
        );
    }

    /**
     * Delete user meta related to the plugin.
     */
    private static function delete_user_meta() {
        global $wpdb;

        // Delete all user meta that starts with 'gemini_cc_'
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->usermeta} 
                WHERE meta_key LIKE %s",
                $wpdb->esc_like('gemini_cc_') . '%'
            )
        );
    }

    /**
     * Remove backup directory and all files.
     */
    private static function remove_backup_directory() {
        $upload_dir = wp_upload_dir();
        $backup_dir = $upload_dir['basedir'] . '/gemini-cc-backups';
        
        if (file_exists($backup_dir)) {
            self::delete_directory($backup_dir);
        }
    }

    /**
     * Recursively delete a directory and all its contents.
     *
     * @param string $dir Directory path.
     * @return bool
     */
    private static function delete_directory($dir) {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!self::delete_directory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }

    /**
     * Clear all plugin cron jobs.
     */
    private static function clear_all_cron_jobs() {
        // Get all cron jobs
        $crons = get_option('cron', array());
        
        foreach ($crons as $timestamp => $cron) {
            if (is_array($cron)) {
                foreach ($cron as $hook => $dings) {
                    if (strpos($hook, 'gemini_cc_') === 0) {
                        foreach ($dings as $sig => $data) {
                            wp_unschedule_event($timestamp, $hook, $data['args']);
                        }
                    }
                }
            }
        }
    }

    /**
     * Clear all plugin transients.
     */
    private static function clear_all_transients() {
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
}