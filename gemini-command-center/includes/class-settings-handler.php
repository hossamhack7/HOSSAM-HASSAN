<?php
/**
 * Settings Handler class.
 * Manages plugin settings and configuration.
 *
 * @package GeminiCommandCenter
 */

class Gemini_CC_Settings_Handler {

    /**
     * Settings option name.
     */
    const SETTINGS_OPTION = 'gemini_cc_settings';

    /**
     * Constructor.
     */
    public function __construct() {
        // No hooks needed here, settings are handled via REST API
    }

    /**
     * Get all settings.
     *
     * @return array Settings array.
     */
    public function get_settings() {
        $default_settings = $this->get_default_settings();
        $saved_settings = get_option(self::SETTINGS_OPTION, array());
        
        return $this->merge_settings($default_settings, $saved_settings);
    }

    /**
     * Save settings.
     *
     * @param array $settings Settings to save.
     * @return bool True if successful.
     */
    public function save_settings($settings) {
        $sanitized_settings = $this->sanitize_settings($settings);
        return update_option(self::SETTINGS_OPTION, $sanitized_settings);
    }

    /**
     * Get default settings structure.
     *
     * @return array Default settings.
     */
    public function get_default_settings() {
        return array(
            'general' => array(
                'api_key' => '',
                'operation_mode' => 'approval',
                'first_setup_completed' => false,
            ),
            'seo' => array(
                'internal_links' => array(
                    'enabled' => true,
                    'max_links' => 5,
                    'auto_link' => false,
                ),
                'ab_testing' => array(
                    'enabled' => false,
                    'default_duration' => 7,
                    'min_sample_size' => 100,
                ),
                'competitive_analysis' => array(
                    'search_region' => 'google.com',
                    'auto_track_competitors' => false,
                ),
                'technical_audit' => array(
                    'auto_fix_issues' => false,
                    'cache_duration' => 24,
                ),
            ),
            'uiux' => array(
                'enabled' => false,
                'colors' => array(
                    'primary' => '',
                    'secondary' => '',
                    'accent' => '',
                ),
                'fonts' => array(
                    'heading' => '',
                    'body' => '',
                ),
                'auto_apply_suggestions' => false,
            ),
            'content' => array(
                'ai_writer' => array(
                    'default_status' => 'draft',
                    'auto_generate_meta' => true,
                    'default_tone' => 'professional',
                ),
                'social_amplifier' => array(
                    'twitter_tone' => 'engaging',
                    'linkedin_tone' => 'professional',
                    'auto_post' => false,
                ),
                'ab_testing' => array(
                    'enabled' => false,
                    'auto_declare_winner' => true,
                ),
            ),
            'system' => array(
                'backup' => array(
                    'schedule' => 'disabled',
                    'retention' => 5,
                    'include_uploads' => true,
                    'auto_cleanup' => true,
                ),
                'logging' => array(
                    'enabled' => true,
                    'level' => 'info',
                    'retention_days' => 30,
                ),
                'rate_limiting' => array(
                    'auto_cooldown' => true,
                    'requests_per_minute' => 15,
                    'cooldown_duration' => 300,
                ),
                'performance' => array(
                    'cache_api_responses' => true,
                    'cache_duration' => 3600,
                    'preload_assets' => false,
                ),
                'notifications' => array(
                    'email_reports' => false,
                    'admin_notices' => true,
                    'error_notifications' => true,
                ),
            ),
            'integrations' => array(
                'site_kit' => array(
                    'enabled' => false,
                    'auto_sync' => false,
                ),
                'yoast_seo' => array(
                    'enabled' => false,
                    'sync_settings' => false,
                ),
                'rankmath' => array(
                    'enabled' => false,
                    'sync_settings' => false,
                ),
            ),
            'advanced' => array(
                'debug_mode' => false,
                'custom_endpoints' => false,
                'developer_mode' => false,
                'beta_features' => false,
            ),
        );
    }

