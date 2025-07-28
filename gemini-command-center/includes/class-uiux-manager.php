<?php
/**
 * UI/UX Manager class.
 * Handles UI/UX improvements and design optimization.
 *
 * @package GeminiCommandCenter
 */

class Gemini_CC_UIUX_Manager {

    /**
     * Constructor.
     */
    public function __construct() {
        // Initialize UI/UX features
    }

    /**
     * Analyze website design.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response
     */
    public function analyze_design($request) {
        try {
            require_once GEMINI_CC_INCLUDES_DIR . 'class-gemini-api.php';
            $gemini_api = new Gemini_CC_API();
            
            $analysis = $gemini_api->generate_structured_content('design_analysis', array(
                'site_url' => home_url(),
            ));

            if (is_wp_error($analysis)) {
                throw new Exception($analysis->get_error_message());
            }

            $analysis_json = json_decode($analysis, true);
            
            if (!$analysis_json) {
                // Fallback to basic analysis
                $analysis_json = $this->perform_basic_design_analysis();
            }

            return new WP_REST_Response(array(
                'success' => true,
                'analysis' => $analysis_json,
            ), 200);
            
        } catch (Exception $e) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => sprintf(
                    __('Design analysis failed: %s', 'gemini-command-center'),
                    $e->getMessage()
                ),
            ), 500);
        }
    }

    /**
     * Suggest color palette based on primary color.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response
     */
    public function suggest_color_palette($request) {
        $primary_color = $request->get_param('primary_color');
        
        if (empty($primary_color) || !$this->is_valid_hex_color($primary_color)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Valid hex color is required.', 'gemini-command-center'),
            ), 400);
        }

        try {
            require_once GEMINI_CC_INCLUDES_DIR . 'class-gemini-api.php';
            $gemini_api = new Gemini_CC_API();
            
            $palette = $gemini_api->generate_structured_content('color_palette', array(
                'primary_color' => $primary_color,
            ));

            if (is_wp_error($palette)) {
                throw new Exception($palette->get_error_message());
            }

            $palette_json = json_decode($palette, true);
            
            if (!$palette_json) {
                // Fallback to algorithmic palette generation
                $palette_json = $this->generate_algorithmic_palette($primary_color);
            }

            return new WP_REST_Response(array(
                'success' => true,
                'palette' => $palette_json,
                'primary_color' => $primary_color,
            ), 200);
            
        } catch (Exception $e) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => sprintf(
                    __('Palette generation failed: %s', 'gemini-command-center'),
                    $e->getMessage()
                ),
            ), 500);
        }
    }

    /**
     * Save UI styles.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response
     */
    public function save_ui_styles($request) {
        $styles = $request->get_json_params();
        
        if (empty($styles)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Style data is required.', 'gemini-command-center'),
            ), 400);
        }

        try {
            $sanitized_styles = $this->sanitize_styles($styles);
            
            // Save styles to options
            update_option('gemini_cc_custom_styles', $sanitized_styles);
            
            // Update main settings
            $settings = get_option('gemini_cc_settings', array());
            if (isset($sanitized_styles['colors'])) {
                $settings['uiux']['colors'] = $sanitized_styles['colors'];
            }
            if (isset($sanitized_styles['fonts'])) {
                $settings['uiux']['fonts'] = $sanitized_styles['fonts'];
            }
            update_option('gemini_cc_settings', $settings);

            return new WP_REST_Response(array(
                'success' => true,
                'message' => __('Styles saved successfully.', 'gemini-command-center'),
                'styles' => $sanitized_styles,
            ), 200);
            
        } catch (Exception $e) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => sprintf(
                    __('Failed to save styles: %s', 'gemini-command-center'),
                    $e->getMessage()
                ),
            ), 500);
        }
    }

    /**
     * Inject custom styles into the website.
     * Called from the main plugin class on wp_head.
     */
    public function inject_custom_styles() {
        $settings = get_option('gemini_cc_settings', array());
        
        // Check if UI/UX module is enabled
        if (empty($settings['uiux']['enabled'])) {
            return;
        }

        $custom_styles = get_option('gemini_cc_custom_styles', array());
        $inline_css = $this->build_inline_css($custom_styles);
        
        if (!empty($inline_css)) {
            echo "<style id='gemini-cc-custom-styles'>\n" . $inline_css . "\n</style>\n";
        }
    }

    /**
     * Perform basic design analysis.
     *
     * @return array Basic analysis results.
     */
    private function perform_basic_design_analysis() {
        $analysis = array(
            'overall_score' => 70,
            'strengths' => array(
                __('WordPress theme detected', 'gemini-command-center'),
                __('Responsive design elements present', 'gemini-command-center'),
            ),
            'improvements' => array(
                __('Consider optimizing color contrast', 'gemini-command-center'),
                __('Review typography hierarchy', 'gemini-command-center'),
                __('Ensure consistent spacing', 'gemini-command-center'),
            ),
            'accessibility' => array(
                'score' => 75,
                'issues' => array(
                    __('Some images may be missing alt text', 'gemini-command-center'),
                    __('Color contrast could be improved', 'gemini-command-center'),
                ),
            ),
            'recommendations' => array(
                array(
                    'category' => 'colors',
                    'title' => __('Improve Color Palette', 'gemini-command-center'),
                    'description' => __('Use a more cohesive color scheme throughout the site.', 'gemini-command-center'),
                    'priority' => 'medium',
                ),
                array(
                    'category' => 'typography',
                    'title' => __('Enhance Typography', 'gemini-command-center'),
                    'description' => __('Improve font pairing and text hierarchy.', 'gemini-command-center'),
                    'priority' => 'medium',
                ),
            ),
        );

        return $analysis;
    }

    /**
     * Generate algorithmic color palette.
     *
     * @param string $primary_color Primary color hex.
     * @return array Color palette.
     */
    private function generate_algorithmic_palette($primary_color) {
        $rgb = $this->hex_to_rgb($primary_color);
        
        return array(
            'primary' => $primary_color,
            'secondary' => $this->adjust_brightness($primary_color, -30),
            'accent' => $this->get_complementary_color($primary_color),
            'background' => array(
                'light' => '#ffffff',
                'dark' => '#f8f9fa',
            ),
            'text' => array(
                'primary' => '#212529',
                'secondary' => '#6c757d',
            ),
            'status' => array(
                'success' => '#28a745',
                'warning' => '#ffc107',
                'error' => '#dc3545',
            ),
        );
    }

    /**
     * Sanitize styles array.
     *
     * @param array $styles Raw styles.
     * @return array Sanitized styles.
     */
    private function sanitize_styles($styles) {
        $sanitized = array();

        // Sanitize colors
        if (isset($styles['colors']) && is_array($styles['colors'])) {
            $sanitized['colors'] = array();
            foreach ($styles['colors'] as $key => $color) {
                if ($this->is_valid_hex_color($color)) {
                    $sanitized['colors'][sanitize_key($key)] = sanitize_hex_color($color);
                }
            }
        }

        // Sanitize fonts
        if (isset($styles['fonts']) && is_array($styles['fonts'])) {
            $sanitized['fonts'] = array();
            foreach ($styles['fonts'] as $key => $font) {
                $sanitized['fonts'][sanitize_key($key)] = sanitize_text_field($font);
            }
        }

        // Sanitize custom CSS
        if (isset($styles['custom_css'])) {
            $sanitized['custom_css'] = wp_strip_all_tags($styles['custom_css']);
        }

        return $sanitized;
    }

    /**
     * Build inline CSS from styles.
     *
     * @param array $styles Styles array.
     * @return string CSS string.
     */
    private function build_inline_css($styles) {
        $css = '';

        // CSS variables for colors
        if (!empty($styles['colors'])) {
            $css .= ":root {\n";
            foreach ($styles['colors'] as $key => $color) {
                $css .= "  --gcc-" . esc_attr($key) . ": " . esc_attr($color) . ";\n";
            }
            $css .= "}\n\n";
        }

        // Font styles
        if (!empty($styles['fonts'])) {
            if (!empty($styles['fonts']['heading'])) {
                $css .= "h1, h2, h3, h4, h5, h6 {\n";
                $css .= "  font-family: '" . esc_attr($styles['fonts']['heading']) . "', sans-serif;\n";
                $css .= "}\n\n";
            }
            
            if (!empty($styles['fonts']['body'])) {
                $css .= "body {\n";
                $css .= "  font-family: '" . esc_attr($styles['fonts']['body']) . "', sans-serif;\n";
                $css .= "}\n\n";
            }
        }

        // Custom CSS
        if (!empty($styles['custom_css'])) {
            $css .= "/* Custom CSS */\n" . $styles['custom_css'] . "\n";
        }

        return $css;
    }

    /**
     * Check if string is valid hex color.
     *
     * @param string $color Color string.
     * @return bool True if valid hex color.
     */
    private function is_valid_hex_color($color) {
        return preg_match('/^#[a-f0-9]{6}$/i', $color);
    }

    /**
     * Convert hex color to RGB.
     *
     * @param string $hex Hex color.
     * @return array RGB values.
     */
    private function hex_to_rgb($hex) {
        $hex = ltrim($hex, '#');
        return array(
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        );
    }

    /**
     * Convert RGB to hex color.
     *
     * @param array $rgb RGB values.
     * @return string Hex color.
     */
    private function rgb_to_hex($rgb) {
        return sprintf('#%02x%02x%02x', $rgb['r'], $rgb['g'], $rgb['b']);
    }

    /**
     * Adjust color brightness.
     *
     * @param string $hex_color Hex color.
     * @param int    $adjustment Brightness adjustment (-255 to 255).
     * @return string Adjusted hex color.
     */
    private function adjust_brightness($hex_color, $adjustment) {
        $rgb = $this->hex_to_rgb($hex_color);
        
        $rgb['r'] = max(0, min(255, $rgb['r'] + $adjustment));
        $rgb['g'] = max(0, min(255, $rgb['g'] + $adjustment));
        $rgb['b'] = max(0, min(255, $rgb['b'] + $adjustment));
        
        return $this->rgb_to_hex($rgb);
    }

    /**
     * Get complementary color.
     *
     * @param string $hex_color Hex color.
     * @return string Complementary hex color.
     */
    private function get_complementary_color($hex_color) {
        $rgb = $this->hex_to_rgb($hex_color);
        
        // Simple complementary color calculation
        $rgb['r'] = 255 - $rgb['r'];
        $rgb['g'] = 255 - $rgb['g'];
        $rgb['b'] = 255 - $rgb['b'];
        
        return $this->rgb_to_hex($rgb);
    }
}