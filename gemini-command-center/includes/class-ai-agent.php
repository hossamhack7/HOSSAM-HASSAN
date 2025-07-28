<?php
/**
 * AI Agent class.
 * Handles conversational AI functionality.
 *
 * @package GeminiCommandCenter
 */

class Gemini_CC_AI_Agent {

    /**
     * Constructor.
     */
    public function __construct() {
        // Initialize AI agent features
    }

    /**
     * Handle conversation with AI agent.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response
     */
    public function converse($request) {
        $message = $request->get_param('message');
        $conversation_history = $request->get_param('conversation_history');

        if (empty($message)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Message is required.', 'gemini-command-center'),
            ), 400);
        }

        try {
            // Build context-aware prompt
            $context_prompt = $this->build_context_prompt($message, $conversation_history);
            
            require_once GEMINI_CC_INCLUDES_DIR . 'class-gemini-api.php';
            $gemini_api = new Gemini_CC_API();
            
            $ai_response = $gemini_api->generate_content($context_prompt, array(
                'temperature' => 0.7,
                'max_tokens' => 2048,
            ));

            if (is_wp_error($ai_response)) {
                throw new Exception($ai_response->get_error_message());
            }

            // Process AI response for actions
            $processed_response = $this->process_ai_response($ai_response);
            
            // Store conversation in memory
            $this->store_conversation_memory($message, $processed_response);

            return new WP_REST_Response(array(
                'success' => true,
                'response' => $processed_response,
                'timestamp' => current_time('c'),
            ), 200);
            
        } catch (Exception $e) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => sprintf(
                    __('AI conversation failed: %s', 'gemini-command-center'),
                    $e->getMessage()
                ),
            ), 500);
        }
    }

    /**
     * Build context-aware prompt for AI.
     *
     * @param string $user_message User's message.
     * @param array  $history      Conversation history.
     * @return string Context prompt.
     */
    private function build_context_prompt($user_message, $history = array()) {
        $prompt = $this->get_system_preamble();
        $prompt .= "\n\n" . $this->get_long_term_memory();
        $prompt .= "\n\n" . $this->get_site_context();
        
        // Add conversation history
        if (!empty($history)) {
            $prompt .= "\n\nCONVERSATION HISTORY:\n";
            foreach (array_slice($history, -5) as $exchange) {
                $prompt .= "User: " . $exchange['user'] . "\n";
                $prompt .= "Assistant: " . $exchange['assistant'] . "\n";
            }
        }
        
        $prompt .= "\n\nCURRENT USER MESSAGE: " . $user_message;
        $prompt .= "\n\nPlease respond as the Gemini Command Center AI assistant. Be helpful, concise, and actionable.";
        
        return $prompt;
    }

    /**
     * Get system preamble for AI context.
     *
     * @return string System preamble.
     */
    private function get_system_preamble() {
        return "You are the Gemini Command Center AI Assistant, an intelligent helper for WordPress website management.

AVAILABLE CAPABILITIES:
- SEO optimization and technical audits
- Content creation and optimization
- A/B testing for headlines
- Website backup and security
- UI/UX design analysis
- Performance monitoring
- Internal link building
- Competitive analysis
- Social media content creation

AVAILABLE ACTIONS:
- Run SEO audits
- Create backups
- Generate content
- Start A/B tests
- Analyze competitors
- Check system health
- Create internal links
- Generate color palettes

Always provide specific, actionable advice and offer to perform tasks when appropriate.";
    }

    /**
     * Get long-term memory context.
     *
     * @return string Memory context.
     */
    private function get_long_term_memory() {
        global $wpdb;
        
        $memory_facts = $wpdb->get_results($wpdb->prepare("
            SELECT fact_content, importance_score 
            FROM " . GEMINI_CC_MEMORY_TABLE . " 
            WHERE user_id = %d 
            ORDER BY importance_score DESC, created_at DESC 
            LIMIT 10
        ", get_current_user_id()));

        if (empty($memory_facts)) {
            return "MEMORY: No previous interactions recorded.";
        }

        $memory_text = "MEMORY (Previous interactions and learned facts):\n";
        foreach ($memory_facts as $fact) {
            $memory_text .= "- " . $fact->fact_content . "\n";
        }

        return $memory_text;
    }

    /**
     * Get current site context.
     *
     * @return string Site context.
     */
    private function get_site_context() {
        $context = "CURRENT SITE STATUS:\n";
        
        // WordPress info
        global $wp_version;
        $context .= "- WordPress " . $wp_version . "\n";
        
        // Theme info
        $theme = wp_get_theme();
        $context .= "- Theme: " . $theme->get('Name') . " v" . $theme->get('Version') . "\n";
        
        // Content stats
        $post_count = wp_count_posts();
        $context .= "- Published posts: " . $post_count->publish . "\n";
        $context .= "- Draft posts: " . $post_count->draft . "\n";
        
        // Plugin settings
        $settings = get_option('gemini_cc_settings', array());
        $api_configured = !empty($settings['general']['api_key']);
        $context .= "- Gemini API: " . ($api_configured ? "Configured" : "Not configured") . "\n";
        
        // Recent plugin activity
        $context .= $this->get_recent_activity();
        
        return $context;
    }

    /**
     * Get recent plugin activity.
     *
     * @return string Recent activity summary.
     */
    private function get_recent_activity() {
        global $wpdb;
        
        $recent_logs = $wpdb->get_results($wpdb->prepare("
            SELECT message, level, created_at 
            FROM " . GEMINI_CC_LOGS_TABLE . " 
            WHERE created_at >= %s 
            ORDER BY created_at DESC 
            LIMIT 5
        ", date('Y-m-d H:i:s', strtotime('-24 hours'))));

        if (empty($recent_logs)) {
            return "\n- No recent plugin activity";
        }

        $activity = "\nRECENT ACTIVITY (last 24h):\n";
        foreach ($recent_logs as $log) {
            $activity .= "- " . $log->message . " (" . $log->level . ")\n";
        }

        return $activity;
    }

    /**
     * Process AI response for actionable items.
     *
     * @param string $ai_response Raw AI response.
     * @return array Processed response.
     */
    private function process_ai_response($ai_response) {
        $response = array(
            'text' => $ai_response,
            'actions' => array(),
            'suggestions' => array(),
        );

        // Extract action suggestions from response
        $actions = $this->extract_action_suggestions($ai_response);
        if (!empty($actions)) {
            $response['actions'] = $actions;
        }

        // Extract follow-up suggestions
        $suggestions = $this->extract_suggestions($ai_response);
        if (!empty($suggestions)) {
            $response['suggestions'] = $suggestions;
        }

        return $response;
    }

    /**
     * Extract action suggestions from AI response.
     *
     * @param string $response AI response.
     * @return array Action suggestions.
     */
    private function extract_action_suggestions($response) {
        $actions = array();

        // Look for specific action patterns
        $action_patterns = array(
            'run.*seo.*audit' => array(
                'type' => 'seo_audit',
                'label' => 'Run SEO Audit',
                'endpoint' => '/seo/technical-audit',
            ),
            'create.*backup' => array(
                'type' => 'backup',
                'label' => 'Create Backup',
                'endpoint' => '/backup/create',
            ),
            'check.*health' => array(
                'type' => 'health_check',
                'label' => 'Check System Health',
                'endpoint' => '/system/health-check',
            ),
            'generate.*content' => array(
                'type' => 'generate_content',
                'label' => 'Generate Content',
                'endpoint' => '/content/generate-article',
            ),
        );

        foreach ($action_patterns as $pattern => $action) {
            if (preg_match('/' . $pattern . '/i', $response)) {
                $actions[] = $action;
            }
        }

        return $actions;
    }

    /**
     * Extract suggestions from AI response.
     *
     * @param string $response AI response.
     * @return array Suggestions.
     */
    private function extract_suggestions($response) {
        $suggestions = array();

        // Look for suggestion patterns
        if (preg_match_all('/(?:I suggest|I recommend|You should|Consider) ([^.]+)/i', $response, $matches)) {
            foreach ($matches[1] as $match) {
                $suggestions[] = trim($match);
            }
        }

        return array_slice($suggestions, 0, 3); // Limit to 3 suggestions
    }

    /**
     * Store conversation in memory.
     *
     * @param string $user_message User's message.
     * @param array  $ai_response  AI's response.
     */
    private function store_conversation_memory($user_message, $ai_response) {
        // Extract important facts to remember
        $facts = $this->extract_memory_facts($user_message, $ai_response);
        
        if (!empty($facts)) {
            global $wpdb;
            
            foreach ($facts as $fact) {
                $wpdb->insert(
                    GEMINI_CC_MEMORY_TABLE,
                    array(
                        'user_id' => get_current_user_id(),
                        'fact_type' => $fact['type'],
                        'fact_content' => $fact['content'],
                        'importance_score' => $fact['importance'],
                    ),
                    array('%d', '%s', '%s', '%d')
                );
            }
        }
    }

    /**
     * Extract facts worth remembering from conversation.
     *
     * @param string $user_message User's message.
     * @param array  $ai_response  AI's response.
     * @return array Memory facts.
     */
    private function extract_memory_facts($user_message, $ai_response) {
        $facts = array();

        // User goals and preferences
        if (preg_match('/(?:I want to|my goal is|I need to) ([^.]+)/i', $user_message, $match)) {
            $facts[] = array(
                'type' => 'user_goal',
                'content' => 'User goal: ' . trim($match[1]),
                'importance' => 80,
            );
        }

        // Technical issues mentioned
        if (preg_match('/(?:problem|issue|error|broken) with ([^.]+)/i', $user_message, $match)) {
            $facts[] = array(
                'type' => 'technical_issue',
                'content' => 'Technical issue: ' . trim($match[1]),
                'importance' => 90,
            );
        }

        // Site improvements mentioned
        if (preg_match('/(?:improve|optimize|enhance) ([^.]+)/i', $user_message, $match)) {
            $facts[] = array(
                'type' => 'improvement_area',
                'content' => 'Wants to improve: ' . trim($match[1]),
                'importance' => 70,
            );
        }

        return $facts;
    }

    /**
     * Clean up old memory entries.
     * Called periodically to prevent memory table from growing too large.
     */
    public function cleanup_memory() {
        global $wpdb;
        
        // Delete low-importance memories older than 30 days
        $wpdb->query($wpdb->prepare("
            DELETE FROM " . GEMINI_CC_MEMORY_TABLE . " 
            WHERE importance_score < 50 
            AND created_at < %s
        ", date('Y-m-d H:i:s', strtotime('-30 days'))));
        
        // Keep only top 100 memories per user
        $wpdb->query("
            DELETE m1 FROM " . GEMINI_CC_MEMORY_TABLE . " m1
            INNER JOIN (
                SELECT user_id, importance_score, created_at
                FROM " . GEMINI_CC_MEMORY_TABLE . "
                ORDER BY user_id, importance_score DESC, created_at DESC
            ) m2 ON m1.user_id = m2.user_id
            WHERE m1.importance_score < m2.importance_score
            OR (m1.importance_score = m2.importance_score AND m1.created_at < m2.created_at)
            HAVING COUNT(*) > 100
        ");
    }

    /**
     * Get conversation statistics.
     *
     * @return array Statistics.
     */
    public function get_conversation_stats() {
        global $wpdb;
        
        $user_id = get_current_user_id();
        
        $stats = array();
        
        // Total memories
        $stats['total_memories'] = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM " . GEMINI_CC_MEMORY_TABLE . " WHERE user_id = %d
        ", $user_id));
        
        // Memory types distribution
        $memory_types = $wpdb->get_results($wpdb->prepare("
            SELECT fact_type, COUNT(*) as count 
            FROM " . GEMINI_CC_MEMORY_TABLE . " 
            WHERE user_id = %d 
            GROUP BY fact_type
        ", $user_id));
        
        $stats['memory_types'] = array();
        foreach ($memory_types as $type) {
            $stats['memory_types'][$type->fact_type] = $type->count;
        }
        
        // Recent activity
        $stats['recent_conversations'] = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM " . GEMINI_CC_MEMORY_TABLE . " 
            WHERE user_id = %d 
            AND created_at >= %s
        ", $user_id, date('Y-m-d H:i:s', strtotime('-7 days'))));
        
        return $stats;
    }
}