    /**
     * Sanitize settings array.
     *
     * @param array $settings Raw settings.
     * @return array Sanitized settings.
     */
    public function sanitize_settings($settings) {
        $sanitized = array();

        // General settings
        if (isset($settings['general'])) {
            $sanitized['general'] = array();
            
            if (isset($settings['general']['api_key'])) {
                $sanitized['general']['api_key'] = sanitize_text_field($settings['general']['api_key']);
            }
            
            if (isset($settings['general']['operation_mode'])) {
                $mode = sanitize_text_field($settings['general']['operation_mode']);
                $sanitized['general']['operation_mode'] = in_array($mode, array('approval', 'autonomous')) ? $mode : 'approval';
            }
            
            if (isset($settings['general']['first_setup_completed'])) {
                $sanitized['general']['first_setup_completed'] = (bool) $settings['general']['first_setup_completed'];
            }
        }

        // SEO settings
        if (isset($settings['seo'])) {
            $sanitized['seo'] = array();
            
            // Internal links
            if (isset($settings['seo']['internal_links'])) {
                $sanitized['seo']['internal_links'] = array(
                    'enabled' => isset($settings['seo']['internal_links']['enabled']) ? (bool) $settings['seo']['internal_links']['enabled'] : true,
                    'max_links' => isset($settings['seo']['internal_links']['max_links']) ? min(20, max(1, (int) $settings['seo']['internal_links']['max_links'])) : 5,
                    'auto_link' => isset($settings['seo']['internal_links']['auto_link']) ? (bool) $settings['seo']['internal_links']['auto_link'] : false,
                );
            }
            
            // A/B testing
            if (isset($settings['seo']['ab_testing'])) {
                $sanitized['seo']['ab_testing'] = array(
                    'enabled' => isset($settings['seo']['ab_testing']['enabled']) ? (bool) $settings['seo']['ab_testing']['enabled'] : false,
                    'default_duration' => isset($settings['seo']['ab_testing']['default_duration']) ? min(30, max(1, (int) $settings['seo']['ab_testing']['default_duration'])) : 7,
                    'min_sample_size' => isset($settings['seo']['ab_testing']['min_sample_size']) ? max(50, (int) $settings['seo']['ab_testing']['min_sample_size']) : 100,
                );
            }
            
            // Competitive analysis
            if (isset($settings['seo']['competitive_analysis'])) {
                $region = isset($settings['seo']['competitive_analysis']['search_region']) ? sanitize_text_field($settings['seo']['competitive_analysis']['search_region']) : 'google.com';
                $valid_regions = array('google.com', 'google.co.uk', 'google.ca', 'google.com.au', 'google.de', 'google.fr');
                
                $sanitized['seo']['competitive_analysis'] = array(
                    'search_region' => in_array($region, $valid_regions) ? $region : 'google.com',
                    'auto_track_competitors' => isset($settings['seo']['competitive_analysis']['auto_track_competitors']) ? (bool) $settings['seo']['competitive_analysis']['auto_track_competitors'] : false,
                );
            }
            
            // Technical audit
            if (isset($settings['seo']['technical_audit'])) {
                $sanitized['seo']['technical_audit'] = array(
                    'auto_fix_issues' => isset($settings['seo']['technical_audit']['auto_fix_issues']) ? (bool) $settings['seo']['technical_audit']['auto_fix_issues'] : false,
                    'cache_duration' => isset($settings['seo']['technical_audit']['cache_duration']) ? min(168, max(1, (int) $settings['seo']['technical_audit']['cache_duration'])) : 24,
                );
            }
        }

        // UI/UX settings
        if (isset($settings['uiux'])) {
            $sanitized['uiux'] = array();
            
            $sanitized['uiux']['enabled'] = isset($settings['uiux']['enabled']) ? (bool) $settings['uiux']['enabled'] : false;
            
            // Colors
            if (isset($settings['uiux']['colors'])) {
                $sanitized['uiux']['colors'] = array(
                    'primary' => isset($settings['uiux']['colors']['primary']) ? sanitize_hex_color($settings['uiux']['colors']['primary']) : '',
                    'secondary' => isset($settings['uiux']['colors']['secondary']) ? sanitize_hex_color($settings['uiux']['colors']['secondary']) : '',
                    'accent' => isset($settings['uiux']['colors']['accent']) ? sanitize_hex_color($settings['uiux']['colors']['accent']) : '',
                );
            }
            
            // Fonts
            if (isset($settings['uiux']['fonts'])) {
                $sanitized['uiux']['fonts'] = array(
                    'heading' => isset($settings['uiux']['fonts']['heading']) ? sanitize_text_field($settings['uiux']['fonts']['heading']) : '',
                    'body' => isset($settings['uiux']['fonts']['body']) ? sanitize_text_field($settings['uiux']['fonts']['body']) : '',
                );
            }
            
            $sanitized['uiux']['auto_apply_suggestions'] = isset($settings['uiux']['auto_apply_suggestions']) ? (bool) $settings['uiux']['auto_apply_suggestions'] : false;
        }

        // Content settings
        if (isset($settings['content'])) {
            $sanitized['content'] = array();
            
            // AI Writer
            if (isset($settings['content']['ai_writer'])) {
                $status = isset($settings['content']['ai_writer']['default_status']) ? sanitize_text_field($settings['content']['ai_writer']['default_status']) : 'draft';
                $tone = isset($settings['content']['ai_writer']['default_tone']) ? sanitize_text_field($settings['content']['ai_writer']['default_tone']) : 'professional';
                
                $sanitized['content']['ai_writer'] = array(
                    'default_status' => in_array($status, array('draft', 'publish', 'private')) ? $status : 'draft',
                    'auto_generate_meta' => isset($settings['content']['ai_writer']['auto_generate_meta']) ? (bool) $settings['content']['ai_writer']['auto_generate_meta'] : true,
                    'default_tone' => in_array($tone, array('professional', 'casual', 'formal', 'friendly', 'authoritative')) ? $tone : 'professional',
                );
            }
            
            // Social Amplifier
            if (isset($settings['content']['social_amplifier'])) {
                $sanitized['content']['social_amplifier'] = array(
                    'twitter_tone' => isset($settings['content']['social_amplifier']['twitter_tone']) ? sanitize_text_field($settings['content']['social_amplifier']['twitter_tone']) : 'engaging',
                    'linkedin_tone' => isset($settings['content']['social_amplifier']['linkedin_tone']) ? sanitize_text_field($settings['content']['social_amplifier']['linkedin_tone']) : 'professional',
                    'auto_post' => isset($settings['content']['social_amplifier']['auto_post']) ? (bool) $settings['content']['social_amplifier']['auto_post'] : false,
                );
            }
            
            // A/B Testing
            if (isset($settings['content']['ab_testing'])) {
                $sanitized['content']['ab_testing'] = array(
                    'enabled' => isset($settings['content']['ab_testing']['enabled']) ? (bool) $settings['content']['ab_testing']['enabled'] : false,
                    'auto_declare_winner' => isset($settings['content']['ab_testing']['auto_declare_winner']) ? (bool) $settings['content']['ab_testing']['auto_declare_winner'] : true,
                );
            }
        }

        // System settings
        if (isset($settings['system'])) {
            $sanitized['system'] = array();
            
            // Backup
            if (isset($settings['system']['backup'])) {
                $schedule = isset($settings['system']['backup']['schedule']) ? sanitize_text_field($settings['system']['backup']['schedule']) : 'disabled';
                
                $sanitized['system']['backup'] = array(
                    'schedule' => in_array($schedule, array('disabled', 'daily', 'weekly', 'monthly')) ? $schedule : 'disabled',
                    'retention' => isset($settings['system']['backup']['retention']) ? min(30, max(1, (int) $settings['system']['backup']['retention'])) : 5,
                    'include_uploads' => isset($settings['system']['backup']['include_uploads']) ? (bool) $settings['system']['backup']['include_uploads'] : true,
                    'auto_cleanup' => isset($settings['system']['backup']['auto_cleanup']) ? (bool) $settings['system']['backup']['auto_cleanup'] : true,
                );
            }
            
            // Logging
            if (isset($settings['system']['logging'])) {
                $level = isset($settings['system']['logging']['level']) ? sanitize_text_field($settings['system']['logging']['level']) : 'info';
                
                $sanitized['system']['logging'] = array(
                    'enabled' => isset($settings['system']['logging']['enabled']) ? (bool) $settings['system']['logging']['enabled'] : true,
                    'level' => in_array($level, array('error', 'warning', 'info', 'debug')) ? $level : 'info',
                    'retention_days' => isset($settings['system']['logging']['retention_days']) ? min(365, max(1, (int) $settings['system']['logging']['retention_days'])) : 30,
                );
            }
            
            // Rate limiting
            if (isset($settings['system']['rate_limiting'])) {
                $sanitized['system']['rate_limiting'] = array(
                    'auto_cooldown' => isset($settings['system']['rate_limiting']['auto_cooldown']) ? (bool) $settings['system']['rate_limiting']['auto_cooldown'] : true,
                    'requests_per_minute' => isset($settings['system']['rate_limiting']['requests_per_minute']) ? min(60, max(5, (int) $settings['system']['rate_limiting']['requests_per_minute'])) : 15,
                    'cooldown_duration' => isset($settings['system']['rate_limiting']['cooldown_duration']) ? min(3600, max(60, (int) $settings['system']['rate_limiting']['cooldown_duration'])) : 300,
                );
            }
            
            // Performance
            if (isset($settings['system']['performance'])) {
                $sanitized['system']['performance'] = array(
                    'cache_api_responses' => isset($settings['system']['performance']['cache_api_responses']) ? (bool) $settings['system']['performance']['cache_api_responses'] : true,
                    'cache_duration' => isset($settings['system']['performance']['cache_duration']) ? min(86400, max(300, (int) $settings['system']['performance']['cache_duration'])) : 3600,
                    'preload_assets' => isset($settings['system']['performance']['preload_assets']) ? (bool) $settings['system']['performance']['preload_assets'] : false,
                );
            }
            
            // Notifications
            if (isset($settings['system']['notifications'])) {
                $sanitized['system']['notifications'] = array(
                    'email_reports' => isset($settings['system']['notifications']['email_reports']) ? (bool) $settings['system']['notifications']['email_reports'] : false,
                    'admin_notices' => isset($settings['system']['notifications']['admin_notices']) ? (bool) $settings['system']['notifications']['admin_notices'] : true,
                    'error_notifications' => isset($settings['system']['notifications']['error_notifications']) ? (bool) $settings['system']['notifications']['error_notifications'] : true,
                );
            }
        }

        // Integrations
        if (isset($settings['integrations'])) {
            $sanitized['integrations'] = array();
            
            $integration_keys = array('site_kit', 'yoast_seo', 'rankmath');
            foreach ($integration_keys as $key) {
                if (isset($settings['integrations'][$key])) {
                    $sanitized['integrations'][$key] = array(
                        'enabled' => isset($settings['integrations'][$key]['enabled']) ? (bool) $settings['integrations'][$key]['enabled'] : false,
                        'auto_sync' => isset($settings['integrations'][$key]['auto_sync']) ? (bool) $settings['integrations'][$key]['auto_sync'] : false,
                    );
                    
                    if ($key !== 'site_kit') {
                        $sanitized['integrations'][$key]['sync_settings'] = isset($settings['integrations'][$key]['sync_settings']) ? (bool) $settings['integrations'][$key]['sync_settings'] : false;
                    }
                }
            }
        }

        // Advanced settings
        if (isset($settings['advanced'])) {
            $sanitized['advanced'] = array(
                'debug_mode' => isset($settings['advanced']['debug_mode']) ? (bool) $settings['advanced']['debug_mode'] : false,
                'custom_endpoints' => isset($settings['advanced']['custom_endpoints']) ? (bool) $settings['advanced']['custom_endpoints'] : false,
                'developer_mode' => isset($settings['advanced']['developer_mode']) ? (bool) $settings['advanced']['developer_mode'] : false,
                'beta_features' => isset($settings['advanced']['beta_features']) ? (bool) $settings['advanced']['beta_features'] : false,
            );
        }

        return $sanitized;
    }

