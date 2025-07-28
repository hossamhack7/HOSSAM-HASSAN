<?php
/**
 * Gemini API class.
 * Handles communication with Google's Gemini API.
 *
 * @package GeminiCommandCenter
 */

class Gemini_CC_API {

    /**
     * API key.
     *
     * @var string
     */
    private $api_key;

    /**
     * API base URL.
     *
     * @var string
     */
    private $base_url = 'https://generativelanguage.googleapis.com/v1/models/';

    /**
     * Rate limiter instance.
     *
     * @var Gemini_CC_Rate_Limiter
     */
    private $rate_limiter;

    /**
     * Constructor.
     *
     * @param string $api_key The Gemini API key.
     */
    public function __construct($api_key = '') {
        $this->api_key = $api_key;
        
        if (empty($this->api_key)) {
            $settings = get_option('gemini_cc_settings', array());
            $this->api_key = isset($settings['general']['api_key']) ? $settings['general']['api_key'] : '';
        }

        $this->rate_limiter = new Gemini_CC_Rate_Limiter();
    }

    /**
     * Test API connection.
     *
     * @return array Result array with success status and message.
     */
    public function test_connection() {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'message' => __('API key is required.', 'gemini-command-center'),
            );
        }

        $test_prompt = 'Hello, this is a connection test. Please respond with "Connection successful".';
        
        $response = $this->generate_content($test_prompt);

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => sprintf(
                    __('Connection failed: %s', 'gemini-command-center'),
                    $response->get_error_message()
                ),
            );
        }

        return array(
            'success' => true,
            'message' => __('Connection successful!', 'gemini-command-center'),
            'response' => $response,
        );
    }

    /**
     * Generate content using Gemini API.
     *
     * @param string $prompt The prompt to send.
     * @param array  $options Additional options.
     * @return string|WP_Error Generated content or error.
     */
    public function generate_content($prompt, $options = array()) {
        // Check rate limiting
        if (!$this->rate_limiter->is_allowed()) {
            return new WP_Error(
                'rate_limit_exceeded',
                __('Rate limit exceeded. Please try again later.', 'gemini-command-center')
            );
        }

        // Prepare the request
        $model = isset($options['model']) ? $options['model'] : 'gemini-pro';
        $url = $this->base_url . $model . ':generateContent?key=' . $this->api_key;

        $body = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array(
                            'text' => $prompt,
                        ),
                    ),
                ),
            ),
            'generationConfig' => array(
                'temperature' => isset($options['temperature']) ? $options['temperature'] : 0.7,
                'topK' => isset($options['top_k']) ? $options['top_k'] : 40,
                'topP' => isset($options['top_p']) ? $options['top_p'] : 0.95,
                'maxOutputTokens' => isset($options['max_tokens']) ? $options['max_tokens'] : 2048,
            ),
        );

        // Add safety settings if specified
        if (isset($options['safety_settings'])) {
            $body['safetySettings'] = $options['safety_settings'];
        }

        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode($body),
            'timeout' => 30,
        );

        // Log the request
        $this->log_api_request($prompt, $options);

        // Record rate limit usage
        $this->rate_limiter->record_request();

        // Make the request
        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            $this->log_api_error($response->get_error_message());
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            $error_message = $this->parse_error_response($response_body, $response_code);
            $this->log_api_error($error_message);
            return new WP_Error('api_error', $error_message);
        }

        $data = json_decode($response_body, true);

        if (!$data || !isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $error_message = __('Invalid response from Gemini API.', 'gemini-command-center');
            $this->log_api_error($error_message);
            return new WP_Error('invalid_response', $error_message);
        }

        $generated_text = $data['candidates'][0]['content']['parts'][0]['text'];
        
        // Log successful response
        $this->log_api_success($generated_text);

        return $generated_text;
    }

    /**
     * Generate structured content with specific instructions.
     *
     * @param string $type Content type (article, social, seo, etc.).
     * @param array  $params Parameters for content generation.
     * @return string|WP_Error Generated content or error.
     */
    public function generate_structured_content($type, $params) {
        $prompt = $this->build_structured_prompt($type, $params);
        
        $options = array(
            'temperature' => 0.8,
            'max_tokens' => 4096,
        );

        return $this->generate_content($prompt, $options);
    }

    /**
     * Build structured prompt based on content type.
     *
     * @param string $type Content type.
     * @param array  $params Parameters.
     * @return string Formatted prompt.
     */
    private function build_structured_prompt($type, $params) {
        $prompts = array(
            'article' => $this->build_article_prompt($params),
            'social_twitter' => $this->build_twitter_prompt($params),
            'social_linkedin' => $this->build_linkedin_prompt($params),
            'seo_cluster' => $this->build_seo_cluster_prompt($params),
            'competitor_analysis' => $this->build_competitor_analysis_prompt($params),
            'design_analysis' => $this->build_design_analysis_prompt($params),
            'color_palette' => $this->build_color_palette_prompt($params),
        );

        return isset($prompts[$type]) ? $prompts[$type] : '';
    }

    /**
     * Build article generation prompt.
     *
     * @param array $params Parameters.
     * @return string Prompt.
     */
    private function build_article_prompt($params) {
        $topic = isset($params['topic']) ? $params['topic'] : '';
        $keywords = isset($params['keywords']) ? $params['keywords'] : '';
        $tone = isset($params['tone']) ? $params['tone'] : 'professional';

        return sprintf(
            'Write a comprehensive, SEO-optimized article about "%s". 
            
            Requirements:
            - Target keywords: %s
            - Tone: %s
            - Length: 1000-1500 words
            - Include proper HTML formatting with headings (h2, h3)
            - Add meta description (max 160 characters)
            - Include internal linking opportunities (mark with [INTERNAL_LINK])
            - Make it engaging and valuable for readers
            
            Structure the response as:
            TITLE: [Article title]
            META_DESCRIPTION: [Meta description]
            CONTENT: [Full article with HTML formatting]',
            esc_html($topic),
            esc_html($keywords),
            esc_html($tone)
        );
    }

    /**
     * Build Twitter prompt.
     *
     * @param array $params Parameters.
     * @return string Prompt.
     */
    private function build_twitter_prompt($params) {
        $content = isset($params['content']) ? $params['content'] : '';
        $tone = isset($params['tone']) ? $params['tone'] : 'engaging';

        return sprintf(
            'Create a Twitter thread based on this content: %s
            
            Requirements:
            - Tone: %s
            - 5-7 tweets maximum
            - Each tweet under 280 characters
            - Include relevant hashtags
            - Make it engaging and shareable
            - Number each tweet (1/n format)
            
            Format as:
            TWEET 1: [content]
            TWEET 2: [content]
            etc.',
            esc_html($content),
            esc_html($tone)
        );
    }

    /**
     * Build LinkedIn prompt.
     *
     * @param array $params Parameters.
     * @return string Prompt.
     */
    private function build_linkedin_prompt($params) {
        $content = isset($params['content']) ? $params['content'] : '';
        $tone = isset($params['tone']) ? $params['tone'] : 'professional';

        return sprintf(
            'Create a LinkedIn post based on this content: %s
            
            Requirements:
            - Tone: %s
            - Professional and value-driven
            - Include a compelling hook
            - Add relevant hashtags
            - Include a call-to-action
            - 1300 characters maximum
            
            Format as a single LinkedIn post.',
            esc_html($content),
            esc_html($tone)
        );
    }

    /**
     * Build SEO cluster prompt.
     *
     * @param array $params Parameters.
     * @return string Prompt.
     */
    private function build_seo_cluster_prompt($params) {
        $pillar_topic = isset($params['pillar_topic']) ? $params['pillar_topic'] : '';

        return sprintf(
            'Create a comprehensive content cluster for the pillar topic: "%s"
            
            Generate:
            1. 1 pillar page topic (main comprehensive guide)
            2. 8-12 cluster topics (supporting articles)
            3. Brief description for each topic
            4. Suggested internal linking strategy
            
            Format as JSON:
            {
                "pillar": {
                    "title": "...",
                    "description": "..."
                },
                "clusters": [
                    {
                        "title": "...",
                        "description": "...",
                        "keywords": ["...", "..."]
                    }
                ]
            }',
            esc_html($pillar_topic)
        );
    }

    /**
     * Build competitor analysis prompt.
     *
     * @param array $params Parameters.
     * @return string Prompt.
     */
    private function build_competitor_analysis_prompt($params) {
        $url = isset($params['url']) ? $params['url'] : '';
        $content = isset($params['content']) ? $params['content'] : '';

        return sprintf(
            'Analyze this competitor content from %s:
            
            Content: %s
            
            Provide:
            1. Content structure analysis
            2. Key strengths and weaknesses
            3. Missing topics/opportunities
            4. Suggested improvements
            5. Content gaps we can exploit
            
            Format as JSON with clear sections.',
            esc_url($url),
            esc_html(substr($content, 0, 2000)) // Limit content length
        );
    }

    /**
     * Build design analysis prompt.
     *
     * @param array $params Parameters.
     * @return string Prompt.
     */
    private function build_design_analysis_prompt($params) {
        $site_url = isset($params['site_url']) ? $params['site_url'] : home_url();

        return sprintf(
            'Analyze the design and user experience of this website: %s
            
            Based on modern web design principles, provide:
            1. First impression assessment
            2. User experience evaluation
            3. Visual hierarchy analysis
            4. Color scheme feedback
            5. Typography assessment
            6. Specific improvement recommendations
            
            Format as structured JSON with actionable insights.',
            esc_url($site_url)
        );
    }

    /**
     * Build color palette prompt.
     *
     * @param array $params Parameters.
     * @return string Prompt.
     */
    private function build_color_palette_prompt($params) {
        $primary_color = isset($params['primary_color']) ? $params['primary_color'] : '';

        return sprintf(
            'Create a professional color palette based on this primary color: %s
            
            Generate:
            1. Primary color (given)
            2. Secondary color (complementary)
            3. Accent color
            4. Background colors (light/dark)
            5. Text colors
            6. Error/success/warning colors
            
            Ensure accessibility (WCAG AA compliance) and provide hex codes.
            
            Format as JSON:
            {
                "primary": "#...",
                "secondary": "#...",
                "accent": "#...",
                "background": {
                    "light": "#...",
                    "dark": "#..."
                },
                "text": {
                    "primary": "#...",
                    "secondary": "#..."
                },
                "status": {
                    "success": "#...",
                    "warning": "#...",
                    "error": "#..."
                }
            }',
            esc_html($primary_color)
        );
    }

    /**
     * Parse error response from API.
     *
     * @param string $response_body Response body.
     * @param int    $response_code Response code.
     * @return string Error message.
     */
    private function parse_error_response($response_body, $response_code) {
        $data = json_decode($response_body, true);
        
        if ($data && isset($data['error']['message'])) {
            return $data['error']['message'];
        }

        return sprintf(
            __('API request failed with status code: %d', 'gemini-command-center'),
            $response_code
        );
    }

    /**
     * Log API request.
     *
     * @param string $prompt The prompt sent.
     * @param array  $options Request options.
     */
    private function log_api_request($prompt, $options) {
        if (class_exists('Gemini_CC_Logger')) {
            $logger = new Gemini_CC_Logger();
            $logger->info('Gemini API request', array(
                'prompt_length' => strlen($prompt),
                'options' => $options,
                'user_id' => get_current_user_id(),
            ));
        }
    }

    /**
     * Log API success.
     *
     * @param string $response The response received.
     */
    private function log_api_success($response) {
        if (class_exists('Gemini_CC_Logger')) {
            $logger = new Gemini_CC_Logger();
            $logger->info('Gemini API success', array(
                'response_length' => strlen($response),
                'user_id' => get_current_user_id(),
            ));
        }
    }

    /**
     * Log API error.
     *
     * @param string $error_message Error message.
     */
    private function log_api_error($error_message) {
        if (class_exists('Gemini_CC_Logger')) {
            $logger = new Gemini_CC_Logger();
            $logger->error('Gemini API error', array(
                'error' => $error_message,
                'user_id' => get_current_user_id(),
            ));
        }
    }
}