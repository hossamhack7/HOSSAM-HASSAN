<?php
/**
 * SEO Manager class.
 * Handles SEO-related functionality.
 *
 * @package GeminiCommandCenter
 */

class Gemini_CC_SEO_Manager {

    /**
     * Constructor.
     */
    public function __construct() {
        // Initialize SEO features
    }

    /**
     * Run technical SEO audit.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response
     */
    public function run_technical_audit($request) {
        // Check for cached results
        $cache_key = 'gemini_cc_technical_audit';
        $cached_results = get_transient($cache_key);
        
        if ($cached_results !== false) {
            return new WP_REST_Response($cached_results, 200);
        }

        try {
            $audit_results = array(
                'sitemap' => $this->check_sitemap(),
                'robots_txt' => $this->check_robots_txt(),
                'broken_links' => $this->check_broken_links(),
                'meta_tags' => $this->check_meta_tags(),
                'headings' => $this->check_heading_structure(),
                'images' => $this->check_image_optimization(),
                'page_speed' => $this->check_basic_performance(),
            );

            $overall_score = $this->calculate_seo_score($audit_results);
            
            $results = array(
                'success' => true,
                'overall_score' => $overall_score,
                'audit_results' => $audit_results,
                'timestamp' => current_time('c'),
            );

            // Cache results for 24 hours
            set_transient($cache_key, $results, 24 * HOUR_IN_SECONDS);
            
            return new WP_REST_Response($results, 200);
            
        } catch (Exception $e) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => sprintf(
                    __('Technical audit failed: %s', 'gemini-command-center'),
                    $e->getMessage()
                ),
            ), 500);
        }
    }

    /**
     * Generate content cluster for topical authority.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response
     */
    public function generate_content_cluster($request) {
        $pillar_topic = $request->get_param('pillar_topic');
        
        if (empty($pillar_topic)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Pillar topic is required.', 'gemini-command-center'),
            ), 400);
        }

        try {
            require_once GEMINI_CC_INCLUDES_DIR . 'class-gemini-api.php';
            $gemini_api = new Gemini_CC_API();
            
            $cluster_data = $gemini_api->generate_structured_content('seo_cluster', array(
                'pillar_topic' => $pillar_topic,
            ));

            if (is_wp_error($cluster_data)) {
                throw new Exception($cluster_data->get_error_message());
            }

            $cluster_json = json_decode($cluster_data, true);
            
            if (!$cluster_json) {
                throw new Exception(__('Invalid response from AI.', 'gemini-command-center'));
            }

            return new WP_REST_Response(array(
                'success' => true,
                'cluster' => $cluster_json,
            ), 200);
            
        } catch (Exception $e) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => sprintf(
                    __('Content cluster generation failed: %s', 'gemini-command-center'),
                    $e->getMessage()
                ),
            ), 500);
        }
    }

    /**
     * Analyze competitor.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response
     */
    public function analyze_competitor($request) {
        $url = $request->get_param('url');
        
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Valid URL is required.', 'gemini-command-center'),
            ), 400);
        }

        try {
            // Fetch competitor content
            $content = $this->fetch_url_content($url);
            
            require_once GEMINI_CC_INCLUDES_DIR . 'class-gemini-api.php';
            $gemini_api = new Gemini_CC_API();
            
            $analysis = $gemini_api->generate_structured_content('competitor_analysis', array(
                'url' => $url,
                'content' => $content,
            ));

            if (is_wp_error($analysis)) {
                throw new Exception($analysis->get_error_message());
            }

            $analysis_json = json_decode($analysis, true);
            
            if (!$analysis_json) {
                throw new Exception(__('Invalid response from AI.', 'gemini-command-center'));
            }

            return new WP_REST_Response(array(
                'success' => true,
                'analysis' => $analysis_json,
                'url' => $url,
            ), 200);
            
        } catch (Exception $e) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => sprintf(
                    __('Competitor analysis failed: %s', 'gemini-command-center'),
                    $e->getMessage()
                ),
            ), 500);
        }
    }

    /**
     * Get orphan pages (posts with no internal links).
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response
     */
    public function get_orphan_pages($request) {
        global $wpdb;

        try {
            // Get all published posts/pages
            $posts = $wpdb->get_results("
                SELECT ID, post_title, post_type, post_date 
                FROM {$wpdb->posts} 
                WHERE post_status = 'publish' 
                AND post_type IN ('post', 'page')
                ORDER BY post_date DESC
            ");

            $orphan_pages = array();
            $internal_domain = parse_url(home_url(), PHP_URL_HOST);

            foreach ($posts as $post) {
                $post_url = get_permalink($post->ID);
                $link_count = $this->count_internal_links_to_post($post_url, $internal_domain);
                
                if ($link_count === 0) {
                    $orphan_pages[] = array(
                        'id' => $post->ID,
                        'title' => $post->post_title,
                        'type' => $post->post_type,
                        'url' => $post_url,
                        'date' => $post->post_date,
                        'suggested_sources' => $this->suggest_link_sources($post->ID),
                    );
                }
            }

            return new WP_REST_Response(array(
                'success' => true,
                'orphan_pages' => $orphan_pages,
                'total_count' => count($orphan_pages),
            ), 200);
            
        } catch (Exception $e) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => sprintf(
                    __('Failed to get orphan pages: %s', 'gemini-command-center'),
                    $e->getMessage()
                ),
            ), 500);
        }
    }

    /**
     * Create internal link.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response
     */
    public function create_internal_link($request) {
        $source_post_id = $request->get_param('source_post_id');
        $target_post_id = $request->get_param('target_post_id');
        $anchor_text = $request->get_param('anchor_text');

        if (!$source_post_id || !$target_post_id || !$anchor_text) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Source post, target post, and anchor text are required.', 'gemini-command-center'),
            ), 400);
        }

        try {
            $source_post = get_post($source_post_id);
            $target_url = get_permalink($target_post_id);
            
            if (!$source_post || !$target_url) {
                throw new Exception(__('Invalid post IDs.', 'gemini-command-center'));
            }

            // Add link to content
            $updated_content = $this->add_internal_link_to_content(
                $source_post->post_content,
                $anchor_text,
                $target_url
            );

            // Update post
            $update_result = wp_update_post(array(
                'ID' => $source_post_id,
                'post_content' => $updated_content,
            ));

            if (is_wp_error($update_result)) {
                throw new Exception($update_result->get_error_message());
            }

            return new WP_REST_Response(array(
                'success' => true,
                'message' => __('Internal link created successfully.', 'gemini-command-center'),
                'source_post_id' => $source_post_id,
                'target_post_id' => $target_post_id,
            ), 200);
            
        } catch (Exception $e) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => sprintf(
                    __('Failed to create internal link: %s', 'gemini-command-center'),
                    $e->getMessage()
                ),
            ), 500);
        }
    }

    /**
     * Check sitemap status.
     *
     * @return array Sitemap check results.
     */
    private function check_sitemap() {
        $sitemap_urls = array(
            home_url('/sitemap.xml'),
            home_url('/sitemap_index.xml'),
            home_url('/wp-sitemap.xml'),
        );

        foreach ($sitemap_urls as $url) {
            $response = wp_remote_get($url);
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                return array(
                    'status' => 'good',
                    'message' => __('XML sitemap found', 'gemini-command-center'),
                    'url' => $url,
                );
            }
        }

        return array(
            'status' => 'warning',
            'message' => __('No XML sitemap found', 'gemini-command-center'),
        );
    }

    /**
     * Check robots.txt file.
     *
     * @return array Robots.txt check results.
     */
    private function check_robots_txt() {
        $robots_url = home_url('/robots.txt');
        $response = wp_remote_get($robots_url);
        
        if (is_wp_error($response)) {
            return array(
                'status' => 'warning',
                'message' => __('Could not access robots.txt', 'gemini-command-center'),
            );
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $content = wp_remote_retrieve_body($response);

        if ($status_code !== 200) {
            return array(
                'status' => 'warning',
                'message' => __('robots.txt not found', 'gemini-command-center'),
            );
        }

        $has_sitemap = strpos($content, 'Sitemap:') !== false;
        
        return array(
            'status' => $has_sitemap ? 'good' : 'warning',
            'message' => $has_sitemap ? 
                __('robots.txt found with sitemap reference', 'gemini-command-center') :
                __('robots.txt found but no sitemap reference', 'gemini-command-center'),
            'content' => $content,
        );
    }

    /**
     * Check for broken links (simplified version).
     *
     * @return array Broken links check results.
     */
    private function check_broken_links() {
        // This is a simplified implementation
        // In a full implementation, you would crawl and check links
        return array(
            'status' => 'info',
            'message' => __('Broken link check requires full crawl', 'gemini-command-center'),
            'broken_links' => array(),
        );
    }

    /**
     * Check meta tags.
     *
     * @return array Meta tags check results.
     */
    private function check_meta_tags() {
        $home_content = wp_remote_get(home_url());
        
        if (is_wp_error($home_content)) {
            return array(
                'status' => 'warning',
                'message' => __('Could not check meta tags', 'gemini-command-center'),
            );
        }

        $html = wp_remote_retrieve_body($home_content);
        $has_title = strpos($html, '<title>') !== false;
        $has_description = strpos($html, 'name="description"') !== false;
        
        $status = ($has_title && $has_description) ? 'good' : 'warning';
        
        return array(
            'status' => $status,
            'message' => sprintf(
                __('Title: %s, Description: %s', 'gemini-command-center'),
                $has_title ? __('Found', 'gemini-command-center') : __('Missing', 'gemini-command-center'),
                $has_description ? __('Found', 'gemini-command-center') : __('Missing', 'gemini-command-center')
            ),
            'has_title' => $has_title,
            'has_description' => $has_description,
        );
    }

    /**
     * Check heading structure.
     *
     * @return array Heading structure check results.
     */
    private function check_heading_structure() {
        // Simplified implementation
        return array(
            'status' => 'info',
            'message' => __('Heading structure check requires content analysis', 'gemini-command-center'),
        );
    }

    /**
     * Check image optimization.
     *
     * @return array Image optimization check results.
     */
    private function check_image_optimization() {
        // Count images without alt text
        global $wpdb;
        
        $images_without_alt = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_mime_type LIKE 'image/%'
            AND post_excerpt = ''
        ");

        $total_images = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_mime_type LIKE 'image/%'
        ");

        $status = $images_without_alt > 0 ? 'warning' : 'good';
        
        return array(
            'status' => $status,
            'message' => sprintf(
                __('%d of %d images missing alt text', 'gemini-command-center'),
                $images_without_alt,
                $total_images
            ),
            'missing_alt' => $images_without_alt,
            'total_images' => $total_images,
        );
    }

    /**
     * Check basic performance metrics.
     *
     * @return array Performance check results.
     */
    private function check_basic_performance() {
        $start_time = microtime(true);
        $response = wp_remote_get(home_url());
        $load_time = microtime(true) - $start_time;
        
        $status = 'good';
        if ($load_time > 3) {
            $status = 'critical';
        } elseif ($load_time > 1) {
            $status = 'warning';
        }
        
        return array(
            'status' => $status,
            'message' => sprintf(
                __('Page load time: %.2f seconds', 'gemini-command-center'),
                $load_time
            ),
            'load_time' => $load_time,
        );
    }

    /**
     * Calculate overall SEO score.
     *
     * @param array $audit_results Audit results.
     * @return int SEO score (0-100).
     */
    private function calculate_seo_score($audit_results) {
        $score = 0;
        $total_checks = 0;
        
        foreach ($audit_results as $check) {
            $total_checks++;
            switch ($check['status']) {
                case 'good':
                    $score += 100;
                    break;
                case 'warning':
                    $score += 50;
                    break;
                case 'critical':
                    $score += 0;
                    break;
                default:
                    $score += 70; // info
                    break;
            }
        }
        
        return $total_checks > 0 ? round($score / $total_checks) : 0;
    }

    /**
     * Fetch content from URL.
     *
     * @param string $url URL to fetch.
     * @return string URL content.
     */
    private function fetch_url_content($url) {
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'user-agent' => 'Gemini Command Center SEO Analyzer',
        ));

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        return wp_remote_retrieve_body($response);
    }

    /**
     * Count internal links to a specific post.
     *
     * @param string $post_url Post URL.
     * @param string $domain   Internal domain.
     * @return int Number of internal links.
     */
    private function count_internal_links_to_post($post_url, $domain) {
        global $wpdb;
        
        // This is a simplified implementation
        // In a full version, you would parse all post content for links
        $link_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_content LIKE %s 
            AND post_status = 'publish'
        ", '%' . $wpdb->esc_like($post_url) . '%'));
        
        return (int) $link_count;
    }

    /**
     * Suggest link sources for orphan page.
     *
     * @param int $post_id Post ID.
     * @return array Suggested source posts.
     */
    private function suggest_link_sources($post_id) {
        $post = get_post($post_id);
        $post_categories = wp_get_post_categories($post_id);
        
        // Get related posts by category
        $related_posts = get_posts(array(
            'category__in' => $post_categories,
            'exclude' => array($post_id),
            'numberposts' => 5,
            'post_status' => 'publish',
        ));

        $suggestions = array();
        foreach ($related_posts as $related_post) {
            $suggestions[] = array(
                'id' => $related_post->ID,
                'title' => $related_post->post_title,
                'url' => get_permalink($related_post->ID),
                'reason' => __('Same category', 'gemini-command-center'),
            );
        }

        return $suggestions;
    }

    /**
     * Add internal link to post content.
     *
     * @param string $content     Post content.
     * @param string $anchor_text Anchor text.
     * @param string $target_url  Target URL.
     * @return string Updated content.
     */
    private function add_internal_link_to_content($content, $anchor_text, $target_url) {
        // Find first occurrence of anchor text and replace with link
        $link = sprintf('<a href="%s">%s</a>', esc_url($target_url), esc_html($anchor_text));
        $updated_content = preg_replace('/\b' . preg_quote($anchor_text, '/') . '\b/', $link, $content, 1);
        
        return $updated_content;
    }
}