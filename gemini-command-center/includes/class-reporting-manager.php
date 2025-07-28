<?php
/**
 * Reporting Manager class.
 * Handles reporting and analytics functionality.
 *
 * @package GeminiCommandCenter
 */

class Gemini_CC_Reporting_Manager {

    /**
     * Constructor.
     */
    public function __construct() {
        // Initialize reporting features
    }

    /**
     * Get KPI summary.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response
     */
    public function get_kpi_summary($request) {
        try {
            $kpis = array(
                'website_metrics' => $this->get_website_metrics(),
                'plugin_metrics' => $this->get_plugin_metrics(),
                'seo_metrics' => $this->get_seo_metrics(),
                'content_metrics' => $this->get_content_metrics(),
                'performance_metrics' => $this->get_performance_metrics(),
            );

            return new WP_REST_Response(array(
                'success' => true,
                'kpis' => $kpis,
                'timestamp' => current_time('c'),
            ), 200);
            
        } catch (Exception $e) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => sprintf(
                    __('Failed to get KPI summary: %s', 'gemini-command-center'),
                    $e->getMessage()
                ),
            ), 500);
        }
    }

    /**
     * Get impact analysis.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response
     */
    public function get_impact_analysis($request) {
        try {
            $analysis = array(
                'ab_test_impact' => $this->analyze_ab_test_impact(),
                'seo_improvements' => $this->analyze_seo_improvements(),
                'content_performance' => $this->analyze_content_performance(),
                'site_health_trends' => $this->analyze_site_health_trends(),
            );

            return new WP_REST_Response(array(
                'success' => true,
                'analysis' => $analysis,
                'timestamp' => current_time('c'),
            ), 200);
            
        } catch (Exception $e) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => sprintf(
                    __('Failed to get impact analysis: %s', 'gemini-command-center'),
                    $e->getMessage()
                ),
            ), 500);
        }
    }

    /**
     * Get website metrics.
     *
     * @return array Website metrics.
     */
    private function get_website_metrics() {
        global $wpdb;

        $metrics = array();

        // Post count
        $post_counts = wp_count_posts();
        $metrics['total_posts'] = $post_counts->publish;
        $metrics['draft_posts'] = $post_counts->draft;

        // Page count
        $page_counts = wp_count_posts('page');
        $metrics['total_pages'] = $page_counts->publish;

        // Comment count
        $comment_counts = wp_count_comments();
        $metrics['total_comments'] = $comment_counts->approved;
        $metrics['pending_comments'] = $comment_counts->moderated;

        // User count
        $user_count = count_users();
        $metrics['total_users'] = $user_count['total_users'];

        // Media count
        $media_count = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_type = 'attachment'
        ");
        $metrics['total_media'] = (int) $media_count;

        return $metrics;
    }

    /**
     * Get plugin-specific metrics.
     *
     * @return array Plugin metrics.
     */
    private function get_plugin_metrics() {
        global $wpdb;

        $metrics = array();

        // AI-generated content
        $ai_generated = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = 'gemini_cc_generated' 
            AND meta_value = '1'
        ");
        $metrics['ai_generated_content'] = (int) $ai_generated;

        // Active A/B tests
        $active_tests = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM " . GEMINI_CC_AB_TESTS_TABLE . " 
            WHERE status = 'active'
        ");
        $metrics['active_ab_tests'] = (int) $active_tests;

        // Completed A/B tests
        $completed_tests = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM " . GEMINI_CC_AB_TESTS_TABLE . " 
            WHERE status = 'completed'
        ");
        $metrics['completed_ab_tests'] = (int) $completed_tests;

        // Backup count
        $backup_manager = new Gemini_CC_Backup_Manager();
        $backups = $backup_manager->list_backups(new WP_REST_Request());
        $metrics['total_backups'] = count($backups->data['backups']);

        // API usage (last 30 days)
        $api_usage = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM " . GEMINI_CC_LOGS_TABLE . " 
            WHERE message LIKE %s 
            AND created_at >= %s
        ", '%Gemini API%', date('Y-m-d H:i:s', strtotime('-30 days'))));
        $metrics['api_calls_30_days'] = (int) $api_usage;

        return $metrics;
    }

    /**
     * Get SEO metrics.
     *
     * @return array SEO metrics.
     */
    private function get_seo_metrics() {
        global $wpdb;

        $metrics = array();

        // Posts with meta descriptions
        $posts_with_meta = $wpdb->get_var("
            SELECT COUNT(DISTINCT p.ID) 
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_status = 'publish'
            AND p.post_type IN ('post', 'page')
            AND pm.meta_key = '_yoast_wpseo_metadesc'
            AND pm.meta_value != ''
        ");
        $metrics['posts_with_meta_descriptions'] = (int) $posts_with_meta;

        // Internal links created by plugin
        $internal_links = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = 'gemini_cc_internal_links_added'
        ");
        $metrics['internal_links_created'] = (int) $internal_links;

        // SEO audit score (cached)
        $audit_cache = get_transient('gemini_cc_technical_audit');
        if ($audit_cache && isset($audit_cache['overall_score'])) {
            $metrics['seo_audit_score'] = $audit_cache['overall_score'];
        } else {
            $metrics['seo_audit_score'] = null;
        }

        return $metrics;
    }

    /**
     * Get content metrics.
     *
     * @return array Content metrics.
     */
    private function get_content_metrics() {
        global $wpdb;

        $metrics = array();

        // Average post length
        $avg_length = $wpdb->get_var("
            SELECT AVG(CHAR_LENGTH(post_content)) 
            FROM {$wpdb->posts} 
            WHERE post_status = 'publish' 
            AND post_type = 'post'
            AND post_content != ''
        ");
        $metrics['average_post_length'] = round((float) $avg_length);

        // Posts published this month
        $posts_this_month = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_status = 'publish' 
            AND post_type = 'post'
            AND post_date >= %s
        ", date('Y-m-01')));
        $metrics['posts_this_month'] = (int) $posts_this_month;

        // Most popular post (by comment count)
        $popular_post = $wpdb->get_row("
            SELECT post_title, comment_count 
            FROM {$wpdb->posts} 
            WHERE post_status = 'publish' 
            AND post_type = 'post'
            ORDER BY comment_count DESC 
            LIMIT 1
        ");
        
        if ($popular_post) {
            $metrics['most_popular_post'] = array(
                'title' => $popular_post->post_title,
                'comments' => (int) $popular_post->comment_count,
            );
        }

        return $metrics;
    }

    /**
     * Get performance metrics.
     *
     * @return array Performance metrics.
     */
    private function get_performance_metrics() {
        $metrics = array();

        // Database size
        global $wpdb;
        $db_size = $wpdb->get_var("
            SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'DB Size in MB' 
            FROM information_schema.tables 
            WHERE table_schema = '" . DB_NAME . "'
        ");
        $metrics['database_size_mb'] = (float) $db_size;

        // Plugin memory usage
        $metrics['memory_usage'] = memory_get_usage(true);
        $metrics['memory_limit'] = ini_get('memory_limit');

        // Active plugins count
        $active_plugins = get_option('active_plugins', array());
        $metrics['active_plugins_count'] = count($active_plugins);

        // Theme name
        $current_theme = wp_get_theme();
        $metrics['current_theme'] = $current_theme->get('Name');

        return $metrics;
    }

    /**
     * Analyze A/B test impact.
     *
     * @return array A/B test analysis.
     */
    private function analyze_ab_test_impact() {
        global $wpdb;

        $analysis = array();

        // Get completed A/B tests
        $tests = $wpdb->get_results("
            SELECT * FROM " . GEMINI_CC_AB_TESTS_TABLE . " 
            WHERE status = 'completed' 
            ORDER BY ended_at DESC 
            LIMIT 10
        ");

        $total_improvement = 0;
        $winning_tests = 0;

        foreach ($tests as $test) {
            $ctr_a = $test->impressions_a > 0 ? ($test->clicks_a / $test->impressions_a) * 100 : 0;
            $ctr_b = $test->impressions_b > 0 ? ($test->clicks_b / $test->impressions_b) * 100 : 0;
            
            if ($ctr_b > $ctr_a) {
                $improvement = (($ctr_b - $ctr_a) / $ctr_a) * 100;
                $total_improvement += $improvement;
                $winning_tests++;
            }
        }

        $analysis['total_tests'] = count($tests);
        $analysis['winning_tests'] = $winning_tests;
        $analysis['average_improvement'] = $winning_tests > 0 ? $total_improvement / $winning_tests : 0;
        $analysis['success_rate'] = count($tests) > 0 ? ($winning_tests / count($tests)) * 100 : 0;

        return $analysis;
    }

    /**
     * Analyze SEO improvements.
     *
     * @return array SEO analysis.
     */
    private function analyze_seo_improvements() {
        $analysis = array();

        // Get historical SEO audit scores
        $historical_scores = get_option('gemini_cc_seo_history', array());
        
        if (count($historical_scores) >= 2) {
            $latest_score = end($historical_scores);
            $previous_score = prev($historical_scores);
            
            $analysis['score_change'] = $latest_score - $previous_score;
            $analysis['trend'] = $latest_score > $previous_score ? 'improving' : 'declining';
        } else {
            $analysis['score_change'] = 0;
            $analysis['trend'] = 'insufficient_data';
        }

        // Count SEO improvements made
        global $wpdb;
        $improvements = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM " . GEMINI_CC_LOGS_TABLE . " 
            WHERE message LIKE %s 
            AND created_at >= %s
        ", '%SEO%improvement%', date('Y-m-d H:i:s', strtotime('-30 days'))));
        
        $analysis['improvements_last_30_days'] = (int) $improvements;

        return $analysis;
    }

    /**
     * Analyze content performance.
     *
     * @return array Content analysis.
     */
    private function analyze_content_performance() {
        global $wpdb;

        $analysis = array();

        // Compare AI-generated vs manual content performance
        $ai_posts = $wpdb->get_results("
            SELECT p.ID, p.post_title, p.comment_count
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_status = 'publish'
            AND p.post_type = 'post'
            AND pm.meta_key = 'gemini_cc_generated'
            AND pm.meta_value = '1'
        ");

        $manual_posts = $wpdb->get_results("
            SELECT p.ID, p.post_title, p.comment_count
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'gemini_cc_generated'
            WHERE p.post_status = 'publish'
            AND p.post_type = 'post'
            AND pm.meta_value IS NULL
        ");

        $ai_avg_comments = 0;
        if (count($ai_posts) > 0) {
            $ai_total_comments = array_sum(array_column($ai_posts, 'comment_count'));
            $ai_avg_comments = $ai_total_comments / count($ai_posts);
        }

        $manual_avg_comments = 0;
        if (count($manual_posts) > 0) {
            $manual_total_comments = array_sum(array_column($manual_posts, 'comment_count'));
            $manual_avg_comments = $manual_total_comments / count($manual_posts);
        }

        $analysis['ai_generated_posts'] = count($ai_posts);
        $analysis['manual_posts'] = count($manual_posts);
        $analysis['ai_avg_engagement'] = $ai_avg_comments;
        $analysis['manual_avg_engagement'] = $manual_avg_comments;
        
        if ($manual_avg_comments > 0) {
            $analysis['ai_vs_manual_ratio'] = $ai_avg_comments / $manual_avg_comments;
        } else {
            $analysis['ai_vs_manual_ratio'] = 0;
        }

        return $analysis;
    }

    /**
     * Analyze site health trends.
     *
     * @return array Site health analysis.
     */
    private function analyze_site_health_trends() {
        $analysis = array();

        // Get historical health scores
        $health_history = get_option('gemini_cc_health_history', array());
        
        if (count($health_history) >= 2) {
            $latest = end($health_history);
            $previous = prev($health_history);
            
            $analysis['health_trend'] = $latest['score'] > $previous['score'] ? 'improving' : 'declining';
            $analysis['score_change'] = $latest['score'] - $previous['score'];
        } else {
            $analysis['health_trend'] = 'insufficient_data';
            $analysis['score_change'] = 0;
        }

        // Count critical issues resolved
        global $wpdb;
        $issues_resolved = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM " . GEMINI_CC_LOGS_TABLE . " 
            WHERE message LIKE %s 
            AND created_at >= %s
        ", '%resolved%critical%', date('Y-m-d H:i:s', strtotime('-30 days'))));
        
        $analysis['issues_resolved_30_days'] = (int) $issues_resolved;

        return $analysis;
    }
}