    /**
     * Merge default settings with saved settings.
     *
     * @param array $default Default settings.
     * @param array $saved Saved settings.
     * @return array Merged settings.
     */
    private function merge_settings($default, $saved) {
        return array_replace_recursive($default, $saved);
    }

    /**
     * Reset settings to defaults.
     *
     * @param array $sections Optional. Specific sections to reset.
     * @return bool True if successful.
     */
    public function reset_settings($sections = array()) {
        $current_settings = $this->get_settings();
        $default_settings = $this->get_default_settings();
        
        if (empty($sections)) {
            // Reset all settings
            return update_option(self::SETTINGS_OPTION, $default_settings);
        }
        
        // Reset specific sections
        foreach ($sections as $section) {
            if (isset($default_settings[$section])) {
                $current_settings[$section] = $default_settings[$section];
            }
        }
        
        return update_option(self::SETTINGS_OPTION, $current_settings);
    }

    /**
     * Export settings as JSON.
     *
     * @return string JSON encoded settings.
     */
    public function export_settings() {
        $settings = $this->get_settings();
        
        // Remove sensitive data
        if (isset($settings['general']['api_key'])) {
            $settings['general']['api_key'] = '[REDACTED]';
        }
        
        $export_data = array(
            'version' => GEMINI_CC_VERSION,
            'exported_at' => current_time('c'),
            'settings' => $settings,
        );
        
        return wp_json_encode($export_data, JSON_PRETTY_PRINT);
    }

