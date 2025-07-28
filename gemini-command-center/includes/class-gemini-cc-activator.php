<?php
/**
 * Plugin Activator class.
 * Handles plugin activation tasks.
 *
 * @package GeminiCommandCenter
 */

class Gemini_CC_Activator {

    /**
     * Activate the plugin.
     */
    public static function activate() {
        // Create database tables
        self::create_database_tables();
        
        // Set default options
        self::set_default_options();
        
        // Create backup directory
        self::create_backup_directory();
        
        // Set plugin activation timestamp
        update_option('gemini_cc_activated_time', current_time('timestamp'));
        
        // Set plugin version
        update_option('gemini_cc_version', GEMINI_CC_VERSION);
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Schedule cron jobs if needed
        self::schedule_cron_jobs();
    }

    /**
     * Create database tables.
     */
    private static function create_database_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Memory table for AI Agent
        $memory_table = GEMINI_CC_MEMORY_TABLE;
        $memory_sql = "CREATE TABLE $memory_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            fact_type varchar(50) NOT NULL,
            fact_content longtext NOT NULL,
            importance_score int(3) DEFAULT 50,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY fact_type (fact_type),
            KEY importance_score (importance_score)
        ) $charset_collate;";

        // Logs table
        $logs_table = GEMINI_CC_LOGS_TABLE;
        $logs_sql = "CREATE TABLE $logs_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            level varchar(20) NOT NULL DEFAULT 'info',
            message longtext NOT NULL,
            context longtext,
            user_id bigint(20),
            ip_address varchar(45),
            user_agent text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY level (level),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        // A/B Tests table
        $ab_tests_table = GEMINI_CC_AB_TESTS_TABLE;
        $ab_tests_sql = "CREATE TABLE $ab_tests_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            title_a varchar(255) NOT NULL,
            title_b varchar(255) NOT NULL,
            impressions_a int(10) DEFAULT 0,
            impressions_b int(10) DEFAULT 0,
            clicks_a int(10) DEFAULT 0,
            clicks_b int(10) DEFAULT 0,
            status varchar(20) DEFAULT 'active',
            winner varchar(1),
            started_at datetime DEFAULT CURRENT_TIMESTAMP,
            ended_at datetime,
            created_by bigint(20),
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY status (status),
            KEY started_at (started_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($memory_sql);
        dbDelta($logs_sql);
        dbDelta($ab_tests_sql);
    }

    /**
     * Set default plugin options.
     */
    private static function set_default_options() {
        $default_settings = array(
            'general' => array(
                'api_key' => '',
                'operation_mode' => 'approval',
            ),
            'seo' => array(
                'internal_links' => array(
                    'enabled' => true,
                    'max_links' => 5,
                ),
                'ab_testing' => array(
                    'enabled' => false,
                    'default_duration' => 7,
                ),
                'competitive_analysis' => array(
                    'search_region' => 'google.com',
                ),
            ),
            'uiux' => array(
                'enabled' => false,
                'colors' => array(),
                'fonts' => array(),
            ),
            'content' => array(
                'ai_writer' => array(
                    'default_status' => 'draft',
                ),
                'social_amplifier' => array(
                    'twitter_tone' => 'engaging',
                    'linkedin_tone' => 'professional',
                ),
            ),
            'system' => array(
                'backup' => array(
                    'schedule' => 'disabled',
                    'retention' => 5,
                ),
                'logging' => array(
                    'enabled' => true,
                ),
                'rate_limiting' => array(
                    'auto_cooldown' => true,
                ),
            ),
        );

        add_option('gemini_cc_settings', $default_settings);
    }

    /**
     * Create backup directory.
     */
    private static function create_backup_directory() {
        $upload_dir = wp_upload_dir();
        $backup_dir = $upload_dir['basedir'] . '/gemini-cc-backups';
        
        if (!file_exists($backup_dir)) {
            wp_mkdir_p($backup_dir);
            
            // Create .htaccess to protect backup files
            $htaccess_content = "# Protect Gemini Command Center backup files\n";
            $htaccess_content .= "Order deny,allow\n";
            $htaccess_content .= "Deny from all\n";
            
            file_put_contents($backup_dir . '/.htaccess', $htaccess_content);
            
            // Create index.php to prevent directory listing
            file_put_contents($backup_dir . '/index.php', '<?php // Silence is golden');
        }
    }

    /**
     * Schedule cron jobs.
     */
    private static function schedule_cron_jobs() {
        // Schedule daily cleanup job
        if (!wp_next_scheduled('gemini_cc_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'gemini_cc_daily_cleanup');
        }
        
        // Schedule weekly backup job (if enabled)
        $settings = get_option('gemini_cc_settings', array());
        if (!empty($settings['system']['backup']['schedule']) && $settings['system']['backup']['schedule'] === 'weekly') {
            if (!wp_next_scheduled('gemini_cc_weekly_backup')) {
                wp_schedule_event(time(), 'weekly', 'gemini_cc_weekly_backup');
            }
        }
    }
}