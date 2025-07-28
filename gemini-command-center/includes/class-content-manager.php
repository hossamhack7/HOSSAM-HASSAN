<?php
/**
 * Content Manager class.
 * Handles content creation and optimization.
 *
 * @package GeminiCommandCenter
 */

class Gemini_CC_Content_Manager {

    /**
     * Constructor.
     */
    public function __construct() {
        // Initialize content features
        add_filter('the_title', array($this, 'filter_ab_test_title'), 10, 2);
    }

    /**
     * Generate article with AI.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response
     */
    public function generate_article($request) {
        $topic = $request->get_param('topic');
        $keywords = $request->get_param('keywords');
        $tone = $request->get_param('tone');

        if (empty($topic)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Topic is required.', 'gemini-command-center'),
            ), 400);
        }

        try {
            require_once GEMINI_CC_INCLUDES_DIR . 'class-gemini-api.php';
            $gemini_api = new Gemini_CC_API();
            
            $article_content = $gemini_api->generate_structured_content('article', array(
                'topic' => $topic,
                'keywords' => $keywords,
                'tone' => $tone,
            ));

            if (is_wp_error($article_content)) {
                throw new Exception($article_content->get_error_message());
            }

            // Parse the structured response
            $parsed_content = $this->parse_article_response($article_content);

            return new WP_REST_Response(array(
                'success' => true,
                'content' => $parsed_content,
            ), 200);
            
        } catch (Exception $e) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => sprintf(
                    __('Article generation failed: %s', 'gemini-command-center'),
                    $e->getMessage()
                ),
            ), 500);
        }
    }

    /**
     * Save content as draft.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response
     */
    public function save_content_draft($request) {
        $title = $request->get_param('title');
        $content = $request->get_param('content');

        if (empty($title) || empty($content)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Title and content are required.', 'gemini-command-center'),
            ), 400);
        }

        try {
            $post_id = wp_insert_post(array(
                'post_title' => sanitize_text_field($title),
                'post_content' => wp_kses_post($content),
                'post_status' => 'draft',
                'post_author' => get_current_user_id(),
                'meta_input' => array(
                    'gemini_cc_generated' => true,
                    'gemini_cc_generated_at' => current_time('c'),
                ),
            ));

            if (is_wp_error($post_id)) {
                throw new Exception($post_id->get_error_message());
            }

            return new WP_REST_Response(array(
                'success' => true,
                'message' => __('Draft saved successfully.', 'gemini-command-center'),
                'post_id' => $post_id,
                'edit_url' => admin_url('post.php?post=' . $post_id . '&action=edit'),
            ), 200);
            
        } catch (Exception $e) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => sprintf(
                    __('Failed to save draft: %s', 'gemini-command-center'),
                    $e->getMessage()
                ),
            ), 500);
        }
    }

    /**
     * Start A/B test for post title.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response
     */
    public function start_ab_test($request) {
        $post_id = $request->get_param('post_id');
        $title_a = $request->get_param('title_a');
        $title_b = $request->get_param('title_b');

        if (!$post_id || empty($title_a) || empty($title_b)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Post ID and both titles are required.', 'gemini-command-center'),
            ), 400);
        }

        try {
            global $wpdb;
            
            // Insert A/B test record
            $test_id = $wpdb->insert(
                GEMINI_CC_AB_TESTS_TABLE,
                array(
                    'post_id' => $post_id,
                    'title_a' => sanitize_text_field($title_a),
                    'title_b' => sanitize_text_field($title_b),
                    'status' => 'active',
                    'created_by' => get_current_user_id(),
                ),
                array('%d', '%s', '%s', '%s', '%d')
            );

            if ($test_id === false) {
                throw new Exception(__('Failed to create A/B test record.', 'gemini-command-center'));
            }

            return new WP_REST_Response(array(
                'success' => true,
                'message' => __('A/B test started successfully.', 'gemini-command-center'),
                'test_id' => $wpdb->insert_id,
            ), 200);
            
        } catch (Exception $e) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => sprintf(
                    __('Failed to start A/B test: %s', 'gemini-command-center'),
                    $e->getMessage()
                ),
            ), 500);
        }
    }

    /**
     * Amplify content for social media.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response
     */
    public function amplify_content($request) {
        $post_id = $request->get_param('post_id');
        $platform = $request->get_param('platform');

        if (!$post_id || !$platform) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Post ID and platform are required.', 'gemini-command-center'),
            ), 400);
        }

        $allowed_platforms = array('twitter', 'linkedin', 'newsletter');
        if (!in_array($platform, $allowed_platforms, true)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Invalid platform specified.', 'gemini-command-center'),
            ), 400);
        }

        try {
            $post = get_post($post_id);
            if (!$post) {
                throw new Exception(__('Post not found.', 'gemini-command-center'));
            }

            require_once GEMINI_CC_INCLUDES_DIR . 'class-gemini-api.php';
            $gemini_api = new Gemini_CC_API();
            
            $social_content = $gemini_api->generate_structured_content('social_' . $platform, array(
                'content' => $post->post_content,
                'title' => $post->post_title,
                'tone' => $this->get_platform_tone($platform),
            ));

            if (is_wp_error($social_content)) {
                throw new Exception($social_content->get_error_message());
            }

            return new WP_REST_Response(array(
                'success' => true,
                'platform' => $platform,
                'content' => $social_content,
            ), 200);
            
        } catch (Exception $e) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => sprintf(
                    __('Content amplification failed: %s', 'gemini-command-center'),
                    $e->getMessage()
                ),
            ), 500);
        }
    }

    /**
     * Filter post title for A/B testing.
     *
     * @param string $title Post title.
     * @param int    $id    Post ID.
     * @return string Filtered title.
     */
    public function filter_ab_test_title($title, $id) {
        if (is_admin() || !is_main_query()) {
            return $title;
        }

        global $wpdb;
        
        // Check if post has active A/B test
        $ab_test = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM " . GEMINI_CC_AB_TESTS_TABLE . " 
            WHERE post_id = %d AND status = 'active'
        ", $id));

        if (!$ab_test) {
            return $title;
        }

        // Determine which variant to show
        $variant = $this->get_ab_test_variant($id);
        
        if ($variant === 'B') {
            // Track impression
            $this->track_ab_impression($ab_test->id, 'B');
            return $ab_test->title_b;
        } else {
            // Track impression
            $this->track_ab_impression($ab_test->id, 'A');
            return $ab_test->title_a;
        }
    }

    /**
     * Parse article response from AI.
     *
     * @param string $response AI response.
     * @return array Parsed content.
     */
    private function parse_article_response($response) {
        $content = array(
            'title' => '',
            'meta_description' => '',
            'content' => '',
        );

        // Parse the structured response
        if (preg_match('/TITLE:\s*(.+)/i', $response, $title_match)) {
            $content['title'] = trim($title_match[1]);
        }

        if (preg_match('/META_DESCRIPTION:\s*(.+)/i', $response, $meta_match)) {
            $content['meta_description'] = trim($meta_match[1]);
        }

        if (preg_match('/CONTENT:\s*(.*)/is', $response, $content_match)) {
            $content['content'] = trim($content_match[1]);
        }

        return $content;
    }

    /**
     * Get platform-specific tone.
     *
     * @param string $platform Platform name.
     * @return string Platform tone.
     */
    private function get_platform_tone($platform) {
        $settings = get_option('gemini_cc_settings', array());
        
        switch ($platform) {
            case 'twitter':
                return isset($settings['content']['social_amplifier']['twitter_tone']) ? 
                    $settings['content']['social_amplifier']['twitter_tone'] : 'engaging';
            case 'linkedin':
                return isset($settings['content']['social_amplifier']['linkedin_tone']) ? 
                    $settings['content']['social_amplifier']['linkedin_tone'] : 'professional';
            default:
                return 'professional';
        }
    }

    /**
     * Get A/B test variant for user.
     *
     * @param int $post_id Post ID.
     * @return string Variant ('A' or 'B').
     */
    private function get_ab_test_variant($post_id) {
        $cookie_name = 'gemini_cc_ab_' . $post_id;
        
        if (isset($_COOKIE[$cookie_name])) {
            return $_COOKIE[$cookie_name];
        }

        // Assign random variant
        $variant = (rand(0, 1) === 0) ? 'A' : 'B';
        
        // Set cookie for 30 days
        setcookie($cookie_name, $variant, time() + (30 * 24 * 60 * 60), '/');
        
        return $variant;
    }

    /**
     * Track A/B test impression.
     *
     * @param int    $test_id Test ID.
     * @param string $variant Variant ('A' or 'B').
     */
    private function track_ab_impression($test_id, $variant) {
        global $wpdb;
        
        $field = $variant === 'A' ? 'impressions_a' : 'impressions_b';
        
        $wpdb->query($wpdb->prepare("
            UPDATE " . GEMINI_CC_AB_TESTS_TABLE . " 
            SET {$field} = {$field} + 1 
            WHERE id = %d
        ", $test_id));
    }
}