    /**
     * Import settings from JSON.
     *
     * @param string $json JSON encoded settings.
     * @return array Result array with success status and message.
     */
    public function import_settings($json) {
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array(
                'success' => false,
                'message' => __('Invalid JSON format.', 'gemini-command-center'),
            );
        }
        
        if (!isset($data['settings'])) {
            return array(
                'success' => false,
                'message' => __('Invalid settings file format.', 'gemini-command-center'),
            );
        }
        
        // Validate version compatibility if present
        if (isset($data['version'])) {
            $imported_version = version_compare($data['version'], GEMINI_CC_VERSION, '<=');
            if (!$imported_version) {
                return array(
                    'success' => false,
                    'message' => __('Settings file is from a newer version and may not be compatible.', 'gemini-command-center'),
                );
            }
        }
        
        $settings = $data['settings'];
        
        // Preserve current API key if not in import
        $current_settings = $this->get_settings();
        if (isset($settings['general']['api_key']) && $settings['general']['api_key'] === '[REDACTED]') {
            $settings['general']['api_key'] = $current_settings['general']['api_key'];
        }
        
        $sanitized_settings = $this->sanitize_settings($settings);
        $success = $this->save_settings($sanitized_settings);
        
        return array(
            'success' => $success,
            'message' => $success 
                ? __('Settings imported successfully.', 'gemini-command-center')
                : __('Failed to import settings.', 'gemini-command-center'),
        );
    }

    /**
     * Get setting value by key path.
     *
     * @param string $key_path Dot notation key path (e.g., 'general.api_key').
     * @param mixed  $default Default value if not found.
     * @return mixed Setting value.
     */
    public function get_setting($key_path, $default = null) {
        $settings = $this->get_settings();
        $keys = explode('.', $key_path);
        $value = $settings;
        
        foreach ($keys as $key) {
            if (isset($value[$key])) {
                $value = $value[$key];
            } else {
                return $default;
            }
        }
        
        return $value;
    }

    /**
     * Update a specific setting value.
     *
     * @param string $key_path Dot notation key path.
     * @param mixed  $value New value.
     * @return bool True if successful.
     */
    public function update_setting($key_path, $value) {
        $settings = $this->get_settings();
        $keys = explode('.', $key_path);
        $current = &$settings;
        
        for ($i = 0; $i < count($keys) - 1; $i++) {
            $key = $keys[$i];
            if (!isset($current[$key]) || !is_array($current[$key])) {
                $current[$key] = array();
            }
            $current = &$current[$key];
        }
        
        $current[$keys[count($keys) - 1]] = $value;
        
        return $this->save_settings($settings);
    }
}