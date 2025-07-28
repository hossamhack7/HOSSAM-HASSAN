<?php
/**
 * API Registrar class.
 * Handles REST API endpoint registration.
 *
 * @package GeminiCommandCenter
 */

class Gemini_CC_API_Registrar {

    /**
     * API namespace.
     */
    const NAMESPACE = 'gemini-cc/v1';

    /**
     * Constructor.
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register REST API routes.
     */
    public function register_routes() {
        // Status endpoint
        register_rest_route(self::NAMESPACE, '/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_status'),
            'permission_callback' => array($this, 'check_permissions'),
        ));

        // Settings endpoints
        register_rest_route(self::NAMESPACE, '/settings', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_settings'),
                'permission_callback' => array($this, 'check_permissions'),
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'save_settings'),
                'permission_callback' => array($this, 'check_permissions'),
                'args' => $this->get_settings_schema(),
            ),
        ));

        // Test connection endpoint
        register_rest_route(self::NAMESPACE, '/test-connection', array(
            'methods' => 'POST',
            'callback' => array($this, 'test_gemini_connection'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'api_key' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        // Backup endpoints
        $this->register_backup_routes();
        
        // SEO endpoints
        $this->register_seo_routes();
        
        // Content endpoints
        $this->register_content_routes();
        
        // UI/UX endpoints
        $this->register_uiux_routes();
        
        // Reporting endpoints
        $this->register_reporting_routes();
        
        // AI Agent endpoints
        $this->register_ai_agent_routes();
        
        // System endpoints
        $this->register_system_routes();
    }

    /**
     * Register backup-related routes.
     */
    private function register_backup_routes() {
        register_rest_route(self::NAMESPACE, '/backup/create', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_backup'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'type' => array(
                    'required' => true,
                    'type' => 'string',
                    'enum' => array('database', 'full'),
                ),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/backup/list', array(
            'methods' => 'GET',
            'callback' => array($this, 'list_backups'),
            'permission_callback' => array($this, 'check_permissions'),
        ));

        register_rest_route(self::NAMESPACE, '/backup/download/(?P<filename>[a-zA-Z0-9_.-]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'download_backup'),
            'permission_callback' => array($this, 'check_permissions'),
        ));

        register_rest_route(self::NAMESPACE, '/backup/restore', array(
            'methods' => 'POST',
            'callback' => array($this, 'restore_backup'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'filename' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_file_name',
                ),
            ),
        ));
    }

    /**
     * Register SEO-related routes.
     */
    private function register_seo_routes() {
        register_rest_route(self::NAMESPACE, '/seo/technical-audit', array(
            'methods' => 'GET',
            'callback' => array($this, 'run_technical_audit'),
            'permission_callback' => array($this, 'check_permissions'),
        ));

        register_rest_route(self::NAMESPACE, '/seo/generate-cluster', array(
            'methods' => 'POST',
            'callback' => array($this, 'generate_content_cluster'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'pillar_topic' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/seo/analyze-competitor', array(
            'methods' => 'POST',
            'callback' => array($this, 'analyze_competitor'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'url' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'esc_url_raw',
                ),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/links/orphan-pages', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_orphan_pages'),
            'permission_callback' => array($this, 'check_permissions'),
        ));

        register_rest_route(self::NAMESPACE, '/links/create', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_internal_link'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'source_post_id' => array(
                    'required' => true,
                    'type' => 'integer',
                ),
                'target_post_id' => array(
                    'required' => true,
                    'type' => 'integer',
                ),
                'anchor_text' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
    }

    /**
     * Register content-related routes.
     */
    private function register_content_routes() {
        register_rest_route(self::NAMESPACE, '/content/generate-article', array(
            'methods' => 'POST',
            'callback' => array($this, 'generate_article'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'topic' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'keywords' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'tone' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/content/save-draft', array(
            'methods' => 'POST',
            'callback' => array($this, 'save_content_draft'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'title' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'content' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'wp_kses_post',
                ),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/content/start-ab-test', array(
            'methods' => 'POST',
            'callback' => array($this, 'start_ab_test'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'post_id' => array(
                    'required' => true,
                    'type' => 'integer',
                ),
                'title_a' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'title_b' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/content/amplify', array(
            'methods' => 'POST',
            'callback' => array($this, 'amplify_content'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'post_id' => array(
                    'required' => true,
                    'type' => 'integer',
                ),
                'platform' => array(
                    'required' => true,
                    'type' => 'string',
                    'enum' => array('twitter', 'linkedin', 'newsletter'),
                ),
            ),
        ));
    }

    /**
     * Register UI/UX-related routes.
     */
    private function register_uiux_routes() {
        register_rest_route(self::NAMESPACE, '/uiux/analyze-design', array(
            'methods' => 'POST',
            'callback' => array($this, 'analyze_design'),
            'permission_callback' => array($this, 'check_permissions'),
        ));

        register_rest_route(self::NAMESPACE, '/uiux/suggest-palette', array(
            'methods' => 'POST',
            'callback' => array($this, 'suggest_color_palette'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'primary_color' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_hex_color',
                ),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/uiux/save-styles', array(
            'methods' => 'POST',
            'callback' => array($this, 'save_ui_styles'),
            'permission_callback' => array($this, 'check_permissions'),
        ));
    }

    /**
     * Register reporting-related routes.
     */
    private function register_reporting_routes() {
        register_rest_route(self::NAMESPACE, '/reports/kpi-summary', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_kpi_summary'),
            'permission_callback' => array($this, 'check_permissions'),
        ));

        register_rest_route(self::NAMESPACE, '/reports/impact-analysis', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_impact_analysis'),
            'permission_callback' => array($this, 'check_permissions'),
        ));
    }

    /**
     * Register AI Agent-related routes.
     */
    private function register_ai_agent_routes() {
        register_rest_route(self::NAMESPACE, '/agent/converse', array(
            'methods' => 'POST',
            'callback' => array($this, 'agent_converse'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'message' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ),
                'conversation_history' => array(
                    'required' => false,
                    'type' => 'array',
                ),
            ),
        ));
    }

    /**
     * Register system-related routes.
     */
    private function register_system_routes() {
        register_rest_route(self::NAMESPACE, '/system/health-check', array(
            'methods' => 'GET',
            'callback' => array($this, 'system_health_check'),
            'permission_callback' => array($this, 'check_permissions'),
        ));

        register_rest_route(self::NAMESPACE, '/system/logs', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_system_logs'),
            'permission_callback' => array($this, 'check_permissions'),
        ));

        register_rest_route(self::NAMESPACE, '/system/clear-logs', array(
            'methods' => 'POST',
            'callback' => array($this, 'clear_system_logs'),
            'permission_callback' => array($this, 'check_permissions'),
        ));
    }

    /**
     * Check API permissions.
     *
     * @return bool
     */
    public function check_permissions() {
        return current_user_can('manage_options');
    }

    /**
     * Get plugin status.
     *
     * @return WP_REST_Response
     */
    public function get_status() {
        return new WP_REST_Response(array(
            'status' => 'ok',
            'version' => GEMINI_CC_VERSION,
            'timestamp' => current_time('c'),
        ), 200);
    }

    /**
     * Get settings.
     *
     * @return WP_REST_Response
     */
    public function get_settings() {
        $settings = get_option('gemini_cc_settings', array());
        return new WP_REST_Response($settings, 200);
    }

    /**
     * Save settings.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response
     */
    public function save_settings($request) {
        $settings = $request->get_json_params();
        
        // Sanitize settings
        $sanitized_settings = $this->sanitize_settings($settings);
        
        // Save settings
        update_option('gemini_cc_settings', $sanitized_settings);
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => __('Settings saved successfully.', 'gemini-command-center'),
        ), 200);
    }

    /**
     * Test Gemini API connection.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response
     */
    public function test_gemini_connection($request) {
        $api_key = $request->get_param('api_key');
        
        // Test connection using Gemini API class
        require_once GEMINI_CC_INCLUDES_DIR . 'class-gemini-api.php';
        $gemini_api = new Gemini_CC_API($api_key);
        
        $test_result = $gemini_api->test_connection();
        
        if ($test_result['success']) {
            return new WP_REST_Response(array(
                'success' => true,
                'message' => __('Connection successful! Gemini API is working properly.', 'gemini-command-center'),
            ), 200);
        } else {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => $test_result['message'],
            ), 400);
        }
    }

    /**
     * Route callbacks will be implemented in respective manager classes.
     * These are placeholder methods that delegate to the appropriate managers.
     */

    public function create_backup($request) {
        $backup_manager = new Gemini_CC_Backup_Manager();
        return $backup_manager->create_backup($request);
    }

    public function list_backups($request) {
        $backup_manager = new Gemini_CC_Backup_Manager();
        return $backup_manager->list_backups($request);
    }

    public function download_backup($request) {
        $backup_manager = new Gemini_CC_Backup_Manager();
        return $backup_manager->download_backup($request);
    }

    public function restore_backup($request) {
        $backup_manager = new Gemini_CC_Backup_Manager();
        return $backup_manager->restore_backup($request);
    }

    // SEO Manager methods
    public function run_technical_audit($request) {
        $seo_manager = new Gemini_CC_SEO_Manager();
        return $seo_manager->run_technical_audit($request);
    }

    public function generate_content_cluster($request) {
        $seo_manager = new Gemini_CC_SEO_Manager();
        return $seo_manager->generate_content_cluster($request);
    }

    public function analyze_competitor($request) {
        $seo_manager = new Gemini_CC_SEO_Manager();
        return $seo_manager->analyze_competitor($request);
    }

    public function get_orphan_pages($request) {
        $seo_manager = new Gemini_CC_SEO_Manager();
        return $seo_manager->get_orphan_pages($request);
    }

    public function create_internal_link($request) {
        $seo_manager = new Gemini_CC_SEO_Manager();
        return $seo_manager->create_internal_link($request);
    }

    // Content Manager methods
    public function generate_article($request) {
        $content_manager = new Gemini_CC_Content_Manager();
        return $content_manager->generate_article($request);
    }

    public function save_content_draft($request) {
        $content_manager = new Gemini_CC_Content_Manager();
        return $content_manager->save_content_draft($request);
    }

    public function start_ab_test($request) {
        $content_manager = new Gemini_CC_Content_Manager();
        return $content_manager->start_ab_test($request);
    }

    public function amplify_content($request) {
        $content_manager = new Gemini_CC_Content_Manager();
        return $content_manager->amplify_content($request);
    }

    // UI/UX Manager methods
    public function analyze_design($request) {
        $uiux_manager = new Gemini_CC_UIUX_Manager();
        return $uiux_manager->analyze_design($request);
    }

    public function suggest_color_palette($request) {
        $uiux_manager = new Gemini_CC_UIUX_Manager();
        return $uiux_manager->suggest_color_palette($request);
    }

    public function save_ui_styles($request) {
        $uiux_manager = new Gemini_CC_UIUX_Manager();
        return $uiux_manager->save_ui_styles($request);
    }

    // Reporting Manager methods
    public function get_kpi_summary($request) {
        $reporting_manager = new Gemini_CC_Reporting_Manager();
        return $reporting_manager->get_kpi_summary($request);
    }

    public function get_impact_analysis($request) {
        $reporting_manager = new Gemini_CC_Reporting_Manager();
        return $reporting_manager->get_impact_analysis($request);
    }

    // AI Agent methods
    public function agent_converse($request) {
        $ai_agent = new Gemini_CC_AI_Agent();
        return $ai_agent->converse($request);
    }

    // System methods
    public function system_health_check($request) {
        require_once GEMINI_CC_INCLUDES_DIR . 'class-system-health.php';
        $health_checker = new Gemini_CC_System_Health();
        return $health_checker->run_health_check($request);
    }

    public function get_system_logs($request) {
        $logger = new Gemini_CC_Logger();
        return $logger->get_logs($request);
    }

    public function clear_system_logs($request) {
        $logger = new Gemini_CC_Logger();
        return $logger->clear_logs($request);
    }

    /**
     * Get settings schema for validation.
     *
     * @return array
     */
    private function get_settings_schema() {
        return array(
            'general' => array(
                'type' => 'object',
                'properties' => array(
                    'api_key' => array('type' => 'string'),
                    'operation_mode' => array('type' => 'string', 'enum' => array('approval', 'autonomous')),
                ),
            ),
            'seo' => array(
                'type' => 'object',
                'properties' => array(
                    'internal_links' => array(
                        'type' => 'object',
                        'properties' => array(
                            'enabled' => array('type' => 'boolean'),
                            'max_links' => array('type' => 'integer'),
                        ),
                    ),
                    'ab_testing' => array(
                        'type' => 'object',
                        'properties' => array(
                            'enabled' => array('type' => 'boolean'),
                            'default_duration' => array('type' => 'integer'),
                        ),
                    ),
                ),
            ),
        );
    }

    /**
     * Sanitize settings array.
     *
     * @param array $settings Raw settings array.
     * @return array Sanitized settings.
     */
    private function sanitize_settings($settings) {
        $sanitized = array();

        if (isset($settings['general'])) {
            $sanitized['general'] = array();
            if (isset($settings['general']['api_key'])) {
                $sanitized['general']['api_key'] = sanitize_text_field($settings['general']['api_key']);
            }
            if (isset($settings['general']['operation_mode'])) {
                $mode = sanitize_text_field($settings['general']['operation_mode']);
                $sanitized['general']['operation_mode'] = in_array($mode, array('approval', 'autonomous')) ? $mode : 'approval';
            }
        }

        // Add more sanitization for other setting groups as needed

        return $sanitized;
    }
}